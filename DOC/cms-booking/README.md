# CMS Booking – Dokumentation

> Universelles Buchungssystem für 365CMS

---

## Inhaltsverzeichnis

- [README.md](README.md) – Übersicht, Features, Schnellstart
- [DATABASE.md](DATABASE.md) – Datenbank-Schema (6 Tabellen)
- [HOOKS.md](HOOKS.md) – Actions, Filter, Integration-Events

## Architektur

CMS Booking basiert auf einer **Provider-Architektur**: Externe Plugins (cms-experts, cms-speakers, cms-events, cms-companies) registrieren sich als Provider-Typen über den Hook `booking_register_providers`. Das Booking-Plugin verwaltet dann zentral:

1. **Anbieter** (booking_providers) – synchronisiert aus Quell-Plugins oder manuell angelegt
2. **Leistungen** (booking_services) – je Anbieter konfigurierbare Terminarten
3. **Verfügbarkeiten** (booking_availability) – Wochenpläne + Datums-Overrides
4. **Buchungen** (bookings) – kompletter Lifecycle mit Status-Workflow
5. **Einstellungen** (booking_settings) – globale Konfiguration

## Kontaktformular-Integration

CMS Booking stellt 4 optionale Templates für CMS Contact bereit:

| Template-Slug | Beschreibung |
|---|---|
| `booking-simple` | Universelles Buchungs-Kontaktformular |
| `booking-expert` | Split-Layout für Experten-Beratung |
| `booking-event` | Event-/Speaker-Buchungsformular |
| `booking-service` | Business-Dienstleistungsbuchung |

Die Templates liegen physisch in `cms-contact/templates/` und werden dynamisch erkannt. CMS Contact funktioniert vollständig ohne diese Templates.

## Verwandte Dokumentation

- [Plugin-Entwicklung](../../365CMS.DE/DOC/PLUGIN-DEVELOPMENT.md)
- [CMS Contact](../cms-contact/README.md)
