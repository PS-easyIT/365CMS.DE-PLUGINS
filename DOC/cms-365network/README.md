# CMS 365NETWORK

**Version:** 1.0.38
**Status:** Domainbasierte HubSite/Landingpage für Netzwerk-Portale.

CMS 365NETWORK stellt eine eigene Landingpage bereit, die auf einer konfigurierten Zusatzdomain direkt als Root-Seite erscheinen kann. Sie bündelt vier zentrale Bereiche des Netzwerks:

- Events
- Speaker
- Firmen
- Experten

Zusätzlich gibt es eine interne Vorschau-Route, standardmäßig `/365network`.

## Features

- Zusatzdomain-Mapping ohne Core-Anpassung: Root-Aufruf der Domain zeigt die Landingpage.
- Moderne Preview-nahe HubSite ohne eigenen Plugin-Header: Der Theme-Header bleibt zuständig, der Hub startet direkt mit dem Partnerband und danach mit Hero, vier blauen Bereichskarten im 2×2-Raster, nächsten Events, Spotlight-Rotator und Partner-Spalten.
- Optionales Hero-Bild aus URL oder 365CMS-Mediathek mit konfigurierbarer Größe (`250 × 200px` Default), drei Layouts und wahlweise Ersatz der sichtbaren H1 oder zusätzlicher Anzeige neben Titel/Suche; Split-Layouts nutzen fest `33%` Bild und `67%` Text.
- Eigene Admin-Tabs für Featured Card, Hero, Kennzahlen, Teaser-Band/Suche, Direkteinstieg, Partnerband, Events, Spotlight, Partner-Spalten und Toolbox.
- Sortierbare Reihenfolge für Public-Bereiche sowie für die vier Direkteinstieg-Karten.
- Mediathek-Auswahl mit Vorschau für Bild-URL-Felder im Hub-Admin.
- Jeder Hub-Bereich hat Aktivierung, Text-/Content-, Layout- und Design-Settings mit direkter Public-Wirkung.
- Eigene Landingpage-Suche unter `/365network/search`, getrennt von der globalen 365CMS-Suche und begrenzt auf Events, Speaker, Firmen und Experten.
- `Nächstes Event`-Teaser für konkrete Einstiegspunkte.
- No-Sidebar-Layout mit breiter Featured Card, dezentem Featured-Inset und überarbeiteten blauen Bereichskarten mit Icon-Corner oben rechts.
- PHINIT-konformes Navy/Gold-Redesign ohne Electric-Blue-Akzente auf der Landingpage.
- Optionaler M365-Toolbox-Bereich am Ende des Hub-Contents, wenn das Toolbox-Plugin aktiv ist und aktive Hub-Links vorhanden sind.
- Kompatibilität mit der aktuellen `cms-m365tools`-Tool-Registry, falls keine Legacy-`m365toolbox_links` vorhanden sind.
- Layoutvarianten: `2x2`, `4x1` oder automatisch responsiv.
- Design steuerbar: Breite, Rundungen, Abstände, Farben.
- Optionale Sidebar mit kommenden Events und zufälligen Speaker-/Firmen-/Experten-Karten.
- Preview-Cards alternativ unterhalb der vier Bereiche.
- Optionaler Matomo-/SEO-Analyse-Code nur für die 365NETWORK-Public-Site.
- Defensive Cross-Plugin-Integration: läuft auch, wenn einzelne Module deaktiviert sind.
- Robuste Statistik-Zählung für angebundene Events, Speaker, Firmen und Experten mit Status-Fallbacks.
- Tab-basiertes Speichern im Adminbereich bewahrt bestehende Einstellungen anderer Tabs.
- Hub-Änderungen leeren den Public-Cache explizit, damit Texte und Sortierung direkt öffentlich sichtbar werden.
- Core-kompatible Admin-Helfer für Menü, Capability, Nonce, Notice und Redirect werden genutzt, sofern im Core vorhanden.
- Kanonische Detail-Links für Events, Speaker, Firmen und Experten in den Vorschauen.
- Eigene Public-JS-Schicht für den Spotlight-Rotator ohne Icon-Font-Abhängigkeit; sichtbar bleiben nur die Pfeile links/rechts, Autoplay ist optional und respektiert Reduced-Motion.
- Public-Rundungen sind bewusst kantig und auf maximal `2px` begrenzt, auch wenn ältere Admin-Settings höhere Radiuswerte enthalten.
- Companies-Partnerlisten nutzen echte `is_partner`-/`is_top_partner`-Felder; Expert-Partnerlisten fallen schema-schonend auf aktive Expertenprofile zurück.

## Aktueller Audit-Stand

