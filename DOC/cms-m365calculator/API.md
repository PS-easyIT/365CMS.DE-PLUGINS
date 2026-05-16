# CMS M365 Calculator – API

## `CMS_M365CALCULATOR_Catalog`

- `rules()` – lädt Regeln aus `shared_mailbox_rules.json`
- `license_matrix()` – lädt Lizenzprofile aus `mailbox_license_matrix.json`
- `scenarios()` – lädt auswählbare Szenarien
- `pricing()` – lädt Preisannahmen

## `CMS_M365CALCULATOR_Tool_Registry`

- `register(array $tool)` – registriert ein Rechner-Modul
- `tools()` – liefert alle Moduldefinitionen
- `ordered_tools()` – liefert nach Priorität sortierte Module
- `grouped_by_category()` – liefert Module nach Kategorie gruppiert
- `get(string $slug)` – liefert ein Modul nach Slug

## `CMS_M365CALCULATOR_Shared_Mailbox_Calculator`

- `default_input()` – Default-Werte für das Formular
- `normalize_input(array $source)` – normalisiert POST-Daten
- `evaluate(array $input)` – berechnet Empfehlung, Regeln, Warnungen, Kosten und nächste Schritte

## `CMS_M365CALCULATOR_Frontend`

- Registriert `/m365-tools`, `/m365-rechner` und `/shared-mailbox-vs-lizenz`
- Bindet Assets nur auf Plugin-Routen ein
- Rendert Toolbox und Rechner-Template
