# CMS Events – Changelog

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
