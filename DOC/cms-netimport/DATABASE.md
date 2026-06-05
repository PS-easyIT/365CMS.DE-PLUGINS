# DATABASE

`cms-netimport` legt die Historientabelle `cms_netimport_runs` an und nutzt zusätzlich whitelisted Zieltabellen der Import-Plugins.

## Genutzte Zieltabellen

### cms-companies
- `cms_companies`
- `cms_company_experts`
- `cms_company_meta`

### cms-experts
- `cms_experts`
- `cms_expert_meta`
- `cms_expert_skills`
- `cms_expert_certifications`
- `cms_expert_projects`
- `cms_expert_education`
- `cms_expert_specialization_rel`

### cms-speakers
- `cms_speakers`
- `cms_speaker_topics`
- `cms_speaker_events`

### cms-events
- `cms_events`
- `cms_event_speakers`
- `cms_event_meta`

## Strategie

- Upsert über vorhandene Plugin-Datenbankklassen
- direkte Dubletten-Suche per Name/Website/Datum
- Beziehungen nur anlegen, wenn Ziel-Datensätze vorhanden sind oder optional erzeugt werden dürfen

## Plugin-Reset

Der Plugin-Reset löscht ausschließlich Daten aus den oben genannten Inhalts-, Meta- und Relationstabellen. Einstellungen, Presets und Kategorie-Tabellen bleiben erhalten, damit Design-/Admin-Konfigurationen nicht versehentlich verloren gehen.
