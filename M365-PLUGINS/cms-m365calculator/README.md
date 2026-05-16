# CMS M365 Calculator

`cms-m365calculator` ist eine modulare Microsoft-365-Rechner-Toolbox für 365CMS. Enthalten sind der **Frontline Worker Lizenz-Eignung-Check**, der **Copilot Pilot-Phase-Rechner**, der **AI Pack vs. Copilot Pro Vergleich**, der **Archive Mailbox Rechner**, die **M365 Lizenzmatrix**, die **M365 Add-on-Matrix**, der **Annual vs. Monthly Commitment Rechner**, der **M365 Add-On-Konfigurator**, der **M365-Lizenzvergleich**, der **M365-Lizenz-Berater**, der **Copilot ROI-Rechner**, der **Shared-Mailbox vs. Lizenz-Rechner** und der **Copilot Lizenz-Pflicht-Checker**.

## Enthaltene Routen

- `/m365-tools` – Hub-Übersicht aller Module
- `/m365-rechner` – alternative Hub-Route
- `/frontline-worker-lizenz-check` – F1-/F3-Eignung, Mischmodell, Enterprise-Bedarf und Sparpotenzial für Frontline Worker prüfen
- `/copilot-pilot-rechner` – Pilotgröße, Dauer, Budget, Champions, Readiness und Rollout-Zeitplan für Microsoft 365 Copilot berechnen
- `/ai-pack-vs-copilot-pro` – AI Pack, Copilot Pro, Copilot Chat, Microsoft 365 Copilot, Spezial-Copilots und Copilot Studio nach Use Case vergleichen
- `/m365-lizenzmatrix` – Gesamtübersicht der Microsoft-365-Vollpakete ohne Filter oder Formular
- `/m365-addon-matrix` – Gesamtübersicht aller Add-on-Bereiche mit Paketen nebeneinander
- `/m365-archive-mailbox-rechner` – Archive Mailbox Rechner für Archivgröße, Auto-expanding Archive, Shared-/Resource-Mailboxen und Hold-/Purview-Hinweise
- `/m365-jahresvertrag-vs-monatsvertrag` – Annual vs. Monthly Commitment Rechner für Monatslaufzeit, Jahresbindung und Split-Strategien
- `/m365-add-on-konfigurator` – Add-ons, Voraussetzungen, Redundanzen, Upgrades und Verbrauchsprodukte prüfen
- `/m365-lizenzvergleich` – filterbare Lizenz-Vergleichstabelle mit Desktop-Apps, Zusatzdiensten und Feature-Status
- `/m365-lizenzberater` – M365-Lizenz-Berater mit Gruppen-, Add-on- und Kostenmodell
- `/copilot-roi-rechner` – Copilot Business-Case-, Break-even- und Pilot-/Rollout-Rechner
- `/shared-mailbox-vs-lizenz` – Shared-Mailbox-Entscheidungs- und Kostenrechner
- `/copilot-lizenz-check` – Copilot-Basislizenz-, Chat- und Technik-Readiness-Check

## Modulstruktur

```text
cms-m365calculator/
├── cms-m365calculator.php
├── includes/
│   ├── class-catalog.php
│   ├── class-tool-registry.php
│   ├── class-frontend.php
│   ├── class-installer.php
│   ├── class-settings.php
│   ├── class-readonly-matrices.php
│   ├── class-commitment-calculator.php
│   ├── class-archive-mailbox-calculator.php
│   ├── class-ai-product-comparison.php
│   ├── class-copilot-pilot-calculator.php
│   ├── class-frontline-worker-check.php
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
│   ├── readonly_suite_matrix.json
│   ├── readonly_addon_matrix.json
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
```

## Landingpage-Registry

Module melden sich über `CMS_M365CALCULATOR_Tool_Registry::register()` mit `key`, `title`, `description`, `icon`, `url`, `category` und `status` an. Die Landingpage gruppiert automatisch nach Kategorie und sortiert `live` vor `beta` vor `soon`. Der Adminbereich kann je Modul Sichtbarkeit, Status, Priorität, Titel und Beschreibung überschreiben.

## Design

Das Frontend nutzt die PHINIT-Plugin-Komponenten (`phinit-plugin`, `phinit-card`, `phinit-btn`, `phinit-field`, `phinit-table`, `phinit-note`, `phinit-result`) und ergänzt nur schlanke Layout-Klassen mit dem Präfix `m365calc-*`. Öffentliche Pluginseiten setzen einen Plugin-eigenen Abstand von 25px zum Theme-Header.

## Fachliche Logik

Die Lizenz- und Add-on-Matrizen liefern reine Public-Vergleichsseiten ohne Interaktion:

- `/m365-lizenzmatrix` stellt Microsoft-365-Vollpakete als Gesamtübersicht dar: Business Basic, Business Standard, Business Premium, Microsoft 365 E3 und Microsoft 365 E5.
- `/m365-lizenzmatrix` enthält konkrete Planungswerte zu Mailboxgröße, Archiv, Shared-/Resource-Mailboxen, SharePoint-Tenant-Speicher, Site-/Dateilimits, OneDrive, Intune, Entra ID, Defender und Purview.
- `/m365-addon-matrix` gruppiert Add-ons nach Bereichen wie Exchange, SharePoint/OneDrive, Teams/Telefonie, Copilot, Intune, Entra ID, Defender, Purview und Power Platform.
- Beide Seiten nutzen dieselbe Matrix-Optik wie der Lizenzvergleich und bleiben als reine Referenzseiten besonders schnell erfassbar.

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
