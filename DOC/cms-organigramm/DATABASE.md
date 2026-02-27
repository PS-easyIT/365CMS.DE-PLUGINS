# Datenbank-Schema – cms-organigramm

> Dokumentiert alle geplanten Datenbanktabellen für das cms-organigramm Plugin.

**Status:** Plugin in Entwicklung (v0.1.0 geplant)

---

## Tabellen-Übersicht

| Tabelle | Zweck |
|---------|-------|
| `cms_organigramm_charts` | Organigramm-Konfigurationen |
| `cms_organigramm_nodes` | Knoten / Box-Elemente |
| `cms_organigramm_edges` | Verbindungen zwischen Knoten |
| `cms_organigramm_meta` | Zusätzliche Key-Value-Metadaten |

---

## cms_organigramm_charts

Speichert eine Organigramm-Konfiguration (z. B. für ein Unternehmen).

```sql
CREATE TABLE IF NOT EXISTS cms_organigramm_charts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id   INT UNSIGNED DEFAULT NULL COMMENT 'FK cms_companies.id (optional)',
    title        VARCHAR(255) NOT NULL,
    slug         VARCHAR(255) NOT NULL UNIQUE,
    description  TEXT DEFAULT NULL,
    layout       VARCHAR(50)  NOT NULL DEFAULT 'vertical'
                     COMMENT 'vertical | horizontal | radial',
    status       VARCHAR(20)  NOT NULL DEFAULT 'draft'
                     COMMENT 'draft | published | archived',
    is_public    TINYINT(1)   NOT NULL DEFAULT 0
                     COMMENT '1 = öffentlich abrufbar per Slug',
    settings     JSON         DEFAULT NULL
                     COMMENT 'Darstellungsoptionen (Farben, Fonts, Abstände etc.)',
    created_by   INT UNSIGNED DEFAULT NULL COMMENT 'FK cms_users.id',
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company (company_id),
    INDEX idx_status  (status),
    INDEX idx_public  (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Felder

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `id` | INT UNSIGNED | Primärschlüssel |
| `company_id` | INT UNSIGNED | Optionale Verknüpfung mit cms-companies |
| `title` | VARCHAR(255) | Anzeigename des Organigramms |
| `slug` | VARCHAR(255) | URL-freundlicher eindeutiger Bezeichner |
| `description` | TEXT | Optionale Beschreibung |
| `layout` | VARCHAR(50) | `vertical`, `horizontal`, `radial` |
| `status` | VARCHAR(20) | `draft`, `published`, `archived` |
| `is_public` | TINYINT(1) | Öffentlich abrufbar (Einbettung / Shortcode) |
| `settings` | JSON | Optionen: Farben, Fonts, Abstände, etc. |
| `created_by` | INT UNSIGNED | Ersteller (User-ID) |

---

## cms_organigramm_nodes

Einzelne Knoten (Boxen) im Organigramm.

```sql
CREATE TABLE IF NOT EXISTS cms_organigramm_nodes (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chart_id       INT UNSIGNED NOT NULL,
    parent_id      INT UNSIGNED DEFAULT NULL COMMENT 'NULL = Root-Knoten',
    node_type      VARCHAR(50)  NOT NULL DEFAULT 'position'
                       COMMENT 'position | department | person | custom',
    reference_type VARCHAR(50)  DEFAULT NULL
                       COMMENT 'company | expert | job_profile | custom | NULL',
    reference_id   INT UNSIGNED DEFAULT NULL
                       COMMENT 'FK zur referenzierten Entität (optional)',
    label          VARCHAR(255) NOT NULL COMMENT 'Anzeigename',
    sublabel       VARCHAR(255) DEFAULT NULL COMMENT 'z. B. Berufsbezeichnung',
    description    TEXT         DEFAULT NULL,
    photo_url      VARCHAR(500) DEFAULT NULL,
    sort_order     INT          NOT NULL DEFAULT 0,
    style          JSON         DEFAULT NULL COMMENT 'Node-spezifische Darstellungsoptionen',
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (chart_id) REFERENCES cms_organigramm_charts(id) ON DELETE CASCADE,
    INDEX idx_chart  (chart_id),
    INDEX idx_parent (parent_id),
    INDEX idx_ref    (reference_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Felder

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `id` | INT UNSIGNED | Primärschlüssel |
| `chart_id` | INT UNSIGNED | Zugehöriges Organigramm (CASCADE DELETE) |
| `parent_id` | INT UNSIGNED | NULL = Root-Knoten, sonst Eltern-Node-ID |
| `node_type` | VARCHAR(50) | `position`, `department`, `person`, `custom` |
| `reference_type` | VARCHAR(50) | Typ der verknüpften Entität |
| `reference_id` | INT UNSIGNED | ID der verknüpften Entität |
| `label` | VARCHAR(255) | Primärer Anzeigetext |
| `sublabel` | VARCHAR(255) | Sekundärer Text (z. B. Berufsbezeichnung) |
| `photo_url` | VARCHAR(500) | Foto-URL (Person oder Abteilung) |
| `sort_order` | INT | Reihenfolge unter gleichem Parent |
| `style` | JSON | Node-individuelle Stile (override) |

### `reference_type`-Werte

| Wert | Verknüpfung | Voraussetzung |
|------|------------|---------------|
| `company` | `cms_companies.id` | cms-companies aktiv |
| `expert` | `cms_experts.id` | cms-experts aktiv |
| `job_profile` | `cms_job_profiles.id` | cms-jobprofile-generator aktiv |
| `custom` | Kein FK – nur Label/sublabel | — |
| `NULL` | Keine Referenz | — |

---

## cms_organigramm_edges

Explizite Verbindungen (für nicht-hierarchische Graphen oder Zusatz-Relationen).

```sql
CREATE TABLE IF NOT EXISTS cms_organigramm_edges (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chart_id     INT UNSIGNED NOT NULL,
    source_id    INT UNSIGNED NOT NULL COMMENT 'FK cms_organigramm_nodes.id',
    target_id    INT UNSIGNED NOT NULL COMMENT 'FK cms_organigramm_nodes.id',
    edge_type    VARCHAR(50)  NOT NULL DEFAULT 'reports_to'
                     COMMENT 'reports_to | dotted_line | project | custom',
    label        VARCHAR(255) DEFAULT NULL,
    style        JSON         DEFAULT NULL,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chart_id)  REFERENCES cms_organigramm_charts(id) ON DELETE CASCADE,
    FOREIGN KEY (source_id) REFERENCES cms_organigramm_nodes(id)  ON DELETE CASCADE,
    FOREIGN KEY (target_id) REFERENCES cms_organigramm_nodes(id)  ON DELETE CASCADE,
    INDEX idx_chart  (chart_id),
    INDEX idx_source (source_id),
    INDEX idx_target (target_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Hinweis:** Bei rein hierarchischen Organigrammen (tree via `parent_id`) wird diese Tabelle nicht benötigt. Sie erweitert das Schema für Dotted-Line-Beziehungen etc.

---

## cms_organigramm_meta

```sql
CREATE TABLE IF NOT EXISTS cms_organigramm_meta (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chart_id   INT UNSIGNED NOT NULL,
    meta_key   VARCHAR(100) NOT NULL,
    meta_value LONGTEXT     DEFAULT NULL,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chart_id) REFERENCES cms_organigramm_charts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_chart_meta (chart_id, meta_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## ER-Diagramm

```
cms_organigramm_charts
    │
    ├──< cms_organigramm_nodes (chart_id)
    │        │
    │        └── parent_id → cms_organigramm_nodes.id (self-reference)
    │        └── reference_type + reference_id → [cms_companies | cms_experts | cms_job_profiles]
    │
    ├──< cms_organigramm_edges (chart_id)
    │        ├── source_id → cms_organigramm_nodes.id
    │        └── target_id → cms_organigramm_nodes.id
    │
    └──< cms_organigramm_meta (chart_id)
```

---

## Migrations-Beispiel

```php
public function create_tables(): void
{
    $db     = CMS\Database::instance();
    $pdo    = $db->getPdo();
    $prefix = $db->prefix();

    // Charts-Tabelle
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}organigramm_charts (
        id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        company_id   INT UNSIGNED DEFAULT NULL,
        title        VARCHAR(255) NOT NULL,
        slug         VARCHAR(255) NOT NULL UNIQUE,
        layout       VARCHAR(50)  NOT NULL DEFAULT 'vertical',
        status       VARCHAR(20)  NOT NULL DEFAULT 'draft',
        is_public    TINYINT(1)   NOT NULL DEFAULT 0,
        settings     JSON         DEFAULT NULL,
        created_by   INT UNSIGNED DEFAULT NULL,
        created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        updated_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_company (company_id),
        INDEX idx_status  (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Nodes-Tabelle
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}organigramm_nodes (
        id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        chart_id       INT UNSIGNED NOT NULL,
        parent_id      INT UNSIGNED DEFAULT NULL,
        node_type      VARCHAR(50)  NOT NULL DEFAULT 'position',
        reference_type VARCHAR(50)  DEFAULT NULL,
        reference_id   INT UNSIGNED DEFAULT NULL,
        label          VARCHAR(255) NOT NULL,
        sublabel       VARCHAR(255) DEFAULT NULL,
        photo_url      VARCHAR(500) DEFAULT NULL,
        sort_order     INT          NOT NULL DEFAULT 0,
        style          JSON         DEFAULT NULL,
        created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        updated_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_chart (chart_id),
        INDEX idx_ref   (reference_type, reference_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
```
