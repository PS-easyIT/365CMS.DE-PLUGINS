# CMS Experts – Aufgaben, Features & Ideen

> Zukünftige Verbesserungen und Feature-Ideen für das Experten-Plugin.  
> Priorität: 🔴 Hoch · 🟡 Mittel · 🟢 Niedrig · 🔵 Idee/Vision

---

## 🔴 Hohe Priorität

### 1. Versions-Diskrepanz beheben
- [ ] `CMS_EXPERTS_VERSION` Konstante von `1.0.0` auf `2.0.0` aktualisieren
- [ ] `update.json` entsprechend anpassen

### 2. SECURITY.md erstellen
- [ ] Auth-Matrix (Admin/Member/Guest) dokumentieren
- [ ] CSRF-Absicherung aller Admin- und AJAX-Endpunkte
- [ ] Input-Sanitierung pro Feld dokumentieren
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
