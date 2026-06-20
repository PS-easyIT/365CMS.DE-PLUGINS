<?php
/**
 * CMS Beratung – Admin pages and actions.
 *
 * @package CMS_Beratung
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Beratung_Admin_Pages
{
    public const MENU_SLUG = 'cms-beratung';
    public const DEFAULT_PAGE_SLUG = 'cms-beratung-landingpages';

    private const PAGE_TITLES = [
        'cms-beratung-landingpages' => 'CMS Beratung Landingpages',
        'cms-beratung-new' => 'Neue Landingpage erstellen',
        'cms-beratung-settings' => 'Globale Einstellungen',
        'cms-beratung-presets' => 'Design Presets',
        'cms-beratung-submissions' => 'Formular Anfragen',
        'cms-beratung-import-export' => 'Import und Export',
        'cms-beratung-help' => 'Hilfe und Dokumentation',
    ];

    private const PAGE_RENDERERS = [
        'cms-beratung-landingpages' => 'render_landingpages',
        'cms-beratung-new' => 'render_new_page',
        'cms-beratung-settings' => 'render_settings',
        'cms-beratung-presets' => 'render_presets',
        'cms-beratung-submissions' => 'render_submissions',
        'cms-beratung-import-export' => 'render_import_export',
        'cms-beratung-help' => 'render_help',
    ];

    /** @return array<int,array{slug:string,title:string,menu_title:string}> */
    public static function get_menu_pages(): array
    {
        return [
            ['slug' => 'cms-beratung-landingpages', 'title' => 'Landingpages', 'menu_title' => 'Landingpages'],
            ['slug' => 'cms-beratung-new', 'title' => 'Neue Landingpage erstellen', 'menu_title' => 'Neue Landingpage erstellen'],
            ['slug' => 'cms-beratung-settings', 'title' => 'Globale Einstellungen', 'menu_title' => 'Globale Einstellungen'],
            ['slug' => 'cms-beratung-presets', 'title' => 'Design Presets', 'menu_title' => 'Design Presets'],
            ['slug' => 'cms-beratung-submissions', 'title' => 'Formular Anfragen', 'menu_title' => 'Formular Anfragen'],
            ['slug' => 'cms-beratung-import-export', 'title' => 'Import und Export', 'menu_title' => 'Import und Export'],
            ['slug' => 'cms-beratung-help', 'title' => 'Hilfe und Dokumentation', 'menu_title' => 'Hilfe und Dokumentation'],
        ];
    }

    /** @return array{0:class-string,1:string} */
    public static function dispatch_callback_for_slug(string $slug): array
    {
        $slug = self::normalize_slug($slug);
        $method = 'dispatch_' . str_replace('-', '_', $slug);
        return is_callable([self::class, $method]) ? [self::class, $method] : [self::class, 'dispatch_cms_beratung_landingpages'];
    }

    public static function __callStatic(string $name, array $arguments): void
    {
        unset($arguments);
        if (str_starts_with($name, 'dispatch_')) {
            $_GET['page'] = str_replace('_', '-', substr($name, 9));
            self::render_dispatch();
        }
    }

    public static function dispatch_cms_beratung_landingpages(): void { $_GET['page'] = 'cms-beratung-landingpages'; self::render_dispatch(); }
    public static function dispatch_cms_beratung_new(): void { $_GET['page'] = 'cms-beratung-new'; self::render_dispatch(); }
    public static function dispatch_cms_beratung_settings(): void { $_GET['page'] = 'cms-beratung-settings'; self::render_dispatch(); }
    public static function dispatch_cms_beratung_presets(): void { $_GET['page'] = 'cms-beratung-presets'; self::render_dispatch(); }
    public static function dispatch_cms_beratung_submissions(): void { $_GET['page'] = 'cms-beratung-submissions'; self::render_dispatch(); }
    public static function dispatch_cms_beratung_import_export(): void { $_GET['page'] = 'cms-beratung-import-export'; self::render_dispatch(); }
    public static function dispatch_cms_beratung_help(): void { $_GET['page'] = 'cms-beratung-help'; self::render_dispatch(); }

    public static function render_dispatch(): void
    {
        self::ensure_shared_contract_loaded();
        if (function_exists('cms_plugin_admin_sync_page_from_request')) {
            cms_plugin_admin_sync_page_from_request(self::MENU_SLUG);
        }

        $callbacks = self::resolve_callbacks();
        if (function_exists('cms_plugin_admin_dispatch_page')) {
            cms_plugin_admin_dispatch_page($callbacks, self::DEFAULT_PAGE_SLUG, self::MENU_SLUG);
            return;
        }

        $slug = self::normalize_slug((string) ($_GET['page'] ?? self::DEFAULT_PAGE_SLUG));
        $callback = $callbacks[$slug] ?? $callbacks[self::DEFAULT_PAGE_SLUG];
        call_user_func($callback);
    }

    public static function register_admin_routes(mixed $router): void
    {
        self::ensure_shared_contract_loaded();
        if (function_exists('cms_plugin_admin_register_routes')) {
            cms_plugin_admin_register_routes($router, self::MENU_SLUG, self::resolve_callbacks());
        }
    }

    public static function enqueue_admin_assets_for_request(): void
    {
        $uri = strtolower((string) ($_SERVER['REQUEST_URI'] ?? ''));
        $page = strtolower((string) ($_GET['page'] ?? ''));
        if (!str_contains($uri, 'cms-beratung') && !str_contains($page, 'cms-beratung')) {
            return;
        }
        self::enqueue_admin_assets();
    }

    public static function render_landingpages(): void
    {
        self::render_with_layout('cms-beratung-landingpages', function (): void {
            self::handle_landingpage_actions();
            $pages = CMS_Beratung_Storage::instance()->all_landingpages();
            $statuses = CMS_Beratung_Settings::statuses();
            echo '<div class="beratung-admin-hero"><div><h1>CMS Beratung</h1><p>Landingpage Builder für Microsoft 365, Copilot, KI, Security, Compliance und IT Consulting.</p></div><a class="beratung-btn" href="' . self::esc(self::admin_url('cms-beratung-new')) . '">Neue Landingpage</a></div>';
            self::notice_from_query();
            echo '<div class="beratung-table-wrap"><table class="beratung-table"><thead><tr><th>Titel</th><th>Slug</th><th>Status</th><th>Template</th><th>Letzte Änderung</th><th>Erstellt von</th><th>Aktionen</th></tr></thead><tbody>';
            if ($pages === []) {
                echo '<tr><td colspan="7"><div class="beratung-empty">Noch keine Landingpages vorhanden.</div></td></tr>';
            }
            foreach ($pages as $page) {
                $id = (int) ($page['id'] ?? 0);
                $status = (string) ($page['status'] ?? 'draft');
                echo '<tr>';
                echo '<td><strong>' . self::esc((string) ($page['internal_title'] ?? '')) . '</strong><br><small>' . self::esc((string) ($page['public_title'] ?? '')) . '</small></td>';
                echo '<td><code>' . self::esc((string) ($page['slug'] ?? '')) . '</code></td>';
                echo '<td><span class="beratung-status beratung-status--' . self::esc($status) . '">' . self::esc($statuses[$status] ?? $status) . '</span></td>';
                echo '<td>' . self::esc(CMS_Beratung_Settings::templates()[(string) ($page['template'] ?? 'standard')] ?? (string) ($page['template'] ?? 'standard')) . '</td>';
                echo '<td>' . self::esc((string) ($page['updated_at'] ?? '')) . '</td>';
                echo '<td>' . self::esc((string) ($page['created_by'] ?? 'System')) . '</td>';
                echo '<td class="beratung-actions">'
                    . '<a href="' . self::esc(self::admin_url('cms-beratung-new', ['id' => $id])) . '">Bearbeiten</a>'
                    . '<a target="_blank" href="' . self::esc('/beratung/' . (string) ($page['slug'] ?? '')) . '">Vorschau</a>'
                    . self::action_form($id, 'publish', 'Veröffentlichen')
                    . self::action_form($id, 'deactivate', 'Deaktivieren')
                    . self::action_form($id, 'duplicate', 'Duplizieren')
                    . '<a href="' . self::esc(self::admin_url('cms-beratung-import-export', ['export' => $id])) . '">Exportieren</a>'
                    . self::action_form($id, 'delete', 'Löschen', true)
                    . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        });
    }

    public static function render_new_page(): void
    {
        self::render_with_layout('cms-beratung-new', function (): void {
            $storage = CMS_Beratung_Storage::instance();
            $id = max(0, (int) ($_GET['id'] ?? 0));
            $notice = '';
            $error = '';
            if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && (string) ($_POST['beratung_admin_action'] ?? '') === 'save_landingpage') {
                if (!self::verify_nonce('beratung_save_landingpage')) {
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } else {
                    try {
                        $savedId = $storage->save_landingpage($_POST);
                        self::redirect('cms-beratung-new', ['id' => $savedId, 'saved' => 1]);
                    } catch (\Throwable $e) {
                        $error = 'Landingpage konnte nicht gespeichert werden: ' . $e->getMessage();
                    }
                }
            }
            $page = $id > 0 ? $storage->get_landingpage($id) : null;
            if ($page === null) {
                $page = self::default_landingpage();
            }
            if (!empty($_GET['saved'])) {
                $notice = 'Landingpage gespeichert.';
            }
            self::render_notice($notice, $error);
            self::render_landingpage_form($page);
        });
    }

    public static function render_settings(): void
    {
        self::render_with_layout('cms-beratung-settings', function (): void {
            $notice = '';
            $error = '';
            if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && (string) ($_POST['beratung_admin_action'] ?? '') === 'save_settings') {
                if (!self::verify_nonce('beratung_settings')) {
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } else {
                    CMS_Beratung_Settings::save(CMS_Beratung_Settings::sanitize_from_post($_POST));
                    $notice = 'Globale Einstellungen gespeichert.';
                }
            }
            $settings = CMS_Beratung_Settings::all();
            self::render_notice($notice, $error);
            self::render_settings_form($settings);
        });
    }

    public static function render_presets(): void
    {
        self::render_with_layout('cms-beratung-presets', function (): void {
            $presets = CMS_Beratung_Storage::instance()->all_presets();
            echo '<div class="beratung-card"><h1>Design Presets</h1><p>System-Presets für schnelle Beratungsseiten. Eigene Presets sind im Datenmodell vorbereitet.</p><div class="beratung-preset-grid">';
            foreach ($presets as $preset) {
                $design = json_decode((string) ($preset['design_json'] ?? '{}'), true);
                $primary = is_array($design) ? (string) ($design['primary_color'] ?? '#2563eb') : '#2563eb';
                echo '<article class="beratung-preset"><span style="background:' . self::esc($primary) . '"></span><h3>' . self::esc((string) ($preset['name'] ?? 'Preset')) . '</h3><p>' . self::esc((string) ($preset['description'] ?? '')) . '</p></article>';
            }
            echo '</div></div>';
        });
    }

    public static function render_submissions(): void
    {
        self::render_with_layout('cms-beratung-submissions', function (): void {
            $submissions = CMS_Beratung_Storage::instance()->submissions();
            echo '<div class="beratung-card"><h1>Formular Anfragen</h1><p>Gespeicherte Beratungsanfragen aus Landingpage-Formularen.</p></div>';
            echo '<div class="beratung-table-wrap"><table class="beratung-table"><thead><tr><th>Datum</th><th>Landingpage</th><th>Name</th><th>E-Mail</th><th>Thema</th><th>Status</th></tr></thead><tbody>';
            if ($submissions === []) {
                echo '<tr><td colspan="6"><div class="beratung-empty">Noch keine Formular Anfragen vorhanden.</div></td></tr>';
            }
            foreach ($submissions as $submission) {
                echo '<tr><td>' . self::esc((string) ($submission['created_at'] ?? '')) . '</td><td>' . self::esc((string) ($submission['landingpage_title'] ?? '—')) . '</td><td>' . self::esc((string) ($submission['sender_name'] ?? '')) . '</td><td>' . self::esc((string) ($submission['sender_email'] ?? '')) . '</td><td>' . self::esc((string) ($submission['topic'] ?? '')) . '</td><td>' . self::esc((string) ($submission['status'] ?? '')) . '</td></tr>';
            }
            echo '</tbody></table></div>';
        });
    }

    public static function render_import_export(): void
    {
        self::render_with_layout('cms-beratung-import-export', function (): void {
            $notice = '';
            $error = '';
            $exportJson = '';
            $exportId = max(0, (int) ($_GET['export'] ?? 0));
            if ($exportId > 0) {
                $page = CMS_Beratung_Storage::instance()->get_landingpage($exportId);
                if ($page !== null) {
                    $exportJson = CMS_Beratung_Import_Export::export_json($page);
                }
            }
            if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && (string) ($_POST['beratung_admin_action'] ?? '') === 'import_json') {
                if (!self::verify_nonce('beratung_import_json')) {
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } else {
                    try {
                        $payload = CMS_Beratung_Import_Export::decode_import_json((string) ($_POST['import_json'] ?? ''));
                        if ($payload === null) {
                            throw new \InvalidArgumentException('Die JSON-Datei konnte nicht gelesen werden.');
                        }
                        $payload['id'] = 0;
                        $payload['status'] = 'draft';
                        $payload['slug'] = (string) ($payload['slug'] ?? 'beratung') . '-import';
                        $newId = CMS_Beratung_Storage::instance()->save_landingpage($payload);
                        $notice = 'Landingpage importiert. Neue ID: ' . $newId;
                    } catch (\Throwable $e) {
                        $error = $e->getMessage();
                    }
                }
            }
            self::render_notice($notice, $error);
            echo '<div class="beratung-grid-2"><form method="post" class="beratung-card"><input type="hidden" name="csrf_token" value="' . self::esc(self::nonce('beratung_import_json')) . '"><input type="hidden" name="beratung_admin_action" value="import_json"><h1>Import</h1><p>Landingpage als JSON einfügen und als Entwurf importieren.</p><textarea name="import_json" rows="18" class="beratung-code"></textarea><button class="beratung-btn" type="submit">JSON importieren</button></form>';
            echo '<div class="beratung-card"><h1>Export</h1><p>Wählen Sie in der Übersicht „Exportieren“. Der JSON-Export erscheint hier.</p><textarea readonly rows="18" class="beratung-code">' . self::esc($exportJson) . '</textarea></div></div>';
        });
    }

    public static function render_help(): void
    {
        self::render_with_layout('cms-beratung-help', function (): void {
            echo '<div class="beratung-card"><h1>Hilfe und Dokumentation</h1><ul class="beratung-doc-list"><li><strong>Routing:</strong> Veröffentlichte Seiten sind unter <code>/beratung/{slug}</code> erreichbar.</li><li><strong>Bereiche:</strong> Jeder Bereich kann aktiviert, dupliziert, gelöscht und verschoben werden. Cards unterstützen 1 bis 4 Desktop-Spalten.</li><li><strong>Responsivität:</strong> Desktop bis 4 Spalten, Tablet 2 Spalten, Mobil 1 Spalte.</li><li><strong>SEO:</strong> Meta, Open Graph, Twitter Card, Canonical, Noindex/Nofollow und Schema sind vorbereitet.</li><li><strong>Sicherheit:</strong> Admin-Aktionen verwenden Rechteprüfung und CSRF-Token; Frontend-Formulare nutzen Honeypot und Token.</li><li><strong>Mandantenfähigkeit:</strong> Tabellen enthalten <code>tenant_id</code> und sind für spätere Rollen-/Tenant-Filter vorbereitet.</li></ul></div>';
        });
    }

    private static function render_landingpage_form(array $page): void
    {
        $statuses = CMS_Beratung_Settings::statuses();
        $templates = CMS_Beratung_Settings::templates();
        $heroJson = json_encode($page['hero'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
        $sectionsJson = json_encode($page['sections'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
        $designJson = json_encode($page['design'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
        echo '<form method="post" class="beratung-editor"><input type="hidden" name="csrf_token" value="' . self::esc(self::nonce('beratung_save_landingpage')) . '"><input type="hidden" name="beratung_admin_action" value="save_landingpage"><input type="hidden" name="id" value="' . (int) ($page['id'] ?? 0) . '">';
        echo '<div class="beratung-grid-2"><section class="beratung-card"><h1>Allgemeine Einstellungen</h1>';
        self::field('Interner Titel', 'internal_title', (string) ($page['internal_title'] ?? ''));
        self::field('Öffentlicher Titel', 'public_title', (string) ($page['public_title'] ?? ''));
        self::field('URL Slug', 'slug', (string) ($page['slug'] ?? ''));
        self::field('Meta Title', 'meta_title', (string) ($page['meta_title'] ?? ''));
        self::textarea('Meta Description', 'meta_description', (string) ($page['meta_description'] ?? ''), 3);
        self::field('Fokus Keyword', 'focus_keyword', (string) ($page['focus_keyword'] ?? ''));
        self::select('Status', 'status', (string) ($page['status'] ?? 'draft'), $statuses);
        self::select('Template Auswahl', 'template', (string) ($page['template'] ?? 'standard'), $templates);
        self::field('Maximale Inhaltsbreite', 'max_content_width', (string) ($page['max_content_width'] ?? '1200'), 'number');
        self::field('Canonical URL', 'canonical_url', (string) ($page['canonical_url'] ?? ''));
        self::field('Eigene CSS Klasse', 'custom_css_class', (string) ($page['custom_css_class'] ?? ''));
        echo '</section><section class="beratung-card"><h1>Anzeige Optionen</h1>';
        foreach (['custom_design_enabled' => 'Individuelles Design aktivieren', 'use_global_settings' => 'Globale Plugin Einstellungen verwenden', 'show_header' => 'Header anzeigen', 'show_footer' => 'Footer anzeigen', 'show_breadcrumb' => 'Breadcrumb anzeigen', 'show_toc' => 'Inhaltsverzeichnis anzeigen', 'show_anchor_nav' => 'Anker Navigation anzeigen', 'noindex' => 'Noindex aktivieren', 'nofollow' => 'Nofollow aktivieren'] as $name => $label) {
            self::checkbox($label, $name, !empty($page[$name]));
        }
        echo '</section></div>';
        echo '<section class="beratung-card"><h1>Hero / Content Header</h1><p>Bild links oder rechts, nahtloser Bildrand, Badge, Titel, Text, bis zu 3 Buttons und Trust-Hinweis.</p><div id="beratung-hero-builder" data-target="hero_json"></div><textarea id="hero_json" name="hero_json" rows="12" class="beratung-code">' . self::esc($heroJson) . '</textarea></section>';
        echo '<section class="beratung-card"><h1>Frei sortierbare Bereiche und Cards</h1><p>Komfort-Builder: Bereiche und Cards können per Drag and Drop sortiert, dupliziert, deaktiviert und gelöscht werden. Card-Typen zeigen passende Feldgruppen.</p><div id="beratung-builder" data-target="sections_json"></div><textarea id="sections_json" name="sections_json" rows="16" class="beratung-code">' . self::esc($sectionsJson) . '</textarea></section>';
        echo '<section class="beratung-card"><h1>Individuelles Design JSON</h1><textarea name="design_json" rows="8" class="beratung-code">' . self::esc($designJson) . '</textarea></section>';
        echo '<p><button type="submit" class="beratung-btn">Landingpage speichern</button> <a class="beratung-link" href="' . self::esc(self::admin_url('cms-beratung-landingpages')) . '">Zur Übersicht</a></p></form>';
    }

    /** @param array<string,string> $settings */
    private static function render_settings_form(array $settings): void
    {
        $groups = [
            'Globale Design Einstellungen' => ['primary_color', 'secondary_color', 'accent_color', 'background_color', 'text_color', 'heading_color', 'button_color', 'button_text_color', 'card_background_color', 'card_border_color', 'card_shadow_enabled', 'border_radius', 'spacing', 'content_width', 'use_default_font', 'allow_custom_page_css_class'],
            'Kontakt Einstellungen' => ['contact_recipient_email', 'contact_subject_prefix', 'privacy_text', 'success_message', 'error_message', 'form_storage_enabled', 'sender_copy_enabled', 'honeypot_enabled', 'captcha_prepared', 'show_submissions_backend'],
            'SEO Einstellungen' => ['seo_meta_title_enabled', 'seo_meta_description_enabled', 'seo_open_graph_enabled', 'seo_twitter_card_enabled', 'seo_faq_schema_enabled', 'seo_service_schema_enabled', 'seo_breadcrumb_schema_enabled', 'seo_canonical_enabled', 'seo_noindex_per_page_enabled', 'seo_nofollow_per_page_enabled'],
            'Tracking Einstellungen' => ['tracking_button_clicks_enabled', 'tracking_form_submit_enabled', 'tracking_anchor_clicks_enabled', 'tracking_only_when_system_active'],
        ];
        echo '<form method="post"><input type="hidden" name="csrf_token" value="' . self::esc(self::nonce('beratung_settings')) . '"><input type="hidden" name="beratung_admin_action" value="save_settings"><div class="beratung-settings-grid">';
        foreach ($groups as $title => $keys) {
            echo '<section class="beratung-card"><h1>' . self::esc($title) . '</h1>';
            foreach ($keys as $key) {
                $label = ucwords(str_replace('_', ' ', $key));
                if (str_ends_with($key, '_enabled') || str_starts_with($key, 'seo_') || str_starts_with($key, 'tracking_') || in_array($key, ['use_default_font', 'allow_custom_page_css_class', 'captcha_prepared', 'show_submissions_backend'], true)) {
                    self::checkbox($label, $key, ($settings[$key] ?? '0') === '1');
                } elseif (str_contains($key, 'color')) {
                    self::field($label, $key, $settings[$key] ?? '', 'color');
                } elseif (str_contains($key, 'message') || str_contains($key, 'privacy')) {
                    self::textarea($label, $key, $settings[$key] ?? '', 3);
                } else {
                    self::field($label, $key, $settings[$key] ?? '', in_array($key, ['border_radius', 'spacing', 'content_width'], true) ? 'number' : 'text');
                }
            }
            echo '</section>';
        }
        echo '</div><p><button class="beratung-btn" type="submit">Einstellungen speichern</button></p></form>';
    }

    private static function handle_landingpage_actions(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || (string) ($_POST['beratung_admin_action'] ?? '') !== 'landingpage_action') {
            return;
        }
        if (!self::verify_nonce('beratung_landingpage_action')) {
            self::redirect('cms-beratung-landingpages', ['error' => 'csrf']);
        }
        $id = max(0, (int) ($_POST['id'] ?? 0));
        $action = (string) ($_POST['landingpage_action'] ?? '');
        $storage = CMS_Beratung_Storage::instance();
        if ($action === 'publish') {
            $storage->update_status($id, 'published');
        } elseif ($action === 'deactivate') {
            $storage->update_status($id, 'draft');
        } elseif ($action === 'duplicate') {
            $storage->duplicate_landingpage($id);
        } elseif ($action === 'delete') {
            $storage->delete_landingpage($id);
        }
        self::redirect('cms-beratung-landingpages', ['updated' => 1]);
    }

    private static function action_form(int $id, string $action, string $label, bool $danger = false): string
    {
        return '<form method="post" class="beratung-inline-form"><input type="hidden" name="csrf_token" value="' . self::esc(self::nonce('beratung_landingpage_action')) . '"><input type="hidden" name="beratung_admin_action" value="landingpage_action"><input type="hidden" name="landingpage_action" value="' . self::esc($action) . '"><input type="hidden" name="id" value="' . $id . '"><button class="' . ($danger ? 'is-danger' : '') . '" type="submit">' . self::esc($label) . '</button></form>';
    }

    /** @return array<string,mixed> */
    private static function default_landingpage(): array
    {
        return [
            'internal_title' => 'Microsoft 365 Beratung',
            'public_title' => 'Microsoft 365, Copilot und Security Beratung',
            'slug' => 'microsoft-365-beratung',
            'status' => 'draft',
            'template' => 'modern',
            'max_content_width' => 1200,
            'use_global_settings' => 1,
            'show_header' => 1,
            'show_footer' => 1,
            'show_breadcrumb' => 1,
            'show_anchor_nav' => 1,
            'hero' => [
                'enabled' => true,
                'image_url' => '',
                'image_position' => 'left',
                'image_flush' => true,
                'image_height' => 520,
                'image_width' => 46,
                'image_fit' => 'cover',
                'image_alt' => 'Microsoft 365 Beratung',
                'badge_text' => 'Microsoft 365 · Copilot · Security',
                'badge_show' => true,
                'title' => 'Microsoft 365, Copilot und Security Beratung',
                'subtitle' => 'Von Readiness bis Umsetzung – strukturiert, sicher und praxisnah.',
                'description' => 'Wir unterstützen bei Copilot Readiness, Admin Enablement, Security, Compliance, SharePoint Governance, Entra ID und Microsoft Purview.',
                'button_1' => ['text' => 'Beratung anfragen', 'target' => '#kontakt', 'target_type' => 'contact', 'style' => 'primary'],
                'button_2' => ['text' => 'Leistungen ansehen', 'target' => '#angebote', 'target_type' => 'anchor', 'style' => 'ghost'],
                'button_3' => ['text' => 'Workshop planen', 'target' => '#kontakt', 'target_type' => 'contact', 'style' => 'secondary'],
                'trust_text' => 'Praxisnahe Beratung für Microsoft 365, Copilot, Security und Compliance.',
                'background_color' => '#f8fafc',
                'text_color' => '#111827',
                'vertical_align' => 'center',
                'mobile_order' => 'image-first',
            ],
            'sections' => [[
                'id' => 'leistungen',
                'anchor_id' => 'leistungen',
                'enabled' => true,
                'type' => 'services',
                'internal_name' => 'Meine Leistungen',
                'eyebrow' => 'Meine Leistungen',
                'title' => 'Microsoft 365 und Copilot Beratung aus der Praxis',
                'intro' => 'Frei sortierbare Leistungen für Tenant, Security, Compliance, Governance, Automatisierung und Workshops.',
                'columns' => 4,
                'card_type' => 'text',
                'card_design' => 'accent',
                'equal_height' => true,
                'categories_enabled' => true,
                'note_text' => 'Alle Leistungen können als Einzeltermin, Workshop oder Projektbegleitung kombiniert werden.',
                'button_1_text' => 'Beratung anfragen',
                'button_1_target' => '#kontakt',
                'button_1_target_type' => 'contact',
                'button_1_style' => 'primary',
                'background_color' => '#ffffff',
                'text_color' => '#111827',
                'padding_top' => 56,
                'padding_bottom' => 56,
                'max_width' => 1200,
                'text_align' => 'left',
                'cards' => [
                    ['enabled' => true, 'icon' => '🏢', 'category' => 'Tenant', 'title' => 'Microsoft 365 Tenant Check', 'text' => 'Struktur, Lizenzen, Adminrollen, Sicherheit und Governance systematisch prüfen.'],
                    ['enabled' => true, 'icon' => '🤖', 'category' => 'Copilot', 'title' => 'Copilot Readiness Check', 'text' => 'Berechtigungen, Datenqualität, Compliance und technische Voraussetzungen bewerten.'],
                    ['enabled' => true, 'icon' => '🔐', 'category' => 'Identity', 'title' => 'Entra ID Security Review', 'text' => 'MFA, Rollen, Conditional Access, Gastzugriffe und Identity Governance analysieren.'],
                    ['enabled' => true, 'icon' => '📜', 'category' => 'Compliance', 'title' => 'Microsoft Purview Beratung', 'text' => 'Informationsschutz, DLP, Labels, eDiscovery und Aufbewahrung praxisnah einordnen.'],
                    ['enabled' => true, 'icon' => '🧭', 'category' => 'Governance', 'title' => 'SharePoint und OneDrive Governance', 'text' => 'Sites, Freigaben, Lifecycle, Berechtigungen und Informationsarchitektur strukturieren.'],
                    ['enabled' => true, 'icon' => '✉️', 'category' => 'Exchange', 'title' => 'Exchange Online Analyse', 'text' => 'Postfächer, Transportregeln, Schutzfunktionen und Betriebsrisiken bewerten.'],
                    ['enabled' => true, 'icon' => '🛡️', 'category' => 'Security', 'title' => 'Microsoft Defender Review', 'text' => 'Defender-Konfigurationen, Secure Score und Schutzmaßnahmen priorisieren.'],
                    ['enabled' => true, 'icon' => '🚦', 'category' => 'Access', 'title' => 'Conditional Access Bewertung', 'text' => 'Richtlinien, Ausnahmen, Break-Glass-Konten und Risiko-Szenarien prüfen.'],
                    ['enabled' => true, 'icon' => '🎓', 'category' => 'Enablement', 'title' => 'Admin Workshops', 'text' => 'Praxisnahe Workshops für Admin Teams statt Folien-Consulting von der Stange.'],
                    ['enabled' => true, 'icon' => '⚙️', 'category' => 'Automation', 'title' => 'PowerShell Automatisierung', 'text' => 'Wiederkehrende Admin-Aufgaben mit nachvollziehbaren Skripten automatisieren.'],
                    ['enabled' => true, 'icon' => '📘', 'category' => 'Betrieb', 'title' => 'Dokumentation und Übergabe', 'text' => 'Verständliche Dokumentation für Betrieb, Entscheidungen und nächste Schritte.'],
                    ['enabled' => true, 'icon' => '🤝', 'category' => 'Projekt', 'title' => 'Projektbegleitung', 'text' => 'Technische Umsetzung, Review-Termine und Enablement über das Projekt hinweg begleiten.'],
                ],
            ], [
                'id' => 'ablauf',
                'anchor_id' => 'ablauf',
                'enabled' => true,
                'type' => 'steps',
                'internal_name' => 'Beratungsablauf',
                'eyebrow' => 'Vorgehen',
                'title' => 'So läuft die Zusammenarbeit ab',
                'intro' => 'Ein klarer Ablauf macht Beratung planbar, nachvollziehbar und technisch belastbar.',
                'columns' => 4,
                'card_type' => 'step',
                'display_style' => 'horizontal',
                'auto_number' => true,
                'connector' => true,
                'equal_height' => true,
                'button_1_text' => 'Erstgespräch anfragen',
                'button_1_target' => '#kontakt',
                'button_1_target_type' => 'contact',
                'background_color' => '#f8fafc',
                'text_color' => '#111827',
                'cards' => [
                    ['enabled' => true, 'card_type' => 'step', 'auto_number' => true, 'title' => 'Erstgespräch', 'text' => 'Rahmen, Ausgangslage und gewünschtes Ergebnis klären.', 'connector' => true],
                    ['enabled' => true, 'card_type' => 'step', 'auto_number' => true, 'title' => 'Zielklärung', 'text' => 'Prioritäten, Risiken und betroffene Microsoft 365 Bereiche festlegen.', 'connector' => true],
                    ['enabled' => true, 'card_type' => 'step', 'auto_number' => true, 'title' => 'Technische Analyse', 'text' => 'Tenant, Entra ID, Security, Compliance und Governance prüfen.', 'connector' => true],
                    ['enabled' => true, 'card_type' => 'step', 'auto_number' => true, 'title' => 'Bewertung', 'text' => 'Findings einordnen und realistische Handlungsoptionen ableiten.', 'connector' => true],
                    ['enabled' => true, 'card_type' => 'step', 'auto_number' => true, 'title' => 'Maßnahmenplan', 'text' => 'Konkrete Roadmap mit Quick Wins und sauberer Priorisierung erstellen.', 'connector' => true],
                    ['enabled' => true, 'card_type' => 'step', 'auto_number' => true, 'title' => 'Umsetzung', 'text' => 'Konfigurationen, Pilotierung und Admin Enablement begleiten.', 'connector' => true],
                    ['enabled' => true, 'card_type' => 'step', 'auto_number' => true, 'title' => 'Dokumentation', 'text' => 'Ergebnisse, Entscheidungen und Betriebswissen verständlich festhalten.', 'connector' => true],
                    ['enabled' => true, 'card_type' => 'step', 'auto_number' => true, 'title' => 'Übergabe', 'text' => 'Nächste Schritte, Betrieb und Verantwortlichkeiten sauber übergeben.', 'connector' => false],
                ],
            ], [
                'id' => 'vergleich-genai-agentic-ai',
                'anchor_id' => 'vergleich',
                'enabled' => true,
                'type' => 'comparison',
                'internal_name' => 'Vergleich GenAI Agentic AI',
                'eyebrow' => 'Einordnung',
                'title' => 'Vergleiche und Infografik Inhalte',
                'intro' => 'Komplexe KI- und Governance-Themen verständlich gegenüberstellen.',
                'columns' => 2,
                'comparison_variant' => 'genai_agentic',
                'title_band_enabled' => true,
                'title_band_text' => 'GenAI vs. Agentic AI: Die wichtigsten Unterschiede',
                'title_band_background_color' => '#1e3a8a',
                'title_band_text_color' => '#ffffff',
                'border_enabled' => true,
                'shadow_enabled' => true,
                'mobile_stack' => true,
                'background_color' => '#ffffff',
                'text_color' => '#111827',
                'cards' => [
                    ['enabled' => true, 'icon' => '✨', 'title' => 'Generative KI', 'text' => 'Erstellt Inhalte wie Texte, Bilder oder Code auf Basis großer Sprachmodelle. Kreativ und leistungsfähig, aber oft isoliert und ohne direkten Bezug zu Unternehmensprozessen.', 'extra_text' => 'Typischer Einsatz: Content Erstellung, Ideenfindung, Automatisierung einfacher Aufgaben'],
                    ['enabled' => true, 'icon' => '🧠', 'title' => 'Agentic AI', 'text' => 'Geht einen Schritt weiter, handelt proaktiv, integriert sich in bestehende Systeme und führt Aufgaben je nach Anforderung teilautonom aus.', 'extra_text' => 'Typischer Einsatz: Prozessautomatisierung, intelligente Assistenz, datenbasierte Entscheidungsunterstützung'],
                ],
            ], [
                'id' => 'faq',
                'anchor_id' => 'faq',
                'enabled' => true,
                'type' => 'faq',
                'internal_name' => 'FAQ Modul',
                'eyebrow' => 'FAQ',
                'title' => 'Häufige Fragen zur Microsoft 365 und Copilot Beratung',
                'intro' => 'Antworten auf typische Fragen vor einem Beratungsprojekt.',
                'faq_allow_multiple' => false,
                'faq_icon_style' => 'plus',
                'faq_open_behavior' => 'first',
                'faq_schema_enabled' => true,
                'faq_question_background_color' => '#ffffff',
                'faq_question_text_color' => '#111827',
                'faq_answer_background_color' => '#f8fafc',
                'background_color' => '#f8fafc',
                'cards' => [
                    ['enabled' => true, 'question' => 'Was ist Microsoft 365 Copilot?', 'answer' => 'Microsoft 365 Copilot verbindet KI-Funktionen mit Microsoft 365 Apps und Unternehmensdaten, sofern Berechtigungen, Datenstruktur und Compliance sauber vorbereitet sind.', 'default_open' => true],
                    ['enabled' => true, 'question' => 'Für wen eignet sich Microsoft Copilot?', 'answer' => 'Copilot eignet sich für Organisationen, die Microsoft 365 aktiv nutzen und ihre Daten, Berechtigungen und Prozesse transparent im Griff haben möchten.'],
                    ['enabled' => true, 'question' => 'Ist Microsoft Copilot DSGVO konform einsetzbar?', 'answer' => 'Ein DSGVO-konformer Einsatz hängt von Konfiguration, Datenklassifizierung, Governance, Betriebsprozessen und rechtlicher Bewertung ab. Die Beratung hilft bei der technischen Einordnung.'],
                    ['enabled' => true, 'question' => 'Welche Voraussetzungen braucht mein Tenant?', 'answer' => 'Wichtig sind saubere Identitäten, Berechtigungen, Informationsschutz, SharePoint Governance, Auditierbarkeit und passende Lizenzierung.'],
                    ['enabled' => true, 'question' => 'Wie läuft ein Copilot Readiness Check ab?', 'answer' => 'Der Check bewertet Tenant-Konfiguration, Datenzugriffe, Security, Compliance, Governance und organisatorische Bereitschaft.'],
                    ['enabled' => true, 'question' => 'Was kostet eine Microsoft 365 Beratung?', 'answer' => 'Das hängt von Umfang, Zielen und gewünschter Tiefe ab. Nach einem Erstgespräch kann der Aufwand realistisch eingegrenzt werden.'],
                    ['enabled' => true, 'question' => 'Kann die Beratung remote stattfinden?', 'answer' => 'Ja, viele Checks, Workshops und Reviews können remote durchgeführt werden.'],
                    ['enabled' => true, 'question' => 'Unterstützt du auch bei der technischen Umsetzung?', 'answer' => 'Ja, neben Analyse und Konzept kann auch die technische Umsetzung begleitet werden.'],
                    ['enabled' => true, 'question' => 'Gibt es eine Dokumentation nach der Beratung?', 'answer' => 'Ja, Ergebnisse, Empfehlungen und nächste Schritte werden verständlich dokumentiert.'],
                    ['enabled' => true, 'question' => 'Können Workshops für Admin Teams durchgeführt werden?', 'answer' => 'Ja, Workshops für Admin Teams sind ein zentraler Bestandteil des Angebots.'],
                ],
            ], [
                'id' => 'trust',
                'anchor_id' => 'vertrauen',
                'enabled' => true,
                'type' => 'trust',
                'internal_name' => 'Trust Bereich',
                'eyebrow' => 'Vertrauen',
                'title' => 'Technische Beratung statt reines Folien Consulting',
                'intro' => 'Praxis, Betrieb und Sicherheit stehen im Mittelpunkt.',
                'columns' => 3,
                'button_1_text' => 'Projekt besprechen',
                'button_1_target' => '#kontakt',
                'background_color' => '#ffffff',
                'cards' => [
                    ['enabled' => true, 'icon' => '⏱️', 'metric' => '20+ Jahre', 'title' => 'IT Erfahrung', 'text' => 'Langjährige Praxis in Infrastruktur, Microsoft 365 und Administration.'],
                    ['enabled' => true, 'icon' => '🏢', 'title' => 'Echte Admin Umgebungen', 'text' => 'Microsoft 365 Praxis aus produktiven Szenarien statt Labortheorie.'],
                    ['enabled' => true, 'icon' => '🛡️', 'title' => 'Security und Governance', 'text' => 'Fokus auf Sicherheit, Governance und nachhaltigen Betrieb.'],
                    ['enabled' => true, 'icon' => '🛠️', 'title' => 'Technische Beratung', 'text' => 'Konkrete Konfiguration, Bewertung und Umsetzung statt Folienfriedhof.'],
                    ['enabled' => true, 'icon' => '📘', 'title' => 'Verständliche Dokumentation', 'text' => 'Ergebnisse so dokumentiert, dass Teams damit weiterarbeiten können.'],
                    ['enabled' => true, 'icon' => '🎓', 'title' => 'Admin Workshops', 'text' => 'Praxisnahe Workshops für Admin Teams und Betrieb.'],
                ],
            ], [
                'id' => 'technologien',
                'anchor_id' => 'technologien',
                'enabled' => true,
                'type' => 'technology',
                'internal_name' => 'Technologie Bereich',
                'eyebrow' => 'Technologien',
                'title' => 'Relevante Microsoft Technologien',
                'intro' => 'Beratung entlang der Plattformen, die im Microsoft 365 Betrieb wirklich zusammenspielen.',
                'columns' => 4,
                'display_style' => 'icon_grid',
                'background_color' => '#f8fafc',
                'cards' => [
                    ['enabled' => true, 'icon' => '☁️', 'name' => 'Microsoft 365'], ['enabled' => true, 'icon' => '🤖', 'name' => 'Microsoft Copilot'], ['enabled' => true, 'icon' => '🔐', 'name' => 'Microsoft Entra ID'], ['enabled' => true, 'icon' => '📜', 'name' => 'Microsoft Purview'],
                    ['enabled' => true, 'icon' => '🛡️', 'name' => 'Microsoft Defender'], ['enabled' => true, 'icon' => '🧭', 'name' => 'SharePoint Online'], ['enabled' => true, 'icon' => '📁', 'name' => 'OneDrive for Business'], ['enabled' => true, 'icon' => '✉️', 'name' => 'Exchange Online'],
                    ['enabled' => true, 'icon' => '💬', 'name' => 'Microsoft Teams'], ['enabled' => true, 'icon' => '⚙️', 'name' => 'PowerShell'], ['enabled' => true, 'icon' => '🌐', 'name' => 'Azure'], ['enabled' => true, 'icon' => '🚦', 'name' => 'Conditional Access'],
                ],
            ], [
                'id' => 'cta-copilot-readiness',
                'anchor_id' => 'copilot-readiness',
                'enabled' => true,
                'type' => 'cta',
                'internal_name' => 'CTA Band Copilot Readiness',
                'eyebrow' => 'Copilot Readiness',
                'title' => 'Du möchtest wissen, ob dein Microsoft 365 Tenant bereit für Copilot ist?',
                'intro' => 'Dann lass uns gemeinsam prüfen, wo Berechtigungen, Datenstruktur, Governance und Compliance wirklich stehen.',
                'display_style' => 'large',
                'button_1_text' => 'Beratung anfragen',
                'button_1_target' => '#kontakt',
                'button_1_target_type' => 'contact',
                'button_1_style' => 'primary',
                'button_2_text' => 'Leistungen ansehen',
                'button_2_target' => '#leistungen',
                'button_2_target_type' => 'anchor',
                'button_2_style' => 'ghost',
                'background_color' => '#1e3a8a',
                'text_color' => '#ffffff',
            ], [
                'id' => 'trenner',
                'anchor_id' => 'trenner',
                'enabled' => true,
                'type' => 'divider',
                'internal_name' => 'Zitat Trenner',
                'divider_type' => 'quote',
                'divider_title' => 'Gute Copilot Einführung beginnt nicht beim Prompt, sondern bei Daten, Identitäten und Governance.',
                'divider_icon' => '💬',
                'background_color' => '#eff6ff',
                'text_color' => '#111827',
                'divider_line_color' => '#93c5fd',
                'divider_width' => 80,
            ], [
                'id' => 'html-hinweis',
                'anchor_id' => 'html-hinweis',
                'enabled' => false,
                'type' => 'html',
                'internal_name' => 'Freier HTML Bereich',
                'eyebrow' => 'Optional',
                'title' => 'Freier HTML Bereich',
                'intro' => 'Nur für berechtigte Benutzer. Unsichere Skripte werden gefiltert.',
                'html' => '<p><strong>Optionaler Hinweis:</strong> Dieser Bereich kann für geprüfte HTML-Inhalte verwendet werden.</p>',
            ]],
            'design' => [],
        ];
    }

    private static function render_with_layout(string $activePage, callable $renderer): void
    {
        self::check_access();
        self::ensure_shared_contract_loaded();
        if (function_exists('cms_plugin_admin_layout_start')) {
            cms_plugin_admin_layout_start(self::PAGE_TITLES[$activePage] ?? 'CMS Beratung', $activePage);
        } elseif (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart(self::PAGE_TITLES[$activePage] ?? 'CMS Beratung', $activePage);
        }
        self::enqueue_admin_assets();
        $renderer();
        self::enqueue_admin_scripts();
        if (function_exists('cms_plugin_admin_layout_end')) {
            cms_plugin_admin_layout_end();
        } elseif (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
        }
    }

    private static function enqueue_admin_assets(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;
        $css = CMS_BERATUNG_PLUGIN_DIR . 'assets/css/admin.css';
        if (is_file($css)) {
            echo '<link rel="stylesheet" href="' . self::esc(CMS_BERATUNG_PLUGIN_URL . 'assets/css/admin.css') . '?v=' . filemtime($css) . '">' . "\n";
        }
    }

    private static function enqueue_admin_scripts(): void
    {
        $js = CMS_BERATUNG_PLUGIN_DIR . 'assets/js/admin-builder.js';
        if (is_file($js)) {
            echo '<script src="' . self::esc(CMS_BERATUNG_PLUGIN_URL . 'assets/js/admin-builder.js') . '?v=' . filemtime($js) . '" defer></script>' . "\n";
        }
    }

    private static function check_access(): void
    {
        $hasCapability = !function_exists('current_user_can') || current_user_can('manage_options');
        $isAdmin = !class_exists('CMS\\Auth') || \CMS\Auth::instance()->isAdmin();
        if (!$hasCapability || !$isAdmin) {
            header('Location: ' . (defined('SITE_URL') ? (string) SITE_URL : '/'), true, 302);
            exit;
        }
    }

    private static function nonce(string $action): string
    {
        return class_exists('CMS\\Security') ? (string) \CMS\Security::instance()->generateToken($action) : '';
    }

    private static function verify_nonce(string $action): bool
    {
        return class_exists('CMS\\Security') && \CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), $action);
    }

    private static function field(string $label, string $name, string $value, string $type = 'text'): void
    {
        echo '<label class="beratung-field"><span>' . self::esc($label) . '</span><input type="' . self::esc($type) . '" name="' . self::esc($name) . '" value="' . self::esc($value) . '"></label>';
    }

    private static function textarea(string $label, string $name, string $value, int $rows): void
    {
        echo '<label class="beratung-field"><span>' . self::esc($label) . '</span><textarea name="' . self::esc($name) . '" rows="' . $rows . '">' . self::esc($value) . '</textarea></label>';
    }

    /** @param array<string,string> $options */
    private static function select(string $label, string $name, string $value, array $options): void
    {
        echo '<label class="beratung-field"><span>' . self::esc($label) . '</span><select name="' . self::esc($name) . '">';
        foreach ($options as $key => $optionLabel) {
            echo '<option value="' . self::esc($key) . '"' . ($key === $value ? ' selected' : '') . '>' . self::esc($optionLabel) . '</option>';
        }
        echo '</select></label>';
    }

    private static function checkbox(string $label, string $name, bool $checked): void
    {
        echo '<input type="hidden" name="' . self::esc($name) . '" value="0"><label class="beratung-check"><input type="checkbox" name="' . self::esc($name) . '" value="1"' . ($checked ? ' checked' : '') . '> <span>' . self::esc($label) . '</span></label>';
    }

    private static function render_notice(string $notice, string $error): void
    {
        if ($notice !== '') {
            echo '<div class="beratung-notice is-success">' . self::esc($notice) . '</div>';
        }
        if ($error !== '') {
            echo '<div class="beratung-notice is-error">' . self::esc($error) . '</div>';
        }
    }

    private static function notice_from_query(): void
    {
        if (!empty($_GET['updated'])) {
            self::render_notice('Aktion erfolgreich ausgeführt.', '');
        }
        if (!empty($_GET['error'])) {
            self::render_notice('', 'Die Aktion konnte nicht ausgeführt werden.');
        }
    }

    /** @return array<string,callable|null> */
    private static function resolve_callbacks(): array
    {
        $callbacks = [];
        foreach (self::PAGE_RENDERERS as $slug => $method) {
            $callbacks[$slug] = is_callable([self::class, $method]) ? [self::class, $method] : null;
        }
        return $callbacks;
    }

    private static function admin_url(string $pageSlug, array $params = []): string
    {
        $url = function_exists('cms_plugin_admin_page_path') ? cms_plugin_admin_page_path(self::MENU_SLUG, $pageSlug) : '/admin/plugins/' . self::MENU_SLUG . '/' . rawurlencode($pageSlug);
        return $params !== [] ? $url . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986) : $url;
    }

    private static function redirect(string $pageSlug, array $params = []): never
    {
        header('Location: ' . self::admin_url($pageSlug, $params), true, 302);
        exit;
    }

    private static function ensure_shared_contract_loaded(): void
    {
        if (function_exists('cms_plugin_admin_dispatch_page')) {
            return;
        }
        $shared = dirname(CMS_BERATUNG_PLUGIN_DIR) . '/shared/admin/plugin-admin-contract.php';
        if (is_file($shared)) {
            require_once $shared;
        }
    }

    private static function normalize_slug(string $slug): string
    {
        if (function_exists('cms_plugin_admin_normalize_slug')) {
            return cms_plugin_admin_normalize_slug($slug);
        }
        return trim((string) preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($slug)), '-');
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
