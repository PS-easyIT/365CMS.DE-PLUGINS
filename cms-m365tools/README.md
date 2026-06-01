# CMS M365 Tools

> Hinweis: Die reinen Read-only Lizenz- und Add-on-Matrixseiten wurden in das eigenständige Plugin `cms-m365matrices` ausgelagert. `cms-m365tools` enthält weiterhin die interaktiven Rechner, Vergleiche, Preis-/Landingpage-Verwaltung und die gemeinsamen Options-Tabellen.

`cms-m365tools` ist eine modulare Microsoft-365-Rechner- und Tool-Box für 365CMS. Enthalten sind der **All-Module-Best-Practice-Kompass**, der **Power Platform Kosten-Kalkulator mit Well-Architected-Review**, der **Google Workspace ↔ Microsoft 365 TCO-Rechner**, der **M365 Storage-Bedarfs-Rechner**, der **M365 Backup-Kosten-Rechner**, die **Lizenz-Audit-Checkliste**, der **Microsoft-Preiserhöhung-Tracker**, der **Teams Phone-Lizenz-Berater**, der **On-Premise Exchange zu Exchange Online ROI-Rechner**, der **Frontline Worker Lizenz-Eignung-Check**, der **Copilot Pilot-Phase-Rechner**, der **AI Pack vs. Copilot Pro Vergleich**, der **Archive Mailbox Rechner**, die **M365 Lizenzmatrix**, die **M365 Add-on-Matrix**, der **Annual vs. Monthly Commitment Rechner**, der **M365 Add-On-Konfigurator**, der **M365-Lizenzvergleich**, der **M365-Lizenz-Berater**, der **Copilot ROI-Rechner**, der **Shared-Mailbox vs. Lizenz-Rechner** und der **Copilot Lizenz-Pflicht-Checker**. Ab `1.28.0` übernimmt das Plugin die Paketpreise bevorzugt aus dem aktiven `cms-m365lic` Seed-Katalog und pflegt sie zentral als Public-, Member- und Spezialpreise. Ab `1.29.1` steuert ein eigener `Landingpage Designer`-Unterpunkt Contentheader, Layouts, Boxen, Farben, Rundungen und Sichtbarkeit der Hub-Landingpage. Ab `1.29.3` bündelt der Admin-Unterpunkt `Matrixen` die Read-only Lizenzmatrix und Add-on-Matrix mit Bereichs-Tabs und gemeinsamem Design-Tab. Ab `1.29.12` startet der öffentliche Plugin-Content ohne sichtbaren Theme-Header-Saum, aber mit 25px internem Abstand zwischen Plugin-Hintergrundrand und Contentheader. Der `Content-Hintergrund` im Landingpage Designer gilt für Hub und Public-Modulseiten; Boxen, Headerflächen und Buttons sind auf maximal 2px Rundung begrenzt und heben sich dezent über leicht dunklere Surface-Flächen ab. Ab `1.29.13` kann jedes Modul im eigenen Admin-Tab `Public-Design` diese Public-Farben, Header-, Button-, Surface-, Radius- und Abschnittswerte gezielt übersteuern. Ab `1.29.14` sind Admin-Speicheraktionen fail-closed gehärtet, Public-Design-Routen werden aus der Registry erkannt und cachebare CSS-Resets überschreiben keine Admin-Farben mehr. Ab `1.29.15` speichern Admin-Settings migrationssicher ohne `ON DUPLICATE`-Abhängigkeit und alte Matrix-Modul-Admin-URLs werden ohne 404 in den Matrixen-Bereich geführt. Ab `1.29.16` sind Admin-Speicherungen zusätzlich transaktional abgesichert, Landingpage-Card-Ziele browserseitig validiert und alle Public-Komponenten konsequent auf maximal 2px Radius vereinheitlicht. Ab `1.29.17` verarbeitet Public keine POST-Bodies mehr, Landingpage-Card-Klicks nutzen vorhandene Links und die Public-Routenerkennung ist pro Request gecacht. Ab `1.29.18` sind die Admin-Speicher-Vorprüfungen MariaDB-kompatibel auf `INFORMATION_SCHEMA` umgestellt, sodass Matrixen, zentrale Einstellungen, Landingpage Designer und Modulübersicht ohne `SHOW ... LIKE ?`-Fehler speichern. Ab `1.29.19` sind auch Public-Basisstyles und Admin-Bedienelemente auf maximal 2px Radius vereinheitlicht; Public-Routen, Toolbox-Erkennung und Modulschlüssel werden zusätzlich pro Request gecacht. Ab `1.29.20` starten die Read-only Lizenzmatrix und Add-on-Matrix wieder am Seitenanfang, ohne den Ergebnis-Fokus interaktiver Rechner zu verändern. Ab `1.29.21` sind Admin-Redirects zusätzlich validiert, Matrixseiten stellen den Browser-Scroll-State zurück und die Landingpage-Suche arbeitet mit gecachten Section-Card-Listen. Ab `3.0.0` ist die Admin-Farbwert-Synchronisierung als cachebares `defer`-Asset ausgelagert und die Public-Token-Injection enthält nur noch konfigurierbare Designwerte.

## Enthaltene Routen

