# Changelog

## 1.1.0 – 2026-03-15

- Spezial-User-Verwaltung im Admin ergänzt und Spezialseite nur noch für explizit zugewiesene 365CMS-Benutzer freigeschaltet
- Laufzeit-/Zahlungslogik mit drei Modellen eingebaut: jährlich, jährlich monatlich (+5%) und monatlich (+20%)
- Seed-Katalog vollständig mit Startpreisen befüllt und um `pricing_basis` für Benutzer- vs. Fixpreis-SKUs erweitert
- Paketverwaltung, Dashboard, PDF und Frontend auf neue Billing-Logik und Spezialzugriffe umgestellt

## 1.0.3 – 2026-03-15

- Öffentliche Rechnerseite fest auf den Public-Preiskontext verdrahtet; der Preis-Kontext ist im Public-Formular nicht mehr umschaltbar
- Geschützte Member- und Spezialbereiche über den 365CMS-Mitgliederbereich ergänzt (`/member/plugin/m365-license` und `/member/plugin/m365-license-special`)
- Auswertung und PDF-Export leiten den Preiskontext nun serverseitig aus dem Zugriffsbereich ab statt aus manipulierbaren Formularfeldern

## 1.0.2 – 2026-03-15

- Public-POST-CSRF-Flow an den globalen `form_guard` des Routers angepasst
- Seed-Preis-Synchronisierung für Bestandsinstallationen korrigiert, sodass neue Seed-Preise nicht mehr von alten `null`-Werten blockiert werden
- Startpreise für häufige Business-/Teams-Pakete ergänzt und fehlende Preiswarnung im Frontend präzisiert

## 1.0.1 – 2026-03-15

- Public-Rendering auf korrekte Theme-Header/Footer-Integration umgestellt
- Seed-Katalog um zusätzliche Microsoft-365-, Teams-, SharePoint-, OneDrive-, Visio- und Project-Pakete erweitert
- Enthaltene Services für bestehende Seed-Pakete ergänzt und Seed-Synchronisierung für Bestandsinstallationen verbessert

## 1.0.0 – 2026-03-15

- Erstes Release des CMS M365 License Plugins
- Seed-Katalog für Microsoft 365 Basislizenzen, Frontline, Copilot und Add-ons
- Adminbereich für Paketverwaltung und Konfiguration
- Öffentliche Bedarfsanalyse mit festem Public-Kontext
- PDF-Export und Tageslimit-Logik
