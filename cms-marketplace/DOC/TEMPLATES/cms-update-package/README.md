# Vorlage: CMS-Update-Paket

Diese Vorlage ist für zentrale 365CMS-Core- bzw. CMS-Update-Pakete gedacht.

## Ordnerstruktur

```text
cms-update-package/
├── CHANGELOG.md
├── manifest.json
├── update.json
└── marketplace-item.json
```

## So verwendest du die Vorlage

1. Ordner kopieren
2. Version, Releasedatum und Anforderungen anpassen
3. `CHANGELOG.md` pflegen
4. `manifest.json` und `update.json` auf die neue Release-Datei verweisen lassen
5. `marketplace-item.json` als Vorlage für den CMS-Bereich im Marketplace-Admin verwenden
6. das echte Release-ZIP separat als `365cms-<version>.zip` bereitstellen

## Typische Werte

- Slug: `365cms`
- Typ: `cms`
- Download-Ziel: `/marketplace/core/365cms/365cms-<version>.zip`
- Manifest: `/marketplace/core/365cms/manifest.json`
- Update: `/marketplace/core/365cms/update.json`

## Hinweis

Für CMS-Pakete verwaltet der Marketplace zentral nur die neueste freigegebene Version im öffentlichen Kanal. Passe deshalb immer die letzte veröffentlichte Version und den Changelog sorgfältig an.
