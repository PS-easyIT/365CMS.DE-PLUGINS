# CMS WordPress Importer – Datenbank-Referenz

## Tabellen-Übersicht

| Tabelle | Zweck |
|---------|-------|
| `cms_import_log` | Protokoll pro Import-Run |
| `cms_import_meta` | Nicht-gemappte Meta-Felder |
| `cms_import_items` | Persistentes Quell-/Ziel-Mapping für Re-Imports und Shortcode-Auflösung |

---

## `cms_import_log`

```sql
CREATE TABLE cms_import_log (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename          VARCHAR(500) NOT NULL,
    import_type       ENUM('posts','pages','mixed','other') DEFAULT 'mixed',
    total             INT UNSIGNED DEFAULT 0,
    imported          INT UNSIGNED DEFAULT 0,
    skipped           INT UNSIGNED DEFAULT 0,
    errors            INT UNSIGNED DEFAULT 0,
    images_downloaded INT UNSIGNED DEFAULT 0,
    meta_report_path  VARCHAR(500) DEFAULT NULL,
    user_id           INT UNSIGNED DEFAULT NULL,
    started_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    finished_at       TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_started (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

| Spalte | Beschreibung |
|--------|------------- |
| `filename` | Original-Dateiname der WXR-Datei |
| `import_type` | grobe Kennzeichnung des Runs |
| `total` / `imported` / `skipped` / `errors` | Ergebniszähler pro Run |
| `images_downloaded` | Anzahl erfolgreich geladener Bilder |
| `meta_report_path` | Relativer Pfad zum generierten Meta-Report |

---

## `cms_import_meta`

```sql
CREATE TABLE cms_import_meta (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    log_id     INT UNSIGNED NOT NULL,
    source_id  VARCHAR(50) NOT NULL,
    post_title VARCHAR(255) NOT NULL,
    post_type  VARCHAR(50) NOT NULL,
    meta_key   VARCHAR(255) NOT NULL,
    meta_value LONGTEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log (log_id),
    INDEX idx_key (meta_key(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## `cms_import_items`

```sql
CREATE TABLE cms_import_items (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    log_id           INT UNSIGNED DEFAULT NULL,
    source_type      VARCHAR(50) NOT NULL,
    source_wp_id     BIGINT UNSIGNED DEFAULT NULL,
    source_reference VARCHAR(191) DEFAULT NULL,
    source_slug      VARCHAR(255) DEFAULT NULL,
    source_url       VARCHAR(500) DEFAULT NULL,
    target_type      VARCHAR(50) NOT NULL,
    target_id        BIGINT UNSIGNED DEFAULT NULL,
    target_slug      VARCHAR(255) DEFAULT NULL,
    target_url       VARCHAR(500) DEFAULT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log (log_id),
    INDEX idx_source_wp (source_type, source_wp_id),
    INDEX idx_source_ref (source_type, source_reference),
    INDEX idx_target (target_type, target_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

`cms_import_items` speichert die Beziehung zwischen WordPress-Quelle und 365CMS-Ziel. Das wird verwendet für:

- Re-Importe / Duplikat-Erkennung
- Auflösung von `_thumbnail_id`-nahen Folgeimporten
- Umwandlung von WordPress-Tabellen-Shortcodes zu `site-table`-Shortcodes

---

## Import-Ziel-Tabellen

Das Plugin schreibt direkt in CMS-Core-Tabellen:

| Ziel-Tabelle | Herkunft |
|-------------|----------|
| `cms_posts` | WXR-Posts + Custom Post Types |
| `cms_pages` | WXR-Pages |
| `cms_site_tables` | TablePress-Exporte |
| `cms_media` | Geladene Bilder |
| `cms_post_categories` | WordPress-Kategorien |
| `cms_post_tags` / `cms_post_tag_rel` | WordPress-Tags |
| `cms_seo_meta` | strukturierte SEO-Daten |

### Feld-Mapping (WXR → cms_posts)

| WXR-Feld | cms_posts-Feld |
|----------|---------------|
| `<title>` | `title` |
| `<content:encoded>` | `content` |
| `<excerpt:encoded>` | `excerpt` |
| `<wp:post_date>` | `created_at`, `published_at` |
| `<wp:status>` | `status` (publish→published, draft→draft) |
| `<wp:post_name>` | `slug` |
| Kategorien | `category_id` |
| Tags | `tags` + native Tag-Relation |
| `_thumbnail_id` / Attachment | `featured_image` |
| `_yoast_wpseo_title` & Co. | `meta_title` |
| `_yoast_wpseo_metadesc` & Co. | `meta_description` |
