# CMS Experts Directory Plugin

**Version:** 3.0.4  
**Requires:** 365CMS 3.0+  
**PHP:** 8.4+

## Beschreibung

Das CMS Experts Directory Plugin verwaltet IT-Experten-Profile mit umfangreichen Metadaten, einer Card-Übersicht und vollständigen Detailseiten. Es ist das zentrale Plugin für das 365network Experten-Verzeichnis und entspricht funktional dem WordPress-Plugin `it-expert-cards`.

---

## Features

### Basis-Profil
- ✅ Name, Position, Firma, Kontaktdaten (E-Mail, Telefon, Mobil)
- ✅ Standort (Stadt, PLZ, Land)
- ✅ Biografie (SunEditor WYSIWYG)
- ✅ Profilfoto-URL
- ✅ Berufserfahrung in Jahren
- ✅ Motto / Tagline
- ✅ Arbeitsform (Freelancer / Angestellt / Agentur / Contractor)
- ✅ Zeitzone

### Verfügbarkeit & Konditionen
- ✅ Verfügbarkeitsstatus (verfügbar / begrenzt / nicht verfügbar)
- ✅ Verfügbar-ab-Datum
- ✅ Stundensatz & Tagessatz (€)
- ✅ Wöchentliche Stunden
- ✅ Min./Max. Projektdauer
- ✅ Mindestbuchungsdauer
- ✅ Zahlungsziel (Netto 7/14/30/60, Vorkasse)
- ✅ Festpreis-Projekte / Time & Material (Checkboxen)
- ✅ Reisekosten-Modell
- ✅ Max. Reisedistanz (km)
- ✅ Bevorzugte Unternehmensgrößen (Startup / KMU / Konzern / Öffentlich)

### Technische Expertise
- ✅ Programmiersprachen mit Level (Einsteiger … Experte)
- ✅ Frameworks & Bibliotheken mit Level
- ✅ Datenbanken mit Level
- ✅ Cloud-Plattformen mit Level
- ✅ Branchenerfahrung (Tags)
- ✅ Bevorzugte Tools (Tags)

### Berufliche Stationen
- ✅ JSON-Repeater: Firma, Position, Von/Bis, Ort, Beschreibung

### Referenzen & Portfolio
- ✅ Testimonials (Zitat, Kundenname, Position, Unternehmen, Bewertung ⭐)
- ✅ Case Studies (Titel, Beschreibung, Link)
- ✅ Konferenzvorträge (Titel, Veranstaltung, Jahr, Video-Link)

### Service-Angebot
- ✅ Beratung / Consulting
- ✅ Umsetzung / Implementierung
- ✅ Training & Schulung
- ✅ Support & Wartung
- ✅ Audit & Review
- ✅ Notfall-Support (24/7)
- ✅ Workshops & Intensiv-Sessions

### Netzwerk & Skalierung
- ✅ Subunternehmer verfügbar
- ✅ Team-Erweiterung möglich
- ✅ Max. Teamgröße & geführte Teamgröße
- ✅ Anzahl abgeschlossener Projekte
- ✅ Partner-Netzwerke (Freitext)

### Online-Präsenz
- ✅ LinkedIn, XING, GitHub, GitLab, Stack Overflow
- ✅ Twitter/X, YouTube-Kanal, Blog / RSS-Feed
- ✅ Persönliche Website
- ✅ Erreichbarkeitszeiten

### Profil-Status & Badges
- ✅ Partner-Status (Kein / Partner / Top-Partner / Sponsor)
- ✅ MVP-Badge
- ✅ Zertifiziert-Badge
- ✅ Premium-Badge
- ✅ Eigener Custom-Award-Text

### Fachrichtungen & Skills
- ✅ Hierarchische Fachrichtungs-Taxonomie
- ✅ Skills in drei Kategorien: Allgemein / Technisch / Soft Skills
- ✅ Skill-Presets (vordefinierte Skill-Listen)

### Zertifikate, Projekte & Ausbildung
- ✅ Zertifikate (Name, Aussteller, Datum, Ablauf, URL)
- ✅ Projekte (Name, Beschreibung, Rolle, Zeitraum, URL, Technologien)
- ✅ Ausbildung (Abschluss, Institution, Fachrichtung, Zeitraum)

