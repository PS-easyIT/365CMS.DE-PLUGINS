---
applyTo: "*/member/**/*.php,*/views/member/**/*.php,*/includes/class-member*.php,*/includes/member/**/*.php"
---

# 365CMS Plugin Member-Bereich – Regeln

## Authentifizierung

```php
if (!CMS\Auth::instance()->isLoggedIn()) {
    header('Location: ' . SITE_URL . '/login');
    exit;
}
```

## Verzeichnis-Layout

```
includes/
└── class-member-dashboard.php   # oder class-member-controller.php

member/                          # oder includes/member/
├── trait-member-overview.php
└── trait-member-settings.php

views/member/
├── page-overview.php
└── page-settings.php
```

## Hook-Registrierung

```php
CMS\Hooks::addAction('cms_member_dashboard', [$this, 'render_widget'], 20);
```

## CSS im Member-Bereich

- `main.css` + `member.css` für Standard-Member-Views
- `admin.css` hinzufügen wenn `.admin-card`, `.form-control`, `.btn`, `.tab-btn`, `.alert` genutzt werden

## Eigentümer-Prüfung

Bevor ein Datensatz bearbeitet wird:
```php
$record = $db->prepare("SELECT * FROM {$p}my_table WHERE id = ? AND user_id = ?")->execute([$id, $current_user_id]);
if (!$record) {
    $error = 'Keine Berechtigung';
}
```

## Trait-Muster für Member-Module

```php
trait CMS_Plugin_Member_Settings_Trait
{
    public function render_settings_inline(): void
    {
        $userId = CMS\Auth::instance()->getUserId();
        // Daten direkt per DB-Query laden (kein PluginManager-Guard hier)
        // View einbinden
    }
}
```
