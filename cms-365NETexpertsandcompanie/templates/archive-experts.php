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

$base = rtrim((string) SITE_URL, '/');
$baseUrl = $base . '/experts';
$e = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$expertName = static function (object $expert): string {
    $first = trim((string) ($expert->first_name ?? ''));
    $last = trim((string) ($expert->last_name ?? ''));
    $full = trim($first . ' ' . $last);
    return $full !== '' ? $full : 'Expert #' . (int) ($expert->id ?? 0);
};

$expertInitials = static function (object $expert): string {
    $first = trim((string) ($expert->first_name ?? ''));
    $last = trim((string) ($expert->last_name ?? ''));
    $letters = strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
    return $letters !== '' ? $letters : 'EX';
};

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
?>

<main class="cms-excomp-public cms-excomp-archive cms-excomp-archive--experts">
    <div class="cms-excomp-container">
        <section class="cms-excomp-hero cms-excomp-hero--compact cms-excomp-hero--experts" aria-label="Experts">
            <span class="cms-excomp-kicker">365 Network · Expert Directory</span>
            <h1>Experts</h1>
            <p class="cms-excomp-hero__description">Echte Profile mit Skills, Verfügbarkeit und direkten Verknüpfungen zu Company & Speaker.</p>
        </section>

        <form method="GET" action="<?= $e($baseUrl) ?>" class="cms-excomp-search cms-excomp-search--header-card" role="search" aria-label="Experts Suche">
            <div class="cms-excomp-search__field">
                <input type="search" name="q" value="<?= $e($q) ?>" placeholder="Name, Firma, Position, Skills …" aria-label="Experts suchen">
            </div>
            <div class="cms-excomp-search__field">
                <input type="text" name="city" value="<?= $e($city) ?>" placeholder="Stadt …" aria-label="Stadt">
            </div>
            <div class="cms-excomp-search__actions">
                <button type="submit">Suchen</button>
                <?php if ($q !== '' || $city !== ''): ?><a href="<?= $e($baseUrl) ?>">Zurücksetzen</a><?php endif; ?>
            </div>
        </form>

        <?php if ($experts === []): ?>
            <div class="cms-excomp-empty">Keine Experts für den aktuellen Filter gefunden.</div>
        <?php else: ?>
            <div class="cms-excomp-grid">
                <?php foreach ($experts as $expert): ?>
                    <?php
                    $name = $expertName($expert);
                    $detailUrl = trim((string) ($expert->detail_url ?? ''));
                    if ($detailUrl === '') {
                        $expertId = (int) ($expert->id ?? 0);
                        $detailUrl = $expertId > 0 ? ($base . '/experts/' . $expertId) : $baseUrl;
                    }

                    $websiteUrl = trim((string) ($expert->website ?? ''));
                    $position = trim((string) ($expert->position ?? ''));
                    $company = trim((string) ($expert->company ?? ''));
                    $location = trim((string) ($expert->location_city ?? $expert->city ?? ''));
                    $availability = trim((string) ($expert->availability ?? ''));
                    $photo = trim((string) ($expert->photo_url ?? ''));
                    $bio = $expertCardExcerpt($expert);
                    $linkedSpeaker = is_object($expert->linked_speaker ?? null) ? $expert->linked_speaker : null;
                    $linkedCompany = is_object($expert->linked_company ?? null) ? $expert->linked_company : null;
                    ?>
                    <article class="cms-excomp-card cms-excomp-card--expert">
                        <div class="cms-excomp-card__head">
                            <div class="cms-excomp-card__media">
                                <?php if ($photo !== ''): ?>
                                    <img class="cms-excomp-avatar" src="<?= $e($photo) ?>" alt="<?= $e($name) ?>" loading="lazy">
                                <?php else: ?>
                                    <span class="cms-excomp-avatar cms-excomp-avatar--fallback"><?= $e($expertInitials($expert)) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="cms-excomp-card__titleblock">
                                <h2 class="cms-excomp-card__name"><a href="<?= $e($detailUrl) ?>"><?= $e($name) ?></a></h2>
                                <?php
                                $metaLine = trim($position . ($company !== '' ? ' · ' . $company : ''));
                                if ($metaLine !== ''):
                                ?>
                                    <p class="cms-excomp-card__meta-line"><?= $e($metaLine) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="cms-excomp-card__meta">
                            <?php if ($location !== ''): ?><span><?= $e($location) ?></span><?php endif; ?>
                            <?php if ($availability !== ''): ?><span class="is-highlight"><?= $e($availability) ?></span><?php endif; ?>
                        </div>

                        <?php if ($linkedSpeaker !== null || $linkedCompany !== null): ?>
                            <div class="cms-excomp-linked">
                                <?php if ($linkedSpeaker !== null): ?>
                                    <?php
                                    $speakerName = trim((string) ($linkedSpeaker->display_name ?? ''));
                                    $speakerSlug = trim((string) ($linkedSpeaker->slug ?? ''));
                                    $speakerUrl = $speakerSlug !== '' ? ($base . '/speakers/' . rawurlencode($speakerSlug)) : ($base . '/speakers');
                                    ?>
                                    <a class="cms-excomp-linked-item" href="<?= $e($speakerUrl) ?>">🎤 <?= $e($speakerName !== '' ? $speakerName : 'Speaker') ?></a>
                                <?php endif; ?>
                                <?php if ($linkedCompany !== null): ?>
                                    <?php
                                    $linkedCompanyId = (int) ($linkedCompany->id ?? 0);
                                    $companyUrl = $linkedCompanyId > 0 ? ($base . '/companies/' . $linkedCompanyId) : ($base . '/companies');
                                    $companyName = trim((string) ($linkedCompany->name ?? ''));
                                    ?>
                                    <a class="cms-excomp-linked-item" href="<?= $e($companyUrl) ?>">🏢 <?= $e($companyName !== '' ? $companyName : 'Company') ?></a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($bio !== ''): ?><p class="cms-excomp-card__excerpt"><?= $e($bio) ?></p><?php endif; ?>

                        <footer class="cms-excomp-card__footer">
                            <span class="cms-excomp-card__badge"><?= $availability !== '' ? $e($availability) : 'Expert' ?></span>
                            <a class="cms-excomp-card__more" href="<?= $e($detailUrl) ?>">Mehr Infos …</a>
                        </footer>

                        <?php if ($websiteUrl !== ''): ?><a class="cms-excomp-card__ghost-link" href="<?= $e($websiteUrl) ?>" target="_blank" rel="noopener noreferrer">Website öffnen</a><?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
