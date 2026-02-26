<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
/**
 * Member-View: Firmen-Einstellungen (Unternehmensübersicht für Mitglieder)
 *
 * @var object|null           $company            Firmen-Datensatz aus cms-companies (oder null)
 * @var array<string,array>   $allBenefits         Gruppierter Benefit-Katalog
 * @var array<int>            $assignedBenefitIds  Standard-Benefits dieser Firma
 * @var string                $csrfInfo            CSRF-Token (action: member_company_settings)
 * @var string                $csrfBenefits        CSRF-Token (action: member_company_benefits)
 * @var string                $csrf                CSRF-Token (legacy alias für csrfInfo)
 * @var string                $activeTab           Aktiver Tab: 'info' | 'benefits' | 'team' | 'departments'
 * @var array                 $departments          Abteilungen der Firma
 * @var string                $jobsPageUrl          URL zur Jobs-Seite
 * @var array                 $allReqItems          Gruppierter Anforderungs-Katalog
 * @var int                   $selectedDeptId       Aktuell bearbeitete Abteilung
 * @var array<int>            $deptBenefitIds       Benefit-IDs der Abteilung
 * @var array<int>            $deptReqIds           Anforderungs-IDs der Abteilung
 * @var string                $csrfDepartments      CSRF-Token (action: member_company_departments)
 * @var string                $baseUrl             Basis-URL (z. B. /member/plugin/member-jobs)
 * @var string                $notice
 * @var string                $error
 */