- **1.0.38:** UI-Fix: Bei Bild-oben erscheint die Hero-Suche als eigenes helleres Band unter Logo/Untertext; der Untertext steht wieder direkt unter dem Logo.
- **1.0.37:** UI-Fix: Bereichskarten-CTA (`… entdecken`) sitzt immer rechts unten in der Card.
- **1.0.36:** Fix: Bei Bild-oben/Text-unten nutzt die Hero-Suche die volle Contentbreite; der Untertext steht unter dem Suchfeld statt unter dem Logo.
- **1.0.35:** Fix: Hero-Suche in Split-Layouts nutzt die volle Textspaltenbreite, Suchfeld/Button `4px`, Bild unten maximal `50px` Abstand.
- **1.0.34:** Fix: `image-left` links bündig, `image-right` rechts bündig am Content-Rand; Bild-/Textabstand bleibt maximal `50px`.
- **1.0.33:** Fix: `image-left` startet Bild/Logo bündig am linken Content-Rand, passend zum darunterliegenden Content.
- **1.0.32:** UI-Polish: Hero ohne Farbverlauf; Hintergrund, Text, Label, Suche und Button nutzen passende Hero-Farbsettings.
- **1.0.31:** Fix: Suchband in `image-left`/`image-right` bündig am Textanfang; Logo-/Textabstand pixelbasiert maximal `50px`.
- **1.0.30:** Fix: Bei Hero-Bild/Logo ohne `Label über H1` ist der obere Bildabstand zum Hero-Hintergrundrand maximal `25px`.
- **1.0.29:** Fix: Leeres Hero-Feld `Label über H1` blendet das Label aus, statt auf den Default-Text zurückzufallen.
- **1.0.28:** Fix: Hero-Split-Layouts nutzen fest `33%` Bild links/rechts und `67%` Text daneben.
- **1.0.27:** Fix: Hero-Bildgröße und Hero-Bildposition folgen der Admin-Auswahl zuverlässig; Split-Layouts zeigen das Bild auch bei `H1 ersetzen` seitlich.
- **1.0.26:** UI-Polish: Bereichskarten auf blaue Töne umgestellt; Icon sitzt oben rechts als Corner-Badge auf diagonaler Dreieck-Fläche.
- **1.0.25:** Feature: Hero-Bildgröße, drei Hero-Layouts und Bildmodus `H1 ersetzen` oder `mit Titel anzeigen` ergänzt.
- **1.0.24:** UI-Polish: Bereichskarten optisch überarbeitet und als `Events/Speaker` sowie `Experts/Firmen` im zweispaltigen 2×2-Raster ausgegeben.
- **1.0.23:** Fix: Jahreszahl im Kalender-Icon von `Nächstes Event` wieder mittig ausgerichtet.
- **1.0.22:** UI-Polish: Public-Rundungen maximal `2px`; `Im Fokus` ohne Dot-/Pagination-Leiste, nur mit Pfeilen links und rechts.
- **1.0.21:** Redesign: Die Public-HubSite folgt dem HTML-Preview ohne eigenen Plugin-Header oder Menüband; das Partnerband sitzt ohne äußeren Abstand direkt unter dem Theme-Header und über den weiteren Hub-Bereichen.
- **1.0.20:** Feature: Die 365NETWORK-Landingpage nutzt eine eigene Suchroute `/365network/search`, getrennt von der globalen 365CMS-Suche, und durchsucht ausschließlich Events, Speaker, Firmen und Experten.
- **1.0.19:** Fix/UX: Hub-/Textänderungen leeren nach dem Speichern immer den Public-Cache; ältere Hub-Settings-Schemata werden für Textarea-Felder, Dubletten und fehlende `setting_key`-Eindeutigkeit migriert, Featured-Bildhöhe ist steuerbar und zeigt Bilder vollständig ohne Zuschnitt, Hero hat kompakt/normal/groß als Höhen-Layout und nutzt die volle H1-Kachelbreite, der Mediathek-Picker öffnet mit Fallback und Public-Kacheln bleiben ohne Hover-Unterstreichung.
- **1.0.18:** Feature: Hub-Bereiche und Direkteinstieg-Karten sind im Admin sortierbar; Bild-URL-Felder können Bilder direkt aus der 365CMS-Mediathek übernehmen.
- **1.0.17:** Fix: Hub-Suche nutzt `/search` statt `/suche`, stale Defaults werden migriert, der Toolbox-Gesamtlink zeigt auf `/m365-tools` und die Public-Toolbox kann aktive `cms-m365tools`-Registry-Tools anzeigen.
- **1.0.16:** Audit-Fix: Core-Metadaten im Plugin-Header, `cms_register_hook`-kompatible `hub_install()`/`hub_uninstall()`-Callbacks, Core-Helper für Admin-Menü/Capability/Nonce/Notice/Redirect, kein Inline-Redirect-Script und request-lokaler Statistik-Cache.
- **1.0.15:** Admin-UX: Hub-Bereiche sind eigene Tabs mit Aktivierung, Text-/Content-, Layout- und Design-Settings; Public-Ausgabe nutzt die neuen Werte direkt über Klassen und CSS-Variablen.
- **1.0.14:** Audit-Fix: Core-kompatible Toolbox-Aktivprüfung ohne direkte Plugin-Tabellenprüfung, HTTP(S)-Only Featured-Bilder, request-lokaler Settings-Cache und kein Inline-onclick in leeren Bereichskacheln.
- **1.0.13:** Featured Card steht als erste Hub-Komponente vor dem Hero; ohne Bild nutzt sie eine kompakte einspaltige Textvariante statt leerem Placeholder.
- **1.0.12:** Hub-Bereiche über `cms_network_hub_settings` typisiert administrierbar; Public-Ausgabe nutzt Sichtbarkeit, Texte, URLs, Icons, Suchparameter und Toolbox-Limit aus den Settings.
- **1.0.11:** Optionaler M365-Toolbox-Bereich nach den Direkteinstieg-Kacheln ergänzt; die Sektion erscheint nur bei aktivem Toolbox-Plugin und aktiven `show_on_hub`-Links.
- **1.0.10:** `Nächstes Event` und Suche sind als getrennte volle Zeilen umgesetzt; leere Bereichskacheln zeigen nur noch `Demnächst verfügbar` ohne Ansehen-Link.
- **1.0.9:** Bereichskacheln mit sprechenden Linktexten, individuelleren Standard-Subtexten, `Demnächst verfügbar`-Hinweis bei leeren Bereichen sowie neuer Suche und Nächstes-Event-Teaser.
- **1.0.8:** Direkteinstieg-Karten wieder vertikal mit prominentem Iconblock oben rechts; Hero, Stat-Kacheln und CTAs bleiben im vorherigen Stand.
- **1.0.7:** Landingpage-Zähler verwenden zuerst die Plugin-Database-APIs und erst danach direkte SQL-Fallbacks mit robuster Tabellenauflösung.
- **1.0.6:** Landingpage-Zähler zählen vorhandene Integrationstabellen direkt und werden nicht mehr durch PluginManager-/Klassen-Ladezeitpunkt auf `0` blockiert.
- **1.0.5:** 365NETWORK-Hub-Landingpage visuell neu aufgebaut; Hero, Metric-Cards und Direkteinstieg sind unter `.cms-network-hub-wrap` gescopt, ohne DB-/COUNT-Logik anzufassen.
- **1.0.4:** Landingpage-Statistiken zählen geladene/aktive Integrationen robuster und berücksichtigen kompatible öffentliche Statuswerte statt fälschlich `0` auszugeben.
- **1.0.3:** Public-Layout ohne Sidebar poliert: Featured Card nutzt ein dezentes Content-Inset; Bereichskarten bleiben im normalen Raster und erhalten ein dezentes Corner-/Icon-Design.
- **1.0.2:** Speicherbug behoben, bei dem ein Tab-Speichern Werte anderer Tabs auf Defaults zurücksetzen konnte.
- Public-Vorschauen nutzen kanonische URLs und feste Bild-Dimensionen gegen Layout-Shift.
- Sicherheits-/Kompatibilitätsprüfung für die geänderten Dateien ohne kritische Treffer.

