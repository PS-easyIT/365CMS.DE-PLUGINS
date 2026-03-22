# Architektur des zentralen Marketplace

## Verzeichnisstruktur auf der zentralen Site

```text
/marketplace/
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

## Datenquelle

Das Plugin speichert die Marketplace-Einträge in einer eigenen Tabelle:

- `*_marketplace_items`

Je Eintrag werden u. a. gespeichert:

- Typ `plugin` oder `theme`
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

Über `/marketplace-submit` können Plugins und Themes öffentlich eingereicht werden.

Diese Einreichungen werden immer:

- mit Quelle `public` gespeichert
- nicht automatisch veröffentlicht
- erst nach Admin-Prüfung durch Freigabe sichtbar

## Ziel für spätere 365CMS-Clients

Externe 365CMS-Installationen sollen später diese Endpunkte lesen können:

- Plugin-Katalog: `https://365cms.de/marketplace/plugins/index.json`
- Theme-Katalog: `https://365cms.de/marketplace/themes/index.json`
- Plugin-Manifest: `https://365cms.de/marketplace/plugins/<slug>/manifest.json`
- Theme-Manifest: `https://365cms.de/marketplace/themes/<slug>/manifest.json`
- Plugin-Update: `https://365cms.de/marketplace/plugins/<slug>/update.json`
- Theme-Update: `https://365cms.de/marketplace/themes/<slug>/update.json`

## Sicherheitsansatz

- nur ZIP-Dateien
- ZIP-Slip-Schutz bei der Paketprüfung
- Root-Ordner in der ZIP muss dem `slug` entsprechen
- SHA-256 wird nach dem Speichern des Pakets berechnet
- öffentliche Kataloge werden nur aus freigegebenen Einträgen erzeugt
