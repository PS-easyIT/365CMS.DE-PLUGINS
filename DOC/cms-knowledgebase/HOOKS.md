# Hooks & Routen

## Registrierte Actions

- `cms_init` → Schema-Upgrade prüfen, Output-Buffer-Fallback starten
- `plugin_activated` → Tabellen anlegen
- `plugin_uninstalled` → Log-Eintrag schreiben
- `cms_admin_menu` → Admin-Menü registrieren
- `register_routes` → Öffentliche KB-Routen registrieren
- `head` → Public-CSS laden
- `before_footer` → Tooltip-JavaScript laden
- `main_nav` → Optionalen Nav-Link ausgeben

## Registrierte Filter

- `content_render` → Bevorzugter Auto-Linking-Filter für Content-Fragmente

## Öffentliche Routen

- `GET /kb`
- `GET /kb/:slug`
