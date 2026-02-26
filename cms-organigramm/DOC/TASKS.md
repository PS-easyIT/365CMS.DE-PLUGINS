Hier ist die vollständige und detaillierte Master-Projektliste für das neue **CMS Organigramm Plugin (`cms-organigramm`)**.

Diese Task-Liste ist speziell darauf ausgelegt, die strengen 365CMS-Architekturvorgaben und die lose gekoppelte Integration der drei Fremd-Plugins sicherzustellen.

---

# 🚀 Master-Projektliste: CMS Organigramm

## Phase 1: Fundament, Architektur & Datenbank

* [ ] **1.1 Plugin-Grundgerüst**
* [ ] Datei `plugins/cms-organigramm/cms-organigramm.php` mit offiziellem Plugin-Header anlegen.
* [ ] Singleton-Klasse `class-plugin.php` im Namespace `CMS365\Plugins\Organigramm` erstellen.
* [ ] `plugins_loaded` Hook zur Initialisierung verwenden.


* [ ] **1.2 Datenbank-Design (Activation Hook)**
* [ ] `cms_organigramm_charts` (id, user_id, title, status, created_at, updated_at) anlegen.
* [ ] `cms_organigramm_nodes` (id, chart_id, parent_id, reference_type, reference_id, custom_label, custom_role, sort_order) anlegen. *(`reference_type` ENUM: 'company', 'expert', 'job_profile', 'custom')*
* [ ] Tabellen-Präfix über `CMS\Database::instance()->getPrefix()` auslesen.


* [ ] **1.3 Asset-Struktur**
* [ ] Verzeichnisse `assets/css/` und `assets/js/` anlegen.
* [ ] `builder.js` (Visual Drag&Drop Editor) und `builder.css` (CSS Custom Properties) anlegen.
* [ ] Hooks `wp_head` und `wp_footer` für das dynamische Laden der Skripte nur auf Organigramm-Admin-Seiten konfigurieren.



## Phase 2: Core-Integration & Abo-System

* [ ] **2.1 Subscription-Limits & Features**
* [ ] Limit `limit_organigramms` und `limit_organigramm_nodes` zur Tabelle `cms_subscription_plans` per Migration hinzufügen.
* [ ] Premium-Features `feature_organigramm_export` (PDF/SVG) und `feature_organigramm_branding` hinzufügen.
* [ ] Helper-Aufruf `update_resource_usage('organigramms', ...)` bei Chart-Erstellung integrieren.


* [ ] **2.2 Security & RBAC**
* [ ] Admin-Routen mit `CMS\Auth::instance()->isAdmin()` absichern.
* [ ] Plugin-Zugriff via `user_can_access_plugin('cms-organigramm')` prüfen.
* [ ] Warnungen via `display_resource_limit_warning()` ausgeben, wenn das Node-Limit im Visual Builder erreicht wird.



## Phase 3: Admin-Backend

### 3.1 Menüpunkt 1: Dashboard (`OrganigrammDashboard.php`)

* [ ] **Tab 1: Übersicht** – KPI-Kacheln (Erstellte Charts, Gesamt-Nodes, Verknüpfte Experten/Jobs).
* [ ] **Tab 2: Alle Organigramme** – Data-Table (Vanilla JS) mit Liste aller Charts.
* [ ] **Tab 3: Entwürfe** – Übersicht unveröffentlichter Strukturen.
* [ ] **Tab 4: Papierkorb** – Soft-Deletes mit Wiederherstellen-Funktion.
* [ ] **Tab 5: Statistiken** – Auswertung der Fronted-Aufrufe (Views) via `CMS\Services\AnalyticsService`.

### 3.2 Menüpunkt 2: Visual Builder (`OrganigrammBuilder.php`)

* [ ] **Tab 1: Canvas (Visual Editor)** – Kern-Feature: Vanilla JS Canvas für Nodes. Native Drag&Drop HTML5 API für Positionierung und Parent-Zuweisung.
* [ ] **Tab 2: Nodes verwalten** – Sidebar-Menü zur manuellen Bearbeitung der angeklickten Nodes (Titel, Typ, Farbe).
* [ ] **Tab 3: Hierarchie-Listenansicht** – Fallback: Baumstruktur als klassische `<ul>/<li>` Liste mit Edit-Buttons.
* [ ] **Tab 4: Layout-Settings** – Auswahl des Render-Algorithmus (Top-Down, Bottom-Up, Left-to-Right) via Vanilla JS.
* [ ] **Tab 5: Live-Preview** – Vorschau, wie das Chart auf der öffentlichen Seite via Shortcode aussehen wird.

