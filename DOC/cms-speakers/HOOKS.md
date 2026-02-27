# CMS Speakers – Hooks-Referenz

## Actions

### `speaker_created`
```php
CMS\Hooks::doAction('speaker_created', int $speaker_id, array $data);
```

### `speaker_updated`
```php
CMS\Hooks::doAction('speaker_updated', int $speaker_id, array $new_data, array $old_data);
```

### `speaker_deleted`
```php
CMS\Hooks::doAction('speaker_deleted', int $speaker_id);
```

### `speaker_presentation_added`
Ausgelöst wenn ein neuer Auftritt/Vortrag gespeichert wird.
```php
CMS\Hooks::doAction('speaker_presentation_added', int $speaker_id, array $event_data);
```

### `speaker_topic_added`
```php
CMS\Hooks::doAction('speaker_topic_added', int $speaker_id, string $topic_name);
```

### `speaker_availability_changed`
```php
CMS\Hooks::doAction('speaker_availability_changed', int $speaker_id, string $old, string $new);
```

---

## Filters

### `speaker_card_content`
```php
CMS\Hooks::applyFilters('speaker_card_content', string $html, array $speaker): string;
```

### `speaker_query_args`
```php
CMS\Hooks::applyFilters('speaker_query_args', array $args): array;
// Default:
[
    'status'       => 'active',
    'availability' => null,
    'featured'     => false,
    'verified'     => false,
    'format'       => null,   // 'keynote', 'workshop', etc.
    'limit'        => 12,
    'offset'       => 0,
    'orderby'      => 'last_name',
    'order'        => 'ASC',
]
```

### `speaker_fee_display`
Ermöglicht eigenes Formatieren der Honnorar-Anzeige.
```php
CMS\Hooks::applyFilters('speaker_fee_display', string $formatted, float $min, float $max): string;
```

---

## CMS-Core-Hooks

| Hook | Callback | Priorität |
|------|----------|-----------|
| `cms_init` | `CMS_Speakers::init_plugin()` | 10 |
| `plugin_activated` | `CMS_Speakers::on_activation()` | 10 |
| `head` | `CMS_Speakers::enqueue_styles()` | 10 |
| `body_end` | `CMS_Speakers::enqueue_scripts()` | 10 |
| `cms_admin_menu` | `CMS_Speakers_Admin::register_menu()` | 10 |
| `cms_member_dashboard` | `CMS_Speakers_Member_Dashboard::render()` | 20 |
