---
name: 🔌 Neues Plugin vorschlagen
about: Konzept für ein neues 365CMS-Plugin einreichen
title: "[PLUGIN] cms-"
labels: new-plugin
assignees: ''
---

## Plugin-Name

<!-- Vorgeschlagener Slug: `cms-NAME` (Kleinbuchstaben, Bindestrich) -->
`cms-`

## Kurzbeschreibung

<!-- Ein Satz: Was macht dieses Plugin? -->

## Zielgruppe

- [ ] Admins
- [ ] Mitglieder / Nutzer
- [ ] Öffentliche Besucher
- [ ] Alle

## Kern-Features

1. 
2. 
3. 

## Benötigte Datenbank-Tabellen (Entwurf)

```sql
-- Beispiel
CREATE TABLE cms_name (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ...
);
```

## Cross-Plugin-Abhängigkeiten

<!-- Welche bestehenden Plugins werden benötigt? -->
- [ ] cms-companies
- [ ] cms-events
- [ ] cms-experts
- [ ] cms-speakers
- [ ] Keine

## Hooks (geplant)

| Hook | Typ | Beschreibung |
|------|-----|--------------|
| `xxx_created` | Action | |
| `xxx_query_args` | Filter | |

## Admin-Module (geplant)

- `trait-page-dashboard.php`
- ...

## Member-Module (geplant)

- `trait-member-overview.php`
- ...

## Priorität

- [ ] 🔴 Hoch – Wird aktiv benötigt
- [ ] 🟡 Mittel – Nice-to-have
- [ ] 🟢 Niedrig – Zukunftsvision

## Zusätzlicher Kontext

<!-- Mockups, Referenz-Plugins, Marktbeispiele -->
