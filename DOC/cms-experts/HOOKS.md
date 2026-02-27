# CMS Experts – Hooks-Referenz

## Actions

### `expert_created`
```php
CMS\Hooks::doAction('expert_created', int $expert_id, array $data);
```

### `expert_updated`
```php
CMS\Hooks::doAction('expert_updated', int $expert_id, array $new_data, array $old_data);
```

### `expert_deleted`
```php
CMS\Hooks::doAction('expert_deleted', int $expert_id);
```

### `expert_availability_changed`
Ausgelöst wenn sich der Verfügbarkeitsstatus ändert.
```php
CMS\Hooks::doAction('expert_availability_changed', int $expert_id, string $old_status, string $new_status);
```

### `expert_skill_added`
```php
CMS\Hooks::doAction('expert_skill_added', int $expert_id, string $skill_name, string $level);
```

### `expert_certification_added`
```php
CMS\Hooks::doAction('expert_certification_added', int $expert_id, array $cert_data);
```

---

## Filters

### `expert_card_content`
```php
CMS\Hooks::applyFilters('expert_card_content', string $html, array $expert): string;
```

### `expert_query_args`
```php
CMS\Hooks::applyFilters('expert_query_args', array $args): array;
// Default $args:
[
    'status'         => 'active',
    'availability'   => null,
    'skill'          => null,
    'partner_status' => null,
    'limit'          => 12,
    'offset'         => 0,
    'orderby'        => 'last_name',
    'order'          => 'ASC',
]
```

### `expert_profile_tabs`
Ermöglicht das Hinzufügen eigener Tabs zur Detailseite.
```php
CMS\Hooks::applyFilters('expert_profile_tabs', array $tabs, array $expert): array;
// $tabs = [['id' => 'my-tab', 'label' => 'Mein Tab', 'content' => '<div>...</div>']]
```

### `expert_meta_value`
```php
CMS\Hooks::applyFilters('expert_meta_value', mixed $value, string $key, int $expert_id): mixed;
```

---

## CMS-Core-Hooks (Plugin registriert sich auf diese)

| Hook | Callback | Priorität |
|------|----------|-----------|
| `cms_init` | `CMS_Experts::init_plugin()` | 10 |
| `plugin_activated` | `CMS_Experts::on_activation()` | 10 |
| `head` | `CMS_Experts::enqueue_styles()` | 10 |
| `body_end` | `CMS_Experts::enqueue_scripts()` | 10 |
| `cms_admin_menu` | `CMS_Experts_Admin::register_menu()` | 10 |
| `cms_member_dashboard` | `CMS_Experts_Member_Dashboard::render()` | 20 |
| `dsgvo_export_data` | `CMS_Experts::export_user_data()` | 10 |
| `dsgvo_delete_data` | `CMS_Experts::delete_user_data()` | 10 |
