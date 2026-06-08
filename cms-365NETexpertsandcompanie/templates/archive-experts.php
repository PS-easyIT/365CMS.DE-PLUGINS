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

$expertCardExcerpt = static function (object $expert): string {
    $raw = trim((string) ($expert->biography ?? ''));
    if ($raw === '') {
        return '';
    }

    $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    if ($text === '') {
        return '';
    }

    return function_exists('mb_substr')
        ? (string) mb_substr($text, 0, 180, 'UTF-8')
        : substr($text, 0, 180);
};

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

<main class="cms-events-public cms-speakers-archive cms-excomp-public cms-excomp-public--experts">
    <div class="cms-events-container cms-excomp-container">
        <section class="cms-events-hero cms-events-hero--compact cms-excomp-hero cms-excomp-hero--experts" aria-label="Experts">
            <span class="cms-events-kicker">365 Network · Experts</span>
            <h1>Experts</h1>
            <p>Publicsite im Events-/Speaker-Stil: filter-first, responsive Card-Grid und fokussierte Expertenprofile.</p>
        </section>

        <form method="GET" action="<?= $e($baseUrl) ?>" class="cms-events-search cms-events-search--header-card cms-excomp-search" role="search" aria-label="Experts Suche">
            <div class="cms-events-search__field">
                <input type="search" name="q" value="<?= $e($q) ?>" placeholder="Name, Firma, Position, Skills …">
            </div>
            <div class="cms-events-search__field">
                <input type="text" name="city" value="<?= $e($city) ?>" placeholder="Stadt …">
            </div>
            <div class="cms-events-search__actions">
                <button type="submit">Suchen</button>
                <?php if ($q !== '' || $city !== ''): ?>
                    <a href="<?= $e($baseUrl) ?>" class="cms-excomp-reset">Reset</a>
                <?php endif; ?>
            </div>
        </form>

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
                <div class="cms-events-grid cms-excomp-grid">
                    <?php foreach ($experts as $expert): ?>
                        <?php
                        $name = $expertName($expert);
                        $detailUrl = trim((string) ($expert->detail_url ?? ''));
                        $websiteUrl = trim((string) ($expert->website ?? ''));
                        $position = trim((string) ($expert->position ?? ''));
                        $company = trim((string) ($expert->company ?? ''));
                        $location = trim((string) ($expert->location_city ?? $expert->city ?? ''));
                        $availability = trim((string) ($expert->availability ?? ''));
                        $photo = trim((string) ($expert->photo_url ?? ''));
                        $bio = $expertCardExcerpt($expert);
                        $linkedSpeaker = is_object($expert->linked_speaker ?? null) ? $expert->linked_speaker : null;
                        $linkedCompany = is_object($expert->linked_company ?? null) ? $expert->linked_company : null;
                        $speakerSlug = trim((string) ($linkedSpeaker->slug ?? ''));
                        $speakerName = trim((string) ($linkedSpeaker->display_name ?? ''));
                        $companyName = trim((string) ($linkedCompany->name ?? ''));
                        ?>
                        <article class="cms-events-card cms-speaker-card cms-excomp-card cms-excomp-card--expert">
                            <div class="cms-speaker-card__head cms-excomp-card-head">
                                <div class="cms-speaker-card__media">
                                <?php if ($photo !== ''): ?>
                                    <img src="<?= $e($photo) ?>" alt="<?= $e($name) ?>" loading="lazy" class="cms-speaker-photo cms-speaker-photo--card cms-excomp-avatar">
                                <?php else: ?>
                                    <span class="cms-speaker-avatar cms-speaker-avatar--card cms-excomp-avatar cms-excomp-avatar--fallback"><?= $e($expertInitials($expert)) ?></span>
                                <?php endif; ?>
                                </div>
                                <div class="cms-speaker-card__titleblock">
                                    <h2 class="cms-speaker-card__name"><a href="<?= $e($detailUrl !== '' ? $detailUrl : '#') ?>"><?= $e($name) ?></a></h2>
                                    <?php if ($position !== '' || $company !== ''): ?>
                                        <p class="cms-speaker-card__meta"><?= $e(trim($position . ($company !== '' ? ' · ' . $company : ''))) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="cms-events-card__meta cms-excomp-card-meta">
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
                                        <?php $linkedCompanyId = (int) ($linkedCompany->id ?? 0); ?>
                                        <a class="cms-excomp-linked-item" href="<?= $e($linkedCompanyId > 0 ? (rtrim((string) SITE_URL, '/') . '/companies/' . $linkedCompanyId) : (rtrim((string) SITE_URL, '/') . '/companies')) ?>">🏢 <?= $e($companyName !== '' ? $companyName : 'Company') ?></a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($bio !== ''): ?><p class="cms-speaker-card__excerpt cms-excomp-card-excerpt"><?= $e($bio) ?></p><?php endif; ?>

                            <footer>
                                <span class="cms-speaker-card__events"><?= $availability !== '' ? $e($availability) : 'Expert' ?></span>
                                <?php if ($detailUrl !== ''): ?>
                                    <a class="cms-events-card__more" href="<?= $e($detailUrl) ?>">Mehr Infos …</a>
                                <?php else: ?>
                                    <span class="cms-events-card__more">Kein Profil</span>
                                <?php endif; ?>
                            </footer>

                            <?php if ($websiteUrl !== ''): ?><a class="cms-excomp-card-link cms-excomp-card-link--ghost" href="<?= $e($websiteUrl) ?>" target="_blank" rel="noopener noreferrer">Website öffnen</a><?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>
