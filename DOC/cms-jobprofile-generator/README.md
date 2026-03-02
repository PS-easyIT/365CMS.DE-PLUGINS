# CMS Job Profile Generator – Plugin-Dokumentation

> **Plugin-Slug:** `cms-jobprofile-generator`  
> **Version:** 0.9.6 | **Mindest-CMS:** 0.20.0 | **PHP:** 8.2+  
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

Das Plugin ist **trait-basiert** aufgebaut. Beide Controller (`class-admin-pages.php` und `class-member-controller.php`) wurden in eigenständige Trait-Dateien aufgeteilt:

```
Singleton-Pattern  CMS_JobProfileGenerator::instance()
                   └── load_dependencies()   // require_once alle Klassen
                   └── init_hooks()          // CMS\Hooks::addAction()

CMS-Hooks:
  cms_init           → CMS_JPG_Installer::maybe_install()
  plugin_activated   → CMS_JPG_Installer::install()
  cms_admin_menu     → CMS_JPG_Admin_Menu::register()
  member_dashboard_init → CMS_JPG_MemberController::register_via_plugin_dashboard()
  head               → CSS-Asset einbinden
  body_end           → JS-Asset einbinden
```

Das Plugin hält sich strikt an das 365CMS-Hook-System:

```php
CMS\Hooks::addAction('cms_init', [$this, 'init_plugin'], 10);
```

Es registriert **keine** WordPress-Hooks – ausschließlich 365CMS-Hooks.

### Admin-Traits (`admin/modules/`)

| Trait-Datei | Trait | Inhalt |
|---|---|---|
| `trait-page-dashboard.php` | `CMS_JPG_Page_Dashboard_Trait` | Dashboard-Übersicht, KPI-Stats |
| `trait-page-generator.php` | `CMS_JPG_Page_Generator_Trait` | Generator-Wizard, Firmen-Autofill |
| `trait-page-libraries.php` | `CMS_JPG_Page_Libraries_Trait` | Bibliotheken-Verwaltung (6 Tabs) |
| `trait-page-design.php` | `CMS_JPG_Page_Design_Trait` | Templates & Corporate Design |
| `trait-page-settings.php` | `CMS_JPG_Page_Settings_Trait` | Einstellungen (5 Tabs) |
| `trait-page-workflow.php` | `CMS_JPG_Page_Workflow_Trait` | Workflow-Schritte Editor |
| `trait-page-approvals.php` | `CMS_JPG_Page_Approvals_Trait` | Admin-Genehmigungen (Dot-Progress) |
| `trait-page-companies.php` | `CMS_JPG_Page_Companies_Trait` | Unternehmens-Übersicht + Benefits |
| `trait-page-subscription.php` | `CMS_JPG_Page_Subscription_Trait` | Abo-Rollen-Verwaltung |
| `trait-page-users.php` | `CMS_JPG_Page_Users_Trait` | Plugin-User-Verwaltung |

### Member-Traits (`includes/member/`)

| Trait-Datei | Trait | Inhalt |
|---|---|---|
| `trait-member-hooks.php` | `CMS_JPG_Member_Hooks_Trait` | Hooks, Routes, Stats, Dashboard-Menü |
| `trait-member-dsgvo.php` | `CMS_JPG_Member_Dsgvo_Trait` | Daten-Export (Art. 20), Account-Löschung |
| `trait-member-jobs.php` | `CMS_JPG_Member_Jobs_Trait` | Stellenanzeigen: List, Create, Edit, Duplicate |
| `trait-member-inline.php` | `CMS_JPG_Member_Inline_Trait` | Plugin-Dashboard-Registrierung, Inline-Views |
| `trait-member-applications.php` | `CMS_JPG_Member_Applications_Trait` | Bewerbungs-Postfach, Ajax-Status, Download |
| `trait-member-approvals.php` | `CMS_JPG_Member_Approvals_Trait` | Member-Genehmigungsbereich (Standalone + Inline) |
| `trait-member-settings.php` | `CMS_JPG_Member_Settings_Trait` | Firmen-Einstellungen, Abteilungen, Jobs-Seite |

---

## Verzeichnisstruktur

