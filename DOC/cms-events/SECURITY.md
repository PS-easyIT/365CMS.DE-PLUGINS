# CMS Events – Sicherheitskonzept für 365CMS 3.x

## Zweck

Dieses Dokument beschreibt den Sicherheits-Zielstand und die Audit-Prüfpunkte für `cms-events` im Rahmen des **365CMS-3.x-Audits**. Stand: `3.0.6` vom 2026-05-25.

Der Schwerpunkt liegt auf:

- Event-CRUD im Adminbereich
- Event-CRUD im Member-Bereich
- Speaker-Zuordnungen
- Kategorien und Tag-Presets
- Frontend-Ausgabe, Kalender-Ansichten und externe Links

## Authentifizierung & Autorisierung

| Bereich | Erwartete Prüfung |
|---------|-------------------|
| Admin-Backend | `CMS\Auth::instance()->isAdmin()` |
| Member-Dashboard | `CMS\Auth::instance()->isLoggedIn()` |
| Event eines Members bearbeiten | nur eigenes Event oder Admin |
| Kategorien / Tag-Presets verwalten | nur Admin |
| Speaker zuordnen / entfernen | derzeit nur Admin |
| Öffentliche Archiv- und Detailseiten | ohne Login lesbar |

## CSRF-/Nonce-Schutz

Alle schreibenden Operationen müssen durch ein gültiges CSRF-Token geschützt sein:

- Event erstellen
- Event bearbeiten
- Status ändern
- Speaker zuordnen oder entfernen
- Kategorien anlegen / löschen
- Tag-Presets anlegen / löschen
- Member-Event-Aktionen

Beispiel-Zielmuster:

```php
$token = CMS\Security::instance()->generateToken('events_form');

if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'events_form')) {
    throw new RuntimeException('Ungültiger Sicherheits-Token.');
}
```

## Eingabevalidierung

| Feldtyp | Erwartete Behandlung |
|---------|----------------------|
| IDs | harte Integer-Casts und Wertebereich prüfen |
| Datum/Uhrzeit | Format validieren, keine freien Rohwerte persistieren |
| URLs | nur valide HTTP/HTTPS-URLs zulassen |
| Preis/Kapazität | numerisch validieren, negative Werte ausschließen |
| Titel/Kategorie/Stadt | Text-Sanitizing, definierte Längen bevorzugen |
| Tags | Arrays normalisieren, leere Werte entfernen |
| Status | nur erlaubte Whitelist-Werte akzeptieren |
| Speaker-Typ | nur erlaubte Typen wie `speaker` oder `expert` akzeptieren |

## Ausgabe-Escaping

Vor jeder Ausgabe ist der Kontext zu beachten:

- HTML-Textinhalte escapen
- Attributwerte escapen
- URLs separat und strikt behandeln
- Query-Parameter für Kalender- oder Filterlinks sicher erzeugen
- WYSIWYG-/Langtexte nur über einen kontrollierten, erlaubten HTML-Umfang rendern
- Event-Beschreibungen auf öffentlichen Single-Seiten nicht roh rendern, sondern als Text escapen und nur kontrolliert mit `nl2br()` umbrechen
- Intern zusammengesetzte Detail-, Breadcrumb- und Register-Links ebenfalls im `href`-Attribut-Kontext escapen
- Reset-, Filter- und Pagination-Links im Archiv mit zusammengesetzten Query-Parametern ebenfalls nur escaped in `href` ausgeben
- Dynamische Gradientwerte in `style`-Attributen des Single-Templates ebenfalls nur escaped ausgeben
- Externe Registrierungs-, Online-, Banner-, Bild- und Veranstalter-Kontaktfelder auf öffentlichen Renderpfaden zusätzlich zur Eingabe-Sanitierung nochmals zur Laufzeit validieren
- Zentrale Save-Pfade für Events müssen bei Updates für Nicht-Admins den `user_id`-Besitz des Datensatzes gegenprüfen und fremde IDs verwerfen

## SQL-Sicherheit

