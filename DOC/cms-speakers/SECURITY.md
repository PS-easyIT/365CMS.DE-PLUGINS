# CMS Speakers – Sicherheitskonzept für 365CMS V2.8.0

## Zweck

Dieses Dokument beschreibt den Sicherheits-Zielstand und die Audit-Schwerpunkte für `cms-speakers` im Rahmen der Anpassung an **365CMS V2.8.0**.

Schwerpunkte sind:

- Speaker-CRUD im Admin- und Member-Bereich
- Topic- und Event-History-Verwaltung
- Cross-Plugin-Links zu Experts, Companies und Events
- Social-/Media-/Link-Felder
- öffentliche Single- und Archive-Ausgaben

## Authentifizierung & Autorisierung

| Bereich | Erwartete Prüfung |
|---------|-------------------|
| Admin-Backend | `CMS\Auth::instance()->isAdmin()` |
| Member-Dashboard | `CMS\Auth::instance()->isLoggedIn()` |
| Eigenes Speaker-Profil bearbeiten | aktuell kein Member-Edit-Pfad; Bearbeiten derzeit nur im Admin-Backend |
| Fremde Speaker bearbeiten | nur Admin |
| Event-/Topic-Zuordnungen pflegen | derzeit nur Admin |
| Öffentliche Profilseiten | ohne Login lesbar |

## CSRF-/Nonce-Schutz

Alle schreibenden Operationen müssen ein gültiges Token prüfen:

- Speaker anlegen
- Speaker bearbeiten
- Topics ändern
- Speaker-Events / Auftritte ändern
- Status oder Verfügbarkeit ändern
- Member-Aktionen im Dashboard

Beispiel-Zielmuster:

```php
$token = CMS\Security::instance()->generateToken('speakers_form');

if (!CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'speakers_form')) {
    throw new RuntimeException('Ungültiger Sicherheits-Token.');
}
```

## Eingabevalidierung

| Feldtyp | Erwartete Behandlung |
|---------|----------------------|
| IDs | Integer-Cast + Existenz-/Ownership-Prüfung |
| Namen / Titel / Position | Text-Sanitizing |
| Social-/Website-Links | nur valide HTTP/HTTPS-URLs |
| Honorar / Reichweite / Zahlenfelder | numerisch validieren |
| Status / Availability / Presence-Typ | Whitelist verwenden |
| Topics / Formate | normalisierte Arrays oder definierte Strings |
| Freitext / Bio | kontrolliertes HTML oder sicheres Textformat |
| Datum / Event-Bezug | Format- und Referenzvalidierung |

## Ausgabe-Escaping

- alle Textinhalte HTML-sicher ausgeben
- Attributwerte gesondert escapen
- URLs nur nach Validierung rendern
- Social-Links und Medienlinks nicht roh ausgeben
- Bio- und Beschreibungstexte auf Single-Seiten nicht roh rendern, sondern als Text escapen und nur kontrolliert mit `nl2br()` umbrechen
- intern zusammengesetzte Profil-, Kontakt- und Register-Links im `href`-Attribut-Kontext escapen
- Reset-, Filter- und Pagination-Links im Archiv mit zusammengesetzten Query-Parametern ebenfalls nur escaped in `href` ausgeben
- auch Detail-Links aus internen Variablen wie `$speaker_url` in Cards nur escaped im `href`-Attribut rendern
- auch CTA-Links in Cards bei internen URL-Variablen nur escaped im `href`-Attribut rendern
- auch dynamische Gradientwerte in `style`-Attributen und zusammengesetzte Tooltip-Texte in `title`-Attributen im Single-Template escapen
- auch interne Social-Icon-Labels im `title`-Attribut des Single-Templates im Attribut-Kontext escapen
- Foto-, Website-, Mail-, Telefon- und Social-Link-Felder auf öffentlichen Renderpfaden zusätzlich zur Eingabe-Sanitierung nochmals zur Laufzeit validieren, damit auch Alt- und Bestandsdaten keine unsicheren Attributwerte erzeugen
- zentrale Save-Pfade für Speaker müssen bei Updates für Nicht-Admins den `user_id`-Besitz des Datensatzes gegenprüfen und fremde IDs verwerfen
- Topic- und Event-History-Ausgaben gegen XSS absichern

## SQL-Sicherheit

- parametrisierte Queries oder sichere DB-Helper verwenden
- `LIMIT`, `OFFSET`, Sortierung und Filter whitelisten
- keine ungeprüften Request-Werte in Query-Fragmente einbauen
- Cross-Plugin-Joins robust gegen fehlende Tabellen/Datensätze gestalten

## Ownership- und IDOR-Schutz

Besonders zu prüfen:

- Member darf keine fremden Speaker-Profile bearbeiten
- Member darf keine fremden Topic-/Event-Einträge manipulieren; aktuell existiert dafür kein öffentlicher Member-Update-Pfad
- Speaker- und Event-IDs aus Request-Daten müssen immer gegen Rechte und Existenz geprüft werden

## Cross-Plugin-Sicherheit

Für `expert_id`, `company_id` und `cms_event_id` gilt:

- Referenz nur nutzen, wenn Zielplugin aktiv ist
- Ziel-ID validieren
- Ausfälle defensiv behandeln
- keine harten Annahmen über externe Tabellen oder Feldverfügbarkeit treffen

## Datei- und Medienbezug

- Speaker-Bilder nur über sichere Referenzen oder valide URLs verwenden
- keine direkten Dateipfade aus Benutzereingaben übernehmen
- bei späterem Upload: MIME, Endung, Größe, Ownership und Herkunft prüfen

## Berechtigungsmatrix

| Aktion | Admin | Member (eigenes Profil) | Gast |
|--------|-------|--------------------------|------|
| Speaker ansehen | ✅ | ✅ | ✅ |
| Profil erstellen | ✅ | ✅ sofern vorgesehen | ❌ |
| Eigenes Profil bearbeiten | ✅ | ❌ (derzeit kein Member-Edit-Pfad) | ❌ |
| Fremdes Profil bearbeiten | ✅ | ❌ | ❌ |
| Topics / Auftritte verwalten | ✅ | ❌ | ❌ |
| Status / Verfügbarkeit ändern | ✅ | ❌ | ❌ |
| Profil löschen | ✅ | ❌ bzw. nur definierter Self-Service-Flow | ❌ |

## Audit-Checkliste für V2.8.0

- [ ] Save-Handler für Speaker, Topics und Auftritte auf Token- und Rechteprüfung geprüft
- [ ] Social-/Website-/Video-/Slides-Links validiert
- [ ] Verfügbarkeits- und Statuswerte per Whitelist gehärtet
- [ ] Member-Ownership auf Profil- und Unterdatensatzebene verifiziert
- [ ] Single-/Card-Templates auf konsequentes Escaping geprüft
- [ ] Cross-Plugin-Referenzen defensiv abgesichert
- [ ] Versions- und Dokumentationsstand an V2.8.0 angepasst
