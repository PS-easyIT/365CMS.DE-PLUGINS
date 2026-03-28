# CMS Experts – Aufgaben, Features & Ideen

> Zukünftige Verbesserungen und Feature-Ideen für das Experten-Plugin.  
> Priorität: 🔴 Hoch · 🟡 Mittel · 🟢 Niedrig · 🔵 Idee/Vision

---

## V2.8.0 – Audit- und Maßnahmenplan

### Ziel des Durchgangs

- [ ] Plugin vollständig auf **365CMS V2.8.0-Zielstand** dokumentarisch und technisch prüfen
- [x] **Keine Core-Änderungen** vornehmen
- [x] erkannte Security-, Speed- und Best-Practice-Probleme direkt im Plugin beheben

### Security

- [x] alle Admin- und Member-Save-Handler identifizieren
- [ ] Meta-Whitelist und Sanitizing pro Feldtyp finalisieren, insbesondere für Enum-/Meta-Reste wie `partner_status`
- [x] Ownership-Schutz für Member-Profilbearbeitung gegen fremde IDs verifizieren
- [ ] JSON-/Repeater-Felder strukturell validieren
- [x] Social-, Website- und Feed-URLs validieren und sicher ausgeben
- [x] Rich-Text-/Biografie-Ausgabe auf kontrolliertes HTML und korrektes Escaping prüfen

### Speed

- [ ] Expertenlisten, Detailseiten und Member-Ansichten auf N+1-Abfragen prüfen
- [ ] Meta-, Skills- und Specialization-Lookups auf Bündelungspotenzial prüfen
- [x] wiederholte Initialisierungs- und Migrationspfade auf unnötige Laufzeitkosten prüfen
- [ ] Template-Ausgabe und Asset-Ladung nur in benötigten Kontexten sicherstellen

### Best Practices

- [x] Versionsabweichung zwischen Plugin-Header, Konstante, Klassenwerten und `update.json` bereinigen
- [ ] Availability- und Partner-Status-Konsistenz zwischen Formular, Save-Whitelist und Frontend-Badges bereinigen
- [ ] Cross-Plugin-Referenzen defensiv mit Guard-Checks absichern
- [x] Fehlerbehandlung ohne fatale Seiteneffekte vereinheitlichen
- [x] Doku in `README.md`, `CHANGELOG.md` und `SECURITY.md` nach Fixes aktuell halten

### Abschlusskriterien

- [ ] keine offenen kritischen Ownership-/Input-Validierungsprobleme
- [ ] keine offensichtlichen Performance-Bremsen in Listen und Detail-Lookups
- [ ] Versions- und Dokumentationsstand konsistent nachvollziehbar

### Audit-Zwischenstand 2026-03-28

- [x] Versionsabweichung zwischen Plugin-Header, Konstante, Klassenwert und `update.json` auf den plugin-eigenen Release-Stand `2.1.0` bereinigt.
- [x] Listenabfragen in der DB-Klasse für `limit`/`offset` defensiv gehärtet.
- [x] Save-Pfad für `availability` auf erlaubte Werte begrenzt und beschädigter Admin-Save-Block repariert.
- [x] Member-Create-Handler für E-Mail-, URL-, Availability-, Skill- und Spezialisierungsdaten an den gehärteten Save-Standard angeglichen.
- [x] Single-Template für Experten-Biografie auf sichere Textausgabe mit Escaping und Zeilenumbruch-Rendering umgestellt.
- [x] Intern zusammengesetzte Breadcrumb-, Kontakt- und Register-Links im Single-Template konsequent im Attribut-Kontext escaped.
- [x] Detail-Links aus `$url` im Card-Template ebenfalls konsequent im Attribut-Kontext escaped.
- [x] Auch CTA-Link aus `$url` im Card-Template sowie Reset-Link im Archive-Template konsequent im Attribut-Kontext escaped.
- [x] Auch Avatar-Gradient im `style`-Attribut und Event-Zähler im `title`-Attribut des Single-Templates konsequent escaped.
- [x] Zentrale `save_expert()`-Persistenz verweigert für Nicht-Admins Updates auf fremde Experten-IDs und entschärft damit latente Member-IDOR-Pfade.
- [x] Auch interne Social-Icon-Labels im `title`-Attribut des Single-Templates werden jetzt konsequent escaped.
- [x] Plugin-Bootstrap instanziiert hook-registrierende Klassen jetzt frühzeitig, damit Admin-Sidebar-Eintrag und Admin-Routen nach Aktivierung zuverlässig verfügbar sind.
- [x] Schema-Initialisierung auf versionsgesteuerte Prüfung reduziert, um unnötige Laufzeitkosten zu vermeiden.
- [x] Asset-Versionierung in Bootstrap und Admin konsistent vereinheitlicht.
- [x] Foto-, Website-, Mail-, Telefon- und Social-Link-Felder in Single- und Card-Templates per zusätzlicher Renderzeit-Validierung gegen Alt- und Bestandsdaten gehärtet.
- [x] Ownership-Stand präzisiert: Member-Dashboard bietet derzeit nur Create + Liste; kein realer Member-Edit- oder Repeater-Update-Pfad vorhanden, zentrale `save_expert()`-Härtung bleibt als Vorsorge aktiv.
- [x] Früher Plugin-Bootstrap zusätzlich gegen Aktivierungs-/Lade-Fatals gehärtet: Komponenten werden nur bei verfügbarem Core-Kontext (`CMS\Hooks`, `CMS\Database`) instanziiert.
- [x] Tabellenaufbau beim Aktivieren vollständig entschärft: Auch Fehler bereits beim initialen Datenbankzugriff in `create_tables()` werden jetzt geloggt statt als Fatal an den Aktivierungs-Flow zurückzureichen.
- [ ] Offener Restbefund: Das Admin-Formular bietet bei `availability` noch `unavailable`, Save- und Frontend-Pfade arbeiten aber bereits mit `booked`; zusätzlich sollte `partner_status` als Meta-Enum explizit gewhitelistet werden.
- [x] Heutige Audit-/Doku-Änderungen auf plugin-eigene Release-Versionen umgestellt: Doku-Release `2.0.1`, technisches Audit-/Stabilitäts-Release `2.1.0`.

