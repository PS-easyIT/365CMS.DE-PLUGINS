# CMS Feed – RSS-Feed-Aggregator

> Plugin für 365CMS zum Sammeln, Anzeigen und Versenden von RSS-Feeds.

## Features

- **RSS-Feed-Sammlung** – Unterstützt RSS 2.0, RSS 1.0 (RDF) und Atom-Feeds
- **Bereiche/Kategorien** – Feeds in thematische Bereiche gruppieren (z.B. Security, Tech News)
- **Öffentliche Seiten** – Zentrale Feed-Übersicht + individuelle Bereichsseiten mit eigenen Slugs
- **3 Layout-Optionen** – Grid, Liste, Magazin (pro Bereich konfigurierbar)
- **Design-Anpassung** – Farben, Border-Radius, Spalten im Admin konfigurierbar
- **E-Mail-Digest** – Ausgewählte Feeds 1×–4× täglich als E-Mail versenden
- **Suche** – Volltextsuche über alle gesammelten Beiträge
- **Featured & Hidden** – Beiträge hervorheben oder ausblenden
- **Auto-Cleanup** – Alte Beiträge automatisch oder manuell entfernen
- **Theme-Override** – Templates können im aktiven Theme überschrieben werden

## Schnellstart

1. Plugin in `/plugins/cms-feed/` kopieren
2. Im CMS-Admin unter „Plugins" aktivieren
3. Admin → 📡 Feeds → Bereiche anlegen
4. Kanäle (RSS-URLs) hinzufügen
5. Feeds abrufen → Öffentliche Seite unter `/feeds` aufrufen

## Verzeichnisstruktur

```
cms-feed/
├── cms-feed.php                    # Hauptdatei
├── update.json                     # Plugin-Manifest
├── includes/
│   ├── class-database.php          # DB-Tabellen + CRUD
│   ├── class-rss-fetcher.php       # RSS/Atom-Parser
│   ├── class-template-loader.php   # Template-Loader (Theme-Override)
│   ├── class-public-controller.php # Öffentliche Routen
│   ├── class-email-digest.php      # E-Mail-Digest
│   └── class-admin.php             # Admin-Backend
├── admin/views/
│   └── page-admin.php              # Admin-View (alle Tabs)
├── templates/
│   ├── archive-feed.php            # Haupt-Archiv
│   ├── archive-category.php        # Bereichs-Archiv
│   └── feed-card.php               # Einzelne Feed-Karte
├── assets/
│   ├── css/
│   │   ├── style.css               # Public CSS
│   │   └── feed-admin.css          # Admin CSS
│   └── js/
│       ├── script.js               # Public JS
│       └── admin.js                # Admin JS
└── DOC/
    └── ...                         # Dokumentation
```

## Systemanforderungen

- 365CMS ≥ 0.20.0
- PHP ≥ 8.1
- MySQL/MariaDB mit InnoDB
- `allow_url_fopen` oder cURL

## Lizenz

Proprietär – 365 Network