- `/m365-tools` – Hub-Übersicht aller Module
- `/m365-rechner` – alternative Hub-Route
- `/m365-lizenz-audit-checkliste` – interaktive Lizenz-Audit-Checkliste mit Browser-Fortschritt, Druckzusammenfassung und Deep Links zu Spezialrechnern
- `/power-platform-kosten-kalkulator` – Power Apps, Power Automate, Dataverse for Teams, Power Pages, Copilot Studio, Credits, Requests, Storage, PAYG, Capacity sowie Security-, ALM-, Performance- und Governance-Reife bewerten
- `/google-workspace-zu-m365-tco` – Google Workspace und Microsoft 365 inklusive Lizenzkosten, Migration, Schulung, Change-Aufwand, Parallelbetrieb und Break-even vergleichen
- `/m365-storage-bedarfsrechner` – SharePoint-Pool, OneDrive-Quotas, Exchange-Postfächer, Archivbedarf, Wachstum und Zusatzspeicherbedarf berechnen
- `/m365-backup-kostenrechner` – Microsoft-365-Backup-Baseline und Providervergleich nach Kosten, Workloads, Retention, Restore-Tiefe und Betriebsmodell berechnen
- `/microsoft-preiserhoehung-tracker` – offizielle Microsoft-Preis-, Packaging-, SKU-, Renewal- und Forecast-Ereignisse mit kompaktem Chart.js-Preisverlauf, Business-Standardauswahl, 5er-Lizenz-Cap und lokalem persönlichen Kosten-Tracker auswerten
- `/teams-phone-lizenzberater` – Teams Phone, Calling Plan, Operator Connect, Direct Routing, Mischmodell und Sonderpfade bewerten
- `/exchange-online-roi` – Vollkosten-, Break-even- und Migrationspfad-Rechner für On-Prem Exchange zu Exchange Online
- `/frontline-worker-lizenz-check` – F1-/F3-Eignung, Mischmodell, Enterprise-Bedarf und Sparpotenzial für Frontline Worker prüfen
- `/copilot-pilot-rechner` – Pilotgröße, Dauer, Budget, Champions, Readiness und Rollout-Zeitplan für Microsoft 365 Copilot berechnen
- `/ai-pack-vs-copilot-pro` – AI Pack, Copilot Pro, Copilot Chat, Microsoft 365 Copilot, Spezial-Copilots und Copilot Studio nach Use Case vergleichen
- `/m365-archive-mailbox-rechner` – Archive Mailbox Rechner für Archivgröße, Auto-expanding Archive, Shared-/Resource-Mailboxen und Hold-/Purview-Hinweise
- `/m365-jahresvertrag-vs-monatsvertrag` – Annual vs. Monthly Commitment Rechner für Monatslaufzeit, Jahresbindung und Split-Strategien
- `/m365-add-on-konfigurator` – Add-ons, Voraussetzungen, Redundanzen, Upgrades und Verbrauchsprodukte prüfen
- `/m365-lizenzvergleich` – filterbare Lizenz-Vergleichstabelle mit Desktop-Apps, Zusatzdiensten und Feature-Status; die Vergleichsspalten sind reaktiv über bis zu vier Dropdowns wählbar
- `/m365-lizenzberater` – M365-Lizenz-Berater mit Gruppen-, Add-on- und Kostenmodell
- `/copilot-roi-rechner` – Copilot Business-Case-, Break-even- und Pilot-/Rollout-Rechner
- `/shared-mailbox-vs-lizenz` – Shared-Mailbox-Entscheidungs- und Kostenrechner
- `/copilot-lizenz-check` – Copilot-Basislizenz-, Chat- und Technik-Readiness-Check

## Modulstruktur

