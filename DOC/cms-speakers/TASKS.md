# CMS Speakers – Aufgaben, Features & Ideen

> Zukünftige Verbesserungen und Feature-Ideen für das Speaker-Plugin.  
> Priorität: 🔴 Hoch · 🟡 Mittel · 🟢 Niedrig · 🔵 Idee/Vision

---

## V2.8.0 – Audit- und Maßnahmenplan

### Ziel des Durchgangs

- [ ] Plugin vollständig auf **365CMS V2.8.0-Zielstand** dokumentarisch und technisch prüfen
- [x] **Keine Core-Änderungen** vornehmen
- [x] erkannte Security-, Speed- und Best-Practice-Probleme direkt im Plugin beheben

### Security

- [x] alle Admin- und Member-Save-Handler für Speaker, Topics und Auftritte identifizieren
- [x] Ownership-Schutz gegen fremde Speaker-Profile und Unterdatensätze verifizieren
- [x] Social-, Website-, Mail-, Telefon- und zentrale Social-/Medien-Links validieren und sicher ausgeben
- [ ] Status-, Availability- und Travel-Werte sind gehärtet; offener Rest: `formats` und `presence_type` in Unterdatensätzen explizit whitelisten
- [x] Single- und Card-Templates auf konsequentes Escaping prüfen

### Speed

- [ ] Speaker-Listen, Detailseiten und Member-Dashboard auf N+1-Abfragen prüfen
- [ ] Topic-, Event- und Cross-Plugin-Lookups auf Bündelungspotenzial prüfen
- [x] wiederholte Tabellenanlage/Migrationen im Init-Pfad auf Optimierungspotenzial prüfen
- [ ] Asset-Ladung und unnötige Initialisierungskosten minimieren

### Best Practices

- [x] Versions- und Update-Metadaten konsistent halten
- [x] Cross-Plugin-Abhängigkeiten defensiv mit Guard-Checks absichern
- [x] Fehlerbehandlung und Fallbacks robust vereinheitlichen
- [x] Datenformate für Topics, Events und Social-Felder normalisieren
- [x] Doku in `README.md`, `CHANGELOG.md` und `SECURITY.md` nach Fixes aktuell halten

### Abschlusskriterien

- [ ] keine offenen kritischen Ownership-, Escaping- oder Link-Validierungsprobleme
- [ ] keine offensichtlichen Query-Bremsen in Listen oder Detail-Lookups
- [ ] Doku- und Auditstand konsistent nachvollziehbar

### Audit-Zwischenstand 2026-03-28

