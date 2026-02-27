---
applyTo: "*/admin/**/*.php,*/admin/views/**/*.php"
---

# 365CMS Plugin – Admin Design & Layout

> Ergänzt `admin-default.instructions.md`. Regelt das visuelle Design, CSS-Klassen-Verwendung
> und konkrete Layout-Muster für alle Plugin-Admin-Seiten.

---

## 1. Seitengerüst (View-Dateien)

Jede View-Datei in `admin/views/page-*.php` folgt diesem Grundgerüst – **kein** vollständiges HTML-Dokument (das liefert `renderAdminLayoutStart()`):

```php
<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<!-- 1. Page-Header -->
<div class="admin-page-header">
    <div>
        <h2>🔧 Seitenname</h2>
        <p>Einzeilige Beschreibung was hier verwaltet wird.</p>
    </div>
    <div class="header-actions">
        <a href="?action=new" class="btn btn-primary">➕ Neu erstellen</a>
    </div>
</div>

<!-- 2. Alerts direkt nach dem Header -->
<?php if (!empty($notice)): ?>
<div class="alert alert-success">✅ <?php echo htmlspecialchars($notice); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- 3. Inhalt in admin-card(s) -->
<div class="admin-card">
    <h3>📋 Abschnittstitel</h3>
    <!-- Inhalt -->
</div>
```

**Reihenfolge ist verpflichtend:** Header → Alerts → Tabs (falls vorhanden) → Inhalt.

---

## 2. Page-Header-Varianten

### 2.1 Standard (Titel + Beschreibung + Aktions-Button)

```php
<div class="admin-page-header">
    <div>
        <h2>📊 Dashboard</h2>
        <p>Gesamtübersicht aller Einträge und Aktivitäten</p>
    </div>
    <div class="header-actions">
        <a href="?tab=overview" class="btn btn-secondary btn-sm">👁️ Ansicht wechseln</a>
        <a href="?action=new" class="btn btn-primary">➕ Neu erstellen</a>
    </div>
</div>
```

### 2.2 Einfach (nur Titel, kein Button)

```php
<div class="admin-page-header">
    <div>
        <h2>⚙️ Einstellungen</h2>
        <p>Plugin-Konfiguration und Berechtigungen</p>
    </div>
</div>
```

**Regeln:**
- `h2` enthält **immer** ein Emoji als Präfix
- `p` maximal 1 Satz, max. 80 Zeichen, Farbe `#64748b`
- `header-actions`: maximal 3 Buttons; bei mehr → Dropdown
- Buttons in `header-actions`: erst sekundäre, zuletzt die primäre Aktion

---

## 3. Stat-Cards (Dashboard-Kennzahlen)

```php
<div class="dashboard-grid">
    <?php foreach ($stats as $key => [$icon, $label, $value]): ?>
    <div class="stat-card">
        <div class="stat-icon"><?php echo $icon; ?></div>
        <div class="stat-number"><?php echo number_format((int)$value); ?></div>
        <div class="stat-label"><?php echo htmlspecialchars($label); ?></div>
    </div>
    <?php endforeach; ?>
</div>
```

```css
/* In plugin-admin.css */
.stat-icon { font-size: 1.75rem; margin-bottom: .25rem; }
```

**Regeln:**
- `dashboard-grid` nutzt `repeat(auto-fit, minmax(180px, 1fr))` + `gap: 1.25rem`
- `stat-number`: `font-size: 2rem`, `font-weight: 700`, `color: var(--admin-primary)`
- `stat-label`: `font-size: .8rem`, `color: #94a3b8`, `text-transform: uppercase`, `letter-spacing: .04em`
- Das Emoji-Icon **nicht** mit `style="font-size:…"` direkt im HTML – in die plugin-CSS auslagern (Klasse `.stat-icon`)
- Stat-Cards **nur** für numerische KPIs auf Dashboard-Seiten

---

## 4. Tab-Navigation (Plugin-spezifisch)

Für Plugin-eigene Tabs eine dedizierte CSS-Klasse mit Plugin-Präfix verwenden – **nicht** `.tabs`/`.tab-btn` (das ist das globale Muster):

### 4.1 URL-basierte Tabs (Seitenneuladen, empfohlen)

