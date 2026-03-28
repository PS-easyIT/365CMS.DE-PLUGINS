# DATABASE

`cms-netimport` legt keine eigenen Datenbanktabellen an.

## Genutzte Zieltabellen

### cms-companies
- `cms_companies`
- `cms_company_experts`

### cms-experts
- `cms_experts`
- `cms_expert_meta`
- `cms_expert_skills`

### cms-speakers
- `cms_speakers`
- `cms_speaker_topics`

### cms-events
- `cms_events`
- `cms_event_speakers`

## Strategie

- Upsert über vorhandene Plugin-Datenbankklassen
- direkte Dubletten-Suche per Name/Website/Datum
- Beziehungen nur anlegen, wenn Ziel-Datensätze vorhanden sind oder optional erzeugt werden dürfen
