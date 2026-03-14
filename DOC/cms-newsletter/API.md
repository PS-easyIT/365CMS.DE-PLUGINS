# CMS Newsletter – API

## Hauptklassen

### `CMS_Newsletter`
Bootstrap-Klasse für Laden, Hook-Registrierung und Styles.

### `CMS_Newsletter_Installer`
Erstellt die Tabellenstruktur und setzt Standardwerte.

### `CMS_Newsletter_Repository`
Kapselt alle Datenzugriffe für:
- Subscriber
- Templates
- Kampagnen
- Settings
- Dashboard-Statistiken

### `CMS_Newsletter_Admin_Pages`
Verarbeitet Admin-POSTs, rendert Layout und lädt Views.

### `CMS_Newsletter_Public_Controller`
Stellt die öffentliche Newsletter-Landingpage sowie Subscribe-/Unsubscribe-Routen bereit.
