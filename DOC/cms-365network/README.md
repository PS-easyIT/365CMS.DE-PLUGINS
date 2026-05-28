# CMS 365NETWORK

**Version:** 1.0.14
**Status:** Domainbasierte HubSite/Landingpage für Netzwerk-Portale.

CMS 365NETWORK stellt eine eigene Landingpage bereit, die auf einer konfigurierten Zusatzdomain direkt als Root-Seite erscheinen kann. Sie bündelt vier zentrale Bereiche des Netzwerks:

- Events
- Speaker
- Firmen
- Experten

Zusätzlich gibt es eine interne Vorschau-Route, standardmäßig `/365network`.

## Features

- Zusatzdomain-Mapping ohne Core-Anpassung: Root-Aufruf der Domain zeigt die Landingpage.
- Moderne HubSite mit Hero-Bereich, klickbaren Metric-Cards, Featured Card und vier Bereichskarten.
- Admin-Tab `Hub` zur zentralen Steuerung von Featured Card, Hero, Kennzahlen, Teaser-Band, Direkteinstieg und Toolbox.
- Kompakte Landingpage-Suche und `Nächstes Event`-Teaser für konkrete Einstiegspunkte.
- No-Sidebar-Layout mit breiter Featured Card, dezentem Featured-Inset und überarbeiteten Bereichskarten.
- PHINIT-konformes Navy/Gold-Redesign ohne Electric-Blue-Akzente auf der Landingpage.
- Optionaler M365-Toolbox-Bereich am Ende des Hub-Contents, wenn das Toolbox-Plugin aktiv ist und aktive Hub-Links vorhanden sind.
- Layoutvarianten: `2x2`, `4x1` oder automatisch responsiv.
- Design steuerbar: Breite, Rundungen, Abstände, Farben.
- Optionale Sidebar mit kommenden Events und zufälligen Speaker-/Firmen-/Experten-Karten.
- Preview-Cards alternativ unterhalb der vier Bereiche.
- Optionaler Matomo-/SEO-Analyse-Code nur für die 365NETWORK-Public-Site.
- Defensive Cross-Plugin-Integration: läuft auch, wenn einzelne Module deaktiviert sind.
- Robuste Statistik-Zählung für angebundene Events, Speaker, Firmen und Experten mit Status-Fallbacks.
- Tab-basiertes Speichern im Adminbereich bewahrt bestehende Einstellungen anderer Tabs.
- Kanonische Detail-Links für Events, Speaker, Firmen und Experten in den Vorschauen.

## Aktueller Audit-Stand

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
Für die Toolbox-Sektion müssen zusätzlich aktive Datensätze in `m365toolbox_links` mit `show_on_hub = 1` vorhanden sein.
