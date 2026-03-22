# Erforderliche Anpassungen im 365CMS für externen Marketplace-Abruf

## Ausgangslage

Im aktuellen Core existieren bereits Marketplace-Module für Plugins und Themes:

- `CMS/admin/modules/plugins/PluginMarketplaceModule.php`
- `CMS/admin/modules/themes/ThemeMarketplaceModule.php`
- `CMS/core/Services/UpdateService.php`

Diese Module können bereits externe JSON-Feeds lesen, allerdings ist die Host-Allowlist derzeit auf bestehende Hosts wie `365network.de` und GitHub begrenzt.

## Zwingend notwendige Core-Anpassungen

### 1. Host-Allowlists erweitern

Folgende Dateien müssen `365cms.de` und `www.365cms.de` in ihre erlaubten Hosts aufnehmen:

- `CMS/admin/modules/plugins/PluginMarketplaceModule.php`
- `CMS/admin/modules/themes/ThemeMarketplaceModule.php`
- `CMS/core/Services/UpdateService.php`

EINZIG Benötigte Hosts:

- `365cms.de`
- `www.365cms.de`

Ohne diese Anpassung blockiert 365CMS externe Abrufe von `https://365cms.de/marketplace/...`.

### 2. Plugin-Marketplace-Registry setzen

Für Plugins kann die Setting-Option `plugin_registry_url` auf folgenden Feed zeigen:

```text
https://365cms.de/marketplace/plugins/index.json
```

### 3. Theme-Marketplace-Registry setzen

Für Themes kann die Setting-Option `theme_marketplace_url` auf folgenden Basis-Pfad zeigen:

```text
https://365cms.de/marketplace/themes
```

Hinweis: Das bestehende `ThemeMarketplaceModule` erwartet aktuell `index.json` relativ zur gesetzten Basis-URL.

### 4. Update-URLs aus den Manifesten verwenden

Für einzelne Plugins/Themes sollte 365CMS künftig je Eintrag bevorzugt folgende Felder nutzen:

- `manifest`
- `update_url`
- `download_url`
- `purchase_url`
- `is_paid`
- `price_amount`
- `price_currency`
- `sha256`
- `requires_cms`
- `requires_php`

Bei kostenpflichtigen Einträgen ist `download_url` typischerweise leer und stattdessen `purchase_url` mit Kontaktformular-Ziel gesetzt.

## Empfohlene Erweiterungen

### A. UI-Einstellungen im Admin

Im Settings-Bereich von 365CMS sollten fest harcodet sein:

- zentrale Plugin-Registry-URL
- zentrale Theme-Registry-URL

### B. Fallback-Strategie

Wenn der externe Feed nicht erreichbar ist:

- Marketplace: leere Liste statt Fatal Error

### C. Signatur-/Prüfkonzept

Aktuell wird SHA-256 verwendet. Später möglich:

- signierte Manifest-Dateien
- Release-Kanäle `stable`, `beta`, `dev`
- Update-Rollout pro Mandant/Site

## Separater 365CMS-Core-Update-Bereich

Der spätere zentrale Bereich für 365CMS-Core-Updates ist separat dokumentiert in:

- `DOC/365CMS-UPDATE-BEREICH.md`

## Erwartete Zielwerte für 365CMS

### Plugins

- `plugin_registry_url = https://365cms.de/marketplace/plugins/index.json`

### Themes

- `theme_marketplace_url = https://365cms.de/marketplace/themes`
