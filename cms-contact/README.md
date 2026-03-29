# CMS Contact

> Kontaktformular-Plugin für 365CMS mit 6 Templates, benutzerdefinierten Feldern und Mehrfach-Formularen.

## Features

- **6 Templates**: Classic, Modern, Split, Minimal, Business, Fullwidth
- **Mehrere Formulare**: Jedes Formular hat eigenen Slug (`/contact/kontakt`, `/contact/support`, …)
- **Benutzerdefinierte Felder**: Text, E-Mail, Telefon, Textarea, Select, Radio, Checkbox, Zahl, Datum, URL, Hidden
- **Pflichtfelder**: Jedes Feld einzeln als Pflichtfeld konfigurierbar
- **Feldbreiten**: Voll (100%), Halb (50%), Drittel (33%), Zwei Drittel (66%)
- **Drag & Drop Sortierung**: Felder per Drag & Drop umsortieren
- **Spamschutz**: Honeypot + Math-Captcha + sessionbasiertes Rate-Limiting
- **Sichtbare Serverfehler**: Feldfehler werden nach Redirect wieder am jeweiligen Formularfeld angezeigt
- **Nachrichten-Telemetrie**: Admin sieht zu jeder Anfrage die erfasste IP-Adresse und den User-Agent
- **E-Mail-Benachrichtigungen**: Globaler Admin-Empfänger oder Formular-Empfänger + optionale Bestätigung an Absender
- **DSGVO-konform**: Export- und Lösch-Hooks, automatische Bereinigung
- **Dashboard**: Statistiken, Trends, aktuelle Nachrichten
- **Responsive**: Alle Templates mobil-optimiert
- **Dark Mode**: Vollständige Dark-Mode-Unterstützung

## Installation

1. Ordner `cms-contact/` nach `CMS/plugins/` kopieren
2. Im Admin unter **Plugins** → **CMS Contact** aktivieren
3. Unter **Kontakt → Dashboard** beginnen

## Dateien

```
cms-contact/
├── cms-contact.php              # Plugin-Hauptdatei
├── update.json                  # Versionsinfo
├── README.md                    # Diese Datei
├── includes/
│   ├── class-installer.php      # DB-Schema + Seeding
│   ├── class-forms.php          # Formular-CRUD
│   ├── class-fields.php         # Feld-CRUD
│   ├── class-submissions.php    # Nachrichtenverwaltung
│   └── class-frontend.php       # Routing + Rendering
├── admin/
│   ├── class-admin-menu.php     # Admin-Menü
│   ├── class-admin-pages.php    # Trait-Shell
│   ├── modules/
│   │   ├── trait-page-dashboard.php
│   │   ├── trait-page-forms.php
│   │   ├── trait-page-submissions.php
│   │   └── trait-page-settings.php
│   └── views/
│       ├── page-dashboard.php
│       ├── page-forms-list.php
│       ├── page-form-new.php
│       ├── page-form-edit.php
│       ├── page-form-fields.php
│       ├── page-submissions-list.php
│       ├── page-submission-view.php
│       └── page-settings.php
├── templates/
│   ├── template-classic.php
│   ├── template-modern.php
│   ├── template-split.php
│   ├── template-minimal.php
│   ├── template-business.php
│   └── template-fullwidth.php
└── assets/
    ├── css/
    │   ├── contact-public.css
    │   └── contact-admin.css
    └── js/
        ├── contact-public.js
        └── contact-admin.js
```

## Datenbank-Tabellen

| Tabelle | Zweck |
|---------|-------|
| `cms_contact_forms` | Formulare (Titel, Slug, Template, Einstellungen) |
| `cms_contact_fields` | Formularfelder (Typ, Label, Pflicht, Reihenfolge) |
| `cms_contact_submissions` | Eingegangene Nachrichten inkl. User-Agent und IP-Adresse |
| `cms_contact_submission_meta` | Key-Value-Metadaten pro Nachricht |
| `cms_contact_settings` | Plugin-Einstellungen |

## Templates

| Template | Beschreibung |
|----------|-------------|
| Classic | Klassisches einspaltiges Formular |
| Modern | Kartendesign mit Schatten und dekorativen Elementen |
| Split | Zweigeteilt: Kontaktinfos links, Formular rechts |
| Minimal | Minimalistisch mit Underline-Eingabefeldern |
| Business | Hero-Banner + Info-Karten + Formular + Karte |
| Fullwidth | Farbiger Hero + zentriertes Formular-Card |

## Hooks

### Actions
- `contact_submitted` – Nach erfolgreicher Formular-Einsendung
- `contact_form_created` – Nach Erstellen eines neuen Formulars
- `contact_form_deleted` – Nach Löschen eines Formulars

### DSGVO
- `dsgvo_export_data` – Datenexport für einen Benutzer
- `dsgvo_delete_data` – Datenlöschung für einen Benutzer

## Version

- **1.1.5** – Sicherheits-, Performance- und UX-Fixes für Redirects, Mail-Settings, Feldfehler und Submission-Listen
