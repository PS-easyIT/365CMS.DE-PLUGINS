# CMS M365 License

> Microsoft-365-Lizenzberater für 365CMS mit Paketkatalog, Copilot-Unterstützung, Bedarfsanalyse für Public/Member/Spezial und PDF-Export.

## Features

- Mehrzeilige Bedarfsanalyse für verschiedene Benutzergruppen
- Vordefinierte Presets wie `Nur Mail`, `Mail + Teams`, `Web Office + Mail + OneDrive`, `Knowledge Worker`, `Frontline`
- Seed-Katalog mit Microsoft-365-Basislizenzen, Frontline-SKUs, Copilot-Optionen und Add-ons
- Vollständig vorbelegte Basispreise für alle Seed-SKUs inklusive Power BI, Teams Phone, Visio, Project, Exchange, SharePoint und Copilot-Optionen
- Getrennte Zugriffsflächen für `public`, `member` und `group`
- Eigene Admin-Seite für Spezial-User-Zuweisungen direkt auf 365CMS-Benutzer
- Auswahl von Laufzeit & Zahlungsart je Bereich: `1 Jahr jährlich`, `1 Jahr monatlich (+5%)`, `1 Monat (+20%)`
- Tageslimits für Auswertung und PDF-Export je Kontext
- Publicsite, die Header/Footer des aktiven Themes verwendet
- PDF-Export der Ergebnisübersicht
- Adminverwaltung für Pakete, Feature-Matrix, Spezial-User, Preise und Settings

## Routen

| Route | Methode | Zweck |
|---|---|---|
| `/m365-lizenzberater` | GET/POST | Public Calculator mit Auswertung |
| `/member/plugin/m365-license` | GET/POST | Geschützter Member-Calculator |
| `/member/plugin/m365-license-special` | GET/POST | Geschützter Spezial-Calculator für zugewiesene Benutzer |
| `/api/m365lic/export` | POST | PDF-Export der aktuellen Auswertung |

> Der öffentliche Haupt-Slug ist im Admin änderbar. Member- und Spezialbereich laufen bewusst ausschließlich über den 365CMS-Login im Member-Bereich.

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

Alle Seed-Pakete werden jetzt mit Startpreisen aus öffentlichen Microsoft-/Partner-Snippets vorbelegt. Zusätzlich kennt der Katalog zwei Preisarten:

- `per_user`: Preis pro Benutzer/Monat
- `flat_monthly`: monatlicher Fixpreis, z. B. für tenantweite Copilot-Studio-/Security-Copilot-Kapazitäten

Die im Admin gepflegten Preise sind immer der Basiswert für `1 Jahr Laufzeit mit jährlicher Zahlung`. Im Frontend und PDF wird daraus abhängig vom ausgewählten Modell gerechnet:

- `1 Jahr · jährliche Zahlung` → Basispreis
- `1 Jahr · monatliche Zahlung` → Basispreis $\times 1{,}05$
- `1 Monat · monatlich` → Basispreis $\times 1{,}20$

Bestandsinstallationen übernehmen neue Seed-Preise nun automatisch, solange beim vorhandenen Paket noch kein eigener Preis gepflegt wurde.

Die öffentliche Seite verwendet immer ausschließlich den Public-Kontext. Member- und Spezialpreise werden serverseitig nur in den geschützten Login-Bereichen freigegeben. Die Spezialseite erscheint nur für Benutzer, die im Plugin explizit als Spezial-User zugewiesen wurden.

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
