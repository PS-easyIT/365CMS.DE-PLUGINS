<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>
<?php
$publicLang = $lang ?? 'de';
$dlLabel = static function (string $key) use ($publicLang): string {
    $labels = [
        'external_title' => ['de' => 'Externer Download', 'en' => 'External download'],
        'external_body_suffix' => ['de' => 'liegt auf einer externen Website.', 'en' => 'is hosted on an external website.'],
        'external_target' => ['de' => 'Ziel', 'en' => 'Target'],
        'external_continue' => ['de' => 'Externen Download öffnen', 'en' => 'Open external download'],
        'back' => ['de' => 'Zurück', 'en' => 'Back'],
        'external_note' => ['de' => 'Hinweis: Externe Downloads werden nicht direkt von 365CMS ausgeliefert. Bitte prüfe bei sensiblen Inhalten die Ziel-Domain und den Anbieter, bevor du fortfährst.', 'en' => 'Note: External downloads are not served directly by 365CMS. Please verify the target domain and provider before continuing.'],
    ];
    return (string) ($labels[$key][$publicLang] ?? $labels[$key]['de'] ?? $key);
};
?>
<main class="dl-archive-main">
    <section class="dl-empty-state" aria-labelledby="dl-external-title">
        <h1 id="dl-external-title"><?php echo htmlspecialchars($dlLabel('external_title'), ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="dl-empty-body">
            <?php echo htmlspecialchars($publicLang === 'en' ? 'The download' : 'Der Download', ENT_QUOTES, 'UTF-8'); ?>
            <strong><?php echo htmlspecialchars((string) ($download['title'] ?? 'Datei'), ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php echo htmlspecialchars($dlLabel('external_body_suffix'), ENT_QUOTES, 'UTF-8'); ?>
        </p>

        <div class="dl-card-meta dl-external-target">
            <?php echo htmlspecialchars($dlLabel('external_target'), ENT_QUOTES, 'UTF-8'); ?>: <?php echo htmlspecialchars((string) $externalUrl, ENT_QUOTES, 'UTF-8'); ?>
        </div>

        <div class="dl-external-actions">
            <a href="<?php echo htmlspecialchars($continueUrl, ENT_QUOTES, 'UTF-8'); ?>" class="dl-download-button"><?php echo htmlspecialchars($dlLabel('external_continue'), ENT_QUOTES, 'UTF-8'); ?></a>
            <a href="<?php echo htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8'); ?>" class="dl-filter-link"><?php echo htmlspecialchars($dlLabel('back'), ENT_QUOTES, 'UTF-8'); ?></a>
        </div>

        <p class="dl-admin-muted dl-external-note">
            <?php echo htmlspecialchars($dlLabel('external_note'), ENT_QUOTES, 'UTF-8'); ?>
        </p>
    </section>
</main>