- [x] Status-Schema und Anwendungspfade für `pending`/`deleted` harmonisiert und Migration für Bestandsdaten ergänzt.
- [x] `get_speakers()` für `limit`/`offset` und `ORDER BY` mit defensiven Whitelists gehärtet.
- [x] Member-Dashboard für eigene Speaker aller Status sowie Admin-Übersichten konsistent nachgezogen.
- [x] Admin-Save mit Whitelists für `gender`, `travel_radius`, `availability`, `status` und normalisierten Arrays abgesichert.
- [x] Member-Create-Handler für Gender-, Format-, Travel-, Availability-, Topic- und Link-Daten an den gehärteten Save-Standard angeglichen.
- [x] Single-Template für Speaker-Bio auf sichere Textausgabe mit Escaping und Zeilenumbruch-Rendering umgestellt.
- [x] Intern zusammengesetzte Breadcrumb-, Company-, Kontakt- und Register-Links im Single-Template konsequent im Attribut-Kontext escaped.
- [x] Reset- und Pagination-Links im Archive-Template für interne URLs und Query-Parameter konsequent im Attribut-Kontext escaped.
- [x] Detail-Links aus `$speaker_url` im Card-Template ebenfalls konsequent im Attribut-Kontext escaped.
- [x] Auch der CTA-Link aus `$speaker_url` im Card-Template wird jetzt konsequent im Attribut-Kontext escaped.
- [x] Auch Avatar-Gradient im `style`-Attribut und Event-Zähler im `title`-Attribut des Single-Templates werden jetzt konsequent escaped.
- [x] Zentrale `save_speaker()`-Persistenz verweigert für Nicht-Admins Updates auf fremde Speaker-IDs und entschärft damit latente Member-IDOR-Pfade.
- [x] Auch interne Social-Icon-Labels im `title`-Attribut des Single-Templates werden jetzt konsequent escaped.
- [x] Plugin-Bootstrap instanziiert hook-registrierende Klassen jetzt frühzeitig, damit Admin-Sidebar-Eintrag und Admin-Routen nach Aktivierung zuverlässig verfügbar sind.
- [x] Spezial-Handler für Speaker-Event-Unterdatensätze (`admin_event_add`/`admin_event_delete`) als Admin-only mit CSRF-Schutz verifiziert; aktuell kein Member-IDOR-Pfad vorhanden.
- [x] Asset-Versionierung in Bootstrap und Admin konsistent vereinheitlicht.
- [x] Verbleibende Template-Restbefunde für Foto-, Website-, Mail-, Telefon- und Social-Links in Card- und Single-Templates per zusätzlicher Renderzeit-Validierung gegen Alt- und Bestandsdaten gehärtet.
- [x] Aktuellen Ownership-Stand präzisiert: Member-Dashboard bietet derzeit nur Create + Liste, kein realer Member-Edit- oder Event-/Topic-Update-Pfad vorhanden.
- [x] Früher Plugin-Bootstrap zusätzlich gegen Aktivierungs-/Lade-Fatals gehärtet: Komponenten werden nur bei verfügbarem Core-Kontext (`CMS\Hooks`, `CMS\Database`) instanziiert.
- [x] Tabellenaufbau beim Aktivieren vollständig entschärft: Auch Fehler bereits beim initialen Datenbankzugriff in `create_tables()` werden jetzt geloggt statt als Fatal an den Aktivierungs-Flow zurückzureichen.
- [ ] Offener Restbefund: `admin_event_add()` übernimmt `presence_type` für Speaker-Auftritte derzeit noch als freien String; außerdem werden `formats` zwar normalisiert, aber noch nicht gegen feste erlaubte Werte gewhitelistet.
- [x] Heutige Audit-/Doku-Änderungen auf plugin-eigene Release-Versionen umgestellt: Doku-Release `1.0.1`, technisches Audit-/Stabilitäts-Release `1.1.0`.

---

## 🔴 Hohe Priorität

### 1. DSGVO-Compliance vervollständigen
- [ ] `dsgvo_export_data` Hook implementieren (Speaker-Profil + Topics + Events)
- [ ] `dsgvo_delete_data` Hook implementieren (Soft-Delete + Anonymisierung)
- [ ] Einverständniserklärung bei Profilerstellung im Member-Bereich
- [ ] HOOKS.md aktualisieren: DSGVO-Hooks dokumentieren

### 2. SECURITY.md vervollständigen
- [x] Auth-Matrix (Admin/Member/Guest) für alle Endpunkte
- [x] CSRF-Absicherung dokumentieren
- [x] Input-Sanitierung pro Feldtyp dokumentieren
- [ ] Profilbild-Upload-Validierung

### 3. Member-Dashboard erweitern
- [ ] Eigenes Speaker-Profil bearbeiten (alle Felder)
- [ ] Topics verwalten (hinzufügen, entfernen, sortieren)
- [ ] Auftritte / Events verwalten
- [ ] Profil-Vorschau (wie es öffentlich aussieht)
- [ ] Verfügbarkeit direkt umschalten
- [ ] Honorar-Bereich: Tagessatz/Stundensatz aktualisieren

---

## 🟡 Mittlere Priorität

