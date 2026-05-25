# CMS Events – Datenbank-Referenz

## Tabellen-Übersicht

| Tabelle | Zweck |
|---------|-------|
| `cms_events` | Haupt-Event-Datensätze |
| `cms_event_speakers` | M2M-Relation Event ↔ Speaker/Experte |
| `cms_event_meta` | Flexible Zusatz-Metadaten |
| `cms_event_categories` | Kategorie-Präsets |
| `cms_event_tag_presets` | Tag-Vorlagen |
| `cms_event_settings` | Legacy-Fallback für Archiv-/Design-Settings; primärer Speicher ist `cms_settings` via `CMS\Services\SettingsService` |

---

## `cms_events`

```sql
CREATE TABLE cms_events (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED DEFAULT NULL,
    title            VARCHAR(255) NOT NULL,
    excerpt          VARCHAR(500) DEFAULT NULL,
    description      TEXT         DEFAULT NULL,
    event_date       DATE         NOT NULL,
    event_time       TIME         DEFAULT NULL,
    end_date         DATE         DEFAULT NULL,
    end_time         TIME         DEFAULT NULL,
    location         VARCHAR(255) DEFAULT NULL,
    address          TEXT         DEFAULT NULL,
    city             VARCHAR(100) DEFAULT NULL,
    zip              VARCHAR(20)  DEFAULT NULL,
    country          VARCHAR(100) DEFAULT 'Deutschland',
    category         VARCHAR(100) DEFAULT NULL,
    tags             TEXT         DEFAULT NULL  COMMENT 'JSON-Array',
    capacity         INT UNSIGNED DEFAULT NULL,
    registration_url VARCHAR(500) DEFAULT NULL,
    price_type       ENUM('free','paid','donation') DEFAULT 'free',
    price            DECIMAL(10,2) DEFAULT NULL,
    price_currency   VARCHAR(10)  DEFAULT 'EUR',
    image_url        VARCHAR(500) DEFAULT NULL,
    banner_url       VARCHAR(500) DEFAULT NULL,
    is_online        BOOLEAN      DEFAULT FALSE,
    online_url       VARCHAR(500) DEFAULT NULL,
    is_featured      BOOLEAN      DEFAULT FALSE,
    organizer_name   VARCHAR(255) DEFAULT NULL,
    organizer_email  VARCHAR(150) DEFAULT NULL,
    organizer_phone  VARCHAR(50)  DEFAULT NULL,
    organizer_website VARCHAR(500) DEFAULT NULL,
    status           VARCHAR(20)  NOT NULL DEFAULT 'published',
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_status   (status),
    INDEX idx_date     (event_date),
    INDEX idx_category (category),
    INDEX idx_city     (city),
    INDEX idx_featured (is_featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Migrationen

- Neue Spalten werden über `INFORMATION_SCHEMA.COLUMNS` geprüft und anschließend per `ALTER TABLE` ergänzt.
- Foreign Keys werden erst nach erfolgreicher Tabellen-/Spaltenprüfung gesetzt und sind präfixsicher benannt.
- FK-Fehler blockieren den Installer nicht, sondern werden über `error_log()` protokolliert.

### Felder

| Spalte | Typ | Pflicht | Beschreibung |
|--------|-----|---------|--------------|
| `id` | INT UNSIGNED | AUTO | Primärschlüssel |
| `user_id` | INT UNSIGNED | Nein | FK cms_users (Ersteller) |
| `title` | VARCHAR(255) | **Ja** | Event-Titel |
| `excerpt` | VARCHAR(500) | Nein | Kurzbeschreibung (max. 500 Zeichen) |
| `description` | TEXT | Nein | Vollständige Beschreibung (HTML) |
| `event_date` | DATE | **Ja** | Startdatum |
| `event_time` | TIME | Nein | Startzeit |
| `end_date` | DATE | Nein | Enddatum |
| `end_time` | TIME | Nein | Endzeit |
| `location` | VARCHAR(255) | Nein | Veranstaltungsort (Name) |
| `address` | TEXT | Nein | Vollständige Adresse |
| `city` | VARCHAR(100) | Nein | Stadt |
| `country` | VARCHAR(100) | Nein | Land (Standard: Deutschland) |
| `category` | VARCHAR(100) | Nein | Kategorie-Slug |
| `tags` | TEXT | Nein | JSON-Array von Tag-Strings |
| `capacity` | INT UNSIGNED | Nein | Max. Teilnehmeranzahl |
| `registration_url` | VARCHAR(500) | Nein | Anmeldungs-URL |
| `price_type` | ENUM | Nein | `free`, `paid`, `donation` |
| `price` | DECIMAL(10,2) | Nein | Preis (bei `paid`) |
| `image_url` | VARCHAR(500) | Nein | Teaser-Bild |
| `banner_url` | VARCHAR(500) | Nein | Banner-Bild |
| `is_online` | BOOLEAN | Nein | Online-Event-Flag |
| `online_url` | VARCHAR(500) | Nein | Meeting-/Stream-Link |
| `is_featured` | BOOLEAN | Nein | Featured-Flag für Homepage |
| `organizer_*` | VARCHAR | Nein | Veranstalter-Infos (manuell) |
| `status` | VARCHAR(20) | **Ja** | `published`, `draft`, `cancelled`, `archived` |

---

## `cms_event_speakers`

```sql
CREATE TABLE cms_event_speakers (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id           INT UNSIGNED NOT NULL,
    speaker_id         INT UNSIGNED NOT NULL,
    speaker_type       ENUM('speaker','expert') DEFAULT 'speaker',
    role               VARCHAR(100) DEFAULT NULL,
    presentation_title VARCHAR(255) DEFAULT NULL,
    session_time       TIME         DEFAULT NULL,
    created_at         TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_event   (event_id),
    INDEX idx_speaker (speaker_id),
    INDEX idx_type    (speaker_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Optionaler FK bei kompatibler Zielumgebung:

```sql
ALTER TABLE cms_event_speakers
    ADD CONSTRAINT fk_cms_events_speakers_event
    FOREIGN KEY (event_id) REFERENCES cms_events(id)
    ON DELETE CASCADE;
```

| Spalte | Beschreibung |
|--------|--------------|
| `speaker_id` | FK zu `cms_speakers.id` oder `cms_experts.id` |
| `speaker_type` | `speaker` = aus `cms-speakers`, `expert` = aus `cms-experts` |
| `role` | Rolle bei diesem Event (Keynote-Speaker, Moderator, …) |
| `presentation_title` | Titel des Vortrags |
| `session_time` | Uhrzeit des Vortrags |

---

## `cms_event_categories`

```sql
CREATE TABLE cms_event_categories (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) NOT NULL,
    slug       VARCHAR(150) NOT NULL UNIQUE,
    icon       VARCHAR(10)  DEFAULT '📂',
    sort_order INT          DEFAULT 0,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## `cms_event_settings` – Legacy-Fallback

Neue Settings werden über `CMS\Services\SettingsService` in der Core-Tabelle `cms_settings` gespeichert. Die Gruppe lautet `cms-events`, daraus entstehen Optionsnamen wie `cms-events.archive_title` oder `cms-events.color_primary`.

Die Plugin-eigene Tabelle bleibt als Abwärtskompatibilitäts-Fallback erhalten, damit bestehende Installationen alte Werte weiter lesen können.

```sql
CREATE TABLE cms_event_settings (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Wichtige Settings

| Key | Zweck |
|-----|-------|
| `archive_title`, `archive_description`, `archive_slug`, `per_page` | Archiv-Verhalten |
| `color_primary`, `color_accent`, `color_card_bg`, `color_card_border` | Frontend-Farben |
| `color_badge_*` | Status-, Featured- und Online-Badge-Farben |
| `show_status_badge`, `show_featured_badge`, `show_online_badge` | Badge-Ausgabe |
| `show_category`, `show_city`, `show_capacity`, `show_price`, `show_tags` | Karten-Pills |

### Standard-Kategorien (Seeding)

| Name | Slug | Icon |
|------|------|------|
| Konferenz | konferenz | 🎤 |
| Workshop | workshop | 🛠️ |
| Webinar | webinar | 💻 |
| Networking | networking | 🤝 |
| Messe | messe | 🏛️ |
| Schulung | schulung | 📚 |

---

## Entity-Relationship-Diagramm

```
cms_users (1)────(0..N) cms_events (1)────(M) cms_event_speakers (M)────(1) cms_speakers
                            │                                        └────(1) cms_experts
                            └──(1)────(M) cms_event_meta
                            └──(N)────(1) cms_event_categories

cms_event_settings (Key/Value, pluginweit)
```
