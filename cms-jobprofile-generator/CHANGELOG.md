# Changelog – cms-jobprofile-generator

Alle wesentlichen Änderungen sind in dieser Datei dokumentiert.  
Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/).

---

## [0.9.3] – 2026-02-26

### Geändert
- **Admin-Controller-Split:** `admin/class-admin-pages.php` in 10 Trait-Dateien unter `admin/modules/` aufgeteilt. Controller-Shell auf 137 Zeilen reduziert.
- **Member-Controller-Split:** `includes/class-member-controller.php` in 7 Trait-Dateien unter `includes/member/` aufgeteilt. Controller-Shell auf 116 Zeilen reduziert.

### Behoben
- **CSRF-Bug Plugin-Rollen-Admin:** Nonce-Generierung in `trait-page-subscription.php` und `trait-page-users.php` nach POST-Handler verschoben. Vorher überschrieb `CMS\Security::generateToken()` den Session-Token vor der Verifikation → jeder Speichern-Klick scheiterte mit „Sicherheitscheck fehlgeschlagen".

---

## [0.9.2] – 2026-02

### Hinzugefügt
- **Departments-Accordion (Member + Admin)** — Departments-Tab in Firmen-Einstellungen zeigt Abteilungen als inline Accordion ohne Page-Reload. `jpgMemberToggleDept()` / `jpgAdminToggleDept()` JS-Funktionen. Jede Abteilung zeigt Benefits + Anforderungen als vorausgefüllte Checkboxen.
- **Jobs-Seite Konfiguration** — Neuer Tab „Jobs-Seite" in Member Firmen-Einstellungen. Felder: Seitentitel, Intro-Text, Kontakt-E-Mail, Gehalt anzeigen (Toggle), Seite aktivieren, URL. POST-Handler in `class-member-controller.php` und `class-departments.php` (`get_company_settings()` / `save_company_settings()`).
- **Admin: Jobs-Seite Tab in Firmen-Übersicht** — `admin/views/page-company-overview.php` mit vollständigem Konfig-Formular.

### Behoben
- **TypeError: Argument #1 ($v) must be of type string, null given** — Mit `declare(strict_types=1)` und `fn(string $v)` verwarf PHP 8 alle null-Werte aus DB-Spalten (z. B. `$benefit->name`, `$cat->icon`). Alle 12 betroffenen Closures in Views und `class-export.php` auf `fn(string|null $v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES)` geändert.
- **Doppeltes Formular in `page-jobs-create.php`** — Das alte einfache Erstellungsformular (ehemalige Zeilen 469–566) wurde beim Einbau des 5-Tab-Wizards nicht entfernt und renderte doppelt. Duplikat entfernt.

### Geändert
- **Version:** `0.9.1` → `0.9.2`

---

## [0.9.1] – 2026-02-26

### Hinzugefügt
- **Standalone-Route `/member/jobs/approvals`** — Genehmigungsbereich ist jetzt über eine eigene URL erreichbar (GET + POST). Zugriff für Admins und zugewiesene Genehmiger.
- **`render_approvals()` Standalone-Methode** — Vollwertige Standalone-Version von `render_approvals_inline()` mit Auth-Check, Workflows und Render-with-Layout.
- **Genehmigungs-Menüeintrag** — `add_menu_item()` fügt für Admins und berechtigte Genehmiger automatisch den Eintrag „✅ Genehmigungen" (`/member/jobs/approvals`) in die Plugin-Sidebar ein.
- **Anforderungs-Katalog vorbelegt** — `seed_requirement_items()` wird bei Erstinstallation aufgerufen und befüllt die Tabelle `jpg_requirement_items` mit über 60 Einträgen in 8 Kategorien: IT & Programmierung, Sprachen, Soft Skills, Ausbildung & Qualifikation, Kaufmännisch, Tools & Software, Führung & Projektmanagement, Marketing & Vertrieb, Persönlichkeit.
- **Einklappbare Gruppen im Generator** — Benefits-Tab: Gruppen-Karten mit klickbarem Titel zum Ein-/Ausklappen (`▼/▶`). JS-Funktion `jpgToggleGroup()` in `page-generator.php`.
- **Einklappbare Gruppen in Bibliotheken** — Benefit-Katalog und Anforderungs-Katalog in `page-libraries.php` verwenden denselben `jpgToggleGroup()` Mechanismus.
- **Öffentliche Stellenanzeigen-Seite** — Route `/jobs` + `render_jobs_list()` + View `views/public/jobs-list.php` für die öffentliche Ansicht aller freigeschalteten Stellen.
- **`show_in_listing`-Toggle** — Neue Spalte in `jpg_profiles`; Stellen können gezielt aus der öffentlichen Listing-Ansicht ausgeblendet werden. Toggle in Generator + Admin-Listenansicht.

