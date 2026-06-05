<?php
/**
 * CMS M365 Message Center – Public detail view.
 *
 * @package CMS_M365MessageCenter
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$item = is_array($item ?? null) ? $item : [];
$settings = is_array($settings ?? null) ? $settings : [];
$baseUrl = trim((string) ($baseUrl ?? '/m365-messagecenter'));
$publicMaxWidth = max(900, min(1600, (int) ($settings['public_max_width'] ?? 1160)));

$title = trim((string) ($item['title'] ?? '')) ?: 'M365 Message Center';
$graphId = trim((string) ($item['graph_id'] ?? ''));
$category = trim((string) ($item['category'] ?? ''));
$severity = trim((string) ($item['severity'] ?? ''));
$services = array_values(array_filter(array_map('strval', (array) ($item['services'] ?? []))));
$tags = array_values(array_filter(array_map('strval', (array) ($item['tags'] ?? []))));
$body = trim((string) ($item['body_content'] ?? ''));
if ($body === '') {
    $body = trim((string) ($item['body_excerpt'] ?? ''));
}
$externalUrl = trim((string) ($item['external_url'] ?? ''));
if ($externalUrl !== '') {
    $parts = parse_url($externalUrl);
    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    if (!in_array($scheme, ['https', 'http'], true)) {
        $externalUrl = '';
    }
}
if ($externalUrl === '' && $graphId !== '') {
    $externalUrl = 'https://admin.microsoft.com/Adminportal/Home#/MessageCenter/:/messages/' . rawurlencode($graphId);
}

$formatDateTime = static function (string $value): string {
    $timestamp = strtotime($value);
    return $timestamp !== false ? date('d.m.Y H:i', $timestamp) : '';
};

$dateFields = [
    'last_modified_at' => 'Zuletzt geändert',
    'action_required_at' => 'Handlung erforderlich bis',
    'start_at' => 'Start',
    'end_at' => 'Ende',
    'raw_updated_at' => 'Lokal aktualisiert',
];
?>
<main class="phinit-plugin m365mc-archive m365mc-detail" id="m365-messagecenter-detail" style="--m365mc-public-max-width: <?php echo $publicMaxWidth; ?>px;">
    <nav class="m365mc-breadcrumb" aria-label="Breadcrumb">
        <a href="<?php echo $esc($baseUrl); ?>">← Zurück zur Übersicht</a>
    </nav>

    <article class="m365mc-detail-card phinit-card">
        <header class="m365mc-detail__header">
            <div class="m365mc-card__eyebrow">
                <?php if ($category !== ''): ?><span><?php echo $esc($category); ?></span><?php endif; ?>
                <?php if ($severity !== ''): ?><span><?php echo $esc($severity); ?></span><?php endif; ?>
                <?php if ($graphId !== ''): ?><code><?php echo $esc($graphId); ?></code><?php endif; ?>
            </div>
            <h1><?php echo $esc($title); ?></h1>
            <?php if (!empty($item['is_major_change'])): ?>
            <span class="m365mc-badge m365mc-badge--major">Major Change</span>
            <?php endif; ?>
        </header>

        <section class="m365mc-detail__section" aria-labelledby="m365mc-detail-content-title">
            <h2 id="m365mc-detail-content-title">Vollständige Meldung</h2>
            <?php if ($body !== ''): ?>
            <div class="m365mc-detail__body">
                <?php foreach (preg_split('/\n{2,}|(?<=\.)\s+(?=[A-ZÄÖÜ0-9])/u', $body) ?: [$body] as $paragraph): ?>
                <?php $paragraph = trim((string) $paragraph); if ($paragraph === '') { continue; } ?>
                <p><?php echo $esc($paragraph); ?></p>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="m365mc-detail__muted">Für diesen Cache-Eintrag ist noch kein vollständiger Meldungstext gespeichert. Bitte den Graph-Abruf erneut ausführen.</p>
            <?php endif; ?>
        </section>

        <section class="m365mc-detail__section" aria-labelledby="m365mc-detail-meta-title">
            <h2 id="m365mc-detail-meta-title">Alle Informationen</h2>
            <dl class="m365mc-detail-meta">
                <?php if ($graphId !== ''): ?><div><dt>Graph-ID</dt><dd><code><?php echo $esc($graphId); ?></code></dd></div><?php endif; ?>
                <?php if ($category !== ''): ?><div><dt>Kategorie</dt><dd><?php echo $esc($category); ?></dd></div><?php endif; ?>
                <?php if ($severity !== ''): ?><div><dt>Priorität</dt><dd><?php echo $esc($severity); ?></dd></div><?php endif; ?>
                <div><dt>Major Change</dt><dd><?php echo !empty($item['is_major_change']) ? 'Ja' : 'Nein'; ?></dd></div>
                <?php foreach ($dateFields as $field => $label): ?>
                <?php $dateLabel = $formatDateTime((string) ($item[$field] ?? '')); if ($dateLabel === '') { continue; } ?>
                <div><dt><?php echo $esc($label); ?></dt><dd><time datetime="<?php echo $esc((string) ($item[$field] ?? '')); ?>"><?php echo $esc($dateLabel); ?></time></dd></div>
                <?php endforeach; ?>
                <?php if ($services !== []): ?><div><dt>Services</dt><dd><?php echo $esc(implode(' · ', $services)); ?></dd></div><?php endif; ?>
                <?php if ($tags !== []): ?><div><dt>Tags</dt><dd><?php echo $esc(implode(' · ', $tags)); ?></dd></div><?php endif; ?>
            </dl>
        </section>

        <footer class="m365mc-detail__footer">
            <a class="phinit-btn phinit-btn--secondary" href="<?php echo $esc($baseUrl); ?>">Zur Übersicht</a>
            <?php if ($externalUrl !== ''): ?>
            <a class="phinit-btn phinit-btn--primary m365mc-admin-link" href="<?php echo $esc($externalUrl); ?>" target="_blank" rel="noopener noreferrer">Original im M365 Admin Center <span aria-hidden="true">→</span></a>
            <?php endif; ?>
        </footer>
    </article>
</main>
