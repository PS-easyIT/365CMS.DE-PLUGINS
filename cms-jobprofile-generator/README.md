# cms-jobprofile-generator

**Version:** 0.9.1 · **Status:** Beta  
Vollständiges Plugin für **365CMS** zum Erstellen, Verwalten und Veröffentlichen von Stellenanzeigen mit Workflow-Genehmigung.

---

## Features

| Bereich | Funktionen |
|---|---|
| **Stellenanzeigen** | Erstellen, Bearbeiten, Archivieren; 5-Tab-Wizard (Basisdaten, Aufgaben, Anforderungen, Benefits, Workflow) |
| **Workflow** | Konfigurierbare Genehmigungsschritte, Approve / Reject / Reset, Verlaufsprotokoll |
| **Genehmigungen** | Standalone-Route `/member/jobs/approvals` + Inline-Dashboard; Admins + zugewiesene Genehmiger |
| **Benefits** | Gruppierbarer Katalog; Department-spezifische Zuweisung; Seeding mit Standard-Einträgen |
| **Anforderungen** | Anforderungs-Katalog mit 60+ voreingestellten Einträgen in 8 Kategorien; Picker im Generator |
| **Abteilungen** | Départements anlegen, Benefits und Anforderungen pro Abteilung einschränken |
| **Public Listing** | Route `/jobs` → öffentliche Übersicht aller freigegebenen Stellen (`show_in_listing = 1`) |
| **Bewerbungen** | Bewerbungs-Postfach für Stelleninhaber; CV-Download via sicherem Token |
| **Bibliotheken** | Benefit-Katalog, Skill-Matrix, Job-Kategorien, Textbausteine, PDF-Templates |
| **Admin** | Vollständiger Admin-Bereich: Übersicht, Workflow-Editor, Genehmigungen, Bibliotheken, Statistiken, Audit-Log |

---

## Installation

Das Plugin liegt unter `PLUGINS/cms-jobprofile-generator/` und wird über den Plugin-Manager von 365CMS aktiviert.  
Bei Aktivierung führt `CMS_JPG_Installer::install()` automatisch aus:

1. Datenbank-Tabellen anlegen / migrieren
2. System-Daten seeden (Kategorien, Benefits, Skills, Textbausteine, Templates, Anforderungs-Katalog)
3. Routing registrieren

---

## Routing

### Member (eingeloggt)

| Methode | Pfad | Beschreibung |
|---|---|---|
| GET | `/member/jobs` | Eigene Stellenanzeigen-Liste |
| GET/POST | `/member/jobs/create` | Neue Stelle anlegen |
| GET/POST | `/member/jobs/edit/:id` | Stelle bearbeiten |
| GET/POST | `/member/jobs/approvals` | Genehmigungsbereich |
| GET | `/member/jobs/applications` | Bewerbungs-Postfach |
| GET | `/member/jobs/download/:token` | Bewerbungs-Anhang herunterladen |
| GET/POST | `/member/jobs/settings` | Unternehmens-Einstellungen (Departments, Benefits) |
| POST | `/member/jobs/workflow/submit/:id` | Workflow-Submit |

### Public

| Methode | Pfad | Beschreibung |
|---|---|---|
| GET | `/jobs` | Öffentliche Stellenanzeigen-Übersicht |

### Admin

Alle Admin-Seiten unter `/admin/plugins/jpg-dashboard/`:
- `jpg-overview` – Übersicht
- `jpg-jobs` – Alle Stellenanzeigen
- `jpg-approvals` – Ausstehende Genehmigungen
- `jpg-workflow` – Workflow-Editor
- `jpg-libraries` – Bibliotheken (Benefits, Skills, Kategorien, Textbausteine)
- `jpg-stats` – Statistiken & Charts
- `jpg-audit` – Audit-Log

---

## Datenbank-Schema (v5)

| Tabelle | Beschreibung |
|---|---|
| `jpg_profiles` | Stellenanzeigen |
| `jpg_tasks` | Aufgaben-Einträge pro Stelle |
| `jpg_requirements` | Anforderungen pro Stelle |
| `jpg_benefits` | Benefits pro Stelle |
| `jpg_applications` | Bewerbungen |
| `jpg_workflow_steps` | Konfigurierte Workflow-Schritte |
| `jpg_workflow_history` | Genehmigungsverlauf |
| `jpg_benefits_catalog` | Globaler Benefits-Katalog |
| `jpg_requirement_items` | Anforderungs-Katalog (vorbelegt) |
| `jpg_skill_matrix` | Skill-Bibliothek |
| `jpg_job_categories` | Job-Kategorien |
| `jpg_text_modules` | Wiederverwendbare Textbausteine |
| `jpg_templates` | PDF-Vorlagen |
| `jpg_departments` | Abteilungen pro Unternehmen |
| `jpg_department_benefits` | Benefits ↔ Abteilung (m:n) |
| `jpg_department_requirements` | Anforderungen ↔ Abteilung (m:n) |
| `jpg_admin_access_log` | Audit-Log für Admin-Zugriffe |

---

## Anforderungs-Katalog (Seed-Daten)

Beim ersten Start werden über 60 Einträge in folgenden Gruppen angelegt:

- IT & Programmierung (PHP, JS, Python, SQL, REST, Git, Docker, Cloud, Linux, CI/CD)
- Sprachen (Deutsch, Englisch C1, Englisch B2, Fremdsprache)
- Soft Skills (Teamfähigkeit, Kreativität, Kommunikation, Struktur, …)
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
- User mit aktuell ausstehenden Genehmigungen

---

## Changelog

Siehe [CHANGELOG.md](CHANGELOG.md).
