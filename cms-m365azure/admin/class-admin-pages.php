<?php
/**
 * CMS M365 Azure – Admin Pages.
 *
 * @package CMS_M365Azure
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365Azure_Admin_Pages
{
    public const ADMIN_BASE_URL = '/admin/plugins/m365azure/m365azure';

    public static function render_dispatch(): void
    {
        self::check_access();
        CMS_M365Azure_Installer::maybe_install();

        $repo = CMS_M365Azure_Repository::instance();
        $section = self::allowed_section((string) ($_GET['section'] ?? 'dashboard'));
        $settingsPane = self::allowed_settings_pane((string) ($_GET['pane'] ?? 'content'));
        $notice = '';
        $error = '';

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            try {
                self::handle_post($repo);
                $notice = 'Änderungen gespeichert.';
            } catch (\Throwable $e) {
                $error = 'Aktion konnte nicht ausgeführt werden. Bitte Eingaben prüfen und erneut versuchen.';
                self::log_error('admin action failed :: ' . $e->getMessage());
            }
        }

        self::start_admin_layout(self::page_title($section));
        self::enqueue_admin_assets();

        echo '<div class="azs-admin-shell">';
        self::render_header($section, $notice, $error);
        echo '<div class="azs-admin-layout">';
        self::render_sidebar($section, $settingsPane);
        echo '<div class="azs-admin-content">';
        self::render_section($repo, $section, $settingsPane);
        echo '</div>';
        echo '</div>';
        echo '</div>';

        self::render_delete_modal();
        self::render_media_picker_modal();
        self::enqueue_admin_scripts();
        self::end_admin_layout();
    }

    private static function handle_post(CMS_M365Azure_Repository $repo): void
    {
        self::assert_manage_permissions();

        if (!class_exists('CMS\\Security') || !\CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'm365azure_admin')) {
            throw new \RuntimeException('Sicherheitscheck fehlgeschlagen.');
        }

        $action = self::allowed_action((string) ($_POST['action'] ?? ''));
        if ($action === 'save_category') {
            $repo->save_category(self::category_payload());
            return;
        }

        if ($action === 'delete_category') {
            $repo->delete_category(self::positive_id($_POST['id'] ?? 0));
            return;
        }

        if ($action === 'save_service') {
            if ($repo->categories(false) === []) {
                throw new \RuntimeException('Services benötigen mindestens eine Kategorie.');
            }
            $repo->save_service(self::service_payload());
            return;
        }

        if ($action === 'delete_service') {
            $repo->delete_service(self::positive_id($_POST['id'] ?? 0));
            return;
        }

        if ($action === 'save_settings') {
            $repo->save_settings(self::collect_settings());
            return;
        }

        throw new \RuntimeException('Unbekannte Aktion.');
    }

    private static function allowed_action(string $action): string
    {
        return in_array($action, ['save_category', 'delete_category', 'save_service', 'delete_service', 'save_settings'], true)
            ? $action
            : '';
    }

    /** @return array<string,mixed> */
    private static function category_payload(): array
    {
        return [
            'id' => self::positive_id($_POST['id'] ?? 0),
            'slug' => (string) ($_POST['slug'] ?? ''),
            'title' => (string) ($_POST['title'] ?? ''),
            'overline' => (string) ($_POST['overline'] ?? ''),
            'intro' => (string) ($_POST['intro'] ?? ''),
            'gallery_images' => is_array($_POST['gallery_images'] ?? null) ? $_POST['gallery_images'] : [],
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'is_active' => !empty($_POST['is_active']) ? 1 : 0,
        ];
    }

    /** @return array<string,mixed> */
    private static function service_payload(): array
    {
        return [
            'id' => self::positive_id($_POST['id'] ?? 0),
            'category_id' => self::positive_id($_POST['category_id'] ?? 0),
            'slug' => (string) ($_POST['slug'] ?? ''),
            'title' => (string) ($_POST['title'] ?? ''),
            'subtitle' => (string) ($_POST['subtitle'] ?? ''),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'image_url' => (string) ($_POST['image_url'] ?? ''),
            'image_alt' => (string) ($_POST['image_alt'] ?? ''),
            'summary' => (string) ($_POST['summary'] ?? ''),
            'content' => (string) ($_POST['content'] ?? ''),
            'features' => (string) ($_POST['features'] ?? ''),
            'use_cases' => (string) ($_POST['use_cases'] ?? ''),
            'docs_url' => (string) ($_POST['docs_url'] ?? ''),
            'pricing_url' => (string) ($_POST['pricing_url'] ?? ''),
            'is_active' => !empty($_POST['is_active']) ? 1 : 0,
        ];
    }

    private static function positive_id(mixed $value): int
    {
        return max(0, (int) $value);
    }

    /** @return array<string,string> */
    private static function collect_settings(): array
    {
        $settings = [];
        foreach (self::text_setting_keys() as $key) {
            $value = (string) ($_POST[$key] ?? '');
            if ($key === 'route_slug') {
                $settings[$key] = CMS_M365Azure_Repository::slug($value);
                continue;
            }

            if (str_ends_with($key, '_url')) {
                $settings[$key] = CMS_M365Azure_Repository::public_url($value);
                continue;
            }

            $settings[$key] = CMS_M365Azure_Repository::long_text($value);
        }

        foreach (self::color_setting_defaults() as $key => $fallback) {
            $settings[$key] = CMS_M365Azure_Repository::color((string) ($_POST[$key] ?? ''), $fallback);
        }

        foreach (self::numeric_setting_bounds() as $key => [$min, $max]) {
            $value = (int) ($_POST[$key] ?? $min);
            if ($key === 'card_image_width' && $value > $max) {
                $value = 72;
            }
            $settings[$key] = (string) max($min, min($max, $value));
        }

        foreach (self::enum_setting_options() as $key => $options) {
            $value = (string) ($_POST[$key] ?? '');
            $settings[$key] = in_array($value, $options, true) ? $value : (string) $options[0];
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
            'route_slug', 'page_title', 'page_overline', 'page_intro', 'seo_title', 'seo_description',
            'hero_primary_button_text', 'hero_primary_button_url', 'hero_secondary_button_text', 'hero_secondary_button_url', 'hero_cta_button_text', 'hero_cta_button_url',
            'toc_title', 'table_service_label', 'table_description_label', 'table_features_label', 'table_use_cases_label', 'table_links_label',
            'docs_link_label', 'pricing_link_label', 'empty_value_label', 'note_title', 'note_items', 'source_title', 'source_intro', 'source_details_label',
        ];
    }

    /** @return array<int,string> */
    private static function bool_setting_keys(): array
    {
        return [
            'show_hero', 'show_hero_actions', 'show_toc', 'toc_nowrap', 'show_category_intro',
            'show_service_images', 'show_service_subtitles', 'show_description', 'show_service_links', 'show_feature_lists', 'show_use_cases',
            'show_notes_section', 'show_info_note', 'show_sources_card',
        ];
    }

    /** @return array<string,string> */
    private static function color_setting_defaults(): array
    {
        return [
            'design_primary_color' => '#2563eb',
            'design_accent_color' => '#f59e0b',
            'design_background_color' => '#ffffff',
            'design_surface_color' => '#ffffff',
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
            'card_image_width' => [40, 160],
            'design_border_radius' => [0, 32],
            'design_toc_font_size' => [10, 18],
            'design_table_font_size' => [11, 18],
            'design_link_font_size' => [10, 16],
        ];
    }

    /** @return array<string,array<int,string>> */
    private static function enum_setting_options(): array
    {
        return [
            'card_image_position' => ['left', 'right'],
            'toc_columns' => ['1', '2', '3', '4'],
        ];
    }

    private static function render_header(string $section, string $notice, string $error): void
    {
        echo '<div class="admin-page-header"><div><h2>☁️ ' . self::esc(self::page_title($section)) . '</h2><p>Azure-Service-Kategorien, Service-Cards, Texte, Bilder und Design zentral steuern.</p></div>';
        echo '<div class="header-actions"><a class="btn btn-secondary" href="' . self::esc(self::public_archive_url()) . '" target="_blank" rel="noopener noreferrer">👁️ Public ansehen</a></div></div>';
        if ($notice !== '') {
            echo '<div class="alert alert-success">✅ ' . self::esc($notice) . '</div>';
        }
        if ($error !== '') {
            echo '<div class="alert alert-error">❌ ' . self::esc($error) . '</div>';
        }
    }

    private static function render_sidebar(string $section, string $settingsPane): void
    {
        $items = [
            'dashboard' => '📊 Dashboard',
            'categories' => '🗂️ Kategorien',
            'services' => '☁️ Services',
            'settings' => '⚙️ Steuerung & Design',
            'system' => '🖥️ System',
        ];

        echo '<aside class="azs-sidebar" aria-label="M365 Azure Navigation">';
        echo '<nav class="azs-sidebar-nav">';
        foreach ($items as $key => $label) {
            $active = $section === $key ? ' active' : '';
            echo '<a class="azs-sidebar-link' . $active . '" href="' . self::esc(self::admin_url($key)) . '">' . self::esc($label) . '</a>';
        }
        echo '</nav>';

        if ($section === 'settings') {
            self::render_settings_submenu($settingsPane);
        }

        echo '</aside>';
    }

    private static function render_settings_submenu(string $activePane): void
    {
        $panes = [
            'content' => '📝 Inhalte',
            'hero' => '🔗 Hero-Buttons',
            'table' => '🏷️ Tabellen-Texte',
            'visibility' => '👁️ Sichtbarkeit',
            'toc' => '🧭 Inhaltsverzeichnis',
            'cards' => '🃏 Cards',
            'notes' => 'ℹ️ Hinweise',
            'design' => '🎨 Design',
        ];

        echo '<section class="azs-sidebar-submenu" aria-label="Einstellungen Untermenü">';
        echo '<h3>Untermenü</h3>';
        echo '<nav class="azs-sidebar-submenu-links">';
        foreach ($panes as $pane => $label) {
            $active = $activePane === $pane ? ' active' : '';
            echo '<a class="azs-sidebar-sublink' . $active . '" href="'
                . self::esc(self::admin_url('settings', ['pane' => $pane]))
                . '">' . self::esc($label) . '</a>';
        }
        echo '</nav></section>';
    }

    private static function render_section(CMS_M365Azure_Repository $repo, string $section, string $settingsPane): void
    {
        $callbacks = [
            'dashboard' => static fn() => self::render_dashboard($repo),
            'categories' => static fn() => self::render_categories($repo),
            'services' => static fn() => self::render_services($repo),
            'settings' => static fn() => self::render_settings($repo, $settingsPane),
            'system' => static fn() => self::render_system($repo),
        ];
        $callback = $callbacks[$section] ?? null;

        if (!is_callable($callback)) {
            if (function_exists('cms_plugin_admin_emit_notice')) {
                cms_plugin_admin_emit_notice(
                    'Die angeforderte Admin-Sektion ist nicht verfügbar. Dashboard wird angezeigt.',
                    'error',
                    'missing section callback section=' . $section
                );
            } else {
                echo '<div class="alert alert-error">Die angeforderte Admin-Sektion ist nicht verfügbar. Dashboard wird angezeigt.</div>';
            }
            self::render_dashboard($repo);
            return;
        }

        $callback();
    }

    private static function render_dashboard(CMS_M365Azure_Repository $repo): void
    {
        $stats = $repo->stats();
        echo '<div class="admin-card azs-card-connected"><h3>📊 Übersicht</h3><div class="dashboard-grid">';
        foreach ([['🗂️', 'Kategorien', $stats['categories'] ?? 0], ['☁️', 'Services', $stats['services'] ?? 0], ['✅', 'Aktive Services', $stats['active_services'] ?? 0]] as [$icon, $label, $value]) {
            echo '<div class="stat-card"><div class="stat-icon">' . self::esc((string) $icon) . '</div><div class="stat-number">' . (int) $value . '</div><div class="stat-label">' . self::esc((string) $label) . '</div></div>';
        }
        echo '</div></div>';
        echo '<div class="admin-card"><h3>⚡ Schnellzugriff</h3><div class="azs-quicklinks"><a class="btn btn-secondary" href="' . self::esc(self::admin_url('services', ['edit' => 0])) . '">➕ Service anlegen</a><a class="btn btn-secondary" href="' . self::esc(self::admin_url('categories', ['edit' => 0])) . '">➕ Kategorie anlegen</a><a class="btn btn-primary" href="' . self::esc(self::admin_url('settings')) . '">⚙️ Darstellung steuern</a></div></div>';
    }

    private static function render_categories(CMS_M365Azure_Repository $repo): void
    {
        $editId = isset($_GET['edit']) ? max(0, (int) $_GET['edit']) : -1;
        $edit = $editId > 0 ? $repo->category($editId) : null;
        $token = self::csrf();
        echo '<div class="admin-card azs-card-connected"><h3>🗂️ Kategorien verwalten</h3>';
        self::render_category_form($edit, $token);
        echo '<hr class="azs-separator"><div class="users-table-container"><table class="users-table"><thead><tr><th>Titel</th><th>Slug</th><th>Status</th><th>Bilder</th><th>Sortierung</th><th>Aktionen</th></tr></thead><tbody>';
        foreach ($repo->categories(false) as $cat) {
            $name = (string) $cat['title'];
            $galleryCount = count(CMS_M365Azure_Repository::gallery_images_list($cat['gallery_images'] ?? ''));
            echo '<tr><td><strong>' . self::esc($name) . '</strong></td><td><code>' . self::esc((string) $cat['slug']) . '</code></td><td>' . self::status((int) $cat['is_active'] === 1) . '</td><td>' . ($galleryCount > 0 ? '🖼️ ' . (int) $galleryCount : '—') . '</td><td>' . (int) $cat['sort_order'] . '</td><td><div class="azs-actions"><a class="btn btn-sm btn-secondary" href="' . self::esc(self::admin_url('categories', ['edit' => (int) $cat['id']])) . '">✏️</a><button type="button" class="btn btn-sm btn-danger" data-delete-entity="category" data-delete-id="' . (int) $cat['id'] . '" data-delete-name="' . self::esc($name) . '">🗑️</button></div></td></tr>';
        }
        echo '</tbody></table></div></div>';
    }

    /** @param array<string,mixed>|null $edit */
    private static function render_category_form(?array $edit, string $token): void
    {
        echo '<form method="POST" class="admin-form azs-form-grid"><input type="hidden" name="action" value="save_category"><input type="hidden" name="csrf_token" value="' . self::esc($token) . '"><input type="hidden" name="id" value="' . (int) ($edit['id'] ?? 0) . '">';
        self::input('title', 'Titel', (string) ($edit['title'] ?? ''), true);
        self::input('slug', 'Slug', (string) ($edit['slug'] ?? ''), false);
        self::input('overline', 'Overline', (string) ($edit['overline'] ?? 'Azure Kategorie'), false);
        self::number('sort_order', 'Sortierung', (int) ($edit['sort_order'] ?? 100));
        self::textarea('intro', 'Beschreibung', (string) ($edit['intro'] ?? ''), 3);
        self::render_category_gallery_field(CMS_M365Azure_Repository::gallery_images_list($edit['gallery_images'] ?? ''));
        self::checkbox('is_active', 'Kategorie aktiv anzeigen', (int) ($edit['is_active'] ?? 1) === 1);
        echo '<div class="azs-form-actions"><button class="btn btn-primary" type="submit">💾 Kategorie speichern</button></div></form>';
    }

    private static function render_services(CMS_M365Azure_Repository $repo): void
    {
        $editId = isset($_GET['edit']) ? max(0, (int) $_GET['edit']) : -1;
        $edit = $editId > 0 ? $repo->service($editId) : null;
        $categories = $repo->categories(false);
        $token = self::csrf();
        echo '<div class="admin-card azs-card-connected"><h3>☁️ Azure Services verwalten</h3>';
        if ($categories === []) {
            echo '<div class="alert alert-error">Bitte zuerst mindestens eine Kategorie anlegen, bevor Services erstellt werden.</div>';
            echo '</div>';
            return;
        }
        self::render_service_form($edit, $categories, $token);
        echo '<hr class="azs-separator"><div class="users-table-container"><table class="users-table"><thead><tr><th>Service</th><th>Kategorie</th><th>Status</th><th>Bild</th><th>Aktionen</th></tr></thead><tbody>';
        foreach ($repo->services(null, false) as $service) {
            $name = (string) $service['title'];
            echo '<tr><td><strong>' . self::esc($name) . '</strong><br><small>' . self::esc((string) $service['slug']) . '</small></td><td>' . self::esc((string) $service['category_title']) . '</td><td>' . self::status((int) $service['is_active'] === 1) . '</td><td>' . (((string) ($service['image_url'] ?? '') !== '') ? '🖼️' : '—') . '</td><td><div class="azs-actions"><a class="btn btn-sm btn-secondary" href="' . self::esc(self::admin_url('services', ['edit' => (int) $service['id']])) . '">✏️</a><button type="button" class="btn btn-sm btn-danger" data-delete-entity="service" data-delete-id="' . (int) $service['id'] . '" data-delete-name="' . self::esc($name) . '">🗑️</button></div></td></tr>';
        }
        echo '</tbody></table></div></div>';
    }

    /** @param array<string,mixed>|null $edit @param array<int,array<string,mixed>> $categories */
    private static function render_service_form(?array $edit, array $categories, string $token): void
    {
        echo '<form method="POST" class="admin-form azs-form-grid"><input type="hidden" name="action" value="save_service"><input type="hidden" name="csrf_token" value="' . self::esc($token) . '"><input type="hidden" name="id" value="' . (int) ($edit['id'] ?? 0) . '">';
        echo '<div class="form-group"><label class="form-label" for="category_id">Kategorie</label><select class="form-control" id="category_id" name="category_id">';
        foreach ($categories as $cat) {
            $selected = (int) ($edit['category_id'] ?? 0) === (int) $cat['id'] ? ' selected' : '';
            echo '<option value="' . (int) $cat['id'] . '"' . $selected . '>' . self::esc((string) $cat['title']) . '</option>';
        }
        echo '</select></div>';
        self::input('title', 'Service-Titel', (string) ($edit['title'] ?? ''), true);
        self::input('slug', 'Slug', (string) ($edit['slug'] ?? ''), false);
        self::input('subtitle', 'Kurzzeile', (string) ($edit['subtitle'] ?? ''), false);
        self::number('sort_order', 'Sortierung', (int) ($edit['sort_order'] ?? 100));
        self::input('image_url', 'Bild-URL', (string) ($edit['image_url'] ?? ''), false);
        self::input('image_alt', 'Bild-Alt-Text', (string) ($edit['image_alt'] ?? ''), false);
        self::textarea('summary', 'Kurzbeschreibung', (string) ($edit['summary'] ?? ''), 3);
        self::textarea('content', 'Haupttext', (string) ($edit['content'] ?? ''), 5);
        self::textarea('features', 'Features – je Zeile ein Punkt', (string) ($edit['features'] ?? ''), 4);
        self::textarea('use_cases', 'Einsatzbereiche – je Zeile ein Punkt', (string) ($edit['use_cases'] ?? ''), 4);
        self::input('docs_url', 'Dokumentations-Link', (string) ($edit['docs_url'] ?? ''), false);
        self::input('pricing_url', 'Preis-Link', (string) ($edit['pricing_url'] ?? ''), false);
        self::checkbox('is_active', 'Service öffentlich anzeigen', (int) ($edit['is_active'] ?? 1) === 1);
        echo '<div class="azs-form-actions"><button class="btn btn-primary" type="submit">💾 Service speichern</button></div></form>';
    }

    private static function render_settings(CMS_M365Azure_Repository $repo, string $pane): void
    {
        $s = $repo->settings();
        echo '<div class="admin-card azs-card-connected"><form method="POST" class="admin-form"><input type="hidden" name="action" value="save_settings"><input type="hidden" name="csrf_token" value="' . self::esc(self::csrf()) . '">';
        foreach (self::bool_setting_keys() as $boolKey) {
            echo '<input type="hidden" name="' . self::esc($boolKey) . '" value="' . self::esc((string) ($s[$boolKey] ?? '0')) . '">';
        }
        foreach (self::text_setting_keys() as $hiddenKey) {
            echo '<input type="hidden" name="' . self::esc($hiddenKey) . '" value="' . self::esc((string) ($s[$hiddenKey] ?? '')) . '">';
        }
        foreach (array_keys(self::color_setting_defaults()) as $hiddenKey) {
            echo '<input type="hidden" name="' . self::esc($hiddenKey) . '" value="' . self::esc((string) ($s[$hiddenKey] ?? '')) . '">';
        }
        foreach (array_keys(self::numeric_setting_bounds()) as $hiddenKey) {
            echo '<input type="hidden" name="' . self::esc($hiddenKey) . '" value="' . self::esc((string) ($s[$hiddenKey] ?? '')) . '">';
        }
        foreach (array_keys(self::enum_setting_options()) as $hiddenKey) {
            echo '<input type="hidden" name="' . self::esc($hiddenKey) . '" value="' . self::esc((string) ($s[$hiddenKey] ?? '')) . '">';
        }
        if ($pane === 'content') {
            echo '<h3>📝 Seiteninhalte</h3>';
            self::replace_input('route_slug', 'Öffentlicher Slug', (string) ($s['route_slug'] ?? 'azure-services'));
            self::replace_input('page_overline', 'Overline', (string) ($s['page_overline'] ?? 'Azure Überblick'));
            self::replace_input('page_title', 'Seitentitel', (string) ($s['page_title'] ?? 'Microsoft Azure Services'));
            self::replace_textarea('page_intro', 'Einleitung', (string) ($s['page_intro'] ?? ''), 4);
            self::replace_input('seo_title', 'SEO-Titel', (string) ($s['seo_title'] ?? ''));
            self::replace_textarea('seo_description', 'SEO-Beschreibung', (string) ($s['seo_description'] ?? ''), 3);
        } elseif ($pane === 'hero') {
            echo '<h3>🔗 Hero-Buttons</h3>';
            self::replace_checkbox('show_hero', 'Headerbereich anzeigen', (string) ($s['show_hero'] ?? '1') === '1');
            self::replace_checkbox('show_hero_actions', 'Hero-Buttons anzeigen', (string) ($s['show_hero_actions'] ?? '1') === '1');
            self::replace_input('hero_primary_button_text', 'Button 1 Text', (string) ($s['hero_primary_button_text'] ?? 'M365 Lizenzmatrix öffnen'));
            self::replace_input('hero_primary_button_url', 'Button 1 Ziel', (string) ($s['hero_primary_button_url'] ?? '/m365-lizenzmatrix'));
            self::replace_input('hero_secondary_button_text', 'Button 2 Text', (string) ($s['hero_secondary_button_text'] ?? 'M365 AddOn-Übersicht öffnen'));
            self::replace_input('hero_secondary_button_url', 'Button 2 Ziel', (string) ($s['hero_secondary_button_url'] ?? '/m365-addon-matrix'));
            self::replace_input('hero_cta_button_text', 'CTA Button Text', (string) ($s['hero_cta_button_text'] ?? 'Azure-Beratung anfragen'));
            self::replace_input('hero_cta_button_url', 'CTA Button Ziel', (string) ($s['hero_cta_button_url'] ?? '/kontakt'));
        } elseif ($pane === 'table') {
            echo '<h3>🏷️ Tabellen- und Link-Texte</h3>';
            self::replace_input('table_service_label', 'Spalte: Dienst', (string) ($s['table_service_label'] ?? 'Dienst'));
            self::replace_input('table_description_label', 'Spalte: Beschreibung', (string) ($s['table_description_label'] ?? 'Beschreibung'));
            self::replace_input('table_features_label', 'Spalte: Wichtige Hinweise', (string) ($s['table_features_label'] ?? 'Wichtige Hinweise'));
            self::replace_input('table_use_cases_label', 'Spalte: Typische Einsatzszenarien', (string) ($s['table_use_cases_label'] ?? 'Typische Einsatzszenarien'));
            self::replace_input('table_links_label', 'Spalte: Links', (string) ($s['table_links_label'] ?? 'Links'));
            self::replace_input('docs_link_label', 'Dokumentations-Link Text', (string) ($s['docs_link_label'] ?? 'Dokumentation'));
            self::replace_input('pricing_link_label', 'Preis-Link Text', (string) ($s['pricing_link_label'] ?? 'Preise'));
            self::replace_input('empty_value_label', 'Text für leere Werte', (string) ($s['empty_value_label'] ?? '—'));
        } elseif ($pane === 'visibility') {
            echo '<h3>👁️ Sichtbarkeit</h3>';
            self::replace_checkbox('show_hero', 'Headerbereich anzeigen', (string) ($s['show_hero'] ?? '1') === '1');
            self::replace_checkbox('show_hero_actions', 'Hero-Buttons anzeigen', (string) ($s['show_hero_actions'] ?? '1') === '1');
            self::replace_checkbox('show_toc', 'Inhaltsverzeichnis anzeigen', (string) ($s['show_toc'] ?? '1') === '1');
            self::replace_checkbox('show_category_intro', 'Kategorie-Beschreibungen anzeigen', (string) ($s['show_category_intro'] ?? '1') === '1');
            self::replace_checkbox('show_service_images', 'Service-Bilder anzeigen', (string) ($s['show_service_images'] ?? '1') === '1');
            self::replace_checkbox('show_service_subtitles', 'Service-Kurzzeilen anzeigen', (string) ($s['show_service_subtitles'] ?? '1') === '1');
            self::replace_checkbox('show_description', 'Beschreibungsspalte anzeigen', (string) ($s['show_description'] ?? '1') === '1');
            self::replace_checkbox('show_feature_lists', 'Wichtige Hinweise anzeigen', (string) ($s['show_feature_lists'] ?? '1') === '1');
            self::replace_checkbox('show_use_cases', 'Einsatzszenarien anzeigen', (string) ($s['show_use_cases'] ?? '1') === '1');
            self::replace_checkbox('show_service_links', 'Dokumentations-/Preislinks anzeigen', (string) ($s['show_service_links'] ?? '1') === '1');
            self::replace_checkbox('show_notes_section', 'Hinweis-/Quellenbereich anzeigen', (string) ($s['show_notes_section'] ?? '1') === '1');
            self::replace_checkbox('show_info_note', 'Hinweisbox anzeigen', (string) ($s['show_info_note'] ?? '1') === '1');
            self::replace_checkbox('show_sources_card', 'Quellenbox anzeigen', (string) ($s['show_sources_card'] ?? '1') === '1');
        } elseif ($pane === 'toc') {
            echo '<h3>🧭 Inhaltsverzeichnis</h3>';
            self::replace_checkbox('show_toc', 'Inhaltsverzeichnis anzeigen', (string) ($s['show_toc'] ?? '1') === '1');
            self::replace_input('toc_title', 'Überschrift', (string) ($s['toc_title'] ?? 'Inhaltsverzeichnis'));
            self::replace_checkbox('show_category_intro', 'Kategorie-Beschreibungen anzeigen', (string) ($s['show_category_intro'] ?? '1') === '1');
            self::replace_select('toc_columns', 'Maximale Spalten', (string) ($s['toc_columns'] ?? '3'), ['1' => '1 Spalte', '2' => '2 Spalten', '3' => '3 Spalten', '4' => '4 Spalten']);
            self::replace_checkbox('toc_nowrap', 'Einträge einzeilig halten', (string) ($s['toc_nowrap'] ?? '1') === '1');
        } elseif ($pane === 'cards') {
            echo '<h3>🃏 Card-Layout</h3>';
            self::replace_checkbox('show_service_images', 'Service-Bilder anzeigen', (string) ($s['show_service_images'] ?? '1') === '1');
            self::replace_checkbox('show_service_subtitles', 'Service-Kurzzeilen anzeigen', (string) ($s['show_service_subtitles'] ?? '1') === '1');
            self::replace_checkbox('show_service_links', 'Dokumentations-/Preislinks anzeigen', (string) ($s['show_service_links'] ?? '1') === '1');
            self::replace_checkbox('show_feature_lists', 'Feature-Listen anzeigen', (string) ($s['show_feature_lists'] ?? '1') === '1');
            self::replace_checkbox('show_use_cases', 'Einsatzbereiche anzeigen', (string) ($s['show_use_cases'] ?? '1') === '1');
            self::replace_select('card_image_position', 'Bildposition', (string) ($s['card_image_position'] ?? 'left'), ['left' => 'Links', 'right' => 'Rechts']);
            self::replace_number('card_image_width', 'Bildbreite in px', (int) ($s['card_image_width'] ?? 72), 40, 160);
        } elseif ($pane === 'notes') {
            echo '<h3>ℹ️ Hinweise & Quellen</h3>';
            self::replace_checkbox('show_notes_section', 'Hinweis-/Quellenbereich anzeigen', (string) ($s['show_notes_section'] ?? '1') === '1');
            self::replace_checkbox('show_info_note', 'Hinweisbox anzeigen', (string) ($s['show_info_note'] ?? '1') === '1');
            self::replace_input('note_title', 'Hinweisbox Überschrift', (string) ($s['note_title'] ?? 'Hinweise zu Azure Services'));
            self::replace_textarea('note_items', 'Hinweise – je Zeile ein Punkt', (string) ($s['note_items'] ?? ''), 4);
            self::replace_checkbox('show_sources_card', 'Quellenbox anzeigen', (string) ($s['show_sources_card'] ?? '1') === '1');
            self::replace_input('source_title', 'Quellenbox Überschrift', (string) ($s['source_title'] ?? 'Quellenstand'));
            self::replace_textarea('source_intro', 'Quellenbox Text', (string) ($s['source_intro'] ?? ''), 3);
            self::replace_input('source_details_label', 'Details-Link Text', (string) ($s['source_details_label'] ?? 'Quellen anzeigen'));
        } else {
            echo '<h3>🎨 Design</h3>';
            self::replace_number('layout_max_width', 'Maximale Inhaltsbreite in px', (int) ($s['layout_max_width'] ?? 1180), 720, 1600);
            self::replace_number('layout_padding_x', 'Seitlicher Innenabstand in px', (int) ($s['layout_padding_x'] ?? 0), 0, 80);
            self::replace_number('layout_padding_top', 'Oberer Innenabstand in px', (int) ($s['layout_padding_top'] ?? 25), 0, 120);
            self::replace_color('design_primary_color', 'Primärfarbe', (string) ($s['design_primary_color'] ?? '#2563eb'), '#2563eb');
            self::replace_color('design_accent_color', 'Akzentfarbe', (string) ($s['design_accent_color'] ?? '#f59e0b'), '#f59e0b');
            self::replace_color('design_background_color', 'Seitenhintergrund', (string) ($s['design_background_color'] ?? '#ffffff'), '#ffffff');
            self::replace_color('design_surface_color', 'Card-Hintergrund', (string) ($s['design_surface_color'] ?? '#ffffff'), '#ffffff');
            self::replace_color('design_text_color', 'Textfarbe', (string) ($s['design_text_color'] ?? '#1e293b'), '#1e293b');
            self::replace_color('design_muted_color', 'Sekundärtext', (string) ($s['design_muted_color'] ?? '#64748b'), '#64748b');
            self::replace_color('design_border_color', 'Rahmenfarbe', (string) ($s['design_border_color'] ?? '#e2e8f0'), '#e2e8f0');
            self::replace_number('design_border_radius', 'Card-Radius in px', (int) ($s['design_border_radius'] ?? 10), 0, 32);
            self::replace_number('design_toc_font_size', 'TOC-Schriftgröße in px', (int) ($s['design_toc_font_size'] ?? 13), 10, 18);
            self::replace_number('design_table_font_size', 'Tabellen-Schriftgröße in px', (int) ($s['design_table_font_size'] ?? 14), 11, 18);
            self::replace_number('design_link_font_size', 'Link-Schriftgröße in px', (int) ($s['design_link_font_size'] ?? 12), 10, 16);
        }
        echo '<button class="btn btn-primary" type="submit">💾 Einstellungen speichern</button></form></div>';
    }

    private static function render_system(CMS_M365Azure_Repository $repo): void
    {
        $stats = $repo->stats();
        echo '<div class="admin-card azs-card-connected"><h3>🖥️ System-Informationen</h3><div class="info-grid"><div class="info-card"><h4>Plugin</h4><ul class="info-list"><li><strong>Version:</strong> ' . self::esc(CMS_M365AZURE_VERSION) . '</li><li><strong>DB-Version:</strong> ' . self::esc(CMS_M365AZURE_DB_VERSION) . '</li></ul></div><div class="info-card"><h4>Inhalte</h4><ul class="info-list"><li><strong>Kategorien:</strong> ' . (int) ($stats['categories'] ?? 0) . '</li><li><strong>Services:</strong> ' . (int) ($stats['services'] ?? 0) . '</li></ul></div></div></div>';
    }

    private static function render_delete_modal(): void
    {
        echo '<div id="azsDeleteModal" class="modal" style="display:none;"><div class="modal-content" style="max-width:480px;"><div class="modal-header"><h3>🗑️ Eintrag löschen</h3><button class="modal-close" type="button" data-azs-close>&times;</button></div><div class="modal-body"><p>Soll <strong id="azsDeleteName"></strong> wirklich gelöscht werden?</p><p style="color:#ef4444;font-size:.875rem;">⚠️ Diese Aktion kann nicht rückgängig gemacht werden.</p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-azs-close>Abbrechen</button><form method="POST" id="azsDeleteForm"><input type="hidden" name="csrf_token" value="' . self::esc(self::csrf()) . '"><input type="hidden" name="action" id="azsDeleteAction"><input type="hidden" name="id" id="azsDeleteId"><button class="btn btn-danger" type="submit">🗑️ Endgültig löschen</button></form></div></div></div>';
    }

    private static function render_media_picker_modal(): void
    {
        $token = '';
        if (class_exists('CMS\\Security')) {
            try {
                $token = \CMS\Security::instance()->generateToken('editorjs_media');
            } catch (\Throwable $e) {
                self::log_error('media token generation failed :: ' . $e->getMessage());
            }
        }

        echo '<div class="modal modal-blur fade" id="settingsMediaPickerModal" tabindex="-1" aria-hidden="true">';
        echo '<div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">';
        echo '<div class="modal-header"><h5 class="modal-title" data-media-picker-title>Bild auswählen</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button></div>';
        echo '<div class="modal-body"><div data-media-picker-modal data-api-url="/api/media" data-csrf-token="' . self::esc($token) . '">';
        echo '<p class="text-secondary small mb-3">Ein Klick übernimmt das Bild direkt in den gewählten Galerie-Slot.</p>';
        echo '<div class="row g-2 align-items-center mb-3"><div class="col-md-8"><input type="search" class="form-control" placeholder="Mediathek durchsuchen …" data-media-picker-search></div><div class="col-md-4 text-secondary small" data-media-picker-status>Lade Medien …</div></div>';
        echo '<div class="row row-cards g-3" data-media-picker-grid></div>';
        echo '</div></div></div></div></div>';
    }

    /** @param array<int,string> $images */
    private static function render_category_gallery_field(array $images): void
    {
        echo '<section class="form-group azs-wide azs-gallery-admin" aria-label="Kategorie-Bilder">';
        echo '<label class="form-label">Kategorie-Galerie</label>';
        echo '<p class="form-text">Bis zu 6 Bilder aus der Mediathek auswählen. Leere Slots werden öffentlich nicht angezeigt.</p>';
        echo '<div class="azs-gallery-admin__grid">';
        for ($i = 0; $i < 6; $i++) {
            $value = (string) ($images[$i] ?? '');
            $inputId = 'category_gallery_image_' . $i;
            $previewId = $inputId . '_preview';
            echo '<article class="azs-gallery-admin__slot">';
            echo '<div id="' . self::esc($previewId) . '" class="azs-gallery-admin__preview" data-media-preview data-preview-variant="image" data-input-id="' . self::esc($inputId) . '"' . ($value === '' ? ' hidden' : '') . '>';
            if ($value !== '') {
                echo '<img src="' . self::esc($value) . '" alt="Galerie-Bild ' . (int) ($i + 1) . ' Vorschau" loading="lazy">';
            }
            echo '</div>';
            echo '<label class="azs-visually-hidden" for="' . self::esc($inputId) . '">Galerie-Bild ' . (int) ($i + 1) . '</label>';
            echo '<input class="form-control" type="text" id="' . self::esc($inputId) . '" name="gallery_images[]" value="' . self::esc($value) . '" placeholder="/uploads/bild.webp" data-media-target-input data-azs-gallery-input>';
            echo '<div class="azs-gallery-admin__actions">';
            echo '<button type="button" class="btn btn-secondary btn-sm" data-open-media-picker data-target-input="' . self::esc($inputId) . '" data-preview-id="' . self::esc($previewId) . '" data-picker-title="Galerie-Bild ' . (int) ($i + 1) . ' auswählen">🖼️ Mediathek</button>';
            echo '<button type="button" class="btn btn-secondary btn-sm" data-clear-media-input data-target-input="' . self::esc($inputId) . '" data-preview-id="' . self::esc($previewId) . '">Leeren</button>';
            echo '</div></article>';
        }
        echo '</div></section>';
    }

    private static function input(string $name, string $label, string $value, bool $required): void
    {
        echo '<div class="form-group"><label class="form-label" for="' . self::esc($name) . '">' . self::esc($label) . '</label><input class="form-control" type="text" id="' . self::esc($name) . '" name="' . self::esc($name) . '" value="' . self::esc($value) . '"' . ($required ? ' required' : '') . '></div>';
    }

    private static function replace_input(string $name, string $label, string $value): void
    {
        echo '<input type="hidden" name="' . self::esc($name) . '" value="">';
        self::input($name, $label, $value, false);
    }

    private static function textarea(string $name, string $label, string $value, int $rows): void
    {
        $value = CMS_M365Azure_Repository::normalize_newlines($value);
        echo '<div class="form-group azs-wide"><label class="form-label" for="' . self::esc($name) . '">' . self::esc($label) . '</label><textarea class="form-control" id="' . self::esc($name) . '" name="' . self::esc($name) . '" rows="' . (int) $rows . '">' . self::esc($value) . '</textarea></div>';
    }

    private static function replace_textarea(string $name, string $label, string $value, int $rows): void
    {
        echo '<input type="hidden" name="' . self::esc($name) . '" value="">';
        self::textarea($name, $label, $value, $rows);
    }

    private static function number(string $name, string $label, int $value): void
    {
        echo '<div class="form-group"><label class="form-label" for="' . self::esc($name) . '">' . self::esc($label) . '</label><input class="form-control" type="number" id="' . self::esc($name) . '" name="' . self::esc($name) . '" value="' . (int) $value . '"></div>';
    }

    private static function replace_number(string $name, string $label, int $value, int $min, int $max): void
    {
        echo '<div class="form-group"><label class="form-label" for="' . self::esc($name) . '">' . self::esc($label) . '</label><input class="form-control" type="number" min="' . (int) $min . '" max="' . (int) $max . '" id="' . self::esc($name) . '" name="' . self::esc($name) . '" value="' . (int) $value . '"></div>';
    }

    /** @param array<string,string> $options */
    private static function replace_select(string $name, string $label, string $value, array $options): void
    {
        echo '<div class="form-group"><label class="form-label" for="' . self::esc($name) . '">' . self::esc($label) . '</label><select class="form-control" id="' . self::esc($name) . '" name="' . self::esc($name) . '">';
        foreach ($options as $optionValue => $optionLabel) {
            $selected = $value === $optionValue ? ' selected' : '';
            echo '<option value="' . self::esc($optionValue) . '"' . $selected . '>' . self::esc($optionLabel) . '</option>';
        }
        echo '</select></div>';
    }

    private static function checkbox(string $name, string $label, bool $checked): void
    {
        echo '<label class="checkbox-label azs-wide"><input type="checkbox" name="' . self::esc($name) . '" value="1"' . ($checked ? ' checked' : '') . '> ' . self::esc($label) . '</label>';
    }

    private static function replace_checkbox(string $name, string $label, bool $checked): void
    {
        echo '<input type="hidden" name="' . self::esc($name) . '" value="0">';
        self::checkbox($name, $label, $checked);
    }

    private static function replace_color(string $name, string $label, string $value, string $fallback): void
    {
        $value = CMS_M365Azure_Repository::color($value, $fallback);
        echo '<input type="hidden" name="' . self::esc($name . '_fallback') . '" value="' . self::esc($fallback) . '"><div class="form-group"><label class="form-label" for="' . self::esc($name) . '">' . self::esc($label) . '</label><div class="azs-color-row"><input class="form-control" type="color" id="' . self::esc($name) . '" name="' . self::esc($name) . '" value="' . self::esc($value) . '"><input class="form-control azs-mono" type="text" name="' . self::esc($name) . '_text" value="' . self::esc($value) . '" readonly></div></div>';
    }

    private static function status(bool $active): string
    {
        return $active ? '<span class="status-badge active">✅ Aktiv</span>' : '<span class="status-badge inactive">⏸️ Inaktiv</span>';
    }

    private static function csrf(): string
    {
        return class_exists('CMS\\Security') ? \CMS\Security::instance()->generateToken('m365azure_admin') : '';
    }

    private static function check_access(): void
    {
        if (!self::has_manage_permissions()) {
            header('Location: ' . (defined('SITE_URL') ? SITE_URL : '/'));
            exit;
        }
    }

    private static function assert_manage_permissions(): void
    {
        if (!self::has_manage_permissions()) {
            throw new \RuntimeException('Keine Berechtigung für diese Aktion.');
        }
    }

    private static function has_manage_permissions(): bool
    {
        if (function_exists('current_user_can') && current_user_can('manage_options')) {
            return true;
        }

        if (!class_exists('CMS\\Auth')) {
            return false;
        }

        try {
            return (bool) \CMS\Auth::instance()->isAdmin();
        } catch (\Throwable $e) {
            self::log_error('auth check failed :: ' . $e->getMessage());
            return false;
        }
    }

    private static function start_admin_layout(string $title): void
    {
        if (function_exists('cms_plugin_admin_layout_start')) {
            cms_plugin_admin_layout_start($title, 'm365azure');
            return;
        }

        $menuFile = ABSPATH . 'admin/partials/admin-menu.php';
        $resolved = realpath($menuFile);
        $basePath = realpath((string) ABSPATH);
        if (
            $resolved !== false
            && $basePath !== false
            && str_starts_with(str_replace('\\', '/', $resolved), rtrim(str_replace('\\', '/', $basePath), '/') . '/')
            && is_file($resolved)
            && !function_exists('renderAdminLayoutStart')
        ) {
            require_once $resolved;
        }

        if (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart($title, 'm365azure');
        }
    }

    private static function end_admin_layout(): void
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
        $css = CMS_M365AZURE_PLUGIN_DIR . 'assets/css/m365azure-admin.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="' . self::esc(CMS_M365AZURE_PLUGIN_URL . 'assets/css/m365azure-admin.css') . '?v=' . filemtime($css) . '">' . "\n";
        }
    }

    private static function enqueue_admin_scripts(): void
    {
        $scripts = [];
        $js = CMS_M365AZURE_PLUGIN_DIR . 'assets/js/m365azure-admin.js';
        if (file_exists($js)) {
            $scripts[] = CMS_M365AZURE_PLUGIN_URL . 'assets/js/m365azure-admin.js?v=' . filemtime($js);
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
        $url = self::ADMIN_BASE_URL . '?section=' . urlencode($section);
        foreach ($params as $key => $value) {
            $url .= '&' . urlencode((string) $key) . '=' . urlencode((string) $value);
        }
        return $url;
    }

    private static function page_title(string $section): string
    {
        return match ($section) {
            'categories' => 'Azure Kategorien',
            'services' => 'Azure Services',
            'settings' => 'M365 Azure Steuerung',
            'system' => 'M365 Azure System',
            default => 'M365 Azure',
        };
    }

    private static function public_archive_url(): string
    {
        try {
            $settings = CMS_M365Azure_Repository::instance()->settings();
            $slug = CMS_M365Azure_Repository::slug((string) ($settings['route_slug'] ?? 'azure-services'));
            return '/' . ($slug !== '' ? $slug : 'azure-services');
        } catch (\Throwable $e) {
            return '/azure-services';
        }
    }

    private static function allowed_section(string $section): string
    {
        return in_array($section, ['dashboard', 'categories', 'services', 'settings', 'system'], true) ? $section : 'dashboard';
    }

    private static function allowed_settings_pane(string $pane): string
    {
        return in_array($pane, ['content', 'hero', 'table', 'visibility', 'toc', 'cards', 'notes', 'design'], true) ? $pane : 'content';
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private static function log_error(string $message): void
    {
        error_log('[cms-m365azure] admin :: ' . $message);
    }
}
