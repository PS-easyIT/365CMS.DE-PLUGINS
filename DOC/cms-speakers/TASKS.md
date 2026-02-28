# CMS Speakers – Aufgaben, Features & Ideen

> Zukünftige Verbesserungen und Feature-Ideen für das Speaker-Plugin.  
> Priorität: 🔴 Hoch · 🟡 Mittel · 🟢 Niedrig · 🔵 Idee/Vision

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
