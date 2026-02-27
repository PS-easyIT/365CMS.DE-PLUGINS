# CMS Events – Dokumentation

**Plugin:** `cms-events`  
**Version:** 1.0.0  
**Namespace:** `CMS_Events`  
**Mindest-CMS-Version:** 365CMS 2.0+  
**PHP:** 8.1+

---

## Übersicht

Das **CMS Events**-Plugin verwaltet Veranstaltungen – von Webinaren bis zu Konferenzen. Es unterstützt physische, Online- und hybride Events und bietet vollständige Speaker-Integration.

### Kernfunktionen

| Bereich | Funktion |
|---------|----------|
| **Event-Verwaltung** | Datum/Zeit, Ort, Kategorie, Kapazität, Preis |
| **Event-Typen** | Physisch, Online, Hybrid |
| **Speaker-Zuordnung** | M2M zu `cms-speakers` und `cms-experts` |
| **Kategorien** | Vordefinierte & benutzerdefinierte Kategorien |
| **Admin-Backend** | CRUD-Oberfläche unter `/admin/events` |
| **Member-Dashboard** | Eigene Events erstellen und verwalten |
| **Shortcode** | `[cms_events]` – Grid-Ansicht kommender Events |
| **Öffentliche Routen** | `/events`, `/events/{id}` |

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
/admin/events?new       → Neues Event
/admin/events?edit=N    → Event N bearbeiten
```

### Frontend
```
/events                 → Upcoming Events (Grid)
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
