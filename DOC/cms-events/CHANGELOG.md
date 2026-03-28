# CMS Events – Changelog

## [2.8.0-audit] – 2026-03-28

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

## [2.8.0-docs] – 2026-03-28

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
