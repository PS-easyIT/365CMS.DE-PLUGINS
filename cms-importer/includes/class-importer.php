<?php
/**
 * CMS WordPress Importer – Kern-Logik & Datenbankzugriff
 *
 * Verantwortlich für:
 * - DB-Tabellen anlegen (create_tables)
 * - Posts importieren (import_as_post)
 * - Pages importieren (import_as_page)
 * - Bilder herunterladen (download_post_images)
 * - Import-Log schreiben
 * - Markdown-Bericht für unbekannte Meta-Felder erstellen
 *
 * @package CMS_Importer
 * @since   1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// ── Datenbankschicht ──────────────────────────────────────────────────────────

/**
 * Kapselt die DB-Initialisierung des Importers.
 */
class CMS_Importer_DB
{
    /**
     * Erstellt die benötigten Tabellen, falls noch nicht vorhanden.
     * Legt außerdem das Upload-Verzeichnis uploads/import/ an.
     */
    public static function create_tables(): void
    {
        if (!class_exists('CMS\Database')) {
            return;
        }

        $db = CMS\Database::instance();
        $p  = $db->getPrefix();

        // Import-Log: eine Zeile pro Import-Run
        $db->query("
            CREATE TABLE IF NOT EXISTS {$p}import_log (
                id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                filename          VARCHAR(255) NOT NULL,
                import_type       ENUM('posts','pages','mixed','other') DEFAULT 'mixed',
                total             INT UNSIGNED DEFAULT 0,
                imported          INT UNSIGNED DEFAULT 0,
                skipped           INT UNSIGNED DEFAULT 0,
                errors            INT UNSIGNED DEFAULT 0,
                images_downloaded INT UNSIGNED DEFAULT 0,
                meta_report_path  VARCHAR(500),
                started_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                finished_at       TIMESTAMP NULL,
                user_id           INT UNSIGNED NULL,
                INDEX idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Importierte Meta-Felder (nicht auf CMS-Felder gemappte Keys)
        $db->query("
            CREATE TABLE IF NOT EXISTS {$p}import_meta (
                id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                log_id      INT UNSIGNED NOT NULL,
                source_id   VARCHAR(50)  NOT NULL COMMENT 'wp_post_id aus WXR',
                post_title  VARCHAR(255),
                post_type   VARCHAR(50),
                meta_key    VARCHAR(255) NOT NULL,
                meta_value  LONGTEXT,
                INDEX idx_log (log_id),
                INDEX idx_key (meta_key(100))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Upload-Ordner für Import-Dateien anlegen
        if (defined('UPLOAD_PATH')) {
            $import_dir = rtrim(UPLOAD_PATH, '/') . '/import/';
            if (!is_dir($import_dir)) {
                mkdir($import_dir, 0755, true);
            }
        }
    }
}

// ── Haupt-Importer ────────────────────────────────────────────────────────────

/**
 * Führt den eigentlichen Import durch.
 */
class CMS_Importer_Service
{
    /** Status-Mapping WP → CMS */
    private const STATUS_MAP = [
        'publish'   => 'published',
        'published' => 'published',
        'draft'     => 'draft',
        'pending'   => 'draft',
        'future'    => 'draft',
        'private'   => 'draft',
        'trash'     => 'trash',
    ];

    private int    $log_id            = 0;
    private int    $total             = 0;
    private int    $imported          = 0;
    private int    $skipped           = 0;
    private int    $errors            = 0;
    private int    $images_downloaded = 0;
    private array  $unknown_meta      = [];
    private string $filename          = '';
    private array  $options           = [];

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Importiert eine geparste WXR-Datei.
     *
     * @param  array  $parsed    Ergebnis von CMS_Importer_XML_Parser::parse()
     * @param  string $filename  Originaler Dateiname (für Log)
     * @param  int    $user_id   Benutzer-ID die den Import auslöst
     * @param  array  $options   Import-Optionen (skip_duplicates, import_drafts, ...)
     * @return array  Zusammenfassung des Import-Runs
     */
    public function import(array $parsed, string $filename, int $user_id = 0, array $options = []): array
    {
        if (!class_exists('CMS\Database')) {
            return ['error' => 'CMS\\Database nicht verfügbar'];
        }

        $this->filename = $filename;
        $this->options  = array_merge([
            'skip_duplicates'     => true,
            'import_drafts'       => true,
            'import_trashed'      => false,
            'import_custom_types' => true,
            'generate_report'     => true,
            'download_images'     => true,
        ], $options);

        $this->reset_counters();

        $db = CMS\Database::instance();
        $p  = $db->getPrefix();

        $this->log_id = $this->create_log_entry($db, $p, $filename, $user_id);

        foreach ($parsed['posts'] as $item) {
            $this->total++;
            $this->import_as_post($db, $p, $item);
        }

        foreach ($parsed['pages'] as $item) {
            $this->total++;
            $this->import_as_page($db, $p, $item);
        }

        if ($this->options['import_custom_types']) {
            foreach ($parsed['others'] as $item) {
                $this->total++;
                $this->import_as_post($db, $p, $item);
            }
        }

        if ($this->options['generate_report']) {
            $this->store_unknown_meta($db, $p);
        }

        $report_path = '';
        if ($this->options['generate_report']) {
            $report_path = $this->generate_meta_report($parsed['site'] ?? []);
        }

        $this->finalize_log($db, $p, $report_path);

        return [
            'log_id'            => $this->log_id,
            'total'             => $this->total,
            'imported'          => $this->imported,
            'skipped'           => $this->skipped,
            'errors'            => $this->errors,
            'images_downloaded' => $this->images_downloaded,
            'meta_keys'         => count(array_unique(array_column($this->unknown_meta, 'meta_key'))),
            'meta_report'       => $report_path,
        ];
    }

    // ── Private: Posts ────────────────────────────────────────────────────────

    private function import_as_post(\CMS\Database $db, string $p, array $item): void
    {
        $status = self::STATUS_MAP[$item['post_status']] ?? 'draft';

        if ($status === 'trash' && !$this->options['import_trashed']) {
            $this->skipped++;
            return;
        }
        if ($status === 'draft' && !$this->options['import_drafts']) {
            $this->skipped++;
            return;
        }

        $base_slug = $item['slug'] !== ''
            ? $this->sanitize_slug($item['slug'])
            : $this->slugify($item['title']);

        if ($this->options['skip_duplicates']) {
            $exists = (int) $db->get_var(
                "SELECT COUNT(*) FROM {$p}posts WHERE slug = ?",
                [$base_slug]
            );
            if ($exists > 0) {
                $this->skipped++;
                return;
            }
            $slug = $base_slug;
        } else {
            $slug = $this->unique_slug($db, $p . 'posts', $base_slug);
        }

        $author_id = $this->resolve_author_id($db, $p, $item['author_login']);
        $tags      = implode(',', array_map('trim', array_merge($item['tags'] ?? [], $item['categories'] ?? [])));

        $data = [
            'title'            => $this->sanitize_title($item['title']),
            'slug'             => $slug,
            'content'          => $item['content'],
            'excerpt'          => $item['excerpt'],
            'featured_image'   => $item['featured_image'] ?? '',
            'status'           => $status,
            'author_id'        => $author_id,
            'tags'             => mb_substr($tags, 0, 500),
            'meta_title'       => mb_substr($item['meta_title'] ?? '', 0, 255),
            'meta_description' => $item['meta_description'] ?? '',
            'created_at'       => $this->safe_date($item['date']),
            'published_at'     => $status === 'published' ? $this->safe_date($item['date']) : null,
        ];

        try {
            // WICHTIG: insert() fügt intern den Prefix hinzu → KEIN Prefix übergeben!
            $db->insert('posts', $data);
            $post_id = $db->insert_id();
            $this->imported++;

            if ($this->options['download_images'] && !empty($item['image_urls'])) {
                $downloaded = $this->download_post_images($db, $p, $slug, $item['image_urls']);
                if (!empty($downloaded)) {
                    $this->images_downloaded += count($downloaded);
                    if (empty($data['featured_image']) && defined('UPLOAD_URL')) {
                        $featured_url = rtrim(UPLOAD_URL, '/') . '/images/' . $slug . '/' . basename($downloaded[0]);
                        $db->execute(
                            "UPDATE {$p}posts SET featured_image = ? WHERE id = ?",
                            [$featured_url, $post_id]
                        );
                    }
                }
            }

            $this->collect_unknown_meta($item);

        } catch (\Exception $e) {
            $this->errors++;
            error_log('CMS_Importer: Post-Import fehlgeschlagen: ' . $e->getMessage() . ' – Titel: ' . $item['title']);
        }
    }

    private function import_as_page(\CMS\Database $db, string $p, array $item): void
    {
        $status = self::STATUS_MAP[$item['post_status']] ?? 'draft';

        if ($status === 'trash' && !$this->options['import_trashed']) {
            $this->skipped++;
            return;
        }
        if ($status === 'draft' && !$this->options['import_drafts']) {
            $this->skipped++;
            return;
        }

        $base_slug = $item['slug'] !== ''
            ? $this->sanitize_slug($item['slug'])
            : $this->slugify($item['title']);

        if ($this->options['skip_duplicates']) {
            $exists = (int) $db->get_var(
                "SELECT COUNT(*) FROM {$p}pages WHERE slug = ?",
                [$base_slug]
            );
            if ($exists > 0) {
                $this->skipped++;
                return;
            }
            $slug = $base_slug;
        } else {
            $slug = $this->unique_slug($db, $p . 'pages', $base_slug);
        }

        $author_id = $this->resolve_author_id($db, $p, $item['author_login']);

        $data = [
            'slug'         => $slug,
            'title'        => $this->sanitize_title($item['title']),
            'content'      => $item['content'],
            'excerpt'      => $item['excerpt'],
            'status'       => $status,
            'hide_title'   => 0,
            'author_id'    => $author_id,
            'created_at'   => $this->safe_date($item['date']),
            'published_at' => $status === 'published' ? $this->safe_date($item['date']) : null,
        ];

        try {
            // WICHTIG: insert() fügt intern den Prefix hinzu → KEIN Prefix übergeben!
            $db->insert('pages', $data);
            $this->imported++;

            if ($this->options['download_images'] && !empty($item['image_urls'])) {
                $downloaded = $this->download_post_images($db, $p, $slug, $item['image_urls']);
                $this->images_downloaded += count($downloaded);
            }

            $this->collect_unknown_meta($item);

        } catch (\Exception $e) {
            $this->errors++;
            error_log('CMS_Importer: Page-Import fehlgeschlagen: ' . $e->getMessage() . ' – Titel: ' . $item['title']);
        }
    }

    // ── Private: Bild-Downloader ──────────────────────────────────────────────

    /**
     * Lädt alle Bilder eines Posts herunter und registriert sie in cms_media.
     * Zielverzeichnis: uploads/images/{slug}/
     *
     * @param  string[] $urls  Absolute Bild-URLs
     * @return string[]        Lokale Dateipfade der erfolgreich geladenen Bilder
     */
    private function download_post_images(\CMS\Database $db, string $p, string $slug, array $urls): array
    {
        if (empty($urls) || !defined('UPLOAD_PATH')) {
            return [];
        }

        $dir = rtrim(UPLOAD_PATH, '/') . '/images/' . $slug . '/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            error_log('CMS_Importer: Konnte Verzeichnis nicht anlegen: ' . $dir);
            return [];
        }

        $downloaded = [];

        foreach ($urls as $url) {
            $url = trim($url);
            if ($url === '') {
                continue;
            }

            $filename   = $this->url_to_filename($url);
            $local_path = $dir . $filename;

            if (file_exists($local_path)) {
                $downloaded[] = $local_path;
                continue;
            }

            $content = $this->fetch_remote_file($url);
            if ($content === null) {
                continue;
            }

            if (file_put_contents($local_path, $content) === false) {
                continue;
            }

            $downloaded[] = $local_path;
            $this->register_media($db, $local_path, $filename, $slug);
        }

        return $downloaded;
    }

    /**
     * Lädt eine Remote-URL herunter (cURL bevorzugt, fgc als Fallback).
     */
    private function fetch_remote_file(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 5,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT      => 'CMS-Importer/' . CMS_IMPORTER_VERSION,
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200 && is_string($body) && strlen($body) > 0) {
                return $body;
            }
            return null;
        }

        $ctx  = stream_context_create(['http' => [
            'timeout'    => 20,
            'user_agent' => 'CMS-Importer/' . CMS_IMPORTER_VERSION,
        ]]);
        $body = @file_get_contents($url, false, $ctx);
        return ($body !== false && strlen($body) > 0) ? $body : null;
    }

    /**
     * Leitet eine URL in einen sicheren Dateinamen um.
     */
    private function url_to_filename(string $url): string
    {
        $path     = parse_url($url, PHP_URL_PATH) ?? '';
        $filename = basename($path);
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '-', $filename) ?? 'image';
        $filename = trim($filename, '-');
        return $filename !== '' ? $filename : 'image-' . substr(md5($url), 0, 8);
    }

