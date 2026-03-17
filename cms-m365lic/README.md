# CMS M365 License

> Microsoft-365-Lizenzberater für 365CMS mit Paketkatalog, Copilot-Unterstützung, Bedarfsanalyse für Public/Member/Spezial und PDF-Export.

## Features

- Mehrzeilige Bedarfsanalyse für verschiedene Benutzergruppen
- Vordefinierte Presets wie `Nur Mail`, `Mail + Teams`, `Web Office + Mail + OneDrive`, `Knowledge Worker`, `Frontline`
- Seed-Katalog mit Microsoft-365-Basislizenzen, Frontline-SKUs, Copilot-Optionen und Add-ons
- Vollständig vorbelegte Basispreise für alle Seed-SKUs inklusive Power BI, Teams Phone, Visio, Project, Exchange, SharePoint, Entra-/Defender-Erweiterungen und Copilot-Optionen
- Alle Preisangaben und Auswertungen konsistent in Euro (EUR / €)
- Bedarfsmerkmal für `Terminalserver / Shared Computer Activation`, damit RDS-/Terminalserver-Szenarien gezielt auf passende Microsoft-365-/Office-SKUs gelenkt werden
- 3-stufiger Wizard mit getrenntem Add-on-/Security-Schritt für gezielte Erweiterungen wie Defender, Entra ID P2 oder Copilot
- Getrennte Zugriffsflächen für `public`, `member` und `group`
- Eigene Admin-Seite für Spezial-User-Zuweisungen direkt auf 365CMS-Benutzer
- Auswahl von Laufzeit & Zahlungsart je Bereich: `1 Jahr jährlich`, `1 Jahr monatlich (+5%)`, `1 Monat (+20%)`
- Optionale Alternativanbieter-Tabelle unter der Auswertung mit separaten Jahres-/Monatspreisen je Kategorie und Anbieter
- Eigene pflegbare Admin-Bereiche für `EU-Alternativen` und `europäische KI-/Copilot-Alternativen`
- Eigene Public-Site `EU-Vergleich`, um Microsoft-365-Pläne mit europäischen All-in-One- und Best-of-Breed-Anbietern zu vergleichen
- Tageslimits für Auswertung und PDF-Export je Kontext
- Publicsite, die Header/Footer des aktiven Themes verwendet
- Echter PDF-Export der Ergebnisübersicht über den 365CMS-PDF-Stack
- Adminverwaltung für Pakete, Feature-Matrix, Spezial-User, Preise und Settings

## Routen

| Route | Methode | Zweck |
|---|---|---|
| `/m365-lizenzberater` | GET/POST | Public Calculator mit Auswertung |
| `/m365-lizenzberater/eu-vergleich` | GET/POST | Public-Vergleich von M365 mit europäischen Alternativen |
| `/member/plugin/m365-license` | GET/POST | Geschützter Member-Calculator |
| `/member/plugin/m365-license-special` | GET/POST | Geschützter Spezial-Calculator für zugewiesene Benutzer |
| `/api/m365lic/export` | POST | PDF-Export der aktuellen Auswertung |

> Der öffentliche Haupt-Slug ist im Admin änderbar. Member- und Spezialbereich laufen bewusst ausschließlich über den 365CMS-Login im Member-Bereich.

## Paketlogik

Das Plugin unterscheidet zwischen:

- **Basislizenzen**: z. B. Business Basic, Business Standard, Business Premium, Office 365 E3, Microsoft 365 E5, F1/F3
- **Add-ons**: z. B. Microsoft 365 Copilot, Copilot Business, Copilot Chat, Teams Premium, Power BI Pro, Project, Visio, Planner, Entra ID P1/P2 und mehrere Defender-Produkte

Copilot-Lizenzvoraussetzungen wurden anhand offizieller Microsoft-Informationen als Seed-Tags modelliert, damit kompatible Add-ons bevorzugt zu passenden Basispaketen empfohlen werden.

Zusätzlich berücksichtigt die Bedarfsmatrix jetzt auch **Terminalserver-/RDS-Anforderungen**. Wird `Terminalserver / Shared Activation` ausgewählt, bevorzugt der Rechner passende SKUs mit Shared-Computer-Activation-Unterstützung wie `Microsoft 365 Apps for enterprise`, `Office 365 E3/E5`, `Microsoft 365 E3/E5` sowie – für kleinere Setups – `Microsoft 365 Business Premium`.

Für Sicherheits- und Identitäts-Themen gibt es nun außerdem einen dedizierten dritten Wizard-Schritt für gezielte Erweiterungen, z. B. `Microsoft Entra ID P2`, `Defender for Business`, `Defender for Office 365` oder `Defender for Endpoint`.

## Preise

Preisfelder sind absichtlich editierbar und unterstützen drei Ebenen:

- `public_price`
- `member_price`
- `group_price`

Alle Seed-Pakete werden jetzt mit Startpreisen aus öffentlichen Microsoft-/Partner-Snippets als EUR-Basiswerte vorbelegt. Zusätzlich kennt der Katalog zwei Preisarten:

- `per_user`: Preis pro Benutzer/Monat
- `flat_monthly`: monatlicher Fixpreis, z. B. für tenantweite Copilot-Studio-/Security-Copilot-Kapazitäten

Die im Admin gepflegten Preise sind immer der Referenz-Monatspreis in Euro für `1 Jahr Laufzeit mit monatlicher Zahlung (+5%)`. Im Frontend, PDF und jetzt auch in der Admin-Paketübersicht werden daraus die weiteren Modelle abgeleitet:

