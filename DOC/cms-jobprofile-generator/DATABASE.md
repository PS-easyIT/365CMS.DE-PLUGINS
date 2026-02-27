# Datenbank-Schema – CMS Job Profile Generator

> Alle Tabellennamen verwenden das dynamische CMS-Präfix via `$db->getPrefix()`.  
> Beispiel: Präfix `cms_` → Tabelle `cms_jpg_profiles`.

---

## Inhaltsverzeichnis

1. [Übersicht aller Tabellen](#übersicht-aller-tabellen)
2. [jpg_profiles](#jpg_profiles)
3. [jpg_profile_tasks](#jpg_profile_tasks)
4. [jpg_profile_requirements](#jpg_profile_requirements)
5. [jpg_profile_benefits](#jpg_profile_benefits)
6. [jpg_text_modules](#jpg_text_modules)
7. [jpg_skills](#jpg_skills)
8. [jpg_profile_skills](#jpg_profile_skills)
9. [jpg_benefits](#jpg_benefits)
10. [jpg_job_categories](#jpg_job_categories)
11. [jpg_templates](#jpg_templates)
12. [jpg_settings](#jpg_settings)
13. [jpg_stats](#jpg_stats)
14. [jpg_requirement_items](#jpg_requirement_items)
15. [jpg_company_default_benefits](#jpg_company_default_benefits)
16. [jpg_workflow_steps](#jpg_workflow_steps)
17. [jpg_workflow_history](#jpg_workflow_history)
18. [Entity-Relationship-Diagramm](#entity-relationship-diagramm)

---

## Übersicht aller Tabellen

| Tabelle | Zeilen (Seed) | Beschreibung |
|---|---|---|
| `jpg_profiles` | – | Kern-Tabelle: ein Datensatz pro Stellenprofil |
| `jpg_profile_tasks` | – | 1:n – Aufgaben eines Profils |
| `jpg_profile_requirements` | – | 1:n – Anforderungen eines Profils |
| `jpg_profile_benefits` | – | m:n – Profil ↔ Benefit |
| `jpg_text_modules` | – | Bibliothek wiederverwendbarer Texte |
| `jpg_skills` | 11 | Skill-Matrix mit Gruppen |
| `jpg_profile_skills` | – | m:n – Profil ↔ Skill |
| `jpg_benefits` | 12 | Benefit-Katalog mit SVG-Icon |
| `jpg_job_categories` | 8 | Stellenkategorien (Abteilungen) |
| `jpg_templates` | 3 | PDF/Web/E-Mail-Templates |
| `jpg_settings` | – | Plugin-Key-Value-Konfiguration |
| `jpg_stats` | – | DSGVO-konformes View-Tracking |
| `jpg_requirement_items` | – | Bibliothek: eigene Anforderungs-Bausteine |
| `jpg_company_default_benefits` | – | Standard-Benefits pro Unternehmen (m:n) |
| `jpg_workflow_steps` | – | Konfigurierbare Genehmigungsschritte |
| `jpg_workflow_history` | – | Audit-Log aller Workflow-Aktionen |

---

## jpg_profiles

Die zentrale Tabelle für alle Stellenprofile.

```sql
CREATE TABLE `{prefix}jpg_profiles` (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED NOT NULL,
    category_id     INT UNSIGNED DEFAULT NULL,
    slug            VARCHAR(255) NOT NULL,
    title           VARCHAR(255) NOT NULL,
    status          ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    employment_type VARCHAR(50)  DEFAULT NULL,  -- 'fulltime','parttime','contract','internship'
    remote_option   VARCHAR(20)  DEFAULT NULL,  -- 'onsite','hybrid','remote'
    location        VARCHAR(255) DEFAULT NULL,
    experience_lvl  VARCHAR(50)  DEFAULT NULL,  -- 'entry','mid','senior','lead'
    salary_min      DECIMAL(10,2) DEFAULT NULL,
    salary_max      DECIMAL(10,2) DEFAULT NULL,
    salary_currency CHAR(3)      DEFAULT 'EUR',
    summary         TEXT         DEFAULT NULL,   -- max. ~500 Zeichen (Teaser)
    description     LONGTEXT     DEFAULT NULL,   -- SunEditor HTML
    views           INT UNSIGNED NOT NULL DEFAULT 0,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  KEY uq_slug (slug),
    KEY     idx_status (status),
    KEY     idx_user   (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## jpg_profile_tasks

Sortierbare Aufgabenliste (Tätigkeitsbeschreibung).

```sql
CREATE TABLE `{prefix}jpg_profile_tasks` (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    profile_id  INT UNSIGNED NOT NULL,
    description TEXT         NOT NULL,
    sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_profile (profile_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

> Beim Speichern (Tab „Aufgaben") werden alle bestehenden Zeilen für `profile_id` gelöscht und neu eingefügt. Die Reihenfolge entspricht dem `sort_order`-Wert, der durch HTML5-DnD gesetzt wird.

---

## jpg_profile_requirements

Anforderungen mit Typ-Unterscheidung (Pflicht / Optional).

```sql
CREATE TABLE `{prefix}jpg_profile_requirements` (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    profile_id  INT UNSIGNED NOT NULL,
    type        ENUM('must','nice') NOT NULL DEFAULT 'must',
    description TEXT NOT NULL,
    sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_profile (profile_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## jpg_profile_benefits

Verknüpfungstabelle Profil ↔ Benefit (m:n).

```sql
CREATE TABLE `{prefix}jpg_profile_benefits` (
    profile_id INT UNSIGNED NOT NULL,
    benefit_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (profile_id, benefit_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## jpg_text_modules

Bibliothek für wiederverwendbare Texte (z. B. Unternehmensbeschreibungen).

```sql
CREATE TABLE `{prefix}jpg_text_modules` (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title       VARCHAR(255) NOT NULL,
    content     LONGTEXT     NOT NULL,
    category    VARCHAR(100) DEFAULT NULL,
    usage_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## jpg_skills

Skill-Matrix für die Anforderungsauswahl.

```sql
CREATE TABLE `{prefix}jpg_skills` (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(255) NOT NULL,
    skill_group VARCHAR(100) DEFAULT NULL,  -- z. B. 'IT', 'Soft Skills', 'Sprachen'
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_group (skill_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Seed-Daten (11 Einträge):** PHP, MySQL, JavaScript, Python, Java, HTML/CSS, Git, Kommunikation, Teamarbeit, Englisch, Deutsch (B2+).

---

## jpg_profile_skills

Verknüpfungstabelle Profil ↔ Skill (m:n).

```sql
CREATE TABLE `{prefix}jpg_profile_skills` (
    profile_id INT UNSIGNED NOT NULL,
    skill_id   INT UNSIGNED NOT NULL,
    level      TINYINT UNSIGNED DEFAULT 0,  -- 0-5
    PRIMARY KEY (profile_id, skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## jpg_benefits

Benefit-Katalog mit Gruppierung.

```sql
CREATE TABLE `{prefix}jpg_benefits` (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name         VARCHAR(255) NOT NULL,
    benefit_group VARCHAR(100) DEFAULT NULL, -- z. B. 'Vergütung', 'Flexibilität'
    icon_svg     TEXT         DEFAULT NULL,
    is_active    TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_group (benefit_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Seed-Daten (12 Einträge):** Remote Work, Flexible Arbeitszeiten, 30 Urlaubstage, Betriebliche Altersvorsorge, Weiterbildungsbudget, Firmenwagen, JobRad, Homeoffice-Ausstattung, Teamevents, Kantine/Essenszuschuss, Gesundheitsangebote, Kinderbetreuung.

---

## jpg_job_categories

Stellenkategorien / Abteilungen.

```sql
CREATE TABLE `{prefix}jpg_job_categories` (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(255) NOT NULL,
    slug        VARCHAR(255) NOT NULL,
    description TEXT         DEFAULT NULL,
    sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Seed-Daten (8 Einträge):** IT & Entwicklung, Marketing & Kommunikation, Vertrieb & Sales, Finanzen & Controlling, Personal & HR, Kundenservice, Produktion & Logistik, Führung & Management.

---

## jpg_templates

PDF-, Web- und E-Mail-Templates.

```sql
CREATE TABLE `{prefix}jpg_templates` (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(255) NOT NULL,
    type        ENUM('pdf','web','email') NOT NULL DEFAULT 'web',
    content     LONGTEXT     NOT NULL,    -- HTML-Template mit Platzhaltern
    custom_css  TEXT         DEFAULT NULL,
    is_default  TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Platzhalter in Templates:**

| Platzhalter | Beschreibung |
|---|---|
| `{{title}}` | Stellenbezeichnung |
| `{{summary}}` | Kurztext/Teaser |
| `{{description}}` | Ausführliche Beschreibung (HTML) |
| `{{tasks}}` | Aufgaben-Liste (HTML) |
| `{{requirements}}` | Anforderungen-Liste (HTML) |
| `{{benefits}}` | Benefits-Liste (HTML) |
| `{{location}}` | Standort |
| `{{employment_type}}` | Beschäftigungsart |

---

## jpg_settings

Plugin-Konfigurationseinstellungen im Key-Value-Format.

```sql
CREATE TABLE `{prefix}jpg_settings` (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    option_name  VARCHAR(191) NOT NULL,
    option_value LONGTEXT     DEFAULT NULL,
    autoload     TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_option (option_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Bekannte Option-Keys:**

| Key | Typ | Standard | Beschreibung |
|---|---|---|---|
| `jpg_db_version` | string | `'1'` | Datenbankschema-Version |
| `jpg_default_status` | string | `'draft'` | Standard-Status neuer Profile |
| `jpg_profiles_per_page` | int | `'20'` | Paginierung |
| `jpg_slug_prefix` | string | `'stelle'` | URL-Präfix |
| `jpg_role_create` | string | `'admin'` | Mindest-Rolle für Erstellung |
| `jpg_review_required` | int | `'0'` | 4-Augen-Prinzip ein/aus |
| `jpg_notify_email` | string | `''` | Benachrichtigungs-E-Mail |
| `jpg_primary_color` | string | `'#3b82f6'` | Corporate-Primärfarbe |
| `jpg_company_name` | string | `''` | Unternehmensname für Templates |

---

## jpg_stats

DSGVO-konformes Page-View-Tracking (kein personenbezogenes Datum).

```sql
CREATE TABLE `{prefix}jpg_stats` (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    profile_id  INT UNSIGNED    NOT NULL,
    view_date   DATE            NOT NULL,
    view_count  INT UNSIGNED    NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_profile_date (profile_id, view_date),
    KEY idx_profile (profile_id),
    KEY idx_date    (view_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

> Pro Profil und Tag wird genau **ein Datensatz** gehalten (`view_count` wird inkrementiert). Es werden keine IPs, User-Agents oder Session-Daten gespeichert.

---

## jpg_requirement_items

Bibliothek für eigene Anforderungs-Bausteine (unabhängig von der Skill-Matrix).

```sql
CREATE TABLE `{prefix}jpg_requirement_items` (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    group_name  VARCHAR(100) DEFAULT NULL,   -- z. B. 'Soft Skills', 'Fachkenntnisse'
    title       VARCHAR(255) NOT NULL,
    sort_order  SMALLINT(5)  NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_group (group_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

> Einträge werden im Generator-Wizard (Tab „Anforderungen") als Picker-Quelle genutzt. Verwaltung über **Bibliotheken → Tab „Anforderungs-Liste"**.

---

## jpg_company_default_benefits

Speichert die Standard-Benefits, die einem Unternehmen aus dem `cms-companies`-Plugin zugewiesen werden. Diese werden im Generator-Wizard (Tab „Benefits") für neue Profile vorausgewählt.

```sql
CREATE TABLE `{prefix}jpg_company_default_benefits` (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id  INT UNSIGNED NOT NULL,
    benefit_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_company_benefit (company_id, benefit_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

> Gespeichert via DELETE+INSERT (vollständiger Austausch pro `company_id` bei jeder Speicherung). Verwaltung über **Unternehmens-Übersicht** im Admin-Menü.

---

## jpg_workflow_steps

Konfigurierbare Genehmigungsschritte für den n-stufigen Freigabeprozess.

```sql
CREATE TABLE `{prefix}jpg_workflow_steps` (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sort_order          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    step_name           VARCHAR(255) NOT NULL,
    approver_role       VARCHAR(100) NOT NULL,
    allow_self_approve  TINYINT(1)   NOT NULL DEFAULT 0,
    notification_email  VARCHAR(255) DEFAULT NULL,
    active              TINYINT(1)   NOT NULL DEFAULT 1,
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## jpg_workflow_history

Unveränderliches Audit-Log aller Workflow-Aktionen (Einreichungen, Genehmigungen, Ablehnungen, Resets).

```sql
CREATE TABLE `{prefix}jpg_workflow_history` (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    profile_id  INT UNSIGNED    NOT NULL,
    step_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    action      ENUM('submitted','approved','rejected','reset') NOT NULL,
    actor_id    INT UNSIGNED    NOT NULL,
    note        TEXT            DEFAULT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_profile (profile_id),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Entity-Relationship-Diagramm

```
jpg_job_categories ──< jpg_profiles >── jpg_profile_tasks
                                    >── jpg_profile_requirements
                                    >── jpg_profile_benefits >── jpg_benefits
                                    >── jpg_profile_skills   >── jpg_skills
                                    >── jpg_stats
                                    >── jpg_workflow_history
                                    ──< jpg_templates  (via settings)

jpg_text_modules          (Bibliothek: Textbausteine)
jpg_requirement_items     (Bibliothek: Anforderungs-Bausteine)
jpg_workflow_steps        (Workflow-Konfiguration)
jpg_settings              (Plugin-Konfiguration)

cms_companies (externes Plugin)
    └──< jpg_company_default_benefits >── jpg_benefits
```