- ausschließlich parametrisierte Queries oder sichere DB-Helper verwenden
- `LIMIT` und `OFFSET` niemals ungeprüft aus Request-Daten übernehmen
- Sortier- und Filterwerte über Whitelists absichern
- Tabellennamen nur aus dem internen Präfix-System zusammensetzen
- Schema-Checks laufen über `INFORMATION_SCHEMA`, nicht über vorbereitete `SHOW COLUMNS ... LIKE ?`-Statements
- Foreign-Key-Migrationen müssen idempotent sein und dürfen den Installer bei Zielumgebungsproblemen nicht blockieren
- Plugin-Settings werden primär über `CMS\Services\SettingsService` gelesen/geschrieben; Legacy-Tabellen sind nur Fallbacks.

## Fehlerpfade & Logging

- Öffentliche 404-Pfade nutzen die native 365CMS-404-Renderstrecke mit Fallback-Markup.
- Template-Fehler werden serverseitig protokolliert und über 365CMS-Error-Fallbacks angezeigt.
- AJAX-Endpunkte liefern immer JSON mit passendem HTTP-Status und `X-Content-Type-Options: nosniff`.
- POST-only Admin-Endpunkte liefern bei direkten GET-Aufrufen explizit `405 Method Not Allowed` mit `Allow: POST`.

## Ownership- und IDOR-Schutz

Besonders zu prüfen:

- Member darf keine fremden Events über manipulierte IDs bearbeiten
- Speaker-Zuordnungen dürfen nicht auf fremde oder unzulässige Datensätze zeigen; `speaker_type` ist zentral auf `speaker`/`expert` zu whitelisten
- Delete-/Status-Aktionen müssen die Ziel-ID und Berechtigung zusammen prüfen

## Externe Links und Kalenderdaten

Besonderheiten von `cms-events`:

- `registration_url`, `online_url`, `organizer_website` und ähnliche Felder müssen validiert werden
- Kalender-Parameter wie `month` und `view` dürfen nur über enge Format- und Whitelist-Prüfungen in Query- oder Render-Kontexte gelangen
- ICS-/Kalender-nahe Inhalte dürfen keine ungefilterten Sonderzeichen oder Header-gefährdenden Zeichenketten ausgeben
- Redirects zu externen Registrierungsseiten müssen nur auf validen URLs basieren

## Datei- und Medienbezug

- Bilder und Banner sollen nur als sichere Medien-Referenzen oder valide URLs gespeichert werden
- keine direkte ungeprüfte Dateiverarbeitung im Plugin
- bei späteren Upload-Flows sind MIME-Type, Endung, Größe und Herkunft strikt zu validieren

## Berechtigungsmatrix

| Aktion | Admin | Member (eigene Events) | Gast |
|--------|-------|-------------------------|------|
| Events ansehen | ✅ | ✅ | ✅ |
| Event erstellen | ✅ | ✅ sofern vorgesehen | ❌ |
| Eigenes Event bearbeiten | ✅ | ✅ | ❌ |
| Fremdes Event bearbeiten | ✅ | ❌ | ❌ |
| Kategorien verwalten | ✅ | ❌ | ❌ |
| Tag-Presets verwalten | ✅ | ❌ | ❌ |
| Speaker zuordnen | ✅ | ❌ | ❌ |
| Event löschen / Status ändern | ✅ | eingeschränkt nach Flow | ❌ |

## Audit-Checkliste für 3.0.3

- [x] alle Event-Save-Handler auf Token- und Rechteprüfung geprüft
- [x] Member-Ownership für Edit/Delete geprüft
- [x] URL-Felder validiert und beim Rendern sicher escaped
- [x] numerische Felder (`capacity`, `price`, IDs, Pagination) gehärtet
- [x] Kalender-/Filter-Links auf sichere Parametrisierung geprüft
- [x] Speaker-, Kategorien- und Preset-Aktionen gegen CSRF und IDOR geprüft
- [x] Bootstrap-/Include-Dateien gegen Redeclare-Fatals geschützt
- [x] fehlende Kalender-Route mit Template und responsivem CSS abgedeckt