### 3.3 Menüpunkt 3: Datenquellen & Sync (`OrganigrammSync.php`)

* [ ] **Sicherheits-Check:** Bei allen Tabs `CMS\PluginManager::instance()->isPluginActive(...)` nutzen.
* [ ] **Tab 1: Companies Sync** – Suchmaske für `cms_companies`, um Firmen als Root-Nodes hinzuzufügen.
* [ ] **Tab 2: Experts Sync** – Suchmaske für `cms_experts`, um Mitarbeiter in das Chart zu ziehen.
* [ ] **Tab 3: Vacant Jobs Sync** – Suchmaske für `cms_job_profiles`, um Vakanzen anzuzeigen (z.B. als gestrichelte Boxen).
* [ ] **Tab 4: Manuelle Nodes** – Erstellen von Nodes ohne Plugin-Verknüpfung (z.B. externe Berater).
* [ ] **Tab 5: Sync-Logs** – Protokollierung, wenn referenzierte Experten/Jobs im Original-Plugin gelöscht wurden (Broken-Link Detektor).

### 3.4 Menüpunkt 4: Design & Export (`OrganigrammDesign.php`)

* [ ] **Tab 1: Theme-Presets** – 3-4 vorgefertigte CSS-Themes für das Organigramm (Modern, Classic, Flat).
* [ ] **Tab 2: Node-Styling** – Farbauswahl für verschiedene Node-Typen (Expert = Blau, Vacancy = Rot) inkl. `user_has_feature('organigramm_branding')` Check.
* [ ] **Tab 3: Typografie** – Systemschrift-Auswahl für Node-Labels.
* [ ] **Tab 4: Export-Settings** – Konfiguration für PDF- oder SVG-Export (gated via `organigramm_export`).
* [ ] **Tab 5: Embed-Codes** – Übersicht der generierten Shortcodes (z.B. `[cms_organigramm id="1"]`).

### 3.5 Menüpunkt 5: Einstellungen (`OrganigrammSettings.php`)

* [ ] **Tab 1: Allgemein** – Standard-Layout-Ausrichtung, Default-Zoom-Level.
* [ ] **Tab 2: Berechtigungen** – Zuweisung, welche `cms_roles` Organigramme bauen dürfen.
* [ ] **Tab 3: Caching** – Konfiguration der Cache-TTL (Time-to-Live) für das Frontend via `CMS\CacheManager`.
* [ ] **Tab 4: Limit-Warnungen** – E-Mail-Setup für Limit-Erreichung.
* [ ] **Tab 5: System-Info** – Check der Vanilla-JS Kompatibilität und Tabellen-Größen.

## Phase 4: Der Visual Builder (Frontend des Backends)

* [ ] **4.1 Vanilla JS Data Model**
* [ ] Implementierung einer Tree-Data-Structure in JS zur Haltung des State in-memory.


* [ ] **4.2 SVG / DOM Rendering**
* [ ] Dynamisches Zeichnen der Verbindungslinien (Edges) zwischen den DOM-Elementen (Nodes) via `<svg>` oder HTML Canvas.
* [ ] Zoom & Pan (Verschieben der Arbeitsfläche) mit Vanilla JS Event-Listenern (`wheel`, `mousedown`, `mousemove`).


* [ ] **4.3 Auto-Save via AJAX**
* [ ] Event-Listener auf Node-Drop, der via `fetch()` das JSON-Objekt an `/api/organigramm/autosave` sendet.
* [ ] Zwingend `CMS\Security::instance()->generateNonce()` Header mitgeben und im Backend mit `verifyNonce()` prüfen.



## Phase 5: Frontend-Ausgabe (Der Shortcode)

* [ ] **5.1 Shortcode-Registrierung**
* [ ] `CMS\Hooks::addFilter('page_content', ...)` nutzen, um `[cms_organigramm id="x"]` zu parsen und zu ersetzen.


