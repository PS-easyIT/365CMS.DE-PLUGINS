# CMS Experts – Dokumentation

**Plugin:** `cms-experts`  
**Version:** 3.0.11
**Namespace:** `CMS_Experts`  
**Aktueller Laufzeitstand:** 365CMS 3.0+  
**Audit-/Dokustand:** PHINIT-Preview-Detailseite am 2026-05-31  
**PHP:** 8.4+

---

## Übersicht

Das **CMS Experts**-Plugin ist das zentrale Verzeichnis für IT-Experten-Profile im 365network-Ökosystem. Es entspricht funktional dem WordPress-Plugin `it-expert-cards` und bietet weit über 100 Profilfelder.

## 3.0.x-Status

- Die Dokumentation ist auf den **Audit- und Zielstand für 365CMS 3.x** angehoben.
- Änderungen erfolgen ausschließlich im Plugin; **der 365CMS-Core bleibt unberührt**.
- Version 3.0.4 stellt die öffentliche Übersicht wie `cms-events` auf Filter-first und responsives Card-Grid um.
- Expert-Cards nutzen Avatar/Initialen, MVP-/Premium-/Award-/Spezialisierungs-Badges, Verfügbarkeit, Standort/Firma, Erfahrung, Zertifikate, Skills und Profil-CTA.
- Version 3.0.8 begrenzt die Public-Experts-Detailseite auf maximal `1160px`, entfernt Hintergrundabstände zu Theme-Header/-Footer, füllt kurze Seiten bis zum Footer und sichert Responsive Layout sowie Dark Mode ab.
- Version 3.0.9 baut die Detailseite nach der PHINIT-HTML-Preview neu auf: Navy/Amber-Hero, Karten für Profil, Expertise, Zertifikate, Leistungen und Projekte/Referenzen, Sidebar für Anfrage, Social, Details und ähnliche Experten, zentrale de/en-Übersetzungen und Detail-CSS nur auf `/experts/{slug}`.
- Version 3.0.10 ergänzt einen lokalen `CMS/lang`-YAML-Fallback im Detailtemplate, damit rohe `cms_experts.detail.*` Keys auch dann nicht erscheinen, wenn der globale Translator den Key unverändert zurückliefert.

### Kernfunktionen

| Bereich | Funktion |
|---------|----------|
| **Basisprofil** | Name, Position, Firma, Kontakt, Biografie, Foto |
| **Verfügbarkeit** | Status, Datum, Stunden/Tagessatz, Konditionen |
| **Technische Expertise** | Sprachen, Frameworks, DBs, Cloud mit Level-Angaben |
| **Karriere-Stationen** | JSON-Repeater mit Firma, Rolle, Zeitraum |
| **Referenzen** | Testimonials, Case Studies, Konferenzvorträge |
| **Service-Angebot** | 7 Checkbox-Flags (Consulting, Implementierung, …) |
| **Zertifikate** | Name, Aussteller, Datum, Ablauf, URL |
| **Badges** | MVP, Zertifiziert, Premium, Custom-Award |
| **Taxonomien** | Fachrichtungen (hierarchisch), Skills (3 Kategorien) |
| **Admin-Backend** | Vollständige CRUD + Meta-Boxes |
| **Member-Dashboard** | Eigenes Profil im Member-Bereich |
| **Shortcode** | `[cms_experts]` |
| **Öffentliche Routen** | `/experts`, `/experts/{id}` |

---

## Dateistruktur

```
cms-experts/
├── cms-experts.php
├── README.md
├── CHANGELOG.md
├── update.json
├── includes/
│   ├── class-database.php
│   ├── class-admin.php
│   ├── class-member-dashboard.php
│   ├── class-meta-boxes.php        # 10+ render_*()-Methoden
│   ├── class-post-type.php
│   ├── class-shortcode.php
│   ├── class-taxonomies.php        # Fachrichtungen + Skill-Kategorien
│   └── class-template-loader.php
├── templates/
│   ├── archive-expert.php
│   ├── expert-card.php
│   └── single-expert.php
└── assets/
    ├── css/
    └── js/
```

---

## Weitere Dokumente

| Dokument | Inhalt |
|----------|--------|
| [DATABASE.md](DATABASE.md) | Alle Tabellen (10+), Schemas, Relationen |
| [HOOKS.md](HOOKS.md) | Actions & Filter |
| [API.md](API.md) | Methoden-Referenz |
| [CHANGELOG.md](CHANGELOG.md) | Versionshistorie |
| [META-FIELDS.md](META-FIELDS.md) | Vollständige Meta-Key-Referenz (100+ Felder) |
| [SECURITY.md](SECURITY.md) | Sicherheitskonzept |

---

## Meta-Felder Kategorien

Das Plugin nutzt `cms_expert_meta` für flexible Zusatzdaten:

| Kategorie | Meta-Keys (Auswahl) |
|-----------|---------------------|
| Basis | `motto`, `work_type`, `timezone` |
| Verfügbarkeit | `available_from`, `weekly_hours`, `min_project_duration`, `max_project_duration`, `payment_terms` |
| Konditionen | `fixed_price`, `time_material`, `travel_model`, `max_travel_km`, `preferred_company_sizes` |
| Badges | `badge_mvp`, `badge_certified`, `badge_premium`, `custom_award` |
| Tech-Expertise | `programming_languages`, `frameworks`, `databases`, `cloud_platforms`, `tools`, `industry_experience` |
| Karriere | `career_stations` (JSON) |
| Referenzen | `testimonials` (JSON), `case_studies` (JSON), `conference_talks` (JSON) |
| Services | `service_consulting`, `service_implementation`, `service_training`, `service_support`, `service_audit`, `service_emergency`, `service_workshops` |
| Netzwerk | `has_subcontractors`, `team_expansion`, `max_team_size`, `total_projects`, `partner_networks` |
| Social | `linkedin`, `xing`, `github`, `gitlab`, `stackoverflow`, `twitter`, `youtube`, `blog_url`, `availability_hours` |

---

## Shortcode

```html
[cms_experts]
[cms_experts limit="12" availability="available"]
[cms_experts skill="PHP" partner_status="partner"]
```

| Attribut | Standardwert | Optionen |
|----------|----------|---------|
| `limit` | `12` | Zahl |
| `availability` | `''` | `available`, `limited`, `unavailable` |
| `skill` | `''` | Skill-Name |
| `partner_status` | `''` | `partner`, `top_partner`, `sponsor` |
| `columns` | `3` | `1`–`4` |

---

## Cross-Plugin-Integration

| Plugin | Richtung | Beschreibung |
|--------|----------|--------------|
| `cms-companies` | → | Experten werden Firmen via `cms_company_experts` zugeordnet |
| `cms-speakers` | ← | Speaker können via `expert_id` mit Experten-Profilen verknüpft werden |
| `cms-events` | ← | Experten als Speaker-Typ `expert` in Events einladbar |
| `cms-organigramm` | ← | Experten als Mitarbeiter-Nodes |
| `cms-jobprofile-generator` | ← | Job-Profile können Skill-Daten aus Expert-Meta nutzen |
