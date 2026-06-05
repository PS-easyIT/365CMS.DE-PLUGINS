# CMS M365 Price Tracker

`cms-m365price-tracker` kapselt den Microsoft-Preiserhöhung-Tracker als eigenständiges Public-Plugin aus `cms-m365tools` aus.

## Public Route

- `/microsoft-preiserhoehung-tracker` – offizielle Microsoft-Preis-, Packaging-, SKU-, Renewal- und Forecast-Ereignisse mit Chart.js-Preisverlauf, Business-Standardauswahl, 5er-Lizenz-Cap und lokalem persönlichen Kosten-Tracker auswerten.

## Admin

- Menüpunkt `M365 Preise` mit Status-Dashboard, Datenpaket-Prüfung und direktem Public-Link.
- Admin-Tabs für Layout/Abstände, Farben, Bereiche und Karten.
- Inhaltsmodus `Nur Chart`, `Kompakt`, `Vollständig` oder benutzerdefinierte Bereichsauswahl.
- Info Card, Link Card, Hero-CTA und Auswahlbereiche sind administrierbar.

## Verhalten

- Die Seite rendert den normalen Theme-Header über `ThemeManager::getHeader()` und springt beim Öffnen nicht automatisch in einen Publicsite-Contentbereich.
- Abstand zwischen Theme-Header/Footer und Plugin-Content ist auf maximal 25px begrenzt.
- Chart.js wird vor dem lokalen Tracker-Script geladen.
- Der Preisverlauf steht direkt oben nach dem Seitenkopf.
- Eigene Lizenzpositionen werden ausschließlich lokal im Browser per `localStorage` gespeichert.

## Datenquelle

Das Plugin liefert eigene JSON-Dateien in `cms-m365price-tracker/data/` aus. Der Fallback auf bestehende M365-Tools-Dateien in `cms-m365tools/data/` bleibt nur als Sicherheitsnetz erhalten:

- `package_price_catalog.json`
- `microsoft_price_events.json`
- `microsoft_price_changes.json`
- `microsoft_inventory_mapping.json`
- `microsoft_price_forecast_rules.json`

## Dateien

```text
cms-m365price-tracker/
├── cms-m365price-tracker.php
├── includes/
│   ├── class-installer.php
│   ├── class-settings.php
│   ├── class-catalog.php
│   ├── class-frontend.php
│   └── class-microsoft-price-tracker.php
├── admin/
│   ├── class-admin-menu.php
│   ├── class-admin-pages.php
│   └── views/
│       └── page-dashboard.php
├── data/
│   ├── microsoft_inventory_mapping.json
│   ├── microsoft_price_changes.json
│   ├── microsoft_price_events.json
│   ├── microsoft_price_forecast_rules.json
│   └── package_price_catalog.json
├── templates/
│   └── page-microsoft-price-tracker.php
└── assets/
    ├── css/
    │   ├── plugin-base.css
    │   ├── price-tracker-admin.css
    │   └── price-tracker.css
    └── js/
        └── price-tracker.js
```
