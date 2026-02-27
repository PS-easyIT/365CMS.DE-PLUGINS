---
applyTo: "*/member/**/*.php,*/views/member/**/*.php,*/includes/class-member*.php,*/includes/member/**/*.php"
---

# 365CMS Plugin – Member-Bereich Layout

> Ergänzt `member-default.instructions.md`. Regelt das visuelle Layout, Komponenten-Muster
> und CSS-Verwendung für alle Member-seitigen Plugin-Views.

---

## 1. Grundprinzip: Admin-Klassen im Member-Bereich

Der Member-Bereich nutzt **dieselben CSS-Klassen** wie der Admin-Bereich (`admin.css` wird auf Member-Seiten eingebunden), jedoch **ohne** Sidebar und ohne vollen Admin-Page-Header.

```
Member-Seite:
┌─────────────────────────────────────────────────────┐
│  CMS Member-Navigation / Header (vom Theme/CMS)     │
├─────────────────────────────────────────────────────┤
│  Plugin-Widget oder Inline-Seite:                   │
│                                                     │
│  [alerts]                                           │
│  [analytics-grid / stat-cards]                      │
│  [tab-navigation]                                   │
│  [admin-card mit Inhalt]                            │
│  [pagination / footer-actions]                      │
└─────────────────────────────────────────────────────┘
```

**Kein** `renderAdminSidebar()` – kein `<body class="admin-body">` im Member-Bereich.

---

## 2. Alerts

```php
<!-- Direkt am Anfang der View-Datei, vor allem anderen Inhalt -->
<?php if (!empty($bulkNotice)): ?>
<div class="alert alert-success">✅ <?php echo htmlspecialchars($bulkNotice); ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
```

---

## 3. Analytics-Grid (Kennzahlen-Karten)

Für Member-Dashboards mit mehreren KPIs ein **Auto-Fit-Grid** aus `admin-card`:

```php
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem;">

    <!-- Einfache Zahl-Karte -->
    <div class="admin-card" style="padding:1.25rem;text-align:center;margin-bottom:0;">
        <div style="font-size:1.75rem;font-weight:700;color:#3b82f6;">
            <?php echo number_format($totalViews); ?>
        </div>
        <div style="font-size:.8rem;color:#64748b;margin-top:.25rem;">👁️ Gesamt-Aufrufe</div>
    </div>

    <!-- Zahl-Karte mit Unterzeile -->
    <div class="admin-card" style="padding:1.25rem;text-align:center;margin-bottom:0;">
        <div style="font-size:1.75rem;font-weight:700;color:#10b981;">
            <?php echo $totalApps; ?>
        </div>
        <div style="font-size:.8rem;color:#64748b;margin-top:.25rem;">📬 Bewerbungen (30 Tage)</div>
        <?php if ($totalViews > 0 && $totalApps > 0): ?>
        <div style="font-size:.75rem;color:#94a3b8;margin-top:.2rem;">
            Conversion: <?php echo round($totalApps / $totalViews * 100, 1); ?>%
        </div>
        <?php endif; ?>
    </div>

</div>
```

**Regeln:**
- `margin-bottom:0` auf jeder Karte im Grid (das Grid hat eigenes Abstand mit `margin-bottom:1.5rem`)
- Zahlen-Farben: Blau `#3b82f6` (neutral), Grün `#10b981` (positiv), Rot `#ef4444` (Alarm)
- Keine Emojis als Hauptelement – Emoji in der Label-Zeile ist OK

---

## 4. Quota-/Fortschritts-Widget (SVG-Ringdiagramm)

