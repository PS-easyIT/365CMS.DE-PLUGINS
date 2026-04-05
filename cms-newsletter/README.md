# CMS Newsletter

Newsletter-Plugin für 365CMS mit Subscriber-Verwaltung, Templates, Kampagnenplanung, Opt-In-Prozess und öffentlicher Anmeldeseite.

## Features

- Abonnenten mit Segment, Quelle und Status verwalten
- Newsletter-Templates mit HTML- und Text-Fallback speichern
- Kampagnen planen und Empfängerzahl segmentbasiert vorberechnen
- Öffentliche Newsletter-Seite unter `/newsletter`
- Double-Opt-In-fähige Anmeldelogik als Grundlage für DSGVO-konforme Workflows

## Admin-Bereiche

- `Dashboard`
- `Abonnenten`
- `Templates`
- `Kampagnen`
- `Einstellungen`

## Öffentliche Routen

- `GET /newsletter`
- `POST /newsletter/subscribe`
- `GET /newsletter/unsubscribe/:token`

## Sicherheitsstatus (2026-04-04)

- Snyk-Code-Audit für `cms-newsletter` abgeschlossen, aktuell ohne offene Findings.
- Admin-Redirects verwenden nach POST-Aktionen nur noch feste interne Ziele.
- Damit wurde der verbliebene Open-Redirect-Befund in der Admin-Navigation geschlossen.
