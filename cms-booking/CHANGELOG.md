# Changelog – CMS Booking

Alle nennenswerten Änderungen an diesem Plugin werden hier dokumentiert.

## [3.0.2] – 2026-05-25

### Fehlerbehebungen

- Der Installer liest und schreibt die Core-Tabelle `settings` wieder mit den korrekten Spalten `option_name` und `option_value`; dadurch verschwinden die `Unknown column 'setting_value'`-Logs beim DB-Versionscheck.
- Foreign Keys werden beim Anlegen der Booking-Tabellen ohne manuell vergebene Constraint-Namen erstellt, damit MariaDB/MySQL keine schemaweiten Namenskollisionen (`errno: 121`) mehr erzeugt.
- Die Installer-Klasse ist gegen versehentliches erneutes Laden geschützt.

## [3.0.0] – 2026-05-17

### Sicherheitsfixes

- Public-Frontend-Routen senden jetzt `X-Content-Type-Options`, `X-Frame-Options` und `Referrer-Policy`, sofern noch keine Header verschickt wurden.
- Bestätigungs- und iCal-Zugangstoken werden per `hash_hmac('sha256', ...)` erzeugt; der iCal-Link enthält den geprüften Token wieder korrekt.
- Dynamische Provider-Tabellen- und Spaltennamen in der Integration-API werden per Allowlist validiert, bevor sie in SQL-Identifier gelangen.
- iCal-Export normalisiert UID, Dateiname und Mailfelder und sendet zusätzlich `X-Content-Type-Options: nosniff`.
- Das öffentliche Buchungsformular escaped den CSRF-Token im Attributkontext explizit mit `ENT_QUOTES` und `UTF-8`.
- Öffentliche Slot-Buttons und Zusammenfassungen werden jetzt DOM-sicher per `createElement()` und `textContent` statt per `innerHTML` aufgebaut.
- Damit ist der letzte DOM-XSS-Befund im Booking-Frontend bereinigt.
- Das Admin-Bestätigungsmodal wird ebenfalls vollständig per DOM-API erzeugt; `innerHTML` und Hilfs-Escaping-Sinks sind entfernt.
- Das Buchungsformular gibt `data-available-dates` attributsicher aus und lädt Public-CSS/-JS nicht mehr zusätzlich zur zentralen Hook-Ausgabe.
- Öffentliche Buchungs-POSTs schlagen jetzt geschlossen fehl, falls der CMS-Sicherheitsdienst fehlt; Bestätigungs- und iCal-Zugangstoken werden timing-sicher mit `hash_equals()` geprüft.
- Provider- und Bestätigungsseiten laden das Public-CSS nicht mehr doppelt; Admin-Links in neuen Tabs verwenden `rel="noopener noreferrer"`.

### Design & Performance

- Public-Templates verwenden weniger statische Inline-Styles; Formular-Schritte wechseln auf das native `hidden`-Attribut und werden per JS ohne Layout-Inline-Manipulation sichtbar gemacht.
- Provider-, Buchungs- und Bestätigungsansicht wurden von dekorativer Emoji-UI bereinigt, damit sie ruhiger in das PHINIT-Theme fallen.
- Booking-Public-CSS bindet zentrale PHINIT-Tokens ein, ergänzt sichtbare `:focus-visible`-Zustände und stellt 44px-Touch-Targets für Kalender, Slots und Buttons sicher.
- Die Admin-Buchungsübersicht nutzt wiederverwendbare CSS-Klassen für Filter, Aktionen, Meta-Texte und Pagination statt statischer Layout-Inline-Styles.

## [1.0.0] – 2025-06-28

### Hinzugefügt

- **Universelles Buchungssystem** mit Provider-Architektur
- **Hook-basierte Integration** (`booking_register_providers`) für externe Plugins
- Automatische Erkennung und Registrierung von cms-experts, cms-speakers, cms-events, cms-companies
- **6 Datenbank-Tabellen**: `booking_providers`, `booking_services`, `booking_availability`, `bookings`, `booking_meta`, `booking_settings`
- **Admin-Backend** mit 5 Bereichen: Dashboard, Buchungen, Anbieter, Leistungen, Einstellungen
- **Buchungsformular** mit Kalender-Widget, Slot-Auswahl und Step-by-Step-Steuerung
- **Verfügbarkeits-Engine**: Wochenplan, Datums-Overrides, Puffer-Zeiten, Slot-Berechnung
- **Status-Workflow**: Ausstehend → Bestätigt → Abgeschlossen / Storniert / Nicht-Erschienen
- **E-Mail-Benachrichtigungen**: Kundenbestätigung, Status-Updates, Anbieter-Benachrichtigung, Admin-Info
- **iCal-Export** (.ics-Download) und **Google-Kalender-Link**
- **Provider-Übersichtsseite** mit Service-Karten
- **Bestätigungsseite** mit Zusammenfassung und Kalender-Links
- **Booking-Button und -Widget** zum Einbetten auf Single-Pages anderer Plugins
- **4 Buchungs-Kontaktformulare** in cms-contact projiziert (booking-simple, booking-expert, booking-event, booking-service)
- **Slots-API** (`/api/booking/slots/{providerId}/{date}`) für AJAX-Kalender
- **14 konfigurierbare Einstellungen** (E-Mail, Vorlaufzeiten, Stornierungsfrist, Auto-Bestätigung, Erinnerungen, Primärfarbe)
- **DSGVO-Hooks** für Datenexport und -löschung (Anonymisierung)
- Responsive Design für alle Seiten
- CSRF-Schutz und Honeypot in allen Formularen
