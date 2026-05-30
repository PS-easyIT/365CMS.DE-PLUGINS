<?php
/**
 * @package CMS_Downloads
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Downloads_Repository
{
    private static ?self $instance = null;

    private \CMS\Database $db;
    private string $prefix;

    /** @var array<string,array<string,string>> */
    private const DOWNLOAD_TYPES = [
        'generic' => ['label' => 'Allgemein', 'icon' => '⬇️', 'hint' => 'Allgemeine Dateien und Ressourcen'],
        'powershell' => ['label' => 'PowerShell', 'icon' => '🖥️', 'hint' => 'Skripte, Module und Admin-Automationen'],
        'webproject' => ['label' => 'Webprojekt', 'icon' => '🌐', 'hint' => 'ZIP-Archive, Stacks und Web-Starter'],
        'document' => ['label' => 'Dokument', 'icon' => '📄', 'hint' => 'PDFs, Dokumentationen und Formulare'],
        'ebook' => ['label' => 'eBook', 'icon' => '📚', 'hint' => 'Guides, Handbücher und digitale Bücher'],
        'archive' => ['label' => 'Archiv/Toolkit', 'icon' => '🧰', 'hint' => 'Sammlungen, ZIP-Dateien und Toolkits'],
    ];

    /** @var array<string,string> */
    private const DEFAULT_SETTINGS = [
        'archive_title' => 'Downloads',
        'archive_description' => 'Öffentliche Downloads, Vorlagen und Ressourcen.',
        'downloads_per_page' => '24',
        'show_search' => '1',
        'show_category_overview' => '1',
        'show_external_notice' => '1',
        'external_allowed_domains' => '',
        'show_nav_link' => '0',
        'nav_label' => 'Downloads',
    ];

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->db = \CMS\Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    public function get_type_templates(): array
    {
        return self::DOWNLOAD_TYPES;
    }

    public function get_dashboard_stats(): array
    {
        return [
            'downloads' => (int) $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}downloads"),
            'active_downloads' => (int) $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}downloads WHERE status = 'active'"),
            'categories' => (int) $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}download_categories"),
            'downloads_total' => (int) $this->db->get_var("SELECT COALESCE(SUM(download_count), 0) FROM {$this->prefix}downloads"),
        ];
    }

    public function get_settings(): array
    {
        $stmt = $this->db->prepare("SELECT setting_key, setting_value FROM {$this->prefix}download_settings");
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $settings = self::DEFAULT_SETTINGS;

        foreach ($rows as $row) {
            $settings[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }

        return $settings;
    }

    public function seed_settings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $exists = $this->db->prepare("SELECT id FROM {$this->prefix}download_settings WHERE setting_key = ? LIMIT 1");
            $exists->execute([$key]);
            if ($exists->fetchColumn() !== false) {
                continue;
            }

            $stmt = $this->db->prepare("INSERT INTO {$this->prefix}download_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, (string) $value]);
        }
    }

    public function save_settings(array $post): array
    {
        $settings = [
            'archive_title' => $this->clean_text($post['archive_title'] ?? 'Downloads'),
            'archive_description' => $this->clean_textarea($post['archive_description'] ?? ''),
            'downloads_per_page' => (string) max(6, min(120, (int) ($post['downloads_per_page'] ?? 24))),
            'show_search' => !empty($post['show_search']) ? '1' : '0',
            'show_category_overview' => !empty($post['show_category_overview']) ? '1' : '0',
            'show_external_notice' => !empty($post['show_external_notice']) ? '1' : '0',
            'external_allowed_domains' => $this->normalize_domain_allowlist($post['external_allowed_domains'] ?? ''),
            'show_nav_link' => !empty($post['show_nav_link']) ? '1' : '0',
            'nav_label' => $this->clean_text($post['nav_label'] ?? 'Downloads'),
        ];

        foreach ($settings as $key => $value) {
            $exists = $this->db->prepare("SELECT id FROM {$this->prefix}download_settings WHERE setting_key = ? LIMIT 1");
            $exists->execute([$key]);
            if ($exists->fetchColumn() !== false) {
                $stmt = $this->db->prepare("UPDATE {$this->prefix}download_settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->execute([$value, $key]);
            } else {
                $stmt = $this->db->prepare("INSERT INTO {$this->prefix}download_settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->execute([$key, $value]);
            }
        }

        return ['success' => true, 'message' => 'Download-Einstellungen gespeichert.'];
    }

    public function get_categories(bool $activeOnly = false): array
    {
        $sql = "SELECT c.*, (SELECT COUNT(*) FROM {$this->prefix}downloads d WHERE d.category_id = c.id) AS download_count
                FROM {$this->prefix}download_categories c";
        if ($activeOnly) {
            $sql .= " WHERE c.status = 'active'";
        }
        $sql .= ' ORDER BY c.sort_order ASC, c.name ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function get_category_by_id(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->prefix}download_categories WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    public function get_category_by_slug(string $slug): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->prefix}download_categories WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    public function seed_category(array $data): void
    {
        $slug = $this->ensure_unique_category_slug((string) ($data['slug'] ?? $this->slugify((string) ($data['name'] ?? ''))));
        $exists = $this->db->prepare("SELECT id FROM {$this->prefix}download_categories WHERE slug = ? LIMIT 1");
        $exists->execute([$slug]);
        if ($exists->fetchColumn() !== false) {
            return;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->prefix}download_categories (name, slug, description, icon, status, sort_order) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $this->clean_text($data['name'] ?? ''),
            $slug,
            $this->clean_textarea($data['description'] ?? ''),
            $this->clean_text($data['icon'] ?? '📁'),
            'active',
            (int) ($data['sort_order'] ?? 0),
        ]);
    }

    public function save_category(array $post): array
    {
        $id = (int) ($post['category_id'] ?? 0);
        $name = $this->clean_text($post['name'] ?? '');
        if ($name === '') {
            return ['success' => false, 'error' => 'Bitte einen Kategorienamen angeben.'];
        }

        $slug = $this->slugify($post['slug'] ?? $name);
        $slug = $this->ensure_unique_category_slug($slug, $id);
        $description = $this->clean_textarea($post['description'] ?? '');
        $icon = $this->clean_text($post['icon'] ?? '📁');
        $status = ($post['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $sortOrder = max(0, (int) ($post['sort_order'] ?? 0));

        if ($id > 0) {
            $stmt = $this->db->prepare(
                "UPDATE {$this->prefix}download_categories SET name = ?, slug = ?, description = ?, icon = ?, status = ?, sort_order = ? WHERE id = ?"
            );
            $stmt->execute([$name, $slug, $description, $icon, $status, $sortOrder, $id]);
            return ['success' => true, 'message' => 'Kategorie aktualisiert.'];
        }

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->prefix}download_categories (name, slug, description, icon, status, sort_order) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$name, $slug, $description, $icon, $status, $sortOrder]);

        return ['success' => true, 'message' => 'Kategorie erstellt.'];
    }

    public function delete_category(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige Kategorie.'];
        }

        $stmt = $this->db->prepare("UPDATE {$this->prefix}downloads SET category_id = NULL WHERE category_id = ?");
        $stmt->execute([$id]);

        $stmt = $this->db->prepare("DELETE FROM {$this->prefix}download_categories WHERE id = ?");
        $stmt->execute([$id]);

        return ['success' => true, 'message' => 'Kategorie gelöscht.'];
    }

    public function get_downloads(array $filters = []): array
    {
        [$where, $params] = $this->build_download_filters($filters);

        $limitSql = '';
        if (isset($filters['limit'])) {
            $limit = max(1, (int) $filters['limit']);
            $offset = max(0, (int) ($filters['offset'] ?? 0));
            $limitSql = ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        }

        $sql = "SELECT d.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon
                FROM {$this->prefix}downloads d
                LEFT JOIN {$this->prefix}download_categories c ON c.id = d.category_id";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY d.is_featured DESC, d.sort_order ASC, d.created_at DESC' . $limitSql;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function count_downloads(array $filters = []): int
    {
        [$where, $params] = $this->build_download_filters($filters);

        $sql = "SELECT COUNT(*)
                FROM {$this->prefix}downloads d
                LEFT JOIN {$this->prefix}download_categories c ON c.id = d.category_id";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array{0: array<int,string>, 1: array<int,mixed>}
     */
    private function build_download_filters(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'd.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['category_slug'])) {
            $where[] = 'c.slug = ?';
            $params[] = $filters['category_slug'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(d.title LIKE ? OR d.summary LIKE ? OR d.description LIKE ?)';
            $search = '%' . trim((string) $filters['search']) . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        return [$where, $params];
    }

    public function get_download(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT d.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon
             FROM {$this->prefix}downloads d
             LEFT JOIN {$this->prefix}download_categories c ON c.id = d.category_id
             WHERE d.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    public function get_download_by_slug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT d.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon
             FROM {$this->prefix}downloads d
             LEFT JOIN {$this->prefix}download_categories c ON c.id = d.category_id
             WHERE d.slug = ? AND d.status = 'active' LIMIT 1"
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    public function save_download(array $post, array $files = []): array
    {
        $id = (int) ($post['download_id'] ?? 0);
        $existing = $id > 0 ? $this->get_download($id) : null;
        $title = $this->clean_text($post['title'] ?? '');
        if ($title === '') {
            return ['success' => false, 'error' => 'Bitte einen Titel angeben.'];
        }

        $slug = $this->ensure_unique_download_slug($this->slugify($post['slug'] ?? $title), $id);
        $categoryId = (int) ($post['category_id'] ?? 0);
        $summary = $this->clean_textarea($post['summary'] ?? '');
        $description = $this->clean_html($post['description'] ?? '');
        $downloadType = array_key_exists((string) ($post['download_type'] ?? 'generic'), self::DOWNLOAD_TYPES)
            ? (string) $post['download_type']
            : 'generic';
        $versionLabel = $this->clean_text($post['version_label'] ?? '');
        $externalUrl = $this->clean_url($post['external_url'] ?? '');
        $status = ($post['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $requiresLogin = !empty($post['requires_login']) ? 1 : 0;
        $isFeatured = !empty($post['is_featured']) ? 1 : 0;
        $sortOrder = max(0, (int) ($post['sort_order'] ?? 0));

        $fileName = (string) ($existing['file_name'] ?? '');
        $filePath = (string) ($existing['file_path'] ?? '');
        $fileUrl = (string) ($existing['file_url'] ?? '');
        $fileExt = (string) ($existing['file_ext'] ?? '');
        $fileSize = (int) ($existing['file_size'] ?? 0);

        if (!empty($files['download_file']) && is_array($files['download_file']) && (int) ($files['download_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = $this->handle_upload($files['download_file']);
            if (!($uploadResult['success'] ?? false)) {
                return $uploadResult;
            }

            $fileName = (string) $uploadResult['file_name'];
            $filePath = (string) $uploadResult['file_path'];
            $fileUrl = (string) $uploadResult['file_url'];
            $fileExt = (string) $uploadResult['file_ext'];
            $fileSize = (int) $uploadResult['file_size'];
        }

        if ($filePath === '' && $externalUrl === '') {
            return ['success' => false, 'error' => 'Bitte eine Datei hochladen oder eine externe Download-URL angeben.'];
        }

        if ($categoryId <= 0) {
            $categoryId = null;
        }

        if ($id > 0) {
            $stmt = $this->db->prepare(
                "UPDATE {$this->prefix}downloads
                 SET category_id = ?, title = ?, slug = ?, summary = ?, description = ?, file_name = ?, file_path = ?, file_url = ?, external_url = ?, file_size = ?, file_ext = ?, version_label = ?, download_type = ?, requires_login = ?, is_featured = ?, status = ?, sort_order = ?
                 WHERE id = ?"
            );
            $stmt->execute([$categoryId, $title, $slug, $summary, $description, $fileName, $filePath, $fileUrl, $externalUrl, $fileSize, $fileExt, $versionLabel, $downloadType, $requiresLogin, $isFeatured, $status, $sortOrder, $id]);
            return ['success' => true, 'message' => 'Download aktualisiert.'];
        }

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->prefix}downloads (category_id, title, slug, summary, description, file_name, file_path, file_url, external_url, file_size, file_ext, version_label, download_type, requires_login, is_featured, status, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$categoryId, $title, $slug, $summary, $description, $fileName, $filePath, $fileUrl, $externalUrl, $fileSize, $fileExt, $versionLabel, $downloadType, $requiresLogin, $isFeatured, $status, $sortOrder]);

        return ['success' => true, 'message' => 'Download erstellt.'];
    }

    public function delete_download(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültiger Download.'];
        }

        $stmt = $this->db->prepare("DELETE FROM {$this->prefix}downloads WHERE id = ?");
        $stmt->execute([$id]);

        return ['success' => true, 'message' => 'Download gelöscht.'];
    }

    public function increment_download_count(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE {$this->prefix}downloads SET download_count = download_count + 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    private function handle_upload(array $file): array
    {
        if (!class_exists('CMS\\Services\\MediaService') || !defined('UPLOAD_PATH') || !defined('UPLOAD_URL')) {
            return ['success' => false, 'error' => 'Upload-Service ist aktuell nicht verfügbar.'];
        }

        $uploadRoot = rtrim((string) UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'downloads';
        $service = \CMS\Services\MediaService::getInstance($uploadRoot);
        $storedFile = $service->uploadFile($file, '', [
            'allowed_types' => ['document', 'archive'],
            'max_upload_size' => '256M',
            'block_dangerous_types' => true,
            'validate_image_content' => true,
        ]);

        if ($storedFile instanceof \CMS\WP_Error) {
            return ['success' => false, 'error' => $storedFile->get_error_message()];
        }

        $storedName = trim((string) $storedFile, '/\\');
        $relativePath = 'downloads/' . $storedName;
        $absolutePath = $uploadRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $storedName);

        $uploadRootReal = realpath($uploadRoot);
        $absolutePathReal = realpath($absolutePath);
        if ($uploadRootReal === false || $absolutePathReal === false || !str_starts_with($absolutePathReal, rtrim($uploadRootReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
            return ['success' => false, 'error' => 'Die hochgeladene Datei konnte nicht sicher im Download-Verzeichnis verifiziert werden.'];
        }

        return [
            'success' => true,
            'file_name' => basename($storedName),
            'file_path' => str_replace('\\', '/', $relativePath),
            'file_url' => rtrim((string) UPLOAD_URL, '/') . '/downloads/' . str_replace('\\', '/', $storedName),
            'file_ext' => strtolower(pathinfo($storedName, PATHINFO_EXTENSION)),
            'file_size' => is_file($absolutePathReal) ? (int) filesize($absolutePathReal) : 0,
        ];
    }

    private function ensure_unique_category_slug(string $slug, int $ignoreId = 0): string
    {
        return $this->ensure_unique_slug($slug, 'download_categories', $ignoreId);
    }

    private function ensure_unique_download_slug(string $slug, int $ignoreId = 0): string
    {
        return $this->ensure_unique_slug($slug, 'downloads', $ignoreId);
    }

    private function ensure_unique_slug(string $slug, string $table, int $ignoreId = 0): string
    {
        $base = $slug !== '' ? $slug : 'eintrag';
        $candidate = $base;
        $suffix = 2;

        do {
            $sql = "SELECT id FROM {$this->prefix}{$table} WHERE slug = ?" . ($ignoreId > 0 ? ' AND id != ?' : '') . ' LIMIT 1';
            $stmt = $this->db->prepare($sql);
            $params = [$candidate];
            if ($ignoreId > 0) {
                $params[] = $ignoreId;
            }
            $stmt->execute($params);
            $exists = $stmt->fetchColumn();

            if ($exists === false) {
                return $candidate;
            }

            $candidate = $base . '-' . $suffix;
            $suffix++;
        } while (true);
    }

    private function slugify(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = preg_replace('/[^\p{L}0-9]+/u', '-', $value) ?? '';
        $value = trim((string) preg_replace('/-+/', '-', $value), '-');

        return $value !== '' ? $value : 'download';
    }

    private function clean_text(mixed $value): string
    {
        return trim(strip_tags((string) $value));
    }

    private function clean_textarea(mixed $value): string
    {
        return trim(strip_tags((string) $value));
    }

    private function clean_html(mixed $value): string
    {
        return strip_tags((string) $value, '<p><a><strong><em><ul><ol><li><br><code><pre>');
    }

    private function clean_url(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $normalized = preg_match('#^https?://#i', $value) === 1 ? $value : 'https://' . ltrim($value, '/');
        $validated = filter_var($normalized, FILTER_VALIDATE_URL);

        return $validated !== false ? (string) $validated : '';
    }

    private function normalize_domain_allowlist(mixed $value): string
    {
        $raw = str_replace(["\r\n", "\r", ';'], ["\n", "\n", ','], (string) $value);
        $parts = preg_split('/[\n,]+/', $raw) ?: [];
        $domains = [];

        foreach ($parts as $part) {
            $domain = trim(mb_strtolower($part, 'UTF-8'));
            if ($domain === '') {
                continue;
            }

            if (str_contains($domain, '://')) {
                $parsedHost = parse_url($domain, PHP_URL_HOST);
                $domain = is_string($parsedHost) ? $parsedHost : '';
            }

            $domain = trim($domain, "/\\ ");
            $domain = ltrim($domain, '.');

            if ($domain === '' || preg_match('/[^a-z0-9.-]/', $domain) === 1) {
                continue;
            }

            $domains[] = $domain;
        }

        $domains = array_values(array_unique($domains));
        sort($domains);

        return implode("\n", $domains);
    }
}
