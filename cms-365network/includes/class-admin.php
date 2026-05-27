<?php
/**
 * Admin settings UI for CMS 365NETWORK.
 *
 * @package CMS_365NETWORK
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

final class CMS_365NETWORK_Admin
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->load_admin_menu();
        CMS\Hooks::addAction('cms_admin_menu', [$this, 'register_admin_menu'], 10);
        CMS\Hooks::addAction('register_routes', [$this, 'register_routes'], 10);
        CMS\Hooks::addFilter('admin_menu_items', [$this, 'add_menu_item'], 10);
    }

    public function register_admin_menu(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            '365NETWORK',
            '365NETWORK',
            'manage_options',
            'cms-365network',
            [self::class, 'render_plugin_page_bridge'],
            '🌐',
            48
        );
    }

    public static function render_plugin_page_bridge(): void
    {
        $targetUrl = rtrim((string) SITE_URL, '/') . '/admin/365network';
        echo '<div class="admin-card"><p>Weiterleitung zu 365NETWORK … <a href="' . htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8') . '">Falls nichts passiert, hier klicken</a>.</p></div>';
        echo '<script>window.location.replace(' . json_encode($targetUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ');</script>';
    }

    public function register_routes($router): void
    {
        $router->addRoute('GET', '/admin/365network', [$this, 'render_settings']);
        $router->addRoute('POST', '/admin/365network/settings/save', [$this, 'save_settings']);
    }

    public function add_menu_item(array $menuItems): array
    {
        $currentPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $isActive = is_string($currentPath) && strpos($currentPath, '/admin/365network') === 0;

        $menuItems[] = [
            'type' => 'item',
            'slug' => 'cms-365network',
            'label' => '365NETWORK',
            'icon' => '🌐',
            'url' => '/admin/365network',
            'active' => $isActive,
        ];

        return $menuItems;
    }

    public function render_settings(): void
    {
        $this->require_admin();
        $this->start_admin_layout('365NETWORK', 'cms-365network');
        $this->enqueue_admin_css();

        $settings = CMS_365NETWORK_Database::instance()->get_settings();
        $tab = $this->sanitize_tab((string) ($_GET['tab'] ?? 'domain'));
        $saved = (string) ($_GET['saved'] ?? '') === '1';
        $csrfToken = Security::instance()->generateToken('cms_365network_settings');
        $previewUrl = rtrim((string) SITE_URL, '/') . '/' . trim((string) ($settings['route_slug'] ?? '365network'), '/');
        $mainHost = $this->normalize_host((string) (parse_url((string) SITE_URL, PHP_URL_HOST) ?: ''));
        $domains = $this->normalize_domain_list((string) ($settings['hub_domains'] ?? ''));
        $domainHint = $domains === []
            ? 'Noch keine Zusatzdomain hinterlegt. Die Vorschau ist trotzdem über die interne Route erreichbar.'
            : 'Aktive Zusatzdomain' . (count($domains) === 1 ? '' : 's') . ': ' . implode(', ', $domains);

        echo '<main class="admin-page n365-admin-shell">';
        echo '<header class="admin-page-header">';
        echo '<div><h2>🌐 CMS 365NETWORK</h2><p>Domain-Landingpage, Layout und Netzwerk-Daten konfigurieren.</p></div>';
        echo '<div class="header-actions"><a class="btn btn-secondary" href="' . htmlspecialchars($previewUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">👁️ Vorschau öffnen</a></div>';
        echo '</header>';

        if ($saved) {
            echo '<div class="alert alert-success" role="status">✅ Einstellungen gespeichert.</div>';
        }

        echo '<section class="admin-card n365-status-grid" aria-label="365NETWORK Status">';
        echo '<div class="n365-status-card"><span class="n365-status-card__label">Domain-Status</span><strong>' . htmlspecialchars($domains === [] ? 'Interne Route aktiv' : 'Zusatzdomain aktiv', ENT_QUOTES, 'UTF-8') . '</strong><p>' . htmlspecialchars($domainHint, ENT_QUOTES, 'UTF-8') . '</p></div>';
        echo '<div class="n365-status-card"><span class="n365-status-card__label">Hauptdomain</span><strong>' . htmlspecialchars($mainHost !== '' ? $mainHost : 'nicht erkannt', ENT_QUOTES, 'UTF-8') . '</strong><p>Auf der Hauptdomain bleibt die normale Startseite erhalten.</p></div>';
        echo '</section>';

        $this->render_tabs($tab);

        echo '<form class="admin-card admin-form n365-tab-panel n365-admin-form" method="post" action="' . htmlspecialchars(rtrim((string) SITE_URL, '/') . '/admin/365network/settings/save', ENT_QUOTES, 'UTF-8') . '">';
        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">';
        echo '<input type="hidden" name="tab" value="' . htmlspecialchars($tab, ENT_QUOTES, 'UTF-8') . '">';

        match ($tab) {
            'content' => $this->render_content_tab($settings),
            'layout' => $this->render_layout_tab($settings),
            'sidebar' => $this->render_sidebar_tab($settings),
            'cards' => $this->render_cards_tab($settings),
            'analytics' => $this->render_analytics_tab($settings),
            default => $this->render_domain_tab($settings),
        };

        echo '<div class="form-actions"><button class="btn btn-primary" type="submit">💾 Einstellungen speichern</button></div>';
        echo '</form>';
        echo '</main>';

        $this->end_admin_layout();
    }

    public function save_settings(): void
    {
        $this->require_admin();

        if (!Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'cms_365network_settings')) {
            http_response_code(403);
            echo '<!DOCTYPE html><html lang="de"><body><h1>403</h1><p>Sicherheitscheck fehlgeschlagen.</p></body></html>';
            exit;
        }

        $tab = $this->sanitize_tab((string) ($_POST['tab'] ?? 'domain'));
        $settings = $this->sanitize_settings($_POST);
        CMS_365NETWORK_Database::instance()->save_settings($settings);

        header('Location: ' . rtrim((string) SITE_URL, '/') . '/admin/365network?tab=' . rawurlencode($tab) . '&saved=1');
        exit;
    }

    private function render_tabs(string $activeTab): void
    {
        $tabs = [
            'domain' => '🌐 Domain',
            'content' => '📝 Inhalte',
            'layout' => '🎨 Layout',
            'sidebar' => '📊 Sidebar & Daten',
            'cards' => '🧩 Bereichskarten',
            'analytics' => '📈 Analytics',
        ];

        echo '<nav class="n365-tabs" aria-label="365NETWORK Einstellungen">';
        foreach ($tabs as $slug => $label) {
            $url = rtrim((string) SITE_URL, '/') . '/admin/365network?tab=' . rawurlencode($slug);
            $class = $slug === $activeTab ? 'n365-tab active' : 'n365-tab';
            echo '<a class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
        }
        echo '</nav>';
    }

    private function render_domain_tab(array $settings): void
    {
        echo '<section class="n365-admin-section"><div class="n365-panel-header"><h3>🌐 Domain-Mapping</h3><p>Eine oder mehrere Zusatzdomains als Root-Landingpage für 365NETWORK nutzen.</p></div>';
        $this->checkbox('landing_enabled', 'Landingpage aktivieren', $settings);
        $this->textarea('hub_domains', 'Zusatzdomain(s)', $settings, 'network.example.com', 'Eine Domain pro Zeile oder kommasepariert. Bitte ohne https:// und ohne Pfad eintragen.');
        $this->input('route_slug', 'Interne Vorschau-Route', $settings, '365network', 'text', 'Über diese Route ist die Landingpage unabhängig von der Domain erreichbar.');
        echo '</section>';
    }

    private function render_content_tab(array $settings): void
    {
        echo '<section class="n365-admin-section"><div class="n365-panel-header"><h3>📝 Hero & Featured Card</h3><p>Texte, Buttons und die optionale Empfehlungskarte der Publicsite pflegen.</p></div>';
        $this->input('hero_eyebrow', 'Eyebrow', $settings);
        $this->input('landing_title', 'Titel', $settings);
        $this->textarea('landing_subtitle', 'Einleitung', $settings);
        $this->input('primary_button_label', 'Primärer Button', $settings);
        $this->input('primary_button_url', 'Primäre Button-URL', $settings, '#n365-areas');
        $this->input('secondary_button_label', 'Sekundärer Button', $settings);
        $this->input('secondary_button_url', 'Sekundäre Button-URL', $settings, '/kontakt');
        $this->checkbox('featured_enabled', 'Featured Card anzeigen', $settings);
        $this->input('featured_title', 'Featured Titel', $settings);
        $this->textarea('featured_text', 'Featured Text', $settings);
        $this->input('featured_image_url', 'Featured Bild-URL', $settings, 'https://...', 'url');
        $this->input('featured_url', 'Featured Link', $settings, '/events', 'text');
        echo '</section>';
    }

    private function render_layout_tab(array $settings): void
    {
        echo '<section class="n365-admin-section"><div class="n365-panel-header"><h3>🎨 Layout & Design</h3><p>Breite, Raster, Abstände und ruhige Theme-Farbwerte festlegen.</p></div>';
        $this->select('layout_variant', 'Bereichskarten-Layout', $settings, [
            'grid-2x2' => '2 × 2 Grid',
            'grid-4x1' => '4 × 1 Reihe',
            'auto' => 'Automatisch responsiv',
        ]);
        $this->number('content_width', 'Maximale Breite (px)', $settings, 920, 1500);
        $this->number('card_radius', 'Rundungen (px)', $settings, 0, 40);
        $this->number('section_gap', 'Abstände (px)', $settings, 16, 80);
        $this->input('primary_color', 'Primärfarbe', $settings, '#2563eb', 'color');
        $this->input('accent_color', 'Akzentfarbe', $settings, '#0f766e', 'color');
        $this->input('background_color', 'Hintergrund', $settings, '#f8fafc', 'color');
        $this->input('surface_color', 'Kartenfläche', $settings, '#ffffff', 'color');
        $this->input('text_color', 'Textfarbe', $settings, '#0f172a', 'color');
        $this->input('muted_color', 'Sekundärtext', $settings, '#64748b', 'color');
        $this->input('border_color', 'Rahmenfarbe', $settings, '#e2e8f0', 'color');
        echo '</section>';
    }

    private function render_sidebar_tab(array $settings): void
    {
        echo '<section class="n365-admin-section"><div class="n365-panel-header"><h3>📊 Sidebar & dynamische Inhalte</h3><p>Daten aus Events, Speakern, Firmen und Experten als Vorschau anzeigen.</p></div>';
        $this->checkbox('show_sidebar', 'Sidebar anzeigen', $settings);
        $this->select('sidebar_position', 'Sidebar-Position', $settings, ['right' => 'Rechts', 'left' => 'Links']);
        $this->select('preview_placement', 'Preview-Cards anzeigen', $settings, ['sidebar' => 'In der Sidebar', 'below' => 'Unter den vier Bereichen', 'off' => 'Nicht anzeigen']);
        $this->checkbox('show_events_preview', 'Kommende Events anzeigen', $settings);
        $this->number('sidebar_events_count', 'Anzahl kommende Events', $settings, 0, 8);
        $this->checkbox('show_speakers_preview', 'Zufällige Speaker anzeigen', $settings);
        $this->number('random_speakers_count', 'Anzahl Speaker', $settings, 0, 4);
        $this->checkbox('show_companies_preview', 'Zufällige Firmen anzeigen', $settings);
        $this->number('random_companies_count', 'Anzahl Firmen', $settings, 0, 4);
        $this->checkbox('show_experts_preview', 'Zufällige Experten anzeigen', $settings);
        $this->number('random_experts_count', 'Anzahl Experten', $settings, 0, 4);
        echo '</section>';
    }

    private function render_cards_tab(array $settings): void
    {
        $cards = [
            'events' => 'Events',
            'speakers' => 'Speaker',
            'companies' => 'Firmen',
            'experts' => 'Experten',
        ];

        echo '<section class="n365-admin-section"><div class="n365-panel-header"><h3>🧩 Vier Netzwerk-Bereiche</h3><p>Beschriftung und Zielseiten der vier Einstiegskarten steuern.</p></div><div class="n365-admin-card-grid">';
        foreach ($cards as $key => $label) {
            echo '<fieldset class="n365-admin-mini-card"><legend>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</legend>';
            $this->input($key . '_card_title', 'Titel', $settings);
            $this->textarea($key . '_card_text', 'Text', $settings);
            $this->input($key . '_card_url', 'Ziel-URL', $settings);
            echo '</fieldset>';
        }
        echo '</div></section>';
    }

    private function render_analytics_tab(array $settings): void
    {
        echo '<section class="n365-admin-section"><div class="n365-panel-header"><h3>📈 SEO-/Analyse-Code</h3><p>Matomo oder ähnlichen Analyse-Code ausschließlich auf der 365NETWORK-Publicsite laden.</p></div>';
        $this->checkbox('analytics_enabled', 'Analyse-Code aktivieren', $settings);
        $this->select('analytics_position', 'Ausgabe-Position', $settings, [
            'head' => 'Im head laden (typisch für Matomo)',
            'body_end' => 'Vor dem schließenden body-Tag laden',
        ]);
        $this->tracking_code_textarea('analytics_code', 'Tracking-/Analyse-Code', $settings, 'Matomo-Snippet inklusive <script>…</script> hier einfügen.');
        echo '<p class="admin-help">Wichtig: Bitte nur Code aus vertrauenswürdigen Quellen einfügen und Datenschutz-/Consent-Vorgaben prüfen.</p>';
        echo '</section>';
    }

    private function sanitize_settings(array $post): array
    {
        $defaults = CMS_365NETWORK_Database::instance()->default_settings();
        $settings = $defaults;

        $textKeys = [
            'hero_eyebrow', 'landing_title', 'primary_button_label', 'secondary_button_label', 'featured_title',
            'events_card_title', 'speakers_card_title', 'companies_card_title', 'experts_card_title', 'route_slug',
        ];
        foreach ($textKeys as $key) {
            $settings[$key] = $this->clean_text((string) ($post[$key] ?? $defaults[$key] ?? ''));
        }

        $textareaKeys = ['landing_subtitle', 'featured_text', 'events_card_text', 'speakers_card_text', 'companies_card_text', 'experts_card_text'];
        foreach ($textareaKeys as $key) {
            $settings[$key] = $this->clean_textarea((string) ($post[$key] ?? $defaults[$key] ?? ''));
        }

        $settings['hub_domains'] = implode("\n", $this->normalize_domain_list((string) ($post['hub_domains'] ?? '')));

        foreach (['primary_button_url', 'secondary_button_url', 'featured_url', 'events_card_url', 'speakers_card_url', 'companies_card_url', 'experts_card_url'] as $key) {
            $settings[$key] = $this->safe_url((string) ($post[$key] ?? $defaults[$key] ?? ''));
        }
        $settings['featured_image_url'] = $this->safe_image_url((string) ($post['featured_image_url'] ?? ''));

        $settings['layout_variant'] = $this->enum((string) ($post['layout_variant'] ?? ''), ['grid-2x2', 'grid-4x1', 'auto'], 'grid-2x2');
        $settings['sidebar_position'] = $this->enum((string) ($post['sidebar_position'] ?? ''), ['right', 'left'], 'right');
        $settings['preview_placement'] = $this->enum((string) ($post['preview_placement'] ?? ''), ['sidebar', 'below', 'off'], 'sidebar');
        $settings['analytics_position'] = $this->enum((string) ($post['analytics_position'] ?? ''), ['head', 'body_end'], 'head');

        foreach (['content_width' => [920, 1500], 'card_radius' => [0, 40], 'section_gap' => [16, 80], 'sidebar_events_count' => [0, 8], 'random_speakers_count' => [0, 4], 'random_companies_count' => [0, 4], 'random_experts_count' => [0, 4]] as $key => $range) {
            $settings[$key] = (string) $this->clamp_int($post[$key] ?? $defaults[$key] ?? 0, (int) $range[0], (int) $range[1]);
        }

        foreach (['primary_color', 'accent_color', 'background_color', 'surface_color', 'text_color', 'muted_color', 'border_color'] as $key) {
            $settings[$key] = $this->hex_color((string) ($post[$key] ?? $defaults[$key] ?? ''), (string) $defaults[$key]);
        }

        foreach (['landing_enabled', 'featured_enabled', 'show_sidebar', 'show_events_preview', 'show_speakers_preview', 'show_companies_preview', 'show_experts_preview', 'analytics_enabled'] as $key) {
            $settings[$key] = isset($post[$key]) && (string) $post[$key] === '1' ? '1' : '0';
        }

        $settings['analytics_code'] = $this->sanitize_tracking_code((string) ($post['analytics_code'] ?? ''));

        $routeSlug = trim((string) preg_replace('/[^a-z0-9-]+/i', '-', (string) $settings['route_slug']), '-');
        $settings['route_slug'] = $routeSlug !== '' ? strtolower($routeSlug) : '365network';

        return $settings;
    }

    private function input(string $name, string $label, array $settings, string $placeholder = '', string $type = 'text', string $help = ''): void
    {
        $value = (string) ($settings[$name] ?? '');
        $id = 'n365-' . $name;
        echo '<div class="form-group"><label class="form-label" for="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label><input id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" class="form-control" type="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '" placeholder="' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '"></div>';
        if ($help !== '') {
            echo '<small class="form-text">' . htmlspecialchars($help, ENT_QUOTES, 'UTF-8') . '</small>';
        }
    }

    private function textarea(string $name, string $label, array $settings, string $placeholder = '', string $help = ''): void
    {
        $value = (string) ($settings[$name] ?? '');
        $id = 'n365-' . $name;
        echo '<div class="form-group"><label class="form-label" for="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label><textarea id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" class="form-control" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" rows="4" placeholder="' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</textarea></div>';
        if ($help !== '') {
            echo '<small class="form-text">' . htmlspecialchars($help, ENT_QUOTES, 'UTF-8') . '</small>';
        }
    }

    private function tracking_code_textarea(string $name, string $label, array $settings, string $placeholder = ''): void
    {
        $value = (string) ($settings[$name] ?? '');
        $id = 'n365-' . $name;
        echo '<div class="form-group"><label class="form-label" for="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label><textarea id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" class="form-control n365-code-textarea" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" rows="12" spellcheck="false" placeholder="' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</textarea></div>';
    }

    private function checkbox(string $name, string $label, array $settings): void
    {
        $checked = (string) ($settings[$name] ?? '0') === '1' ? ' checked' : '';
        echo '<label class="checkbox-label"><input type="checkbox" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="1"' . $checked . '> ' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label>';
    }

    private function select(string $name, string $label, array $settings, array $options): void
    {
        $current = (string) ($settings[$name] ?? '');
        $id = 'n365-' . $name;
        echo '<div class="form-group"><label class="form-label" for="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label><select id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" class="form-control" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '">';
        foreach ($options as $value => $optionLabel) {
            $selected = (string) $value === $current ? ' selected' : '';
            echo '<option value="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>' . htmlspecialchars((string) $optionLabel, ENT_QUOTES, 'UTF-8') . '</option>';
        }
        echo '</select></div>';
    }

    private function number(string $name, string $label, array $settings, int $min, int $max): void
    {
        $value = (int) ($settings[$name] ?? $min);
        $id = 'n365-' . $name;
        echo '<div class="form-group"><label class="form-label" for="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label><input id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" class="form-control" type="number" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="' . (int) $value . '" min="' . (int) $min . '" max="' . (int) $max . '"></div>';
    }

    private function load_admin_menu(): void
    {
        $menuFile = ABSPATH . 'admin/partials/admin-menu.php';
        if (is_file($menuFile) && !function_exists('renderAdminLayoutStart')) {
            require_once $menuFile;
        }
    }

    private function start_admin_layout(string $title, string $activePage): void
    {
        $this->load_admin_menu();
        if (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart($title, $activePage);
            return;
        }

        $pageTitle = $title;
        require_once ABSPATH . 'admin/partials/header.php';
        require_once ABSPATH . 'admin/partials/sidebar.php';
    }

    private function end_admin_layout(): void
    {
        if (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
            return;
        }

        require_once ABSPATH . 'admin/partials/footer.php';
    }

    private function enqueue_admin_css(): void
    {
        $css = CMS_365NETWORK_PLUGIN_DIR . 'assets/css/admin.css';
        $version = is_file($css) ? (string) filemtime($css) : CMS_365NETWORK_VERSION;
        echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_365NETWORK_PLUGIN_URL . 'assets/css/admin.css?v=' . $version, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }

    private function require_admin(): void
    {
        if (!Auth::instance()->isAdmin()) {
            header('Location: ' . rtrim((string) SITE_URL, '/') . '/login');
            exit;
        }
    }

    private function sanitize_tab(string $tab): string
    {
        return in_array($tab, ['domain', 'content', 'layout', 'sidebar', 'cards', 'analytics'], true) ? $tab : 'domain';
    }

    private function clean_text(string $value): string
    {
        return trim((string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', strip_tags($value)));
    }

    private function clean_textarea(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", strip_tags($value));
        return trim((string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', $value));
    }

    private function safe_url(string $value): string
    {
        $url = trim($value);
        if ($url === '') {
            return '';
        }
        if (preg_match('/^#[A-Za-z][A-Za-z0-9_-]*$/', $url) === 1) {
            return $url;
        }
        if (str_starts_with($url, '/') && !str_starts_with($url, '//') && !str_contains($url, "\0")) {
            return $url;
        }

        $parts = parse_url($url);
        $scheme = is_array($parts) ? strtolower((string) ($parts['scheme'] ?? '')) : '';
        return in_array($scheme, ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : '';
    }

    private function safe_image_url(string $value): string
    {
        $url = $this->safe_url($value);
        return $url !== '' && $url[0] !== '#' ? $url : '';
    }

    private function enum(string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function clamp_int(mixed $value, int $min, int $max): int
    {
        return max($min, min($max, (int) $value));
    }

    private function hex_color(string $value, string $default): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : $default;
    }

    private function sanitize_tracking_code(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = str_replace("\0", '', $value);
        $value = preg_replace('/<\?(?:php)?|\?>/i', '', $value) ?? '';
        $value = trim($value);

        if (strlen($value) > 20000) {
            $value = substr($value, 0, 20000);
        }

        return $value;
    }

    private function normalize_domain_list(string $value): array
    {
        $parts = preg_split('/[\s,;]+/', $value) ?: [];
        $domains = [];
        foreach ($parts as $part) {
            $domain = $this->normalize_host($part);
            if ($domain !== '' && !in_array($domain, $domains, true)) {
                $domains[] = $domain;
            }
        }

        return $domains;
    }

    private function normalize_host(string $host): string
    {
        $host = trim(strtolower($host));
        if ($host === '') {
            return '';
        }

        if (str_contains($host, '://')) {
            $parsedHost = parse_url($host, PHP_URL_HOST);
            $host = is_string($parsedHost) ? $parsedHost : '';
        }

        $host = preg_replace('/:\d+$/', '', $host) ?? '';
        $host = trim($host, '.');
        $host = preg_replace('/^www\./', '', $host) ?? '';
        return preg_match('/^[a-z0-9.-]+$/', $host) === 1 ? $host : '';
    }
}
