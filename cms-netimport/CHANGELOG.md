# Changelog

## 1.1.0 — 2026-03-28

- Admin-Ownership für Companies, Experts, Speakers und Events vereinheitlicht
- Event-Import kann fehlende Speaker/Experts minimal anlegen und sofort verknüpfen
- CSV-Validierung, Parsing-Caches, Dry-Run, Update-Erkennung und Run-Throttling ausgebaut

## 1.0.5 — 2026-03-28

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
