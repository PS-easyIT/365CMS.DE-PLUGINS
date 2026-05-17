# M365-PLUGINS – Planungsordner

Dieser Ordner enthält die initialen Konzept- und Umsetzungspläne für die M365-Tools rund um Lizenzen, Kosten, ROI, Migration und Optimierung für `phinit.de`.

## Ausgangslage

- Die Anforderung spricht von **22 Tool-Ideen**, die bereitgestellte Liste enthält aber **25 konkrete Tools**.
- Deshalb wurden hier **alle 25 gelisteten Tools** jeweils als eigene Markdown-Planungsdatei angelegt.
- Als gestalterische und funktionale Vorlage dient das bestehende Plugin `cms-m365lic`.

## Gemeinsamer Design-Blueprint aus `cms-m365lic`

Alle Tools sollten – je nach Komplexität – dieselbe Produktsprache verwenden:

- dunkler Hero mit Navy-/Gold-/Teal-Akzenten
- klare KPI-Karten direkt über den Ergebnissen
- modularer Wizard oder Konfigurator statt langer Einzelformulare
- Ergebnisblöcke als Karten + Tabellen + Delta-/Summenfelder
- konsistente CTA-Zone für PDF-Export, Beratung und Kontaktaufnahme
- Theme-Embed statt isolierter Tool-Seite
- mobil gut bedienbar, mit gestapelten Karten statt unlesbarer Desktop-Tabellen

## Gemeinsame technische Basis

Für die spätere Umsetzung sollte möglichst viel gemeinsam genutzt werden:

- zentrale Preis- und Produktdaten, idealerweise `pricing.json` + ergänzende Katalogdateien
- gemeinsame Render-Bausteine für Hero, KPI-Cards, Ergebnis-Cards, Vergleichstabellen, CTA-Footer
- gemeinsamer PDF-Export-Flow
- Admin-Pflege für Preise, Texte, SEO, Quellenstand und Defaults
- Tracking für Tool-Start, Ergebnis erzeugt, PDF exportiert und Beratung angefragt

## Empfohlene gemeinsame Datenquellen

- `pricing.json` – M365-Basispreise, Add-ons, Zahlungsmodelle, Aufschläge
- `plans.json` – Plan-Matrix, Features, Voraussetzungen, Zielgruppen
- `addons.json` – Add-on-Katalog inkl. Überschneidungen und Pflichtvoraussetzungen
- `providers.json` – Alternativanbieter, Backup-Tools, Migrations-Tools, Telefonie-Provider
- `assumptions.json` – Default-Annahmen für ROI, Migration, Schulung, Admin-Aufwand
- `price-history.json` – Preisentwicklungen, Regionen, Stichtage, Quellenlinks

## Wiederverwendbare Funktionsbausteine

Diese Funktionsfamilien tauchen in fast allen Tools wieder auf:

- `load_*_catalog()` – Stammdaten laden
- `validate_*_input()` – Eingaben normalisieren und prüfen
- `calculate_*()` – Kernlogik oder Simulation
- `build_*_result_viewmodel()` – Ergebnisdaten für Templates vorbereiten
- `render_*_page()` – Frontend-Seite rendern
- `export_*_pdf()` – PDF-Auswertung erzeugen
- `track_*_event()` – Conversion- und Nutzungstracking

## Inhaltsverzeichnis

### Lizenz & Preis-Beratung

1. `01-m365-lizenz-berater-killer-tool.md`
2. `02-lizenz-vergleichstabelle.md`
3. `03-add-on-konfigurator.md`
4. `04-step-up-vs-mix-berater.md`
5. `05-annual-vs-monthly-commitment-rechner.md`

### KI & Copilot

6. `06-copilot-roi-rechner.md`
7. `07-copilot-lizenz-pflicht-checker.md`
8. `08-ai-pack-vs-copilot-pro-vergleich.md`
9. `09-pilot-phase-rechner-copilot.md`

### Storage, Backup & Capacity

10. `10-storage-bedarfs-rechner.md`
11. `11-archive-mailbox-rechner.md`
12. `12-m365-backup-kosten-rechner.md`
13. `13-sharepoint-storage-limit-rechner.md`

### Migration & Wechsel

14. `14-google-workspace-zu-m365-tco-rechner.md`
15. `15-on-premise-exchange-zu-exchange-online-roi.md`
16. `16-tenant-tenant-migration-kosten-schaetzer.md`
17. `17-csp-vs-ea-vs-mca-vergleich.md`

### Optimierung & Audit

18. `18-inaktive-user-sparpotenzial-rechner.md`
19. `19-frontline-worker-lizenz-eignung-check.md`
20. `20-shared-mailbox-vs-lizenz-rechner.md`
21. `21-lizenz-audit-checkliste.md`

### Spezialthemen

22. `22-power-platform-kosten-kalkulator.md`
23. `23-microsoft-preiserhoehung-tracker.md`
24. `24-teams-phone-lizenz-berater.md`
25. `25-eu-data-boundary-lizenz-aufpreis-rechner.md`

## Empfohlene Priorisierung

1. `06-copilot-roi-rechner.md`
2. `01-m365-lizenz-berater-killer-tool.md`
3. `02-lizenz-vergleichstabelle.md`
4. `03-add-on-konfigurator.md`
5. `12-m365-backup-kosten-rechner.md`

## Toolbox-Gedanke

Die Einzeltools sollten später zusätzlich in einer gemeinsamen Landingpage **„M365 Cost Toolbox“** gebündelt werden. Dadurch entstehen:

- eine starke SEO-Hub-Seite
- interne Verlinkung zwischen den Tools
- bessere Lead-Übergänge in Beratung, Audit und CSP-Services
- zentrale Pflege von Preisstand und Quellenhinweisen

Stand dieses Planungsordners: `15.05.2026`