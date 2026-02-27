# 365CMS Plugin-Repository – GitHub Copilot Anweisungen

> Gilt für **alle PHP-Dateien** in diesem Repository.  
> Pfade beziehen sich auf das Verzeichnis `365CMS.DE-PLUGINS/`.

---

## 1. Allgemeine Architektur-Konventionen

### 1.1 Plugin-Header & Boilerplate

Jede Plugin-Hauptdatei (`cms-[name].php`) beginnt mit:

```php
<?php
/**
 * Plugin Name: CMS [Name]
 * Plugin URI: https://365network.de/cms-[name]
 * Description: [Beschreibung]
 * Version: X.Y.Z
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_[Name]
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('CMS_[NAME]_VERSION',    'X.Y.Z');
define('CMS_[NAME]_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_[NAME]_PLUGIN_URL', '/plugins/cms-[name]/');
```

### 1.2 Jede PHP-Datei (inkl. Klassen)

```php
<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
```

### 1.3 Singleton-Pattern (Pflicht für alle Klassen)

```php
final class CMS_MyPlugin
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->load_dependencies();
        $this->init_hooks();
    }
}
CMS_MyPlugin::instance();
```

---

## 2. Hooks-System

**Immer** `CMS\Hooks` verwenden, niemals direkte PHP-Funktionsaufrufe für Events:

```php
// Registrieren
CMS\Hooks::addAction('cms_init', [$this, 'init_plugin'], 10);
CMS\Hooks::addAction('plugin_activated', [$this, 'on_activation'], 10);
CMS\Hooks::addAction('cms_admin_menu', [$this, 'register_menu'], 10);

// Auslösen
CMS\Hooks::doAction('my_plugin_event', $param1, $param2);

// Filter
CMS\Hooks::applyFilters('my_filter', $value, $context);
CMS\Hooks::addFilter('my_filter', [$this, 'modify_value'], 10);
```

**Standard-Hooks:**

| Hook | Zeitpunkt |
|------|-----------|
| `cms_init` | Plugin-Initialisierung |
| `plugin_activated` | Plugin-Aktivierung (param: Plugin-Slug) |
| `cms_admin_menu` | Admin-Menü-Registrierung |
| `cms_member_dashboard` | Member-Dashboard-Widgets |
| `cms_frontend_route` | Frontend-Routing |
| `head` | `<head>` – Assets einbinden |
| `body_end` | Vor `</body>` – JS einbinden |
| `dsgvo_export_data` | DSGVO Art. 20 – Daten-Export |
| `dsgvo_delete_data` | DSGVO Art. 17 – Datenlöschung |

---

## 3. Datenbank-Zugriff

```php
$db     = CMS\Database::instance();
$pdo    = $db->getPdo();
$prefix = $db->prefix();   // z. B. "cms_"

// Prepared Statement (Pflicht!)
$stmt = $db->prepare("SELECT * FROM {$prefix}companies WHERE id = ? AND status = ?");
$stmt->execute([$id, 'active']);
$row = $stmt->fetch();
$rows = $stmt->fetchAll();

// INSERT
$db->insert("{$prefix}companies", ['name' => $name, 'email' => $email]);
$id = $db->lastInsertId();
```

**Verboten:** Direkte String-Interpolation von Benutzereingaben in SQL!

---

## 4. Sicherheit (CSRF + Sanitierung)

```php
// CSRF generieren
$csrfToken = CMS\Security::instance()->generateToken('mein_action_slug');

// CSRF prüfen
if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'mein_action_slug')) {
    $error = 'Sicherheitscheck fehlgeschlagen';
    // Handler abbrechen!
}

// Eingabe-Sanitierung
$text  = sanitize_text_field($_POST['name'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$url   = filter_var($_POST['url'] ?? '', FILTER_VALIDATE_URL) ?: '';
$id    = (int)($_POST['id'] ?? 0);
$html  = strip_tags($_POST['content'] ?? '', '<p><a><strong><em><ul><ol><li><br>');

// Ausgabe-Escaping
echo htmlspecialchars($value);
echo htmlspecialchars($value, ENT_QUOTES);
```

---

## 5. Admin-Backend-Struktur

### 5.1 Verzeichnis-Layout

```
admin/
├── class-admin-menu.php     # Menü-Registrierung (cms_admin_menu Hook)
├── class-admin-pages.php    # Shell + Trait-Loader
└── modules/
    ├── trait-page-dashboard.php
    ├── trait-page-[name].php
    └── ...
```

### 5.2 Admin-Seiten-Boilerplate

```php
<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$csrfToken = Security::instance()->generateToken('plugin_seite');
require_once CMS_PATH . 'admin/partials/admin-menu.php';
?>
```

### 5.3 Trait-Muster für Admin-Module

