# CMS Speakers – Datenbank-Referenz

## Tabellen-Übersicht

| Tabelle | Zweck |
|---------|-------|
| `cms_speakers` | Haupt-Profil-Datensätze |
| `cms_speaker_topics` | Speaker-Themengebiete |
| `cms_speaker_events` | Auftritte & Präsentationen |
| `cms_speaker_plugin_settings` | Legacy-/Fallback-Settings; primär wird `SettingsService` Gruppe `cms-speakers` genutzt |

---

## `cms_speakers`

```sql
CREATE TABLE cms_speakers (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id           INT UNSIGNED DEFAULT NULL,
    first_name        VARCHAR(100) NOT NULL DEFAULT '',
    last_name         VARCHAR(100) NOT NULL DEFAULT '',
    title             VARCHAR(100) DEFAULT NULL             COMMENT 'Akademischer Titel',
    gender            ENUM('','m','f','d') DEFAULT '',
    position          VARCHAR(200) DEFAULT NULL,
    company           VARCHAR(200) DEFAULT NULL,
    company_id        INT UNSIGNED DEFAULT NULL             COMMENT 'FK cms_companies',
    email             VARCHAR(150) NOT NULL DEFAULT '',
    phone             VARCHAR(60)  DEFAULT NULL,
    bio               LONGTEXT     DEFAULT NULL,
    short_bio         VARCHAR(600) DEFAULT NULL,
    photo_url         VARCHAR(600) DEFAULT NULL,
    location_city     VARCHAR(100) DEFAULT NULL,
    location_zip      VARCHAR(20)  DEFAULT NULL,
    location_country  VARCHAR(100) DEFAULT 'Deutschland',
    website           VARCHAR(600) DEFAULT NULL,
    linkedin          VARCHAR(600) DEFAULT NULL,
    twitter           VARCHAR(200) DEFAULT NULL,
    xing              VARCHAR(600) DEFAULT NULL,
    instagram         VARCHAR(200) DEFAULT NULL,
    youtube           VARCHAR(600) DEFAULT NULL,
    github            VARCHAR(600) DEFAULT NULL,
    gitlab            VARCHAR(600) DEFAULT NULL,
    languages         VARCHAR(400) DEFAULT NULL             COMMENT 'JSON-Array',
    formats           VARCHAR(400) DEFAULT NULL             COMMENT 'JSON-Array: keynote,workshop,...',
    target_audience   VARCHAR(400) DEFAULT NULL,
    speaking_style    VARCHAR(200) DEFAULT NULL,
    awards            TEXT         DEFAULT NULL,
    recognitions      TEXT         DEFAULT NULL COMMENT 'JSON-Array',
    skills            TEXT         DEFAULT NULL COMMENT 'JSON-Array',
    travel_radius     ENUM('local','regional','national','international','worldwide') DEFAULT 'national',
    max_audience_size INT UNSIGNED DEFAULT NULL,
    speaking_fee_min  DECIMAL(10,2) DEFAULT NULL,
    speaking_fee_max  DECIMAL(10,2) DEFAULT NULL,
    availability      ENUM('available','limited','booked') DEFAULT 'available',
    status            ENUM('active','inactive','draft','pending','deleted') DEFAULT 'active',
    is_featured       TINYINT(1)   DEFAULT 0,
    is_verified       TINYINT(1)   DEFAULT 0,
    profile_views     INT UNSIGNED DEFAULT 0,
    created_at        DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_status        (status),
    INDEX idx_availability  (availability),
    INDEX idx_featured      (is_featured),
    INDEX idx_city          (location_city),
    INDEX idx_company_id    (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## `cms_speaker_topics`

```sql
CREATE TABLE cms_speaker_topics (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    speaker_id INT UNSIGNED NOT NULL,
    topic_name VARCHAR(200) NOT NULL,
    topic_desc TEXT         DEFAULT NULL,
    sort_order INT          DEFAULT 0,
    created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_speaker (speaker_id),
    UNIQUE KEY unique_topic (speaker_id, topic_name(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## `cms_speaker_events`

```sql
CREATE TABLE cms_speaker_events (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    speaker_id      INT UNSIGNED NOT NULL,
    event_title     VARCHAR(300) NOT NULL,
    event_type      ENUM('keynote','workshop','panel','moderation','interview','webinar','conference','training','other') DEFAULT 'keynote',
    event_date      DATE         DEFAULT NULL,
    event_date_end  DATE         DEFAULT NULL,
    event_location  VARCHAR(300) DEFAULT NULL,
    presence_type   ENUM('presence','online','hybrid') DEFAULT 'presence',
    organizer_type  ENUM('company','cms_event','manual') DEFAULT 'manual',
    company_id      INT UNSIGNED DEFAULT NULL   COMMENT 'FK cms_companies',
    cms_event_id    INT UNSIGNED DEFAULT NULL   COMMENT 'FK cms_events',
    organizer_name  VARCHAR(300) DEFAULT NULL,
    topic           VARCHAR(400) DEFAULT NULL,
    description     TEXT         DEFAULT NULL,
    audience_size   INT UNSIGNED DEFAULT NULL,
    video_url       VARCHAR(600) DEFAULT NULL,
    slides_url      VARCHAR(600) DEFAULT NULL,
    event_url       VARCHAR(600) DEFAULT NULL,
    is_public       TINYINT(1)   DEFAULT 1,
    created_at      DATETIME     DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_speaker    (speaker_id),
    INDEX idx_event_date (event_date),
    INDEX idx_company    (company_id),
    INDEX idx_cms_event  (cms_event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Settings

Neue und aktualisierte Einstellungen werden in der Core-Tabelle `cms_settings` über `CMS\Services\SettingsService` mit dem Gruppenpräfix `cms-speakers.*` gespeichert.

Die Tabelle `cms_speaker_plugin_settings` bleibt für Migrationen und als Fallback bestehen:

```sql
CREATE TABLE cms_speaker_plugin_settings (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(255) NOT NULL UNIQUE,
    setting_value LONGTEXT DEFAULT NULL,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Entity-Relationship-Diagramm

```
cms_users (1)──(0..1) cms_speakers (1)──(M) cms_speaker_topics
                          │           (1)──(M) cms_speaker_events ──(0..1) cms_events
                          └──(0..1) cms_companies (company_id)
```

## Migrationen

Schema-Prüfungen nutzen `INFORMATION_SCHEMA.COLUMNS` mit Prepared Statements. Dadurch werden MySQL/MariaDB-Fehler durch `SHOW COLUMNS ... LIKE ?` vermieden und Spaltennamen nur aus internen Allow-Lists verarbeitet.