    /**
     * Registriert eine heruntergeladene Datei in der cms_media-Tabelle.
     */
    private function register_media(\CMS\Database $db, string $local_path, string $filename, string $slug): void
    {
        try {
            // WICHTIG: insert() fügt intern den Prefix hinzu → KEIN Prefix übergeben!
            $db->insert('media', [
                'filename'    => $filename,
                'filepath'    => 'images/' . $slug . '/' . $filename,
                'filetype'    => mime_content_type($local_path) ?: 'image/jpeg',
                'filesize'    => (int) (filesize($local_path) ?: 0),
                'title'       => pathinfo($filename, PATHINFO_FILENAME),
                'alt_text'    => '',
                'caption'     => '',
                'uploaded_by' => 0,
            ]);
        } catch (\Exception $e) {
            error_log('CMS_Importer: Media-Registrierung fehlgeschlagen: ' . $e->getMessage());
        }
    }

    // ── Private: Meta-Handling ────────────────────────────────────────────────

    private function collect_unknown_meta(array $item): void
    {
        if (empty($item['meta'])) {
            return;
        }
        foreach ($item['meta'] as $key => $value) {
            $this->unknown_meta[] = [
                'source_id'  => (string) $item['wp_id'],
                'post_title' => mb_substr($item['title'], 0, 255),
                'post_type'  => $item['post_type'],
                'meta_key'   => $key,
                'meta_value' => $value,
            ];
        }
    }

