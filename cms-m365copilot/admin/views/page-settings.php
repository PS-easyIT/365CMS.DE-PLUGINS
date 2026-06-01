<?php
/**
 * CMS M365 Copilot – grouped settings view.
 *
 * @package CMS_M365Copilot
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$value = static fn(string $key): string => (string) ($settings[$key] ?? '');
$layoutOptions = ['1' => 'Layout 1', '2' => 'Layout 2', '3' => 'Layout 3'];
$section = static function (string $id, string $title, string $text) use ($esc): void {
    echo '<section class="m365cp-admin-section" id="' . $esc($id) . '">';
    echo '<div class="m365cp-admin-section__head"><h3>' . $esc($title) . '</h3><p>' . $esc($text) . '</p></div>';
};
$endSection = static function (): void {
    echo '</section>';
};
$input = static function (string $key, string $label, string $help = '', string $type = 'text', int $max = 255) use ($esc, $value): void {
    echo '<div class="form-group">';
    echo '<label class="form-label" for="' . $esc($key) . '">' . $esc($label) . '</label>';
    echo '<input type="' . $esc($type) . '" id="' . $esc($key) . '" name="' . $esc($key) . '" class="form-control" value="' . $esc($value($key)) . '" maxlength="' . (int) $max . '">';
    if ($help !== '') {
        echo '<small class="form-text">' . $esc($help) . '</small>';
    }
    echo '</div>';
};
$textarea = static function (string $key, string $label, string $help = '') use ($esc, $value): void {
    echo '<div class="form-group m365cp-admin-field--wide">';
    echo '<label class="form-label" for="' . $esc($key) . '">' . $esc($label) . '</label>';
    echo '<textarea id="' . $esc($key) . '" name="' . $esc($key) . '" class="form-control" rows="3">' . $esc($value($key)) . '</textarea>';
    if ($help !== '') {
        echo '<small class="form-text">' . $esc($help) . '</small>';
    }
    echo '</div>';
};
$select = static function (string $key, string $label, array $options, string $help = '') use ($esc, $value): void {
    echo '<div class="form-group">';
    echo '<label class="form-label" for="' . $esc($key) . '">' . $esc($label) . '</label>';
    echo '<select id="' . $esc($key) . '" name="' . $esc($key) . '" class="form-control">';
    $current = $value($key);
    foreach ($options as $optionValue => $optionLabel) {
        echo '<option value="' . $esc($optionValue) . '"' . ($current === (string) $optionValue ? ' selected' : '') . '>' . $esc($optionLabel) . '</option>';
    }
    echo '</select>';
    if ($help !== '') {
        echo '<small class="form-text">' . $esc($help) . '</small>';
    }
    echo '</div>';
};
$number = static function (string $key, string $label, int $min, int $max, string $help = '') use ($esc, $value): void {
    echo '<div class="form-group">';
    echo '<label class="form-label" for="' . $esc($key) . '">' . $esc($label) . '</label>';
    echo '<input type="number" id="' . $esc($key) . '" name="' . $esc($key) . '" class="form-control" value="' . $esc($value($key)) . '" min="' . (int) $min . '" max="' . (int) $max . '" step="1">';
    if ($help !== '') {
        echo '<small class="form-text">' . $esc($help) . '</small>';
    }
    echo '</div>';
};
?>

<div class="m365cp-admin-shell">
    <div class="admin-page-header">
        <div>
            <h2>🤖 M365 Copilot Landing</h2>
            <p>Eine konfigurierbare PHINIT-Landingpage für Copilot-Beratung und Lizenzvertrieb.</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo $esc($publicUrl); ?>" class="btn btn-primary" target="_blank" rel="noopener noreferrer">👁️ Public öffnen</a>
        </div>
    </div>

    <?php if ($notice !== ''): ?>
    <div class="alert alert-success">✅ <?php echo $esc($notice); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <div class="alert alert-error">❌ <?php echo $esc($error); ?></div>
    <?php endif; ?>

    <div class="m365cp-tabs" aria-label="Einstellungsbereiche">
        <?php foreach ($tabs as $tabKey => $tabLabel): ?>
        <a class="m365cp-tab<?php echo $tab === $tabKey ? ' active' : ''; ?>" href="?page=m365copilot-settings&amp;tab=<?php echo $esc($tabKey); ?>"><?php echo $esc($tabLabel); ?></a>
        <?php endforeach; ?>
    </div>

    <div class="admin-card m365cp-admin-card">
        <form method="POST" class="admin-form">
            <input type="hidden" name="action" value="save_settings">
            <input type="hidden" name="csrf_token" value="<?php echo $esc($csrfToken); ?>">

            <?php if ($tab === 'content-header'): ?>
            <?php $section('content-header', '🖼️ Content Header', 'Hero-Inhalt, Bild, CTA und Layoutvariante.'); ?>
            <div class="m365cp-admin-grid">
                <?php $input('route_slug', 'Public Route', 'Ohne führenden Slash, z. B. microsoft-365-copilot.'); ?>
                <?php $select('header_layout', 'Header Layout', ['1' => '1 – Bild links', '2' => '2 – Full-width Background', '3' => '3 – Zentriertes Overlay']); ?>
                <?php $input('header_image_url', 'Header Bild URL', 'Medienpfad oder öffentliche URL.'); ?>
                <?php $input('header_image_alt', 'Header Bild Alt-Text'); ?>
                <?php $input('header_title', 'Titel'); ?>
                <?php $input('header_cta_label', 'CTA Label'); ?>
                <?php $input('header_cta_url', 'CTA URL'); ?>
                <?php $textarea('header_intro', 'Subtitle / Intro'); ?>
            </div>
            <?php $endSection(); ?>
            <?php endif; ?>

            <?php if ($tab === 'service-band'): ?>
            <?php $section('service-band', '🤝 Dienstleistungsband', 'Beratungs- und Sales-CTA direkt am unteren Header-Rand.'); ?>
            <label class="checkbox-label m365cp-admin-toggle">
                <input type="hidden" name="service_enabled" value="0">
                <input type="checkbox" name="service_enabled" value="1"<?php echo $value('service_enabled') === '1' ? ' checked' : ''; ?>>
                Dienstleistungsband anzeigen
            </label>
            <div class="m365cp-admin-grid">
                <?php $input('service_section_label', 'Band Label', 'Text im linken Abschnittslabel.'); ?>
                <?php $select('service_layout', 'Band Layout', ['1' => '1 – Logo + Text + Button', '2' => '2 – Zentriert', '3' => '3 – Split']); ?>
                <?php $input('service_logo_url', 'Service Logo URL'); ?>
                <?php $input('service_logo_alt', 'Service Logo Alt-Text'); ?>
                <?php $input('service_cta_label', 'Kontakt CTA Label'); ?>
                <?php $input('service_cta_url', 'Kontakt CTA URL'); ?>
                <?php $input('service_section_aria_label', 'Band ARIA Label', 'Für Screenreader / Accessibility.'); ?>
                <?php $textarea('service_text', 'Kurztext'); ?>
            </div>
            <?php $endSection(); ?>
            <?php endif; ?>

            <?php if ($tab === 'cards'): ?>
            <?php $section('cards', '🃏 Bereichscards', 'Drei steuerbare Einstiegsflächen für Lizenzen, Informationen und Datenschutz.'); ?>
            <div class="m365cp-admin-grid">
                <?php $input('cards_link_label', 'Card Link Label', 'Wird auf allen drei Cards genutzt, z. B. Mehr erfahren →'); ?>
                <?php $input('cards_section_aria_label', 'Cards ARIA Label', 'Für Screenreader / Accessibility.'); ?>
            </div>
            <?php for ($i = 1; $i <= 3; $i++): ?>
            <div class="m365cp-admin-cardset">
                <h4>Card <?php echo (int) $i; ?></h4>
                <div class="m365cp-admin-grid">
                    <?php $input('card_' . $i . '_title', 'Titel'); ?>
                    <?php $input('card_' . $i . '_url', 'Ziel URL'); ?>
                    <?php $input('card_' . $i . '_image_url', 'Icon/Bild URL'); ?>
                    <?php $input('card_' . $i . '_image_alt', 'Icon/Bild Alt-Text'); ?>
                    <?php $textarea('card_' . $i . '_text', 'Kurztext'); ?>
                </div>
            </div>
            <?php endfor; ?>

            <div class="m365cp-admin-cardset">
                <h4>Hero Cards – Dienstleistungsinfos</h4>
                <label class="checkbox-label m365cp-admin-toggle">
                    <input type="hidden" name="service_cards_show" value="0">
                    <input type="checkbox" name="service_cards_show" value="1"<?php echo $value('service_cards_show') === '1' ? ' checked' : ''; ?>>
                    Hero-Dienstleistungs-Cards anzeigen (zwischen Bereichscards und Beiträgen)
                </label>
                <div class="m365cp-admin-grid">
                    <?php $input('service_cards_section_label', 'Section Label', 'Kleines Label über den Hero-Cards.'); ?>
                    <?php $input('service_cards_aria_label', 'Section ARIA Label', 'Für Screenreader / Accessibility.'); ?>
                    <?php $input('service_cards_title', 'Überschrift'); ?>
                    <?php $textarea('service_cards_intro', 'Introtext'); ?>
                </div>

                <?php for ($i = 1; $i <= 3; $i++): ?>
                <div class="m365cp-admin-grid">
                    <?php $input('service_info_' . $i . '_title', 'Hero Card ' . $i . ' Titel'); ?>
                    <?php $textarea('service_info_' . $i . '_text', 'Hero Card ' . $i . ' Text'); ?>
                </div>
                <?php endfor; ?>
            </div>

            <?php $endSection(); ?>
            <?php endif; ?>

            <?php if ($tab === 'posts'): ?>
            <?php $section('posts', '📰 Beiträge', 'Aktuelle Beiträge per bestehender Kategorie-/Post-Abfrage.'); ?>
            <label class="checkbox-label m365cp-admin-toggle">
                <input type="hidden" name="posts_show" value="0">
                <input type="checkbox" name="posts_show" value="1"<?php echo $value('posts_show') === '1' ? ' checked' : ''; ?>>
                Beitragsbereich anzeigen
            </label>
            <div class="m365cp-admin-grid">
                <?php $input('posts_section_label', 'Section Label', 'Kleines Label oberhalb der Beitragsüberschrift.'); ?>
                <?php $input('posts_title', 'Bereichstitel'); ?>
                <?php $number('posts_count', 'Anzahl Beiträge', 1, 12); ?>
                <?php $input('posts_readmore_label', 'Read-More Label'); ?>
                <?php $input('posts_empty_title', 'Empty-State Titel'); ?>
                <div class="form-group">
                    <label class="form-label" for="posts_category">Kategorie</label>
                    <input type="text" id="posts_category" name="posts_category" class="form-control" value="<?php echo $esc($value('posts_category')); ?>" list="m365cp-categories" maxlength="190">
                    <datalist id="m365cp-categories">
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $esc((string) ($category['name'] ?? '')); ?>"><?php echo $esc((string) ($category['slug'] ?? '')); ?></option>
                        <?php endforeach; ?>
                    </datalist>
                    <small class="form-text">Default: Microsoft Copilot. Name oder Slug möglich.</small>
                </div>
                <?php $textarea('posts_intro', 'Bereichsintro'); ?>
                <?php $textarea('posts_empty_text', 'Empty-State Text'); ?>
            </div>
            <?php $endSection(); ?>
            <?php endif; ?>

            <?php if ($tab === 'layout'): ?>
            <?php $section('layout', '📐 Global Layout & Spacing', 'Alle Werte werden als CSS-Variablen ausgegeben.'); ?>
            <div class="m365cp-admin-grid">
                <?php $number('layout_content_max_width', 'Content Max-Width px', 720, 1800); ?>
                <?php $number('layout_padding_left', 'Padding links px', 0, 96); ?>
                <?php $number('layout_padding_right', 'Padding rechts px', 0, 96); ?>
                <?php $number('layout_padding_top', 'Padding oben px', 0, 160); ?>
                <?php $number('layout_padding_bottom', 'Padding unten px', 0, 160); ?>
                <?php $number('layout_section_gap', 'Abstand zwischen Sektionen px', 0, 160); ?>
                <?php $number('layout_header_offset', 'Abstand zum Theme-Header px', 0, 160); ?>
                <?php $number('layout_footer_offset', 'Abstand zum Theme-Footer px', 0, 160); ?>
            </div>
            <?php $endSection(); ?>
            <?php endif; ?>

            <?php if ($tab === 'texts'): ?>
            <?php $section('texts', '✍️ Texte & Labels', 'Zentrale Steuerung der verbleibenden sichtbaren Standardtexte.'); ?>
            <div class="m365cp-admin-grid">
                <?php $input('header_cta_label', 'Header CTA Label'); ?>
                <?php $input('service_cta_label', 'Service CTA Label'); ?>
                <?php $input('cards_link_label', 'Cards Link Label'); ?>
                <?php $input('posts_readmore_label', 'Read-More Label'); ?>
                <?php $input('posts_read_aria_prefix', 'Read-More ARIA Präfix'); ?>
                <?php $input('posts_empty_title', 'Empty-Title'); ?>
                <?php $textarea('posts_empty_text', 'Empty-Text'); ?>
            </div>
            <?php $endSection(); ?>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
        </form>
    </div>
</div>
