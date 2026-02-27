---
applyTo: "**/*.php"
---

# 365CMS Plugin – PHP-Entwicklungs-Regeln

## Pflicht in jeder Datei

```php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
```

## Keine direkten SQL-String-Interpolationen

- **Verboten:** `"SELECT * FROM cms_users WHERE email = '$email'"`
- **Erlaubt:** `$stmt = $db->prepare("SELECT * WHERE email = ?"); $stmt->execute([$email]);`

## CSRF-Token

- Jedes POST-Formular: `<input type="hidden" name="csrf_token" value="...">`
- Serverseitig: `CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'slug')`

## Ausgabe-Escaping

- Text-Nodes: `htmlspecialchars($value)`
- Attribute: `htmlspecialchars($value, ENT_QUOTES)`
- **Niemals** rohe `$_POST`/`$_GET`-Werte direkt ausgeben

## Singleton-Pattern

Alle Plugin-Klassen nutzen `private static ?self $instance = null;` + `::instance()`.

## Admin-Seiten

`Auth::instance()->isAdmin()` am Anfang jeder Admin-PHP-Seite.

## Member-Seiten

`Auth::instance()->isLoggedIn()` am Anfang jeder Member-PHP-Seite.

## Cross-Plugin-Integration

```php
if (CMS\PluginManager::instance()->isPluginActive('cms-companies')) {
    // Integration-Code
}
```