```text
cms-m365tools/
├── cms-m365tools.php
├── includes/
│   ├── class-catalog.php
│   ├── class-tool-registry.php
│   ├── class-frontend.php
│   ├── class-installer.php
│   ├── class-settings.php
│   ├── class-commitment-calculator.php
│   ├── class-archive-mailbox-calculator.php
│   ├── class-ai-product-comparison.php
│   ├── class-copilot-pilot-calculator.php
│   ├── class-frontline-worker-check.php
│   ├── class-exchange-online-roi-calculator.php
│   ├── class-teams-phone-advisor.php
│   ├── class-microsoft-price-tracker.php
│   ├── class-license-audit-checklist.php
│   ├── class-storage-needs-calculator.php
│   ├── class-backup-cost-calculator.php
│   ├── class-workspace-m365-tco-calculator.php
│   ├── class-power-platform-cost-calculator.php
│   ├── class-addon-configurator.php
│   ├── class-license-comparison.php
│   ├── class-license-advisor.php
│   ├── class-shared-mailbox-calculator.php
│   ├── class-copilot-license-checker.php
│   └── class-copilot-roi-calculator.php
├── data/
│   ├── shared_mailbox_rules.json
│   ├── mailbox_license_matrix.json
│   ├── shared_mailbox_scenarios.json
│   ├── pricing.json
│   ├── package_price_catalog.json
│   ├── copilot_eligibility_matrix.json
│   ├── copilot_technical_prerequisites.json
│   ├── license_upgrade_paths.json
│   ├── license_advisor_plans.json
│   ├── license_advisor_addons.json
│   ├── license_advisor_feature_matrix.json
│   ├── license_advisor_persona_presets.json
│   ├── license_advisor_commercial_rules.json
│   ├── copilot_pricing.json
│   ├── copilot_readiness_rules.json
│   ├── roi_assumptions.json
│   ├── persona_roi_presets.json
│   ├── plan_comparison_feature_matrix.json
│   ├── plan_comparison_badges.json
│   ├── plan_comparison_notes.json
│   ├── archive_mailbox_plans.json
│   ├── archive_mailbox_assumptions.json
│   ├── ai_product_catalog.json
│   ├── ai_use_case_matrix.json
│   ├── ai_dynamic_offers.json
│   ├── copilot_pilot_sizes.json
│   ├── copilot_rollout_templates.json
│   ├── copilot_readiness_checklist.json
│   ├── frontline_user_type_matrix.json
│   ├── frontline_plan_matrix.json
│   ├── frontline_industry_presets.json
│   ├── exchange_online_plans.json
│   ├── onprem_exchange_cost_defaults.json
│   ├── exchange_migration_velocity.json
│   ├── teams_phone_base_eligibility.json
│   ├── teams_pstn_model_rules.json
│   ├── teams_country_availability.json
│   ├── teams_voice_providers.json
│   ├── teams_direct_routing_requirements.json
│   ├── teams_phone_cost_assumptions.json
│   ├── microsoft_price_events.json
│   ├── microsoft_price_changes.json
│   ├── microsoft_inventory_mapping.json
│   ├── microsoft_price_forecast_rules.json
│   ├── m365_best_practice_catalog.json
│   ├── license_audit_checklist.json
│   ├── license_audit_deeplinks.json
│   ├── audit_pdf_template.json
│   ├── sharepoint_storage_rules.json
│   ├── onedrive_quota_presets.json
│   ├── exchange_storage_rules.json
│   ├── storage_growth_assumptions.json
│   ├── microsoft_backup_baseline.json
│   ├── backup_providers.json
│   ├── backup_comparison_rules.json
│   ├── google_workspace_plans.json
│   ├── m365_target_plans.json
│   ├── workspace_to_m365_mapping.json
│   ├── migration_defaults.json
│   ├── power_platform_products.json
│   ├── power_platform_use_cases.json
│   ├── power_platform_connector_rules.json
│   ├── power_platform_capacity_catalog.json
│   ├── power_platform_governance_rules.json
│   ├── commitment_pricing.json
│   ├── commitment_assumptions.json
│   ├── commitment_channel_notes.json
│   ├── addon_configurator_addons.json
│   ├── addon_overlap_rules.json
│   └── consumption_modules.json
├── templates/
│   ├── landing.php
│   ├── page-readonly-suite-matrix.php
│   ├── page-readonly-addon-matrix.php
│   ├── page-ai-product-comparison.php
│   ├── page-copilot-pilot-calculator.php
│   ├── page-frontline-worker-check.php
│   ├── page-exchange-online-roi.php
│   ├── page-teams-phone-advisor.php
│   ├── page-microsoft-price-tracker.php
│   ├── page-license-audit-checklist.php
│   ├── page-storage-needs-calculator.php
│   ├── page-backup-cost-calculator.php
│   ├── page-workspace-m365-tco-calculator.php
│   ├── page-power-platform-cost-calculator.php
│   ├── page-archive-mailbox-calculator.php
│   ├── page-commitment-calculator.php
│   ├── page-addon-configurator.php
│   ├── page-license-comparison.php
│   ├── page-license-advisor.php
│   ├── page-shared-mailbox.php
│   ├── page-copilot-license-check.php
│   └── page-copilot-roi.php
└── assets/
    ├── css/
    │   ├── plugin-base.css
    │   ├── style.css
    │   └── m365calculator-public.css
    └── js/
        ├── m365tools-landing.js
        └── m365calculator-public.js
```

## Landingpage-Registry

Module melden sich über `CMS_M365CALCULATOR_Tool_Registry::register()` mit `key`, `title`, `description`, `icon`, `url`, `category` und `status` an. Die Landingpage gruppiert automatisch nach Kategorie, sortiert `live` vor `beta` vor `soon`, zeigt Kennzahlen, eine Kategorie-Schnellnavigation und je Modul die Schwerpunkte sowie ab `1.26.0` konkrete aktuelle Prüfpunkte aus `m365_best_practice_catalog.json`. Der Adminbereich kann je Modul Sichtbarkeit, Status, Priorität, Titel und Beschreibung überschreiben; ab `1.27.0` werden die Modul-Unterpunkte im Adminmenü zusätzlich fachlich nach Kategorie und Priorität sortiert.

## Admin-Modulsettings

Ab `1.25.0` erhält jedes registrierte Modul genau einen eigenen Unterpunkt unter `M365 Tools`. Die Modul-Unterseite nutzt Tabs für:

- `Übersicht` – Registry-Metadaten, Route, Status und Schnellaktionen
- `Anzeige` – Sichtbarkeit, Status, Sortierung, Titel und Beschreibung
- `Public-Design` – optionale modulbezogene Farben, Header-/Buttonflächen, Abschnittsabstand und maximal 2px Rundung
- `Preise & Annahmen` – modulbezogene Preisaufschläge, Rabatte, Puffer und fachliche Zusatzwerte
- `Workflow` – Owner, Review-Intervall, Freigabemodus, Quellen-/Exportverhalten und modulnahe Review-Regeln
- `Daten & Regeln` – Quellenstand, Annahmenstatus, manuelle Annahmen und interne Änderungsvermerke

Design-, Preis-, Workflow- und Datenoptionen werden in `cms_m365tools_module_options` gespeichert. Die Felddefinitionen kommen zentral aus `CMS_M365CALCULATOR_Admin_Module_Config`, damit neue Module automatisch eine konsistente Einstellungsseite erhalten. Ab `1.26.0` enthält der Tab `Daten & Regeln` zusätzlich Quellenprofil, Endpoint-/Netzwerkpfad-Review, Schutz-/Datenzugriffs-Review sowie Servicegrenzen-/Kapazitäts-Review; Copilot- und Power-Platform-Module erhalten passende Zusatzschwellen. Ab `1.29.13` schreibt der Tab `Public-Design` routegenaue CSS-Variablen, sodass einzelne Public-Modulseiten vom globalen Landingpage-Design abweichen können, ohne Templates zu duplizieren. Ab `1.29.14` wird diese Route-zu-Modul-Erkennung zuerst aus der Registry abgeleitet, damit neue Module automatisch Design-Overrides erhalten.

