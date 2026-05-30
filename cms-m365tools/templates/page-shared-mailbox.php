<?php
/**
 * Public Template: Shared-Mailbox vs. Lizenz-Rechner.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$formatMoney = static function (mixed $value): string {
    return htmlspecialchars(number_format((float) $value, 2, ',', '.') . ' €', ENT_QUOTES, 'UTF-8');
};

$boolFields = [
    'needs_direct_login' => ['label' => 'Eigenes Login nötig', 'hint' => 'Soll sich jemand direkt mit dieser Mailbox anmelden?'],
    'external_direct_access' => ['label' => 'Externe sollen direkt zugreifen', 'hint' => 'Externe Benutzer sollen das Postfach direkt öffnen.'],
    'send_as_required' => ['label' => 'Send As / Send on Behalf', 'hint' => 'Das Team soll im Namen der Adresse senden.'],
    'shared_calendar_required' => ['label' => 'Gemeinsamer Kalender', 'hint' => 'Termine sollen zentral im Postfach verwaltet werden.'],
    'deletion_protection_required' => ['label' => 'Löschschutz / strenge Nachvollziehbarkeit', 'hint' => 'Benutzer sollen Nachrichten nicht ohne Governance entfernen können.'],
    'mobile_usage_required' => ['label' => 'Mobile Nutzung wichtig', 'hint' => 'Zugriff soll stabil auf mobilen Clients funktionieren.'],
    'automapping_required' => ['label' => 'Automapping gewünscht', 'hint' => 'Outlook soll das Postfach automatisch anzeigen.'],
    'hidden_from_gal' => ['label' => 'Hidden GAL geplant', 'hint' => 'Die Adresse soll nicht in der globalen Adressliste erscheinen.'],
];

$complianceFields = [
    'archive_required' => ['label' => 'Archiv / Auto-expanding Archive', 'hint' => 'Archivpostfach oder sehr großes Langzeitarchiv nötig.'],
    'litigation_hold_required' => ['label' => 'Litigation Hold / In-Place Hold', 'hint' => 'Rechtliche Aufbewahrung soll aktiviert werden.'],
    'retention_required' => ['label' => 'Purview / Retention / eDiscovery', 'hint' => 'Erweiterte Compliance- oder Aufbewahrungsrichtlinien.'],
    'defender_required' => ['label' => 'Defender for Office 365', 'hint' => 'Erweiterter Schutz für Links, Anhänge oder Investigation.'],
    'encryption_required' => ['label' => 'Eigener Sicherheitskontext / Verschlüsselung', 'hint' => 'Mailboxbezogene Verschlüsselung oder persönliche Identität erforderlich.'],
];

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'Shared-Mailbox vs. Lizenz-Rechner']);
}
?>

<main class="phinit-plugin m365calc-page" id="shared-mailbox-vs-lizenz">
    <section class="phinit-card m365calc-hero" aria-labelledby="m365calc-shared-title">
        <p class="m365calc-eyebrow">Microsoft 365 · Lizenzentscheidung</p>
        <div class="m365calc-hero__content">
            <div>
                <h1 id="m365calc-shared-title">Shared-Mailbox vs. Lizenz-Rechner</h1>
                <p>Prüft, ob eine Shared Mailbox ohne Zusatzlizenz genügt, ob eine Exchange-/Compliance-Lizenz nötig wird oder ob eine Benutzer-Mailbox bzw. Microsoft 365 Group besser passt.</p>
            </div>
            <a class="phinit-btn phinit-btn--secondary" href="/m365-tools">Alle M365 Tools</a>
        </div>
    </section>

    <?php if ($notice !== ''): ?>
    <div class="phinit-note phinit-note--success"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <div class="phinit-note phinit-note--danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="m365calc-layout">
        <section class="phinit-card" aria-labelledby="m365calc-form-title">
            <div class="m365calc-section__head">
                <h2 id="m365calc-form-title">Szenario erfassen</h2>
                <p>Alle Pflichtfelder sind mit realistischen Defaults vorbelegt. Passe die Werte an deinen Tenant-Fall an.</p>
            </div>

            <form method="GET" class="m365calc-form" data-m365calc-form>
                <fieldset class="m365calc-fieldset">
                    <legend>Grunddaten</legend>
                    <div class="m365calc-form-grid m365calc-form-grid--3">
                        <div class="phinit-field">
                            <label for="purpose">Mailbox-Zweck</label>
                            <select id="purpose" name="purpose" class="phinit-select" required>
                                <?php foreach ($scenarios as $scenarioKey => $scenario): ?>
                                <option value="<?php echo htmlspecialchars((string) $scenarioKey, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ((string) $input['purpose'] === (string) $scenarioKey) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) ($scenario['label'] ?? $scenarioKey), ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="phinit-field">
                            <label for="internal_users">Interne Nutzer</label>
                            <input id="internal_users" name="internal_users" class="phinit-input" type="number" min="0" max="10000" value="<?php echo (int) $input['internal_users']; ?>" required>
                        </div>
                        <div class="phinit-field">
                            <label for="affected_mailboxes">Betroffene Mailboxen</label>
                            <input id="affected_mailboxes" name="affected_mailboxes" class="phinit-input" type="number" min="1" max="10000" value="<?php echo (int) $input['affected_mailboxes']; ?>" required>
                        </div>
                    </div>

                    <div class="m365calc-form-grid m365calc-form-grid--3">
                        <div class="phinit-field">
                            <label for="current_size_gb">Aktuelle Größe in GB</label>
                            <input id="current_size_gb" name="current_size_gb" class="phinit-input" type="number" min="0" step="0.1" value="<?php echo htmlspecialchars((string) $input['current_size_gb'], ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="phinit-field">
                            <label for="expected_size_gb">Erwartete Größe in 12 Monaten</label>
                            <input id="expected_size_gb" name="expected_size_gb" class="phinit-input" type="number" min="0" step="0.1" value="<?php echo htmlspecialchars((string) $input['expected_size_gb'], ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="phinit-field">
                            <label for="collaboration_scope">Zusammenarbeit</label>
                            <select id="collaboration_scope" name="collaboration_scope" class="phinit-select">
                                <option value="internal_mail_calendar" <?php echo (string) $input['collaboration_scope'] === 'internal_mail_calendar' ? 'selected' : ''; ?>>Interne Inbox + Kalender</option>
                                <option value="external_collaboration" <?php echo (string) $input['collaboration_scope'] === 'external_collaboration' ? 'selected' : ''; ?>>Externe Zusammenarbeit</option>
                                <option value="workspace_required" <?php echo (string) $input['collaboration_scope'] === 'workspace_required' ? 'selected' : ''; ?>>Workspace mit Dateien/Planner/SharePoint</option>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Fachliche Anforderungen</legend>
                    <div class="m365calc-choice-grid">
                        <?php foreach ($boolFields as $fieldName => $fieldMeta): ?>
                        <input type="hidden" name="<?php echo htmlspecialchars((string) $fieldName, ENT_QUOTES, 'UTF-8'); ?>" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="<?php echo htmlspecialchars((string) $fieldName, ENT_QUOTES, 'UTF-8'); ?>" value="1" <?php echo !empty($input[$fieldName]) ? 'checked' : ''; ?>>
                            <span>
                                <strong><?php echo htmlspecialchars((string) $fieldMeta['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?php echo htmlspecialchars((string) $fieldMeta['hint'], ENT_QUOTES, 'UTF-8'); ?></small>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Lizenz & Compliance</legend>
                    <div class="m365calc-choice-grid">
                        <?php foreach ($complianceFields as $fieldName => $fieldMeta): ?>
                        <input type="hidden" name="<?php echo htmlspecialchars((string) $fieldName, ENT_QUOTES, 'UTF-8'); ?>" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="<?php echo htmlspecialchars((string) $fieldName, ENT_QUOTES, 'UTF-8'); ?>" value="1" <?php echo !empty($input[$fieldName]) ? 'checked' : ''; ?>>
                            <span>
                                <strong><?php echo htmlspecialchars((string) $fieldMeta['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?php echo htmlspecialchars((string) $fieldMeta['hint'], ENT_QUOTES, 'UTF-8'); ?></small>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Betrieb & Kostenannahmen</legend>
                    <div class="m365calc-form-grid m365calc-form-grid--3">
                        <div class="phinit-field">
                            <label for="environment">Tenant-Umgebung</label>
                            <select id="environment" name="environment" class="phinit-select">
                                <option value="cloud" <?php echo (string) $input['environment'] === 'cloud' ? 'selected' : ''; ?>>Cloud-only</option>
                                <option value="hybrid" <?php echo (string) $input['environment'] === 'hybrid' ? 'selected' : ''; ?>>Hybrid Exchange</option>
                            </select>
                        </div>
                        <div class="phinit-field">
                            <label for="current_license_type">Referenzlizenz</label>
                            <input id="current_license_type" name="current_license_type" class="phinit-input" type="text" maxlength="80" value="<?php echo htmlspecialchars((string) $input['current_license_type'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <input type="hidden" name="convert_existing_user_mailbox" value="0">
                        <label class="m365calc-choice m365calc-choice--inline">
                            <input type="checkbox" name="convert_existing_user_mailbox" value="1" <?php echo !empty($input['convert_existing_user_mailbox']) ? 'checked' : ''; ?>>
                            <span><strong>Bestehende User-Mailbox konvertieren</strong><small>Migration-/Konvertierungshinweise ausgeben.</small></span>
                        </label>
                    </div>

                    <div class="m365calc-form-grid m365calc-form-grid--2">
                        <div class="phinit-field">
                            <label for="user_mailbox_monthly_price">Referenzkosten User-Mailbox / Monat</label>
                            <input id="user_mailbox_monthly_price" name="user_mailbox_monthly_price" class="phinit-input" type="number" min="0" step="0.01" value="<?php echo htmlspecialchars((string) $input['user_mailbox_monthly_price'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="phinit-field">
                            <label for="shared_license_monthly_price">Zusatzlizenz-Annahme / Monat</label>
                            <input id="shared_license_monthly_price" name="shared_license_monthly_price" class="phinit-input" type="number" min="0" step="0.01" value="<?php echo htmlspecialchars((string) $input['shared_license_monthly_price'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                </fieldset>

                <div class="m365calc-actions">
                    <button class="phinit-btn phinit-btn--primary" type="submit">Auswertung berechnen</button>
                    <button class="phinit-btn phinit-btn--secondary" type="button" data-m365calc-reset>Defaults wiederherstellen</button>
                </div>
            </form>
        </section>

        <aside class="m365calc-aside" aria-label="Hinweise">
            <section class="phinit-card">
                <h2>Quick-Regeln</h2>
                <ul class="m365calc-note-list">
                    <li>Bis 50 GB ohne eigene Shared-Mailbox-Lizenz möglich.</li>
                    <li>Zugreifende Benutzer benötigen eigene Exchange-Berechtigung.</li>
                    <li>Archiv, Hold, Retention und Defender können Lizenzbedarf auslösen.</li>
                    <li>Bei externem Workspace-Bedarf Microsoft 365 Group prüfen.</li>
                </ul>
            </section>

            <section class="phinit-card">
                <h2>Preisannahmen</h2>
                <p><?php echo htmlspecialchars((string) ($pricing['review_note'] ?? 'Richtwerte vor Bestellung prüfen.'), ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="phinit-table-wrap">
                    <table class="phinit-table">
                        <tbody>
                            <tr><th>Referenz User</th><td><?php echo $formatMoney((float) ($pricing['defaults']['reference_user_mailbox_monthly'] ?? 10.80)); ?></td></tr>
                            <tr><th>Zusatzlizenz</th><td><?php echo $formatMoney((float) ($pricing['defaults']['shared_license_monthly'] ?? 6.90)); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </aside>
    </div>

    <?php if (is_array($result)): ?>
    <section class="phinit-card m365calc-result-card" id="m365calc-result" data-m365calc-result>
        <div class="phinit-result phinit-result--<?php echo htmlspecialchars((string) $result['status_tone'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="m365calc-result-heading">
                <div>
                    <p class="m365calc-eyebrow">Empfehlung</p>
                    <h2><?php echo htmlspecialchars((string) $result['status_label'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p><?php echo htmlspecialchars((string) $result['summary'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="m365calc-score" aria-label="Shared-Mailbox-Fit Score">
                    <span>Fit Score</span>
                    <strong><?php echo (int) $result['score']; ?></strong>
                    <div class="m365calc-score__bar"><span style="--m365calc-score-width: <?php echo (int) $result['score_percent']; ?>%;"></span></div>
                </div>
            </div>
        </div>

        <div class="m365calc-summary-grid">
            <article class="phinit-card m365calc-mini-card">
                <span>Lizenzstatus</span>
                <strong><?php echo htmlspecialchars((string) $result['license_state_label'], ENT_QUOTES, 'UTF-8'); ?></strong>
            </article>
            <article class="phinit-card m365calc-mini-card">
                <span>Max. Größe</span>
                <strong><?php echo htmlspecialchars((string) number_format((float) $result['max_size_gb'], 1, ',', '.'), ENT_QUOTES, 'UTF-8'); ?> GB</strong>
            </article>
            <article class="phinit-card m365calc-mini-card">
                <span>Monatliche Zielkosten</span>
                <strong><?php echo $formatMoney((float) ($result['costs']['target_monthly'] ?? 0)); ?></strong>
            </article>
            <article class="phinit-card m365calc-mini-card">
                <span>Potenzial / Jahr</span>
                <strong><?php echo $formatMoney((float) ($result['costs']['annual_savings'] ?? 0)); ?></strong>
            </article>
        </div>

        <div class="m365calc-result-grid">
            <section class="phinit-card">
                <h3>Auslösende Regeln</h3>
                <ul class="m365calc-note-list">
                    <?php foreach (($result['rules'] ?? []) as $rule): ?>
                    <li><?php echo htmlspecialchars((string) $rule, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="phinit-card">
                <h3>Hinweise & Risiken</h3>
                <?php if (!empty($result['warnings'])): ?>
                <ul class="m365calc-note-list">
                    <?php foreach (($result['warnings'] ?? []) as $warning): ?>
                    <li><?php echo htmlspecialchars((string) $warning, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p>Keine zusätzlichen Warnungen für dieses Szenario.</p>
                <?php endif; ?>
            </section>
        </div>

        <?php if (!empty($result['recommended_products'])): ?>
        <section class="phinit-card">
            <h3>Empfohlene Zusatzlizenz / Add-on</h3>
            <ul class="m365calc-note-list">
                <?php foreach (($result['recommended_products'] ?? []) as $product): ?>
                <li><?php echo htmlspecialchars((string) $product, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>

        <section class="phinit-card">
            <h3>Kostenübersicht</h3>
            <div class="phinit-table-wrap">
                <table class="phinit-table">
                    <thead>
                        <tr>
                            <th>Kennzahl</th>
                            <th>Wert</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Betroffene Mailboxen</td><td><?php echo (int) ($result['costs']['affected_mailboxes'] ?? 1); ?></td></tr>
                        <tr><td>Referenzkosten reguläre Benutzer-Mailboxen / Monat</td><td><?php echo $formatMoney((float) ($result['costs']['reference_user_mailbox_monthly'] ?? 0)); ?></td></tr>
                        <tr><td>Zielkosten nach Empfehlung / Monat</td><td><?php echo $formatMoney((float) ($result['costs']['target_monthly'] ?? 0)); ?></td></tr>
                        <tr><td>Geschätztes Einsparpotenzial / Monat</td><td><?php echo $formatMoney((float) ($result['costs']['monthly_savings'] ?? 0)); ?></td></tr>
                        <tr><td>Geschätztes Einsparpotenzial / Jahr</td><td><?php echo $formatMoney((float) ($result['costs']['annual_savings'] ?? 0)); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="phinit-card">
            <h3>Nächste Schritte</h3>
            <ol class="m365calc-note-list">
                <?php foreach (($result['next_steps'] ?? []) as $step): ?>
                <li><?php echo htmlspecialchars((string) $step, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ol>
            <div class="m365calc-actions">
                <button type="button" class="phinit-btn phinit-btn--primary" data-m365calc-print>Druck/PDF erzeugen</button>
                <a class="phinit-btn phinit-btn--secondary" href="/kontakt">Beratung anfragen</a>
            </div>
        </section>
    </section>
    <?php endif; ?>

    <section class="phinit-card m365calc-source-card">
        <h2>Quellenstand & Annahmen</h2>
        <p>Quellenprüfung: <?php echo htmlspecialchars((string) ($rules['version'] ?? '2026-05-15'), ENT_QUOTES, 'UTF-8'); ?>. Preise sind Richtwerte und müssen vor Bestellung geprüft werden.</p>
        <?php if (!empty($rules['sources']) && is_array($rules['sources'])): ?>
        <details>
            <summary>Microsoft-Quellen anzeigen</summary>
            <ul class="m365calc-note-list">
                <?php foreach ($rules['sources'] as $sourceUrl): ?>
                <li><a href="<?php echo htmlspecialchars((string) $sourceUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars((string) $sourceUrl, ENT_QUOTES, 'UTF-8'); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>
        <?php endif; ?>
    </section>
</main>

<?php
if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getFooter();
}
