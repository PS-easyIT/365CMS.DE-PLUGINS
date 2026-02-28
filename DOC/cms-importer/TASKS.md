# CMS Importer – Aufgaben, Features & Ideen

> Zukünftige Verbesserungen und Feature-Ideen für das Importer-Plugin.  
> Priorität: 🔴 Hoch · 🟡 Mittel · 🟢 Niedrig · 🔵 Idee/Vision

---

## 🔴 Hohe Priorität

### 1. Fehlende Dokumentation erstellen
- [ ] API.md erstellen (CMS_Importer, CMS_XML_Parser, CMS_Importer_Admin Klassen)
- [ ] MAPPING.md erstellen (im README referenziert, existiert aber nicht)
- [ ] SECURITY.md erstellen (Upload-Validierung, XML-Injection-Schutz)

### 2. Erweiterte Import-Formate
- [ ] JSON-Import (neben WXR/XML)
- [ ] CSV-Import für einfache Post-Listen
- [ ] Markdown-Import (Verzeichnis mit .md-Dateien)
- [ ] RSS-Feed als Quelle (einmalig oder regelmäßig)

### 3. Fehlerbehandlung verbessern
- [ ] Detaillierte Fehlerberichte pro Beitrag (nicht nur Gesamt)
- [ ] Undo/Rollback: Gesamten Import rückgängig machen
- [ ] Dry-Run-Modus: Import simulieren ohne DB-Änderungen
- [ ] Fortschrittsanzeige bei großen Importen (AJAX mit Progress-Bar)

### 4. Medien-Import erweitern
- [ ] Fernbild-Download mit Retry und Timeout
- [ ] Lokale Bilder: Referenzen auf bereits vorhandene Medien
- [ ] Bildgrößen-Generierung nach Import (Thumbnails)
- [ ] Featured-Image-Zuweisung aus Content oder Meta

---

## 🟡 Mittlere Priorität

### 5. Plugin-Daten-Import
- [ ] CMS-Companies-Import aus CSV/JSON
- [ ] CMS-Experts-Import aus CSV/JSON
- [ ] CMS-Events-Import aus iCal/CSV
- [ ] CMS-Speakers-Import aus CSV
- [ ] Mapping-Konfiguration: Quellfelder → Zielfelder zuordnen

### 6. SEO-Mapping erweitern
- [ ] All-in-One-SEO-Pack Support
- [ ] SEO-Framework Support
- [ ] Meta-Description und Title aus verschiedenen Quellen extrahieren
- [ ] Canonical-URL-Handling beim Import

### 7. Duplikat-Erkennung
- [ ] Prüfung auf bereits importierte Beiträge (via GUID oder Slug)
- [ ] Strategie wählen: Überspringen, Aktualisieren, Neu anlegen
- [ ] Zusammenführen: Fehlende Meta-Daten ergänzen

### 8. Import-Scheduling
- [ ] Zeitgesteuerter Import (z.B. täglich RSS → Posts)
- [ ] Import-Warteschlange für große Dateien
- [ ] Hintergrund-Import via Cron (nicht blockierend)

---

## 🟢 Niedrige Priorität

### 9. Export-Funktionalität
- [ ] CMS-eigenes Export-Format (JSON, vollständig)
- [ ] WXR-Export (WordPress-kompatibel)
- [ ] Selektiver Export (nach Typ, Datum, Kategorie)
- [ ] Backup-Export: Alle Inhalte als ZIP

### 10. Migration-Assistenten
- [ ] Schritt-für-Schritt-Wizard statt einmaliger Upload
- [ ] Feld-Mapping-UI: Drag & Drop Zuordnung
- [ ] Vorschau vor dem Import (erste 5 Einträge)
- [ ] Migrations-Bericht als PDF

### 11. Admin-UX
- [ ] Import-Historie: Alle vergangenen Importe durchsuchen
- [ ] Import-Templates: Wiederverwendbare Konfigurationen
- [ ] Bulk-Import: Mehrere Dateien gleichzeitig
- [ ] Drag & Drop Upload-Zone

---

## 🔵 Ideen / Vision

### 12. AI-gestützter Import
- [ ] Automatische Feld-Erkennung bei unbekannten Formaten
- [ ] Content-Kategorisierung: Beiträge automatisch in CMS-Kategorien einordnen
- [ ] Übersetzung: Importierte Inhalte automatisch übersetzen

### 13. Multi-Source-Import
- [ ] WordPress REST-API als Quelle (Live-Import)
- [ ] Ghost CMS Export
- [ ] Medium Export
- [ ] Notion Export
- [ ] Confluence Export
