# Changelog – cms-jobprofile-generator

Alle wesentlichen Änderungen sind in dieser Datei dokumentiert.  
Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/).

---

## [3.0.0] – 2026-05-17

### Sicherheitsfixes

- DOM-XSS-Sinks in Admin-/Member-Views und `jobprofile-admin.js` durch DOM-API (`createElement`, `textContent`, `append`, `replaceChildren`) ersetzt.
- Bewerbungsanschreiben im Member-Modal werden als Text gerendert statt als HTML in den DOM geschrieben.
- Asset-URL-Ausgaben in der Plugin-Hauptdatei nutzen nun explizites Attribute-Escaping mit `ENT_QUOTES` und UTF-8.

### Geändert

- Plugin-Version und Update-Metadaten auf 365CMS 3.0.0 aktualisiert.

## [Unreleased] – 2026-04-04

### Sicherheitsfixes

- Member-Create-Views nutzen jetzt serverseitig vorbereitete, sanitierte Prefill-Daten statt requestnaher Formwerte.
- Design-/Settings-Upserts wurden auf Prepared Statements umgestellt.
- Öffentliche Fehlerausgaben in Admin- und Member-Flows geben keine Roh-Exceptions mehr aus und loggen intern stattdessen generische Fehlermeldungen.

## [0.9.6] – 2026-02-27

### Behoben
- **Member-Dashboard Einstellungen: Tabs „Team-Genehmiger", „Abteilungen", „Jobs-Seite" und „E-Mail Vorlagen" zeigen keinen Inhalt** — Die vier Tab-Blöcke in `views/member/page-company-settings.php` waren als eigenständige `if ($activeTab === 'xxx'):`-Blöcke angelegt, aber strukturell *innerhalb* des `elseif ($activeTab === 'benefits'):`-Zweigs verschachtelt (kein trennendes `endif;` nach dem Benefits-Formular). Sobald ein anderer Tab als `info` oder `benefits` aktiv war, schlug die äußere `elseif`-Bedingung fehl und die gesamten Inhalte blieben ungerendert – sichtbar war nur die Statistik-Leiste mit dem „➕ Neue Stelle"-Button. Fix: Alle vier Blöcke wurden in `elseif`-Zweige derselben Kette umgewandelt (`if info / elseif benefits / elseif team / elseif departments / elseif jobs-page / elseif email-templates / endif`).
- **Member-Dashboard Einstellungen: Firmendaten wurden nicht geladen** — `render_settings_inline()` prüfte, ob `cms-companies` in `PluginManager::getActivePlugins()` enthalten ist. In Umgebungen, in denen `CMS/plugins/` leer ist oder das Plugin nicht in der `{prefix}settings`-Tabelle als aktiv eingetragen ist, blieb `$company = null` → alle Tabs zeigten den Empty-State „Kein Firmenprofil". `render_company_inline()` hatte diesen Guard nie → daher funktionierte nur die Firmenübersicht. Fix: PluginManager-Check entfernt; Firma wird analog zu `render_company_inline()` direkt per DB-Query geladen.
- **Plugin-Sektionen im Member-Dashboard ungestylt** — `CMS/member/plugin-section.php` lud nur `main.css` + `member.css`. Der Settings-View nutzt `.admin-card`, `.form-control`, `.btn`, `.tab-btn`, `.alert` aus `admin.css` → Formulare und Cards waren vollständig ungestylt. `admin.css?v=20260222b` ergänzt.

### Geändert
- **Version:** `0.9.5` → `0.9.6`
- **`views/member/page-company-settings.php`:** `if/elseif`-Kette für alle 6 Tabs korrigiert.
- **`includes/member/trait-member-settings.php`:** PluginManager-Guard in `render_settings_inline()` entfernt.
- **`CMS/member/plugin-section.php` (Core):** `admin.css` in `<head>` ergänzt.

---

## [0.9.5] – 2026-02-28

