# CMS Events – Dokumentation

**Plugin:** `cms-events`  
**Version:** 3.0.33
**Namespace:** `CMS_Events`  
**Aktueller Laufzeitstand:** 365CMS 3.0+  
**Audit-/Dokustand:** PHINIT-Preview-Detailseite am 2026-05-31
**PHP:** 8.4+

---

## Übersicht

Das **CMS Events**-Plugin verwaltet Veranstaltungen – von Webinaren bis zu Konferenzen. Es unterstützt physische, Online- und hybride Events und bietet vollständige Speaker-Integration.

## 365CMS-3.x-Status

- Version `3.0.3` enthält den aktuellen Audit-/Stabilitätsstand für PHP 8.4 und 365CMS 3.x.
- Version `3.0.4` behebt die defensive Admin-Menü-Registrierung, sodass der Events-Eintrag in der Sidebar zuverlässig sichtbar ist.
- Version `3.0.5` behebt den Hauptdatei-Guard, damit `CMS_Events::instance()` beim Laden des Plugins tatsächlich ausgeführt wird.
- Version `3.0.6` rendert den Events-Menüeintrag direkt als Dashboard/Overview statt über eine JS-Weiterleitung.
- Version `3.0.9` ergänzt die steuerbare öffentliche Hauptnavigation (`show_nav_link`, `nav_label`), lässt den Link standardmäßig deaktiviert und gleicht Archiv, Cards, Detailseite, Anmeldung und Related Events an das PHINIT-Publicsite-Design an.
- Version `3.0.10` überarbeitet den Events-Adminbereich gemäß 365CMS Admin Design Richtlinien mit Admin-Tabelle, einheitlichen Tab-Panels, Settings-Cards, `admin-form`-Formularen und inline Speaker-AJAX-Alerts.
- Version `3.0.11` behebt einen Public-Template-500 auf Instanzen ohne `mbstring` durch robuste Fallbacks in Templates und Sanitizern.
- Version `3.0.14` ergänzt die final offenen Publicsite-Deltas für Eventkarten, Share-Buttons, Meta-Zeile und Related-Event-Bildplatzhalter.
- Version `3.0.15` setzt aktuelle Monats-/Jahresfilter als Default, macht Eventkarten kompakter und klickbar, ergänzt robuste Share-Links samt Tabler-Fallback und vereinheitlicht Related-Event-Placeholder.
- Version `3.0.16` koppelt Archivfilter und Pagination serverseitig: Standardansicht ab aktuellem Monat, Filterparameter in Seitenlinks, Seite-1-Reset bei Filterwechsel und graue Markierung vergangener Events.
- Version `3.0.17` verhindert doppelte Ortsausgaben in Eventkarten und ergänzt pro Archivkarte oben rechts eine Kosten-/Preis-Pill für kostenlose, kostenpflichtige oder spendenbasierte Events.
- Version `3.0.18` macht die Public-Ausgabe vollständiger und zugleich ruhiger: Archivkarten zeigen Bild, dezente Kategorie-/Format-Badges, Uhrzeit sowie Ort und Veranstalter mit Icon direkt unter dem Titel; Tags/Themen erscheinen nur noch in der Detailseite, deren Meta-Angaben als einzelne Icon-Badges inklusive zweizeiligem Start-/Endtermin für Mehrtagesevents dargestellt werden.
- Version `3.0.19` entfernt die redundanten Meta-Ausgaben direkt unter dem Eventtitel inklusive Teaser-/Excerpt-Zeile; Sidebar-Meta und Veranstalter-Kontakt bleiben erhalten, Themen/Tags erscheinen unterhalb der Beschreibung als einzelne, deduplizierte Badges.
- Version `3.0.20` ersetzt `extract()` im Template-Loader durch eine explizite Whitelist kontrollierter Template-Kontextvariablen für Plugin-Templates und Theme-Overrides.
- Version `3.0.21` entfernt den Veranstalter aus den Archivkarten und platziert das Online-/Hybrid-/Präsenz-Badge mit Icon in der Ortszeile vor dem Veranstaltungsort.
- Version `3.0.22` entfernt den Kopfbereich oberhalb der Events-Suche, sodass die Übersicht direkt mit der Filter-/Suchleiste beginnt.
- Version `3.0.23` ergänzt einen zweispaltigen Card-Footer mit Speaker/Veranstalter links und Details-Button rechts; Speaker haben Priorität, leere Infos bleiben ohne Placeholder.
- Version `3.0.24` setzt den Code-Audit um: nicht-destruktiver Uninstall, zusammengesetzter Datums-/Statusindex, Settings-Cache, sichere Datumsvergleiche, novalidate-Formulare und entfernte Inline-Handler.
- Version `3.0.27` setzt in der Public-Events-Filterleiste einen primären „Suchen“-Button als Hauptaktion; Zurücksetzen bleibt gezielt im Empty-State verfügbar.
- Version `3.0.28` begrenzt die Public-Events-Detailseite auf maximal `1160px`, hält die Hintergrund-Shell bündig zum Theme, füllt kurze Seiten bis zum Footer und sichert Responsive Layout sowie Dark Mode ab.
- Version `3.0.29` ersetzt die Event-Detailseite durch das PHINIT-Preview-Layout mit Navy/Amber-Hero, Datebox, Status, Agenda aus zugeordneten Speaker-Sessiondaten, Speaker-Lineup, Teilnahme-/Social-/Details-/Venue-/Related-Sidebar, Inline-SVGs statt Icon-Font und lokalem YAML-Übersetzungsfallback.
- Version `3.0.30` entfernt den externen Tabler-Icons-CDN-Fallback und lädt die Icon-Kompatibilität ausschließlich über das lokale Core-Asset `/assets/tabler-icons/tabler-icons.min.css`.
- Version `3.0.33` härtet den Admin-Save-Pfad für Event-Bearbeiten/Speichern: EditorService-/Sanitizer-Ausnahmen und DB-Updatefehler werden abgefangen, leere Adminfelder bekommen sichere Fallbacks und führen nicht mehr zu 500-Serverfehlern.
- Bootstrap, Include-Klassen und Lifecycle-Hooks sind idempotent und gegen Redeclare-Fatals abgesichert.
- Datenbankmigrationen nutzen `INFORMATION_SCHEMA`, erstellen die Settings-Tabelle im Installer und ergänzen Foreign Keys nicht-blockierend.
- Öffentliche Fehlerpfade nutzen die 365CMS-404/Error-Fallbacks und protokollieren technische Details serverseitig.

