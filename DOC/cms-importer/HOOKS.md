# CMS WordPress Importer – Hooks-Referenz

## Actions

### `import_started`
Ausgelöst wenn ein Import-Run beginnt.
```php
CMS\Hooks::doAction('import_started', int $log_id, string $filename);
```

### `import_completed`
Ausgelöst wenn ein Import-Run abgeschlossen ist.
```php
CMS\Hooks::doAction('import_completed', int $log_id, array $stats);
// $stats: ['posts_ok' => N, 'posts_fail' => N, 'pages_ok' => N, 'pages_fail' => N]
```

### `import_failed`
Ausgelöst wenn ein Import-Run fehlschlägt.
```php
CMS\Hooks::doAction('import_failed', int $log_id, string $error_message);
```

### `post_imported`
Ausgelöst nach dem erfolgreichen Import eines Beitrags.
```php
CMS\Hooks::doAction('post_imported', int $new_post_id, array $wp_post_data);
```

### `page_imported`
```php
CMS\Hooks::doAction('page_imported', int $new_page_id, array $wp_page_data);
```

---

## Filters

### `import_post_data`
Ermöglicht das Modifizieren der Daten vor dem Einfügen in `cms_posts`.
```php
CMS\Hooks::applyFilters('import_post_data', array $data, array $wp_raw): array;
```

### `import_meta_mapping`
Ermöglicht das Hinzufügen eigener Meta-Key-Mappings.
```php
CMS\Hooks::applyFilters('import_meta_mapping', array $mapping): array;
// $mapping = ['wp_meta_key' => 'cms_field_name', ...]
```

**Beispiel:**
```php
CMS\Hooks::addFilter('import_meta_mapping', function(array $m): array {
    $m['_my_custom_field'] = 'custom_data';
    return $m;
});
```

### `import_should_skip_post`
Ermöglicht das Überspringen bestimmter Posts beim Import.
```php
CMS\Hooks::applyFilters('import_should_skip_post', bool $skip, array $wp_post): bool;
```
