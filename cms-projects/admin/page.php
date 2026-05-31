<?php
declare(strict_types=1);

$collectBoardGroups = static function (array $payload): array {
    foreach (['columns', 'lanes', 'clusters', 'stages', 'milestones'] as $key) {
        if (isset($payload[$key]) && is_array($payload[$key])) {
            return $payload[$key];
        }
    }

    return [];
};

$renderBoardPreview = static function (array $board): string {
    $payload = (array) ($board['payload_data'] ?? []);
    $groups = [];
    $boardId = (int) ($board['id'] ?? 0);
    foreach (['columns', 'lanes', 'clusters', 'stages', 'milestones'] as $key) {
        if (isset($payload[$key]) && is_array($payload[$key])) {
            $groups = $payload[$key];
            break;
        }
    }

    ob_start();
    echo '<div class="cp-board-preview-grid">';
    foreach ($groups as $group) {
        $title = htmlspecialchars((string) ($group['title'] ?? 'Block'), ENT_QUOTES, 'UTF-8');
        $columnKey = htmlspecialchars((string) ($group['key'] ?? ''), ENT_QUOTES, 'UTF-8');
        $wipLimit = max(0, (int) ($group['wip_limit'] ?? 0));
        $wipCount = max(0, (int) ($group['wip_count'] ?? count((array) ($group['tasks'] ?? []))));
        $wipReached = !empty($group['wip_limit_reached']);
        $wipOverLimit = !empty($group['wip_over_limit']);
        echo '<article class="cp-board-preview-column" data-drop-board-id="' . $boardId . '" data-drop-column-key="' . $columnKey . '">';
        echo '<h4>' . $title . '</h4>';
        if ($wipLimit > 0) {
            echo '<div class="cp-ticket-meta">';
            echo '<span class="cp-badge">WIP ' . $wipCount . '/' . $wipLimit . '</span>';
            if ($wipOverLimit) {
                echo '<span class="cp-badge">Limit ueberschritten</span>';
            } elseif ($wipReached) {
                echo '<span class="cp-badge">Limit erreicht</span>';
            }
            echo '</div>';
        }
        echo '<ul>';
        foreach ((array) ($group['items'] ?? []) as $item) {
            echo '<li>' . htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        echo '</ul>';
        $tasks = (array) ($group['tasks'] ?? []);
        echo '<div class="cp-ticket-stack" data-ticket-stack>';
        foreach ($tasks as $task) {
            echo '<article class="cp-ticket-card cp-ticket-card--draggable" draggable="true" data-task-id="' . (int) ($task['id'] ?? 0) . '">';
            echo '<div class="cp-ticket-card__head">';
            echo '<strong>' . htmlspecialchars((string) ($task['title'] ?? ''), ENT_QUOTES, 'UTF-8') . '</strong>';
            echo '<span class="cp-badge">' . htmlspecialchars((string) ($task['priority_label'] ?? ''), ENT_QUOTES, 'UTF-8') . '</span>';
            echo '</div>';
            if (!empty($task['description'])) {
                echo '<p>' . nl2br(htmlspecialchars((string) ($task['description'] ?? ''), ENT_QUOTES, 'UTF-8')) . '</p>';
            }
            echo '<div class="cp-ticket-meta">';
            if (!empty($task['assignee_name'])) {
                echo '<span>' . htmlspecialchars((string) ($task['assignee_name'] ?? ''), ENT_QUOTES, 'UTF-8') . '</span>';
            }
            if (!empty($task['due_date'])) {
                echo '<span>' . htmlspecialchars((string) ($task['due_date'] ?? ''), ENT_QUOTES, 'UTF-8') . '</span>';
            }
            echo '</div>';
            echo '</article>';
        }
        echo '</div>';
        echo '<div class="cp-ticket-dropzone-hint' . ($tasks !== [] ? ' cp-ticket-dropzone-hint--hidden' : '') . '">Ticket hier ablegen</div>';
        echo '</article>';
    }
    echo '</div>';
    return (string) ob_get_clean();
};

$renderWidgetPreview = static function (array $widget): string {
    $payload = (array) ($widget['payload_data'] ?? []);
    $widgetType = (string) ($widget['widget_type'] ?? 'text');
    ob_start();

    if ($widgetType === 'text') {
        echo '<p>' . nl2br(htmlspecialchars((string) ($widget['content'] ?? ''), ENT_QUOTES, 'UTF-8')) . '</p>';
    } elseif ($widgetType === 'checklist') {
        echo '<ul class="cp-checklist">';
        foreach ((array) ($payload['items'] ?? []) as $item) {
            $label = htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8');
            $done = !empty($item['done']) ? ' done' : '';
            echo '<li class="' . $done . '">' . $label . '</li>';
        }
        echo '</ul>';
    } elseif ($widgetType === 'links') {
        echo '<ul class="cp-link-list">';
        foreach ((array) ($payload['items'] ?? []) as $item) {
            $label = htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8');
            $url = htmlspecialchars((string) ($item['url'] ?? '#'), ENT_QUOTES, 'UTF-8');
            echo '<li><a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $label . '</a></li>';
        }
        echo '</ul>';
    } elseif ($widgetType === 'stats') {
        echo '<div class="cp-stats-grid">';
        foreach ((array) ($payload['items'] ?? []) as $item) {
            echo '<div class="cp-stat-card">';
            echo '<strong>' . htmlspecialchars((string) ($item['value'] ?? ''), ENT_QUOTES, 'UTF-8') . '</strong>';
            echo '<span>' . htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') . '</span>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<ul class="cp-timeline">';
        foreach ((array) ($payload['items'] ?? []) as $item) {
            $date = htmlspecialchars((string) ($item['date'] ?? ''), ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8');
            echo '<li><strong>' . $date . '</strong><span>' . $label . '</span></li>';
        }
        echo '</ul>';
    }

    return (string) ob_get_clean();
};

$boardColumnOptions = [];
foreach (($projectBoards ?? []) as $board) {
    $payload = (array) ($board['payload_data'] ?? []);
    $groups = $collectBoardGroups($payload);
    if ($groups === []) {
        continue;
    }

    $columns = [];
    foreach ($groups as $group) {
        $columnKey = (string) ($group['key'] ?? '');
        $columnTitle = (string) ($group['title'] ?? 'Block');
        if ($columnKey === '') {
            continue;
        }

        $columns[] = [
            'key' => $columnKey,
            'title' => $columnTitle,
        ];
    }

    if ($columns !== []) {
        $boardColumnOptions[] = [
            'id' => (int) ($board['id'] ?? 0),
            'title' => (string) ($board['title'] ?? 'Board'),
            'columns' => $columns,
        ];
    }
}

$taskFormValues = is_array($taskFormValues ?? null) ? $taskFormValues : [];
$activeTaskId = (int) ($taskFormValues['id'] ?? 0);
?>
<div class="cp-admin-shell">
    <div class="cp-header-card">
        <div>
            <span class="cp-kicker">CMS Projects</span>
            <h1><?php echo htmlspecialchars((string) ($sectionConfig['title'] ?? 'Projects'), ENT_QUOTES, 'UTF-8'); ?></h1>
            <p><?php echo htmlspecialchars((string) ($sectionConfig['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="cp-alert cp-alert-<?php echo htmlspecialchars((string) $messageType, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="cp-summary-grid">
        <article class="cp-summary-card cp-summary-card--accent"><strong><?php echo (int) ($summary['projects'] ?? 0); ?></strong><span>Projekte</span></article>
        <article class="cp-summary-card"><strong><?php echo (int) ($summary['boards'] ?? 0); ?></strong><span>Boards</span></article>
        <article class="cp-summary-card"><strong><?php echo (int) ($summary['widgets'] ?? 0); ?></strong><span>Widgets</span></article>
        <article class="cp-summary-card"><strong><?php echo (int) ($summary['tasks'] ?? 0); ?></strong><span>Tickets</span></article>
        <article class="cp-summary-card"><strong><?php echo (int) ($summary['public_projects'] ?? 0); ?></strong><span>Public-Projekte</span></article>
    </div>

    <div class="cp-admin-grid">
        <section class="cp-panel cp-panel-wide">
            <div class="cp-panel-head">
                <h2>Projekte</h2>
                <span class="cp-muted">Projekt-Dashboards mit Boards und Widgets</span>
            </div>
            <div class="cp-table-wrap">
                <table class="widefat striped cp-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Sichtbarkeit</th>
                            <th>Boards</th>
                            <th>Widgets</th>
                            <th>Tickets</th>
                            <th>Member</th>
                            <th>Public</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($projects === []): ?>
                            <tr><td colspan="8">Noch keine Projekte angelegt.</td></tr>
                        <?php else: ?>
                            <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string) ($project['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="cp-muted"><code><?php echo htmlspecialchars((string) ($project['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></div>
                                        <div class="cp-table-actions"><a href="<?php echo htmlspecialchars((string) (($pageLinks['projects'] ?? '?page=cms-projects-projects') . '&project_id=' . (int) ($project['id'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?>">Bearbeiten</a></div>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) ($projectStatuses[$project['status'] ?? 'active'] ?? ($project['status'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($projectVisibilities[$project['visibility'] ?? 'member'] ?? ($project['visibility'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo (int) ($project['board_count'] ?? 0); ?></td>
                                    <td><?php echo (int) ($project['widget_count'] ?? 0); ?></td>
                                    <td><?php echo (int) ($project['task_count'] ?? 0); ?></td>
                                    <td><a href="<?php echo htmlspecialchars((string) (($project['preview_links']['member'] ?? '#')), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Member</a></td>
                                    <td><a href="<?php echo htmlspecialchars((string) (($project['preview_links']['public'] ?? '#')), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Public</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="cp-panel">
            <div class="cp-panel-head">
                <div>
                    <h2><?php echo !empty($projectFormValues['id']) ? 'Projekt bearbeiten' : 'Neues Projekt'; ?></h2>
                    <span class="cp-muted">Stammdaten, Sichtbarkeit und Headlines für Member/Public definieren.</span>
                </div>
            </div>
            <form method="post" class="cp-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="cms_projects_action" value="save_project">
                <input type="hidden" name="project_id" value="<?php echo (int) ($projectFormValues['id'] ?? 0); ?>">
                <div class="cp-form-section">
                    <div class="cp-form-section__title">Projektkern</div>
                    <label><span>Name</span><input type="text" name="name" maxlength="190" required placeholder="z. B. Kundenportal 2026" value="<?php echo htmlspecialchars((string) ($projectFormValues['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label><span>Slug</span><input type="text" name="slug" maxlength="120" inputmode="url" placeholder="kundenportal-2026" value="<?php echo htmlspecialchars((string) ($projectFormValues['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><small class="cp-field-hint">Wenn leer, wird der Slug automatisch aus dem Projektnamen erzeugt.</small></label>
                    <label><span>Owner / Lead</span><input type="text" name="owner_name" maxlength="190" placeholder="Projektleitung oder Team" value="<?php echo htmlspecialchars((string) ($projectFormValues['owner_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                </div>
                <div class="cp-form-section">
                    <div class="cp-form-section__title">Status und Darstellung</div>
                    <div class="cp-form-grid-2">
                        <label>
                            <span>Status</span>
                            <select name="status">
                                <?php foreach ($projectStatuses as $statusKey => $statusLabel): ?>
                                    <option value="<?php echo htmlspecialchars((string) $statusKey, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($projectFormValues['status'] ?? 'active') === $statusKey) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $statusLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>
                            <span>Sichtbarkeit</span>
                            <select name="visibility">
                                <?php foreach ($projectVisibilities as $visibilityKey => $visibilityLabel): ?>
                                    <option value="<?php echo htmlspecialchars((string) $visibilityKey, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($projectFormValues['visibility'] ?? 'member') === $visibilityKey) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $visibilityLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <label><span>Akzentfarbe</span><input type="color" name="accent_color" value="<?php echo htmlspecialchars((string) ($projectFormValues['accent_color'] ?? '#2563eb'), ENT_QUOTES, 'UTF-8'); ?>"><small class="cp-field-hint">Wird für Hero, Badges und Kartenakzente verwendet.</small></label>
                </div>
                <div class="cp-form-section">
                    <div class="cp-form-section__title">Headlines und Inhalt</div>
                    <label><span>Member-Headline</span><input type="text" name="member_headline" maxlength="190" placeholder="Projekt-Dashboard für Mitglieder" value="<?php echo htmlspecialchars((string) ($projectFormValues['member_headline'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label><span>Public-Headline</span><input type="text" name="public_headline" maxlength="190" placeholder="Öffentliche Projektübersicht" value="<?php echo htmlspecialchars((string) ($projectFormValues['public_headline'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label><span>Summary</span><textarea name="summary" rows="3" maxlength="1200" placeholder="Kurze Executive Summary für Listen und Hero-Bereiche."><?php echo htmlspecialchars((string) ($projectFormValues['summary'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></label>
                    <label><span>Beschreibung</span><textarea name="description" rows="6" maxlength="20000" placeholder="Projektkontext, Ziele, Status und nächste Schritte."><?php echo htmlspecialchars((string) ($projectFormValues['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></label>
                </div>
                <?php if (!empty($projectFormValues['id'])): ?>
                    <div class="cp-form-section cp-form-section--muted">
                        <div class="cp-form-section__title">Vorschau</div>
                        <div class="cp-preview-links">
                            <a class="cp-button cp-button-secondary" href="<?php echo htmlspecialchars((string) (($selectedProject['preview_links']['member'] ?? '#')), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Member öffnen</a>
                            <a class="cp-button cp-button-secondary" href="<?php echo htmlspecialchars((string) (($selectedProject['preview_links']['public'] ?? '#')), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Public öffnen</a>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="cp-form-actions"><button type="submit" class="button button-primary">Projekt speichern</button></div>
            </form>
        </section>
    </div>

    <?php if ($selectedProject !== null): ?>
        <form method="post" hidden data-cp-task-move-form>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="cms_projects_action" value="move_task">
            <input type="hidden" name="project_id" value="<?php echo (int) ($selectedProject['id'] ?? 0); ?>">
            <input type="hidden" name="task_id" value="0">
            <input type="hidden" name="target_board_id" value="0">
            <input type="hidden" name="target_column_key" value="">
            <input type="hidden" name="ordered_task_ids" value="">
        </form>

        <div class="cp-admin-grid cp-admin-grid-bottom">
            <section class="cp-panel">
                <div class="cp-panel-head">
                    <div>
                        <h2>Board anlegen</h2>
                        <span class="cp-muted"><?php echo htmlspecialchars((string) ($selectedProject['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
                <form method="post" class="cp-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="cms_projects_action" value="save_board">
                    <input type="hidden" name="project_id" value="<?php echo (int) ($selectedProject['id'] ?? 0); ?>">
                    <label><span>Titel</span><input type="text" name="title" maxlength="190" required placeholder="z. B. Delivery Board"></label>
                    <div class="cp-form-grid-2">
                        <label>
                            <span>Board-Typ</span>
                            <select name="board_type">
                                <?php foreach ($boardTypes as $boardKey => $boardConfig): ?>
                                    <option value="<?php echo htmlspecialchars((string) $boardKey, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($boardConfig['label'] ?? $boardKey), ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label><span>Position</span><input type="number" name="position" value="0" min="0"></label>
                    </div>
                    <label><span>Beschreibung</span><textarea name="description" rows="3" maxlength="1200" placeholder="Zweck, Teamfokus oder Steuerungslogik des Boards."></textarea></label>
                    <label><span>Payload (JSON)</span><textarea name="payload" rows="10" class="cp-code-input" spellcheck="false" autocomplete="off">{
  "columns": [
    {
      "title": "Backlog",
      "wip_limit": 5,
      "items": ["Feature definieren", "Team briefen"]
    }
  ],
  "meta": {
    "note": "wip_limit ist optional und blendet nur Warnungen ein."
  }
}</textarea><small class="cp-field-hint">Nur gültiges JSON. Nicht erlaubte Inhalte werden serverseitig gefiltert.</small></label>
                    <label class="cp-checkbox-row"><input type="checkbox" name="is_public" value="1" checked><span>Auch im Public-Dashboard zeigen</span></label>
                    <label class="cp-checkbox-row"><input type="checkbox" name="is_active" value="1" checked><span>Board aktiv</span></label>
                    <div class="cp-form-actions"><button type="submit" class="button button-primary">Board speichern</button></div>
                </form>
            </section>

            <section class="cp-panel">
                <div class="cp-panel-head">
                    <div>
                        <h2>Widget anlegen</h2>
                        <span class="cp-muted">Member/Public-Widgets</span>
                    </div>
                </div>
                <form method="post" class="cp-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="cms_projects_action" value="save_widget">
                    <input type="hidden" name="project_id" value="<?php echo (int) ($selectedProject['id'] ?? 0); ?>">
                    <label><span>Titel</span><input type="text" name="title" maxlength="190" required placeholder="z. B. KPI Snapshot"></label>
                    <div class="cp-form-grid-2">
                        <label>
                            <span>Widget-Typ</span>
                            <select name="widget_type">
                                <?php foreach ($widgetTypes as $widgetKey => $widgetConfig): ?>
                                    <option value="<?php echo htmlspecialchars((string) $widgetKey, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($widgetConfig['label'] ?? $widgetKey), ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>
                            <span>Scope</span>
                            <select name="scope">
                                <?php foreach ($widgetScopes as $scopeKey => $scopeLabel): ?>
                                    <option value="<?php echo htmlspecialchars((string) $scopeKey, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $scopeLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <label><span>Textinhalt</span><textarea name="content" rows="4" maxlength="20000" placeholder="Freier Begleittext für Text-Widgets oder Kontext für strukturierte Widgets."></textarea></label>
                    <label><span>Position</span><input type="number" name="position" value="0" min="0"></label>
                    <label><span>Payload (JSON)</span><textarea name="payload" rows="10" class="cp-code-input" spellcheck="false" autocomplete="off">{
  "items": [
    {
      "label": "Fortschritt",
      "value": "68%"
    }
  ]
}</textarea><small class="cp-field-hint">Für `links` sind ausschließlich `http`/`https`-URLs erlaubt.</small></label>
                    <label class="cp-checkbox-row"><input type="checkbox" name="is_active" value="1" checked><span>Widget aktiv</span></label>
                    <div class="cp-form-actions"><button type="submit" class="button button-primary">Widget speichern</button></div>
                </form>
            </section>

            <section class="cp-panel">
                <div class="cp-panel-head">
                    <div>
                        <h2><?php echo $activeTaskId > 0 ? 'Ticket bearbeiten' : 'Ticket anlegen'; ?></h2>
                        <span class="cp-muted">Echte Tasks pro Board-Spalte</span>
                    </div>
                </div>
                <form method="post" class="cp-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="cms_projects_action" value="save_task">
                    <input type="hidden" name="project_id" value="<?php echo (int) ($selectedProject['id'] ?? 0); ?>">
                    <input type="hidden" name="task_id" value="<?php echo $activeTaskId; ?>">
                    <label><span>Titel</span><input type="text" name="title" maxlength="190" required placeholder="z. B. API-Fehler bei Login beheben" value="<?php echo htmlspecialchars((string) ($taskFormValues['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <div class="cp-form-grid-2">
                        <label>
                            <span>Board</span>
                            <select name="board_id" required>
                                <option value="">Board wählen</option>
                                <?php foreach ($boardColumnOptions as $boardOption): ?>
                                    <option value="<?php echo (int) ($boardOption['id'] ?? 0); ?>" <?php echo ((int) ($taskFormValues['board_id'] ?? 0) === (int) ($boardOption['id'] ?? 0)) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) ($boardOption['title'] ?? 'Board'), ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>
                            <span>Spalte</span>
                            <select name="column_key" required>
                                <option value="">Spalte wählen</option>
                                <?php foreach ($boardColumnOptions as $boardOption): ?>
                                    <optgroup label="<?php echo htmlspecialchars((string) ($boardOption['title'] ?? 'Board'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php foreach ((array) ($boardOption['columns'] ?? []) as $column): ?>
                                            <option value="<?php echo htmlspecialchars((string) ($column['key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" <?php echo ((string) ($taskFormValues['column_key'] ?? '') === (string) ($column['key'] ?? '')) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) ($column['title'] ?? 'Block'), ENT_QUOTES, 'UTF-8'); ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <div class="cp-form-grid-2">
                        <label>
                            <span>Priorität</span>
                            <select name="priority">
                                <?php foreach ($taskPriorities as $priorityKey => $priorityLabel): ?>
                                    <option value="<?php echo htmlspecialchars((string) $priorityKey, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ((string) ($taskFormValues['priority'] ?? 'medium') === (string) $priorityKey) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $priorityLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label><span>Sortierung</span><input type="number" name="sort_order" value="<?php echo (int) ($taskFormValues['sort_order'] ?? 0); ?>" min="0"></label>
                    </div>
                    <div class="cp-form-grid-2">
                        <label><span>Zuständig</span><input type="text" name="assignee_name" maxlength="190" placeholder="z. B. Max Mustermann" value="<?php echo htmlspecialchars((string) ($taskFormValues['assignee_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                        <label><span>Fällig am</span><input type="date" name="due_date" value="<?php echo htmlspecialchars((string) ($taskFormValues['due_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                    </div>
                    <label><span>Beschreibung</span><textarea name="description" rows="5" maxlength="20000" placeholder="Was muss erledigt werden, welche Akzeptanzkriterien gelten und gibt es Blocker?"><?php echo htmlspecialchars((string) ($taskFormValues['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></label>
                    <label class="cp-checkbox-row"><input type="checkbox" name="is_public" value="1" <?php echo !empty($taskFormValues['is_public']) ? 'checked' : ''; ?>><span>Auch im Public-Board zeigen</span></label>
                    <label class="cp-checkbox-row"><input type="checkbox" name="is_active" value="1" <?php echo !array_key_exists('is_active', $taskFormValues) || !empty($taskFormValues['is_active']) ? 'checked' : ''; ?>><span>Ticket aktiv</span></label>
                    <div class="cp-form-actions">
                        <button type="submit" class="button button-primary"><?php echo $activeTaskId > 0 ? 'Ticket aktualisieren' : 'Ticket speichern'; ?></button>
                        <?php if ($activeTaskId > 0): ?>
                            <a class="button" href="<?php echo htmlspecialchars((string) (($pageLinks['projects'] ?? '?page=cms-projects-projects') . '&project_id=' . (int) ($selectedProject['id'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?>">Neu anlegen</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>
        </div>

        <div class="cp-admin-grid cp-admin-grid-bottom">
            <section class="cp-panel cp-panel-wide">
                <div class="cp-panel-head"><h2>Audit Trail</h2></div>
                <?php if (($auditLogs ?? []) === []): ?>
                    <div class="cp-empty-state">Noch keine Audit-Einträge vorhanden.</div>
                <?php else: ?>
                    <div class="cp-table-wrap">
                        <table class="widefat striped cp-table">
                            <thead>
                                <tr>
                                    <th>Zeitpunkt</th>
                                    <th>User</th>
                                    <th>Aktion</th>
                                    <th>Entität</th>
                                    <th>Projekt</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ((array) $auditLogs as $auditLog): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars((string) ($auditLog['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string) (($auditLog['actor_name'] ?? '') !== '' ? ($auditLog['actor_name'] ?? '') : 'system'), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><code><?php echo htmlspecialchars((string) ($auditLog['action'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td>
                                        <td>
                                            <?php echo htmlspecialchars((string) ($auditLog['entity_type'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                            <?php if ((int) ($auditLog['entity_id'] ?? 0) > 0): ?>
                                                #<?php echo (int) ($auditLog['entity_id'] ?? 0); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo (int) ($auditLog['project_id'] ?? 0); ?></td>
                                        <td>
                                            <span class="cp-badge"><?php echo htmlspecialchars((string) ($auditLog['result'] ?? 'success'), ENT_QUOTES, 'UTF-8'); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="cp-panel cp-panel-wide">
                <div class="cp-panel-head"><h2>Projektboards</h2></div>
                <?php if ($projectBoards === []): ?>
                    <div class="cp-empty-state">Noch keine Boards für dieses Projekt.</div>
                <?php else: ?>
                    <div class="cp-stack-list">
                        <?php foreach ($projectBoards as $board): ?>
                            <article class="cp-item-card">
                                <div class="cp-item-card__head">
                                    <div>
                                        <strong><?php echo htmlspecialchars((string) ($board['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <span class="cp-badge"><?php echo htmlspecialchars((string) ($board['board_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="cp-badge"><?php echo !empty($board['is_public']) ? 'Public' : 'Member'; ?></span>
                                        <span class="cp-badge"><?php echo (int) ($board['task_count'] ?? 0); ?> Tickets</span>
                                    </div>
                                </div>
                                <?php if (!empty($board['description'])): ?><p><?php echo htmlspecialchars((string) ($board['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
                                <?php echo $renderBoardPreview($board); ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="cp-panel cp-panel-wide">
                <div class="cp-panel-head"><h2>Projektwidgets</h2></div>
                <?php if ($projectWidgets === []): ?>
                    <div class="cp-empty-state">Noch keine Widgets für dieses Projekt.</div>
                <?php else: ?>
                    <div class="cp-widget-grid">
                        <?php foreach ($projectWidgets as $widget): ?>
                            <article class="cp-widget-card">
                                <div class="cp-item-card__head">
                                    <strong><?php echo htmlspecialchars((string) ($widget['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span class="cp-badge"><?php echo htmlspecialchars((string) ($widget['widget_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="cp-badge"><?php echo htmlspecialchars((string) ($widgetScopes[$widget['scope'] ?? 'both'] ?? ($widget['scope'] ?? 'both')), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <?php echo $renderWidgetPreview($widget); ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="cp-panel cp-panel-wide">
                <div class="cp-panel-head"><h2>Projekt-Tickets</h2></div>
                <?php if (($projectTasks ?? []) === []): ?>
                    <div class="cp-empty-state">Noch keine Tickets für dieses Projekt.</div>
                <?php else: ?>
                    <div class="cp-ticket-stack cp-ticket-stack--list">
                        <?php foreach ($projectTasks as $task): ?>
                            <article class="cp-ticket-card cp-ticket-card--admin-list">
                                <div class="cp-ticket-card__head">
                                    <div>
                                        <strong><?php echo htmlspecialchars((string) ($task['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="cp-ticket-meta">
                                            <span><?php echo htmlspecialchars((string) ($task['priority_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php if (!empty($task['assignee_name'])): ?><span><?php echo htmlspecialchars((string) ($task['assignee_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                                            <?php if (!empty($task['due_date'])): ?><span><?php echo htmlspecialchars((string) ($task['due_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                                            <span><?php echo !empty($task['is_public']) ? 'Public' : 'Intern'; ?></span>
                                        </div>
                                    </div>
                                    <span class="cp-badge"><?php echo htmlspecialchars((string) ($task['column_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <?php if (!empty($task['description'])): ?>
                                    <p><?php echo nl2br(htmlspecialchars((string) ($task['description'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></p>
                                <?php endif; ?>
                                <div class="cp-inline-actions">
                                    <a class="cp-button cp-button-secondary" href="<?php echo htmlspecialchars((string) (($pageLinks['projects'] ?? '?page=cms-projects-projects') . '&project_id=' . (int) ($selectedProject['id'] ?? 0) . '&task_id=' . (int) ($task['id'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?>">Bearbeiten</a>
                                    <form method="post" class="cp-inline-form" onsubmit="return confirm('Ticket wirklich löschen?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="cms_projects_action" value="delete_task">
                                        <input type="hidden" name="project_id" value="<?php echo (int) ($selectedProject['id'] ?? 0); ?>">
                                        <input type="hidden" name="task_id" value="<?php echo (int) ($task['id'] ?? 0); ?>">
                                        <button type="submit" class="button">Löschen</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <script>
            (function () {
                var moveForm = document.querySelector('[data-cp-task-move-form]');
                if (!moveForm) {
                    return;
                }

                var taskInput = moveForm.querySelector('input[name="task_id"]');
                var boardInput = moveForm.querySelector('input[name="target_board_id"]');
                var columnInput = moveForm.querySelector('input[name="target_column_key"]');
                var orderedInput = moveForm.querySelector('input[name="ordered_task_ids"]');
                var draggedTaskId = '';
                var draggedBoardId = '';
                var draggedColumnKey = '';
                var draggedCard = null;
                var sourceStack = null;
                var sourceOrder = '';

                var getStackOrder = function (stack) {
                    return Array.prototype.map.call(stack.querySelectorAll('.cp-ticket-card--draggable[data-task-id]'), function (card) {
                        return card.getAttribute('data-task-id') || '';
                    }).filter(function (taskId) {
                        return taskId !== '';
                    });
                };

                var syncDropHints = function () {
                    document.querySelectorAll('.cp-board-preview-column').forEach(function (column) {
                        var stack = column.querySelector('[data-ticket-stack]');
                        var hint = column.querySelector('.cp-ticket-dropzone-hint');
                        if (!stack || !hint) {
                            return;
                        }

                        hint.classList.toggle('cp-ticket-dropzone-hint--hidden', stack.querySelector('.cp-ticket-card--draggable[data-task-id]') !== null);
                    });
                };

                var getDragAfterElement = function (stack, clientY) {
                    var cards = Array.prototype.slice.call(stack.querySelectorAll('.cp-ticket-card--draggable[data-task-id]:not(.cp-ticket-card--dragging)'));

                    return cards.reduce(function (closest, card) {
                        var box = card.getBoundingClientRect();
                        var offset = clientY - box.top - (box.height / 2);

                        if (offset < 0 && offset > closest.offset) {
                            return { offset: offset, element: card };
                        }

                        return closest;
                    }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
                };

                var clearTargets = function () {
                    document.querySelectorAll('.cp-board-preview-column--drop-target').forEach(function (element) {
                        element.classList.remove('cp-board-preview-column--drop-target');
                    });
                };

                document.querySelectorAll('.cp-ticket-card--draggable[data-task-id]').forEach(function (ticketCard) {
                    ticketCard.addEventListener('dragstart', function (event) {
                        var currentColumn = ticketCard.closest('.cp-board-preview-column[data-drop-board-id][data-drop-column-key]');
                        var currentStack = ticketCard.closest('[data-ticket-stack]');
                        draggedTaskId = ticketCard.getAttribute('data-task-id') || '';
                        draggedBoardId = currentColumn ? (currentColumn.getAttribute('data-drop-board-id') || '') : '';
                        draggedColumnKey = currentColumn ? (currentColumn.getAttribute('data-drop-column-key') || '') : '';
                        draggedCard = ticketCard;
                        sourceStack = currentStack;
                        sourceOrder = currentStack ? getStackOrder(currentStack).join(',') : '';
                        ticketCard.classList.add('cp-ticket-card--dragging');

                        if (event.dataTransfer) {
                            event.dataTransfer.effectAllowed = 'move';
                            event.dataTransfer.setData('text/plain', draggedTaskId);
                        }
                    });

                    ticketCard.addEventListener('dragend', function () {
                        draggedTaskId = '';
                        draggedBoardId = '';
                        draggedColumnKey = '';
                        draggedCard = null;
                        sourceStack = null;
                        sourceOrder = '';
                        ticketCard.classList.remove('cp-ticket-card--dragging');
                        clearTargets();
                        syncDropHints();
                    });
                });

                document.querySelectorAll('.cp-board-preview-column[data-drop-board-id][data-drop-column-key]').forEach(function (column) {
                    var stack = column.querySelector('[data-ticket-stack]');

                    if (!stack) {
                        return;
                    }

                    stack.addEventListener('dragover', function (event) {
                        if (!draggedTaskId) {
                            return;
                        }

                        event.preventDefault();
                        if (draggedCard) {
                            var afterElement = getDragAfterElement(stack, event.clientY);
                            if (afterElement === null) {
                                stack.appendChild(draggedCard);
                            } else if (afterElement !== draggedCard) {
                                stack.insertBefore(draggedCard, afterElement);
                            }
                        }

                        clearTargets();
                        column.classList.add('cp-board-preview-column--drop-target');
                        syncDropHints();
                    });

                    stack.addEventListener('drop', function (event) {
                        var targetBoardId;
                        var targetColumnKey;
                        var taskId;
                        var orderedTaskIds;

                        if (!draggedTaskId) {
                            return;
                        }

                        event.preventDefault();
                        targetBoardId = column.getAttribute('data-drop-board-id') || '';
                        targetColumnKey = column.getAttribute('data-drop-column-key') || '';
                        taskId = draggedTaskId;
                        orderedTaskIds = getStackOrder(stack);

                        clearTargets();
                        syncDropHints();

                        if (!taskId || !targetBoardId || !targetColumnKey) {
                            return;
                        }

                        if (orderedTaskIds.length === 0) {
                            return;
                        }

                        if (draggedBoardId === targetBoardId && draggedColumnKey === targetColumnKey && orderedTaskIds.join(',') === sourceOrder) {
                            return;
                        }

                        taskInput.value = taskId;
                        boardInput.value = targetBoardId;
                        columnInput.value = targetColumnKey;
                        orderedInput.value = orderedTaskIds.join(',');
                        moveForm.submit();
                    });

                    column.addEventListener('dragover', function (event) {
                        if (!draggedTaskId) {
                            return;
                        }

                        event.preventDefault();
                        clearTargets();
                        column.classList.add('cp-board-preview-column--drop-target');
                    });
                });

                syncDropHints();
            }());
        </script>
    <?php endif; ?>
</div>
