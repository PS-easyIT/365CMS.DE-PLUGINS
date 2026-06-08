<?php
/**
 * Public Detail Template – Expert.
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

$expert = is_object($expert ?? null) ? $expert : null;
if ($expert === null) {
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

$firstName = trim((string) ($expert->first_name ?? ''));
$lastName = trim((string) ($expert->last_name ?? ''));
$fullName = trim($firstName . ' ' . $lastName);
$fullName = $fullName !== '' ? $fullName : ('Expert #' . (int) ($expert->id ?? 0));

$position = trim((string) ($expert->position ?? ''));
$company = trim((string) ($expert->company ?? ''));
$location = trim((string) ($expert->location_city ?? $expert->city ?? ''));
$country = trim((string) ($expert->country ?? ''));
$availability = trim((string) ($expert->availability ?? ''));
$website = trim((string) ($expert->website ?? ''));
$skillsGeneral = trim((string) ($expert->skills_general ?? ''));
$skillsTech = trim((string) ($expert->skills_tech ?? ''));
$skillsSoft = trim((string) ($expert->skills_soft ?? ''));

$linkedCompany = is_object($linkedCompany ?? null) ? $linkedCompany : null;
$linkedSpeaker = is_object($linkedSpeaker ?? null) ? $linkedSpeaker : null;

$metaItems = array_values(array_filter([$position, $company, $location, $country], static fn(string $value): bool => $value !== ''));
$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
if ($initials === '') {
    $initials = 'EX';
}
?>

<main class="cms-excomp-public cms-excomp-detail cms-excomp-detail--expert">
    <div class="cms-excomp-container">
        <nav class="cms-excomp-breadcrumb">
            <a href="<?= $e($base . '/experts') ?>">Experts</a>
            <span>/</span>
            <span><?= $e($fullName) ?></span>
        </nav>

        <article class="cms-excomp-detail-layout">
            <header class="cms-excomp-detail-header">
                <div class="cms-excomp-detail-header__identity">
                    <span class="cms-excomp-avatar cms-excomp-avatar--fallback cms-excomp-avatar--detail"><?= $e($initials) ?></span>
                    <div class="cms-excomp-detail-header__text">
                        <h1><?= $e($fullName) ?></h1>
                        <?php if ($metaItems !== []): ?>
                            <div class="cms-excomp-detail-header__meta"><?php foreach ($metaItems as $meta): ?><span><?= $e($meta) ?></span><?php endforeach; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <div class="cms-excomp-detail-layout__body">
                <section class="cms-excomp-detail-main">
                    <?= $renderEditor($expert->biography_json ?? '', $expert->biography ?? '') ?>
                </section>

                <aside class="cms-excomp-sidebar">
                    <section class="cms-excomp-sidecard">
                        <h2>Profil</h2>
                        <dl class="cms-excomp-meta-list">
                            <?php if ($availability !== ''): ?><div><dt>Verfügbarkeit</dt><dd><?= $e($availability) ?></dd></div><?php endif; ?>
                            <?php if ($skillsGeneral !== ''): ?><div><dt>Skills</dt><dd><?= $e($skillsGeneral) ?></dd></div><?php endif; ?>
                            <?php if ($skillsTech !== ''): ?><div><dt>Tech</dt><dd><?= $e($skillsTech) ?></dd></div><?php endif; ?>
                            <?php if ($skillsSoft !== ''): ?><div><dt>Soft Skills</dt><dd><?= $e($skillsSoft) ?></dd></div><?php endif; ?>
                        </dl>
                    </section>

                    <?php if ($linkedCompany !== null || $linkedSpeaker !== null || $website !== ''): ?>
                        <section class="cms-excomp-sidecard">
                            <h2>Verknüpfungen</h2>
                            <div class="cms-excomp-linked cms-excomp-linked--stack">
                                <?php if ($linkedCompany !== null): ?>
                                    <?php $linkedCompanyId = (int) ($linkedCompany->id ?? 0); ?>
                                    <a class="cms-excomp-linked-item" href="<?= $e($linkedCompanyId > 0 ? ($base . '/companies/' . $linkedCompanyId) : ($base . '/companies')) ?>">🏢 <?= $e((string) ($linkedCompany->name ?? 'Company')) ?></a>
                                <?php endif; ?>
                                <?php if ($linkedSpeaker !== null): ?>
                                    <?php $speakerSlug = trim((string) ($linkedSpeaker->slug ?? '')); ?>
                                    <a class="cms-excomp-linked-item" href="<?= $e($speakerSlug !== '' ? ($base . '/speakers/' . rawurlencode($speakerSlug)) : ($base . '/speakers')) ?>">🎤 <?= $e((string) ($linkedSpeaker->display_name ?? 'Speaker')) ?></a>
                                <?php endif; ?>
                                <?php if ($website !== ''): ?>
                                    <a class="cms-excomp-linked-item" href="<?= $e($website) ?>" target="_blank" rel="noopener noreferrer">🌐 Website</a>
                                <?php endif; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </aside>
            </div>
        </article>
    </div>
</main>
