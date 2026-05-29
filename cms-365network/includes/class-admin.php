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
        if (function_exists('cms_register_admin_menu')) {
            cms_register_admin_menu([
                'slug' => 'cms-365network',
                'label' => '365Network Hub',
                'icon' => 'ti-network',
                'callback' => 'hub_admin_page',
                'capability' => 'manage_plugins',
            ]);
            return;
        }

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
        self::redirect_static('/admin/365network');
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
        $saveFailed = (string) ($_GET['error'] ?? '') === 'save_failed';
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
            $this->admin_notice('Einstellungen gespeichert.', 'success');
        }

        if ($saveFailed) {
            $this->admin_notice('Einstellungen konnten nicht gespeichert werden. Bitte Server-Log prüfen.', 'error');
        }

        echo '<section class="admin-card n365-status-grid" aria-label="365NETWORK Status">';
        echo '<div class="n365-status-card"><span class="n365-status-card__label">Domain-Status</span><strong>' . htmlspecialchars($domains === [] ? 'Interne Route aktiv' : 'Zusatzdomain aktiv', ENT_QUOTES, 'UTF-8') . '</strong><p>' . htmlspecialchars($domainHint, ENT_QUOTES, 'UTF-8') . '</p></div>';
        echo '<div class="n365-status-card"><span class="n365-status-card__label">Hauptdomain</span><strong>' . htmlspecialchars($mainHost !== '' ? $mainHost : 'nicht erkannt', ENT_QUOTES, 'UTF-8') . '</strong><p>Auf der Hauptdomain bleibt die normale Startseite erhalten.</p></div>';
        echo '</section>';

        $this->render_tabs($tab);

        $formClass = 'admin-card admin-form n365-tab-panel n365-admin-form' . ($this->is_hub_section_tab($tab) ? ' hub-admin-form' : '');
        echo '<form class="' . htmlspecialchars($formClass, ENT_QUOTES, 'UTF-8') . '" method="post" action="' . htmlspecialchars($this->admin_url('/admin/365network/settings/save'), ENT_QUOTES, 'UTF-8') . '" novalidate>';
        $this->nonce_field();
        echo '<input type="hidden" name="tab" value="' . htmlspecialchars($tab, ENT_QUOTES, 'UTF-8') . '">';

        if ($this->is_hub_section_tab($tab)) {
            $sectionKey = $this->hub_section_for_tab($tab);
            $this->render_hub_section_tab($sectionKey, CMS_365NETWORK_Database::instance()->get_hub_setting_rows());
        } else {
            match ($tab) {
                'layout' => $this->render_layout_tab($settings),
                'sidebar' => $this->render_sidebar_tab($settings),
                'analytics' => $this->render_analytics_tab($settings),
                default => $this->render_domain_tab($settings),
            };
        }

        echo '<div class="form-actions"><button class="btn btn-primary" type="submit">💾 Einstellungen speichern</button></div>';
        echo '</form>';
        $this->render_media_picker_modal();
        echo '</main>';

        $this->enqueue_admin_scripts();
        $this->end_admin_layout();
    }

    public function save_settings(): void
    {
        $this->require_admin();

        if (!$this->verify_nonce()) {
            http_response_code(403);
            echo '<!DOCTYPE html><html lang="de"><body><h1>403</h1><p>Sicherheitscheck fehlgeschlagen.</p></body></html>';
            exit;
        }

        $tab = $this->sanitize_tab((string) ($_POST['tab'] ?? 'domain'));
        $database = CMS_365NETWORK_Database::instance();

        if ($this->is_hub_section_tab($tab)) {
            $settings = $this->sanitize_hub_settings($_POST, $this->hub_rows_for_section($database->get_hub_setting_rows(), $this->hub_section_for_tab($tab)));
            if (!$database->save_hub_settings($settings)) {
                $this->redirect('/admin/365network?tab=' . rawurlencode($tab) . '&error=save_failed');
                return;
            }

            $this->clear_public_cache('hub_settings_save');

            $this->redirect('/admin/365network?tab=' . rawurlencode($tab) . '&saved=1');
            return;
        }

        $settings = $this->sanitize_settings($_POST, $database->get_settings(), $tab);
        if (!$database->save_settings($settings)) {
            $this->redirect('/admin/365network?tab=' . rawurlencode($tab) . '&error=save_failed');
            return;
        }

        $this->clear_public_cache('settings_save');

        $this->redirect('/admin/365network?tab=' . rawurlencode($tab) . '&saved=1');
    }

    private function clear_public_cache(string $reason): void
    {
        try {
            if (class_exists('CMS\\CacheManager')) {
                CMS\CacheManager::instance()->clear();
            }

            if (class_exists('CMS\\Hooks')) {
                CMS\Hooks::doAction('performance_cache_purged', 'cms-365network', [
                    'reason' => $reason,
                    'purged_at' => date('c'),
                ]);
                CMS\Hooks::doAction('performance_cdn_purge_requested', [
                    'scope' => 'cms-365network',
                    'source' => 'cms-365network.' . $reason,
                    'purged_at' => date('c'),
                ]);
            }
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK cache clear failed: ' . $e->getMessage());
        }
    }

    private function render_tabs(string $activeTab): void
    {
        $tabs = [
            'domain' => '🌐 Domain',
            'hub-order' => '↕️ Reihenfolge',
            'hub-featured' => '⭐ Featured',
            'hub-hero' => '🏁 Hero',
            'hub-stats' => '📊 Kennzahlen',
            'hub-band' => '🔎 Teaser & Suche',
            'hub-areas' => '🧭 Bereiche',
            'hub-toolbox' => '🧰 Toolbox',
            'layout' => '🎨 Seitenlayout',
            'sidebar' => '📊 Sidebar & Daten',
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

    /**
     * @return array<string,array{section:string,title:string,description:string}>
     */
    private function hub_section_tabs(): array
    {
        return [
            'hub-order' => [
                'section' => 'order',
                'title' => '↕️ Bereichs-Reihenfolge',
                'description' => 'Sortiere die sichtbaren Public-Bereiche der 365NETWORK-Landingpage. Deaktivierte Bereiche bleiben ausgeblendet.',
            ],
            'hub-featured' => [
                'section' => 'featured',
                'title' => '⭐ Featured Card',
                'description' => 'Content-Header oberhalb des Hero: aktivieren, Texte pflegen und Darstellung gestalten.',
            ],
            'hub-hero' => [
                'section' => 'hero',
                'title' => '🏁 Hero-Bereich',
                'description' => 'Hauptüberschrift, CTA-Buttons, Ausrichtung und Farben des Einstiegsbereichs.',
            ],
            'hub-stats' => [
                'section' => 'stats',
                'title' => '📊 Kennzahlen',
                'description' => 'Zähler-Kacheln, Beschriftungen, Zielseiten und Kachel-Design konfigurieren.',
            ],
            'hub-band' => [
                'section' => 'band',
                'title' => '🔎 Teaser & Suche',
                'description' => 'Nächstes Event, Suche, Texte, Layout und visuelle Band-Darstellung steuern.',
            ],
            'hub-areas' => [
                'section' => 'areas',
                'title' => '🧭 Direkteinstieg',
                'description' => 'Bereichskarten einzeln aktivieren, Inhalte pflegen und das Kartenraster gestalten.',
            ],
            'hub-toolbox' => [
                'section' => 'toolbox',
                'title' => '🧰 Toolbox',
                'description' => 'Optionaler Tool-Bereich aus cms-m365tools oder Legacy-m365toolbox mit Limit, Link, Layout und Card-Design.',
            ],
        ];
    }

    private function is_hub_section_tab(string $tab): bool
    {
        return isset($this->hub_section_tabs()[$tab]);
    }

    private function hub_section_for_tab(string $tab): string
    {
        return (string) ($this->hub_section_tabs()[$tab]['section'] ?? 'featured');
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function hub_rows_for_section(array $rows, string $sectionKey): array
    {
        return array_values(array_filter($rows, static fn(array $row): bool => (string) ($row['section'] ?? '') === $sectionKey));
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     */
    private function render_hub_section_tab(string $sectionKey, array $rows): void
    {
        $tabs = $this->hub_section_tabs();
        $activeMeta = null;
        foreach ($tabs as $meta) {
            if ($meta['section'] === $sectionKey) {
                $activeMeta = $meta;
                break;
            }
        }

        $sectionRows = $this->hub_rows_for_section($rows, $sectionKey);
        $groups = [
            'activation' => [],
            'content' => [],
            'layout' => [],
            'design' => [],
        ];

        foreach ($sectionRows as $row) {
            $groups[$this->hub_field_group((string) ($row['setting_key'] ?? ''), (string) ($row['setting_type'] ?? 'text'))][] = $row;
        }

        echo '<section class="n365-admin-section n365-hub-section-tab">';
        echo '<div class="n365-panel-header"><h3>' . htmlspecialchars((string) ($activeMeta['title'] ?? '🧭 Hub-Bereich'), ENT_QUOTES, 'UTF-8') . '</h3><p>' . htmlspecialchars((string) ($activeMeta['description'] ?? 'Diesen Bereich der öffentlichen 365NETWORK-Landingpage konfigurieren.'), ENT_QUOTES, 'UTF-8') . '</p></div>';
        echo '<div class="alert n365-info-alert">ℹ️ Dieser Tab speichert nur diesen Bereich. Texte, Layout und Design werden direkt in der öffentlichen Hub-Ausgabe verwendet.</div>';

        $groupLabels = [
            'activation' => '✅ Aktivierung',
            'content' => '📝 Texte & Inhalte',
            'layout' => '📐 Layout',
            'design' => '🎨 Design',
        ];

        foreach ($groupLabels as $groupKey => $label) {
            if ($groups[$groupKey] === []) {
                continue;
            }

            echo '<fieldset class="hub-admin-section hub-admin-group hub-admin-group--' . htmlspecialchars($groupKey, ENT_QUOTES, 'UTF-8') . '">';
            echo '<legend class="hub-admin-section-title">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</legend>';
            echo '<div class="hub-admin-fields">';
            foreach ($groups[$groupKey] as $field) {
                $this->render_hub_setting_field($field);
            }
            echo '</div></fieldset>';
        }

        echo '</section>';
    }

    private function hub_field_group(string $key, string $type): string
    {
        if (in_array($key, ['hub_section_order', 'hub_area_card_order'], true)) {
            return 'layout';
        }

        if ($type === 'bool' || str_ends_with($key, '_visible')) {
            return 'activation';
        }

        if ($type === 'color' || str_ends_with($key, '_radius') || str_contains($key, '_color')) {
            return 'design';
        }

        if ($type === 'int' || str_ends_with($key, '_layout') || str_ends_with($key, '_style') || str_ends_with($key, '_width') || str_contains($key, 'limit')) {
            return 'layout';
        }

        return 'content';
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     */
    private function render_hub_tab(array $rows): void
    {
        $sections = [
            'featured' => 'Featured Card',
            'hero' => 'Hero-Bereich',
            'stats' => 'Zähler-Kacheln',
            'band' => 'Teaser-Band',
            'areas' => 'Direkteinstieg',
            'toolbox' => 'Toolbox',
        ];

        $grouped = [];
        foreach ($rows as $row) {
            $section = (string) ($row['section'] ?? '');
            if ($section !== '') {
                $grouped[$section][] = $row;
            }
        }

        echo '<section class="n365-admin-section"><div class="n365-panel-header"><h3>🧭 Hub-Bereiche</h3><p>Featured Card, Hero, Kennzahlen, Teaser-Band, Direkteinstieg und Toolbox zentral für die Public-Landingpage steuern.</p></div>';
        echo '<div class="alert n365-info-alert">ℹ️ Diese Einstellungen überschreiben die Hub-Inhalte auf der öffentlichen 365NETWORK-Landingpage. Domain und Analytics bleiben in ihren eigenen Tabs.</div>';

        foreach ($sections as $sectionKey => $sectionLabel) {
            if (empty($grouped[$sectionKey])) {
                continue;
            }

            echo '<div class="hub-admin-section">';
            echo '<h3 class="hub-admin-section-title">' . htmlspecialchars($sectionLabel, ENT_QUOTES, 'UTF-8') . '</h3>';
            echo '<div class="hub-admin-fields">';

            foreach ($grouped[$sectionKey] as $field) {
                $this->render_hub_setting_field($field);
            }

            echo '</div></div>';
        }

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
        $this->input('primary_color', 'Primärfarbe', $settings, '#e6a817', 'color');
        $this->input('accent_color', 'Akzentfarbe', $settings, '#e6a817', 'color');
        $this->input('background_color', 'Hintergrund', $settings, '#e8ecf0', 'color');
        $this->input('surface_color', 'Kartenfläche', $settings, '#ffffff', 'color');
        $this->input('text_color', 'Textfarbe', $settings, '#1a2e4a', 'color');
        $this->input('muted_color', 'Sekundärtext', $settings, '#5a6a7a', 'color');
        $this->input('border_color', 'Rahmenfarbe', $settings, '#dce3ec', 'color');
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

    private function sanitize_settings(array $post, array $existing = [], string $tab = 'domain'): array
    {
        $defaults = CMS_365NETWORK_Database::instance()->default_settings();
        $settings = array_merge($defaults, array_intersect_key($existing, $defaults));
        $activeKeys = array_flip($this->setting_keys_for_tab($tab));
        $isActiveKey = static fn(string $key): bool => isset($activeKeys[$key]);

        $textKeys = [
            'hero_eyebrow', 'landing_title', 'primary_button_label', 'secondary_button_label', 'featured_title',
            'events_card_title', 'speakers_card_title', 'companies_card_title', 'experts_card_title', 'route_slug',
        ];
        foreach ($textKeys as $key) {
            if ($isActiveKey($key) && array_key_exists($key, $post)) {
                $settings[$key] = $this->clean_text((string) $post[$key]);
            }
        }

        $textareaKeys = ['landing_subtitle', 'featured_text', 'events_card_text', 'speakers_card_text', 'companies_card_text', 'experts_card_text'];
        foreach ($textareaKeys as $key) {
            if ($isActiveKey($key) && array_key_exists($key, $post)) {
                $settings[$key] = $this->clean_textarea((string) $post[$key]);
            }
        }

        if ($isActiveKey('hub_domains') && array_key_exists('hub_domains', $post)) {
            $settings['hub_domains'] = implode("\n", $this->normalize_domain_list((string) $post['hub_domains']));
        }

        foreach (['primary_button_url', 'secondary_button_url', 'featured_url', 'events_card_url', 'speakers_card_url', 'companies_card_url', 'experts_card_url'] as $key) {
            if ($isActiveKey($key) && array_key_exists($key, $post)) {
                $settings[$key] = $this->safe_url((string) $post[$key]);
            }
        }
        if ($isActiveKey('featured_image_url') && array_key_exists('featured_image_url', $post)) {
            $settings['featured_image_url'] = $this->safe_image_url((string) $post['featured_image_url']);
        }

        if ($isActiveKey('layout_variant') && array_key_exists('layout_variant', $post)) {
            $settings['layout_variant'] = $this->enum((string) $post['layout_variant'], ['grid-2x2', 'grid-4x1', 'auto'], (string) ($settings['layout_variant'] ?? 'grid-2x2'));
        }
        if ($isActiveKey('sidebar_position') && array_key_exists('sidebar_position', $post)) {
            $settings['sidebar_position'] = $this->enum((string) $post['sidebar_position'], ['right', 'left'], (string) ($settings['sidebar_position'] ?? 'right'));
        }
        if ($isActiveKey('preview_placement') && array_key_exists('preview_placement', $post)) {
            $settings['preview_placement'] = $this->enum((string) $post['preview_placement'], ['sidebar', 'below', 'off'], (string) ($settings['preview_placement'] ?? 'sidebar'));
        }
        if ($isActiveKey('analytics_position') && array_key_exists('analytics_position', $post)) {
            $settings['analytics_position'] = $this->enum((string) $post['analytics_position'], ['head', 'body_end'], (string) ($settings['analytics_position'] ?? 'head'));
        }

        foreach (['content_width' => [920, 1500], 'card_radius' => [0, 40], 'section_gap' => [16, 80], 'sidebar_events_count' => [0, 8], 'random_speakers_count' => [0, 4], 'random_companies_count' => [0, 4], 'random_experts_count' => [0, 4]] as $key => $range) {
            if ($isActiveKey($key) && array_key_exists($key, $post)) {
                $settings[$key] = (string) $this->clamp_int($post[$key], (int) $range[0], (int) $range[1]);
            }
        }

        foreach (['primary_color', 'accent_color', 'background_color', 'surface_color', 'text_color', 'muted_color', 'border_color'] as $key) {
            if ($isActiveKey($key) && array_key_exists($key, $post)) {
                $settings[$key] = $this->hex_color((string) $post[$key], (string) ($settings[$key] ?? $defaults[$key]));
            }
        }

        foreach (['landing_enabled', 'featured_enabled', 'show_sidebar', 'show_events_preview', 'show_speakers_preview', 'show_companies_preview', 'show_experts_preview', 'analytics_enabled'] as $key) {
            if ($isActiveKey($key)) {
                $settings[$key] = isset($post[$key]) && (string) $post[$key] === '1' ? '1' : '0';
            }
        }

        if ($isActiveKey('analytics_code') && array_key_exists('analytics_code', $post)) {
            $settings['analytics_code'] = $this->sanitize_tracking_code((string) $post['analytics_code']);
        }

        $routeSlug = trim((string) preg_replace('/[^a-z0-9-]+/i', '-', (string) $settings['route_slug']), '-');
        $settings['route_slug'] = $routeSlug !== '' ? strtolower($routeSlug) : '365network';

        return $settings;
    }

    /**
     * @param array<string,mixed> $post
     * @param array<int,array<string,mixed>> $rows
     * @return array<string,string>
     */
    private function sanitize_hub_settings(array $post, array $rows): array
    {
        $settings = [];

        foreach ($rows as $row) {
            $key = (string) ($row['setting_key'] ?? '');
            if ($key === '' || !str_starts_with($key, 'hub_')) {
                continue;
            }

            $type = (string) ($row['setting_type'] ?? 'text');
            $rawValue = $post[$key] ?? '';
            if (in_array($key, ['hub_section_order', 'hub_area_card_order'], true) && isset($post[$key . '_items']) && is_array($post[$key . '_items'])) {
                $rawValue = implode(',', array_map('strval', $post[$key . '_items']));
            }

            if (is_array($rawValue)) {
                $rawValue = implode(',', array_map('strval', $rawValue));
            }

            $value = (string) $rawValue;
            if ($type === 'bool') {
                $settings[$key] = trim($value) === '1' ? '1' : '0';
                continue;
            }

            if ($type === 'int') {
                [$min, $max] = $this->hub_int_range($key);
                $settings[$key] = (string) $this->clamp_int($value, $min, $max);
                continue;
            }

            if ($type === 'color') {
                $settings[$key] = $this->hex_color($value, (string) ($row['setting_val'] ?? '#000000'));
                continue;
            }

            if ($type === 'select') {
                $options = array_keys($this->hub_select_options($key));
                $settings[$key] = $options === [] ? $this->clean_text($value) : $this->enum($value, $options, (string) ($row['setting_val'] ?? $options[0]));
                continue;
            }

            if ($key === 'hub_section_order') {
                $settings[$key] = $this->sanitize_order_value($value, array_keys($this->hub_order_items('sections')));
                continue;
            }

            if ($key === 'hub_area_card_order') {
                $settings[$key] = $this->sanitize_order_value($value, array_keys($this->hub_order_items('areas')));
                continue;
            }

            if ($key === 'hub_featured_image_url') {
                $settings[$key] = $this->safe_image_url($value);
                continue;
            }

            if (str_ends_with($key, '_url')) {
                $settings[$key] = $this->safe_url($value);
                continue;
            }

            if (str_ends_with($key, '_icon')) {
                $settings[$key] = $this->tabler_icon_class($value);
                continue;
            }

            if ($key === 'hub_band_search_param') {
                $param = trim((string) preg_replace('/[^a-zA-Z0-9_-]+/', '', $value));
                $settings[$key] = $param !== '' ? $param : 'q';
                continue;
            }

            $settings[$key] = $type === 'textarea'
                ? $this->clean_textarea($value)
                : $this->clean_text($value);
        }

        return $settings;
    }

    private function setting_keys_for_tab(string $tab): array
    {
        $groups = [
            'domain' => ['landing_enabled', 'hub_domains', 'route_slug'],
            'content' => ['hero_eyebrow', 'landing_title', 'landing_subtitle', 'primary_button_label', 'primary_button_url', 'secondary_button_label', 'secondary_button_url', 'featured_enabled', 'featured_title', 'featured_text', 'featured_image_url', 'featured_url'],
            'layout' => ['layout_variant', 'content_width', 'card_radius', 'section_gap', 'primary_color', 'accent_color', 'background_color', 'surface_color', 'text_color', 'muted_color', 'border_color'],
            'sidebar' => ['show_sidebar', 'sidebar_position', 'preview_placement', 'show_events_preview', 'sidebar_events_count', 'show_speakers_preview', 'random_speakers_count', 'show_companies_preview', 'random_companies_count', 'show_experts_preview', 'random_experts_count'],
            'cards' => ['events_card_title', 'events_card_text', 'events_card_url', 'speakers_card_title', 'speakers_card_text', 'speakers_card_url', 'companies_card_title', 'companies_card_text', 'companies_card_url', 'experts_card_title', 'experts_card_text', 'experts_card_url'],
            'analytics' => ['analytics_enabled', 'analytics_position', 'analytics_code'],
        ];

        return $groups[$this->sanitize_tab($tab)] ?? $groups['domain'];
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
        if (function_exists('cms_enqueue_style')) {
            cms_enqueue_style('cms-365network-admin', CMS_365NETWORK_PLUGIN_URL . 'assets/css/admin.css', [], $version);
            return;
        }

        echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_365NETWORK_PLUGIN_URL . 'assets/css/admin.css?v=' . $version, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }

    private function enqueue_admin_scripts(): void
    {
        $scripts = [];
        $adminJs = CMS_365NETWORK_PLUGIN_DIR . 'assets/js/admin.js';
        if (is_file($adminJs)) {
            $scripts[] = CMS_365NETWORK_PLUGIN_URL . 'assets/js/admin.js?v=' . filemtime($adminJs);
        }

        $scripts[] = $this->core_asset_url('js/admin-media-integrations.js');

        foreach (array_unique(array_filter($scripts)) as $src) {
            echo '<script src="' . htmlspecialchars((string) $src, ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n";
        }
    }

    private function core_asset_url(string $asset): string
    {
        if (function_exists('cms_asset_url')) {
            return (string) cms_asset_url($asset);
        }

        return rtrim((string) SITE_URL, '/') . '/assets/' . ltrim($asset, '/');
    }

    private function require_admin(): void
    {
        if (function_exists('cms_require_capability')) {
            $allowed = cms_require_capability('manage_plugins');
            if ($allowed === false) {
                $this->redirect('/login');
            }
            return;
        }

        $auth = Auth::instance();
        $allowed = method_exists($auth, 'hasCapability') ? $auth->hasCapability('manage_plugins') : $auth->isAdmin();
        if (!$allowed) {
            $this->redirect('/login');
        }
    }

    private function nonce_field(): void
    {
        if (function_exists('cms_nonce_field')) {
            $field = cms_nonce_field('hub_settings_save');
            if (is_string($field)) {
                echo $field;
            }
            return;
        }

        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(Security::instance()->generateToken('cms_365network_settings'), ENT_QUOTES, 'UTF-8') . '">';
    }

    private function verify_nonce(): bool
    {
        if (function_exists('cms_verify_nonce')) {
            try {
                return (bool) cms_verify_nonce('hub_settings_save');
            } catch (\Throwable $e) {
                return false;
            }
        }

        return Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'cms_365network_settings');
    }

    private function admin_notice(string $message, string $type): void
    {
        if (function_exists('cms_admin_notice')) {
            cms_admin_notice($message, $type);
            return;
        }

        $class = $type === 'success' ? 'alert alert-success' : 'alert alert-error';
        echo '<div class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '" role="status">' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>';
    }

    private function render_media_picker_modal(): void
    {
        $token = Security::instance()->generateToken('editorjs_media');

        echo '<div class="modal modal-blur fade" id="settingsMediaPickerModal" tabindex="-1" aria-hidden="true">';
        echo '<div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">';
        echo '<div class="modal-header"><h5 class="modal-title" data-media-picker-title>Bild auswählen</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button></div>';
        echo '<div class="modal-body"><div data-media-picker-modal data-api-url="/api/media" data-csrf-token="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
        echo '<p class="text-secondary small mb-3">Ein Klick übernimmt das Bild direkt in das aktuell gewählte Bild-URL-Feld.</p>';
        echo '<div class="row g-2 align-items-center mb-3"><div class="col-md-8"><input type="search" class="form-control" placeholder="Mediathek durchsuchen …" data-media-picker-search></div><div class="col-md-4 text-secondary small" data-media-picker-status>Lade Medien …</div></div>';
        echo '<div class="row row-cards g-3" data-media-picker-grid></div>';
        echo '</div></div></div></div></div>';
    }

    private function admin_url(string $path): string
    {
        if ($path === '/admin/365network' && function_exists('cms_admin_url')) {
            return (string) cms_admin_url('cms-365network');
        }

        return self::absolute_url($path);
    }

    private function redirect(string $path): void
    {
        self::redirect_static($path);
    }

    private static function redirect_static(string $path): void
    {
        if (function_exists('cms_redirect')) {
            cms_redirect(self::absolute_url($path));
            exit;
        }

        if (class_exists('CMS\\Router')) {
            CMS\Router::instance()->redirect($path);
            return;
        }

        $targetUrl = self::absolute_url($path);
        echo '<div class="admin-card"><p>Weiterleitung nicht automatisch möglich. <a href="' . htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8') . '">Weiter zu 365NETWORK</a>.</p></div>';
        exit;
    }

    private static function absolute_url(string $path): string
    {
        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        return rtrim((string) SITE_URL, '/') . '/' . ltrim($path, '/');
    }

    private function sanitize_tab(string $tab): string
    {
        $allowed = array_merge(['domain', 'layout', 'sidebar', 'analytics'], array_keys($this->hub_section_tabs()));
        return in_array($tab, $allowed, true) ? $tab : 'domain';
    }

    /**
     * @param array<string,mixed> $field
     */
    private function render_hub_setting_field(array $field): void
    {
        $key = (string) ($field['setting_key'] ?? '');
        if ($key === '' || !str_starts_with($key, 'hub_')) {
            return;
        }

        $label = (string) ($field['label'] ?? $key);
        $type = (string) ($field['setting_type'] ?? 'text');
        $value = (string) ($field['setting_val'] ?? '');
        $id = 'n365-' . $key;

        echo '<div class="hub-admin-field">';
        echo '<label for="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label>';

        if ($key === 'hub_section_order') {
            $this->render_order_setting_control($key, $label, $value, $this->hub_order_items('sections'), 'Die Bereiche werden in dieser Reihenfolge ausgegeben; ausgeblendete Bereiche werden automatisch übersprungen.');
            echo '</div>';
            return;
        }

        if ($key === 'hub_area_card_order') {
            $this->render_order_setting_control($key, $label, $value, $this->hub_order_items('areas'), 'Steuert die Reihenfolge der vier Direkteinstieg-Karten im Public-Bereich.');
            echo '</div>';
            return;
        }

        if ($this->is_image_url_field($key)) {
            $this->render_image_url_field($key, $label, $value, $id);
            echo '</div>';
            return;
        }

        if ($type === 'bool') {
            $checked = (int) $value === 1 ? ' checked' : '';
            echo '<label class="hub-toggle" aria-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">';
            echo '<input type="hidden" name="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" value="0">';
            echo '<input type="checkbox" id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" name="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" value="1"' . $checked . '>';
            echo '<span class="hub-toggle-slider"></span>';
            echo '</label>';
        } elseif ($type === 'textarea') {
            echo '<textarea id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" name="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" rows="3">' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</textarea>';
        } elseif ($type === 'int') {
            [$min, $max] = $this->hub_int_range($key);
            echo '<input type="number" id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" name="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" value="' . (int) $value . '" min="' . (int) $min . '" max="' . (int) $max . '">';
        } elseif ($type === 'color') {
            echo '<input type="color" id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" name="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($this->hex_color($value, '#000000'), ENT_QUOTES, 'UTF-8') . '">';
        } elseif ($type === 'select') {
            $options = $this->hub_select_options($key);
            echo '<select id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" name="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '">';
            foreach ($options as $optionValue => $optionLabel) {
                $selected = $optionValue === $value ? ' selected' : '';
                echo '<option value="' . htmlspecialchars($optionValue, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>' . htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8') . '</option>';
            }
            echo '</select>';
        } else {
            echo '<input type="text" id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" name="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '">';
        }

        echo '</div>';
    }

    /**
     * @return array<string,string>
     */
    private function hub_select_options(string $key): array
    {
        return match ($key) {
            'hub_featured_style' => [
                'auto' => 'Automatisch (Bild nur wenn URL gesetzt)',
                'text' => 'Einspaltig ohne Bild',
                'image' => 'Zweispaltig mit Bild',
            ],
            'hub_featured_width' => [
                'full' => 'Volle Breite',
                'compact' => 'Kompakt zentriert',
            ],
            'hub_hero_layout' => [
                'left' => 'Links ausgerichtet',
                'center' => 'Zentriert',
            ],
            'hub_hero_height' => [
                'compact' => 'Kompakt',
                'normal' => 'Normal',
                'large' => 'Groß',
            ],
            'hub_stats_layout' => [
                'grid' => '4er Grid',
                'compact' => 'Kompakt',
                'inline' => 'Inline-Reihe',
            ],
            'hub_band_layout' => [
                'split' => 'Zwei Spalten',
                'stack' => 'Unter jede andere',
            ],
            'hub_areas_layout' => [
                'grid-2x2' => '2 × 2 Grid',
                'grid-4x1' => '4er Reihe',
                'auto' => 'Automatisch responsiv',
            ],
            'hub_areas_card_style' => [
                'icon-corner' => 'Icon-Ecke',
                'plain' => 'Ruhig/flach',
            ],
            'hub_toolbox_layout' => [
                'grid' => '3er Grid',
                'compact' => 'Kompakte Liste',
            ],
            default => [],
        };
    }

    /**
     * @return array<string,string>
     */
    private function hub_order_items(string $type): array
    {
        if ($type === 'areas') {
            return [
                'events' => '📅 Events',
                'speakers' => '🎙️ Speaker',
                'companies' => '🏢 Firmen',
                'experts' => '👥 Experten',
            ];
        }

        return [
            'featured' => '⭐ Featured Card',
            'hero' => '🏁 Hero-Bereich',
            'stats' => '📊 Kennzahlen',
            'band' => '🔎 Teaser & Suche',
            'areas' => '🧭 Direkteinstieg',
            'toolbox' => '🧰 Toolbox',
        ];
    }

    /**
     * @param array<string,string> $items
     */
    private function render_order_setting_control(string $key, string $label, string $value, array $items, string $help): void
    {
        $order = $this->normalize_order_values($value, array_keys($items));
        $id = 'n365-' . $key;

        echo '<div class="n365-order-control" data-n365-order-control>';
        echo '<input type="hidden" id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" name="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars(implode(',', $order), ENT_QUOTES, 'UTF-8') . '" data-n365-order-input>';
        echo '<ol class="n365-order-list" aria-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '" data-n365-order-list>';

        foreach ($order as $itemKey) {
            $itemLabel = (string) ($items[$itemKey] ?? $itemKey);
            echo '<li class="n365-order-item" draggable="true" tabindex="-1" data-order-key="' . htmlspecialchars($itemKey, ENT_QUOTES, 'UTF-8') . '">';
            echo '<input type="hidden" name="' . htmlspecialchars($key . '_items[]', ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($itemKey, ENT_QUOTES, 'UTF-8') . '" data-n365-order-item-input>';
            echo '<span class="n365-order-handle" aria-hidden="true">☰</span>';
            echo '<span class="n365-order-title">' . htmlspecialchars($itemLabel, ENT_QUOTES, 'UTF-8') . '</span>';
            echo '<span class="n365-order-actions">';
            echo '<button type="button" class="btn btn-secondary btn-sm" data-order-move="up" aria-label="' . htmlspecialchars($itemLabel . ' nach oben verschieben', ENT_QUOTES, 'UTF-8') . '">↑</button>';
            echo '<button type="button" class="btn btn-secondary btn-sm" data-order-move="down" aria-label="' . htmlspecialchars($itemLabel . ' nach unten verschieben', ENT_QUOTES, 'UTF-8') . '">↓</button>';
            echo '</span></li>';
        }

        echo '</ol>';
        echo '<small class="form-text">' . htmlspecialchars($help, ENT_QUOTES, 'UTF-8') . '</small>';
        echo '</div>';
    }

    private function render_image_url_field(string $key, string $label, string $value, string $id): void
    {
        $previewId = $id . '-preview';

        echo '<div class="n365-media-field">';
        echo '<div class="n365-media-input-row">';
        echo '<input type="text" id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" name="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '" placeholder="/uploads/bild.webp oder https://..." data-media-target-input>';
        echo '<button type="button" class="btn btn-secondary" data-open-media-picker data-target-input="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" data-preview-id="' . htmlspecialchars($previewId, ENT_QUOTES, 'UTF-8') . '" data-picker-title="' . htmlspecialchars($label . ' auswählen', ENT_QUOTES, 'UTF-8') . '">🖼️ Mediathek</button>';
        echo '<button type="button" class="btn btn-secondary" data-clear-media-input data-target-input="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" data-preview-id="' . htmlspecialchars($previewId, ENT_QUOTES, 'UTF-8') . '">Leeren</button>';
        echo '</div>';
        echo '<div id="' . htmlspecialchars($previewId, ENT_QUOTES, 'UTF-8') . '" class="n365-media-preview" data-media-preview data-preview-variant="image" data-input-id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' . ($value === '' ? ' hidden' : '') . '>';
        if ($value !== '') {
            echo '<img src="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($label . ' Vorschau', ENT_QUOTES, 'UTF-8') . '" loading="lazy">';
        }
        echo '</div>';
        echo '<small class="form-text">URL manuell eintragen oder ein vorhandenes Bild aus der Mediathek übernehmen.</small>';
        echo '</div>';
    }

    private function is_image_url_field(string $key): bool
    {
        return str_ends_with($key, '_image_url') || (str_contains($key, 'image') && str_ends_with($key, '_url'));
    }

    /**
     * @param array<int,string> $allowed
     * @return array<int,string>
     */
    private function normalize_order_values(string $value, array $allowed): array
    {
        $parts = array_filter(array_map(
            static fn(string $item): string => trim($item),
            explode(',', $value)
        ), static fn(string $item): bool => $item !== '');

        $ordered = [];
        foreach ($parts as $item) {
            if (in_array($item, $allowed, true) && !in_array($item, $ordered, true)) {
                $ordered[] = $item;
            }
        }

        foreach ($allowed as $item) {
            if (!in_array($item, $ordered, true)) {
                $ordered[] = $item;
            }
        }

        return $ordered;
    }

    /**
     * @param array<int,string> $allowed
     */
    private function sanitize_order_value(string $value, array $allowed): string
    {
        return implode(',', $this->normalize_order_values($value, $allowed));
    }

    /**
     * @return array{0:int,1:int}
     */
    private function hub_int_range(string $key): array
    {
        if (str_ends_with($key, '_radius')) {
            return [0, 40];
        }

        if ($key === 'hub_toolbox_limit') {
            return [1, 50];
        }

        if ($key === 'hub_featured_image_height') {
            return [120, 720];
        }

        return [1, 50];
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
        $url = trim(strip_tags($value));
        $url = str_replace('\\', '/', $url);
        $url = (string) preg_replace('/[\x00-\x1F\x7F]+/u', '', $url);
        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, './')) {
            $url = substr($url, 2);
        }

        if (preg_match('#^(?:uploads|media)(?:/|$)#i', $url) === 1 || preg_match('#^media-file(?:\?|$)#i', $url) === 1) {
            $url = '/' . ltrim($url, '/');
        }

        if (str_starts_with($url, '/') && !str_starts_with($url, '//') && !str_contains($url, '..')) {
            return str_replace(' ', '%20', $url);
        }

        $url = str_replace(' ', '%20', $url);
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return '';
        }

        $parts = parse_url($url);
        $scheme = is_array($parts) ? strtolower((string) ($parts['scheme'] ?? '')) : '';
        return in_array($scheme, ['http', 'https'], true) ? $url : '';
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

    private function tabler_icon_class(string $icon): string
    {
        $icon = strtolower(trim($icon));
        if ($icon === '') {
            return 'ti-link';
        }

        if (!str_starts_with($icon, 'ti-')) {
            $icon = 'ti-' . $icon;
        }

        return preg_match('/^ti-[a-z0-9-]+$/', $icon) === 1 ? $icon : 'ti-link';
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