Ab `1.27.0` stehen die globalen Adminbereiche vor den Modulseiten: `Zentrale Einstellungen`, `Paketpreise` sowie `Abopreise & Laufzeiten`. Das Admin-Menü sortiert Module anschließend nach Fachkategorie und Priorität, damit Lizenz-, Copilot-, Exchange-, Teams-, Speicher-, Power-Platform- und Migrationsseiten leichter auffindbar sind. Globale Werte werden in derselben Optionslogik gespeichert und dienen als zentrale Defaults für alle Module.

Ab `3.0.20` zeigt die Admin-Modulübersicht je sichtbar geschaltetem Tool ein kompaktes Public-Site-Icon. Wird ein Modul unsichtbar geschaltet, verschwinden Public-Öffnen-Buttons für dieses Modul aus öffentlichen und sichtbarkeitsgebundenen Kontexten sowie aus detailseitigen Querverlinkungen auf anderen Tool-Publicsites. Ab `3.0.21` bleibt die eigentliche URL-Spalte in der Admin-Modulübersicht dennoch immer anklickbar, damit Admins auch unsichtbare Tool-Publicsites direkt öffnen und prüfen können.

Ab `1.28.0` rendert `Paketpreise` die Paketbasis aus `cms-m365lic`: Basislizenzen und Add-ons erhalten je SKU eigene Felder für Public-, Member- und Spezialpreise. `pricing()` und `commitment_pricing()` übernehmen diese Werte als Standard für Rechner und Laufzeitmodelle; die Unterseite `Abopreise & Laufzeiten` pflegt dazu die Faktoren für Jahr/jährlich, Jahr/monatlich und Monat/flexibel. Die Admin-Modulunterpunkte verwenden kurze Labels, damit die Sidebar auch mit 21 Modulen lesbar bleibt. Ab `3.0.22` liefert `data/package_price_catalog.json` zusätzlich einen lokalen Standardkatalog mit 23 Basisplänen und 51 Add-ons/Spezial-SKUs. Der Katalog wird mit `cms-m365lic`, Legacy-`pricing.json` und vorhandenen DB-Overrides gemerged, sodass bereits gespeicherte Paketpreise auch dann in der Admin-Ansicht sichtbar bleiben, wenn kein Seed aus `cms-m365lic` vorhanden ist. Ab `3.0.23` ist derselbe Katalog die kanonische Preiszeitreihenquelle für Admin-Paketpreise und den Microsoft-Preiserhöhung-Tracker: Mai-2026-Baseline, historische Anker, Teams-/No-Teams-Varianten, Consumer/Perpetual/Education/NonProfit-SKUs und bestätigte Juli-2026-Future-Rows werden strukturiert pro SKU geführt. Ab `3.0.24` enthält der Katalog zusätzlich separate Promo-Blöcke außerhalb der Zeitreihe, getrennte Copilot-Business-/Enterprise-SKUs, Copilot Chat, Copilot-Studio-Commit-Hinweise, Business+Copilot-Bundles, Consumer Premium, Office-365-Education-A-SKUs und weitere historische Anker; Promo-Werte dürfen nicht in Tracker-Graphen oder Baseline-Preisvergleiche einfließen.

Ab `1.29.0` nutzt der Plugin-Adminbereich die volle Admincontent-Breite mit 25px Abstand an allen Seiten. Der Tab `Dienstleister & Kontakt` pflegt Anbietername, CTA-Texte, Kontaktformular-URL, Profil-Link, E-Mail, Telefon und Darstellung. Toolseiten geben diesen Hinweis zentral vor dem Footer aus.

Ab `1.29.1` ist der `Landingpage Designer` ein eigener Untermenüpunkt. Er steuert Overline, Titel, Intro, Review-Texte, Seitenbreite, Contentheader-Varianten, Kategorie-Navigation, Modulbox-Layouts, Box-Stil, Dichte, Kartenbreite, Rundungen, Abschnittsabstände, Farbpalette, Kennzahlen, Best-Practice-Kompass, Icons, Beschreibungen, Statuslabels, Modul-Chips, Prüfpunkte und Button-Beschriftung. Ab `1.29.12` ist die Rundung für Boxen und Buttons bewusst auf maximal 2px begrenzt; der Content-Hintergrund wirkt auf Landingpage und Modul-Publicseiten.

Ab `1.29.2` kann der Designer zusätzlich Header-Overline, Header-Titel, Header-Intro, Header-Buttons, einzelne Kennzahlen, Kategorie-Overline, Kategorie-Zähler, Review-Beschreibungen, Modultitel-Links, Tool-Buttons und Hinweise für inaktive Module separat ausblenden. Primär- und Sekundärbutton im Contentheader erhalten eigene Texte und Ziele. Modulbox-Buttons können zur jeweiligen Toolseite, zu einem Header-Button-Ziel oder zu einem eigenen globalen Ziel führen. Für den Contentheader stehen eigene Stile, Ausrichtungen, Button-Layouts sowie Header- und Button-Farben bereit.

Ab `3.0.4` sind `M365 Lizenzmatrix` und `M365 Add-on-Matrix` vollständig in das Plugin `cms-m365matrices` ausgelagert. Dort liegen Adminbereich, Public-Routen, Templates, Assets und JSON-Kataloge; die gemeinsamen Optionswerte bleiben in den bestehenden M365-Tools-Tabellen erhalten.