```php
<?php
$circleR  = 28;
$circleC  = 2 * M_PI * $circleR;
$quotaPct = $quotaLimit > 0 ? min(100, (int)round($quotaUsed / $quotaLimit * 100)) : 0;
$dashVal  = round($circleC * $quotaPct / 100, 2);
$dashOff  = round($circleC, 2);
$strokeColor = $quotaPct >= 90 ? '#ef4444' : '#3b82f6';
?>
<div class="admin-card" style="padding:1.25rem;text-align:center;margin-bottom:0;">
    <?php if ($quotaLimit > 0): ?>
    <svg width="72" height="72" viewBox="0 0 72 72" aria-label="<?php echo $quotaUsed; ?> von <?php echo $quotaLimit; ?> genutzt">
        <circle cx="36" cy="36" r="<?php echo $circleR; ?>"
                fill="none" stroke="#e2e8f0" stroke-width="6"/>
        <circle cx="36" cy="36" r="<?php echo $circleR; ?>"
                fill="none" stroke="<?php echo $strokeColor; ?>" stroke-width="6"
                stroke-dasharray="<?php echo $dashVal; ?> <?php echo $dashOff; ?>"
                stroke-linecap="round"
                transform="rotate(-90 36 36)"/>
        <text x="36" y="41" text-anchor="middle"
              style="font-size:13px;font-weight:700;fill:#1e293b;">
            <?php echo $quotaPct; ?>%
        </text>
    </svg>
    <div style="font-size:.8rem;color:#64748b;margin-top:.25rem;">
        📦 <?php echo $quotaUsed; ?> / <?php echo $quotaLimit; ?> genutzt
    </div>
    <?php if ($quotaPct >= 90): ?>
    <div style="color:#ef4444;font-size:.75rem;margin-top:.15rem;">⚠️ Limit fast erreicht</div>
    <?php endif; ?>
    <?php else: ?>
    <div style="font-size:1.75rem;font-weight:700;color:#3b82f6;"><?php echo $quotaUsed; ?></div>
    <div style="font-size:.8rem;color:#64748b;margin-top:.25rem;">📦 Einträge</div>
    <?php endif; ?>
</div>
```

---

## 5. Mini-Balken-Chart (Trend 7 Tage)

```php
<?php
// Daten vorbereiten
$trendDays = [];
for ($i = 6; $i >= 0; $i--) {
    $d           = date('Y-m-d', strtotime("-{$i} days"));
    $trendDays[] = ['date' => date('d.m', strtotime($d)), 'cnt' => $trend7[$d] ?? 0];
}
$maxTrend = max(1, max(array_column($trendDays, 'cnt')));
?>
<div class="admin-card" style="padding:1.25rem;margin-bottom:0;">
    <div style="font-size:.75rem;font-weight:600;color:#475569;margin-bottom:.6rem;">
        📈 Trend (letzte 7 Tage)
    </div>
    <div style="display:flex;align-items:flex-end;gap:3px;height:40px;" role="img"
         aria-label="Trend-Diagramm der letzten 7 Tage">
        <?php foreach ($trendDays as $td):
            $barH = max(3, (int)round($td['cnt'] / $maxTrend * 40));
        ?>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;">
            <div style="width:100%;height:<?php echo $barH; ?>px;
                        background:<?php echo $td['cnt'] > 0 ? '#3b82f6' : '#e2e8f0'; ?>;
                        border-radius:2px;margin-top:<?php echo 40 - $barH; ?>px;"
                 title="<?php echo $td['date']; ?>: <?php echo $td['cnt']; ?>">
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div style="display:flex;justify-content:space-between;font-size:.65rem;color:#94a3b8;margin-top:.3rem;">
        <span><?php echo $trendDays[0]['date']; ?></span>
        <span><?php echo $trendDays[6]['date']; ?></span>
    </div>
</div>
```

---

## 6. Tab-Navigation (Member-Views)

Member-Views verwenden dasselbe `.tabs`/`.tab-btn`-Muster mit einem klaren Stil-Attribut
für den Tab-Container (da kein `admin.css` Tab-Nav-Stil vorhanden ist):

### 6.1 Dedizierte Tab-CSS-Klassen (wenn Plugin-Admin-CSS auch für Member geladen wird)

