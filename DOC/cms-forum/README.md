# CMS Forum – Plugin-Dokumentation

> **Version:** 1.0.0  
> **Autor:** 365 Network  
> **Minimum PHP:** 8.3  
> **Minimum MySQL:** 8.0 / MariaDB 10.6

---

## Übersicht

Das **CMS Forum**-Plugin stellt ein vollständiges Community-Forum für die 365CMS-Plattform bereit. Es umfasst:

- **Kategorien & Foren** – Hierarchische Gliederung mit Unterforen
- **Threads & Beiträge** – Erstellen, Bearbeiten, Zitieren, Löschen (soft)
- **BBCode-Editor** – Formatierung, Links, Bilder, YouTube-Embeds, Spoiler, Listen
- **Umfragen** – Optional pro Thread, Einzel- oder Mehrfachauswahl
- **Likes** – Beiträge bewerten (Toggle)
- **Abonnements** – E-Mail-Benachrichtigungen bei neuen Antworten
- **Meldungen** – Posts durch Benutzer melden, Moderatoren bearbeiten
- **Berechtigungen** – 6-stufig (Admin → Supermod → Mod → Gruppe → Registriert → Gast)
- **Rang-System** – Automatische Rang-Vergabe basierend auf Beitragsanzahl
- **Gelesen-Tracking** – Ungelesene Threads markieren
- **Suche** – Volltextsuche mit Datumsfilter und Foren-Filter
- **Flood Control** – Spam-Schutz mit konfigurierbaren Intervallen
- **DSGVO-konform** – Export und Anonymisierung personenbezogener Daten
- **Dark Mode** – CSS Custom Properties passen sich automatisch an

---

## Installation

1. Plugin-Ordner `cms-forum/` nach `CMS/plugins/` kopieren
2. Im Admin-Bereich → Plugins → **CMS Forum** aktivieren
3. Die Datenbank-Tabellen werden automatisch erstellt
4. Unter Admin → Forum → **Einstellungen** Grundkonfiguration prüfen

---

## Features

### Kategorien & Foren

Foren werden in Kategorien gruppiert. Jedes Forum kann optionale Unterforen haben (1 Ebene). Sortierung über Drag & Drop im Admin.

### BBCode-Editor

| BBCode | Ausgabe |
|--------|---------|
| `[b]text[/b]` | **fett** |
| `[i]text[/i]` | *kursiv* |
| `[u]text[/u]` | unterstrichen |
| `[s]text[/s]` | ~~durchgestrichen~~ |
| `[url=...]text[/url]` | Link |
| `[img]url[/img]` | Bild |
| `[code]...[/code]` | Code-Block |
| `[quote]...[/quote]` | Zitat |
| `[quote="Name"]...[/quote]` | Zitat mit Autor |
| `[list][*]...[/list]` | Liste |
| `[spoiler]...[/spoiler]` | Spoiler (Klick zum Aufdecken) |
| `[youtube]url[/youtube]` | YouTube-Embed |
| `[color=red]...[/color]` | Farbtext |
| `[size=18]...[/size]` | Schriftgröße |

### Berechtigungssystem

8 Flags pro Forum × Benutzergruppe:

| Flag | Beschreibung |
|------|--------------|
| `can_view` | Forum sehen |
| `can_read` | Threads lesen |
| `can_post` | Antworten schreiben |
| `can_create_thread` | Neue Themen erstellen |
| `can_edit_own` | Eigene Beiträge bearbeiten |
| `can_delete_own` | Eigene Beiträge löschen |
| `can_upload` | Dateien hochladen |
| `can_vote` | An Umfragen teilnehmen |

### Rang-System

Standardmäßig 6 Ränge (konfigurierbar):

| Rang | Min. Beiträge |
|------|---------------|
| Neuling | 0 |
| Mitglied | 10 |
| Aktives Mitglied | 50 |
| Experte | 200 |
| Meister | 500 |
| Veteran | 1000 |

---

## Dateistruktur

