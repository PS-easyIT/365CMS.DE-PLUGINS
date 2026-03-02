# cms-jobprofile-generator

**Version:** 0.9.6 · **Status:** Beta · **PHP:** 8.2+ · **365CMS:** ≥ 0.20.0

Vollständiges Plugin für **365CMS** zum Erstellen, Verwalten und Veröffentlichen von Stellenanzeigen mit konfigurierbarem Workflow-Genehmigungsprozess.

---

## Inhalt

1. [Features](#features)
2. [Architektur](#architektur)
3. [Verzeichnisstruktur](#verzeichnisstruktur)
4. [Routing](#routing)
5. [Datenbank](#datenbank)
6. [Admin-Menü](#admin-menü)
7. [Installation](#installation)
8. [Cross-Plugin-Integration](#cross-plugin-integration)
9. [Dokumentation](#dokumentation)

---

## Features

| Bereich | Funktionen |
|---|---|
| **Stellenanzeigen** | Erstellen, Bearbeiten, Duplizieren, Archivieren; 5-Tab-Wizard (Basisdaten, Aufgaben, Anforderungen, Benefits, Workflow) |
| **Workflow** | Konfigurierbare Genehmigungsschritte, Approve / Reject / Reset, Verlaufsprotokoll |
| **Genehmigungen** | Standalone-Route `/member/jobs/approvals` + Inline-Dashboard; Admins + zugewiesene Genehmiger |
| **Benefits** | Gruppierbarer Katalog; Department-spezifische Zuweisung; Seeding mit Standard-Einträgen |
| **Anforderungen** | Anforderungs-Katalog mit 60+ Einträgen in 8 Kategorien; Picker im Generator |
| **Abteilungen** | Departments anlegen, Benefits und Anforderungen per Accordion verwalten |
| **Einstellungen** | Tabs: Allgemein, Team/Genehmiger, Abteilungen, Jobs-Seite; Firma Autofill |
| **Public Listing** | Route `/jobs` → öffentliche Übersicht aller freigegebenen Stellen (`show_in_listing = 1`) |
| **Bewerbungen** | Bewerbungs-Postfach für Stelleninhaber; CV-Download via sicherem Token |
| **Bibliotheken** | Benefit-Katalog, Skill-Matrix, Job-Kategorien, Textbausteine, PDF-Templates |
| **Admin** | 8 Untermenüpunkte: Übersicht, Generator, Bibliotheken, Design, Workflow-Editor, Genehmigungen, Unternehmens-Übersicht, Einstellungen |
| **DSGVO** | Daten-Export (Art. 20) und Account-Löschung (Art. 17) via CMS-Hooks |
| **Sicherheit** | CSRF-Nonces, PDO Prepared Statements, XSS-Escaping, Rate-Limiting |

---

## Architektur

Das Plugin ist **trait-basiert** aufgebaut. Monolithische Controller wurden in eigenständige Trait-Dateien aufgeteilt:

### Admin-Controller (`admin/class-admin-pages.php` — 137 Zeilen Shell)

| Trait-Datei | Trait | Inhalt |
|---|---|---|
| `admin/modules/trait-page-dashboard.php` | `CMS_JPG_Page_Dashboard_Trait` | Dashboard-Übersicht, Stats |
| `admin/modules/trait-page-generator.php` | `CMS_JPG_Page_Generator_Trait` | Generator-Wizard, Firmen-Autofill |
| `admin/modules/trait-page-libraries.php` | `CMS_JPG_Page_Libraries_Trait` | Bibliotheken-Verwaltung |
| `admin/modules/trait-page-design.php` | `CMS_JPG_Page_Design_Trait` | Templates & Corporate Design |
| `admin/modules/trait-page-settings.php` | `CMS_JPG_Page_Settings_Trait` | Einstellungen (5 Tabs) |
| `admin/modules/trait-page-workflow.php` | `CMS_JPG_Page_Workflow_Trait` | Workflow-Editor |
| `admin/modules/trait-page-approvals.php` | `CMS_JPG_Page_Approvals_Trait` | Admin-Genehmigungen |
| `admin/modules/trait-page-companies.php` | `CMS_JPG_Page_Companies_Trait` | Unternehmens-Übersicht |
| `admin/modules/trait-page-subscription.php` | `CMS_JPG_Page_Subscription_Trait` | Abo-Rollen-Verwaltung |
| `admin/modules/trait-page-users.php` | `CMS_JPG_Page_Users_Trait` | Plugin-User-Verwaltung |

### Member-Controller (`includes/class-member-controller.php` — 116 Zeilen Shell)

| Trait-Datei | Trait | Inhalt |
|---|---|---|
| `includes/member/trait-member-hooks.php` | `CMS_JPG_Member_Hooks_Trait` | Hooks, Routes, Stats, Dashboard-Menü |
| `includes/member/trait-member-dsgvo.php` | `CMS_JPG_Member_Dsgvo_Trait` | Daten-Export, Account-Löschung |
| `includes/member/trait-member-jobs.php` | `CMS_JPG_Member_Jobs_Trait` | Stellenanzeigen: List, Create, Edit |
| `includes/member/trait-member-inline.php` | `CMS_JPG_Member_Inline_Trait` | Plugin-Dashboard-Integration, Inline-Views |
| `includes/member/trait-member-applications.php` | `CMS_JPG_Member_Applications_Trait` | Bewerbungs-Postfach, Download |
| `includes/member/trait-member-approvals.php` | `CMS_JPG_Member_Approvals_Trait` | Member-Genehmigungsbereich |
| `includes/member/trait-member-settings.php` | `CMS_JPG_Member_Settings_Trait` | Firmen-Einstellungen, Abteilungen, Bibliotheken |

---

## Verzeichnisstruktur

```
cms-jobprofile-generator/
├── cms-jobprofile-generator.php     # Bootstrap, Konstanten, Singleton
├── uninstall.php                    # Deinstallations-Cleanup
├── update.json                      # Update-Manifest
├── admin/
│   ├── class-admin-menu.php         # Admin-Untermenü registrieren (8 Punkte)
│   ├── class-admin-pages.php        # Shell (137 Zeilen) — 10 Traits per use
│   ├── modules/                     # Admin-Trait-Dateien
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
│       ├── page-generator.php
│       ├── page-libraries.php
│       ├── page-design.php
│       ├── page-settings.php
│       ├── page-workflow-editor.php
│       ├── page-approvals.php
│       ├── page-company-overview.php
│       └── ...
├── includes/
│   ├── class-member-controller.php  # Shell (116 Zeilen) — 7 Traits per use
│   ├── class-installer.php
│   ├── class-profiles.php
│   ├── class-benefits-catalog.php
│   ├── class-departments.php
│   ├── class-export.php
│   ├── class-frontend.php
│   ├── class-job-categories.php
│   ├── class-requirement-items.php
│   ├── class-skill-matrix.php
│   ├── class-text-modules.php
│   └── member/                      # Member-Trait-Dateien
│       ├── trait-member-hooks.php
│       ├── trait-member-dsgvo.php
│       ├── trait-member-jobs.php
│       ├── trait-member-inline.php
│       ├── trait-member-applications.php
│       ├── trait-member-approvals.php
│       └── trait-member-settings.php
├── assets/
│   ├── css/
│   └── js/
├── views/
│   ├── member/
│   │   ├── page-jobs-list.php
│   │   ├── page-jobs-create.php
│   │   ├── page-jobs-edit.php
│   │   ├── page-approvals.php
│   │   ├── page-applications.php
│   │   └── page-settings.php
│   └── public/
│       └── jobs-list.php
└── DOC/                             # Vollständige Dokumentation
```

---

## Routing

### Member-Routen (eingeloggt)

| Methode | Pfad | Beschreibung |
|---|---|---|
| GET | `/member/jobs` | Eigene Stellenanzeigen-Liste |
| GET/POST | `/member/jobs/create` | Neue Stelle anlegen (5-Tab-Wizard) |
| GET/POST | `/member/jobs/edit/:id` | Stelle bearbeiten (5-Tab-Wizard) |
| GET/POST | `/member/jobs/duplicate/:id` | Stelle duplizieren |
| GET/POST | `/member/jobs/approvals` | Genehmigungsbereich (Standalone) |
| GET | `/member/jobs/applications` | Bewerbungs-Postfach |
| GET | `/member/jobs/download/:token` | Bewerbungs-Anhang herunterladen |
| GET/POST | `/member/jobs/settings` | Unternehmens-Einstellungen |
| POST | `/member/jobs/workflow/submit/:id` | Stelle zum Workflow einreichen |

### Public-Routen

| Methode | Pfad | Beschreibung |
|---|---|---|
| GET | `/jobs` | Öffentliche Stellenanzeigen-Übersicht |
| GET | `/jobs/:slug` | Einzelne veröffentlichte Stellenanzeige |
| GET | `/api/jobs/:slug/pdf` | PDF-Download |

### Admin-Routen

Alle unter `/{admin-slug}/plugins/jpg-dashboard/`:

| Slug | Beschreibung |
|---|---|
| `jpg-dashboard` | Übersicht & Statistiken |
| `jpg-generator` | Profil-Generator-Wizard |
| `jpg-libraries` | Bibliotheken |
| `jpg-design` | Vorlagen & Corporate Design |
| `jpg-workflow` | Workflow-Editor |
| `jpg-approvals` | Ausstehende Genehmigungen |
| `jpg-companies` | Unternehmens-Übersicht |
| `jpg-settings` | Plugin-Einstellungen |

---

## Datenbank

**DB-Version:** 7 · **Tabellen:** 19 (Präfix `jpg_`)

| Tabelle | Beschreibung |
|---|---|
| `jpg_profiles` | Stellenanzeigen (Stammdaten) |
| `jpg_profile_tasks` | Aufgaben je Profil (sortierbar, DnD) |
| `jpg_profile_requirements` | Anforderungen je Profil |
| `jpg_profile_benefits` | m:n Profil ↔ Benefit |
| `jpg_profile_skills` | m:n Profil ↔ Skill |
| `jpg_applications` | Bewerbungen mit CV-Upload |
| `jpg_workflow_steps` | Konfigurierte Genehmigungsschritte |
| `jpg_workflow_history` | Vollständiges Genehmigungsprotokoll |
| `jpg_team_approvers` | Team-Genehmiger (zusätzlich zu Rollen) |
| `jpg_benefits_catalog` | Globaler Benefits-Katalog |
| `jpg_requirement_items` | Anforderungs-Bausteine (60+ Seed-Einträge) |
| `jpg_skill_matrix` | Skill-Bibliothek |
| `jpg_job_categories` | Stellenkategorien |
| `jpg_text_modules` | Wiederverwendbare Textbausteine |
| `jpg_templates` | PDF/Web/E-Mail-Vorlagen |
| `jpg_departments` | Abteilungen pro Unternehmen |
| `jpg_department_benefits` | m:n Abteilung ↔ Benefit |
| `jpg_department_requirements` | m:n Abteilung ↔ Anforderung |
| `jpg_admin_access_log` | Audit-Log für Admin-Zugriffe |

> Vollständiges Schema mit allen Spalten: [DOC/DATABASE.md](DOC/DATABASE.md)

---

## Admin-Menü

| # | Menüpunkt | Slug | Beschreibung |
|---|---|---|---|
| 1 | 📊 Dashboard | `jpg-dashboard` | KPI-Kacheln, Entwürfe, Statistiken |
| 2 | 📝 Stellenanzeigen | `jpg-generator` | 5-Tab-Wizard, Firmen-Autofill |
| 3 | 📚 Bibliotheken | `jpg-libraries` | Textbausteine, Anforderungen, Skills, Benefits, Kategorien, Im-/Export |
| 4 | 🎨 Vorlagen & Design | `jpg-design` | PDF/Web/E-Mail-Templates, Corporate Design |
| 5 | 🔄 Workflow-Editor | `jpg-workflow` | Genehmigungsschritte konfigurieren |
| 6 | ✅ Genehmigungen | `jpg-approvals` | Ausstehende Freigaben verwalten |
| 7 | 🏢 Unternehmens-Übersicht | `jpg-companies` | Standard-Benefits pro Unternehmen (cms-companies) |
| 8 | ⚙️ Einstellungen | `jpg-settings` | Allgemein, Berechtigungen, Benachrichtigungen, Abo-Rollen, Plugin-User |

---

## Installation

Das Plugin liegt unter `PLUGINS/cms-jobprofile-generator/` und wird über den Plugin-Manager von 365CMS aktiviert.

Bei Aktivierung führt `CMS_JPG_Installer::install()` automatisch aus:

1. 19 Datenbank-Tabellen anlegen / migrieren
2. System-Daten seeden (Kategorien, Benefits, Skills, Textbausteine, Templates, 60+ Anforderungs-Bausteine)
3. Routing registrieren

> Ausführliche Anleitung: [DOC/INSTALLATION.md](DOC/INSTALLATION.md)

---

## Cross-Plugin-Integration

| Plugin | Integration |
|---|---|
| **cms-companies** | Firmen-Dropdown im Generator, Autofill (PLZ/Ort/Land/Telefon), Standard-Benefits, Mandant-Rollenprüfung |
| **cms-experts** | Team-Genehmiger-Auswahl aus Experten-Profilen |

> Details: [DOC/CROSS-PLUGIN-INTEGRATION.md](DOC/CROSS-PLUGIN-INTEGRATION.md)

---

## Dokumentation

| Datei | Inhalt |
|---|---|
| [DOC/README.md](DOC/README.md) | Plugin-Architektur, Klassen-Referenz, detailliertes Admin-Menü |
| [DOC/CHANGELOG.md](DOC/CHANGELOG.md) | Detaillierter Änderungsverlauf |
| [DOC/INSTALLATION.md](DOC/INSTALLATION.md) | Installations- & Aktivierungsanleitung, DB-Tabellen-Übersicht |
| [DOC/DATABASE.md](DOC/DATABASE.md) | Vollständiges DB-Schema (alle Tabellen + Spalten) |
| [DOC/HOOKS-API.md](DOC/HOOKS-API.md) | Actions & Filter für Dritt-Plugins / Custom-Code |
| [DOC/SECURITY.md](DOC/SECURITY.md) | CSRF, Sanitierung, Escaping, SQL-Injection-Schutz |
| [DOC/FRONTEND-ROUTING.md](DOC/FRONTEND-ROUTING.md) | Öffentliche Routen, Template-Hierarchie, Caching |
| [DOC/CROSS-PLUGIN-INTEGRATION.md](DOC/CROSS-PLUGIN-INTEGRATION.md) | cms-companies & cms-experts Integration |
| [DOC/CHECK.md](DOC/CHECK.md) | QA-Checkliste & Bug-Tracking |
| [DOC/TASKS.md](DOC/TASKS.md) | Entwicklungs-Aufgabenliste |
| [CHANGELOG.md](CHANGELOG.md) | Kompakter Änderungsverlauf |

---

## Anforderungs-Katalog (Seed-Daten)

Beim ersten Start werden über 60 Einträge in folgenden Gruppen angelegt:

- IT & Programmierung (PHP, JS, Python, SQL, REST, Git, Docker, Cloud, Linux, CI/CD)
- Sprachen (Deutsch, Englisch C1, Englisch B2, Fremdsprache)
- Soft Skills (Teamfähigkeit, Kreativität, Kommunikation, Struktur …)
- Ausbildung & Qualifikation (Studium, Ausbildung, Zertifizierungen)
- Kaufmännisch (Buchhaltung, Auftragsabwicklung, Controlling, Faktura)
- Tools & Software (Office, Excel, CRM, SAP, Figma, JIRA)
- Führung & Projektmanagement (Scrum, Kanban, Budgetverantwortung)
- Marketing & Vertrieb (B2B, SEO, Content, Social Media, Key Account)
- Persönlichkeit (Belastbarkeit, Hands-on, analytisch, Reisebereitschaft)

---

## Genehmigungsbereich

Der Genehmigungsbereich ist zu erreichen:
- **Inline** im Member-Dashboard unter dem Sidebar-Eintrag „✅ Genehmigungen"
- **Standalone** unter `/member/jobs/approvals`

Zugang haben:
- Admins (immer)
- User mit einer Rolle, die einem Workflow-Schritt als `approver_role` zugewiesen ist
- User mit aktuell ausstehenden Genehmigungen (Rollen- oder Team-Genehmiger)
