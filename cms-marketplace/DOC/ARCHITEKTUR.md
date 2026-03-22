# Architektur des zentralen Marketplace

## Verzeichnisstruktur auf der zentralen Site

```text
/marketplace/
├── index.json
├── core/
│   └── 365cms/
│       ├── index.json
│       ├── manifest.json
│       ├── update.json
│       └── 365cms-<version>.zip
├── plugins/
│   ├── index.json
│   └── <slug>/
│       ├── <slug>-<version>.zip
│       ├── manifest.json
│       └── update.json
└── themes/
    ├── index.json
    └── <slug>/
        ├── <slug>-<version>.zip
        ├── manifest.json
        └── update.json
```

Zusätzlich gibt es einen öffentlichen Einreichungsbereich unter:

```text
/marketplace-submit
```

Zusätzlich rendert das Plugin öffentliche HTML-Seiten direkt selbst:

- `/marketplace-public` als Übersicht aller öffentlichen Bereiche
- `/marketplace-public/plugins`
- `/marketplace-public/themes`
- `/marketplace-public/cms`

## Datenquelle

Das Plugin speichert die Marketplace-Einträge in einer eigenen Tabelle:

- `*_marketplace_items`

Je Eintrag werden u. a. gespeichert:

- Typ `cms`, `plugin` oder `theme`
- `slug`
- `name`
- `version`
- Autor und Beschreibung
- ZIP-Dateiname und Speicherpfad
- SHA-256-Prüfsumme
- Release-Datum
- Veröffentlichungsstatus
- kostenpflichtig oder kostenlos
- Preis und Währung
- Kontaktformular-Ziel für Kauf-/Anfragevorgänge
- Herkunft des Eintrags `admin` oder `public`
- Einreicher-Name und Einreicher-E-Mail bei öffentlicher Einreichung

## Veröffentlichungslogik

Nur Einträge mit `is_published = 1` werden in den öffentlichen JSON-Dateien ausgegeben.

Zusätzlich wird pro `slug` nur die jeweils höchste Version in den zentralen `index.json`-Dateien veröffentlicht.

Für `cms` wird zusätzlich immer ein zentraler `manifest.json`- und `update.json`-Stand im Verzeichnis `/marketplace/core/365cms/` erzeugt.

Wenn für denselben `slug` eine höhere Version angelegt und freigegeben wird, werden die öffentlichen Dateien automatisch aktualisiert:

- `index.json` zeigt die neueste Version
- `manifest.json` wird auf die neueste freigegebene Version geschrieben
- `update.json` wird auf die neueste freigegebene Version geschrieben

Vorhandene ältere ZIP-Dateien können im Verzeichnis bestehen bleiben, werden aber nicht mehr als aktuelle Version im Index geführt.

## Kostenpflichtige Einträge

Kostenpflichtige Plugins und Themes benötigen kein Paket.

Für sie werden stattdessen im öffentlichen Katalog gespeichert:

- Preis
- Währung
- Kennzeichen `is_paid`
- Kauf-/Anfrage-Link über das hinterlegte Kontaktformular-Ziel

Damit können spätere 365CMS-Clients oder öffentliche Marketplace-Ansichten statt eines Downloads einen Kauf- oder Anfrageprozess anbieten.

## Öffentliche Einreichungen

Über den konfigurierbaren Public-Submission-Pfad können CMS-, Plugin- und Theme-Pakete öffentlich eingereicht werden.

Diese Einreichungen werden immer:

- mit Quelle `public` gespeichert
- nicht automatisch veröffentlicht
- erst nach Admin-Prüfung durch Freigabe sichtbar

## Ziel für spätere 365CMS-Clients

Externe 365CMS-Installationen sollen später diese Endpunkte lesen können:

- Öffentliche HTML-Übersicht: `https://365cms.de/marketplace-public`
- Plugin-Katalog: `https://365cms.de/marketplace/plugins/index.json`
- Theme-Katalog: `https://365cms.de/marketplace/themes/index.json`
- CMS-Kanal: `https://365cms.de/marketplace/core/365cms/update.json`
- Plugin-Manifest: `https://365cms.de/marketplace/plugins/<slug>/manifest.json`
- Theme-Manifest: `https://365cms.de/marketplace/themes/<slug>/manifest.json`
- Plugin-Update: `https://365cms.de/marketplace/plugins/<slug>/update.json`
- Theme-Update: `https://365cms.de/marketplace/themes/<slug>/update.json`

## Admin-Architektur

Das Plugin gliedert den Adminbereich in eigene Bereiche für:

- `Übersicht`
- `CMS`
- `Plugins`
- `Themes`
- `Verzeichnis`
- `Einstellungen`

In den Einstellungen werden bereichsspezifische Defaults für `CMS`, `Plugins` und `Themes` persistent gespeichert.

## Sicherheitsansatz

- nur ZIP-Dateien
- ZIP-Slip-Schutz bei der Paketprüfung
- Root-Ordner in der ZIP muss dem `slug` entsprechen
- SHA-256 wird nach dem Speichern des Pakets berechnet
- öffentliche Kataloge werden nur aus freigegebenen Einträgen erzeugt
