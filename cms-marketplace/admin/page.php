<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$entryType = (string) ($entryType ?? ($filterType !== '' ? $filterType : 'plugin'));
$isEntrySection = in_array($section ?? '', ['overview', 'plugins', 'themes', 'cms'], true);
$isSettingsSection = ($section ?? '') === 'settings';
$isDirectorySection = ($section ?? '') === 'directory';
$isCmsSection = ($section ?? '') === 'cms';
$pageLinks = [
    'overview' => '?page=cms-marketplace',
    'cms' => '?page=cms-marketplace-cms',
    'plugins' => '?page=cms-marketplace-plugins',
    'themes' => '?page=cms-marketplace-themes',
    'directory' => '?page=cms-marketplace-directory',
    'settings' => '?page=cms-marketplace-settings',
];
$sidebarGroups = [
    [
        'label' => 'Marketplace Bereiche',
        'open' => in_array(($section ?? ''), ['overview', 'cms', 'plugins', 'themes'], true),
        'items' => [
            ['key' => 'overview', 'label' => 'Übersicht'],
            ['key' => 'cms', 'label' => 'CMS'],
            ['key' => 'plugins', 'label' => 'Plugins'],
            ['key' => 'themes', 'label' => 'Themes'],
        ],
    ],
    [
        'label' => 'System',
        'open' => in_array(($section ?? ''), ['directory', 'settings'], true),
        'items' => [
            ['key' => 'directory', 'label' => 'Verzeichnis'],
            ['key' => 'settings', 'label' => 'Einstellungen'],
        ],
    ],
];

$defaultEditValues = [
    'id' => 0,
    'type' => $entryType,
    'slug' => '',
    'name' => '',
    'version' => '',
    'author' => '',
    'description' => '',
    'category' => '',
    'homepage_url' => '',
    'docs_url' => '',
    'changelog_url' => '',
    'icon_url' => '',
    'screenshot_url' => '',
    'requires_cms' => '',
    'requires_php' => '',
    'tested_up_to' => '',
    'released_on' => date('Y-m-d'),
    'notes' => '',
    'is_paid' => 0,
    'price_amount' => '',
    'price_currency' => 'EUR',
    'contact_form_slug' => '',
    'submission_source' => 'admin',
    'submitter_name' => '',
    'submitter_email' => '',
    'is_published' => 0,
    'package_file_name' => '',
    'package_sha256' => '',
    'package_size' => 0,
];

$editValues = is_array($editItem ?? null)
    ? array_merge($defaultEditValues, (array) ($formDefaults ?? []), $editItem)
    : array_merge($defaultEditValues, (array) ($formDefaults ?? []), [
        'type' => $entryType,
        'released_on' => date('Y-m-d'),
        'price_currency' => (string) ($settings['default_currency'] ?? 'EUR'),
    ]);

