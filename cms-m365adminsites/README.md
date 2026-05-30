# CMS M365 Adminsites

`cms-m365adminsites` ist eine kuratierte Portal-Sammlung für Microsoft-365-, Azure-, Security-, Compliance-, Power-Platform-, Lizenz-, Consumer- und Education-Portale.

## Features

- Public-Seite unter `/m365-adminsites` mit Fallbacks `/m365-admin-sites` und `/m365-admin-portale`
- Kategorien, Suche, Cards und Tabelle
- Admin-gesteuerte Standardansicht: Cards, Tabelle oder beides
- Einstellbare sichtbare Tabellenspalten
- Konfigurierbare Farben, Abstände, Bildhöhen, Radius und Tabellendichte
- Bearbeitbare Public-Texte, Labels und Sidebar-Widget-Texte
- Startdaten aus der Nutzerliste plus `fetch_webpage`-Recherche über PHINIT-KB, Microsoft Learn und MSPortals.io
- Optionales PHINIT-Sidebar-Widget via `CMS_M365ADMINSITES_Widget::render_phinit_sidebar_widget()`

## Kategorien

- Microsoft 365 Admin
- Azure & Identity
- Sicherheit & Compliance
- Power Platform & AI
- Lizenzierung & Partner
- Dokumentation & Learning
- Microsoft Account & Personal Administration
- Consumer Web Apps
- Education Administration
- Health / Status
- Developer & AI Portals

## Adminbereich

Menüpunkt: **M365 Adminsites**

Tabs:

1. **Portale** – Einträge anlegen, bearbeiten, deaktivieren oder löschen
2. **Inhalte & Texte** – Public-Labels und Widget-Texte
3. **Anzeige & Design** – Layout, Tabellenoptionen, Farben und Sidebar-Verhalten
4. **Hinweise** – Quellen- und Integrationshinweise

## Sicherheit

- Adminzugriff nur für Administratoren
- POST-Aktionen mit CSRF-Token
- Datenbankzugriff über Prepared Statements
- Public-Ausgabe mit `htmlspecialchars()` escaped
- Externe Links mit `target="_blank" rel="noopener noreferrer"`

## Version

Aktuell: `1.0.0` vom 30.05.2026
