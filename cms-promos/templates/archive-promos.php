<?php declare(strict_types=1); if (!defined('ABSPATH')) exit;

$sanitizePromoUrl = static function (string $url): string {
    $url = trim($url);
    if ($url === '' || strlen($url) > 2048 || preg_match('/[[:cntrl:]]/', $url) === 1 || !filter_var($url, FILTER_VALIDATE_URL)) {
        return '';
    }

    $parts = parse_url($url);
    if (!is_array($parts)) {
        return '';
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    if (!in_array($scheme, ['http', 'https'], true)) {
        return '';
    }

    if (($parts['user'] ?? '') !== '' || ($parts['pass'] ?? '') !== '') {
        return '';
    }

    $host = strtolower(trim((string) ($parts['host'] ?? ''), '[]'));
    if ($host === '' || in_array($host, ['localhost', 'localhost.localdomain'], true) || str_ends_with($host, '.local')) {
        return '';
    }

    $ip = filter_var($host, FILTER_VALIDATE_IP);
    if ($ip !== false && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return '';
    }

    return $url;
};

$sanitizePromoHtml = static function (string $html) use ($sanitizePromoUrl): string {
    $html = trim(strip_tags($html, '<p><a><strong><em><ul><ol><li><br><h2><h3><h4><span>'));
    $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
    $html = preg_replace('/\s+style\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
    return preg_replace_callback('/\s+href\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', static function (array $matches) use ($sanitizePromoUrl): string {
        $href = html_entity_decode((string) ($matches[2] ?? $matches[3] ?? $matches[4] ?? ''), ENT_QUOTES, 'UTF-8');
        $safeHref = $sanitizePromoUrl($href);
        return $safeHref !== '' ? ' href="' . htmlspecialchars($safeHref, ENT_QUOTES, 'UTF-8') . '"' : '';
    }, $html) ?? '';
};
?>

<main class="promos-archive">
    <section class="promos-hero">
        <span class="promos-kicker">365CMS Promos</span>
        <h1 class="promos-title"><?php echo htmlspecialchars((string) ($settings['archive_title'] ?? 'Promotions & Highlights'), ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="promos-lead"><?php echo htmlspecialchars((string) ($settings['archive_description'] ?? 'Zentrale Übersicht aktiver Kampagnen, CTA-Flächen und Teaser-Aktionen.'), ENT_QUOTES, 'UTF-8'); ?></p>
        <?php if ($currentPlacement !== null): ?>
            <p class="promos-lead">Aktuelle Platzierung: <strong><?php echo htmlspecialchars((string) ($currentPlacement['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></p>
        <?php endif; ?>
    </section>

    <?php if (!empty($placements)): ?>
        <nav class="promos-placement-nav" aria-label="Promo-Platzierungen">
            <?php foreach ($placements as $placement): ?>
                <a class="promos-placement-link" href="/promos/placement/<?php echo rawurlencode((string) $placement['slug']); ?>">
                    <strong><?php echo htmlspecialchars((string) $placement['name'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                    <span><?php echo htmlspecialchars((string) ($placement['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <section class="promos-grid">
        <?php if (empty($promos)): ?>
            <article class="promos-card" role="status" aria-live="polite">
                <span class="promos-card__eyebrow">Noch leer</span>
                <h2 class="promos-card__title">Aktuell keine aktiven Promos</h2>
                <p>Sobald Kampagnen aktiviert und einer Platzierung zugeordnet wurden, erscheinen sie hier automatisch.</p>
            </article>
        <?php else: ?>
            <?php foreach ($promos as $promo): ?>
                <article class="promos-card">
                    <span class="promos-card__eyebrow"><?php echo htmlspecialchars((string) ($promo['placement_name'] ?? 'Promo'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <h2 class="promos-card__title"><?php echo htmlspecialchars((string) ($promo['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h2>
                    <?php if (!empty($promo['teaser'])): ?><p><?php echo htmlspecialchars((string) $promo['teaser'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
                    <?php if (!empty($promo['content_html'])): ?><div class="promos-card__body"><?php echo $sanitizePromoHtml((string) $promo['content_html']); ?></div><?php endif; ?>
                    <?php if (!empty($promo['target_url'])): ?>
                        <p class="promos-card__actions">
                            <a class="promos-card__button" href="/promo/click/<?php echo rawurlencode((string) $promo['slug']); ?>"><?php echo htmlspecialchars((string) ($promo['button_label'] ?: ($settings['default_button_label'] ?? 'Mehr erfahren')), ENT_QUOTES, 'UTF-8'); ?></a>
                        </p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</main>