$activeFilter = $filterType;
$formatBytes = static function (int $bytes): string {
    if ($bytes <= 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB'];
    $power = (int) floor(log($bytes, 1024));
    $power = max(0, min($power, count($units) - 1));
    $value = $bytes / (1024 ** $power);
    return number_format($value, $power === 0 ? 0 : 2, ',', '.') . ' ' . $units[$power];
};

$buildDirectoryInspectUrl = static function (string $path, string $scope) use ($pageLinks): string {
    return ($pageLinks['directory'] ?? '?page=cms-marketplace-directory')
        . '&scope=' . rawurlencode($scope)
        . '&inspect=' . rawurlencode($path);
};

$entryListTitle = match ($section ?? '') {
    'cms' => 'CMS-Pakete',
    'plugins' => 'Plugin-Einträge',
    'themes' => 'Theme-Einträge',
    default => 'Einträge',
};
?>
<div class="cms-marketplace-admin-shell">
    <aside class="cms-marketplace-sidebar">
        <div class="cms-marketplace-sidebar-title">Navigation</div>
        <?php foreach ($sidebarGroups as $sidebarGroup): ?>
            <details class="cms-marketplace-sidebar-group" <?php echo !empty($sidebarGroup['open']) ? 'open' : ''; ?>>
                <summary><?php echo htmlspecialchars((string) ($sidebarGroup['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></summary>
                <div class="cms-marketplace-sidebar-links">
                    <?php foreach ((array) ($sidebarGroup['items'] ?? []) as $sidebarItem): ?>
                        <?php $itemKey = (string) ($sidebarItem['key'] ?? 'overview'); ?>
                        <a href="<?php echo htmlspecialchars((string) ($pageLinks[$itemKey] ?? $pageLinks['overview']), ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo ($section ?? '') === $itemKey ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars((string) ($sidebarItem['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endforeach; ?>
    </aside>

    <main class="cms-marketplace-admin">
        <div class="cms-marketplace-header">
            <div>
                <h1><?php echo htmlspecialchars((string) ($sectionConfig['title'] ?? '365CMS Marketplace'), ENT_QUOTES, 'UTF-8'); ?></h1>
                <p><?php echo htmlspecialchars((string) ($sectionConfig['description'] ?? 'Zentrale Verwaltung für veröffentlichte Plugins und Themes unter /marketplace.'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="cms-marketplace-header-actions">
                <?php if (!empty($publicRouteMap['overview'])): ?>
                    <a class="button button-primary" href="<?php echo htmlspecialchars((string) ($publicRouteMap['overview'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Public Marketplace</a>
                <?php endif; ?>
                <?php if (!empty($publicUrls['submit'])): ?>
                    <a class="button" href="<?php echo htmlspecialchars((string) ($publicUrls['submit'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Public Einreichung</a>
                <?php endif; ?>
                <?php if (!empty($publicUrls['plugins_index'])): ?>
                    <a class="button" href="<?php echo htmlspecialchars((string) ($publicUrls['plugins_index'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Plugins Feed</a>
                <?php endif; ?>
                <?php if (!empty($publicUrls['themes_index'])): ?>
                    <a class="button" href="<?php echo htmlspecialchars((string) ($publicUrls['themes_index'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Themes Feed</a>
                <?php endif; ?>
                <?php if (!empty($publicUrls['cms_update'])): ?>
                    <a class="button" href="<?php echo htmlspecialchars((string) ($publicUrls['cms_update'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">CMS Feed</a>
                <?php endif; ?>
            </div>
        </div>

    <section class="cms-marketplace-hero-card">
        <div class="cms-marketplace-hero-copy">
            <span class="eyebrow"><?php echo htmlspecialchars((string) ($sectionConfig['label'] ?? 'Marketplace'), ENT_QUOTES, 'UTF-8'); ?></span>
            <h2><?php echo $isCmsSection ? 'CMS-Pakete, Core-Release-Dateien und Update-Feeds verwalten' : ($isSettingsSection ? 'Bereichsspezifische Defaults und Public-Routen steuern' : ($isDirectorySection ? 'Dateistruktur, Hashes und Vorschau der erzeugten Marketplace-Artefakte prüfen' : 'Marketplace, Freigaben und Public-Submission an einer Stelle steuern')); ?></h2>
            <p><?php echo $isCmsSection ? 'Hier pflegst du echte CMS-Pakete, Versionen, ZIP-Dateien sowie die öffentlichen Manifest- und Update-Dateien für den späteren 365CMS-Core-Update-Flow.' : ($isSettingsSection ? 'Lege Pfade, Währung, Verzeichnistiefe und eigene Standardwerte für CMS, Plugins und Themes fest.' : ($isDirectorySection ? 'Die Verzeichnisansicht zeigt dir Pfade, Größen, Änderungsdatum, SHA-256 und eine Textvorschau ausgewählter Dateien direkt aus dem Marketplace-Speicher.' : 'Du verwaltest hier kostenlose und kostenpflichtige Einträge, prüfst Public-Einreichungen und stellst die öffentlichen Marketplace-Seiten und Feeds für CMS, Plugins und Themes bereit.')); ?></p>
        </div>
        <div class="cms-marketplace-badges">
            <span class="badge">Gesamt: <?php echo (int) ($summary['total'] ?? 0); ?></span>
            <span class="badge">CMS: <?php echo (int) ($summary['cms'] ?? 0); ?></span>
            <span class="badge">Plugins: <?php echo (int) ($summary['plugins'] ?? 0); ?></span>
            <span class="badge">Themes: <?php echo (int) ($summary['themes'] ?? 0); ?></span>
            <span class="badge">Kostenpflichtig: <?php echo (int) ($summary['paid'] ?? 0); ?></span>
            <span class="badge badge-success">Freigegeben: <?php echo (int) ($summary['published'] ?? 0); ?></span>
            <span class="badge badge-muted">Entwurf: <?php echo (int) ($summary['drafts'] ?? 0); ?></span>
        </div>
    </section>

    <?php if (!empty($message)): ?>
        <div class="notice notice-<?php echo $messageType === 'error' ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($isSettingsSection): ?>
        <section class="cms-marketplace-card">
            <h2>Marketplace-Einstellungen</h2>
            <form method="post" class="marketplace-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="cms_marketplace_action" value="save_settings">

                <div class="settings-grid">
                    <div class="settings-group">
                        <h3>Public & Routing</h3>
                        <label class="checkbox-row">
                            <input type="checkbox" name="public_submission_enabled" value="1" <?php echo !empty($settings['public_submission_enabled']) ? 'checked' : ''; ?>>
                            <span>Öffentliche Einreichungen erlauben</span>
                        </label>
                        <label>
                            <span>Public-Submission-Pfad</span>
                            <input type="text" name="public_submission_path" value="<?php echo htmlspecialchars((string) ($settings['public_submission_path'] ?? '/marketplace-submit'), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Kontaktformular-Basis-URL</span>
                            <input type="url" name="contact_form_base_url" value="<?php echo htmlspecialchars((string) ($settings['contact_form_base_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label class="checkbox-row">
                            <input type="checkbox" name="security_reports_enabled" value="1" <?php echo !empty($settings['security_reports_enabled']) ? 'checked' : ''; ?>>
                            <span>Öffentlichen Security-Report-Intake aktivieren</span>
                        </label>
                        <label>
                            <span>Security-Report-Pfad</span>
                            <input type="text" name="security_report_path" value="<?php echo htmlspecialchars((string) ($settings['security_report_path'] ?? '/marketplace-security-report'), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <div class="package-info">
                            <strong>Öffentliche Seiten</strong>
                            <span><code><?php echo htmlspecialchars((string) ($publicRouteMap['overview'] ?? '/marketplace-public'), ENT_QUOTES, 'UTF-8'); ?></code></span>
                            <span><code><?php echo htmlspecialchars((string) ($publicRouteMap['plugins'] ?? '/marketplace-public/plugins'), ENT_QUOTES, 'UTF-8'); ?></code></span>
                            <span><code><?php echo htmlspecialchars((string) ($publicRouteMap['themes'] ?? '/marketplace-public/themes'), ENT_QUOTES, 'UTF-8'); ?></code></span>
                            <span><code><?php echo htmlspecialchars((string) ($publicRouteMap['cms'] ?? '/marketplace-public/cms'), ENT_QUOTES, 'UTF-8'); ?></code></span>
                            <?php if (!empty($publicRouteMap['security_report'])): ?>
                                <span><code><?php echo htmlspecialchars((string) ($publicRouteMap['security_report'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="settings-group">
                        <h3>Darstellung & Verzeichnis</h3>
                        <label>
                            <span>Standard-Währung</span>
                            <input type="text" name="default_currency" value="<?php echo htmlspecialchars((string) ($settings['default_currency'] ?? 'EUR'), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Verzeichnisansicht Tiefe</span>
                            <input type="number" min="1" max="6" name="directory_view_depth" value="<?php echo (int) ($settings['directory_view_depth'] ?? 3); ?>">
                        </label>
                        <label class="checkbox-row">
                            <input type="checkbox" name="show_file_sizes" value="1" <?php echo !empty($settings['show_file_sizes']) ? 'checked' : ''; ?>>
                            <span>Dateigrößen in der Verzeichnisansicht anzeigen</span>
                        </label>
                    </div>

                    <div class="settings-group">
                        <h3>Publish Guardrails</h3>
                        <label class="checkbox-row">
                            <input type="checkbox" name="publish_guardrails_enabled" value="1" <?php echo !empty($settings['publish_guardrails_enabled']) ? 'checked' : ''; ?>>
                            <span>Guardrails beim Veröffentlichen aktivieren</span>
                        </label>
                        <label class="checkbox-row">
                            <input type="checkbox" name="guardrail_require_docs_url" value="1" <?php echo !empty($settings['guardrail_require_docs_url']) ? 'checked' : ''; ?>>
                            <span>Dokumentations-URL verpflichtend</span>
                        </label>
                        <label class="checkbox-row">
                            <input type="checkbox" name="guardrail_require_changelog_url" value="1" <?php echo !empty($settings['guardrail_require_changelog_url']) ? 'checked' : ''; ?>>
                            <span>Changelog-URL verpflichtend</span>
                        </label>
                        <label class="checkbox-row">
                            <input type="checkbox" name="guardrail_require_checksum" value="1" <?php echo !empty($settings['guardrail_require_checksum']) ? 'checked' : ''; ?>>
                            <span>SHA-256 Checksum verpflichtend</span>
                        </label>
                        <label>
                            <span>Mindestalter vor Freigabe (Stunden)</span>
                            <input type="number" min="0" max="720" name="guardrail_min_age_hours" value="<?php echo (int) ($settings['guardrail_min_age_hours'] ?? 0); ?>">
                        </label>
                    </div>

                    <div class="settings-group">
                        <h3>CMS-Update-Bereich</h3>
                        <label class="checkbox-row">
                            <input type="checkbox" name="cms_updates_enabled" value="1" <?php echo !empty($settings['cms_updates_enabled']) ? 'checked' : ''; ?>>
                            <span>CMS-Update-Bereich sichtbar vorbereiten</span>
                        </label>
                        <label>
                            <span>Update-Kanal</span>
                            <select name="cms_update_channel">
                                <option value="stable" <?php echo (($settings['cms_update_channel'] ?? 'stable') === 'stable') ? 'selected' : ''; ?>>stable</option>
                                <option value="beta" <?php echo (($settings['cms_update_channel'] ?? '') === 'beta') ? 'selected' : ''; ?>>beta</option>
                                <option value="dev" <?php echo (($settings['cms_update_channel'] ?? '') === 'dev') ? 'selected' : ''; ?>>dev</option>
                            </select>
                        </label>
                        <label>
                            <span>Notizen</span>
                            <textarea name="cms_update_notes" rows="5"><?php echo htmlspecialchars((string) ($settings['cms_update_notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </label>
                    </div>

                    <div class="settings-group">
                        <h3>CMS-Defaults</h3>
                        <label>
                            <span>Standard-Slug</span>
                            <input type="text" name="cms_default_slug" value="<?php echo htmlspecialchars((string) ($settings['cms_default_slug'] ?? '365cms'), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Standard-Autor</span>
                            <input type="text" name="cms_default_author" value="<?php echo htmlspecialchars((string) ($settings['cms_default_author'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Requires CMS</span>
                            <input type="text" name="cms_default_requires_cms" value="<?php echo htmlspecialchars((string) ($settings['cms_default_requires_cms'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Requires PHP</span>
                            <input type="text" name="cms_default_requires_php" value="<?php echo htmlspecialchars((string) ($settings['cms_default_requires_php'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                    </div>

                    <div class="settings-group">
                        <h3>Plugin-Defaults</h3>
                        <label>
                            <span>Standard-Autor</span>
                            <input type="text" name="plugin_default_author" value="<?php echo htmlspecialchars((string) ($settings['plugin_default_author'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Requires CMS</span>
                            <input type="text" name="plugin_default_requires_cms" value="<?php echo htmlspecialchars((string) ($settings['plugin_default_requires_cms'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Requires PHP</span>
                            <input type="text" name="plugin_default_requires_php" value="<?php echo htmlspecialchars((string) ($settings['plugin_default_requires_php'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                    </div>

                    <div class="settings-group">
                        <h3>Theme-Defaults</h3>
                        <label>
                            <span>Standard-Autor</span>
                            <input type="text" name="theme_default_author" value="<?php echo htmlspecialchars((string) ($settings['theme_default_author'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Requires CMS</span>
                            <input type="text" name="theme_default_requires_cms" value="<?php echo htmlspecialchars((string) ($settings['theme_default_requires_cms'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Requires PHP</span>
                            <input type="text" name="theme_default_requires_php" value="<?php echo htmlspecialchars((string) ($settings['theme_default_requires_php'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary">Einstellungen speichern</button>
                </div>
            </form>

            <h3>Sicherheitsmeldungen (Admin-Review)</h3>
            <div class="table-wrap">
                <table class="marketplace-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Paket</th>
                            <th>Meldung</th>
                            <th>Reporter</th>
                            <th>Status</th>
                            <th>Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($securityReports)): ?>
                            <tr>
                                <td colspan="6" class="empty-state">Keine Sicherheitsmeldungen vorhanden.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($securityReports as $report): ?>
                                <tr>
                                    <td><?php echo (int) ($report['id'] ?? 0); ?></td>
                                    <td>
                                        <div><code><?php echo htmlspecialchars((string) (($report['type'] ?? '') . ':' . ($report['slug'] ?? '') . '@' . ($report['version'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></code></div>
                                        <div class="subline"><?php echo htmlspecialchars((string) ($report['item_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td>
                                        <div><strong><?php echo htmlspecialchars((string) ($report['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                        <div class="subline"><?php echo htmlspecialchars((string) ($report['details'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars((string) ($report['reporter_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="subline"><?php echo htmlspecialchars((string) ($report['reporter_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td><span class="status-pill"><?php echo htmlspecialchars((string) ($report['status'] ?? 'new'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td>
                                        <form method="post" class="action-stack">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="cms_marketplace_action" value="update_security_report_status">
                                            <input type="hidden" name="report_id" value="<?php echo (int) ($report['id'] ?? 0); ?>">
                                            <select name="security_status">
                                                <?php foreach (['new', 'triaged', 'resolved', 'rejected'] as $status): ?>
                                                    <option value="<?php echo $status; ?>" <?php echo (($report['status'] ?? 'new') === $status) ? 'selected' : ''; ?>><?php echo $status; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="text" name="security_status_note" placeholder="Interne Notiz" value="<?php echo htmlspecialchars((string) ($report['status_note'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" class="button button-small">Status speichern</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php elseif ($isDirectorySection): ?>
        <section class="cms-marketplace-card">
            <div class="list-header">
                <h2>Verzeichnisansicht</h2>
                <div class="filter-links">
                    <a href="?page=cms-marketplace-directory&scope=all" class="<?php echo (($directorySnapshot['scope'] ?? 'all') === 'all') ? 'active' : ''; ?>">Alle</a>
                    <a href="?page=cms-marketplace-directory&scope=cms" class="<?php echo (($directorySnapshot['scope'] ?? '') === 'cms') ? 'active' : ''; ?>">CMS</a>
                    <a href="?page=cms-marketplace-directory&scope=plugin" class="<?php echo (($directorySnapshot['scope'] ?? '') === 'plugin') ? 'active' : ''; ?>">Plugins</a>
                    <a href="?page=cms-marketplace-directory&scope=theme" class="<?php echo (($directorySnapshot['scope'] ?? '') === 'theme') ? 'active' : ''; ?>">Themes</a>
                </div>
            </div>

            <div class="directory-meta-grid">
                <div class="package-info">
                    <strong>Root-Pfad</strong>
                    <code><?php echo htmlspecialchars((string) ($directorySnapshot['root_path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                </div>
                <div class="package-info">
                    <strong>Root-URL</strong>
                    <code><?php echo htmlspecialchars((string) ($directorySnapshot['root_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                </div>
                <div class="package-info">
                    <strong>Scan-Tiefe</strong>
                    <span><?php echo (int) ($directorySnapshot['max_depth'] ?? 0); ?></span>
                </div>
            </div>

            <div class="table-wrap">
                <table class="marketplace-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Typ</th>
                            <th>Pfad</th>
                            <th>Geändert</th>
                            <th>Größe</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($directorySnapshot['entries'])): ?>
                            <tr>
                                <td colspan="6" class="empty-state">Keine Verzeichniseinträge gefunden.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach (($directorySnapshot['entries'] ?? []) as $entry): ?>
                                <tr>
                                    <td class="directory-name-cell"><span style="padding-left: <?php echo (int) (($entry['depth'] ?? 0) * 18); ?>px;"><?php echo (($entry['type'] ?? '') === 'dir') ? '📁 ' : '📄 '; ?><?php echo htmlspecialchars((string) ($entry['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td><?php echo htmlspecialchars((string) ($entry['type'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><code><?php echo htmlspecialchars((string) ($entry['relative_path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td>
                                    <td><?php echo htmlspecialchars((string) ($entry['modified_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo !empty($settings['show_file_sizes']) ? htmlspecialchars($formatBytes((int) ($entry['size'] ?? 0)), ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                                    <td>
                                        <a class="button button-small" href="<?php echo htmlspecialchars($buildDirectoryInspectUrl((string) ($entry['relative_path'] ?? ''), (string) ($directorySnapshot['scope'] ?? 'all')), ENT_QUOTES, 'UTF-8'); ?>">Ansehen</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="cms-marketplace-grid">
            <section class="cms-marketplace-card">
                <h2>Datei-Details</h2>
                <div class="feed-list">
                    <div>
                        <strong>Status</strong>
                        <span><?php echo !empty($directoryEntryDetails['exists']) ? 'Gefunden' : 'Nicht gefunden'; ?></span>
                    </div>
                    <div>
                        <strong>Typ</strong>
                        <span><?php echo htmlspecialchars((string) ($directoryEntryDetails['type'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div>
                        <strong>Relativer Pfad</strong>
                        <code><?php echo htmlspecialchars((string) ($directoryEntryDetails['relative_path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                    </div>
                    <div>
                        <strong>Absoluter Pfad</strong>
                        <code><?php echo htmlspecialchars((string) ($directoryEntryDetails['absolute_path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                    </div>
                    <div>
                        <strong>Public URL</strong>
                        <code><?php echo htmlspecialchars((string) ($directoryEntryDetails['public_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                    </div>
                    <div>
                        <strong>SHA-256</strong>
                        <code><?php echo htmlspecialchars((string) ($directoryEntryDetails['sha256'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                    </div>
                </div>
            </section>

            <section class="cms-marketplace-card">
                <h2>Vorschau</h2>
                <?php if (!empty($directoryEntryDetails['preview'])): ?>
                    <pre class="directory-preview"><?php echo htmlspecialchars((string) ($directoryEntryDetails['preview'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></pre>
                <?php else: ?>
                    <div class="empty-state">Für diesen Eintrag ist keine Textvorschau verfügbar.</div>
                <?php endif; ?>
            </section>
        </div>
    <?php elseif ($isCmsSection): ?>
        <div class="cms-marketplace-grid">
            <section class="cms-marketplace-card">
                <h2>CMS-Feed und Zielstruktur</h2>
                <div class="feed-list">
                    <div>
                        <strong>CMS Root</strong>
                        <code><?php echo htmlspecialchars((string) ($publicUrls['cms_root'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                    </div>
                    <div>
                        <strong>CMS Update Feed</strong>
                        <code><?php echo htmlspecialchars((string) ($publicUrls['cms_update'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                    </div>
                    <div>
                        <strong>Öffentliche CMS-Seite</strong>
                        <code><?php echo htmlspecialchars((string) ($publicRouteMap['cms'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                    </div>
                    <div>
                        <strong>Update-Kanal</strong>
                        <span><?php echo htmlspecialchars((string) ($settings['cms_update_channel'] ?? 'stable'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
                <p class="muted">Dieser Bereich verwaltet reale CMS-Pakete samt ZIP, Checksummen und öffentlicher Manifest-/Update-Dateien für die spätere 365CMS-Core-Integration.</p>
            </section>

            <section class="cms-marketplace-card">
                <h2><?php echo !empty($editValues['id']) ? 'CMS-Paket bearbeiten' : 'Neues CMS-Paket anlegen'; ?></h2>
                <form method="post" enctype="multipart/form-data" class="marketplace-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="cms_marketplace_action" value="save_item">
                    <input type="hidden" name="edit_id" value="<?php echo (int) ($editValues['id'] ?? 0); ?>">
                    <input type="hidden" name="filter_type" value="cms">
                    <input type="hidden" name="type" value="cms">

                    <div class="form-grid form-grid-4">
                        <label>
                            <span>Slug</span>
                            <input type="text" name="slug" required value="<?php echo htmlspecialchars((string) ($editValues['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Name</span>
                            <input type="text" name="name" required value="<?php echo htmlspecialchars((string) ($editValues['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Version</span>
                            <input type="text" name="version" required value="<?php echo htmlspecialchars((string) ($editValues['version'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Autor</span>
                            <input type="text" name="author" value="<?php echo htmlspecialchars((string) ($editValues['author'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                    </div>

                    <div class="form-grid form-grid-4">
                        <label>
                            <span>Kategorie</span>
                            <input type="text" name="category" value="<?php echo htmlspecialchars((string) ($editValues['category'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Requires CMS</span>
                            <input type="text" name="requires_cms" value="<?php echo htmlspecialchars((string) ($editValues['requires_cms'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Requires PHP</span>
                            <input type="text" name="requires_php" value="<?php echo htmlspecialchars((string) ($editValues['requires_php'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Getestet bis</span>
                            <input type="text" name="tested_up_to" value="<?php echo htmlspecialchars((string) ($editValues['tested_up_to'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                    </div>

                    <div class="form-grid form-grid-3">
                        <label>
                            <span>Release-Datum</span>
                            <input type="date" name="released_on" value="<?php echo htmlspecialchars((string) ($editValues['released_on'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>ZIP-Paket</span>
                            <input type="file" name="package_zip" accept=".zip">
                        </label>
                        <label class="checkbox-row">
                            <input type="checkbox" name="is_published" value="1" <?php echo !empty($editValues['is_published']) ? 'checked' : ''; ?>>
                            <span>Direkt freigeben</span>
                        </label>
                    </div>

                    <div class="form-grid form-grid-2">
                        <label>
                            <span>Beschreibung</span>
                            <textarea name="description" rows="4"><?php echo htmlspecialchars((string) ($editValues['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </label>
                        <label>
                            <span>Hinweise</span>
                            <textarea name="notes" rows="4"><?php echo htmlspecialchars((string) ($editValues['notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </label>
                    </div>

                    <div class="form-grid form-grid-2">
                        <label>
                            <span>Homepage-URL</span>
                            <input type="url" name="homepage_url" value="<?php echo htmlspecialchars((string) ($editValues['homepage_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Dokumentations-URL</span>
                            <input type="url" name="docs_url" value="<?php echo htmlspecialchars((string) ($editValues['docs_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                    </div>

                    <div class="form-grid form-grid-3">
                        <label>
                            <span>Changelog-URL</span>
                            <input type="url" name="changelog_url" value="<?php echo htmlspecialchars((string) ($editValues['changelog_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Icon-URL</span>
                            <input type="url" name="icon_url" value="<?php echo htmlspecialchars((string) ($editValues['icon_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Screenshot-URL</span>
                            <input type="url" name="screenshot_url" value="<?php echo htmlspecialchars((string) ($editValues['screenshot_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                    </div>

                    <label class="checkbox-row">
                        <input type="checkbox" name="is_paid" value="1" <?php echo !empty($editValues['is_paid']) ? 'checked' : ''; ?>>
                        <span>Dieses CMS-Paket ist kostenpflichtig</span>
                    </label>

                    <div class="form-grid form-grid-3">
                        <label>
                            <span>Preis</span>
                            <input type="text" name="price_amount" value="<?php echo htmlspecialchars((string) ($editValues['price_amount'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Währung</span>
                            <input type="text" name="price_currency" value="<?php echo htmlspecialchars((string) ($editValues['price_currency'] ?? 'EUR'), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Kontaktformular-Slug / Pfad</span>
                            <input type="text" name="contact_form_slug" value="<?php echo htmlspecialchars((string) ($editValues['contact_form_slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                    </div>

                    <?php if (!empty($editValues['package_file_name'])): ?>
                        <div class="package-info">
                            <strong>Aktuelles Paket</strong>
                            <span><?php echo htmlspecialchars((string) ($editValues['package_file_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                            <span>SHA-256: <code><?php echo htmlspecialchars((string) ($editValues['package_sha256'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></span>
                            <span>Größe: <?php echo htmlspecialchars($formatBytes((int) ($editValues['package_size'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="form-actions">
                        <button type="submit" class="button button-primary">CMS-Paket speichern</button>
                        <a class="button" href="<?php echo htmlspecialchars($pageLinks['cms'], ENT_QUOTES, 'UTF-8'); ?>">Neu beginnen</a>
                    </div>
                </form>
            </section>
        </div>

        <section class="cms-marketplace-card">
            <div class="list-header">
                <h2>CMS-Paketliste</h2>
                <div class="filter-links">
                    <span class="badge">Kanal: <?php echo htmlspecialchars((string) ($settings['cms_update_channel'] ?? 'stable'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <a href="?page=cms-marketplace-directory&scope=cms">CMS-Verzeichnis öffnen</a>
                </div>
            </div>

            <div class="table-wrap">
                <table class="marketplace-table">
                    <thead>
                        <tr>
                            <th>Slug</th>
                            <th>Name</th>
                            <th>Version</th>
                            <th>Modell</th>
                            <th>Status</th>
                            <th>Paket</th>
                            <th>Öffentliche URLs</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($items === []): ?>
                            <tr>
                                <td colspan="8" class="empty-state">Noch keine CMS-Pakete vorhanden.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <?php $entryUrls = $this->service->getPublicEntryUrls($item); ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars((string) ($item['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string) ($item['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="subline"><?php echo htmlspecialchars((string) ($item['author'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) ($item['version'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if (!empty($item['is_paid'])): ?>
                                            <div><strong>Kostenpflichtig</strong></div>
                                            <div class="subline"><?php echo htmlspecialchars((string) ($item['price_amount'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars((string) ($item['price_currency'] ?? 'EUR'), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <?php else: ?>
                                            <span class="subline">Kostenlos</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($item['is_published'])): ?>
                                            <span class="status-pill status-pill-success">Freigegeben</span>
                                        <?php else: ?>
                                            <span class="status-pill status-pill-muted">Entwurf</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($item['package_file_name'])): ?>
                                            <div><?php echo htmlspecialchars((string) ($item['package_file_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="subline"><?php echo htmlspecialchars($formatBytes((int) ($item['package_size'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <?php else: ?>
                                            <span class="subline">Kein Paket hinterlegt</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($entryUrls['manifest'])): ?>
                                            <div><a href="<?php echo htmlspecialchars((string) ($entryUrls['manifest'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">manifest.json</a></div>
                                            <div><a href="<?php echo htmlspecialchars((string) ($entryUrls['update'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">update.json</a></div>
                                            <?php if (!empty($entryUrls['download'])): ?>
                                                <div><a href="<?php echo htmlspecialchars((string) ($entryUrls['download'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">ZIP</a></div>
                                            <?php endif; ?>
                                            <?php if (!empty($entryUrls['purchase'])): ?>
                                                <div><a href="<?php echo htmlspecialchars((string) ($entryUrls['purchase'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Kauf / Anfrage</a></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="subline">Noch keine URLs verfügbar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-stack">
                                            <a class="button button-small" href="<?php echo htmlspecialchars($pageLinks['cms'] . '&edit=' . (int) ($item['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">Bearbeiten</a>
                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="cms_marketplace_action" value="toggle_publish">
                                                <input type="hidden" name="item_id" value="<?php echo (int) ($item['id'] ?? 0); ?>">
                                                <input type="hidden" name="filter_type" value="cms">
                                                <input type="hidden" name="publish" value="<?php echo !empty($item['is_published']) ? '0' : '1'; ?>">
                                                <button type="submit" class="button button-small <?php echo !empty($item['is_published']) ? '' : 'button-primary'; ?>">
                                                    <?php echo !empty($item['is_published']) ? 'Zurückziehen' : 'Freigeben'; ?>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php else: ?>
        <div class="cms-marketplace-grid">
            <section class="cms-marketplace-card">
                <h2>Öffentliche Feeds</h2>
                <div class="feed-list">
                    <div>
                        <strong>Marketplace Übersicht</strong>
                        <code><?php echo htmlspecialchars((string) ($publicRouteMap['overview'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                    </div>
                    <div>
                        <strong>Plugins Index</strong>
                        <code><?php echo htmlspecialchars((string) ($publicUrls['plugins_index'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                    </div>
                    <div>
                        <strong>Themes Index</strong>
                        <code><?php echo htmlspecialchars((string) ($publicUrls['themes_index'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                    </div>
                    <div>
                        <strong>Plugins Basis</strong>
                        <code><?php echo htmlspecialchars((string) ($publicUrls['plugins_root'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                    </div>
                    <div>
                        <strong>Themes Basis</strong>
                        <code><?php echo htmlspecialchars((string) ($publicUrls['themes_root'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                    </div>
                    <div>
                        <strong>Public Einreichung</strong>
                        <code><?php echo htmlspecialchars((string) ($publicUrls['submit'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                    </div>
                    <div>
                        <strong>CMS Update Feed</strong>
                        <code><?php echo htmlspecialchars((string) ($publicUrls['cms_update'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                    </div>
                </div>
                <p class="muted">Die JSON-Dateien werden automatisch aus allen freigegebenen Einträgen neu geschrieben. Kostenpflichtige Einträge erhalten Preis- und Kaufdaten statt Download-Link.</p>
            </section>

            <section class="cms-marketplace-card">
                <h2><?php echo !empty($editValues['id']) ? 'Eintrag bearbeiten' : 'Neuen Eintrag anlegen'; ?></h2>
                <form method="post" enctype="multipart/form-data" class="marketplace-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="cms_marketplace_action" value="save_item">
                    <input type="hidden" name="edit_id" value="<?php echo (int) ($editValues['id'] ?? 0); ?>">
                    <input type="hidden" name="filter_type" value="<?php echo htmlspecialchars($activeFilter, ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="form-grid form-grid-4">
                    <label>
                        <span>Typ</span>
                        <select name="type" required>
                            <option value="plugin" <?php echo (($editValues['type'] ?? '') === 'plugin') ? 'selected' : ''; ?>>Plugin</option>
                            <option value="theme" <?php echo (($editValues['type'] ?? '') === 'theme') ? 'selected' : ''; ?>>Theme</option>
                        </select>
                    </label>
                    <label>
                        <span>Slug</span>
                        <input type="text" name="slug" required value="<?php echo htmlspecialchars((string) ($editValues['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    <label>
                        <span>Name</span>
                        <input type="text" name="name" required value="<?php echo htmlspecialchars((string) ($editValues['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    <label>
                        <span>Version</span>
                        <input type="text" name="version" required value="<?php echo htmlspecialchars((string) ($editValues['version'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    </div>

                    <div class="form-grid form-grid-4">
                    <label>
                        <span>Autor</span>
                        <input type="text" name="author" value="<?php echo htmlspecialchars((string) ($editValues['author'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    <label>
                        <span>Kategorie</span>
                        <input type="text" name="category" value="<?php echo htmlspecialchars((string) ($editValues['category'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    <label>
                        <span>Requires CMS</span>
                        <input type="text" name="requires_cms" value="<?php echo htmlspecialchars((string) ($editValues['requires_cms'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    <label>
                        <span>Requires PHP</span>
                        <input type="text" name="requires_php" value="<?php echo htmlspecialchars((string) ($editValues['requires_php'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    </div>

                    <div class="form-grid form-grid-3">
                    <label>
                        <span>Getestet bis</span>
                        <input type="text" name="tested_up_to" value="<?php echo htmlspecialchars((string) ($editValues['tested_up_to'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    <label>
                        <span>Release-Datum</span>
                        <input type="date" name="released_on" value="<?php echo htmlspecialchars((string) ($editValues['released_on'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    <label>
                        <span>ZIP-Paket</span>
                        <input type="file" name="package_zip" accept=".zip">
                    </label>
                    </div>
                    <p class="muted">Für kostenlose Einträge ist ein ZIP erforderlich. Bei kostenpflichtigen Einträgen kann der Kauf über ein Kontaktformular-Ziel laufen.</p>

                    <div class="form-grid form-grid-2">
                    <label>
                        <span>Beschreibung</span>
                        <textarea name="description" rows="4"><?php echo htmlspecialchars((string) ($editValues['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </label>
                    <label>
                        <span>Hinweise</span>
                        <textarea name="notes" rows="4"><?php echo htmlspecialchars((string) ($editValues['notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </label>
                    </div>

                    <div class="form-grid form-grid-2">
                    <label>
                        <span>Homepage-URL</span>
                        <input type="url" name="homepage_url" value="<?php echo htmlspecialchars((string) ($editValues['homepage_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    <label>
                        <span>Dokumentations-URL</span>
                        <input type="url" name="docs_url" value="<?php echo htmlspecialchars((string) ($editValues['docs_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    </div>

                    <div class="form-grid form-grid-3">
                    <label>
                        <span>Changelog-URL</span>
                        <input type="url" name="changelog_url" value="<?php echo htmlspecialchars((string) ($editValues['changelog_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    <label>
                        <span>Icon-URL</span>
                        <input type="url" name="icon_url" value="<?php echo htmlspecialchars((string) ($editValues['icon_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    <label>
                        <span>Screenshot-URL</span>
                        <input type="url" name="screenshot_url" value="<?php echo htmlspecialchars((string) ($editValues['screenshot_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    </div>

                    <label class="checkbox-row">
                    <input type="checkbox" name="is_paid" value="1" <?php echo !empty($editValues['is_paid']) ? 'checked' : ''; ?>>
                    <span>Dies ist ein kostenpflichtiger Eintrag</span>
                    </label>

                    <div class="form-grid form-grid-3">
                    <label>
                        <span>Preis</span>
                        <input type="text" name="price_amount" value="<?php echo htmlspecialchars((string) ($editValues['price_amount'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    <label>
                        <span>Währung</span>
                        <input type="text" name="price_currency" value="<?php echo htmlspecialchars((string) ($editValues['price_currency'] ?? 'EUR'), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    <label>
                        <span>Kontaktformular-Slug / Pfad</span>
                        <input type="text" name="contact_form_slug" value="<?php echo htmlspecialchars((string) ($editValues['contact_form_slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    </div>
                    <p class="muted">Der Kontaktformular-Slug kann als relativer Pfad oder volle URL hinterlegt werden und wird bei kostenpflichtigen Einträgen als Kauf-/Anfrage-Link ausgegeben.</p>

                    <label class="checkbox-row">
                    <input type="checkbox" name="is_published" value="1" <?php echo !empty($editValues['is_published']) ? 'checked' : ''; ?>>
                    <span>Direkt für den öffentlichen Marketplace freigeben</span>
                    </label>

                    <?php if (($editValues['submission_source'] ?? 'admin') === 'public'): ?>
                        <div class="package-info">
                            <strong>Öffentliche Einreichung</strong>
                            <span>Quelle: Public Submission</span>
                            <span>Name: <?php echo htmlspecialchars((string) ($editValues['submitter_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                            <span>E-Mail: <?php echo htmlspecialchars((string) ($editValues['submitter_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($editValues['package_file_name'])): ?>
                        <div class="package-info">
                            <strong>Aktuelles Paket:</strong>
                            <span><?php echo htmlspecialchars((string) ($editValues['package_file_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                            <span>SHA-256: <code><?php echo htmlspecialchars((string) ($editValues['package_sha256'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></span>
                            <span>Größe: <?php echo htmlspecialchars($formatBytes((int) ($editValues['package_size'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="form-actions">
                        <button type="submit" class="button button-primary">Speichern</button>
                        <a class="button" href="<?php echo htmlspecialchars($pageLinks[$section ?? 'overview'] ?? $pageLinks['overview'], ENT_QUOTES, 'UTF-8'); ?>">Neu beginnen</a>
                    </div>
                </form>
            </section>
        </div>

        <section class="cms-marketplace-card">
            <div class="list-header">
                <h2><?php echo htmlspecialchars($entryListTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
                <div class="filter-links">
                    <?php if (($section ?? '') === 'overview'): ?>
                        <a href="?page=cms-marketplace" class="<?php echo $activeFilter === '' ? 'active' : ''; ?>">Alle</a>
                        <a href="?page=cms-marketplace&type=plugin" class="<?php echo $activeFilter === 'plugin' ? 'active' : ''; ?>">Plugins</a>
                        <a href="?page=cms-marketplace&type=theme" class="<?php echo $activeFilter === 'theme' ? 'active' : ''; ?>">Themes</a>
                    <?php else: ?>
                        <span class="badge"><?php echo ($section ?? '') === 'plugins' ? 'Filter: Plugins' : 'Filter: Themes'; ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="table-wrap">
                <table class="marketplace-table">
                    <thead>
                        <tr>
                            <th>Typ</th>
                            <th>Slug</th>
                            <th>Name</th>
                            <th>Version</th>
                            <th>Modell</th>
                            <th>Status</th>
                            <th>Paket</th>
                            <th>Öffentliche URLs</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($items === []): ?>
                            <tr>
                                <td colspan="9" class="empty-state">Noch keine Marketplace-Einträge vorhanden.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <?php $entryUrls = $this->service->getPublicEntryUrls($item); ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) ($item['type'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><code><?php echo htmlspecialchars((string) ($item['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string) ($item['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="subline"><?php echo htmlspecialchars((string) ($item['author'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="subline">Quelle: <?php echo htmlspecialchars((string) ($item['submission_source'] ?? 'admin'), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <?php if (($item['submission_source'] ?? 'admin') === 'public' && (!empty($item['submitter_name']) || !empty($item['submitter_email']))): ?>
                                            <div class="subline">Einreicher: <?php echo htmlspecialchars(trim((string) (($item['submitter_name'] ?? '') . ' ' . ($item['submitter_email'] ?? ''))), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) ($item['version'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if (!empty($item['is_paid'])): ?>
                                            <div><strong>Kostenpflichtig</strong></div>
                                            <div class="subline"><?php echo htmlspecialchars((string) ($item['price_amount'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars((string) ($item['price_currency'] ?? 'EUR'), ENT_QUOTES, 'UTF-8'); ?></div>
                                            <?php if (!empty($item['contact_form_slug'])): ?>
                                                <div class="subline"><?php echo htmlspecialchars((string) ($item['contact_form_slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="subline">Kostenlos</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($item['is_published'])): ?>
                                            <span class="status-pill status-pill-success">Freigegeben</span>
                                        <?php else: ?>
                                            <span class="status-pill status-pill-muted">Entwurf</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($item['package_file_name'])): ?>
                                            <div><?php echo htmlspecialchars((string) ($item['package_file_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="subline"><?php echo htmlspecialchars($formatBytes((int) ($item['package_size'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <?php else: ?>
                                            <span class="subline">Kein Paket hinterlegt</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($entryUrls['manifest'])): ?>
                                            <div><a href="<?php echo htmlspecialchars((string) $entryUrls['manifest'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">manifest.json</a></div>
                                            <div><a href="<?php echo htmlspecialchars((string) $entryUrls['update'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">update.json</a></div>
                                            <?php if (!empty($entryUrls['download'])): ?>
                                                <div><a href="<?php echo htmlspecialchars((string) $entryUrls['download'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">ZIP</a></div>
                                            <?php endif; ?>
                                            <?php if (!empty($entryUrls['purchase'])): ?>
                                                <div><a href="<?php echo htmlspecialchars((string) $entryUrls['purchase'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Kauf / Anfrage</a></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="subline">Noch keine URLs verfügbar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-stack">
                                            <a class="button button-small" href="<?php echo htmlspecialchars(($pageLinks[$section ?? 'overview'] ?? $pageLinks['overview']) . ((($section ?? '') === 'overview' && $activeFilter !== '') ? '&type=' . rawurlencode($activeFilter) : '') . '&edit=' . (int) ($item['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">Bearbeiten</a>
                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="cms_marketplace_action" value="toggle_publish">
                                                <input type="hidden" name="item_id" value="<?php echo (int) ($item['id'] ?? 0); ?>">
                                                <input type="hidden" name="filter_type" value="<?php echo htmlspecialchars($activeFilter, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="publish" value="<?php echo !empty($item['is_published']) ? '0' : '1'; ?>">
                                                <button type="submit" class="button button-small <?php echo !empty($item['is_published']) ? '' : 'button-primary'; ?>">
                                                    <?php echo !empty($item['is_published']) ? 'Zurückziehen' : 'Freigeben'; ?>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
    </main>
</div>
