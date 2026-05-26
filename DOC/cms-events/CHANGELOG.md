# CMS Events – Changelog

## [3.0.11] – 2026-05-26

- **Hotfix Public-Rendering:** 500-Fehler „Template-Fehler – Das Event-Template konnte nicht gerendert werden“ auf Systemen ohne `mbstring` behoben.
- **Templates:** `archive-event.php` und `event-card.php` nutzen jetzt robuste Lowercase-Fallbacks (`mb_strtolower` → `strtolower`).
- **Sanitizer:** `class-post-type.php` und `class-shortcode.php` nutzen Fallbacks für String-Truncation (`mb_substr` → `substr`) statt harter Abhängigkeit.

## [3.0.10] – 2026-05-26

- **Admin-Design:** Der Events-Adminbereich folgt nun konsequent den 365CMS Admin Design Richtlinien: Header → Alerts → Tabs → Content, konsistente `admin-card`-Panels und `admin-form`-Formulare.
- **Overview:** Die frühere Custom-Card-Liste wurde durch eine Admin-Tabelle mit `.users-table`, klaren Status-Badges, kompakten Merkmal-Badges und standardisierten Aktionsbuttons ersetzt.
- **Kategorien/Tags:** Taxonomie- und Tag-Presets nutzen jetzt ein gemeinsames Tab-Panel mit Tabellen, Empty States und schlanken Formular-Sidepanels.
- **Design/Einstellungen:** Beide Tabs bestehen aus jeweils einer zusammenhängenden Settings-Card mit internen Sektionen, Live-Vorschau und Speichern-Aktion am Kartenende.
- **Admin-Assets:** `events-admin.css` wurde auf plugin-spezifische Layout-Regeln reduziert; Speaker-AJAX-Meldungen erscheinen inline als Admin-Alerts statt per nativer Browser-Alerts.

## [3.0.9] – 2026-05-26

- **Navigation:** Der öffentliche Events-Link in der Hauptnavigation ist nun über die Events-Einstellungen steuerbar (`show_nav_link`) und standardmäßig deaktiviert; das Label bleibt über `nav_label` frei konfigurierbar.
- **Publicsite-Design:** Archiv-Header, Such-/Filterleiste, Event-Cards, Badges, Tags, Buttons, Empty State und Pagination wurden auf das gewünschte PHINIT-Layout normalisiert.
- **Detailseite:** Meta-Zeile zeigt Datum/Zeit, Ort, Speaker und Preis; die Anmeldung hebt den Preis hervor, bietet Share-Links und zeigt bei ausgebuchten Events eine Wartelisten-Option.
- **Speaker/Related:** Speaker-Teaser nutzt Avatar, Position und Kurzbio; Related Events werden als kompakte Bild-Teaser dargestellt.

## [3.0.6] – 2026-05-25

- **Admin-Menü-Dashboard:** Der Plugin-Menüeintrag `/admin/plugins/events/events` rendert den Overview-/Dashboard-Tab jetzt direkt serverseitig über `CMS_Events_Post_Type::admin_list()`.
- Die bisherige JavaScript-Weiterleitungsbrücke nach `/admin/events` wurde entfernt. Damit erscheint das Dashboard sofort beim Klick auf den Sidebar-Menüeintrag und nicht erst nach einem zusätzlichen Tab-Klick oder Redirect.

## [3.0.5] – 2026-05-25

- **Admin-Menü final:** Der Guard der Hauptdatei wurde von einem selbst-auslösenden `if (class_exists(...)) return;` auf das funktionierende `cms-feed`-Muster `if (!class_exists(...)) { class ... }` umgestellt. Dadurch wird `CMS_Events::instance()` beim Plugin-Laden ausgeführt und der `cms_admin_menu`-Hook tatsächlich registriert.
- **Bootstrap-Timing:** Hook-Komponenten wie Admin, Routen, Meta-Boxen, Shortcodes und Member-Dashboard werden jetzt bereits bei verfügbarem `CMS\Hooks` registriert; DB-Migrationen bleiben weiterhin an `CMS\Database` gekoppelt.

## [3.0.4] – 2026-05-25

- **Admin-Menü:** `CMS_Events_Admin` lädt vor der Menüregistrierung die zentralen 365CMS Admin-Menü-Helper (`includes/functions/admin-menu.php`) defensiv. Dadurch erscheint der Events-Eintrag zuverlässig in der Sidebar, auch wenn der Core-Helper beim Plugin-Hook noch nicht global geladen war.

## [3.0.3] – 2026-05-25

### Geändert

