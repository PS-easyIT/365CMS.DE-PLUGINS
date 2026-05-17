<?php
/**
 * CMS M365 Tools – global settings view.
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
$isPackagePriceGrid = $area === 'package-prices' && in_array($activeTab, ['base-packages', 'addons'], true);
$packagePriceRows = [];

if ($isPackagePriceGrid) {
    foreach ($fields as $field) {
        $key = (string) ($field['key'] ?? '');
        if (!preg_match('/^pkg-(.+)-(public|member|group)-eur$/', $key, $matches)) {
            continue;
        }

        $slug = (string) $matches[1];
        $tier = (string) $matches[2];
        $label = (string) ($field['label'] ?? $slug);
        $productLabel = preg_replace('/\s+·\s+(Public|Member|Spezial)$/u', '', $label) ?: $label;

        $packagePriceRows[$slug] ??= [
            'label' => $productLabel,
            'help' => (string) ($field['help'] ?? ''),
            'tiers' => [],
        ];
        $packagePriceRows[$slug]['tiers'][$tier] = $field;
    }
}
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
                <div class="m365calculator-admin-color-field">
                    <input type="color" id="<?php echo $esc($key); ?>" name="<?php echo $esc($key); ?>" class="form-control m365calculator-admin-control m365calculator-admin-control--color" value="<?php echo $esc(preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? $value : ($field['default'] ?? '#000000')); ?>">
                    <input type="text" class="form-control m365calculator-admin-control m365calculator-admin-control--color-text" value="<?php echo $esc(preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? $value : ($field['default'] ?? '#000000')); ?>" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$" aria-label="<?php echo $esc($label); ?> Farbwert" data-m365-color-copy="<?php echo $esc($key); ?>">
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
$renderPriceInput = static function (array $field, string $tierLabel) use ($esc, $fieldValue): void {
    $key = (string) ($field['key'] ?? '');
    $value = $fieldValue($field);
    ?>
    <div class="form-group m365calculator-price-cell">
        <label class="form-label" for="<?php echo $esc($key); ?>"><?php echo $esc($tierLabel); ?></label>
        <input type="number" id="<?php echo $esc($key); ?>" name="<?php echo $esc($key); ?>" class="form-control m365calculator-admin-control" value="<?php echo $esc($value); ?>" min="<?php echo $esc($field['min'] ?? ''); ?>" max="<?php echo $esc($field['max'] ?? ''); ?>" step="<?php echo $esc($field['step'] ?? '1'); ?>">
    </div>
    <?php
};
?>

<div class="admin-page-header">
    <div>
        <h2><?php echo $esc($pageTitle); ?></h2>
        <p><?php echo $esc($pageDescription); ?></p>
    </div>
    <div class="header-actions">
        <a href="/admin/plugins/m365tools-dashboard/m365tools-dashboard" class="btn btn-secondary">📊 Übersicht</a>
        <a href="/m365-tools" class="btn btn-primary" target="_blank" rel="noopener noreferrer">👁️ Public Hub</a>
    </div>
</div>

<?php if ($notice !== ''): ?>
<div class="alert alert-success">✅ <?php echo $esc($notice); ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
<div class="alert alert-error">❌ <?php echo $esc($error); ?></div>
<?php endif; ?>

<div class="m365calculator-admin-tabs">
    <?php foreach ($tabs as $tabKey => $tabLabel): ?>
    <a href="<?php echo $esc($baseAdminUrl . '?tab=' . rawurlencode((string) $tabKey)); ?>" class="m365calculator-admin-tab<?php echo $activeTab === $tabKey ? ' active' : ''; ?>">
        <?php echo $esc($tabLabel); ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="admin-card m365calculator-admin-tab-card<?php echo $isPackagePriceGrid ? ' m365calculator-admin-tab-card--wide' : ''; ?>">
    <div class="m365calculator-admin-callout">
        <strong>Zentrale Vorgabe</strong>
        <span>Diese Werte gelten pluginweit als Standard und können je Modul gezielt übersteuert werden.</span>
    </div>

    <form method="POST" class="admin-form">
        <input type="hidden" name="action" value="save_global_options">
        <input type="hidden" name="settings_group" value="<?php echo $esc($activeTab); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo $esc($csrfToken); ?>">

        <?php if ($isPackagePriceGrid && $packagePriceRows !== []): ?>
            <div class="m365calculator-price-table" role="table" aria-label="Paketpreise nach Preisstufe">
                <div class="m365calculator-price-table__head" role="row">
                    <span role="columnheader">Paket</span>
                    <span role="columnheader">Public</span>
                    <span role="columnheader">Member</span>
                    <span role="columnheader">Spezial</span>
                </div>
                <?php foreach ($packagePriceRows as $row): ?>
                    <div class="m365calculator-price-row" role="row">
                        <div class="m365calculator-price-product" role="cell">
                            <strong><?php echo $esc($row['label'] ?? 'Paket'); ?></strong>
                            <?php if ((string) ($row['help'] ?? '') !== ''): ?>
                                <small class="form-text"><?php echo $esc($row['help']); ?></small>
                            <?php endif; ?>
                        </div>
                        <?php foreach (['public' => 'Public', 'member' => 'Member', 'group' => 'Spezial'] as $tier => $tierLabel): ?>
                            <?php if (is_array($row['tiers'][$tier] ?? null)): ?>
                                <?php $renderPriceInput($row['tiers'][$tier], $tierLabel); ?>
                            <?php else: ?>
                                <div class="m365calculator-price-cell m365calculator-price-cell--empty" role="cell">—</div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="m365calculator-admin-form-grid">
                <?php foreach ($fields as $field): ?>
                    <?php $renderField($field); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary">💾 Globale Einstellungen speichern</button>
    </form>
</div>

<script>
document.querySelectorAll('[data-m365-color-copy]').forEach(function (input) {
    var color = document.getElementById(input.getAttribute('data-m365-color-copy'));
    if (!color) {
        return;
    }

    input.addEventListener('input', function () {
        if (/^#[0-9A-Fa-f]{6}$/.test(input.value)) {
            color.value = input.value;
        }
    });
    color.addEventListener('input', function () {
        input.value = color.value;
    });
});
</script>