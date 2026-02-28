# CMS Events – Aufgaben, Features & Ideen

> Zukünftige Verbesserungen und Feature-Ideen für das Events-Plugin.  
> Priorität: 🔴 Hoch · 🟡 Mittel · 🟢 Niedrig · 🔵 Idee/Vision

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

### 3. SECURITY.md erstellen
- [ ] Auth-Matrix dokumentieren
- [ ] CSRF-Absicherung aller Endpunkte verifizieren
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
