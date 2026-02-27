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

### `getUpcoming(int $limit, int $offset, array $filters): array`
Gibt kommende Events (>= heute) zurück.
```php
$events = CMS_Events_Database::instance()->getUpcoming(limit: 10, offset: 0, filters: [
    'category' => 'webinar',
    'featured' => true,
]);
```

### `getById(int $id): array|false`
```php
$event = CMS_Events_Database::instance()->getById(5);
```

### `create(array $data): int`
```php
$id = CMS_Events_Database::instance()->create([
    'title'      => 'PHP Summit 2026',
    'event_date' => '2026-06-15',
    'category'   => 'konferenz',
    'is_online'  => false,
    'status'     => 'published',
]);
```

### `update(int $id, array $data): bool`
```php
CMS_Events_Database::instance()->update(5, ['is_featured' => true]);
```

### `delete(int $id): bool`

### `getSpeakers(int $event_id, string $type = 'all'): array`
```php
// Alle Speaker
$all     = CMS_Events_Database::instance()->getSpeakers(5, 'all');
// Nur cms-speakers
$speaker = CMS_Events_Database::instance()->getSpeakers(5, 'speaker');
// Nur cms-experts
$experts = CMS_Events_Database::instance()->getSpeakers(5, 'expert');
```

### `assignSpeaker(int $event_id, int $speaker_id, string $type, array $meta): void`
```php
CMS_Events_Database::instance()->assignSpeaker(
    event_id: 5,
    speaker_id: 12,
    type: 'speaker',
    meta: ['role' => 'Keynote', 'presentation_title' => 'PHP 9 Features']
);
```

### `getCategories(): array`
```php
$cats = CMS_Events_Database::instance()->getCategories();
```

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
/** @var array $event  Vollständiger Datensatz */
/** @var array $speakers Speaker-Liste mit role/presentation_title */
/** @var array $meta    Meta-Werte */
```
