# CMS Promos – Hooks

## Verwendete Core-Hooks

- `cms_init` – initialisiert Installer, Repository und Public-Controller
- `plugin_activated` – legt Tabellen und Defaults an
- `cms_admin_menu` – registriert Promo-Dashboard und Unterseiten
- `register_routes` – bindet öffentliche Promo-Routen ein
- `head` – lädt `promos-public.css`
- `body_start` – optionale automatische Promo-Ausspielung direkt nach `<body>`
- `after_header` – automatische Promo-Ausspielung unterhalb des Headers
- `home_content` – automatische Promo-Ausspielung im Home-Content
- `before_footer` – automatische Promo-Ausspielung oberhalb des Footers

## Öffentliche Routen

- `GET /promos`
- `GET /promos/placement/:slug`
- `GET /promo/click/:slug`

## Platzierungslogik

Platzierungen besitzen ein Feld `theme_hook` und optional `hook_priority`.

- `manual`: keine automatische Einbindung, nur Archiv/gezielte Ausgabe
- `body_start`, `after_header`, `home_content`, `before_footer`: automatische Ausgabe aktiver Promos dieser Platzierung

Die Reihenfolge mehrerer Platzierungen innerhalb desselben Hooks richtet sich nach `hook_priority` aufsteigend.
