# CMS Events – Aufgaben, Features & Ideen

> Zukünftige Verbesserungen und Feature-Ideen für das Events-Plugin.  
> Priorität: 🔴 Hoch · 🟡 Mittel · 🟢 Niedrig · 🔵 Idee/Vision

---

## 365CMS 3.x – Audit- und Maßnahmenplan

### Ziel des Durchgangs

- [x] Plugin vollständig auf **365CMS-3.x-Zielstand** dokumentarisch und technisch prüfen
- [x] **Keine Core-Änderungen** vornehmen
- [x] Security-, Speed- und Best-Practice-Befunde priorisieren und direkt im Plugin beheben

### Security

- [x] Alle Admin- und Member-POST-Flows identifizieren
- [x] CSRF-Absicherung für Event-Save, Statuswechsel, Speaker-Zuordnung, Kategorien und Tag-Presets verifizieren
- [x] Rechteprüfung zwischen Admin und Member sauber trennen
- [x] Ownership-Schutz für Member-Events gegen fremde IDs prüfen
- [x] URL-Felder (`registration_url`, `online_url`, `organizer_website`, Medienlinks) validieren und beim Rendern sicher escapen
- [x] Status-, Typ- und Filterwerte (`status`, `price_type`, `speaker_type`) auf Whitelist-Prüfung prüfen
- [x] Kalender- und Query-Links gegen manipulierte Parameter absichern

### Speed

- [x] Listenabfragen in Archiv, Admin und Member auf sinnvolle Limits/Pagination prüfen
- [x] Speaker-Lookups und Event-Relations gegen fehlende Cross-Plugin-Tabellen absichern
- [x] Wiederholte Initialisierungsarbeit in Bootstrap-/Init-Pfaden prüfen
- [x] Asset-Ausgabe nur dort ausliefern, wo sie wirklich benötigt wird
- [x] wiederholte `filemtime()`-/Asset-Operationen auf unnötige Aufrufe prüfen

### Best Practices

- [x] Versionsangaben zwischen Plugin-Header, Konstante, README und `update.json` konsistent halten
- [x] Redirects, Fehlerpfade und Fallbacks defensiv prüfen
- [x] Datums-, Zahlen- und JSON-Felder vor Persistenz normalisieren
- [x] Cross-Plugin-Abhängigkeiten nur mit Guard-Checks verwenden
- [x] Doku in `README.md`, `CHANGELOG.md` und `SECURITY.md` nach jedem relevanten Fix nachziehen

### Abschlusskriterien

- [x] keine offenen kritischen Security-Befunde im Plugin
- [x] keine offensichtlichen ungebremsten Listen-/Query-Pfade
- [x] alle geänderten Punkte im Changelog und in der Sicherheitsdoku nachvollziehbar dokumentiert

### Audit-Abschluss 2026-05-25

- [x] Version `3.0.3` für den aktuellen CMS-EVENTS-Audit vergeben.
- [x] Klassen-/Bootstrap-Guards gegen doppelte Plugin-Ladepfade ergänzt.
- [x] Settings-Tabelle, INFORMATION_SCHEMA-Migrationen und idempotente Foreign Keys ergänzt.
- [x] Kalenderroute `/events/calendar` mit Template und Styles ausgeliefert.
- [x] Native 365CMS-404/Error-Fallbacks und serverseitiges Logging für Render-/AJAX-Fehler ergänzt.

### Audit-Zwischenstand 2026-03-28

- [x] Member-Dashboard-Zählung für eigene Events so korrigiert, dass Entwürfe nicht fälschlich ausgeblendet werden.
- [x] Event-Listenpfade in der DB-Klasse auf begrenzte `limit`-/`offset`-Werte gehärtet.
- [x] Admin-Save für `status`, `price_type`, URL- und E-Mail-Felder mit Whitelists bzw. Validierung abgesichert.
- [x] Member-Create-Handler für Preis-, URL-, E-Mail- und Tag-Felder an den gehärteten Admin-Standard angeglichen.
- [x] Single-Template für Event-Beschreibung auf sichere Textausgabe mit Escaping und Zeilenumbruch-Rendering umgestellt.
- [x] Intern zusammengesetzte Breadcrumb-, Speaker- und Register-Links im Single-Template konsequent im Attribut-Kontext escaped.
- [x] Reset- und Pagination-Links im Archive-Template für interne URLs und Query-Parameter konsequent im Attribut-Kontext escaped.
- [x] Auch dynamische Gradientwerte im `style`-Attribut des Speaker-Fallbacks im Single-Template konsequent escaped.
- [x] Zentrale `save_event()`-Persistenz verweigert für Nicht-Admins Updates auf fremde Event-IDs und entschärft damit latente Member-IDOR-Pfade.
- [x] Plugin-Bootstrap instanziiert hook-registrierende Klassen jetzt frühzeitig, damit Admin-Sidebar-Eintrag und Admin-Routen nach Aktivierung zuverlässig verfügbar sind.
- [x] Asset-Versionierung in Bootstrap und Admin auf konsistente, dateigeprüfte Nutzung von `filemtime()` vereinheitlicht.
- [x] Speaker-Typen, Kalender-Parameter und Render-Escaping vollständig nachgezogen: `speaker_type` zentral gewhitelistet, Kalender-`month`/`view` validiert und Frontend-URL-/Bild-/Kontaktpfade zusätzlich zur Laufzeit gehärtet.
- [x] Früher Plugin-Bootstrap zusätzlich gegen Aktivierungs-/Lade-Fatals gehärtet: Komponenten werden nur bei verfügbarem Core-Kontext (`CMS\Hooks`, `CMS\Database`) instanziiert.
- [x] Heutige Audit-/Doku-Änderungen auf plugin-eigene Release-Versionen umgestellt: Doku-Release `1.0.2`, technisches Audit-/Stabilitäts-Release `1.1.0`.