### Behoben
- **DB-Fehler `c.company_name`** — Alle SQL-Abfragen verwendeten fälschlicherweise `c.company_name` statt `c.name AS company_name` (Spalte in `companies`-Tabelle heißt `name`). Behoben in 5 Stellen: `admin/class-admin-pages.php` (2×), `includes/class-member-controller.php` (3×).
- **Admin sieht im Member-Dashboard keine Profile** — Ursache war der DB-Exception durch `c.company_name`-Fehler, der `$profiles = []` zurückgab. Durch DB-Fix behoben.
- **404 bei „Neue Stelle erstellen"** — `$createUrl` Variable fehlte in `render_list()`, was zu einem fehlerhaften Link führte.
- **PDF/Export: „Headers already sent"** — `ob_start()` in Bootstrap + `ob_end_clean()` in beiden AJAX-Handler-Pfaden verhindert Ausgabe-Konflikte vor Header-Ausgabe.

### Geändert
- **DB-Schema v5** → `maybe_add_columns()` migriert ggf. fehlende `show_in_listing`-Spalte.

---

## [0.5.0] – 2026-02-25

### Hinzugefügt
- **Admin: Untermenü „Genehmigungen"** — Ausstehende Stellenanzeigen-Genehmigungen werden jetzt als eigener Admin-Untermenüpunkt (`jpg-approvals`) geführt und nicht mehr im Workflow-Editor angezeigt.
- **Admin: `render_approvals()` + `page-approvals.php`** — Neue, eigenständige Seite mit Stats-Zeile (Anzahl ausstehend, Workflow-Stufen, Rollen), vollständiger Tabelle mit Dot-Progress-Visualisierung und Approve/Reject/Reset-Modal inkl. Notizfeld.
- **Member: 5-Tab-Wizard Stellenanzeige bearbeiten** — `views/member/page-jobs-edit.php` vollständig auf Tab-Struktur umgestellt: *Basisdaten*, *Aufgaben*, *Anforderungen*, *Benefits*, *Workflow*.
  - Neu in Basisdaten: `salary_min`, `salary_max`, `experience_level`, ausführliche `description`-Textarea.
  - Neu: Benefits-Tab mit Checkbox-Grid aus `$allBenefits`.
  - Workflow-Tab: vollständige Schritt-Visualisierung von Entwurf→Schritte→Veröffentlicht.
  - Gesperrt-Modus: bei Status `pending`/`approved` sind alle Felder readonly und ein Info-Banner erscheint.
- **Member: 5-Tab-Wizard Stellenanzeige anlegen** — `views/member/page-jobs-create.php` vollständig neu als 5-Tab-Wizard: *Basisdaten*, *Aufgaben* (Drag&Drop), *Anforderungen*, *Benefits*, *Workflow*.
- **Member: Genehmiger-Dashboard** — Approver sehen im PluginDashboard jetzt den Eintrag „✅ Genehmigungen" (`member-job-approvals`) mit den auf sie wartenden Stellenanzeigen, Approve/Reject-Modal und Verlaufsanzeige.
  - `render_approvals_inline()` in `class-member-controller.php` verarbeitet Approve/Reject/Reset via `CMS_JPG_Workflow`.
  - Neue View `views/member/page-approvals.php`.
  - Eintrag erscheint nur für Admins und User mit tatsächlich ausstehenden Genehmigungen.
- **Controller: `save_profile_post()`** — Speichert nun auch `requirements` (`req_text[]`/`req_type[]`) via `CMS_JPG_Profiles::save_requirements()` und `benefits` (`benefit_ids[]`) via `CMS_JPG_Profiles::save_benefits()`.
- **Controller: `render_create_inline()` / `render_create()`** — Übergeben jetzt `$allBenefits`, `$tasks=[]`, `$requirements=[]`, `$benefitIds=[]` an die Create-View.
- **Controller: `render_edit_inline()`** — Übergibt jetzt `$wfCsrf` an die Edit-View.

### Geändert
- **Admin: `page-workflow-editor.php`** — Der Block „Ausstehende Genehmigungen" wurde entfernt; stattdessen ein Link-Hinweis auf die neue Genehmigungen-Seite.
- **Admin: `handle_workflow_post()`** — Approve/Reject/Reset-Fälle in ein separates `handle_approvals_post()` ausgelagert.

### Behoben
- Doppelter Member-Dashboard-Menüeintrag für Stellenanzeigen (aus vorheriger Session).

---

## [0.4.0] – 2026-02-25

### Hinzugefügt
- Workflow-Engine (`CMS_JPG_Workflow`) mit konfigurierbaren Genehmigungsschritten
- Workflow-Editor im Admin (`jpg-workflow`)
- Workflow-Submit-Route für Member (`/member/jobs/workflow/submit/:id`)
- Verlaufseinträge (`jpg_workflow_history`)
- Benefits-Katalog (`CMS_JPG_BenefitsCatalog`) mit gruppierten Benefits
- Job-Kategorien (`CMS_JPG_JobCategories`)
- Bewerbungs-Postfach für Member (`/member/jobs/applications`)
- PluginDashboardRegistry-Integration (Sidebar-Eintrag ohne Menü-Duplikat)
- GDPR: Daten-Export-Hook und Account-Löschung-Hook

### Geändert
- DB-Schema v4: Tabellen `jpg_workflow_steps`, `jpg_workflow_history`, `jpg_benefits`, `jpg_job_categories`

---

## [0.3.x] – 2026-02-25

Initiale Plugin-Struktur, Grundfunktionen für Job-Profile, Bewerbungen, Member-Routing, Admin-Grundseiten.
