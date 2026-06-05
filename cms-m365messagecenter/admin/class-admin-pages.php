<?php
/**
 * CMS M365 Message Center – Admin Pages.
 *
 * @package CMS_M365MessageCenter
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

final class CMS_M365MessageCenter_Admin_Pages
{
    private const ACTION = 'm365messagecenter_admin';

    public static function render_dashboard(): void
    {
        self::check_access();
        $repo = self::repo();
        self::handle_post($repo);
        $settings = $repo->settings();
        $messages = $repo->public_messages(['per_page' => 6, 'sort' => 'last_modified', 'direction' => 'desc']);
        self::render_page_start('Übersicht');
        self::render_header('📋 Übersicht', 'Lokaler Cache und sicherer Graph-Abruf für das eigenständige M365 Message Center.');
        self::flash();

        echo '<div class="admin-card"><h3>🔔 Cache-Status</h3><div class="dashboard-grid">';
        self::stat('Meldungen', (string) (int) $messages['total']);
        self::stat('Letzter Abruf', self::format_admin_datetime((string) ($settings['last_fetch_at'] ?? '')));
        self::stat('Cron-Abruf', self::format_admin_datetime((string) ($settings['last_cron_fetch_at'] ?? '')));
        self::stat('Abrufmenge', (string) (int) ($settings['last_fetch_count'] ?? 0));
        self::stat('Public-Route', '/' . self::esc(CMS_M365MessageCenter_Repository::slug((string) ($settings['route_slug'] ?? 'm365-messagecenter'))));
        echo '</div></div>';

        echo '<div class="admin-card"><h3>⚡ Aktionen</h3><p class="form-text">Publicseiten lesen ausschließlich aus dem lokalen Cache. Der Graph-Abruf läuft hier im Adminbereich und nutzt Least-Privilege <code>ServiceMessage.Read.All</code>.</p><div class="m365mc-actions">';
        echo '<form method="POST" class="m365mc-inline-form"><input type="hidden" name="action" value="refresh_messages"><input type="hidden" name="csrf_token" value="' . self::esc(self::csrf()) . '"><button type="submit" class="btn btn-primary">🔄 Meldungen abrufen</button></form>';
        echo '<a class="btn btn-secondary" href="' . self::esc(self::admin_url('settings')) . '">⚙️ Einstellungen</a>';
        echo '<a class="btn btn-secondary" href="/' . self::esc(CMS_M365MessageCenter_Repository::slug((string) ($settings['route_slug'] ?? 'm365-messagecenter'))) . '" target="_blank" rel="noopener noreferrer">👁️ Public ansehen</a>';
        echo '</div></div>';

        echo '<div class="admin-card"><h3>🧾 Letzte Meldungen</h3><div class="users-table-container"><table class="users-table"><thead><tr><th>ID</th><th>Titel</th><th>Kategorie</th><th>Services</th><th>Geändert</th></tr></thead><tbody>';
        if ($messages['items'] === []) {
            echo '<tr><td colspan="5"><div class="empty-state"><p><strong>Noch keine Meldungen im Cache</strong></p><p>Trage Graph-Zugangsdaten ein und starte den Abruf.</p></div></td></tr>';
        }
        foreach ($messages['items'] as $item) {
            echo '<tr><td><code>' . self::esc((string) ($item['graph_id'] ?? '')) . '</code></td><td><strong>' . self::esc((string) ($item['title'] ?? '')) . '</strong></td><td>' . self::esc((string) ($item['category'] ?? '—')) . '</td><td>' . self::esc(implode(', ', (array) ($item['services'] ?? []))) . '</td><td>' . self::esc(self::format_admin_datetime((string) ($item['last_modified_at'] ?? ''))) . '</td></tr>';
        }
        echo '</tbody></table></div></div>';
        self::render_page_end();
    }

    public static function render_settings(): void
    {
        self::check_access();
        $repo = self::repo();
        self::handle_post($repo);
        $s = $repo->settings();
        self::render_page_start('Einstellungen');
        self::render_header('⚙️ Einstellungen', 'Graph-Zugangsdaten, Public-Route, Cache- und Sortierverhalten konfigurieren.');
        self::flash();

        echo '<div class="admin-card"><form method="POST" class="admin-form"><input type="hidden" name="action" value="save_settings"><input type="hidden" name="csrf_token" value="' . self::esc(self::csrf()) . '">';
        echo '<h3>🌐 Publicsite</h3>';
        self::input('route_slug', 'Öffentlicher Slug', self::setting($s, 'route_slug', 'm365-messagecenter'));
        self::input('page_overline', 'Overline', self::setting($s, 'page_overline', 'Microsoft 365'));
        self::input('page_title', 'Seitentitel', self::setting($s, 'page_title', 'M365 Message Center'));
        self::textarea('page_intro', 'Einleitung', self::setting($s, 'page_intro', ''), 3);
        self::textarea('empty_text', 'Leerzustand', self::setting($s, 'empty_text', ''), 2);
        self::select('default_sort', 'Standard-Sortierung', self::setting($s, 'default_sort', 'last_modified'), self::sort_options());
        self::select('default_direction', 'Standard-Richtung', self::setting($s, 'default_direction', 'desc'), ['desc' => 'Absteigend', 'asc' => 'Aufsteigend']);
        self::select('public_layout', 'Public-Layout', self::setting($s, 'public_layout', 'standard'), self::layout_options());
        self::number('public_max_width', 'Max. Content-Breite der Publicsite (px)', (int) self::setting($s, 'public_max_width', '1160'), 900, 1600);
        self::number('items_per_page', 'Einträge pro Seite', (int) self::setting($s, 'items_per_page', '24'), 6, 60);
        self::checkbox('show_detail_pages', 'Detailseiten aktivieren', self::setting($s, 'show_detail_pages', '1') === '1');
        self::checkbox('show_status_panel', 'Info-Kachelbereich mit Cache, Aktualisierung und Sprache anzeigen', self::setting($s, 'show_status_panel', '1') === '1');
        self::checkbox('show_filters', 'Such-, Filter- und Sortierbereich anzeigen', self::setting($s, 'show_filters', '1') === '1');
        self::checkbox('show_body_excerpt', 'Auszug auf Publicsite anzeigen', self::setting($s, 'show_body_excerpt', '1') === '1');
        self::checkbox('show_external_links', 'Externe Microsoft-Links anzeigen', self::setting($s, 'show_external_links', '1') === '1');

        echo '<hr class="m365mc-separator"><h3>🔐 Microsoft Graph</h3>';
        echo '<div class="alert alert-info">Benötigt eine Microsoft-Entra-App mit Application Permission <strong>ServiceMessage.Read.All</strong> und Admin Consent. Publicseiten nutzen nur den lokalen Cache.</div>';
        self::select('graph_cloud', 'Microsoft Cloud', self::setting($s, 'graph_cloud', 'global'), ['global' => 'Global', 'gcc_high' => 'US Government GCC High', 'dod' => 'US Government DoD', 'china' => 'China 21Vianet']);
        self::select('graph_language', 'Graph-Sprache', self::setting($s, 'graph_language', 'de-DE'), ['de-DE' => 'Deutsch (Deutschland)', 'en-US' => 'Englisch (USA)']);
        self::input('graph_tenant_id', 'Tenant-ID / Tenant-Domain', self::setting($s, 'graph_tenant_id', ''));
        self::input('graph_client_id', 'Client-ID', self::setting($s, 'graph_client_id', ''));
        self::password('graph_client_secret', 'Client-Secret', self::setting($s, 'graph_client_secret', '') !== '');
        self::number('fetch_limit', 'Max. Abrufmenge', (int) self::setting($s, 'fetch_limit', '100'), 1, 200);
        self::number('cache_ttl_minutes', 'Cache-Hinweis in Minuten', (int) self::setting($s, 'cache_ttl_minutes', '120'), 15, 1440);
        self::checkbox('cron_enabled', 'Automatischen Abruf per cron.php aktivieren (täglich ab 12:00 Uhr)', self::setting($s, 'cron_enabled', '1') === '1');
        self::input('service_filter', 'Optionaler Service-Filter beim Speichern', self::setting($s, 'service_filter', ''), 'z. B. Teams, SharePoint');
        self::input('category_filter', 'Optionaler Kategorie-Filter beim Speichern', self::setting($s, 'category_filter', ''), 'z. B. PlanForChange');

        echo '<div class="m365mc-form-actions"><button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button></div></form></div>';
        self::render_page_end();
    }

    private static function handle_post(CMS_M365MessageCenter_Repository $repo): void
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            return;
        }

        if (!Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), self::ACTION)) {
            self::redirect(self::current_admin_url(['error' => 'csrf']));
        }

        $action = (string) ($_POST['action'] ?? '');
        try {
            if ($action === 'save_settings') {
                $settings = self::posted_settings($repo->settings());
                $repo->save_settings($settings);
                self::redirect(self::admin_url('settings', ['saved' => 1]));
            }

            if ($action === 'refresh_messages') {
                $result = CMS_M365MessageCenter_Refresh_Service::refresh(['source' => 'admin']);
                if (empty($result['success'])) {
                    self::redirect(self::admin_url('dashboard', ['error' => (string) ($result['error'] ?? 'refresh-failed')])) ;
                }

                self::redirect(self::admin_url('dashboard', ['refreshed' => (int) ($result['count'] ?? 0)]));
            }
        } catch (\Throwable $e) {
            self::log_exception('post_failed', $e);
            self::redirect(self::current_admin_url(['error' => 'save']));
        }
    }

    /** @param array<string,string> $existing @return array<string,string> */
    private static function posted_settings(array $existing): array
    {
        $settings = [];
        foreach (['route_slug', 'page_overline', 'page_title', 'page_intro', 'empty_text', 'graph_cloud', 'graph_language', 'graph_tenant_id', 'graph_client_id', 'service_filter', 'category_filter', 'default_sort', 'default_direction'] as $key) {
            $settings[$key] = CMS_M365MessageCenter_Repository::text((string) ($_POST[$key] ?? ''), 900);
        }
        $secret = trim((string) ($_POST['graph_client_secret'] ?? ''));
        $settings['graph_client_secret'] = $secret !== '' ? $secret : (string) ($existing['graph_client_secret'] ?? '');
        $settings['route_slug'] = CMS_M365MessageCenter_Repository::slug($settings['route_slug']);
        $settings['default_sort'] = CMS_M365MessageCenter_Repository::public_sort($settings['default_sort']);
        $settings['default_direction'] = $settings['default_direction'] === 'asc' ? 'asc' : 'desc';
        $settings['public_layout'] = in_array((string) ($_POST['public_layout'] ?? ''), ['standard', 'compact', 'list'], true) ? (string) $_POST['public_layout'] : 'standard';
        $settings['public_max_width'] = (string) max(900, min(1600, (int) ($_POST['public_max_width'] ?? 1160)));
        $settings['items_per_page'] = (string) max(6, min(60, (int) ($_POST['items_per_page'] ?? 24)));
        $settings['fetch_limit'] = (string) max(1, min(200, (int) ($_POST['fetch_limit'] ?? 100)));
        $settings['cache_ttl_minutes'] = (string) max(15, min(1440, (int) ($_POST['cache_ttl_minutes'] ?? 120)));
        $settings['cron_enabled'] = !empty($_POST['cron_enabled']) ? '1' : '0';
        $settings['cron_hour'] = '12';
        $settings['show_detail_pages'] = !empty($_POST['show_detail_pages']) ? '1' : '0';
        $settings['show_status_panel'] = !empty($_POST['show_status_panel']) ? '1' : '0';
        $settings['show_filters'] = !empty($_POST['show_filters']) ? '1' : '0';
        $settings['show_body_excerpt'] = !empty($_POST['show_body_excerpt']) ? '1' : '0';
        $settings['show_external_links'] = !empty($_POST['show_external_links']) ? '1' : '0';

        return $settings;
    }

    private static function check_access(): void
    {
        if (!Auth::instance()->isAdmin()) {
            header('Location: ' . SITE_URL);
            exit;
        }
    }

    private static function repo(): CMS_M365MessageCenter_Repository
    {
        CMS_M365MessageCenter_Installer::maybe_install();
        return CMS_M365MessageCenter_Repository::instance();
    }

    private static function csrf(): string
    {
        return Security::instance()->generateToken(self::ACTION);
    }

    private static function render_page_start(string $section): void
    {
        echo '<div class="admin-content m365mc-admin m365mc-admin--' . self::esc(strtolower($section)) . '">';
    }

    private static function render_page_end(): void
    {
        echo '</div>';
    }

    private static function render_header(string $title, string $intro): void
    {
        echo '<div class="admin-page-header"><div><h2>' . self::esc($title) . '</h2><p>' . self::esc($intro) . '</p></div></div>';
    }

    private static function flash(): void
    {
        if (isset($_GET['saved'])) {
            echo '<div class="alert alert-success">✅ Einstellungen gespeichert.</div>';
        }
        if (isset($_GET['refreshed'])) {
            echo '<div class="alert alert-success">✅ ' . (int) $_GET['refreshed'] . ' Meldungen sicher abgerufen und lokal zwischengespeichert.</div>';
        }
        if (isset($_GET['error'])) {
            echo '<div class="alert alert-error">❌ Fehler: ' . self::esc((string) $_GET['error']) . '</div>';
        }
    }

    private static function stat(string $label, string $value): void
    {
        echo '<div class="stat-card"><div class="stat-number">' . self::esc($value !== '' ? $value : '—') . '</div><div class="stat-label">' . self::esc($label) . '</div></div>';
    }

    /** @param array<string,string> $settings */
    private static function setting(array $settings, string $key, string $default): string
    {
        $value = trim((string) ($settings[$key] ?? ''));
        return $value !== '' ? $value : $default;
    }

    private static function input(string $name, string $label, string $value, string $placeholder = ''): void
    {
        echo '<div class="form-group"><label class="form-label" for="' . self::esc($name) . '">' . self::esc($label) . '</label><input type="text" class="form-control" id="' . self::esc($name) . '" name="' . self::esc($name) . '" value="' . self::esc($value) . '" placeholder="' . self::esc($placeholder) . '"></div>';
    }

    private static function password(string $name, string $label, bool $hasSecret): void
    {
        $placeholder = $hasSecret ? 'Gespeichertes Secret beibehalten – nur zum Ändern neu eintragen' : '';
        echo '<div class="form-group"><label class="form-label" for="' . self::esc($name) . '">' . self::esc($label) . '</label><input type="password" class="form-control" id="' . self::esc($name) . '" name="' . self::esc($name) . '" value="" autocomplete="new-password" placeholder="' . self::esc($placeholder) . '"></div>';
    }

    private static function textarea(string $name, string $label, string $value, int $rows): void
    {
        echo '<div class="form-group"><label class="form-label" for="' . self::esc($name) . '">' . self::esc($label) . '</label><textarea class="form-control" id="' . self::esc($name) . '" name="' . self::esc($name) . '" rows="' . max(2, $rows) . '">' . self::esc($value) . '</textarea></div>';
    }

    /** @param array<string,string> $options */
    private static function select(string $name, string $label, string $value, array $options): void
    {
        echo '<div class="form-group"><label class="form-label" for="' . self::esc($name) . '">' . self::esc($label) . '</label><select class="form-control" id="' . self::esc($name) . '" name="' . self::esc($name) . '">';
        foreach ($options as $optionValue => $optionLabel) {
            $selected = (string) $optionValue === $value ? ' selected' : '';
            echo '<option value="' . self::esc((string) $optionValue) . '"' . $selected . '>' . self::esc((string) $optionLabel) . '</option>';
        }
        echo '</select></div>';
    }

    private static function number(string $name, string $label, int $value, int $min, int $max): void
    {
        $value = max($min, min($max, $value));
        echo '<div class="form-group"><label class="form-label" for="' . self::esc($name) . '">' . self::esc($label) . '</label><input type="number" class="form-control" id="' . self::esc($name) . '" name="' . self::esc($name) . '" value="' . $value . '" min="' . $min . '" max="' . $max . '"></div>';
    }

    private static function checkbox(string $name, string $label, bool $checked): void
    {
        echo '<label class="checkbox-label"><input type="checkbox" name="' . self::esc($name) . '" value="1"' . ($checked ? ' checked' : '') . '> ' . self::esc($label) . '</label>';
    }

    /** @return array<string,string> */
    private static function sort_options(): array
    {
        return [
            'last_modified' => 'Zuletzt geändert',
            'action_required' => 'Handlungsdatum',
            'category' => 'Kategorie',
            'service' => 'Service',
            'title' => 'Titel',
        ];
    }

    /** @return array<string,string> */
    private static function layout_options(): array
    {
        return [
            'standard' => 'Standard – breite Meldungskarten',
            'compact' => 'Kompakt – Kartenraster',
            'list' => 'Liste – dichter Informationsfluss',
        ];
    }

    /** @param array<string,string|int> $params */
    private static function admin_url(string $section, array $params = []): string
    {
        $slug = $section === 'settings' ? 'm365messagecenter-settings' : 'm365messagecenter';
        $url = '/admin/plugins/m365messagecenter/' . rawurlencode($slug);
        if ($params !== []) {
            $url .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        return $url;
    }

    /** @param array<string,string|int> $params */
    private static function current_admin_url(array $params = []): string
    {
        $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? self::admin_url('dashboard')), PHP_URL_PATH);
        $url = $path !== '' ? $path : self::admin_url('dashboard');
        if ($params !== []) {
            $url .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        return $url;
    }

    private static function redirect(string $url): void
    {
        if (class_exists('CMS\\Router')) {
            \CMS\Router::instance()->redirect($url);
        }
        header('Location: ' . $url);
        exit;
    }

    private static function format_admin_datetime(string $value): string
    {
        $timestamp = strtotime($value);
        return $timestamp !== false ? date('d.m.Y H:i', $timestamp) : '—';
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private static function log_exception(string $context, \Throwable $e): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CMS M365 Message Center admin [' . $context . ']: ' . $e->getMessage());
        }
    }
}
