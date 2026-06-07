# CMS Events Manager Plugin

**Version:** 3.0.38
**Requires:** 365CMS 3.0+  
**PHP:** 8.4+

## Description

The CMS Events Manager plugin manages events with calendar view and detail pages. This is a required core plugin for 365CMS.

## Features

- ✅ Event management with date/time support
- ✅ Custom database tables with proper relationships
- ✅ Admin interface for managing events
- ✅ Adminbereich nach 365CMS Admin Design Richtlinien mit Tabellenübersicht, Tab-Panels, Settings-Cards und inline Alerts
- ✅ Frontend display with card grid layout
- ✅ Öffentlicher Hauptnavigations-Link über Events-Einstellungen steuerbar und standardmäßig deaktiviert (`show_nav_link`, `nav_label`)
- ✅ Öffentliche Übersicht startet direkt mit der Filter-/Suchleiste ohne zusätzlichen Archivkopf
- ✅ Öffentliche Filterleiste mit primärem „Suchen“-Button und Reset nur im Empty-State
- ✅ Öffentliche Detailseite mit maximal 1160px Contentbreite, bündiger Theme-Shell, responsivem Layout und Dark Mode
- ✅ Öffentliche Detailseite im PHINIT-Preview-Layout mit Navy/Amber-Hero, Datebox, Agenda aus Speaker-Sessiondaten, Inline-SVGs und lokalem Übersetzungsfallback
- ✅ Öffentliche Übersicht mit kompakten Eventkarten, Kategorie-/Preis-Badges und Format-Badge direkt in der Ortszeile
- ✅ Eventkarten-Footer mit Speaker/Veranstalter links und Details-Button rechts
- ✅ Nicht-destruktiver De-/Uninstall; Eventdaten bleiben erhalten
- ✅ Calendar view for events
- ✅ Native 365CMS 404/Error-Fallbacks for plugin render failures
- ✅ Detail pages for individual events
- ✅ Speaker assignments (supports both speaker and expert profiles)
- ✅ Online and physical event support
- ✅ Meta data support
- ✅ Shortcode support: `[cms_events]`
- ✅ Inline-freier Admin-/Member-Workflow für Modale, Bestätigungen, Formular-Toggles und Kalendernavigation
- ✅ Idempotenter Bootstrap mit Klassen-Guards, nicht-destruktivem De-/Uninstall und robuster Schema-Migration
- ✅ Settings über `CMS\Services\SettingsService` mit Legacy-Fallback auf `cms_event_settings`

## Database Tables

### cms_events
Main table storing event information with fields:
- id, title, description, event_date, event_time
- end_date, end_time, location, address, category
- capacity, registration_url, image_url
- is_online, online_url, status, created_at, updated_at

### cms_event_speakers
Relationship table linking events to speakers/experts:
- id, event_id, speaker_id, speaker_type
- role, presentation_title, created_at

### cms_event_meta
Additional metadata for events:
- id, event_id, meta_key, meta_value, created_at

### cms_event_settings
Legacy fallback table for archive/design behavior. New writes use `CMS\Services\SettingsService` (`cms_settings`, group `cms-events`):
- id, setting_key, setting_value, updated_at

## Usage

### Admin Interface
Navigate to `/admin/events` to manage events.

### Frontend Display
- List upcoming events: `/events`
- Calendar view: `/events/calendar`
- View event detail: `/events/{id}`
- Shortcode: `[cms_events]` - Displays upcoming events in a grid
- Navigation: In den Events-Einstellungen kann der automatische Hauptmenü-Link ein-/ausgeschaltet und beschriftet werden; ohne Aktivierung wird kein Link im Hauptmenü erzeugt.

### Programmatic Access

```php
// Get upcoming events
$db = CMS\Database::instance();
$stmt = $db->prepare("
    SELECT * FROM {$db->prefix()}events 
    WHERE status = ? AND event_date >= CURDATE()
    ORDER BY event_date ASC
");
$stmt->execute(['published']);
$events = $stmt->fetchAll();

// Get event by ID
$stmt = $db->prepare("SELECT * FROM {$db->prefix()}events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

// Get speakers for an event
$stmt = $db->prepare("
    SELECT * FROM {$db->prefix()}event_speakers
    WHERE event_id = ?
");
$stmt->execute([$event_id]);
$speakers = $stmt->fetchAll();
```

## Hooks

### Actions
- `event_created` - Fired when a new event is created
- `event_speaker_assigned` - Fired when a speaker is assigned to an event

### Filters
- `event_card_content` - Modify event card HTML output

## Template Files

Templates can be added to the `templates/` directory:
- `archive-event.php` - Event list view
- `single-event.php` - Event detail view
- `event-card.php` - Card component
- `calendar-view.php` - Calendar view

## Sicherheitsstatus (2026-05-26)

