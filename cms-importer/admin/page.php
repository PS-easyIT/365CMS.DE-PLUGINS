<?php
/**
 * Admin-Template: WordPress Importer Hauptseite
 *
 * @var string|null $message         Feedback-Meldung
 * @var string      $msg_type        success|error|warning
 * @var array|null  $result          Import-Ergebnis-Array
 * @var string      $nonce           CSRF-Nonce (cms-importer-upload)
 * @var string      $nonce_download  CSRF-Nonce (cms-importer-download)
 * @var array       $log_entries     Letzte Import-Logs
 * @var array       $import_files    XML-Dateien im Import-Ordner
 * @var string      $import_dir_url  URL zum Import-Ordner
 */

if (!defined('ABSPATH')) {
    exit;
}

$esc_nonce          = htmlspecialchars($nonce ?? '');
$esc_nonce_download = htmlspecialchars($nonce_download ?? '');
?>
<div class="cms-importer-wrap">

    <!-- Header -->
    <div class="ci-header">
        <div class="ci-header__icon">&#8681;</div>
        <div class="ci-header__text">
            <h1 class="ci-header__title">WordPress Import</h1>
            <p class="ci-header__sub">WordPress WXR-Exportdateien (.xml) in die CMS-Struktur importieren &mdash; Beitr&auml;ge, Seiten &amp; Bilder.</p>
        </div>
        <a href="/admin/plugins/cms-importer/cms-importer-log" class="ci-btn ci-btn--ghost ci-btn--sm">&#128203; Protokoll</a>
    </div>

    <!-- Feedback -->
    <div id="js-import-notice"
         class="ci-notice ci-notice--<?php echo htmlspecialchars($msg_type ?? 'success'); ?>"
         <?php if (!($message ?? null)): ?>hidden<?php endif; ?>>
        <?php echo htmlspecialchars($message ?? ''); ?>
        <?php if (($result ?? null) && !empty($result['meta_report'])): ?>
            &nbsp;<a class="ci-notice__link"
               href="/admin/plugins/cms-importer/cms-importer?action=download_report&amp;log_id=<?php echo (int)($result['log_id'] ?? 0); ?>&amp;_nonce=<?php echo $esc_nonce_download; ?>">
                &#128196; Meta-Bericht herunterladen (.md)
            </a>
        <?php endif; ?>
    </div>

    <!-- Stats -->
    <div class="ci-stats" id="js-stats-box" <?php if (!($result ?? null)): ?>hidden<?php endif; ?>>
        <div class="ci-stat">
            <span class="ci-stat__val"><?php echo (int)($result['total']            ?? 0); ?></span>
            <span class="ci-stat__lbl">Gesamt</span>
        </div>
        <div class="ci-stat ci-stat--ok">
            <span class="ci-stat__val"><?php echo (int)($result['imported']         ?? 0); ?></span>
            <span class="ci-stat__lbl">Importiert</span>
        </div>
        <div class="ci-stat ci-stat--warn">
            <span class="ci-stat__val"><?php echo (int)($result['skipped']          ?? 0); ?></span>
            <span class="ci-stat__lbl">&Uuml;bersprungen</span>
        </div>
        <div class="ci-stat ci-stat--err">
            <span class="ci-stat__val"><?php echo (int)($result['errors']           ?? 0); ?></span>
            <span class="ci-stat__lbl">Fehler</span>
        </div>
        <div class="ci-stat ci-stat--img">
            <span class="ci-stat__val"><?php echo (int)($result['images_downloaded'] ?? 0); ?></span>
            <span class="ci-stat__lbl">Bilder</span>
        </div>
        <div class="ci-stat ci-stat--meta">
            <span class="ci-stat__val"><?php echo (int)($result['meta_keys']        ?? 0); ?></span>
            <span class="ci-stat__lbl">Unbekannte Metas</span>
        </div>
    </div>

    <!-- Tabs -->
    <div class="ci-tabs">
        <button class="ci-tab ci-tab--active" data-tab="upload" type="button">
            &#128193; Datei hochladen
        </button>
        <button class="ci-tab" data-tab="folder" type="button">
            &#128194; Aus Import-Ordner
            <?php if (!empty($import_files)): ?>
                <span class="ci-tab-badge"><?php echo count($import_files); ?></span>
            <?php endif; ?>
        </button>
    </div>

    <!-- Tab: Upload -->
    <div class="ci-tab-panel ci-tab-panel--active" id="tab-upload">
        <div class="ci-card">
            <h2 class="ci-card__title">WordPress-Exportdatei hochladen</h2>

            <form id="js-import-form" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="_nonce"     value="<?php echo $esc_nonce; ?>">
                <input type="hidden" name="cms_action" value="cms_importer_upload_only">
                <input type="hidden" id="js-uploaded-file" name="import_file" value="">

                <!-- Verstecktes File-Input -->
                <input type="file" name="wxr_file" id="wxr_file"
                       accept=".xml,text/xml,application/xml"
                       style="display:none">

                <!-- 3-Schritt-Assistent ──────────────────────────────────────── -->
                <div class="ci-wizard">

                    <!-- Schritt 1: Datei auswählen -->
                    <div class="ci-wizard-step ci-wizard-step--active" id="ci-step-1">
                        <div class="ci-wizard-step__badge"><span>1</span></div>
                        <div class="ci-wizard-step__content">
                            <p class="ci-wizard-step__title">Datei&nbsp;ausw&auml;hlen</p>
                            <button type="button" class="ci-btn ci-btn--ghost" id="js-btn-select">
                                &#128193;&nbsp;XML&nbsp;ausw&auml;hlen
                            </button>
                            <span class="ci-upload-filename" id="js-filename"></span>
                        </div>
                    </div>

                    <div class="ci-wizard-sep" id="ci-sep-1"></div>

                    <!-- Schritt 2: Hochladen -->
                    <div class="ci-wizard-step" id="ci-step-2">
                        <div class="ci-wizard-step__badge"><span>2</span></div>
                        <div class="ci-wizard-step__content">
                            <p class="ci-wizard-step__title">Hochladen</p>
                            <button type="button" class="ci-btn ci-btn--ghost" id="js-btn-upload" disabled>
                                &#8679;&nbsp;Hochladen
                            </button>
                            <span id="js-upload-status" class="ci-wizard-step__status"></span>
                        </div>
                    </div>

                    <div class="ci-wizard-sep" id="ci-sep-2"></div>

                    <!-- Schritt 3: Import starten -->
                    <div class="ci-wizard-step" id="ci-step-3">
                        <div class="ci-wizard-step__badge"><span>3</span></div>
                        <div class="ci-wizard-step__content">
                            <p class="ci-wizard-step__title">Import&nbsp;starten</p>
                            <button type="button" class="ci-btn ci-btn--primary" id="js-submit-btn" disabled>
                                <span id="js-btn-text">&#9654;&nbsp;Import&nbsp;starten</span>
                                <span id="js-btn-spin" hidden>&#8635;&nbsp;Importiere&hellip;</span>
                            </button>
                        </div>
                    </div>

                </div><!-- /.ci-wizard -->

                <!-- Import-Optionen ──────────────────────────────────────────── -->
                <div class="ci-options">
                    <p class="ci-options__title">Import-Optionen</p>
                    <div class="ci-options__grid">
                        <label class="ci-option">
                            <input type="checkbox" name="skip_duplicates"     value="1" checked>
                            <span>Duplikate &uuml;berspringen (gleicher Slug)</span>
                        </label>
                        <label class="ci-option">
                            <input type="checkbox" name="import_drafts"       value="1" checked>
                            <span>Entw&uuml;rfe importieren</span>
                        </label>
                        <label class="ci-option">
                            <input type="checkbox" name="import_trashed"      value="1">
                            <span>Gel&ouml;schte Beitr&auml;ge importieren</span>
                        </label>
                        <label class="ci-option">
                            <input type="checkbox" name="import_custom_types" value="1" checked>
                            <span>Benutzerdefinierte Post-Types importieren</span>
                        </label>
                        <label class="ci-option">
                            <input type="checkbox" name="generate_report"     value="1" checked>
                            <span>Markdown-Bericht f&uuml;r unbekannte Meta-Felder erstellen</span>
                        </label>
                    </div>
                </div>

                <!-- Fortschrittsbalken ───────────────────────────────────────── -->
                <div class="ci-progress" id="js-progress" hidden>
                    <div class="ci-progress__bar">
                        <div class="ci-progress__fill" id="js-prog-fill"></div>
                    </div>
                    <p class="ci-progress__label" id="js-prog-label">Wird verarbeitet&hellip;</p>
                </div>

            </form>
        </div>
    </div>

    <!-- Tab: Import-Ordner -->
    <div class="ci-tab-panel" id="tab-folder">
        <div class="ci-card">
            <div class="ci-card__head">
                <h2 class="ci-card__title">
                    Dateien aus Import-Ordner
                    <?php if (!empty($import_files)): ?>
                        <span class="ci-badge"><?php echo count($import_files); ?></span>
                    <?php endif; ?>
                </h2>
                <span class="ci-muted">Pfad: <code>uploads/import/</code></span>
            </div>

            <div id="js-folder-notice" hidden></div>

            <?php if (empty($import_files)): ?>
                <div class="ci-empty">
                    <div class="ci-empty__icon">&#128194;</div>
                    <p>Keine XML-Dateien im Import-Ordner vorhanden.</p>
                    <p class="ci-muted">Lade eine Datei mit der Option &bdquo;Im Import-Ordner speichern&ldquo; hoch oder kopiere sie direkt per (S)FTP.</p>
                </div>
            <?php else: ?>
                <div class="ci-table-wrap">
                    <table class="ci-table">
                        <thead>
                            <tr>
                                <th>Dateiname</th>
                                <th>Gr&ouml;&szlig;e</th>
                                <th>Datum</th>
                                <th>Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($import_files as $f): ?>
                            <tr id="row-<?php echo htmlspecialchars($f['name']); ?>">
                                <td><code><?php echo htmlspecialchars($f['name']); ?></code></td>
                                <td><?php echo htmlspecialchars($f['size_human']); ?></td>
                                <td><?php echo htmlspecialchars($f['date']); ?></td>
                                <td>
                                    <form class="js-folder-import-form"
                                          method="POST"
                                          data-filename="<?php echo htmlspecialchars($f['name']); ?>">
                                        <input type="hidden" name="_nonce"               value="<?php echo $esc_nonce; ?>">
                                        <input type="hidden" name="cms_action"           value="cms_importer_folder_import">
                                        <input type="hidden" name="import_file"          value="<?php echo htmlspecialchars($f['name']); ?>">
                                        <input type="hidden" name="skip_duplicates"      value="1">
                                        <input type="hidden" name="import_drafts"        value="1">
                                        <input type="hidden" name="import_custom_types"  value="1">
                                        <input type="hidden" name="generate_report"      value="1">
                                        <button type="submit"
                                                class="ci-btn ci-btn--primary ci-btn--sm js-folder-import-btn">
                                            &#9654; Importieren
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Info-Grid -->
    <div class="ci-info-grid">
        <div class="ci-info-card">
            <h3>&#9989; Was wird importiert?</h3>
            <ul>
                <li>Beitr&auml;ge (<code>post</code>) &amp; Seiten (<code>page</code>)</li>
                <li>Benutzerdefinierte Post-Types (optional)</li>
                <li>Kategorien &amp; Tags (kommagetrennt)</li>
                <li>SEO-Meta: Yoast, Rank Math, SEOPress</li>
                <li>Bilder &rarr; <code>uploads/images/{slug}/</code></li>
            </ul>
        </div>
        <div class="ci-info-card">
            <h3>&#9888;&#65039; Was wird NICHT importiert?</h3>
            <ul>
                <li>Kommentare</li>
                <li>Benutzerkonten</li>
                <li>Men&uuml;s &amp; Navigation</li>
                <li>Plugin-spezifische Daten (werden dokumentiert)</li>
            </ul>
        </div>
        <div class="ci-info-card">
            <h3>&#128247; Bild-Download</h3>
            <p>Alle <code>&lt;img&gt;</code>-URLs werden heruntergeladen und in
            <code>uploads/images/<em>slug</em>/</code> gespeichert.
            Das erste Bild wird als <strong>Featured Image</strong> gesetzt.</p>
        </div>
        <div class="ci-info-card">
            <h3>&#128196; Meta-Bericht</h3>
            <p>Unbekannte Meta-Felder werden in <code>cms_import_meta</code> gespeichert
            und als <strong>Markdown-Datei</strong> zum Download bereitgestellt.</p>
        </div>
    </div>

    <!-- Letzte Imports -->
    <?php if (!empty($log_entries)): ?>
    <div class="ci-card">
        <h2 class="ci-card__title">Zuletzt importiert</h2>
        <div class="ci-table-wrap">
            <table class="ci-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Dateiname</th>
                        <th>Gesamt</th>
                        <th>Importiert</th>
                        <th>&Uuml;bergangen</th>
                        <th>Fehler</th>
                        <th>Bilder</th>
                        <th>Datum</th>
                        <th>Bericht</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($log_entries as $log): ?>
                    <tr>
                        <td><?php echo (int)$log->id; ?></td>
                        <td><code class="ci-code"><?php echo htmlspecialchars($log->filename); ?></code></td>
                        <td><?php echo (int)$log->total; ?></td>
                        <td class="ci-ok"><?php echo (int)$log->imported; ?></td>
                        <td class="ci-warn"><?php echo (int)$log->skipped; ?></td>
                        <td class="ci-err"><?php echo (int)$log->errors; ?></td>
                        <td><?php echo (int)($log->images_downloaded ?? 0); ?></td>
                        <td><?php echo htmlspecialchars(substr($log->started_at ?? '', 0, 16)); ?></td>
                        <td>
                            <?php if (!empty($log->meta_report_path)): ?>
                                <a href="/admin/plugins/cms-importer/cms-importer?action=download_report&amp;log_id=<?php echo (int)$log->id; ?>&amp;_nonce=<?php echo $esc_nonce_download; ?>"
                                   class="ci-link">&#128196; .md</a>
                            <?php else: ?>
                                <span class="ci-muted">&mdash;</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /.cms-importer-wrap -->