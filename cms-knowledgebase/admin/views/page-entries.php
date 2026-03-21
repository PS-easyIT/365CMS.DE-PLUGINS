<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<?php
$packageCount = count($standardPackages ?? []);
$starterEntryCount = array_sum(array_map(static fn(array $package): int => (int) ($package['entry_count'] ?? 0), $standardPackages ?? []));
?>

<div class="kb-admin-shell">
    <div class="admin-page-header">
        <div>
            <h2>🧠 Knowledgebase-Einträge</h2>
            <p>Pflege Fokusbegriffe, Synonyme, Tooltips und Zielseiten für die automatische Verlinkung.</p>
        </div>
        <div class="header-actions">
            <a href="/kb" class="btn btn-secondary btn-sm" target="_blank" rel="noopener noreferrer">🌍 Öffentliche KB</a>
        </div>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-<?php echo ($notice['type'] ?? '') === 'success' ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars((string) ($notice['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-grid kb-admin-dashboard-grid kb-admin-dashboard-grid--compact">
        <div class="stat-card"><div class="stat-icon">📚</div><div class="stat-number"><?php echo number_format(count($entries)); ?></div><div class="stat-label">Einträge geladen</div></div>
        <div class="stat-card"><div class="stat-icon">🌍</div><div class="stat-number"><?php echo number_format(count(array_filter($entries, static fn(array $item): bool => ((int) ($item['is_active'] ?? 0)) === 1))); ?></div><div class="stat-label">Aktiv im Frontend</div></div>
        <div class="stat-card"><div class="stat-icon">📦</div><div class="stat-number"><?php echo number_format($packageCount); ?></div><div class="stat-label">Standardpakete</div></div>
        <div class="stat-card"><div class="stat-icon">🧩</div><div class="stat-number"><?php echo number_format($starterEntryCount); ?></div><div class="stat-label">Starter-Einträge</div></div>
    </div>

    <div class="kb-entry-layout">
        <div class="admin-card kb-preset-card">
            <div class="kb-panel-header">
                <div>
                    <h3>📦 Standardpakete</h3>
                    <p>Starterpakete mit je mindestens 25 Einträgen aus 6 Bereichen – inklusive M365. Vorhandene Platzhalter werden dabei mit besseren Inhalten aktualisiert.</p>
                </div>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="create_all_standard_packages">
                    <button type="submit" class="btn btn-primary btn-sm">⚡ Alle Pakete anlegen / aktualisieren</button>
                </form>
            </div>

            <div class="kb-package-grid">
                <?php foreach ($standardPackages as $package): ?>
                    <article class="kb-package-card" style="--kb-package-accent: <?php echo htmlspecialchars((string) ($package['accent'] ?? '#0d9488'), ENT_QUOTES, 'UTF-8'); ?>;">
                        <div class="kb-package-card__head">
                            <div>
                                <p class="kb-package-card__eyebrow">Bereich</p>
                                <h4><?php echo htmlspecialchars((string) ($package['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h4>
                            </div>
                            <span class="kb-package-card__count"><?php echo (int) ($package['entry_count'] ?? 0); ?>+</span>
                        </div>

                        <p class="kb-package-card__text"><?php echo htmlspecialchars((string) ($package['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>

                        <?php if (!empty($package['sample_terms']) && is_array($package['sample_terms'])): ?>
                            <div class="kb-meta-pills kb-meta-pills--wrap">
                                <?php foreach ($package['sample_terms'] as $term): ?>
                                    <span class="kb-meta-pill kb-meta-pill--soft"><?php echo htmlspecialchars((string) $term, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" class="kb-package-card__action">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="create_standard_package">
                            <input type="hidden" name="package_key" value="<?php echo htmlspecialchars((string) ($package['key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="btn btn-secondary btn-sm">➕ Paket anlegen / aktualisieren</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="admin-card kb-form-card">
            <div class="kb-panel-header">
                <div>
                    <h3><?php echo $entry !== null ? '✏️ Eintrag bearbeiten' : '➕ Eintrag anlegen'; ?></h3>
                    <p>Ein Fokusbegriff bildet die Hauptverlinkung. Synonyme erweitern die Trefferbasis.</p>
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
                            <input type="text" name="category" value="<?php echo htmlspecialchars((string) ($entry['category'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="z. B. SEO, Hosting, Security">
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
                            <textarea name="content" rows="10" placeholder="HTML mit einfachen Tags ist erlaubt."><?php echo htmlspecialchars((string) ($entry['content'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
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
                        <a href="/admin/plugins/knowledgebase-dashboard/knowledgebase-entries" class="btn btn-secondary">Neu beginnen</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="admin-card">
            <div class="kb-panel-header">
                <div>
                    <h3>📚 Vorhandene Einträge</h3>
                    <p>Alle Begriffe mit Status, Kategorie und Schnellzugriff auf Bearbeitung oder öffentliche Seite.</p>
                </div>
            </div>

            <?php if (empty($entries)): ?>
                <div class="empty-state">
                    <p class="kb-empty-icon">🧩</p>
                    <p><strong>Noch keine Einträge vorhanden</strong></p>
                    <p class="kb-empty-text">Ein paar saubere Begriffe genügen schon, damit dein Content intern deutlich hilfreicher wird.</p>
                </div>
            <?php else: ?>
                <div class="kb-entry-card-list kb-entry-card-list--dense">
                    <?php foreach ($entries as $item): ?>
                        <article class="kb-entry-card">
                            <div class="kb-entry-card__header">
                                <div>
                                    <h4><?php echo htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                    <p><code><?php echo htmlspecialchars((string) $item['slug'], ENT_QUOTES, 'UTF-8'); ?></code></p>
                                </div>
                                <span class="status-badge <?php echo ((int) ($item['is_active'] ?? 0) === 1) ? 'active' : 'inactive'; ?>">
                                    <?php echo ((int) ($item['is_active'] ?? 0) === 1) ? 'Aktiv' : 'Inaktiv'; ?>
                                </span>
                            </div>

                            <div class="kb-meta-pills">
                                <span class="kb-meta-pill"><strong>Keyword</strong><?php echo htmlspecialchars((string) $item['keyword'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="kb-meta-pill"><strong>Kategorie</strong><?php echo htmlspecialchars((string) ($item['category'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="kb-meta-pill"><strong>Priorität</strong><?php echo (int) ($item['priority'] ?? 100); ?></span>
                            </div>

                            <?php if (!empty($item['excerpt'])): ?>
                                <p class="kb-entry-card__excerpt"><?php echo htmlspecialchars((string) $item['excerpt'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>

                            <div class="kb-action-row">
                                <a href="/admin/plugins/knowledgebase-dashboard/knowledgebase-entries?edit=<?php echo (int) $item['id']; ?>" class="btn btn-secondary btn-sm">Bearbeiten</a>
                                <a href="/kb/<?php echo rawurlencode((string) $item['slug']); ?>" class="btn btn-secondary btn-sm" target="_blank" rel="noopener noreferrer">Öffnen</a>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="delete_entry">
                                    <input type="hidden" name="entry_id" value="<?php echo (int) $item['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Löschen</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