$esc        = fn(string|null $v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
$baseUrl    = $baseUrl ?? '/member/jobs';
$activeTab  = $activeTab  ?? 'info';
$csrfInfo     = $csrfInfo     ?? $csrf ?? '';
$csrfBenefits = $csrfBenefits ?? $csrf ?? '';
$allBenefits         = $allBenefits         ?? [];
$assignedBenefitIds  = $assignedBenefitIds  ?? [];
$companyJobStats     = $companyJobStats ?? ['published' => 0, 'total' => 0, 'draft' => 0, 'archived' => 0];
$teamApprovers       = $teamApprovers ?? [];
$availableUsers      = $availableUsers ?? [];
$csrfTeam            = $csrfTeam ?? '';
$departments         = $departments ?? [];
$jobsPageUrl         = $jobsPageUrl ?? '';
$allReqItems         = $allReqItems ?? [];
$selectedDeptId      = $selectedDeptId ?? 0;
$deptBenefitIds      = $deptBenefitIds ?? [];
$deptReqIds          = $deptReqIds ?? [];
$csrfDepartments     = $csrfDepartments ?? '';
$allDeptBenefitIds   = $allDeptBenefitIds ?? [];
$allDeptReqIds       = $allDeptReqIds     ?? [];
$companySettings     = $companySettings   ?? null;

// URL zum Anlegen einer neuen Stelle (mit company_preselect)
$isInlineMode = isset($isInline) ? $isInline : str_contains($baseUrl, '/plugin/');
$createJobUrl = $isInlineMode
    ? '/member/plugin/member-jobs?action=create'
    : $baseUrl . '/create';

// Tab-URL-Basis (inline-Mode vs. Standalone-Route)
$isInline   = str_contains($baseUrl, '/plugin/');
$tabUrl     = $isInline
    ? '/member/plugin/member-job-settings'
    : $baseUrl . '/settings';
?>

<?php if (!empty($notice)): ?>
<div class="alert alert-success" style="margin-bottom:1.25rem;">✅ <?php echo $esc($notice); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error" style="margin-bottom:1.25rem;">❌ <?php echo $esc($error); ?></div>
<?php endif; ?>

<?php if ($company === null): ?>
<!-- Kein Firmenprofil verfügbar -->
<div class="admin-card">
    <div class="empty-state" style="padding:2.5rem 1.5rem;">
        <p style="font-size:2.5rem;margin:0;">🏢</p>
        <p><strong>Kein Firmenprofil gefunden</strong></p>
        <p style="color:#64748b;font-size:.875rem;">
            Deinem Konto ist noch kein Unternehmen zugeordnet.<br>
            Bitte kontaktiere den Administrator, um ein Firmenprofil anzulegen.
        </p>
        <a href="<?php echo $esc($baseUrl); ?>" class="btn btn-secondary" style="margin-top:1rem;">← Zurück zur Übersicht</a>
    </div>
</div>

<?php else: ?>

<!-- ── Tab-Navigation ──────────────────────────────────────────────── -->
<div style="display:flex;gap:.25rem;margin-bottom:1.5rem;border-bottom:2px solid #e2e8f0;">
    <?php
    $tabs = [
        'info'        => ['icon' => '📋', 'label' => 'Firmeninformationen'],
        'benefits'    => ['icon' => '🎁', 'label' => 'Standard-Benefits'],
        'departments' => ['icon' => '🏢', 'label' => 'Abteilungen'],
        'team'        => ['icon' => '👥', 'label' => 'Team-Genehmiger'],
        'jobs-page'   => ['icon' => '🔗', 'label' => 'Jobs-Seite'],
    ];
    foreach ($tabs as $tabKey => $tabDef):
        $isActive = $activeTab === $tabKey;
        $badge = '';
        if ($tabKey === 'departments' && count($departments) > 0) {
            $badge = '<span style="background:' . ($isActive ? '#eff6ff' : '#f1f5f9') . ';color:' . ($isActive ? '#3b82f6' : '#64748b') . ';padding:.1rem .45rem;border-radius:10px;font-size:.72rem;font-weight:700;">' . count($departments) . '</span>';
        }
        $href     = $tabUrl . '?tab=' . $tabKey . ($isInline ? '' : '');
    ?>
    <a href="<?php echo $esc($href); ?>"
       style="display:inline-flex;align-items:center;gap:.35rem;padding:.55rem 1.1rem;border-radius:6px 6px 0 0;text-decoration:none;font-size:.875rem;font-weight:600;border:2px solid transparent;border-bottom:none;margin-bottom:-2px;
              <?php echo $isActive
                  ? 'background:#fff;border-color:#e2e8f0;color:#1e293b;'
                  : 'color:#64748b;'; ?>">
        <?php echo $tabDef['icon']; ?> <?php echo $tabDef['label']; ?>
        <?php echo $badge; ?>
        <?php if ($tabKey === 'benefits' && count($assignedBenefitIds) > 0): ?>
        <span style="background:<?php echo $isActive ? '#eff6ff' : '#f1f5f9'; ?>;color:<?php echo $isActive ? '#3b82f6' : '#64748b'; ?>;padding:.1rem .45rem;border-radius:10px;font-size:.72rem;font-weight:700;">
            <?php echo count($assignedBenefitIds); ?>
        </span>
        <?php endif; ?>
        <?php if ($tabKey === 'team' && count($teamApprovers) > 0): ?>
        <span style="background:<?php echo $isActive ? '#fef3c7' : '#f1f5f9'; ?>;color:<?php echo $isActive ? '#92400e' : '#64748b'; ?>;padding:.1rem .45rem;border-radius:10px;font-size:.72rem;font-weight:700;">
            <?php echo count($teamApprovers); ?>
        </span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- ── Unternehmens-Statistiken ──────────────────────────────────────── -->
<?php if ($companyJobStats['total'] > 0 || true): ?>
<div style="display:flex;align-items:center;flex-wrap:wrap;gap:.75rem;margin-bottom:1.25rem;
            padding:.75rem 1rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">
    <span style="font-size:.8rem;font-weight:600;color:#475569;margin-right:.25rem;">📊 Meine Stellen:</span>
    <?php if ($companyJobStats['published'] > 0): ?>
    <a href="<?php echo $esc($isInlineMode ? '/member/plugin/member-jobs?action=list' : $baseUrl); ?>"
       style="display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .65rem;border-radius:12px;
              background:#d1fae5;color:#065f46;font-size:.82rem;font-weight:600;text-decoration:none;">
        ✅ <?php echo $companyJobStats['published']; ?> Aktiv
    </a>
    <?php endif; ?>
    <?php if ($companyJobStats['draft'] > 0): ?>
    <a href="<?php echo $esc($isInlineMode ? '/member/plugin/member-jobs?action=list' : $baseUrl); ?>"
       style="display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .65rem;border-radius:12px;
              background:#fef3c7;color:#92400e;font-size:.82rem;font-weight:600;text-decoration:none;">
        📝 <?php echo $companyJobStats['draft']; ?> Entwurf
    </a>
    <?php endif; ?>
    <?php if ($companyJobStats['total'] === 0): ?>
    <span style="font-size:.82rem;color:#94a3b8;">Noch keine Stellenanzeigen vorhanden</span>
    <?php endif; ?>
    <a href="<?php echo $esc($createJobUrl); ?>"
       style="margin-left:auto;display:inline-flex;align-items:center;gap:.3rem;padding:.3rem .85rem;
              border-radius:8px;background:#3b82f6;color:#fff;font-size:.82rem;font-weight:600;
              text-decoration:none;">
        ➕ Neue Stelle
    </a>
</div>
<?php endif; ?>

<!-- ══ Tab: Firmeninformationen ══════════════════════════════════════ -->
<?php if ($activeTab === 'info'): ?>

<form method="post" class="admin-form" action="<?php echo $esc($tabUrl . '?tab=info'); ?>">
    <input type="hidden" name="_jpg_csrf"      value="<?php echo $esc($csrfInfo); ?>">
    <input type="hidden" name="settings_tab"   value="info">

    <!-- ── Zweispaltig: Basisdaten + Beschreibung/Standort ── -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">

        <!-- Linke Spalte -->
        <div>
            <div class="admin-card">
                <h3>🏢 Firmenidentität</h3>

                <?php if (!empty($company->logo_url)): ?>
                <div style="margin-bottom:1rem;text-align:center;padding:.75rem;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;">
                    <img src="<?php echo $esc($company->logo_url); ?>"
                         alt="Firmenlogo"
                         style="max-height:80px;max-width:220px;object-fit:contain;">
                    <p style="font-size:.78rem;color:#94a3b8;margin:.5rem 0 0;">Aktuelles Logo</p>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">Logo-URL</label>
                    <input type="url" name="company_logo_url" class="form-control"
                           value="<?php echo $esc($company->logo_url ?? ''); ?>"
                           placeholder="https://deinefirma.de/logo.png">
                    <small class="form-text">Direktlink zu deinem Firmenlogo (PNG, SVG oder JPG empfohlen).</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Unternehmensname <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="company_name" class="form-control" required
                           value="<?php echo $esc($company->name ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Website</label>
                    <input type="url" name="company_website" class="form-control"
                           value="<?php echo $esc($company->website ?? ''); ?>"
                           placeholder="https://deinefirma.de">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label class="form-label">E-Mail</label>
                        <input type="email" name="company_email" class="form-control"
                               value="<?php echo $esc($company->email ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telefon</label>
                        <input type="text" name="company_phone" class="form-control"
                               value="<?php echo $esc($company->phone ?? ''); ?>">
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <h3>🏭 Branche & Größe</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label class="form-label">Branche</label>
                        <input type="text" name="company_industry" class="form-control"
                               value="<?php echo $esc($company->industry ?? ''); ?>"
                               placeholder="z.B. IT, Gesundheit, Logistik">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unternehmensgröße</label>
                        <select name="company_size" class="form-control">
                            <?php
                            $sizes = ['', '1-10', '11-50', '51-200', '201-500', '501-1000', '1000+'];
                            foreach ($sizes as $sz):
                            ?>
                            <option value="<?php echo $esc($sz); ?>"
                                <?php echo ($company->company_size ?? '') === $sz ? 'selected' : ''; ?>>
                                <?php echo $sz ?: '– bitte wählen –'; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gründungsjahr</label>
                        <input type="number" name="company_founded_year" class="form-control"
                               min="1800" max="<?php echo date('Y'); ?>"
                               value="<?php echo (int)($company->founded_year ?? 0) ?: ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mitarbeiteranzahl</label>
                        <input type="number" name="company_employee_count" class="form-control"
                               min="0"
                               value="<?php echo (int)($company->employee_count ?? 0) ?: ''; ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Rechte Spalte -->
        <div>
            <div class="admin-card">
                <h3>📝 Über uns</h3>
                <div class="form-group">
                    <textarea name="company_description" class="form-control"
                              rows="10" style="resize:vertical;"><?php echo $esc($company->description ?? ''); ?></textarea>
                    <small class="form-text">
                        Kurze Unternehmensvorstellung – erscheint auf deiner Profil- und Stellenanzeigen-Seite.
                    </small>
                </div>
            </div>

            <div class="admin-card">
                <h3>📍 Standort</h3>
                <div class="form-group">
                    <label class="form-label">Stadt</label>
                    <input type="text" name="company_city" class="form-control"
                           value="<?php echo $esc($company->location_city ?? ''); ?>">
                </div>
                <div style="display:grid;grid-template-columns:120px 1fr;gap:1rem;">
                    <div class="form-group">
                        <label class="form-label">PLZ</label>
                        <input type="text" name="company_zip" class="form-control"
                               value="<?php echo $esc($company->location_zip ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Land</label>
                        <input type="text" name="company_country" class="form-control"
                               value="<?php echo $esc($company->location_country ?? ''); ?>"
                               placeholder="DE">
                    </div>
                </div>
            </div>

            <!-- Info-Box: Hinweis-->
            <div class="admin-card" style="background:#eff6ff;border-color:#bfdbfe;">
                <p style="margin:0;font-size:.875rem;color:#1e40af;">
                    💡 <strong>Tipp:</strong> Diese Daten werden automatisch bei neuen Stellenanzeigen vorbelegt und
                    auf deinem öffentlichen Firmenprofil angezeigt.
                </p>
            </div>
        </div>
    </div>

    <!-- Speichern (Info-Tab) -->
    <div class="admin-card form-actions-card">
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
            <a href="<?php echo $esc($tabUrl . '?tab=benefits'); ?>" class="btn btn-secondary">🎁 Zu Standard-Benefits →</a>
            <a href="<?php echo $esc($createJobUrl); ?>" class="btn btn-secondary btn-sm" style="margin-left:auto;">➕ Neue Stelle erstellen</a>
        </div>
    </div>
</form>

<!-- ══ Tab: Standard-Benefits ═══════════════════════════════════════ -->
<?php elseif ($activeTab === 'benefits'): ?>

<div style="margin-bottom:1rem;">
    <h3 style="margin:0 0 .25rem;">🎁 Standard-Benefits:
        <strong><?php echo $esc($company->name ?? ''); ?></strong>
    </h3>
    <p style="color:#64748b;font-size:.875rem;margin:0;">
        <?php echo count($assignedBenefitIds); ?> Benefit(s) zugewiesen – werden beim Erstellen neuer
        Stellenanzeigen automatisch vorausgewählt.
    </p>
</div>

<form method="post" class="admin-form" action="<?php echo $esc($tabUrl . '?tab=benefits'); ?>">
    <input type="hidden" name="_jpg_csrf"    value="<?php echo $esc($csrfBenefits); ?>">
    <input type="hidden" name="settings_tab" value="benefits">

    <?php if (empty($allBenefits)): ?>
    <div class="admin-card">
        <div class="empty-state" style="padding:2rem 1.5rem;text-align:center;">
            <p style="font-size:2rem;margin:0;">📭</p>
            <p><strong>Noch keine Benefits im Katalog</strong></p>
            <p style="color:#64748b;font-size:.875rem;">Der Administrator muss zuerst Benefits im Benefit-Katalog anlegen.</p>
        </div>
    </div>

    <?php else: ?>

    <div class="admin-card" style="background:#eff6ff;border-color:#bfdbfe;padding:1rem 1.25rem;margin-bottom:1.25rem;">
        <p style="margin:0;font-size:.875rem;color:#1e40af;">
            💡 <strong>Tipp:</strong> Diese Benefits werden bei jeder neuen Stellenanzeige für dein Unternehmen
            vorausgewählt. Du kannst sie im Wizard jederzeit individuell anpassen.
        </p>
    </div>

    <?php foreach ($allBenefits as $groupName => $benefits): ?>
    <div class="admin-card" style="margin-bottom:1rem;padding:1rem 1.25rem;">
        <h4 style="margin:0 0 .75rem;font-size:.95rem;color:#475569;"><?php echo $esc($groupName); ?></h4>
        <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
            <?php foreach ($benefits as $b):
                $checked = in_array((int) $b->id, $assignedBenefitIds, true);
            ?>
            <label class="jpg-benefit-toggle<?php echo $checked ? ' selected' : ''; ?>"
                   style="cursor:pointer;user-select:none;display:inline-flex;align-items:center;gap:.3rem;
                          padding:.35rem .75rem;border-radius:20px;font-size:.85rem;font-weight:500;
                          border:2px solid <?php echo $checked ? '#3b82f6' : '#e2e8f0'; ?>;
                          background:<?php echo $checked ? '#eff6ff' : '#fff'; ?>;
                          color:<?php echo $checked ? '#1d4ed8' : '#475569'; ?>;
                          transition:all .15s ease;">
                <input type="checkbox"
                       name="benefit_ids[]"
                       value="<?php echo (int) $b->id; ?>"
                       <?php echo $checked ? 'checked' : ''; ?>
                       style="display:none;"
                       onchange="(function(el){
                           const lbl = el.closest('.jpg-benefit-toggle');
                           const on  = el.checked;
                           lbl.style.borderColor  = on ? '#3b82f6' : '#e2e8f0';
                           lbl.style.background   = on ? '#eff6ff' : '#fff';
                           lbl.style.color        = on ? '#1d4ed8' : '#475569';
                           lbl.classList.toggle('selected', on);
                       })(this)">
                <?php echo $esc(trim($b->icon . ' ' . $b->title)); ?>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <?php endif; ?>

    <div class="admin-card form-actions-card">
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Standard-Benefits speichern</button>
            <a href="<?php echo $esc($tabUrl . '?tab=info'); ?>" class="btn btn-secondary">📋 Zurück zur Firmeninfo</a>
            <a href="<?php echo $esc($createJobUrl); ?>" class="btn btn-secondary btn-sm" style="margin-left:auto;">➕ Neue Stelle (Benefits vorauswählen)</a>
        </div>
    </div>
