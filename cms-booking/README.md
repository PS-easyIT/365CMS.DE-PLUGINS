# CMS Booking – Universelles Buchungssystem

> **Version:** 3.0.5  
> **Autor:** 365 Network  
> **Abhängigkeiten:** 365CMS Core ≥ 2.0  
> **Optionale Integration:** cms-contact, cms-experts, cms-speakers, cms-events, cms-companies

---

## Übersicht

CMS Booking ist ein universelles Buchungssystem für 365CMS. Andere Plugins können sich per Hook-System als
Buchungsanbieter (Provider) registrieren. Das Plugin verwaltet Leistungen, Verfügbarkeiten, Buchungen und
Benachrichtigungen zentral.

## Features

- **Provider-Architektur**: Jede Person / jedes Unternehmen wird als „Anbieter" geführt und kann mehrere buchbare Leistungen anbieten
- **Hook-basierte Integration**: `booking_register_providers` erlaubt externen Plugins, eigene Anbietertypen zu registrieren
- **Automatische Plugin-Erkennung**: cms-experts, cms-speakers, cms-events und cms-companies werden automatisch als Anbietertypen registriert
- **Verfügbarkeits-Engine**: Wochenplan, Datums-Overrides, Slot-Berechnung mit Pufferzeiten
- **Buchungsworkflow**: Ausstehend → Bestätigt → Abgeschlossen / Storniert / Nicht-Erschienen
- **E-Mail-Benachrichtigungen**: 5 vordefinierte E-Mail-Templates (Kunden- und Anbieter-Seite)
- **Kalender-Integration**: iCal-Export (.ics) und Google-Kalender-Link
- **DSGVO-konform**: Export- und Löschungs-Hooks für personenbezogene Daten
- **Kontaktformular-Vorlagen**: 4 Booking-Templates in cms-contact projiziert

## Verzeichnisstruktur

```
cms-booking/
├── cms-booking.php              ← Hauptdatei (Singleton, Hooks, Loader)
├── update.json                  ← Versions-Info für Auto-Updater
├── CHANGELOG.md
├── README.md
├── includes/
│   ├── class-installer.php      ← DB-Migration (6 Tabellen)
│   ├── class-providers.php      ← Anbieter-CRUD + Upsert
│   ├── class-services.php       ← Leistungen-CRUD
│   ├── class-availability.php   ← Wochenplan, Overrides, Slot-Engine
│   ├── class-bookings.php       ← Buchungs-CRUD, Status-Workflow
│   ├── class-notifications.php  ← E-Mail-Templates
│   ├── class-calendar-export.php← iCal + Google-Kalender
│   ├── class-integration.php    ← Provider-Type-Registry + Buttons
│   └── class-frontend.php       ← Routen und Frontend-Rendering
├── admin/
│   ├── class-admin-menu.php     ← Admin-Menü (5 Einträge)
│   ├── class-admin-pages.php    ← Trait-Loader
│   ├── modules/
│   │   ├── trait-page-dashboard.php
│   │   ├── trait-page-bookings.php
│   │   ├── trait-page-providers.php
│   │   ├── trait-page-services.php
│   │   └── trait-page-settings.php
│   └── views/
│       ├── dashboard.php
│       ├── bookings.php
│       ├── providers.php
│       ├── services.php
│       └── settings.php
├── templates/
│   ├── page-provider.php        ← Provider-Übersicht
│   ├── page-booking.php         ← Buchungsformular mit Kalender
│   └── page-confirmation.php    ← Bestätigungsseite
└── assets/
    ├── css/
    │   ├── booking-public.css
    │   └── booking-admin.css
    └── js/
        ├── booking-public.js     ← Kalender, Slots, Stepper
        └── booking-admin.js      ← Modale, Bestätigungen
```

## Schnellstart

1. Plugin-Ordner `cms-booking/` nach `CMS/plugins/` kopieren
2. Im Admin unter **Plugins** das Plugin **CMS Booking** aktivieren
3. Unter **Plugins → Buchungen → Einstellungen** die Grundkonfiguration vornehmen
4. Anbieter werden automatisch aus verknüpften Plugins synchronisiert oder können manuell angelegt werden
5. Leistungen je Anbieter erstellen (Dauer, Preis, Ort-Typ)
6. Verfügbarkeiten über Wochenplan definieren

## Integration eigener Plugins

```php
// In Ihrem Plugin:
CMS\Hooks::addAction('booking_register_providers', function () {
    CMS_Booking_Integration::register_provider_type('mein-plugin', [
        'label'          => 'Mein Typ',
        'icon'           => '🔧',
        'source_table'   => 'meine_tabelle',
        'name_column'    => 'name',
        'email_column'   => 'email',
        'user_id_column' => 'user_id',
        'single_route'   => '/mein-bereich/{slug}',
        'contact_template' => 'booking-simple',
    ]);
});
```

## Routen

| Route | Methode | Beschreibung |
|---|---|---|
| `/booking/{provider-slug}` | GET | Provider-Übersicht mit Service-Liste |
| `/booking/{provider-slug}/{service-slug}` | GET/POST | Buchungsformular |
| `/booking/confirm/{booking-id}` | GET | Bestätigungsseite |
| `/booking/ical/{booking-id}` | GET | iCal-Download |
| `/api/booking/slots/{provider-id}/{date}` | GET | Verfügbare Zeitfenster (JSON) |

## Sicherheitsstatus (2026-04-04)

- Snyk-Code-Audit für `cms-booking` abgeschlossen, aktuell ohne offene Findings.
- Die öffentliche Slot-Auswahl rendert Zeitfenster jetzt konsequent per DOM-API statt per `innerHTML`, wodurch DOM-XSS-Risiken aus Remote-Slotdaten reduziert wurden.
- Die Buchungszusammenfassung nutzt im Frontend nur noch textbasierte Ausgabe für dynamische Inhalte.

## Lizenz

Copyright © 2025 365 Network. Alle Rechte vorbehalten.
