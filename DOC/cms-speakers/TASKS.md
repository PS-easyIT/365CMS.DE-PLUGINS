# CMS Speakers – Aufgaben, Features & Ideen

> Zukünftige Verbesserungen und Feature-Ideen für das Speaker-Plugin.  
> Priorität: 🔴 Hoch · 🟡 Mittel · 🟢 Niedrig · 🔵 Idee/Vision

---

## V2.8.0 – Audit- und Maßnahmenplan

### Ziel des Durchgangs

- [ ] Plugin vollständig auf **365CMS V2.8.0-Zielstand** dokumentarisch und technisch prüfen
- [ ] **Keine Core-Änderungen** vornehmen
- [ ] erkannte Security-, Speed- und Best-Practice-Probleme direkt im Plugin beheben

### Security

- [ ] alle Admin- und Member-Save-Handler für Speaker, Topics und Auftritte identifizieren
- [ ] Ownership-Schutz gegen fremde Speaker-Profile und Unterdatensätze verifizieren
- [ ] Social-, Website-, Video- und Slides-Links validieren und sicher ausgeben
- [ ] Status-, Availability-, Format- und Presence-Werte nur per Whitelist akzeptieren
- [ ] Single- und Card-Templates auf konsequentes Escaping prüfen

### Speed

- [ ] Speaker-Listen, Detailseiten und Member-Dashboard auf N+1-Abfragen prüfen
- [ ] Topic-, Event- und Cross-Plugin-Lookups auf Bündelungspotenzial prüfen
- [ ] wiederholte Tabellenanlage/Migrationen im Init-Pfad auf Optimierungspotenzial prüfen
- [ ] Asset-Ladung und unnötige Initialisierungskosten minimieren

### Best Practices

- [ ] Versions- und Update-Metadaten konsistent halten
- [ ] Cross-Plugin-Abhängigkeiten defensiv mit Guard-Checks absichern
- [ ] Fehlerbehandlung und Fallbacks robust vereinheitlichen
- [ ] Datenformate für Topics, Events und Social-Felder normalisieren
- [ ] Doku in `README.md`, `CHANGELOG.md` und `SECURITY.md` nach Fixes aktuell halten

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
- [x] Asset-Versionierung in Bootstrap und Admin konsistent vereinheitlicht.
- [ ] Ownership-Schutz für Speaker-Unterdatensätze und Template-Escaping noch vollständig vertiefen.

---

## 🔴 Hohe Priorität

### 1. DSGVO-Compliance vervollständigen
- [ ] `dsgvo_export_data` Hook implementieren (Speaker-Profil + Topics + Events)
- [ ] `dsgvo_delete_data` Hook implementieren (Soft-Delete + Anonymisierung)
- [ ] Einverständniserklärung bei Profilerstellung im Member-Bereich
- [ ] HOOKS.md aktualisieren: DSGVO-Hooks dokumentieren

### 2. SECURITY.md erstellen
- [ ] Auth-Matrix (Admin/Member/Guest) für alle Endpunkte
- [ ] CSRF-Absicherung dokumentieren
- [ ] Input-Sanitierung pro Feld
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