</form>

<!-- ══ Tab: Team-Genehmiger (Phase 9) ══════════════════════════════ -->
<?php if ($activeTab === 'team'): ?>

<div class="admin-card" style="background:#fffbeb;border-color:#fde68a;padding:1rem 1.25rem;margin-bottom:1.25rem;">
    <p style="margin:0;font-size:.875rem;color:#92400e;">
        👥 <strong>Team-Genehmiger:</strong> Personen die hier eingetragen sind, können deine Stellenanzeigen
        im Genehmigungsprozess freigeben – ohne Admin-Rechte zu benötigen.
    </p>
</div>

<!-- Aktuelle Genehmiger -->
<div class="admin-card">
    <h3>👥 Aktuelle Genehmiger</h3>
    <?php if (empty($teamApprovers)): ?>
    <div class="empty-state" style="padding:2rem 1.5rem;text-align:center;">
        <p style="font-size:2rem;margin:0;">👤</p>
        <p><strong>Noch keine Team-Genehmiger eingetragen</strong></p>
        <p style="color:#64748b;font-size:.875rem;">Füge unten Personen hinzu, die deine Stellen genehmigen dürfen.</p>
    </div>
    <?php else: ?>
    <div class="users-table-container">
        <table class="users-table">
            <thead><tr>
                <th>Name</th>
                <th>E-Mail</th>
                <th>Hinzugefügt am</th>
                <th>Aktion</th>
            </tr></thead>
            <tbody>
            <?php foreach ($teamApprovers as $approver): ?>
            <tr>
                <td>
                    <?php
                    $fullName = trim(htmlspecialchars(($approver->firstname ?? '') . ' ' . ($approver->lastname ?? '')));
                    echo $fullName ?: htmlspecialchars($approver->username ?? '–');
                    ?>
                </td>
                <td><?php echo htmlspecialchars($approver->email ?? ''); ?></td>
                <td style="font-size:.82rem;color:#64748b;"><?php echo htmlspecialchars(date('d.m.Y', strtotime($approver->created_at ?? 'now'))); ?></td>
                <td>
                    <form method="post" action="<?php echo $esc($tabUrl . '?tab=team'); ?>" style="display:inline;">
                        <input type="hidden" name="_jpg_csrf"         value="<?php echo $esc($csrfTeam); ?>">
                        <input type="hidden" name="settings_tab"      value="team">
                        <input type="hidden" name="team_action"       value="remove">
                        <input type="hidden" name="approver_user_id"  value="<?php echo (int) $approver->approver_user_id; ?>">
                        <button type="submit" class="btn btn-sm btn-danger"
                                onclick="return confirm('Genehmiger wirklich entfernen?');">🗑️</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Neuen Genehmiger hinzufügen -->
