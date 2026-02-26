<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * View: Unternehmens-Übersicht – Firmeninfo + Standard-Benefits
 *
 * @var array<object>         $companies           Alle aktiven Firmen (CMS Companies – alle Felder inkl. user_id)
 * @var array<string,array>   $allBenefits         Gruppierter Benefit-Katalog
 * @var array<int,array<int>> $assignmentsMap      company_id → [benefit_ids]
 * @var array<int,array>      $jobCountsMap        company_id → ['published'=>n,'total'=>n]
 * @var int                   $selectedCompanyId   Aktuell gewählte Firma (GET ?company=)
 * @var object|null           $linkedUser          Verknüpfter CMS-User der ausgewählten Firma
 * @var string                $notice
 * @var string                $error
 */

$nonceInfo     = CMS_JPG_Admin_Pages::nonce('jpg_company_info_save');
$nonceBenefits = CMS_JPG_Admin_Pages::nonce('jpg_company_benefits_save');
$linkedUser    = $linkedUser ?? null;

// Aktiver Tab (info | benefits | departments | jobs-link)
$activeTab = in_array($_GET['tab'] ?? '', ['info', 'benefits', 'departments', 'jobs-link'], true)
    ? ($_GET['tab']) : 'info';

// Departments-Variablen defaults
$departments       = $departments       ?? [];
$jobsPageUrl       = $jobsPageUrl       ?? '';
$allReqItems       = $allReqItems       ?? [];
$selectedDeptId    = $selectedDeptId    ?? 0;
$deptBenefitIds    = $deptBenefitIds    ?? [];
$deptReqIds        = $deptReqIds        ?? [];
$allDeptBenefitIds = $allDeptBenefitIds ?? [];
$allDeptReqIds     = $allDeptReqIds     ?? [];
$companySettings   = $companySettings   ?? null;
$nonceInfoKey      = CMS_JPG_Admin_Pages::nonce('jpg_company_info_save');

// Ausgewählte Firma ermitteln
$selectedCompany    = null;
$assignedBenefitIds = [];
$jobCountsMap       = $jobCountsMap ?? [];
if ($selectedCompanyId > 0) {
    foreach ($companies as $c) {
        if ((int) $c->id === $selectedCompanyId) {
            $selectedCompany = $c;
            break;
        }
    }
    $assignedBenefitIds = $assignmentsMap[$selectedCompanyId] ?? [];
}
?>

<div class="admin-page-header">
    <div>
        <h2>🏢 Unternehmens-Übersicht</h2>
        <p>Firmenprofile einsehen und bearbeiten sowie Standard-Benefits pro Unternehmen hinterlegen.</p>
    </div>
</div>

<?php if (!empty($notice)): ?>
<div class="alert alert-success">✅ <?php echo htmlspecialchars($notice); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if (empty($companies)): ?>
<div class="admin-card">
    <div class="empty-state">
        <p style="font-size:2rem;">🏢</p>
        <p><strong>Keine Unternehmen gefunden</strong></p>
        <p class="text-muted" style="color:#64748b;font-size:.875rem;">
            Das Plugin <code>cms-companies</code> ist nicht aktiv oder enthält noch keine Unternehmen.
        </p>
    </div>
</div>
<?php else: ?>

