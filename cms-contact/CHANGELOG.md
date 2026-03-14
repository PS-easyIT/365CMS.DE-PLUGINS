# Changelog – CMS Contact

Alle nennenswerten Änderungen an diesem Plugin werden hier dokumentiert.

## [1.1.1] – 2026-03-14

### Geändert

- Installer und Versionsverwaltung an die aktuellen 365CMS-Settings-Spalten `option_name` / `option_value` angepasst
- Plugin-Metadaten für das 365CMS-Kompatibilitätsupdate aktualisiert

## [1.1.0] – 2025-06-28

### Hinzugefügt

- **4 Booking-Templates** für das CMS-Booking-Plugin:
  - `booking-simple` – Schlichtes Buchungs-Kontaktformular
  - `booking-expert` – Split-Layout für Experten-Beratung
  - `booking-event` – Event-/Speaker-Buchungsformular
  - `booking-service` – Business-Dienstleistungsbuchung
- Dynamische Template-Erkennung: Booking-Templates werden nur angezeigt, wenn die Template-Dateien vorhanden sind
- CMS-Contact funktioniert weiterhin vollständig ohne CMS-Booking

## [1.0.0] – 2025-06-27

### Hinzugefügt

- **Kontaktformular-Builder** mit Drag-and-Drop-Feldverwaltung
- **6 Design-Templates**: Classic, Modern, Split Screen, Minimal, Business, Fullwidth
- **Formular-Engine**: 8 Feldtypen (Text, E-Mail, Telefon, Textarea, Select, Checkbox, Radio, Hidden)
- **Einreichungsverwaltung** mit Status-Workflow und Sternmarkierung
- **E-Mail-Benachrichtigungen** an Admin und Absender (Auto-Reply)
- **Spam-Schutz**: Honeypot-Feld und Mathe-Captcha
- **Statistik-Dashboard** mit Balkendiagramm (letzte 30 Tage)
- **Admin-Backend** mit 5 Bereichen: Dashboard, Formulare, Einreichungen, Templates, Einstellungen
- **DSGVO-konform**: Datenexport und -löschung via CMS-Hooks
- CSRF-Token-Schutz in allen Formularen
- Responsives Design für alle Templates