Ab `1.29.5` besitzt die Hub-Landingpage eine sticky Live-Suche mit Kategorie-Chips, eine sticky Kategorie-Sidebar mit aktiver 3px-Navy-Kante, mobile horizontale Filter-Chips, Kategorie-Heros mit SVG-Icon und Kurzbeschreibung, Tabler-Icons im Best-Practice-Kompass, `Beliebt`-/`Neu`-Badges, klickbare Toolcards, Hero-Direkteinstieg mit Suchfokus, Kategorie-Hash, Slash-Suchshortcut und Back-to-top. Die Interaktionen laufen in `assets/js/m365tools-landing.js` ohne externe Bibliotheken. Ab `1.29.11` werden die Landingpage-Farb- und Layoutwerte zusätzlich als Public-Design-Tokens ausgegeben und von allen Modul-Publicseiten genutzt. Ab `1.29.13` können diese Tokens je Modul überschrieben werden; JSON-Kataloge und Optionswerte werden dabei pro Request gecacht. Ab `1.29.14` überschreibt kein systemseitiger Dark Mode mehr die im Adminbereich gepflegten Farben.

## All-Module-Best-Practice-Kompass

Der zentrale Katalog `m365_best_practice_catalog.json` ordnet alle Public-Module querschnittlichen Review-Domänen zu:

- Lizenz & Kosten
- Identität & Zugriff
- Schutz & Compliance
- Servicegrenzen
- Speicher & Backup
- Netzwerk & Performance
- Copilot & KI
- Power Platform Betrieb
- Migration & Betrieb

Die Hub-Landingpage rendert daraus eine kompakte Übersicht, Modul-Fokuschips und konkrete Prüfpunkte je Tool. Die Inhalte basieren auf offiziellen Microsoft-Learn-Quellen zu Usage Reports, Lizenzzuweisung, Gruppenlizenzierung, M365-Endpoint-Webservice, Endpoint-Change-Management, Network Connectivity Principles, Network Planning and Performance, Entra Conditional Access Planning, Defender for Office 365 Deployment, Exchange-/SharePoint-/Teams-Grenzen, Copilot Licensing/Requirements/Setup, Microsoft 365 Backup sowie Power Platform Well-Architected, Request Limits und Dataverse Capacity.

## Design

Das Frontend nutzt die PHINIT-Plugin-Komponenten (`phinit-plugin`, `phinit-card`, `phinit-btn`, `phinit-field`, `phinit-table`, `phinit-note`, `phinit-result`) und ergänzt nur schlanke Layout-Klassen mit den Präfixen `m365calc-*` und `m365tools-*`. Ab `1.24.0` ist die Public-Oberfläche bewusst redaktioneller aufgebaut: keine Verlaufsflächen, keine Glassmorphism-Effekte, keine Icon-Kacheln in Signalfarben, reduzierte Schriftgewichte, dezente Statuskanten und bessere Scanbarkeit auf Landingpage, Formularen, Ergebnisbereichen, Tabellen, Auditlisten und Charts. Ab `1.29.5` sind Card- und Kompass-Beschreibungen mindestens 14px groß, Lauftexte großzügiger gesetzt, Metadaten kontrastreicher, Cards erhalten klare Hover-/Fokuszustände und die Landingpage nutzt 3/2/1-Spalten-Breakpoints plus Touch-Ziele ab 44px. Ab `1.29.12` gibt es keinen sichtbaren äußeren Abstand oder Saum zwischen Theme-Header, Theme-Wrappern und Plugin-Content; gewünschte Luft entsteht ausschließlich innerhalb der Plugin-Struktur mit 25px oberem Innenabstand. M365TOOLS markiert dafür generisch Zwischenknoten vor dem eigenen Content als `m365tools-header-interstitial`, ohne spezifische Abhängigkeit auf ein anderes Plugin. Die Modul-Publicseiten nutzen dieselben Hintergrund-, Surface-, Header-, Button- und Radius-Variablen wie die Landingpage; Boxen und Buttons bleiben maximal 2px gerundet. Ab `1.29.13` kann ein Modul diese Variablen über seinen Admin-Tab `Public-Design` routegenau überschreiben. Ab `1.29.14` liegt der statische Header-/Wrapper-Reset in `plugin-base.css`, sodass der Head nur noch dynamische Design-Tokens ausgibt.

Ab `3.0.20` kann das Button-Layout der Tool-Publicsites außerhalb der Hub-Übersicht zentral unter `Zentrale Einstellungen > Allgemein` angepasst werden. Unterstützt werden Standard/inline, gestapelt, rechtsbündig und vollbreit; die Hub-Toolübersicht behält ihre eigenen Landingpage-Designer-Buttonregeln. Ab `3.0.25` ergänzt der gemeinsame Tool-Header automatisch die dezenten Sekundäraktionen `Alle Tools anzeigen` (`/m365-tools`) und `Kontaktanfrage` (globale Provider-Kontaktformular-URL, Fallback `/kontakt`), ohne dass einzelne Tool-Templates eigene Header-Buttons pflegen müssen. Ab `3.0.29` entfernt der Frontend-Controller alte lokale Hero-Aktionsnavs der Toolseiten vor der Injection, damit nicht zusätzlich `Toolbox anzeigen`, `Lizenzberater öffnen` oder ähnliche Template-Buttons doppelt erscheinen.

## Fachliche Logik

Die reinen Lizenz- und Add-on-Matrizen sind nicht mehr Bestandteil von `cms-m365tools`. Sie werden vom Plugin `cms-m365matrices` bereitgestellt und nutzen weiterhin dieselben gemeinsamen Optionsgruppen `matrix-suite`, `matrix-addon` und `matrix-design` in den bestehenden M365-Tools-Optionstabellen.

Die Lizenz-Audit-Checkliste bewertet und strukturiert unter anderem:

