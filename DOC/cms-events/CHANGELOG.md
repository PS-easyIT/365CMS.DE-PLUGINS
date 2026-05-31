# CMS Events – Changelog

## [3.0.30] – 2026-05-31

- **Assets:** Der Tabler-Icons-Fallback lädt keine externe jsDelivr-/CDN-URL mehr.
- **Runtime:** Icons-Kompatibilität wird ausschließlich über das lokale Core-Asset `/assets/tabler-icons/tabler-icons.min.css` eingebunden.

## [3.0.29] – 2026-05-31

- **Public-Detailseite:** Die Event-Detailseite folgt nun der PHINIT-HTML-Preview mit Navy/Amber-Hero, großem Datebox-Block, Status-Badge, Meta-Zeile und ruhigen Tag-Chips.
- **Daten-Mapping:** Das Programm wird aus vorhandenen Speaker-Zuordnungen (`presentation_title`, `session_time`, `role`) erzeugt; dedizierte Agenda-, Sprach- und Anmeldeschluss-Felder existieren im aktuellen Schema nicht und werden nicht künstlich befüllt.
- **Sidebar:** Teilnahme, Social/Share, Event-Details, Veranstaltungsort und weitere Events wurden in preview-nahe Cards überführt.
- **I18n & Assets:** Sichtbare Labels laufen über `cms_events.detail.*` mit lokalem YAML-Fallback; die Detailseite nutzt Inline-SVGs statt Tabler-Icon-Font und `assets/css/single.css` wird nur noch auf Event-Detailrouten geladen.

## [3.0.28] – 2026-05-31

- **Public-Detailseite:** Breadcrumb und Detail-Grid der Event-Detailseite sind auf maximal `1160px` Contentbreite zentriert.
- **Theme-Shell:** Die Detailseite nutzt eine bündige Hintergrund-Shell ohne Theme-Header/-Footer-Gaps und füllt kurze Inhalte bis zum Footer.
- **Responsive/Dark Mode:** Hauptspalte, Sidebar, Anmeldung, Speaker-Teaser, Share-Leisten, Related Events und Themen-Badges brechen mobil sauber um und sind für `body.dark-mode` sowie `html.dark-mode body:not(.light-mode)` abgesichert.

## [3.0.27] – 2026-05-31

- **Public-Suchbereich:** Die Hauptaktion der Events-Filterleiste ist nun ein primärer Button „Suchen“ statt „Filter zurücksetzen“.
- **Reset-Verhalten:** Zurücksetzen bleibt gezielt im Empty-State verfügbar, damit die normale Filterleiste wie Companies/Speakers/Experts mit Suche startet.

## [3.0.26] – 2026-05-31

- **Public-Suchbereich:** Die Events-Übersicht nutzt nun das einheitliche Companies-basierte Filterdesign mit gleicher Feldhöhe, Label-Rhythmik, Button-Optik, voller `1160px`-Contentbreite und responsiven Umbrüchen.
- **Plugin-Anpassung:** Kategorie, Monat, Jahr und Eventsuche behalten ihre Event-spezifische Funktion und verwenden die Event-Akzentfarben.

## [3.0.25] – 2026-05-31

- **Public-Übersicht:** Die Shell liegt bündig an Theme-Header und -Footer an; der eigentliche Content startet intern bei `25px`, bleibt responsiv und ist auf maximal `1160px` begrenzt.

## [3.0.24] – 2026-05-28

- **Lifecycle:** Deaktivierung und Uninstall löschen keine Event-Tabellen mehr; Plugin-Daten bleiben erhalten.
- **Datenbank:** Schema-Migration ergänzt idempotent `idx_event_date_status (event_date, status)`; Settings werden request-lokal gecacht.
- **Admin/Member:** Formulare nutzen serverseitige Validierung mit `novalidate`; Redirects laufen über den Core-Router, wenn verfügbar.
- **Public:** Inline-`onclick` aus Eventkarten entfernt, Datumsformatierung gegen ungültige `strtotime()`-Rückgaben abgesichert und Reset-Logik nutzt Server-Defaults.

