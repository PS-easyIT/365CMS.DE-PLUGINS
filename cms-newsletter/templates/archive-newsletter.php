<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<main class="newsletter-archive">
    <?php if ($message !== ''): ?>
        <div class="newsletter-alert<?php echo $messageType === 'error' ? ' newsletter-alert--error' : ''; ?>" role="status" aria-live="polite">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <section class="newsletter-hero">
        <div class="newsletter-hero__panel newsletter-hero__panel--brand">
            <span class="newsletter-kicker">365CMS Newsletter</span>
            <h1 class="newsletter-title"><?php echo htmlspecialchars((string) ($settings['archive_title'] ?? 'Newsletter'), ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="newsletter-lead"><?php echo htmlspecialchars((string) ($settings['archive_description'] ?? 'Bleib über neue Inhalte, Events und Produkt-Updates auf dem Laufenden.'), ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="newsletter-lead"><?php echo htmlspecialchars((string) ($settings['subscribe_intro'] ?? 'Melde dich für Produkt-News, Event-Hinweise und neue Fachbeiträge an.'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <div class="newsletter-hero__panel">
            <h2 class="newsletter-section-title">Jetzt anmelden</h2>
            <form method="post" action="/newsletter/subscribe" class="newsletter-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <label class="newsletter-hp" aria-hidden="true" tabindex="-1">
                    Website
                    <input type="text" name="website" value="" autocomplete="off" tabindex="-1">
                </label>
                <div class="newsletter-form__grid">
                    <label class="newsletter-label" for="newsletter-first-name">
                        Vorname <span class="newsletter-label__hint">optional</span>
                        <input id="newsletter-first-name" class="newsletter-input" type="text" name="first_name" autocomplete="given-name" maxlength="120" placeholder="Max">
                    </label>
                    <label class="newsletter-label" for="newsletter-last-name">
                        Nachname <span class="newsletter-label__hint">optional</span>
                        <input id="newsletter-last-name" class="newsletter-input" type="text" name="last_name" autocomplete="family-name" maxlength="120" placeholder="Muster">
                    </label>
                </div>
                <label class="newsletter-label" for="newsletter-email">
                    E-Mail-Adresse
                    <input id="newsletter-email" class="newsletter-input" type="email" name="email" autocomplete="email" required maxlength="190" placeholder="max@example.com">
                </label>
                <label class="newsletter-label" for="newsletter-segment">
                    Segment
                    <input id="newsletter-segment" class="newsletter-input" type="text" name="segment_slug" maxlength="80" value="<?php echo htmlspecialchars((string) ($settings['default_segment'] ?? 'general'), ENT_QUOTES, 'UTF-8'); ?>">
                </label>
                <button class="newsletter-submit" type="submit">Newsletter abonnieren</button>
            </form>
            <p class="newsletter-lead newsletter-form-note"><?php echo htmlspecialchars((string) ($settings['footer_note'] ?? 'Du kannst dich jederzeit wieder mit einem Klick abmelden.'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </section>

    <section class="newsletter-stat-grid">
        <div class="newsletter-stat"><strong><?php echo number_format((int) ($stats['active_subscribers'] ?? 0)); ?></strong><span>aktive Abonnenten</span></div>
        <div class="newsletter-stat"><strong><?php echo number_format((int) ($stats['campaigns'] ?? 0)); ?></strong><span>angelegte Kampagnen</span></div>
        <div class="newsletter-stat"><strong><?php echo number_format((int) ($stats['templates'] ?? 0)); ?></strong><span>verfügbare Templates</span></div>
    </section>

    <section class="newsletter-card-grid">
        <article class="newsletter-card">
            <h3 class="newsletter-section-title">Was du erhältst</h3>
            <ul class="newsletter-list">
                <li>Produkt- und Plugin-Updates aus dem 365CMS-Ökosystem</li>
                <li>Hinweise zu Events, Releases und neuen Funktionen</li>
                <li>Kurze, fokussierte Fachinhalte statt Inbox-Lawinen</li>
            </ul>
        </article>
        <article class="newsletter-card">
            <h3 class="newsletter-section-title">Aktuelle Versandplanung</h3>
            <?php if (empty($campaigns)): ?>
                <p>Aktuell sind noch keine Kampagnen öffentlich sichtbar vorbereitet.</p>
            <?php else: ?>
                <ul class="newsletter-list">
                    <?php foreach ($campaigns as $campaign): ?>
                        <li>
                            <strong><?php echo htmlspecialchars((string) ($campaign['name'] ?? 'Kampagne'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            – <?php echo htmlspecialchars((string) ($campaign['subject'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>
    </section>
</main>
