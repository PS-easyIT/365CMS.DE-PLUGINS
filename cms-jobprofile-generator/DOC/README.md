# CMS Job Profile Generator – Plugin-Dokumentation

> **Plugin-Slug:** `cms-jobprofile-generator`  
> **Version:** 0.6.0 | **Mindest-CMS:** 0.20.0 | **PHP:** 8.2+  
> **Lizenz:** Free

---

## Inhaltsverzeichnis

1. [Übersicht](#übersicht)
2. [Architektur](#architektur)
3. [Verzeichnisstruktur](#verzeichnisstruktur)
4. [Klassen-Referenz](#klassen-referenz)
5. [Admin-Menü](#admin-menü)
6. [Abo-System-Integration](#abo-system-integration)
7. [Weiterführende Dokumentation](#weiterführende-dokumentation)

---

## Übersicht

Das Plugin **CMS Job Profile Generator** ermöglicht die vollständige Erstellung, Verwaltung und Veröffentlichung von strukturierten Stellenprofilen innerhalb des 365CMS-Frameworks.

**Kernfunktionen:**

| Feature | Beschreibung |
|---|---|
| Generator-Wizard | 6-Tab-Formular: Basisdaten (Firmen-Autofill), Aufgaben (Textbaustein-Picker, DnD), Anforderungen (Bausteine-Picker), Benefits, Skills, Review & Export |
| Bibliotheken | 6 Tabs: Textbausteine, Anforderungs-Liste, Skill-Matrix, Benefit-Katalog, Jobkategorien, Im-/Export |
| Design & Templates | PDF/Web/E-Mail-Templates, Corporate Design, Typografie |
| Export / Import | JSON-Export einzelner Profile; JSON-Datei-Import für Bibliotheksdaten |
| Routing | Theme-integriert (`/jobs/:slug`) + optionales Whitelabel (`/career/:slug`) |
| Sicherheit | CSRF-Nonces, PDO Prepared Statements, XSS-Escaping |

> **Keine KI:** Das Plugin enthält keinerlei künstliche Intelligenz, keine LLM-Schnittstellen und keine automatisch generierten Textvorschläge.

---

## Architektur

```
Singleton-Pattern  CMS_JobProfileGenerator::instance()
                   └── load_dependencies()   // require_once alle Klassen
                   └── init_hooks()          // CMS\Hooks::addAction()

CMS-Hooks:
  cms_init          → CMS_JPG_Installer::maybe_install()
  plugin_activated  → CMS_JPG_Installer::install()
  cms_admin_menu    → CMS_JPG_Admin_Menu::register()
  head              → CSS-Asset einbinden
  body_end          → JS-Asset einbinden
```

Das Plugin hält sich strikt an das 365CMS-Hook-System:

```php
CMS\Hooks::addAction('cms_init', [$this, 'init_plugin'], 10);
```

Es registriert **keine** `wp_head`/`wp_enqueue_scripts`-Hooks – ausschließlich 365CMS-Hooks.

---

## Verzeichnisstruktur

```
cms-jobprofile-generator/
├── job-profile-generator.php          # Bootstrap, Konstanten, Singleton
├── update.json                        # Update-Manifest (Schema s. CMS-Standard)
├── includes/
│   ├── class-installer.php            # DB-Migration, Seed-Daten
│   ├── class-profiles.php             # Profil-CRUD + Unterentitäten
│   ├── class-text-modules.php         # Textbausteine-CRUD
│   ├── class-skill-matrix.php         # Skills-CRUD
│   ├── class-benefits-catalog.php     # Benefits-CRUD
│   ├── class-job-categories.php       # Kategorien-CRUD
│   ├── class-requirement-items.php    # Anforderungs-Bausteine-CRUD
│   └── class-export.php               # HTML/JSON Export + JSON Import
├── admin/
│   ├── class-admin-menu.php           # add_menu_page() / add_submenu_page()
│   ├── class-admin-pages.php          # POST-Handler, AJAX, View-Dispatcher
│   └── views/
│       ├── page-dashboard.php         # Übersicht, Entwürfe, Statistiken
│       ├── page-generator.php         # Wizard (6 Tabs)
│       ├── page-libraries.php         # Bibliotheken (6 Tabs)
│       ├── page-approvals.php         # Genehmigungen (Dot-Progress, Modal)
│       ├── page-company-overview.php  # Unternehmens-Übersicht & Default-Benefits
│       ├── page-design.php            # Templates & Corporate Design
│       └── page-settings.php         # Einstellungen (5 Tabs)
├── assets/
│   ├── css/jobprofile-admin.css       # Tabs, DnD, Pills, Modals
│   └── js/jobprofile-admin.js         # SunEditor, DnD, Char-Counter, Toast
└── DOC/
    ├── README.md                      # Diese Datei
    ├── INSTALLATION.md                # Installations- & Aktivierungsanleitung
    ├── DATABASE.md                    # Datenbank-Schema (alle Tabellen)
    ├── HOOKS-API.md                   # Verfügbare Hooks & Filter
    ├── SECURITY.md                    # Sicherheitskonzept
    ├── FRONTEND-ROUTING.md            # Öffentliche Routen & Templates
    └── TASKS.md                       # Entwicklungs-Aufgabenliste
```

---

## Klassen-Referenz

| Klasse | Datei | Zweck |
|---|---|---|
| `CMS_JobProfileGenerator` | `job-profile-generator.php` | Singleton-Bootstrap |
| `CMS_JPG_Installer` | `includes/class-installer.php` | DB-Tabellen anlegen, Seed-Daten |
| `CMS_JPG_Profiles` | `includes/class-profiles.php` | Profil-CRUD, Tasks, Requirements |
| `CMS_JPG_TextModules` | `includes/class-text-modules.php` | Textbausteine |
| `CMS_JPG_SkillMatrix` | `includes/class-skill-matrix.php` | Fähigkeiten/Skills |
| `CMS_JPG_BenefitsCatalog` | `includes/class-benefits-catalog.php` | Mitarbeiter-Benefits |
| `CMS_JPG_JobCategories` | `includes/class-job-categories.php` | Stellenkategorien |
| `CMS_JPG_RequirementItems` | `includes/class-requirement-items.php` | Anforderungs-Bausteine (Bibliothek) |
| `CMS_JPG_Export` | `includes/class-export.php` | Export (HTML/JSON) + Import |
| `CMS_JPG_Admin_Menu` | `admin/class-admin-menu.php` | Admin-Menü registrieren |
| `CMS_JPG_Admin_Pages` | `admin/class-admin-pages.php` | POST-Handler & View-Dispatch |

Alle Klassen verwenden das **Singleton-Pattern**:

```php
$profiles = CMS_JPG_Profiles::instance();
$list     = $profiles->get_list(['status' => 'published']);
```

### Plugin-Konstanten

| Konstante | Wert | Beschreibung |
|---|---|---|
| `JPG_VERSION` | `'0.6.0'` | Plugin-Version |
| `JPG_DB_VERSION` | `'1'` | Datenbankschema-Version |
| `JPG_DIR` | `dirname(__FILE__) . '/'` | Absoluter Pfad |
| `JPG_URL` | `'/plugins/cms-jobprofile-generator/'` | Relativer URL-Pfad |
| `JPG_TEXT_DOMAIN` | `'cms-jobprofile-generator'` | i18n-Textdomain |

---

## Admin-Menü

### Untermenü-Übersicht

| # | Menüpunkt | Slug | Beschreibung |
|---|---|---|---|
| 1 | 📊 Dashboard | `jpg-dashboard` | Übersicht, Entwürfe, Veröffentlicht, Archiv, Statistiken |
| 2 | 📝 Stellenanzeigen | `jpg-generator` | Generator-Wizard (6 Tabs) |
| 3 | 📚 Bibliotheken | `jpg-libraries` | Textbausteine, Anforderungs-Liste, Skill-Matrix, Benefit-Katalog, Kategorien, Im-/Export |
| 4 | 🎨 Vorlagen & Design | `jpg-design` | PDF-, Web-, E-Mail-Templates, Corporate Design, Typografie |
| 5 | 🔄 Workflow-Editor | `jpg-workflow` | Genehmigungsschritte konfigurieren |
| 6 | ✅ Genehmigungen | `jpg-approvals` | Ausstehende Freigaben verwalten |
| 7 | 🏢 Unternehmens-Übersicht | `jpg-companies` | Standard-Benefits pro Unternehmen zuweisen |
| 8 | ⚙️ Einstellungen | `jpg-settings` | Allgemein, Berechtigungen, Workflow, Benachrichtigungen, System-Info |

### Generator-Wizard – Tabs

| Tab | Name | Inhalt |
|---|---|---|
| 1️⃣ | Basisdaten | Titel, Unternehmen (Autofill: PLZ/Ort/Land/Telefon/Website), Kategorie, Gehalt, Erfahrungslevel |
| 2️⃣ | Aufgaben | Drag & Drop-Liste, einklappbarer Textbaustein-Picker |
| 3️⃣ | Anforderungen | Skill-Matrix-Picker + Anforderungs-Bausteine-Picker |
| 4️⃣ | Benefits | Checkbox-Grid; Company Default Benefits werden bei neuem Profil vorausgewählt |
| 5️⃣ | Skills | Skill-Anforderungen (Typ, Level, Pflicht-Flag) |
| 6️⃣ | Review & Export | Vorschau, JSON-Export, Einreichung zum Workflow |

### Bibliotheken – Tabs

| Tab | Name | Inhalt |
|---|---|---|
| 1 | Textbausteine | Wiederverwendbare Texte für den Aufgaben-Tab |
| 2 | Anforderungs-Liste | Eigene Anforderungs-Bausteine (Gruppe, Titel, Reihenfolge) |
| 3 | Skill-Matrix | Fähigkeiten mit Level-Definition |
| 4 | Benefit-Katalog | Benefits mit Gruppen-Zuordnung |
| 5 | Kategorien | Jobkategorien |
| 6 | Im-/Export | JSON-Datei-Import / Export |

---

## Abo-System-Integration

> Diese Features sind für zukünftige CMS-Abo-Plan-Integration vorbereitet, aber noch nicht aktiviert.

| Feature-Flag | Beschreibung |
|---|---|
| `feature_whitelabel_jobs` | Whitelabel-Routing `/career/:slug` ohne CMS-Header |
| `feature_custom_branding` | Corporate-Design-Tab editierbar (Farben, Logo) |
| `limit_job_profiles` | Maximale Anzahl aktiver Profile pro Account |

---

## Weiterführende Dokumentation

- [INSTALLATION.md](INSTALLATION.md) – Aktivierung, DB-Migration, Konfiguration
- [DATABASE.md](DATABASE.md) – Vollständiges Datenbank-Schema
- [HOOKS-API.md](HOOKS-API.md) – Actions & Filter für Dritt-Plugins
- [SECURITY.md](SECURITY.md) – CSRF, Sanitierung, Escaping, Nonces
- [FRONTEND-ROUTING.md](FRONTEND-ROUTING.md) – Öffentliche Routen & Template-Hierarchie