- **Bootstrap-Härtung:** Plugin-Konstanten, Hauptklasse und Include-Klassen sind gegen doppelte Ladepfade/Redeclare-Fatals abgesichert; Dependency-Loading überspringt bereits geladene Klassen.
- **Lifecycle:** Deactivation- und Uninstall-Hooks wurden ergänzt; der Uninstall entfernt Event-Tabellen inklusive Settings-/Meta-/Relationstabellen kontrolliert.
- **Datenbank:** Installer erstellt jetzt auch `event_settings`; Spaltenprüfungen laufen über `INFORMATION_SCHEMA`; Foreign Keys werden idempotent, präfixsicher und nicht-blockierend ergänzt.
- **SettingsService:** Plugin-Settings werden primär über `CMS\Services\SettingsService` in `cms_settings` gespeichert; `event_settings` bleibt als Legacy-Fallback lesbar.
- **Public-Routen:** Das fehlende Template `calendar-view.php` wurde ergänzt; Template-Fehler erzeugen keine Blank Pages mehr, sondern 365CMS-Error-Fallbacks mit serverseitigem Logging.
- **Fehlerseiten:** Event-404 nutzt nun die native 365CMS-404-Renderstrecke mit minimalem Fallback.
- **Admin/AJAX:** Approve nutzt einen eigenen CSRF-Kontext; Speaker-AJAX liefert konsistente JSON-Statuscodes und protokolliert technische Details.
- **HTTP-Methoden:** Mutierende Admin-Endpunkte liefern für Browser-GETs jetzt explizit `405 Method Not Allowed` mit `Allow: POST` statt uneindeutiger 404-/Redirect-Pfade.
- **Settings:** Badge-/Pill-Felder und Badge-Farben werden vollständig gespeichert und mit Default-Werten ausgeliefert.
- **Frontend:** Kalenderansicht und Pagination behalten Filterzustände sauber bei; Shortcodes liefern die benötigten Template-Daten vollständig.

## [3.0.2] – 2026-05-18

### Geändert

- **Public-Design-Pass:** Archiv-Header und Suche sind als abgegrenzter Filterbereich gruppiert.
- **Event-Cards:** Karten wachsen ohne feste Höhen; lange Titel, Tags und Pills brechen kontrolliert um.
- **Responsive UX:** Footer-Aktionen bleiben am Kartenende und funktionieren stabil auf Mobile/Desktop.

## [3.0.1] – 2026-05-17

### Geändert

- **iCal-Härtung:** Der Export nutzt jetzt immutable Datumsobjekte, validierte Start-/Endzeiten, einen sicheren Host, `nosniff`, `Content-Length`, gefaltete ICS-Zeilen und validierte HTTP(S)-URLs.
- **Query-/POST-Normalisierung:** Archiv-, Kalender-, Admin-Save-, Settings-, Speaker-AJAX- und Member-Create-Pfade validieren Datums-, Zeit-, URL-, Tab-, Filter- und Textwerte restriktiver.
- **Publicsite-Design:** Archiv- und Karten-Templates wurden von dekorativem Emoji-UI bereinigt und behalten die PHINIT-nahe, ruhige Darstellung bei.
- **Admin-/Member-Ausgabe:** CSRF-Attribute, Admin-Asset-URLs, JSON-Antworten und dynamische CSS-Farbwerte werden konsequenter escaped bzw. normalisiert.

## [1.1.0] – 2026-03-28

### Geändert

- **Listenhärtung:** `get_events()` begrenzt `limit` und `offset` jetzt defensiv, damit Archiv-, Admin- und Member-Abfragen keine ungebremsten Query-Werte übernehmen.
- **Admin-Save:** `status`, `price_type`, URL-, E-Mail- und Tag-Felder werden im Save-Handler jetzt restriktiver validiert und normalisiert.
- **Member-Create:** Der Member-Create-Handler nutzt jetzt dieselben restriktiven Validierungen für Preis-, URL-, E-Mail- und Tag-Felder wie der gehärtete Save-Pfad.
- **Template-Escaping:** Die Event-Beschreibung im Single-Template wird nicht mehr roh ausgegeben, sondern sicher escaped und mit Zeilenumbrüchen gerendert.
- **Link-Escaping:** Intern zusammengesetzte Breadcrumb-, Speaker- und Register-Links im Single-Template werden jetzt ebenfalls konsequent im Attribut-Kontext escaped.
- **Archive-Link-Escaping:** Reset- und Pagination-Links im Archive-Template behandeln interne URLs und Query-Parameter jetzt ebenfalls konsequent im `href`-Attribut-Kontext.
- **Style-Attribut-Escaping:** Auch dynamische Gradientwerte im Speaker-Fallback des Single-Templates werden jetzt konsequent im `style`-Attribut-Kontext escaped.
- **Speaker-Typ-Härtung:** Die zentrale `assign_speaker()`-Persistenz whitelisted `speaker_type` jetzt zusätzlich selbst auf `speaker`/`expert`.
- **Kalender-Parameter:** Calendar-Shortcodes validieren `month` und `view` jetzt auch aus Attributen und Query-Parametern restriktiv, bevor sie in Query- oder Render-Kontexte laufen.
- **Renderzeit-Validierung:** Speaker-/Experten-Profillinks sowie Banner-, Bild-, Registrierungs-, Online- und Veranstalter-Kontaktfelder werden im Single-Template jetzt zusätzlich gegen Alt- und Bestandsdaten validiert.
- **Ownership-Härtung:** Die zentrale `save_event()`-Persistenz blockiert für Nicht-Admins jetzt Updates auf fremde Event-IDs und entschärft damit latente IDOR-/Fremd-ID-Pfade.
- **Bootstrap-Fix:** Hook-registrierende Klassen werden jetzt bereits beim Plugin-Start instanziiert, sodass Admin-Sidebar-Eintrag und Admin-Routen nicht mehr von `cms_init` abhängen.
- **Bootstrap-Guard:** Der frühe Bootstrap wird jetzt zusätzlich nur bei verfügbarem Core-Kontext (`CMS\Hooks`, `CMS\Database`) ausgeführt und entschärft damit Aktivierungs-/Lade-Fatals.
- **Member-Dashboard:** Eigene Event-Zähler berücksichtigen nicht mehr nur veröffentlichte Einträge, sondern den tatsächlichen Bearbeitungsstand des Members.
- **Assets:** Bootstrap- und Admin-Assets nutzen konsistent dateigeprüfte, lokal zwischengespeicherte Versionswerte.

