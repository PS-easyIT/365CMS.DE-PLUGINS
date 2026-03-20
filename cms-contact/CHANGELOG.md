# Changelog – CMS Contact

Alle nennenswerten Änderungen an diesem Plugin werden hier dokumentiert.

## [1.1.3] – 2026-03-19

### Geändert

- Die Speicherung von IP-Adressen in `cms-contact` wurde vollständig entfernt; bestehende Installationen räumen die veraltete Spalte per Migration auf.
- Das bisherige IP-basierte Rate-Limiting wurde auf eine sessionbasierte Begrenzung erfolgreicher Einsendungen umgestellt.
- Alle Frontend-Formulare verlangen jetzt standardmäßig eine ausdrückliche Datenschutz-Einwilligung mit Link zur Datenschutzerklärung.
- Die Admin-Ansicht zeigt bei Nachrichten jetzt sichtbar an, ob und wann die Datenschutz-Einwilligung bestätigt wurde.

### Hinzugefügt

- Neue globale Einstellungen für `Datenschutz-URL` und `Datenschutz-Einwilligung verpflichtend`.

## [1.1.2] – 2026-03-17

### Geändert

- Die restlichen Admin-Views (`Neues Formular`, Formularliste, Nachrichtenliste, Nachrichten-Detail) verwenden jetzt ebenfalls konsequent `contact-admin.css` und `contact-admin.js` statt zusätzlicher Inline-Styles oder Inline-Skripte.
- Template-Auswahl, Lösch-Modal, Bulk-Checkboxen und Status-/Detail-Aktionen wurden auf zentrale Datenattribute und wiederverwendbare Admin-JavaScript-Initialisierung umgestellt.

### Verbessert

- Konsistentere Admin-Layouts für Formulare, Listen, Pagination und Modale vereinfachen Wartung, reduzieren Markup-Rauschen und halten die Plugin-Views näher an den 365CMS-Admin-Konventionen.

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