```php
<!-- Tabs -->
<div class="[prefix]-tabs">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="?tab=<?php echo esc_attr($key); ?>"
       class="[prefix]-tab<?php echo $tab === $key ? ' active' : ''; ?>">
        <?php echo esc_html($label); ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Content-Card direkt anschließend, Border-Radius oben links entfernen -->
<div class="admin-card" style="border-radius:0 10px 10px 10px;margin-top:0;">
    <!-- Tab-Inhalt -->
</div>
```

```css
/* In plugin-admin.css */
.[prefix]-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 0;
}

.[prefix]-tab {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .6rem 1.1rem;
    font-size: .875rem;
    font-weight: 500;
    color: #475569;
    text-decoration: none;
    border: 1px solid transparent;
    border-bottom: 2px solid transparent;
    border-radius: 6px 6px 0 0;
    margin-bottom: -2px;
    transition: color .15s, background .15s;
}

.[prefix]-tab:hover {
    color: var(--admin-primary, #3b82f6);
    background: #f8fafc;
}

.[prefix]-tab.active {
    color: var(--admin-primary, #3b82f6);
    border-color: #e2e8f0 #e2e8f0 #fff #e2e8f0;
    background: #fff;
    font-weight: 600;
}
```

### 4.2 JavaScript-Tabs (ohne Reload, für Formulare)

```php
<div class="tabs" style="margin-bottom:1.5rem;display:flex;gap:.3rem;border-bottom:2px solid #e2e8f0;flex-wrap:wrap;">
    <button class="tab-btn active" onclick="switchTab('tab-general', this)" type="button">⚙️ Allgemein</button>
    <button class="tab-btn" onclick="switchTab('tab-advanced', this)" type="button">🔧 Erweitert</button>
</div>

<div id="tab-general"  class="tab-content active"><!-- … --></div>
<div id="tab-advanced" class="tab-content"><!-- … --></div>
```

```javascript
// admin.js enthält switchTab() bereits global
function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    btn.classList.add('active');
}
```

**Wann welche Variante:**
| Anwendungsfall | Variante |
|----------------|---------|
| Einstellungsseiten, viele Tabs | URL-basiert (`[prefix]-tabs`) |
| Wizard-Formulare (kein Seitenneuladen) | JS-Tabs (`.tab-btn`) |
| Very einfache 2–3 Tab-Toggle ohne URL | JS-Tabs |

---

## 5. Tabellen

```php
<div class="users-table-container">
    <table class="users-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Status</th>
                <th>Erstellt</th>
                <th>Aktionen</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
            <td>
                <a href="?edit=<?php echo (int)$item->id; ?>"
                   style="font-weight:600;color:var(--admin-primary);">
                    <?php echo htmlspecialchars($item->name); ?>
                </a>
            </td>
            <td>
                <span class="status-badge <?php echo $item->active ? 'active' : 'inactive'; ?>">
                    <?php echo $item->active ? 'Aktiv' : 'Inaktiv'; ?>
                </span>
            </td>
            <td><?php echo date('d.m.Y', strtotime($item->created_at)); ?></td>
            <td>
                <div style="display:flex;gap:.4rem;">
                    <a href="?edit=<?php echo (int)$item->id; ?>" class="btn btn-sm btn-secondary" title="Bearbeiten">✏️</a>
                    <button type="button"
                            class="btn btn-sm btn-danger"
                            onclick="openDeleteModal(<?php echo (int)$item->id; ?>, '<?php echo htmlspecialchars($item->name, ENT_QUOTES); ?>')"
                            title="Löschen">🗑️</button>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
```

**Regeln:**
- Immer in `.users-table-container` (overflow-x: auto für Mobile)
- Aktionen in der **letzten** Spalte, Buttons in Flex-Container `gap: .4rem`
- Löschen-Button öffnet **Modal**, kein `window.confirm()`
- Verlinkter Titeltext: `font-weight:600; color:var(--admin-primary)`
- Datum immer als `dd.mm.YYYY` formatieren

---

## 6. Badges & Status-Anzeigen

```php
<!-- Status (aktiv/inaktiv/danger) -->
<span class="status-badge active">✅ Aktiv</span>
<span class="status-badge inactive">⏸️ Inaktiv</span>
<span class="status-badge danger">❌ Abgelehnt</span>

<!-- Rollen-Badge -->
<span class="role-badge admin">Admin</span>
<span class="role-badge member">Mitglied</span>

<!-- Zähler-Badge im Nav oder Tab-Label -->
<span style="background:#eff6ff;color:#3b82f6;padding:.1rem .45rem;border-radius:10px;font-size:.72rem;font-weight:700;">
    <?php echo $count; ?>
</span>
```

