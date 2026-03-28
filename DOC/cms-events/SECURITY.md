# CMS Events – Sicherheitskonzept für 365CMS V2.8.0

## Zweck

Dieses Dokument beschreibt den Sicherheits-Zielstand und die Audit-Prüfpunkte für `cms-events` im Rahmen der Anpassung an **365CMS V2.8.0**.

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
| Speaker zuordnen / entfernen | nur Admin oder berechtigter Owner-Flow |
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

## SQL-Sicherheit

- ausschließlich parametrisierte Queries oder sichere DB-Helper verwenden
- `LIMIT` und `OFFSET` niemals ungeprüft aus Request-Daten übernehmen
- Sortier- und Filterwerte über Whitelists absichern
- Tabellennamen nur aus dem internen Präfix-System zusammensetzen

## Ownership- und IDOR-Schutz

Besonders zu prüfen:

- Member darf keine fremden Events über manipulierte IDs bearbeiten
- Speaker-Zuordnungen dürfen nicht auf fremde oder unzulässige Datensätze zeigen
- Delete-/Status-Aktionen müssen die Ziel-ID und Berechtigung zusammen prüfen

## Externe Links und Kalenderdaten

Besonderheiten von `cms-events`:

- `registration_url`, `online_url`, `organizer_website` und ähnliche Felder müssen validiert werden
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
| Speaker zuordnen | ✅ | eingeschränkt nach Flow | ❌ |
| Event löschen / Status ändern | ✅ | eingeschränkt nach Flow | ❌ |

## Audit-Checkliste für V2.8.0

- [ ] alle Event-Save-Handler auf Token- und Rechteprüfung geprüft
- [ ] Member-Ownership für Edit/Delete geprüft
- [ ] URL-Felder validiert und beim Rendern sicher escaped
- [ ] numerische Felder (`capacity`, `price`, IDs, Pagination) gehärtet
- [ ] Kalender-/Filter-Links auf sichere Parametrisierung geprüft
- [ ] Speaker-, Kategorien- und Preset-Aktionen gegen CSRF und IDOR geprüft
