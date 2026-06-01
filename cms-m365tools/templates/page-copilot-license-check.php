<?php
/**
 * Public Template: Copilot Lizenz-Pflicht-Checker.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$formatMoney = static function (mixed $value, string $currency = 'EUR'): string {
    $suffix = $currency === 'EUR' ? ' €' : ' ' . $currency;
    return htmlspecialchars(number_format((float) $value, 2, ',', '.') . $suffix, ENT_QUOTES, 'UTF-8');
};

$readinessFields = [
    'has_entra_account' => ['label' => 'Microsoft Entra ID-Konto vorhanden', 'hint' => 'Benutzer sind als Entra Work-/School-Accounts im Tenant vorhanden.'],
    'has_primary_exchange_mailbox' => ['label' => 'Primäres Exchange-Online-Postfach vorhanden', 'hint' => 'Kein Shared-, Group-, Archive- oder Delegate-Postfach als Ersatz.'],
    'm365_apps_deployed' => ['label' => 'Microsoft 365 Apps bereitgestellt', 'hint' => 'Apps sind ausgerollt und updatefähig.'],
    'onedrive_enabled' => ['label' => 'OneDrive aktiviert', 'hint' => 'OneDrive-Konten und Datei-Dienste sind für die Zielgruppe bereit.'],
    'teams_ready' => ['label' => 'Teams-Readiness geprüft', 'hint' => 'Teams-App, Meeting-Policies und Transkription sind bewertet.'],
    'privacy_controls_reviewed' => ['label' => 'App-Privacy geprüft', 'hint' => 'Connected Experiences und Privacy Controls blockieren Copilot nicht.'],
    'network_ready' => ['label' => 'Netzwerk bereit', 'hint' => 'Microsoft-365-Endpunkte und WebSockets sind nicht blockiert.'],
];

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'Copilot Lizenz-Pflicht-Checker']);
}
?>

<main class="phinit-plugin m365calc-page" id="copilot-lizenz-check">
    <section class="phinit-card m365calc-hero" aria-labelledby="m365calc-copilot-title">
        <p class="m365calc-eyebrow">Microsoft 365 · Copilot-Eligibility</p>
        <header class="m365calc-hero__content">
            <section>
                <h1 id="m365calc-copilot-title">Copilot Lizenz-Pflicht-Checker</h1>
                <p>Prüft Basislizenz, Copilot-Variante und technische Mindestvoraussetzungen vor der Zuweisung.</p>
            </section>
            <nav class="m365calc-actions" aria-label="Weitere Copilot Tools">
                <a class="phinit-btn phinit-btn--secondary" href="/copilot-roi-rechner">ROI berechnen</a>
                <a class="phinit-btn phinit-btn--secondary" href="/m365-lizenzberater">Lizenzberater</a>
            </nav>
        </header>
    </section>

    <?php if ($notice !== ''): ?>
    <section class="phinit-note phinit-note--success" role="status" aria-live="polite"><?php echo $esc($notice); ?></section>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <section class="phinit-note phinit-note--danger" role="alert"><?php echo $esc($error); ?></section>
    <?php endif; ?>

    <section class="m365calc-layout" aria-label="Copilot Lizenzprüfung">
        <section class="phinit-card" aria-labelledby="m365calc-copilot-form-title">
            <header class="m365calc-section__head">
                <section>
                    <h2 id="m365calc-copilot-form-title">Lizenzbasis und Readiness erfassen</h2>
                    <p>Erfasst Tenant-Typ, Basislizenz, Zielgruppe und die wichtigsten Readiness-Punkte.</p>
                </section>
            </header>

            <form method="GET" class="m365calc-form" data-m365calc-form>
                <fieldset class="m365calc-fieldset">
                    <legend>Lizenzdaten</legend>
                    <section class="m365calc-form-grid m365calc-form-grid--3">
                        <section class="phinit-field">
                            <label for="tenant_segment">Tenant-Typ</label>
                            <select id="tenant_segment" name="tenant_segment" class="phinit-select" required>
                                <?php foreach ($planOptions as $segmentKey => $segmentMeta): ?>
                                <option value="<?php echo $esc($segmentKey); ?>" <?php echo (string) $input['tenant_segment'] === (string) $segmentKey ? 'selected' : ''; ?>><?php echo $esc($segmentMeta['label'] ?? $segmentKey); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </section>

                        <section class="phinit-field">
                            <label for="desired_variant">Gewünschte Copilot-Variante</label>
                            <select id="desired_variant" name="desired_variant" class="phinit-select" required>
                                <option value="full_copilot" <?php echo (string) $input['desired_variant'] === 'full_copilot' ? 'selected' : ''; ?>>Microsoft 365 Copilot Add-on</option>
                                <option value="copilot_chat" <?php echo (string) $input['desired_variant'] === 'copilot_chat' ? 'selected' : ''; ?>>Nur Copilot Chat prüfen</option>
                            </select>
                        </section>

                        <section class="phinit-field">
                            <label for="base_plan">Aktuelle Basislizenz</label>
                            <select id="base_plan" name="base_plan" class="phinit-select" required>
                                <?php foreach ($planOptions as $segmentKey => $segmentMeta): ?>
                                <optgroup label="<?php echo $esc($segmentMeta['label'] ?? $segmentKey); ?>">
                                    <?php foreach (($segmentMeta['plans'] ?? []) as $planKey => $plan): ?>
                                    <option value="<?php echo $esc($planKey); ?>" <?php echo ((string) $input['tenant_segment'] === (string) $segmentKey && (string) $input['base_plan'] === (string) $planKey) ? 'selected' : ''; ?>><?php echo $esc($plan['name'] ?? $planKey); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </section>
                    </section>

                    <section class="m365calc-form-grid m365calc-form-grid--3">
                        <section class="phinit-field">
                            <label for="target_user_count">Zielgruppe in Usern</label>
                            <input id="target_user_count" name="target_user_count" class="phinit-input" type="number" min="1" max="100000" value="<?php echo (int) $input['target_user_count']; ?>" required>
                        </section>
                        <section class="phinit-field">
                            <label for="tenant_user_count">Tenant gesamt in Usern</label>
                            <input id="tenant_user_count" name="tenant_user_count" class="phinit-input" type="number" min="1" max="100000" value="<?php echo (int) $input['tenant_user_count']; ?>" required>
                        </section>
                        <section class="phinit-field">
                            <label for="copilot_addon_monthly">Copilot Add-on / User / Monat</label>
                            <input id="copilot_addon_monthly" name="copilot_addon_monthly" class="phinit-input" type="number" min="0" step="0.01" value="<?php echo $esc((string) $input['copilot_addon_monthly']); ?>">
                        </section>
                    </section>
                </fieldset>

                <fieldset class="m365calc-fieldset">
                    <legend>Technik-Readiness</legend>
                    <section class="m365calc-choice-grid">
                        <?php foreach ($readinessFields as $fieldName => $fieldMeta): ?>
                        <input type="hidden" name="<?php echo $esc($fieldName); ?>" value="0">
                        <label class="m365calc-choice">
                            <input type="checkbox" name="<?php echo $esc($fieldName); ?>" value="1" <?php echo !empty($input[$fieldName]) ? 'checked' : ''; ?>>
                            <span>
                                <strong><?php echo $esc($fieldMeta['label']); ?></strong>
                                <small><?php echo $esc($fieldMeta['hint']); ?></small>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </section>
                </fieldset>

                <footer class="m365calc-actions">
                    <button class="phinit-btn phinit-btn--primary" type="submit">Copilot-Check berechnen</button>
                    <button class="phinit-btn phinit-btn--secondary" type="button" data-m365calc-reset>Defaults wiederherstellen</button>
                </footer>
            </form>
        </section>

        <aside class="m365calc-aside" aria-label="Copilot Leitplanken">
            <section class="phinit-card">
                <h2>Quick-Regeln</h2>
                <ul class="m365calc-note-list">
                    <li>Copilot Add-on braucht eine berechtigte Basislizenz.</li>
                    <li>Copilot Chat ist nicht die volle M365-Copilot-Lizenz.</li>
                    <li>Primäres Exchange-Online-Postfach bleibt Pflicht.</li>
                </ul>
            </section>

            <section class="phinit-card">
                <h2>Pflegehinweis</h2>
                <p><?php echo $esc($upgradePaths['note'] ?? 'Preise und Eligibility vor Bestellung prüfen.'); ?></p>
                <p>Quellenstand: <?php echo $esc($prerequisites['source_checked_at'] ?? $prerequisites['version'] ?? '2026-05-16'); ?></p>
            </section>
        </aside>
    </section>

    <?php if (is_array($result)): ?>
    <section class="m365calc-result-card" id="m365calc-result" data-m365calc-result>
        <section class="phinit-result phinit-note phinit-note--<?php echo $esc($result['status_tone'] ?? 'info'); ?>">
            <header class="m365calc-result-heading">
                <section>
                    <p class="m365calc-eyebrow">Ergebnis</p>
                    <h2><?php echo $esc($result['status_label'] ?? ''); ?></h2>
                    <p><?php echo $esc($result['summary'] ?? ''); ?></p>
                </section>
            </header>
        </section>

        <section class="m365calc-summary-grid" aria-label="Kennzahlen">
            <article class="phinit-result m365calc-mini-card">
                <span>Basislizenz</span>
                <strong><?php echo $esc($result['license']['plan_name'] ?? ''); ?></strong>
            </article>
            <article class="phinit-result m365calc-mini-card">
                <span>Zielgruppe</span>
                <strong><?php echo (int) ($result['costs']['target_users'] ?? 0); ?> User</strong>
            </article>
            <article class="phinit-result m365calc-mini-card">
                <span>Mehrkosten / Monat</span>
                <strong><?php echo $formatMoney((float) ($result['costs']['total_monthly_delta'] ?? 0), (string) ($result['costs']['currency'] ?? 'EUR')); ?></strong>
            </article>
            <article class="phinit-result m365calc-mini-card">
                <span>Mehrkosten / Jahr</span>
                <strong><?php echo $formatMoney((float) ($result['costs']['total_annual_delta'] ?? 0), (string) ($result['costs']['currency'] ?? 'EUR')); ?></strong>
            </article>
        </section>

        <section class="m365calc-result-grid">
            <section class="phinit-card">
                <h3>Lizenzbewertung</h3>
                <ul class="m365calc-note-list">
                    <li>Volle Copilot-Lizenz möglich: <?php echo !empty($result['license']['eligible_full']) ? 'ja' : 'nein'; ?></li>
                    <li>Copilot Chat möglich: <?php echo !empty($result['license']['eligible_chat']) ? 'ja' : 'nein'; ?></li>
                    <li>Segment: <?php echo $esc($result['license']['segment_label'] ?? ''); ?></li>
                </ul>
                <?php if (!empty($result['license']['notes'])): ?>
                <ul class="m365calc-note-list">
                    <?php foreach (($result['license']['notes'] ?? []) as $note): ?>
                    <li><?php echo $esc($note); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </section>

            <section class="phinit-card">
                <h3>Copilot Chat vs. voller Copilot</h3>
                <p><?php echo $esc($result['chat_notice'] ?? ''); ?></p>
            </section>
        </section>

        <section class="m365calc-result-grid">
            <section class="phinit-card">
                <h3>Fehlende Mindestvoraussetzungen</h3>
                <?php if (!empty($result['technical']['missing_required'])): ?>
                <ul class="m365calc-note-list">
                    <?php foreach (($result['technical']['missing_required'] ?? []) as $missing): ?>
                    <li><strong><?php echo $esc($missing['label'] ?? ''); ?>:</strong> <?php echo $esc($missing['message'] ?? ''); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p>Alle Mindestvoraussetzungen sind laut Eingabe erfüllt.</p>
                <?php endif; ?>
            </section>

            <section class="phinit-card">
                <h3>Weitere Readiness-Punkte</h3>
                <?php if (!empty($result['technical']['missing_recommended'])): ?>
                <ul class="m365calc-note-list">
                    <?php foreach (($result['technical']['missing_recommended'] ?? []) as $missing): ?>
                    <li><strong><?php echo $esc($missing['label'] ?? ''); ?>:</strong> <?php echo $esc($missing['message'] ?? ''); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p>Keine zusätzlichen Technik-Warnungen für dieses Szenario.</p>
                <?php endif; ?>
            </section>
        </section>

        <section class="phinit-card">
            <h3>Upgrade- und Kostenpfad</h3>
            <section class="phinit-table-wrap">
                <table class="phinit-table">
                    <thead>
                        <tr>
                            <th>Kennzahl</th>
                            <th>Wert</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Empfohlene Zielbasis</td><td><?php echo $esc($result['upgrade']['target_label'] ?: 'Keine Änderung nötig'); ?></td></tr>
                        <tr><td>Basis-Upgrade-Delta / User</td><td><?php echo $formatMoney((float) ($result['costs']['base_upgrade_delta_per_user'] ?? 0), (string) ($result['costs']['currency'] ?? 'EUR')); ?></td></tr>
                        <tr><td>Copilot Add-on / User</td><td><?php echo $formatMoney((float) ($result['costs']['copilot_addon_per_user'] ?? 0), (string) ($result['costs']['currency'] ?? 'EUR')); ?></td></tr>
                        <tr><td>Basis-Upgrade / Monat</td><td><?php echo $formatMoney((float) ($result['costs']['base_upgrade_monthly'] ?? 0), (string) ($result['costs']['currency'] ?? 'EUR')); ?></td></tr>
                        <tr><td>Copilot Add-on / Monat</td><td><?php echo $formatMoney((float) ($result['costs']['copilot_addon_monthly'] ?? 0), (string) ($result['costs']['currency'] ?? 'EUR')); ?></td></tr>
                    </tbody>
                </table>
            </section>
            <?php if (!empty($result['upgrade']['reason'])): ?>
            <p><?php echo $esc($result['upgrade']['reason']); ?></p>
            <?php endif; ?>
        </section>

        <section class="phinit-card">
            <h3>Nächste Schritte</h3>
            <ol class="m365calc-note-list">
                <?php foreach (($result['next_steps'] ?? []) as $step): ?>
                <li><?php echo $esc($step); ?></li>
                <?php endforeach; ?>
            </ol>
            <footer class="m365calc-actions">
                <a class="phinit-btn phinit-btn--primary" href="/kontakt">Beratung anfragen</a>
                <button type="button" class="phinit-btn phinit-btn--secondary" data-m365calc-print>Druck/PDF erzeugen</button>
            </footer>
        </section>
    </section>
    <?php endif; ?>

    <section class="phinit-card m365calc-source-card">
        <h2>Quellenstand & Annahmen</h2>
        <p>Quellenprüfung: 16.05.2026. Preise sind Pflegewerte und müssen vor Bestellung im jeweiligen Vertrag geprüft werden.</p>
        <details>
            <summary>Microsoft-Quellen anzeigen</summary>
            <ul class="m365calc-note-list">
                <li><a href="https://learn.microsoft.com/en-us/microsoft-365/copilot/microsoft-365-copilot-licensing" target="_blank" rel="noopener noreferrer">License options for Microsoft 365 Copilot</a></li>
                <li><a href="https://learn.microsoft.com/en-us/microsoft-365/copilot/microsoft-365-copilot-requirements" target="_blank" rel="noopener noreferrer">Microsoft 365 app and network requirements for Microsoft 365 Copilot</a></li>
                <li><a href="https://learn.microsoft.com/en-us/copilot/manage#microsoft-365-copilot-chat-eligibility" target="_blank" rel="noopener noreferrer">Microsoft 365 Copilot Chat eligibility</a></li>
                <li><a href="https://learn.microsoft.com/en-us/copilot/overview" target="_blank" rel="noopener noreferrer">Overview of Microsoft 365 Copilot Chat</a></li>
            </ul>
        </details>
    </section>
</main>

<?php
if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getFooter();
}