**Farb-Schema:**

| Klasse / Zweck | Hintergrund | Textfarbe |
|----------------|-------------|-----------|
| `active` | `#d1fae5` | `#065f46` |
| `inactive` | `#f1f5f9` | `#64748b` |
| `danger` / abgelehnt | `#fee2e2` | `#991b1b` |
| `pending` / ausstehend | `#fef3c7` | `#92400e` |
| `admin` | `#fef3c7` | `#92400e` |
| `member` | `#dbeafe` | `#1e40af` |
| Zähler-Badge (blau) | `#eff6ff` | `#3b82f6` |

---

## 7. Schnellzugriff-Leiste (Dashboard)

```php
<div class="admin-card" style="padding:1rem 1.25rem;margin-bottom:1.5rem;">
    <h3 style="margin:0 0 .75rem;font-size:.85rem;color:#475569;font-weight:700;
               text-transform:uppercase;letter-spacing:.05em;">⚡ Schnellzugriff</h3>
    <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
        <a href="?action=new"    class="btn btn-secondary btn-sm">➕ Neuer Eintrag</a>
        <a href="?tab=settings"  class="btn btn-secondary btn-sm">⚙️ Einstellungen</a>
        <a href="?tab=approvals" class="btn btn-secondary btn-sm">
            ✅ Genehmigungen
            <?php if ($pendingCount > 0): ?>
            <span style="background:#fee2e2;color:#991b1b;padding:.1rem .4rem;
                         border-radius:10px;font-size:.7rem;font-weight:700;margin-left:.2rem;">
                <?php echo $pendingCount; ?>
            </span>
            <?php endif; ?>
        </a>
    </div>
</div>
```

---

## 8. Empty States

```php
<div class="empty-state">
    <p style="font-size:2.5rem;margin:0;">📭</p>
    <p><strong>Noch keine Einträge vorhanden</strong></p>
    <p style="color:#64748b;font-size:.875rem;">
        Erstelle den ersten Eintrag über den Button oben rechts.
    </p>
    <a href="?action=new" class="btn btn-primary" style="margin-top:1rem;">➕ Jetzt erstellen</a>
</div>
```

**Regeln:**
- Großes Emoji (2.5rem) als visuelles Signal
- 2-Zeiler: **Bold-Titel** + erklärender Satz in `#64748b`
- Optional: direkter CTA-Button
- **Niemals** Platzhalter-Daten oder Demo-Einträge anzeigen

---

## 9. Info-/Detail-Cards (zweispaltig)

```php
<div class="info-grid">
    <div class="info-card">
        <h4>📋 Basisdaten</h4>
        <ul class="info-list">
            <li><strong>Status:</strong> <?php echo htmlspecialchars($item->status); ?></li>
            <li><strong>Erstellt:</strong> <?php echo date('d.m.Y', strtotime($item->created_at)); ?></li>
            <li><strong>Bearbeitet:</strong> <?php echo date('d.m.Y H:i', strtotime($item->updated_at)); ?></li>
        </ul>
    </div>
    <div class="info-card">
        <h4>📊 Statistiken</h4>
        <ul class="info-list">
            <li><strong>Aufrufe:</strong> <?php echo number_format($stats->views); ?></li>
        </ul>
    </div>
</div>
```

---

## 10. Lösch-Bestätigungs-Modal (Standard-Pattern)

```php
<!-- Trigger in Tabellen-Aktion -->
<button type="button" class="btn btn-sm btn-danger"
        onclick="openDeleteModal(<?php echo (int)$id; ?>, '<?php echo htmlspecialchars($name, ENT_QUOTES); ?>')">
    🗑️
</button>

<!-- Modal (einmal pro Seite am Body-Ende) -->
<div id="deleteModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <h3>🗑️ Eintrag löschen</h3>
            <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p>Soll <strong id="deleteModalName"></strong> wirklich gelöscht werden?</p>
            <p style="color:#ef4444;font-size:.875rem;">⚠️ Diese Aktion kann nicht rückgängig gemacht werden.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Abbrechen</button>
            <form method="POST" id="deleteModalForm" style="display:inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteModalId">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <button type="submit" class="btn btn-danger">🗑️ Endgültig löschen</button>
            </form>
        </div>
    </div>
</div>

<script>
function openDeleteModal(id, name) {
    document.getElementById('deleteModalId').value   = id;
    document.getElementById('deleteModalName').textContent = name;
    openModal('deleteModal');
}
</script>
```