---

## 🔴 Hohe Priorität

### 1. Versions-Diskrepanz beheben
- [x] Plugin-Header, Konstante, Klassenwert und `update.json` auf den plugin-eigenen Release-Stand `2.1.0` vereinheitlichen

### 2. SECURITY.md vervollständigen
- [x] Auth-Matrix (Admin/Member/Guest) dokumentieren
- [x] CSRF-Absicherung aller Admin- und AJAX-Endpunkte
- [x] Input-Sanitierung pro Feldtyp dokumentieren
- [ ] Upload-Validierung für Profilbilder

### 3. DSGVO erweitern
- [ ] `dsgvo_export_data`: Vollständigen Datenexport (alle 50+ Meta-Felder)
- [ ] `dsgvo_delete_data`: Soft-Delete + Anonymisierung statt Hard-Delete
- [ ] Einverständniserklärung bei Profilerstellung
- [ ] Recht auf Berichtigung: Änderungshistorie

### 4. Member-Dashboard erweitern
- [ ] Vollständiges Profil-Editing im Member-Bereich (alle Meta-Felder)
- [ ] Profilbild-Upload direkt im Member-Dashboard
- [ ] Profil-Vorschau: So sieht mein Profil öffentlich aus
- [ ] Profil-Vollständigkeits-Anzeige (% komplett)
- [ ] Benachrichtigung bei Profilbesuchen (optional)

---

## 🟡 Mittlere Priorität

### 5. Experten-Matching
- [ ] Suchmaschine: „Finde den passenden Experten" (Skills, Standort, Budget)
- [ ] Filter-Kombination: Technologie + Verfügbarkeit + Region
- [ ] Matching-Score basierend auf Suchanfrage vs. Profil
- [ ] Empfehlungs-System: „Experten wie dieser"

### 6. Zertifikats-Management
- [ ] Zertifikats-Upload mit Validierungsdatum
- [ ] Automatische Benachrichtigung bei ablaufenden Zertifikaten
- [ ] Zertifikats-Badge-System (Microsoft, AWS, Google, etc.)
- [ ] Zertifikats-Verifikation via API (optional)

### 7. Portfolio & Referenzen
- [ ] Case-Study-Editor mit strukturierten Feldern (Problem, Lösung, Ergebnis)
- [ ] Medien-Upload für Referenzprojekte (Screenshots, Dokumente)
- [ ] Referenz-Anfrage per E-Mail an ehemalige Auftraggeber
- [ ] Portfolio-Galerie auf der Profilseite

### 8. Erweiterte Suche & Filter
- [ ] Faceted Search auf der Archivseite
- [ ] Skill-Tags als klickbare Filter
- [ ] Standort-Karte mit allen Experten
- [ ] Verfügbarkeits-Kalender (wann ist der Experte frei?)
- [ ] AJAX-basierte Filterung ohne Page-Reload



---

## 🟢 Niedrige Priorität

### 10. Import/Export
- [ ] CSV-Import für Massenimport von Experten
- [ ] LinkedIn-Profil-Import (manuell oder API)
- [ ] vCard-Export pro Experte
- [ ] PDF-Profil-Export (als druckbare Visitenkarte / CV)

### 11. Kommunikation
- [ ] Kontaktformular auf der Profilseite (kein offenes E-Mail)
- [ ] Angebotsanfrage: Strukturiertes Formular (Projekt, Budget, Zeitraum)
- [ ] Direktnachrichten zwischen Mitgliedern (Integration mit zukünftigem Messaging-System)
- [ ] Kalender-Buchung: Kennenlerngespräch buchen (Integration Calendly/Cal.com)

### 12. Public-Seite Verbesserungen
- [ ] Experten nach Fachgebiet (Taxonomy-Archive)
- [ ] Rich Snippets / Schema.org für bessere SEO (Person)
- [ ] Dark-Mode-Support

### 13. Gamification
- [ ] Profil-Vollständigkeits-Badge
- [ ] Aktivitäts-Punkte (Profilpflege, Event-Teilnahme, Bewertungen)
- [ ] Level-System (Junior, Senior, Principal, Elite)
- [ ] Leaderboard nach Score

---

## 🔵 Ideen / Vision

### 14. AI-Features
- [ ] Automatische Skill-Vorschläge basierend auf Beschreibung
- [ ] Profil-Zusammenfassung generieren (One-Liner)
- [ ] Ähnliche Experten via Embedding-Suche
- [ ] Trend-Analyse: Welche Skills werden häufiger nachgefragt?

### 15. Marktplatz-Integration
- [ ] Projektausschreibungen: Auftraggeber schreiben Projekte aus, Experten bewerben sich
- [ ] Stundensatz-Verhandlung / Angebotssystem
- [ ] Vertrag & NDA als Template bereitstellen
- [ ] Abrechnung & Zeiterfassung (Integration)

### 16. Team-Bildung
- [ ] Experten zu Teams zusammenstellen
- [ ] Team-Profile mit kombinierten Skills
- [ ] Team-Anfragen: „Ich suche ein 3er-Team für Cloud-Migration"
- [ ] Cross-Plugin: Teams als Firmen-Abteilung in cms-companies
