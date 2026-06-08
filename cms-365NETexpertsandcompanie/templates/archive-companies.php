<?php
/**
 * Public Archive Template – Companies.
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

$companies = array_values(array_filter(array_map(
    static fn($item): ?object => is_object($item) ? $item : (is_array($item) ? (object) $item : null),
    (array) ($companies ?? [])
)));

$filters = is_array($filters ?? null) ? $filters : [];
$q = trim((string) ($filters['q'] ?? ''));
$city = trim((string) ($filters['city'] ?? ''));

$base = rtrim((string) SITE_URL, '/');
$baseUrl = $base . '/companies';
$e = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$companyCardExcerpt = static function (object $company): string {
    $raw = trim((string) ($company->description ?? ''));
    if ($raw === '') {
        return '';
    }

    $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    if ($text === '') {
        return '';
    }

    return function_exists('mb_substr')
        ? (string) mb_substr($text, 0, 260, 'UTF-8')
        : substr($text, 0, 260);
};

$companyInitials = static function (object $company): string {
    $name = trim((string) ($company->name ?? ''));
    if ($name === '') {
        return 'CO';
    }

    $parts = preg_split('/\s+/', $name) ?: [];
    $letters = '';
    foreach ($parts as $part) {
        $letters .= strtoupper(substr((string) $part, 0, 1));
        if (strlen($letters) >= 2) {
            break;
        }
    }

    return $letters !== '' ? substr($letters, 0, 2) : 'CO';
};

$partnerLabel = static function (object $company): string {
    if ((int) ($company->is_sponsor ?? 0) === 1) {
        return 'Sponsor';
    }
    if ((int) ($company->is_top_partner ?? 0) === 1) {
        return 'Top-Partner';
    }
    if ((int) ($company->is_partner ?? 0) === 1) {
        return 'Partner';
    }

    return 'Unternehmen';
};
?>

<main class="cms-excomp-public cms-excomp-archive cms-excomp-archive--companies">
    <div class="cms-excomp-container">
        <section class="cms-excomp-hero cms-excomp-hero--compact cms-excomp-hero--companies" aria-label="Companies">
            <span class="cms-excomp-kicker">365 Network · Company Directory</span>
            <h1>Companies</h1>
            <p class="cms-excomp-hero__description">Partner, Organisationen und Unternehmen mit direkten Verknüpfungen zu Experts und Speakern.</p>
        </section>

        <form method="GET" action="<?= $e($baseUrl) ?>" class="cms-excomp-search cms-excomp-search--header-card" role="search" aria-label="Companies Suche">
            <div class="cms-excomp-search__field">
                <input type="search" name="q" value="<?= $e($q) ?>" placeholder="Name, Branche, Beschreibung …" aria-label="Companies suchen">
            </div>
            <div class="cms-excomp-search__field">
                <input type="text" name="city" value="<?= $e($city) ?>" placeholder="Stadt …" aria-label="Stadt">
            </div>
            <div class="cms-excomp-search__actions">
                <button type="submit">Suchen</button>
                <?php if ($q !== '' || $city !== ''): ?><a href="<?= $e($baseUrl) ?>">Zurücksetzen</a><?php endif; ?>
            </div>
        </form>

        <?php if ($companies === []): ?>
            <div class="cms-excomp-empty">Keine Companies für den aktuellen Filter gefunden.</div>
        <?php else: ?>
            <div class="cms-excomp-grid">
                <?php foreach ($companies as $company): ?>
                    <?php
                    $name = trim((string) ($company->name ?? ''));
                    $name = $name !== '' ? $name : 'Company #' . (int) ($company->id ?? 0);

                    $detailUrl = trim((string) ($company->detail_url ?? ''));
                    if ($detailUrl === '') {
                        $companyId = (int) ($company->id ?? 0);
                        $detailUrl = $companyId > 0 ? ($base . '/companies/' . $companyId) : $baseUrl;
                    }

                    $websiteUrl = trim((string) ($company->website ?? ''));
                    $industry = trim((string) ($company->industry ?? ''));
                    $location = trim((string) ($company->location_city ?? $company->city ?? ''));
                    $logo = trim((string) ($company->logo_url ?? ''));
                    $description = $companyCardExcerpt($company);
                    $linkedExperts = is_array($company->linked_experts ?? null) ? $company->linked_experts : [];
                    $linkedSpeakers = is_array($company->linked_speakers ?? null) ? $company->linked_speakers : [];
                    $relationsCount = count($linkedExperts) + count($linkedSpeakers);
                    ?>
                    <article class="cms-excomp-card cms-excomp-card--company">
                        <div class="cms-excomp-card__head">
                            <div class="cms-excomp-card__media">
                                <?php if ($logo !== ''): ?>
                                    <img class="cms-excomp-avatar cms-excomp-avatar--company" src="<?= $e($logo) ?>" alt="<?= $e($name) ?>" loading="lazy">
                                <?php else: ?>
                                    <span class="cms-excomp-avatar cms-excomp-avatar--company cms-excomp-avatar--fallback"><?= $e($companyInitials($company)) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="cms-excomp-card__titleblock">
                                <h2 class="cms-excomp-card__name"><a href="<?= $e($detailUrl) ?>"><?= $e($name) ?></a></h2>
                                <?php if ($industry !== ''): ?><p class="cms-excomp-card__meta-line"><?= $e($industry) ?></p><?php endif; ?>
                            </div>
                        </div>

                        <div class="cms-excomp-card__meta">
                            <span><?= $e($partnerLabel($company)) ?></span>
                            <?php if ($location !== ''): ?><span><?= $e($location) ?></span><?php endif; ?>
                            <?php if ($relationsCount > 0): ?><span><?= $relationsCount ?> Verknüpfungen</span><?php endif; ?>
                        </div>

                        <?php if ($linkedExperts !== [] || $linkedSpeakers !== []): ?>
                            <div class="cms-excomp-linked">
                                <?php foreach (array_slice($linkedExperts, 0, 2) as $linkedExpert): ?>
                                    <?php
                                    if (!is_object($linkedExpert)) {
                                        continue;
                                    }
                                    $linkedExpertId = (int) ($linkedExpert->id ?? 0);
                                    $linkedExpertName = trim((string) (($linkedExpert->first_name ?? '') . ' ' . ($linkedExpert->last_name ?? '')));
                                    if ($linkedExpertName === '') {
                                        $linkedExpertName = 'Expert #' . $linkedExpertId;
                                    }
                                    $linkedExpertUrl = $linkedExpertId > 0 ? ($base . '/experts/' . $linkedExpertId) : ($base . '/experts');
                                    ?>
                                    <a class="cms-excomp-linked-item" href="<?= $e($linkedExpertUrl) ?>">👤 <?= $e($linkedExpertName) ?></a>
                                <?php endforeach; ?>

                                <?php foreach (array_slice($linkedSpeakers, 0, 2) as $linkedSpeaker): ?>
                                    <?php
                                    if (!is_object($linkedSpeaker)) {
                                        continue;
                                    }
                                    $linkedSpeakerName = trim((string) ($linkedSpeaker->display_name ?? ''));
                                    if ($linkedSpeakerName === '') {
                                        $linkedSpeakerName = 'Speaker #' . (int) ($linkedSpeaker->id ?? 0);
                                    }
                                    $linkedSpeakerSlug = trim((string) ($linkedSpeaker->slug ?? ''));
                                    $linkedSpeakerUrl = $linkedSpeakerSlug !== ''
                                        ? ($base . '/speakers/' . rawurlencode($linkedSpeakerSlug))
                                        : ($base . '/speakers');
                                    ?>
                                    <a class="cms-excomp-linked-item" href="<?= $e($linkedSpeakerUrl) ?>">🎤 <?= $e($linkedSpeakerName) ?></a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($description !== ''): ?><p class="cms-excomp-card__excerpt"><?= $e($description) ?></p><?php endif; ?>

                        <footer class="cms-excomp-card__footer">
                            <span class="cms-excomp-card__badge"><?= $industry !== '' ? $e($industry) : 'Company' ?></span>
                            <a class="cms-excomp-card__more" href="<?= $e($detailUrl) ?>">Mehr Infos …</a>
                        </footer>

                        <?php if ($websiteUrl !== ''): ?><a class="cms-excomp-card__ghost-link" href="<?= $e($websiteUrl) ?>" target="_blank" rel="noopener noreferrer">Website öffnen</a><?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
