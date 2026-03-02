<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>💬 Forum – Dashboard</h2>
        <p>Übersicht über das Community-Forum</p>
    </div>
    <div class="header-actions">
        <a href="?page=forum-categories" class="btn btn-secondary btn-sm">🗂️ Kategorien</a>
        <a href="?page=forum-reports" class="btn btn-secondary btn-sm">
            🚩 Meldungen
            <?php if ($stats->open_reports > 0): ?>
                <span style="background:#fee2e2;color:#991b1b;padding:.1rem .4rem;border-radius:10px;font-size:.7rem;font-weight:700;margin-left:.2rem;"><?php echo (int)$stats->open_reports; ?></span>
            <?php endif; ?>
        </a>
    </div>
</div>

<!-- Stat-Cards -->
<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-icon">🗂️</div>
        <div class="stat-number"><?php echo (int)$stats->categories; ?></div>
        <div class="stat-label">Kategorien</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📁</div>
        <div class="stat-number"><?php echo (int)$stats->forums; ?></div>
        <div class="stat-label">Foren</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📝</div>
        <div class="stat-number"><?php echo number_format((int)$stats->threads); ?></div>
        <div class="stat-label">Threads</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">💬</div>
        <div class="stat-number"><?php echo number_format((int)$stats->posts); ?></div>
        <div class="stat-label">Beiträge</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-number"><?php echo (int)$stats->users; ?></div>
        <div class="stat-label">Aktive Benutzer</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🚩</div>
        <div class="stat-number" style="color:<?php echo $stats->open_reports > 0 ? '#ef4444' : '#3b82f6'; ?>"><?php echo (int)$stats->open_reports; ?></div>
        <div class="stat-label">Offene Meldungen</div>
    </div>
</div>

<!-- Schnellzugriff -->
<div class="admin-card" style="padding:1rem 1.25rem;margin-bottom:1.5rem;">
    <h3 style="margin:0 0 .75rem;font-size:.85rem;color:#475569;font-weight:700;text-transform:uppercase;letter-spacing:.05em;">⚡ Schnellzugriff</h3>
    <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
        <a href="?page=forum-categories" class="btn btn-secondary btn-sm">🗂️ Kategorien</a>
        <a href="?page=forum-forums" class="btn btn-secondary btn-sm">📁 Foren</a>
        <a href="?page=forum-ranks" class="btn btn-secondary btn-sm">🏅 Ränge</a>
        <a href="?page=forum-permissions" class="btn btn-secondary btn-sm">🔒 Berechtigungen</a>
        <a href="?page=forum-settings" class="btn btn-secondary btn-sm">⚙️ Einstellungen</a>
    </div>
</div>

<!-- Neueste Threads -->
<div class="admin-card">
    <h3>📝 Neueste Threads</h3>
    <?php if (empty($recentThreads)): ?>
        <div class="empty-state">
            <p style="font-size:2.5rem;margin:0;">📭</p>
            <p><strong>Noch keine Threads vorhanden</strong></p>
        </div>
    <?php else: ?>
        <div class="users-table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Titel</th>
                        <th>Autor</th>
                        <th>Typ</th>
                        <th>Status</th>
                        <th>Erstellt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentThreads as $t): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($t->title); ?></td>
                        <td><?php echo htmlspecialchars($t->username ?? 'Gelöscht'); ?></td>
                        <td>
                            <?php if ($t->type === 'sticky'): ?>
                                <span class="status-badge" style="background:#fef3c7;color:#92400e;">📌 Sticky</span>
                            <?php elseif ($t->type === 'announcement'): ?>
                                <span class="status-badge" style="background:#dbeafe;color:#1e40af;">📢 Ankündigung</span>
                            <?php else: ?>
                                <span class="status-badge" style="background:#f1f5f9;color:#64748b;">Normal</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $t->status === 'open' ? 'active' : 'inactive'; ?>">
                                <?php echo $t->status === 'open' ? 'Offen' : ($t->status === 'closed' ? 'Geschlossen' : 'Gelöscht'); ?>
                            </span>
                        </td>
                        <td style="color:#64748b;font-size:.85rem;"><?php echo date('d.m.Y H:i', strtotime($t->created_at)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Aktivste Benutzer -->
<?php if (!empty($topUsers)): ?>
<div class="admin-card">
    <h3>🏆 Aktivste Benutzer (30 Tage)</h3>
    <div class="users-table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Benutzer</th>
                    <th>Beiträge</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topUsers as $u): ?>
                <tr>
                    <td><?php echo htmlspecialchars($u->username); ?></td>
                    <td><strong><?php echo (int)$u->post_count; ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