### Öffentliche Ansicht
- ✅ Event-style Card-Übersicht (`/experts`) mit Filter nach Suche, Verfügbarkeit und Stadt
- ✅ Detailseite (`/experts/{vorname}-{nachname}-{id}`) mit allen Sektionen
- ✅ Visual Level-Balken für Technische Expertise
- ✅ Karriere-Timeline
- ✅ Referenzen- & Testimonials-Grid
- ✅ RSS-Feed-Vorschau (Blog)
- ✅ Vollständige Sidebar (Konditionen, Services, Netzwerk, Social)

### Weitere Features
- ✅ SunEditor WYSIWYG-Ausgabe mit korrektem CSS auf Public-Seiten
- ✅ Member-Dashboard-Integration (`/member/plugin/experts`)
- ✅ Shortcodes: `[cms_experts]`, `[cms_expert id="123"]`
- ✅ Design-Einstellungen (Farben, Radien, Grid)
- ✅ CSRF-Schutz auf allen Formularen
- ✅ Pagination
- ✅ N+1-optimiertes Bulk-Loading in der Übersicht
- ✅ Soft-Delete (Status `deleted`, kein physischer DB-Eintrag entfernt)
- ✅ Slug-Format: `vorname-nachname-{id}`

### Publicsite-Status (2026-05-30)

- Die öffentliche Übersicht `/experts` startet wie `cms-events` direkt mit Filter und responsivem Card-Grid.
- Expert-Cards zeigen Avatar/Initialen, MVP-/Premium-/Award-/Spezialisierungs-Badges, Verfügbarkeit, Standort/Firma, Erfahrung, Zertifikate, Skills und Profil-CTA.
- Cards sind komplett klickbar und zusätzlich per `Enter`/`Space` tastaturbedienbar.

---

## Datenbank-Tabellen

| Tabelle | Inhalt |
|---|---|
| `{prefix}experts` | Haupt-Profiltabelle |
| `{prefix}expert_skills` | Skills mit Level und Typ (general/tech/soft) |
| `{prefix}expert_meta` | Flexible Key-Value-Metadaten |
| `{prefix}expert_certifications` | Zertifikate |
| `{prefix}expert_projects` | Projekte |
| `{prefix}expert_education` | Ausbildungseinträge |
| `{prefix}expert_specializations` | Fachrichtungs-Taxonomie |
| `{prefix}expert_specialization_rel` | Expertein ⇔ Fachrichtung (n:m) |
| `{prefix}expert_plugin_settings` | Plugin-/Design-Einstellungen |

---

## URLs & Routen

### Public
| URL | Beschreibung |
|---|---|
| `/experts` | Experten-Übersicht (mit Filter) |
| `/experts/{vorname}-{nachname}-{id}` | Experten-Detailseite |
| `/expert/{slug}` | Legacy-Redirect |

### Admin
| URL | Beschreibung |
|---|---|
| `/admin/experts` | Liste & Verwaltung |
| `/admin/experts/new` | Neuen Experten anlegen |
| `/admin/experts/edit/{id}` | Experten bearbeiten |
| `/admin/experts/approve/{id}` | Experten genehmigen (pending → active) |
| `/admin/experts/delete/{id}` | Experten löschen (Soft Delete) |

---

## Shortcodes

```
[cms_experts]                          // Alle aktiven Experten (max. 12)
[cms_experts limit="6"]               // Begrenzung auf 6 Einträge
[cms_experts availability="available"] // Nur verfügbare Experten
[cms_expert id="42"]                   // Einzelne Expert-Card
```

---

## Programmatischer Zugriff

```php
$db = CMS_Experts_Database::instance();

// Alle aktiven Experten
$experts = $db->get_experts(['status' => 'active', 'limit' => 20]);

// Alle Experten inkl. pending/inactive (Admin-Nutzung)
$all = $db->get_experts_all();

// Experte nach ID
$expert = $db->get_expert(42);

// Slug generieren
$slug = CMS_Experts_Database::generate_slug($expert);
// → "max-mustermann-42"

// Meta holen
$meta = $db->get_all_meta($expert->id);
$linkedin = $meta['social_linkedin'] ?? '';

// Skills (gruppiert)
$skills = $db->get_expert_skills_grouped($expert->id);
// → ['general' => [...], 'tech' => [...], 'soft' => [...]]

// Zählen
$count = $db->countExperts();           // alle nicht-gelöscht
$active = $db->countExperts('active');  // nur aktive
```

---

## Hooks

### Actions
| Hook | Parameter | Beschreibung |
|---|---|---|
| `expert_registered` | `$expert_id` | Neuer Experte angelegt |
| `expert_updated` | `$expert_id` | Experte geändert |
| `expert_deleted` | `$expert_id` | Experte gelöscht |
| `expert_profile_view` | `$expert_id` | Detailseite aufgerufen |