### 4. Speaker-Buchung
- [ ] Buchungsanfrage-Formular auf der Profilseite
- [ ] Strukturierte Anfrage: Event-Typ, Datum, Budget, Thema
- [ ] Anfrage-Verwaltung im Member-Dashboard
- [ ] Status-Tracking: Angefragt → Bestätigt → Abgeschlossen
- [ ] E-Mail-Benachrichtigung bei neuer Anfrage

### 5. Erweiterte Topic-Verwaltung
- [ ] Topic-Taxonomie: Hierarchische Themenstruktur
- [ ] Topic-Tags auf der Archivseite als Filter
- [ ] Topic-Detailseite: Alle Speaker zu einem Thema
- [ ] Trend-Topics: Welche Themen werden am häufigsten genannt?

### 6. Medien & Portfolio
- [ ] Video-Reel: YouTube/Vimeo-Links auf der Profilseite
- [ ] Vortrags-Slides (SlideShare, Speaker Deck-Embed)
- [ ] Testimonials / Referenzen von Veranstaltern
- [ ] Presse-Kit: Fotos, Bio-Versionen (kurz/lang) als Download

### 7. Erweiterte Filter & Suche
- [ ] Filter: Sprache, Format, Honorar-Range, Reiseradius
- [ ] Verfügbarkeits-Filter (nur verfügbare Speaker)
- [ ] Themen-basierte Suche (Fuzzy-Match)
- [ ] Standort-basierte Suche (PLZ-Radius)
- [ ] AJAX-Filterung ohne Page-Reload

### 8. Event-History erweitern
- [ ] Automatische Verknüpfung mit cms-events (bidirektional)
- [ ] Vergangene Auftritte als Timeline auf der Profilseite
- [ ] Statistik: Anzahl Auftritte, Durchschnittsbewertung
- [ ] Teilnehmer-Feedback zu Vorträgen

---

## 🟢 Niedrige Priorität

### 9. Import/Export
- [ ] CSV-Import für Speaker-Profile
- [ ] PDF-Export: Speaker-Profil als One-Pager
- [ ] vCard-Export pro Speaker
- [ ] Sessionize.com-Import (beliebte Speaker-Plattform)

### 10. Public-Seite Verbesserungen
- [ ] Speaker-des-Monats Feature (automatisch oder manuell)
- [ ] Neue Speaker Carousel
- [ ] Schema.org Person-Markup für SEO
- [ ] Rich Snippets: Bewertungen, Expertise, Verfügbarkeit
- [ ] Dark-Mode-Support

### 11. E-Mail & Benachrichtigungen
- [ ] Newsletter: Neue Speaker im Netzwerk
- [ ] Matching-Alert: „Neuer Speaker passt zu Ihren Event-Themen"
- [ ] Erinnerung: Profil aktualisieren (alle 3 Monate)
- [ ] Event-Einladung per E-Mail an passende Speaker

### 12. Cross-Plugin-Vertiefung
- [ ] Speaker → Expert-Profil: Automatische Verlinkung wenn gleiche Person
- [ ] Speaker auf Firmenprofil anzeigen (cms-companies)
- [ ] Speaker-Empfehlung basierend auf Event-Themen (cms-events)
- [ ] Organigramm: Speaker als externe Berater/Referenten (cms-organigramm)

---

## 🔵 Ideen / Vision

### 13. AI-Features
- [ ] Automatische Topic-Vorschläge aus Bio-Text
- [ ] Ähnliche Speaker finden (Embedding-Suche)
- [ ] Vortragstitel-Generator basierend auf Topics
- [ ] Qualitäts-Score: Profilvollständigkeit, Aktivität, Bewertungen

### 14. Event-Plattform-Integration
- [ ] Sessionize.com Sync (bidirektional)
- [ ] SpeakerHub-Integration
- [ ] Conference-Buddy: Speaker für gleiche Konferenz matchen

### 15. Monetarisierung
- [ ] Premium-Speaker-Profile (erweiterte Sichtbarkeit)
- [ ] Featured-Platzierung gegen Gebühr