### Behoben
- **Benutzer & Mandanten: Modal öffnet sich weiterhin nicht** — `jpgOpenModal()` und `jpgCloseModal()` waren ausschließlich in `jobprofile-admin.js` definiert, das mit `defer`-Attribut erst *nach* dem vollständigen HTML-Parsing ausgeführt wird. Das Inline-`<script>` in `page-users.php` lief jedoch sofort beim Parsen, sodass beim Klick auf „Bearbeiten" (vor dem defer-Ladeabschluss) ein `ReferenceError: jpgOpenModal is not defined` entstand und das Modal sich nie öffnete. Fix: `jpgOpenModal`, `jpgCloseModal` sowie die Backdrop-click- und Escape-Handler werden jetzt direkt im Inline-Script von `page-users.php` definiert.
- **AJAX-Fallback auf nativen POST** — Wenn `jpgSubmitUserAction` eine Nicht-JSON-Antwort (z. B. 404-HTML bei nicht registrierter Route) oder einen Netzwerkfehler erhält, fällt der Code nun auf einen nativen `formEl.submit()` zurück, sodass der bereits funktionierende POST-Handler in `render_users()` greift.
- **Ausgabepuffer in `handle_users_ajax()`** — `ob_end_clean()` räumt etwaige vorherige Ausgaben auf, bevor der `Content-Type: application/json`-Header gesetzt wird. Verhindert „headers already sent"-Warnungen und kaputte JSON-Antworten.
- **CSRF-Reload-Fallback** — Schlägt der CSRF-Check im AJAX-Endpoint fehl (z. B. abgelaufene Session), erhält der Benutzer im Modal die Meldung „Sicherheitscheck fehlgeschlagen → Seite wird neu geladen" und die Seite lädt nach 2 s automatisch nach.

### Geändert
- **Version:** `0.9.4` → `0.9.5`
- **`admin/views/page-users.php`:** Modal-Helfer inline ergänzt; `jpgSubmitUserAction` mit JSON-Parse-Fehlerbehandlung und nativem POST-Fallback robuster gemacht.
- **`admin/modules/trait-page-users.php`:** `ob_end_clean()` am Anfang von `handle_users_ajax()`.

---

## [0.9.4] – 2026-02-27

### Behoben
- **Benutzer & Mandanten: „Bearbeiten"-Button wirkungslos** — Alle Aktionen im Bearbeitungs-Modal (Rolle setzen, Unternehmen zuweisen/anlegen) erfolgten per normaler HTML-Formular-POST. Da `renderPluginPage()` den HTML-Layout-Header *vor* dem Plugin-Callback rendert, war ein PHP-Redirect nach dem Speichern unmöglich → Modal schloss sich, Erfolgs-/Fehlermeldung erschien oben auf der Seite und wurde übersehen. Benutzer glaubten, die Aktion sei fehlgeschlagen.
- **Firmen-Dropdown nur für unzugewiesene Firmen** — Die Abfrage `WHERE user_id IS NULL OR user_id = 0` ließ alle bereits vergebenen Firmen aus dem Dropdown verschwinden. Das gesamte „Vorhandenes Unternehmen zuweisen"-Formular war nicht sichtbar, sobald alle Firmen einem Benutzer zugewiesen waren. Benutzer konnten kein Unternehmen neu zuweisen, nur neue anlegen.
- **Verwaister `</div>` am Ende von `page-users.php`** — Ein abschließender `</div>` nach dem `</script>`-Tag schloss `<div class="admin-content">` des Layouts vorzeitig. `renderAdminLayoutEnd()` erzeugte dadurch ein weiteres ungematchtes `</div>` → ungültiges HTML.

### Hinzugefügt
- **AJAX-Endpoint `POST /api/jpg/admin/users-action`** — Neue Route, registriert via `register_admin_ajax_routes()` in `CMS_JobProfileGenerator::init_hooks()`. Alle Modal-Aktionen (set_role, assign_company, create_company) werden jetzt per AJAX verarbeitet und liefern JSON zurück.
- **Inline-Feedback im Modal** — Neues `#jpgModalFeedback`-Element im Modal zeigt Erfolgs- oder Fehlermeldung direkt im Modal ohne Seiten-Reload (grünes `alert-success` / rotes `alert-error`).
- **Sofortige Tabellen-Aktualisierung ohne Reload** — Nach einem erfolgreichen Rollen-Save wird der `status-badge` des betreffenden Benutzers in der Tabelle per JS direkt aktualisiert. Bei Firmenzuweisung wird die Firmen-Spalte sofort befüllt.
- **Alle Firmen im Dropdown** — `$unassignedCompanies`-Query lädt jetzt via `LEFT JOIN {prefix}users` alle Firmen mit Angabe des aktuell zugewiesenen Benutzers. Eine Neuzuweisung ist damit für jede vorhandene Firma möglich.

### Geändert
- **Version:** `0.9.3` → `0.9.4`
- **`cms-jobprofile-generator.php`:** `register_admin_ajax_routes()`-Methode + Hook-Registrierung.
- **`admin/modules/trait-page-users.php`:** Firmen-Query erweitert; `handle_users_ajax()` als neuer `public static`-Endpoint ergänzt.
- **`admin/views/page-users.php`:** Script-Section auf AJAX umgestellt; Feedback-Div; Data-Attribute auf Role-Badge und Company-Zelle; Firma-Dropdown immer sichtbar.

---

## [0.9.3] – 2026-02-26

