<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<div class="kb-admin-shell">
    <div class="admin-page-header">
        <div>
            <h2>⚙️ Knowledgebase-Einstellungen</h2>
            <p>Steuere Logik, Design und öffentliche Darstellung der Knowledgebase zentral an einer Stelle.</p>
        </div>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-<?php echo ($notice['type'] ?? '') === 'success' ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars((string) ($notice['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-grid kb-settings-summary-grid">
        <div class="kb-note-card">
            <span class="kb-note-card__eyebrow">Auto-Linking</span>
            <span class="kb-note-card__title"><?php echo ($settings['enable_autolink'] ?? '0') === '1' ? 'Aktiv' : 'Inaktiv'; ?></span>
            <span class="kb-note-card__text"><?php echo number_format((int) ($stats['active_entries'] ?? 0)); ?> aktive Begriffe stehen aktuell für Verlinkungen bereit.</span>
        </div>
        <div class="kb-note-card">
            <span class="kb-note-card__eyebrow">Design-Akzent</span>
            <span class="kb-note-card__title"><?php echo htmlspecialchars((string) ($settings['design_accent_color'] ?? '#0D9488'), ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="kb-note-card__text">Wird für KB-Links, Fokus-States und das Theme-Styling der öffentlichen Seiten genutzt.</span>
        </div>
        <div class="kb-note-card">
            <span class="kb-note-card__eyebrow">Content-Breite</span>
            <span class="kb-note-card__title"><?php echo (int) ($settings['content_max_width'] ?? 1200); ?>px</span>
            <span class="kb-note-card__text">Diese Breite landet direkt als CSS-Variable im Frontend und wird vom Theme ausgewertet.</span>
        </div>
    </div>

    <div class="kb-tabs">
        <?php foreach ($tabs as $key => $label): ?>
            <a href="?tab=<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" class="kb-tab<?php echo $tab === $key ? ' active' : ''; ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></a>
        <?php endforeach; ?>
    </div>

    <div class="admin-card kb-settings-card kb-settings-card--tabbed">
        <form method="post" class="admin-form kb-settings-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="save_settings">
            <input type="hidden" name="redirect_tab" value="<?php echo htmlspecialchars($tab, ENT_QUOTES, 'UTF-8'); ?>">

            <?php if ($tab === 'general'): ?>
                <h3>⚙️ Allgemeine Einstellungen</h3>
                <div class="alert alert-success">ℹ️ Diese Optionen steuern Logik, Navigation und Sichtbarkeit im öffentlichen Bereich.</div>

                <div class="kb-settings-grid kb-settings-grid--cards">
                    <div class="kb-settings-group">
                        <h3>Auto-Linking</h3>
                        <label><input type="checkbox" name="enable_autolink" value="1" <?php echo ($settings['enable_autolink'] ?? '0') === '1' ? 'checked' : ''; ?>> Auto-Linking aktivieren</label>
                        <label><input type="checkbox" name="enable_output_buffer" value="1" <?php echo ($settings['enable_output_buffer'] ?? '0') === '1' ? 'checked' : ''; ?>> Output-Buffer-Fallback aktivieren</label>
                        <label><input type="checkbox" name="nofollow_links" value="1" <?php echo ($settings['nofollow_links'] ?? '0') === '1' ? 'checked' : ''; ?>> Links mit <code>nofollow</code> markieren</label>
                        <label><input type="checkbox" name="open_links_new_tab" value="1" <?php echo ($settings['open_links_new_tab'] ?? '0') === '1' ? 'checked' : ''; ?>> Links in neuem Tab öffnen</label>
                        <label>
                            <span>Max. Links pro Seite</span>
                            <input type="number" name="max_links_per_page" min="1" max="25" value="<?php echo (int) ($settings['max_links_per_page'] ?? 6); ?>">
                        </label>
                    </div>

                    <div class="kb-settings-group">
                        <h3>Navigation & Tooltips</h3>
                        <label><input type="checkbox" name="enable_tooltips" value="1" <?php echo ($settings['enable_tooltips'] ?? '0') === '1' ? 'checked' : ''; ?>> Tooltip-Vorschau aktivieren</label>
                        <label><input type="checkbox" name="show_nav_link" value="1" <?php echo ($settings['show_nav_link'] ?? '0') === '1' ? 'checked' : ''; ?>> Link in Hauptnavigation ausgeben</label>
                        <label>
                            <span>Label für Navigation</span>
                            <input type="text" name="nav_label" maxlength="40" value="<?php echo htmlspecialchars((string) ($settings['nav_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                    </div>

                    <div class="kb-settings-group kb-settings-group--full">
                        <h3>Öffentliche Knowledgebase</h3>
                        <label>
                            <span>Archiv-Titel</span>
                            <input type="text" name="archive_title" maxlength="120" value="<?php echo htmlspecialchars((string) ($settings['archive_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </label>
                        <label>
                            <span>Archiv-Einleitung</span>
                            <textarea name="archive_intro" rows="4"><?php echo htmlspecialchars((string) ($settings['archive_intro'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </label>
                        <div class="kb-checkbox-row kb-form-grid__full">
                            <label><input type="checkbox" name="show_search" value="1" <?php echo ($settings['show_search'] ?? '0') === '1' ? 'checked' : ''; ?>> Suchformular im Archiv anzeigen</label>
                            <label><input type="checkbox" name="show_category_sidebar" value="1" <?php echo ($settings['show_category_sidebar'] ?? '0') === '1' ? 'checked' : ''; ?>> Kategorien-Sidebar anzeigen</label>
                            <label><input type="checkbox" name="show_keyword_badges" value="1" <?php echo ($settings['show_keyword_badges'] ?? '0') === '1' ? 'checked' : ''; ?>> Keyword-Hinweise anzeigen</label>
                            <label><input type="checkbox" name="show_related_entries" value="1" <?php echo ($settings['show_related_entries'] ?? '0') === '1' ? 'checked' : ''; ?>> Verwandte Einträge anzeigen</label>
                        </div>
                    </div>
                </div>
            <?php elseif ($tab === 'design'): ?>
                <h3>🎨 Design-Einstellungen</h3>
                <div class="alert alert-success">ℹ️ Diese Werte werden als CSS-Variablen im Frontend ausgegeben und von Tooltip sowie `cms-phinit` direkt verwendet.</div>

                <div class="kb-settings-grid kb-settings-grid--cards">
                    <div class="kb-settings-group kb-settings-group--full">
                        <h3>Layout</h3>
                        <div class="kb-settings-inline-grid">
                            <label>
                                <span>Content-Breite</span>
                                <input type="number" name="content_max_width" min="720" max="1600" value="<?php echo (int) ($settings['content_max_width'] ?? 1200); ?>">
                            </label>
                            <label>
                                <span>Sidebar-Breite</span>
                                <input type="number" name="sidebar_width" min="220" max="420" value="<?php echo (int) ($settings['sidebar_width'] ?? 300); ?>">
                            </label>
                            <label>
                                <span>Border-Radius</span>
                                <input type="number" name="design_border_radius" min="0" max="32" value="<?php echo (int) ($settings['design_border_radius'] ?? 14); ?>">
                            </label>
                        </div>
                    </div>

                    <div class="kb-settings-group">
                        <h3>Knowledgebase-Farben</h3>
                        <div class="kb-color-grid">
                            <label>
                                <span>Akzent</span>
                                <div class="kb-color-field"><input type="color" name="design_accent_color" value="<?php echo htmlspecialchars((string) ($settings['design_accent_color'] ?? '#0D9488'), ENT_QUOTES, 'UTF-8'); ?>"><input type="text" name="design_accent_color_text" value="<?php echo htmlspecialchars((string) ($settings['design_accent_color'] ?? '#0D9488'), ENT_QUOTES, 'UTF-8'); ?>"></div>
                            </label>
                            <label>
                                <span>Akzent Hover</span>
                                <div class="kb-color-field"><input type="color" name="design_accent_hover_color" value="<?php echo htmlspecialchars((string) ($settings['design_accent_hover_color'] ?? '#0F766E'), ENT_QUOTES, 'UTF-8'); ?>"><input type="text" name="design_accent_hover_color_text" value="<?php echo htmlspecialchars((string) ($settings['design_accent_hover_color'] ?? '#0F766E'), ENT_QUOTES, 'UTF-8'); ?>"></div>
                            </label>
                            <label>
                                <span>Fläche</span>
                                <div class="kb-color-field"><input type="color" name="design_surface_color" value="<?php echo htmlspecialchars((string) ($settings['design_surface_color'] ?? '#FFFFFF'), ENT_QUOTES, 'UTF-8'); ?>"><input type="text" name="design_surface_color_text" value="<?php echo htmlspecialchars((string) ($settings['design_surface_color'] ?? '#FFFFFF'), ENT_QUOTES, 'UTF-8'); ?>"></div>
                            </label>
                            <label>
                                <span>Rahmen</span>
                                <div class="kb-color-field"><input type="color" name="design_border_color" value="<?php echo htmlspecialchars((string) ($settings['design_border_color'] ?? '#DBE1EA'), ENT_QUOTES, 'UTF-8'); ?>"><input type="text" name="design_border_color_text" value="<?php echo htmlspecialchars((string) ($settings['design_border_color'] ?? '#DBE1EA'), ENT_QUOTES, 'UTF-8'); ?>"></div>
                            </label>
                        </div>
                    </div>

                    <div class="kb-settings-group">
                        <h3>Tooltip-Farben</h3>
                        <div class="kb-color-grid">
                            <label>
                                <span>Hintergrund</span>
                                <div class="kb-color-field"><input type="color" name="tooltip_background_color" value="<?php echo htmlspecialchars((string) ($settings['tooltip_background_color'] ?? '#111827'), ENT_QUOTES, 'UTF-8'); ?>"><input type="text" name="tooltip_background_color_text" value="<?php echo htmlspecialchars((string) ($settings['tooltip_background_color'] ?? '#111827'), ENT_QUOTES, 'UTF-8'); ?>"></div>
                            </label>
                            <label>
                                <span>Text</span>
                                <div class="kb-color-field"><input type="color" name="tooltip_text_color" value="<?php echo htmlspecialchars((string) ($settings['tooltip_text_color'] ?? '#F8FAFC'), ENT_QUOTES, 'UTF-8'); ?>"><input type="text" name="tooltip_text_color_text" value="<?php echo htmlspecialchars((string) ($settings['tooltip_text_color'] ?? '#F8FAFC'), ENT_QUOTES, 'UTF-8'); ?>"></div>
                            </label>
                        </div>
                    </div>

                    <div class="kb-settings-group kb-settings-group--full">
                        <h3>Live-Vorschau</h3>
                        <div class="kb-design-preview" style="--kb-preview-accent: <?php echo htmlspecialchars((string) ($designTokens['--kb-accent'] ?? '#0D9488'), ENT_QUOTES, 'UTF-8'); ?>; --kb-preview-accent-hover: <?php echo htmlspecialchars((string) ($designTokens['--kb-accent-hover'] ?? '#0F766E'), ENT_QUOTES, 'UTF-8'); ?>; --kb-preview-surface: <?php echo htmlspecialchars((string) ($designTokens['--kb-surface'] ?? '#FFFFFF'), ENT_QUOTES, 'UTF-8'); ?>; --kb-preview-border: <?php echo htmlspecialchars((string) ($designTokens['--kb-border'] ?? '#DBE1EA'), ENT_QUOTES, 'UTF-8'); ?>; --kb-preview-radius: <?php echo htmlspecialchars((string) ($designTokens['--kb-radius'] ?? '14px'), ENT_QUOTES, 'UTF-8'); ?>; --kb-preview-tooltip-bg: <?php echo htmlspecialchars((string) ($designTokens['--kb-tooltip-bg'] ?? '#111827'), ENT_QUOTES, 'UTF-8'); ?>; --kb-preview-tooltip-text: <?php echo htmlspecialchars((string) ($designTokens['--kb-tooltip-text'] ?? '#F8FAFC'), ENT_QUOTES, 'UTF-8'); ?>;">
                            <div class="kb-design-preview__card">
                                <p class="kb-design-preview__eyebrow">Knowledgebase</p>
                                <h4>Beispiel-Eintrag</h4>
                                <p>So wirken Flächen, Rahmen und Akzentfarbe auf der öffentlichen Seite.</p>
                                <a href="#">Artikel lesen</a>
                            </div>
                            <div class="kb-design-preview__tooltip">
                                <strong>Tooltip-Vorschau</strong>
                                <span>Farben werden direkt auf die Tooltip-Box der automatischen Begriffslinks angewendet.</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <h3>🖥️ System-Informationen</h3>
                <div class="kb-system-grid">
                    <div class="kb-system-card">
                        <h4>Plugin</h4>
                        <ul class="kb-system-list">
                            <li><strong>Version:</strong> <?php echo htmlspecialchars((string) $systemInfo['plugin_version'], ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Route:</strong> <code><?php echo htmlspecialchars((string) $systemInfo['public_route'], ENT_QUOTES, 'UTF-8'); ?></code></li>
                            <li><strong>Aktive Begriffe:</strong> <?php echo number_format((int) ($stats['active_entries'] ?? 0)); ?></li>
                        </ul>
                    </div>
                    <div class="kb-system-card">
                        <h4>Frontend</h4>
                        <ul class="kb-system-list">
                            <li><strong>Content-Breite:</strong> <?php echo htmlspecialchars((string) $systemInfo['content_max_width'], ENT_QUOTES, 'UTF-8'); ?>px</li>
                            <li><strong>Sidebar:</strong> <?php echo htmlspecialchars((string) $systemInfo['sidebar_width'], ENT_QUOTES, 'UTF-8'); ?>px</li>
                            <li><strong>Output-Buffer:</strong> <?php echo htmlspecialchars((string) $systemInfo['output_buffer'], ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Auto-Linking:</strong> <?php echo htmlspecialchars((string) $systemInfo['autolink_enabled'], ENT_QUOTES, 'UTF-8'); ?></li>
                        </ul>
                    </div>
                </div>
                <div class="alert alert-success">💡 Alle Werte aus den Tabs „Allgemein“ und „Design“ werden nach dem Speichern direkt neu geladen und im Frontend als Einstellungen oder CSS-Variablen verwendet.</div>
            <?php endif; ?>

            <?php if ($tab !== 'system'): ?>
                <div class="kb-form-actions kb-settings-group--full">
                    <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>
