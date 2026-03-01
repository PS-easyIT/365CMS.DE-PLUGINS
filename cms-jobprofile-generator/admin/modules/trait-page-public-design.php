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

        // Checkbox-Felder erkennen (Standard-Wert '1' oder '0')
        $checkboxFields = self::get_checkbox_fields_for_tab($tab);

        // Felder die NICHT durch strip_tags laufen dürfen (HTML/CSS-Inhalt)
        $rawFields = ['custom_css', 'custom_head_code'];

        $data = [];
        foreach ($fields as $key => $default) {
            if (in_array($key, $checkboxFields, true)) {
                // Unchecked Checkboxen senden keinen POST-Wert → '0' speichern
                $data[$key] = isset($_POST[$key]) ? '1' : '0';
            } elseif (in_array($key, $rawFields, true)) {
                // HTML/CSS-Felder: nur trimmen, nicht strip_tags
                $data[$key] = trim((string) ($_POST[$key] ?? $default));
            } else {
                $data[$key] = sanitize_text_field($_POST[$key] ?? $default);
            }
        }

        self::save_public_design_settings($data);
        $notice = 'Design-Einstellungen gespeichert.';

        return [$notice, $error];
    }

    /**
     * Gibt die Checkbox-Feldnamen für einen Tab zurück.
     *
     * @return array<int, string>
     */
    private static function get_checkbox_fields_for_tab(string $tab): array
    {
        return match ($tab) {
            'single' => [
                'single_show_salary', 'single_show_company', 'single_show_team',
                'single_show_skills', 'single_show_benefits', 'single_show_jsonld',
                'single_show_share',
            ],
            'list' => [
                'list_show_title', 'list_show_count',
                'list_show_salary', 'list_show_company', 'list_show_remote',
                'list_show_category', 'list_show_summary', 'list_show_filters',
            ],
            'apply-modal' => [
                'modal_show_phone', 'modal_show_cv', 'modal_require_cv',
                'modal_require_login', 'modal_show_register',
            ],
            'advanced' => [
                'disable_animations', 'lazy_load_images', 'enable_print_styles',
            ],
            default => [],
        };
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
                'list_show_title'     => '1',
                'list_show_count'     => '1',
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
        if ($g('salary_badge_bg'))   $css .= '--jpg-salary-bg: '    . $g('salary_badge_bg')    . ';';
        if ($g('salary_badge_text')) $css .= '--jpg-salary-text: '  . $g('salary_badge_text')  . ';';

        // Typografie
        if ($g('font_body'))         $css .= '--jpg-font-body: '    . $g('font_body')          . ';';
        if ($g('font_heading'))      $css .= '--jpg-font-heading: ' . $g('font_heading')       . ';';
        $fontSize = $g('font_size_base', '16');
        if ($fontSize !== '16')      $css .= '--jpg-font-size-base: ' . (int)$fontSize         . 'px;';
        $lineHeight = $g('line_height', '1.6');
        if ($lineHeight !== '1.6')   $css .= '--jpg-line-height: '    . $lineHeight             . ';';
        $h1Size = $g('font_size_h1', '1.6');
        if ($h1Size !== '1.6')       $css .= '--jpg-font-h1: '       . $h1Size                 . 'rem;';
        $h2Size = $g('font_size_h2', '1.1');
        if ($h2Size !== '1.1')       $css .= '--jpg-font-h2: '       . $h2Size                 . 'rem;';
        $h3Size = $g('font_size_h3', '0.875');
        if ($h3Size !== '0.875')     $css .= '--jpg-font-h3: '       . $h3Size                 . 'rem;';
        $metaSize = $g('font_size_meta', '0.85');
        if ($metaSize !== '0.85')    $css .= '--jpg-font-meta: '     . $metaSize               . 'rem;';
        $smallSize = $g('font_size_small', '0.82');
        if ($smallSize !== '0.82')   $css .= '--jpg-font-small: '    . $smallSize              . 'rem;';
        $headWeight = $g('heading_weight', '700');
        if ($headWeight !== '700')   $css .= '--jpg-heading-weight: ' . $headWeight             . ';';

        // Layout
        if ($g('card_border_radius')) $css .= '--jpg-radius: '      . (int)$g('card_border_radius') . 'px;';
        $gridGap = $g('grid_gap', '1.5');
        if ($gridGap !== '1.5')      $css .= '--jpg-grid-gap: '    . $gridGap                . 'rem;';
        $sectionPad = $g('section_padding', '2');
        if ($sectionPad !== '2')     $css .= '--jpg-section-pad: ' . $sectionPad             . 'rem;';
        $contentPad = $g('content_padding', '2');
        if ($contentPad !== '2')     $css .= '--jpg-content-pad: ' . $contentPad             . 'rem;';

        // Button
        $btnRadius = $g('btn_border_radius', '8');
        if ($btnRadius !== '8')      $css .= '--jpg-btn-radius: '    . (int)$btnRadius         . 'px;';
        $btnPadX = $g('btn_padding_x', '2');
        if ($btnPadX !== '2')        $css .= '--jpg-btn-pad-x: '     . $btnPadX               . 'rem;';
        $btnPadY = $g('btn_padding_y', '0.75');
        if ($btnPadY !== '0.75')     $css .= '--jpg-btn-pad-y: '     . $btnPadY               . 'rem;';
        $btnWeight = $g('btn_font_weight', '700');
        if ($btnWeight !== '700')    $css .= '--jpg-btn-weight: '    . $btnWeight              . ';';
        $btnFontSize = $g('btn_font_size', '0.95');
        if ($btnFontSize !== '0.95') $css .= '--jpg-btn-font-size: ' . $btnFontSize           . 'rem;';
        if ($g('btn_primary_bg'))    $css .= '--jpg-btn-bg: '        . $g('btn_primary_bg')    . ';';
        if ($g('btn_primary_text'))  $css .= '--jpg-btn-text: '      . $g('btn_primary_text')  . ';';

        $css .= '}';

        // ── Direkte CSS-Regeln (nicht nur Custom Properties) ─────────────────

        // Seiten-Hintergrund
        $bgColor = $g('bg_color');
        if ($bgColor && $bgColor !== '#f8fafc') {
            $css .= '.jpg-public, .jpg-layout-modern, .jpg-layout-compact, .jpg-layout-sidebar { background: ' . $bgColor . '; }';
        }

        // Typografie direkt anwenden
        $fontBody = $g('font_body');
        if ($fontBody && $fontBody !== '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif') {
            $css .= '.jpg-public, .jpg-layout-modern, .jpg-layout-compact, .jpg-layout-sidebar { font-family: ' . $fontBody . '; }';
        }
        $fontHeading = $g('font_heading');
        if ($fontHeading && $fontHeading !== '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif') {
            $css .= '.jpg-public h1, .jpg-public h2, .jpg-public h3, .jpg-public h4 { font-family: ' . $fontHeading . '; }';
        }
        if ($fontSize !== '16') {
            $css .= '.jpg-public { font-size: ' . (int)$fontSize . 'px; }';
        }
        if ($lineHeight !== '1.6') {
            $css .= '.jpg-public { line-height: ' . $lineHeight . '; }';
        }
        if ($headWeight !== '700') {
            $css .= '.jpg-public h1, .jpg-public h2, .jpg-public h3 { font-weight: ' . $headWeight . '; }';
        }
        if ($h1Size !== '1.6') {
            $css .= '.jpg-public h1, .jpg-modern-hero__title { font-size: ' . $h1Size . 'rem; }';
        }
        if ($h2Size !== '1.1') {
            $css .= '.jpg-public h2, .jpg-section h2 { font-size: ' . $h2Size . 'rem; }';
        }

        // Text-Farben direkt anwenden
        $textColor = $g('text_color');
        if ($textColor && $textColor !== '#1e293b') {
            $css .= '.jpg-public, .jpg-public p, .jpg-public li { color: ' . $textColor . '; }';
        }
        $textMuted = $g('text_muted');
        if ($textMuted && $textMuted !== '#64748b') {
            $css .= '.jpg-job-meta span, .jpg-compact-meta span, .jpg-modern-meta-chip { color: ' . $textMuted . '; }';
        }

        // Content-Breite (Single)
        $singleWidth = $g('single_max_width', '900');
        if ($singleWidth !== '900') {
            $css .= '.jpg-public, .jpg-modern-content { max-width: ' . (int)$singleWidth . 'px; }';
        }

        // Content-Breite (Global Fallback)
        $contentWidth = $g('content_max_width', '900');
        if ($contentWidth !== '900' && $singleWidth === '900') {
            $css .= '.jpg-public, .jpg-modern-content { max-width: ' . (int)$contentWidth . 'px; }';
        }

        // Content-Breite (Liste)
        $listWidth = $g('list_max_width', '900');
        if ($listWidth !== '900') {
            $css .= '.jpg-jobs-list { max-width: ' . (int)$listWidth . 'px; }';
        }

        // Header-Farben
        $headerBg = $g('header_bg');
        if ($headerBg && $headerBg !== '#3b82f6') {
            $css .= '.jpg-job-header, .jpg-modern-hero { background: ' . $headerBg . '; }';
        }
        $headerText = $g('header_text');
        if ($headerText && $headerText !== '#ffffff') {
            $css .= '.jpg-job-header, .jpg-job-header h1, .jpg-modern-hero, .jpg-modern-hero__title { color: ' . $headerText . '; }';
        }

        // Gehalts-Badge
        $salaryBg = $g('salary_badge_bg');
        if ($salaryBg && $salaryBg !== '#d1fae5') {
            $css .= '.jpg-salary-badge, .jpg-modern-hero__salary { background: ' . $salaryBg . '; }';
        }
        $salaryText = $g('salary_badge_text');
        if ($salaryText && $salaryText !== '#065f46') {
            $css .= '.jpg-salary-badge, .jpg-modern-hero__salary { color: ' . $salaryText . '; }';
        }

        // Karten
        $cardBg = $g('card_bg');
        if ($cardBg && $cardBg !== '#ffffff') {
            $css .= '.jpg-job-card, .jpg-modern-card, .jpg-aside-box, .jpg-sidebar-sticky { background: ' . $cardBg . '; }';
        }
        $borderColor = $g('border_color');
        if ($borderColor && $borderColor !== '#e2e8f0') {
            $css .= '.jpg-job-card, .jpg-aside-box, .jpg-compact-divider { border-color: ' . $borderColor . '; }';
        }

        // Button-Anpassungen
        if ($btnRadius !== '8') {
            $css .= '.jpg-btn-apply, .jpg-btn, .jpg-modal__btn { border-radius: ' . (int)$btnRadius . 'px; }';
        }
        $btnPrimaryBg = $g('btn_primary_bg');
        if (!empty($btnPrimaryBg)) {
            $css .= '.jpg-btn-apply, .jpg-modal__btn--submit { background: ' . $btnPrimaryBg . '; }';
            $css .= '.jpg-btn-apply:hover, .jpg-modal__btn--submit:hover { background: ' . ($g('primary_dark') ?: $btnPrimaryBg) . '; }';
        }
        $btnPrimaryText = $g('btn_primary_text');
        if ($btnPrimaryText && $btnPrimaryText !== '#ffffff') {
            $css .= '.jpg-btn-apply, .jpg-modal__btn--submit { color: ' . $btnPrimaryText . '; }';
        }
        if ($btnWeight !== '700') {
            $css .= '.jpg-btn-apply, .jpg-btn, .jpg-modal__btn { font-weight: ' . $btnWeight . '; }';
        }
        if ($btnFontSize !== '0.95') {
            $css .= '.jpg-btn-apply, .jpg-btn, .jpg-modal__btn { font-size: ' . $btnFontSize . 'rem; }';
        }
        if ($btnPadX !== '2' || $btnPadY !== '0.75') {
            $css .= '.jpg-btn-apply, .jpg-modal__btn { padding: ' . $btnPadY . 'rem ' . $btnPadX . 'rem; }';
        }

        // Card Shadow
        $cardShadow = $g('card_shadow');
        if ($cardShadow && $cardShadow !== '0 4px 24px rgba(0,0,0,.08)') {
            $css .= '.jpg-job-card, .jpg-modern-card { box-shadow: ' . $cardShadow . '; }';
        }
        $cardShadowHover = $g('card_shadow_hover');
        if ($cardShadowHover && $cardShadowHover !== '0 8px 32px rgba(0,0,0,.12)') {
            $css .= '.jpg-job-card:hover { box-shadow: ' . $cardShadowHover . '; }';
        }

        // Padding
        if ($contentPad !== '2') {
            $css .= '.jpg-job-body, .jpg-job-header, .jpg-modern-card, .jpg-compact-section { padding: ' . $contentPad . 'rem; }';
        }

        // Sidebar Width (Two-Column)
        $sidebarW = $g('sidebar_width', '280');
        if ($sidebarW !== '280') {
            $css .= '.jpg-job-two-col { grid-template-columns: 1fr ' . (int)$sidebarW . 'px; }';
            $css .= '.jpg-sidebar-layout { grid-template-columns: 1fr ' . (int)$sidebarW . 'px; }';
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

        // Listen-Karten
        $listShadow = $g('list_card_shadow');
        if ($listShadow && $listShadow !== '0 1px 3px rgba(0,0,0,.06)') {
            $css .= '.jpg-jobs-list .jpg-list-card { box-shadow: ' . $listShadow . '; }';
        }

        // Custom CSS
        $customCss = $g('custom_css');
        if (!empty($customCss)) {
            $css .= "\n/* Custom CSS */\n" . $customCss;
        }

        return $css;
    }
}