```php
<div class="[prefix]-tabs" style="margin-bottom:0;">
    <a href="?tab=overview" class="[prefix]-tab<?php echo $activeTab === 'overview' ? ' active' : ''; ?>">
        📊 Übersicht
    </a>
    <a href="?tab=settings" class="[prefix]-tab<?php echo $activeTab === 'settings' ? ' active' : ''; ?>">
        ⚙️ Einstellungen
    </a>
</div>
<div class="admin-card" style="border-radius:0 10px 10px 10px;margin-top:0;">
    <!-- Inhalt -->
</div>
```

### 6.2 Inline-Stil-Tabs (wenn kein Plugin-Admin-CSS geladen)

```php
<div style="display:flex;gap:.25rem;margin-bottom:1.5rem;border-bottom:2px solid #e2e8f0;flex-wrap:wrap;">
    <?php
    $tabs = [
        'overview' => ['icon' => '📊', 'label' => 'Übersicht'],
        'settings' => ['icon' => '⚙️', 'label' => 'Einstellungen'],
    ];
    foreach ($tabs as $key => $def):
        $isActive = $activeTab === $key;
    ?>
    <a href="?tab=<?php echo esc_attr($key); ?>"
       style="display:inline-flex;align-items:center;gap:.35rem;
              padding:.55rem 1.1rem;
              border-radius:6px 6px 0 0;
              text-decoration:none;
              font-size:.875rem;font-weight:600;
              border:2px solid transparent;border-bottom:none;
              margin-bottom:-2px;
              <?php echo $isActive
                  ? 'background:#fff;border-color:#e2e8f0;color:#1e293b;'
                  : 'color:#64748b;'; ?>">
        <?php echo $def['icon']; ?> <?php echo $def['label']; ?>
        <?php /* Optionaler Zähler-Badge */ ?>
        <?php if ($isActive && isset($counts[$key]) && $counts[$key] > 0): ?>
        <span style="background:#eff6ff;color:#3b82f6;
                     padding:.1rem .45rem;border-radius:10px;
                     font-size:.72rem;font-weight:700;">
            <?php echo $counts[$key]; ?>
        </span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>
```

---

## 7. Aktion-Header (Buttons + Bulk-Bar)

```php
<!-- Über der Listen-Tabelle -->
<div style="display:flex;justify-content:space-between;align-items:center;
            margin-bottom:1rem;flex-wrap:wrap;gap:.5rem;">
    <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
        <?php if ($canCreate): ?>
        <a href="<?php echo esc_attr($createUrl); ?>" class="btn btn-primary">➕ Neu erstellen</a>
        <?php endif; ?>
    </div>

    <?php if (!empty($items)): ?>
    <!-- Bulk-Aktions-Bar -->
    <div style="display:flex;gap:.5rem;align-items:center;" id="bulkBar">
        <select id="bulkSelect" class="form-control"
                style="width:auto;padding:.375rem .75rem;font-size:.875rem;">
            <option value="">Bulk-Aktion …</option>
            <option value="archive">📦 Archivieren</option>
            <option value="delete">🗑️ Löschen</option>
        </select>
        <button type="button" class="btn btn-secondary btn-sm" id="bulkApplyBtn"
                onclick="applyBulkAction()">Ausführen</button>
    </div>
    <?php endif; ?>
</div>
```

---

## 8. Listen-Tabelle (Member-Kontext)