- aktiven Nutzerbestand, Nutzungsstandort, ehemalige Nutzer und hybride Identitätsquellen
- direkte und gruppenbasierte Lizenzzuweisungen, Fehlerlisten, verschachtelte Gruppen, große Lizenzgruppen und Renewal-Fenster
- Shared Mailboxes bis 50 GB, Archiv-/Hold-/Premiumfälle, Konvertierung mit Ankerkonto und Inactive-Mailbox-Pfade
- OneDrive-Zugriff und Aufbewahrungsfristen bei ehemaligen Nutzern
- Frontline-Kandidaten, Copilot-Basislizenzen, primäres Exchange-Online-Postfach und App-/Netzwerk-Readiness
- Add-on-Redundanzen, SharePoint-Tenant-Speicher, Site-Limits, Extra File Storage, Microsoft 365 Backup und Laufzeitmodell
- privilegierte Rollen, Conditional Access, Mail-Schutz, Domain-Authentizität und Microsoft-365-Nutzungsberichte
- M365-Endpoints, Netzwerkpfad, Performance-Basiswerte, SharePoint-/OneDrive-/Teams-Grenzen, Copilot-Datenzugriff, Copilot-Setup, Power-Platform-Requests, Dataverse-Kapazität und Wiederherstellungsziele
- lokalen Browser-Fortschritt, offene/erledigte Punkte, Druck-/PDF-Zusammenfassung und Deep Links zu passenden Spezialrechnern

Der Google Workspace ↔ Microsoft 365 TCO-Rechner bewertet unter anderem:

- laufende Lizenzkosten für Google Workspace und Microsoft 365 über 12 bis 60 Monate
- Mapping von Business Starter, Standard, Plus und Enterprise auf passende Microsoft-365-Zielpläne
- Rückwärtsvergleich von Microsoft 365 zu Google Workspace
- Migrationskosten, Schulung, Change-Aufwand, Hypercare und Parallelbetrieb als getrennte Kostenblöcke
- Break-even, Delta, monatliche Kosten und kumulierte Kostenentwicklung
- Business-Plan-Grenzen bei mehr als 300 Nutzern und Enterprise-Sonderfälle
- native Microsoft-Migrationsleitplanken für Mail, Calendar, Contacts und Rules

Der Power Platform Kosten-Kalkulator bewertet unter anderem:

- enthaltene Microsoft-365-/Teams-Rechte gegenüber Standalone-Premium-, PAYG- und Capacity-Pfaden
- Power Apps per app, Power Apps Premium, Power Automate Premium, Process, Hosted RPA und Power Pages Kapazitätsmodelle
- Premium-, Custom- und On-Premises-Connectoren als harte Lizenztreiber
- Dataverse for Teams inklusive 2-GB-/Teams-Kontext, Upgrade-Pfad, AI- und Desktop-Flow-Grenzen
- Copilot Studio, AI Builder, Copilot Credits, Requests, Dataverse Storage und Process Mining als eigene Kostenarten
- Governance-Treiber wie Managed Environments, CMK, Customer Lockbox, vNet und Architektur-Review
- Microsoft Well-Architected-Leitplanken für Security Baseline, Identitätssteuerung, Zugangsdaten, Datenrichtlinien, Managed Environments, ALM, Operations und Performance
- Best-Practice-Score mit Prüfpunkten zu Umgebungsstrategie, Datenmodell, Monitoring, Deployment und produktionsnahen Performance-Zielen
- Empfehlungskategorien für seeded ausreichend, günstiger Einstieg, Per-User-Premium, Capacity-Modell oder Architekturprüfung

Der M365 Storage-Bedarfs-Rechner bewertet unter anderem:

- SharePoint-Tenant-Pool nach Microsoft-Formel 1 TB plus 10 GB je qualifizierter Lizenz
- größten Site-Bedarf gegen die 25-TB-Site-Grenze und tenantweiten Pool getrennt
- OneDrive-Bedarf je Nutzer, konfigurierte Tenant-Quota und 1-TB-/5-TB-Planungsgrenzen
- Exchange-Primärpostfächer mit 50-GB- oder 100-GB-Modell sowie größte Mailbox im Forecast
- Exchange-Archiv mit 50 GB, 100 GB oder Auto-expanding bis 1,5 TB
- Wachstum über 12, 24 oder 36 Monate, Planungspuffer, Cleanup-Potenzial und Zusatzspeicherpreisannahme
- Sync-Performance-Richtwert von 300.000 Elementen, 1.000.000-Elemente-Preview und 250-GB-Einzeldateigrenze
- Empfehlungskategorien für ausreichend dimensioniert, beobachten, Zusatzspeicher/Archivstrategie, akutes Risiko oder Governance zuerst

Der M365 Backup-Kosten-Rechner bewertet unter anderem:

- offizielle Microsoft-365-Backup-Baseline mit 0,15 USD pro geschütztem GB und Monat
- geschützte Datenmenge aus Exchange, OneDrive, SharePoint, optionalen Teams-Dateien und gelöschten oder versionierten Daten
- Schutzanteil, 12-Monats-Wachstum und gewünschte Aufbewahrung
- Providervergleich mit manuell gepflegten Vergleichswerten für Veeam, AvePoint, Backupify/Datto und Afi.ai
- Ranking nach Kosten, Workload-Abdeckung, Retention, Restore-Tiefe, Trust Boundary und Betriebsmodell
- Empfehlungskategorien für Microsoft ausreichend, Partnerprüfung, Drittanbieter, Hybrid-Modell oder unvollständige Vergleichsdaten
- FAQ und Quellenstand aus Microsoft Learn zu Backup Overview, Pricing, Billing, FAQ, Privacy/Compliance und Graph Backup Storage

Der Microsoft-Preiserhöhung-Tracker bewertet unter anderem:

