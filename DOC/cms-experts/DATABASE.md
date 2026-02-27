# CMS Experts – Datenbank-Referenz

## Tabellen-Übersicht

| Tabelle | Zeilen (Typ) | Zweck |
|---------|------------|-------|
| `cms_experts` | Hauptdaten | Basis-Profilfelder |
| `cms_expert_meta` | Key-Value | 100+ flexible Felder |
| `cms_expert_skills` | Skill-Einträge | Taxonomie-Zuordnungen |
| `cms_expert_certifications` | Zertifikate | Zertifikat-Objekte |
| `cms_expert_projects` | Projekte | Referenz-Projekte |
| `cms_expert_education` | Ausbildung | Bildungsabschlüsse |
| `cms_expert_taxonomies` | Taxonomien | Fachrichtung-Def. |
| `cms_expert_skill_taxonomy` | Skill-Taxonomie | Skill-Kategorien |
| `cms_expert_skill_presets` | Skill-Presets | Vordefinierte Listen |
| `cms_expert_plugin_settings` | Settings | Plugin-Optionen |

---

## `cms_experts` (Haupttabelle)

```sql
CREATE TABLE cms_experts (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED DEFAULT NULL,
    first_name       VARCHAR(100) NOT NULL,
    last_name        VARCHAR(100) NOT NULL,
    email            VARCHAR(150) NOT NULL,
    phone            VARCHAR(50)  DEFAULT NULL,
    mobile           VARCHAR(50)  DEFAULT NULL,
    position         VARCHAR(255) DEFAULT NULL,
    company          VARCHAR(255) DEFAULT NULL,
    biography        TEXT         DEFAULT NULL,
    photo_url        VARCHAR(500) DEFAULT NULL,
    location_city    VARCHAR(100) DEFAULT NULL,
    location_zip     VARCHAR(20)  DEFAULT NULL,
    location_country VARCHAR(100) DEFAULT NULL,
    hourly_rate      DECIMAL(10,2) DEFAULT NULL,
    daily_rate       DECIMAL(10,2) DEFAULT NULL,
    availability     VARCHAR(50)  DEFAULT 'available',
    experience_years INT          DEFAULT 0,
    status           VARCHAR(20)  NOT NULL DEFAULT 'active',
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user_id     (user_id),
    INDEX idx_status      (status),
    INDEX idx_email       (email),
    INDEX idx_availability (availability),
    INDEX idx_city        (location_city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### `availability`-Werte

| Wert | Beschreibung |
|------|-------------|
| `available` | Sofort verfügbar |
| `limited` | Begrenzt verfügbar |
| `unavailable` | Nicht verfügbar |

---

## `cms_expert_meta` (Flexibel)

```sql
CREATE TABLE cms_expert_meta (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expert_id  INT UNSIGNED NOT NULL,
    meta_key   VARCHAR(255) NOT NULL,
    meta_value LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (expert_id) REFERENCES cms_experts(id) ON DELETE CASCADE,
    INDEX idx_expert     (expert_id),
    INDEX idx_meta_key   (meta_key),
    INDEX idx_expert_key (expert_id, meta_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### JSON-Repeater-Strukturen

**`programming_languages` / `frameworks` / `databases` / `cloud_platforms`:**
```json
[
  { "name": "PHP", "level": "expert" },
  { "name": "Python", "level": "advanced" }
]
```
Level: `beginner` | `intermediate` | `advanced` | `expert`

**`career_stations`:**
```json
[
  {
    "company": "Acme GmbH",
    "position": "Senior Developer",
    "from": "2020-01",
    "to": "2024-12",
    "location": "Berlin",
    "description": "..."
  }
]
```

**`testimonials`:**
```json
[
  {
    "quote": "Hervorragende Arbeit!",
    "client_name": "Max Mustermann",
    "client_position": "CTO",
    "client_company": "XYZ AG",
    "rating": 5
  }
]
```

**`case_studies`:**
```json
[{ "title": "...", "description": "...", "url": "..." }]
```

**`conference_talks`:**
```json
[{ "title": "...", "event": "...", "year": 2025, "video_url": "..." }]
```

---

## `cms_expert_skills`

```sql
CREATE TABLE cms_expert_skills (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expert_id        INT UNSIGNED NOT NULL,
    skill_name       VARCHAR(150) NOT NULL,
    skill_level      VARCHAR(50)  DEFAULT 'intermediate',
    years_experience INT          DEFAULT 0,
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (expert_id) REFERENCES cms_experts(id) ON DELETE CASCADE,
    INDEX idx_expert (expert_id),
    INDEX idx_skill  (skill_name),
    INDEX idx_level  (skill_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## `cms_expert_certifications`

```sql
CREATE TABLE cms_expert_certifications (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expert_id   INT UNSIGNED NOT NULL,
    cert_name   VARCHAR(255) NOT NULL,
    cert_issuer VARCHAR(255) DEFAULT NULL,
    cert_date   DATE         DEFAULT NULL,
    cert_expiry DATE         DEFAULT NULL,
    cert_url    VARCHAR(500) DEFAULT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (expert_id) REFERENCES cms_experts(id) ON DELETE CASCADE,
    INDEX idx_expert (expert_id),
    INDEX idx_date   (cert_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Entity-Relationship-Diagramm

```
cms_users (1)──(0..1) cms_experts (1)──(M) cms_expert_meta
                          │           (1)──(M) cms_expert_skills
                          │           (1)──(M) cms_expert_certifications
                          │           (1)──(M) cms_expert_projects
                          │           (1)──(M) cms_expert_education
                          └──(M)──(1) cms_companies (via cms_company_experts)
                          └──(M)──(1) cms_events    (via cms_event_speakers, type=expert)
                          └──(1)──(0..1) cms_speakers.expert_id
```