<div style="display:grid;grid-template-columns:280px 1fr;gap:1.5rem;align-items:start;">

    <!-- ── Unternehmen-Liste ─────────────────────────── -->
    <div class="admin-card" style="padding:1rem;">
        <h3 style="margin:0 0 .75rem;font-size:1rem;">🏢 Unternehmen
            <span style="font-weight:400;color:#94a3b8;font-size:.8rem;">(<?php echo count($companies); ?>)</span>
        </h3>
        <nav>
            <?php foreach ($companies as $comp): ?>
            <?php
                $isSelected    = (int) $comp->id === $selectedCompanyId;
                $assignedCount = count($assignmentsMap[(int) $comp->id] ?? []);
                $jobCounts     = $jobCountsMap[(int) $comp->id] ?? ['published' => 0, 'total' => 0];
                $hasLogo       = !empty($comp->logo_url);
                $hasLinkedUser = !empty($comp->user_id);
            ?>
            <a href="?company=<?php echo (int) $comp->id; ?>&tab=<?php echo htmlspecialchars($activeTab); ?>"
               style="display:flex;align-items:center;gap:.5rem;padding:.45rem .6rem;border-radius:6px;text-decoration:none;margin-bottom:.2rem;
                      <?php echo $isSelected
                          ? 'background:var(--admin-primary);color:#fff;'
                          : 'color:#1e293b;'; ?>">
                <?php if ($hasLogo): ?>
                <img src="<?php echo htmlspecialchars($comp->logo_url); ?>"
                     alt="" style="width:24px;height:24px;border-radius:4px;object-fit:contain;background:#f1f5f9;flex-shrink:0;">
                <?php else: ?>
                <span style="width:24px;height:24px;border-radius:4px;background:<?php echo $isSelected ? 'rgba(255,255,255,.2)' : '#e2e8f0'; ?>;display:flex;align-items:center;justify-content:center;font-size:.75rem;flex-shrink:0;">🏢</span>
                <?php endif; ?>
                <span style="font-size:.875rem;font-weight:<?php echo $isSelected ? '600' : '400'; ?>;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <?php echo htmlspecialchars($comp->name); ?>
                </span>
                <span style="display:flex;flex-direction:column;gap:.15rem;align-items:flex-end;flex-shrink:0;">
                    <?php if ($jobCounts['published'] > 0): ?>
                    <span title="Aktive Stellen" style="background:<?php echo $isSelected ? 'rgba(255,255,255,.25)' : '#d1fae5'; ?>;
                                 color:<?php echo $isSelected ? '#fff' : '#065f46'; ?>;
                                 padding:.1rem .35rem;border-radius:8px;font-size:.7rem;font-weight:700;line-height:1.2;">
                        📄<?php echo $jobCounts['published']; ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($assignedCount > 0): ?>
                    <span title="Standard-Benefits" style="background:<?php echo $isSelected ? 'rgba(255,255,255,.25)' : '#eff6ff'; ?>;
                                 color:<?php echo $isSelected ? '#fff' : '#3b82f6'; ?>;
                                 padding:.1rem .35rem;border-radius:8px;font-size:.7rem;font-weight:700;line-height:1.2;">
                        🎁<?php echo $assignedCount; ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($hasLinkedUser): ?>
                    <span title="Verknüpftes Mitglied (user_id: <?php echo (int)$comp->user_id; ?>)"
                          style="background:<?php echo $isSelected ? 'rgba(255,255,255,.25)' : '#fef3c7'; ?>;
                                 color:<?php echo $isSelected ? '#fff' : '#92400e'; ?>;
                                 padding:.1rem .35rem;border-radius:8px;font-size:.7rem;font-weight:700;line-height:1.2;">
                        👤
                    </span>
                    <?php endif; ?>
                </span>
            </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <!-- ── Rechte Spalte: Tabs ───────────────────────── -->
    <div>
        <?php if (!$selectedCompany): ?>
        <div class="admin-card">
            <div class="empty-state">
                <p style="font-size:2rem;">👈</p>
                <p><strong>Unternehmen auswählen</strong></p>
                <p style="color:#64748b;font-size:.875rem;">Wähle ein Unternehmen aus der Liste links, um Details anzuzeigen.</p>
            </div>
        </div>
        <?php else: ?>

        <!-- Tab-Navigation -->
        <div style="display:flex;gap:.25rem;margin-bottom:1.25rem;border-bottom:2px solid #e2e8f0;">
            <?php
            $tabs = [
                'info'        => ['icon' => '📋', 'label' => 'Firmeninformationen'],
                'benefits'    => ['icon' => '🎁', 'label' => 'Standard-Benefits'],
                'departments' => ['icon' => '🏢', 'label' => 'Abteilungen'],
                'jobs-link'   => ['icon' => '🔗', 'label' => 'Jobs-Seite'],
            ];
            foreach ($tabs as $tabKey => $tabDef):
                $isActive = $activeTab === $tabKey;
                $badge = '';
                if ($tabKey === 'departments' && count($departments) > 0) {
                    $badge = '<span style="background:' . ($isActive ? '#eff6ff' : '#f1f5f9') . ';color:' . ($isActive ? '#3b82f6' : '#64748b') . ';padding:.1rem .45rem;border-radius:10px;font-size:.72rem;font-weight:700;">' . count($departments) . '</span>';
                }
            ?>
            <a href="?company=<?php echo $selectedCompanyId; ?>&tab=<?php echo $tabKey; ?>"
               style="display:inline-flex;align-items:center;gap:.35rem;padding:.6rem 1.1rem;border-radius:6px 6px 0 0;text-decoration:none;font-size:.9rem;font-weight:600;border:2px solid transparent;border-bottom:none;margin-bottom:-2px;
                      <?php echo $isActive
                          ? 'background:#fff;border-color:#e2e8f0;color:#1e293b;'
                          : 'color:#64748b;'; ?>">
                <?php echo $tabDef['icon']; ?> <?php echo $tabDef['label']; ?>
                <?php echo $badge; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- ══ Tab: Firmeninformationen ══ -->
        <?php if ($activeTab === 'info'): ?>
        <form method="post" class="admin-form">
            <input type="hidden" name="post_action"  value="save_company_info">
            <input type="hidden" name="_jpg_nonce"   value="<?php echo esc_attr($nonceInfo); ?>">
            <input type="hidden" name="company_id"   value="<?php echo $selectedCompanyId; ?>">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">

                <!-- Linke Spalte: Basisdaten -->
                <div>
                    <div class="admin-card">
                        <h3>🏢 Basisdaten</h3>

                        <?php if (!empty($selectedCompany->logo_url)): ?>
                        <div style="margin-bottom:1rem;text-align:center;">
                            <img src="<?php echo htmlspecialchars($selectedCompany->logo_url); ?>"
                                 alt="Logo" style="max-height:80px;max-width:200px;object-fit:contain;border-radius:6px;border:1px solid #e2e8f0;padding:.5rem;">
                        </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label class="form-label">Logo-URL</label>
                            <input type="url" name="company_logo_url" class="form-control"
                                   value="<?php echo htmlspecialchars($selectedCompany->logo_url ?? ''); ?>"
                                   placeholder="https://…/logo.png">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Unternehmensname <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="company_name" class="form-control" required
                                   value="<?php echo htmlspecialchars($selectedCompany->name ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Website</label>
                            <input type="url" name="company_website" class="form-control"
                                   value="<?php echo htmlspecialchars($selectedCompany->website ?? ''); ?>"
                                   placeholder="https://…">
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div class="form-group">
                                <label class="form-label">E-Mail</label>
                                <input type="email" name="company_email" class="form-control"
                                       value="<?php echo htmlspecialchars($selectedCompany->email ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Telefon</label>
                                <input type="text" name="company_phone" class="form-control"
                                       value="<?php echo htmlspecialchars($selectedCompany->phone ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="admin-card">
                        <h3>🏭 Branche & Größe</h3>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div class="form-group">
                                <label class="form-label">Branche</label>
                                <input type="text" name="company_industry" class="form-control"
                                       value="<?php echo htmlspecialchars($selectedCompany->industry ?? ''); ?>"
                                       placeholder="z.B. IT, Gesundheit">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Unternehmensgröße</label>
                                <select name="company_size" class="form-control">
                                    <?php
                                    $sizes = ['', '1-10', '11-50', '51-200', '201-500', '501-1000', '1000+'];
                                    foreach ($sizes as $sz):
                                    ?>
                                    <option value="<?php echo $sz; ?>" <?php echo ($selectedCompany->company_size ?? '') === $sz ? 'selected' : ''; ?>>
                                        <?php echo $sz ?: '– bitte wählen –'; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Gründungsjahr</label>
                                <input type="number" name="company_founded_year" class="form-control"
                                       min="1800" max="<?php echo date('Y'); ?>"
                                       value="<?php echo (int)($selectedCompany->founded_year ?? 0) ?: ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Mitarbeiteranzahl</label>
                                <input type="number" name="company_employee_count" class="form-control"
                                       min="0"
                                       value="<?php echo (int)($selectedCompany->employee_count ?? 0) ?: ''; ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rechte Spalte: Beschreibung + Standort -->
                <div>
                    <div class="admin-card">
                        <h3>📝 Beschreibung</h3>
                        <div class="form-group">
                            <textarea name="company_description" class="form-control"
                                      rows="8" style="resize:vertical;"><?php echo htmlspecialchars($selectedCompany->description ?? ''); ?></textarea>
                            <small class="form-text">Kurze Unternehmensvorstellung (wird auf der Profilseite angezeigt).</small>
                        </div>
                    </div>

                    <div class="admin-card">
                        <h3>📍 Standort</h3>
                        <div class="form-group">
                            <label class="form-label">Stadt</label>
                            <input type="text" name="company_city" class="form-control"
                                   value="<?php echo htmlspecialchars($selectedCompany->location_city ?? ''); ?>">
                        </div>
                        <div style="display:grid;grid-template-columns:120px 1fr;gap:1rem;">
                            <div class="form-group">
                                <label class="form-label">PLZ</label>
                                <input type="text" name="company_zip" class="form-control"
                                       value="<?php echo htmlspecialchars($selectedCompany->location_zip ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Land</label>
                                <input type="text" name="company_country" class="form-control"
                                       value="<?php echo htmlspecialchars($selectedCompany->location_country ?? ''); ?>"
                                       placeholder="DE">
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($selectedCompany->is_partner)): ?>
                    <div class="admin-card" style="background:#fefce8;border-color:#fde047;">
                        <p style="margin:0;font-size:.875rem;color:#713f12;">
                            ⭐ Dieses Unternehmen ist als <strong>Partner</strong> markiert.
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-actions-card admin-card" style="margin-top:0;">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Firmendaten speichern</button>
                    <a href="?company=<?php echo $selectedCompanyId; ?>&tab=benefits" class="btn btn-secondary">🎁 Zu Standard-Benefits</a>
                    <?php
                    $selJobCounts = $jobCountsMap[$selectedCompanyId] ?? ['published' => 0, 'total' => 0];
                    if ($selJobCounts['total'] > 0):
                    ?>
                    <a href="<?php echo esc_url('/admin/plugins/jpg-dashboard/jpg-generator?company_id=' . $selectedCompanyId); ?>"
                       class="btn btn-secondary btn-sm" style="margin-left:auto;">
                        📄 <?php echo $selJobCounts['published']; ?> aktive / <?php echo $selJobCounts['total']; ?> gesamt
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($linkedUser !== null): ?>
            <!-- Verknüpftes Mitglied -->
            <div class="admin-card" style="margin-top:0;background:#eff6ff;border-color:#bfdbfe;padding:.9rem 1.25rem;">
                <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
                    <span style="font-size:1.25rem;">👤</span>
                    <div style="flex:1;min-width:200px;">
                        <div style="font-weight:600;font-size:.9rem;color:#1e40af;">
                            <?php
                            $userName = trim(($linkedUser->firstname ?? '') . ' ' . ($linkedUser->lastname ?? ''));
                            echo htmlspecialchars($userName ?: ($linkedUser->username ?? 'Unbekannt'));
                            ?>
                        </div>
                        <div style="font-size:.8rem;color:#3b82f6;">
                            <?php echo htmlspecialchars($linkedUser->email ?? ''); ?>
                        </div>
                    </div>
                    <a href="<?php echo esc_url('/admin/users?id=' . (int)$linkedUser->id); ?>"
                       class="btn btn-secondary btn-sm">👁️ User-Profil</a>
                    <?php if (!empty($linkedUser->id)): ?>
                    <a href="<?php echo esc_url('/member/plugin/member-job-settings'); ?>"
                       class="btn btn-secondary btn-sm" title="Mitglieder-Einstellungsseite für diese Firma">
                        🔗 Member-Einst.
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </form>

        <!-- ══ Tab: Standard-Benefits ══ -->
        <?php else: ?>

        <div style="margin-bottom:1rem;">
            <h3 style="margin:0 0 .25rem;">🎁 Standard-Benefits:
                <strong><?php echo htmlspecialchars($selectedCompany->name); ?></strong>
            </h3>
            <p style="color:#64748b;font-size:.875rem;margin:0;">
                <?php echo count($assignedBenefitIds); ?> Benefit(s) zugewiesen – werden beim Erstellen neuer Stellenanzeigen vorausgewählt.
            </p>
        </div>

        <form method="post" class="admin-form">
            <input type="hidden" name="post_action" value="save_benefits">
            <input type="hidden" name="_jpg_nonce"  value="<?php echo esc_attr($nonceBenefits); ?>">
            <input type="hidden" name="company_id"  value="<?php echo $selectedCompanyId; ?>">

            <?php if (empty($allBenefits)): ?>
            <div class="admin-card">
                <div class="empty-state">
                    <p style="font-size:2rem;">📭</p>
                    <p>Noch keine Benefits im Katalog.</p>
                    <a href="<?php echo esc_url('/admin/plugins/jpg-dashboard/jpg-libraries?tab=benefit-catalog'); ?>"
                       class="btn btn-secondary">📚 Zum Benefit-Katalog</a>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($allBenefits as $groupName => $benefits): ?>
            <div class="admin-card" style="margin-bottom:1rem;padding:1rem 1.25rem;">
                <h4 style="margin:0 0 .75rem;"><?php echo htmlspecialchars($groupName); ?></h4>
                <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
                    <?php foreach ($benefits as $b): ?>
                    <?php $checked = in_array((int) $b->id, $assignedBenefitIds); ?>
                    <label class="jpg-benefit-toggle<?php echo $checked ? ' selected' : ''; ?>"
                           style="cursor:pointer;user-select:none;">
                        <input type="checkbox"
                               name="benefit_ids[]"
                               value="<?php echo (int) $b->id; ?>"
                               <?php echo $checked ? 'checked' : ''; ?>
                               style="display:none;"
                               onchange="this.closest('.jpg-benefit-toggle').classList.toggle('selected', this.checked)">
                        <?php echo htmlspecialchars($b->icon . ' ' . $b->title); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="form-actions-card admin-card" style="margin-top:1rem;">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Standard-Benefits speichern</button>
                    <a href="?company=<?php echo $selectedCompanyId; ?>&tab=info" class="btn btn-secondary">📋 Zurück zur Firmeninfo</a>
                    <a href="<?php echo esc_url('/admin/plugins/jpg-dashboard/jpg-generator?tab=basic&company_preselect=' . $selectedCompanyId); ?>"
                       class="btn btn-secondary btn-sm" style="margin-left:auto;">
                        ➕ Neue Stelle für Firma
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </form>
        <?php endif; ?><!-- /tabs -->

        <!-- ══ Tab: Abteilungen ════════════════════════════════════════ -->
        <?php if ($activeTab === 'departments'): ?>
        <div class="admin-card">
            <h3>🏢 Abteilungen</h3>
            <p style="color:#64748b;font-size:.875rem;">Abteilungen erben die Standard-Benefits und Anforderungen der Firma und können eigene hinzufügen.</p>

            <?php if (empty($departments)): ?>
            <div class="empty-state" style="padding:2rem;text-align:center;">
                <p style="font-size:2rem;margin:0;">🏢</p>
                <p><strong>Noch keine Abteilungen</strong></p>
            </div>
            <?php else: ?>
            <div class="users-table-container" style="margin-bottom:1rem;">
                <table class="users-table">
                    <thead><tr><th>Abteilung</th><th>Beschreibung</th><th>Aktionen</th></tr></thead>
                    <tbody>
                    <?php foreach ($departments as $dept): ?>
                    <tr>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($dept->name); ?></td>
                        <td style="font-size:.82rem;color:#64748b;"><?php echo htmlspecialchars($dept->description ?? ''); ?></td>
                        <td>
                            <div style="display:flex;gap:.35rem;">
                                <button type="button"
                                        id="jpg-dept-btn-<?php echo (int)$dept->id; ?>"
                                        onclick="jpgAdminToggleDept(<?php echo (int)$dept->id; ?>)"
                                        class="btn btn-sm btn-secondary">⚙️&nbsp;Konfigurieren</button>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="_jpg_nonce"     value="<?php echo esc_attr($nonceInfoKey); ?>">
                                    <input type="hidden" name="post_action"    value="delete_department">
                                    <input type="hidden" name="company_id"     value="<?php echo $selectedCompanyId; ?>">
                                    <input type="hidden" name="department_id"  value="<?php echo (int)$dept->id; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Abteilung wirklich löschen?');">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Neue Abteilung anlegen -->
            <form method="post" class="admin-form" style="margin-top:1.5rem;">
                <input type="hidden" name="_jpg_nonce"   value="<?php echo esc_attr($nonceInfoKey); ?>">
                <input type="hidden" name="post_action"  value="save_department">
                <input type="hidden" name="company_id"   value="<?php echo $selectedCompanyId; ?>">
                <h4>➕ Neue Abteilung</h4>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label class="form-label">Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="dept_name" class="form-control" required placeholder="z.B. Marketing">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Beschreibung</label>
                        <input type="text" name="dept_description" class="form-control" placeholder="Kurze Beschreibung">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">🏢 Abteilung anlegen</button>
            </form>
        </div>

        <!-- Inline-Akkordeon-Panels pro Abteilung -->
        <?php foreach ($departments as $dept):
            $dId            = (int) $dept->id;
            $thisBenefitIds = $allDeptBenefitIds[$dId] ?? [];
            $thisReqIds     = $allDeptReqIds[$dId]     ?? [];
        ?>
        <div id="jpg-dept-panel-<?php echo $dId; ?>" class="admin-card" style="display:none;border-left:4px solid var(--admin-primary);">
            <h3>⚙️ Abteilung: <?php echo htmlspecialchars($dept->name); ?></h3>

            <!-- Benefits dieser Abteilung -->
            <h4 style="margin-top:1rem;">🎁 Zusätzliche Benefits (erbt Firma-Standard)</h4>
            <p style="font-size:.82rem;color:#64748b;margin-bottom:.75rem;">Diese Benefits kommen zusätzlich zu den Firma-Standard-Benefits für Stellen dieser Abteilung.</p>
            <form method="post" class="admin-form">
                <input type="hidden" name="_jpg_nonce"    value="<?php echo esc_attr($nonceInfoKey); ?>">
                <input type="hidden" name="post_action"   value="save_dept_benefits">
                <input type="hidden" name="company_id"    value="<?php echo $selectedCompanyId; ?>">
                <input type="hidden" name="department_id" value="<?php echo $dId; ?>">
                <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem;">
                    <?php foreach ($allBenefits as $grpName => $benefits): ?>
                    <div style="width:100%;margin-top:.65rem;font-weight:600;font-size:.82rem;color:#475569;text-transform:uppercase;"><?php echo htmlspecialchars($grpName); ?></div>
                    <?php foreach ($benefits as $b):
                        $checked = in_array((int)$b->id, $thisBenefitIds); ?>
                    <label class="jpg-benefit-toggle<?php echo $checked ? ' selected' : ''; ?>" style="cursor:pointer;">
                        <input type="checkbox" name="dept_benefit_ids[]" value="<?php echo (int)$b->id; ?>"
                               <?php echo $checked ? 'checked' : ''; ?> style="display:none;"
                               onchange="this.closest('.jpg-benefit-toggle').classList.toggle('selected', this.checked)">
                        <?php echo htmlspecialchars(($b->icon ?? '') . ' ' . $b->title); ?>
                    </label>
                    <?php endforeach; endforeach; ?>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">💾 Benefits speichern</button>
            </form>

            <!-- Anforderungen dieser Abteilung -->
            <?php if (!empty($allReqItems)): ?>
            <h4 style="margin-top:1.5rem;">📌 Zusätzliche Anforderungen (Katalog)</h4>
            <p style="font-size:.82rem;color:#64748b;margin-bottom:.75rem;">Anforderungs-Einträge aus dem Katalog, die für Stellen dieser Abteilung vorgesehen sind.</p>
            <form method="post" class="admin-form">
                <input type="hidden" name="_jpg_nonce"    value="<?php echo esc_attr($nonceInfoKey); ?>">
                <input type="hidden" name="post_action"   value="save_dept_requirements">
                <input type="hidden" name="company_id"    value="<?php echo $selectedCompanyId; ?>">
                <input type="hidden" name="department_id" value="<?php echo $dId; ?>">
                <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem;">
                    <?php foreach ($allReqItems as $grpName => $items): ?>
                    <div style="width:100%;margin-top:.65rem;font-weight:600;font-size:.82rem;color:#475569;text-transform:uppercase;"><?php echo htmlspecialchars($grpName); ?></div>
                    <?php foreach ($items as $ri):
                        $checked    = in_array((int)$ri->id, $thisReqIds);
                        $typeColors = ['must' => '#fee2e2', 'nice' => '#fef3c7', 'optional' => '#d1fae5'];
                        $typeBg     = $typeColors[$ri->req_type ?? 'must'] ?? '#f1f5f9';
                    ?>
                    <label style="cursor:pointer;display:inline-flex;align-items:center;gap:.35rem;padding:.3rem .7rem;border-radius:6px;border:1px solid #e2e8f0;font-size:.82rem;background:<?php echo $checked ? $typeBg : '#f8fafc'; ?>;">
                        <input type="checkbox" name="dept_req_ids[]" value="<?php echo (int)$ri->id; ?>"
                               <?php echo $checked ? 'checked' : ''; ?>
                               onchange="this.closest('label').style.background=this.checked?'<?php echo $typeBg; ?>':'#f8fafc'">
                        <?php echo htmlspecialchars(($ri->icon ?? '') . ' ' . $ri->title); ?>
                        <span style="font-size:.72rem;color:#64748b;">(<?php echo htmlspecialchars($ri->req_type ?? 'must'); ?>)</span>
                    </label>
                    <?php endforeach; endforeach; ?>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">💾 Anforderungen speichern</button>
            </form>
            <?php endif; ?>

            <div style="margin-top:1.25rem;">
                <button type="button" onclick="jpgAdminToggleDept(<?php echo $dId; ?>)"
                        class="btn btn-secondary btn-sm">✖ Schließen</button>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- ══ Tab: Jobs-Seite ════════════════════════════════════════ -->
        <?php elseif ($activeTab === 'jobs-link'): ?>
        <div class="admin-card">
            <h3>🔗 Karriere- / Jobs-Seite</h3>
            <p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem;">
                Konfiguriere die öffentliche Karriere-Seite dieser Firma.
            </p>
            <form method="post" class="admin-form">
                <input type="hidden" name="_jpg_nonce"  value="<?php echo esc_attr($nonceInfoKey); ?>">
                <input type="hidden" name="post_action" value="save_jobs_page_settings">
                <input type="hidden" name="company_id"  value="<?php echo $selectedCompanyId; ?>">

                <div class="form-group">
                    <label class="form-label">Seiten-Titel</label>
                    <input type="text" name="jobs_page_title" class="form-control"
                           value="<?php echo htmlspecialchars($companySettings->jobs_page_title ?? ''); ?>"
                           placeholder="z.B. Offene Stellen bei Muster GmbH">
                    <small class="form-text">Wird als Überschrift der Karriere-Seite angezeigt.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Einleitungstext</label>
                    <textarea name="jobs_page_intro" class="form-control" rows="3"
                              placeholder="Kurzer Einleitungstext für die Karriere-Seite…"><?php echo htmlspecialchars($companySettings->jobs_page_intro ?? ''); ?></textarea>
                    <small class="form-text">Optionaler Einleitungstext unterhalb des Titels.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Kontakt-E-Mail</label>
                    <input type="email" name="jobs_page_contact_email" class="form-control"
                           value="<?php echo htmlspecialchars($companySettings->jobs_page_contact_email ?? ''); ?>"
                           placeholder="jobs@meinewebsite.de">
                    <small class="form-text">Bewerbungs-Kontaktadresse, die auf der Seite angezeigt wird.</small>
                </div>

                <div class="form-group" style="display:flex;gap:2rem;align-items:center;flex-wrap:wrap;">
                    <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;">
                        <input type="checkbox" name="jobs_page_show_salary"
                               <?php echo !empty($companySettings->jobs_page_show_salary) ? 'checked' : ''; ?>>
                        💰 Gehalt anzeigen
                    </label>
                    <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;">
                        <input type="checkbox" name="jobs_page_enabled"
                               <?php echo (!isset($companySettings->jobs_page_enabled) || !empty($companySettings->jobs_page_enabled)) ? 'checked' : ''; ?>>
                        ✅ Karriere-Seite aktiv
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-label">URL zur Karriere-/Jobs-Seite</label>
                    <input type="url" name="jobs_page_url" class="form-control"
                           value="<?php echo htmlspecialchars($companySettings->jobs_page_url ?? $jobsPageUrl); ?>"
                           placeholder="https://meinewebsite.de/jobs">
                    <small class="form-text">Vollständige URL inkl. https://</small>
                </div>

                <?php $currentUrl = $companySettings->jobs_page_url ?? $jobsPageUrl; ?>
                <?php if (!empty($currentUrl)): ?>
                <div style="margin-bottom:1rem;padding:.75rem 1rem;background:#eff6ff;border-radius:6px;">
                    <span style="font-size:.875rem;color:#1e40af;">🔗 Aktuell: </span>
                    <a href="<?php echo htmlspecialchars($currentUrl); ?>" target="_blank"
                       style="color:#3b82f6;font-size:.875rem;">
                        <?php echo htmlspecialchars($currentUrl); ?>
                    </a>
                </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
            </form>
        </div>

        <?php endif; ?><!-- /tabs -->

        <?php endif; ?><!-- /selectedCompany -->
    </div><!-- /right col -->

</div><!-- /grid -->

<?php endif; ?>

<script>
function jpgAdminToggleDept(deptId) {
    var panel = document.getElementById('jpg-dept-panel-' + deptId);
    var btn   = document.getElementById('jpg-dept-btn-' + deptId);
    if (!panel) return;
    var isOpen = panel.style.display !== 'none';
    // Close all open panels first
    document.querySelectorAll('[id^="jpg-dept-panel-"]').forEach(function(p) {
        p.style.display = 'none';
    });
    document.querySelectorAll('[id^="jpg-dept-btn-"]').forEach(function(b) {
        b.textContent = '⚙️\u00a0Konfigurieren';
    });
    // Open this one if it was closed
    if (!isOpen) {
        panel.style.display = '';
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        if (btn) btn.textContent = '✖\u00a0Schließen';
    }
}
</script>