```php
<div class="users-table-container">
    <form id="bulkForm" method="POST">
        <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars($csrf); ?>">
        <input type="hidden" name="action" value="bulk" id="bulkActionInput">

        <table class="users-table">
            <thead>
                <tr>
                    <th style="width:36px;">
                        <input type="checkbox" id="selectAll" title="Alle auswählen"
                               onchange="document.querySelectorAll('.row-cb').forEach(cb => cb.checked = this.checked); updateBulkBar();">
                    </th>
                    <th>Titel</th>
                    <th>Status</th>
                    <th>Erstellt</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td>
                    <input type="checkbox" name="ids[]" value="<?php echo (int)$item->id; ?>"
                           class="row-cb" onchange="updateBulkBar()">
                </td>
                <td>
                    <a href="<?php echo esc_attr($editUrl . $item->id); ?>"
                       style="font-weight:600;color:var(--admin-primary, #3b82f6);">
                        <?php echo htmlspecialchars($item->title); ?>
                    </a>
                </td>
                <td>
                    <span class="status-badge <?php echo htmlspecialchars($item->status); ?>">
                        <?php echo match($item->status) {
                            'published' => '✅ Veröffentlicht',
                            'draft'     => '📝 Entwurf',
                            'archived'  => '📦 Archiviert',
                            default     => htmlspecialchars($item->status),
                        }; ?>
                    </span>
                </td>
                <td><?php echo date('d.m.Y', strtotime($item->created_at)); ?></td>
                <td>
                    <div style="display:flex;gap:.35rem;">
                        <a href="<?php echo esc_attr($editUrl . $item->id); ?>"
                           class="btn btn-sm btn-secondary" title="Bearbeiten">✏️</a>
                        <button type="button"
                                class="btn btn-sm btn-danger"
                                onclick="openDeleteModal(<?php echo (int)$item->id; ?>, '<?php echo htmlspecialchars($item->title, ENT_QUOTES); ?>')"
                                title="Löschen">🗑️</button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </form>
</div>
```

---

## 9. Leer-Zustand (Member-Kontext)

```php
<?php if (empty($items)): ?>
<div class="admin-card">
    <div class="empty-state" style="padding:2.5rem 1.5rem;">
        <p style="font-size:2.5rem;margin:0;">📭</p>
        <p><strong>Noch keine Einträge vorhanden</strong></p>
        <p style="color:#64748b;font-size:.875rem;">
            Erstelle deinen ersten Eintrag über den Button oben.
        </p>
        <?php if ($canCreate): ?>
        <a href="<?php echo esc_attr($createUrl); ?>" class="btn btn-primary" style="margin-top:1rem;">
            ➕ Jetzt erstellen
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
```

---

## 10. Formulare im Member-Bereich

```php
<!-- Wizard-Formular mit JS-Tabs -->
<div class="tabs" style="margin-bottom:1.5rem;display:flex;gap:.3rem;
                          border-bottom:2px solid #e2e8f0;flex-wrap:wrap;">
    <button class="tab-btn active" onclick="switchTab('tab-basics', this)" type="button">
        📋 Basisdaten
    </button>
    <button class="tab-btn" onclick="switchTab('tab-details', this)" type="button">
        🔧 Details
    </button>
</div>

<form method="POST" id="mainForm" class="admin-form">
    <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars($csrf); ?>">

    <div id="tab-basics" class="tab-content active">
        <div class="admin-card">
            <h3>📋 Basisdaten</h3>

            <div class="form-group">
                <label class="form-label" for="field_title">
                    Titel <span style="color:#ef4444;">*</span>
                </label>
                <input type="text" id="field_title" name="title" class="form-control"
                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                       maxlength="255" required placeholder="Aussagekräftiger Titel…">
            </div>

            <!-- Zweispaltiges Layout für kurze Felder -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                <div class="form-group">
                    <label class="form-label">Kategorie</label>
                    <select name="category_id" class="form-control">
                        <option value="">— Keine —</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int)$cat->id; ?>"
                                <?php echo ((int)($_POST['category_id'] ?? 0) === (int)$cat->id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat->name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Standort</label>
                    <input type="text" name="location" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>"
                           placeholder="z. B. Berlin oder Remote">
                </div>
            </div>

        </div>
    </div>

    <!-- Sticky Save-Bar am Ende -->
    <div class="admin-card" style="position:sticky;bottom:1rem;z-index:10;
                                    padding:.875rem 1.25rem;border-top:2px solid #e2e8f0;">
        <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary">💾 Speichern</button>
            <a href="<?php echo esc_attr($backUrl); ?>" class="btn btn-secondary">← Zurück</a>
            <span style="color:#94a3b8;font-size:.8rem;margin-left:auto;">
                Änderungen werden erst nach dem Speichern übernommen.
            </span>
        </div>
    </div>
</form>
```

---

## 11. Paginierung

