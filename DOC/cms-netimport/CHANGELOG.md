# CHANGELOG

## 3.0.4 — 2026-06-04

- Bugfix für die flexible CSV-Erkennung: `Experts*.csv` und andere typbezogene CSV-Namen werden nun gegenüber Beispiel-/Musterdateien bevorzugt.
- Importtypspezifische Dateinamen-Aliasse verhindern, dass eine kompatible Experts-Datei beim MVP-Import oder einem anderen falschen Ziel landet.

## 3.0.3 — 2026-06-04

- Flexible CSV-Erkennung per Header-Kompatibilität ergänzt, damit korrekt formatierte CSVs im Ordner `files_import/` auch mit abweichendem Dateinamen importiert werden können.
- Admin-Ansicht `Plugin-Reset` ergänzt, um Inhalts-, Meta- und Relationstabellen von Companies, Experts, Speakers und Events gezielt zu bereinigen.
- Plugin-Reset mit CSRF, eigenem Rate-Limit, Confirm-Checkbox, Confirm-Modal und Datenbanktransaktion abgesichert.

## 3.0.2 — 2026-05-25

- Stabilitätsfix: `CMS_NetImport_Importer` ist gegen versehentliches erneutes Laden geschützt, damit Doppel-Include-Pfade keine `Cannot redeclare class`-Fatals erzeugen.

## 3.0.1 — 2026-05-18

- Security-/UX-Pass für 365CMS v3.x.x und PHP 8.4.
- CSRF-, Redirect-, Output-Escaping- und Admin-Style-Härtungen dokumentiert.
- URL-Normalisierung beim CSV-Import gegen Credentials und Kontrollzeichen verschärft.

## 3.0.0 — 2026-05-17

- 365CMS-3.x-Security-Baseline für NetImport dokumentiert.
- DB-Rate-Limits, CSV-Pfad-Containment und sichere öffentliche URL-Normalisierung ergänzt.

## 1.0.0 — 2026-03-28

- neues Plugin `cms-netimport` dokumentiert
- Admin-Import für vorbereitete Netzwerk-CSV-Dateien beschrieben
- Beispiel-Dateien für Companies und zusätzliche Experts ergänzt