- `1 Jahr · monatliche Zahlung` → gepflegter Referenzpreis
- `1 Jahr · jährliche Zahlung` → Referenzpreis $\div 1{,}05$
- `1 Monat · monatlich` → `(Referenzpreis \div 1{,}05) \times 1{,}20`

Für die nachgereichten Spezialpreise aus Jahreslisten (`1J1J`) rechnet das Plugin die Werte beim Seed-Katalog entsprechend auf den gespeicherten Referenz-Monatspreis herunter. Dadurch bleiben Spezialpreise in der Auswertung für `Jahr / jährlich`, `Jahr / monatlich` und `Monat / monatlich` konsistent.

Bestandsinstallationen übernehmen neue Seed-Preise nun automatisch, solange beim vorhandenen Paket noch kein eigener Preis gepflegt wurde.

Die öffentliche Seite verwendet immer ausschließlich den Public-Kontext. Member- und Spezialpreise werden serverseitig nur in den geschützten Login-Bereichen freigegeben. Die Spezialseite erscheint nur für Benutzer, die im Plugin explizit als Spezial-User zugewiesen wurden.

## Alternativen

Im Admin gibt es unter `Einstellungen -> Alternativen` jetzt drei getrennte Pflegebereiche:

- `Normale Alternativen`
- `EU-Alternativen`
- `Europäische KI- & Copilot-Alternativen`

Für normale Alternativen werden pro Eintrag gespeichert:

- `Kategorie` – z. B. `Mail`, `Storage`, `Office`, `Security`
- `Anbieter` – z. B. `Google Workspace`, `Dropbox`, `Zoho`
- `Preis 1 Jahr`
- `Preis monatlich`

Ab Werk ist die Liste jetzt bereits mit mehreren Vergleichsanbietern vorbefüllt. Die Seed-Daten decken diese Bereiche ab:

- `Mail`
- `Office & Produktivität`
- `Storage & Dateien`
- `Zusammenarbeit & Meetings`
- `Projektmanagement`
- `Identität & Sicherheit`

Je Bereich sind kuratierte Vergleichseinträge hinterlegt, typischerweise aus 3 bis 6 Angeboten/Plänen, unter anderem von:

- `Google Workspace`
- `Zoho`
- `Proton`
- `Dropbox`
- `Slack`
- `Asana`
- `MeisterTask`

Im Frontend kann der Benutzer neben der Laufzeit die Option **„Nach der Auswertung Alternativen anzeigen“** aktivieren. Nach der M365-Auswertung erscheint dann eine Tabelle im Format:

`Kategorie | Anbieter | Preis`

Wichtig: Für Alternativen wird **keine** prozentuale Laufzeitlogik aus den Microsoft-365-Preisen angewendet. Es werden immer die im Admin direkt gepflegten Werte verwendet:

- `annual_upfront` und `annual_monthly` → `Preis 1 Jahr`
- `monthly_flex` → `Preis monatlich`

Fehlt für das gewählte Modell der passende Alternativpreis, wird der Eintrag im Frontend nicht angezeigt.

Für `EU-Alternativen` und `Europäische KI- & Copilot-Alternativen` kommen zusätzlich `EU-Kategorie` bzw. `Fokus / Nutzen` dazu. KI-Alternativen dürfen bewusst auch ohne Preis gepflegt werden und erscheinen dann im Frontend mit Preisstatus `offen`.

Die EU-Alternativen decken jetzt zusätzlich auch Enterprise-Add-ons und Services ab, unter anderem:

- `Endpoint Security & XDR`
- `MDM & UEM`
- `Identity & Access (IAM)`
- `Enterprise Projektmanagement`
- `Low-Code & Automatisierung`
- `Datenanalyse & BI`
- `DMS, Archivierung & Compliance`
- `Enterprise Cloud-Telefonie`
- `KI & Copilot`

Bestehende Installationen behalten bereits manuell gepflegte Alternativen. Nur wenn die jeweilige Liste noch leer ist, wird sie automatisch mit den Standardvergleichen initial befüllt.

## EU-Vergleich

Zusätzlich zur normalen Public-Auswertung gibt es jetzt eine eigene Seite `EU-Vergleich`. Dort kann ein öffentlicher Besucher typische Microsoft-365-Pläne wie `Business Basic`, `Business Standard`, `Business Premium`, `Office 365 E3` oder `Microsoft 365 E3` direkt mit europäischen Alternativen vergleichen.

Die Seite unterstützt zwei Modi:

- `All-in-One Workspace` – vergleicht einen M365-Plan mit einem europäischen Komplettanbieter aus der Kategorie `All-in-One Workspaces`
- `Best-of-Breed Stack` – kombiniert einen europäischen Core-Workspace mit optionalen Spezialkategorien für `Office & Produktivität`, `Zusammenarbeit & Intranet`, `IT-Sicherheit & Endgeräteverwaltung` und `Projektmanagement`

Die Auswertung wird als Tabellenvergleich dargestellt:

- links der gewählte M365-Plan
- rechts der ausgewählte europäische Stack
- darunter `Gesamtkosten M365`, `Gesamtkosten Alternativen` und das Preisdelta

Die europäischen Vergleichsanbieter werden jetzt aus den Plugin-Einstellungen gelesen. Der Admin kann damit die komplette EU-Liste inklusive KI-/Copilot-Pendants direkt pflegen, ohne den Code anzufassen.

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