```
cms-forum/
├── cms-forum.php              # Bootstrap
├── config/config.php          # Standard-Konfiguration
├── update.json                # Update-Metadaten
├── includes/
│   └── class-database.php     # Tabellen & Migration
├── src/
│   ├── Models/                # 13 Datenbank-Models
│   ├── Services/              # 6 Service-Klassen
│   ├── Helpers/               # 4 Helper-Klassen
│   └── Controllers/           # 5 Controller
├── admin/
│   ├── class-admin-menu.php   # Menü-Registrierung
│   ├── class-admin-pages.php  # Trait-Shell
│   ├── modules/               # 9 Admin-Traits
│   └── views/                 # 9 Admin-Views
├── views/
│   ├── frontend/              # 7 Frontend-Templates
│   └── member/                # 2 Member-Templates
├── assets/
│   ├── css/                   # 4 Stylesheets
│   ├── js/                    # 4 JavaScript-Module
│   └── icons/icons.svg        # SVG Sprite
└── lang/                      # Sprachdateien (de_DE, en_US)
```

---

## URL-Routing

| Route | Controller-Methode |
|-------|--------------------|
| `GET /forum` | `ForumController::index()` |
| `GET /forum/:slug` | `ForumController::showForum()` |
| `GET /forum/:slug/new-thread` | `ForumController::newThread()` |
| `POST /forum/:slug/new-thread` | `ThreadController::create()` |
| `GET /forum/thread/:id` | `ThreadController::show()` |
| `POST /forum/thread/:id/reply` | `ThreadController::reply()` |
| `GET /forum/post/:id/edit` | `PostController::edit()` |
| `POST /forum/post/:id/edit` | `PostController::update()` |
| `POST /forum/post/:id/like` | `PostController::toggleLike()` |
| `POST /forum/post/:id/report` | `PostController::report()` |
| `GET /forum/search` | `ForumController::search()` |
| `GET /forum/user/:id` | `ForumController::userProfile()` |

---

## Admin-Seiten

| Seite | Beschreibung |
|-------|-------------|
| Dashboard | Statistiken, letzte Threads, Top-User |
| Kategorien | CRUD mit Slug-Generierung |
| Foren | CRUD, Zuordnung zu Kategorien |
| Threads | Filter, Moderation (sperren/pinnen/löschen) |
| Benutzer | Suche, Sperren/Entsperren |
| Ränge | CRUD, automatische Neuberechnung |
| Berechtigungen | Matrix-Editor (Forum × Gruppe × 8 Flags) |
| Meldungen | Offene/Erledigte Meldungen bearbeiten |
| Einstellungen | 21+ konfigurierbare Optionen + Wartung |

---

## Konfiguration

Alle Einstellungen können über Admin → Forum → Einstellungen geändert werden. Standard-Werte sind in `config/config.php` definiert.

### Wichtige Einstellungen

| Schlüssel | Standard | Beschreibung |
|-----------|----------|--------------|
| `threads_per_page` | 20 | Threads pro Seite |
| `posts_per_page` | 15 | Beiträge pro Seite |
| `allow_guest_view` | 1 | Gäste dürfen lesen |
| `allow_attachments` | 1 | Dateianhänge erlaubt |
| `max_attachment_size` | 5242880 | Max. Dateigröße (5 MB) |
| `flood_interval_thread` | 60 | Sekunden zwischen Threads |
| `flood_interval_post` | 15 | Sekunden zwischen Posts |
| `enable_likes` | 1 | Like-System aktiv |
| `enable_polls` | 1 | Umfragen aktiv |
| `enable_signatures` | 1 | Signaturen anzeigen |
| `post_edit_time_limit` | 1440 | Bearbeiten bis X Min. (0 = unbegrenzt) |

---

## DSGVO

Das Plugin registriert folgende Hooks:

- `dsgvo_export_data` – Exportiert alle Forum-Daten eines Benutzers (Threads, Posts, User-Meta, Likes, Umfrage-Stimmen, Abonnements)
- `dsgvo_delete_data` – Anonymisiert Beiträge und Threads (Benutzername → „Gelöschter Benutzer"), löscht User-Meta, Likes, Abonnements

---

## Lizenz

Proprietär – 365 Network. Alle Rechte vorbehalten.
