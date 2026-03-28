# CMS Experts – Sicherheitskonzept für 365CMS V2.8.0

## Zweck

Dieses Dokument beschreibt den Sicherheits-Zielstand und die Audit-Schwerpunkte für `cms-experts` im Rahmen der Anpassung an **365CMS V2.8.0**.

Besonders kritisch sind:

- umfangreiche Admin- und Member-Formulare
- sehr viele Meta-Felder mit unterschiedlichen Datentypen
- JSON-Repeater und Listenfelder
- Cross-Plugin-Verknüpfungen zu Companies, Speakers und Events
- öffentliche Profilseiten mit umfangreicher HTML-Ausgabe

## Authentifizierung & Autorisierung

| Bereich | Erwartete Prüfung |
|---------|-------------------|
| Admin-Backend | `CMS\Auth::instance()->isAdmin()` |
| Member-Dashboard | `CMS\Auth::instance()->isLoggedIn()` |
| Eigenes Expertenprofil bearbeiten | nur Owner oder Admin |
| Fremde Expertenprofile bearbeiten | nur Admin |
| Taxonomien / Skill-Presets verwalten | nur Admin |
| Öffentliche Profilseiten | ohne Login lesbar |

## CSRF-/Nonce-Schutz

Alle schreibenden Operationen müssen ein gültiges CSRF-Token prüfen:

- Profil erstellen
- Profil bearbeiten
- Status ändern
- Meta-Daten speichern
- Skills / Fachrichtungen / Zertifikate / Projekte / Ausbildung ändern
- Member-Aktionen im Dashboard

Beispiel-Zielmuster:

```php
$token = CMS\Security::instance()->generateToken('experts_form');

if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'experts_form')) {
    throw new RuntimeException('Ungültiger Sicherheits-Token.');
}
```

## Eingabevalidierung nach Feldtypen

| Feldtyp | Erwartete Behandlung |
|---------|----------------------|
| IDs / Owner-Referenzen | Integer-Cast + Ownership-/Existenzprüfung |
| Namen / Position / Stadt | Text-Sanitizing, sinnvolle Längen |
| E-Mail | E-Mail-Validierung |
| URLs / Social Links | nur valide HTTP/HTTPS-URLs |
| numerische Werte | Integer/Float validieren, keine negativen Werte falls fachlich unzulässig |
| JSON-Repeater | Struktur normalisieren, Whitelist pro Schlüssel |
| Checkbox-/Bool-Felder | konsequent auf `0/1` normalisieren |
| Status / Availability / Partner-Status | nur Whitelist-Werte zulassen |
| WYSIWYG-/Bio-Felder | erlaubtes HTML streng kontrollieren |

## Meta-Whitelist

Da `cms-experts` sehr viele Meta-Schlüssel verarbeitet, muss gelten:

- nur bekannte Meta-Keys dürfen gespeichert werden
- pro Meta-Key muss ein definierter Sanitizing-Typ existieren
- unbekannte Felder werden verworfen
- Array-/JSON-Felder dürfen nicht roh gespeichert werden

## Ausgabe-Escaping

Für Profilseiten und Cards gilt:

- Textinhalte HTML-sicher ausgeben
- Attributwerte strikt escapen
- Links getrennt validieren und escapen
- Rich-Text nur in kontrolliertem Umfang rendern
- Biografie- und ähnliche Langtexte auf Single-Seiten nicht roh rendern, sondern als Text escapen und nur kontrolliert mit `nl2br()` umbrechen
- intern zusammengesetzte Profil-, Kontakt- und Register-Links im `href`-Attribut-Kontext escapen
- auch Detail-Links aus internen Variablen wie `$url` in Cards nur escaped im `href`-Attribut rendern
- auch CTA-Links in Cards und Reset-Links im Archiv bei internen URL-Werten nur escaped im `href`-Attribut rendern
- auch dynamische Gradientwerte in `style`-Attributen und zusammengesetzte Tooltip-Texte in `title`-Attributen im Single-Template escapen
- zentrale Save-Pfade für Experten müssen bei Updates für Nicht-Admins den `user_id`-Besitz des Datensatzes gegenprüfen und fremde IDs verwerfen
- eingebundene externe Feed-/RSS-Daten robust gegen Fehler und unerwartete Inhalte behandeln

## SQL-Sicherheit

- ausschließlich parametrisierte Statements oder sichere DB-Helper
- `LIMIT`, `OFFSET` und Sortierung nur nach harter Typisierung/Whitelist
- keine ungeprüften Request-Werte in SQL-Fragmente übernehmen
- Relationstabellen mit sicheren IDs befüllen

## Ownership- und IDOR-Schutz

Audit-Schwerpunkte:

- Member darf nur das eigene Profil ändern
- Member darf keine fremden Zertifikate, Projekte oder Skill-Datensätze manipulieren
- Profil-IDs aus URLs oder Formularen müssen stets gegen den eingeloggten User geprüft werden
- Admin- und Member-Flows dürfen nicht vermischt werden

## Cross-Plugin-Sicherheit

Bei Referenzen auf `company_id`, `expert_id`, Event-Bezüge oder Speaker-Verknüpfungen gilt:

- Ziel-Datensätze nur verwenden, wenn das Ziel-Plugin aktiv ist
- IDs validieren
- keine Annahme, dass Fremdtabellen immer existieren
- Fallbacks statt fataler Fehler

## Datei- und Medienbezug

- Profilbilder sollen nur über sichere CMS-Medienpfade oder valide URLs referenziert werden
- keine ungeprüften Uploads oder direkten Dateipfade übernehmen
- bei späteren Upload-Workflows: MIME, Endung, Größe und Ownership prüfen

## Berechtigungsmatrix

| Aktion | Admin | Member (eigenes Profil) | Gast |
|--------|-------|--------------------------|------|
| Experten ansehen | ✅ | ✅ | ✅ |
| Profil erstellen | ✅ | ✅ sofern vorgesehen | ❌ |
| Eigenes Profil bearbeiten | ✅ | ✅ | ❌ |
| Fremdes Profil bearbeiten | ✅ | ❌ | ❌ |
| Status ändern | ✅ | eingeschränkt nach Flow | ❌ |
| Skill-/Preset-Verwaltung | ✅ | ❌ | ❌ |
| Soft-Delete | ✅ | ❌ bzw. nur definierter Self-Service-Flow | ❌ |

## Audit-Checkliste für V2.8.0

- [ ] Versionsinkonsistenzen dokumentiert und bereinigt
- [ ] Save-Handler je Feldtyp auf Sanitizing geprüft
- [ ] Ownership-Prüfungen im Member-Dashboard verifiziert
- [ ] JSON-/Repeater-Felder gegen Strukturfehler gehärtet
- [ ] RSS-/externe Inhaltsausgabe robust abgesichert
- [ ] Profilseiten auf konsequentes URL-/HTML-/Attribut-Escaping geprüft
- [ ] Cross-Plugin-Referenzen defensiv abgesichert
