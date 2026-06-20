# CMS Beratung – Qualitätssicherung

Diese Checkliste dient zur technischen Prüfung des 365CMS Plugins `cms-beratung`.

## Backend Tests

- [ ] Landingpage erstellen
- [ ] Landingpage bearbeiten
- [ ] Landingpage duplizieren
- [ ] Landingpage löschen
- [ ] Landingpage-Vorschlag laden
- [ ] Design Preset anwenden
- [ ] Entwurfs-Vorschau öffnen
- [ ] Section erstellen
- [ ] Section per Drag and Drop sortieren
- [ ] Section duplizieren
- [ ] Section deaktivieren
- [ ] Card erstellen
- [ ] Card per Drag and Drop sortieren
- [ ] Card duplizieren
- [ ] FAQ Eintrag erstellen
- [ ] Formular Anfrage anzeigen
- [ ] Einstellungen speichern
- [ ] JSON Export durchführen
- [ ] JSON Import als neue Landingpage durchführen
- [ ] JSON Import mit Slug-Konflikt prüfen
- [ ] JSON Import mit Bild-Überspringen prüfen
- [ ] JSON Import mit Überschreiben einer bestehenden Landingpage prüfen

## Frontend Tests

- [ ] Hero korrekt anzeigen
- [ ] Hero Bild links nahtlos anzeigen
- [ ] Hero Bild rechts anzeigen
- [ ] Buttons korrekt verlinken
- [ ] Cards in 1 Spalte anzeigen
- [ ] Cards in 2 Spalten anzeigen
- [ ] Cards in 3 Spalten anzeigen
- [ ] Cards in 4 Spalten anzeigen
- [ ] Tablet Darstellung prüfen
- [ ] Mobile Darstellung prüfen
- [ ] FAQ per Klick öffnen
- [ ] FAQ per Tastatur bedienen
- [ ] Kontaktformular erfolgreich absenden
- [ ] Kontaktformular Fehlermeldungen anzeigen
- [ ] Kontaktformular Erfolgsmeldung anzeigen
- [ ] SEO Meta Tags prüfen
- [ ] Open Graph Tags prüfen
- [ ] Twitter Card Tags prüfen
- [ ] FAQ Schema prüfen
- [ ] Service Schema prüfen
- [ ] Breadcrumb Schema prüfen
- [ ] Keine globalen CSS Konflikte
- [ ] Keine JavaScript Fehler in der Konsole

## Performance Tests

- [ ] Frontend CSS wird nur auf Plugin-Landingpages geladen
- [ ] Frontend JavaScript wird nur auf Plugin-Landingpages geladen
- [ ] Backend Drag and Drop JavaScript wird nur im Adminbereich geladen
- [ ] Keine externen CDN-Ressourcen geladen
- [ ] Bilder werden mit `loading="lazy"` ausgegeben, außer Hero-Eager-Bild
- [ ] Renderpfade verwenden bestehende Renderer statt doppelter DB-Abfragen
- [ ] JSON Export enthält keine unnötigen Laufzeitdaten

## Barrierefreiheit

- [ ] Semantische HTML Struktur prüfen
- [ ] H1 genau im Hero vorhanden
- [ ] H2 für Sections vorhanden
- [ ] Alt Texte für Bilder gepflegt
- [ ] FAQ Buttons haben `aria-expanded` und `aria-controls`
- [ ] Fokuszustände sichtbar
- [ ] Formularfelder haben Labels
- [ ] Fehlermeldungen sind verständlich und über `role="alert"` wahrnehmbar
- [ ] Buttons und Links sind eindeutig benannt
- [ ] Keine rein farbliche Bedeutungsvermittlung

## Sicherheitsprüfung

- [ ] Admin-Aktionen verwenden CSRF Token
- [ ] Preview nur für berechtigte Admin-Benutzer erreichbar
- [ ] Import validiert JSON-Struktur
- [ ] Import speichert standardmäßig Entwurf
- [ ] HTML-Bereiche werden serverseitig gefiltert
- [ ] Formular nutzt Honeypot und CSRF Token