    private function store_unknown_meta(\CMS\Database $db, string $p): void
    {
        if (empty($this->unknown_meta) || $this->log_id === 0) {
            return;
        }
        foreach ($this->unknown_meta as $row) {
            try {
                // WICHTIG: insert() fügt intern den Prefix hinzu → KEIN Prefix übergeben!
                $db->insert('import_meta', array_merge(['log_id' => $this->log_id], $row));
            } catch (\Exception $e) {
                error_log('CMS_Importer: Meta-Speicherung fehlgeschlagen: ' . $e->getMessage());
            }
        }
    }

    // ── Private: Markdown-Bericht ─────────────────────────────────────────────

    private function generate_meta_report(array $site_info): string
    {
        if (empty($this->unknown_meta)) {
            return '';
        }

        $report_dir = CMS_IMPORTER_PLUGIN_DIR . 'reports/';
        if (!is_dir($report_dir)) {
            mkdir($report_dir, 0755, true);
        }

        $safe_name   = preg_replace('/[^a-z0-9_-]/', '_', strtolower(pathinfo($this->filename, PATHINFO_FILENAME)));
        $report_file = $report_dir . date('Y-m-d_His') . '_' . $safe_name . '_meta-report.md';

        $grouped = [];
        foreach ($this->unknown_meta as $row) {
            $key = $row['meta_key'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = ['count' => 0, 'examples' => []];
            }
            $grouped[$key]['count']++;
            if (count($grouped[$key]['examples']) < 3) {
                $grouped[$key]['examples'][] = [
                    'source_id'  => $row['source_id'],
                    'post_title' => $row['post_title'],
                    'post_type'  => $row['post_type'],
                    'value'      => mb_substr((string) $row['meta_value'], 0, 200),
                ];
            }
        }

        ksort($grouped);

        $md  = "# WordPress-Import – Unbekannte Meta-Felder\n\n";
        $md .= "> **Import-Datei:** `{$this->filename}`  \n";
        $md .= "> **Erstellt:** " . date('d.m.Y H:i:s') . "  \n";
        $md .= "> **Quelle:** " . htmlspecialchars($site_info['title'] ?? 'Unbekannt') . " (`" . ($site_info['base_site_url'] ?? '') . "`)  \n";
        $md .= "> **Anzahl unbekannter Keys:** " . count($grouped) . "  \n";
        $md .= "> **Gesamte Meta-Einträge:** " . count($this->unknown_meta) . "  \n\n";
        $md .= "---\n\n";
        $md .= "## Hinweis\n\n";
        $md .= "Die folgenden Meta-Keys aus dem WordPress-Export konnten **nicht automatisch** auf ein CMS-Datenbankfeld gemappt werden. ";
        $md .= "Sie wurden trotzdem in der Tabelle `cms_import_meta` gespeichert und können manuell nachverarbeitet werden.\n\n";
        $md .= "---\n\n";
        $md .= "## Übersicht aller unbekannten Meta-Keys\n\n";
        $md .= "| # | Meta-Key | Anzahl | Hinweis |\n";
        $md .= "|---|----------|--------|---------|\n";

        $i = 1;
        foreach ($grouped as $key => $info) {
            $hint = $this->get_meta_hint($key);
            $md  .= "| {$i} | `{$key}` | {$info['count']} | {$hint} |\n";
            $i++;
        }

        $md .= "\n---\n\n";
        $md .= "## Details der unbekannten Meta-Keys\n\n";

        foreach ($grouped as $key => $info) {
            $hint = $this->get_meta_hint($key);
            $md  .= "### `{$key}`\n\n";
            $md  .= "- **Vorkommen:** {$info['count']}\n";
            $md  .= "- **Hinweis:** {$hint}\n";
            $md  .= "- **Beispielwerte:**\n\n";
            foreach ($info['examples'] as $ex) {
                $value = str_replace(['|', "\n", "\r"], [' &#124; ', ' ', ''], $ex['value']);
                $md   .= "  - Post `{$ex['source_id']}` (**{$ex['post_title']}**, Typ: `{$ex['post_type']}`):  \n";
                $md   .= "    `{$value}`\n";
            }
            $md .= "\n";
        }

        $md .= "---\n\n";
        $md .= "*Automatisch generiert vom CMS WordPress Importer v" . CMS_IMPORTER_VERSION . "*\n";

        file_put_contents($report_file, $md);
        return $report_file;
    }

