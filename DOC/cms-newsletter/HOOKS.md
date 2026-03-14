# CMS Newsletter – Hooks

## Verwendete Core-Hooks

- `cms_init` – Initialisiert Installer, Repository und Public-Controller
- `plugin_activated` – führt `install()` für `cms-newsletter` aus
- `plugin_uninstalled` – Platzhalter für spätere Cleanup-Strategien
- `cms_admin_menu` – registriert Dashboard und Admin-Unterseiten
- `register_routes` – bindet öffentliche Newsletter-Routen an den Router
- `head` – lädt `newsletter-public.css`

## Öffentliche Routen

- `GET /newsletter`
- `POST /newsletter/subscribe`
- `GET /newsletter/unsubscribe/:token`
