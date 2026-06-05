# Changelog

## 3.0.4 — 2026-06-04

- Bugfix für flexible CSV-Quellenerkennung: Typbezogene Dateien wie `Experts*.csv` werden nun vor `Experts_Beispiel.csv` gewählt, sofern die Header passen.
- Dateinamen-Prefixes werden importtypspezifisch bewertet, damit `Experts*.csv` nicht mehr versehentlich als MVP-Quelle und andere kompatible CSVs nicht beim falschen Importtyp landen.
- Beispiel-/Musterdateien bleiben als Fallback erhalten, verlieren aber gegen passend benannte produktive CSV-Dateien.

## 3.0.3 — 2026-06-04

- Flexible CSV-Quellenerkennung ergänzt: CSV-Dateien im Ordner `files_import/` müssen nicht mehr wie die Musterdateien heißen, solange sie die Pflichtspalten des gewählten Importtyps enthalten.
- Header-Pflichtfeldprüfung akzeptiert jetzt definierte Alias-Gruppen wie `vorname|first_name`, `nachname|last_name`, `event_name|title` und `wann|event_date`.
- Neue Admin-Tabseite `Plugin-Reset` ergänzt, um Inhalts-, Meta- und Relationstabellen von `cms-events`, `cms-experts`, `cms-speakers` und `cms-companies` gezielt zu bereinigen.
- Plugin-Reset läuft transaktional, CSRF-geschützt, mit separatem Rate-Limit und löscht nur whitelisted Zielplugin-Tabellen; Einstellungen und Preset-/Kategorie-Listen bleiben erhalten.

## 3.0.1 — 2026-05-18

- PHP-Anforderung auf 8.4 angehoben und Plugin-Version auf `3.0.1` aktualisiert.
- Admin-CSRF-Token werden vor der Prüfung konsequent als String behandelt.
- Interne Admin-Bridge-Redirects nutzen HTTP 303 statt impliziter Standardweiterleitung.
- Admin-Ausgaben und Formularziele wurden konsistent mit `ENT_QUOTES` und UTF-8 escaped.
- Statische Inline-Styles wurden in `assets/css/netimport-admin.css` verschoben.
- Importierte URLs blockieren jetzt zusätzlich Credentials, Kontrollzeichen und überlange Werte.
- Admin-UI optisch beruhigt: weniger Symbol-Deko, klarere Textlabels.

## 1.5.0 — 2026-03-28

- Full-Import-Läufe aggregieren Reset-/Cleanup-Daten jetzt vollständig, sodass `Reset` auch nach Komplettimporten zuverlässig arbeitet
- bereits zurückgesetzte Läufe werden als solche erkannt und nicht erneut irreführend verarbeitet
- Historien-Filter bleiben bei `Reset` und `Historie löschen` erhalten
- Historien-Aktionen zusätzlich per eigener Rate-Limit-Spur abgesichert
- Dry-Run simuliert jetzt auch automatisch angelegte Event-Personen und Companies konsistent für Folge-Verknüpfungen
- Historie kennzeichnet `Reset`- und Fehler-Läufe deutlicher und blendet unnötige Reset-Aktionen für Dry-Runs aus
- Dry-Run zählt mehrfach referenzierte, simulierte Companies/Speaker/Experts nicht länger fälschlich mehrfach als neue Datensätze
- Dry-Run vergibt jetzt auch neuen Events simulierte IDs, damit Event↔Speaker/Expert-Verknüpfungen in der Vorschau vollständig mitgezählt werden
- JSON- und Report-Speicherung nutzt jetzt UTF-8-robuste Encode-/Decode-Helper, damit fehlerhafte CSV-Zeichen die Historie und Speaker-JSON-Felder nicht beschädigen
- Import-Historie zeigt jetzt pro Lauf eine aufklappbare Detailansicht mit gespeicherten Meldungen, Teil-Schritten, Cleanup-Daten und Reset-Informationen
- Destruktive Historien-Aktionen (`Reset`, `Historie löschen`) laufen jetzt über ein eigenes Confirm-Modal statt direkter Sofortausführung

## 1.4.0 — 2026-03-28

- Historien-Filter nach Typ, Dry-Run/Live und Fehlerstatus ergänzt
- `Historie löschen` für gespeicherte Importläufe ergänzt
- `Reset` pro Importlauf ergänzt, inklusive Cleanup von gespeicherten Event-Verknüpfungen und erzeugten Datensätzen soweit resetbar

## 1.3.0 — 2026-03-28

- persistente Import-Historie pro Lauf via Datenbanktabelle ergänzt
- Admin-Ansicht für letzte Läufe mit Counts, Fehlern, Dauer und Admin-Zuordnung ergänzt
- Import-Reports speichern jetzt Zeit, Quelle, Dry-Run/Live und Ergebnisdaten dauerhaft

## 1.2.0 — 2026-03-28

- Admin-Ownership für Companies, Experts, Speakers und Events vereinheitlicht
- Event-Import kann fehlende Speaker/Experts minimal anlegen und sofort verknüpfen
- CSV-Validierung, Parsing-Caches, Dry-Run, Update-Erkennung und Run-Throttling ausgebaut

## 1.1.0 — 2026-03-28

- Dry-Run-/Preview-Modus für alle Importtypen ergänzt
- Import-Engine mit Transaktionsschutz und Lookup-Caches neu aufgebaut
- automatische Erkennung datierter Update-CSV-Dateien derselben Dateifamilie ergänzt
- Admin-Oberfläche um Preview-Option, Update-Badges und Run-Throttling erweitert
- CSV-Pflichtspalten-Prüfung und geparste Quellen-Caches ergänzt
- Event-Gruppierung von `md5()` auf stärkeres Hashing umgestellt

## 1.0.0 — 2026-03-28

- Erstveröffentlichung von `cms-netimport`
- Admin-Importer für vorbereitete CSV-Quellen hinzugefügt
- Mapping für Companies, Experts, Speakers und Events umgesetzt
- neue Beispiel-Dateien `Companies_Beispiel.csv` und `Experts_Beispiel.csv` ergänzt
- Cross-Plugin-Verknüpfungen für Company↔Expert und Event↔Speaker/Expert integriert
