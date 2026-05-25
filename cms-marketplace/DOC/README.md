# cms-marketplace – Dokumentation

Dieses Plugin stellt den zentralen 365CMS-Marketplace auf `365cms.de` bereit.

## Ziel

Das Plugin verwaltet unter der zentralen Site den öffentlichen Bereich:

- `/marketplace/plugins`
- `/marketplace/themes`
- `/marketplace/core/365cms`

Für die HTML-Übersicht und die Bereichsseiten nutzt das Plugin bewusst einen separaten Pfad, damit es keine Kollision mit dem echten Datei-/Feed-Ordner `/marketplace` gibt:

- `/marketplace-public`
- `/marketplace-public/plugins`
- `/marketplace-public/themes`
- `/marketplace-public/cms`

Dort werden pro freigegebenem Eintrag automatisch abgelegt:

- das ZIP-Paket
- `manifest.json`
- `update.json`

Für CMS-Pakete wird zusätzlich ein zentraler Core-Bereich unter `/marketplace/core/365cms` gepflegt.

Kostenpflichtige Einträge können ebenfalls veröffentlicht werden. Für diese ist kein Paket zwingend notwendig. Stattdessen werden Preis- und Kaufdaten über ein Kontaktformular-Ziel veröffentlicht.

Zusätzlich wird je Typ ein zentraler Katalog erzeugt:

- `/marketplace/index.json`
- `/marketplace/plugins/index.json`
- `/marketplace/themes/index.json`
- `/marketplace/core/365cms/update.json`

Außerdem gibt es einen öffentlichen Einreichungsbereich unter:

- `/marketplace-submit`

Der Pfad ist in den Plugin-Einstellungen konfigurierbar und kann deaktiviert werden.

## Enthaltene Dokumente

- [ARCHITEKTUR.md](ARCHITEKTUR.md)
- [365CMS-INTEGRATION.md](365CMS-INTEGRATION.md)
- [JSON-FORMATE.md](JSON-FORMATE.md)
- [365CMS-UPDATE-BEREICH.md](365CMS-UPDATE-BEREICH.md)
- [TEMPLATES/README.md](TEMPLATES/README.md)

## Kurzablauf

1. Im Adminbereich den Bereich `CMS`, `Plugins` oder `Themes` öffnen
2. Paket anlegen oder bearbeiten und optional ZIP-Datei hochladen
3. Metadaten, Anforderungen, Preis-/Kaufdaten und Sichtbarkeit pflegen
4. Eintrag freigeben
5. Öffentliche Seiten, `index.json`, `manifest.json` und `update.json` werden automatisch neu geschrieben

## Funktionsstand

- Öffentliche Marketplace-Übersicht unter `/marketplace-public`
- Öffentliche Bereichsseiten für `CMS`, `Plugins`, `Themes`
- CMS-Paketverwaltung mit eigenem Adminbereich
- Datei-Details mit SHA-256 und Textvorschau in der Verzeichnisansicht
- Separate Standardwerte für `CMS`, `Plugins` und `Themes` in den Einstellungen
- MariaDB-sichere Schema-Migration über `INFORMATION_SCHEMA` statt `SHOW COLUMNS ... LIKE ?`

## Wichtiger Hinweis

Damit bestehende 365CMS-Installationen die Daten von `365cms.de` abrufen können, müssen im Core aktuell die Marketplace-/Update-Allowlisten erweitert werden. Details dazu stehen in [365CMS-INTEGRATION.md](365CMS-INTEGRATION.md).
