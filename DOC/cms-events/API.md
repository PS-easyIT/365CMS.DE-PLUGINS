# CMS Events – API-Referenz

## Klassen

| Klasse | Datei |
|--------|-------|
| `CMS_Events` | `cms-events.php` |
| `CMS_Events_Database` | `includes/class-database.php` |
| `CMS_Events_Admin` | `includes/class-admin.php` |
| `CMS_Events_Member_Dashboard` | `includes/class-member-dashboard.php` |
| `CMS_Events_Shortcode` | `includes/class-shortcode.php` |
| `CMS_Events_Template_Loader` | `includes/class-template-loader.php` |
| `CMS_Events_Taxonomies` | `includes/class-taxonomies.php` |

---

## `CMS_Events_Database` – Methoden

### `get_events(array $args = []): array`
Gibt Event-Listen zurück. `limit`/`offset`, Status-, Kategorie-, Stadt-, Monats- und Zeitfilter werden defensiv normalisiert.
```php
$events = CMS_Events_Database::instance()->get_events([
    'limit' => 10,
    'category' => 'webinar',
    'upcoming' => true,
]);
```

### `get_event(int $id): object|false`
```php
$event = CMS_Events_Database::instance()->get_event(5);
```

### `save_event(array $data): int`
```php
$id = CMS_Events_Database::instance()->save_event([
    'title'      => 'PHP Summit 2026',
    'event_date' => '2026-06-15',
    'category'   => 'konferenz',
    'is_online'  => false,
    'status'     => 'published',
]);
```

### `delete_event(int $id): bool`

### `get_event_speakers(int $event_id): array`
```php
$speakers = CMS_Events_Database::instance()->get_event_speakers(5);
```

### `assign_speaker(int $event_id, int $speaker_id, string $type, array $meta): bool`
```php
$ok = CMS_Events_Database::instance()->assign_speaker(
    5,
    12,
    'speaker',
    ['role' => 'Keynote', 'presentation_title' => 'PHP 9 Features']
);
```

### `get_event_categories(): array`
```php
$cats = CMS_Events_Database::instance()->get_event_categories();
```

### `drop_tables(): void`
Kompatibilitäts-Stub: Deaktivierung und Uninstall behalten Event-, Relation-, Meta-, Preset- und Settings-Tabellen bewusst bei.

---

## Shortcode-Attribute

```html
[cms_events limit="12" category="" featured="false" upcoming="true" orderby="event_date" order="ASC"]
```

---

## Template-Variablen

**`event-card.php`:**
```php
/** @var array $event */
$event['id'], $event['title'], $event['event_date'], $event['event_time'],
$event['location'], $event['city'], $event['is_online'], $event['category'],
$event['image_url'], $event['price_type'], $event['price'], $event['is_featured']
```

**`single-event.php`:**
```php
/** @var object $event  Vollständiger Datensatz */
/** @var array $speakers Speaker-Liste mit role/presentation_title */
/** @var array $settings Plugin-Settings */
```

**`calendar-view.php`:**
```php
/** @var array $events Monats-Events */
/** @var string $month YYYY-MM */
/** @var string $view month|week */
/** @var array $settings Plugin-Settings */
```
