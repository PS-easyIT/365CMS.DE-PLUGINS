# CMS Experts – Changelog

## [3.0.10] – 2026-05-31

### Behoben

- **Hotfix i18n:** `single-expert.php` nutzt einen lokalen `CMS/lang`-YAML-Fallback, falls `TranslationService` oder `__()` den Original-Key zurückgeben.
- **Frontend:** Breadcrumb, Contentbereiche und Sidebar zeigen dadurch keine rohen `cms_experts.detail.*` Keys mehr.

## [3.0.9] – 2026-05-31

### Geändert

- **Public-Detailseite:** Die Experts-Detailseite wurde nach `experts-detail-vorschau.html` neu aufgebaut: Navy/Amber-Hero, Profil-, Expertise-, Zertifikats-, Leistungs- und Projekte/Referenzen-Karten.
- **Internationalisierung:** Alle sichtbaren Detailseiten-Labels nutzen zentrale `cms_experts.detail.*`-Keys in `CMS/lang/de.yaml` und `CMS/lang/en.yaml`.
- **Assets:** `assets/css/single.css` wird nur noch auf Expert-Detailseiten geladen; Archivseiten laden weiterhin nur Basis- und Archiv-CSS.
- **Sidebar:** Kontakt-CTA, Social-Media-Links, Detaildaten und ähnliche Experten orientieren sich an der HTML-Preview und nutzen Inline-SVG-Icons statt Icon-Fonts.
- **Mapping:** Preview-Felder ohne Backend-Entsprechung sind dokumentiert: Mastodon wird nicht gerendert, statische Blogartikel werden durch Projekte, Case Studies, Konferenzvorträge und Event-Auftritte ersetzt.

## [3.0.8] – 2026-05-31

### Geändert

- **Public-Detailseite:** Die Experts-Detailseite liegt mit ihrem Hintergrund bündig an Theme-Header und -Footer an und füllt bei kurzem Inhalt den Bereich bis zum Footer.
- **Layout:** Breadcrumb, Hero, Bridge-Cards, Content-/Sidebar-Grid und Claim-Banner sind auf maximal `1160px` Theme-Contentbreite zentriert.
- **Responsive/Dark Mode:** Detail-Grid, Bridge-Cards, Skills, Zertifikate, Events, Sidebar-Karten, Tags und Texte brechen mobil sauber um und nutzen explizite Dark-Mode-Tokens.

## [3.0.7] – 2026-05-31

### Geändert

- **Public-Suchbereich:** Die Experts-Übersicht nutzt nun das einheitliche Companies-basierte Filterdesign mit gleicher Feldhöhe, Label-Rhythmik, Button-Optik, voller `1160px`-Contentbreite und responsiven Umbrüchen.
- **Plugin-Anpassung:** Expertensuche, Stadt und Verfügbarkeit behalten ihre Experts-spezifische Funktion und verwenden die Experts-Akzentfarben.

## [3.0.6] – 2026-05-31

### Geändert

- **Public-Übersicht:** Die Shell liegt bündig an Theme-Header und -Footer an; der eigentliche Content startet intern bei `25px`, bleibt responsiv und ist auf maximal `1160px` begrenzt.

## [3.0.4] – 2026-05-30

### Geändert

- **Public-Archiv:** Die öffentliche Übersicht rendert wie `cms-events` direkt mit Filterbereich und responsivem Card-Grid statt separatem Archivkopf.
- **Expert-Cards:** Cards zeigen Avatar/Initialen, MVP-/Premium-/Award-/Spezialisierungs-Badges, Verfügbarkeit, Standort/Firma, Erfahrung, Zertifikate, Skills und Profil-CTA.
- **Interaktion:** Cards sind komplett klickbar und zusätzlich per `Enter`/`Space` tastaturbedienbar; Website-/Company-Links bleiben separat erreichbar.

## [3.0.3] – 2026-05-25

### Fehlerbehebungen

- `CMS_Experts_Meta_Boxes` ist gegen versehentliches erneutes Laden geschützt, damit Alt-/Doppel-Include-Pfade keine `Cannot redeclare class`-Fatals erzeugen.

## [3.0.2] – 2026-05-18

### Geändert

