<?php
/**
 * CMS M365 Landing – Admin Pages.
 *
 * @package CMS_M365Landing
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365Landing_Admin_Pages
{
    public const ADMIN_BASE_URL = '/admin/plugins/m365landing-dashboard';
    private const MENU_BASE_SLUG = 'm365landing-dashboard';

    private static string $dispatchNotice = '';

    /** @return array<string,string> */
    private static function section_page_slugs(): array
    {
        return [
            'dashboard' => 'm365landing-dashboard',
            'cards' => 'm365landing-cards',
            'settings' => 'm365landing-settings',
            'system' => 'm365landing-system',
        ];
    }

    public static function render_dispatch(): void
    {
        self::check_access();

        $callbackMap = [
            'm365landing-dashboard' => [self::class, 'render_dashboard'],
            'm365landing-cards' => [self::class, 'render_cards'],
            'm365landing-settings' => [self::class, 'render_settings'],
            'm365landing-system' => [self::class, 'render_system'],
        ];
        $defaultSlug = self::section_page_slugs()['dashboard'] ?? 'm365landing-dashboard';
        $requestedSlug = function_exists('cms_plugin_admin_active_slug')
            ? cms_plugin_admin_active_slug($defaultSlug)
            : self::clean_slug((string) ($_GET['page'] ?? $defaultSlug));

        if (!isset($callbackMap[$requestedSlug])) {
            self::$dispatchNotice = 'Die angeforderte Unterseite ist nicht verfügbar. Dashboard wird angezeigt.';
        }

        if (function_exists('cms_plugin_admin_dispatch_page')) {
            cms_plugin_admin_dispatch_page($callbackMap, $defaultSlug, self::MENU_BASE_SLUG);
            return;
        }

        $callback = $callbackMap[$requestedSlug] ?? $callbackMap[$defaultSlug];
        if (is_callable($callback)) {
            call_user_func($callback);
            return;
        }

        self::render_recovery_page(new \RuntimeException('Admin-Dispatch konnte nicht aufgelöst werden.'));
    }

    public static function render_dashboard(): void
    {
        self::render_dispatch_for('dashboard');
    }

    public static function render_cards(): void
    {
        self::render_dispatch_for('cards');
    }

    public static function render_settings(): void
    {
        self::render_dispatch_for('settings');
    }

    public static function render_system(): void
    {
        self::render_dispatch_for('system');
    }

    private static function render_dispatch_for(string $section): void
    {
        $bufferLevel = ob_get_level();
        try {
            self::render_admin_page($section);
        } catch (\Throwable $e) {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }
            self::render_recovery_page($e);
        }
    }

    private static function render_admin_page(string $section): void
    {
        ob_start();
        self::check_access();
        $bootError = '';
        try {
            CMS_M365Landing_Installer::maybe_install();
        } catch (\Throwable $e) {
            self::log_exception('installer_boot_failed', $e);
            $bootError = 'Initialisierung konnte nicht vollständig ausgeführt werden: ' . $e->getMessage();
        }

        $repo = CMS_M365Landing_Repository::instance();
        $section = self::allowed_section($section);
        $notice = '';
        $error = $bootError;

        if (self::$dispatchNotice !== '') {
            $notice = self::$dispatchNotice;
            self::$dispatchNotice = '';
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            try {
                self::handle_post($repo);
                $notice = 'Änderungen gespeichert.';
            } catch (\Throwable $e) {
                $error = 'Aktion konnte nicht ausgeführt werden: ' . $e->getMessage();
            }
        }

        self::layout_start(self::page_title($section), self::section_slug($section));
        self::enqueue_admin_assets();

        echo '<div class="m365landing-admin-shell">';
        self::render_header($repo, $section, $notice, $error);

        match ($section) {
            'cards' => self::render_cards_section($repo),
            'settings' => self::render_settings_section($repo),
            'system' => self::render_system_section($repo),
            default => self::render_dashboard_section($repo),
        };
        echo '</div>';

        self::render_delete_modal();
        self::render_media_picker_modal();
        self::enqueue_admin_scripts();
        self::layout_end();
        ob_end_flush();
    }

    private static function render_recovery_page(\Throwable $e): void
    {
        $layoutStarted = false;
        try {
            self::layout_start('M365 Landing – Wiederherstellung', self::MENU_BASE_SLUG);
            $layoutStarted = true;
        } catch (\Throwable $layoutError) {
            $layoutStarted = false;
        }

        $version = defined('CMS_M365LANDING_VERSION') ? (string) CMS_M365LANDING_VERSION : 'unbekannt';
        $message = $e->getMessage() !== '' ? $e->getMessage() : get_class($e);

        echo '<main class="page-body"><div class="container-xl">';
        echo '<div class="alert alert-warning" role="alert" style="margin:1rem 0;">';
        echo '<h2 class="alert-title">M365 Landing im Wiederherstellungsmodus</h2>';
        echo '<div>Die normale Adminseite konnte nicht vollständig gestartet werden. Die Admin-Shell bleibt nutzbar; bitte lade den kompletten Ordner <code>cms-m365landing</code> per FTP erneut hoch, damit keine gemischten Dateiversionen aktiv sind.</div>';
        echo '<details class="mt-3"><summary>Technischer Hinweis für Administratoren</summary><pre class="mt-2 mb-0" style="white-space:pre-wrap;">' . self::esc($message) . '</pre></details>';
        echo '</div>';
        echo '<div class="card"><div class="card-body">';
        echo '<h3>Upload-Checkliste</h3>';
        echo '<p>Aktive lokale Plugin-Version: <strong>' . self::esc($version) . '</strong></p>';
        echo '<ul>';
        echo '<li><code>cms-m365landing.php</code></li>';
        echo '<li><code>admin/class-admin-pages.php</code> und <code>admin/class-admin-menu.php</code></li>';
        echo '<li><code>includes/class-installer.php</code>, <code>includes/class-repository.php</code>, <code>includes/class-frontend.php</code></li>';
        echo '<li><code>templates/landing.php</code></li>';
        echo '<li><code>assets/css/style.css</code>, <code>update.json</code>, <code>CHANGELOG.md</code></li>';
        echo '</ul>';
        echo '<p>Wenn diese Ansicht nach einem vollständigen Upload weiterhin erscheint, ist der angezeigte technische Hinweis der entscheidende nächste Ansatzpunkt.</p>';
        echo '</div></div>';
        echo '</div></main>';

        if ($layoutStarted) {
            try {
                self::layout_end();
            } catch (\Throwable $layoutError) {
                // Recovery darf nie wieder in die Core-Fehlerkarte fallen.
            }
        }
    }

    private static function handle_post(CMS_M365Landing_Repository $repo): void
    {
        self::check_access();
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            throw new \RuntimeException('Ungültige Anfragemethode.');
        }

        if (!class_exists('CMS\\Security') || !\CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'm365landing_admin')) {
            throw new \RuntimeException('Sicherheitscheck fehlgeschlagen.');
        }

        $action = trim((string) ($_POST['action'] ?? ''));
        if ($action === 'save_card') {
            $repo->save_card(self::collect_card_payload());
            return;
        }

        if ($action === 'delete_card') {
            $id = max(0, (int) ($_POST['id'] ?? 0));
            if ($id <= 0) {
                throw new \RuntimeException('Ungültige Karten-ID.');
            }
            $repo->delete_card($id);
            return;
        }

        if ($action === 'save_settings') {
            $repo->save_settings(self::collect_settings());
            return;
        }

        throw new \RuntimeException('Unbekannte Aktion.');
    }

    /** @return array<string,mixed> */
    private static function collect_card_payload(): array
    {
        $keys = [
            'id', 'section', 'slug', 'title', 'subtitle', 'description', 'icon',
            'image_url', 'image_alt', 'url', 'button_label', 'sort_order',
            'is_featured', 'is_active',
        ];
        $payload = [];
        foreach ($keys as $key) {
            $payload[$key] = $_POST[$key] ?? null;
        }

        return $payload;
    }

    /** @return array<string,string> */
    private static function collect_settings(): array
    {
        $settings = [];
        foreach (self::text_setting_keys() as $key) {
            $value = (string) ($_POST[$key] ?? '');
            if ($key === 'route_slug') {
                $settings[$key] = CMS_M365Landing_Repository::slug($value);
                continue;
            }

            if ($key === 'landing_domains') {
                $settings[$key] = implode("\n", CMS_M365Landing_Repository::normalize_domain_list($value));
                continue;
            }

            if ($key === 'layout_variant') {
                $settings[$key] = self::layout_variant($value);
                continue;
            }

            if ($key === 'posts_section_mode') {
                $settings[$key] = in_array($value, ['category', 'all'], true) ? $value : 'category';
                continue;
            }

            if ($key === 'hero_image_url') {
                $settings[$key] = CMS_M365Landing_Repository::public_image_url($value);
                continue;
            }

            if (str_ends_with($key, '_url')) {
                $settings[$key] = CMS_M365Landing_Repository::public_url($value);
                continue;
            }

            if (str_ends_with($key, '_card_layout')) {
                $settings[$key] = self::card_layout($value);
                continue;
            }

            $settings[$key] = CMS_M365Landing_Repository::long_text($value);
        }

        foreach (self::color_setting_defaults() as $key => $fallback) {
            $postedText = trim((string) ($_POST[$key . '_text'] ?? ''));
            $postedPicker = trim((string) ($_POST[$key] ?? ''));
            $posted = $postedText !== '' ? $postedText : $postedPicker;
            $settings[$key] = CMS_M365Landing_Repository::color($posted, $fallback);
        }

        foreach (self::numeric_setting_bounds() as $key => [$min, $max]) {
            $rawValue = trim((string) ($_POST[$key] ?? ''));
            $value = $rawValue !== '' ? (int) $rawValue : self::numeric_setting_default($key);
            $settings[$key] = (string) max($min, min($max, $value));
        }

        foreach (self::bool_setting_keys() as $boolKey) {
            $settings[$boolKey] = !empty($_POST[$boolKey]) ? '1' : '0';
        }

        return $settings;
    }

    /** @return array<int,string> */
    private static function text_setting_keys(): array
    {
        return [
            'route_slug', 'landing_domains', 'page_overline', 'page_title', 'page_intro',
            'hero_image_url', 'hero_image_alt',
            'hero_primary_button_text', 'hero_primary_button_url', 'hero_secondary_button_text', 'hero_secondary_button_url',
            'matrix_section_overline', 'matrix_section_title', 'matrix_section_intro',
            'areas_section_overline', 'areas_section_title', 'areas_section_intro',
            'tools_section_overline', 'tools_section_title', 'tools_section_intro',
            'posts_section_overline', 'posts_section_title', 'posts_section_intro', 'posts_section_mode',
            'separator_label', 'empty_state_title', 'empty_state_text', 'seo_title', 'seo_description',
            'card_button_label_default', 'layout_variant', 'matrix_card_layout', 'areas_card_layout', 'tools_card_layout',
            'graph_tenant_id', 'graph_client_id', 'graph_client_secret', 'message_center_service_filter',
            'service_health_section_overline', 'service_health_section_overline_en', 'service_health_section_title', 'service_health_section_title_en',
            'service_health_section_intro', 'service_health_section_intro_en', 'service_health_empty_text', 'service_health_empty_text_en',
            'message_center_section_overline', 'message_center_section_overline_en', 'message_center_section_title', 'message_center_section_title_en',
            'message_center_section_intro', 'message_center_section_intro_en', 'message_center_empty_text', 'message_center_empty_text_en',
        ];
    }

    /** @return array<int,string> */
    private static function bool_setting_keys(): array
    {
        return [
            'show_hero', 'show_hero_actions', 'show_matrix_section', 'show_separator', 'show_areas_section', 'show_tools_section',
            'show_posts_section', 'posts_section_domain_only', 'matrix_card_hide_title', 'areas_card_hide_title', 'tools_card_hide_title',
            'show_service_health_panel', 'show_message_center_panel',
        ];
    }

    /** @return array<string,string> */
    private static function color_setting_defaults(): array
    {
        return [
            'design_primary_color' => '#2563eb',
            'design_accent_color' => '#0f766e',
            'design_background_color' => '#edf1f6',
            'design_surface_color' => '#ffffff',
            'design_surface_alt_color' => '#f8fafc',
            'design_text_color' => '#1e293b',
            'design_muted_color' => '#64748b',
            'design_border_color' => '#e2e8f0',
        ];
    }

    /** @return array<string,array{0:int,1:int}> */
    private static function numeric_setting_bounds(): array
    {
        return [
            'layout_max_width' => [720, 1800],
            'layout_padding_x' => [0, 80],
            'layout_padding_top' => [0, 120],
            'layout_padding_bottom' => [0, 160],
            'design_border_radius' => [0, 32],
            'hero_image_height' => [80, 320],
            'card_icon_size' => [24, 80],
            'card_image_height' => [90, 420],
            'card_image_width' => [72, 220],
            'posts_section_category_id' => [0, 999999],
            'posts_section_limit' => [6, 9],
            'service_health_max_items' => [1, 12],
            'message_center_max_items' => [1, 12],
        ];
    }

    private static function render_header(CMS_M365Landing_Repository $repo, string $section, string $notice, string $error): void
    {
        $settings = self::safe_settings($repo);
        $slug = CMS_M365Landing_Repository::slug((string) ($settings['route_slug'] ?? 'm365'));
        $publicUrl = '/' . ($slug !== '' ? $slug : 'm365');
        echo '<div class="admin-page-header"><div><h2>🏠 ' . self::esc(self::page_title($section)) . '</h2><p>Zentrale M365-Landingpage mit Matrixen, Bereichen, Tools, Texten und Design steuern.</p></div>';
        echo '<div class="header-actions"><a class="btn btn-secondary" href="' . self::esc($publicUrl) . '" target="_blank" rel="noopener noreferrer">👁️ Public ansehen</a><a class="btn btn-primary" href="' . self::esc(self::admin_url('cards', ['edit' => 0])) . '">➕ Karte anlegen</a></div></div>';
        if ($notice !== '') {
            echo '<div class="alert alert-success">✅ ' . self::esc($notice) . '</div>';
        }
        if ($error !== '') {
            echo '<div class="alert alert-error">❌ ' . self::esc($error) . '</div>';
        }
    }

    private static function render_dashboard_section(CMS_M365Landing_Repository $repo): void
    {
        $stats = self::safe_stats($repo);
        echo '<div class="admin-card m365landing-card-connected"><h3>📊 Übersicht</h3><div class="dashboard-grid">';
        foreach ([['📊', 'Matrix-Karten', $stats['matrix'] ?? 0], ['🧭', 'Bereiche', $stats['areas'] ?? 0], ['🧰', 'Tools', $stats['tools'] ?? 0], ['✅', 'Aktive Karten', $stats['active_cards'] ?? 0]] as [$icon, $label, $value]) {
            echo '<div class="stat-card"><div class="stat-icon">' . self::esc((string) $icon) . '</div><div class="stat-number">' . (int) $value . '</div><div class="stat-label">' . self::esc((string) $label) . '</div></div>';
        }
        echo '</div></div>';
        echo '<div class="admin-card"><h3>⚡ Schnellzugriff</h3><div class="m365landing-quicklinks"><a class="btn btn-secondary" href="' . self::esc(self::admin_url('cards', ['edit' => 0])) . '">➕ Karte erstellen</a><a class="btn btn-secondary" href="' . self::esc(self::admin_url('settings')) . '#content">📝 Texte bearbeiten</a><a class="btn btn-primary" href="' . self::esc(self::admin_url('settings')) . '#design">🎨 Design anpassen</a></div></div>';
    }

    private static function render_cards_section(CMS_M365Landing_Repository $repo): void
    {
        $editId = isset($_GET['edit']) ? max(0, (int) $_GET['edit']) : -1;
        $edit = $editId > 0 ? self::safe_card($repo, $editId) : null;
        $cards = self::safe_cards($repo);
        echo '<div class="admin-card m365landing-card-connected"><h3>🃏 Karten verwalten</h3>';
        self::render_card_form($edit, self::csrf());
        echo '<hr class="m365landing-separator"><div class="users-table-container"><table class="users-table"><thead><tr><th>Karte</th><th>Bereich</th><th>Ziel</th><th>Status</th><th>Sortierung</th><th>Aktionen</th></tr></thead><tbody>';
        if ($cards === []) {
            echo '<tr><td colspan="6"><div class="empty-state"><p><strong>Noch keine Karten vorhanden</strong></p><p>Lege die erste Karte über das Formular oben an.</p></div></td></tr>';
        }
        foreach ($cards as $card) {
            $name = (string) $card['title'];
            echo '<tr><td><strong>' . self::esc($name) . '</strong><br><small>' . self::esc((string) $card['slug']) . '</small></td><td>' . self::section_badge((string) $card['section']) . '</td><td>' . self::esc((string) ($card['url'] ?: '—')) . '</td><td>' . self::status((int) $card['is_active'] === 1) . '</td><td>' . (int) $card['sort_order'] . '</td><td><div class="m365landing-actions"><a class="btn btn-sm btn-secondary" href="' . self::esc(self::admin_url('cards', ['edit' => (int) $card['id']])) . '">✏️</a><button type="button" class="btn btn-sm btn-danger" data-delete-card data-delete-id="' . (int) $card['id'] . '" data-delete-name="' . self::esc($name) . '">🗑️</button></div></td></tr>';
        }
        echo '</tbody></table></div></div>';
    }

    /** @param array<string,mixed>|null $edit */
    private static function render_card_form(?array $edit, string $token): void
    {
        echo '<form method="POST" class="admin-form m365landing-form-grid"><input type="hidden" name="action" value="save_card"><input type="hidden" name="csrf_token" value="' . self::esc($token) . '"><input type="hidden" name="id" value="' . (int) ($edit['id'] ?? 0) . '">';
        self::select('section', 'Bereich', (string) ($edit['section'] ?? 'tools'), ['matrix' => 'Matrixen oben', 'areas' => 'Weitere M365 Bereiche', 'tools' => 'M365 Tools Sammlung']);
        self::input('title', 'Titel', (string) ($edit['title'] ?? ''), true);
        self::preset_input('slug', 'Slug', (string) ($edit['slug'] ?? ''), 'm365landing-slug-presets');
        self::input('subtitle', 'Kurzzeile', (string) ($edit['subtitle'] ?? ''), false);
        self::number('sort_order', 'Sortierung', (int) ($edit['sort_order'] ?? 100), 0, 9999);
        self::input('icon', 'Icon/Emoji Fallback', (string) ($edit['icon'] ?? ''), false);
        self::image_input('image_url', 'Mediathek-Bild / Bild-URL', (string) ($edit['image_url'] ?? ''));
        self::input('image_alt', 'Bild-Alt-Text', (string) ($edit['image_alt'] ?? ''), false);
        self::preset_input('url', 'Ziel-Link', (string) ($edit['url'] ?? ''), 'm365landing-url-presets');
        self::input('button_label', 'Button-Text', (string) ($edit['button_label'] ?? ''), false);
        self::render_target_presets();
        self::textarea('description', 'Beschreibung', (string) ($edit['description'] ?? ''), 4);
        self::checkbox('is_featured', 'Als hervorgehobene Karte markieren', (int) ($edit['is_featured'] ?? 0) === 1);
        self::checkbox('is_active', 'Karte öffentlich anzeigen', (int) ($edit['is_active'] ?? 1) === 1);
        echo '<div class="m365landing-form-actions"><button class="btn btn-primary" type="submit">💾 Karte speichern</button></div></form>';
    }

    private static function render_settings_section(CMS_M365Landing_Repository $repo): void
    {
        $s = self::safe_settings($repo);
        echo '<div class="admin-card"><form method="POST" class="admin-form">';
        echo '<input type="hidden" name="action" value="save_settings"><input type="hidden" name="csrf_token" value="' . self::esc(self::csrf()) . '">';
        echo '<h3 id="content">📝 Content Header</h3>';
        self::replace_input('route_slug', 'Öffentlicher Slug', self::setting_value($s, 'route_slug', 'm365'));
        self::replace_input('page_overline', 'Overline', self::setting_value($s, 'page_overline', 'Microsoft 365 Hub'));
        self::replace_input('page_title', 'Seitentitel', self::setting_value($s, 'page_title', 'M365 im Überblick – Matrixen, Azure, Tutorials und Tools'));
        self::replace_textarea('page_intro', 'Einleitung', self::setting_value($s, 'page_intro', 'Die zentrale Einstiegsseite für Microsoft-365-Entscheidungen: Lizenzmatrixen, Add-ons, Copilot, Azure Services, Tutorials und praktische Rechner an einem Ort.'), 4);
        self::image_input('hero_image_url', 'Content-Header Bild / Bild-URL', (string) ($s['hero_image_url'] ?? ''), 'Headerbild auswählen');
        self::replace_input('hero_image_alt', 'Content-Header Bild-Alt-Text', (string) ($s['hero_image_alt'] ?? ''));
        self::replace_number('hero_image_height', 'Header-Bildhöhe in px', (int) self::setting_value($s, 'hero_image_height', '150'), 80, 320);
        self::replace_input('hero_primary_button_text', 'Primärbutton Text', self::setting_value($s, 'hero_primary_button_text', 'M365 Lizenzmatrix öffnen'));
        self::preset_input('hero_primary_button_url', 'Primärbutton Ziel', self::setting_value($s, 'hero_primary_button_url', '/m365-lizenzmatrix'), 'm365landing-url-presets');
        self::replace_input('hero_secondary_button_text', 'Sekundärbutton Text', self::setting_value($s, 'hero_secondary_button_text', 'Add-on-Matrix öffnen'));
        self::preset_input('hero_secondary_button_url', 'Sekundärbutton Ziel', self::setting_value($s, 'hero_secondary_button_url', '/m365-addon-matrix'), 'm365landing-url-presets');
        self::render_target_presets();
        self::replace_input('seo_title', 'SEO-Titel', self::setting_value($s, 'seo_title', 'Microsoft 365 Hub'));
        self::replace_textarea('seo_description', 'SEO-Beschreibung', self::setting_value($s, 'seo_description', 'Zentrale Landingpage für Microsoft 365 Lizenzmatrixen, Add-ons, Copilot, Azure Services, Tutorials und M365 Tools.'), 3);

        echo '<hr class="m365landing-separator"><h3 id="domains">🌐 Domain-Mapping</h3>';
        $domains = CMS_M365Landing_Repository::normalize_domain_list((string) ($s['landing_domains'] ?? ''));
        $siteUrl = defined('SITE_URL') ? (string) SITE_URL : '';
        $mainHost = CMS_M365Landing_Repository::normalize_host((string) (parse_url($siteUrl, PHP_URL_HOST) ?: ''));
        $status = $domains === [] ? 'Keine Zusatzdomain aktiv' : 'Aktive Zusatzdomain' . (count($domains) === 1 ? '' : 's') . ': ' . implode(', ', $domains);
        echo '<div class="alert" style="background:#dbeafe;color:#1e40af;border-left:4px solid #3b82f6;margin-bottom:1.25rem;">ℹ️ Hinterlegte Zusatzdomains zeigen die M365-Landingpage direkt auf der Domain-Startseite. Die Hauptdomain ' . self::esc($mainHost !== '' ? $mainHost : 'bleibt unverändert') . ' behält ihre normale Startseite.</div>';
        echo '<p class="form-text" style="margin-bottom:1rem;"><strong>' . self::esc($status) . '</strong></p>';
        self::replace_textarea('landing_domains', 'Zusatzdomain(s)', implode("\n", $domains), 4);
        echo '<p class="form-text">Eine Domain pro Zeile oder kommasepariert eintragen, ohne <code>https://</code> und ohne Pfad. <code>www.</code> und Ports werden automatisch normalisiert.</p>';

        echo '<hr class="m365landing-separator"><h3 id="sections">🧱 Abschnittstexte</h3>';
        foreach ([['matrix', 'Matrixen'], ['areas', 'Weitere M365 Bereiche'], ['tools', 'M365 Tools Sammlung']] as [$prefix, $label]) {
            echo '<h4>' . self::esc((string) $label) . '</h4>';
            self::replace_input($prefix . '_section_overline', 'Overline', (string) ($s[$prefix . '_section_overline'] ?? ''));
            self::replace_input($prefix . '_section_title', 'Titel', (string) ($s[$prefix . '_section_title'] ?? ''));
            self::replace_textarea($prefix . '_section_intro', 'Intro', (string) ($s[$prefix . '_section_intro'] ?? ''), 3);
            self::select($prefix . '_card_layout', 'Card-Layout', self::card_layout((string) ($s[$prefix . '_card_layout'] ?? 'media')), self::card_layout_options());
            self::replace_checkbox($prefix . '_card_hide_title', 'Kartentitel in diesem Bereich ausblenden', (string) ($s[$prefix . '_card_hide_title'] ?? '0') === '1');
        }
        self::replace_input('separator_label', 'Text im optischen Trenner', (string) ($s['separator_label'] ?? ''));
        self::replace_input('empty_state_title', 'Leerer-Zustand Titel', (string) ($s['empty_state_title'] ?? ''));
        self::replace_textarea('empty_state_text', 'Leerer-Zustand Text', (string) ($s['empty_state_text'] ?? ''), 2);
        self::replace_input('card_button_label_default', 'Standard-Buttontext', (string) ($s['card_button_label_default'] ?? 'Öffnen'));

        echo '<hr class="m365landing-separator"><h3 id="posts">📰 Aktuelle Beiträge</h3>';
        echo '<div class="alert" style="background:#dbeafe;color:#1e40af;border-left:4px solid #3b82f6;margin-bottom:1.25rem;">ℹ️ Dieser Bereich zeigt die letzten veröffentlichten Beiträge als PHINIT-Grid-Cards. Bei Auswahl einer Hauptkategorie werden alle Beiträge aus Unterkategorien automatisch mitgeladen. Alternativ können alle News angezeigt werden.</div>';
        self::replace_checkbox('show_posts_section', 'Beitragsbereich anzeigen', (string) ($s['show_posts_section'] ?? '0') === '1');
        self::replace_checkbox('posts_section_domain_only', 'Nur auf hinterlegten Zusatzdomains anzeigen', (string) ($s['posts_section_domain_only'] ?? '1') === '1');
        self::select('posts_section_mode', 'Beitragsquelle', self::setting_value($s, 'posts_section_mode', 'category'), ['category' => 'Ausgewählte Kategorie inkl. Unterkategorien', 'all' => 'Alle News / alle veröffentlichten Beiträge']);
        self::select('posts_section_limit', 'Anzahl Beiträge', self::setting_value($s, 'posts_section_limit', '6'), ['6' => 'Letzte 6 Beiträge', '9' => 'Letzte 9 Beiträge']);
        $categoryOptions = ['0' => '— Kategorie auswählen —'];
        $categories = method_exists($repo, 'post_categories') ? $repo->post_categories() : [];
        foreach ($categories as $category) {
            $id = (int) ($category['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $categoryOptions[(string) $id] = (string) ($category['name'] ?? ('Kategorie #' . $id));
        }
        self::select('posts_section_category_id', 'Kategorie inkl. Unterkategorien', self::setting_value($s, 'posts_section_category_id', '0'), $categoryOptions);
        self::replace_input('posts_section_overline', 'Overline', (string) ($s['posts_section_overline'] ?? ''));
        self::replace_input('posts_section_title', 'Titel', (string) ($s['posts_section_title'] ?? ''));
        self::replace_textarea('posts_section_intro', 'Intro', (string) ($s['posts_section_intro'] ?? ''), 3);

        echo '<hr class="m365landing-separator"><h3 id="graph">🔔 M365 Live-Meldungen (Graph)</h3>';
        echo '<div class="alert" style="background:#f0f9ff;color:#0c4a6e;border-left:4px solid #0ea5e9;margin-bottom:1.25rem;">ℹ️ Optional: Service-Health-Panel und Message-Center-Highlights werden direkt aus Microsoft Graph geladen. Benötigt eine Azure-App mit Application Permissions für Service Communications.</div>';
        self::replace_input('graph_tenant_id', 'Tenant-ID', self::setting_value($s, 'graph_tenant_id', ''));
        self::replace_input('graph_client_id', 'Client-ID', self::setting_value($s, 'graph_client_id', ''));
        self::replace_input('graph_client_secret', 'Client-Secret', self::setting_value($s, 'graph_client_secret', ''));
        self::replace_checkbox('show_service_health_panel', 'Service-Health-Panel anzeigen', (string) ($s['show_service_health_panel'] ?? '0') === '1');
        self::replace_number('service_health_max_items', 'Service-Health Einträge', (int) self::setting_value($s, 'service_health_max_items', '5'), 1, 12);
        self::replace_input('service_health_section_overline', 'Service-Health Overline (de)', self::setting_value($s, 'service_health_section_overline', 'Live-Status'));
        self::replace_input('service_health_section_overline_en', 'Service-Health Overline (en)', self::setting_value($s, 'service_health_section_overline_en', 'Live status'));
        self::replace_input('service_health_section_title', 'Service-Health Titel (de)', self::setting_value($s, 'service_health_section_title', 'Tenant Service Health'));
        self::replace_input('service_health_section_title_en', 'Service-Health Titel (en)', self::setting_value($s, 'service_health_section_title_en', 'Tenant service health'));
        self::replace_textarea('service_health_section_intro', 'Service-Health Intro (de)', self::setting_value($s, 'service_health_section_intro', 'Aktuelle Vorfälle und Advisories aus Microsoft 365 Services.'), 2);
        self::replace_textarea('service_health_section_intro_en', 'Service-Health Intro (en)', self::setting_value($s, 'service_health_section_intro_en', 'Current incidents and advisories from Microsoft 365 services.'), 2);
        self::replace_textarea('service_health_empty_text', 'Service-Health Hinweis bei leer/Fehler (de)', self::setting_value($s, 'service_health_empty_text', 'Der Service-Health-Feed ist aktuell nicht verfügbar.'), 2);
        self::replace_textarea('service_health_empty_text_en', 'Service-Health Hinweis bei leer/Fehler (en)', self::setting_value($s, 'service_health_empty_text_en', 'The service health feed is currently unavailable.'), 2);
        self::replace_checkbox('show_message_center_panel', 'Message-Center-Panel anzeigen', (string) ($s['show_message_center_panel'] ?? '0') === '1');
        self::replace_number('message_center_max_items', 'Message-Center Einträge', (int) self::setting_value($s, 'message_center_max_items', '5'), 1, 12);
        self::replace_input('message_center_service_filter', 'Message-Center Service-Filter (optional, z. B. teams, sharepoint)', self::setting_value($s, 'message_center_service_filter', ''));
        self::replace_input('message_center_section_overline', 'Message-Center Overline (de)', self::setting_value($s, 'message_center_section_overline', 'Änderungsankündigungen'));
        self::replace_input('message_center_section_overline_en', 'Message-Center Overline (en)', self::setting_value($s, 'message_center_section_overline_en', 'Change announcements'));
        self::replace_input('message_center_section_title', 'Message-Center Titel (de)', self::setting_value($s, 'message_center_section_title', 'Message Center Highlights'));
        self::replace_input('message_center_section_title_en', 'Message-Center Titel (en)', self::setting_value($s, 'message_center_section_title_en', 'Message center highlights'));
        self::replace_textarea('message_center_section_intro', 'Message-Center Intro (de)', self::setting_value($s, 'message_center_section_intro', 'Wichtige angekündigte Änderungen mit Relevanz für Betrieb und Governance.'), 2);
        self::replace_textarea('message_center_section_intro_en', 'Message-Center Intro (en)', self::setting_value($s, 'message_center_section_intro_en', 'Important upcoming Microsoft 365 changes for operations and governance.'), 2);
        self::replace_textarea('message_center_empty_text', 'Message-Center Hinweis bei leer/Fehler (de)', self::setting_value($s, 'message_center_empty_text', 'Der Message-Center-Feed ist aktuell nicht verfügbar.'), 2);
        self::replace_textarea('message_center_empty_text_en', 'Message-Center Hinweis bei leer/Fehler (en)', self::setting_value($s, 'message_center_empty_text_en', 'The message center feed is currently unavailable.'), 2);

        echo '<hr class="m365landing-separator"><h3 id="visibility">👁️ Sichtbarkeit</h3>';
        self::replace_checkbox('show_hero', 'Content Header anzeigen', (string) ($s['show_hero'] ?? '1') === '1');
        self::replace_checkbox('show_hero_actions', 'Header-Buttons anzeigen', (string) ($s['show_hero_actions'] ?? '1') === '1');
        self::replace_checkbox('show_matrix_section', 'Matrix-Bereich anzeigen', (string) ($s['show_matrix_section'] ?? '1') === '1');
        self::replace_checkbox('show_separator', 'Dezenten Trenner anzeigen', (string) ($s['show_separator'] ?? '1') === '1');
        self::replace_checkbox('show_areas_section', 'Weitere M365 Bereiche anzeigen', (string) ($s['show_areas_section'] ?? '1') === '1');
        self::replace_checkbox('show_tools_section', 'M365 Tools Sammlung anzeigen', (string) ($s['show_tools_section'] ?? '1') === '1');

        echo '<hr class="m365landing-separator"><h3 id="design">🎨 Layout & Design</h3>';
        self::select('layout_variant', 'Layout', self::layout_variant((string) ($s['layout_variant'] ?? 'balanced')), self::layout_options());
        self::replace_number('layout_max_width', 'Maximale Inhaltsbreite in px', (int) ($s['layout_max_width'] ?? 1180), 720, 1800);
        self::replace_number('layout_padding_x', 'Seitlicher Innenabstand in px', (int) ($s['layout_padding_x'] ?? 0), 0, 80);
        self::replace_number('layout_padding_top', 'Abstand zum Theme-Header in px', (int) ($s['layout_padding_top'] ?? 25), 0, 120);
        self::replace_number('layout_padding_bottom', 'Abstand zum Theme-Footer in px', (int) ($s['layout_padding_bottom'] ?? 64), 0, 160);
        self::replace_color('design_primary_color', 'Primärfarbe', (string) ($s['design_primary_color'] ?? '#2563eb'), '#2563eb');
        self::replace_color('design_accent_color', 'Akzentfarbe', (string) ($s['design_accent_color'] ?? '#0f766e'), '#0f766e');
        self::replace_color('design_background_color', 'Seitenhintergrund', (string) ($s['design_background_color'] ?? '#edf1f6'), '#edf1f6');
        self::replace_color('design_surface_color', 'Card-Hintergrund', (string) ($s['design_surface_color'] ?? '#ffffff'), '#ffffff');
        self::replace_color('design_surface_alt_color', 'Alternativer Hintergrund', (string) ($s['design_surface_alt_color'] ?? '#f8fafc'), '#f8fafc');
        self::replace_color('design_text_color', 'Textfarbe', (string) ($s['design_text_color'] ?? '#1e293b'), '#1e293b');
        self::replace_color('design_muted_color', 'Sekundärtext', (string) ($s['design_muted_color'] ?? '#64748b'), '#64748b');
        self::replace_color('design_border_color', 'Rahmenfarbe', (string) ($s['design_border_color'] ?? '#e2e8f0'), '#e2e8f0');
        self::replace_number('design_border_radius', 'Card-Radius in px', (int) ($s['design_border_radius'] ?? 10), 0, 32);
        self::replace_number('card_icon_size', 'Icon-Größe in px', (int) ($s['card_icon_size'] ?? 42), 24, 80);
        self::replace_number('card_image_height', 'Bildhöhe in px', (int) ($s['card_image_height'] ?? 205), 90, 420);
        self::replace_number('card_image_width', 'Bildbreite links in px', (int) ($s['card_image_width'] ?? 120), 72, 220);
        echo '<button class="btn btn-primary" type="submit">💾 Einstellungen speichern</button></form></div>';
    }

    /** @param array<string,string> $settings */
    private static function setting_value(array $settings, string $key, string $default = ''): string
    {
        $value = trim((string) ($settings[$key] ?? ''));

        return $value !== '' ? $value : $default;
    }

    private static function numeric_setting_default(string $key): int
    {
        $defaults = [
            'layout_max_width' => 1180,
            'layout_padding_x' => 0,
            'layout_padding_top' => 25,
            'layout_padding_bottom' => 64,
            'design_border_radius' => 10,
            'hero_image_height' => 150,
            'card_icon_size' => 42,
            'card_image_height' => 205,
            'card_image_width' => 120,
            'posts_section_category_id' => 0,
            'posts_section_limit' => 6,
            'service_health_max_items' => 5,
            'message_center_max_items' => 5,
        ];

        return $defaults[$key] ?? 0;
    }

    private static function render_system_section(CMS_M365Landing_Repository $repo): void
    {
        $stats = self::safe_stats($repo);
        echo '<div class="admin-card m365landing-card-connected"><h3>🖥️ System-Informationen</h3><div class="info-grid"><div class="info-card"><h4>Plugin</h4><ul class="info-list"><li><strong>Version:</strong> ' . self::esc(CMS_M365LANDING_VERSION) . '</li><li><strong>DB-Version:</strong> ' . self::esc(CMS_M365LANDING_DB_VERSION) . '</li></ul></div><div class="info-card"><h4>Inhalte</h4><ul class="info-list"><li><strong>Matrix-Karten:</strong> ' . (int) ($stats['matrix'] ?? 0) . '</li><li><strong>Bereiche:</strong> ' . (int) ($stats['areas'] ?? 0) . '</li><li><strong>Tools:</strong> ' . (int) ($stats['tools'] ?? 0) . '</li></ul></div></div></div>';
    }

    /** @return array<string,string> */
    private static function safe_settings(CMS_M365Landing_Repository $repo): array
    {
        try {
            return $repo->settings();
        } catch (\Throwable $e) {
            self::log_exception('settings_safe_load_failed', $e);
            return [];
        }
    }

    /** @return array<string,int> */
    private static function safe_stats(CMS_M365Landing_Repository $repo): array
    {
        try {
            return $repo->stats();
        } catch (\Throwable $e) {
            self::log_exception('stats_safe_load_failed', $e);
            return ['matrix' => 0, 'areas' => 0, 'tools' => 0, 'active_cards' => 0];
        }
    }

    /** @return array<string,mixed>|null */
    private static function safe_card(CMS_M365Landing_Repository $repo, int $id): ?array
    {
        try {
            return $repo->card($id);
        } catch (\Throwable $e) {
            self::log_exception('card_safe_load_failed', $e);
            return null;
        }
    }

    /** @return array<int,array<string,mixed>> */
    private static function safe_cards(CMS_M365Landing_Repository $repo): array
    {
        try {
            return $repo->cards(null, false);
        } catch (\Throwable $e) {
            self::log_exception('cards_safe_load_failed', $e);
            return [];
        }
    }

    private static function render_delete_modal(): void
    {
        echo '<div id="m365landingDeleteModal" class="modal" style="display:none;"><div class="modal-content" style="max-width:480px;"><div class="modal-header"><h3>🗑️ Karte löschen</h3><button class="modal-close" type="button" data-m365landing-close>&times;</button></div><div class="modal-body"><p>Soll <strong id="m365landingDeleteName"></strong> wirklich gelöscht werden?</p><p style="color:#ef4444;font-size:.875rem;">⚠️ Diese Aktion kann nicht rückgängig gemacht werden.</p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-m365landing-close>Abbrechen</button><form method="POST" id="m365landingDeleteForm"><input type="hidden" name="csrf_token" value="' . self::esc(self::csrf()) . '"><input type="hidden" name="action" value="delete_card"><input type="hidden" name="id" id="m365landingDeleteId"><button class="btn btn-danger" type="submit">🗑️ Endgültig löschen</button></form></div></div></div>';
    }

    private static function render_media_picker_modal(): void
    {
        $token = class_exists('CMS\\Security') ? \CMS\Security::instance()->generateToken('editorjs_media') : '';
        echo '<div class="modal modal-blur fade" id="settingsMediaPickerModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" data-media-picker-title>Bild auswählen</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button></div><div class="modal-body"><div data-media-picker-modal data-api-url="/api/media" data-csrf-token="' . self::esc($token) . '"><p class="text-secondary small mb-3">Ein Klick übernimmt das Bild in das Kartenfeld.</p><div class="row g-2 align-items-center mb-3"><div class="col-md-8"><input type="search" class="form-control" placeholder="Mediathek durchsuchen …" data-media-picker-search></div><div class="col-md-4 text-secondary small" data-media-picker-status>Lade Medien …</div></div><div class="row row-cards g-3" data-media-picker-grid></div></div></div></div></div></div>';
    }

    private static function input(string $name, string $label, string $value, bool $required): void
    {
        echo '<div class="form-group"><label class="form-label" for="' . self::esc($name) . '">' . self::esc($label) . '</label><input class="form-control" type="text" id="' . self::esc($name) . '" name="' . self::esc($name) . '" value="' . self::esc($value) . '"' . ($required ? ' required' : '') . '></div>';
    }

    private static function preset_input(string $name, string $label, string $value, string $listId): void
    {
        echo '<div class="form-group"><label class="form-label" for="' . self::esc($name) . '">' . self::esc($label) . '</label><input class="form-control" type="text" id="' . self::esc($name) . '" name="' . self::esc($name) . '" value="' . self::esc($value) . '" list="' . self::esc($listId) . '"></div>';
    }

    private static function replace_input(string $name, string $label, string $value): void
    {
        self::input($name, $label, $value, false);
    }

    private static function image_input(string $name, string $label, string $value, string $pickerTitle = 'Kartenbild auswählen'): void
    {
        $previewId = $name . '_preview';
        echo '<div class="form-group"><label class="form-label" for="' . self::esc($name) . '">' . self::esc($label) . '</label>';
        echo '<div id="' . self::esc($previewId) . '" class="m365landing-media-preview" data-media-preview data-preview-variant="image" data-input-id="' . self::esc($name) . '"' . ($value === '' ? ' hidden' : '') . '>';
        if ($value !== '') {
            echo '<img src="' . self::esc($value) . '" alt="Bildvorschau" loading="lazy">';
        }
        echo '</div><input class="form-control" type="text" id="' . self::esc($name) . '" name="' . self::esc($name) . '" value="' . self::esc($value) . '" placeholder="/uploads/bild.webp" data-media-target-input>';
        echo '<div class="m365landing-media-actions"><button type="button" class="btn btn-secondary btn-sm" data-open-media-picker data-target-input="' . self::esc($name) . '" data-preview-id="' . self::esc($previewId) . '" data-picker-title="' . self::esc($pickerTitle) . '">🖼️ Mediathek</button><button type="button" class="btn btn-secondary btn-sm" data-clear-media-input data-target-input="' . self::esc($name) . '" data-preview-id="' . self::esc($previewId) . '">Leeren</button></div></div>';
    }

    private static function textarea(string $name, string $label, string $value, int $rows): void
    {
        $value = CMS_M365Landing_Repository::normalize_newlines($value);
        echo '<div class="form-group m365landing-wide"><label class="form-label" for="' . self::esc($name) . '">' . self::esc($label) . '</label><textarea class="form-control" id="' . self::esc($name) . '" name="' . self::esc($name) . '" rows="' . (int) $rows . '">' . self::esc($value) . '</textarea></div>';
    }

    private static function replace_textarea(string $name, string $label, string $value, int $rows): void
    {
        self::textarea($name, $label, $value, $rows);
    }

    private static function number(string $name, string $label, int $value, int $min, int $max): void
    {
        echo '<div class="form-group"><label class="form-label" for="' . self::esc($name) . '">' . self::esc($label) . '</label><input class="form-control" type="number" min="' . (int) $min . '" max="' . (int) $max . '" id="' . self::esc($name) . '" name="' . self::esc($name) . '" value="' . (int) $value . '"></div>';
    }

    private static function replace_number(string $name, string $label, int $value, int $min, int $max): void
    {
        self::number($name, $label, $value, $min, $max);
    }

    /** @param array<int|string,string> $options */
    private static function select(string $name, string $label, string $value, array $options): void
    {
        echo '<div class="form-group"><label class="form-label" for="' . self::esc($name) . '">' . self::esc($label) . '</label><select class="form-control" id="' . self::esc($name) . '" name="' . self::esc($name) . '">';
        foreach ($options as $optionValue => $optionLabel) {
            $optionValue = (string) $optionValue;
            $optionLabel = (string) $optionLabel;
            $selected = $value === $optionValue ? ' selected' : '';
            echo '<option value="' . self::esc($optionValue) . '"' . $selected . '>' . self::esc($optionLabel) . '</option>';
        }
        echo '</select></div>';
    }

    /** @return array<string,string> */
    private static function layout_options(): array
    {
        return [
            'balanced' => 'Standard – ausgewogene Bereichscards',
            'compact' => 'Kompakt – kürzere Abstände und dichteres Hero',
            'spotlight' => 'Spotlight – Headerbild links, Cards luftig',
        ];
    }

    private static function layout_variant(string $value): string
    {
        return array_key_exists($value, self::layout_options()) ? $value : 'balanced';
    }

    /** @return array<string,string> */
    private static function card_layout_options(): array
    {
        return [
            'media' => 'Bild links, Titel rechts',
            'stacked' => 'Bild oben, Inhalt darunter',
        ];
    }

    private static function card_layout(string $value): string
    {
        return array_key_exists($value, self::card_layout_options()) ? $value : 'media';
    }

    /** @return array<int,array{slug:string,url:string,label:string}> */
    private static function target_presets(): array
    {
        return [
            ['slug' => 'm365-lizenzmatrix', 'url' => '/m365-lizenzmatrix', 'label' => 'M365 Lizenzmatrix'],
            ['slug' => 'm365-addon-matrix', 'url' => '/m365-addon-matrix', 'label' => 'M365 Add-on-Matrix'],
            ['slug' => 'm365-copilot-matrix', 'url' => '/m365-copilot-matrix', 'label' => 'Copilot Matrix'],
            ['slug' => 'azure-services', 'url' => '/azure-services', 'label' => 'Azure Services'],
            ['slug' => 'm365-tools', 'url' => '/m365-tools', 'label' => 'M365 Tools Hub'],
            ['slug' => 'm365-lizenzvergleich', 'url' => '/m365-lizenzvergleich', 'label' => 'M365 Lizenzvergleich'],
            ['slug' => 'm365-lizenzberater', 'url' => '/m365-lizenzberater', 'label' => 'M365 Lizenz-Berater'],
            ['slug' => 'm365-add-on-konfigurator', 'url' => '/m365-add-on-konfigurator', 'label' => 'Add-on-Konfigurator'],
            ['slug' => 'copilot-roi-rechner', 'url' => '/copilot-roi-rechner', 'label' => 'Copilot ROI-Rechner'],
            ['slug' => 'm365-storage-bedarfsrechner', 'url' => '/m365-storage-bedarfsrechner', 'label' => 'Storage-Bedarfs-Rechner'],
            ['slug' => 'teams-phone-lizenzberater', 'url' => '/teams-phone-lizenzberater', 'label' => 'Teams Phone-Berater'],
        ];
    }

    private static function render_target_presets(): void
    {
        echo '<datalist id="m365landing-slug-presets">';
        foreach (self::target_presets() as $preset) {
            echo '<option value="' . self::esc($preset['slug']) . '">' . self::esc($preset['label']) . '</option>';
        }
        echo '</datalist><datalist id="m365landing-url-presets">';
        foreach (self::target_presets() as $preset) {
            echo '<option value="' . self::esc($preset['url']) . '">' . self::esc($preset['label']) . '</option>';
        }
        echo '</datalist><div class="m365landing-wide m365landing-target-presets"><strong>Standard-Ziele:</strong> ';
        $links = [];
        foreach (self::target_presets() as $preset) {
            $links[] = '<code>' . self::esc($preset['slug']) . '</code> → <code>' . self::esc($preset['url']) . '</code>';
        }
        echo implode(' · ', $links) . '</div>';
    }

    private static function checkbox(string $name, string $label, bool $checked): void
    {
        echo '<label class="checkbox-label m365landing-wide"><input type="checkbox" name="' . self::esc($name) . '" value="1"' . ($checked ? ' checked' : '') . '> ' . self::esc($label) . '</label>';
    }

    private static function replace_checkbox(string $name, string $label, bool $checked): void
    {
        echo '<input type="hidden" name="' . self::esc($name) . '" value="0">';
        self::checkbox($name, $label, $checked);
    }

    private static function replace_color(string $name, string $label, string $value, string $fallback): void
    {
        $value = CMS_M365Landing_Repository::color($value, $fallback);
        echo '<div class="form-group"><label class="form-label" for="' . self::esc($name) . '">' . self::esc($label) . '</label><div class="m365landing-color-row"><input class="form-control" type="color" id="' . self::esc($name) . '" name="' . self::esc($name) . '" value="' . self::esc($value) . '"><input class="form-control m365landing-mono" type="text" name="' . self::esc($name) . '_text" value="' . self::esc($value) . '" pattern="^#[0-9A-Fa-f]{6}$" maxlength="7" placeholder="' . self::esc($fallback) . '"></div></div>';
    }

    private static function status(bool $active): string
    {
        return $active ? '<span class="status-badge active">✅ Aktiv</span>' : '<span class="status-badge inactive">⏸️ Inaktiv</span>';
    }

    private static function section_badge(string $section): string
    {
        $labels = ['matrix' => '📊 Matrix', 'areas' => '🧭 Bereich', 'tools' => '🧰 Tool'];
        return '<span class="status-badge active">' . self::esc($labels[$section] ?? '🧰 Tool') . '</span>';
    }

    private static function csrf(): string
    {
        try {
            return class_exists('CMS\\Security') ? \CMS\Security::instance()->generateToken('m365landing_admin') : '';
        } catch (\Throwable $e) {
            self::log_exception('csrf_generate_failed', $e);
            return '';
        }
    }

    private static function check_access(): void
    {
        if (!class_exists('CMS\\Auth') || !\CMS\Auth::instance()->isAdmin()) {
            header('Location: ' . self::safe_admin_redirect_url(), true, 302);
            exit;
        }
    }

    private static function safe_admin_redirect_url(): string
    {
        $url = defined('SITE_URL') ? trim((string) SITE_URL) : '/';
        if ($url === '' || str_contains($url, "\0") || preg_match('/[\r\n]/', $url) === 1) {
            return '/';
        }

        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return $url;
        }

        $scheme = strtolower((string) (parse_url($url, PHP_URL_SCHEME) ?: ''));
        if (in_array($scheme, ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false) {
            return $url;
        }

        return '/';
    }

    private static function load_admin_menu(): void
    {
        $menuFile = ABSPATH . 'admin/partials/admin-menu.php';
        if (!is_file($menuFile) || function_exists('renderAdminLayoutStart')) {
            return;
        }

        $menuReal = realpath($menuFile);
        $baseReal = realpath((string) ABSPATH);
        if ($menuReal === false || $baseReal === false) {
            return;
        }

        $normalizedBase = rtrim(str_replace('\\', '/', $baseReal), '/') . '/';
        $normalizedMenu = str_replace('\\', '/', $menuReal);
        if (!str_starts_with($normalizedMenu, $normalizedBase)) {
            return;
        }

        require_once $menuReal;
    }

    private static function layout_start(string $title, string $slug): void
    {
        self::load_admin_menu();
        if (function_exists('cms_plugin_admin_layout_start')) {
            cms_plugin_admin_layout_start($title, $slug);
            return;
        }

        if (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart($title, $slug);
        }
    }

    private static function layout_end(): void
    {
        if (function_exists('cms_plugin_admin_layout_end')) {
            cms_plugin_admin_layout_end();
            return;
        }

        if (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
        }
    }

    private static function enqueue_admin_assets(): void
    {
        $css = CMS_M365LANDING_PLUGIN_DIR . 'assets/css/m365landing-admin.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="' . self::esc(CMS_M365LANDING_PLUGIN_URL . 'assets/css/m365landing-admin.css') . '?v=' . filemtime($css) . '">' . "\n";
        }
    }

    private static function enqueue_admin_scripts(): void
    {
        $scripts = [];
        $js = CMS_M365LANDING_PLUGIN_DIR . 'assets/js/m365landing-admin.js';
        if (file_exists($js)) {
            $scripts[] = CMS_M365LANDING_PLUGIN_URL . 'assets/js/m365landing-admin.js?v=' . filemtime($js);
        }
        $scripts[] = self::core_asset_url('js/admin-media-integrations.js');

        foreach (array_unique(array_filter($scripts)) as $src) {
            echo '<script src="' . self::esc((string) $src) . '" defer></script>' . "\n";
        }
    }

    private static function core_asset_url(string $asset): string
    {
        if (function_exists('cms_asset_url')) {
            return (string) cms_asset_url($asset);
        }

        return rtrim((string) (defined('SITE_URL') ? SITE_URL : ''), '/') . '/assets/' . ltrim($asset, '/');
    }

    private static function admin_url(string $section = 'dashboard', array $params = []): string
    {
        $slug = self::section_slug($section);
        $url = self::ADMIN_BASE_URL . '/' . rawurlencode($slug);
        if ($params !== []) {
            $url .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        return $url;
    }

    private static function section_slug(string $section): string
    {
        $section = self::allowed_section($section);
        $map = self::section_page_slugs();

        return $map[$section] ?? ($map['dashboard'] ?? 'm365landing-dashboard');
    }

    private static function clean_slug(string $value): string
    {
        if (function_exists('cms_plugin_admin_normalize_slug')) {
            return cms_plugin_admin_normalize_slug($value);
        }

        return trim((string) preg_replace('/[^a-z0-9_-]+/i', '-', strtolower(trim($value))), '-');
    }

    private static function page_title(string $section): string
    {
        return match ($section) {
            'cards' => 'M365 Landing Karten',
            'settings' => 'M365 Landing Steuerung',
            'system' => 'M365 Landing System',
            default => 'M365 Landing',
        };
    }

    private static function allowed_section(string $section): string
    {
        return in_array($section, ['dashboard', 'cards', 'settings', 'system'], true) ? $section : 'dashboard';
    }

    private static function esc(mixed $value): string
    {
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif ($value === null) {
            $value = '';
        } elseif (!is_scalar($value)) {
            $value = '';
        }

        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private static function log_exception(string $context, \Throwable $e): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CMS M365 Landing [' . $context . ']: ' . $e->getMessage());
        }
    }
}
