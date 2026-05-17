# Lizenz-Audit-Checkliste

## Überblick

Die **Lizenz-Audit-Checkliste** ist ein Public-Modul von `cms-m365tools` für ein strukturiertes Microsoft-365-Lizenzaudit. Das Modul führt IT, Einkauf oder Management durch die wichtigsten Prüffelder: Identitäten, Lizenzzuweisungen, ehemalige Nutzer, Mailbox- und Aufbewahrungspfade, Copilot, Frontline Worker, Storage, Backup und Vertrags-/Renewal-Themen.

- Route: `/m365-lizenz-audit-checkliste`
- Registry-Key: `license-audit-checklist`
- Engine: `CMS_M365CALCULATOR_License_Audit_Checklist`
- Template: `templates/page-license-audit-checklist.php`
- Kataloge:
  - `data/license_audit_checklist.json`
  - `data/license_audit_deeplinks.json`
  - `data/audit_pdf_template.json`

## Zielbild

Das Modul liefert eine interaktive Schritt-für-Schritt-Liste für das M365-Lizenzaudit. Der Fortschritt wird lokal im Browser gespeichert und kann über die Druckfunktion als PDF abgelegt werden. Zusätzlich schlägt das Modul passende Spezialrechner aus der M365-Toolbox vor.

## Fachliche Bereiche

| Bereich | Zweck |
|---|---|
| Identität und Nutzerbestand | Aktive Nutzer, Nutzungsstandort, ehemalige Nutzer und hybride Konten prüfen |
| Lizenzzuweisung und Fehlerprüfung | Direkte vs. gruppenbasierte Zuweisung, Fehlerlisten, verschachtelte Gruppen und Renewal-Fenster bewerten |
| Mailbox, Archiv und Aufbewahrung | Shared Mailboxes, Archiv, Hold, Inactive Mailboxes, OneDrive-Aufbewahrung und Konvertierungspfade prüfen |
| Nutzersegmentierung und Copilot | Frontline-Kandidaten, Copilot-Basislizenzen, Postfach- und App-Anforderungen bewerten |
| Speicher, Backup und Beschaffung | SharePoint-Speicher, Site-Limits, Extra File Storage, Microsoft 365 Backup und Laufzeitmodell prüfen |

## Datenmodell

### `license_audit_checklist.json`

Enthält die eigentliche Auditstruktur.

Wichtige Felder:

| Feld | Beschreibung |
|---|---|
| `meta.source_checked` | Stand der Quellenprüfung |
| `meta.sources` | Microsoft-Learn-Quellenbasis |
| `categories` | Gruppierte Auditbereiche |
| `items[].id` | Stabiler Schlüssel für Browser-Fortschritt und Zusammenfassung |
| `items[].label` | Öffentlicher Prüftitel |
| `items[].description` | Fachliche Erläuterung |
| `items[].severity` | `info`, `savings`, `risk`, `compliance` oder `critical` |
| `items[].weight` | Gewicht für Priorisierung und Auditdruck |
| `items[].finding_type` | Interner Fundtyp |
| `items[].source_url` | Primäre Microsoft-Quelle |
| `items[].deeplink_triggers` | Auslöser für Spezialtool-Empfehlungen |
| `items[].summary_if_open` | Text für offene Punkte |
| `items[].summary_if_done` | Text für erledigte Punkte |

### `license_audit_deeplinks.json`

Ordnet Auditfunde passenden Spezialrechnern zu.

Beispiele:

| Trigger | Zielroute | Zweck |
|---|---|---|
| `shared_mailbox` | `/shared-mailbox-vs-lizenz` | Shared-Mailbox-Größe, Archiv, Hold und Lizenzbedarf prüfen |
| `archive_mailbox` | `/m365-archive-mailbox-rechner` | Archiv- und Auto-expanding-Pfade bewerten |
| `copilot_license` | `/copilot-lizenz-check` | Copilot-Basislizenz und technische Readiness prüfen |
| `copilot_pilot` | `/copilot-pilot-rechner` | Pilotgröße, Budget und Readiness planen |
| `frontline_check` | `/frontline-worker-lizenz-check` | F1/F3- oder Mischmodell-Eignung bewerten |
| `addon_configurator` | `/m365-add-on-konfigurator` | Add-ons, Prerequisites, Redundanzen und Verbrauchsprodukte prüfen |
| `license_advisor` | `/m365-lizenzberater` | Basislizenzen und Mischmodelle je Nutzergruppe empfehlen |
| `license_comparison` | `/m365-lizenzvergleich` | Pläne und Feature-Abdeckung vergleichen |
| `commitment_calculator` | `/m365-jahresvertrag-vs-monatsvertrag` | Jahresbindung, Monatslaufzeit und Split-Strategie bewerten |
| `price_tracker` | `/microsoft-preiserhoehung-tracker` | Preis-, Packaging- und Renewal-Ereignisse bewerten |

### `audit_pdf_template.json`

Definiert Labels und Struktur für die druckfreundliche Zusammenfassung.

Enthaltene Abschnitte:

- Management-Zusammenfassung
- erledigte Prüfpunkte
- offene Prüfpunkte
- empfohlene Detailrechner
- Quellenstand

## Engine-Logik

