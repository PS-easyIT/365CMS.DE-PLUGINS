<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Marketplace Einreichung</title>
    <?php if (!empty($publicCssUrl)): ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars((string) $publicCssUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
</head>
<body>
    <div class="shell">
        <div class="hero">
            <div class="hero-panel">
                <span class="eyebrow">Plugin-eigene Public-Seite</span>
                <div>
                    <h1>Marketplace Einreichung</h1>
                    <p>Hier kannst du Plugins, Themes und CMS-Pakete für den zentralen 365CMS Marketplace einreichen. Öffentliche Einreichungen werden nie sofort veröffentlicht, sondern müssen zuerst von einem Administrator geprüft und freigeschaltet werden.</p>
                </div>
                <div class="hero-badges">
                    <span class="hero-badge">Theme-unabhängige Ausgabe</span>
                    <span class="hero-badge">Admin-Freigabe erforderlich</span>
                    <span class="hero-badge">CMS, Plugins oder Themes</span>
                    <span class="hero-badge">Kostenlos oder kostenpflichtig</span>
                </div>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="notice notice-<?php echo $messageType === 'error' ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="grid">
            <aside class="card card-accent">
                <h2>Hinweise</h2>
                <div class="facts">
                    <div class="fact-item">
                        <strong>Öffentliche Einreichung</strong>
                        <div class="muted">Alle Einreichungen bleiben zunächst im Status Entwurf und müssen im Marketplace-Adminbereich freigegeben werden.</div>
                    </div>
                    <div class="fact-item">
                        <strong>Kostenpflichtige Einträge</strong>
                        <div class="muted">Bei kostenpflichtigen CMS-, Plugin- oder Theme-Einträgen ist kein Paket zwingend erforderlich. Statt Download wird im Marketplace ein Kauf-/Anfrage-Link auf dein Kontaktformular ausgegeben.</div>
                    </div>
                    <div class="fact-item">
                        <strong>Kostenlose Einträge</strong>
                        <div class="muted">Für kostenlose oder direkt installierbare Pakete ist ein ZIP-Upload erforderlich. Das ZIP muss einen Root-Ordner enthalten, der exakt dem Slug entspricht.</div>
                    </div>
                    <div class="fact-item">
                        <strong>Öffentliche URL</strong>
                        <code><?php echo htmlspecialchars((string) $submitUrl, ENT_QUOTES, 'UTF-8'); ?></code>
                    </div>
                </div>
            </aside>

            <section class="card">
                <h2>Einreichungsformular</h2>
                <form method="post" enctype="multipart/form-data" action="<?php echo htmlspecialchars((string) $submitUrl, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="MAX_FILE_SIZE" value="52428800">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <label class="hp-field" aria-hidden="true" tabindex="-1">
                        <span>Website</span>
                        <input type="text" name="company_website" value="" autocomplete="off" tabindex="-1">
                    </label>

                    <div class="form-grid cols-2">
                        <label>
                            <span>Dein Name</span>
                            <input type="text" name="submitter_name" required value="<?php echo htmlspecialchars((string) ($values['submitter_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Deine E-Mail</span>
                            <input type="email" name="submitter_email" required value="<?php echo htmlspecialchars((string) ($values['submitter_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                    </div>

                    <div class="form-grid cols-4">
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
                            <span>Name</span>
                            <input type="text" name="name" required value="<?php echo htmlspecialchars((string) ($values['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Version</span>
                            <input type="text" name="version" required value="<?php echo htmlspecialchars((string) ($values['version'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                    </div>

                    <div class="form-grid cols-4">
                        <label>
                            <span>Autor</span>
                            <input type="text" name="author" value="<?php echo htmlspecialchars((string) ($values['author'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Kategorie</span>
                            <input type="text" name="category" value="<?php echo htmlspecialchars((string) ($values['category'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Requires CMS</span>
                            <input type="text" name="requires_cms" value="<?php echo htmlspecialchars((string) ($values['requires_cms'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Requires PHP</span>
                            <input type="text" name="requires_php" value="<?php echo htmlspecialchars((string) ($values['requires_php'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                    </div>

                    <div class="form-grid cols-3">
                        <label>
                            <span>Getestet bis</span>
                            <input type="text" name="tested_up_to" value="<?php echo htmlspecialchars((string) ($values['tested_up_to'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Release-Datum</span>
                            <input type="date" name="released_on" value="<?php echo htmlspecialchars((string) ($values['released_on'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>ZIP-Paket</span>
                            <input type="file" name="package_zip" accept=".zip,application/zip,application/x-zip-compressed">
                        </label>
                    </div>
                    <div class="hint">Bei kostenpflichtigen Einträgen ist ein ZIP optional. Bei kostenlosen Einträgen ist es erforderlich. Maximal 50 MB, Root-Ordner muss exakt dem Slug entsprechen.</div>

                    <div class="form-grid cols-2">
                        <label>
                            <span>Beschreibung</span>
                            <textarea name="description"><?php echo htmlspecialchars((string) ($values['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </label>
                        <label>
                            <span>Hinweise</span>
                            <textarea name="notes"><?php echo htmlspecialchars((string) ($values['notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </label>
                    </div>

                    <div class="form-grid cols-2">
                        <label>
                            <span>Homepage-URL</span>
                            <input type="url" name="homepage_url" value="<?php echo htmlspecialchars((string) ($values['homepage_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Dokumentations-URL</span>
                            <input type="url" name="docs_url" value="<?php echo htmlspecialchars((string) ($values['docs_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                    </div>

                    <div class="form-grid cols-3">
                        <label>
                            <span>Changelog-URL</span>
                            <input type="url" name="changelog_url" value="<?php echo htmlspecialchars((string) ($values['changelog_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Icon-URL</span>
                            <input type="url" name="icon_url" value="<?php echo htmlspecialchars((string) ($values['icon_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Screenshot-URL</span>
                            <input type="url" name="screenshot_url" value="<?php echo htmlspecialchars((string) ($values['screenshot_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                    </div>

                    <label class="checkbox-row">
                        <input type="checkbox" name="is_paid" value="1" <?php echo !empty($values['is_paid']) ? 'checked' : ''; ?>>
                        <span>Dies ist ein kostenpflichtiger Eintrag</span>
                    </label>

                    <div class="form-grid cols-3">
                        <label>
                            <span>Preis</span>
                            <input type="text" name="price_amount" value="<?php echo htmlspecialchars((string) ($values['price_amount'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Währung</span>
                            <input type="text" name="price_currency" value="<?php echo htmlspecialchars((string) ($values['price_currency'] ?? 'EUR'), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Kontaktformular-Slug / Pfad</span>
                            <input type="text" name="contact_form_slug" value="<?php echo htmlspecialchars((string) ($values['contact_form_slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                    </div>
                    <div class="hint">Beispiel: <code>kontakt/plugin-anfrage</code> oder eine vollständige URL. Dieser Link wird bei kostenpflichtigen Einträgen als Kauf-/Anfrageziel verwendet.</div>

                    <div class="actions">
                        <button type="submit" class="button button-primary">Einreichen</button>
                        <?php if ($siteUrl !== ''): ?>
                            <a class="button" href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>">Zur Startseite</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>
        </div>
    </div>
</body>
</html>
