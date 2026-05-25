# CMS Events – Hooks-Referenz

## Actions

### `event_created`
Ausgelöst nach dem Erstellen eines neuen Events.
```php
CMS\Hooks::doAction('event_created', int $event_id, array $data);
```

### `event_updated`
Ausgelöst nach dem Aktualisieren eines Events.
```php
CMS\Hooks::doAction('event_updated', int $event_id, array $new_data, array $old_data);
```

### `event_cancelled`
Ausgelöst wenn ein Event auf `cancelled` gesetzt wird.
```php
CMS\Hooks::doAction('event_cancelled', int $event_id);
```

### `event_speaker_assigned`
Ausgelöst wenn ein Speaker/Experte einem Event zugeordnet wird.
```php
CMS\Hooks::doAction('event_speaker_assigned', int $event_id, int $speaker_id, string $type);
```
| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `$event_id` | int | Event-ID |
| `$speaker_id` | int | Speaker- oder Experten-ID |
| `$type` | string | `'speaker'` oder `'expert'` |

### `event_speaker_removed`
```php
CMS\Hooks::doAction('event_speaker_removed', int $event_id, int $speaker_id);
```

---

## Filters

### `event_card_content`
Modifiziert den HTML-Output einer Event-Karte.
```php
CMS\Hooks::applyFilters('event_card_content', string $html, array $event): string;
```

### `event_query_args`
Modifiziert die Abfrage-Parameter für Event-Listen.
```php
CMS\Hooks::applyFilters('event_query_args', array $args): array;
```
```php
// Standardwerte
$args = [
    'status'    => 'published',
    'upcoming'  => true,       // nur Events >= heute
    'category'  => null,
    'featured'  => false,
    'limit'     => 12,
    'offset'    => 0,
    'orderby'   => 'event_date',
    'order'     => 'ASC',
];
```

### `event_registration_url`
Ermöglicht das Umschreiben der Anmeldungs-URL.
```php
CMS\Hooks::applyFilters('event_registration_url', string $url, array $event): string;
```

---

## CMS-Core-Hooks (Plugin registriert sich auf diese)

| Hook | Callback | Priorität |
|------|----------|-----------|
| `cms_init` | `CMS_Events::init_plugin()` | 10 |
| `plugin_activated` | `CMS_Events::on_activation()` | 10 |
| `plugin_deactivated` | `CMS_Events::on_deactivation()` | 10 |
| `plugin_uninstalled` | `CMS_Events::on_uninstall()` | 10 |
| `head` | `CMS_Events::enqueue_styles()` | 10 |
| `body_end` | `CMS_Events::enqueue_scripts()` | 10 |
| `cms_admin_menu` | `CMS_Events_Admin::register_admin_menu()` | 10 |
| `member_dashboard_init` | `CMS_Events_Member_Dashboard::register()` | 10 |
| `member_plugin_section_head` | `CMS_Events_Member_Dashboard::enqueueEventStyles()` | 10 |
