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

$baseUrl = rtrim((string) SITE_URL, '/') . '/companies';
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

<main class="cms-events-public cms-events-archive cms-excomp-public cms-excomp-public--companies">
    <div class="cms-events-container cms-excomp-container">
        <section class="cms-events-hero cms-events-hero--compact cms-excomp-hero cms-excomp-hero--companies" aria-label="Companies">
            <span class="cms-events-kicker">365 Network · Companies</span>
            <h1>Companies</h1>
            <p>Publicsite im Events-/Speaker-Stil: Filter zuerst, klares Grid und Partner-Visualisierung in Grün.</p>
        </section>

        <form method="GET" action="<?= $e($baseUrl) ?>" class="cms-events-search cms-events-search--header-card cms-excomp-search" role="search" aria-label="Companies Suche">
            <div class="cms-events-search__field">
                <input type="search" name="q" value="<?= $e($q) ?>" placeholder="Name, Branche, Beschreibung …">
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

        <section class="cms-excomp-section cms-excomp-section--companies" aria-label="Companies Liste">
            <header class="cms-excomp-section-head">
                <h2>Unternehmen</h2>
                <span><?= count($companies) ?> Treffer</span>
            </header>

            <?php if ($companies === []): ?>
                <div class="cms-excomp-empty">
                    <p>Keine Companies für den aktuellen Filter gefunden.</p>
                </div>
            <?php else: ?>
                <div class="cms-events-grid cms-excomp-grid">
                    <?php foreach ($companies as $company): ?>
                        <?php
                        $name = trim((string) ($company->name ?? ''));
                        $name = $name !== '' ? $name : 'Company #' . (int) ($company->id ?? 0);
                        $detailUrl = trim((string) ($company->detail_url ?? ''));
                        $websiteUrl = trim((string) ($company->website ?? ''));
                        $industry = trim((string) ($company->industry ?? ''));
                        $location = trim((string) ($company->location_city ?? $company->city ?? ''));
                        $logo = trim((string) ($company->logo_url ?? ''));
                        $description = $companyCardExcerpt($company);
                        $linkedExperts = is_array($company->linked_experts ?? null) ? $company->linked_experts : [];
                        $linkedSpeakers = is_array($company->linked_speakers ?? null) ? $company->linked_speakers : [];
                        ?>
                        <article class="cms-events-card cms-excomp-card cms-excomp-card--company">
                            <div class="cms-excomp-card-head">
                                <?php if ($logo !== ''): ?>
                                    <img src="<?= $e($logo) ?>" alt="<?= $e($name) ?>" loading="lazy" class="cms-excomp-avatar cms-excomp-avatar--company">
                                <?php else: ?>
                                    <span class="cms-excomp-avatar cms-excomp-avatar--fallback cms-excomp-avatar--company"><?= $e($companyInitials($company)) ?></span>
                                <?php endif; ?>
                                <div>
                                    <h3><?= $e($name) ?></h3>
                                    <?php if ($industry !== ''): ?><p><?= $e($industry) ?></p><?php endif; ?>
                                </div>
                            </div>

                            <div class="cms-events-card__meta cms-excomp-card-meta">
                                <span><?= $e($partnerLabel($company)) ?></span>
                                <?php if ($location !== ''): ?><span><?= $e($location) ?></span><?php endif; ?>
                            </div>

                            <?php if ($linkedExperts !== [] || $linkedSpeakers !== []): ?>
                                <div class="cms-excomp-linked">
                                    <?php foreach (array_slice($linkedExperts, 0, 2) as $linkedExpert): ?>
                                        <?php
                                        if (!is_object($linkedExpert)) {
                                            continue;
                                        }
                                        $linkedExpertName = trim((string) (($linkedExpert->first_name ?? '') . ' ' . ($linkedExpert->last_name ?? '')));
                                        if ($linkedExpertName === '') {
                                            $linkedExpertName = 'Expert #' . (int) ($linkedExpert->id ?? 0);
                                        }
                                        $linkedExpertId = (int) ($linkedExpert->id ?? 0);
                                        ?>
                                        <a class="cms-excomp-linked-item" href="<?= $e($linkedExpertId > 0 ? (rtrim((string) SITE_URL, '/') . '/experts/' . $linkedExpertId) : (rtrim((string) SITE_URL, '/') . '/experts')) ?>">👤 <?= $e($linkedExpertName) ?></a>
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
                                            ? (rtrim((string) SITE_URL, '/') . '/speakers/' . rawurlencode($linkedSpeakerSlug))
                                            : (rtrim((string) SITE_URL, '/') . '/speakers');
                                        ?>
                                        <a class="cms-excomp-linked-item" href="<?= $e($linkedSpeakerUrl) ?>">🎤 <?= $e($linkedSpeakerName) ?></a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($description !== ''): ?><p class="cms-excomp-card-excerpt"><?= $e($description) ?></p><?php endif; ?>

                            <footer>
                                <span class="cms-events-card__date-badge"><?= $industry !== '' ? $e($industry) : 'Company' ?></span>
                                <?php if ($detailUrl !== ''): ?>
                                    <a class="cms-events-card__more" href="<?= $e($detailUrl) ?>">Mehr Infos …</a>
                                <?php else: ?>
                                    <span class="cms-events-card__more">Kein Profil</span>
                                <?php endif; ?>
                            </footer>

                            <?php if ($websiteUrl !== ''): ?>
                                <a class="cms-excomp-card-link cms-excomp-card-link--ghost" href="<?= $e($websiteUrl) ?>" target="_blank" rel="noopener noreferrer">Website öffnen</a>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>
