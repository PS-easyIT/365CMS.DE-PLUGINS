# CMS Companies – Changelog

Alle Änderungen folgen dem Format [Keep a Changelog](https://keepachangelog.com/de/).

---

## [3.0.9] – 2026-05-31

### Geändert

- **Public-Detailseite:** Die Companies-Detailseite nutzt nun wie die Übersichten eine bündige Full-Bleed-Hintergrund-Shell ohne Theme-Abstände zu Header und Footer.
- **Kurze Inhalte:** Der Detail-Hintergrund füllt den Bereich bis zum Theme-Footer auch dann, wenn das Unternehmensprofil nur wenig Inhalt enthält.
- **Contentbreite:** Breadcrumb und Detail-Grid bleiben weiterhin responsiv auf maximal `1160px` begrenzt; Abstände entstehen nur noch durch internes Content-Padding.

## [3.0.8] – 2026-05-31

### Geändert

- **Public-Suchbereich:** Das Companies-Referenzdesign für Filterleisten wurde finalisiert: gleiche Feldhöhe, Label-Rhythmik, Button-Optik, volle `1160px`-Contentbreite und responsive Umbrüche.
- **Plugin-Anpassung:** Unternehmen-Suche, Stadt, Branche und Partnerstatus behalten ihre Companies-spezifische Funktion und Akzentfarben.

## [3.0.7] – 2026-05-31

### Geändert

- **Public-Detailseite:** Die Companies-Detailansicht ist jetzt responsiv auf die Theme-Contentbreite von maximal `1160px` begrenzt statt über die volle Fensterbreite zu laufen.
- **Dark Mode:** Header, Detailkarten, Logo-Box, Badges, Facts und Textfarben der Detailseite verwenden nun explizite Dark-Mode-kompatible PHINIT-Farbvariablen.

## [3.0.6] – 2026-05-31

### Geändert

- **Public-Übersicht:** Die Archiv-Shell liegt bündig an Theme-Header und -Footer an; der eigentliche Content startet intern bei `25px`, bleibt responsiv und ist auf maximal `1160px` begrenzt.
- **Companies-Filter:** Der Such-/Filterbereich der Publicsite-Übersicht nutzt die volle Contentbreite mit wachsendem Suchfeld.

## [3.0.5] – 2026-05-31

### Geändert

- **Public-Übersicht:** Hintergrund, Filter, Empty-State und Pagination der Companies-Übersicht wurden an den finalen `cms-events` Publicsite-Look angeglichen; Cards bleiben unverändert.

## [3.0.2] – 2026-05-30

### Geändert

- **Company-Archiv:** Die öffentliche Übersicht rendert wie die Event-Übersicht direkt mit Filterbereich und responsivem Card-Grid statt mit separatem Archivkopf.
- **Company-Cards:** Cards zeigen nun Logo/Initialen, Partner-/Branchen-Badges, Standort-/Team-Meta, Beschreibungsauszug, Website-Hinweis und kompakten Details-CTA.
- **Interaktion:** Cards bleiben komplett klickbar und sind zusätzlich per `Enter`/`Space` tastaturbedienbar; echte Links bleiben separat erreichbar.

## [3.0.1] – 2026-05-18

### Geändert

- **Public-Design-Pass:** Archiv-Header und Suche sind als sauber abgegrenzter Filterbereich gruppiert, Company-Cards wachsen ohne feste Höhen, lange Namen/Pills brechen kontrolliert um und Footer-Aktionen bleiben am Kartenende.

## [3.0.0] – 2026-05-17

### Geändert

- **365CMS-3.x-Modernisierung:** Idempotenter Bootstrap, `schema_version`-Gating, PHINIT Publicsite-Basis, semantische Archive-/Card-/Detailtemplates und Theme-Override-Harmonisierung.
- **Public-Härtung:** Public-Logo-/Website-/Profilbild-URLs werden auf `http`/`https` beschränkt; Member-Formulare escapen CSRF-/POST-Attribute explizit und Redirects nutzen `303`.
- **Publicsite-UX:** Öffentliche Cards und Detailseiten wurden von dekorativer Emoji-UI bereinigt und stärker an das ruhige PHINIT-Design angeglichen.

## [1.1.0] – 2026-03-28

### Geändert

- **Datenbankpfad:** Die beschädigte `get_companies()`-Implementierung wurde repariert und die Datei wieder in einen stabilen, syntaktisch sauberen Zustand gebracht.
- **Listen- und Statuslogik:** Firmenlisten und Zählabfragen wurden für `limit`, `offset` und `status => 'any'` defensiv vereinheitlicht.
- **Member-Create:** E-Mail-, URL-, Branchen-, Größen-, Jahres- und Tag-Daten werden im Member-Create-Handler jetzt restriktiver geprüft und normalisiert.
- **Template-Escaping:** Die Firmenbeschreibung im Single-Template wird nicht mehr roh ausgegeben, sondern sicher escaped und mit Zeilenumbrüchen gerendert.
- **Link-Escaping:** Intern zusammengesetzte Breadcrumb-, Experten-, Speaker- und Register-Links im Single-Template werden jetzt ebenfalls konsequent im Attribut-Kontext escaped.
- **Archive-Link-Escaping:** Reset- und Fallback-Links im Archive-Template behandeln interne Navigationsziele jetzt ebenfalls konsequent im `href`-Attribut-Kontext.
- **Card-Link-Escaping:** Detail-Links aus `cms_company_url()` werden im Card-Template jetzt ebenfalls konsequent im `href`-Attribut-Kontext escaped.
- **Archive-Pagination-Escaping:** Auch Pagination-Links im Archive-Template mit zusammengesetzten Query-Parametern werden jetzt konsequent im `href`-Attribut-Kontext escaped.
- **Style-Attribut-Escaping:** Dynamische Avatar-Gradienten, Ribbon-Farben und Card-Rahmenwerte werden in Single- und Card-Templates jetzt ebenfalls konsequent im `style`-Attribut-Kontext escaped.
- **Renderzeit-Validierung:** Logo-, Website-, Mail- und Telefon-Attribute werden in Single- und Card-Templates jetzt zusätzlich gegen Alt- und Bestandsdaten validiert, bevor sie im Frontend gerendert werden.
- **Ownership-Härtung:** Die zentrale `save_company()`-Persistenz blockiert für Nicht-Admins jetzt Updates auf fremde Company-IDs und entschärft damit latente IDOR-/Fremd-ID-Pfade.
- **Bootstrap-Fix:** Hook-registrierende Klassen werden jetzt bereits beim Plugin-Start instanziiert, sodass Admin-Sidebar-Eintrag und Admin-Routen nicht mehr von `cms_init` abhängen.
- **Bootstrap-Guard:** Der frühe Bootstrap wird jetzt zusätzlich nur bei verfügbarem Core-Kontext (`CMS\Hooks`, `CMS\Database`) ausgeführt und entschärft damit Aktivierungs-/Lade-Fatals.
- **DB-Fatal-Guard:** Der komplette Tabellenaufbau inklusive initialem Datenbankzugriff wird im Aktivierungs-/Init-Pfad jetzt defensiv abgefangen und nur noch geloggt statt als Fatal nach oben weitergereicht.
- **Member-Dashboard:** Eigene `pending`-Firmen werden nun konsistent gezählt und in der Übersicht angezeigt; Admin-Ansichten können alle nicht gelöschten Einträge laden.
- **Member-Dashboard-Status:** Der aktuelle Rechte-Stand wurde nachgezogen: Im Member-Bereich existiert derzeit nur ein Create-/Listen-Flow, aber kein realer Edit- oder Experten-Relations-Update-Pfad für bestehende Firmen.
- **Assets:** Admin- und Bootstrap-Assets nutzen robuste, dateigeprüfte Versionswerte statt direkter `filemtime()`-Aufrufe ohne Fallback.

## [1.0.1] – 2026-03-28

### Geändert

- **Dokumentation:** README, Aufgabenplanung und Sicherheitsdokumentation auf den Zielstand für **365CMS V2.8.0** erweitert.
- **Audit-Fokus:** Security-, Speed- und Best-Practice-Prüfschritte für `cms-companies` konkretisiert, insbesondere zu Paginierung, Filter-Whitelists, Ownership-Prüfung und Ausgabehärtung.
- **Planung:** Verweis auf den zentralen Abarbeitungsplan `DOC/365CMS-V2.8.0-PLUGIN-AUDIT-PLAN.md` ergänzt.

## [1.0.0] – 2026-02-21

### Hinzugefügt

- **Kern:** Singleton-Hauptklasse `CMS_Companies` mit automatischem Autoloading
- **Datenbank:** Tabellen `cms_companies`, `cms_company_experts`, `cms_company_meta`, `cms_company_plugin_settings`, `cms_company_industries`
- **Admin-Backend:** Vollständige CRUD-Oberfläche unter `/admin/companies`
  - Firmen-Übersicht mit Tabelle, Status-Badges, Such- und Filterleiste
  - Neue Firma anlegen / bearbeiten (Modal oder Seite)
  - Partner-Status-Verwaltung (Partner / Top-Partner / Sponsor)
  - Experten-Zuordnung per Autocomplete
  - Meta-Felder-Editor
- **Member-Dashboard:** Integration in den Member-Bereich
  - Eigenes Firmenprofil anlegen und bearbeiten
  - Experten-Übersicht der eigenen Firma
- **Frontend:** Öffentliche Routen `/companies` und `/companies/{id}`
- **Shortcode:** `[cms_companies]` mit Attributen `limit`, `industry`, `partner_only`, `orderby`
- **Templates:** `archive-company.php`, `single-company.php`, `company-card.php`
- **Hooks:** `company_created`, `company_updated`, `company_deleted`, `company_expert_assigned`, `company_expert_removed`
- **Filter:** `company_card_content`, `company_query_args`, `company_meta_value`
- **Cross-Plugin:** Schnittstellen zu `cms-experts` (M2M), `cms-speakers` (FK), `cms-organigramm` (Root-Node)
- **Sicherheit:** CSRF-Schutz, PDO Prepared Statements, `htmlspecialchars()` bei allen Ausgaben
- **Assets:** Basis-CSS (`style.css`) und optionales JS (`script.js`)
