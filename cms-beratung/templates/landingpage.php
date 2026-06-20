<?php
/**
 * CMS Beratung frontend template.
 *
 * @var array<string,mixed> $page
 * @var array<string,string> $settings
 * @var array<string,string> $design
 * @var array<int,array<string,mixed>> $sections
 * @var array<int,array<string,mixed>> $anchors
 * @var array<string,mixed> $m365Faq
 * @var array{success:bool,message:string} $formResult
 * @var string $csrfToken
 *
 * @package CMS_Beratung
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$renderer = CMS_Beratung_Renderer::class;
$customClass = trim((string) ($page['custom_css_class'] ?? ''));
$hero = is_array($page['hero'] ?? null) ? $page['hero'] : [];
$contact = is_array($page['contact'] ?? null) ? $page['contact'] : [];
$formErrors = is_array($formResult['errors'] ?? null) ? $formResult['errors'] : [];
$formValues = is_array($formResult['values'] ?? null) ? $formResult['values'] : [];
$primaryCtaUsed = false;
$heroPortraitPath = CMS_BERATUNG_PLUGIN_DIR . 'assets/img/consultant-portrait.svg';
$heroPortraitUrl = CMS_BERATUNG_PLUGIN_URL . 'assets/img/consultant-portrait.svg';

// Platzhalter: Microsoft Bookings Link hier eintragen, wenn die Terminbuchung aktiv angezeigt werden soll.
$bookingUrl = '';

$serviceOutcomeTexts = [
    'Microsoft 365 Tenant Check' => 'Du bekommst eine klare Risiko- und Lizenzeinschätzung deines Tenants und weißt, wo Governance fehlt.',
    'Copilot Readiness Check' => 'Du erkennst, ob Datenzugriffe, Inhalte und Compliance für Copilot belastbar vorbereitet sind. So startest du kontrolliert statt mit blinden Flecken.',
    'Entra ID Security Review' => 'Du siehst, welche Identitätsrisiken deinen Tenant wirklich betreffen. Daraus entstehen konkrete Schritte für MFA, Rollen und Zugriffsschutz.',
    'Microsoft Purview Beratung' => 'Du weißt, welche Purview-Funktionen für deine Daten sinnvoll sind und wie Schutz, DLP und Aufbewahrung zusammenwirken.',
    'SharePoint und OneDrive Governance' => 'Du bekommst klare Regeln für Sites, Freigaben und Berechtigungen. So reduzierst du Wildwuchs und schaffst eine bessere Grundlage für Copilot.',
    'Exchange Online Analyse' => 'Du erkennst Risiken in Mailfluss, Postfächern und Schutzfunktionen. Danach weißt du, welche Anpassungen Betrieb und Sicherheit verbessern.',
    'Microsoft Defender Review' => 'Du bekommst priorisierte Findings zu Defender und Secure Score. So weißt du, welche Schutzmaßnahmen zuerst Wirkung bringen.',
    'Conditional Access Bewertung' => 'Du erkennst unsichere Ausnahmen, Lücken und Konflikte in deinen Richtlinien. Daraus entsteht ein belastbarer Zugriffsschutz für Benutzer und Admins.',
    'Admin Workshops' => 'Dein Admin-Team versteht die Entscheidungen und kann sie selbstständig weiterführen. Die Inhalte richten sich an euren echten Aufgaben aus.',
    'PowerShell Automatisierung' => 'Du reduzierst wiederkehrende Admin-Arbeit und bekommst nachvollziehbare Skripte für stabile Abläufe.',
    'Dokumentation und Übergabe' => 'Du erhältst verständliche Ergebnisse, Entscheidungen und nächste Schritte. Damit bleiben Wissen und Verantwortung im Team nutzbar.',
    'Projektbegleitung' => 'Du bekommst technische Begleitung während Umsetzung, Review und Übergabe. So bleiben Entscheidungen sauber und Risiken früh sichtbar.',
];

// Editierbare Platzhalter für Social Proof. Für echte Referenzen nur dieses Array anpassen, kein Markup ändern.
// Platzhalter-Hinweis: Diese Einträge später durch echte, freigegebene Referenzen oder anonymisierte Projektergebnisse ersetzen.
// Wird das Array geleert, unterdrückt sich die Social-Proof-Sektion automatisch.
$proofItems = [
    [
        'quote' => 'Nach dem Tenant Check war klar, welche Risiken zuerst angegangen werden müssen. Die Empfehlungen waren verständlich genug für Management und Admin-Team.',
        'attribution' => 'IT-Leiter, Fertigungsunternehmen, rund 250 MA',
        'initials' => 'IT',
    ],
    [
        'quote' => 'Der Copilot Readiness Check hat unsere offenen SharePoint-Berechtigungen sichtbar gemacht. Danach konnten wir Pilotierung und Governance deutlich sicherer planen.',
        'attribution' => 'Head of Digital Workplace, Dienstleister, rund 600 MA',
        'initials' => 'DW',
    ],
    [
        'quote' => 'Statt allgemeiner Folien gab es konkrete Maßnahmen für Entra ID, Defender und Conditional Access. Genau das hat uns in der Umsetzung geholfen.',
        'attribution' => 'Senior Administrator, öffentliche Einrichtung',
        'initials' => 'SA',
    ],
];

$hasText = static function (mixed $value): bool {
    return trim((string) $value) !== '';
};

$defaultHeroTrustBadges = ['Ex-Microsoft MVP', '20+ Jahre', 'LPIC 1 & 2', 'Microsoft zertifiziert'];
$heroTrustBadgeValues = [];
if (array_key_exists('trust_badges', $hero) && is_array($hero['trust_badges'])) {
    $heroTrustBadgeValues = $hero['trust_badges'];
} elseif (array_key_exists('trust_badge_1', $hero) || array_key_exists('trust_badge_2', $hero) || array_key_exists('trust_badge_3', $hero) || array_key_exists('trust_badge_4', $hero)) {
    $heroTrustBadgeValues = [$hero['trust_badge_1'] ?? '', $hero['trust_badge_2'] ?? '', $hero['trust_badge_3'] ?? '', $hero['trust_badge_4'] ?? ''];
} else {
    $heroTrustBadgeValues = $defaultHeroTrustBadges;
}
$heroTrustBadges = array_values(array_filter(array_map(static fn(mixed $value): string => trim((string) $value), array_slice($heroTrustBadgeValues, 0, 4)), static fn(string $value): bool => $value !== ''));
$partnerBandEnabled = !empty($hero['partner_band_enabled']);
$partnerBandText = trim((string) ($hero['partner_band_text'] ?? ''));
$partnerBandWebsiteLabel = trim((string) ($hero['partner_band_website_label'] ?? ''));
$partnerBandWebsiteUrl = trim((string) ($hero['partner_band_website_url'] ?? ''));
$partnerBandMapLabel = trim((string) ($hero['partner_band_map_label'] ?? ''));
$partnerBandMapUrl = trim((string) ($hero['partner_band_map_url'] ?? ''));
$partnerBandHasContent = $partnerBandEnabled && ($partnerBandText !== '' || ($partnerBandWebsiteLabel !== '' && $partnerBandWebsiteUrl !== '') || ($partnerBandMapLabel !== '' && $partnerBandMapUrl !== ''));
$anchorNavLayout = in_array((string) ($hero['anchor_nav_layout'] ?? 'pills'), ['pills', 'cards', 'goldbar', 'minimal', 'threegrid'], true) ? (string) ($hero['anchor_nav_layout'] ?? 'pills') : 'pills';

$buttonClass = static function (string $style): string {
    return 'cms-beratung__btn cms-beratung__btn--' . preg_replace('/[^a-z0-9_-]/i', '', $style ?: 'primary');
};

$renderLinkButton = static function (string $label, string $url, string $style = 'primary') use ($renderer, $buttonClass, &$primaryCtaUsed): void {
    $label = trim($label);
    $url = trim($url);
    if ($label === '' || $url === '') {
        return;
    }
    $isMainCta = strcasecmp($label, 'Beratung anfragen') === 0 && !$primaryCtaUsed;
    $style = $isMainCta ? 'primary' : 'ghost';
    if ($isMainCta) {
        $primaryCtaUsed = true;
    }
    $external = preg_match('#^https?://#i', $url) === 1;
    echo '<a class="' . $renderer::esc($buttonClass($style)) . '" href="' . $renderer::esc($url) . '"' . ($external ? ' target="_blank" rel="noopener noreferrer"' : '') . '>' . $renderer::esc($label) . '</a>';
};

$renderButton = static function (array $button) use ($renderLinkButton): void {
    $renderLinkButton((string) ($button['text'] ?? ''), (string) ($button['target'] ?? ''), (string) ($button['style'] ?? 'primary'));
};

$renderSectionActions = static function (array $section) use ($renderLinkButton): void {
    if (empty($section['button_1_text']) && empty($section['button_2_text'])) {
        return;
    }
    echo '<div class="cms-beratung-module__actions">';
    $renderLinkButton((string) ($section['button_1_text'] ?? ''), (string) ($section['button_1_target'] ?? ''), (string) ($section['button_1_style'] ?? 'primary'));
    $renderLinkButton((string) ($section['button_2_text'] ?? ''), (string) ($section['button_2_target'] ?? ''), (string) ($section['button_2_style'] ?? 'ghost'));
    echo '</div>';
};

$renderCardButton = static function (array $card) use ($renderLinkButton): void {
    $renderLinkButton((string) ($card['button_label'] ?? ''), (string) ($card['button_url'] ?? ''), 'link');
};

$renderCard = static function (array $card, int $index, string $sectionType = 'card_grid') use ($renderer, $renderCardButton, $serviceOutcomeTexts, $hasText): void {
    if (array_key_exists('enabled', $card) && empty($card['enabled'])) {
        return;
    }
    if ($sectionType === 'services') {
        $serviceTitle = (string) ($card['title'] ?? '');
        if (isset($serviceOutcomeTexts[$serviceTitle])) {
            $card['text'] = $serviceOutcomeTexts[$serviceTitle];
        }
    }
    $type = $sectionType === 'steps' ? 'step' : (string) ($card['card_type'] ?? 'text');
    $classes = ['cms-beratung-card', 'cms-beratung-card--' . $type, 'cms-beratung-card--module-' . $sectionType];
    foreach (['featured' => 'is-featured', 'hover_enabled' => 'has-hover'] as $key => $class) {
        if (!empty($card[$key])) {
            $classes[] = $class;
        }
    }
    if (empty($card['border_enabled'])) { $classes[] = 'no-border'; }
    if (empty($card['shadow_enabled'])) { $classes[] = 'no-shadow'; }
    $style = '--card-bg:' . $renderer::esc((string) ($card['background_color'] ?? '#ffffff')) . ';--card-text:' . $renderer::esc((string) ($card['text_color'] ?? '#111827')) . ';--card-border:' . $renderer::esc((string) ($card['border_color'] ?? '#dbeafe')) . ';';
    echo '<article class="' . $renderer::esc(implode(' ', $classes)) . '" style="' . $style . '">';

    if ($sectionType === 'faq') {
        echo '</article>';
        return;
    }

    if ($sectionType === 'technology') {
        if (!empty($card['logo_url'])) {
            echo '<img class="cms-beratung-card__logo" src="' . $renderer::esc((string) $card['logo_url']) . '" alt="' . $renderer::esc((string) ($card['name'] ?? $card['title'] ?? 'Technologie')) . '" loading="lazy">';
        } elseif (!empty($card['icon'])) {
            echo '<div class="cms-beratung-card__icon" aria-hidden="true">' . $renderer::esc((string) $card['icon']) . '</div>';
        }
        echo '<h3>' . $renderer::esc((string) ($card['name'] ?? $card['title'] ?? 'Technologie')) . '</h3>';
        if ($hasText($card['text'] ?? '')) { echo '<p>' . $renderer::nl((string) $card['text']) . '</p>'; }
        echo '</article>';
        return;
    }

    if ($sectionType === 'trust') {
        if (!empty($card['metric'])) { echo '<strong class="cms-beratung-card__metric">' . $renderer::esc((string) $card['metric']) . '</strong>'; }
        if (!empty($card['icon'])) { echo '<div class="cms-beratung-card__icon" aria-hidden="true">' . $renderer::esc((string) $card['icon']) . '</div>'; }
        if (!empty($card['title'])) { echo '<h3>' . $renderer::esc((string) $card['title']) . '</h3>'; }
        if ($hasText($card['text'] ?? '')) { echo '<p>' . $renderer::nl((string) $card['text']) . '</p>'; }
        $renderCardButton($card);
        echo '</article>';
        return;
    }

    if ($sectionType === 'services' && !empty($card['category'])) {
        echo '<span class="cms-beratung-card__category">' . $renderer::esc((string) $card['category']) . '</span>';
    }

    if ($type === 'image_label') {
        if (!empty($card['image_url'])) {
            echo '<div class="cms-beratung-card__image" style="--card-image-height:' . (int) ($card['image_height'] ?? 230) . 'px;--card-image-fit:' . $renderer::esc((string) ($card['image_fit'] ?? 'cover')) . '"><img src="' . $renderer::esc((string) $card['image_url']) . '" alt="' . $renderer::esc((string) ($card['image_alt'] ?? $card['title'] ?? '')) . '" loading="lazy"></div>';
        } elseif (!empty($card['icon'])) {
            echo '<div class="cms-beratung-card__image is-placeholder" aria-hidden="true">' . $renderer::esc((string) $card['icon']) . '</div>';
        }
        if (!empty($card['label'])) {
            echo '<span class="cms-beratung-card__label cms-beratung-card__label--' . $renderer::esc((string) ($card['label_position'] ?? 'left')) . ' cms-beratung-card__label--' . $renderer::esc((string) ($card['label_style'] ?? 'filled')) . '">' . $renderer::esc((string) $card['label']) . '</span>';
        }
        echo '<div class="cms-beratung-card__body">';
        if (!empty($card['title'])) { echo '<h3>' . $renderer::esc((string) $card['title']) . '</h3>'; }
        if ($hasText($card['text'] ?? '')) { echo '<p>' . $renderer::nl((string) $card['text']) . '</p>'; }
        $renderCardButton($card);
        echo '</div>';
    } elseif ($type === 'offer_icon_tab') {
        if (!empty($card['tab_enabled'])) {
            echo '<div class="cms-beratung-card__tab" style="--icon-bg:' . $renderer::esc((string) ($card['icon_background'] ?? '#eff6ff')) . ';--icon-color:' . $renderer::esc((string) ($card['icon_color'] ?? '#2563eb')) . '" aria-hidden="true">' . $renderer::esc((string) ($card['icon'] ?? '💡')) . '</div>';
        }
        if (!empty($card['title'])) { echo '<h3>' . $renderer::esc((string) $card['title']) . '</h3>'; }
        if ($hasText($card['text'] ?? '')) { echo '<p>' . $renderer::nl((string) $card['text']) . '</p>'; }
        $renderCardButton($card);
    } elseif ($type === 'step') {
        $step = !empty($card['auto_number']) ? (string) ($index + 1) : (string) ($card['step_number'] ?? $index + 1);
        echo '<div class="cms-beratung-card__step"><span>' . $renderer::esc($step) . '</span>' . (!empty($card['icon']) ? '<i aria-hidden="true">' . $renderer::esc((string) $card['icon']) . '</i>' : '') . '</div>';
        if (!empty($card['connector'])) { echo '<span class="cms-beratung-card__connector" aria-hidden="true"></span>'; }
        if (!empty($card['title'])) { echo '<h3>' . $renderer::esc((string) $card['title']) . '</h3>'; }
        if ($hasText($card['text'] ?? '')) { echo '<p>' . $renderer::nl((string) $card['text']) . '</p>'; }
        $renderCardButton($card);
    } elseif ($type === 'problem_solution') {
        echo '<div class="cms-beratung-problem-solution"><div><span>Spalte 1</span><h3>' . $renderer::esc((string) ($card['problem_title'] ?? 'Problem')) . '</h3>' . ($hasText($card['problem_text'] ?? '') ? '<p>' . $renderer::nl((string) $card['problem_text']) . '</p>' : '') . '</div><div><span>Spalte 2</span><h3>' . $renderer::esc((string) ($card['solution_title'] ?? 'Lösung')) . '</h3>' . ($hasText($card['solution_text'] ?? '') ? '<p>' . $renderer::nl((string) $card['solution_text']) . '</p>' : '') . '</div></div>';
        if ($hasText($card['extra_text'] ?? '') || $hasText($card['recommendation'] ?? '')) {
            echo '<div class="cms-beratung-card__risk"><p>' . $renderer::nl((string) ($card['extra_text'] ?? $card['recommendation'] ?? '')) . '</p></div>';
        }
        $renderCardButton($card);
    } else {
        if (!empty($card['badge'])) { echo '<span class="cms-beratung-card__badge">' . $renderer::esc((string) $card['badge']) . '</span>'; }
        if (!empty($card['icon'])) { echo '<div class="cms-beratung-card__icon" aria-hidden="true">' . $renderer::esc((string) $card['icon']) . '</div>'; }
        if (!empty($card['title'])) { echo '<h3>' . $renderer::esc((string) $card['title']) . '</h3>'; }
        if ($hasText($card['text'] ?? '')) { echo '<p>' . $renderer::nl((string) $card['text']) . '</p>'; }
        if ($hasText($card['extra_text'] ?? '')) { echo '<p class="cms-beratung-card__extra">' . $renderer::nl((string) $card['extra_text']) . '</p>'; }
        $renderCardButton($card);
    }
    echo '</article>';
};

$renderCardsGrid = static function (array $section, array $cards, int $columns) use ($renderCard): void {
    echo '<div class="cms-beratung__cards cms-beratung__cards--' . $columns . ' cms-beratung__cards--design-' . CMS_Beratung_Renderer::esc((string) ($section['card_design'] ?? 'standard')) . '">';
    foreach ($cards as $index => $card) {
        if (is_array($card)) {
            $renderCard($card, $index, (string) ($section['type'] ?? 'card_grid'));
        }
    }
    echo '</div>';
};

$expertName = static function (object $expert): string {
    $first = trim((string) ($expert->first_name ?? ''));
    $last = trim((string) ($expert->last_name ?? ''));
    $name = trim($first . ' ' . $last);
    return $name !== '' ? $name : 'Expert #' . (int) ($expert->id ?? 0);
};

$expertInitials = static function (object $expert) use ($expertName): string {
    $parts = preg_split('/\s+/', trim($expertName($expert))) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= function_exists('mb_substr') ? mb_substr($part, 0, 1, 'UTF-8') : substr($part, 0, 1);
    }
    $letters = strtoupper($letters);
    return $letters !== '' ? $letters : 'EX';
};

$expertExcerpt = static function (object $expert): string {
    $raw = trim((string) ($expert->biography ?? ''));
    if ($raw === '') {
        return '';
    }
    $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    if ($text === '') {
        return '';
    }
    return function_exists('mb_substr') ? (string) mb_substr($text, 0, 150, 'UTF-8') : substr($text, 0, 150);
};

$loadCollaborationExperts = static function (array $ids): array {
    if (!class_exists('CMS_365NET_Experts_And_Companie_Database')) {
        return [];
    }
    $database = CMS_365NET_Experts_And_Companie_Database::instance();
    $experts = [];
    foreach (array_values(array_unique(array_map('intval', $ids))) as $id) {
        if ($id <= 0) {
            continue;
        }
        $expert = $database->getExpertPublicById($id);
        if (is_object($expert)) {
            $experts[] = $expert;
        }
    }
    return $experts;
};

$renderCollaboration = static function (array $section, int $columns) use ($renderer, $loadCollaborationExperts, $expertName, $expertInitials, $expertExcerpt): void {
    $experts = $loadCollaborationExperts(is_array($section['expert_ids'] ?? null) ? $section['expert_ids'] : []);
    if ($experts === []) {
        return;
    }
    $base = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
    echo '<div class="cms-beratung-collab cms-beratung-collab--cols-' . max(2, min(4, $columns)) . '">';
    foreach ($experts as $expert) {
        $id = (int) ($expert->id ?? 0);
        $name = $expertName($expert);
        $position = trim((string) ($expert->position ?? ''));
        $company = trim((string) ($expert->company ?? ''));
        $awards = trim((string) ($expert->awards ?? ''));
        $isMvp = $awards !== '' && stripos($awards, 'mvp') !== false;
        $photo = trim((string) ($expert->photo_url ?? ''));
        $detailUrl = $id > 0 && $base !== '' ? $base . '/experts/' . $id : '';
        $excerpt = $expertExcerpt($expert);
        echo '<article class="cms-beratung-collab-card' . ($isMvp ? ' is-mvp' : '') . '">';
        if ($isMvp) {
            echo '<span class="cms-beratung-collab-card__mvp">Microsoft MVP</span>';
        }
        echo '<div class="cms-beratung-collab-card__head"><div class="cms-beratung-collab-card__avatar">';
        if ($photo !== '') {
            echo '<img src="' . $renderer::esc($photo) . '" alt="' . $renderer::esc($name) . '" loading="lazy">';
        } else {
            echo '<span>' . $renderer::esc($expertInitials($expert)) . '</span>';
        }
        echo '</div><div><h3>' . $renderer::esc($name) . '</h3>';
        if ($position !== '') {
            echo '<p class="cms-beratung-collab-card__position">' . $renderer::esc($position) . '</p>';
        }
        echo '</div></div>';
        if ($company !== '') {
            echo '<span class="cms-beratung-collab-card__company">' . $renderer::esc($company) . '</span>';
        }
        if ($excerpt !== '') {
            echo '<p class="cms-beratung-collab-card__excerpt">' . $renderer::esc($excerpt) . '</p>';
        }
        if ($detailUrl !== '') {
            echo '<a class="cms-beratung-collab-card__link" href="' . $renderer::esc($detailUrl) . '">Expert Profil ansehen</a>';
        }
        echo '</article>';
    }
    echo '</div>';
    if (!empty($section['mvp_note_enabled']) && trim((string) ($section['mvp_note_text'] ?? '')) !== '') {
        echo '<p class="cms-beratung-collab__mvp-note"><span>★</span>' . $renderer::esc((string) $section['mvp_note_text']) . '</p>';
    }
};

$renderComparison = static function (array $section, array $cards, int $columns) use ($renderer, $hasText): void {
    if (!empty($section['title_band_enabled']) && !empty($section['title_band_text'])) {
        echo '<div class="cms-beratung-comparison__band" style="--band-bg:' . $renderer::esc((string) ($section['title_band_background_color'] ?? '#1e3a8a')) . ';--band-text:' . $renderer::esc((string) ($section['title_band_text_color'] ?? '#ffffff')) . '">' . $renderer::esc((string) $section['title_band_text']) . '</div>';
    }
    echo '<div class="cms-beratung-comparison cms-beratung-comparison--' . $columns . ' ' . (!empty($section['border_enabled']) ? 'has-border' : '') . ' ' . (!empty($section['shadow_enabled']) ? 'has-shadow' : '') . '">';
    foreach ($cards as $card) {
        if (!is_array($card) || empty($card['enabled'])) { continue; }
        echo '<article class="cms-beratung-comparison__column">';
        if (!empty($card['icon'])) { echo '<div aria-hidden="true">' . $renderer::esc((string) $card['icon']) . '</div>'; }
        echo '<h3>' . $renderer::esc((string) ($card['title'] ?? $card['problem_title'] ?? 'Vergleich')) . '</h3>';
        if ($hasText($card['text'] ?? $card['problem_text'] ?? '')) { echo '<p>' . $renderer::nl((string) ($card['text'] ?? $card['problem_text'] ?? '')) . '</p>'; }
        if ($hasText($card['extra_text'] ?? '')) { echo '<small>' . $renderer::nl((string) $card['extra_text']) . '</small>'; }
        echo '</article>';
    }
    echo '</div>';
};

$renderFaq = static function (array $section, array $cards, string $sectionId) use ($renderer, $hasText): void {
    echo '<div class="cms-beratung-faq" data-allow-multiple="' . (!empty($section['faq_allow_multiple']) ? '1' : '0') . '" style="--faq-q-bg:' . $renderer::esc((string) ($section['faq_question_background_color'] ?? '#ffffff')) . ';--faq-q-text:' . $renderer::esc((string) ($section['faq_question_text_color'] ?? '#111827')) . ';--faq-a-bg:' . $renderer::esc((string) ($section['faq_answer_background_color'] ?? '#f8fafc')) . '">';
    $openBehavior = (string) ($section['faq_open_behavior'] ?? 'none');
    foreach ($cards as $index => $card) {
        if (!is_array($card) || empty($card['enabled'])) { continue; }
        $isOpen = ($openBehavior === 'first' && $index === 0) || ($openBehavior === 'custom' && !empty($card['default_open']));
        $buttonId = $sectionId . '-faq-button-' . $index;
        $panelId = $sectionId . '-faq-panel-' . $index;
        $icon = (string) ($section['faq_icon_style'] ?? 'plus');
        echo '<article class="cms-beratung-faq__item ' . ($isOpen ? 'is-open' : '') . '"><h3><button type="button" id="' . $renderer::esc($buttonId) . '" aria-controls="' . $renderer::esc($panelId) . '" aria-expanded="' . ($isOpen ? 'true' : 'false') . '"><span>' . $renderer::esc((string) ($card['question'] ?? $card['title'] ?? 'Frage')) . '</span><i data-icon-style="' . $renderer::esc($icon) . '" aria-hidden="true"></i></button></h3><div id="' . $renderer::esc($panelId) . '" role="region" aria-labelledby="' . $renderer::esc($buttonId) . '"' . ($isOpen ? '' : ' hidden') . '>' . ($hasText($card['answer'] ?? $card['text'] ?? '') ? '<p>' . $renderer::nl((string) ($card['answer'] ?? $card['text'] ?? '')) . '</p>' : '') . '</div></article>';
    }
    echo '</div>';
};

$renderDivider = static function (array $section) use ($renderer, $renderSectionActions, $hasText): void {
    $type = (string) ($section['divider_type'] ?? 'line');
    echo '<div class="cms-beratung-divider cms-beratung-divider--' . $renderer::esc($type) . ' cms-beratung-divider--mobile-' . $renderer::esc((string) ($section['divider_mobile_behavior'] ?? 'stack')) . '" style="--divider-bg:' . $renderer::esc((string) ($section['background_color'] ?? '#ffffff')) . ';--divider-text:' . $renderer::esc((string) ($section['text_color'] ?? '#111827')) . ';--divider-line:' . $renderer::esc((string) ($section['divider_line_color'] ?? '#dbeafe')) . ';--divider-width:' . (int) ($section['divider_width'] ?? 100) . '%;">';
    if ($type === 'spacer') { echo '<span aria-hidden="true"></span>'; }
    elseif ($type === 'wave') { echo '<svg viewBox="0 0 1440 90" aria-hidden="true" focusable="false"><path d="M0,48 C180,96 360,0 540,42 C720,84 900,84 1080,36 C1260,-12 1350,24 1440,54 L1440,90 L0,90 Z"></path></svg>'; }
    else {
        if (!empty($section['divider_icon'])) { echo '<span class="cms-beratung-divider__icon">' . $renderer::esc((string) $section['divider_icon']) . '</span>'; }
        if (!empty($section['divider_title'])) { echo '<h2>' . $renderer::esc((string) $section['divider_title']) . '</h2>'; }
        if ($hasText($section['divider_subtitle'] ?? '')) { echo '<p>' . $renderer::nl((string) $section['divider_subtitle']) . '</p>'; }
        if ($type === 'cta') { $renderSectionActions($section); }
    }
    echo '</div>';
};

$renderProofSection = static function (array $items) use ($renderer): void {
    $items = array_values(array_filter($items, static fn(array $item): bool => trim((string) ($item['quote'] ?? '')) !== '' && trim((string) ($item['attribution'] ?? '')) !== ''));
    if ($items === []) {
        return;
    }
    echo '<section class="cms-beratung-proof" aria-labelledby="beratung-proof-title"><div class="cms-beratung-proof__inner"><div class="cms-beratung-proof__head"><span>Stimmen aus der Praxis</span><h2 id="beratung-proof-title">Was nach Beratung greifbar wird</h2><p>Platzhalter für anonymisierte Ergebnisse und Referenzen. Später können hier echte Stimmen ergänzt werden.</p></div><div class="cms-beratung-proof__grid">';
    foreach (array_slice($items, 0, 3) as $item) {
        $initials = trim((string) ($item['initials'] ?? ''));
        echo '<article class="cms-beratung-proof-card"><div class="cms-beratung-proof-card__mark" aria-hidden="true">“</div><blockquote>' . $renderer::esc((string) $item['quote']) . '</blockquote><footer>' . ($initials !== '' ? '<span class="cms-beratung-proof-card__avatar">' . $renderer::esc($initials) . '</span>' : '') . '<span>' . $renderer::esc((string) $item['attribution']) . '</span></footer></article>';
    }
    echo '</div></div></section>';
};

$renderBookingSection = static function (string $bookingUrl) use ($renderer): void {
    $bookingUrl = trim($bookingUrl);
    if ($bookingUrl !== '' && preg_match('#^https://#i', $bookingUrl) !== 1) {
        $bookingUrl = '';
    }
    echo '<section class="cms-beratung-booking" id="termin-buchen" aria-labelledby="beratung-booking-title"><div class="cms-beratung-booking__inner"><div class="cms-beratung-booking__head"><span>Termin</span><h2 id="beratung-booking-title">Direkt einen Termin buchen</h2><p>Wähle einen passenden Slot für ein erstes Gespräch zu Microsoft 365, Copilot oder Security. Danach klären wir Ziel, Ausgangslage und den nächsten sinnvollen Schritt.</p><ul class="cms-beratung-booking__trust" aria-label="Termin Vorteile"><li>Kostenloses Erstgespräch</li><li>Antwort in 24h</li><li>Remote oder vor Ort</li></ul></div><div class="cms-beratung-booking-card">';
    if ($bookingUrl !== '') {
        echo '<div class="cms-beratung-booking-card__embed"><iframe src="' . $renderer::esc($bookingUrl) . '" title="Microsoft Bookings Terminbuchung für Beratung" loading="lazy" allow="clipboard-write; fullscreen" sandbox="allow-forms allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-top-navigation-by-user-activation"></iframe></div>';
    } else {
        echo '<div class="cms-beratung-booking-card__fallback"><span>Terminbuchung wird vorbereitet</span><h3>Der direkte Kalender ist noch nicht verbunden.</h3><p>Bis der Microsoft Bookings Link hinterlegt ist, kannst du deine Anfrage über das Kontaktformular senden. Ich melde mich mit passenden Terminvorschlägen zurück.</p><a class="cms-beratung__btn cms-beratung__btn--primary" href="#kontakt">Beratung anfragen</a></div>';
    }
    echo '</div></div></section>';
};
?>
<main class="cms-beratung cms-beratung--<?php echo $renderer::esc((string) ($page['template'] ?? 'standard')); ?> <?php echo $renderer::esc($customClass); ?>" style="--beratung-primary: <?php echo $renderer::esc($design['primary_color']); ?>; --beratung-secondary: <?php echo $renderer::esc($design['secondary_color']); ?>; --beratung-accent: <?php echo $renderer::esc($design['accent_color']); ?>; --beratung-bg: <?php echo $renderer::esc($design['background_color']); ?>; --beratung-text: <?php echo $renderer::esc($design['text_color']); ?>; --beratung-heading: <?php echo $renderer::esc($design['heading_color']); ?>; --beratung-button: <?php echo $renderer::esc($design['button_color']); ?>; --beratung-button-text: <?php echo $renderer::esc($design['button_text_color']); ?>; --beratung-card-bg: <?php echo $renderer::esc($design['card_background_color']); ?>; --beratung-card-border: <?php echo $renderer::esc($design['card_border_color']); ?>; --beratung-radius: <?php echo $renderer::esc($design['border_radius']); ?>; --beratung-spacing: <?php echo $renderer::esc($design['spacing']); ?>; --beratung-width: <?php echo $renderer::esc($design['content_width']); ?>; --beratung-card-shadow: <?php echo $renderer::esc($design['card_shadow']); ?>;">
    <?php if (!empty($page['show_breadcrumb'])): ?>
        <nav class="cms-beratung__breadcrumb" aria-label="Breadcrumb"><a href="/">Startseite</a><span aria-hidden="true">/</span><span><?php echo $renderer::esc((string) ($page['public_title'] ?? 'Beratung')); ?></span></nav>
    <?php endif; ?>

    <?php if (!empty($hero['enabled'])): ?>
        <?php $heroTitle = trim((string) ($hero['title'] ?? '')) ?: (string) ($page['public_title'] ?? 'CMS Beratung'); ?>
        <header class="cms-beratung-hero cms-beratung-hero--image-<?php echo $renderer::esc((string) ($hero['image_position'] ?? 'left')); ?> cms-beratung-hero--mobile-<?php echo $renderer::esc((string) ($hero['mobile_order'] ?? 'image-first')); ?> <?php echo !empty($hero['image_flush']) ? 'is-flush' : ''; ?>" style="--hero-bg:<?php echo $renderer::esc((string) ($hero['background_color'] ?? '#f8fafc')); ?>;--hero-text:<?php echo $renderer::esc((string) ($hero['text_color'] ?? '#111827')); ?>;--hero-image-height:<?php echo (int) ($hero['image_height'] ?? 520); ?>px;--hero-image-width:<?php echo (int) ($hero['image_width'] ?? 46); ?>%;--hero-image-fit:<?php echo $renderer::esc((string) ($hero['image_fit'] ?? 'cover')); ?>;--hero-align:<?php echo $renderer::esc((string) ($hero['vertical_align'] ?? 'center')); ?>;">
            <div class="cms-beratung-hero__image"><?php if (!empty($hero['image_url'])): ?><img src="<?php echo $renderer::esc((string) $hero['image_url']); ?>" alt="<?php echo $renderer::esc((string) ($hero['image_alt'] ?? $heroTitle)); ?>" loading="eager"><?php else: ?><div class="cms-beratung-hero__placeholder" aria-hidden="true">365</div><?php endif; ?></div>
            <div class="cms-beratung-hero__content">
                <?php if (!empty($hero['badge_show']) && !empty($hero['badge_text'])): ?><span class="cms-beratung__kicker"><?php echo $renderer::esc((string) $hero['badge_text']); ?></span><?php endif; ?>
                <h1><?php echo $renderer::esc($heroTitle); ?></h1>
                <?php if (!empty($hero['subtitle'])): ?><p class="cms-beratung-hero__subtitle"><?php echo $renderer::esc((string) $hero['subtitle']); ?></p><?php endif; ?>
                <?php if ($heroTrustBadges !== []): ?><div class="cms-beratung-hero__trust-row" aria-label="Trust Signale"><?php foreach ($heroTrustBadges as $trustBadge): ?><span><?php echo $renderer::esc($trustBadge); ?></span><?php endforeach; ?></div><?php endif; ?>
                <?php if (!empty($hero['description'])): ?><p class="cms-beratung-hero__description"><?php echo $renderer::esc((string) $hero['description']); ?></p><?php endif; ?>
                <div class="cms-beratung__hero-actions"><?php $renderButton(is_array($hero['button_1'] ?? null) ? $hero['button_1'] : []); ?><?php $renderButton(is_array($hero['button_2'] ?? null) ? $hero['button_2'] : []); ?><?php $renderButton(is_array($hero['button_3'] ?? null) ? $hero['button_3'] : []); ?></div>
                <?php if (!empty($hero['trust_text'])): ?><p class="cms-beratung-hero__trust"><?php echo $renderer::esc((string) $hero['trust_text']); ?></p><?php endif; ?>
                <figure class="cms-beratung-hero__portrait-slot">
                    <?php if (is_file($heroPortraitPath)): ?>
                        <img class="cms-beratung-hero__portrait-image" src="<?php echo $renderer::esc($heroPortraitUrl); ?>" alt="Portrait eines Microsoft 365 Beraters" loading="lazy" decoding="async">
                    <?php else: ?>
                        <span aria-label="Portrait Platzhalter">M365</span>
                    <?php endif; ?>
                </figure>
            </div>
        </header>
    <?php endif; ?>

    <?php if ($partnerBandHasContent): ?>
        <aside class="cms-beratung-partner-band" aria-label="Partner Netzwerk">
            <?php if ($partnerBandText !== ''): ?><strong><?php echo $renderer::esc($partnerBandText); ?></strong><?php endif; ?>
            <span class="cms-beratung-partner-band__links">
                <?php if ($partnerBandWebsiteLabel !== '' && $partnerBandWebsiteUrl !== ''): ?><a href="<?php echo $renderer::esc($partnerBandWebsiteUrl); ?>" target="_blank" rel="noopener noreferrer"><?php echo $renderer::esc($partnerBandWebsiteLabel); ?></a><?php endif; ?>
                <?php if ($partnerBandMapLabel !== '' && $partnerBandMapUrl !== ''): ?><a href="<?php echo $renderer::esc($partnerBandMapUrl); ?>" target="_blank" rel="noopener noreferrer"><?php echo $renderer::esc($partnerBandMapLabel); ?></a><?php endif; ?>
            </span>
        </aside>
    <?php endif; ?>

    <?php $renderProofSection($proofItems); ?>

    <?php $renderBookingSection($bookingUrl); ?>

    <?php if (!empty($page['show_anchor_nav']) && $anchors !== []): ?>
        <nav class="cms-beratung__anchor-nav cms-beratung__anchor-nav--<?php echo $renderer::esc($anchorNavLayout); ?>" aria-label="Anker Navigation" id="beratung-inhalte">
            <span class="cms-beratung__anchor-nav-title">Inhalte</span>
            <?php foreach ($anchors as $navIndex => $section): ?><a href="#<?php echo $renderer::esc((string) ($section['anchor_id'] ?? $section['id'] ?? 'bereich')); ?>"><span class="cms-beratung__anchor-nav-index"><?php echo $renderer::esc(str_pad((string) ($navIndex + 1), 2, '0', STR_PAD_LEFT)); ?></span><span class="cms-beratung__anchor-nav-text"><?php echo $renderer::esc((string) ($section['title'] ?? $section['internal_name'] ?? 'Bereich')); ?></span></a><?php endforeach; ?>
            <a href="#kontakt"><span class="cms-beratung__anchor-nav-index"><?php echo $renderer::esc(str_pad((string) (count($anchors) + 1), 2, '0', STR_PAD_LEFT)); ?></span><span class="cms-beratung__anchor-nav-text">Kontakt</span></a>
        </nav>
    <?php endif; ?>

    <?php $sectionNumber = 0; foreach ($sections as $section): ?>
        <?php if (empty($section['enabled'])) { continue; } ?>
        <?php $columns = max(1, min(4, (int) ($section['columns'] ?? 3))); $sectionId = (string) ($section['anchor_id'] ?? $section['id'] ?? 'bereich'); $sectionType = (string) ($section['type'] ?? 'card_grid'); $cards = is_array($section['cards'] ?? null) ? $section['cards'] : []; ?>
        <?php if ($sectionType === 'faq') { continue; } ?>
        <?php if ($sectionType === 'collaboration' && empty($section['expert_ids'])) { continue; } ?>
        <?php $sectionNumber++; $rhythmClass = $sectionType === 'cta' ? 'cms-beratung__section--rhythm-accent' : (($sectionNumber % 2 === 0) ? 'cms-beratung__section--rhythm-soft' : 'cms-beratung__section--rhythm-white'); ?>
        <section class="cms-beratung__section cms-beratung__section--<?php echo $renderer::esc($sectionType); ?> cms-beratung__section--display-<?php echo $renderer::esc((string) ($section['display_style'] ?? 'cards')); ?> <?php echo $renderer::esc($rhythmClass); ?> <?php echo !empty($section['equal_height']) ? 'has-equal-cards' : ''; ?>" id="<?php echo $renderer::esc($sectionId); ?>" style="--section-bg: <?php echo $renderer::esc((string) ($section['background_color'] ?? '#ffffff')); ?>; --section-text: <?php echo $renderer::esc((string) ($section['text_color'] ?? '#111827')); ?>; --section-pt: <?php echo (int) ($section['padding_top'] ?? 56); ?>px; --section-pb: <?php echo (int) ($section['padding_bottom'] ?? 56); ?>px; --section-width: <?php echo (int) ($section['max_width'] ?? 1200); ?>px; --section-align: <?php echo $renderer::esc((string) ($section['text_align'] ?? 'left')); ?>; <?php if (!empty($section['background_image_url'])): ?>--section-bg-image:url('<?php echo $renderer::esc((string) $section['background_image_url']); ?>');<?php endif; ?>">
            <div class="cms-beratung__section-inner">
                <?php if (!in_array($sectionType, ['divider'], true)): ?>
                    <div class="cms-beratung__section-head"><?php if ($hasText($section['eyebrow'] ?? '')): ?><span><?php echo $renderer::esc((string) $section['eyebrow']); ?></span><?php endif; ?><?php if ($hasText($section['title'] ?? '')): ?><h2><?php echo $renderer::esc((string) $section['title']); ?></h2><?php endif; ?><?php if ($hasText($section['intro'] ?? '')): ?><p><?php echo $renderer::esc((string) $section['intro']); ?></p><?php endif; ?></div>
                <?php endif; ?>

                <?php if ($sectionType === 'divider'): ?><?php $renderDivider($section); ?>
                <?php elseif ($sectionType === 'html' && !empty($section['html'])): ?><div class="cms-beratung__html"><?php echo $renderer::safe_html((string) $section['html']); ?></div>
                <?php elseif ($sectionType === 'comparison'): ?><?php $renderComparison($section, $cards, $columns); ?>
                <?php elseif ($sectionType === 'faq'): ?><?php $renderFaq($section, $cards, $sectionId); ?>
                <?php elseif ($sectionType === 'collaboration'): ?><?php $renderCollaboration($section, $columns); ?>
                <?php elseif ($sectionType === 'cta'): ?><div class="cms-beratung-cta cms-beratung-cta--<?php echo $renderer::esc((string) ($section['display_style'] ?? 'large')); ?>"><?php $renderSectionActions($section); ?></div>
                <?php elseif ($cards !== []): ?><?php $renderCardsGrid($section, $cards, $columns); ?>
                <?php endif; ?>

                <?php if ($hasText($section['note_text'] ?? '')): ?><p class="cms-beratung-module__note"><?php echo $renderer::esc((string) $section['note_text']); ?></p><?php endif; ?>
                <?php if (!in_array($sectionType, ['cta', 'divider'], true)) { $renderSectionActions($section); } ?>
            </div>
        </section>
    <?php endforeach; ?>

    <?php if (!empty($m365Faq['enabled'])): ?>
        <?php $faqItems = is_array($m365Faq['items'] ?? null) ? $m365Faq['items'] : []; ?>
        <?php if ($faqItems !== []): ?>
            <?php $faqSection = ['faq_allow_multiple' => !empty($m365Faq['allow_multiple']), 'faq_icon_style' => (string) ($m365Faq['icon_style'] ?? 'plus'), 'faq_open_behavior' => (string) ($m365Faq['open_behavior'] ?? 'first'), 'faq_question_background_color' => '#ffffff', 'faq_question_text_color' => '#111827', 'faq_answer_background_color' => '#f8fafc']; ?>
            <section class="cms-beratung__section cms-beratung__section--faq" id="<?php echo $renderer::esc((string) ($m365Faq['anchor_id'] ?? 'faq')); ?>" style="--section-bg: transparent; --section-text: var(--beratung-text); --section-pt: 25px; --section-pb: 25px; --section-width: 1160px; --section-align: left;">
                <div class="cms-beratung__section-inner">
                    <div class="cms-beratung__section-head"><?php if ($hasText($m365Faq['eyebrow'] ?? 'FAQ')): ?><span><?php echo $renderer::esc((string) ($m365Faq['eyebrow'] ?? 'FAQ')); ?></span><?php endif; ?><?php if ($hasText($m365Faq['title'] ?? 'Häufige Fragen')): ?><h2><?php echo $renderer::esc((string) ($m365Faq['title'] ?? 'Häufige Fragen')); ?></h2><?php endif; ?><?php if ($hasText($m365Faq['intro'] ?? '')): ?><p><?php echo $renderer::esc((string) $m365Faq['intro']); ?></p><?php endif; ?></div>
                    <?php $renderFaq($faqSection, $faqItems, (string) ($m365Faq['anchor_id'] ?? 'faq')); ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (($contact['enabled'] ?? true) !== false): ?>
    <?php $contactId = (string) ($contact['anchor_id'] ?? 'kontakt'); $contactMode = (string) ($contact['mode'] ?? 'form'); ?>
    <section class="cms-beratung__contact" id="<?php echo $renderer::esc($contactId); ?>" aria-labelledby="beratung-contact-title" style="--contact-bg:<?php echo $renderer::esc((string) ($contact['background_color'] ?? '#ffffff')); ?>;--contact-text:<?php echo $renderer::esc((string) ($contact['text_color'] ?? '#111827')); ?>;">
        <div class="cms-beratung__contact-content"><?php if ($hasText($contact['eyebrow'] ?? 'Kontakt')): ?><span class="cms-beratung__kicker"><?php echo $renderer::esc((string) ($contact['eyebrow'] ?? 'Kontakt')); ?></span><?php endif; ?><h2 id="beratung-contact-title"><?php echo $renderer::esc((string) ($contact['title'] ?? 'Beratungsanfrage senden')); ?></h2><?php if ($hasText($contact['description'] ?? '')): ?><p><?php echo $renderer::esc((string) $contact['description']); ?></p><?php endif; ?><?php if (!empty($contact['image_url'])): ?><img src="<?php echo $renderer::esc((string) $contact['image_url']); ?>" alt="<?php echo $renderer::esc((string) ($contact['title'] ?? 'Kontakt')); ?>" loading="lazy"><?php endif; ?><?php if ($hasText($contact['note_text'] ?? '')): ?><p class="cms-beratung-module__note"><?php echo $renderer::esc((string) $contact['note_text']); ?></p><?php endif; ?></div>
        <?php if ($contactMode === 'button'): ?>
            <div class="cms-beratung-module__actions"><?php $renderLinkButton((string) ($contact['button_text'] ?? 'Kontakt aufnehmen'), (string) ($contact['button_target'] ?? '#kontakt'), (string) ($contact['button_style'] ?? 'primary')); ?><?php $renderLinkButton((string) ($contact['button_2_text'] ?? ''), (string) ($contact['button_2_target'] ?? ''), (string) ($contact['button_2_style'] ?? 'ghost')); ?></div>
        <?php else: ?>
        <form method="post" class="cms-beratung-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $renderer::esc($csrfToken); ?>"><input type="hidden" name="beratung_form_action" value="submit_request"><label class="cms-beratung-form__hp">Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            <?php if (($formResult['message'] ?? '') !== ''): ?><div class="cms-beratung-form__notice <?php echo !empty($formResult['success']) ? 'is-success' : 'is-error'; ?>" role="status"><?php echo $renderer::esc((string) $formResult['message']); ?></div><?php endif; ?>
            <div class="cms-beratung-form__grid"><label for="beratung-name">Name*<input id="beratung-name" name="sender_name" value="<?php echo $renderer::esc((string) ($formValues['sender_name'] ?? '')); ?>" required aria-invalid="<?php echo isset($formErrors['sender_name']) ? 'true' : 'false'; ?>"><?php if (isset($formErrors['sender_name'])): ?><small role="alert"><?php echo $renderer::esc((string) $formErrors['sender_name']); ?></small><?php endif; ?></label><label for="beratung-email">E-Mail*<input id="beratung-email" name="sender_email" type="email" value="<?php echo $renderer::esc((string) ($formValues['sender_email'] ?? '')); ?>" required aria-invalid="<?php echo isset($formErrors['sender_email']) ? 'true' : 'false'; ?>"><?php if (isset($formErrors['sender_email'])): ?><small role="alert"><?php echo $renderer::esc((string) $formErrors['sender_email']); ?></small><?php endif; ?></label><label for="beratung-phone">Telefon<input id="beratung-phone" name="phone" value="<?php echo $renderer::esc((string) ($formValues['phone'] ?? '')); ?>"></label><label for="beratung-company">Unternehmen<input id="beratung-company" name="company" value="<?php echo $renderer::esc((string) ($formValues['company'] ?? '')); ?>"></label></div>
            <label for="beratung-service">Wunschleistung<select id="beratung-service" name="desired_service"><option value="">Bitte wählen</option><?php foreach (CMS_Beratung_Forms::desired_services() as $serviceKey => $serviceLabel): ?><option value="<?php echo $renderer::esc($serviceKey); ?>"<?php echo (($formValues['desired_service'] ?? '') === $serviceKey) ? ' selected' : ''; ?>><?php echo $renderer::esc($serviceLabel); ?></option><?php endforeach; ?></select></label>
            <label for="beratung-topic">Thema<input id="beratung-topic" name="topic" value="<?php echo $renderer::esc((string) ($formValues['topic'] ?? '')); ?>" placeholder="z. B. Copilot Readiness, Security Workshop, Purview Compliance"></label><label for="beratung-message">Nachricht*<textarea id="beratung-message" name="message" rows="5" required aria-invalid="<?php echo isset($formErrors['message']) ? 'true' : 'false'; ?>"><?php echo $renderer::esc((string) ($formValues['message'] ?? '')); ?></textarea><?php if (isset($formErrors['message'])): ?><small role="alert"><?php echo $renderer::esc((string) $formErrors['message']); ?></small><?php endif; ?></label><label class="cms-beratung-form__check"><input type="checkbox" name="consent" value="1" required aria-invalid="<?php echo isset($formErrors['consent']) ? 'true' : 'false'; ?>"> <span><?php echo $renderer::esc((string) ($contact['privacy_text'] ?? $settings['privacy_text'] ?? 'Ich stimme der Verarbeitung meiner Angaben zu.')); ?></span></label><?php if (isset($formErrors['consent'])): ?><small class="cms-beratung-form__error" role="alert"><?php echo $renderer::esc((string) $formErrors['consent']); ?></small><?php endif; ?>
            <?php if (($settings['sender_copy_enabled'] ?? '0') === '1'): ?><label class="cms-beratung-form__check"><input type="checkbox" name="copy_to_sender" value="1"> <span>Kopie an mich senden</span></label><?php endif; ?>
            <button class="cms-beratung__btn cms-beratung__btn--form-submit" type="submit">Anfrage senden</button>
        </form>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</main>
