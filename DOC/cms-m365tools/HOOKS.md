# CMS M365 Tools – Hooks

## Actions

| Hook | Callback | Zweck |
|---|---|---|
| `cms_init` | `CMS_M365CALCULATOR::init_plugin()` | Prüft Installer-Tabelle und initialisiert Frontend Controller |
| `plugin_activated` | `CMS_M365CALCULATOR::on_activation()` | Erstellt Modulsettings-Tabelle und initialisiert Frontend Controller |
| `cms_admin_menu` | `CMS_M365CALCULATOR_Admin_Menu::register()` | Registriert Admin-Menü |
| `register_routes` | `CMS_M365CALCULATOR_Frontend::instance()` | Registriert Public Routes |
| `head` | `CMS_M365CALCULATOR_Frontend::enqueue_public_styles()` | Bindet CSS nur auf Plugin-Routen ein |
| `body_end` | `CMS_M365CALCULATOR_Frontend::enqueue_public_scripts()` | Bindet JavaScript nur auf Rechner-Routen ein, nicht auf dem Hub |

## Filter

| Filter | Callback | Zweck |
|---|---|---|
| `body_class` | `CMS_M365CALCULATOR_Frontend::filter_body_class()` | Ergänzt Body-Klasse für Theme-Embedding |

## Registry-Filter

| Filter | Wert | Zweck |
|---|---|---|
| `m365calculator_tools` | `array<string,array<string,mixed>>` | Optionale Erweiterung der Tool-Registry durch weitere Module |

## GET-only-Routen ohne CSRF-Abhängigkeit

| Route | Methode | CSRF |
|---|---|---|
| `/m365-lizenzmatrix` | `GET` | Nicht erforderlich, da keine Eingaben oder Mutationen |
| `/m365-addon-matrix` | `GET` | Nicht erforderlich, da keine Eingaben oder Mutationen |
| `/m365-lizenz-audit-checkliste` | `GET` | Nicht erforderlich, da nur priorisiert und der Fortschritt lokal im Browser liegt |
| `/m365-backup-kostenrechner` | `GET` | Nicht erforderlich, da nur berechnet und nichts serverseitig gespeichert wird |
| `/microsoft-preiserhoehung-tracker` | `GET` | Nicht erforderlich, da nur berechnet und nichts serverseitig gespeichert wird |
| `/teams-phone-lizenzberater` | `GET` | Nicht erforderlich, da nur berechnet und nichts serverseitig gespeichert wird |
| `/exchange-online-roi` | `GET` | Nicht erforderlich, da nur berechnet und nichts serverseitig gespeichert wird |
| `/frontline-worker-lizenz-check` | `GET` | Nicht erforderlich, da nur berechnet und nichts serverseitig gespeichert wird |
| `/copilot-pilot-rechner` | `GET` | Nicht erforderlich, da nur berechnet und nichts serverseitig gespeichert wird |
| `/ai-pack-vs-copilot-pro` | `GET` | Nicht erforderlich, da nur berechnet und nichts serverseitig gespeichert wird |
| `/m365-archive-mailbox-rechner` | `GET` | Nicht erforderlich, da nur berechnet und nichts gespeichert wird |
| `/m365-jahresvertrag-vs-monatsvertrag` | `GET` | Nicht erforderlich, da nur berechnet und nichts gespeichert wird |