```php
trait CMS_PLUGIN_Page_Dashboard_Trait
{
    public function render_dashboard_page(): void
    {
        // Lesen + Daten vorbereiten
        $stats = $this->get_stats();
        
        // HTML ausgeben
        include CMS_MYPLUGIN_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
}
```

---

## 6. Member-Bereich-Struktur

```
includes/
└── class-member-dashboard.php   # Oder: class-member-controller.php
member/                          # Oder: includes/member/
├── trait-member-overview.php
├── trait-member-settings.php
└── ...
views/member/
├── page-overview.php
├── page-settings.php
└── ...
```

**Registrierung:**
```php
CMS\Hooks::addAction('cms_member_dashboard', [$this, 'render_dashboard_widget'], 20);
```

---

## 7. Cross-Plugin-Integration

**Regel:** Immer mit `PluginManager::isPluginActive()` absichern!

```php
if (CMS\PluginManager::instance()->isPluginActive('cms-companies')) {
    $db = CMS\Database::instance();
    $company = $db->prepare("SELECT * FROM {$db->prefix()}companies WHERE id = ?");
    $company->execute([$company_id]);
    $company = $company->fetch();
}
```

---

## 8. Datenbank-Tabellen anlegen (Activation Hook)

```php
public function create_tables(): void
{
    $db     = CMS\Database::instance();
    $pdo    = $db->getPdo();
    $prefix = $db->prefix();

    $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}my_table (
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

**Migration (bestehende Tabellen):**
```php
$cols = $pdo->query("SHOW COLUMNS FROM {$prefix}my_table LIKE 'new_col'")->fetchAll();
if (empty($cols)) {
    $pdo->exec("ALTER TABLE {$prefix}my_table ADD COLUMN new_col VARCHAR(100) DEFAULT NULL");
}
```

---

## 9. Assets einbinden

```php
public function enqueue_styles(): void
{
    $css = CMS_MYPLUGIN_PLUGIN_DIR . 'assets/css/style.css';
    if (file_exists($css)) {
        echo '<link rel="stylesheet" href="' . CMS_MYPLUGIN_PLUGIN_URL . 'assets/css/style.css?v=' . filemtime($css) . '">' . "\n";
    }
}

public function enqueue_scripts(): void
{
    $js = CMS_MYPLUGIN_PLUGIN_DIR . 'assets/js/script.js';
    if (file_exists($js)) {
        echo '<script src="' . CMS_MYPLUGIN_PLUGIN_URL . 'assets/js/script.js?v=' . filemtime($js) . '" defer></script>' . "\n";
    }
}
```

---

## 10. Namenskonventionen

| Element | Konvention | Beispiel |
|---------|-----------|---------|
| Plugin-Klassen | `PascalCase` mit Präfix | `CMS_Companies`, `CMS_JPG_Admin` |
| Methoden | `snake_case` | `get_companies()`, `render_page()` |
| Traits | `PascalCase + _Trait` | `CMS_JPG_Page_Dashboard_Trait` |
| Konstanten | `UPPER_SNAKE_CASE` | `CMS_COMPANIES_VERSION` |
| Tabellen | `cms_` + `snake_case` | `cms_companies`, `cms_event_speakers` |
| Dateien (Klassen) | `class-kebab-case.php` | `class-admin-pages.php` |
| Dateien (Traits) | `trait-kebab-case.php` | `trait-page-dashboard.php` |
| Views | `page-kebab-case.php` | `page-company-settings.php` |

---

## 11. Dokumentations-Pflicht

Für jedes neue Plugin oder Feature:

```
DOC/[plugin-slug]/
├── README.md       # Übersicht, Schnellstart, Features
├── DATABASE.md     # Alle Tabellen, Felder, Relationen
├── HOOKS.md        # Actions & Filter mit Signaturen
├── API.md          # PHP-Klassen und -Methoden
└── CHANGELOG.md    # Versionshistorie
```

---

## 12. Checkliste vor dem Commit

- [ ] `declare(strict_types=1)` und `ABSPATH`-Guard in jeder Datei
- [ ] Singleton-Pattern korrekt implementiert
- [ ] Alle DB-Operationen als Prepared Statements
- [ ] CSRF-Token in jedem Formular und AJAX-Request
- [ ] Alle `$_POST`/`$_GET`-Werte sanitiert
- [ ] Alle HTML-Ausgaben per `htmlspecialchars()` escaped
- [ ] Cross-Plugin-Zugriffe mit `PluginManager::isPluginActive()` gesichert
- [ ] `update.json` aktualisiert
- [ ] DOC-Dateien aktualisiert
- [ ] Admin-Seiten: `Auth::instance()->isAdmin()` geprüft
- [ ] Member-Seiten: `Auth::instance()->isLoggedIn()` geprüft
- [ ] DSGVO-Hooks registriert (falls personenbezogene Daten)