## Einrichtung

1. Plugin aktivieren.
2. Adminbereich öffnen: **365NETWORK** oder `/admin/365network`.
3. Zusatzdomain ohne Protokoll eintragen, z. B. `network.example.com`.
4. DNS/Webserver so konfigurieren, dass die Zusatzdomain auf dieselbe 365CMS-Installation zeigt.
5. Landingpage über `/365network` testen.

## Verhalten bei Domains

- Auf der Hauptdomain bleibt die normale CMS-Startseite aktiv.
- Auf konfigurierten Zusatzdomains wird `/` als 365NETWORK-Landingpage gerendert.
- Die interne Route bleibt zusätzlich verfügbar.

## Analytics / Matomo

Im Tab **Analytics** kann ein Analyse-Snippet, z. B. ein Matomo-Code, hinterlegt werden. Die Ausgabe ist bewusst streng auf die Plugin-Public-Site begrenzt:

- interne Landingpage-Route, standardmäßig `/365network`
- Root-Seite einer konfigurierten Zusatzdomain

Andere CMS-Seiten, Event-Seiten, Firmenprofile, Blogseiten oder Adminseiten laden diesen Code nicht.

## Abhängigkeiten

Das Plugin funktioniert eigenständig. Für dynamische Inhalte nutzt es optional:

- `cms-events`
- `cms-speakers`
- `cms-companies`
- `cms-experts`
- `m365toolbox`/`cms-m365tools` für den optionalen Tool-Link-Bereich

Sind diese Plugins nicht aktiv oder fehlen Tabellen, werden die jeweiligen Vorschaukarten leer/fallbackend angezeigt.
Für Legacy-Toolbox-Installationen werden aktive Datensätze in `m365toolbox_links` mit `show_on_hub = 1` bevorzugt. Ist stattdessen die aktuelle `cms-m365tools`-Toolbox aktiv, nutzt 365NETWORK automatisch deren Tool-Registry.
