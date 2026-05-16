# CMS M365 Calculator

`cms-m365calculator` ist eine modulare Microsoft-365-Rechner-Toolbox für 365CMS. Der erste enthaltene Rechner ist der **Shared-Mailbox vs. Lizenz-Rechner**.

## Enthaltene Routen

- `/m365-tools` – Hub-Übersicht aller Module
- `/m365-rechner` – alternative Hub-Route
- `/shared-mailbox-vs-lizenz` – Shared-Mailbox-Entscheidungs- und Kostenrechner

## Modulstruktur

```text
cms-m365calculator/
├── cms-m365calculator.php
├── includes/
│   ├── class-catalog.php
│   ├── class-tool-registry.php
│   ├── class-frontend.php
│   └── class-shared-mailbox-calculator.php
├── data/
│   ├── shared_mailbox_rules.json
│   ├── mailbox_license_matrix.json
│   ├── shared_mailbox_scenarios.json
│   └── pricing.json
├── templates/
│   ├── landing.php
│   └── page-shared-mailbox.php
└── assets/
    ├── css/
    │   ├── plugin-base.css
    │   ├── style.css
    │   └── m365calculator-public.css
    └── js/
```

## Landingpage-Registry

Module melden sich über `CMS_M365CALCULATOR_Tool_Registry::register()` mit `key`, `title`, `description`, `icon`, `url`, `category` und `status` an. Die Landingpage gruppiert automatisch nach Kategorie und sortiert `live` vor `beta` vor `soon`.

## Design

Das Frontend nutzt die PHINIT-Plugin-Komponenten (`phinit-plugin`, `phinit-card`, `phinit-btn`, `phinit-field`, `phinit-table`, `phinit-note`, `phinit-result`) und ergänzt nur schlanke Layout-Klassen mit dem Präfix `m365calc-*`.

## Fachliche Logik

Der Shared-Mailbox-Rechner bewertet unter anderem:

- Zweck und Login-Anforderung
- interne/externe Nutzung
- aktuelle und erwartete Mailboxgröße
- Archiv, Hold, Retention/Purview und Defender
- Automapping, Hidden GAL, Hybrid und Konvertierung
- geschätztes Einsparpotenzial gegenüber regulären Benutzer-Mailboxen

## Nächste Module

Die Tool-Registry ist vorbereitet, damit spätere Rechner wie ein vollwertiger Microsoft-365-Lizenzberater oder weitere Kosten-/Governance-Tools als Module ergänzt werden können.
