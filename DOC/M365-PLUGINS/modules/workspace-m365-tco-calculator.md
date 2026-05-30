# Google Workspace ↔ Microsoft 365 TCO-Rechner

## Modul

- Registry-Key: `workspace-m365-tco-calculator`
- Route: `/google-workspace-zu-m365-tco`
- Engine: `CMS_M365CALCULATOR_Workspace_M365_TCO_Calculator`
- Template: `templates/page-workspace-m365-tco-calculator.php`
- Status: `live`
- Version: `1.20.0`

## Zweck

Vergleicht Google Workspace und Microsoft 365 über einen wählbaren Betrachtungszeitraum. Das Modul trennt laufende Lizenzkosten von Migration, Schulung, Change-Aufwand, Hypercare und Parallelbetrieb und liefert eine Management-taugliche Empfehlung inklusive Break-even.

## Datenquellen

- `data/google_workspace_plans.json`
- `data/m365_target_plans.json`
- `data/workspace_to_m365_mapping.json`
- `data/migration_defaults.json`

## Leitplanken

- Google Workspace Business Starter, Standard und Plus werden als Business-Pläne bis 300 Nutzer modelliert.
- Google Storage wird planabhängig mit 30 GB, 2 TB, 5 TB oder Enterprise-Pfad je Nutzer modelliert.
- Microsoft-365-Zielpläne nutzen die gepflegten Referenzpreise aus dem Plugin-Kontext.
- Native Microsoft-Google-Workspace-Migration berücksichtigt Mail, Calendar, Contacts und Rules.
- Migrationsplanung enthält Routingdomänen, Batches, inkrementelle Synchronisierung, Lizenzzuweisungsfenster, Schulung, Change und Hypercare.
- Preise und Projektannahmen bleiben Pflegewerte und müssen je Vertrag ersetzt werden.

## Eingaben

- Vergleichsrichtung Google Workspace zu Microsoft 365 oder Microsoft 365 zu Google Workspace
- Nutzerzahl und Betrachtungszeitraum
- Google-Workspace-Plan
- Microsoft-365-Plan oder automatische Empfehlung
- Google- und Microsoft-Add-ons je Nutzer und Monat
- Migration, Schulung und Change-Aufwand je Nutzer
- Projektbasis, Admin-Mehraufwand, Hypercare, Parallelbetrieb und Parallelkostenanteil
- Security-/Compliance-Niveau, Speicherbedarf, KI-Anforderung
- Enterprise-Sonderkontrollen und unbekannte Google-Add-ons

## Ergebnis

- Gesamt-TCO für Google Workspace und Microsoft 365
- monatliche Plattformkosten je Seite
- Projektkosten als eigener Block
- Delta, Prozentdifferenz und Break-even
- automatisch abgeleitetes Planmapping mit Begründung
- kumulierte Kostenentwicklung
- Projektannahmen und Migrationsleitplanken
- Auffälligkeiten, nächste Schritte und Quellenstand

## Empfehlungskategorien

- Microsoft 365 wirtschaftlich günstiger
- Google Workspace wirtschaftlich günstiger
- Wirtschaftlich ähnlich – strategische Faktoren entscheiden
- Google bleibt kurzfristig günstiger
- Migration kostet kurzfristig mehr, rechnet sich aber später
- Mapping / Anforderungen manuell prüfen

## Public-Verhalten

Die Route berechnet ausschließlich aus Anfrageparametern und speichert keine Eingaben serverseitig. Die Ergebnisansicht enthält keine Besucherhinweise zu Formularprüfmechanismen.

## Pflege

- Google Workspace Preise und Promotions regelmäßig prüfen
- Microsoft-365-Referenzpreise mit CSP-, EA-, MCA- oder Resellerkonditionen ersetzen
- Mapping bei neuen KI-, Security- oder Storage-Bundles nachführen
- Projektkosten je Migrationsmethode, Mandantengröße und Change-Komplexität schärfen
- Quellenstand bei Microsoft- und Google-Änderungen aktualisieren
