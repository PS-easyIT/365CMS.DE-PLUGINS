# CMS NetImport

## Überblick

`cms-netimport` ist ein vorbereiteter CSV-Importer für Netzwerkdaten im Plugin-Repository. Er liest definierte Dateien aus `cms-netimport/files_import/`, erkennt neuere datierte Update-Dateien derselben Dateifamilie automatisch und schreibt die Daten in die Ziel-Plugins `cms-companies`, `cms-experts`, `cms-speakers` und `cms-events`.

## Importquellen

- `Companies_Beispiel.csv`
- `MVPs.csv`
- `Experts_Beispiel.csv`
- `Speaker.csv`
- `Events_mit_Speaker.csv`

## Importziele

- Companies: Firmendatensätze inkl. Partner-Flags
- Experts: MVPs und weitere Experten inkl. Skills/Meta
- Speakers: Speaker-Profile inkl. Topics
- Events: Event-Datensätze inkl. Speaker-/Expert-Zuordnungen

## Admin-Nutzung

Pfad: `/admin/netimport`

Optionen:

- bestehende Datensätze aktualisieren
- fehlende Companies automatisch anlegen
- Cross-Plugin-Beziehungen verknüpfen
- fehlende Speaker/Experts aus Event-Zeilen minimal anlegen und direkt verknüpfen
- Dry-Run / Preview ohne Datenbank-Schreibzugriffe

Zusätzlich zeigt die Admin-Tabelle an, ob statt der Basisdatei bereits eine neuere `UPDATE`-CSV verwendet wird.

Alle neu angelegten oder beim Import berührten Datensätze werden nach dem Import dem aktiven Admin über `user_id` zugeordnet.

Unterhalb der Importmaske wird außerdem eine persistente Historie der letzten Läufe angezeigt. Die Reports enthalten Zeit, Quelle, Counts, Fehler/Warnungen, Dry-Run/Live-Modus und den zugeordneten Admin.

Seit `1.5.0` gilt zusätzlich:

- `Reset` funktioniert auch für gespeicherte Komplettimporte zuverlässig
- bereits zurückgesetzte Läufe werden sauber erkannt
- gesetzte Historien-Filter bleiben auch nach `Reset` oder `Historie löschen` erhalten
- Historien-Aktionen besitzen ein eigenes Rate-Limit zusätzlich zum eigentlichen Import-Run-Limit

## Empfohlener Ablauf

1. Companies importieren
2. Experts (MVPs)
3. Experts Beispiel
4. Speakers
5. Events