- **Public-Design-Pass:** Archiv-Header und Suche sind als sauber abgegrenzter Filterbereich gruppiert, Experten-Cards wachsen ohne abgeschnittene Inhalte, Social-Icon-Bänder wurden aus der Übersicht entfernt und CTA/Footer bleiben stabil am Kartenende.

## [3.0.1] – 2026-05-17

### Geändert

- Öffentliche Experten-URLs, Profilbilder, Social-Links, Projekt- und Zertifikatslinks werden auf sichere `http`/`https`-Ziele ohne lokale/private Hosts begrenzt.
- Archiv-, Admin- und Member-Filter normalisieren Query-/POST-Werte mit Whitelists, Längenlimits und robusten Bounds für Pagination/Listenlimits.
- Admin-CSRF-Token und Asset-URLs werden im Attribut-Kontext escaped; öffentliche Card-/Detailausgaben vermeiden dekorative Emoji-Badges in den wichtigsten CTA-/Statusflächen.
- Zertifikats- und Projektdaten validieren Datumsfelder vor der Speicherung und kappen Freitextfelder defensiv.

## [2.1.0] – 2026-03-28

### Geändert

- **Versionierung:** Plugin-Header, Konstante, Klassenwert und `update.json` wurden auf den konsistenten plugin-eigenen Release-Stand `2.1.0` gebracht.
- **Datenbankpfade:** Listenabfragen verwenden jetzt defensiv begrenzte `limit`-/`offset`-Werte, und die Schema-Initialisierung läuft versionsgesteuert statt unnötig oft im Laufzeitpfad.
- **Admin-Save:** Der beschädigte Save-Block wurde repariert und die `availability`-Normalisierung auf erlaubte Werte begrenzt.
- **Member-Create:** E-Mail-, URL-, Availability-, Skill- und Spezialisierungsdaten werden im Member-Create-Handler jetzt ebenso restriktiv normalisiert.
- **Template-Escaping:** Die Experten-Biografie im Single-Template wird nicht mehr roh ausgegeben, sondern sicher escaped und mit Zeilenumbrüchen gerendert.
- **Link-Escaping:** Intern zusammengesetzte Breadcrumb-, Kontakt- und Register-Links im Single-Template werden jetzt ebenfalls konsequent im Attribut-Kontext escaped.
- **Card-Link-Escaping:** Detail-Links aus `$url` werden im Card-Template jetzt ebenfalls konsequent im `href`-Attribut-Kontext escaped.
- **Archive-/CTA-Link-Escaping:** Auch Reset-Link im Archive-Template sowie CTA-Link aus `$url` im Card-Template werden jetzt konsequent im `href`-Attribut-Kontext escaped.
- **Style-/Title-Escaping:** Auch Avatar-Gradient im `style`-Attribut und Event-Zähler im `title`-Attribut des Single-Templates werden jetzt konsequent escaped.
- **Renderzeit-Validierung:** Foto-, Website-, Mail-, Telefon- und Social-Link-Felder werden in Single- und Card-Templates jetzt zusätzlich gegen Alt- und Bestandsdaten validiert, bevor sie in `src`, `href`, `mailto:` oder `tel:` gerendert werden.
- **Member-Dashboard-Status:** Der aktuelle Rechte-Stand wurde nachgezogen: Im Member-Bereich existiert derzeit nur ein Create-/Listen-Flow, aber kein realer Edit- oder Repeater-Update-Pfad für bestehende Experten.
- **Ownership-Härtung:** Die zentrale `save_expert()`-Persistenz blockiert für Nicht-Admins jetzt Updates auf fremde Experten-IDs und entschärft damit latente IDOR-/Fremd-ID-Pfade.
- **Title-Attribut-Escaping:** Auch interne Social-Icon-Labels im `title`-Attribut des Single-Templates werden jetzt konsequent escaped.
- **Bootstrap-Fix:** Hook-registrierende Klassen werden jetzt bereits beim Plugin-Start instanziiert, sodass Admin-Sidebar-Eintrag und Admin-Routen nicht mehr von `cms_init` abhängen.
- **Bootstrap-Guard:** Der frühe Bootstrap wird jetzt zusätzlich nur bei verfügbarem Core-Kontext (`CMS\Hooks`, `CMS\Database`) ausgeführt und entschärft damit Aktivierungs-/Lade-Fatals.
- **DB-Fatal-Guard:** Der komplette Tabellenaufbau inklusive initialem Datenbankzugriff wird im Aktivierungs-/Init-Pfad jetzt defensiv abgefangen und nur noch geloggt statt als Fatal nach oben weitergereicht.
- **Assets:** Bootstrap- und Admin-Assets verwenden konsistent lokal zwischengespeicherte `filemtime()`-Versionen.

