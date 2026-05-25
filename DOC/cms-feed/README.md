# CMS Feed – RSS-Feed-Aggregator

> Plugin für 365CMS zum Sammeln, Anzeigen und Versenden von RSS-Feeds.

## Aktueller Stand

- Version: `3.0.3`
- Release: `2026-05-25`
- Kompatibilität laut Manifest: `365CMS >= 3.0.0`, `PHP >= 8.4`
- Aktueller Fix-Schwerpunkt: Audit-Härtung für Lifecycle/Uninstall, Schema-Migrationen, arraysichere Admin-POST-Verarbeitung, native 365CMS-Fehlerseiten und strukturiertes Plugin-Logging.

## Features

- **RSS-Feed-Sammlung** – Unterstützt RSS 2.0, RSS 1.0 (RDF) und Atom-Feeds
- **Bereiche/Kategorien** – Feeds in thematische Bereiche gruppieren (z.B. Security, Tech News)
- **Öffentliche Seiten** – Zentrale Feed-Übersicht + individuelle Bereichsseiten (ThemeManager-integriert)
- **3 Layout-Optionen** – Grid, Liste, Magazin (pro Bereich konfigurierbar)
- **Design-Anpassung** – Farben, Border-Radius, Spalten im Admin konfigurierbar
- **Feed-Katalog** – 300+ kuratierte RSS-Feeds in 10 Kategorien (IT-News, Security, Development, Cloud, Microsoft, Linux, AI, Networking, Business-IT, Hardware) – Import mit einem Klick
- **Teilimport aus Katalogen** – Kataloge können komplett oder als gezielte Auswahl einzelner Feed-Quellen importiert werden
- **E-Mail-Digest** – Ausgewählte Feeds 1×–4× täglich als E-Mail versenden
- **Member Feed-Abos** – Mitglieder wählen mehrere Feeds und erhalten tägliche oder wöchentliche Mail-Digests direkt aus dem `cms-phinit` Memberbereich
- **Suche** – Volltextsuche über alle gesammelten Beiträge
- **Featured & Hidden** – Beiträge hervorheben oder ausblenden
- **Auto-Cleanup** – Alte Beiträge automatisch stündlich auf 7 Tage begrenzen oder manuell entfernen
- **Theme-Override** – Templates können im aktiven Theme überschrieben werden
- **Whitelabel** – Standalone-Seite ohne CMS-Theme für Einbettung unter `/{archive_slug}/embed`

## Schnellstart

1. Plugin in `/plugins/cms-feed/` kopieren
2. Im CMS-Admin unter „Plugins" aktivieren
3. Admin → 📡 Feeds → **📚 Katalog** → Gewünschte Kategorie importieren
4. Alternativ: Bereiche manuell anlegen + Kanäle (RSS-URLs) hinzufügen
5. Dashboard → „🔄 Alle Feeds abrufen" → Öffentliche Seite unter `/feeds`

## Verzeichnisstruktur

```
cms-feed/
├── cms-feed.php                    # Hauptdatei (v3.0.3)
├── update.json                     # Plugin-Manifest
├── includes/
│   ├── class-database.php          # DB-Tabellen + CRUD (659 Zeilen)
│   ├── class-error-handler.php     # Native 365CMS-Fehlerseiten + strukturierter Logger
│   ├── class-rss-fetcher.php       # RSS/Atom-Parser (477 Zeilen)
│   ├── class-feed-catalog.php      # Kuratierter Feed-Katalog (300+ Feeds)
│   ├── class-template-loader.php   # Template-Loader (Theme-Override)
│   ├── class-public-controller.php # Öffentliche Routen (142 Zeilen)
│   ├── class-email-digest.php      # E-Mail-Digest + Member-Abo-Versand
│   └── class-admin.php             # Admin-Backend (7 Tabs)
├── admin/views/
│   └── page-admin.php              # Admin-View (alle Tabs)
├── templates/
│   ├── archive-feed.php            # Haupt-Archiv (ThemeManager-integriert)
│   ├── archive-category.php        # Bereichs-Archiv (ThemeManager-integriert)
│   ├── feed-card.php               # Einzelne Feed-Karte
│   └── whitelabel-feed.php         # Standalone-Whitelabel-Seite
├── assets/
│   ├── css/
│   │   ├── style.css               # Public CSS
│   │   └── feed-admin.css          # Admin CSS
│   └── js/
│       ├── script.js               # Public JS (Filter, Suche)
│       └── admin.js                # Admin JS (Modals, Tabs, Color-Sync)
└── DOC/
    └── ...                         # Dokumentation
```

## Admin-Tabs

