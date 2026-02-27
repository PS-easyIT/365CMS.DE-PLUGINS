---
applyTo: "*/includes/class-database.php"
---

# 365CMS Plugin – Datenbank-Klassen-Regeln

## Tabellen via create_tables()

```php
public function create_tables(): void
{
    $db     = CMS\Database::instance();
    $pdo    = $db->getPdo();
    $prefix = $db->prefix();

    $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}table_name (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id    INT UNSIGNED DEFAULT NULL,
        title      VARCHAR(255) NOT NULL,
        status     VARCHAR(20)  NOT NULL DEFAULT 'active',
        created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user   (user_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
```

## Migration (ALTER TABLE)

```php
$cols = $pdo->query("SHOW COLUMNS FROM {$prefix}table LIKE 'new_col'")->fetchAll();
if (empty($cols)) {
    $pdo->exec("ALTER TABLE {$prefix}table ADD COLUMN new_col VARCHAR(100) DEFAULT NULL");
}
```

## Immer utf8mb4

Engine: `InnoDB`, Charset: `utf8mb4`, Collate: `utf8mb4_unicode_ci`

## Keine Hard-codierten Tabellennamen

Immer `$db->prefix()` verwenden: `{$prefix}companies`, nicht `cms_companies`.

## JSON-Felder

JSON-Arrays/Objekte in `TEXT` oder `LONGTEXT` speichern; im PHP mit `json_encode()` / `json_decode()` arbeiten.
