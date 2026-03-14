# CMS Promos – API

## Hauptklassen

### `CMS_Promos`
Bootstrap-Klasse für Laden, Hook-Registrierung und Public-CSS.

### `CMS_Promos_Installer`
Erstellt Tabellen für Promos, Platzierungen und Settings.

### `CMS_Promos_Repository`
Bietet CRUD-Methoden und Statistiken für:
- Promos
- Platzierungen
- Settings
- Klick- und Impression-Zähler
- Theme-Hook-Mapping für automatische Platzierungen

### `CMS_Promos_Admin_Pages`
Zentrale Admin-Shell für Views und POST-Verarbeitung.

### `CMS_Promos_Public_Controller`
Rendert die öffentliche Promo-Übersicht, verarbeitet Klick-Redirects und spielt Platzierungen automatisch an definierten Theme-Hooks aus.