- offizielle Microsoft Licensing News Ereignisse für Pricing, Packaging, SKU-Split, End-of-sale, Retirement, Verbrauchsabrechnung und Pricing Consistency
- getrennte Datumslogik für Veröffentlichung, Wirksamkeit, Packaging-Rollout und Renewal-Wirkung
- Microsoft 365 Commercial Suites Pricing and Packaging Updates 2026 inklusive Bestandskundenlogik bis zum Renewal
- Teams-/No-Teams-Strukturereignisse 2024 und 2025 inklusive Teams Enterprise / EEA Preiszeile
- Power Apps per app End-of-sale, Power BI Premium P-SKU Retirement und Fabric-Nutzungsabrechnung als nicht reine Preiserhöhungsereignisse
- bis zu drei Bestands-SKUs mit Menge, aktuellem Monatspreis, Jahresbetrag, Monatsdelta und Jahresdelta
- visuellen Jahresvergleich von historischem Betrag zu aktuellem Stand, Renewal-Zielwert und optionalem Forecast
- klare Trennung zwischen offiziellen Microsoft-Ereignissen und Planungsschätzung für spätere Budgetrunden

Der Teams Phone-Lizenz-Berater bewertet unter anderem:

- Teams Phone als PBX-Funktion getrennt von PSTN-Konnektivität, Rufnummern und Carrier-Modell
- Basislizenzen wie Business Basic/Standard/Premium, Enterprise-Suiten mit oder ohne Teams, E5-Kontexte, Frontline F1/F3 und Teams Standalone
- PSTN-Modelle Calling Plan, Operator Connect, Direct Routing, Mischmodell und Architektur-Review
- Länder- und Verfügbarkeitsannahmen für Calling Plans, Audio Conferencing, Communication Credits, Operator Connect und Direct Routing
- Direct-Routing-Voraussetzungen wie zertifizierter SBC, öffentlicher DNS, öffentliche IP, registrierte Domäne, Zertifikat und TeamsOnly-Planung
- Shared Calling für Low-Volume-Nutzer ohne persönliche Durchwahl über Ressourcenkonto und Auto Attendant
- Audio Conferencing, Communication Credits, Frontline-Pfade, Budgetrahmen, Umsetzungsrisiken und nächste Schritte

Der On-Premise Exchange zu Exchange Online ROI-Rechner bewertet unter anderem:

- Vollkosten für lokalen Exchange-Betrieb aus Hardware, Exchange-/Windows-Lizenzen, Storage, Backup, Strom, Hosting, Wartung, Administration, HA/DR, Monitoring und Zertifikaten
- Hardware-Refresh-Druck und CAPEX-vs.-OPEX-Effekt über 36 und 60 Monate
- Exchange-Online-Zielkosten für Exchange Online Plan 1, Plan 2, Business Premium, Microsoft 365 E3 und Microsoft 365 E5
- optionale Archiv-, Compliance-, Hybrid-, Koexistenz- und Migrationskosten
- Break-even-Monat, 3-/5-Jahres-Delta, monatliche Kosten pro Mailbox und Management-Fazit
- Migrationspfade wie Cutover, Staged, Hybrid und Dritttool-/Partner-Migration inklusive Größen- und Komplexitätshinweisen
- Planungsrisiken durch Queue-Zeiten, Netzwerk, Quellsystem-Performance, große Mailboxen, Public Folder, Legacy-Apps und Datenqualität

Der Frontline Worker Lizenz-Eignung-Check bewertet unter anderem:

- Rollenprofile wie Retail, Store Management, Produktion, Logistik, Healthcare, Field Service, Service Counter und Information Worker
- Gerätemodelle wie Shared Device, BYOD, Firmen-Smartphone, dediziertes Gerät und Kiosk-Terminal
- typische Frontline-Signale wie mobile/deskless Arbeit, Schichtbetrieb, Kunden-/Service-/Produktionsnähe und einfache Aufgabenübergabe
- App-Bedarf für Teams, Shifts, Tasks, Mail, SharePoint, OneDrive, Viva, Power Apps, Power Automate und Desktop-Apps
- Identitäts-, Geräteverwaltungs- und Zugriffskontrollplanung für Shared-Device- und BYOD-Szenarien
- Empfehlungskategorien F1 geeignet, F3 geeignet, Mischmodell sinnvoll, Enterprise-Lizenz weiter nötig oder manuelle Detailprüfung
- Kostenpotenzial gegenüber Business Premium, E3 oder E5 auf Basis der hinterlegten Preisannahmen

Der Copilot Pilot-Phase-Rechner bewertet unter anderem:

- Organisationsgröße, Knowledge-Worker-Anteil, Fachbereiche, Budgetrahmen und Copilot-Preisannahme
- Pilotziele wie Produktivität, Use-Case-Findung, Governance, Management-Buy-in und ROI-Validierung
- Readiness-Faktoren für Lizenzbasis, Apps, Netzwerk, Mailboxen, OneDrive, Teams, Datenhygiene, Champions, Feedback und Reporting
- Oversharing-Risiko, Governance-Bedarf, empfohlene Pilotgröße, Laufzeit, Champion-Anzahl und Budgetlücke
- Pilotstufen von Micro-Pilot über Abteilungs- und funktionsübergreifende Piloten bis Enterprise-Waves
- Rollout-Timeline, Messkriterien, nächste Schritte und Quellenstand aus Microsoft Learn und Adoption Hub

Der AI Pack vs. Copilot Pro Vergleich bewertet unter anderem:

