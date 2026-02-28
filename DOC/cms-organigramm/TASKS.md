# CMS Organigramm – Aufgaben, Features & Ideen

> Zukünftige Verbesserungen und Feature-Ideen für das Organigramm-Plugin.  
> Status: 🚧 **In Entwicklung** – Plugin noch nicht implementiert.  
> Priorität: 🔴 Hoch · 🟡 Mittel · 🟢 Niedrig · 🔵 Idee/Vision

---

## 🔴 Hohe Priorität (MVP)

### 1. Grundgerüst implementieren
- [ ] Plugin-Hauptdatei `cms-organigramm.php` mit Singleton-Pattern
- [ ] DB-Tabellen erstellen: `charts`, `nodes`, `edges`, `meta` (lt. DATABASE.md)
- [ ] Admin-Menü-Registrierung via `cms_admin_menu` Hook
- [ ] Admin-Seite: Organigramm-Liste (CRUD)

### 2. Organigramm-Builder (Frontend)
- [ ] Canvas/SVG-basierter visueller Editor
- [ ] Node erstellen/bearbeiten/löschen (Drag & Drop)
- [ ] Edge erstellen/löschen (Verbindungslinien zwischen Nodes)
- [ ] Auto-Layout-Algorithmus (Sugiyama-basiert, lt. BUILDER.md)
- [ ] AJAX-Save: Änderungen persistent speichern

### 3. Node-Typen
- [ ] Person-Node: Name, Titel, Abteilung, Foto
- [ ] Abteilungs-Node: Name, Beschreibung, Farbe
- [ ] Positions-Node: Titel, Ebene – ohne feste Person
- [ ] Cross-Plugin-Nodes: Verknüpfung mit cms-companies/cms-experts

### 4. Export-Funktionen
- [ ] JSON-Export (vollständiges Organigramm)
- [ ] SVG-Export (Vektorgrafik)
- [ ] PNG-Export (Rastergrafik)
- [ ] PDF-Export

### 5. Viewer / Public-Seite
- [ ] Öffentliche Organigramm-Ansicht (read-only)
- [ ] Shortcode `[cms_organigramm id="X"]`
- [ ] ThemeManager-Integration für Standalone-Seite
- [ ] Responsive: Scrollen/Zoomen auf Mobile

---

## 🟡 Mittlere Priorität

### 6. Cross-Plugin-Integration
- [ ] Firmen-Organigramm: Automatisch aus cms-companies-Mitarbeitern generieren
- [ ] Experten einbinden: cms-experts-Profile als Nodes
- [ ] Speaker-Zuordnung: cms-speakers als Referenten in Abteilungen
- [ ] Event-Team: Organisations-Team eines Events als Organigramm

### 7. Erweiterte Editor-Features
- [ ] Undo/Redo im Builder
- [ ] Gruppenauswahl (Multi-Select + Verschieben)
- [ ] Snap-to-Grid
- [ ] Node-Styles: Farbe, Form, Icon anpassbar
- [ ] Edge-Styles: Durchgezogen, gestrichelt, Farbe
- [ ] Kommentare/Notizen an Nodes

### 8. Vorlagen
- [ ] Organigramm-Templates (Klassisch, Matrix, Divisional, Flat)
- [ ] Import aus vorhandenen Daten (CSV mit Hierarchie)
- [ ] Template-Galerie im Admin

### 9. Member-Integration
- [ ] Eigenes Organigramm im Member-Dashboard
- [ ] Abteilungszugehörigkeit für Mitglieder
- [ ] Berechtigungen: Wer darf welches Organigramm bearbeiten?

---

## 🟢 Niedrige Priorität

### 10. Erweiterte Ansichten
- [ ] Listen-Ansicht (flache Hierarchie)
- [ ] Tabellen-Ansicht (Mitarbeiter-Verzeichnis)
- [ ] Kreisförmiges Organigramm
- [ ] Vertikal vs. Horizontal Layout-Umschaltung

### 11. Dokumentation
- [ ] API.md erstellen
- [ ] SECURITY.md erstellen
- [ ] PUBLICSITE.md erstellen
- [ ] INSTALLATION.md erstellen

### 12. Performance
- [ ] Lazy-Loading für große Organigramme (>100 Nodes)
- [ ] Minimap-Navigation
- [ ] Caching der SVG-Ausgabe

### 13. Erweiterte Analyse
- [ ] Reporting-Count pro Ebene
- [ ] Span-of-Control-Analyse
- [ ] Vakanz-Übersicht (unbesetzte Positionen)
- [ ] Altersstruktur-Visualisierung

### 14. Workflow-Integration
- [ ] Genehmigungsprozess: Organigramm-Änderungen müssen freigegeben werden
- [ ] Versionshistorie: Organigramm-Stand zu einem bestimmten Datum
- [ ] Diff-Ansicht: Was hat sich zwischen zwei Versionen geändert?

### 15. Embedding & Sharing
- [ ] iFrame-Embed-Code für externe Seiten
- [ ] Passwortgeschützter Freigabe-Link
- [ ] Live-Vorschau bei Maus-Hover auf Nodes (Profilkarte)
