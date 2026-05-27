# CMS 365NETWORK

**Version:** 1.0.1  
**Status:** Domainbasierte HubSite/Landingpage für Netzwerk-Portale.

CMS 365NETWORK stellt eine eigene Landingpage bereit, die auf einer konfigurierten Zusatzdomain direkt als Root-Seite erscheinen kann. Sie bündelt vier zentrale Bereiche des Netzwerks:

- Events
- Speaker
- Firmen
- Experten

Zusätzlich gibt es eine interne Vorschau-Route, standardmäßig `/365network`.

## Features

- Zusatzdomain-Mapping ohne Core-Anpassung: Root-Aufruf der Domain zeigt die Landingpage.
- Moderne HubSite mit Hero-Bereich, Kennzahlen, Featured Card und vier Bereichskarten.
- Layoutvarianten: `2x2`, `4x1` oder automatisch responsiv.
- Design steuerbar: Breite, Rundungen, Abstände, Farben.
- Optionale Sidebar mit kommenden Events und zufälligen Speaker-/Firmen-/Experten-Karten.
- Preview-Cards alternativ unterhalb der vier Bereiche.
- Optionaler Matomo-/SEO-Analyse-Code nur für die 365NETWORK-Public-Site.
- Defensive Cross-Plugin-Integration: läuft auch, wenn einzelne Module deaktiviert sind.

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

Sind diese Plugins nicht aktiv oder fehlen Tabellen, werden die jeweiligen Vorschaukarten leer/fallbackend angezeigt.
