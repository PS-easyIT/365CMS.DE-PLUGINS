<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Marketplace Einreichung</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f8fafc;
            --card: #ffffff;
            --line: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
            --primary: #2563eb;
            --primary-soft: rgba(37, 99, 235, 0.12);
            --success-bg: #ecfdf5;
            --success-text: #166534;
            --error-bg: #fef2f2;
            --error-text: #991b1b;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, Arial, sans-serif;
            background: linear-gradient(180deg, #eff6ff 0%, var(--bg) 240px);
            color: var(--text);
        }
        .shell {
            max-width: 1120px;
            margin: 0 auto;
            padding: 48px 20px 64px;
        }
        .hero {
            margin-bottom: 24px;
        }
        .hero-panel {
            display: grid;
            gap: 18px;
            padding: 28px;
            border-radius: 24px;
            background: linear-gradient(135deg, rgba(255,255,255,0.94) 0%, rgba(239,246,255,0.92) 100%);
            border: 1px solid #dbeafe;
            box-shadow: 0 20px 40px rgba(37, 99, 235, 0.08);
        }
        .eyebrow {
            display: inline-flex;
            align-items: center;
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
            margin: 0 0 8px;
            font-size: 36px;
            line-height: 1.1;
        }
        .hero p {
            margin: 0;
            color: var(--muted);
            max-width: 760px;
            line-height: 1.6;
        }
        .hero-badges {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 999px;
            background: #ffffff;
            border: 1px solid #dbeafe;
            color: #1e3a8a;
            font-size: 13px;
            font-weight: 700;
        }
        .notice {
            margin: 0 0 24px;
            padding: 14px 16px;
            border-radius: 14px;
            font-weight: 600;
        }
        .notice-success {
            background: var(--success-bg);
            color: var(--success-text);
            border: 1px solid #bbf7d0;
        }
        .notice-error {
            background: var(--error-bg);
            color: var(--error-text);
            border: 1px solid #fecaca;
        }
        .grid {
            display: grid;
            grid-template-columns: 360px minmax(0, 1fr);
            gap: 24px;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.06);
        }
        .card h2 {
            margin-top: 0;
            margin-bottom: 16px;
            font-size: 24px;
        }
        .card-accent {
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            border-color: #dbeafe;
        }
        .facts {
            display: grid;
            gap: 14px;
        }
        .fact-item {
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
        }
        .facts strong {
            display: block;
            margin-bottom: 4px;
        }
        .facts code {
            display: inline-block;
            margin-top: 6px;
            padding: 4px 8px;
            border-radius: 8px;
            background: #f8fafc;
            color: #334155;
            word-break: break-all;
        }
        .muted {
            color: var(--muted);
            line-height: 1.6;
        }
        form {
            display: grid;
            gap: 16px;
        }
        .form-grid {
            display: grid;
            gap: 14px;
        }
        .form-grid.cols-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .form-grid.cols-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .form-grid.cols-4 {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
        label {
            display: grid;
            gap: 8px;
            font-weight: 600;
            color: #1f2937;
        }
        input, textarea, select {
            width: 100%;
            padding: 11px 12px;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: var(--text);
            font: inherit;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #60a5fa;
            box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.16);
        }
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-row input {
            width: auto;
        }
        .hint {
            margin-top: -4px;
            color: var(--muted);
            font-size: 14px;
        }
        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 18px;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: #ffffff;
            color: var(--text);
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }
        .button:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        }
        .button-primary {
            background: var(--primary);
            border-color: var(--primary);
            color: #ffffff;
        }
        @media (max-width: 980px) {
            .grid,
            .form-grid.cols-4,
            .form-grid.cols-3,
            .form-grid.cols-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="hero">
            <div class="hero-panel">
                <span class="eyebrow">Plugin-eigene Public-Seite</span>
                <div>
                    <h1>Marketplace Einreichung</h1>
                    <p>Hier kannst du Plugins und Themes für den zentralen 365CMS Marketplace einreichen. Öffentliche Einreichungen werden nie sofort veröffentlicht, sondern müssen zuerst von einem Administrator geprüft und freigeschaltet werden.</p>
                </div>
                <div class="hero-badges">
                    <span class="hero-badge">Theme-unabhängige Ausgabe</span>
                    <span class="hero-badge">Admin-Freigabe erforderlich</span>
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
                        <div class="muted">Bei kostenpflichtigen Plugins oder Themes ist kein Paket zwingend erforderlich. Statt Download wird im Marketplace ein Kauf-/Anfrage-Link auf dein Kontaktformular ausgegeben.</div>
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
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

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
                            <input type="file" name="package_zip" accept=".zip">
                        </label>
                    </div>
                    <div class="hint">Bei kostenpflichtigen Einträgen ist ein ZIP optional. Bei kostenlosen Einträgen ist es erforderlich.</div>

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
