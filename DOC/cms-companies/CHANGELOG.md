# CMS Companies – Changelog

Alle Änderungen folgen dem Format [Keep a Changelog](https://keepachangelog.com/de/).

---

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
