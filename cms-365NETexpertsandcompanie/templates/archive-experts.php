<?php
/**
 * Public Archive Template – Experts.
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

$experts = array_values(array_filter(array_map(
    static fn($item): ?object => is_object($item) ? $item : (is_array($item) ? (object) $item : null),
    (array) ($experts ?? [])
)));

$filters = is_array($filters ?? null) ? $filters : [];
$q = trim((string) ($filters['q'] ?? ''));
$city = trim((string) ($filters['city'] ?? ''));

$baseUrl = rtrim((string) SITE_URL, '/') . '/experts';
$e = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$expertInitials = static function (object $expert): string {
    $first = trim((string) ($expert->first_name ?? ''));
    $last = trim((string) ($expert->last_name ?? ''));
    $letters = strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
    return $letters !== '' ? $letters : 'EX';
};

$expertName = static function (object $expert): string {
    $first = trim((string) ($expert->first_name ?? ''));
    $last = trim((string) ($expert->last_name ?? ''));
    $full = trim($first . ' ' . $last);
    return $full !== '' ? $full : 'Expert #' . (int) ($expert->id ?? 0);
};
?>

<main class="cms-excomp-public cms-excomp-public--experts">
    <div class="cms-excomp-container">
        <section class="cms-excomp-hero cms-excomp-hero--experts" aria-label="Experts">
            <p class="cms-excomp-kicker">365 Network · Experts</p>
            <h1>Experts</h1>
            <p>Publicsite im Events-/Speaker-Stil: filter-first, responsive Card-Grid und fokussierte Expertenprofile.</p>
        </section>

        <form method="GET" action="<?= $e($baseUrl) ?>" class="cms-excomp-search" role="search" aria-label="Experts Suche">
            <input type="search" name="q" value="<?= $e($q) ?>" placeholder="Name, Firma, Position, Skills …">
            <input type="text" name="city" value="<?= $e($city) ?>" placeholder="Stadt …">
            <button type="submit">Suchen</button>
            <?php if ($q !== '' || $city !== ''): ?>
                <a href="<?= $e($baseUrl) ?>" class="cms-excomp-reset">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="cms-excomp-container cms-excomp-content">
        <section class="cms-excomp-section cms-excomp-section--experts" aria-label="Experts Liste">
            <header class="cms-excomp-section-head">
                <h2>Expert:innen</h2>
                <span><?= count($experts) ?> Treffer</span>
            </header>

            <?php if ($experts === []): ?>
                <div class="cms-excomp-empty">
                    <p>Keine Experts für den aktuellen Filter gefunden.</p>
                </div>
            <?php else: ?>
                <div class="cms-excomp-grid">
                    <?php foreach ($experts as $expert): ?>
                        <?php
                        $name = $expertName($expert);
                        $detailUrl = trim((string) ($expert->detail_url ?? ''));
                        $position = trim((string) ($expert->position ?? ''));
                        $company = trim((string) ($expert->company ?? ''));
                        $location = trim((string) ($expert->location_city ?? $expert->city ?? ''));
                        $availability = trim((string) ($expert->availability ?? ''));
                        $photo = trim((string) ($expert->photo_url ?? ''));
                        $bio = trim((string) ($expert->biography ?? ''));
                        $linkedSpeaker = is_object($expert->linked_speaker ?? null) ? $expert->linked_speaker : null;
                        $linkedCompany = is_object($expert->linked_company ?? null) ? $expert->linked_company : null;
                        $speakerSlug = trim((string) ($linkedSpeaker->slug ?? ''));
                        $speakerName = trim((string) ($linkedSpeaker->display_name ?? ''));
                        $companyName = trim((string) ($linkedCompany->name ?? ''));
                        ?>
                        <article class="cms-excomp-card cms-excomp-card--expert">
                            <div class="cms-excomp-card-head">
                                <?php if ($photo !== ''): ?>
                                    <img src="<?= $e($photo) ?>" alt="<?= $e($name) ?>" loading="lazy" class="cms-excomp-avatar">
                                <?php else: ?>
                                    <span class="cms-excomp-avatar cms-excomp-avatar--fallback"><?= $e($expertInitials($expert)) ?></span>
                                <?php endif; ?>
                                <div>
                                    <h3><?= $e($name) ?></h3>
                                    <?php if ($position !== ''): ?><p><?= $e($position) ?></p><?php endif; ?>
                                </div>
                            </div>

                            <div class="cms-excomp-card-meta">
                                <?php if ($company !== ''): ?><span><?= $e($company) ?></span><?php endif; ?>
                                <?php if ($location !== ''): ?><span><?= $e($location) ?></span><?php endif; ?>
                                <?php if ($availability !== ''): ?><span class="cms-excomp-badge"><?= $e($availability) ?></span><?php endif; ?>
                            </div>

                            <?php if ($linkedSpeaker !== null || $linkedCompany !== null): ?>
                                <div class="cms-excomp-linked">
                                    <?php if ($linkedSpeaker !== null): ?>
                                        <a class="cms-excomp-linked-item" href="<?= $e($speakerSlug !== '' ? (rtrim((string) SITE_URL, '/') . '/speakers/' . rawurlencode($speakerSlug)) : (rtrim((string) SITE_URL, '/') . '/speakers')) ?>">🎤 <?= $e($speakerName !== '' ? $speakerName : 'Speaker') ?></a>
                                    <?php endif; ?>
                                    <?php if ($linkedCompany !== null): ?>
                                        <a class="cms-excomp-linked-item" href="<?= $e(rtrim((string) SITE_URL, '/') . '/companies?q=' . rawurlencode($companyName)) ?>">🏢 <?= $e($companyName !== '' ? $companyName : 'Company') ?></a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($bio !== ''): ?><p class="cms-excomp-card-excerpt"><?= $e($bio) ?></p><?php endif; ?>

                            <?php if ($detailUrl !== ''): ?>
                                <a class="cms-excomp-card-link" href="<?= $e($detailUrl) ?>" target="_blank" rel="noopener noreferrer">Website öffnen</a>
                            <?php else: ?>
                                <span class="cms-excomp-card-link is-disabled">Keine Website hinterlegt</span>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>
