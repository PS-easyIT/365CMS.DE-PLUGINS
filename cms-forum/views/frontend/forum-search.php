<?php
/**
 * CMS Forum – Suche
 *
 * Verfügbare Variablen:
 *   $query      – Suchbegriff
 *   $filters    – Array mit Filtern
 *   $threads    – Suchergebnisse (Thread-Objekte)
 *   $pagination – Pagination-Objekt
 *   $pageTitle  – String
 *
 * @package CMS_Forum\Views
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS_Forum\Helpers\TimeHelper;
?>

<div class="cmsforum">
    <div class="cmsforum-container">

        <!-- Breadcrumb -->
        <nav class="cmsforum-breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo htmlspecialchars(rtrim((string) SITE_URL, '/'), ENT_QUOTES, 'UTF-8'); ?>/">Startseite</a>
            <span class="cmsforum-breadcrumb__sep" aria-hidden="true">›</span>
            <a href="<?php echo htmlspecialchars(rtrim((string) SITE_URL, '/'), ENT_QUOTES, 'UTF-8'); ?>/forum">Forum</a>
            <span class="cmsforum-breadcrumb__sep" aria-hidden="true">›</span>
            <span class="cmsforum-breadcrumb__current" aria-current="page">Suche</span>
        </nav>

        <div class="cmsforum-page-header">
            <h1 class="cmsforum-page-header__title">Forum-Suche</h1>
        </div>

        <!-- Suchformular -->
        <div class="cmsforum-card">
            <form method="GET" action="<?php echo htmlspecialchars(rtrim((string) SITE_URL, '/'), ENT_QUOTES, 'UTF-8'); ?>/forum/search" class="cmsforum-search-form">
                <div class="cmsforum-search-form__row">
                          <input type="text" name="q" class="cmsforum-input cmsforum-input--lg" value="<?php echo htmlspecialchars((string) $query, ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder="Suchbegriff eingeben..." autofocus>
                          <button type="submit" class="cmsforum-btn cmsforum-btn--primary">Suchen</button>
                </div>
                <details class="cmsforum-details" <?php echo !empty($filters) ? 'open' : ''; ?>>
                    <summary class="cmsforum-details__summary">⚙️ Erweiterte Filter</summary>
                    <div class="cmsforum-details__body">
                        <div class="cmsforum-search-filters">
                            <div class="cmsforum-form-group">
                                <label class="cmsforum-label">Datum von</label>
                                <input type="date" name="from" class="cmsforum-input" value="<?php echo htmlspecialchars((string) ($filters['date_from'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="cmsforum-form-group">
                                <label class="cmsforum-label">Datum bis</label>
                                <input type="date" name="to" class="cmsforum-input" value="<?php echo htmlspecialchars((string) ($filters['date_to'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>
                    </div>
                </details>
            </form>
        </div>

        <!-- Ergebnisse -->
        <?php if ($query !== ''): ?>
            <?php if (empty($threads)): ?>
                <div class="cmsforum-empty">
                    <p class="cmsforum-empty__icon">🔍</p>
                    <p class="cmsforum-empty__text">Keine Ergebnisse für &bdquo;<?php echo htmlspecialchars((string) $query, ENT_QUOTES, 'UTF-8'); ?>&ldquo;</p>
                </div>
            <?php else: ?>
                <div class="cmsforum-search-results">
                    <p class="cmsforum-search-results__count"><?php echo $pagination->total; ?> Ergebnis<?php echo $pagination->total !== 1 ? 'se' : ''; ?> gefunden</p>

                    <div class="cmsforum-thread-list">
                        <?php foreach ($threads as $t): ?>
                        <div class="cmsforum-thread-item">
                            <div class="cmsforum-thread-item__body">
                                <h3 class="cmsforum-thread-item__title">
                                    <a href="<?php echo htmlspecialchars(rtrim((string) SITE_URL, '/'), ENT_QUOTES, 'UTF-8'); ?>/forum/thread/<?php echo (int)$t->id; ?>">
                                        <?php echo htmlspecialchars((string) $t->title, ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </h3>
                                <div class="cmsforum-thread-item__meta">
                                    <span><?php echo htmlspecialchars((string) ($t->username ?? 'Gelöscht'), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="cmsforum-thread-item__sep">·</span>
                                    <?php echo TimeHelper::tag($t->created_at); ?>
                                    <?php if (!empty($t->forum_name)): ?>
                                        <span class="cmsforum-thread-item__sep">·</span>
                                        <span>in <?php echo htmlspecialchars((string) $t->forum_name, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($t->snippet)): ?>
                                    <p class="cmsforum-search-results__snippet"><?php echo htmlspecialchars((string) $t->snippet, ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="cmsforum-thread-item__stats">
                                <span class="cmsforum-thread-item__stat">💬 <?php echo (int)$t->reply_count; ?></span>
                                <span class="cmsforum-thread-item__stat">👁️ <?php echo (int)$t->view_count; ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php echo $pagination->render(rtrim((string) SITE_URL, '/') . '/forum/search?q=' . rawurlencode((string) $query)); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div><!-- /.cmsforum-container -->
</div><!-- /.cmsforum -->