```
cms-jobprofile-generator/
├── cms-jobprofile-generator.php     # Bootstrap, Konstanten, Singleton
├── uninstall.php                    # Deinstallations-Cleanup
├── update.json                      # Update-Manifest
├── admin/
│   ├── class-admin-menu.php         # Admin-Untermenü (8 Punkte)
│   ├── class-admin-pages.php        # Shell (137 Zeilen) + 10 use TraitName
│   ├── modules/                     # Admin-Traits
│   │   ├── trait-page-dashboard.php
│   │   ├── trait-page-generator.php
│   │   ├── trait-page-libraries.php
│   │   ├── trait-page-design.php
│   │   ├── trait-page-settings.php
│   │   ├── trait-page-workflow.php
│   │   ├── trait-page-approvals.php
│   │   ├── trait-page-companies.php
│   │   ├── trait-page-subscription.php
│   │   └── trait-page-users.php
│   └── views/                       # PHP-View-Dateien
│       ├── page-dashboard.php
│       ├── page-generator.php       # 5-Tab-Wizard + Company-Autofill
│       ├── page-libraries.php       # 6-Tab-Bibliothek
│       ├── page-design.php
│       ├── page-settings.php
│       ├── page-workflow-editor.php
│       ├── page-approvals.php       # Dot-Progress, Approve-Modal
│       ├── page-company-overview.php
│       └── ...
├── includes/
│   ├── class-member-controller.php  # Shell (116 Zeilen) + 7 use TraitName
│   ├── class-installer.php          # DB-Migration, Seed-Daten
│   ├── class-profiles.php           # Profil-CRUD + Unterentitäten
│   ├── class-benefits-catalog.php   # Benefits-CRUD
│   ├── class-departments.php        # Abteilungen-CRUD + Einstellungen
│   ├── class-export.php             # HTML/JSON-Export + Import
│   ├── class-frontend.php           # Öffentliche Routes + Templates
│   ├── class-job-categories.php     # Kategorien-CRUD
│   ├── class-requirement-items.php  # Anforderungs-Bausteine-CRUD
│   ├── class-skill-matrix.php       # Skills-CRUD
│   ├── class-text-modules.php       # Textbausteine-CRUD
│   └── member/                      # Member-Traits
│       ├── trait-member-hooks.php
│       ├── trait-member-dsgvo.php
│       ├── trait-member-jobs.php
│       ├── trait-member-inline.php
│       ├── trait-member-applications.php
│       ├── trait-member-approvals.php
│       └── trait-member-settings.php
├── assets/
│   ├── css/jobprofile-admin.css     # Tabs, DnD, Pills, Modals
│   └── js/jobprofile-admin.js       # SunEditor, DnD, Char-Counter, Toast
├── views/
│   ├── member/
│   │   ├── page-jobs-list.php
│   │   ├── page-jobs-create.php     # 5-Tab-Wizard
│   │   ├── page-jobs-edit.php       # 5-Tab-Wizard (Gesperrt-Modus)
│   │   ├── page-approvals.php
│   │   ├── page-applications.php
│   │   └── page-settings.php       # Tabs: Info, Benefits, Team, Depts, Jobs-Seite
│   └── public/
│       └── jobs-list.php
└── DOC/
    ├── README.md                    # Diese Datei
    ├── CHANGELOG.md                 # Detaillierter Änderungsverlauf
    ├── INSTALLATION.md              # Aktivierung, DB-Migration
    ├── DATABASE.md                  # Vollständiges DB-Schema
    ├── HOOKS-API.md                 # Actions & Filter
    ├── SECURITY.md                  # CSRF, Sanitierung, Escaping
    ├── FRONTEND-ROUTING.md          # Öffentliche Routen & Templates
    ├── CROSS-PLUGIN-INTEGRATION.md  # cms-companies & cms-experts
    ├── CHECK.md                     # QA-Checkliste
    └── TASKS.md                     # Entwicklungs-Aufgabenliste
```

---

## Klassen-Referenz

| Klasse | Datei | Zweck |
|---|---|---|
| `CMS_JobProfileGenerator` | `cms-jobprofile-generator.php` | Singleton-Bootstrap, Hook-Registrierung |
| `CMS_JPG_Installer` | `includes/class-installer.php` | DB-Tabellen anlegen (19), Seed-Daten |
| `CMS_JPG_Profiles` | `includes/class-profiles.php` | Profil-CRUD, Tasks, Requirements |
| `CMS_JPG_TextModules` | `includes/class-text-modules.php` | Textbausteine |
| `CMS_JPG_SkillMatrix` | `includes/class-skill-matrix.php` | Fähigkeiten/Skills |
| `CMS_JPG_BenefitsCatalog` | `includes/class-benefits-catalog.php` | Mitarbeiter-Benefits |
| `CMS_JPG_JobCategories` | `includes/class-job-categories.php` | Stellenkategorien |
| `CMS_JPG_RequirementItems` | `includes/class-requirement-items.php` | Anforderungs-Bausteine |
| `CMS_JPG_Departments` | `includes/class-departments.php` | Abteilungen + Firmen-Einstellungen |
| `CMS_JPG_Export` | `includes/class-export.php` | Export (HTML/JSON) + Import |
| `CMS_JPG_Frontend` | `includes/class-frontend.php` | Öffentliche Listen-Route |
| `CMS_JPG_Admin_Menu` | `admin/class-admin-menu.php` | Admin-Menü registrieren (8 Punkte) |
| `CMS_JPG_Admin_Pages` | `admin/class-admin-pages.php` | Shell + 10 Admin-Traits |
| `CMS_JPG_MemberController` | `includes/class-member-controller.php` | Shell + 7 Member-Traits |

