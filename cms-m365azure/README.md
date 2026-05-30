# CMS M365 Azure

Separates 365CMS-Plugin für einen redaktionell steuerbaren Azure-Service-Katalog.

## Funktionen

- Öffentliche Route `/azure-services` mit Hero, Inhaltsverzeichnis und Kategorien
- Service-Cards mit optionalem Bild links oder rechts und reinem Textlayout ohne Bild
- Kategorie-Galerien mit bis zu 6 Mediathek-Bildern als kompakte Thumbnails im Kategorie-Header
- Admin-Steuerung für Texte, SEO, Route, Hero-Buttons, Tabellenlabels, Hinweise, Quellen, Sichtbarkeit, Card-Layout, Farben, Abstände und Breiten
- CRUD für Kategorien und Azure Services
- Seed-Daten aus offiziellen Azure-/Microsoft-Quellen als Startbestand
- Responsive Ausgabe für Desktop, Tablet und Mobile mit ausgewogenen Tabellen-Spaltenbreiten

## Admin

Der Adminbereich liegt unter **M365 Azure** und bietet:

- Dashboard mit Inhaltsstatistiken
- Kategorienverwaltung
- Serviceverwaltung
- Steuerung & Design
- Systeminformationen

### Steuerbare Public-Bereiche

- Seitenkopf mit Overline, Titel, Einleitung und bis zu drei Hero-Buttons
- Inhaltsverzeichnis inklusive Titel, Spaltenzahl, No-Wrap-Verhalten und Kategorie-Intro-Sichtbarkeit
- Kategorie-Karten mit optionaler Mediathek-Galerie direkt neben dem Kategorie-Titel
- Service-Tabelle mit editierbaren Spaltenüberschriften, Link-Labels und Leerwert-Text
- Sichtbarkeit für Hero, Buttons, TOC, Kategorie-Intro, Bilder, Kurzzeilen, Beschreibung, Hinweise, Einsatzszenarien, Links, Hinweisbox und Quellenbox
- Designwerte für Farben, Inhaltsbreite, Innenabstände, Bildbreite, Radius sowie Schriftgrößen von TOC, Tabelle und Links
- Responsive Public-Ausgabe mit Desktop-Tabelle und mobiler Accordion-Ansicht

## Datenbank

Das Plugin erstellt beim Aktivieren beziehungsweise beim ersten Laden:

- `cms_m365azure_categories`
- `cms_m365azure_services`
- `cms_m365azure_settings`

Der Prefix wird über `CMS\Database::prefix()` ermittelt.
