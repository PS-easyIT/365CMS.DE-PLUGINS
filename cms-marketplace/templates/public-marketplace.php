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
    <style>
        :root {
            color-scheme: light;
            --bg: #f8fafc;
            --card: #ffffff;
            --line: #dbe4f0;
            --text: #0f172a;
            --muted: #64748b;
            --primary: #2563eb;
            --primary-soft: rgba(37, 99, 235, 0.12);
            --success: #166534;
            --success-bg: #dcfce7;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: var(--text);
            background: var(--bg);
        }
        a { color: var(--primary); }
        .shell {
            max-width: 1180px;
            margin: 0 auto;
            padding: 40px 20px 72px;
            display: grid;
            gap: 24px;
        }
        .hero {
            display: grid;
            gap: 18px;
            padding: 28px;
            border-radius: 24px;
            background: #ffffff;
            border: 1px solid #dbeafe;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }
        .eyebrow {
            display: inline-flex;
            width: fit-content;
            padding: 6px 12px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .hero h1 {
            margin: 0;
            font-size: 38px;
            line-height: 1.1;
        }
        .hero p {
            margin: 0;
            color: var(--muted);
            max-width: 860px;
            line-height: 1.7;
        }
        .hero-badges,
        .section-nav,
        .entry-links,
        .section-card-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .hero-badge,
        .section-nav a,
        .button,
        .link-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 9px 14px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: #ffffff;
            color: #1e293b;
            text-decoration: none;
            font-weight: 700;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .section-nav a.active,
        .button-primary {
            background: var(--primary);
            border-color: var(--primary);
            color: #ffffff;
        }
        .hero-badge.success {
            background: var(--success-bg);
            color: var(--success);
            border-color: #bbf7d0;
        }
        .button:hover,
        .section-nav a:hover,
        .link-chip:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
        }
        .cards-4 {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 22px;
            padding: 22px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }
        .card h2,
        .card h3 {
            margin: 0 0 12px;
        }
        .card p,
        .muted {
            color: var(--muted);
            line-height: 1.7;
        }
        .stat {
            font-size: 30px;
            font-weight: 800;
            color: #0f172a;
        }
        .entry-list {
            display: grid;
            gap: 16px;
        }
        .entry-card {
            display: grid;
            gap: 12px;
            padding: 18px;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
        }
        .entry-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            flex-wrap: wrap;
        }
        .entry-title {
            margin: 0;
            font-size: 22px;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: #e0f2fe;
            color: #0369a1;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .pill.success {
            background: var(--success-bg);
            color: var(--success);
        }
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }
        .meta-box {
            padding: 12px 14px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .meta-box strong {
            display: block;
            margin-bottom: 4px;
        }
        code {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 8px;
            background: #f8fafc;
            word-break: break-all;
            color: #334155;
        }
        .empty {
            padding: 18px;
            border-radius: 18px;
            border: 1px dashed #cbd5e1;
            background: #ffffff;
            color: var(--muted);
        }
        @media (max-width: 1100px) {
            .cards-4,
            .grid,
            .meta-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
