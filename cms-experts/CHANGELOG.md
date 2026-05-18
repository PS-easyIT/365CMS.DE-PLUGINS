# Changelog – CMS Experts Directory Plugin

Alle wesentlichen Änderungen an diesem Plugin werden in dieser Datei dokumentiert.
Format: [Keep a Changelog](https://keepachangelog.com/de/1.0.0/)

---

## [3.0.2] – 2026-05-18

### Geändert

- Public-Archiv an PHINIT/365Network/M365Tools-Designsprache angeglichen: Header und Suche sind jetzt als sauber abgegrenzter Filterbereich gruppiert.
- Experten-Cards wachsen nun natürlich mit ihrem Inhalt; lange Namen, Titel, Unternehmen, Orte und Expertise-Werte werden nicht mehr hart abgeschnitten.
- Social-Media-Icon-Bänder werden in der Übersicht ausgeblendet, damit die Cards ruhiger wirken und der CTA stabil am Kartenende bleibt.

---

## [Unreleased] – 2026-04-04

### Sicherheitsfixes

- Such- und Ortsfilter im Experten-Archiv werden jetzt direkt an den Eingabe- und Reset-Sinks escaped.
- Der letzte XSS-Befund im öffentlichen Archiv-Template ist damit geschlossen.

## [2.1.1] – 2026-03-29

### Geändert
- Admin-Übersicht der Experten von Kartenansicht auf eine kompakte Listen-/Tabellenansicht umgestellt, damit Status, Verfügbarkeit und Aktionen schneller erfassbar sind.
- Die Expertenliste im Admin besitzt jetzt eine steuerbare Sortierung für Aktualisierung, Erstellungsdatum, Name, Status und Verfügbarkeit.

### Behoben
- Löschen eines Experten im Admin funktioniert wieder korrekt: der Lösch-Request nutzt nun die richtige CSRF-Action `experts_admin` und leitet sauber mit Statusmeldung zurück.
- Genehmigen/Löschen verwenden nun Bootstrap-kompatible Modals im bestehenden Admin-Layout, sodass die Aktionsbuttons zuverlässig reagieren.
- Die Aktionsbuttons in der Admin-Liste nutzen nun zusätzlich das bereits im CMS bewährte `cmsConfirm()`-Bestätigungsmuster direkt am Formular-Button, wodurch Löschen und Genehmigen zuverlässig aus der Übersicht ausgelöst werden.
- Erfolgsmeldungen nach Freigabe/Löschung wurden im Admin deutlicher formuliert; typische Fehlerfälle wie CSRF oder ungültige IDs werden verständlicher angezeigt.

## [2.0.0] – 2026-02-24

### Hinzugefügt

#### Neue Meta-Felder (admin & public)
- **Basis:** Motto / Tagline, Arbeitsform (Freelancer/Angestellt/Agentur/Contractor), Zeitzone
- **Konditionen:** Verfügbar-ab-Datum, Wochenstunden, Min./Max. Projektdauer, Mindestbuchungsdauer, Zahlungsziel (Netto 7/14/30/60 / Vorkasse), Festpreis-Projekte, Time & Material, Reisekosten-Modell, Max. Reisedistanz, bevorzugte Unternehmensgrößen (Checkbox-Array)
- **Profil-Badges:** MVP, Zertifiziert, Premium (Checkboxen), eigener Custom-Award-Text
- **Technische Expertise:** Programmiersprachen, Frameworks, Datenbanken, Cloud-Plattformen – jeweils als JSON-Repeater mit Level (Einsteiger … Experte); Tools und Branchenerfahrung als Tags
- **Berufliche Stationen:** JSON-Repeater (Firma, Position, Von/Bis, Ort, Beschreibungen)
- **Referenzen & Portfolio:** Testimonials (mit Sternebewertung), Case Studies, Konferenzvorträge
- **Service-Angebot:** 7 Checkbox-Flags (Consulting, Implementierung, Training, Support, Audit, Notfall-Support, Workshops)
- **Netzwerk & Skalierung:** Subunternehmer, Team-Erweiterung, max. Teamgröße, geführte Teamgröße, Gesamtanzahl Projekte, Partner-Netzwerke
- **Online-Präsenz:** GitLab, Stack Overflow, YouTube-Kanal, Blog/RSS-Feed, Erreichbarkeitszeiten

#### Admin-Formulare (`class-meta-boxes.php`)
- `render_professional_info()`: Arbeitsform-Select + Zeitzone-Input
- `render_availability_rates()`: Kompletter Umbau mit allen Konditions-Feldern
- `render_partner_status()`: MVP-, Zertifiziert-, Premium-Checkboxen + Custom-Award
- `render_social_links()`: 4 neue Plattformen + Erreichbarkeitszeiten
- **Neue Methode** `render_tech_expertise()`: JSON-Repeater für alle Tech-Felder
- **Neue Methode** `render_career_stations_form()`: Karriere-Repeater
- **Neue Methode** `render_references()`: Testimonials, Case Studies, Konferenzvorträge
- **Neue Methode** `render_services()`: Service-Angebot-Checkboxen
- **Neue Methode** `render_network_scale()`: Netzwerk-Felder
- `render_basic_info()`: Motto-Feld ergänzt

#### Öffentliche Detailseite (`single-expert.php`)
- ~65 neue Variablen-Deklarationen für alle Meta-Felder
- **Header:** MVP/Certified/Premium/Custom-Award-Badges; Motto unter dem Namen; work_type/timezone/total_projects-Chips; SVG-Icons für GitLab, Stack Overflow, YouTube, RSS
- **Neue Section:** Technische Expertise (4-spaltig) mit visuellen Level-Balken
- **Neue Section:** Berufliche Stationen (Timeline)
- **Neue Section:** Referenzen & Portfolio (Testimonials-Grid, Case-Studies-Liste, Konferenzvorträge)
- **Sidebar komplett überarbeitet:** Profil & Arbeitsweise mit farbigen Sprach-Badges, Retainer-Badge; Erweiterte Konditionen als Grid; Service-Angebot-Card; Netzwerk & Skalierung-Card; Social Links mit allen neuen Plattformen
- **RSS-Feed-Sektion** nach `</main>` mit live Vorschau via `simplexml_load_file()`
- **Erweitertes CSS** für alle neuen UI-Elemente

#### Datenbank (`class-database.php`)
- **`countExperts(string $status = '')`**: Zählt nicht-gelöschte Experten, optional nach Status gefiltert
- **`getExperts(array $args)`**: camelCase-Alias für `get_experts_all()` (Member-Dashboard-Kompatibilität)

#### Sonstige
- SunEditor CSS wird jetzt auch auf Public-Seiten eingebunden (`enqueue_styles()`)

### Geändert
- **`admin_save()` Meta-Whitelist** komplett überarbeitet: von 10 auf 50+ erlaubte Meta-Schlüssel erweitert; Arrays werden als JSON gespeichert; Checkboxen werden explizit auf 0 gesetzt wenn nicht gesendet; separate Listen für text/url/bool/json Felder
- **`class-member-dashboard.php`:** `getExperts()` → `get_experts_all()`, korrekte Namens-Darstellung aus `first_name`/`last_name`
- **Version:** 1.0.0 → 2.0.0 (Plugin-Header, Konstante `CMS_EXPERTS_VERSION`, `update.json`)
- **SunEditor-Ausgabe:** Biography-Darstellung nutzt jetzt `<div class="sun-editor-editable">` als Wrapper für korrekte CSS-Formatierung; Regex-basierte HTML-Erkennung statt fragiles `str_contains`
- **`style.css`:** SunEditor-Override-Klassen für Public-Ansicht ergänzt (Editor-UI neutralisiert, Inhaltsformatierung erhalten)
- **`README.md`:** Komplett neu geschrieben mit allen aktuellen Features, Meta-Referenz, Hooks-Tabelle

### Behoben
- **MemberDashboard:** `CMS_Experts_Database::instance()->getExperts()` warf fatalen Fehler (Methode existierte nicht)
- **MemberDashboard:** `countExperts()` warf fatalen Fehler (Methode fehlte in DB-Klasse)
- **MemberDashboard:** Experten-Namen wurden als leer angezeigt (falsche Feld `$expert->name` statt `first_name`/`last_name`)
- **SunEditor WYSIWYG-Inhalte** wurden ohne Formatierung als Plaintext ausgegeben (fehlende CSS-Einbindung auf Public-Seiten)
- **Neue Meta-Felder** wurden bei `admin_save()` nicht gespeichert (fehlende Whitelist-Einträge)

---

## [1.0.0] – 2026-02-21

### Hinzugefügt
- Erstveröffentlichung: IT-Experten-Profile mit Card-Ansicht und Detailseiten
- Custom-Datenbank-Tabellen mit vollständigen Relationen
- Admin-Interface zur Verwaltung von Experten
- Frontend-Archiv mit Grid-Layout und Filterung
- Skills-System (3 Typen: general / tech / soft)
- Zertifikate, Projekte und Ausbildungseinträge
- Fachrichtungs-Taxonomie (hierarchisch)
- Shortcodes: `[cms_experts]`, `[cms_expert id="x"]`
- Member-Dashboard-Integration
- CSRF-Schutz, Soft-Delete, Slug-System (`vorname-nachname-{id}`)
- Design-Einstellungen (Farben, Radien, Grid-Spalten)
