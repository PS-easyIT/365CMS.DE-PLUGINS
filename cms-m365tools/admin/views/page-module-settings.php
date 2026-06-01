<?php
/**
 * CMS M365 Tools – module settings view.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$fieldValue = static function (array $field) use ($tabOptions): string {
    $key = (string) ($field['key'] ?? '');

    return (string) ($tabOptions[$key] ?? ($field['default'] ?? ''));
};
$moduleEnabled = (int) ($moduleSettings['is_enabled'] ?? 1) === 1;
$publicUrl = trim((string) ($tool['url'] ?? ''));
$renderField = static function (array $field) use ($esc, $fieldValue): void {
    $key = (string) ($field['key'] ?? '');
    $label = (string) ($field['label'] ?? $key);
    $type = (string) ($field['type'] ?? 'text');
    $value = $fieldValue($field);
    $help = (string) ($field['help'] ?? '');
    ?>
    <div class="form-group m365calculator-admin-field">
        <?php if ($type === 'checkbox'): ?>
            <label class="checkbox-label" for="<?php echo $esc($key); ?>">
                <input type="checkbox" id="<?php echo $esc($key); ?>" name="<?php echo $esc($key); ?>" value="1"<?php echo $value === '1' ? ' checked' : ''; ?>>
                <?php echo $esc($label); ?>
            </label>
        <?php else: ?>
            <label class="form-label" for="<?php echo $esc($key); ?>"><?php echo $esc($label); ?></label>
            <?php if ($type === 'textarea'): ?>
                <textarea id="<?php echo $esc($key); ?>" name="<?php echo $esc($key); ?>" class="form-control m365calculator-admin-control" rows="4"><?php echo $esc($value); ?></textarea>
            <?php elseif ($type === 'select'): ?>
                <select id="<?php echo $esc($key); ?>" name="<?php echo $esc($key); ?>" class="form-control m365calculator-admin-control">
                    <?php foreach ((array) ($field['options'] ?? []) as $optionValue => $optionLabel): ?>
                    <option value="<?php echo $esc($optionValue); ?>"<?php echo $value === (string) $optionValue ? ' selected' : ''; ?>><?php echo $esc($optionLabel); ?></option>
                    <?php endforeach; ?>
                </select>
            <?php elseif ($type === 'number'): ?>
                <input type="number" id="<?php echo $esc($key); ?>" name="<?php echo $esc($key); ?>" class="form-control m365calculator-admin-control" value="<?php echo $esc($value); ?>" min="<?php echo $esc($field['min'] ?? ''); ?>" max="<?php echo $esc($field['max'] ?? ''); ?>" step="<?php echo $esc($field['step'] ?? '1'); ?>">
            <?php elseif ($type === 'color'): ?>
                <?php $colorValue = preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? $value : (string) ($field['default'] ?? '#000000'); ?>
                <div class="m365calculator-admin-color-field">
                    <input type="color" id="<?php echo $esc($key); ?>" name="<?php echo $esc($key); ?>" class="form-control m365calculator-admin-control m365calculator-admin-control--color" value="<?php echo $esc($colorValue); ?>">
                    <input type="text" class="form-control m365calculator-admin-control m365calculator-admin-control--color-text" value="<?php echo $esc($colorValue); ?>" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$" aria-label="<?php echo $esc($label); ?> Farbwert" data-m365-color-copy="<?php echo $esc($key); ?>">
                </div>
            <?php else: ?>
                <input type="text" id="<?php echo $esc($key); ?>" name="<?php echo $esc($key); ?>" class="form-control m365calculator-admin-control" value="<?php echo $esc($value); ?>" maxlength="255">
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($help !== ''): ?>
            <small class="form-text"><?php echo $esc($help); ?></small>
        <?php endif; ?>
    </div>
    <?php
};
?>

<div class="admin-page-header">
    <div>
        <h2>⚙️ <?php echo $esc($tool['title'] ?? $moduleKey); ?></h2>
        <p>Modulbezogene Einstellungen, Annahmen und Workflows verwalten.</p>
    </div>
    <div class="header-actions">
        <?php if ($moduleEnabled && $publicUrl !== ''): ?>
        <a href="<?php echo $esc((string) ($tool['url'] ?? '/m365-tools')); ?>" class="btn btn-secondary" target="_blank" rel="noopener noreferrer">👁️ Public öffnen</a>
        <?php endif; ?>
        <a href="/admin/plugins/m365tools-dashboard/m365tools-dashboard" class="btn btn-primary">📊 Dashboard</a>
    </div>
</div>

<?php if ($notice !== ''): ?>
<div class="alert alert-success">✅ <?php echo $esc($notice); ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
<div class="alert alert-error">❌ <?php echo $esc($error); ?></div>
<?php endif; ?>

<div class="m365calculator-admin-subnav-layout">
    <aside class="m365calculator-admin-submenu" aria-label="Modulbereiche">
        <h3>Bereiche</h3>
        <?php foreach ($tabs as $tabKey => $tabLabel): ?>
        <a href="<?php echo $esc($moduleAdminUrl . '?tab=' . rawurlencode((string) $tabKey)); ?>" class="m365calculator-admin-submenu-link<?php echo $activeTab === $tabKey ? ' active' : ''; ?>">
            <?php echo $esc($tabLabel); ?>
        </a>
        <?php endforeach; ?>
    </aside>

    <div class="admin-card m365calculator-admin-tab-card">
        <?php if ($activeTab === 'overview'): ?>
            <h3>📌 Modulübersicht</h3>
            <div class="m365calculator-admin-summary-grid">
                <div>
                    <strong>Kategorie</strong>
                    <span><?php echo $esc($tool['category'] ?? ''); ?></span>
                </div>
                <div>
                    <strong>Status</strong>
                    <span><?php echo $esc($moduleSettings['status_override'] ?? ($tool['status'] ?? 'live')); ?></span>
                </div>
                <div>
                    <strong>Sortierung</strong>
                    <span><?php echo (int) ($moduleSettings['priority_override'] ?? ($tool['priority'] ?? 100)); ?></span>
                </div>
                <div>
                    <strong>Route</strong>
                    <span><code><?php echo $esc($tool['url'] ?? ''); ?></code></span>
                </div>
            </div>
            <p class="m365calculator-admin-muted">Dieses Modul hat einen eigenen Admin-Unterpunkt. Anzeige, Preisannahmen, Workflow und Datenstand werden in den Bereichen dieser Seite gepflegt.</p>
            <div class="m365calculator-admin-next-actions">
                <a class="btn btn-secondary" href="<?php echo $esc($moduleAdminUrl . '?tab=display'); ?>">🖥️ Anzeige bearbeiten</a>
                <a class="btn btn-secondary" href="<?php echo $esc($moduleAdminUrl . '?tab=design'); ?>">🎨 Public-Design</a>
                <a class="btn btn-secondary" href="<?php echo $esc($moduleAdminUrl . '?tab=pricing'); ?>">💶 Preise anpassen</a>
                <a class="btn btn-secondary" href="<?php echo $esc($moduleAdminUrl . '?tab=workflow'); ?>">🔁 Workflow pflegen</a>
            </div>
        <?php elseif ($activeTab === 'display'): ?>
            <h3>🖥️ Anzeige & Modulkarte</h3>
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="save_module_display">
                <input type="hidden" name="csrf_token" value="<?php echo $esc($csrfToken); ?>">

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_enabled" value="1"<?php echo (int) ($moduleSettings['is_enabled'] ?? 1) === 1 ? ' checked' : ''; ?>>
                        Modul öffentlich anzeigen
                    </label>
                    <small class="form-text">Steuert die Sichtbarkeit auf der Hub-Landingpage und in der Registry.</small>
                </div>

                <div class="m365calculator-admin-form-grid">
                    <div class="form-group">
                        <label class="form-label" for="status_override">Status</label>
                        <select id="status_override" name="status_override" class="form-control m365calculator-admin-control">
                            <?php foreach (['live' => 'Live', 'beta' => 'Beta', 'soon' => 'Bald'] as $status => $label): ?>
                            <option value="<?php echo $esc($status); ?>"<?php echo (string) ($moduleSettings['status_override'] ?? ($tool['status'] ?? 'live')) === $status ? ' selected' : ''; ?>><?php echo $esc($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="priority_override">Sortierung</label>
                        <input type="number" id="priority_override" name="priority_override" class="form-control m365calculator-admin-control" value="<?php echo (int) ($moduleSettings['priority_override'] ?? ($tool['priority'] ?? 100)); ?>" min="0" max="1000">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="title_override">Öffentlicher Titel</label>
                    <input type="text" id="title_override" name="title_override" class="form-control m365calculator-admin-control" value="<?php echo $esc($moduleSettings['title_override'] ?? ''); ?>" maxlength="90" placeholder="<?php echo $esc($tool['title'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="description_override">Öffentliche Beschreibung</label>
                    <textarea id="description_override" name="description_override" class="form-control m365calculator-admin-control" rows="3" maxlength="140" placeholder="<?php echo $esc($tool['description'] ?? ''); ?>"><?php echo $esc($moduleSettings['description_override'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">💾 Anzeige speichern</button>
            </form>
        <?php else: ?>
            <?php
            $tabTitles = [
                'design' => '🎨 Public-Design dieses Moduls',
                'pricing' => '💶 Preise & Annahmen',
                'workflow' => '🔁 Workflow-Einstellungen',
                'data' => '🧾 Datenstand & Regeln',
            ];
            ?>
            <h3><?php echo $esc($tabTitles[$activeTab] ?? '⚙️ Einstellungen'); ?></h3>
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="save_module_options">
                <input type="hidden" name="settings_group" value="<?php echo $esc($activeTab); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $esc($csrfToken); ?>">

                <div class="m365calculator-admin-form-grid">
                    <?php foreach ($fields as $field): ?>
                        <?php $renderField($field); ?>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
            </form>
        <?php endif; ?>
    </div>
</div>