---

## 11. Plugin-spezifisches CSS (plugin-admin.css)

Jedes Plugin mit komplexerem Admin-Bereich bekommt eine eigene `assets/css/[plugin-slug]-admin.css`:

```
assets/css/
├── style.css            # Public Frontend (Archive + Card)
├── single.css           # Public Detail-Seite
└── [plugin-slug]-admin.css  # Admin-Bereich (nur wenn nötig)
```

**Was in die Plugin-Admin-CSS gehört:**
- Plugin-eigene Tab-Klassen (`.jpg-tabs`, `.jpg-tab`)
- Sortable-Listen, Drag&Drop-Indikatoren
- Spezifische Grid-Layouts für Karten-/Template-Übersichten
- `stat-icon`-Größe, spezifische Badge-Varianten

**Was NICHT in die Plugin-Admin-CSS gehört:**
- `.admin-card`, `.btn`, `.form-control`, `.alert`, `.users-table` → kommen aus `admin.css`
- `:root`-Variablen → werden von `admin.css` gesetzt

**CSS-Inline vs. Datei:**
- Einmalige dynamische Layout-Korrekturen (z. B. `border-radius:0 10px 10px 10px` für Tab-Content): Inline `style=""` OK
- Alles was sich wiederholt oder ein Pattern ist: in die CSS-Datei

---

## 12. Design-Anti-Patterns (Admin-Bereich)

### ❌ NIEMALS

```php
/* window.confirm() für Löschbestätigungen */
<button onclick="if(!confirm('Löschen?')) return; ...">

/* Inline-CSS für Designwerte die sich wiederholen */
<div style="background:#fff;border-radius:10px;padding:1.5rem;box-shadow:0 1px 4px rgba(0,0,0,.06);">
<!-- → stattdessen: class="admin-card" -->

/* <style>-Blöcke in View-Dateien */
<style>.meine-klasse { ... }</style>
<!-- → gehört in plugin-admin.css -->

/* Externe CDN-Links */
<link href="https://cdn.com/bootstrap.css" rel="stylesheet">

/* jQuery */
<script src="jquery.min.js"></script>
$('.btn').click(function() { ... });
```

### ✅ STATTDESSEN

```php
/* Modal für Bestätigungen */
<button onclick="openDeleteModal(<?= $id ?>, '<?= $name ?>')">🗑️</button>

/* CSS-Klassen verwenden */
<div class="admin-card">…</div>

/* CSS in Datei auslagern */
<!-- In plugin-admin.css: .jpg-tpl-card { border-radius:10px; … } -->

/* Vanilla JS */
document.querySelectorAll('.btn').forEach(btn => btn.addEventListener('click', handler));
```

---

## 13. Responsive (Admin-Seiten)

```css
/* Mobile: ≤ 960px → Sidebar eingeklappt (admin.css handled) */
/* Content-Anpassungen im Plugin-Admin-CSS */
@media (max-width: 960px) {
    .dashboard-grid { grid-template-columns: repeat(2, 1fr); }
    .[prefix]-tabs { overflow-x: auto; flex-wrap: nowrap; }
}

@media (max-width: 640px) {
    .dashboard-grid { grid-template-columns: 1fr; }
    .users-table-container { font-size: .8rem; }
    .admin-page-header { flex-direction: column; gap: .75rem; }
    .header-actions { width: 100%; }
    .header-actions .btn { width: 100%; justify-content: center; }
}
```

---

## 14. Checkliste vor dem Commit (Admin-Views)

- [ ] `admin-page-header` mit Emoji-`h2` und optionalem `p`-Beschreibungstext
- [ ] Alerts direkt nach dem Header, vor dem Inhalt
- [ ] Kein `window.confirm()` – immer Modal
- [ ] Lösch-Aktionen über POST-Formular mit CSRF-Token (kein GET)
- [ ] Kein `<style>`-Block in der View-Datei (statische CSS → plugin-admin.css)
- [ ] Kein jQuery, kein Bootstrap, keine externen CDN-Links
- [ ] Empty State mit `.empty-state` bei leeren Listen/Tabellen
- [ ] Tabellen-Actions: Löschen immer `btn-danger` + Modal, Bearbeiten `btn-secondary`
- [ ] Zähler-Badges in Tab-Labels wenn Anzahl relevant
- [ ] `esc_attr()` auf alle `href`-Attribute mit variablen Bestandteilen
