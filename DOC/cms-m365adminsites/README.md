# CMS M365 Adminsites

## Überblick

`cms-m365adminsites` stellt eine öffentliche, admin-konfigurierbare Übersicht wichtiger Microsoft-Portale bereit. Das Plugin orientiert sich bewusst am Aufbau von `cms-m365linkcollection`, ist aber auf Adminsites, Portale und Portalnavigation fokussiert.

## Quellenbasis

Die initialen Startdaten wurden aus folgenden Quellen abgeleitet:

- Nutzerliste mit Microsoft 365, Azure, Security, Compliance, Power Platform, Lizenzierung, Consumer und Education Portalen
- PHINIT-KB-Seiten für Microsoft 365 Admin Center, Apps Admin Center, Intune, Teams, Stream und Azure Cosmos DB
- Microsoft Learn Produktverzeichnis
- Microsoft Product Terms und M365 Maps
- `msportals.io` mit Admin-, Licensing-, Consumer- und Education-Kategorien
- Direktaufrufe relevanter Microsoft-Portale per `fetch_webpage`

Viele Microsoft-Portale sind Login- oder JavaScript-Shells. Für diese Einträge wurden URL, Portaltitel und Kategorie aus erreichbaren Metadaten, Redirects und MSPortals.io übernommen.

## Public-Seite

Standardroute: `/m365-adminsites`

Fallbacks:

- `/m365-admin-sites`
- `/m365-admin-portale`

Funktionen:

- Kategorie-Filterkarten
- Suchformular
- Cards-Ansicht
- Tabellenansicht
- Admin-gesteuerte Standardansicht
- Admin-gesteuerte Spaltenauswahl
- Pagination

## Adminbereich

Menüpunkt: `M365 Adminsites`

Tabs:

- Portale
- Inhalte & Texte
- Anzeige & Design
- Hinweise

## Widget

Das optionale Widget wird über folgende Methode gerendert:

`CMS_M365ADMINSITES_Widget::render_phinit_sidebar_widget(string $orderStyle = '')`

Es nutzt bevorzugt Portale mit `is_featured = 1` und fällt danach auf aktive Portale zurück.
