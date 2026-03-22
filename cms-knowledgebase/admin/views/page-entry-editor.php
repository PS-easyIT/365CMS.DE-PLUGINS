<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="kb-admin-shell">
    <div class="admin-page-header">
        <div>
            <h2><?php echo $entry !== null ? '✏️ Knowledgebase-Eintrag bearbeiten' : '➕ Knowledgebase-Eintrag anlegen'; ?></h2>
            <p>Pflege Fokusbegriff, Synonyme, Inhalte und Verlinkungslogik auf einer separaten Editor-Seite.</p>
        </div>
        <div class="header-actions">
            <a href="/admin/plugins/knowledgebase-dashboard/knowledgebase-entries" class="btn btn-secondary btn-sm">← Zur Liste</a>
            <a href="/admin/plugins/knowledgebase-dashboard/knowledgebase-categories" class="btn btn-secondary btn-sm">🗂️ Kategorien</a>
            <?php if ($entry !== null && !empty($entry['slug'])): ?>
                <a href="/kb/<?php echo rawurlencode((string) $entry['slug']); ?>" class="btn btn-secondary btn-sm" target="_blank" rel="noopener noreferrer">🌍 Öffnen</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-<?php echo htmlspecialchars((string) ($notice['type'] ?? 'success'), ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars((string) ($notice['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="admin-card kb-form-card kb-form-card--single">
        <div class="kb-panel-header">
            <div>
                <h3><?php echo $entry !== null ? 'Eintrag bearbeiten' : 'Neuen Eintrag anlegen'; ?></h3>
                <p>Ein Fokusbegriff bildet die Hauptverlinkung. Synonyme erweitern die Trefferbasis und der Inhalt steuert die KB-Zielseite.</p>
            </div>
        </div>

        <form method="post" class="kb-settings-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="save_entry">
            <input type="hidden" name="entry_id" value="<?php echo (int) ($entry['id'] ?? 0); ?>">

            <section class="kb-settings-group kb-settings-group--full">
                <h3>Basisdaten</h3>
                <div class="kb-form-grid">
                    <label>
                        <span>Titel</span>
                        <input type="text" name="title" value="<?php echo htmlspecialchars((string) ($entry['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                    </label>

                    <label>
                        <span>Fokusbegriff</span>
                        <input type="text" name="keyword" value="<?php echo htmlspecialchars((string) ($entry['keyword'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                    </label>

                    <label>
                        <span>Slug</span>
                        <input type="text" name="slug" value="<?php echo htmlspecialchars((string) ($entry['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="wird automatisch erzeugt">
                    </label>

                    <label>
                        <span>Kategorie</span>
                        <input type="text" name="category" list="kb-category-suggestions" value="<?php echo htmlspecialchars((string) ($entry['category'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="z. B. SEO, Hosting, Security">
                    </label>

                    <label>
                        <span>Priorität</span>
                        <input type="number" name="priority" min="1" max="9999" value="<?php echo (int) ($entry['priority'] ?? 100); ?>">
                    </label>

                    <label>
                        <span>Max. Links pro Seite</span>
                        <input type="number" name="max_links_per_page" min="1" max="20" value="<?php echo (int) ($entry['max_links_per_page'] ?? 1); ?>">
                    </label>
                </div>
            </section>

            <section class="kb-settings-group kb-settings-group--full">
                <h3>Kurzinhalte & Verlinkung</h3>
                <div class="kb-form-grid">
                    <label class="kb-form-grid__full">
                        <span>Kurzbeschreibung / Excerpt</span>
                        <textarea name="excerpt" rows="3"><?php echo htmlspecialchars((string) ($entry['excerpt'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </label>

                    <label class="kb-form-grid__full">
                        <span>Tooltip-Text</span>
                        <textarea name="tooltip_text" rows="3" placeholder="Kurze Erklärung für Hover/Fokus auf dem Link"><?php echo htmlspecialchars((string) ($entry['tooltip_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </label>

                    <label class="kb-form-grid__full">
                        <span>Synonyme</span>
                        <textarea name="synonyms" rows="4" placeholder="Ein Begriff pro Zeile oder kommasepariert"><?php echo htmlspecialchars((string) ($entry['synonyms'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </label>
                </div>
            </section>

            <section class="kb-settings-group kb-settings-group--full">
                <h3>Inhalt & Status</h3>
                <div class="kb-form-grid">
                    <label class="kb-form-grid__full">
                        <span>Inhalt der KB-Seite</span>
                        <div class="kb-editor-help" aria-label="Editor-Hinweise">
                            <span class="kb-editor-help__chip">HTML-Tags erlaubt</span>
                            <span class="kb-editor-help__chip">Site-Tables unterstützt</span>
                            <span class="kb-editor-help__chip">CMS-Präfix-Platzhalter</span>
                        </div>
                        <div class="kb-code-editor" data-code-editor>
                            <div class="kb-code-editor__toolbar" role="toolbar" aria-label="HTML-Werkzeuge">
                                <div class="kb-code-editor__toolbar-group">
                                    <button type="button" class="btn btn-secondary btn-sm" data-editor-insert="paragraph">&lt;p&gt;</button>
                                    <button type="button" class="btn btn-secondary btn-sm" data-editor-insert="heading2">&lt;h2&gt;</button>
                                    <button type="button" class="btn btn-secondary btn-sm" data-editor-insert="strong">&lt;strong&gt;</button>
                                    <button type="button" class="btn btn-secondary btn-sm" data-editor-insert="list">Liste</button>
                                    <button type="button" class="btn btn-secondary btn-sm" data-editor-insert="table">Tabelle</button>
                                    <button type="button" class="btn btn-secondary btn-sm" data-editor-insert="info-table">Info-Tabelle</button>
                                    <button type="button" class="btn btn-secondary btn-sm" data-editor-insert="kb-template">KB-Vorlage</button>
                                    <button type="button" class="btn btn-secondary btn-sm" data-editor-insert="site-table">Site-Table</button>
                                    <button type="button" class="btn btn-secondary btn-sm" data-editor-insert="prefix">Präfix</button>
                                </div>
                                <label class="kb-code-editor__snippet-picker">
                                    <span class="screen-reader-text">Snippet auswählen</span>
                                    <select data-editor-snippet>
                                        <option value="">Snippet einfügen …</option>
                                        <option value="info-table">Info-Tabelle</option>
                                        <option value="kb-template">KB-Vorlage</option>
                                        <option value="paragraph">Absatz</option>
                                        <option value="heading2">Zwischenüberschrift</option>
                                        <option value="strong">Hinweis fett</option>
                                        <option value="list">Liste</option>
                                        <option value="table">HTML-Tabelle</option>
                                        <option value="site-table">Site-Table-Shortcode</option>
                                        <option value="prefix">CMS-Präfix</option>
                                    </select>
                                </label>
                                <div class="kb-code-editor__toolbar-group kb-code-editor__toolbar-group--view">
                                    <button type="button" class="btn btn-secondary btn-sm" data-editor-toggle="code">Code</button>
                                    <button type="button" class="btn btn-secondary btn-sm" data-editor-toggle="split">Live</button>
                                    <button type="button" class="btn btn-secondary btn-sm" data-editor-toggle="preview">Vorschau</button>
                                </div>
                            </div>
                            <div class="kb-code-editor__status" data-code-editor-status>Modus: Code</div>
                            <div class="kb-code-editor__workspace">
                                <div class="kb-code-editor__panel kb-code-editor__panel--code" data-code-editor-panel="code">
                                    <div class="kb-code-editor__panel-head">Code</div>
                                    <textarea name="content" rows="14" class="kb-code-editor__textarea" placeholder="HTML mit einfachen Tags ist erlaubt." data-code-editor-input><?php echo htmlspecialchars((string) ($entry['content'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                                <div class="kb-code-editor__panel kb-code-editor__panel--preview" data-code-editor-panel="preview">
                                    <div class="kb-code-editor__panel-head">Live-Preview</div>
                                    <div class="kb-code-editor__preview" data-code-editor-preview></div>
                                </div>
                            </div>
                        </div>
                        <small class="kb-field-help">Erlaubt sind jetzt auch HTML-Tabellen wie <code>&lt;table&gt;</code>, <code>&lt;tr&gt;</code>, <code>&lt;td&gt;</code> etc. Public werden außerdem <code>[site-table id="1"]</code> und <code>[table id=1 /]</code> gerendert. Für einen editorialen Infomodul-Look kannst du den Shortcode mit <code>&lt;div class="cms-kb-table-module cms-kb-table-module--info"&gt;...&lt;/div&gt;</code> umschließen. Für Tabellen-/DB-Namen mit CMS-Präfix kannst du Platzhalter wie <code>{{cms_prefix}}</code> oder <code>{table_prefix}</code> verwenden. Mit <kbd>Tab</kbd> und <kbd>Enter</kbd> unterstützt der Editor jetzt einfache Auto-Einrückung für Listen und Tabellen.</small>
                    </label>
                </div>

                <div class="kb-checkbox-row kb-form-grid__full">
                    <label><input type="checkbox" name="is_active" value="1" <?php echo ((int) ($entry['is_active'] ?? 1) === 1) ? 'checked' : ''; ?>> Aktiv</label>
                    <label><input type="checkbox" name="is_whole_word" value="1" <?php echo ((int) ($entry['is_whole_word'] ?? 1) === 1) ? 'checked' : ''; ?>> Nur ganze Wörter verlinken</label>
                    <label><input type="checkbox" name="is_case_sensitive" value="1" <?php echo ((int) ($entry['is_case_sensitive'] ?? 0) === 1) ? 'checked' : ''; ?>> Groß-/Kleinschreibung beachten</label>
                </div>
            </section>

            <div class="kb-form-actions kb-form-grid__full">
                <button type="submit" class="btn btn-primary">💾 Speichern</button>
                <?php if ($entry !== null): ?>
                    <a href="/admin/plugins/knowledgebase-dashboard/knowledgebase-entry-editor" class="btn btn-secondary">Neuen Eintrag anlegen</a>
                <?php endif; ?>
                <a href="/admin/plugins/knowledgebase-dashboard/knowledgebase-entries" class="btn btn-secondary">Zurück zur Liste</a>
            </div>
        </form>
    </div>
</div>

<datalist id="kb-category-suggestions">
    <?php foreach ($categories as $categoryItem): ?>
        <option value="<?php echo htmlspecialchars((string) ($categoryItem['category'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></option>
    <?php endforeach; ?>
</datalist>