<?php if (!empty($availableUsers)): ?>
<div class="admin-card">
    <h3>➕ Genehmiger hinzufügen</h3>
    <form method="post" action="<?php echo $esc($tabUrl . '?tab=team'); ?>">
        <input type="hidden" name="_jpg_csrf"    value="<?php echo $esc($csrfTeam); ?>">
        <input type="hidden" name="settings_tab" value="team">
        <input type="hidden" name="team_action"  value="add">
        <div style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap;">
            <div class="form-group" style="flex:1;min-width:200px;margin-bottom:0;">
                <label class="form-label">Benutzer auswählen</label>
                <select name="approver_user_id" class="form-control" required>
                    <option value="">– Bitte wählen –</option>
                    <?php foreach ($availableUsers as $u): ?>
                    <option value="<?php echo (int) $u->id; ?>">
                        <?php
                        $name = trim(($u->firstname ?? '') . ' ' . ($u->lastname ?? ''));
                        echo htmlspecialchars($name ?: $u->username);
                        ?> (<?php echo htmlspecialchars($u->email ?? ''); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="white-space:nowrap;">👥 Als Genehmiger eintragen</button>
        </div>
    </form>
</div>
<?php else: ?>
<div class="admin-card">
    <p style="color:#64748b;margin:0;font-size:.875rem;">Alle verfügbaren Nutzer sind bereits als Genehmiger eingetragen.</p>
</div>
<?php endif; ?>

<?php endif; ?><!-- /activeTab team -->

<!-- ══ Tab: Abteilungen ══════════════════════════════════ -->
<?php if ($activeTab === 'departments'): ?>

<div class="admin-card">
    <h3>🏢 Abteilungen deiner Firma</h3>
    <p style="color:#64748b;font-size:.875rem;margin-bottom:1rem;">Abteilungen erben die Firma-Benefits und können eigene Benefits/Anforderungen erhalten, die auf alle Stellen dieser Abteilung vererbt werden.</p>

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
                <td style="font-weight:600;"><?php echo $esc($dept->name); ?></td>
                <td style="font-size:.82rem;color:#64748b;"><?php echo $esc($dept->description ?? ''); ?></td>
                <td>
                    <div style="display:flex;gap:.35rem;">
                        <button type="button"
                                id="jpg-mdept-btn-<?php echo (int)$dept->id; ?>"
                                onclick="jpgMemberToggleDept(<?php echo (int)$dept->id; ?>)"
                                class="btn btn-sm btn-secondary">⚙️&nbsp;Konfigurieren</button>
                        <form method="post" action="<?php echo $esc($tabUrl . '?tab=departments'); ?>" style="display:inline;">
                            <input type="hidden" name="_jpg_csrf"       value="<?php echo $esc($csrfDepartments); ?>">
                            <input type="hidden" name="settings_tab"    value="departments">
                            <input type="hidden" name="dept_action"     value="delete_department">
                            <input type="hidden" name="department_id"   value="<?php echo (int)$dept->id; ?>">
                            <button type="submit" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Abteilung wirklich löschen?')">🗑️</button>
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
    <form method="post" action="<?php echo $esc($tabUrl . '?tab=departments'); ?>" class="admin-form" style="margin-top:1rem;">
        <input type="hidden" name="_jpg_csrf"     value="<?php echo $esc($csrfDepartments); ?>">
        <input type="hidden" name="settings_tab" value="departments">
        <input type="hidden" name="dept_action"  value="save_department">
        <h4>➕ Neue Abteilung anlegen</h4>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="form-group">
                <label class="form-label">Name <span style="color:#ef4444;">*</span></label>
                <input type="text" name="dept_name" class="form-control" required placeholder="z.B. Marketing">
            </div>
            <div class="form-group">
                <label class="form-label">Beschreibung</label>
                <input type="text" name="dept_description" class="form-control" placeholder="Optionale Beschreibung">
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
<div id="jpg-mdept-panel-<?php echo $dId; ?>" class="admin-card" style="display:none;border-left:4px solid var(--admin-primary,#3b82f6);">
    <h3>⚙️ Abteilung: <?php echo $esc($dept->name); ?></h3>

    <!-- Benefits -->
    <h4 style="margin-top:1rem;">🎁 Zusätzliche Benefits</h4>
    <p style="font-size:.82rem;color:#64748b;margin-bottom:.75rem;">Diese Benefits ergänzen die Firma-Standard-Benefits für alle Stellen dieser Abteilung.</p>
    <form method="post" action="<?php echo $esc($tabUrl . '?tab=departments'); ?>" class="admin-form">
        <input type="hidden" name="_jpg_csrf"     value="<?php echo $esc($csrfDepartments); ?>">
        <input type="hidden" name="settings_tab"  value="departments">
        <input type="hidden" name="dept_action"   value="save_dept_benefits">
        <input type="hidden" name="department_id" value="<?php echo $dId; ?>">
        <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem;">
            <?php foreach ($allBenefits as $grpName => $benefits): ?>
            <div style="width:100%;margin-top:.65rem;font-weight:600;font-size:.82rem;color:#475569;text-transform:uppercase;cursor:pointer;display:flex;justify-content:space-between;align-items:center;"
                 onclick="jpgToggleGroup(this)">
                <span><?php echo $esc($grpName); ?></span>
                <span class="jpg-toggle-icon" style="font-size:.75rem;color:#94a3b8;">▼</span>
            </div>
            <div class="jpg-grp-body" style="display:flex;flex-wrap:wrap;gap:.5rem;width:100%;">
            <?php foreach ($benefits as $b):
                $checked = in_array((int)$b->id, $thisBenefitIds); ?>
            <label style="cursor:pointer;display:inline-flex;align-items:center;gap:.35rem;padding:.3rem .7rem;border-radius:6px;border:1px solid #e2e8f0;font-size:.85rem;background:<?php echo $checked ? '#eff6ff' : '#f8fafc'; ?>;">
                <input type="checkbox" name="dept_benefit_ids[]" value="<?php echo (int)$b->id; ?>"
                       <?php echo $checked ? 'checked' : ''; ?>
                       onchange="this.closest('label').style.background=this.checked?'#eff6ff':'#f8fafc'">
                <?php echo $esc(($b->icon ?? '') . ' ' . $b->title); ?>
            </label>
            <?php endforeach; ?>
            </div><!-- /.jpg-grp-body -->
            <?php endforeach; // allBenefits ?>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">💾 Benefits speichern</button>
    </form>

    <!-- Anforderungen -->
    <?php if (!empty($allReqItems)): ?>
    <h4 style="margin-top:1.5rem;">📌 Anforderungen (Katalog)</h4>
    <p style="font-size:.82rem;color:#64748b;margin-bottom:.75rem;">Anforderungen aus dem Katalog für Stellen dieser Abteilung vorbelegen.</p>
    <form method="post" action="<?php echo $esc($tabUrl . '?tab=departments'); ?>" class="admin-form">
        <input type="hidden" name="_jpg_csrf"     value="<?php echo $esc($csrfDepartments); ?>">
        <input type="hidden" name="settings_tab"  value="departments">
        <input type="hidden" name="dept_action"   value="save_dept_requirements">
        <input type="hidden" name="department_id" value="<?php echo $dId; ?>">
        <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem;">
            <?php foreach ($allReqItems as $grpName => $items): ?>
            <div style="width:100%;margin-top:.65rem;font-weight:600;font-size:.82rem;color:#475569;text-transform:uppercase;cursor:pointer;display:flex;justify-content:space-between;align-items:center;"
                 onclick="jpgToggleGroup(this)">
                <span><?php echo $esc($grpName); ?></span>
                <span class="jpg-toggle-icon" style="font-size:.75rem;color:#94a3b8;">▼</span>
            </div>
            <div class="jpg-grp-body" style="display:flex;flex-wrap:wrap;gap:.5rem;width:100%;">
            <?php foreach ($items as $ri):
                $checked  = in_array((int)$ri->id, $thisReqIds);
                $typeMap  = ['must' => '#fee2e2', 'nice' => '#fef3c7', 'optional' => '#d1fae5'];
                $typeBg   = $typeMap[$ri->req_type ?? 'must'] ?? '#f1f5f9';
            ?>
            <label style="cursor:pointer;display:inline-flex;align-items:center;gap:.35rem;padding:.3rem .7rem;border-radius:6px;border:1px solid #e2e8f0;font-size:.82rem;background:<?php echo $checked ? $typeBg : '#f8fafc'; ?>;">
                <input type="checkbox" name="dept_req_ids[]" value="<?php echo (int)$ri->id; ?>"
                       <?php echo $checked ? 'checked' : ''; ?>
                       onchange="this.closest('label').style.background=this.checked?'<?php echo $typeBg; ?>':'#f8fafc'">
                <?php echo $esc(($ri->icon ?? '') . ' ' . $ri->title); ?>
                <span style="font-size:.72rem;color:#64748b;">(<?php echo $esc($ri->req_type ?? 'must'); ?>)</span>
            </label>
            <?php endforeach; ?>
            </div><!-- /.jpg-grp-body -->
            <?php endforeach; // allReqItems ?>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">💾 Anforderungen speichern</button>
    </form>
    <?php endif; ?>

    <div style="margin-top:1.25rem;">
        <button type="button" onclick="jpgMemberToggleDept(<?php echo $dId; ?>)"
                class="btn btn-secondary btn-sm">✖ Schließen</button>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?><!-- /activeTab departments -->

<!-- ══ Tab: Jobs-Seite ══════════════════════════════════ -->
<?php if ($activeTab === 'jobs-page'): ?>
<div class="admin-card">
    <h3>🔗 Karriere- / Jobs-Seite</h3>
    <p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem;">
        Konfiguriere die öffentliche Karriere-Seite deiner Firma.
    </p>
    <form method="post" action="<?php echo $esc($tabUrl . '?tab=jobs-page'); ?>" class="admin-form">
        <input type="hidden" name="_jpg_csrf"    value="<?php echo $esc($csrfInfo); ?>">
        <input type="hidden" name="settings_tab" value="jobs-page">

        <div class="form-group">
            <label class="form-label">Seiten-Titel</label>
            <input type="text" name="jobs_page_title" class="form-control"
                   value="<?php echo $esc($companySettings->jobs_page_title ?? ''); ?>"
                   placeholder="z.B. Offene Stellen bei Muster GmbH">
            <small class="form-text">Wird als Überschrift der Karriere-Seite angezeigt.</small>
        </div>

        <div class="form-group">
            <label class="form-label">Einleitungstext</label>
            <textarea name="jobs_page_intro" class="form-control" rows="3"
                      placeholder="Kurzer Einleitungstext für die Karriere-Seite…"><?php echo $esc($companySettings->jobs_page_intro ?? ''); ?></textarea>
            <small class="form-text">Optionaler Einleitungstext unterhalb des Titels.</small>
        </div>

        <div class="form-group">
            <label class="form-label">Kontakt-E-Mail</label>
            <input type="email" name="jobs_page_contact_email" class="form-control"
                   value="<?php echo $esc($companySettings->jobs_page_contact_email ?? ''); ?>"
                   placeholder="jobs@meinewebsite.de">
            <small class="form-text">Bewerbungs-Kontaktadresse, die auf der Seite angezeigt wird.</small>
        </div>

        <div class="form-group" style="display:flex;gap:2rem;align-items:center;flex-wrap:wrap;margin-bottom:1.25rem;">
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
                   value="<?php echo $esc($companySettings->jobs_page_url ?? ''); ?>"
                   placeholder="https://meinewebsite.de/jobs">
            <small class="form-text">Vollständige URL inkl. https://</small>
        </div>

        <?php $currentJobsUrl = $companySettings->jobs_page_url ?? ''; ?>
        <?php if (!empty($currentJobsUrl)): ?>
        <div style="margin-bottom:1rem;padding:.75rem 1rem;background:#eff6ff;border-radius:6px;">
            <span style="font-size:.875rem;color:#1e40af;">🔗 Aktuell: </span>
            <a href="<?php echo $esc($currentJobsUrl); ?>" target="_blank"
               style="color:#3b82f6;font-size:.875rem;"><?php echo $esc($currentJobsUrl); ?></a>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
    </form>
</div>
<?php endif; ?><!-- /activeTab jobs-page -->

<?php endif; ?><!-- /activeTab -->
<?php endif; ?><!-- /company -->

<script>
if (typeof jpgToggleGroup !== 'function') {
    function jpgToggleGroup(header) {
        var body = header.nextElementSibling;
        if (!body) return;
        var isOpen = body.style.display !== 'none';
        body.style.display = isOpen ? 'none' : '';
        var icon = header.querySelector('.jpg-toggle-icon');
        if (icon) icon.textContent = isOpen ? '\u25b6' : '\u25bc';
    }
}
function jpgMemberToggleDept(deptId) {
    var panel = document.getElementById('jpg-mdept-panel-' + deptId);
    var btn   = document.getElementById('jpg-mdept-btn-' + deptId);
    if (!panel) return;
    var isOpen = panel.style.display !== 'none';
    document.querySelectorAll('[id^="jpg-mdept-panel-"]').forEach(function(p) {
        p.style.display = 'none';
    });
    document.querySelectorAll('[id^="jpg-mdept-btn-"]').forEach(function(b) {
        b.textContent = '⚙️\u00a0Konfigurieren';
    });
    if (!isOpen) {
        panel.style.display = '';
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        if (btn) btn.textContent = '✖\u00a0Schließen';
    }
}
</script>

