# Changelog – CMS M365 Price Tracker

## 1.0.2 – 2026-06-02

- Admin-Tabs für Layout/Abstände, Farben, Bereiche und Karten ergänzt.
- Inhaltsmodus `Nur Chart` ergänzt; zusätzliche Bereiche darunter können einzeln ein- oder ausgeblendet werden.
- Info Card, Link Card, Hero-Texte und CTAs im Adminbereich editierbar gemacht.
- Theme-Header/Footer-Abstand zum Plugin-Content serverseitig und per CSS auf maximal 25px begrenzt.
- Settings-Tabelle `cms_m365price_tracker_settings` für persistente Designwerte ergänzt.

## 1.0.1 – 2026-06-02

- Admin-Menüeintrag `M365 Preise` ergänzt.
- Admin-Dashboard mit Plugin-Status, Datenpaket-Dateiliste und Public-Link hinzugefügt.
- Admin-Routing über `cms_plugin_admin_register_routes()` verdrahtet.

## 1.0.0 – 2026-06-02

- Microsoft-Preiserhöhung-Tracker aus `cms-m365tools` in ein eigenständiges Plugin ausgelagert.
- Public Route `/microsoft-preiserhoehung-tracker` registriert.
- Chart.js wird vor dem lokalen Tracker-Script geladen, damit Preisverlauf und persönlicher Kosten-Tracker wieder als Liniencharts rendern.
- Preisverlauf direkt an die oberste Contentposition nach dem Seitenkopf verschoben.
- Kein automatisches Scrollen in den Pluginbereich: Der normale Theme-Header bleibt beim Seitenaufruf sichtbar.
