# Hooks – CMS M365 Price Tracker

## Registrierte Hooks

- `cms_init` → stellt die Settings-Tabelle sicher und initialisiert den Frontend-Controller.
- `plugin_activated` → legt die Settings-Tabelle an und initialisiert den Frontend-Controller nach Aktivierung.
- `cms_admin_menu` → registriert den Admin-Menüpunkt `M365 Preise`.
- `register_routes` → registriert `/microsoft-preiserhoehung-tracker` und `/en/microsoft-preiserhoehung-tracker`.
- `register_routes` → registriert zusätzlich die Admin-Route `m365price-tracker`.
- `head` → lädt Public-CSS und gibt die Admin-Designvariablen aus.
- `body_end` → lädt Chart.js und `assets/js/price-tracker.js`.
- `body_class` → ergänzt `m365price-tracker-theme-embed` und `m365tools-module-microsoft-price-tracker`.

## SEO-Filter

Beim Rendern der Route werden `seo_title` und `seo_description` gesetzt.
