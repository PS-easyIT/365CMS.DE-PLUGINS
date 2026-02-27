---
applyTo: "*/admin/**/*.php"
---

# 365CMS Plugin Admin-Bereich – Regeln

## Boilerplate

```php
<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
use CMS\Auth;
use CMS\Security;
if (!Auth::instance()->isAdmin()) { header('Location: ' . SITE_URL); exit; }
$csrfToken = Security::instance()->generateToken('seite_slug');
```

## Verzeichnis-Layout

```
admin/
├── class-admin-menu.php       # Hook: cms_admin_menu
├── class-admin-pages.php      # Trait-Shell
└── modules/
    ├── trait-page-dashboard.php
    └── trait-page-[name].php
```

## Trait-Muster für Seiten

```php
trait CMS_Plugin_Page_Name_Trait
{
    public function render_name_page(): void
    {
        // Daten laden
        // View einbinden
        include CMS_PLUGIN_DIR . 'admin/views/name.php';
    }
}
```

## HTML-Layout

Alle Admin-Views nutzen:
- `renderAdminSidebar('sidebar-slug')` und `renderAdminSidebarStyles()`
- `.admin-content` als Haupt-Container
- `.admin-page-header` mit `h2` + Emoji
- `.admin-card` für inhaltliche Abschnitte
- `admin.css` mit `?v=YYYYMMDD` Cache-Parameter

## Keine window.confirm()

Stattdessen eigenes Modal für Bestätigungen verwenden.

## Kein jQuery

Ausschließlich Vanilla JavaScript (ES2020+).
