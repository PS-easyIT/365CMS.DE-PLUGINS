# CMS Companies – API-Referenz

## Klassen-Übersicht

| Klasse | Datei | Beschreibung |
|--------|-------|--------------|
| `CMS_Companies` | `cms-companies.php` | Singleton-Bootstrap |
| `CMS_Companies_Database` | `includes/class-database.php` | DB-Operationen |
| `CMS_Companies_Admin` | `includes/class-admin.php` | Admin-Registrierung |
| `CMS_Companies_Member_Dashboard` | `includes/class-member-dashboard.php` | Member-Views |
| `CMS_Companies_Shortcode` | `includes/class-shortcode.php` | Shortcode-Handler |
| `CMS_Companies_Template_Loader` | `includes/class-template-loader.php` | Frontend-Routing |

---

## `CMS_Companies_Database`

### `getAll(int $limit, int $offset, array $filters): array`

```php
$db    = CMS_Companies_Database::instance();
$items = $db->getAll(limit: 20, offset: 0, filters: [
    'status'   => 'active',
    'industry' => 'IT',
]);
```

**Filter-Optionen:**

| Key | Typ | Standardwert |
|-----|-----|----------|
| `status` | string | `'active'` |
| `industry` | string\|null | `null` |
| `is_partner` | bool | `false` |
| `search` | string | `''` |
| `orderby` | string | `'name'` |
| `order` | string | `'ASC'` |

---

### `getById(int $id): array|false`

```php
$company = CMS_Companies_Database::instance()->getById(42);
if ($company) {
    echo htmlspecialchars($company['name']);
}
```

---

### `create(array $data): int`

Erstellt eine neue Firma. Gibt die neue ID zurück.

```php
$id = CMS_Companies_Database::instance()->create([
    'name'     => 'Muster GmbH',
    'email'    => 'info@muster.de',
    'industry' => 'IT',
    'status'   => 'active',
]);
```

---

### `update(int $id, array $data): bool`

```php
$success = CMS_Companies_Database::instance()->update(42, [
    'is_partner' => true,
    'status'     => 'active',
]);
```

---

### `delete(int $id): bool`

```php
CMS_Companies_Database::instance()->delete(42);
```

---

### `getMeta(int $company_id, string $key, mixed $default = null): mixed`

```php
$linkedin = CMS_Companies_Database::instance()->getMeta(42, 'social_linkedin');
```

---

### `setMeta(int $company_id, string $key, mixed $value): void`

```php
CMS_Companies_Database::instance()->setMeta(42, 'social_linkedin', 'https://linkedin.com/company/muster');
```

---

### `assignExpert(int $company_id, int $expert_id, string $role = ''): void`

```php
CMS_Companies_Database::instance()->assignExpert(
    company_id: 42,
    expert_id: 7,
    role: 'CTO'
);
```

---

### `removeExpert(int $company_id, int $expert_id): void`

```php
CMS_Companies_Database::instance()->removeExpert(42, 7);
```

---

### `getExperts(int $company_id, bool $current_only = true): array`

```php
$experts = CMS_Companies_Database::instance()->getExperts(42, current_only: true);
```

---

### `getCount(array $filters = []): int`

```php
$total = CMS_Companies_Database::instance()->getCount(['status' => 'active']);
```

---

## Shortcode-API

```html
[cms_companies]
```

| Attribut | Standardwert | Beschreibung |
|----------|----------|--------------|
| `limit` | `12` | Maximale Anzahl |
| `industry` | `''` | Filter nach Branche |
| `partner_only` | `false` | Nur Partner anzeigen |
| `orderby` | `name` | Sortierfeld |
| `order` | `ASC` | Sortierrichtung |
| `columns` | `3` | Grid-Spalten |

---

## Template-Überschreibung

Eigene Templates in einem Theme ablegen:

```
themes/mein-theme/
└── plugins/
    └── cms-companies/
        ├── archive-company.php
        ├── company-card.php
        └── single-company.php
```

Im Template stehen folgende Variablen zur Verfügung:

**`company-card.php`:**
```php
/** @var array $company Firma-Datensatz */
$company['id'], $company['name'], $company['industry'], $company['logo_url'],
$company['location_city'], $company['is_partner'], $company['is_top_partner']
```

**`single-company.php`:**
```php
/** @var array $company Vollständiger Datensatz inkl. Meta */
/** @var array $experts Aktuell zugeordnete Experten */
/** @var array $meta    Array aller Meta-Keys der Firma */
```

---

## REST-ähnliche AJAX-Endpunkte

Das Plugin registriert folgende AJAX-Endpunkte (POST auf die Admin-URL):

| Action | Beschreibung | Auth |
|--------|--------------|------|
| `cms_companies_save` | Firma speichern/aktualisieren | Admin |
| `cms_companies_delete` | Firma löschen | Admin |
| `cms_companies_assign_expert` | Experten zuordnen | Admin |
| `cms_companies_remove_expert` | Experten entfernen | Admin |
| `cms_companies_get_meta` | Meta-Wert abfragen | Admin |
| `cms_companies_set_meta` | Meta-Wert setzen | Admin |

Alle Endpunkte erfordern CSRF-Token (`csrf_token`-POST-Feld).