---

## 🔴 Hohe Priorität

### 1. Teilnehmer-Management
- [ ] Anmeldeformular auf der Event-Detailseite
- [ ] Teilnehmer-Liste im Admin oder Member (mit Status: angemeldet, bestätigt, abgesagt)
- [ ] Warteliste bei ausgebuchten Events
- [ ] Bestätigungs-E-Mail nach Anmeldung
- [ ] QR-Code-Tickets für Check-in
- [ ] Teilnehmer-Export (CSV/Excel)

### 2. DSGVO-Compliance
- [ ] `dsgvo_export_data` Hook (Teilnehmer-Daten eines Users)
- [ ] `dsgvo_delete_data` Hook (Anmeldungen eines Users löschen)
- [ ] Einverständniserklärung bei Anmeldung
- [ ] Datenschutzhinweis auf Event-Seiten

### 3. SECURITY.md vervollständigen
- [x] Auth-Matrix dokumentieren
- [x] CSRF-Absicherung aller Endpunkte verifizieren
- [ ] Upload-Validierung für Event-Bilder

### 4. Kalender-Integration
- [ ] iCal/ICS-Export pro Event und als Gesamt-Kalender
- [ ] Google Calendar „Hinzufügen"-Button
- [ ] Kalender-Widget auf der Archivseite (Monatsansicht)
- [ ] Recurring Events (wiederkehrende Veranstaltungen)

---

## 🟡 Mittlere Priorität

### 5. Erweiterte Event-Typen
- [ ] Mehrtägige Events mit Agenda/Zeitplan
- [ ] Sessions/Tracks innerhalb eines Events
- [ ] Workshop-Slots mit begrenzter Teilnehmerzahl
- [ ] Keynote vs. Breakout-Sessions unterscheiden

### 6. Zahlungsintegration
- [ ] Bezahl-Events mit Stripe/PayPal-Integration
- [ ] Frühbucher-Rabatte (Early-Bird-Pricing)
- [ ] Gutschein-Codes / Promo-Codes
- [ ] Rechnungs-/Quittungserstellung (PDF)
- [ ] Stornierung + Rückerstattung

### 7. Speaker-Management erweitern
- [ ] Speaker-Einladungen per E-Mail direkt aus dem Event-Admin
- [ ] Speaker-Bestätigung mit eigenem Dashboard
- [ ] Speaking-Slots mit Uhrzeit und Raum
- [ ] Speaker-Biografie direkt im Event-Kontext (Override)

### 8. Erweiterte Filter
- [ ] Datumsfilter (heute, diese Woche, dieser Monat, Zeitraum)
- [ ] Standort-Filter (Stadt, PLZ-Radius)
- [ ] Preisfilter (kostenlos, kostenpflichtig)
- [ ] Format-Filter (online, hybrid, vor Ort)
- [ ] AJAX-basierte Filterung ohne Page-Reload

### 9. Member-Dashboard erweitern
- [ ] Meine Events: Erstellte + Angemeldete Events
- [ ] Event erstellen/bearbeiten im Member-Bereich
- [ ] Vergangene Events mit Teilnehmer-Feedback
- [ ] Event-Vorschläge basierend auf Interessen

---

## 🟢 Niedrige Priorität

### 10. E-Mail-Benachrichtigungen
- [ ] Event-Erinnerung (1 Tag vorher, 1 Stunde vorher)
- [ ] Änderungs-Benachrichtigung bei Event-Updates
- [ ] Absage-Benachrichtigung
- [ ] Nachbereitung (Follow-up-E-Mail mit Materialien)

### 11. Public-Seite Verbesserungen
- [ ] Kartenansicht (Map-View mit allen Veranstaltungsorten)
- [ ] Timeline-Ansicht (chronologisch mit Monats-Trenner)
- [ ] „Ähnliche Events" Empfehlung auf Single-Seite
- [ ] Social-Share-Buttons pro Event
- [ ] Countdown auf der Event-Detailseite

### 12. Import/Export
- [ ] CSV-Import für Massenimport von Events
- [ ] iCal-Import von externen Kalendern
- [ ] WordPress-Event-Plugin-Migration (The Events Calendar, Events Manager)

### 13. Cross-Plugin-Vertiefung
- [ ] Firmenprofile als Veranstalter verlinken (cms-companies)
- [ ] Experten als Teilnehmer/Moderatoren (cms-experts)
- [ ] Event-bezogene Feeds aus cms-feed automatisch verlinken
- [ ] Jobprofile-Generator: Recruiting-Events mit offenen Stellen verknüpfen

---

## 🔵 Ideen / Vision

### 14. Livestream-Integration
- [ ] YouTube/Twitch-Livestream einbetten
- [ ] Virtueller Raum mit Chat
- [ ] Aufzeichnungen nach dem Event bereitstellen

### 15. Gamification
- [ ] Event-Punkte für Teilnahme (Loyalty-Programm)
- [ ] Badges für regelmäßige Teilnehmer
- [ ] Leaderboard der aktivsten Teilnehmer

### 16. Analytics
- [ ] Event-Analytics: Anmeldungen, Views, Conversion-Rate
- [ ] Speaker-Performance: Bewertungen, Anmeldezahlen
- [ ] Trend-Dashboard: Beliebteste Kategorien, Wachstum

### 17. Hybrid-Event-Support
- [ ] Separate Online- und Vor-Ort-Kapazitäten
- [ ] Streaming-Integration für Remote-Teilnehmer
- [ ] Networking-Tool für hybride Teilnehmer
