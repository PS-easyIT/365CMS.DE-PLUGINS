<?php
declare(strict_types=1);

$renderBoardPreview = static function (array $board): string {
    $payload = (array) ($board['payload_data'] ?? []);
    $groups = [];

    if (isset($payload['columns']) && is_array($payload['columns'])) {
        $groups = $payload['columns'];
    } elseif (isset($payload['lanes']) && is_array($payload['lanes'])) {
        $groups = $payload['lanes'];
    } elseif (isset($payload['clusters']) && is_array($payload['clusters'])) {
        $groups = $payload['clusters'];
    } elseif (isset($payload['stages']) && is_array($payload['stages'])) {
        $groups = $payload['stages'];
    } elseif (isset($payload['milestones']) && is_array($payload['milestones'])) {
        $groups = $payload['milestones'];
    }

    ob_start();
    echo '<div class="cp-board-preview-grid">';
    foreach ($groups as $group) {
        $title = htmlspecialchars((string) ($group['title'] ?? 'Block'), ENT_QUOTES, 'UTF-8');
        echo '<article class="cp-board-preview-column">';
        echo '<h4>' . $title . '</h4>';
        echo '<ul>';
        foreach ((array) ($group['items'] ?? []) as $item) {
            echo '<li>' . htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        echo '</ul>';
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
?>
<div class="wrap cp-admin-shell">
    <div class="cp-header-card">
        <div>
            <span class="cp-kicker">CMS Projects</span>
            <h1><?php echo htmlspecialchars((string) ($sectionConfig['title'] ?? 'Projects'), ENT_QUOTES, 'UTF-8'); ?></h1>
            <p><?php echo htmlspecialchars((string) ($sectionConfig['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="cp-nav-links">
            <a class="button <?php echo ($section ?? 'overview') === 'overview' ? 'button-primary' : ''; ?>" href="<?php echo htmlspecialchars((string) ($pageLinks['overview'] ?? '?page=cms-projects'), ENT_QUOTES, 'UTF-8'); ?>">Übersicht</a>
            <a class="button <?php echo ($section ?? '') === 'projects' ? 'button-primary' : ''; ?>" href="<?php echo htmlspecialchars((string) ($pageLinks['projects'] ?? '?page=cms-projects-projects'), ENT_QUOTES, 'UTF-8'); ?>">Projekte</a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="cp-alert cp-alert-<?php echo htmlspecialchars((string) $messageType, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="cp-summary-grid">
        <article class="cp-summary-card"><strong><?php echo (int) ($summary['projects'] ?? 0); ?></strong><span>Projekte</span></article>
        <article class="cp-summary-card"><strong><?php echo (int) ($summary['boards'] ?? 0); ?></strong><span>Boards</span></article>
        <article class="cp-summary-card"><strong><?php echo (int) ($summary['widgets'] ?? 0); ?></strong><span>Widgets</span></article>
        <article class="cp-summary-card"><strong><?php echo (int) ($summary['public_projects'] ?? 0); ?></strong><span>Public-Projekte</span></article>
    </div>

    <div class="cp-admin-grid">
        <section class="cp-panel cp-panel-wide">
            <div class="cp-panel-head">
                <h2>Projekte</h2>
                <span class="cp-muted">Projekt-Dashboards mit Boards und Widgets</span>
            </div>
            <div class="cp-table-wrap">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Sichtbarkeit</th>
                            <th>Boards</th>
                            <th>Widgets</th>
                            <th>Member</th>
                            <th>Public</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($projects === []): ?>
                            <tr><td colspan="7">Noch keine Projekte angelegt.</td></tr>
                        <?php else: ?>
                            <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string) ($project['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="cp-muted"><code><?php echo htmlspecialchars((string) ($project['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></div>
                                        <div><a href="<?php echo htmlspecialchars((string) (($pageLinks['projects'] ?? '?page=cms-projects-projects') . '&project_id=' . (int) ($project['id'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?>">Bearbeiten</a></div>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) ($projectStatuses[$project['status'] ?? 'active'] ?? ($project['status'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($projectVisibilities[$project['visibility'] ?? 'member'] ?? ($project['visibility'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo (int) ($project['board_count'] ?? 0); ?></td>
                                    <td><?php echo (int) ($project['widget_count'] ?? 0); ?></td>
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
                <h2><?php echo !empty($projectFormValues['id']) ? 'Projekt bearbeiten' : 'Neues Projekt'; ?></h2>
            </div>
            <form method="post" class="cp-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="cms_projects_action" value="save_project">
                <input type="hidden" name="project_id" value="<?php echo (int) ($projectFormValues['id'] ?? 0); ?>">
                <label><span>Name</span><input type="text" name="name" required value="<?php echo htmlspecialchars((string) ($projectFormValues['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                <label><span>Slug</span><input type="text" name="slug" value="<?php echo htmlspecialchars((string) ($projectFormValues['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                <label><span>Owner / Lead</span><input type="text" name="owner_name" value="<?php echo htmlspecialchars((string) ($projectFormValues['owner_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
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
                <label><span>Akzentfarbe</span><input type="text" name="accent_color" value="<?php echo htmlspecialchars((string) ($projectFormValues['accent_color'] ?? '#2563eb'), ENT_QUOTES, 'UTF-8'); ?>"></label>
                <label><span>Member-Headline</span><input type="text" name="member_headline" value="<?php echo htmlspecialchars((string) ($projectFormValues['member_headline'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                <label><span>Public-Headline</span><input type="text" name="public_headline" value="<?php echo htmlspecialchars((string) ($projectFormValues['public_headline'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                <label><span>Summary</span><textarea name="summary" rows="3"><?php echo htmlspecialchars((string) ($projectFormValues['summary'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></label>
                <label><span>Beschreibung</span><textarea name="description" rows="6"><?php echo htmlspecialchars((string) ($projectFormValues['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></label>
                <div class="cp-form-actions"><button type="submit" class="button button-primary">Projekt speichern</button></div>
            </form>
        </section>
    </div>

    <?php if ($selectedProject !== null): ?>
        <div class="cp-admin-grid cp-admin-grid-bottom">
            <section class="cp-panel">
                <div class="cp-panel-head">
                    <h2>Board anlegen</h2>
                    <span class="cp-muted"><?php echo htmlspecialchars((string) ($selectedProject['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <form method="post" class="cp-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="cms_projects_action" value="save_board">
                    <input type="hidden" name="project_id" value="<?php echo (int) ($selectedProject['id'] ?? 0); ?>">
                    <label><span>Titel</span><input type="text" name="title" required></label>
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
                    <label><span>Beschreibung</span><textarea name="description" rows="3"></textarea></label>
                    <label><span>Payload (JSON)</span><textarea name="payload" rows="10">{
  "columns": [
    {
      "title": "Backlog",
      "items": ["Feature definieren", "Team briefen"]
    }
  ]
}</textarea></label>
                    <label class="cp-checkbox-row"><input type="checkbox" name="is_public" value="1" checked><span>Auch im Public-Dashboard zeigen</span></label>
                    <label class="cp-checkbox-row"><input type="checkbox" name="is_active" value="1" checked><span>Board aktiv</span></label>
                    <div class="cp-form-actions"><button type="submit" class="button button-primary">Board speichern</button></div>
                </form>
            </section>

            <section class="cp-panel">
                <div class="cp-panel-head">
                    <h2>Widget anlegen</h2>
                    <span class="cp-muted">Member/Public-Widgets</span>
                </div>
                <form method="post" class="cp-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="cms_projects_action" value="save_widget">
                    <input type="hidden" name="project_id" value="<?php echo (int) ($selectedProject['id'] ?? 0); ?>">
                    <label><span>Titel</span><input type="text" name="title" required></label>
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
                    <label><span>Textinhalt</span><textarea name="content" rows="4"></textarea></label>
                    <label><span>Position</span><input type="number" name="position" value="0" min="0"></label>
                    <label><span>Payload (JSON)</span><textarea name="payload" rows="10">{
  "items": [
    {
      "label": "Fortschritt",
      "value": "68%"
    }
  ]
}</textarea></label>
                    <label class="cp-checkbox-row"><input type="checkbox" name="is_active" value="1" checked><span>Widget aktiv</span></label>
                    <div class="cp-form-actions"><button type="submit" class="button button-primary">Widget speichern</button></div>
                </form>
            </section>
        </div>

        <div class="cp-admin-grid cp-admin-grid-bottom">
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
        </div>
    <?php endif; ?>
</div>
