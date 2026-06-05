# CMS M365 Price Tracker

Eigenständiges Public-Plugin für den Microsoft-Preiserhöhung-Tracker.

## Route

- `/microsoft-preiserhoehung-tracker`

## Kernfunktionen

- Offizielle Microsoft-Preisereignisse filtern und tabellarisch ausgeben.
- Kanonische SKU-Preiszeitreihen als Chart.js-Linienchart anzeigen.
- Renewal- und Forecast-Wirkung berechnen.
- Persönliche Lizenzpositionen lokal im Browser erfassen und auswerten.
- Admin-Menüpunkt `M365 Preise` mit Status- und Datenpaket-Übersicht bereitstellen.
- Admin-Einstellungen für Abstände, Layout, Farben, Auswahlbereiche, Inhaltsmodus, Info Card und Link Card bereitstellen.
- Theme-Header/Footer-Abstand zum Plugin-Content auf maximal 25px begrenzen.

## Auslagerung

Der Tracker ist nicht mehr als Modul und Route in `cms-m365tools` registriert. Bestehende Links bleiben über dieselbe Public-Route gültig, werden aber durch dieses Plugin bedient.
