# JSON-Formate des zentralen Marketplace

## 1. Plugin-Katalog

Pfad:

```text
https://365cms.de/marketplace/plugins/index.json
```

Beispielstruktur:

```json
{
  "generated_at": "2026-03-22T10:30:00+00:00",
  "site": "https://365cms.de",
  "base_url": "https://365cms.de/marketplace/plugins",
  "plugins": [
    {
      "slug": "cms-example",
      "name": "CMS Example",
      "type": "plugin",
      "version": "1.0.0",
      "author": "365 Network",
      "description": "Beispiel-Plugin",
      "category": "utilities",
      "download_url": "https://365cms.de/marketplace/plugins/cms-example/cms-example-1.0.0.zip",
      "package_url": "https://365cms.de/marketplace/plugins/cms-example/cms-example-1.0.0.zip",
      "manifest": "https://365cms.de/marketplace/plugins/cms-example/manifest.json",
      "update_url": "https://365cms.de/marketplace/plugins/cms-example/update.json",
      "purchase_url": "",
      "is_paid": false,
      "is_commercial": false,
      "price_amount": null,
      "price_currency": "",
      "price": "",
      "contact_form_slug": "",
      "purchase_type": "download",
      "sha256": "<sha256>",
      "checksum_sha256": "<sha256>",
      "package_size": 123456,
      "homepage_url": "https://365cms.de",
      "docs_url": "https://365cms.de/docs/cms-example",
      "changelog_url": "https://365cms.de/docs/cms-example/changelog",
      "icon_url": "https://365cms.de/assets/example-icon.png",
      "screenshot": "https://365cms.de/assets/example-screen.png",
      "requires_cms": "2.6.0",
      "min_cms_version": "2.6.0",
      "requires_php": "8.4",
      "min_php": "8.4",
      "tested_up_to": "2.6.0",
      "released": "2026-03-22",
      "notes": "Stable release"
    }
  ]
}
```

## 2. Theme-Katalog

Pfad:

```text
https://365cms.de/marketplace/themes/index.json
```

Der Aufbau entspricht dem Plugin-Katalog, nur mit dem Array-Schlüssel `themes`.

Bei kostenpflichtigen Einträgen ist typischerweise:

- `download_url` leer
- `purchase_url` gesetzt
- `is_paid = true`
- `price_amount`, `price_currency` und `price` gefüllt
- `contact_form_slug` gesetzt
- `purchase_type = contact_form`

## 3. Manifest pro Eintrag

Pfad:

```text
https://365cms.de/marketplace/<plugins|themes>/<slug>/manifest.json
```

Zusätzliche Felder gegenüber dem Index:

- `id`
- `published_at`
- `created_at`
- `updated_at`

## 4. Update-Datei pro Eintrag

Pfad:

```text
https://365cms.de/marketplace/<plugins|themes>/<slug>/update.json
```

Aktuell identisch zu `manifest.json`, damit bestehende Update-Logik in 365CMS direkt darauf zugreifen kann.

## Feldkompatibilität zu vorhandenem Core

Die wichtigsten bereits kompatiblen Felder für den vorhandenen Core sind:

- `slug`
- `name`
- `version`
- `description`
- `author`
- `download_url`
- `sha256`
- `checksum_sha256`
- `requires_cms`
- `min_cms_version`
- `requires_php`
- `min_php`
- `manifest`
- `update_url`
- `screenshot`

## Zusätzliche Felder für spätere Marketplace-Clients

Für kostenpflichtige Einträge und Public-Submission sind zusätzlich relevant:

- `purchase_url`
- `is_paid`
- `is_commercial`
- `price_amount`
- `price_currency`
- `price`
- `contact_form_slug`
- `purchase_type`
- `submission_source`