    private function get_meta_hint(string $key): string
    {
        $hints = [
            'rank_math_seo_score'                => 'Rank Math SEO-Score (0–100)',
            'rank_math_focus_keyword'            => 'Rank Math Fokus-Keyword',
            'rank_math_canonical_url'            => 'Rank Math Canonical-URL',
            'rank_math_og_content_image'         => 'Rank Math Open-Graph-Bild',
            'rank_math_internal_links_processed' => 'Rank Math interne Verlinkung (Technik)',
            'rank_math_analytic_object_id'       => 'Rank Math Analytics-ID (Technik)',
            '_yoast_wpseo_focuskw'               => 'Yoast SEO Fokus-Keyword',
            '_yoast_wpseo_canonical'             => 'Yoast SEO Canonical-URL',
            '_yoast_wpseo_opengraph-image'       => 'Yoast SEO Open-Graph-Bild',
            '_yoast_wpseo_schema_page_type'      => 'Yoast SEO Schema-Seitentyp',
            '_yoast_wpseo_schema_article_type'   => 'Yoast SEO Schema-Artikeltyp',
            '_wp_page_template'                  => 'WordPress Seitentemplate-Zuweisung',
            'cmplz_hide_cookiebanner'            => 'Complianz – Cookie-Banner ausblenden',
            'litespeed_vpi_list'                 => 'LiteSpeed Cache VPI-Liste (Technik)',
            '_lwpgls_synonyms'                   => 'Lightweight Glossary – Synonyme',
            '_wpml_word_count'                   => 'WPML Wortanzahl (Technik)',
            '_wpml_media_featured'               => 'WPML Medien Featured (Technik)',
        ];

        if (str_starts_with($key, '_yoast_')) {
            return $hints[$key] ?? 'Yoast SEO Plugin – kein direktes CMS-Äquivalent';
        }
        if (str_starts_with($key, 'rank_math_')) {
            return $hints[$key] ?? 'Rank Math SEO Plugin – kein direktes CMS-Äquivalent';
        }
        if (str_starts_with($key, '_wpml_')) {
            return $hints[$key] ?? 'WPML Mehrsprachigkeit – kein direktes CMS-Äquivalent';
        }
        if (str_starts_with($key, 'litespeed_')) {
            return $hints[$key] ?? 'LiteSpeed Cache – Technik-Metadaten (kann ignoriert werden)';
        }

        return $hints[$key] ?? '—';
    }

