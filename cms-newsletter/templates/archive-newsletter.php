<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<main class="newsletter-archive">
    <?php if ($message !== ''): ?>
        <div class="newsletter-alert<?php echo $messageType === 'error' ? ' newsletter-alert--error' : ''; ?>" role="status" aria-live="polite">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <section class="newsletter-hero">
        <div class="newsletter-hero__panel newsletter-hero__panel--brand">
            <span class="newsletter-kicker"><?php echo htmlspecialchars((string) ($copy['kicker'] ?? '365CMS Newsletter'), ENT_QUOTES, 'UTF-8'); ?></span>
            <h1 class="newsletter-title"><?php echo htmlspecialchars((string) ($copy['archive_title'] ?? 'Newsletter'), ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="newsletter-lead"><?php echo htmlspecialchars((string) ($copy['archive_description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="newsletter-lead"><?php echo htmlspecialchars((string) ($copy['subscribe_intro'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <div class="newsletter-hero__panel">
            <h2 class="newsletter-section-title"><?php echo htmlspecialchars((string) ($copy['signup_title'] ?? 'Jetzt anmelden'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <form method="post" action="<?php echo htmlspecialchars((string) ($subscribeAction ?? '/newsletter/subscribe'), ENT_QUOTES, 'UTF-8'); ?>" class="newsletter-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <label class="newsletter-hp" aria-hidden="true" tabindex="-1">
                    <?php echo htmlspecialchars((string) ($copy['website_label'] ?? 'Website'), ENT_QUOTES, 'UTF-8'); ?>
                    <input type="text" name="website" value="" autocomplete="off" tabindex="-1">
                </label>
                <div class="newsletter-form__grid">
                    <label class="newsletter-label" for="newsletter-first-name">
                        <?php echo htmlspecialchars((string) ($copy['first_name'] ?? 'Vorname'), ENT_QUOTES, 'UTF-8'); ?> <span class="newsletter-label__hint"><?php echo htmlspecialchars((string) ($copy['optional'] ?? 'optional'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <input id="newsletter-first-name" class="newsletter-input" type="text" name="first_name" autocomplete="given-name" maxlength="120" placeholder="Max">
                    </label>
                    <label class="newsletter-label" for="newsletter-last-name">
                        <?php echo htmlspecialchars((string) ($copy['last_name'] ?? 'Nachname'), ENT_QUOTES, 'UTF-8'); ?> <span class="newsletter-label__hint"><?php echo htmlspecialchars((string) ($copy['optional'] ?? 'optional'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <input id="newsletter-last-name" class="newsletter-input" type="text" name="last_name" autocomplete="family-name" maxlength="120" placeholder="Muster">
                    </label>
                </div>
                <label class="newsletter-label" for="newsletter-email">
                    <?php echo htmlspecialchars((string) ($copy['email'] ?? 'E-Mail-Adresse'), ENT_QUOTES, 'UTF-8'); ?>
                    <input id="newsletter-email" class="newsletter-input" type="email" name="email" autocomplete="email" required maxlength="190" placeholder="max@example.com">
                </label>
                <label class="newsletter-label" for="newsletter-segment">
                    <?php echo htmlspecialchars((string) ($copy['segment'] ?? 'Segment'), ENT_QUOTES, 'UTF-8'); ?>
                    <input id="newsletter-segment" class="newsletter-input" type="text" name="segment_slug" maxlength="80" value="<?php echo htmlspecialchars((string) ($settings['default_segment'] ?? 'general'), ENT_QUOTES, 'UTF-8'); ?>">
                </label>
                <button class="newsletter-submit" type="submit"><?php echo htmlspecialchars((string) ($copy['submit'] ?? 'Newsletter abonnieren'), ENT_QUOTES, 'UTF-8'); ?></button>
            </form>
            <p class="newsletter-lead newsletter-form-note"><?php echo htmlspecialchars((string) ($copy['footer_note'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </section>

    <section class="newsletter-stat-grid">
        <div class="newsletter-stat"><strong><?php echo number_format((int) ($stats['active_subscribers'] ?? 0)); ?></strong><span><?php echo htmlspecialchars((string) ($copy['active_subscribers'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></div>
        <div class="newsletter-stat"><strong><?php echo number_format((int) ($stats['campaigns'] ?? 0)); ?></strong><span><?php echo htmlspecialchars((string) ($copy['campaigns'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></div>
        <div class="newsletter-stat"><strong><?php echo number_format((int) ($stats['templates'] ?? 0)); ?></strong><span><?php echo htmlspecialchars((string) ($copy['templates'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></div>
    </section>

    <section class="newsletter-card-grid">
        <article class="newsletter-card">
            <h3 class="newsletter-section-title"><?php echo htmlspecialchars((string) ($copy['what_you_get'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h3>
            <ul class="newsletter-list">
                <li><?php echo htmlspecialchars((string) ($copy['benefit_1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                <li><?php echo htmlspecialchars((string) ($copy['benefit_2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                <li><?php echo htmlspecialchars((string) ($copy['benefit_3'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
            </ul>
        </article>
        <article class="newsletter-card">
            <h3 class="newsletter-section-title"><?php echo htmlspecialchars((string) ($copy['planning_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h3>
            <?php if (empty($campaigns)): ?>
                <p><?php echo htmlspecialchars((string) ($copy['planning_empty'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            <?php else: ?>
                <ul class="newsletter-list">
                    <?php foreach ($campaigns as $campaign): ?>
                        <li>
                            <strong><?php echo htmlspecialchars((string) ($campaign['name'] ?? ($copy['campaign_fallback'] ?? 'Kampagne')), ENT_QUOTES, 'UTF-8'); ?></strong>
                            – <?php echo htmlspecialchars((string) ($campaign['subject'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>
    </section>
</main>
