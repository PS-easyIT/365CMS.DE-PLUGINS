# CMS Experts – Changelog

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
