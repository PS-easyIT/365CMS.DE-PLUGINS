# CMS Speakers – Changelog

## [1.1.0] – 2026-03-28

### Geändert

- **Statusschema:** Speaker-Status und Datenbankschema wurden für `pending` und `deleted` harmonisiert; bestehende Installationen werden per Migration nachgezogen.
- **Listenhärtung:** `get_speakers()` normalisiert `limit`, `offset` und `ORDER BY` jetzt defensiv über feste Whitelists.
- **Dashboard-/Admin-Logik:** Member- und Admin-Listen berücksichtigen Speaker über den tatsächlichen Statusfluss konsistent, inklusive eigener `pending`-Profile.
- **Admin-Save:** `gender`, `travel_radius`, `availability`, `status` sowie Array-/Link-Felder werden restriktiver normalisiert.
- **Member-Create:** Gender-, Format-, Travel-, Availability-, Topic- und Link-Daten werden im Member-Create-Handler jetzt ebenfalls restriktiv normalisiert.
- **Template-Escaping:** Die Speaker-Bio im Single-Template wird nicht mehr roh ausgegeben, sondern sicher escaped und mit Zeilenumbrüchen gerendert.
- **Link-Escaping:** Intern zusammengesetzte Breadcrumb-, Company-, Kontakt- und Register-Links im Single-Template werden jetzt ebenfalls konsequent im Attribut-Kontext escaped.
- **Archive-Link-Escaping:** Reset- und Pagination-Links im Archive-Template behandeln interne URLs und Query-Parameter jetzt ebenfalls konsequent im `href`-Attribut-Kontext.
- **Card-Link-Escaping:** Detail-Links aus `$speaker_url` werden im Card-Template jetzt ebenfalls konsequent im `href`-Attribut-Kontext escaped.
- **Card-CTA-Escaping:** Auch der CTA-Link aus `$speaker_url` im Card-Template wird jetzt konsequent im `href`-Attribut-Kontext escaped.
- **Style-/Title-Escaping:** Auch Avatar-Gradient im `style`-Attribut und Event-Zähler im `title`-Attribut des Single-Templates werden jetzt konsequent escaped.
- **Title-Attribut-Escaping:** Auch interne Social-Icon-Labels im `title`-Attribut des Single-Templates werden jetzt konsequent escaped.
- **Renderzeit-Validierung:** Foto-, Website-, Mail-, Telefon- und Social-Link-Felder werden in Card- und Single-Templates jetzt zusätzlich gegen Alt- und Bestandsdaten validiert, bevor sie in `src`, `href`, `mailto:` oder `tel:` gerendert werden.
- **Member-Dashboard-Status:** Der aktuelle Rechte-Stand wurde nachgezogen: Im Member-Bereich existiert derzeit nur ein Create-/Listen-Flow, aber kein realer Edit- oder Update-Pfad für bestehende Speaker.
- **Ownership-Härtung:** Die zentrale `save_speaker()`-Persistenz blockiert für Nicht-Admins jetzt Updates auf fremde Speaker-IDs und entschärft damit latente IDOR-/Fremd-ID-Pfade.
- **Bootstrap-Fix:** Hook-registrierende Klassen werden jetzt bereits beim Plugin-Start instanziiert, sodass Admin-Sidebar-Eintrag und Admin-Routen nicht mehr von `cms_init` abhängen.
- **Bootstrap-Guard:** Der frühe Bootstrap wird jetzt zusätzlich nur bei verfügbarem Core-Kontext (`CMS\Hooks`, `CMS\Database`) ausgeführt und entschärft damit Aktivierungs-/Lade-Fatals.
- **DB-Fatal-Guard:** Der komplette Tabellenaufbau inklusive initialem Datenbankzugriff wird im Aktivierungs-/Init-Pfad jetzt defensiv abgefangen und nur noch geloggt statt als Fatal nach oben weitergereicht.
- **Assets:** Bootstrap- und Admin-Assets verwenden konsistente, dateigeprüfte Versionswerte.

## [1.0.1] – 2026-03-28

### Geändert

- **Dokumentation:** README, Aufgabenplanung und Sicherheitsdokumentation auf den Zielstand für **365CMS V2.8.0** erweitert.
- **Audit-Fokus:** Security-, Speed- und Best-Practice-Prüfschritte für `cms-speakers` konkretisiert, insbesondere für Ownership, Link-Validierung, Template-Escaping und Cross-Plugin-Referenzen.
- **Planung:** Verweis auf den zentralen Abarbeitungsplan `DOC/365CMS-V2.8.0-PLUGIN-AUDIT-PLAN.md` ergänzt.

## [1.0.0] – 2026-02-21

### Hinzugefügt

- **Kern:** Singleton-Hauptklasse `CMS_Speakers`
- **Datenbank:** `cms_speakers`, `cms_speaker_topics`, `cms_speaker_events`, `cms_speaker_meta`
- **Basis-Profil:** Namen, Titel, Geschlecht, Position, Firma, Kontakt, Bio, Kurzbiografie, Foto
- **Social-Media:** LinkedIn, XING, Twitter, Instagram, YouTube, GitHub, GitLab, Website
- **Vortrags-Formate:** keynote, workshop, panel, moderation, interview, webinar, conference, training
- **Event-History:** Auftritte mit Typ, Datum, Ort, Audience-Größe, Video/Slides-URL
- **Cross-Plugin:** `expert_id` (→ cms-experts), `company_id` (→ cms-companies), `cms_event_id` (← cms-events)
- **Verfügbarkeit:** `available`, `limited`, `booked`
- **Badging:** `is_featured`, `is_verified`, `profile_views`-Counter
- **Reise-Radius:** local / regional / national / international / worldwide
- **Honorar:** Preisspanne (min/max), Zielgruppe, Stil
- **Admin-Backend:** CRUD unter `/admin/speakers`
- **Member-Dashboard:** Eigenes Speaker-Profil
- **Shortcode:** `[cms_speakers]`
- **Templates:** `archive-speaker.php`, `speaker-card.php`, `single-speaker.php`
- **Hooks:** `speaker_created`, `speaker_updated`, `speaker_presentation_added`
- **Filter:** `speaker_card_content`, `speaker_query_args`
- **Sicherheit:** CSRF, PDO Prepared Statements, XSS-Escaping
