# Datenbank – CMS M365 Matrixen

Das Plugin verwendet bewusst die bestehenden M365-Tools-Tabellen.

## Tabellen

- `cms_m365tools_module_settings`
- `cms_m365tools_module_options`

Der tatsächliche Prefix wird über `CMS\Database::instance()->prefix()` ermittelt.

## Optionsgruppen

Alle Matrix-Einstellungen werden als globale M365-Tools-Optionen gespeichert:

| `module_key` | `option_group` | Zweck |
|---|---|---|
| `global` | `matrix-suite` | Texte, CTAs und Sichtbarkeit der Vollpaket-Matrix |
| `global` | `matrix-addon` | Texte, CTAs und Sichtbarkeit der Add-on-Matrix |
| `global` | `matrix-design` | Gemeinsames Außenlayout, Breiten, Abstände, Farben und Defaults |

## Installationsverhalten

`CMS_M365MATRICES_Installer::create_tables()` stellt die beiden gemeinsamen Tabellen sicher, ohne Matrixdaten zu duplizieren.