## [3.0.23] – 2026-05-28

- **Archivkarten:** Der Footer ist nun zweispaltig aufgebaut: links Speaker oder Veranstalter, rechts der Details-Button.
- **Priorität:** Speaker werden bevorzugt angezeigt; nur wenn kein Speaker vorhanden ist, erscheint der Veranstalter.
- **Layout:** Der Details-Button bleibt durch `flex-shrink: 0` stabil rechts, während lange Namen links ellipsiert werden; leere Informationen erzeugen keinen Placeholder.

## [3.0.22] – 2026-05-28

- **Übersicht:** Der Kopfbereich oberhalb der Suche wurde entfernt (`Events`, Seitentitel und Ergebnis-Subtitle wie `94 Events ab aktuellem Monat`).
- **Filter-Fokus:** Die Event-Übersicht startet nun direkt mit der Filter-/Suchleiste.
- **Template-Cleanup:** Nicht mehr benötigte Berechnungen für Archivtitel und Subtitle wurden aus dem Archive-Template entfernt.

## [3.0.21] – 2026-05-28

- **Archivkarten:** Der Veranstalter wird in der Übersicht nicht mehr als eigene Zeile unter dem Ort ausgegeben.
- **Format/Ort:** Das Online-/Hybrid-/Präsenz-Badge wandert aus der Kategorie-Zeile in die Ortszeile und steht dort mit Icon direkt vor dem Veranstaltungsort.
- **Layout:** Kategorie und Preis bleiben oben kompakt getrennt; die Meta-Zeilen unter dem Titel wirken ruhiger und weniger redundant.

## [3.0.20] – 2026-05-27

- **Audit-Fix:** Der Template-Loader verwendet kein `extract()` mehr.
- **Kompatibilität:** Plugin-Templates und Theme-Overrides erhalten weiterhin die erwarteten Kontextvariablen, allerdings nur noch aus einer expliziten Whitelist.
- **Sicherheit/Best Practice:** Das reduziert unbeabsichtigte Variablen-Injection und macht den Render-Kontext nachvollziehbarer.

## [3.0.19] – 2026-05-27

- **Detailseite:** Die redundanten Meta-Ausgaben direkt unter dem Eventtitel inklusive Teaser-/Excerpt-Zeile wurden entfernt; Sidebar-Meta und Veranstalter-Kontakt bleiben erhalten.
- **Themen:** Event-Tags/Themen erscheinen nun unterhalb der Beschreibung als deduplizierte, einzelne Badges; neben JSON-Arrays werden auch kommaseparierte oder zeilenbasierte Werte robust aufbereitet.

## [3.0.18] – 2026-05-27

- **Archivkarten:** Event-Bild, Featured-Status, dezente Kategorie-/Format-Badges, Uhrzeit sowie Ort und Veranstalter mit Icon sitzen kompakt direkt unter dem Titel; Tags/Themen werden aus der Übersicht entfernt.
- **Detailseite:** Teaser, volle Adresse, Format, Veranstalter-Kontakt (Website, E-Mail, Telefon), Online-Zugang und Tags werden in der Detailansicht ergänzt; Meta-Angaben erscheinen als einzelne Icon-Badges.
- **Mehrtagesevents:** Start- und Endtermin werden bei mehrtägigen Events zweizeilig dargestellt, damit keine unschönen Umbrüche in einer langen Datumszeile entstehen.

## [3.0.17] – 2026-05-27

- **Eventkarten:** Veranstaltungsort und Stadt werden in der Archivkarte vor der Ausgabe dedupliziert, damit identische Werte nicht mehr als `Ort, Ort` hintereinander erscheinen.
- **Kostenstatus:** Archivkarten zeigen nun pro Event oben rechts eine dezente Preis-/Kosten-Pill (`Kostenlos`, `Spendenbasis`, Betrag oder `Kostenpflichtig`), während Kategorie- und Status-Badges links bleiben.