## [1.0.2] – 2026-03-28

### Geändert

- **Dokumentation:** README, Aufgabenplanung und Sicherheitsdokumentation auf den Audit-Zielstand für **365CMS V2.8.0** erweitert.
- **Audit-Vorbereitung:** Ein zentraler Abarbeitungsplan für `cms-events`, `cms-experts`, `cms-companies` und `cms-speakers` wurde unter `DOC/365CMS-V2.8.0-PLUGIN-AUDIT-PLAN.md` angelegt.
- **Sicherheitsdokumentation:** Neues `SECURITY.md` ergänzt, mit Fokus auf Rechteprüfung, CSRF-Schutz, URL-/Eingabevalidierung, IDOR-Schutz und sichere Event-/Speaker-Verknüpfung.

## [1.0.1] – 2026-03-18

### Geändert

- **Admin-Assets:** Die Events-Verwaltung bindet jetzt ein eigenes `assets/js/admin.js` ein und steuert Bestätigungen, Modale, Farb-Syncs, Vorschauen, Speaker-Zuordnungen und Formular-Toggles zentral über Data-Attribute statt über Inline-Skripte.
- **Admin-Markup:** `includes/class-admin.php` und `includes/class-meta-boxes.php` nutzen für Kategorien, Tags, Design-Preview, Formular-Layouts, Ortsumschaltung, Speaker-Zuordnung und Statusaktionen wiederverwendbare CSS-Klassen und datengetriebene Hooks statt `onclick`/`onchange`/`<script>`-Blöcke.
- **Member-/Frontend-Markup:** `includes/class-member-dashboard.php` und `includes/class-shortcode.php` verzichten jetzt ebenfalls auf Inline-Handler; Member-Form-Toggles laufen über `assets/js/script.js`, und die Kalendernavigation verwendet echte Monats-Links statt einer nicht vorhandenen `changeMonth()`-Funktion.

### Verbessert

- **Wartbarkeit:** Wiederkehrende Admin- und Frontend-Stile wurden in `assets/css/events-admin.css` bzw. `assets/css/style.css` zentralisiert, sodass Event-Verwaltung, Member-Formulare und Kalender konsistenter und leichter erweiterbar bleiben.
- **Sicherheit/Best Practice:** Löschaktionen für Kategorien, Tag-Presets und Speaker-Zuordnungen laufen jetzt über den zentralen Admin-Confirm-Flow statt über native `confirm()`-Aufrufe im Markup.

## [1.0.0] – 2026-02-21

### Hinzugefügt

- **Kern:** Singleton-Hauptklasse `CMS_Events`
- **Datenbank:** `cms_events`, `cms_event_speakers`, `cms_event_meta`, `cms_event_categories`, `cms_event_tag_presets`
- **Event-Typen:** Physisch, Online (Flag + URL), Hybrid
- **Preis-System:** `free`, `paid`, `donation` mit Währungsfeld
- **Speaker-Integration:** M2M zu `cms-speakers` und `cms-experts` mit `speaker_type`
- **Admin-Backend:** Vollständige Verwaltung unter `/admin/events`
  - Kalenderübersicht und Liste
  - Kategorie-Filter, Status-Filter
  - Speaker-Picker (Cross-Plugin)
- **Member-Dashboard:** Eigene Events verwalten
- **Shortcode:** `[cms_events]`
- **Templates:** `archive-event.php`, `event-card.php`, `single-event.php`
- **Hooks:** `event_created`, `event_updated`, `event_cancelled`, `event_speaker_assigned`
- **Filter:** `event_card_content`, `event_query_args`, `event_registration_url`
- **Kategorien-Seeding:** 6 Standard-Kategorien bei Aktivierung
- **Sicherheit:** CSRF, Prepared Statements, XSS-Escaping
