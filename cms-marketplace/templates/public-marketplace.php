<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$lang = ($lang ?? 'de') === 'en' ? 'en' : 'de';
$t = static function (string $de, string $en) use ($lang): string {
    if (function_exists('cms_plugin_public_i18n_value')) {
        return cms_plugin_public_i18n_value(['text' => $de, 'text_en' => $en], 'text', $lang, $de);
    }

    return $lang === 'en' ? $en : $de;
};
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars((string) ($pageTitle ?? '365CMS Marketplace'), ENT_QUOTES, 'UTF-8'); ?></title>
    <?php if (!empty($publicCssUrl)): ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars((string) $publicCssUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
</head>
<body>
    <div class="shell">
        <section class="hero">
            <span class="eyebrow"><?php echo htmlspecialchars($t('Öffentlicher 365CMS Marketplace', 'Public 365CMS marketplace'), ENT_QUOTES, 'UTF-8'); ?></span>
            <div>
                <h1><?php echo htmlspecialchars((string) ($pageTitle ?? '365CMS Marketplace'), ENT_QUOTES, 'UTF-8'); ?></h1>
                <p><?php echo htmlspecialchars((string) ($pageDescription ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="section-nav">
                <a href="<?php echo htmlspecialchars((string) (($publicRouteMap['overview'] ?? '/marketplace-public')), ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo (($section ?? 'overview') === 'overview') ? 'active' : ''; ?>"><?php echo htmlspecialchars($t('Übersicht', 'Overview'), ENT_QUOTES, 'UTF-8'); ?></a>
                <?php foreach ($sections as $sectionItem): ?>
                    <a href="<?php echo htmlspecialchars((string) ($sectionItem['url'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo (($section ?? '') === ($sectionItem['key'] ?? '')) ? 'active' : ''; ?>"><?php echo htmlspecialchars((string) ($sectionItem['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endforeach; ?>
            </div>
            <div class="hero-badges">
                <span class="hero-badge success"><?php echo htmlspecialchars($t('Live aus dem Plugin gerendert', 'Rendered live by plugin'), ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="hero-badge"><?php echo htmlspecialchars($t('Public JSON verfügbar', 'Public JSON available'), ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="hero-badge"><?php echo htmlspecialchars($t('Installieren & Updates vorbereitet', 'Install and updates prepared'), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </section>

        <?php if (($section ?? 'overview') === 'overview'): ?>
            <section class="cards-4">
                <?php foreach ($sections as $sectionItem): ?>
                    <article class="card">
                        <h3><?php echo htmlspecialchars((string) ($sectionItem['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h3>
                        <div class="stat"><?php echo (int) ($sectionItem['count'] ?? 0); ?></div>
                        <p><?php echo htmlspecialchars((string) ($sectionItem['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <div class="section-card-links">
                            <a class="button button-primary" href="<?php echo htmlspecialchars((string) ($sectionItem['url'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($t('Bereich öffnen', 'Open section'), ENT_QUOTES, 'UTF-8'); ?></a>
                            <?php if (!empty($sectionItem['feed_url'])): ?>
                                <a class="link-chip" href="<?php echo htmlspecialchars((string) ($sectionItem['feed_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Feed</a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <div class="grid">
                <?php foreach (['plugins' => $t('Plugins', 'Plugins'), 'themes' => $t('Themes', 'Themes'), 'cms' => 'CMS'] as $latestKey => $latestLabel): ?>
                    <section class="card">
                        <h2><?php echo htmlspecialchars($t('Neueste ', 'Latest ') . $latestLabel, ENT_QUOTES, 'UTF-8'); ?></h2>
                        <div class="entry-list">
                            <?php $latestEntries = array_slice((array) ($overview['latest'][$latestKey] ?? []), 0, 4); ?>
                            <?php if ($latestEntries === []): ?>
                                <div class="empty"><?php echo htmlspecialchars($t('Aktuell sind in diesem Bereich noch keine freigegebenen Pakete verfügbar.', 'There are currently no published packages in this section.'), ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php else: ?>
                                <?php foreach ($latestEntries as $entry): ?>
                                    <article class="entry-card">
                                        <div class="entry-head">
                                            <div>
                                                <h3 class="entry-title"><?php echo htmlspecialchars((string) ($entry['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h3>
                                                <div class="muted"><?php echo htmlspecialchars((string) ($entry['author'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                            </div>
                                            <span class="pill"><?php echo htmlspecialchars((string) ($entry['version'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                        <div class="muted"><?php echo htmlspecialchars((string) ($entry['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="entry-links">
                                            <?php if (!empty($entry['download_url'])): ?>
                                                <a class="button button-primary" href="<?php echo htmlspecialchars((string) ($entry['download_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($t('Download', 'Download'), ENT_QUOTES, 'UTF-8'); ?></a>
                                            <?php endif; ?>
                                            <?php if (!empty($entry['purchase_url'])): ?>
                                                <a class="button button-primary" href="<?php echo htmlspecialchars((string) ($entry['purchase_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($t('Kauf / Anfrage', 'Buy / Request'), ENT_QUOTES, 'UTF-8'); ?></a>
                                            <?php endif; ?>
                                            <?php if (!empty($entry['security_report_url'])): ?>
                                                <a class="link-chip" href="<?php echo htmlspecialchars((string) ($entry['security_report_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($t('Sicherheitsmeldung', 'Security report'), ENT_QUOTES, 'UTF-8'); ?></a>
                                            <?php endif; ?>
                                            <?php if (!empty($entry['manifest'])): ?>
                                                <a class="link-chip" href="<?php echo htmlspecialchars((string) ($entry['manifest'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">manifest.json</a>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <section class="card">
                <h2><?php echo htmlspecialchars((string) ($pageTitle ?? ''), ENT_QUOTES, 'UTF-8'); ?></h2>
                <div class="entry-list">
                    <?php if ($entries === []): ?>
                        <div class="empty"><?php echo htmlspecialchars($t('Für diesen Bereich sind aktuell keine freigegebenen Einträge verfügbar.', 'No published entries are currently available for this section.'), ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php else: ?>
                        <?php foreach ($entries as $entry): ?>
                            <article class="entry-card">
                                <div class="entry-head">
                                    <div>
                                        <h3 class="entry-title"><?php echo htmlspecialchars((string) ($entry['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h3>
                                        <div class="muted"><?php echo htmlspecialchars((string) ($entry['author'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </div>
                                    <div class="section-card-links">
                                        <span class="pill"><?php echo htmlspecialchars((string) ($entry['type'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="pill success"><?php echo htmlspecialchars((string) ($entry['version'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </div>
                                <div class="muted"><?php echo htmlspecialchars((string) ($entry['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="meta-grid">
                                    <div class="meta-box">
                                        <strong><?php echo htmlspecialchars($t('Slug', 'Slug'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <code><?php echo htmlspecialchars((string) ($entry['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                                    </div>
                                    <div class="meta-box">
                                        <strong><?php echo htmlspecialchars($t('Requires CMS', 'Requires CMS'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <span><?php echo htmlspecialchars((string) ($entry['requires_cms'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <div class="meta-box">
                                        <strong><?php echo htmlspecialchars($t('Requires PHP', 'Requires PHP'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <span><?php echo htmlspecialchars((string) ($entry['requires_php'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </div>
                                <div class="entry-links">
                                    <?php if (!empty($entry['download_url'])): ?>
                                        <a class="button button-primary" href="<?php echo htmlspecialchars((string) ($entry['download_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($t('Download / Installieren', 'Download / Install'), ENT_QUOTES, 'UTF-8'); ?></a>
                                    <?php endif; ?>
                                    <?php if (!empty($entry['purchase_url'])): ?>
                                        <a class="button button-primary" href="<?php echo htmlspecialchars((string) ($entry['purchase_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($t('Kauf / Anfrage', 'Buy / Request'), ENT_QUOTES, 'UTF-8'); ?></a>
                                    <?php endif; ?>
                                    <?php if (!empty($entry['security_report_url'])): ?>
                                        <a class="button" href="<?php echo htmlspecialchars((string) ($entry['security_report_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($t('Sicherheitsproblem melden', 'Report security issue'), ENT_QUOTES, 'UTF-8'); ?></a>
                                    <?php endif; ?>
                                    <?php if (!empty($entry['update_url'])): ?>
                                        <a class="link-chip" href="<?php echo htmlspecialchars((string) ($entry['update_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">update.json</a>
                                    <?php endif; ?>
                                    <?php if (!empty($entry['manifest'])): ?>
                                        <a class="link-chip" href="<?php echo htmlspecialchars((string) ($entry['manifest'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">manifest.json</a>
                                    <?php endif; ?>
                                    <?php if (!empty($entry['docs_url'])): ?>
                                        <a class="link-chip" href="<?php echo htmlspecialchars((string) ($entry['docs_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($t('Doku', 'Docs'), ENT_QUOTES, 'UTF-8'); ?></a>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</body>
</html>