- Copilot Chat, Microsoft 365 Copilot, Microsoft Copilot Consumer, Security Copilot, GitHub Copilot und Copilot Studio
- dynamische Angebotslabels wie AI Pack, Copilot Pro, Sales-/Service-Bundles, Agent-Angebote und Security-Angebote
- Rolle, Hauptdatenquelle, Zielbild, Datenklasse, Nutzerzahl, Governance-Wunsch und Agent-Bedarf
- klare Produktempfehlung, Alternativen, Vergleichstabelle, Einordnungswarnungen und nächste Schritte
- JSON-getriebene Katalogpflege für volatile Microsoft-Produktnamen und Angebotsmodelle

Der Archive Mailbox Rechner bewertet unter anderem:

- Primärmailbox-Kapazität, Archiv-Kapazität und 12-Monats-Wachstumsprojektion
- Auto-expanding Archive bis 1,5 TB, Add-on-/Upgrade-Pfade und Provisioning-Hinweise
- Shared-Mailbox- und Resource-Mailbox-Sonderfälle ab 50 GB bzw. bei Archiv, Hold oder Compliance
- Litigation Hold, In-Place Hold, Purview-Premium-Hinweise und tägliches Archivwachstum über 1 GB
- geschätzte Zusatzkosten für Exchange Online Archiving, falls der Basisplan Auto-expanding nicht enthält
- sharebare Anfrageparameter für reproduzierbare Archivbewertungen

Der Annual vs. Monthly Commitment Rechner bewertet unter anderem:

- Monatslaufzeit, Jahreslaufzeit mit monatlicher Abrechnung und Jahreslaufzeit mit jährlicher Abrechnung
- Split-Strategie aus stabilem Jahreskern und flexiblen monatlichen Nutzern
- Volatilität, Nutzerwachstum oder -rückgang, Betrachtungszeitraum und Beschaffungskanal
- Overcommitment in Seat-Monaten, durchschnittliche Monatskosten, Jahresäquivalent und erste Rechnung
- sharebare Anfrageparameter für wiederholbare Laufzeitvergleiche

Der M365 Add-On-Konfigurator bewertet unter anderem:

- Basislizenz, Nutzerzahl, Laufzeitmodell, Segment und Wunsch-Add-ons
- Add-on-Prerequisites für Copilot, Teams Phone, Calling Plans, Security, Identity und Power Platform
- Redundanzen, wenn Funktionen in der Basislizenz bereits enthalten sind
- Upgrade-vs-Add-on-Empfehlungen für Business Premium, Office 365 E5, Microsoft 365 E5 und Exchange Online Plan 2
- Resource-Account-SKUs als no-cost Spezialfall statt normaler Enduser-Lizenz
- Microsoft 365 Backup und Pay-as-you-go-Telefonie als Verbrauchs-/Spezialblöcke außerhalb klassischer User-SKU-Summen
- sharebare Anfrageparameter für reproduzierbare Add-on-Konfigurationen

Der M365-Lizenzberater bewertet unter anderem:

- bis zu fünf Nutzergruppen mit Persona-Presets und Zusatzanforderungen
- Basislizenzen aus Business-, Enterprise-, Frontline-, Exchange- und App-Plänen
- Add-ons für Copilot, Teams Phone, Power Platform, Intune, Entra, Defender, Archivierung und Speicher
- Business-300-Grenzen, Copilot-Basislizenzfähigkeit, Resource-Account-Sonderfälle und SharePoint-Speicher
- monatliche, jährliche und 36-Monats-Kostenannahmen auf Basis migrierter `cms-m365lic`-Preisdaten

Der M365-Lizenzvergleich bewertet unter anderem:

- Desktop Apps, Office Web Apps, Shared Computer Activation und Mobile-/Web-Pfade
- Exchange Online, Archivierung, SharePoint, OneDrive und Tenant-Speicherlogik
- Teams, Audio Conferencing, Teams Phone, PSTN und Calling-Plan-Hinweise
- Security-, Intune-, Entra-, Defender-, Purview- und Windows-Rechte
- Power Apps/Power Automate seeded Rechte, Premium Connectoren, Dataverse und Power BI
- Copilot Chat, Microsoft 365 Copilot Add-on-Fähigkeit und E5-/Security-Copilot-Pfade
- Feature-Status `enthalten`, `teilweise`, `Add-on nötig`, `Voraussetzung prüfen` und `nicht enthalten`

Der Copilot ROI-Rechner bewertet unter anderem:

- Lizenz- und Readiness-Gate vor jeder Wirtschaftlichkeitsbewertung
- monatlicher Produktivitätswert, Jahres-Netto-ROI und Payback
- Break-even-Minuten pro Tag je adoptiertem Nutzer
- konservative, realistische und optimistische Szenarien
- 12-Monats-Chart für kumulierte Kosten, Produktivitätsgewinn und Netto-Zone
- Empfehlung für Pilot, breiten Rollout oder Readiness-first

Der Shared-Mailbox-Rechner bewertet unter anderem:

- Zweck und Login-Anforderung
- interne/externe Nutzung
- aktuelle und erwartete Mailboxgröße
- Archiv, Hold, Retention/Purview und Defender
- Automapping, Hidden GAL, Hybrid und Konvertierung
- geschätztes Einsparpotenzial gegenüber regulären Benutzer-Mailboxen

Der Copilot Lizenz-Pflicht-Checker bewertet unter anderem:

- Tenant-Segment Commercial, Government oder Education
- aktuelle Basislizenz und Copilot-Chat-Verfügbarkeit
- technische Mindestvoraussetzungen wie Entra ID und primäres Exchange-Online-Postfach
- App-, OneDrive-, Teams-, Privacy- und Netzwerk-Readiness
- Upgrade-Pfade und Mehrkostenannahmen für Copilot-fähige Ausgangsbasis

## Nächste Module

Die Tool-Registry ist vorbereitet, damit weitere Kosten-, Governance- oder Lizenzberater-Module ergänzt werden können.