## [2.0.1] – 2026-03-28

### Geändert

- **Dokumentation:** README, Aufgabenplanung und Sicherheitsdokumentation auf den Zielstand für **365CMS V2.8.0** erweitert.
- **Audit-Fokus:** Security-, Speed- und Best-Practice-Prüfschritte für `cms-experts` konkretisiert, einschließlich Ownership-Prüfung, Meta-Sanitizing und Versionskonsolidierung.
- **Planung:** Verweis auf den zentralen Abarbeitungsplan `DOC/365CMS-V2.8.0-PLUGIN-AUDIT-PLAN.md` ergänzt.

## [2.0.0] – 2026-02-24

### Hinzugefügt

- **Basis-Profil:** Motto/Tagline, Arbeitsform, Zeitzone
- **Verfügbarkeit/Konditionen:** Verfügbar-ab-Datum, Wochenstunden, Min/Max Projektdauer, Mindestbuchungsdauer, Zahlungsziel (Netto 7/14/30/60, Vorkasse), Festpreis, T&M, Reisekosten-Modell, Max. Reisedistanz, bevorzugte Unternehmensgrößen
- **Badges:** MVP, Zertifiziert, Premium (Checkboxen), Custom-Award-Text
- **Technische Expertise:** JSON-Repeater für Programmiersprachen, Frameworks, Datenbanken, Cloud-Plattformen (jeweils mit Level); Tools und Branchenerfahrung als Tags
- **Karriere-Stationen:** JSON-Repeater (Firma, Position, Von/Bis, Ort, Beschreibung)
- **Referenzen & Portfolio:** Testimonials mit 1–5-Sterne-Bewertung, Case Studies, Konferenzvorträge
- **Service-Angebot:** 7 Checkbox-Flags
- **Netzwerk & Skalierung:** Subunternehmer, Team-Erweiterung, Teamgröße, Gesamtprojekte, Partner-Netzwerke
- **Social:** GitLab, Stack Overflow, YouTube-Kanal, Blog/RSS-Feed, Erreichbarkeitszeiten
- **Admin:** Neue Render-Methoden in `class-meta-boxes.php` für alle neuen Bereiche
- **Frontend:** ~65 neue Variablen in `single-expert.php`; MVP/Certified/Premium/Award-Badges; Tech-Expertise-Section mit visuellen Level-Balken; Career-Timeline; Referenzen-Grid

---

## [1.0.0] – 2026-01-15

### Hinzugefügt

- **Kern:** Singleton-Hauptklasse `CMS_Experts`
- **Datenbank:** `cms_experts`, `cms_expert_meta`, `cms_expert_skills`, `cms_expert_certifications`, `cms_expert_projects`, `cms_expert_education`, `cms_expert_taxonomies`, `cms_expert_skill_taxonomy`, `cms_expert_skill_presets`
- **Admin-Backend:** Vollständige CRUD unter `/admin/experts`
- **Member-Dashboard:** Eigenes Experten-Profil verwalten
- **Shortcode:** `[cms_experts]`
- **Templates:** `archive-expert.php`, `single-expert.php`, `expert-card.php`
- **Taxonomien:** Hierarchische Fachrichtungen, Skill-Kategorien (Allgemein/Technisch/Soft Skills)
- **Hooks:** `expert_created`, `expert_updated`, `expert_deleted`, `expert_availability_changed`
- **Filter:** `expert_card_content`, `expert_query_args`
- **DSGVO:** Daten-Export (Art. 20) und Löschung (Art. 17) via CMS-Hooks
- **Sicherheit:** CSRF, Prepared Statements, XSS-Escaping