- Plugin-Audit für `cms-events` auf Version `3.0.3` abgeschlossen.
- Version `3.0.4` lädt die zentralen Admin-Menü-Helper defensiv, damit der Events-Menüeintrag in der 365CMS-Sidebar zuverlässig erscheint.
- Version `3.0.5` korrigiert den PHP-Selbst-Guard der Hauptdatei nach dem funktionierenden `cms-feed`-Muster, damit `CMS_Events::instance()` beim Plugin-Laden wirklich ausgeführt wird.
- Version `3.0.6` rendert den Events-Menüeintrag direkt als Dashboard/Overview, ohne JS-Weiterleitungs-Zwischenseite.
- Version `3.0.9` ergänzt den steuerbaren, standardmäßig deaktivierten Frontend-Menülink und gleicht Archiv, Filter, Cards, Detailseite, Anmeldung sowie Related Events an das PHINIT-Design an.
- Version `3.0.10` richtet den Events-Adminbereich komplett an den 365CMS Admin Design Richtlinien aus: Overview-Tabelle, einheitliche Admin-Cards, `admin-form`-Formulare, schlankes Admin-CSS und inline Speaker-AJAX-Meldungen.
- Version `3.0.11` behebt einen Public-Template-500 auf Systemen ohne `mbstring` über Fallbacks für Lowercase/Substring in Templates und Sanitizern.
- Version `3.0.27` setzt in der Public-Events-Filterleiste einen primären „Suchen“-Button als Hauptaktion; Zurücksetzen bleibt gezielt im Empty-State.
- Version `3.0.28` begrenzt die Public-Events-Detailseite auf maximal `1160px`, hält die Hintergrund-Shell bündig zum Theme, füllt kurze Seiten bis zum Footer und sichert Responsive Layout sowie Dark Mode ab.
- Version `3.0.29` baut die Event-Detailseite nach der PHINIT-HTML-Preview neu: Navy/Amber-Hero mit Datebox, Status, Agenda aus `cms_event_speakers.presentation_title/session_time/role`, Speaker-Lineup, Teilnahme-/Social-/Details-/Venue-/Related-Sidebar, lokaler YAML-Übersetzungsfallback und Inline-SVGs statt Icon-Font. Nicht direkt vorhandene Preview-Felder wie separate Agenda-Abschnitte, Sprache und Anmeldeschluss werden nicht erfunden; Sprache fällt auf den lokalen Default zurück.
- Version `3.0.33` härtet den Admin-Save-Pfad für Event-Bearbeiten/Speichern: EditorService-/Sanitizer-Ausnahmen und DB-Updatefehler werden abgefangen, leere Adminfelder bekommen sichere Fallbacks und führen nicht mehr zu 500-Serverfehlern.
- Version `3.0.34` zieht die vollständige Event-Spaltenmigration beim Speichern defensiv nach, damit bestehende Installationen mit altem Schema nicht mehr als „Datenbank-Fehler“ beim Bearbeiten abbrechen.
- Version `3.0.35` filtert Save-Daten zusätzlich gegen die tatsächlich vorhandenen Tabellenspalten und nutzt `SHOW COLUMNS` als Fallback, falls `INFORMATION_SCHEMA` serverseitig nicht lesbar ist.
- Version `3.0.36` ergänzt einen konservativen Legacy-Retry, falls ein vollständiges Update auf Alt-Schemas weiterhin von einzelnen modernen Feldern blockiert wird.
- Version `3.0.37` ergänzt als letzte Rückfallebene ein spaltenweises Update, damit kompatible Event-Felder weiter gespeichert werden und einzelne problematische Alt-Schema-Felder nicht mehr den gesamten Admin-Save blockieren.
- Version `3.0.38` ergänzt im Admin-Handler einen direkten Prepared-Statement-Fallback, der den DB-Service umgeht und vorhandene kompatible Spalten beim Bearbeiten einzeln speichert.
- Bootstrap und Include-Dateien sind gegen doppelte Ladepfade/klassische Redeclare-Fatals abgesichert.
- DB-Migrationen nutzen `INFORMATION_SCHEMA` statt `SHOW COLUMNS`, Foreign Keys werden idempotent und nicht-blockierend ergänzt.
- Plugin-Settings werden primär über den 365CMS `SettingsService` gelesen/geschrieben; die alte `event_settings`-Tabelle bleibt nur als kompatibler Fallback.
- Speaker-AJAX, Admin-Approve, Member-Create und Template-Fallbacks liefern konsistente Fehlerantworten und protokollieren technische Details serverseitig.

## Event Types

The plugin supports both physical and online events:
- Physical events: Set location and address fields
- Online events: Set `is_online = TRUE` and provide `online_url`
- Hybrid events: Set both physical and online details

## Installation

The plugin is automatically activated during 365CMS setup. Database tables are created on first activation.

## License

Part of 365CMS Core - All Rights Reserved
