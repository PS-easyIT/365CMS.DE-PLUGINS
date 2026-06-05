# CMS NetImport

CSV-Importer für das 365CMS-Plugin-Ökosystem.

## Zweck

`cms-netimport` importiert vorbereitete Netzwerkdaten nach:

- `cms-companies`
- `cms-experts`
- `cms-speakers`
- `cms-events`

Der Import arbeitet mit vorhandenen CSV-Dateien im Ordner `files_import/` und unterstützt Upserts, Dry-Run-Vorschauen, dateibasierte Update-Erkennung, Header-basierte Alternativdateien, Admin-Ownership sowie optionale Cross-Plugin-Verknüpfungen.

## Vorbereitete Quellen

- `Companies_Beispiel.csv` → Beispiel-Firmen für `cms-companies`
- `MVPs.csv` → MVP-/Award-Träger für `cms-experts`
- `Experts_Beispiel.csv` → zusätzliche Experten jenseits der MVP-Liste
- `Speaker.csv` → Speaker-Profile für `cms-speakers`
- `Events_mit_Speaker.csv` → Events inklusive Speaker-/Expert-Zuordnung für `cms-events`

## Funktionen

- Semikolon-CSV-Parsing mit Header-Normalisierung
- Pflichtspalten-Prüfung pro CSV-Typ vor dem Import
- flexible CSV-Auswahl: anders benannte Dateien im Ordner `files_import/` werden akzeptiert, wenn ihre Header zum Importtyp passen
- Validierungsprofile `strict` / `balanced` / `permissive` mit zeilen- und feldgenauer Vorabprüfung
- Dry-Run / Preview ohne Schreibzugriffe
- Dry-Run nutzt simulierte IDs und Laufzeit-Caches, damit wiederholte Referenzen und Event-Verknüpfungen innerhalb desselben Laufs realitätsnah bewertet werden
- JSON-/Report-Speicherung ist gegen ungültige UTF-8-Zeichen aus CSV-Daten gehärtet und nutzt sichere Fallbacks statt stiller Encode-Fehler
- optionaler CSV-Formula-Guard neutralisiert potenziell gefährliche Spreadsheet-Formeln vor Verarbeitung
- Upsert nach Name/Datum/Website
- automatische Erkennung neuerer CSV-Dateien derselben Dateifamilie als `UPDATE`
- optionale Auto-Anlage fehlender Companies
- Event-Verknüpfung mit importierten oder bei Bedarf automatisch minimal angelegten Speakern und Experts
- Zuordnung aller importierten Datensätze zum ausführenden bzw. aktiven Admin
- persistente Import-Historie pro Lauf mit Zeit, Quelle, Counts, Fehlern und Dry-Run/Live-Status
- Filter in der Historie nach Typ, Dry-Run/Live und Fehlerstatus
- Cleanup-Funktionen für Historie löschen und Reset einzelner Import-Läufe
- `Plugin-Reset`-Ansicht zum Bereinigen der Inhalts-, Meta- und Relationstabellen von Companies, Experts, Speakers und Events
- Reset funktioniert auch für Komplettimporte, weil Cleanup-Daten aus allen Teil-Läufen aggregiert werden
- Transaktionsschutz für Live-Imports
- Lookup- und Parsing-Caches für schnellere Dubletten-, Relations- und Quellenprüfung
- grundlegendes Import-Throttling im Admin
- Admin-Oberfläche unter `/admin/netimport`

## Update-Dateien

Wenn im Ordner `files_import/` eine neuere CSV mit identischem Basisnamen und Datums-Suffix liegt, wird sie automatisch als bevorzugte Quelle verwendet. Beispiele:

- `Speaker.csv` → Basisdatei
- `Speaker_20260328.csv` → neuere Update-Datei
- `Speaker-update-2026-03-29.csv` → ebenfalls als Update-Datei erkannt

In der Admin-Tabelle wird die erkannte Datei inklusive `UPDATE`-Hinweis angezeigt.

Anders benannte CSV-Dateien werden als `CUSTOM`-Quelle erkannt, wenn sie die Pflichtspalten des gewählten Importtyps enthalten. Beispiel: `kundenliste_juni.csv` kann als Companies-Quelle dienen, wenn eine Spalte `name`, `firma` oder `company` vorhanden ist.

## Plugin-Reset

Die Ansicht `/admin/netimport?view=reset` kann Inhalte der Zielplugins bereinigen:

- Companies: Unternehmen, Company-Meta und Company↔Expert-Zuordnungen
- Experts: Experten inklusive Meta, Skills, Zertifikaten, Projekten, Ausbildung und Fachrichtungs-Zuordnungen
- Speakers: Speaker inklusive Themen und manuellen Auftritten
- Events: Events inklusive Meta und Event↔Speaker/Expert-Zuordnungen

Der Reset ist CSRF-geschützt, rate-limitiert und transaktional. Plugin-Einstellungen sowie Preset-/Kategorie-Listen bleiben erhalten; Core-Tabellen werden nicht gelöscht.

## Sicherheits- und Qualitätsmerkmale

- akzeptiert nur lesbare CSV-Dateien innerhalb von `files_import/`
- Dateigröße aktuell auf maximal 10 MB begrenzt
- optionale Quarantäne-Prüfung (MIME, Signatur, Binary-Indikatoren, externer Hook-Scan)
- CSRF-Prüfung vor jedem Run
- DB-basiertes Rate-Limiting für Importstarts
- separates DB-basiertes Rate-Limiting für Historien-Aktionen wie `Reset` und `Historie löschen`
- Ownership-Nachzug auf `user_id` für Companies, Experts, Speakers und Events
- Dry-Run zur Vorabprüfung größerer Importchargen empfohlen

## Import-Historie

Jeder Importlauf wird dauerhaft in einer eigenen Historien-Tabelle gespeichert. Erfasst werden unter anderem:

- Zeitpunkt Start / Ende
- Import-Typ und verwendete Quelldatei
- Basis- oder `UPDATE`-Quelle
- Dry-Run oder Live-Import
- erstellt / aktualisiert / verknüpft / übersprungen / Warnungen / Fehler
- Laufzeit in Millisekunden
- ausführender Admin

Die letzten Läufe werden direkt im Admin unterhalb der Importmaske angezeigt.

Zusätzlich stehen dort jetzt zur Verfügung:

- Filter nach Import-Typ
- Filter nach Dry-Run / Live
- Filter nach Läufen mit oder ohne Fehler
- `Historie löschen`
- `Reset` pro Lauf für gespeicherte, resetbare Datensätze und Event-Verknüpfungen
- aufklappbare Detailansicht pro Lauf mit gespeicherten Meldungen, Teil-Schritten, Cleanup-Daten und Reset-Zusammenfassung
- Confirm-Modal für destruktive Historien-Aktionen statt direkter Sofortausführung

Bereits zurückgesetzte Läufe werden dabei erkannt und nicht erneut scheinbar erfolgreich zurückgesetzt.

## Empfohlene Reihenfolge

1. Companies Beispiel importieren
2. MVPs nach Experts importieren
3. Experts Beispiel importieren
4. Speaker importieren
5. Events importieren

Alternativ steht ein Komplettimport zur Verfügung.
