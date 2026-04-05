# CMS Downloads

Öffentliches Download-Plugin für 365CMS mit Kategorien, Datei-Management und Frontend-Archiv.

## Features

- Verwaltung öffentlicher Downloads im Admin
- Kategorien mit Icons und Beschreibungen
- Typ-/Template-Presets für:
  - PowerShell
  - Webprojekte
  - Dokumente
  - eBooks
  - Archive / Toolkits
- Datei-Upload über das 365CMS-Media-System
- Externe Download-URLs als Alternative
- Öffentliches Archiv unter `/downloads`
- Kategorien-Archiv unter `/downloads/category/{slug}`
- Direkter Download-Endpunkt unter `/downloads/file/{slug}`
- Optionale Login-Pflicht pro Download
- Download-Zähler

## Dateistruktur

```text
cms-downloads/
├── cms-downloads.php
├── CHANGELOG.md
├── README.md
├── update.json
├── admin/
│   ├── class-admin-menu.php
│   ├── class-admin-pages.php
│   └── views/
│       ├── page-dashboard.php
│       ├── page-downloads.php
│       ├── page-categories.php
│       └── page-settings.php
├── assets/
│   └── css/
│       ├── downloads-admin.css
│       └── downloads-public.css
├── includes/
│   ├── class-installer.php
│   ├── class-public-controller.php
│   └── class-repository.php
└── templates/
    └── archive-downloads.php
```

## Sicherheitsstatus (2026-04-04)

- Snyk-Code-Audit für `cms-downloads` abgeschlossen, aktuell ohne offene Findings.
- Admin-Redirects führen nach POST-Aktionen jetzt ausschließlich auf feste interne Dashboard-Routen zurück.
- Dadurch werden request-basierte Redirect-Ziele vermieden und Open-Redirect-Befunde beseitigt.
