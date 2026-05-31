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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($t('Security Report', 'Security report'), ENT_QUOTES, 'UTF-8'); ?></title>
    <?php if (!empty($publicCssUrl)): ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars((string) $publicCssUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
</head>
<body>
    <div class="shell">
        <div class="hero">
            <div class="hero-panel">
                <span class="eyebrow"><?php echo htmlspecialchars($t('Dedizierter Security-Intake', 'Dedicated security intake'), ENT_QUOTES, 'UTF-8'); ?></span>
                <div>
                    <h1><?php echo htmlspecialchars($t('Sicherheitsmeldung einreichen', 'Submit security report'), ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p><?php echo htmlspecialchars($t('Melde Sicherheitsprobleme getrennt vom allgemeinen Kauf-/Kontaktweg. Die Meldung ist nur für Administratoren sichtbar und durchläuft interne Review-Status.', 'Report security issues separately from general buy/contact flow. Reports are only visible to administrators and pass through internal review states.'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="notice notice-<?php echo $messageType === 'error' ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <section class="card">
            <h2><?php echo htmlspecialchars($t('Security Report Formular', 'Security report form'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <form method="post" action="<?php echo htmlspecialchars((string) $submitUrl, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <label class="hp-field" aria-hidden="true" tabindex="-1">
                    <span>Website</span>
                    <input type="text" name="company_website" value="" autocomplete="off" tabindex="-1">
                </label>

                <div class="form-grid cols-3">
                    <label>
                        <span>Typ</span>
                        <select name="type" required>
                            <option value="cms" <?php echo (($values['type'] ?? '') === 'cms') ? 'selected' : ''; ?>>CMS</option>
                            <option value="plugin" <?php echo (($values['type'] ?? '') === 'plugin') ? 'selected' : ''; ?>>Plugin</option>
                            <option value="theme" <?php echo (($values['type'] ?? '') === 'theme') ? 'selected' : ''; ?>>Theme</option>
                        </select>
                    </label>
                    <label>
                        <span>Slug</span>
                        <input type="text" name="slug" required value="<?php echo htmlspecialchars((string) ($values['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    <label>
                        <span>Version</span>
                        <input type="text" name="version" required value="<?php echo htmlspecialchars((string) ($values['version'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                </div>

                <div class="form-grid cols-2">
                    <label>
                        <span><?php echo htmlspecialchars($t('Titel', 'Title'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <input type="text" name="title" required value="<?php echo htmlspecialchars((string) ($values['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    <label>
                        <span><?php echo htmlspecialchars($t('Dein Name', 'Your name'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <input type="text" name="reporter_name" required value="<?php echo htmlspecialchars((string) ($values['reporter_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                </div>

                <div class="form-grid cols-2">
                    <label>
                        <span><?php echo htmlspecialchars($t('Deine E-Mail', 'Your email'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <input type="email" name="reporter_email" required value="<?php echo htmlspecialchars((string) ($values['reporter_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    <label>
                        <span><?php echo htmlspecialchars($t('Details', 'Details'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <textarea name="details" required><?php echo htmlspecialchars((string) ($values['details'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </label>
                </div>

                <div class="actions">
                    <button type="submit" class="button button-primary"><?php echo htmlspecialchars($t('Sicherheitsmeldung absenden', 'Submit security report'), ENT_QUOTES, 'UTF-8'); ?></button>
                    <?php if ($siteUrl !== ''): ?>
                        <a class="button" href="<?php echo htmlspecialchars((string) $siteUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($t('Zur Startseite', 'Back to home'), ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php endif; ?>
                </div>
            </form>
        </section>
    </div>
</body>
</html>