Alle Klassen verwenden das **Singleton-Pattern**:

```php
$profiles = CMS_JPG_Profiles::instance();
$list     = $profiles->get_list(['status' => 'published']);
```

### Plugin-Konstanten

| Konstante | Wert | Beschreibung |
|---|---|---|
| `JPG_VERSION` | `'0.9.6'` | Plugin-Version |
| `JPG_DB_VERSION` | `'7'` | Datenbankschema-Version |
| `JPG_DIR` | `dirname(__FILE__) . '/'` | Absoluter Pfad |
| `JPG_URL` | `'/plugins/cms-jobprofile-generator/'` | Relativer URL-Pfad |
| `JPG_TEXT_DOMAIN` | `'cms-jobprofile-generator'` | i18n-Textdomain |

---

## Admin-Menü

### Untermenü-Übersicht

| # | Menüpunkt | Slug | Trait | Beschreibung |
|---|---|---|---|---|
| 1 | 📊 Dashboard | `jpg-dashboard` | `trait-page-dashboard.php` | KPI-Kacheln, Entwürfe, Statistiken |
| 2 | 📝 Stellenanzeigen | `jpg-generator` | `trait-page-generator.php` | Generator-Wizard (5 Tabs), Firmen-Autofill |
| 3 | 📚 Bibliotheken | `jpg-libraries` | `trait-page-libraries.php` | Textbausteine, Anforderungs-Liste, Skill-Matrix, Benefit-Katalog, Kategorien, Im-/Export (6 Tabs) |
| 4 | 🎨 Vorlagen & Design | `jpg-design` | `trait-page-design.php` | PDF-, Web-, E-Mail-Templates, Corporate Design, Typografie |
| 5 | 🔄 Workflow-Editor | `jpg-workflow` | `trait-page-workflow.php` | Genehmigungsschritte konfigurieren |
| 6 | ✅ Genehmigungen | `jpg-approvals` | `trait-page-approvals.php` | Ausstehende Freigaben, Dot-Progress, Approve/Reject-Modal |
| 7 | 🏢 Unternehmens-Übersicht | `jpg-companies` | `trait-page-companies.php` | Standard-Benefits pro Unternehmen zuweisen (cms-companies) |
| 8 | ⚙️ Einstellungen | `jpg-settings` | `trait-page-settings.php` | Allgemein, Berechtigungen, Workflow, Benachrichtigungen, System-Info |

### Generator-Wizard – Tabs

| Tab | Name | Inhalt |
|---|---|---|
| 1️⃣ | Basisdaten | Titel, Unternehmen (Autofill: PLZ/Ort/Land/Telefon/Website), Kategorie, Gehalt, Erfahrungslevel |
| 2️⃣ | Aufgaben | Drag & Drop-Liste, einklappbarer Textbaustein-Picker |
| 3️⃣ | Anforderungen | Skill-Matrix-Picker + Anforderungs-Bausteine-Picker |
| 4️⃣ | Benefits | Checkbox-Grid; Company Default Benefits werden bei neuem Profil vorausgewählt |
| 5️⃣ | Workflow | Schritt-Visualisierung von Entwurf → Genehmigungsschritte → Veröffentlicht |

### Bibliotheken – Tabs

| Tab | Name | Inhalt |
|---|---|---|
| 1 | Textbausteine | Wiederverwendbare Texte für den Aufgaben-Tab |
| 2 | Anforderungs-Liste | Eigene Anforderungs-Bausteine (Gruppe, Titel, Reihenfolge) |
| 3 | Skill-Matrix | Fähigkeiten mit Level-Definition |
| 4 | Benefit-Katalog | Benefits mit Gruppen-Zuordnung + Einklapp-Funktion |
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