### Filters
| Hook | Beschreibung |
|---|---|
| `expert_card_content` | Card-HTML verändern |
| `content` | Shortcode-Verarbeitung im Page-Content |

---

## Template-Dateien

| Datei | Beschreibung |
|---|---|
| `templates/archive-expert.php` | Experten-Übersicht / Archivseite |
| `templates/single-expert.php` | Experten-Detailseite (vollständig) |
| `templates/expert-card.php` | Card-Komponente für Grid & Shortcodes |

Theme-Override möglich: `{theme}/experts/{template-name}.php`

---

## Meta-Schlüssel (Referenz)

| Schlüssel | Typ | Beschreibung |
|---|---|---|
| `motto` | text | Tagline / Motto |
| `work_type` | text | freelancer / employed / agency / contractor |
| `timezone` | text | z.B. Europe/Berlin |
| `partner_status` | text | none / partner / top_partner / sponsor |
| `is_mvp` | bool | MVP-Badge |
| `is_certified` | bool | Zertifiziert-Badge |
| `is_premium` | bool | Premium-Badge |
| `custom_award` | text | Eigener Badge-Text |
| `avail_date` | date | Verfügbar ab |
| `weekly_hours` | int | Stunden/Woche |
| `min/max_project_duration` | text | Projektdauer |
| `payment_terms` | text | Zahlungsziel |
| `fixed_price_projects` | bool | Festpreis möglich |
| `time_material` | bool | T&M möglich |
| `travel_cost_model` | text | Reisekosten-Modell |
| `max_travel_distance_km` | int | Max. Reisedistanz |
| `preferred_company_sizes` | JSON | ["Startup","SMB",…] |
| `programming_languages` | JSON | [{name,level},…] |
| `frameworks` | JSON | [{name,level},…] |
| `databases` | JSON | [{name,level},…] |
| `cloud_platforms` | JSON | [{name,level},…] |
| `tools_preferred` | JSON | ["Docker","Jira",…] |

## Sicherheitsstatus (2026-04-04)

- Snyk-Code-Audit für `cms-experts` abgeschlossen, aktuell ohne offene Findings.
- Das Archiv escaped Such- und Ortsfilter jetzt direkt an den Eingabe- und Reset-Sinks.
- Die öffentliche Filter-Navigation bleibt damit funktional, ohne taint-basierte XSS-Befunde auszulösen.
| `industry_experience` | JSON | ["FinTech",…] |
| `career_stations` | JSON | [{company,position,from_date,to_date,location,achievements},…] |
| `testimonials` | JSON | [{text,client_name,position,company,rating},…] |
| `case_studies` | JSON | [{title,description,link},…] |
| `conference_talks` | JSON | [{title,event,year,video_link},…] |
| `services_*` | bool | 7 Service-Flags |
| `emergency_support` | bool | Notfall-Support |
| `workshop_offerings` | bool | Workshops |
| `subcontractors_available` | bool | Subunternehmer |
| `team_expansion_possible` | bool | Team-Erweiterung |
| `max_team_size` | int | Max. Teamgröße |
| `team_size_led` | int | Geführte Teamgröße |
| `total_projects` | int | Abgeschlossene Projekte |
| `partner_networks` | text | Partner-Netzwerke |
| `social_linkedin` | url | LinkedIn |
| `social_xing` | url | XING |
| `social_github` | url | GitHub |
| `social_gitlab` | url | GitLab |
| `social_stackoverflow` | url | Stack Overflow |
| `social_twitter` | url | Twitter/X |
| `social_youtube` | url | YouTube |
| `social_blog_rss` | url | Blog RSS |
| `social_website` | url | Webseite |
| `contact_times` | text | Erreichbarkeitszeiten |
| `languages` | text | Sprachen (kommagetrennt) |
| `remote_work` | text | No / Partial / Full Remote |
| `notice_period` | text | Kündigungsfrist |
| `travel_willingness` | text | Reisebereitschaft |
| `company_id` | int | Verknüpfte Firma |

---

## Installation

Das Plugin wird während des 365CMS-Setups automatisch aktiviert. Datenbank-Tabellen werden bei der ersten Aktivierung angelegt.

```bash
# Manueller Test (Syntaxprüfung)
php -l PLUGINS/cms-experts/cms-experts.php
```

## License

Part of 365CMS Core - All Rights Reserved
