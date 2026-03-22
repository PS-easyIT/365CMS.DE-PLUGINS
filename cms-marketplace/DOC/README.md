# cms-marketplace – Dokumentation

Dieses Plugin stellt den zentralen 365CMS-Marketplace auf `365cms.de` bereit.

## Ziel

Das Plugin verwaltet unter der zentralen Site den öffentlichen Bereich:

- `/marketplace/plugins`
- `/marketplace/themes`

Dort werden pro freigegebenem Eintrag automatisch abgelegt:

- das ZIP-Paket
- `manifest.json`
- `update.json`

Kostenpflichtige Einträge können ebenfalls veröffentlicht werden. Für diese ist kein Paket zwingend notwendig. Stattdessen werden Preis- und Kaufdaten über ein Kontaktformular-Ziel veröffentlicht.

Zusätzlich wird je Typ ein zentraler Katalog erzeugt:

- `/marketplace/plugins/index.json`
- `/marketplace/themes/index.json`

Außerdem gibt es einen öffentlichen Einreichungsbereich unter:

- `/marketplace-submit`

## Enthaltene Dokumente

- [ARCHITEKTUR.md](ARCHITEKTUR.md)
- [365CMS-INTEGRATION.md](365CMS-INTEGRATION.md)
- [JSON-FORMATE.md](JSON-FORMATE.md)
- [365CMS-UPDATE-BEREICH.md](365CMS-UPDATE-BEREICH.md)

## Kurzablauf

1. Plugin oder Theme im Adminbereich anlegen
2. optional ZIP-Paket hochladen oder kostenpflichtigen Eintrag mit Preis anlegen
3. Metadaten pflegen
4. Eintrag freigeben
5. `index.json`, `manifest.json` und `update.json` werden automatisch neu geschrieben

## Wichtiger Hinweis

Damit bestehende 365CMS-Installationen die Daten von `365cms.de` abrufen können, müssen im Core aktuell die Marketplace-/Update-Allowlisten erweitert werden. Details dazu stehen in [365CMS-INTEGRATION.md](365CMS-INTEGRATION.md).