    // ── Private: Log-Verwaltung ───────────────────────────────────────────────

    private function create_log_entry(\CMS\Database $db, string $p, string $filename, int $user_id): int
    {
        try {
            // WICHTIG: insert() fügt intern den Prefix hinzu → KEIN Prefix übergeben!
            $db->insert('import_log', [
                'filename'    => $filename,
                'import_type' => 'mixed',
                'total'       => 0,
                'imported'    => 0,
                'skipped'     => 0,
                'errors'      => 0,
                'user_id'     => $user_id > 0 ? $user_id : null,
            ]);
            return $db->insert_id();
        } catch (\Exception $e) {
            error_log('CMS_Importer: Log-Eintrag konnte nicht erstellt werden: ' . $e->getMessage());
            return 0;
        }
    }

    private function finalize_log(\CMS\Database $db, string $p, string $report_path): void
    {
        if ($this->log_id === 0) {
            return;
        }
        try {
            // WICHTIG: execute() für parametrisierte DML-Statements (query() nimmt KEINE Params!)
            $db->execute(
                "UPDATE {$p}import_log
                 SET total = ?, imported = ?, skipped = ?, errors = ?,
                     images_downloaded = ?, meta_report_path = ?, finished_at = NOW()
                 WHERE id = ?",
                [
                    $this->total,
                    $this->imported,
                    $this->skipped,
                    $this->errors,
                    $this->images_downloaded,
                    $report_path !== '' ? $report_path : null,
                    $this->log_id,
                ]
            );
        } catch (\Exception $e) {
            error_log('CMS_Importer: Log-Update fehlgeschlagen: ' . $e->getMessage());
        }
    }

