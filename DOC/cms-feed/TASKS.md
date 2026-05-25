# CMS Feed – Aufgaben, Features & Ideen

> Zukünftige Verbesserungen und Feature-Ideen für das Feed-Plugin.  
> Priorität: 🔴 Hoch · 🟡 Mittel · 🟢 Niedrig · 🔵 Idee/Vision

---

## 🔴 Hohe Priorität

### 1. Cron-Integration vervollständigen
- [x] Automatischer Feed-Abruf via `cms_cron_hourly` Hook sicherstellen
- [x] Konfigurierbare Abruf-Intervalle pro Kanal: reguläre `cron.php`-Ticks reihen nach `fetch_interval` fällige Kanäle ein
- [ ] Admin-Dashboard: Nächster geplanter Abruf anzeigen
- [ ] Fehler-Benachrichtigung per E-Mail bei X aufeinanderfolgenden Fehlern

### 2. OPML-Import/Export
- [ ] OPML-Datei-Upload zum Massenimport von Feeds
- [ ] OPML-Export aller konfigurierten Kanäle
- [ ] Integration in den Katalog-Tab (neben Preset-Import)

### 3. Member-Dashboard
- [ ] Eigenes Member-Widget registrieren (wie companies/experts/speakers)
- [ ] Persönliche Feed-Favoriten / Leseliste
- [ ] Eigene Digest-Konfiguration pro Mitglied
- [ ] Feed-Vorschläge basierend auf Interessen

### 4. DSGVO-Compliance
- [ ] `dsgvo_export_data` Hook implementieren (exportiert Digest-Abos)
- [ ] `dsgvo_delete_data` Hook implementieren (löscht Digest-Abos)
- [ ] Opt-in für Digest-E-Mails mit Double-Opt-in
- [ ] Abmeldelink in jeder Digest-E-Mail

---

## 🟡 Mittlere Priorität

### 5. Erweiterte Digest-Funktionen
- [ ] HTML-Template-Editor für Digest-E-Mails
- [ ] Digest-Vorschau im Admin
- [ ] Mehrere Empfänger pro Digest (kommasepariert oder Verteiler)
- [ ] Digest-Statistiken: Geöffnet, Klicks (Tracking-Pixel optional)
- [ ] Wöchentlicher/monatlicher Digest neben täglichem

### 6. Erweiterte Filterung & Suche
- [ ] Tag-System für Feed-Beiträge (automatisch aus Kategorien/Keywords)
- [ ] Erweiterte Admin-Filter: Datum-Range, nur Fehlerhafte, nur Inaktive
- [ ] Public-Seite: Suchvorschläge / Autocomplete
- [ ] Volltextindex (MySQL FULLTEXT) für bessere Suchperformance
- [ ] Gespeicherte Suchen / Benachrichtigung bei neuen Treffern

### 7. Feed-Qualitätsmanagement
- [ ] Automatische Deaktivierung nach X Fehlern
- [ ] Feed-Validierungs-Check (ist die URL ein gültiger RSS/Atom-Feed?)
- [ ] Feed-Vorschau beim Erstellen (ersten 5 Beiträge anzeigen)
- [ ] Duplikat-Erkennung auf Beitrags-Ebene (ähnliche Titel/Links)

### 8. Performance-Optimierung
- [ ] Batch-Fetch: Alle Kanäle parallel abrufen (async mit Retry)
- [ ] Cache-Layer für Public-Seiten (HTML-Fragment-Cache, 5 min TTL)
- [ ] Lazy-Loading für Beitragsbilder
- [ ] Pagination via AJAX (infinite scroll oder „Mehr laden")

### 9. Public-Seite Verbesserungen
- [ ] Bereichs-Icons auf der Hauptseite als visuelle Navigation
- [ ] „Neueste Beiträge" Sidebar-Widget
- [ ] RSS-Feed der aggregierten Beiträge (Meta-Feed)
- [ ] Social-Share-Buttons pro Beitrag
- [ ] Dark-Mode-Support über CSS-Variablen

---

## 🟢 Niedrige Priorität

### 10. Katalog erweitern
- [ ] Internationale Feeds (EN, FR) als eigene Katalog-Sprache
- [ ] Community-Vorschläge: Nutzer können Feeds vorschlagen
- [ ] Katalog-Update-Mechanismus (neue Feeds nachladen)
- [ ] Feed-Bewertung im Katalog (Qualitätsscore basierend auf Verfügbarkeit)

### 11. Mehrsprachigkeit
- [ ] Admin-UI-Übersetzungen (EN, DE)
- [ ] Public-Template-Strings in Sprachdateien auslagern
- [ ] Automatische Spracherkennung der Feed-Sprache

### 12. API & Integrationen
- [ ] REST-API-Endpunkte für Feeds und Beiträge
- [ ] Webhook bei neuen Beiträgen (für externe Systeme)
- [ ] Integration mit cms-experts: Tech-Feeds passend zum Expertenprofil vorschlagen
- [ ] Integration mit cms-events: Event-bezogene Feeds automatisch zuordnen

### 13. Admin-UX-Verbesserungen
- [ ] Drag & Drop Sortierung für Bereiche
- [ ] Bulk-Aktionen: Mehrere Kanäle aktivieren/deaktivieren/löschen
- [ ] Import-Fortschrittsanzeige (Progress-Bar beim Katalog-Import)
- [ ] Kanal-Favicons automatisch erkennen und anzeigen

-