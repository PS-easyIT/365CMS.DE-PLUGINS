# CMS Speakers – Hooks-Referenz

## Actions

### `speaker_created`
```php
CMS\Hooks::doAction('speaker_created', int $speaker_id, array $data);
```

### `speaker_updated`
```php
CMS\Hooks::doAction('speaker_updated', int $speaker_id, array $data);
```

### `cms_speakers_activated`
```php
CMS\Hooks::doAction('cms_speakers_activated');
```

### `cms_speakers_deactivated`
```php
CMS\Hooks::doAction('cms_speakers_deactivated');
```

Weitere Event-/Topic-Hooks können bei Bedarf ergänzt werden; Version 3.0.3 feuert bewusst nur die oben genannten CRUD- und Lifecycle-Hooks.

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
| `plugin_deactivated` | `CMS_Speakers::on_deactivation()` | 10 |
| `plugin_uninstalled` | `CMS_Speakers::on_uninstall()` | 10 |
| `head` | `CMS_Speakers::enqueue_styles()` | 10 |
| `body_end` | `CMS_Speakers::enqueue_scripts()` | 10 |
| `register_routes` | `CMS_Speakers_Post_Type::register_routes()` | 10 |
| `main_nav` | `CMS_Speakers_Post_Type::add_menu_item()` | 10 |
| `cms_admin_menu` | `CMS_Speakers_Admin::register_admin_menu()` | 10 |
| `member_dashboard_init` | `CMS_Speakers_Member_Dashboard::register()` | 10 |
