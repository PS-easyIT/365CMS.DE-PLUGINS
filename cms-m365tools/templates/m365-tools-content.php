<?php
/**
 * Public Partial: M365 Tools content area only.
 *
 * @package CMS_M365TOOLS
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$storedLandingOptions = [];
if (class_exists('CMS_M365CALCULATOR_Settings')) {
    $storedLandingOptions = array_merge(
        CMS_M365CALCULATOR_Settings::global_options('general'),
        CMS_M365CALCULATOR_Settings::global_options('landing'),
        CMS_M365CALCULATOR_Settings::global_options('landing-content'),
        CMS_M365CALCULATOR_Settings::global_options('landing-texts'),
        CMS_M365CALCULATOR_Settings::global_options('landing-layout'),
        CMS_M365CALCULATOR_Settings::global_options('landing-colors'),
        CMS_M365CALCULATOR_Settings::global_options('landing-visibility')
    );
}

$landingOptions = array_merge($storedLandingOptions, is_array($landingOptions ?? null) ? $landingOptions : []);
$groupedTools = is_array($groupedTools ?? null) ? $groupedTools : (class_exists('CMS_M365CALCULATOR_Tool_Registry') ? CMS_M365CALCULATOR_Tool_Registry::grouped_by_category() : []);
$bestPracticeMeta = is_array($bestPracticeMeta ?? null) ? $bestPracticeMeta : [];
$bestPracticeDomains = is_array($bestPracticeDomains ?? null) ? $bestPracticeDomains : [];
$toolReviewMap = is_array($toolReviewMap ?? null) ? $toolReviewMap : [];
$toolCheckMap = is_array($toolCheckMap ?? null) ? $toolCheckMap : [];

$esc = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$cleanSlug = static function (mixed $value): string {
    $slug = strtolower(trim((string) preg_replace('/[^a-z0-9_-]+/i', '-', (string) $value), '-'));

    return $slug !== '' ? $slug : 'weitere-tools';
};
$option = static fn(string $key, string $default = ''): string => trim((string) ($landingOptions[$key] ?? $default));
$textOption = static function (string $key, string $default = '') use ($landingOptions): string {
    return array_key_exists($key, $landingOptions) ? trim((string) $landingOptions[$key]) : $default;
};
$optionBool = static function (string $key, string $default = '1') use ($landingOptions): bool {
    return (string) ($landingOptions[$key] ?? $default) === '1';
};
$optionNumber = static function (string $key, int $default, int $min, int $max) use ($landingOptions): int {
    return max($min, min($max, (int) ($landingOptions[$key] ?? $default)));
};
$optionColor = static function (string $key, string $default) use ($landingOptions): string {
    $value = (string) ($landingOptions[$key] ?? $default);

    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : $default;
};
$safeUrl = static function (mixed $value, string $default = ''): string {
    $url = trim((string) $value);
    if ($url === '') {
        return $default;
    }

    if ((str_starts_with($url, '/') || str_starts_with($url, '#')) && !str_starts_with($url, '//') && !str_contains($url, "\0")) {
        return $url;
    }

    $parts = parse_url($url);
    $scheme = is_array($parts) ? strtolower((string) ($parts['scheme'] ?? '')) : '';

    return in_array($scheme, ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : $default;
};
$renderIcon = static function (string $icon): string {
    if (class_exists('CMS_M365CALCULATOR_Icons')) {
        return CMS_M365CALCULATOR_Icons::svg($icon);
    }

    return '<svg aria-hidden="true" focusable="false" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="4" width="14" height="16" rx="2"/><path d="M8 8h8"/><path d="M8 12h2"/><path d="M14 12h2"/><path d="M8 16h2"/><path d="M14 16h2"/></svg>';
};

$categoryMeta = [
    'copilot' => ['icon' => 'copilot', 'color' => 'var(--m365-tools-c-copilot)', 'bg' => '#f0e9fd', 'summary' => 'Copilot-Eignung, Pilotierung, ROI und KI-Angebote bewerten.'],
    'exchange' => ['icon' => 'mailbox', 'color' => 'var(--m365-tools-c-lizenz)', 'bg' => '#e8f1fe', 'summary' => 'Mailboxen, Archivierung und Exchange-Modernisierung sauber planen.'],
    'lizenzen' => ['icon' => 'license', 'color' => 'var(--m365-tools-c-lizenz)', 'bg' => '#e8f1fe', 'summary' => 'Lizenzmodelle, Add-ons, Audits und Sparpfade schnell einordnen.'],
    'migration' => ['icon' => 'roi', 'color' => 'var(--m365-tools-c-migration)', 'bg' => '#e8f1fe', 'summary' => 'Umzugs- und TCO-Szenarien mit Kosten, Aufwand und Break-even bewerten.'],
    'power-platform' => ['icon' => 'addons', 'color' => 'var(--m365-tools-c-power)', 'bg' => '#fbeee4', 'summary' => 'Power Apps, Automate, Dataverse, Credits und Governance realistisch kalkulieren.'],
    'speicher' => ['icon' => 'storage', 'color' => 'var(--m365-tools-c-speicher)', 'bg' => '#e7eefe', 'summary' => 'SharePoint, OneDrive, Exchange und Backup-Speicherbedarf greifbar machen.'],
    'teams' => ['icon' => 'phone', 'color' => 'var(--m365-tools-c-copilot)', 'bg' => '#f0e9fd', 'summary' => 'Telefonie, PSTN-Modelle und Teams-Phone-Optionen vergleichen.'],
];
$domainMeta = [
    'licensing_cost' => ['icon' => 'license', 'color' => 'var(--m365-tools-c-lizenz)', 'bg' => '#e8f1fe'],
    'identity_access' => ['icon' => 'shield', 'color' => 'var(--m365-tools-c-identitaet)', 'bg' => '#eee9fc'],
    'security_compliance' => ['icon' => 'shield', 'color' => 'var(--m365-tools-c-schutz)', 'bg' => '#e6f6ef'],
    'service_limits' => ['icon' => 'calculator', 'color' => 'var(--m365-tools-c-service)', 'bg' => '#fbeee4'],
    'storage_backup' => ['icon' => 'storage', 'color' => 'var(--m365-tools-c-speicher)', 'bg' => '#e7eefe'],
    'network_performance' => ['icon' => 'roi', 'color' => 'var(--m365-tools-c-netzwerk)', 'bg' => '#e3f4f1'],
    'copilot_ai' => ['icon' => 'copilot', 'color' => 'var(--m365-tools-c-copilot)', 'bg' => '#f0e9fd'],
    'power_platform_ops' => ['icon' => 'addons', 'color' => 'var(--m365-tools-c-power)', 'bg' => '#fbeee4'],
    'migration_operations' => ['icon' => 'roi', 'color' => 'var(--m365-tools-c-migration)', 'bg' => '#e8f1fe'],
];

$visibleGroups = [];
$visibleTools = [];
foreach ($groupedTools as $category => $tools) {
    if (!is_array($tools) || $tools === []) {
        continue;
    }

    $categoryLabel = trim((string) $category) !== '' ? trim((string) $category) : 'Weitere Tools';
    $categorySlug = $cleanSlug($categoryLabel);
    $visibleGroups[$categoryLabel] = $tools;
    foreach ($tools as $tool) {
        if (is_array($tool)) {
            $visibleTools[] = $tool + ['category' => $categoryLabel, 'category_slug' => $categorySlug];
        }
    }
}

$totalTools = count($visibleTools);
$liveTools = count(array_filter($visibleTools, static fn(array $tool): bool => (string) ($tool['status'] ?? 'live') === 'live'));
$reviewCount = count($bestPracticeDomains);
$title = $textOption('landing_title', 'M365 Tools');
$intro = $textOption('landing_intro', 'Eine kuratierte Sammlung für Microsoft-365-Lizenzierung, Kosten, Speicher, Copilot, Telefonie, Migration und Betrieb.');
$primaryButtonLabel = $textOption('landing_primary_button_label', 'Alle Tools durchsuchen ↓');
$primaryButtonUrl = $safeUrl($option('landing_primary_button_url', '#catalog'), '#catalog');
$secondaryButtonLabel = $textOption('landing_secondary_button_label', 'Kontakt aufnehmen');
$secondaryButtonUrl = $safeUrl($option('landing_secondary_button_url', '/kontakt'), '/kontakt');
$searchTitle = $textOption('landing_search_title', 'Tools suchen und filtern');
$searchPlaceholder = $textOption('landing_search_placeholder', 'Nach Tool, Thema oder Kategorie suchen …');
$searchHelp = $textOption('landing_search_help', 'Suche und Kategorie wirken gemeinsam.');
$searchCategoryLabel = $textOption('landing_search_category_label', 'Kategorien');
$allCategoriesLabel = $textOption('landing_all_categories_label', 'Alle');
$categoryOverlineText = $textOption('landing_category_overline_text', 'Tool-Kategorie');
$noResultsTitle = $textOption('landing_no_results_title', 'Keine Tools für deine Auswahl gefunden.');
$noResultsText = $textOption('landing_no_results_text', 'Bitte Suchbegriff anpassen oder einen anderen Kategorie-Chip wählen.');
$statusBetaLabel = $textOption('landing_status_beta_label', 'Beta');
$statusSoonLabel = $textOption('landing_status_soon_label', 'Bald');
$disabledNoteText = $textOption('landing_disabled_note_text', 'Dieses Modul ist vorbereitet und wird bald verfügbar.');
$headerImageUrl = $safeUrl($option('landing_header_image_url', ''), '');
$hasHeaderImage = $headerImageUrl !== '';
$headerImageAlt = $option('landing_header_image_alt', '');
$headerImageLayout = in_array($option('landing_header_image_layout', 'right'), ['right', 'left', 'banner'], true) ? $option('landing_header_image_layout', 'right') : 'right';
$reviewTitle = $textOption('landing_review_title', (string) ($bestPracticeMeta['title'] ?? 'Microsoft 365 Best-Practice-Kompass'));
$reviewIntro = $textOption('landing_review_intro', (string) ($bestPracticeMeta['summary'] ?? 'Quereinstieg für Lizenzierung, Zugriff, Schutz, Servicegrenzen, Netzwerk, Performance, Copilot, Power Platform, Backup und Migration.'));
$showReviewPanel = $optionBool('enable_public_landing_checks', '1') && $optionBool('landing_show_review_panel', '1') && $bestPracticeDomains !== [];
$pageLayout = in_array($option('landing_page_layout', 'wide'), ['normal', 'wide', 'boxed', 'editorial', 'directory'], true) ? $option('landing_page_layout', 'wide') : 'wide';
$headerStyle = in_array($option('landing_header_style', 'plain'), ['plain', 'surface', 'bordered', 'accent', 'inverted'], true) ? $option('landing_header_style', 'plain') : 'plain';
$headerLayout = in_array($option('landing_header_layout', 'split'), ['split', 'stacked', 'compact', 'hero-card', 'editorial'], true) ? $option('landing_header_layout', 'split') : 'split';
$headerAlign = in_array($option('landing_header_alignment', 'left'), ['left', 'center', 'split'], true) ? $option('landing_header_alignment', 'left') : 'left';
$buttonLayout = in_array($option('landing_button_layout', 'inline'), ['inline', 'stacked', 'right'], true) ? $option('landing_button_layout', 'inline') : 'inline';
$searchLayout = in_array($option('landing_search_layout', 'stacked'), ['stacked', 'split-search-left', 'split-search-right'], true) ? $option('landing_search_layout', 'stacked') : 'stacked';
$categoryLayout = in_array($option('landing_category_layout', 'line'), ['line', 'pills', 'cards', 'minimal'], true) ? $option('landing_category_layout', 'line') : 'line';
$toolLayout = in_array($option('landing_tool_layout', 'grid'), ['grid', 'compact-grid', 'list', 'directory', 'feature-first'], true) ? $option('landing_tool_layout', 'grid') : 'grid';
$cardStyle = in_array($option('landing_card_style', 'bordered'), ['bordered', 'quiet', 'flat', 'accent', 'corner-icon'], true) ? $option('landing_card_style', 'bordered') : 'bordered';
$toolButtonStyle = in_array($option('landing_tool_button_style', 'link'), ['link', 'primary', 'secondary', 'minimal'], true) ? $option('landing_tool_button_style', 'link') : 'link';
$density = in_array($option('landing_density', 'comfortable'), ['compact', 'comfortable', 'spacious'], true) ? $option('landing_density', 'comfortable') : 'comfortable';
$layoutWidthDefaults = [
    'normal' => 1040,
    'wide' => 1180,
    'boxed' => 1180,
    'editorial' => 980,
    'directory' => 1280,
];
$contentMaxWidth = $optionNumber('landing_content_max_width', 0, 0, 1600);
if ($contentMaxWidth <= 0) {
    $contentMaxWidth = $layoutWidthDefaults[$pageLayout] ?? 1180;
} else {
    $contentMaxWidth = max(760, $contentMaxWidth);
}
$contentGutter = $optionNumber('landing_content_gutter', 24, 0, 96);
$cardsPerRow = $optionNumber('landing_cards_per_row', 3, 0, 6);
if ($cardsPerRow <= 0) {
    $cardsPerRow = match ($toolLayout) {
        'compact-grid' => 4,
        'list', 'directory' => 1,
        default => 3,
    };
}
$rootClasses = implode(' ', [
    'm365-tools',
    'm365-tools--page-' . $pageLayout,
    'm365-tools--header-' . $headerStyle,
    'm365-tools--header-layout-' . $headerLayout,
    'm365-tools--header-image-' . $headerImageLayout,
    $hasHeaderImage ? 'm365-tools--has-header-image' : 'm365-tools--has-no-header-image',
    'm365-tools--header-align-' . $headerAlign,
    'm365-tools--buttons-' . $buttonLayout,
    'm365-tools--search-' . $searchLayout,
    'm365-tools--categories-' . $categoryLayout,
    'm365-tools--tools-' . $toolLayout,
    'm365-tools--cards-' . $cardStyle,
    'm365-tools--button-' . $toolButtonStyle,
    'm365-tools--density-' . $density,
]);
$cardRadius = $optionNumber('landing_card_radius', 2, 0, 2);
$cardsMinWidth = $optionNumber('landing_cards_min_width', 320, 220, 520);
$sectionGap = $optionNumber('landing_section_gap', 32, 16, 96);
?>
<style>
:root {
    --m365-tools-font-sans: var(--phinit-font-body, "IBM Plex Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif);
    --m365-tools-font-mono: var(--phinit-font-mono, "IBM Plex Mono", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace);
    --m365-tools-navy: var(--phinit-color-surface-alt, #0b1b34);
    --m365-tools-accent: <?php echo $esc($optionColor('landing_color_primary', '#1f6feb')); ?>;
    --m365-tools-accent-2: <?php echo $esc($optionColor('landing_color_accent', '#0f766e')); ?>;
    --m365-tools-accent-dark: color-mix(in srgb, var(--m365-tools-accent) 82%, #07162e 18%);
    --m365-tools-accent-soft: color-mix(in srgb, var(--m365-tools-accent) 10%, transparent);
    --m365-tools-focus-ring: color-mix(in srgb, var(--m365-tools-accent) 46%, transparent);
    --m365-tools-bg: transparent;
    --m365-tools-surface: color-mix(in srgb, <?php echo $esc($optionColor('landing_color_surface', '#ffffff')); ?> 94%, #fff7ed 6%);
    --m365-tools-surface-2: color-mix(in srgb, <?php echo $esc($optionColor('landing_color_surface_alt', '#ffffff')); ?> 84%, #f4ede4 16%);
    --m365-tools-surface-muted: color-mix(in srgb, var(--m365-tools-surface) 84%, #f6efe7 16%);
    --tag-bg: #eef1f6;
    --tag-text: #52617a;
    --tag-border: #e1e6ee;
    --m365-tools-tag-bg: var(--tag-bg, #eef1f6);
    --m365-tools-tag-text: var(--tag-text, #52617a);
    --m365-tools-tag-border: var(--tag-border, #e1e6ee);
    --m365-tools-ink: <?php echo $esc($optionColor('landing_color_text', '#14213d')); ?>;
    --m365-tools-ink-soft: <?php echo $esc($optionColor('landing_color_muted', '#4b5a72')); ?>;
    --m365-tools-ink-faint: color-mix(in srgb, var(--m365-tools-ink-soft) 78%, #ffffff 22%);
    --m365-tools-line: color-mix(in srgb, <?php echo $esc($optionColor('landing_color_border', '#e2e8f0')); ?> 88%, #eadfd2 12%);
    --m365-tools-line-strong: color-mix(in srgb, var(--m365-tools-line) 72%, var(--m365-tools-ink-soft) 28%);
    --m365-tools-c-lizenz: #1f6feb;
    --m365-tools-c-identitaet: #6c4adb;
    --m365-tools-c-schutz: #0f9d72;
    --m365-tools-c-service: #c2602f;
    --m365-tools-c-speicher: #2563eb;
    --m365-tools-c-netzwerk: #0d9488;
    --m365-tools-c-copilot: #7c3aed;
    --m365-tools-c-power: #c2602f;
    --m365-tools-c-migration: #1f6feb;
    --m365-tools-radius: <?php echo (int) $cardRadius; ?>px;
    --m365-tools-radius-lg: <?php echo max(2, (int) $cardRadius + 2); ?>px;
    --m365-tools-radius-pill: 100px;
    --m365-tools-content-max: <?php echo (int) $contentMaxWidth; ?>px;
    --m365-tools-content-gutter: <?php echo (int) $contentGutter; ?>px;
    --m365-tools-card-cols: <?php echo (int) $cardsPerRow; ?>;
    --m365-tools-card-min: <?php echo (int) $cardsMinWidth; ?>px;
    --m365-tools-section-gap: <?php echo (int) $sectionGap; ?>px;
    --m365-tools-edge-gap: 25px;
    --m365-tools-shadow-card: 0 1px 2px rgba(42, 31, 20, 0.035), 0 8px 24px rgba(42, 31, 20, 0.045);
    --m365-tools-shadow-hover: 0 10px 28px rgba(42, 31, 20, 0.085);
}
.m365-tools,
.m365-tools * { box-sizing: border-box; }
.m365-tools { --tag-bg: #eef1f6; --tag-text: #52617a; --tag-border: #e1e6ee; background: transparent; color: var(--m365-tools-ink); font-family: var(--m365-tools-font-sans); line-height: 1.6; margin-bottom: 0; padding: 0 0 min(var(--m365-tools-edge-gap, 25px), 25px); }
.m365-tools > :first-child { margin-top: 0; }
.m365-tools > :last-child { margin-bottom: 0; }
.m365-tools a { color: var(--m365-tools-accent); text-decoration: none; }
.m365-tools svg { display: block; }
.m365-tools [hidden] { display: none !important; }
.m365-tools .sr-only { border: 0; clip: rect(0 0 0 0); height: 1px; margin: -1px; overflow: hidden; padding: 0; position: absolute; white-space: nowrap; width: 1px; }
.m365-tools .wrap { margin: 0 auto; max-width: min(100%, var(--m365-tools-content-max)); padding: 0 var(--m365-tools-content-gutter); width: 100%; }
.m365-tools--page-normal .wrap,
.m365-tools--page-editorial .wrap,
.m365-tools--page-directory .wrap { max-width: min(100%, var(--m365-tools-content-max)); }
.m365-tools--page-boxed .catalog { background: color-mix(in srgb, var(--m365-tools-surface) 82%, transparent); border: 1px solid var(--m365-tools-line); border-radius: var(--m365-tools-radius-lg); padding-bottom: 24px; }
.m365-tools--page-editorial .section-sub,
.m365-tools--page-editorial .cat-sub { max-width: 58ch; }
.m365-tools--page-directory .cat-block { border-top: 1px solid var(--m365-tools-line); }
.m365-tools--page-boxed .hero .wrap,
.m365-tools--header-surface .hero .wrap,
.m365-tools--header-bordered .hero .wrap,
.m365-tools--header-accent .hero .wrap,
.m365-tools--header-inverted .hero .wrap { background: var(--m365-tools-surface-2); border: 1px solid var(--m365-tools-line); border-radius: var(--m365-tools-radius-lg); box-shadow: var(--m365-tools-shadow-card); padding: 26px var(--m365-tools-content-gutter); }
.m365-tools--header-accent .hero .wrap { border-left: 4px solid var(--m365-tools-accent); }
.m365-tools--header-inverted .hero .wrap { background: var(--m365-tools-navy); color: #fff; }
.m365-tools--header-inverted .hero h1,
.m365-tools--header-inverted .lead { color: #fff; }
.m365-tools .hero { padding: 0 0 38px; }
.m365-tools .eyebrow { align-items: center; color: var(--m365-tools-accent); display: inline-flex; font-family: var(--m365-tools-font-mono); font-size: 11px; gap: 7px; letter-spacing: .12em; margin: 0 0 14px; text-transform: uppercase; }
.m365-tools .eyebrow::before { background: var(--m365-tools-accent); border-radius: 2px; content: ""; height: 2px; width: 22px; }
.m365-tools .hero-grid { display: grid; gap: 28px; }
.m365-tools--header-layout-stacked .hero-grid,
.m365-tools--header-layout-compact .hero-grid,
.m365-tools--header-layout-editorial .hero-grid { grid-template-columns: 1fr; }
.m365-tools--header-layout-compact .hero { padding-bottom: 22px; }
.m365-tools--header-layout-compact h1 { font-size: clamp(1.55rem, 5vw, 2rem); margin-bottom: 8px; }
.m365-tools--header-layout-compact .lead { font-size: 15px; margin-bottom: 16px; max-width: 64ch; }
.m365-tools--header-layout-hero-card .hero .wrap { background: var(--m365-tools-surface-2); border: 1px solid var(--m365-tools-line); border-radius: var(--m365-tools-radius-lg); box-shadow: var(--m365-tools-shadow-card); padding: 30px var(--m365-tools-content-gutter); }
.m365-tools--header-layout-editorial .hero header { border-left: 3px solid var(--m365-tools-accent); padding-left: 18px; }
.m365-tools--header-layout-editorial .lead { max-width: 46ch; }
.m365-tools .header-visual { background: var(--m365-tools-surface); border: 1px solid var(--m365-tools-line); border-radius: var(--m365-tools-radius-lg); box-shadow: var(--m365-tools-shadow-card); margin: 0; overflow: hidden; }
.m365-tools .header-visual img { aspect-ratio: 16 / 10; display: block; height: 100%; object-fit: cover; width: 100%; }
.m365-tools--has-no-header-image .hero-grid { min-height: 0; }
.m365-tools--header-image-banner .header-visual img { aspect-ratio: 21 / 8; }
.m365-tools--header-align-center .hero { text-align: center; }
.m365-tools--header-align-center .eyebrow,
.m365-tools--header-align-center .actions { justify-content: center; }
.m365-tools h1,
.m365-tools h2,
.m365-tools h3,
.m365-tools p { margin-top: 0; }
.m365-tools h1 { color: var(--m365-tools-ink); font-size: clamp(2rem, 8vw, 2.5rem); font-weight: 700; letter-spacing: -.02em; line-height: 1.15; margin-bottom: 12px; }
.m365-tools .lead { color: var(--m365-tools-ink-soft); font-size: 17px; margin-bottom: 28px; max-width: 56ch; }
.m365-tools--header-align-center .lead { margin-left: auto; margin-right: auto; }
.m365-tools .actions { display: flex; flex-wrap: wrap; gap: 10px; }
.m365-tools--buttons-stacked .actions { align-items: flex-start; flex-direction: column; }
.m365-tools--buttons-right .actions { justify-content: flex-end; }
.m365-tools .cta-btn { align-items: center; background: var(--m365-tools-accent); border: 1px solid var(--m365-tools-accent); border-radius: var(--m365-tools-radius-pill); color: #fff; display: inline-flex; font-size: 14px; font-weight: 600; gap: 8px; min-height: 44px; padding: 10px 22px; transition: background-color .15s ease, border-color .15s ease, transform .1s ease; }
.m365-tools .cta-btn--secondary { background: var(--m365-tools-surface); color: var(--m365-tools-ink); border-color: var(--m365-tools-line-strong); }
.m365-tools .cta-btn:hover,
.m365-tools .cta-btn:focus-visible { background: var(--m365-tools-accent-dark); border-color: var(--m365-tools-accent-dark); color: #fff; }
.m365-tools .stats { display: grid; gap: 14px; grid-template-columns: repeat(3, minmax(0, 1fr)); }
.m365-tools .stat { background: var(--m365-tools-surface); border: 1px solid var(--m365-tools-line); border-radius: var(--m365-tools-radius); box-shadow: var(--m365-tools-shadow-card); min-width: 0; padding: 16px; }
.m365-tools .stat .num { font-size: clamp(1.6rem, 7vw, 1.875rem); font-weight: 700; letter-spacing: -.02em; line-height: 1; }
.m365-tools .stat .lbl { color: var(--m365-tools-ink-faint); font-size: 11px; letter-spacing: .06em; margin-top: 6px; text-transform: uppercase; }
.m365-tools .toolbar { padding: 8px 0 4px; }
.m365-tools .search-label { color: var(--m365-tools-ink-soft); display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; }
.m365-tools .search-box { align-items: center; background: var(--m365-tools-surface); border: 1px solid var(--m365-tools-line-strong); border-radius: var(--m365-tools-radius); display: flex; gap: 12px; min-height: 50px; padding: 0 16px; transition: border-color .15s ease, box-shadow .15s ease; }
.m365-tools .search-box:focus-within { border-color: var(--m365-tools-accent); box-shadow: 0 0 0 3px var(--m365-tools-focus-ring), var(--m365-tools-shadow-card); }
.m365-tools .search-box svg { color: var(--m365-tools-ink-faint); flex: 0 0 auto; height: 20px; width: 20px; }
.m365-tools .search-box input { background: transparent; border: 0; color: var(--m365-tools-ink); flex: 1 1 auto; font: inherit; font-size: 15px; min-height: 48px; min-width: 0; outline: 0; }
.m365-tools .search-box input:focus-visible { border-radius: 8px; outline: 2px solid var(--m365-tools-focus-ring); outline-offset: 3px; }
.m365-tools--search-split-search-left .toolbar,
.m365-tools--search-split-search-right .toolbar { margin-bottom: min(22px, var(--m365-tools-edge-gap)); padding: 0; }
.m365-tools--search-split-search-left .toolbar .wrap,
.m365-tools--search-split-search-right .toolbar .wrap { background: linear-gradient(180deg, color-mix(in srgb, var(--m365-tools-surface) 94%, #ffffff 6%), color-mix(in srgb, var(--m365-tools-surface-muted) 84%, transparent)); border: 1px solid var(--m365-tools-line); border-radius: var(--m365-tools-radius-lg); box-shadow: var(--m365-tools-shadow-card); display: grid; gap: 8px 18px; overflow: hidden; padding: 16px max(18px, var(--m365-tools-content-gutter)); position: relative; }
.m365-tools--search-split-search-left .search-label,
.m365-tools--search-split-search-right .search-label { color: var(--m365-tools-ink); font-size: 14px; grid-area: label; letter-spacing: .01em; margin-bottom: 0; }
.m365-tools--search-split-search-left .search-box,
.m365-tools--search-split-search-right .search-box { background: #fff; border-color: var(--m365-tools-line-strong); box-shadow: 0 1px 2px rgba(42, 31, 20, .035); gap: 10px; grid-area: search; min-height: 46px; padding: 0 12px; }
.m365-tools--search-split-search-left .search-box svg,
.m365-tools--search-split-search-right .search-box svg { height: 18px; width: 18px; }
.m365-tools--search-split-search-left .search-box input,
.m365-tools--search-split-search-right .search-box input { font-size: 14px; min-height: 44px; }
.m365-tools--search-split-search-left .toolbar .section-tag,
.m365-tools--search-split-search-right .toolbar .section-tag { background: color-mix(in srgb, var(--m365-tools-accent) 6%, transparent); border: 1px solid color-mix(in srgb, var(--m365-tools-accent) 12%, transparent); border-radius: var(--m365-tools-radius); color: var(--m365-tools-ink-soft); font-size: 10px; grid-area: help; letter-spacing: .05em; line-height: 1.35; margin: 0; padding: 6px 8px; }
.m365-tools--search-split-search-left .filters,
.m365-tools--search-split-search-right .filters { align-content: start; align-self: stretch; background: color-mix(in srgb, var(--m365-tools-surface) 72%, transparent); gap: 8px; grid-area: cats; margin-top: 0; padding-top: 2px; }
.m365-tools--search-split-search-left .filters::before,
.m365-tools--search-split-search-right .filters::before { color: var(--m365-tools-ink); content: attr(data-label); display: block; flex: 0 0 100%; font-size: 13px; font-weight: 700; letter-spacing: .02em; margin: 0 0 2px; }
.m365-tools--search-split-search-left .filters[data-label=""]::before,
.m365-tools--search-split-search-right .filters[data-label=""]::before { content: none; display: none; margin: 0; }
.m365-tools--search-split-search-left .chip,
.m365-tools--search-split-search-right .chip { background: #fff; border-color: var(--m365-tools-line); box-shadow: 0 1px 2px rgba(42, 31, 20, .03); }
.m365-tools .filters { display: flex; flex-wrap: wrap; gap: 9px; margin-top: 16px; }
.m365-tools .chip { background: var(--m365-tools-surface); border: 1px solid var(--m365-tools-line-strong); border-radius: var(--m365-tools-radius-pill); color: var(--m365-tools-ink-soft); cursor: pointer; font: inherit; font-size: 13.5px; font-weight: 500; min-height: 38px; padding: 7px 16px; transition: background-color .15s ease, border-color .15s ease, color .15s ease; }
.m365-tools--categories-line .filters { border-bottom: 1px solid var(--m365-tools-line); gap: 18px; }
.m365-tools--categories-line .chip { background: transparent; border-color: transparent; border-radius: 0; margin-bottom: -1px; padding: 9px 0 10px; }
.m365-tools--categories-line .chip.active { background: transparent; border-bottom-color: var(--m365-tools-accent); color: var(--m365-tools-accent); }
.m365-tools--categories-cards .filters { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }
.m365-tools--categories-cards .chip { align-items: center; border-radius: var(--m365-tools-radius-lg); box-shadow: var(--m365-tools-shadow-card); justify-content: space-between; min-height: 58px; padding: 12px 14px; text-align: left; }
.m365-tools--categories-cards .chip.active { box-shadow: var(--m365-tools-shadow-hover); }
.m365-tools--categories-minimal .filters { gap: 14px; }
.m365-tools--categories-minimal .chip { background: transparent; border-color: transparent; color: var(--m365-tools-ink-faint); min-height: 32px; padding: 4px 0; }
.m365-tools--categories-minimal .chip.active { background: transparent; border-color: transparent; color: var(--m365-tools-accent); }
.m365-tools--search-split-search-left .filters,
.m365-tools--search-split-search-right .filters { display: flex; flex-wrap: wrap; gap: 8px; }
.m365-tools--search-split-search-left .chip,
.m365-tools--search-split-search-right .chip { align-items: center; background: #fff; border: 1px solid var(--m365-tools-line); border-radius: var(--m365-tools-radius-pill); box-shadow: 0 1px 2px rgba(42, 31, 20, .03); font-size: 12.5px; justify-content: center; min-height: 32px; padding: 5px 12px; text-align: left; }
.m365-tools--search-split-search-left.m365-tools--categories-pills .chip,
.m365-tools--search-split-search-right.m365-tools--categories-pills .chip { border-radius: var(--m365-tools-radius-pill); }
.m365-tools--search-split-search-left.m365-tools--categories-line .filters,
.m365-tools--search-split-search-right.m365-tools--categories-line .filters { align-items: flex-end; gap: 16px; }
.m365-tools--search-split-search-left.m365-tools--categories-line .chip,
.m365-tools--search-split-search-right.m365-tools--categories-line .chip { background: transparent; border: 0; border-bottom: 1px solid transparent; border-radius: 0; box-shadow: none; color: var(--m365-tools-ink-soft); min-height: 30px; padding: 5px 0 7px; }
.m365-tools--search-split-search-left.m365-tools--categories-cards .filters,
.m365-tools--search-split-search-right.m365-tools--categories-cards .filters { display: grid; gap: 10px; grid-template-columns: repeat(auto-fit, minmax(122px, 1fr)); }
.m365-tools--search-split-search-left.m365-tools--categories-cards .filters::before,
.m365-tools--search-split-search-right.m365-tools--categories-cards .filters::before { grid-column: 1 / -1; }
.m365-tools--search-split-search-left.m365-tools--categories-cards .chip,
.m365-tools--search-split-search-right.m365-tools--categories-cards .chip { border-radius: var(--m365-tools-radius-lg); box-shadow: var(--m365-tools-shadow-card); justify-content: space-between; min-height: 44px; padding: 10px 12px; }
.m365-tools--search-split-search-left.m365-tools--categories-minimal .filters,
.m365-tools--search-split-search-right.m365-tools--categories-minimal .filters { gap: 14px; }
.m365-tools--search-split-search-left.m365-tools--categories-minimal .chip,
.m365-tools--search-split-search-right.m365-tools--categories-minimal .chip { background: transparent; border-color: transparent; border-radius: 0; box-shadow: none; color: var(--m365-tools-ink-faint); min-height: 28px; padding: 3px 0; }
.m365-tools .chip:hover,
.m365-tools .chip:focus-visible { border-color: var(--m365-tools-accent); color: var(--m365-tools-accent); }
.m365-tools .chip:focus-visible { box-shadow: none; outline: 3px solid var(--m365-tools-focus-ring); outline-offset: 3px; }
.m365-tools .chip .cnt { font-family: var(--m365-tools-font-mono); font-size: 11px; margin-left: 5px; opacity: .62; }
.m365-tools .chip.active { background: var(--m365-tools-accent); border-color: var(--m365-tools-accent); color: #fff; }
.m365-tools--categories-line .chip.active { background: transparent; border-color: transparent; border-bottom-color: var(--m365-tools-accent); color: var(--m365-tools-accent); }
.m365-tools--categories-minimal .chip.active { background: transparent; border-color: transparent; color: var(--m365-tools-accent); }
.m365-tools .section { padding: var(--m365-tools-section-gap) 0 24px; }
.m365-tools .section-head { align-items: baseline; display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 6px; }
.m365-tools .section-head h2,
.m365-tools .cat-head h2 { color: var(--m365-tools-ink); font-size: 24px; font-weight: 700; letter-spacing: -.01em; margin: 0; }
.m365-tools .section-tag { color: var(--m365-tools-ink-faint); font-family: var(--m365-tools-font-mono); font-size: 11px; letter-spacing: .1em; text-transform: uppercase; }
.m365-tools .section-sub { color: var(--m365-tools-ink-soft); font-size: 15px; margin-bottom: 22px; max-width: 82ch; }
.m365-tools .compass { display: grid; gap: 10px; grid-template-columns: 1fr; }
.m365-tools .compass-card { background: color-mix(in srgb, var(--m365-tools-surface-muted) 76%, transparent); border: 1px solid color-mix(in srgb, var(--m365-tools-line) 72%, transparent); border-radius: var(--m365-tools-radius); box-shadow: none; min-height: 116px; overflow: hidden; padding: 16px 58px 16px 16px; position: relative; transition: border-color .15s ease, transform .15s ease; }
.m365-tools .compass-card::after { border-left: 58px solid transparent; border-top: 58px solid var(--domain-bg, var(--m365-tools-surface-2)); content: ""; pointer-events: none; position: absolute; right: 0; top: 0; }
.m365-tools .compass-card:hover { border-color: var(--m365-tools-line); transform: translateY(-1px); }
.m365-tools .compass-card .compass-corner,
.m365-tools .cat-head .badge,
.m365-tools .card-ico { align-items: center; display: flex; flex: 0 0 auto; justify-content: center; }
.m365-tools .compass-card .compass-corner { color: var(--domain-color, var(--m365-tools-accent)); height: 24px; position: absolute; right: 6px; top: 6px; width: 24px; z-index: 1; }
.m365-tools .compass-card .compass-corner svg { height: 17px; width: 17px; }
.m365-tools .compass-card .t { color: var(--m365-tools-ink); font-size: 13.5px; font-weight: 600; margin-bottom: 3px; }
.m365-tools .compass-card .d { color: var(--m365-tools-ink-soft); font-size: 12px; line-height: 1.5; margin-bottom: 0; }
.m365-tools .catalog { display: grid; gap: 18px; }
.m365-tools .cat-block { padding: 28px 0 0; scroll-margin-top: 80px; }
.m365-tools .cat-head { align-items: center; display: flex; gap: 12px; margin-bottom: 4px; }
.m365-tools .cat-head .badge { border-radius: 9px; height: 32px; width: 32px; }
.m365-tools .cat-head .badge svg { height: 18px; width: 18px; }
.m365-tools .cat-head h2 { font-size: 22px; }
.m365-tools .modcount { color: var(--m365-tools-ink-faint); font-family: var(--m365-tools-font-mono); font-size: 11px; letter-spacing: .08em; margin-left: auto; text-transform: uppercase; white-space: nowrap; }
.m365-tools .cat-sub { color: var(--m365-tools-ink-soft); font-size: 14.5px; margin-bottom: 20px; padding-left: 0; }
.m365-tools .tool-grid { display: grid; gap: 16px; grid-template-columns: 1fr; }
.m365-tools--tools-list .tool-grid,
.m365-tools--tools-directory .tool-grid { grid-template-columns: 1fr; }
.m365-tools--tools-directory .tool-grid { border-top: 1px solid var(--m365-tools-line); gap: 0; }
.m365-tools--tools-compact-grid .tool-grid { gap: 12px; }
.m365-tools .cat-block[data-count="1"] .tool-grid { background: transparent; border: 0; box-shadow: none; padding: 0; }
.m365-tools .tool-card { background: var(--m365-tools-surface); border: 1px solid var(--m365-tools-line); border-radius: var(--m365-tools-radius-lg); box-shadow: var(--m365-tools-shadow-card); display: flex; flex-direction: column; min-width: 0; padding: 22px; position: relative; transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
.m365-tools--density-compact .tool-card { padding: 16px; }
.m365-tools--density-spacious .tool-card { padding: 28px; }
.m365-tools--cards-flat .tool-card,
.m365-tools--cards-quiet .tool-card { box-shadow: none; }
.m365-tools--cards-quiet .tool-card { background: color-mix(in srgb, var(--m365-tools-surface) 62%, transparent); border-color: color-mix(in srgb, var(--m365-tools-line) 70%, transparent); }
.m365-tools--cards-flat .tool-card { background: transparent; border-color: var(--m365-tools-line); }
.m365-tools--cards-accent .tool-card { border-left: 3px solid var(--m365-tools-accent); }
.m365-tools--cards-corner-icon .tool-card { overflow: hidden; padding-right: 58px; }
.m365-tools--cards-corner-icon .tool-card::after { border-left: 68px solid transparent; border-top: 68px solid color-mix(in srgb, var(--m365-tools-accent) 12%, var(--m365-tools-surface-2)); content: ""; pointer-events: none; position: absolute; right: 0; top: 0; z-index: 0; }
.m365-tools--cards-corner-icon .tool-card .card-head { padding-right: 0; }
.m365-tools--cards-corner-icon .tool-card .card-ico { background: transparent; color: var(--m365-tools-accent); height: 28px; position: absolute; right: 7px; top: 7px; width: 28px; z-index: 1; }
.m365-tools--cards-corner-icon .tool-card .card-ico svg { height: 18px; width: 18px; }
.m365-tools--cards-corner-icon .tool-card .flag { right: 12px; top: 56px; z-index: 2; }
.m365-tools--cards-corner-icon .tool-card h3 { padding-right: 10px; }
.m365-tools--tools-compact-grid .tool-card { padding: 16px; }
.m365-tools--tools-list .tool-card { align-items: start; display: grid; gap: 8px 20px; grid-template-columns: minmax(0, 1fr) auto; }
.m365-tools--tools-list .tool-card .card-head,
.m365-tools--tools-list .tool-card .desc,
.m365-tools--tools-list .tool-card .tags,
.m365-tools--tools-list .tool-card .checks { grid-column: 1; }
.m365-tools--tools-list .tool-card .open-link,
.m365-tools--tools-list .tool-card .disabled-note { grid-column: 2; grid-row: 1 / span 4; margin-top: 0; }
.m365-tools--tools-directory .tool-card { background: transparent; border-width: 0 0 1px; border-radius: 0; box-shadow: none; display: grid; gap: 8px 20px; grid-template-columns: minmax(0, 1fr) auto; padding: 16px 0; }
.m365-tools--tools-directory .tool-card:hover,
.m365-tools--tools-directory .tool-card:focus-within { box-shadow: none; transform: none; }
.m365-tools--tools-directory .tool-card .open-link,
.m365-tools--tools-directory .tool-card .disabled-note { grid-column: 2; grid-row: 1 / span 4; margin-top: 0; }
.m365-tools--tools-feature-first .cat-block .tool-card:first-child { border-left: 4px solid var(--m365-tools-accent); grid-column: 1 / -1; padding: 28px; }
.m365-tools--tools-feature-first .cat-block .tool-card:first-child .card-ico { height: 48px; width: 48px; }
.m365-tools .cat-block[data-count="1"] .tool-card { width: 100%; }
.m365-tools .tool-card:hover,
.m365-tools .tool-card:focus-within { border-color: var(--m365-tools-line-strong); box-shadow: var(--m365-tools-shadow-hover); transform: translateY(-3px); }
.m365-tools .card-head { align-items: center; display: flex; gap: 12px; margin-bottom: 12px; min-width: 0; }
.m365-tools .card-ico { background: var(--m365-tools-accent-soft); border-radius: var(--m365-tools-radius); color: var(--m365-tools-accent); height: 38px; width: 38px; }
.m365-tools .card-ico svg { height: 20px; width: 20px; }
.m365-tools .tool-card h3 { color: var(--m365-tools-ink); font-size: 16px; font-weight: 600; line-height: 1.35; margin: 0; padding-right: 64px; }
.m365-tools .tool-card h3 a { color: inherit; }
.m365-tools .desc { color: var(--m365-tools-ink-soft); font-size: 13.5px; line-height: 1.55; margin-bottom: 16px; }
.m365-tools .tags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px; }
.m365-tools .tag { align-items: center; background: var(--m365-tools-tag-bg); border: 1px solid var(--m365-tools-tag-border); border-radius: var(--m365-tools-radius-pill); box-shadow: none; color: var(--m365-tools-tag-text); display: inline-flex; font-size: 11px; font-weight: 500; height: auto; letter-spacing: .01em; line-height: 1.25; min-height: 22px; padding: 3px 10px; }
.m365-tools .checks { color: var(--m365-tools-ink-soft); font-size: 12px; margin: 0 0 16px 18px; padding: 0; }
.m365-tools .open-link { align-items: center; align-self: flex-start; color: var(--m365-tools-accent-dark); display: inline-flex; font-size: 15px; font-weight: 700; gap: 7px; margin-top: auto; min-height: 38px; padding: 5px 2px; text-decoration: underline; text-decoration-color: color-mix(in srgb, var(--m365-tools-accent) 36%, transparent); text-decoration-thickness: 2px; text-underline-offset: 5px; transition: color .15s ease, text-decoration-color .15s ease; }
.m365-tools--button-primary .open-link,
.m365-tools--button-secondary .open-link { border: 1px solid var(--m365-tools-accent); border-radius: var(--m365-tools-radius-pill); padding: 8px 16px; text-decoration: none; }
.m365-tools--button-primary .open-link { background: var(--m365-tools-accent); color: #fff; }
.m365-tools--button-minimal .open-link { text-decoration: none; }
.m365-tools .open-link:hover,
.m365-tools .open-link:focus-visible { color: var(--m365-tools-accent); text-decoration-color: currentColor; }
.m365-tools--button-primary .open-link:hover,
.m365-tools--button-primary .open-link:focus-visible { color: #fff; background: var(--m365-tools-accent-dark); }
.m365-tools .open-link svg { height: 16px; transition: transform .15s ease; width: 16px; }
.m365-tools .tool-card:hover .open-link svg,
.m365-tools .open-link:focus-visible svg { transform: translateX(3px); }
.m365-tools .open-link:focus-visible { border-radius: 8px; box-shadow: none; outline: 3px solid var(--m365-tools-focus-ring); outline-offset: 3px; }
.m365-tools .flag { border-radius: var(--m365-tools-radius-pill); font-family: var(--m365-tools-font-mono); font-size: 10px; font-weight: 500; letter-spacing: .06em; padding: 4px 10px; position: absolute; right: 18px; text-transform: uppercase; top: 18px; }
.m365-tools .flag.beta { background: #fff4e0; color: #9b5c00; }
.m365-tools .flag.soon { background: #eef1f6; color: #52617a; }
.m365-tools .disabled-note { color: var(--m365-tools-ink-faint); font-size: 12px; margin-top: auto; }
.m365-tools .no-results { border: 1px dashed var(--m365-tools-line-strong); border-radius: var(--m365-tools-radius-lg); color: var(--m365-tools-ink-soft); margin-top: 34px; padding: 34px 22px; text-align: center; }
.m365-tools .no-results-title { color: var(--m365-tools-ink); font-weight: 700; margin-bottom: 4px; }
.m365-tools a:focus-visible,
.m365-tools button:focus-visible,
.m365-tools input:focus-visible { outline: 3px solid var(--m365-tools-focus-ring); outline-offset: 2px; }
@media (min-width: 520px) { .m365-tools .compass { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (min-width: 768px) {
    .m365-tools .wrap { padding: 0 var(--m365-tools-content-gutter); }
    .m365-tools .hero-grid { align-items: end; grid-template-columns: minmax(0, 1fr) auto; gap: 32px; }
    .m365-tools--has-header-image .hero-grid { align-items: center; grid-template-columns: minmax(0, 1fr) minmax(240px, 360px); }
    .m365-tools--has-header-image .stats { grid-column: 1 / -1; }
    .m365-tools--header-image-left .header-visual { order: -1; }
    .m365-tools--header-image-banner .hero-grid { grid-template-columns: 1fr; }
    .m365-tools--header-image-banner .header-visual { grid-column: 1 / -1; order: 2; }
    .m365-tools--header-image-banner .stats { order: 3; }
    .m365-tools--search-split-search-left .toolbar .wrap { grid-template-areas: "label cats" "search cats" "help cats"; grid-template-columns: minmax(240px, .82fr) minmax(0, 1.18fr); }
    .m365-tools--search-split-search-right .toolbar .wrap { grid-template-areas: "cats label" "cats search" "cats help"; grid-template-columns: minmax(0, 1.18fr) minmax(240px, .82fr); }
    .m365-tools--search-split-search-left .filters { border-left: 1px solid var(--m365-tools-line); padding-left: 18px; }
    .m365-tools--search-split-search-right .filters { border-right: 1px solid var(--m365-tools-line); padding-right: 18px; }
    .m365-tools .stats { display: flex; }
    .m365-tools .stat { min-width: 110px; padding: 16px 22px; }
    .m365-tools .compass { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .m365-tools .tool-grid,
    .m365-tools--tools-compact-grid .tool-grid { grid-template-columns: repeat(var(--section-card-cols, var(--m365-tools-card-cols)), minmax(0, 1fr)); }
    .m365-tools--tools-list .tool-grid,
    .m365-tools--tools-directory .tool-grid { grid-template-columns: 1fr; }
    .m365-tools .cat-block[data-count="1"] .tool-grid { grid-template-columns: 1fr; }
}
@media (max-width: 767px) {
    .m365-tools--search-split-search-left .toolbar .wrap,
    .m365-tools--search-split-search-right .toolbar .wrap { display: block; }
    .m365-tools--search-split-search-left .filters,
    .m365-tools--search-split-search-right .filters { border-top: 1px solid var(--m365-tools-line); margin-top: 16px; padding-top: 14px; }
}
@media (prefers-reduced-motion: reduce) { .m365-tools, .m365-tools * { scroll-behavior: auto !important; transition: none !important; } }
</style>

<section class="<?php echo $esc($rootClasses); ?>" id="m365tools-landing" data-m365-tools <?php echo $optionBool('landing_show_header_title', '1') && $title !== '' ? 'aria-labelledby="m365-tools-title"' : 'aria-label="M365 Tools"'; ?>>
    <section class="hero" <?php echo $optionBool('landing_show_header_title', '1') && $title !== '' ? 'aria-labelledby="m365-tools-title"' : 'aria-label="M365 Tools Übersicht"'; ?>>
        <div class="wrap">
            <?php if ($optionBool('landing_show_header_overline', '1')): ?>
            <p class="eyebrow"><?php echo $esc($option('landing_overline', 'Rechner & Tools')); ?></p>
            <?php endif; ?>
            <div class="hero-grid">
                <header>
                    <?php if ($optionBool('landing_show_header_title', '1') && $title !== ''): ?>
                    <h1 id="m365-tools-title"<?php echo $optionBool('landing_show_header_title', '1') ? '' : ' class="sr-only"'; ?>><?php echo $esc($title); ?></h1>
                    <?php endif; ?>
                    <?php if ($optionBool('landing_show_header_intro', '1') && $intro !== ''): ?>
                    <p class="lead"><?php echo $esc($intro); ?></p>
                    <?php endif; ?>
                    <?php if ($optionBool('landing_show_header_buttons', '1') && (($primaryButtonLabel !== '' && $primaryButtonUrl !== '') || ($secondaryButtonLabel !== '' && $secondaryButtonUrl !== ''))): ?>
                    <nav class="actions" aria-label="Landingpage Aktionen">
                        <?php if ($primaryButtonLabel !== '' && $primaryButtonUrl !== ''): ?>
                        <a class="cta-btn" href="<?php echo $esc($primaryButtonUrl); ?>"><?php echo $esc($primaryButtonLabel); ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                        <?php endif; ?>
                        <?php if ($secondaryButtonLabel !== '' && $secondaryButtonUrl !== ''): ?>
                        <a class="cta-btn cta-btn--secondary" href="<?php echo $esc($secondaryButtonUrl); ?>"><?php echo $esc($secondaryButtonLabel); ?></a>
                        <?php endif; ?>
                    </nav>
                    <?php endif; ?>
                </header>
                <?php if ($hasHeaderImage): ?>
                <figure class="header-visual">
                    <img src="<?php echo $esc($headerImageUrl); ?>" alt="<?php echo $esc($headerImageAlt); ?>" width="720" height="450" decoding="async">
                </figure>
                <?php endif; ?>
                <?php if ($optionBool('landing_show_facts', '1')): ?>
                <dl class="stats" aria-label="M365 Tools Kennzahlen">
                    <?php if ($optionBool('landing_show_fact_modules', '1')): ?><div class="stat"><dd class="num"><?php echo (int) $totalTools; ?></dd><dt class="lbl">Module</dt></div><?php endif; ?>
                    <?php if ($optionBool('landing_show_fact_live', '1')): ?><div class="stat"><dd class="num"><?php echo (int) $liveTools; ?></dd><dt class="lbl">Live</dt></div><?php endif; ?>
                    <?php if ($optionBool('landing_show_fact_reviews', '1')): ?><div class="stat"><dd class="num"><?php echo (int) $reviewCount; ?></dd><dt class="lbl">Review</dt></div><?php endif; ?>
                </dl>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="toolbar" <?php echo $searchTitle !== '' ? 'aria-labelledby="m365-tools-filter-title"' : 'aria-label="Tools suchen"'; ?>>
        <div class="wrap">
            <?php if ($searchTitle !== ''): ?>
            <h2 id="m365-tools-filter-title" class="search-label"><?php echo $esc($searchTitle); ?></h2>
            <?php endif; ?>
            <label class="search-box" for="m365ToolsSearch">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <input type="search" id="m365ToolsSearch" autocomplete="off" placeholder="<?php echo $esc($searchPlaceholder); ?>"<?php echo $searchHelp !== '' ? ' aria-describedby="m365-tools-search-help"' : ''; ?>>
            </label>
            <?php if ($searchHelp !== ''): ?>
            <p id="m365-tools-search-help" class="section-tag"><?php echo $esc($searchHelp); ?></p>
            <?php endif; ?>
            <?php if ($optionBool('landing_show_category_nav', '1')): ?>
            <nav class="filters" aria-label="M365 Tool Kategorien" data-label="<?php echo $esc($searchCategoryLabel); ?>">
                <?php if ($allCategoriesLabel !== '' || $optionBool('landing_show_category_counts', '1')): ?><button type="button" class="chip active" data-cat="all" aria-pressed="true"><?php echo $esc($allCategoriesLabel); ?><?php if ($optionBool('landing_show_category_counts', '1')): ?> <span class="cnt"><?php echo (int) $totalTools; ?></span><?php endif; ?></button><?php endif; ?>
                <?php foreach ($visibleGroups as $categoryLabel => $tools): ?>
                <?php $categorySlug = $cleanSlug($categoryLabel); $displayCategoryLabel = $textOption('landing_category_label_' . $categorySlug, (string) $categoryLabel); ?>
                <?php if ($displayCategoryLabel !== '' || $optionBool('landing_show_category_counts', '1')): ?><button type="button" class="chip" data-cat="<?php echo $esc($categorySlug); ?>" aria-pressed="false"><?php echo $esc($displayCategoryLabel); ?><?php if ($optionBool('landing_show_category_counts', '1')): ?> <span class="cnt"><?php echo (int) count($tools); ?></span><?php endif; ?></button><?php endif; ?>
                <?php endforeach; ?>
            </nav>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($showReviewPanel): ?>
    <section class="section" id="kompass" <?php echo $reviewTitle !== '' ? 'aria-labelledby="m365-kompass-title"' : 'aria-label="Best-Practice-Kompass"'; ?>>
        <div class="wrap">
            <div class="section-head">
                <?php if ($reviewTitle !== ''): ?>
                <h2 id="m365-kompass-title"><?php echo $esc($reviewTitle); ?></h2>
                <?php endif; ?>
                <?php $reviewOverline = $textOption('landing_review_overline', 'Querschnittsreview'); ?>
                <?php if ($reviewOverline !== ''): ?><span class="section-tag"><?php echo $esc($reviewOverline); ?></span><?php endif; ?>
            </div>
            <?php if ($reviewIntro !== ''): ?><p class="section-sub"><?php echo $esc($reviewIntro); ?></p><?php endif; ?>
            <div class="compass">
                <?php foreach ($bestPracticeDomains as $domainKey => $domain): ?>
                <?php
                if (!is_array($domain)) {
                    continue;
                }
                $visual = $domainMeta[(string) $domainKey] ?? ['icon' => 'calculator', 'color' => 'var(--m365-tools-accent)', 'bg' => 'var(--m365-tools-surface-2)'];
                $domainOptionKey = $cleanSlug((string) $domainKey);
                $domainLabel = $textOption('landing_review_domain_label_' . $domainOptionKey, (string) ($domain['label'] ?? $domainKey));
                $domainSummary = $textOption('landing_review_domain_summary_' . $domainOptionKey, (string) ($domain['summary'] ?? ''));
                if ($domainLabel === '' && $domainSummary === '') {
                    continue;
                }
                ?>
                <article class="compass-card" style="--domain-bg:<?php echo $esc($visual['bg']); ?>;--domain-color:<?php echo $esc($visual['color']); ?>;">
                    <span class="compass-corner" aria-hidden="true"><?php echo $renderIcon((string) $visual['icon']); ?></span>
                    <?php if ($domainLabel !== ''): ?><h3 class="t"><?php echo $esc($domainLabel); ?></h3><?php endif; ?>
                    <?php if ($optionBool('landing_show_review_domain_summaries', '1') && $domainSummary !== ''): ?><p class="d"><?php echo $esc($domainSummary); ?></p><?php endif; ?>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <div class="wrap catalog" id="catalog">
        <?php foreach ($visibleGroups as $categoryLabel => $tools): ?>
        <?php
        $categorySlug = $cleanSlug($categoryLabel);
        $meta = $categoryMeta[$categorySlug] ?? ['icon' => 'calculator', 'color' => 'var(--m365-tools-accent)', 'bg' => 'var(--m365-tools-surface-2)', 'summary' => 'Weitere Microsoft-365-Tools und Hilfen.'];
        $sectionCardColumns = max(1, min((int) count($tools), (int) $cardsPerRow));
        $displayCategoryLabel = $textOption('landing_category_label_' . $categorySlug, (string) $categoryLabel);
        $displayCategorySummary = $textOption('landing_category_summary_' . $categorySlug, (string) ($meta['summary'] ?? 'Weitere Microsoft-365-Tools und Hilfen.'));
        ?>
        <section class="cat-block" data-cat="<?php echo $esc($categorySlug); ?>" data-count="<?php echo (int) count($tools); ?>" style="--section-card-cols: <?php echo (int) $sectionCardColumns; ?>;" <?php echo $displayCategoryLabel !== '' ? 'aria-labelledby="cat-' . $esc($categorySlug) . '-title"' : 'aria-label="Kategorie"'; ?>>
            <?php if ($optionBool('landing_show_category_overline', '1') && $categoryOverlineText !== ''): ?><p class="section-tag"><?php echo $esc($categoryOverlineText); ?></p><?php endif; ?>
            <?php if ($displayCategoryLabel !== '' || $optionBool('landing_show_category_counts', '1')): ?><div class="cat-head"><span class="badge" style="background:<?php echo $esc($meta['bg']); ?>;color:<?php echo $esc($meta['color']); ?>" role="img" aria-label="Kategorie<?php echo $displayCategoryLabel !== '' ? ' ' . $esc($displayCategoryLabel) : ''; ?>"><?php echo $renderIcon((string) $meta['icon']); ?></span><?php if ($displayCategoryLabel !== ''): ?><h2 id="cat-<?php echo $esc($categorySlug); ?>-title"><?php echo $esc($displayCategoryLabel); ?></h2><?php endif; ?><?php if ($optionBool('landing_show_category_counts', '1')): ?><span class="modcount"><?php echo (int) count($tools); ?> Module</span><?php endif; ?></div><?php endif; ?>
            <?php if ($displayCategorySummary !== ''): ?><p class="cat-sub"><?php echo $esc($displayCategorySummary); ?></p><?php endif; ?>
            <div class="tool-grid">
                <?php foreach ($tools as $tool): ?>
                <?php
                if (!is_array($tool)) {
                    continue;
                }
                $toolKey = (string) ($tool['key'] ?? '');
                $toolOptionKey = $cleanSlug($toolKey);
                $toolTitle = $textOption('landing_tool_title_' . $toolOptionKey, (string) ($tool['title'] ?? $toolKey));
                $toolDesc = $textOption('landing_tool_description_' . $toolOptionKey, (string) ($tool['description'] ?? ''));
                $toolStatus = in_array((string) ($tool['status'] ?? 'live'), ['live', 'beta', 'soon'], true) ? (string) ($tool['status'] ?? 'live') : 'live';
                $toolStatusLabel = $toolStatus === 'beta' ? $statusBetaLabel : $statusSoonLabel;
                $toolUrl = $safeUrl($tool['url'] ?? '', '');
                $targetMode = $option('landing_tool_button_target_mode', 'tool');
                if ($targetMode === 'primary') {
                    $toolUrl = $primaryButtonUrl;
                } elseif ($targetMode === 'secondary') {
                    $toolUrl = $secondaryButtonUrl;
                } elseif ($targetMode === 'custom') {
                    $toolUrl = $safeUrl($option('landing_tool_button_custom_url', ''), $toolUrl);
                }
                $domainKeys = array_values(array_filter(array_map('strval', (array) ($toolReviewMap[$toolKey] ?? []))));
                $tagLabels = [];
                foreach ($domainKeys as $domainKey) {
                    if (isset($bestPracticeDomains[$domainKey]) && is_array($bestPracticeDomains[$domainKey])) {
                        $domainOptionKey = $cleanSlug($domainKey);
                        $tagLabel = $textOption('landing_review_domain_label_' . $domainOptionKey, (string) ($bestPracticeDomains[$domainKey]['label'] ?? $domainKey));
                        if ($tagLabel !== '') {
                            $tagLabels[] = $tagLabel;
                        }
                    }
                }
                $checks = array_values(array_filter(array_map('strval', (array) ($toolCheckMap[$toolKey] ?? []))));
                $buttonLabel = $textOption('landing_tool_button_label_' . $toolOptionKey, '') ?: $textOption('landing_open_button_label', 'Tool öffnen');
                $searchText = implode(' ', [$toolKey, $toolTitle, $toolDesc, $displayCategoryLabel, implode(' ', $tagLabels)]);
                ?>
                <article class="tool-card" data-cat="<?php echo $esc($categorySlug); ?>" data-name="<?php echo $esc($searchText); ?>" data-tags="<?php echo $esc(implode(' ', $tagLabels)); ?>">
                    <?php if ($optionBool('landing_show_status_labels', '1') && $toolStatus !== 'live' && $toolStatusLabel !== ''): ?><span class="flag <?php echo $esc($toolStatus); ?>"><?php echo $esc($toolStatusLabel); ?></span><?php endif; ?>
                    <div class="card-head">
                        <?php if ($optionBool('landing_show_icons', '1')): ?><span class="card-ico" role="img" aria-label="<?php echo $esc($toolTitle !== '' ? $toolTitle : 'Tool'); ?> Icon"><?php echo $renderIcon((string) ($tool['icon'] ?? 'calculator')); ?></span><?php endif; ?>
                        <?php if ($toolTitle !== ''): ?><h3><?php if ($optionBool('landing_link_card_titles', '1') && $toolUrl !== ''): ?><a href="<?php echo $esc($toolUrl); ?>"><?php echo $esc($toolTitle); ?></a><?php else: ?><?php echo $esc($toolTitle); ?><?php endif; ?></h3><?php endif; ?>
                    </div>
                    <?php if ($optionBool('landing_show_tool_descriptions', '1') && $toolDesc !== ''): ?><p class="desc"><?php echo $esc($toolDesc); ?></p><?php endif; ?>
                    <?php if ($optionBool('landing_show_review_chips', '1') && $tagLabels !== []): ?><div class="tags"><?php foreach ($tagLabels as $tagLabel): ?><span class="tag"><?php echo $esc($tagLabel); ?></span><?php endforeach; ?></div><?php endif; ?>
                    <?php if ($optionBool('landing_show_module_checks', '1') && $checks !== []): ?><ul class="checks"><?php foreach (array_slice($checks, 0, 2) as $check): ?><li><?php echo $esc($check); ?></li><?php endforeach; ?></ul><?php endif; ?>
                    <?php if ($optionBool('landing_show_tool_buttons', '1') && $toolUrl !== '' && $buttonLabel !== ''): ?>
                    <a class="open-link" href="<?php echo $esc($toolUrl); ?>"><?php echo $esc($buttonLabel); ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                    <?php elseif ($toolStatus !== 'live' && $optionBool('landing_show_disabled_note', '1') && $disabledNoteText !== ''): ?>
                    <p class="disabled-note"><?php echo $esc($disabledNoteText); ?></p>
                    <?php endif; ?>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>

        <?php if ($noResultsTitle !== '' || $noResultsText !== ''): ?>
        <section class="no-results" id="m365ToolsNoResults" role="status" aria-live="polite" hidden>
            <?php if ($noResultsTitle !== ''): ?><p class="no-results-title"><?php echo $esc($noResultsTitle); ?></p><?php endif; ?>
            <?php if ($noResultsText !== ''): ?><p><?php echo $esc($noResultsText); ?></p><?php endif; ?>
        </section>
        <?php endif; ?>
    </div>
</section>

<script>
(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
            return;
        }

        callback();
    }

    function normalize(value) {
        return String(value || '').trim().toLowerCase();
    }

    ready(function () {
        var root = document.querySelector('.m365-tools[data-m365-tools]');
        if (!root) {
            return;
        }

        var search = root.querySelector('#m365ToolsSearch');
        var chips = Array.prototype.slice.call(root.querySelectorAll('.chip[data-cat]'));
        var sections = Array.prototype.slice.call(root.querySelectorAll('.cat-block[data-cat]'));
        var cards = Array.prototype.slice.call(root.querySelectorAll('.tool-card[data-name][data-tags]'));
        var empty = root.querySelector('#m365ToolsNoResults');
        var activeCat = 'all';

        function cardHaystack(card) {
            if (card.dataset.searchCache) {
                return card.dataset.searchCache;
            }

            var title = card.querySelector('h3');
            var desc = card.querySelector('.desc');
            return normalize([
                card.getAttribute('data-name'),
                card.getAttribute('data-tags'),
                title ? title.textContent : '',
                desc ? desc.textContent : ''
            ].join(' '));
        }

        function applyFilters() {
            var query = normalize(search ? search.value : '');
            var anyVisible = false;

            sections.forEach(function (section) {
                var sectionCat = normalize(section.getAttribute('data-cat'));
                var catMatches = activeCat === 'all' || activeCat === sectionCat;
                var sectionHasVisibleCard = false;
                var sectionCards = Array.prototype.slice.call(section.querySelectorAll('.tool-card[data-name][data-tags]'));

                sectionCards.forEach(function (card) {
                    var cardCat = normalize(card.getAttribute('data-cat') || sectionCat);
                    var cardCatMatches = activeCat === 'all' || activeCat === cardCat;
                    var textMatches = query === '' || cardHaystack(card).indexOf(query) !== -1;
                    var visible = catMatches && cardCatMatches && textMatches;

                    card.hidden = !visible;
                    if (visible) {
                        sectionHasVisibleCard = true;
                        anyVisible = true;
                    }
                });

                section.hidden = !(catMatches && sectionHasVisibleCard);
            });

            if (empty) {
                empty.hidden = anyVisible;
            }
        }

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                chips.forEach(function (item) {
                    item.classList.remove('active');
                    item.setAttribute('aria-pressed', 'false');
                });

                chip.classList.add('active');
                chip.setAttribute('aria-pressed', 'true');
                activeCat = normalize(chip.getAttribute('data-cat')) || 'all';
                applyFilters();
            });
        });

        if (search) {
            search.addEventListener('input', applyFilters);
        }

        cards.forEach(function (card) {
            card.dataset.searchCache = cardHaystack(card);
        });

        applyFilters();
    });
}());
</script>
