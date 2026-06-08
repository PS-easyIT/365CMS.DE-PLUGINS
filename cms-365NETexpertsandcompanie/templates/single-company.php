<?php
/**
 * Public Detail Template – Company.
 *
 * @package CMS_365NET_ExpertsAndCompanie
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('CMS_365NET_Experts_And_Companie')) {
    CMS_365NET_Experts_And_Companie::printInlineStyle('style.css', 'cms-excomp-public-inline');
}

$company = is_object($company ?? null) ? $company : null;
if ($company === null) {
    return;
}

$base = rtrim((string) SITE_URL, '/');
$e = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$renderEditor = static function (mixed $json, mixed $fallback): string {
    $json = (string) $json;
    if ($json !== '' && class_exists('CMS\\Services\\EditorJsRenderer')) {
        $rendered = (string) CMS\Services\EditorJsRenderer::getInstance()->render($json);
        $plain = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($rendered), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $hasMediaBlocks = preg_match('/<(img|video|audio|iframe|table|ul|ol|blockquote|pre|h[1-6])\b/i', $rendered) === 1;
        if ($plain !== '' || $hasMediaBlocks) {
            return $rendered;
        }
    }

    $fallback = trim((string) $fallback);
    return $fallback !== ''
        ? '<div class="cms-excomp-copy">' . nl2br(htmlspecialchars($fallback, ENT_QUOTES, 'UTF-8')) . '</div>'
        : '';
};

$name = trim((string) ($company->name ?? ''));
$name = $name !== '' ? $name : ('Company #' . (int) ($company->id ?? 0));
$industry = trim((string) ($company->industry ?? ''));
$city = trim((string) ($company->location_city ?? $company->city ?? ''));
$country = trim((string) ($company->country ?? ''));
$website = trim((string) ($company->website ?? ''));
$email = trim((string) ($company->email ?? ''));
$phone = trim((string) ($company->phone ?? ''));
$companySize = trim((string) ($company->company_size ?? ''));

$partnerLabel = 'Unternehmen';
if ((int) ($company->is_sponsor ?? 0) === 1) {
    $partnerLabel = 'Sponsor';
} elseif ((int) ($company->is_top_partner ?? 0) === 1) {
    $partnerLabel = 'Top-Partner';
} elseif ((int) ($company->is_partner ?? 0) === 1) {
    $partnerLabel = 'Partner';
}

$linkedExpert = is_object($linkedExpert ?? null) ? $linkedExpert : null;
$linkedSpeaker = is_object($linkedSpeaker ?? null) ? $linkedSpeaker : null;
$linkedExperts = is_array($linkedExperts ?? null) ? $linkedExperts : [];
$linkedSpeakers = is_array($linkedSpeakers ?? null) ? $linkedSpeakers : [];

$metaItems = array_values(array_filter([$industry, $city, $country, $partnerLabel], static fn(string $value): bool => $value !== ''));

$companyInitials = 'CO';
$parts = preg_split('/\s+/', $name) ?: [];
$letters = '';
foreach ($parts as $part) {
    $letters .= strtoupper(substr((string) $part, 0, 1));
    if (strlen($letters) >= 2) {
        break;
    }
}
if ($letters !== '') {
    $companyInitials = substr($letters, 0, 2);
}
?>

<main class="cms-excomp-public cms-excomp-detail cms-excomp-detail--company">
    <div class="cms-excomp-container">
        <nav class="cms-excomp-breadcrumb">
            <a href="<?= $e($base . '/companies') ?>">Companies</a>
            <span>/</span>
            <span><?= $e($name) ?></span>
        </nav>

        <article class="cms-excomp-detail-layout">
            <header class="cms-excomp-detail-header cms-excomp-detail-header--company">
                <div class="cms-excomp-detail-header__identity">
                    <span class="cms-excomp-avatar cms-excomp-avatar--fallback cms-excomp-avatar--company cms-excomp-avatar--detail"><?= $e($companyInitials) ?></span>
                    <div class="cms-excomp-detail-header__text">
                        <h1><?= $e($name) ?></h1>
                        <?php if ($metaItems !== []): ?>
                            <div class="cms-excomp-detail-header__meta"><?php foreach ($metaItems as $meta): ?><span><?= $e($meta) ?></span><?php endforeach; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <div class="cms-excomp-detail-layout__body">
                <section class="cms-excomp-detail-main">
                    <?= $renderEditor($company->description_json ?? '', $company->description ?? '') ?>
                </section>

                <aside class="cms-excomp-sidebar">
                    <section class="cms-excomp-sidecard">
                        <h2>Kontakt</h2>
                        <dl class="cms-excomp-meta-list">
                            <?php if ($companySize !== ''): ?><div><dt>Größe</dt><dd><?= $e($companySize) ?></dd></div><?php endif; ?>
                            <?php if ($email !== ''): ?><div><dt>E-Mail</dt><dd><a href="mailto:<?= $e($email) ?>"><?= $e($email) ?></a></dd></div><?php endif; ?>
                            <?php if ($phone !== ''): ?><div><dt>Telefon</dt><dd><?= $e($phone) ?></dd></div><?php endif; ?>
                            <?php if ($website !== ''): ?><div><dt>Website</dt><dd><a href="<?= $e($website) ?>" target="_blank" rel="noopener noreferrer"><?= $e($website) ?></a></dd></div><?php endif; ?>
                        </dl>
                    </section>

                    <?php if ($linkedExpert !== null || $linkedSpeaker !== null): ?>
                        <section class="cms-excomp-sidecard">
                            <h2>Direkt verknüpft</h2>
                            <div class="cms-excomp-linked cms-excomp-linked--stack">
                                <?php if ($linkedExpert !== null): ?>
                                    <?php
                                    $linkedExpertId = (int) ($linkedExpert->id ?? 0);
                                    $linkedExpertName = trim((string) (($linkedExpert->first_name ?? '') . ' ' . ($linkedExpert->last_name ?? '')));
                                    if ($linkedExpertName === '') {
                                        $linkedExpertName = 'Expert #' . $linkedExpertId;
                                    }
                                    ?>
                                    <a class="cms-excomp-linked-item" href="<?= $e($linkedExpertId > 0 ? ($base . '/experts/' . $linkedExpertId) : ($base . '/experts')) ?>">👤 <?= $e($linkedExpertName) ?></a>
                                <?php endif; ?>
                                <?php if ($linkedSpeaker !== null): ?>
                                    <?php $linkedSpeakerSlug = trim((string) ($linkedSpeaker->slug ?? '')); ?>
                                    <a class="cms-excomp-linked-item" href="<?= $e($linkedSpeakerSlug !== '' ? ($base . '/speakers/' . rawurlencode($linkedSpeakerSlug)) : ($base . '/speakers')) ?>">🎤 <?= $e((string) ($linkedSpeaker->display_name ?? 'Speaker')) ?></a>
                                <?php endif; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </aside>
            </div>

            <?php if ($linkedExperts !== [] || $linkedSpeakers !== []): ?>
                <section class="cms-excomp-detail-related">
                    <?php if ($linkedExperts !== []): ?>
                        <div class="cms-excomp-sidecard">
                            <h2>Expert:innen</h2>
                            <div class="cms-excomp-linked">
                                <?php foreach ($linkedExperts as $expert): ?>
                                    <?php
                                    if (!is_object($expert)) {
                                        continue;
                                    }
                                    $expertId = (int) ($expert->id ?? 0);
                                    $expertName = trim((string) (($expert->first_name ?? '') . ' ' . ($expert->last_name ?? '')));
                                    if ($expertName === '') {
                                        $expertName = 'Expert #' . $expertId;
                                    }
                                    ?>
                                    <a class="cms-excomp-linked-item" href="<?= $e($expertId > 0 ? ($base . '/experts/' . $expertId) : ($base . '/experts')) ?>">👤 <?= $e($expertName) ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($linkedSpeakers !== []): ?>
                        <div class="cms-excomp-sidecard">
                            <h2>Speaker</h2>
                            <div class="cms-excomp-linked">
                                <?php foreach ($linkedSpeakers as $speaker): ?>
                                    <?php
                                    if (!is_object($speaker)) {
                                        continue;
                                    }
                                    $speakerName = trim((string) ($speaker->display_name ?? ''));
                                    if ($speakerName === '') {
                                        $speakerName = 'Speaker #' . (int) ($speaker->id ?? 0);
                                    }
                                    $speakerSlug = trim((string) ($speaker->slug ?? ''));
                                    ?>
                                    <a class="cms-excomp-linked-item" href="<?= $e($speakerSlug !== '' ? ($base . '/speakers/' . rawurlencode($speakerSlug)) : ($base . '/speakers')) ?>">🎤 <?= $e($speakerName) ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </article>
    </div>
</main>
