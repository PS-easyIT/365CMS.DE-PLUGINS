<?php
/**
 * CMS WordPress Importer – Admin-Klasse
 *
 * Registriert die Admin-Seite und verarbeitet Upload + AJAX-Import.
 * Unterstützt:
 * - Direkten Datei-Upload (AJAX + Sync-Fallback)
 * - Auswahl vorhandener Dateien aus uploads/import/
 * - Bilddownload per Slug-Ordner
 *
 * @package CMS_Importer
 * @since   1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Importer_Admin
{
    private static ?self $instance = null;

    /** Maximale Upload-Größe (50 MB) */
    private const MAX_UPLOAD_MB = 50;

    /** Erlaubte MIME-Typen für XML-Uploads */
    private const ALLOWED_MIMES = ['text/xml', 'application/xml', 'application/rss+xml'];

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    // ── Hook-Registrierung ────────────────────────────────────────────────────

    public function register_pages(): void
    {
        if (!class_exists('CMS\Hooks')) {
            return;
        }

        // Hauptmenü registrieren
        add_menu_page(
            'WordPress Importer',
            'WP Importer',
            'admin',
            'cms-importer',
            [$this, 'render_import_page'],
            '📥',
            80
        );

        // Untermenü: Import
        add_submenu_page(
            'cms-importer',
            'WordPress Importer',
            'Import',
            'admin',
            'cms-importer',
            [$this, 'render_import_page']
        );

        // Untermenü: Protokoll
        add_submenu_page(
            'cms-importer',
            'Import-Protokoll',
            'Protokoll',
            'admin',
            'cms-importer-log',
            [$this, 'render_log_page']
        );

        // AJAX-Handler
        CMS\Hooks::addAction('admin_ajax_cms_importer_upload',        [$this, 'handle_ajax_upload']);
        CMS\Hooks::addAction('admin_ajax_cms_importer_folder_import', [$this, 'handle_ajax_folder_import']);
        CMS\Hooks::addAction('admin_ajax_cms_importer_scan_folder',   [$this, 'handle_ajax_scan_folder']);
        CMS\Hooks::addAction('admin_ajax_cms_importer_download_report', [$this, 'handle_download_report']);

        // Import-Ordner sicherstellen
        $this->ensure_import_dir();
    }

    // ── Seiten-Renderer ───────────────────────────────────────────────────────

    public function render_import_page(): void
    {
        // ── AJAX-Dispatch: vor jedem HTML-Output abhandeln ────────────────────
        // Der CMS-Router gibt renderAdminLayoutStart() aus, bevor dieser Callback
        // aufgerufen wird. Deshalb müssen wir den Output-Buffer leeren und dann
        // direkt JSON senden.
        $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_ajax) {
            // Bereits gepufferten Admin-Layout-HTML verwerfen
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            $action = $_POST['cms_action'] ?? '';
            switch ($action) {
                case 'cms_importer_upload':
                    $this->handle_ajax_upload();
                    exit;
                case 'cms_importer_upload_only':
                    $this->handle_ajax_upload_only();
                    exit;
                case 'cms_importer_folder_import':
                    $this->handle_ajax_folder_import();
                    exit;
                case 'cms_importer_scan_folder':
                    $this->handle_ajax_scan_folder();
                    exit;
                default:
                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Unbekannte Aktion: ' . htmlspecialchars($action)]);
                    exit;
            }
        }

        // ── Synchrones POST (normales Formular ohne AJAX) ─────────────────────
        $message  = null;
        $msg_type = 'success';
        $result   = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            [$message, $msg_type, $result] = $this->process_upload_request();
        }

        // WICHTIG: createNonce() ist die korrekte Security-Methode (nicht generateNonce!)
        $security      = class_exists('CMS\Security') ? CMS\Security::instance() : null;
        $nonce         = $security ? $security->createNonce('cms-importer-upload') : '';
        $nonce_download = $security ? $security->createNonce('cms-importer-download') : '';

        $log_entries     = $this->get_recent_logs(5);
        $import_files    = $this->scan_import_folder();
        $import_dir_url  = defined('UPLOAD_URL') ? rtrim(UPLOAD_URL, '/') . '/import/' : '';

        include CMS_IMPORTER_PLUGIN_DIR . 'admin/page.php';
    }

    public function render_log_page(): void
    {
        $log_entries = $this->get_recent_logs(50);
        $security    = class_exists('CMS\Security') ? CMS\Security::instance() : null;
        $nonce       = $security ? $security->createNonce('cms-importer-upload') : '';
        include CMS_IMPORTER_PLUGIN_DIR . 'admin/log.php';
    }

    // ── AJAX: Datei-Upload ────────────────────────────────────────────────────

    public function handle_ajax_upload(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $security = class_exists('CMS\Security') ? CMS\Security::instance() : null;
        $nonce    = $_POST['_nonce'] ?? '';

        if ($security && !$security->verifyNonce($nonce, 'cms-importer-upload')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen.']);
            exit;
        }

        [$message, $type, $result] = $this->process_upload_request();

        echo json_encode($type === 'error'
            ? ['success' => false, 'error' => $message]
            : ['success' => true, 'message' => $message, 'result' => $result]);
        exit;
    }

    // ── AJAX: Import aus Ordner ────────────────────────────────────────────────

    public function handle_ajax_folder_import(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $security = class_exists('CMS\Security') ? CMS\Security::instance() : null;
        $nonce    = $_POST['_nonce'] ?? '';

        if ($security && !$security->verifyNonce($nonce, 'cms-importer-upload')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen.']);
            exit;
        }

        $raw_name = $_POST['import_file'] ?? '';
        if ($raw_name === '') {
            echo json_encode(['success' => false, 'error' => 'Kein Dateiname angegeben.']);
            exit;
        }

        // Nur Dateiname, kein Pfad-Traversal
        $filename  = basename($raw_name);
        $file_path = $this->get_import_dir() . $filename;

        if (!file_exists($file_path)) {
            echo json_encode(['success' => false, 'error' => 'Datei nicht gefunden: ' . htmlspecialchars($filename)]);
            exit;
        }

        [$message, $type, $result] = $this->process_xml_file($file_path, $filename);

        echo json_encode($type === 'error'
            ? ['success' => false, 'error' => $message]
            : ['success' => true, 'message' => $message, 'result' => $result]);
        exit;
    }

    // ── AJAX: Nur Upload (ohne Import) ────────────────────────────────────────

    public function handle_ajax_upload_only(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $security = class_exists('CMS\Security') ? CMS\Security::instance() : null;
        $nonce    = $_POST['_nonce'] ?? '';

        if ($security && !$security->verifyNonce($nonce, 'cms-importer-upload')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen.']);
            exit;
        }

        if (empty($_FILES['wxr_file'])) {
            echo json_encode(['success' => false, 'error' => 'Keine Datei hochgeladen.']);
            exit;
        }

        $file = $_FILES['wxr_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => $this->upload_error_message($file['error'])]);
            exit;
        }

        $max_bytes = self::MAX_UPLOAD_MB * 1048576;
        if ($file['size'] > $max_bytes) {
            echo json_encode(['success' => false, 'error' => 'Datei zu groß. Maximum: ' . self::MAX_UPLOAD_MB . ' MB']);
            exit;
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $detected = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'xml' && !in_array($detected, self::ALLOWED_MIMES, true)) {
            echo json_encode(['success' => false, 'error' => 'Ungültiger Dateityp. Nur XML-Dateien erlaubt.']);
            exit;
        }

        $import_dir = $this->get_import_dir();
        if ($import_dir === '') {
            echo json_encode(['success' => false, 'error' => 'Import-Verzeichnis nicht konfiguriert (UPLOAD_PATH fehlt).']);
            exit;
        }

        $this->ensure_import_dir();

        $filename   = basename($file['name']);
        $saved_path = $import_dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $saved_path)) {
            echo json_encode(['success' => false, 'error' => 'Datei konnte nicht in den Import-Ordner gespeichert werden.']);
            exit;
        }

        echo json_encode([
            'success'  => true,
            'filename' => $filename,
            'size'     => $this->format_bytes($file['size']),
        ]);
        exit;
    }

    // ── AJAX: Ordner-Scan ──────────────────────────────────────────────────────

    public function handle_ajax_scan_folder(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $security = class_exists('CMS\Security') ? CMS\Security::instance() : null;
        $nonce    = $_GET['_nonce'] ?? $_POST['_nonce'] ?? '';

        if ($security && !$security->verifyNonce($nonce, 'cms-importer-upload')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen.']);
            exit;
        }

        echo json_encode(['success' => true, 'files' => $this->scan_import_folder()]);
        exit;
    }

    // ── AJAX: Berichts-Download ────────────────────────────────────────────────

    public function handle_download_report(): void
    {
        $security = class_exists('CMS\Security') ? CMS\Security::instance() : null;
        $nonce    = $_GET['_nonce'] ?? '';

        if ($security && !$security->verifyNonce($nonce, 'cms-importer-download')) {
            http_response_code(403);
            echo 'Sicherheitscheck fehlgeschlagen.';
            exit;
        }

        $log_id = (int) ($_GET['log_id'] ?? 0);
        if ($log_id <= 0) {
            http_response_code(400);
            exit;
        }

        $path = $this->get_report_path($log_id);
        if (!$path || !file_exists($path)) {
            http_response_code(404);
            echo 'Bericht nicht gefunden.';
            exit;
        }

        $filename = basename($path);
        header('Content-Type: text/markdown; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    // ── Upload-Verarbeitung ────────────────────────────────────────────────────

    /**
     * Verarbeitet einen $_FILES-Upload und startet den Import.
     *
     * @return array{0: string, 1: string, 2: array|null}  [message, type, result]
     */
    private function process_upload_request(): array
    {
        if (empty($_FILES['wxr_file'])) {
            return ['Keine Datei hochgeladen.', 'error', null];
        }

        $file = $_FILES['wxr_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [$this->upload_error_message($file['error']), 'error', null];
        }

        $max_bytes = self::MAX_UPLOAD_MB * 1048576;
        if ($file['size'] > $max_bytes) {
            return ['Datei zu groß. Maximum: ' . self::MAX_UPLOAD_MB . ' MB', 'error', null];
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $detected = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'xml' && !in_array($detected, self::ALLOWED_MIMES, true)) {
            return ['Ungültiger Dateityp. Nur XML-Dateien erlaubt. (Erkannt: ' . $detected . ')', 'error', null];
        }

        // Optional: Datei in uploads/import/ speichern
        $save_to_folder = (bool) ($_POST['save_to_folder'] ?? false);
        $import_path    = $file['tmp_name'];

        if ($save_to_folder) {
            $import_dir   = $this->get_import_dir();
            $saved_path   = $import_dir . basename($file['name']);
            if (move_uploaded_file($file['tmp_name'], $saved_path)) {
                $import_path = $saved_path;
            }
        }

        return $this->process_xml_file($import_path, $file['name']);
    }

    /**
     * Gemeinsame Import-Logik für Upload und Ordner-Import.
     *
     * @return array{0: string, 1: string, 2: array|null}  [message, type, result]
     */
    private function process_xml_file(string $file_path, string $display_name): array
    {
        // WXR-Signatur prüfen
        $snippet = file_get_contents($file_path, false, null, 0, 1024);
        if ($snippet === false || strpos($snippet, 'wxr_version') === false) {
            return ['Keine gültige WordPress-Exportdatei (kein WXR-Format erkannt).', 'error', null];
        }

        $parser = new CMS_Importer_XML_Parser();
        $parsed = $parser->parse($file_path);

        if (!empty($parsed['errors'])) {
            return ['XML-Fehler: ' . implode('; ', $parsed['errors']), 'error', null];
        }

        $total_items = count($parsed['posts']) + count($parsed['pages']) + count($parsed['others']);
        if ($total_items === 0) {
            return ['Keine importierbaren Inhalte (Posts, Pages) gefunden.', 'warning', null];
        }

        // Import-Optionen aus POST lesen
        $options = [
            'skip_duplicates'     => isset($_POST['skip_duplicates']),
            'import_drafts'       => isset($_POST['import_drafts']),
            'import_trashed'      => isset($_POST['import_trashed']),
            'import_custom_types' => isset($_POST['import_custom_types']),
            'generate_report'     => isset($_POST['generate_report']),
            'download_images'     => true,
        ];

        // WICHTIG: CMS\Auth::getCurrentUser() ist eine statische Methode
        $user    = class_exists('CMS\Auth') ? CMS\Auth::getCurrentUser() : null;
        $user_id = $user ? (int) ($user->id ?? 0) : 0;

        $service = new CMS_Importer_Service();
        $result  = $service->import($parsed, $display_name, $user_id, $options);

        $msg = sprintf(
            'Import abgeschlossen: %d importiert, %d übersprungen, %d Fehler%s.',
            $result['imported'],
            $result['skipped'],
            $result['errors'],
            $result['images_downloaded'] > 0
                ? ', ' . $result['images_downloaded'] . ' Bilder heruntergeladen'
                : ''
        );

        if ($result['meta_keys'] > 0) {
            $msg .= sprintf(' | %d unbekannte Meta-Keys → Bericht gespeichert.', $result['meta_keys']);
        }

        return [$msg, $result['errors'] > 0 ? 'warning' : 'success', $result];
    }

    // ── Import-Ordner ─────────────────────────────────────────────────────────

    /**
     * Gibt den absoluten Pfad zum Import-Ordner zurück.
     */
    private function get_import_dir(): string
    {
        if (!defined('UPLOAD_PATH')) {
            return '';
        }
        return rtrim(UPLOAD_PATH, '/') . '/import/';
    }

    /**
     * Stellt sicher dass der Import-Ordner existiert.
     */
    private function ensure_import_dir(): void
    {
        $dir = $this->get_import_dir();
        if ($dir !== '' && !is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Listet alle XML-Dateien im Import-Ordner auf.
     *
     * @return array  Array von ['name' => string, 'size' => int, 'date' => string, 'size_human' => string]
     */
    public function scan_import_folder(): array
    {
        $dir = $this->get_import_dir();
        if ($dir === '' || !is_dir($dir)) {
            return [];
        }

        $files = [];
        foreach (glob($dir . '*.xml') as $path) {
            $size  = (int) filesize($path);
            $files[] = [
                'name'       => basename($path),
                'size'       => $size,
                'size_human' => $this->format_bytes($size),
                'date'       => date('d.m.Y H:i', (int) filemtime($path)),
            ];
        }

        // Neueste zuerst
        usort($files, static fn ($a, $b) => strcmp($b['date'], $a['date']));

        return $files;
    }

    // ── DB-Hilfsmethoden ──────────────────────────────────────────────────────

    private function get_report_path(int $log_id): ?string
    {
        if (!class_exists('CMS\Database')) {
            return null;
        }
        $db = CMS\Database::instance();
        $p  = $db->getPrefix();
        return $db->get_var(
            "SELECT meta_report_path FROM {$p}import_log WHERE id = ?",
            [$log_id]
        ) ?: null;
    }

    private function get_recent_logs(int $limit = 10): array
    {
        if (!class_exists('CMS\Database')) {
            return [];
        }
        $db = CMS\Database::instance();
        $p  = $db->getPrefix();
        try {
            return $db->get_results(
                "SELECT * FROM {$p}import_log ORDER BY started_at DESC LIMIT ?",
                [$limit]
            ) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    // ── Hilfsmethoden ──────────────────────────────────────────────────────────

    private function upload_error_message(int $code): string
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE   => 'Datei überschreitet php.ini upload_max_filesize.',
            UPLOAD_ERR_FORM_SIZE  => 'Datei überschreitet MAX_FILE_SIZE des Formulars.',
            UPLOAD_ERR_PARTIAL    => 'Datei wurde nur teilweise hochgeladen.',
            UPLOAD_ERR_NO_FILE    => 'Keine Datei wurde hochgeladen.',
            UPLOAD_ERR_NO_TMP_DIR => 'Temporäres Verzeichnis fehlt.',
            UPLOAD_ERR_CANT_WRITE => 'Datei konnte nicht auf die Festplatte geschrieben werden.',
            UPLOAD_ERR_EXTENSION  => 'Upload durch PHP-Extension abgebrochen.',
        ];
        return $messages[$code] ?? 'Unbekannter Upload-Fehler (Code: ' . $code . ')';
    }

    private function format_bytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return round($bytes / 1048576, 1) . ' MB';
    }
}
