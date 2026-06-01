<?php
/**
 * @package CMS_Downloads
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Downloads_Public_Controller
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function register_routes($router): void
    {
        if (!is_object($router) || !method_exists($router, 'addRoute')) {
            $this->log_event('register_routes aborted: invalid router instance.');
            return;
        }

        $router->addRoute('GET', '/downloads', [$this, 'archive_page']);
        $router->addRoute('GET', '/en/downloads', [$this, 'archive_page']);
        $router->addRoute('GET', '/downloads/category/:slug', [$this, 'archive_page']);
        $router->addRoute('GET', '/en/downloads/category/:slug', [$this, 'archive_page']);
        $router->addRoute('GET', '/downloads/file/:slug', [$this, 'download_file']);
        $router->addRoute('GET', '/en/downloads/file/:slug', [$this, 'download_file']);
    }

    public function render_nav_item(): void
    {
        $settings = CMS_Downloads_Repository::instance()->get_settings();
        if (($settings['show_nav_link'] ?? '0') !== '1') {
            return;
        }

        $lang = $this->current_public_lang();
        $label = $this->public_setting($settings, 'nav_label', $lang, 'Downloads');
        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $active = str_contains((string) $currentPath, '/downloads') ? 'active' : '';
        $href = rtrim((string) SITE_URL, '/') . $this->localized_path('downloads', $lang);

        echo '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" class="nav-link ' . htmlspecialchars($active, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
    }

    public function archive_page(string $slug = ''): void
    {
        $lang = $this->current_public_lang();
        $repository = CMS_Downloads_Repository::instance();
        $settings = $repository->get_settings();
        $categories = $repository->get_categories(true);
        $currentCategory = $slug !== '' ? $repository->get_category_by_slug($slug) : null;
        $search = mb_substr(trim(strip_tags((string) ($_GET['q'] ?? ''))), 0, 120, 'UTF-8');
        $perPage = max(6, min(120, (int) ($settings['downloads_per_page'] ?? 24)));
        $currentPage = max(1, (int) ($_GET['page'] ?? 1));

        $baseFilters = [
            'status' => 'active',
            'category_slug' => $slug,
            'search' => $search,
        ];

        $totalDownloads = $repository->count_downloads($baseFilters);
        $totalPages = max(1, (int) ceil($totalDownloads / $perPage));
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }

        $downloads = $repository->get_downloads($baseFilters + [
            'limit' => $perPage,
            'offset' => ($currentPage - 1) * $perPage,
        ]);

        $typeTemplates = $repository->get_type_templates();
        $archiveTitle = $this->public_setting($settings, 'archive_title', $lang, 'Downloads');
        $archiveDescription = $this->public_setting($settings, 'archive_description', $lang, '');
        $publicLang = $lang;
        $theme = class_exists('CMS\\ThemeManager') ? \CMS\ThemeManager::instance() : null;

        if ($theme !== null) {
            $theme->getHeader();
        }

        include CMS_DOWNLOADS_PLUGIN_DIR . 'templates/archive-downloads.php';

        if ($theme !== null) {
            $theme->getFooter();
        }
    }

    public function download_file(string $slug): void
    {
        $lang = $this->current_public_lang();
        $slug = trim($slug);
        if ($slug === '' || preg_match('/^[\p{L}0-9-]{1,190}$/u', $slug) !== 1) {
            http_response_code(404);
            echo '<h1>' . htmlspecialchars($this->public_label('file_unavailable', $lang), ENT_QUOTES, 'UTF-8') . '</h1>';
            return;
        }

        $repository = CMS_Downloads_Repository::instance();
        $settings = $repository->get_settings();

        if (CMS_Downloads_Security::is_rate_limited($settings)) {
            http_response_code(429);
            header('Retry-After: ' . (string) max(30, (int) ($settings['rate_limit_window'] ?? 60)));
            echo '<h1>' . htmlspecialchars($this->public_label('rate_limited', $lang), ENT_QUOTES, 'UTF-8') . '</h1>';
            return;
        }

        $download = $repository->get_download_by_slug($slug);
        if ($download === null) {
            http_response_code(404);
            echo '<h1>' . htmlspecialchars($this->public_label('not_found', $lang), ENT_QUOTES, 'UTF-8') . '</h1>';
            return;
        }

        if ((int) ($download['requires_login'] ?? 0) === 1 && class_exists('CMS\\Auth') && !\CMS\Auth::instance()->isLoggedIn()) {
            header('Location: ' . SITE_URL . '/login');
            exit;
        }

        $externalUrl = trim((string) ($download['external_url'] ?? ''));
        if ($externalUrl !== '') {
            if (!$this->is_allowed_external_url($externalUrl, $settings)) {
                $this->log_event('Blocked invalid external download URL for slug "' . $this->safe_log_fragment($slug) . '"');
                http_response_code(404);
                echo '<h1>404 – Datei nicht verfügbar</h1>';
                return;
            }

            $confirmExternal = isset($_GET['external']) && (string) $_GET['external'] === 'continue';
            if (($settings['show_external_notice'] ?? '1') === '1' && !$confirmExternal) {
                $this->render_external_redirect_notice($download, $externalUrl, $settings, $lang);
                return;
            }

            if ($confirmExternal) {
                $token = trim((string) ($_GET['token'] ?? ''));
                $expires = (int) ($_GET['expires'] ?? 0);
                if (!CMS_Downloads_Security::verify_external_continue_token((int) $download['id'], $slug, $token, $expires)) {
                    http_response_code(403);
                    echo '<h1>' . htmlspecialchars($this->public_label('external_token_invalid', $lang), ENT_QUOTES, 'UTF-8') . '</h1>';
                    return;
                }
            }

            $repository->increment_download_count((int) $download['id']);
            header('Location: ' . $externalUrl, true, 302);
            exit;
        }

        $relativePath = trim((string) ($download['file_path'] ?? ''), '/\\');
        if ($relativePath === '' || !defined('UPLOAD_PATH')) {
            http_response_code(404);
            echo '<h1>404 – Datei nicht verfügbar</h1>';
            return;
        }

        $absolutePath = $this->resolve_download_path($relativePath);
        if ($absolutePath === null || !is_file($absolutePath) || !is_readable($absolutePath)) {
            http_response_code(404);
            echo '<h1>404 – Datei nicht verfügbar</h1>';
            return;
        }

        $repository->increment_download_count((int) $download['id']);

        $mime = function_exists('mime_content_type') ? (string) mime_content_type($absolutePath) : 'application/octet-stream';
        if ($mime === '' || preg_match('/^[a-z0-9.+-]+\/[a-z0-9.+-]+$/i', $mime) !== 1) {
            $mime = 'application/octet-stream';
        }
        $filename = $this->safe_download_filename((string) ($download['file_name'] ?? basename($absolutePath)));
        $asciiFilename = $this->safe_ascii_filename($filename);

        header('Content-Description: File Transfer');
        header('X-Content-Type-Options: nosniff');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $asciiFilename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
        header('Content-Length: ' . (string) filesize($absolutePath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        readfile($absolutePath);
        exit;
    }

    private function resolve_download_path(string $relativePath): ?string
    {
        $baseDirectory = realpath((string) UPLOAD_PATH);
        if ($baseDirectory === false) {
            $this->log_event('UPLOAD_PATH could not be resolved.');
            return null;
        }

        $normalizedRelativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($relativePath, '/\\'));
        if ($normalizedRelativePath === '' || str_contains($normalizedRelativePath, '..' . DIRECTORY_SEPARATOR) || str_starts_with($normalizedRelativePath, '..')) {
            $this->log_event('Blocked invalid relative download path.');
            return null;
        }

        $requiredPrefix = 'downloads' . DIRECTORY_SEPARATOR;
        if (!str_starts_with($normalizedRelativePath, $requiredPrefix)) {
            $this->log_event('Blocked access to file outside downloads directory.');
            return null;
        }

        $candidatePath = $baseDirectory . DIRECTORY_SEPARATOR . $normalizedRelativePath;
        $resolvedPath = realpath($candidatePath);

        if ($resolvedPath === false) {
            return null;
        }

        $allowedPrefix = rtrim($baseDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($resolvedPath !== $baseDirectory && !str_starts_with($resolvedPath, $allowedPrefix)) {
            $this->log_event('Blocked path traversal attempt for relative path "' . $this->safe_log_fragment($relativePath) . '"');
            return null;
        }

        return $resolvedPath;
    }

    private function is_allowed_external_url(string $url, array $settings = []): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        if (!empty($parts['user']) || !empty($parts['pass'])) {
            return false;
        }

        if (!in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        if ($port !== null && !in_array($port, [80, 443], true)) {
            return false;
        }

        return $this->is_allowed_external_host((string) $parts['host'], $settings);
    }

    private function is_allowed_external_host(string $host, array $settings): bool
    {
        $normalizedHost = mb_strtolower(trim($host), 'UTF-8');
        $normalizedHost = trim($normalizedHost, '[]');
        if ($normalizedHost === '') {
            return false;
        }

        if (!$this->is_public_external_host($normalizedHost)) {
            $this->log_event('Blocked non-public external download host "' . $this->safe_log_fragment($normalizedHost) . '"');
            return false;
        }

        $allowlist = $this->parse_allowlist((string) ($settings['external_allowed_domains'] ?? ''));
        if ($allowlist === []) {
            return true;
        }

        foreach ($allowlist as $allowedDomain) {
            if ($normalizedHost === $allowedDomain || str_ends_with($normalizedHost, '.' . $allowedDomain)) {
                return true;
            }
        }

        $this->log_event('Blocked external download host "' . $this->safe_log_fragment($normalizedHost) . '" because it is not in the allowlist.');
        return false;
    }

    private function is_public_external_host(string $host): bool
    {
        if (in_array($host, ['localhost', 'localhost.localdomain'], true)) {
            return false;
        }

        foreach (['.local', '.internal', '.intranet', '.lan'] as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return false;
            }
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }

        return preg_match('/^(?=.{1,253}$)(?!-)(?:[a-z0-9-]{1,63}\.)+[a-z]{2,63}$/', $host) === 1;
    }

    private function safe_download_filename(string $filename): string
    {
        $filename = basename(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $filename));
        $filename = preg_replace('/[\x00-\x1F\x7F"\\\/]+/', '-', $filename) ?? '';
        $filename = trim($filename, " .\t\n\r\0\x0B-");

        if ($filename === '') {
            return 'download.bin';
        }

        return mb_substr($filename, 0, 180, 'UTF-8');
    }

    private function safe_ascii_filename(string $filename): string
    {
        $ascii = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename) ?? '';
        $ascii = trim($ascii, '.-_');

        return $ascii !== '' ? substr($ascii, 0, 180) : 'download.bin';
    }

    /**
     * @return array<int,string>
     */
    private function parse_allowlist(string $raw): array
    {
        $items = preg_split('/[\r\n,]+/', $raw) ?: [];
        $domains = [];

        foreach ($items as $item) {
            $domain = mb_strtolower(trim($item), 'UTF-8');
            if ($domain === '') {
                continue;
            }

            if (preg_match('/[^a-z0-9.-]/', $domain) === 1) {
                continue;
            }

            $domains[] = $domain;
        }

        return array_values(array_unique($domains));
    }

    private function log_event(string $message): void
    {
        error_log('CMS Downloads: ' . $message);
    }

    private function safe_log_fragment(string $value): string
    {
        return preg_replace('/[\x00-\x1F\x7F]+/', ' ', $value) ?? '';
    }

    private function render_external_redirect_notice(array $download, string $externalUrl, array $settings, string $lang = 'de'): void
    {
        $continueUrl = CMS_Downloads_Security::build_external_continue_url($download);
        if ($lang === 'en') {
            $continueUrl = str_replace('/downloads/file/', '/en/downloads/file/', $continueUrl);
        }
        $backUrl = rtrim((string) SITE_URL, '/') . $this->localized_path('downloads', $lang);
        if (!empty($download['category_slug'])) {
            $backUrl = rtrim((string) SITE_URL, '/') . $this->localized_path('downloads/category/' . rawurlencode((string) $download['category_slug']), $lang);
        }

        $theme = class_exists('CMS\\ThemeManager') ? \CMS\ThemeManager::instance() : null;

        if ($theme !== null) {
            $theme->getHeader();
        }

        include CMS_DOWNLOADS_PLUGIN_DIR . 'templates/external-redirect.php';

        if ($theme !== null) {
            $theme->getFooter();
        }
    }

    private function current_public_lang(): string
    {
        if (function_exists('cms_plugin_public_language')) {
            return cms_plugin_public_language();
        }

        return 'de';
    }

    private function localized_path(string $path, string $lang): string
    {
        if (function_exists('cms_plugin_public_localized_path')) {
            return cms_plugin_public_localized_path($path, $lang);
        }

        return '/' . trim($path, '/');
    }

    private function public_setting(array $settings, string $key, string $lang, string $fallback = ''): string
    {
        if (function_exists('cms_plugin_public_i18n_value')) {
            return cms_plugin_public_i18n_value($settings, $key, $lang, $fallback);
        }

        return trim((string) ($settings[$key] ?? $fallback));
    }

    private function public_label(string $key, string $lang): string
    {
        $labels = [
            'file_unavailable' => ['de' => '404 – Datei nicht verfügbar', 'en' => '404 – File unavailable'],
            'not_found' => ['de' => '404 – Download nicht gefunden', 'en' => '404 – Download not found'],
            'rate_limited' => ['de' => '429 – Zu viele Download-Anfragen. Bitte später erneut versuchen.', 'en' => '429 – Too many download requests. Please try again later.'],
            'external_token_invalid' => ['de' => '403 – Der externe Download-Link ist abgelaufen oder ungültig.', 'en' => '403 – The external download link has expired or is invalid.'],
            'external_title' => ['de' => 'Externer Download', 'en' => 'External download'],
            'external_body_prefix' => ['de' => 'Der Download', 'en' => 'The download'],
            'external_body_suffix' => ['de' => 'liegt auf einer externen Website.', 'en' => 'is hosted on an external website.'],
            'external_target' => ['de' => 'Ziel', 'en' => 'Target'],
            'external_continue' => ['de' => 'Externen Download öffnen', 'en' => 'Open external download'],
            'back' => ['de' => 'Zurück', 'en' => 'Back'],
            'external_note' => ['de' => 'Hinweis: Externe Downloads werden nicht direkt von 365CMS ausgeliefert. Bitte prüfe bei sensiblen Inhalten die Ziel-Domain und den Anbieter, bevor du fortfährst.', 'en' => 'Note: External downloads are not served directly by 365CMS. Please verify the target domain and provider before continuing.'],
            'search_placeholder' => ['de' => 'Downloads durchsuchen', 'en' => 'Search downloads'],
            'search_button' => ['de' => 'Suchen', 'en' => 'Search'],
            'all_categories' => ['de' => 'Alle', 'en' => 'All'],
            'category_label' => ['de' => 'Kategorie', 'en' => 'Category'],
            'empty_title' => ['de' => 'Keine Downloads gefunden', 'en' => 'No downloads found'],
            'empty_body' => ['de' => 'Für diese Auswahl sind aktuell noch keine öffentlichen Dateien hinterlegt.', 'en' => 'There are currently no public files available for this selection.'],
            'download_now' => ['de' => 'Jetzt laden', 'en' => 'Download now'],
            'version' => ['de' => 'Version', 'en' => 'Version'],
            'downloads_count' => ['de' => 'Downloads', 'en' => 'Downloads'],
        ];

        return (string) ($labels[$key][$lang] ?? $labels[$key]['de'] ?? $key);
    }
}
