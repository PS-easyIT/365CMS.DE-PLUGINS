# Vorlage: Standard-Plugin-Paket

Diese Vorlage ist für ein normales 365CMS-Plugin gedacht, das über den Marketplace verteilt werden soll.

## Ordnerstruktur

```text
standard-plugin/
├── cms-your-plugin.php
├── CHANGELOG.md
├── manifest.json
├── update.json
└── marketplace-item.json
```

## So verwendest du die Vorlage

1. Ordner kopieren
2. `your-plugin` durch deinen echten Slug ersetzen
3. in `cms-your-plugin.php` Name, Beschreibung und Version anpassen
4. `CHANGELOG.md` pflegen
5. `manifest.json` und `update.json` anpassen
6. `marketplace-item.json` im Marketplace-Admin als Vorlage für die Eingabedaten nutzen
7. Ordner als ZIP mit Root-Ordner `your-plugin/` packen

## Wichtige Platzhalter

- `{{PLUGIN_SLUG}}`
- `{{PLUGIN_NAME}}`
- `{{PLUGIN_DESCRIPTION}}`
- `{{PLUGIN_VERSION}}`
- `{{PLUGIN_AUTHOR}}`
- `{{REQUIRES_CMS}}`
- `{{REQUIRES_PHP}}`
- `{{HOMEPAGE_URL}}`
- `{{DOCS_URL}}`
- `{{CHANGELOG_URL}}`
- `{{DOWNLOAD_URL}}`
- `{{SCREENSHOT_URL}}`
- `{{ICON_URL}}`
- `{{SHA256}}`

## Hinweis

`manifest.json` und `update.json` sind hier bewusst fast identisch gehalten, damit du sie schnell vorbereiten kannst. Im produktiven Marketplace werden diese Dateien später automatisch aus dem Plugin heraus erzeugt.
