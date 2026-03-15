<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>
<main class="dl-archive-main">
    <section class="dl-empty-state" aria-labelledby="dl-external-title">
        <p class="dl-empty-icon" aria-hidden="true">🌐</p>
        <h1 id="dl-external-title">Externer Download</h1>
        <p class="dl-empty-body">
            Der Download <strong><?php echo htmlspecialchars((string) ($download['title'] ?? 'Datei'), ENT_QUOTES, 'UTF-8'); ?></strong>
            liegt auf einer externen Website.
        </p>

        <div class="dl-card-meta" style="margin: 1rem 0 1.5rem; display: block;">
            Ziel: <?php echo htmlspecialchars((string) $externalUrl, ENT_QUOTES, 'UTF-8'); ?>
        </div>

        <div style="display:flex; gap:0.75rem; flex-wrap:wrap; justify-content:center;">
            <a href="<?php echo htmlspecialchars($continueUrl, ENT_QUOTES, 'UTF-8'); ?>" class="dl-download-button">↗️ Externen Download öffnen</a>
            <a href="<?php echo htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8'); ?>" class="dl-filter-link">← Zurück</a>
        </div>

        <p class="dl-admin-muted" style="margin-top:1rem; max-width:52rem;">
            Hinweis: Externe Downloads werden nicht direkt von 365CMS ausgeliefert. Bitte prüfe bei sensiblen Inhalten die Ziel-Domain und den Anbieter, bevor du fortfährst.
        </p>
    </section>
</main>