    // ── Private: Hilfsmethoden ────────────────────────────────────────────────

    private function reset_counters(): void
    {
        $this->total             = 0;
        $this->imported          = 0;
        $this->skipped           = 0;
        $this->errors            = 0;
        $this->images_downloaded = 0;
        $this->unknown_meta      = [];
        $this->log_id            = 0;
    }

    /**
     * Erzeugt einen eindeutigen Slug (max. 10 Versuche, dann UUID-Suffix).
     *
     * @param string $table  Vollständiger Tabellenname inkl. Prefix (z. B. 'cms_posts')
     */
    private function unique_slug(\CMS\Database $db, string $table, string $base): string
    {
        $slug   = $this->sanitize_slug($base);
        $try    = $slug;
        $suffix = 2;

        for ($i = 0; $i <= 10; $i++) {
            $exists = (int) $db->get_var(
                "SELECT COUNT(*) FROM {$table} WHERE slug = ?",
                [$try]
            );
            if ($exists === 0) {
                return $try;
            }
            $try = $slug . '-' . $suffix;
            $suffix++;
        }

        return $slug . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
    }

    /**
     * Bereinigt einen Slug: Unicode-Buchstaben/Ziffern + Bindestriche, max. 190 Zeichen.
     */
    private function sanitize_slug(string $slug): string
    {
        $slug = mb_strtolower(trim($slug));
        $slug = preg_replace('/[^\p{L}\p{N}\-]/u', '-', $slug) ?? $slug;
        $slug = preg_replace('/-{2,}/', '-', $slug) ?? $slug;
        $slug = trim($slug, '-');
        return mb_substr($slug !== '' ? $slug : 'imported', 0, 190);
    }

    /**
     * Wandelt beliebigen Text in einen URL-freundlichen Slug um.
     * Nutzt intl-Transliteration falls verfügbar, sonst Umlaut-Fallback.
     */
    private function slugify(string $text): string
    {
        if (function_exists('transliterator_transliterate')) {
            $ascii = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
            if ($ascii !== false && $ascii !== '') {
                return $this->sanitize_slug($ascii);
            }
        }

        $map = [
            'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
            'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
        ];
        $text = strtr(mb_strtolower($text), $map);
        return $this->sanitize_slug($text ?: 'imported-' . time());
    }

    /**
     * Bereinigt einen Titel für DB-Speicherung (strip_tags + trim, kein HTML-Encoding!).
     */
    private function sanitize_title(string $text): string
    {
        return trim(strip_tags($text));
    }

    /**
     * Gibt einen gültigen MySQL-Datumstring oder null zurück.
     */
    private function safe_date(string $date): ?string
    {
        if ($date === '' || $date === '0000-00-00 00:00:00') {
            return null;
        }
        $ts = strtotime($date);
        return $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
    }

    /**
     * Ermittelt die CMS-User-ID anhand des WP-Author-Logins (Username oder E-Mail).
     */
    private function resolve_author_id(\CMS\Database $db, string $p, string $login): int
    {
        if ($login === '') {
            return 0;
        }
        try {
            $id = $db->get_var(
                "SELECT id FROM {$p}users WHERE username = ? OR email = ? LIMIT 1",
                [$login, $login]
            );
            return (int) ($id ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }
}