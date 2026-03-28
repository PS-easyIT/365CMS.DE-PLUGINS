# CMS Experts – Changelog

## [2.1.0] – 2026-03-28

### Geändert

- **Versionierung:** Plugin-Header, Konstante, Klassenwert und `update.json` wurden auf den konsistenten plugin-eigenen Release-Stand `2.1.0` gebracht.
- **Datenbankpfade:** Listenabfragen verwenden jetzt defensiv begrenzte `limit`-/`offset`-Werte, und die Schema-Initialisierung läuft versionsgesteuert statt unnötig oft im Laufzeitpfad.
- **Admin-Save:** Der beschädigte Save-Block wurde repariert und die `availability`-Normalisierung auf erlaubte Werte begrenzt.
- **Member-Create:** E-Mail-, URL-, Availability-, Skill- und Spezialisierungsdaten werden im Member-Create-Handler jetzt ebenso restriktiv normalisiert.
- **Template-Escaping:** Die Experten-Biografie im Single-Template wird nicht mehr roh ausgegeben, sondern sicher escaped und mit Zeilenumbrüchen gerendert.
- **Link-Escaping:** Intern zusammengesetzte Breadcrumb-, Kontakt- und Register-Links im Single-Template werden jetzt ebenfalls konsequent im Attribut-Kontext escaped.
- **Card-Link-Escaping:** Detail-Links aus `$url` werden im Card-Template jetzt ebenfalls konsequent im `href`-Attribut-Kontext escaped.
- **Archive-/CTA-Link-Escaping:** Auch Reset-Link im Archive-Template sowie CTA-Link aus `$url` im Card-Template werden jetzt konsequent im `href`-Attribut-Kontext escaped.
- **Style-/Title-Escaping:** Auch Avatar-Gradient im `style`-Attribut und Event-Zähler im `title`-Attribut des Single-Templates werden jetzt konsequent escaped.
- **Renderzeit-Validierung:** Foto-, Website-, Mail-, Telefon- und Social-Link-Felder werden in Single- und Card-Templates jetzt zusätzlich gegen Alt- und Bestandsdaten validiert, bevor sie in `src`, `href`, `mailto:` oder `tel:` gerendert werden.
- **Member-Dashboard-Status:** Der aktuelle Rechte-Stand wurde nachgezogen: Im Member-Bereich existiert derzeit nur ein Create-/Listen-Flow, aber kein realer Edit- oder Repeater-Update-Pfad für bestehende Experten.
- **Ownership-Härtung:** Die zentrale `save_expert()`-Persistenz blockiert für Nicht-Admins jetzt Updates auf fremde Experten-IDs und entschärft damit latente IDOR-/Fremd-ID-Pfade.
- **Title-Attribut-Escaping:** Auch interne Social-Icon-Labels im `title`-Attribut des Single-Templates werden jetzt konsequent escaped.
- **Bootstrap-Fix:** Hook-registrierende Klassen werden jetzt bereits beim Plugin-Start instanziiert, sodass Admin-Sidebar-Eintrag und Admin-Routen nicht mehr von `cms_init` abhängen.
- **Bootstrap-Guard:** Der frühe Bootstrap wird jetzt zusätzlich nur bei verfügbarem Core-Kontext (`CMS\Hooks`, `CMS\Database`) ausgeführt und entschärft damit Aktivierungs-/Lade-Fatals.
- **DB-Fatal-Guard:** Der komplette Tabellenaufbau inklusive initialem Datenbankzugriff wird im Aktivierungs-/Init-Pfad jetzt defensiv abgefangen und nur noch geloggt statt als Fatal nach oben weitergereicht.
- **Assets:** Bootstrap- und Admin-Assets verwenden konsistent lokal zwischengespeicherte `filemtime()`-Versionen.

## [2.0.1] – 2026-03-28

### Geändert

- **Dokumentation:** README, Aufgabenplanung und Sicherheitsdokumentation auf den Zielstand für **365CMS V2.8.0** erweitert.
- **Audit-Fokus:** Security-, Speed- und Best-Practice-Prüfschritte für `cms-experts` konkretisiert, einschließlich Ownership-Prüfung, Meta-Sanitizing und Versionskonsolidierung.
- **Planung:** Verweis auf den zentralen Abarbeitungsplan `DOC/365CMS-V2.8.0-PLUGIN-AUDIT-PLAN.md` ergänzt.

## [2.0.0] – 2026-02-24

### Hinzugefügt

- **Basis-Profil:** Motto/Tagline, Arbeitsform, Zeitzone
- **Verfügbarkeit/Konditionen:** Verfügbar-ab-Datum, Wochenstunden, Min/Max Projektdauer, Mindestbuchungsdauer, Zahlungsziel (Netto 7/14/30/60, Vorkasse), Festpreis, T&M, Reisekosten-Modell, Max. Reisedistanz, bevorzugte Unternehmensgrößen
- **Badges:** MVP, Zertifiziert, Premium (Checkboxen), Custom-Award-Text
- **Technische Expertise:** JSON-Repeater für Programmiersprachen, Frameworks, Datenbanken, Cloud-Plattformen (jeweils mit Level); Tools und Branchenerfahrung als Tags
- **Karriere-Stationen:** JSON-Repeater (Firma, Position, Von/Bis, Ort, Beschreibung)
- **Referenzen & Portfolio:** Testimonials mit 1–5-Sterne-Bewertung, Case Studies, Konferenzvorträge
- **Service-Angebot:** 7 Checkbox-Flags
- **Netzwerk & Skalierung:** Subunternehmer, Team-Erweiterung, Teamgröße, Gesamtprojekte, Partner-Netzwerke
- **Social:** GitLab, Stack Overflow, YouTube-Kanal, Blog/RSS-Feed, Erreichbarkeitszeiten
- **Admin:** Neue Render-Methoden in `class-meta-boxes.php` für alle neuen Bereiche
- **Frontend:** ~65 neue Variablen in `single-expert.php`; MVP/Certified/Premium/Award-Badges; Tech-Expertise-Section mit visuellen Level-Balken; Career-Timeline; Referenzen-Grid

---

## [1.0.0] – 2026-01-15

### Hinzugefügt

- **Kern:** Singleton-Hauptklasse `CMS_Experts`
- **Datenbank:** `cms_experts`, `cms_expert_meta`, `cms_expert_skills`, `cms_expert_certifications`, `cms_expert_projects`, `cms_expert_education`, `cms_expert_taxonomies`, `cms_expert_skill_taxonomy`, `cms_expert_skill_presets`
- **Admin-Backend:** Vollständige CRUD unter `/admin/experts`
- **Member-Dashboard:** Eigenes Experten-Profil verwalten
- **Shortcode:** `[cms_experts]`
- **Templates:** `archive-expert.php`, `single-expert.php`, `expert-card.php`
- **Taxonomien:** Hierarchische Fachrichtungen, Skill-Kategorien (Allgemein/Technisch/Soft Skills)
- **Hooks:** `expert_created`, `expert_updated`, `expert_deleted`, `expert_availability_changed`
- **Filter:** `expert_card_content`, `expert_query_args`
- **DSGVO:** Daten-Export (Art. 20) und Löschung (Art. 17) via CMS-Hooks
- **Sicherheit:** CSRF, Prepared Statements, XSS-Escaping
