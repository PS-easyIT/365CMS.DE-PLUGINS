# CMS Companies – Hooks-Referenz

## Actions (Ereignisse)

### `company_created`

Wird ausgelöst, nachdem eine neue Firma erfolgreich in der Datenbank gespeichert wurde.

```php
CMS\Hooks::doAction('company_created', int $company_id, array $data);
```

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `$company_id` | int | ID der neu erstellten Firma |
| `$data` | array | Alle gespeicherten Felder |

**Verwendungsbeispiel:**
```php
CMS\Hooks::addAction('company_created', function(int $id, array $data): void {
    // z.B. Willkommens-E-Mail senden
    $mailer = CMS\Mail::instance();
    $mailer->send($data['email'], 'Willkommen', 'Ihr Profil wurde erstellt.');
}, 10);
```

---

### `company_updated`

Wird ausgelöst, nachdem eine Firma aktualisiert wurde.

```php
CMS\Hooks::doAction('company_updated', int $company_id, array $new_data, array $old_data);
```

---

### `company_deleted`

Wird ausgelöst, bevor eine Firma gelöscht wird (Soft-Delete oder Hard-Delete).

```php
CMS\Hooks::doAction('company_deleted', int $company_id);
```

---

### `company_expert_assigned`

Wird ausgelöst, wenn ein Experte einer Firma zugeordnet wird.

```php
CMS\Hooks::doAction('company_expert_assigned', int $company_id, int $expert_id, string $role);
```

---

### `company_expert_removed`

Wird ausgelöst, wenn ein Experte aus einer Firma entfernt wird.

```php
CMS\Hooks::doAction('company_expert_removed', int $company_id, int $expert_id);
```

---

## Filters (Wert-Modifikatoren)

### `company_card_content`

Ermöglicht die Modifikation des HTML-Outputs einer Firmenkarte.

```php
CMS\Hooks::applyFilters('company_card_content', string $html, array $company): string;
```

**Verwendungsbeispiel:**
```php
CMS\Hooks::addFilter('company_card_content', function(string $html, array $company): string {
    if ($company['is_top_partner']) {
        $html = '<div class="top-partner-wrapper">' . $html . '</div>';
    }
    return $html;
}, 10);
```

---

### `company_query_args`

Modifiziert die SQL-WHERE-Bedingungen für Firmen-Abfragen im Frontend.

```php
CMS\Hooks::applyFilters('company_query_args', array $args): array;
```

**Struktur von `$args`:**
```php
[
    'status'       => 'active',
    'industry'     => null,
    'is_partner'   => false,
    'limit'        => 12,
    'offset'       => 0,
    'orderby'      => 'name',
    'order'        => 'ASC',
]
```

---

### `company_meta_value`

Filtert einen einzelnen Meta-Wert vor der Ausgabe.

```php
CMS\Hooks::applyFilters('company_meta_value', mixed $value, string $meta_key, int $company_id): mixed;
```

---

## CMS-Core-Hooks (Plugin registriert sich auf diese)

| Hook | Callback | Priorität |
|------|----------|-----------|
| `cms_init` | `CMS_Companies::init_plugin()` | 10 |
| `plugin_activated` | `CMS_Companies::on_activation()` | 10 |
| `head` | `CMS_Companies::enqueue_styles()` | 10 |
| `body_end` | `CMS_Companies::enqueue_scripts()` | 10 |
| `cms_admin_menu` | `CMS_Companies_Admin::register_menu()` | 10 |
| `cms_member_dashboard` | `CMS_Companies_Member_Dashboard::render()` | 20 |

---

## Beispiel: Eigene Erweiterung

```php
// In einem Custom-Plugin oder functions.php

// Firma nach Erstellung in einem externen System anlegen
CMS\Hooks::addAction('company_created', function(int $id, array $data): void {
    $crm = MyCRM::instance();
    $crm->createContact([
        'name'  => $data['name'],
        'email' => $data['email'],
        'type'  => 'company',
    ]);
}, 20);

// Karten-Output um "Verifiziert"-Badge erweitern
CMS\Hooks::addFilter('company_card_content', function(string $html, array $co): string {
    $badge = CMS_Companies_Database::instance()->getMeta($co['id'], 'verified')
        ? '<span class="badge-verified">✅ Verifiziert</span>'
        : '';
    return str_replace('</div>', $badge . '</div>', $html, 1);
}, 15);
```