Die Klasse `CMS_M365CALCULATOR_License_Audit_Checklist` bereitet die JSON-Kataloge für das Template auf.

| Methode | Zweck |
|---|---|
| `default_input()` | Liefert Standardrahmen für Mandantengröße, Prüftiefe und Schwerpunkte |
| `normalize_input(array $source)` | Normalisiert GET-Parameter und Kontext-Checkboxen |
| `tenant_size_options()` | Liefert Mandantengrößen |
| `audit_depth_options()` | Liefert Prüftiefen `basis`, `erweitert`, `tiefgehend` |
| `context_options()` | Liefert auswählbare Schwerpunkte und zugehörige Deep-Link-Trigger |
| `evaluate(array $input)` | Kombiniert Kataloge, Priorisierung, Score, Deep Links und Druckstruktur |
| `load_license_audit_checklist()` | Lädt den Auditkatalog |
| `save_license_audit_progress_local(array $input)` | Erzeugt den Browser-Speicherschlüssel aus dem aktuellen Rahmen |
| `build_license_audit_summary(array $input, array $categories, array $deeplinks)` | Ermittelt Prioritäten, Fokus, Gewichte und Trigger |
| `score_license_audit_findings(array $summary)` | Berechnet Auditdruck, Tonalität und Management-Fazit |
| `export_license_audit_pdf(array $template)` | Normalisiert die druckfreundliche Struktur |
| `render_license_audit_page()` | Liefert den Template-Pfad |

## Priorisierung

Die Priorisierung basiert auf:

- ausgewählten Schwerpunkten im Formular
- Prüftiefe
- Gewicht des Auditpunkts
- Kategorie und Deep-Link-Triggern

Prüftiefen:

| Prüftiefe | Verhalten |
|---|---|
| Basis-Audit | Priorisiert Kontexttreffer und Punkte ab höherem Gewicht |
| Erweitertes Audit | Priorisiert Kontexttreffer und mittlere bis hohe Gewichtung |
| Tiefgehendes Audit | Behandelt alle sichtbaren Punkte als relevant |

## Ergebnislogik

Das Modul erzeugt drei Ergebnisstufen:

| Ergebnis | Bedeutung |
|---|---|
| Fokussiertes Audit möglich | Kompakter Prüfpfad reicht für den Start |
| Strukturierte Prüfung sinnvoll | Mehrere relevante Prüfpfade sind aktiv |
| Hohe Audit-Priorität | Viele gewichtete Punkte sollten vor Lizenz- oder Vertragsänderungen geklärt werden |

Zusätzlich werden angezeigt:

- Fortschritt in Prozent
- erledigte Punkte
- offene Punkte
- priorisierte Punkte
- Fokuslabel
- empfohlene Detailrechner
- Quellenstand

## Frontend-Verhalten

Das Template nutzt PHINIT-Komponenten und bestehende `m365calc-*` Layoutklassen.

Wichtige UI-Bausteine:

- Hero mit Toolbox-Links
- Audit-Rahmenformular mit Mandantengröße, Prüftiefe und Schwerpunkten
- Fortschritts-KPI mit Balken
- gruppierte Checklisten-Cards
- offene und erledigte Zusammenfassung
- Deep-Link-Cards zu Spezialtools
- Druck-/PDF-Zusammenfassung
- Quellenblock

Der Checklistenfortschritt wird rein clientseitig im Browser verwaltet. Das gemeinsame Script `assets/js/m365calculator-public.js` erkennt `[data-m365calc-audit]`, lädt den gespeicherten Stand, aktualisiert Fortschritt und Zusammenfassungslisten und bietet einen Reset-Button.

## Quellenbasis

Die Auditregeln wurden auf Basis offizieller Microsoft-Learn-Seiten modelliert, insbesondere:

- Assign or unassign licenses for users in the Microsoft 365 admin center
- Assign or unassign licenses to a group in the Microsoft 365 admin center
- Remove a former employee and secure data
- OneDrive retention and deletion
- About shared mailboxes in Microsoft 365
- Convert a user mailbox to a shared mailbox
- Create and manage inactive mailboxes
- Exchange Online limits
- Microsoft 365 Copilot licensing and requirements
- SharePoint limits and storage management
- Add more SharePoint storage to your subscription
- Microsoft 365 Backup overview
- Frontline worker user types and licensing

## Pflegehinweise

Bei Änderungen an Microsoft-Lizenzregeln oder neuen Spezialrechnern sollten diese Dateien gemeinsam geprüft werden:

1. `data/license_audit_checklist.json`
2. `data/license_audit_deeplinks.json`
3. `data/audit_pdf_template.json`
4. `includes/class-license-audit-checklist.php`
5. `templates/page-license-audit-checklist.php`
6. `assets/js/m365calculator-public.js`
7. `assets/css/m365calculator-public.css`

## Validierung

Für Änderungen am Modul sollten geprüft werden:

- PHP-Syntax der Engine, Frontend-Klasse und Template-Datei
- JSON-Syntax der drei Audit-Kataloge
- Smoke-Test für `CMS_M365CALCULATOR_License_Audit_Checklist::evaluate([])`
- Tool-Registry-Eintrag `license-audit-checklist`
- Sichtprüfung der öffentlichen Texte
- Browserfunktion für Fortschritt, Reset und Druckausgabe