| Tab | Funktion |
|-----|----------|
| 📊 Dashboard | Statistiken, letzte Aktivitäten, „Alle abrufen"-Button |
| 📡 Kanäle | RSS-Kanäle verwalten (CRUD), Fehlerübersicht |
| 📁 Bereiche | Kategorien/Bereiche mit Slug, Layout, Icon |
| 📚 Katalog | 300+ kuratierte Feeds – Komplett- oder Selektiv-Import |
| 📰 Beiträge | Alle Feeds durchsuchen, filtern, hervorheben/ausblenden |
| 📧 E-Mail-Digests | Digest-Empfänger, Frequenz, Test-Versand |
| ⚙️ Einstellungen | Allgemein, Design, Digest-Einstellungen, Cleanup |

## Memberbereich (`cms-phinit`)

- Unter `/member/feeds` können Mitglieder ihr persönliches Feed-Abo konfigurieren
- Auswahl von **einem oder mehreren Feed-Kanälen** in einer gemeinsamen Mail-Zustellung
- Zeitpläne:
    - **Täglich** um `09:00 Uhr`
    - **Täglich** um `15:00 Uhr`
    - **Täglich 2×** um `09:00 Uhr` und `15:00 Uhr`
    - **Wöchentlich** an einem frei wählbaren Wochentag um `09:00 Uhr` oder `15:00 Uhr`
- Die Zustellung läuft über den bestehenden `cms_cron_hourly`-Hook, sendet nur fällige Slots und legt E-Mails bei aktiver Mail-Queue in den zentralen Cron-Worker

## Wichtige Architektur-Hinweise

- Public CSS/JS wird nur noch auf echten Feed-Routen geladen, nicht mehr global auf allen Frontend-Seiten
- Auch die Consent-Seite nutzt dabei dieselben Public-Assets, damit Änderungen an der Cookie-Einwilligung ohne Template-Sonderskript sauber auf die Ansicht zurückwirken
- Member-Feed-Abos werden separat von den Admin-Digests gespeichert
- Admin-Digests bleiben für manuelle/global konfigurierte Empfänger erhalten; Member-Abos gehören dem jeweiligen Benutzerkonto
- Der stündliche Cron priorisiert die in `cms-phinit` auf der Startseite gewählten Feed-Kanäle, prüft zusätzlich alle nach `fetch_interval` fälligen Kanäle und reiht diese in die Fetch-Queue ein
- Bereits eingereihte Queue-Tasks werden zusätzlich bei jedem regulären `task=all`-/`task=mail-queue`-Cron-Lauf in kleinen Batches weiter abgearbeitet, damit Rückstaus zwischen zwei Stundenläufen nicht stehen bleiben
- Feed-Digests und Member-Abo-Mails nutzen den zentralen MailService bzw. die Mail-Queue statt direktem `mail()`, damit SMTP/OAuth-Konfiguration, Retry-Backoff und Mail-Logs greifen
- Feed-Beiträge älter als 7 Tage werden stündlich automatisch bereinigt
- Seit `3.0.2` normalisiert die Admin-View gemischte Datenbank- und Query-Werte vor strikten PHP-8.4-Formatter-Aufrufen (`number_format()`, `date()`, `rawurlencode()`), damit manipulierte Parameter wie `q[]`, `cat[]`, `page[]` oder defekte UTF-8-Payloads keinen 500er im Feed-Admin mehr auslösen.
- Seit `3.0.3` normalisieren auch alle Admin-POST-Handler skalare Werte und Arrays zentral, setzen bei CSRF-/Bad-Action-/Serverfehlern passende HTTP-Codes und entfernen beim Kanal-/Bereich-Löschen Queue- sowie Member-Abo-Referenzen.
- Plugin-Ausnahmen werden seit `3.0.3` zentral über `CMS_Feed_Error_Handler` behandelt: erst strukturiert im 365CMS-Logger-Channel `plugin-cms-feed` protokollieren, dann native `error.php`-/`404.php`-Theme-Fehlerseite rendern, Fallback nur wenn auch diese Fehlerseite scheitert.
- Die Whitelabel-/Embed-Ansicht ist über `/{archive_slug}/embed` erreichbar und nutzt dieselben Public-Assets wie das Archiv.

## Systemanforderungen

- 365CMS ≥ 3.0.0
- PHP ≥ 8.4
- MySQL/MariaDB mit InnoDB
- `allow_url_fopen` oder cURL

> Hinweis für Shared Hosting: Der Feed-Abruf nutzt bevorzugt Stream-Zugriffe und fällt bei deaktiviertem `allow_url_fopen` automatisch auf cURL zurück. Mindestens einer der beiden Transportwege muss verfügbar sein.

## Lizenz

Proprietär – 365 Network
