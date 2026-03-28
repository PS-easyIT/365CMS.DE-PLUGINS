# CMS Speakers – Changelog

## [2.8.0-docs] – 2026-03-28

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
