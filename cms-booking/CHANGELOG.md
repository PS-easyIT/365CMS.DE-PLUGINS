# Changelog – CMS Booking

Alle nennenswerten Änderungen an diesem Plugin werden hier dokumentiert.

## [Unreleased] – 2026-04-04

### Sicherheitsfixes

- Öffentliche Slot-Buttons und Zusammenfassungen werden jetzt DOM-sicher per `createElement()` und `textContent` statt per `innerHTML` aufgebaut.
- Damit ist der letzte DOM-XSS-Befund im Booking-Frontend bereinigt.

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
