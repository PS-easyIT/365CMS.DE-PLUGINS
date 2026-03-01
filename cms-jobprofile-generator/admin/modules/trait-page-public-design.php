<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin-Seite: Public & Single Page Design-Einstellungen
 *
 * Steuert ALLE visuellen Aspekte der öffentlichen Seiten:
 * - Farben, Typografie, Abstände
 * - Layout-Auswahl (4 Single-Page Layouts)
 * - Content-Breite, Abstände, Schatten
 * - Button-Styles, Apply-Modal, Job-Liste
 *
 * @since   0.9.7
 * @package CMS_JobProfileGenerator
 */
trait CMS_JPG_Page_Public_Design_Trait
{
    // ── PUBLIC DESIGN SEITE ──────────────────────────────────────────────────

    public static function render_public_design(): void
    {
        self::check_access();

        $tab    = sanitize_key($_GET['tab'] ?? 'colors');
        $notice = '';
        $error  = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::verify_nonce('jpg_public_design_save')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                [$notice, $error] = self::handle_public_design_post($tab);
            }
        }

        $tabs = [
            'colors'      => '🎨 Farben',
            'typography'  => '🔤 Typografie',
            'layout'      => '📐 Layout',
            'single'      => '📄 Single Page',
            'list'        => '📋 Listenansicht',
            'buttons'     => '🔘 Buttons',
            'apply-modal' => '📩 Bewerbungsmodal',
            'advanced'    => '⚙️ Erweitert',
        ];

        $settings = self::get_public_design_settings();

        include JPG_DIR . 'admin/views/page-public-design.php';
    }

    // ── Settings Laden ───────────────────────────────────────────────────────

    /**
     * @return array<string, string>
     */
    public static function get_public_design_settings(): array
    {
        try {
            $db   = \CMS\Database::instance();
            $p    = $db->getPrefix();
            $rows = $db->get_results(
                "SELECT setting_key, setting_value FROM {$p}jpg_settings WHERE setting_key LIKE 'pd_%'",
                []
            );
            $settings = [];
            foreach ($rows as $row) {
                $settings[$row->setting_key] = $row->setting_value;
            }
            return $settings;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Einzelnen Public-Design-Wert laden (mit Default).
     */
    public static function pd(array $settings, string $key, string $default = ''): string
    {
        return htmlspecialchars($settings['pd_' . $key] ?? $default, ENT_QUOTES);
    }

    // ── Settings Speichern ───────────────────────────────────────────────────

    /** @return array{string, string} */
    private static function handle_public_design_post(string $tab): array
    {
        $notice = '';
        $error  = '';

        // Sammle alle Settings aus dem POST nach Tab
        $fields = self::get_fields_for_tab($tab);

        if (empty($fields)) {
            return ['', 'Unbekannter Tab.'];
        }

        $data = [];
        foreach ($fields as $key => $default) {
            $data[$key] = sanitize_text_field($_POST[$key] ?? $default);
        }

        self::save_public_design_settings($data);
        $notice = 'Design-Einstellungen gespeichert.';

        return [$notice, $error];
    }

    /**
     * @return array<string, string> Feld-Schlüssel → Default-Wert
     */
    private static function get_fields_for_tab(string $tab): array
    {
        return match ($tab) {
            'colors' => [
                'primary_color'       => '#3b82f6',
                'primary_dark'        => '#2563eb',
                'secondary_color'     => '#1e293b',
                'accent_color'        => '#10b981',
                'bg_color'            => '#f8fafc',
                'card_bg'             => '#ffffff',
                'text_color'          => '#1e293b',
                'text_muted'          => '#64748b',
                'border_color'        => '#e2e8f0',
                'header_bg'           => '#3b82f6',
                'header_text'         => '#ffffff',
                'salary_badge_bg'     => '#d1fae5',
                'salary_badge_text'   => '#065f46',
            ],
            'typography' => [
                'font_body'           => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                'font_heading'        => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                'font_size_base'      => '16',
                'font_size_h1'        => '1.6',
                'font_size_h2'        => '1.1',
                'font_size_h3'        => '0.875',
                'font_size_meta'      => '0.85',
                'font_size_small'     => '0.82',
                'line_height'         => '1.6',
                'heading_weight'      => '700',
            ],
            'layout' => [
                'content_max_width'   => '900',
                'card_border_radius'  => '10',
                'card_shadow'         => '0 4px 24px rgba(0,0,0,.08)',
                'card_shadow_hover'   => '0 8px 32px rgba(0,0,0,.12)',
                'section_padding'     => '2',
                'content_padding'     => '2',
                'grid_gap'            => '1.5',
                'sidebar_width'       => '280',
            ],
            'single' => [
                'single_layout'       => 'classic',
                'single_max_width'    => '900',
                'single_show_salary'  => '1',
                'single_show_company' => '1',
                'single_show_team'    => '1',
                'single_show_skills'  => '1',
                'single_show_benefits'=> '1',
                'single_show_jsonld'  => '1',
                'single_show_share'   => '1',
                'single_header_style' => 'colored',
                'single_two_col_breakpoint' => '640',
            ],
            'list' => [
                'list_max_width'      => '900',
                'list_card_style'     => 'horizontal',
                'list_show_salary'    => '1',
                'list_show_company'   => '1',
                'list_show_remote'    => '1',
                'list_show_category'  => '1',
                'list_show_summary'   => '1',
                'list_per_page'       => '20',
                'list_show_filters'   => '1',
                'list_card_shadow'    => '0 1px 3px rgba(0,0,0,.06)',
            ],
            'buttons' => [
                'btn_border_radius'   => '8',
                'btn_padding_x'       => '2',
                'btn_padding_y'       => '0.75',
                'btn_font_weight'     => '700',
                'btn_font_size'       => '0.95',
                'btn_primary_bg'      => '',
                'btn_primary_text'    => '#ffffff',
                'btn_apply_text'      => '📩 Jetzt bewerben',
                'btn_apply_size'      => 'normal',
            ],
            'apply-modal' => [
                'modal_max_width'       => '580',
                'modal_border_radius'   => '12',
                'modal_show_phone'      => '1',
                'modal_show_cv'         => '1',
                'modal_require_cv'      => '0',
                'modal_cover_min_chars' => '20',
                'modal_privacy_url'     => '/datenschutz',
                'modal_privacy_text'    => 'Mit dem Absenden stimmst du der Verarbeitung deiner Daten gemäß unserer Datenschutzerklärung zu.',
                'modal_success_text'    => 'Bewerbung eingereicht! Wir melden uns so schnell wie möglich.',
                'modal_require_login'   => '1',
                'modal_show_register'   => '1',
            ],
            'advanced' => [
                'custom_css'          => '',
                'custom_head_code'    => '',
                'disable_animations'  => '0',
                'lazy_load_images'    => '1',
                'enable_print_styles' => '1',
            ],
            default => [],
        };
    }

    /** @param array<string, string> $data */
    private static function save_public_design_settings(array $data): void
    {
        try {
            $db  = \CMS\Database::instance();
            $p   = $db->getPrefix();
            $pdo = $db->getPdo();

            foreach ($data as $key => $value) {
                $dbKey = 'pd_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($key));
                $pdo->exec(
                    "INSERT INTO {$p}jpg_settings (setting_key, setting_value)
                     VALUES (" . $pdo->quote($dbKey) . ", " . $pdo->quote((string) $value) . ")
                     ON DUPLICATE KEY UPDATE setting_value = " . $pdo->quote((string) $value)
                );
            }
        } catch (\Throwable $e) {
            error_log('CMS_JPG: save_public_design_settings error: ' . $e->getMessage());
        }
    }

    /**
     * Baut das CSS aus den Design-Settings zusammen.
     * Wird im Frontend via head-Hook eingefügt.
     *
     * @return string CSS-String
     */
    public static function build_public_design_css(): string
    {
        $s = self::get_public_design_settings();
        $g = fn(string $k, string $d = '') => $s['pd_' . $k] ?? $d;

        $css = ':root {';

        // Farben
        if ($g('primary_color'))     $css .= '--jpg-primary: '      . $g('primary_color')     . ';';
        if ($g('primary_dark'))      $css .= '--jpg-primary-dark: ' . $g('primary_dark')       . ';';
        if ($g('secondary_color'))   $css .= '--jpg-secondary: '    . $g('secondary_color')    . ';';
        if ($g('accent_color'))      $css .= '--jpg-accent: '       . $g('accent_color')       . ';';
        if ($g('bg_color'))          $css .= '--jpg-bg: '           . $g('bg_color')           . ';';
        if ($g('card_bg'))           $css .= '--jpg-card-bg: '      . $g('card_bg')            . ';';
        if ($g('text_color'))        $css .= '--jpg-text: '         . $g('text_color')         . ';';
        if ($g('text_muted'))        $css .= '--jpg-text-muted: '   . $g('text_muted')         . ';';
        if ($g('border_color'))      $css .= '--jpg-border: '       . $g('border_color')       . ';';
        if ($g('header_bg'))         $css .= '--jpg-header-bg: '    . $g('header_bg')          . ';';
        if ($g('header_text'))       $css .= '--jpg-header-text: '  . $g('header_text')        . ';';

        // Typografie
        if ($g('font_body'))         $css .= '--jpg-font-body: '    . $g('font_body')          . ';';
        if ($g('font_heading'))      $css .= '--jpg-font-heading: ' . $g('font_heading')       . ';';

        // Layout
        if ($g('card_border_radius')) $css .= '--jpg-radius: '      . $g('card_border_radius') . 'px;';

        $css .= '}';

        // Content-Breite (Single)
        $singleWidth = $g('single_max_width', '900');
        if ($singleWidth !== '900') {
            $css .= '.jpg-public { max-width: ' . (int)$singleWidth . 'px; }';
        }

        // Content-Breite (Liste)
        $listWidth = $g('list_max_width', '900');
        if ($listWidth !== '900') {
            $css .= '.jpg-jobs-list { max-width: ' . (int)$listWidth . 'px; }';
        }

        // Header-Farben
        if ($g('header_bg') && $g('header_bg') !== '#3b82f6') {
            $css .= '.jpg-job-header { background: ' . $g('header_bg') . '; }';
        }

        // Button-Anpassungen
        $btnRadius = $g('btn_border_radius', '8');
        if ($btnRadius !== '8') {
            $css .= '.jpg-btn-apply, .jpg-btn, .jpg-modal__btn { border-radius: ' . (int)$btnRadius . 'px; }';
        }

        // Card Shadow
        $cardShadow = $g('card_shadow');
        if ($cardShadow && $cardShadow !== '0 4px 24px rgba(0,0,0,.08)') {
            $css .= '.jpg-job-card { box-shadow: ' . $cardShadow . '; }';
        }

        // Padding
        $contentPad = $g('content_padding', '2');
        if ($contentPad !== '2') {
            $css .= '.jpg-job-body, .jpg-job-header { padding: ' . $contentPad . 'rem; }';
        }

        // Sidebar Width (Two-Column)
        $sidebarW = $g('sidebar_width', '280');
        if ($sidebarW !== '280') {
            $css .= '.jpg-job-two-col { grid-template-columns: 1fr ' . (int)$sidebarW . 'px; }';
        }

        // Modal
        $modalWidth = $g('modal_max_width', '580');
        if ($modalWidth !== '580') {
            $css .= '.jpg-modal__box { max-width: ' . (int)$modalWidth . 'px; }';
        }
        $modalRadius = $g('modal_border_radius', '12');
        if ($modalRadius !== '12') {
            $css .= '.jpg-modal__box { border-radius: ' . (int)$modalRadius . 'px; }';
        }

        // Custom CSS
        $customCss = $g('custom_css');
        if (!empty($customCss)) {
            $css .= "\n/* Custom CSS */\n" . $customCss;
        }

        return $css;
    }
}
