# CMS WordPress Importer – Datenbank-Referenz

## Tabellen-Übersicht

| Tabelle | Zweck |
|---------|-------|
| `cms_import_log` | Protokoll pro Import-Run |
| `cms_import_meta` | Nicht-gemappte Meta-Felder |

---

## `cms_import_log`

```sql
CREATE TABLE cms_import_log (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename    VARCHAR(500) NOT NULL,
    started_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    finished_at DATETIME     DEFAULT NULL,
    status      ENUM('running','completed','failed') DEFAULT 'running',
    posts_total INT UNSIGNED DEFAULT 0,
    posts_ok    INT UNSIGNED DEFAULT 0,
    posts_fail  INT UNSIGNED DEFAULT 0,
    pages_total INT UNSIGNED DEFAULT 0,
    pages_ok    INT UNSIGNED DEFAULT 0,
    pages_fail  INT UNSIGNED DEFAULT 0,
    error_msg   TEXT         DEFAULT NULL,
    report_path VARCHAR(600) DEFAULT NULL   COMMENT 'Pfad zum Markdown-Report',
    created_by  INT UNSIGNED DEFAULT NULL   COMMENT 'FK cms_users'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

| Spalte | Beschreibung |
|--------|------------- |
| `filename` | Original-Dateiname der WXR-Datei |
| `status` | `running` während des Imports, `completed`/`failed` danach |
| `posts_ok` / `posts_fail` | Erfolgreich / fehlgeschlagen importierte Posts |
| `report_path` | Relativer Pfad zum generierten Meta-Report |

---

## `cms_import_meta`

```sql
CREATE TABLE cms_import_meta (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    log_id     INT UNSIGNED NOT NULL  COMMENT 'FK cms_import_log',
    post_id    INT UNSIGNED DEFAULT NULL,
    meta_key   VARCHAR(500) NOT NULL,
    meta_value LONGTEXT     DEFAULT NULL,
    created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_log      (log_id),
    INDEX idx_meta_key (meta_key(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Import-Ziel-Tabellen

Das Plugin schreibt direkt in CMS-Core-Tabellen:

| Ziel-Tabelle | Herkunft |
|-------------|----------|
| `cms_posts` | WXR-Posts + Custom Post Types |
| `cms_pages` | WXR-Pages |

### Feld-Mapping (WXR → cms_posts)

| WXR-Feld | cms_posts-Feld |
|----------|---------------|
| `<title>` | `title` |
| `<content:encoded>` | `content` |
| `<excerpt:encoded>` | `excerpt` |
| `<wp:post_date>` | `created_at` |
| `<wp:post_modified>` | `updated_at` |
| `<wp:status>` | `status` (publish→published, draft→draft) |
| `<wp:post_type>` | `post_type` |
| `<wp:post_name>` | `slug` |
| Kategorien + Tags | `tags` (kommagetrennt) |
| `_yoast_wpseo_title` | `meta_title` |
| `_yoast_wpseo_metadesc` | `meta_description` |
