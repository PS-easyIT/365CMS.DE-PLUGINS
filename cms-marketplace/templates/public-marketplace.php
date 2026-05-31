<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
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
            <span class="eyebrow">Öffentlicher 365CMS Marketplace</span>
            <div>
                <h1><?php echo htmlspecialchars((string) ($pageTitle ?? '365CMS Marketplace'), ENT_QUOTES, 'UTF-8'); ?></h1>
                <p><?php echo htmlspecialchars((string) ($pageDescription ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="section-nav">
                <a href="<?php echo htmlspecialchars((string) (($publicRouteMap['overview'] ?? '/marketplace-public')), ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo (($section ?? 'overview') === 'overview') ? 'active' : ''; ?>">Übersicht</a>
                <?php foreach ($sections as $sectionItem): ?>
                    <a href="<?php echo htmlspecialchars((string) ($sectionItem['url'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo (($section ?? '') === ($sectionItem['key'] ?? '')) ? 'active' : ''; ?>"><?php echo htmlspecialchars((string) ($sectionItem['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endforeach; ?>
            </div>
            <div class="hero-badges">
                <span class="hero-badge success">Live aus dem Plugin gerendert</span>
                <span class="hero-badge">Public JSON verfügbar</span>
                <span class="hero-badge">Installieren & Updates vorbereitet</span>
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
                            <a class="button button-primary" href="<?php echo htmlspecialchars((string) ($sectionItem['url'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>">Bereich öffnen</a>
                            <?php if (!empty($sectionItem['feed_url'])): ?>
                                <a class="link-chip" href="<?php echo htmlspecialchars((string) ($sectionItem['feed_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Feed</a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <div class="grid">
                <?php foreach (['plugins' => 'Plugins', 'themes' => 'Themes', 'cms' => 'CMS'] as $latestKey => $latestLabel): ?>
                    <section class="card">
                        <h2>Neueste <?php echo htmlspecialchars($latestLabel, ENT_QUOTES, 'UTF-8'); ?></h2>
                        <div class="entry-list">
                            <?php $latestEntries = array_slice((array) ($overview['latest'][$latestKey] ?? []), 0, 4); ?>
                            <?php if ($latestEntries === []): ?>
                                <div class="empty">Aktuell sind in diesem Bereich noch keine freigegebenen Pakete verfügbar.</div>
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
                                                <a class="button button-primary" href="<?php echo htmlspecialchars((string) ($entry['download_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">Download</a>
                                            <?php endif; ?>
                                            <?php if (!empty($entry['purchase_url'])): ?>
                                                <a class="button button-primary" href="<?php echo htmlspecialchars((string) ($entry['purchase_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">Kauf / Anfrage</a>
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
                        <div class="empty">Für diesen Bereich sind aktuell keine freigegebenen Einträge verfügbar.</div>
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
                                        <strong>Slug</strong>
                                        <code><?php echo htmlspecialchars((string) ($entry['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                                    </div>
                                    <div class="meta-box">
                                        <strong>Requires CMS</strong>
                                        <span><?php echo htmlspecialchars((string) ($entry['requires_cms'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <div class="meta-box">
                                        <strong>Requires PHP</strong>
                                        <span><?php echo htmlspecialchars((string) ($entry['requires_php'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </div>
                                <div class="entry-links">
                                    <?php if (!empty($entry['download_url'])): ?>
                                        <a class="button button-primary" href="<?php echo htmlspecialchars((string) ($entry['download_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">Download / Installieren</a>
                                    <?php endif; ?>
                                    <?php if (!empty($entry['purchase_url'])): ?>
                                        <a class="button button-primary" href="<?php echo htmlspecialchars((string) ($entry['purchase_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">Kauf / Anfrage</a>
                                    <?php endif; ?>
                                    <?php if (!empty($entry['update_url'])): ?>
                                        <a class="link-chip" href="<?php echo htmlspecialchars((string) ($entry['update_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">update.json</a>
                                    <?php endif; ?>
                                    <?php if (!empty($entry['manifest'])): ?>
                                        <a class="link-chip" href="<?php echo htmlspecialchars((string) ($entry['manifest'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">manifest.json</a>
                                    <?php endif; ?>
                                    <?php if (!empty($entry['docs_url'])): ?>
                                        <a class="link-chip" href="<?php echo htmlspecialchars((string) ($entry['docs_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Doku</a>
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
