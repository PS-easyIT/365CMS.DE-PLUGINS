# Datenbank

## Tabellen

### `{prefix}m365lic_packages`

Speichert den Lizenzkatalog.

| Feld | Typ | Zweck |
|---|---|---|
| `slug` | varchar(120) | Eindeutiger SKU-Key |
| `name` | varchar(255) | Anzeigename |
| `kind` | varchar(20) | `base` oder `addon` |
| `category` | varchar(80) | Logische Kategorie |
| `audience` | varchar(30) | `knowledge`, `frontline`, `all` |
| `features_json` | longtext | Aktivierte Features |
| `tags_json` | longtext | Kompatibilitäts-/Klassifizierungs-Tags |
| `prerequisite_tags_json` | longtext | Voraussetzungs-Tags für Add-ons |
| `public_price/member_price/group_price` | decimal(10,2) nullable | Preis je Tier |

### `{prefix}m365lic_settings`

Key-Value-Settings für Route, Limits, Exporttexte und Pricing-Kontext.

### `{prefix}m365lic_usage_limits`

Speichert die Tageslimits pro Actor.

| Feld | Typ | Zweck |
|---|---|---|
| `action_key` | varchar(50) | `evaluation` oder `pdf_export` |
| `actor_hash` | char(64) | Hash aus User-ID oder IP |
| `pricing_tier` | varchar(20) | `public`, `member`, `group` |
| `date_key` | char(8) | Tag im Format `Ymd` |
| `hits` | int | Anzahl Aufrufe am Tag |
