# CMS M365 License

> Microsoft-365-Lizenzberater für 365CMS mit Paketkatalog, Copilot-Unterstützung, Bedarfsanalyse, Publicsite und PDF-Export.

## Features

- Mehrzeilige Bedarfsanalyse für verschiedene Benutzergruppen
- Vordefinierte Presets wie `Nur Mail`, `Mail + Teams`, `Web Office + Mail + OneDrive`, `Knowledge Worker`, `Frontline`
- Seed-Katalog mit Microsoft-365-Basislizenzen, Frontline-SKUs, Copilot-Optionen und Add-ons
- Pricing-Tiers für `public`, `member` und `group`
- Tageslimits für Auswertung und PDF-Export je Kontext
- Publicsite, die Header/Footer des aktiven Themes verwendet
- PDF-Export der Ergebnisübersicht
- Adminverwaltung für Pakete, Feature-Matrix, Preise und Settings

## Routen

| Route | Methode | Zweck |
|---|---|---|
| `/m365-lizenzberater` | GET/POST | Public Calculator mit Auswertung |
| `/api/m365lic/export` | POST | PDF-Export der aktuellen Auswertung |

> Der Haupt-Slug ist im Admin änderbar.

## Paketlogik

Das Plugin unterscheidet zwischen:

- **Basislizenzen**: z. B. Business Basic, Business Standard, Business Premium, Office 365 E3, Microsoft 365 E5, F1/F3
- **Add-ons**: z. B. Microsoft 365 Copilot, Copilot Business, Copilot Chat, Teams Premium, Power BI Pro, Project, Visio, Planner

Copilot-Lizenzvoraussetzungen wurden anhand offizieller Microsoft-Informationen als Seed-Tags modelliert, damit kompatible Add-ons bevorzugt zu passenden Basispaketen empfohlen werden.

## Preise

Preisfelder sind absichtlich editierbar und unterstützen drei Ebenen:

- `public_price`
- `member_price`
- `group_price`

Häufige Pakete werden inzwischen mit Startpreisen aus öffentlichen Reseller-Quellen vorbelegt. Nicht öffentlich verfügbare oder stark CSP-/regionsabhängige Preise können weiterhin bewusst leer bleiben. Die Auswertung markiert diese Fälle als `offen`.

Bestandsinstallationen übernehmen neue Seed-Preise nun automatisch, solange beim vorhandenen Paket noch kein eigener Preis gepflegt wurde.

## Verzeichnisstruktur

```text
cms-m365lic/
├── cms-m365lic.php
├── README.md
├── CHANGELOG.md
├── update.json
├── admin/
├── assets/
├── includes/
└── templates/
```

## Lizenz

Copyright © 2026 365 Network. Alle Rechte vorbehalten.
