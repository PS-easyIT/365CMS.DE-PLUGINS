# CMS M365 Azure

Separates 365CMS-Plugin für einen redaktionell steuerbaren Azure-Service-Katalog.

## Funktionen

- Öffentliche Route `/azure-services` mit Hero, Inhaltsverzeichnis und Kategorien
- Service-Cards mit optionalem Bild links oder rechts und reinem Textlayout ohne Bild
- Admin-Steuerung für Texte, SEO, Route, Sichtbarkeit, Card-Layout, Farben und Breiten
- CRUD für Kategorien und Azure Services
- Seed-Daten aus offiziellen Azure-/Microsoft-Quellen als Startbestand
- Responsive Ausgabe für Desktop, Tablet und Mobile

## Admin

Der Adminbereich liegt unter **M365 Azure** und bietet:

- Dashboard mit Inhaltsstatistiken
- Kategorienverwaltung
- Serviceverwaltung
- Steuerung & Design
- Systeminformationen

## Datenbank

Das Plugin erstellt beim Aktivieren beziehungsweise beim ersten Laden:

- `cms_m365azure_categories`
- `cms_m365azure_services`
- `cms_m365azure_settings`

Der Prefix wird über `CMS\Database::prefix()` ermittelt.