### Kernfunktionen

| Bereich | Funktion |
|---------|----------|
| **Event-Verwaltung** | Datum/Zeit, Ort, Kategorie, Kapazität, Preis |
| **Event-Typen** | Physisch, Online, Hybrid |
| **Speaker-Zuordnung** | M2M zu `cms-speakers` und `cms-experts` |
| **Kategorien** | Vordefinierte & benutzerdefinierte Kategorien |
| **Admin-Backend** | CRUD-Oberfläche unter `/admin/events` im 365CMS Admin-Design |
| **Member-Dashboard** | Eigene Events erstellen und verwalten |
| **Shortcode** | `[cms_events]` – Grid-Ansicht kommender Events |
| **Öffentliche Routen** | `/events`, `/events/calendar`, `/events/{id}` |
| **Navigation** | Frontend-Menülink über Events-Einstellungen aktivierbar/deaktivierbar, Standard: aus |

---

## Dateistruktur

```
cms-events/
├── cms-events.php
├── README.md
├── update.json
├── includes/
│   ├── class-database.php
│   ├── class-admin.php
│   ├── class-member-dashboard.php
│   ├── class-meta-boxes.php
│   ├── class-post-type.php
│   ├── class-shortcode.php
│   ├── class-taxonomies.php
│   └── class-template-loader.php
├── templates/
│   ├── archive-event.php
│   ├── calendar-view.php
│   ├── event-card.php
│   └── single-event.php
└── assets/
    ├── css/
    └── js/
```

---

## Weitere Dokumente

| Dokument | Inhalt |
|----------|--------|
| [DATABASE.md](DATABASE.md) | Tabellen, Schemas, Indizes |
| [HOOKS.md](HOOKS.md) | Actions & Filter |
| [API.md](API.md) | Klassen- und Methoden-Referenz |
| [CHANGELOG.md](CHANGELOG.md) | Versionshistorie |
| [SECURITY.md](SECURITY.md) | Sicherheitskonzept |

---

## Schnellstart

### Admin-Interface
```
/admin/events           → Übersicht
/admin/events/new       → Neues Event
/admin/events/edit/N    → Event N bearbeiten
```

### Frontend
```
/events                 → Upcoming Events (Grid)
/events/calendar        → Kalenderansicht
/events/{id}            → Event-Detailseite
```

### Shortcode
```html
[cms_events]
[cms_events limit="6" category="Konferenz"]
[cms_events upcoming="true" featured="true"]
```

### PHP-Zugriff

```php
$db = CMS\Database::instance();

// Kommende Events
$stmt = $db->prepare("
    SELECT * FROM {$db->prefix()}events
    WHERE status = 'published' AND event_date >= CURDATE()
    ORDER BY event_date ASC
    LIMIT ?
");
$stmt->execute([10]);
$events = $stmt->fetchAll();

// Speaker eines Events
$stmt = $db->prepare("
    SELECT s.*, es.role, es.presentation_title
    FROM {$db->prefix()}speakers s
    INNER JOIN {$db->prefix()}event_speakers es ON s.id = es.speaker_id
    WHERE es.event_id = ? AND es.speaker_type = 'speaker'
");
$stmt->execute([$event_id]);
$speakers = $stmt->fetchAll();
```

---

## Event-Typen

| Typ | `is_online` | `location` | `online_url` |
|-----|-------------|------------|--------------|
| Physisch | FALSE | gesetzt | — |
| Online | TRUE | — | gesetzt |
| Hybrid | TRUE | gesetzt | gesetzt |

---

## Preis-Typen

| `price_type` | Beschreibung |
|-------------|--------------|
| `free` | Kostenloses Event |
| `paid` | Kostenpflichtiges Event (`price` + `price_currency`) |
| `donation` | Spendenbasiert |

---

## Cross-Plugin-Integration

| Plugin | Richtung | Beschreibung |
|--------|----------|--------------|
| `cms-speakers` | ← | Speaker werden Events via `cms_event_speakers` zugeordnet |
| `cms-experts` | ← | Experten können als Speaker verknüpft werden (`speaker_type = 'expert'`) |
| `cms-companies` | ← | Firmen als Veranstalter referenzierbar |

**Guard-Pattern:**
```php
if (CMS\PluginManager::instance()->isPluginActive('cms-speakers')) {
    // Speaker-Integration aktiv
}
```