## [3.0.16] – 2026-05-27

- **Archiv-Standardansicht:** Ohne aktive Filter werden Events ab dem aktuellen Monat serverseitig geladen; ältere Events gelten als vergangen und werden bei expliziten Filtern/Alle-Auswahl dezent grau markiert.
- **Filter/Pagination:** Kategorie, Monat, Jahr und Suche werden nun in die URL übernommen und in Pagination-Links beibehalten; Filteränderungen starten wieder auf Seite 1 statt auf einer alten, leeren Offset-Seite.
- **Serverseitige Query:** Monats-, Jahres- und Ab-Monat-Filter laufen in `CMS_Events_Database::get_events()` und `count_events()`, sodass Count, Seitenzahl und Ergebnisliste konsistent sind.

## [3.0.15] – 2026-05-27

- **Archivfilter:** Monat und Jahr werden beim Seitenaufruf auf den aktuellen Monat bzw. das aktuelle Jahr vorbelegt; der JS-Filter läuft sofort beim Laden und Reset springt ebenfalls auf diese aktuellen Werte zurück.
- **Eventkarten:** Karten sind kompakter gepaddet, vollständig klickbar, per Tastatur erreichbar und erhalten Hover-/Focus-Feedback; der Titel bleibt als eigener Link nutzbar.
- **Detailseite:** Share-URLs werden einmalig berechnet und in Hauptbereich sowie Sidebar verwendet, optional inklusive Event-Website-Link und Tabler-Webfont-Fallback für sichtbare Icons.
- **Related Events:** Bild-Platzhalter verwenden das gescopte `related-event-thumb`-Pattern mit `ti-calendar-event` statt leerer grauer Flächen.

## [3.0.14] – 2026-05-27

- **Eventkarten:** Kategorie-Badge nur bei vorhandener Kategorie, zweispaltiger Datum/Titel-Aufbau und kompakter Details-Button mit 6px-Radius nachgezogen.
- **Detailseite:** Share-Leisten im Content und in der Sidebar verwenden explizite `event-share-btn`-Links mit Tabler-Icons und robust berechneter aktueller URL.
- **Related Events:** Bildteaser auf 72×54 normalisiert; fehlende Bilder zeigen nun einen ruhigen Kalender-Placeholder mit `ti-calendar-event`.

## [3.0.13] – 2026-05-27

- **Archiv:** Eventfilter für Kategorie, Monat, Jahr und Suche auf die gewünschten Feld-IDs und `data-*`-Attribute normalisiert; Empty State und Pagination bleiben scoped im Event-Content.
- **Eventkarten:** Karten zeigen nun Kategorie-Badge, Titel, Datum, Ort, Trennlinie und Details-Button in der vorgegebenen 3-Spalten-Struktur ohne Bild-/Hero-Overhead.
- **Detailseite:** Meta-Zeile, Beschreibungskarte, Speaker-Initialen-Fallback, Share-Buttons, Related-Event-Placeholder sowie Preis-/Sitzplatzanzeige in der Anmeldung nach Screenshot-Vorgabe nachgezogen.

## [3.0.12] – 2026-05-27

- **Detailrouting:** `/events/:id` rendert die Detailseite nun direkt statt per Header-Redirect auf die Slug-URL zu wechseln; dadurch entstehen keine 500er mehr, wenn Header bereits ausgegeben wurden.
- **Detaildaten-Fallbacks:** Speaker, Settings und Related Events werden einzeln abgefangen und fallen bei Fehlern auf leere/default Daten zurück, statt die gesamte Detailseite abzubrechen.
- **Publicsite-Design:** Finale PHINIT-Styles korrigieren Textkontrast, Karten, Buttons und Detailspalte; leere Bild-Hero-Flächen werden nicht mehr gerendert, wenn kein Eventbild vorhanden ist.

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