```php
<?php if ($currentPage > 1 || count($items) >= $perPage): ?>
<nav style="display:flex;gap:.5rem;justify-content:center;margin-top:1.5rem;flex-wrap:wrap;"
     aria-label="Seitennavigation">
    <?php if ($currentPage > 1): ?>
    <a href="?page=<?php echo $currentPage - 1; ?>&<?php echo http_build_query($filterParams); ?>"
       class="btn btn-secondary btn-sm" rel="prev">← Zurück</a>
    <?php endif; ?>

    <span style="padding:.375rem .875rem;color:#64748b;font-size:.875rem;align-self:center;">
        Seite <?php echo $currentPage; ?><?php echo $totalPages > 0 ? ' von ' . $totalPages : ''; ?>
    </span>

    <?php if ($totalPages <= 0 ? count($items) >= $perPage : $currentPage < $totalPages): ?>
    <a href="?page=<?php echo $currentPage + 1; ?>&<?php echo http_build_query($filterParams); ?>"
       class="btn btn-secondary btn-sm" rel="next">Weiter →</a>
    <?php endif; ?>
</nav>
<?php endif; ?>
```

---

## 12. Upgrade-/Limit-Hinweise

```php
<!-- Quota-Warnung (wenn Limit fast erreicht oder überschritten) -->
<?php if ($quotaLimit > 0 && $quotaUsed >= $quotaLimit): ?>
<div class="alert alert-error" style="margin-bottom:1.25rem;">
    ❌ <strong>Limit erreicht:</strong>
    Du hast <?php echo $quotaUsed; ?> von <?php echo $quotaLimit; ?> Einträgen genutzt.
    <a href="/member/subscription" style="color:inherit;font-weight:700;text-decoration:underline;margin-left:.5rem;">
        Jetzt upgraden →
    </a>
</div>
<?php elseif ($quotaLimit > 0 && ($quotaUsed / $quotaLimit) >= .9): ?>
<div class="alert" style="background:#fef3c7;color:#92400e;border-left:4px solid #d97706;">
    ⚠️ <strong>Limit fast erreicht:</strong>
    <?php echo $quotaUsed; ?> / <?php echo $quotaLimit; ?> Einträge genutzt.
</div>
<?php endif; ?>
```

---

## 13. CSS in Member-View-Dateien

- `admin.css` wird auf allen Member-Seiten eingebunden (liefert `.admin-card`, `.btn`, `.form-control`, `.alert`, `.users-table`, `.status-badge`, `.tab-btn`)
- Member-spezifische Erweiterungs-Styles: **immer in `assets/css/member.css`** oder `assets/css/style.css` auslagern
- **Keine** `<style>`-Blöcke in View-Dateien, auch nicht für einfache Hilfsklassen
- Inline-`style=`-Attribute: erlaubt für Layout-Einzel-Korrekturen (Grid, Flex, max-width), verboten für Farb-/Designwerte

---

## 14. Checkliste (Member-Views)

- [ ] Authentifizierungs-Check: `CMS\Auth::instance()->isLoggedIn()` am Dateianfang
- [ ] Eigentümer-Check vor DB-Operationen: `AND user_id = ?` in jeder Query
- [ ] CSRF-Token in jedem Formular und jeder POST-Aktion
- [ ] Alerts ganz am Anfang der View, vor Analytics/Content
- [ ] Analytics-Grid mit `margin-bottom:1.5rem` und `margin-bottom:0` auf Karten
- [ ] Quota-/Limit-Hinweis wenn `$quotaLimit > 0`
- [ ] Listen-Tabelle in `.users-table-container` (responsive)
- [ ] Keine `window.confirm()` – Modal für Löschbestätigungen
- [ ] Empty State mit `.empty-state` bei leeren Listen
- [ ] Paginierung mit `rel="prev"` / `rel="next"` und gefilterten GET-Parametern
- [ ] Sticky Save-Bar bei langen Formularen (`position:sticky;bottom:1rem;z-index:10`)
- [ ] Kein `<style>`-Block in der View-Datei
