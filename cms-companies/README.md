# CMS Companies Directory Plugin

**Version:** 3.0.9  
**Requires:** 365CMS 3.0+ / PHP 8.1+

## Description

The CMS Companies Directory plugin manages company profiles with card views and detail pages. This is a required core plugin for 365CMS.

## Features

- ✅ Company profile management
- ✅ Custom database tables with proper relationships
- ✅ Admin interface for managing companies
- ✅ Event-style responsive frontend card grid layout
- ✅ Flush public detail pages with 1160px content width
- ✅ Expert-to-Company relationships (Many-to-Many)
- ✅ Meta data support
- ✅ Shortcode support: `[cms_companies]`

## Database Tables

### cms_companies
Main table storing company profiles with fields:
- id, name, email, industry, size, location
- website, logo_url, description, founded_year, phone
- status, created_at, updated_at

### cms_company_experts
Relationship table linking companies to experts:
- id, company_id, expert_id, role
- start_date, end_date, is_current, created_at

### cms_company_meta
Additional metadata for companies:
- id, company_id, meta_key, meta_value, created_at

## Usage

### Admin Interface
Navigate to `/admin/companies` to manage company profiles.

### Frontend Display
- List all companies in the public card overview: `/companies`
- View company detail: `/companies/{id}`
- Shortcode: `[cms_companies]` - Displays all companies in a grid

### Programmatic Access

```php
// Get all active companies
$db = CMS\Database::instance();
$stmt = $db->prepare("SELECT * FROM {$db->prefix()}companies WHERE status = ?");
$stmt->execute(['active']);
$companies = $stmt->fetchAll();

// Get company by ID
$stmt = $db->prepare("SELECT * FROM {$db->prefix()}companies WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch();

// Get experts for a company
$stmt = $db->prepare("
    SELECT e.* FROM {$db->prefix()}experts e
    JOIN {$db->prefix()}company_experts ce ON e.id = ce.expert_id
    WHERE ce.company_id = ? AND ce.is_current = 1
");
$stmt->execute([$company_id]);
$experts = $stmt->fetchAll();
```

## Hooks

### Actions
- `company_created` - Fired when a new company is created
- `company_expert_assigned` - Fired when an expert is assigned to a company

### Filters
- `company_card_content` - Modify company card HTML output

## Template Files

Templates can be added to the `templates/` directory:
- `archive-company.php` - Company list view
- `single-company.php` - Company detail view
- `company-card.php` - Card component

## Installation

The plugin is automatically activated during 365CMS setup. Database tables are created on first activation.

## Sicherheitsstatus (2026-04-04)

- Snyk-Code-Audit für `cms-companies` abgeschlossen, aktuell ohne offene Findings.
- Das Archiv-Template escaped Such- und Filterwerte jetzt scannerfreundlich direkt an den relevanten Formular- und Link-Sinks.
- Reset- und Archiv-URLs werden im Frontend konsistent über vorab escaped interne Zielpfade ausgegeben.

## Sicherheitsstatus (2026-05-17)

- Public-Logo-, Website- und verknüpfte Profilbild-URLs werden zur Laufzeit auf `http`/`https` beschränkt.
- Member-Create-Formulare escapen CSRF-Token und alte POST-Werte explizit im Attributkontext mit `ENT_QUOTES` und `UTF-8`.
- Member-Redirects nach POST nutzen `303 See Other`, speichern technische Fehler nur ins Log und zeigen Nutzern generische Fehlermeldungen.
- Public Cards, Archiv und Detailseite wurden von dekorativer Emoji-UI bereinigt und stärker an das ruhige PHINIT-Design angeglichen.

## Publicsite-Status (2026-05-30)

- Die öffentliche Übersicht `/companies` startet wie `cms-events` direkt mit Filter und responsivem Card-Grid.
- Company-Cards zeigen Logo/Initialen, Partner-/Branchen-Badges, Standort-/Team-Meta, Beschreibungsauszug, Website-Hinweis und Details-CTA.
- Karten sind weiterhin komplett klickbar und zusätzlich per Tastatur erreichbar.

## License

Part of 365CMS Core - All Rights Reserved
