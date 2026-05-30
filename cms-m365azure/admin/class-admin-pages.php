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
        ob_start();
        self::check_access();
        CMS_M365Azure_Installer::maybe_install();

        $repo = CMS_M365Azure_Repository::instance();
        $section = self::allowed_section((string) ($_GET['section'] ?? 'dashboard'));
        $notice = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                self::handle_post($repo);
                $notice = 'Änderungen gespeichert.';
            } catch (\Throwable $e) {
                $error = 'Aktion konnte nicht ausgeführt werden: ' . $e->getMessage();
            }
        }

        self::load_admin_menu();
        if (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart(self::page_title($section), 'm365azure');
        }
        self::enqueue_admin_assets();

        self::render_header($section, $notice, $error);
        self::render_nav($section);

        match ($section) {
            'categories' => self::render_categories($repo),
            'services' => self::render_services($repo),
            'settings' => self::render_settings($repo),
            'system' => self::render_system($repo),
            default => self::render_dashboard($repo),
        };

        self::render_delete_modal();
        self::enqueue_admin_scripts();
        if (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
        }
        ob_end_flush();
    }

    private static function handle_post(CMS_M365Azure_Repository $repo): void
    {
        if (!class_exists('CMS\\Security') || !\CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'm365azure_admin')) {
            throw new \RuntimeException('Sicherheitscheck fehlgeschlagen.');
        }

        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'save_category') {
            $repo->save_category($_POST);
            return;
        }

        if ($action === 'delete_category') {
            $repo->delete_category(max(0, (int) ($_POST['id'] ?? 0)));
            return;
        }

        if ($action === 'save_service') {
            $repo->save_service($_POST);
            return;
        }

        if ($action === 'delete_service') {
            $repo->delete_service(max(0, (int) ($_POST['id'] ?? 0)));
            return;
        }

        if ($action === 'save_settings') {
            $repo->save_settings(self::collect_settings());
            return;
        }

        throw new \RuntimeException('Unbekannte Aktion.');
    }

    /** @return array<string,string> */
    private static function collect_settings(): array
    {
        $keys = [
            'route_slug', 'page_title', 'page_overline', 'page_intro', 'seo_title', 'seo_description',
            'toc_title', 'card_image_position', 'layout_max_width', 'card_image_width',
            'design_primary_color', 'design_accent_color', 'design_background_color', 'design_surface_color', 'design_border_radius',
        ];
        $settings = [];
        foreach ($keys as $key) {
            $value = (string) ($_POST[$key] ?? '');
            if (str_ends_with($key, '_color')) {
                $settings[$key] = CMS_M365Azure_Repository::color($value, (string) ($_POST[$key . '_fallback'] ?? '#ffffff'));
                continue;
            }
            if ($key === 'route_slug') {
                $settings[$key] = CMS_M365Azure_Repository::slug($value);
                continue;
            }
            if (in_array($key, ['layout_max_width', 'card_image_width', 'design_border_radius'], true)) {
                $settings[$key] = (string) max(0, (int) $value);
                continue;
            }
            if ($key === 'card_image_position') {
                $settings[$key] = in_array($value, ['left', 'right'], true) ? $value : 'left';
                continue;
            }
            $settings[$key] = CMS_M365Azure_Repository::long_text($value);
        }

        foreach (['show_hero', 'show_toc', 'show_category_intro', 'show_service_images', 'show_service_links', 'show_feature_lists', 'show_use_cases'] as $boolKey) {
            $settings[$boolKey] = !empty($_POST[$boolKey]) ? '1' : '0';
        }

        return $settings;
    }

    private static function render_header(string $section, string $notice, string $error): void
    {
        echo '<div class="admin-page-header"><div><h2>☁️ ' . self::esc(self::page_title($section)) . '</h2><p>Azure-Service-Kategorien, Service-Cards, Texte, Bilder und Design zentral steuern.</p></div>';
        echo '<div class="header-actions"><a class="btn btn-secondary" href="/azure-services" target="_blank" rel="noopener noreferrer">👁️ Public ansehen</a></div></div>';
        if ($notice !== '') {
            echo '<div class="alert alert-success">✅ ' . self::esc($notice) . '</div>';
        }
        if ($error !== '') {
            echo '<div class="alert alert-error">❌ ' . self::esc($error) . '</div>';
        }
    }

    private static function render_nav(string $section): void
    {
        $tabs = [
            'dashboard' => '📊 Dashboard',
            'categories' => '🗂️ Kategorien',
            'services' => '☁️ Services',
            'settings' => '⚙️ Steuerung & Design',
            'system' => '🖥️ System',
        ];
        echo '<div class="azs-tabs">';
        foreach ($tabs as $key => $label) {
            $active = $section === $key ? ' active' : '';
            echo '<a class="azs-tab' . $active . '" href="' . self::esc(self::admin_url($key)) . '">' . self::esc($label) . '</a>';
        }
        echo '</div>';
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
        echo '<hr class="azs-separator"><div class="users-table-container"><table class="users-table"><thead><tr><th>Titel</th><th>Slug</th><th>Status</th><th>Sortierung</th><th>Aktionen</th></tr></thead><tbody>';
        foreach ($repo->categories(false) as $cat) {
            $name = (string) $cat['title'];
            echo '<tr><td><strong>' . self::esc($name) . '</strong></td><td><code>' . self::esc((string) $cat['slug']) . '</code></td><td>' . self::status((int) $cat['is_active'] === 1) . '</td><td>' . (int) $cat['sort_order'] . '</td><td><div class="azs-actions"><a class="btn btn-sm btn-secondary" href="' . self::esc(self::admin_url('categories', ['edit' => (int) $cat['id']])) . '">✏️</a><button type="button" class="btn btn-sm btn-danger" data-delete-entity="category" data-delete-id="' . (int) $cat['id'] . '" data-delete-name="' . self::esc($name) . '">🗑️</button></div></td></tr>';
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

    private static function render_settings(CMS_M365Azure_Repository $repo): void
    {
        $s = $repo->settings();
        $tab = in_array((string) ($_GET['tab'] ?? 'content'), ['content', 'toc', 'cards', 'design'], true) ? (string) ($_GET['tab'] ?? 'content') : 'content';
        $tabs = ['content' => '📝 Inhalte', 'toc' => '🧭 Inhaltsverzeichnis', 'cards' => '🃏 Cards', 'design' => '🎨 Design'];
        echo '<div class="azs-subtabs">';
        foreach ($tabs as $key => $label) {
            $active = $tab === $key ? ' active' : '';
            echo '<a class="azs-subtab' . $active . '" href="' . self::esc(self::admin_url('settings', ['tab' => $key])) . '">' . self::esc($label) . '</a>';
        }
        echo '</div><div class="admin-card azs-card-connected"><form method="POST" class="admin-form"><input type="hidden" name="action" value="save_settings"><input type="hidden" name="csrf_token" value="' . self::esc(self::csrf()) . '">';
        foreach (['show_hero', 'show_toc', 'show_category_intro', 'show_service_images', 'show_service_links', 'show_feature_lists', 'show_use_cases'] as $boolKey) {
            echo '<input type="hidden" name="' . self::esc($boolKey) . '" value="' . self::esc((string) ($s[$boolKey] ?? '0')) . '">';
        }
        foreach (['route_slug', 'page_title', 'page_overline', 'page_intro', 'seo_title', 'seo_description', 'toc_title', 'card_image_position', 'layout_max_width', 'card_image_width', 'design_primary_color', 'design_accent_color', 'design_background_color', 'design_surface_color', 'design_border_radius'] as $hiddenKey) {
            echo '<input type="hidden" name="' . self::esc($hiddenKey) . '" value="' . self::esc((string) ($s[$hiddenKey] ?? '')) . '">';
        }
        if ($tab === 'content') {
            echo '<h3>📝 Seiteninhalte</h3>';
            self::replace_input('route_slug', 'Öffentlicher Slug', (string) ($s['route_slug'] ?? 'azure-services'));
            self::replace_input('page_overline', 'Overline', (string) ($s['page_overline'] ?? 'Azure Überblick'));
            self::replace_input('page_title', 'Seitentitel', (string) ($s['page_title'] ?? 'Microsoft Azure Services'));
            self::replace_textarea('page_intro', 'Einleitung', (string) ($s['page_intro'] ?? ''), 4);
            self::replace_input('seo_title', 'SEO-Titel', (string) ($s['seo_title'] ?? ''));
            self::replace_textarea('seo_description', 'SEO-Beschreibung', (string) ($s['seo_description'] ?? ''), 3);
            self::replace_checkbox('show_hero', 'Headerbereich anzeigen', (string) ($s['show_hero'] ?? '1') === '1');
        } elseif ($tab === 'toc') {
            echo '<h3>🧭 Inhaltsverzeichnis</h3>';
            self::replace_checkbox('show_toc', 'Inhaltsverzeichnis anzeigen', (string) ($s['show_toc'] ?? '1') === '1');
            self::replace_input('toc_title', 'Überschrift', (string) ($s['toc_title'] ?? 'Inhaltsverzeichnis'));
            self::replace_checkbox('show_category_intro', 'Kategorie-Beschreibungen anzeigen', (string) ($s['show_category_intro'] ?? '1') === '1');
        } elseif ($tab === 'cards') {
            echo '<h3>🃏 Card-Layout</h3>';
            self::replace_checkbox('show_service_images', 'Service-Bilder anzeigen', (string) ($s['show_service_images'] ?? '1') === '1');
            self::replace_checkbox('show_service_links', 'Dokumentations-/Preislinks anzeigen', (string) ($s['show_service_links'] ?? '1') === '1');
            self::replace_checkbox('show_feature_lists', 'Feature-Listen anzeigen', (string) ($s['show_feature_lists'] ?? '1') === '1');
            self::replace_checkbox('show_use_cases', 'Einsatzbereiche anzeigen', (string) ($s['show_use_cases'] ?? '1') === '1');
            echo '<div class="form-group"><label class="form-label" for="card_image_position">Bildposition</label><select class="form-control" id="card_image_position" name="card_image_position"><option value="left"' . (((string) ($s['card_image_position'] ?? 'left')) === 'left' ? ' selected' : '') . '>Links</option><option value="right"' . (((string) ($s['card_image_position'] ?? 'left')) === 'right' ? ' selected' : '') . '>Rechts</option></select></div>';
            self::replace_number('card_image_width', 'Bildbreite in px', (int) ($s['card_image_width'] ?? 320), 180, 520);
        } else {
            echo '<h3>🎨 Design</h3>';
            self::replace_number('layout_max_width', 'Maximale Inhaltsbreite in px', (int) ($s['layout_max_width'] ?? 1180), 720, 1600);
            self::replace_color('design_primary_color', 'Primärfarbe', (string) ($s['design_primary_color'] ?? '#2563eb'), '#2563eb');
            self::replace_color('design_accent_color', 'Akzentfarbe', (string) ($s['design_accent_color'] ?? '#f59e0b'), '#f59e0b');
            self::replace_color('design_background_color', 'Seitenhintergrund', (string) ($s['design_background_color'] ?? '#ffffff'), '#ffffff');
            self::replace_color('design_surface_color', 'Card-Hintergrund', (string) ($s['design_surface_color'] ?? '#ffffff'), '#ffffff');
            self::replace_number('design_border_radius', 'Card-Radius in px', (int) ($s['design_border_radius'] ?? 10), 0, 24);
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
        if (!class_exists('CMS\\Auth') || !\CMS\Auth::instance()->isAdmin()) {
            header('Location: ' . (defined('SITE_URL') ? SITE_URL : '/'));
            exit;
        }
    }

    private static function load_admin_menu(): void
    {
        $menuFile = ABSPATH . 'admin/partials/admin-menu.php';
        if (file_exists($menuFile) && !function_exists('renderAdminLayoutStart')) {
            require_once $menuFile;
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
        $js = CMS_M365AZURE_PLUGIN_DIR . 'assets/js/m365azure-admin.js';
        if (file_exists($js)) {
            echo '<script src="' . self::esc(CMS_M365AZURE_PLUGIN_URL . 'assets/js/m365azure-admin.js') . '?v=' . filemtime($js) . '" defer></script>' . "\n";
        }
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

    private static function allowed_section(string $section): string
    {
        return in_array($section, ['dashboard', 'categories', 'services', 'settings', 'system'], true) ? $section : 'dashboard';
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
