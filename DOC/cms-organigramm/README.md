# CMS Organigramm – Dokumentation

**Plugin:** `cms-organigramm`  
**Version:** 0.1.0 (In Entwicklung)  
**Status:** 🚧 In Entwicklung  
**Namespace:** `CMS365\Plugins\Organigramm`  
**Mindest-CMS-Version:** 365CMS 2.0+  
**PHP:** 8.2+

---

## Übersicht

Das **CMS Organigramm**-Plugin generiert interaktive Unternehmensstrukturen, indem es Daten aus `cms-companies`, `cms-experts` und `cms-jobprofile-generator` visell verknüpft und als interaktives Chart darstellt.

> **Wichtig:** Dieses Plugin befindet sich in der Entwicklungsphase. Die Implementierung folgt der detaillierten Planungsdokumentation in `cms-organigramm/DOC/`.

### Kernfunktionen (geplant)

| Bereich | Funktion |
|---------|----------|
| **Visual Builder** | Drag & Drop Canvas (reines Vanilla JS + SVG) |
| **Nodes** | Company-Nodes, Personen-Nodes (Experten), Vakante-Stellen-Nodes |
| **Hierarchie** | Parent-Child-Beziehungen in eigenen DB-Tabellen |
| **Cross-Plugin** | Lose Kopplung via `PluginManager::isPluginActive()` |
| **Zoom & Pan** | CSS `transform: scale` + Mouse-Events |
| **Expand/Collapse** | Äste ein-/ausklappen |
| **Export** | PNG / SVG / PDF-Export |
| **Einbettung** | Shortcode `[cms_organigramm id="N"]` |
| **Admin** | 5x5-Tab-Menüstruktur |

---

## Dateistruktur (geplant)

```
cms-organigramm/
├── cms-organigramm.php              # Singleton Bootstrap
├── DOC/                             # Planungsdokumentation
│   ├── INSTRUCTIONS.md
│   ├── TASKS.md
│   └── ...
├── includes/
│   ├── class-plugin.php             # Haupt-Singleton
│   ├── class-installer.php          # DB-Tabellen
│   ├── class-charts.php             # Chart-CRUD
│   ├── class-nodes.php              # Node-Verwaltung
│   ├── class-data-sources.php       # Cross-Plugin-Sync
│   └── class-member.php             # Member-Dashboard
├── admin/
│   ├── class-admin-menu.php
│   └── modules/
│       ├── trait-page-dashboard.php
│       ├── trait-page-builder.php
│       ├── trait-page-datasources.php
│       ├── trait-page-design.php
│       └── trait-page-settings.php
├── member/
├── templates/
│   └── embed-organigramm.php        # Shortcode-Output
└── assets/
    ├── css/
    │   └── builder.css              # CSS Custom Props
    └── js/
        └── builder.js               # Vanilla JS Editor
```

---

## Datenmodell

```sql
-- Organigramm-Definitionen
CREATE TABLE cms_organigramm_charts (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    title      VARCHAR(300) NOT NULL,
    status     ENUM('draft','published','archived') DEFAULT 'draft',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Nodes mit Hierarchie
CREATE TABLE cms_organigramm_nodes (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chart_id       INT UNSIGNED NOT NULL,
    parent_id      INT UNSIGNED DEFAULT NULL,   -- NULL = Root-Node
    reference_type ENUM('company','expert','job_profile','custom') NOT NULL,
    reference_id   INT UNSIGNED DEFAULT NULL,   -- FK zu Quell-Plugin
    custom_label   VARCHAR(300) DEFAULT NULL,
    custom_role    VARCHAR(200) DEFAULT NULL,
    sort_order     INT DEFAULT 0,
    pos_x          INT DEFAULT 0,               -- Canvas-Position
    pos_y          INT DEFAULT 0,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

---

## Cross-Plugin-Nodes

| `reference_type` | Datenquelle | Darstellung |
|-----------------|-------------|------------|
| `company` | `cms_companies` | Firmen-Box (Root oder Abteilung) |
| `expert` | `cms_experts` | Personen-Box (Foto, Name, Rolle) |
| `job_profile` | `cms_job_profiles` | Vakante-Stelle-Box (gestrichelte Umrandung) |
| `custom` | `cms_organigramm_nodes` | Freitext-Node (ohne Datenquelle) |

---

## Weitere Dokumente

| Dokument | Inhalt |
|----------|--------|
| [DATABASE.md](DATABASE.md) | Finale Tabellen-Schemas |
| [HOOKS.md](HOOKS.md) | Actions & Filter |
| [BUILDER.md](BUILDER.md) | Visual Builder – JS-Architektur |
| [CHANGELOG.md](CHANGELOG.md) | Versionshistorie |

---

## Entwicklungs-Ressourcen

Detaillierte Planungsdokumte befinden sich im Plugin-Verzeichnis:
- `cms-organigramm/DOC/INSTRUCTIONS.md` – Technische Anforderungen
- `cms-organigramm/DOC/TASKS.md` – Master-Projektliste
