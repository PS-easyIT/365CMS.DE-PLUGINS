# Datenbank – CMS M365 Price Tracker

Das Plugin legt ab Version `1.0.2` eine eigene Settings-Tabelle an.

## Tabelle `cms_m365price_tracker_settings`

| Feld | Typ | Beschreibung |
|---|---|---|
| `id` | `INT UNSIGNED AUTO_INCREMENT` | Primärschlüssel |
| `setting_key` | `VARCHAR(96)` | Eindeutiger Setting-Key |
| `setting_value` | `TEXT` | Gespeicherter Wert |
| `updated_at` | `TIMESTAMP` | Änderungszeitpunkt |

## Persistenz

- Persönliche Lizenzpositionen werden clientseitig in `localStorage` unter `m365price-tracker-entries-v1` gespeichert.
- Katalogdaten werden aus JSON-Dateien gelesen.
- Admin-Layout-/Designwerte werden in `cms_m365price_tracker_settings` gespeichert.

## JSON-Kataloge

Primärer Pfad: `cms-m365price-tracker/data/`.
Fallback im aktuellen Repository: `cms-m365tools/data/` – nur als Sicherheitsnetz, falls eine Datei im Plugin-Paket fehlt.
