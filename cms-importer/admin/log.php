<?php
/**
 * Admin-Template: Import-Protokoll
 *
 * @var array  $log_entries      Import-Log-Einträge
 * @var string $nonce_download   CSRF-Nonce für Bericht-Download
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="cms-importer-wrap admin-content">

    <div class="cms-importer-header">
        <h1 class="cms-importer-title">
            <span class="cms-importer-icon">📋</span>
            Import-Protokoll
        </h1>
        <p class="cms-importer-subtitle">
            Übersicht aller durchgeführten WordPress-Imports inklusive Berichte für unbekannte Metadaten.
        </p>
    </div>

    <div class="cms-importer-card">
        <div class="cms-importer-card__actions">
            <a href="?page=cms-importer" class="cms-importer-btn cms-importer-btn--secondary">
                ← Zurück zum Import
            </a>
        </div>

        <?php if (empty($log_entries)): ?>
            <div class="cms-importer-empty">
                <p>Noch keine Imports durchgeführt.</p>
            </div>
        <?php else: ?>
        <table class="cms-importer-table cms-importer-table--full">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Dateiname</th>
                    <th>Typ</th>
                    <th>Gesamt</th>
                    <th>Importiert</th>
                    <th>Übersprungen</th>
                    <th>Fehler</th>
                    <th>Gestartet</th>
                    <th>Beendet</th>
                    <th>Bilder</th>
                    <th>Meta-Bericht</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($log_entries as $log): ?>
                <tr>
                    <td><?php echo (int) $log->id; ?></td>
                    <td>
                        <code class="cms-importer-code"><?php echo htmlspecialchars($log->filename); ?></code>
                    </td>
                    <td>
                        <span class="cms-importer-badge cms-importer-badge--<?php echo htmlspecialchars($log->import_type ?? 'mixed'); ?>">
                            <?php echo htmlspecialchars($log->import_type ?? 'mixed'); ?>
                        </span>
                    </td>
                    <td><?php echo (int) $log->total; ?></td>
                    <td class="cms-importer-table__success"><?php echo (int) $log->imported; ?></td>
                    <td class="cms-importer-table__warning"><?php echo (int) $log->skipped; ?></td>
                    <td class="cms-importer-table__error"><?php echo (int) $log->errors; ?></td>
                    <td><?php echo htmlspecialchars(substr($log->started_at ?? '', 0, 16)); ?></td>
                    <td>
                        <?php if (!empty($log->finished_at)): ?>
                            <?php echo htmlspecialchars(substr($log->finished_at, 0, 16)); ?>
                        <?php else: ?>
                            <span class="cms-importer-muted">Läuft…</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo (int) ($log->images_downloaded ?? 0); ?></td>
                    <td>
                        <?php if (!empty($log->meta_report_path)): ?>
                            <a href="/admin/plugins/cms-importer/cms-importer?action=download_report&amp;log_id=<?php echo (int) $log->id; ?>&amp;_nonce=<?php echo htmlspecialchars($nonce_download ?? ''); ?>&amp;format=html"
                               class="cms-importer-link">
                                📄 Bericht
                            </a>
                            <span class="cms-importer-muted"> / </span>
                            <a href="/admin/plugins/cms-importer/cms-importer?action=download_report&amp;log_id=<?php echo (int) $log->id; ?>&amp;_nonce=<?php echo htmlspecialchars($nonce_download ?? ''); ?>&amp;format=md"
                               class="cms-importer-link">.md</a>
                        <?php else: ?>
                            <span class="cms-importer-muted">Keine Metas</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