### Geändert
- **Admin-Controller-Split:** `admin/class-admin-pages.php` in 10 Trait-Dateien unter `admin/modules/` aufgeteilt. Controller-Shell auf 137 Zeilen reduziert.
- **Member-Controller-Split:** `includes/class-member-controller.php` in 7 Trait-Dateien unter `includes/member/` aufgeteilt. Controller-Shell auf 116 Zeilen reduziert.

### Behoben
- **CSRF-Bug Plugin-Rollen-Admin:** Nonce-Generierung in `trait-page-subscription.php` und `trait-page-users.php` nach POST-Handler verschoben. Vorher überschrieb `CMS\Security::generateToken()` den Session-Token vor der Verifikation → jeder Speichern-Klick scheiterte mit „Sicherheitscheck fehlgeschlagen".
- **Standalone-Routen ohne CSS:** `render_with_layout()` (`trait-member-jobs.php`) und `output_settings_with_layout()` (`trait-member-settings.php`) luden nur `main.css` + `member.css`. Alle Member-Views verwenden jedoch `.admin-card`, `.btn`, `.form-control`, `.tab-btn`, `.alert` aus `admin.css` → Standalone-Routen (`/member/jobs`, `/member/jobs/create`, `/member/jobs/edit/:id`, `/member/settings`) waren vollständig ungestylt. `admin.css?v=20260222b` in beiden Layout-Funktionen ergänzt.
- **CSRF-Bug Jobs-Seite Tab:** Der POST-Handler für `settingsTab === 'jobs-page'` prüfte `verify_token('member_company_departments')`, das Formular sendete aber den Token für `'member_company_settings'`. Jedes Speichern der Jobs-Seiten-Konfiguration scheiterte silent mit „Sicherheitscheck fehlgeschlagen". Fix: Korrekter Token-Name `'member_company_settings'`.
- **Datenverlust E-Mail-Templates beim Jobs-Seite-Speichern:** `save_company_settings()` führt einen UPSERT über alle 11 Felder aus. Der Jobs-Seite-Handler übergab keine Template-Felder → gespeicherte E-Mail-Vorlagen wurden auf NULL zurückgesetzt. Fix: Bestehende Einstellungen werden vor dem UPSERT per `get_company_settings()` geladen und die Template-Felder bewahrt.
- **Admin kann fremde Profile nicht bearbeiten/als PDF laden:** `load_own_profile()` filterte immer `AND created_by = ?`, auch für Admins. `render_list_inline()` zeigte Admins alle Profile, aber Klick auf ✏️ Bearbeiten lieferte „Profil nicht gefunden". Fix: Admins bypassen den Ownership-Filter.
- **CV-Download-Pfad-Bug:** `handle_cv_upload()` speicherte den absoluten Systempfad in der DB. `download_file()` baute daraus via `$uploadsBase . ltrim($filePath, '/')` einen doppelten Pfad (z. B. `/uploads/var/www/html/uploads/applications/…`) → alle CV-Downloads schlugen mit 404 fehl. Fix: `handle_cv_upload()` speichert jetzt den relativen Pfad (`applications/<token>.ext`).
- **Admin kann fremde Bewerbungen nicht sehen/verwalten:** `render_applications()`, `ajax_update_status()`, `download_file()` und `render_applications_inline()` in `trait-member-applications.php` filterten Bewerbungen immer per `p.created_by = ?`, auch für Admins. Fix: Admins bypassen den Ownership-Filter in allen vier Stellen.
- **`fix_active_menu_states()`: falsch-positiver aktiver Zustand bei `/pdf/`, `/duplicate/`, `/workflow/`:** Diese Routen geben PDF-Binary bzw. Redirect aus (kein HTML), wurden aber in `$isMainJobs` nicht ausgeschlossen → theoretisch wäre „Meine Stellen" als aktiv markiert. Fix: `$isPdf`, `$isDuplicate`, `$isWorkflow` aus `$isMainJobs` ausgeschlossen.
- **Admin-Bereich: Jobs-Seite löscht E-Mail-Templates (selbes Muster wie Bug 4):** `trait-page-companies.php` Handler `save_jobs_url` / `save_jobs_page_settings` übergab nur 6 Felder an `save_company_settings()` → alle 5 E-Mail-Template-Felder wurden bei jedem Admin-Speichervorgang auf NULL gesetzt. Fix: Bestehende Einstellungen laden und Template-Felder bewahren.
- **Admin-Bereich: Jobs-Seite POST-Felder unsanitiert:** Gleiche 6 Felder wurden ohne `sanitize_text_field()` / `filter_var()` gespeichert. Fix: Sanitierung ergänzt.

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
