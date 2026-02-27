# CMS Experts – Meta-Felder-Referenz

Vollständige Referenz aller Meta-Keys in `cms_expert_meta`.

## Basis

| Meta-Key | Typ | Beschreibung |
|----------|-----|--------------|
| `motto` | string | Tagline / Motto |
| `work_type` | string | `freelancer`, `employed`, `agency`, `contractor` |
| `timezone` | string | Zeitzone (z.B. `Europe/Berlin`) |

## Verfügbarkeit & Konditionen

| Meta-Key | Typ | Beschreibung |
|----------|-----|--------------|
| `available_from` | date | Verfügbar-ab-Datum (YYYY-MM-DD) |
| `weekly_hours` | int | Wöchentliche Stunden |
| `min_project_duration` | string | Min. Projektdauer (z.B. `1 Monat`) |
| `max_project_duration` | string | Max. Projektdauer |
| `min_booking_duration` | string | Mindestbuchungsdauer |
| `payment_terms` | string | `net7`, `net14`, `net30`, `net60`, `prepayment` |
| `fixed_price_projects` | bool | Festpreis-Projekte: `1`/`0` |
| `time_material_projects` | bool | T&M-Projekte: `1`/`0` |
| `travel_model` | string | Reisekosten-Modell |
| `max_travel_km` | int | Max. Reisedistanz in km |
| `preferred_company_sizes` | json | Array: `startup`, `sme`, `enterprise`, `public` |

## Partner-Status & Badges

| Meta-Key | Typ | Beschreibung |
|----------|-----|--------------|
| `partner_status` | string | `none`, `partner`, `top_partner`, `sponsor` |
| `badge_mvp` | bool | MVP-Badge |
| `badge_certified` | bool | Zertifiziert-Badge |
| `badge_premium` | bool | Premium-Badge |
| `custom_award` | string | Eigener Award-Text |

## Technische Expertise (JSON-Repeater)

| Meta-Key | Struktur | Beschreibung |
|----------|----------|--------------|
| `programming_languages` | `[{name, level}]` | Programmiersprachen mit Level |
| `frameworks` | `[{name, level}]` | Frameworks & Bibliotheken |
| `databases` | `[{name, level}]` | Datenbanktechnologien |
| `cloud_platforms` | `[{name, level}]` | Cloud-Plattformen |
| `tools` | `[string]` | Bevorzugte Tools (Tags) |
| `industry_experience` | `[string]` | Branchenerfahrung (Tags) |

**Level-Werte:** `beginner`, `intermediate`, `advanced`, `expert`

## Karriere & Referenzen

| Meta-Key | Struktur | Beschreibung |
|----------|----------|--------------|
| `career_stations` | `[{company, position, from, to, location, description}]` | Berufliche Stationen |
| `testimonials` | `[{quote, client_name, client_position, client_company, rating}]` | Kundenstimmen (1–5 Sterne) |
| `case_studies` | `[{title, description, url}]` | Case Studies |
| `conference_talks` | `[{title, event, year, video_url}]` | Konferenzvorträge |

## Service-Angebot (Booleans)

| Meta-Key | Beschreibung |
|----------|--------------|
| `service_consulting` | Beratung / Consulting |
| `service_implementation` | Umsetzung / Implementierung |
| `service_training` | Training & Schulung |
| `service_support` | Support & Wartung |
| `service_audit` | Audit & Review |
| `service_emergency` | Notfall-Support (24/7) |
| `service_workshops` | Workshops & Intensiv-Sessions |

## Netzwerk & Skalierung

| Meta-Key | Typ | Beschreibung |
|----------|-----|--------------|
| `has_subcontractors` | bool | Subunternehmer verfügbar |
| `team_expansion` | bool | Team-Erweiterung möglich |
| `max_team_size` | int | Maximale Teamgröße |
| `managed_team_size` | int | Bisher geführte Teamgröße |
| `total_projects` | int | Anzahl abgeschlossener Projekte |
| `partner_networks` | string | Partner-Netzwerke (Freitext) |

## Online-Präsenz & Social

| Meta-Key | Typ | Beschreibung |
|----------|-----|--------------|
| `linkedin` | URL | LinkedIn-Profil |
| `xing` | URL | XING-Profil |
| `github` | URL | GitHub-Profil |
| `gitlab` | URL | GitLab-Profil |
| `stackoverflow` | URL | Stack Overflow |
| `twitter` | URL | Twitter/X |
| `youtube` | URL | YouTube-Kanal |
| `blog_url` | URL | Blog / RSS-Feed |
| `personal_website` | URL | Persönliche Website |
| `availability_hours` | string | Erreichbarkeitszeiten |