* [ ] **5.2 Dynamisches Node-Rendering**
* [ ] Wenn `reference_type == 'expert'`, lade dynamisch Bild und Name aus `cms_experts`.
* [ ] Wenn `reference_type == 'job_profile'`, baue dynamisch einen Link zur Job-Detailseite (`/jobs/:slug`) ein.
* [ ] Ausgabe zwingend durch `CMS\Security::instance()->escapeOutput()` leiten.


* [ ] **5.3 Caching & Performance**
* [ ] Da komplexe Hierarchien viele Queries benötigen: Den fertigen HTML/SVG-String via `CMS\CacheManager::instance()->remember()` cachen.

## Phase 6: Member-Bereich & Öffentliches Frontend

* [ ] **6.1 Member-Routing & Controller (`class-member.php`)**
* [ ] `CMS\Hooks::addAction('routes_registered', ...)` nutzen, um die Routen `/member/organigramm` (Übersicht) und `/member/organigramm/edit/:id` (Editor) zu registrieren.
* [ ] Auth-Check in den Callbacks: `if (!CMS\Auth::instance()->isLoggedIn()) { redirect('/login'); exit; }`.
* [ ] Controller-Logik aufbauen, die nur Organigramme des aktuell eingeloggten Users lädt (Prepared Statement: `WHERE user_id = ?`).


* [ ] **6.2 Member Sidebar & Dashboard Widgets**
* [ ] Hook `CMS\Hooks::addFilter('member_menu_items', ...)` nutzen, um "Meine Organigramme" im Sidebar-Menü unter der Kategorie `plugins` anzuzeigen.
* [ ] Hook `CMS\Hooks::addFilter('member_dashboard_widgets', ...)` nutzen, um auf `/member` eine KPI-Kachel ("Du hast X aktive Organigramme") zu rendern.


* [ ] **6.3 Subscription & Limit-Prüfungen (Strict)**
* [ ] Menüpunkt und Route komplett blockieren, falls `!user_can_access_plugin('cms-organigramm')`.
* [ ] In der Ansicht `/member/organigramm` die Warnung `display_resource_limit_warning('organigramms', 'Organigramme')` rendern.
* [ ] Den "Neues Organigramm erstellen"-Button ausblenden oder serverseitig blockieren, falls `!user_can_create_resource('organigramms')`.


* [ ] **6.4 Formular-Sicherheit im Member-Bereich**
* [ ] Auto-Save und Formulare mit `CMS\Security::instance()->generateNonce('member_org_save')` versehen.
* [ ] Validierung jedes Speicher-Requests via `verifyNonce()`.
* [ ] XSS-Schutz: Template-Ausgaben (Titel der Charts) mit `htmlspecialchars()` oder `$security->escapeOutput()` sichern.


* [ ] **6.5 Öffentliche Ansicht (`/org/:slug`)**
* [ ] Route `/org/:slug` (ohne Member-Auth-Check) registrieren.
* [ ] Zugriffs-Check: Nur rendern, wenn das Chart in der DB den Status `published` hat.
* [ ] Clean HTML5-Template laden (ohne CMS-Theme-Header/Footer), um eine Fullscreen-Canvas-Ansicht zu gewährleisten.


* [ ] **6.6 Premium-Feature Gating (Public View)**
* [ ] Logik: Lade die `user_id` des Erstellers des aufgerufenen Organigramms.
* [ ] Prüfe `user_has_feature('organigramm_branding', $ownerId)`.
* [ ] Wenn `true`: Injiziere dynamisch die gespeicherten Custom-CSS-Farben des Users in den `<head>`.
* [ ] Wenn `false`: Nutze das 365CMS-Standard-Design und blende ggf. ein kleines "Powered by 365CMS" Wasserzeichen ein.

## Phase 7: Dokumentation (`/DOC/`)

* [ ] **7.1 README.md** – Kurzbeschreibung und Features.
* [ ] **7.2 INSTALLATION.md** – Aktivierung und Tabellen.
* [ ] **7.3 VISUAL-BUILDER.md** – Technische Erklärung der Vanilla JS Tree-Logik und SVG-Render-Engine.
* [ ] **7.4 CROSS-PLUGIN-SYNC.md** – Erklärung des Datenflusses von `cms-companies`, `cms-experts` und `cms-jobprofile-generator` in die Chart-Nodes.
* [ ] **7.5 SUBSCRIPTION-INTEGRATION.md** – Dokumentation der Abfrage von Node-Limits.