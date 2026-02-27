# CMS Companies – Datenbank-Referenz

## Tabellen-Übersicht

| Tabelle | Zweck |
|---------|-------|
| `cms_companies` | Haupt-Firmen-Datensätze |
| `cms_company_experts` | M2M-Relation Firma ↔ Experte |
| `cms_company_meta` | Flexible Zusatz-Metadaten |
| `cms_company_plugin_settings` | Plugin-Einstellungen |
| `cms_company_industries` | Branchen-Präsets |

---

## `cms_companies`

```sql
CREATE TABLE cms_companies (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED DEFAULT NULL     COMMENT 'FK cms_users',
    name             VARCHAR(255) NOT NULL,
    email            VARCHAR(150) NOT NULL,
    phone            VARCHAR(50)  DEFAULT NULL,
    industry         VARCHAR(150) DEFAULT NULL,
    company_size     VARCHAR(50)  DEFAULT NULL,
    description      TEXT         DEFAULT NULL,
    logo_url         VARCHAR(500) DEFAULT NULL,
    website          VARCHAR(500) DEFAULT NULL,
    location_city    VARCHAR(100) DEFAULT NULL,
    location_zip     VARCHAR(20)  DEFAULT NULL,
    location_country VARCHAR(100) DEFAULT NULL,
    founded_year     INT          DEFAULT NULL,
    employee_count   INT          DEFAULT NULL,
    is_partner       BOOLEAN      DEFAULT FALSE,
    is_top_partner   BOOLEAN      DEFAULT FALSE,
    is_sponsor       BOOLEAN      DEFAULT FALSE,
    status           VARCHAR(20)  NOT NULL DEFAULT 'active',
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user_id  (user_id),
    INDEX idx_status   (status),
    INDEX idx_email    (email),
    INDEX idx_industry (industry),
    INDEX idx_city     (location_city),
    INDEX idx_partner  (is_partner, is_top_partner, is_sponsor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Felder

| Spalte | Typ | Pflicht | Beschreibung |
|--------|-----|---------|--------------|
| `id` | INT UNSIGNED | AUTO | Primärschlüssel |
| `user_id` | INT UNSIGNED | Nein | Verknüpfter CMS-Benutzer (Firmen-Admin) |
| `name` | VARCHAR(255) | **Ja** | Firmenname |
| `email` | VARCHAR(150) | **Ja** | Kontakt-E-Mail |
| `phone` | VARCHAR(50) | Nein | Telefonnummer |
| `industry` | VARCHAR(150) | Nein | Branche (siehe `cms_company_industries`) |
| `company_size` | VARCHAR(50) | Nein | `1-10`, `11-50`, `51-200`, `201-1000`, `1000+` |
| `description` | TEXT | Nein | Firmenbeschreibung (HTML erlaubt nach Sanitierung) |
| `logo_url` | VARCHAR(500) | Nein | Pfad zum Firmenlogo |
| `website` | VARCHAR(500) | Nein | Unternehmens-URL |
| `location_city` | VARCHAR(100) | Nein | Stadt |
| `location_zip` | VARCHAR(20) | Nein | PLZ |
| `location_country` | VARCHAR(100) | Nein | Land |
| `founded_year` | INT | Nein | Gründungsjahr |
| `employee_count` | INT | Nein | Mitarbeiteranzahl |
| `is_partner` | BOOLEAN | Nein | Partner-Status |
| `is_top_partner` | BOOLEAN | Nein | Top-Partner-Status |
| `is_sponsor` | BOOLEAN | Nein | Sponsor-Status |
| `status` | VARCHAR(20) | **Ja** | `active`, `inactive`, `draft` |

---

## `cms_company_experts`

Many-to-Many-Relation zwischen Firmen und Experten.

```sql
CREATE TABLE cms_company_experts (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    expert_id  INT UNSIGNED NOT NULL,
    role       VARCHAR(150) DEFAULT NULL,
    start_date DATE         DEFAULT NULL,
    end_date   DATE         DEFAULT NULL,
    is_current BOOLEAN      DEFAULT TRUE,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (company_id) REFERENCES cms_companies(id) ON DELETE CASCADE,
    INDEX idx_company (company_id),
    INDEX idx_expert  (expert_id),
    INDEX idx_current (is_current),
    UNIQUE KEY unique_company_expert (company_id, expert_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `company_id` | INT UNSIGNED | FK → `cms_companies.id` |
| `expert_id` | INT UNSIGNED | FK → `cms_experts.id` |
| `role` | VARCHAR(150) | Position/Rolle des Experten in der Firma |
| `start_date` | DATE | Beginn der Zugehörigkeit |
| `end_date` | DATE | Ende der Zugehörigkeit (NULL = aktuell) |
| `is_current` | BOOLEAN | Ob die Zuordnung aktuell aktiv ist |

---

## `cms_company_meta`

Flexibles Key-Value-Meta-System für Firmen.

```sql
CREATE TABLE cms_company_meta (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    meta_key   VARCHAR(255) NOT NULL,
    meta_value LONGTEXT,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (company_id) REFERENCES cms_companies(id) ON DELETE CASCADE,
    INDEX idx_company     (company_id),
    INDEX idx_meta_key    (meta_key),
    INDEX idx_company_key (company_id, meta_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Bekannte Meta-Keys

| Key | Format | Beschreibung |
|-----|--------|--------------|
| `social_linkedin` | URL-String | LinkedIn-Firmenseite |
| `social_xing` | URL-String | XING-Firmenseite |
| `social_twitter` | URL-String | Twitter/X-Profil |
| `certifications` | JSON-Array | Zertifizierungen / Awards |
| `custom_fields` | JSON-Object | Benutzerdefinierte Felder |

---

## `cms_company_industries`

Branchen-Präset-Tabelle für Dropdowns.

```sql
CREATE TABLE cms_company_industries (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) NOT NULL,
    slug       VARCHAR(150) NOT NULL UNIQUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Entity-Relationship-Diagramm

```
cms_users (1)────(0..1) cms_companies (1)────(M) cms_company_experts (M)────(1) cms_experts
                            │
                            └──(1)────(M) cms_company_meta
                            │
                            └──(1)────(M) cms_events (als Veranstalter)
                            │
                            └──(1)────(M) cms_speakers (company_id)
```

---

## Migrationen

### Neue Spalte hinzufügen (Migrations-Muster)

```php
$db  = CMS\Database::instance();
$pdo = $db->getPdo();
$p   = $db->prefix();

$cols = $pdo->query("SHOW COLUMNS FROM {$p}companies LIKE 'new_column'")->fetchAll();
if (empty($cols)) {
    $pdo->exec("ALTER TABLE {$p}companies ADD COLUMN new_column VARCHAR(100) DEFAULT NULL AFTER status");
}
```
