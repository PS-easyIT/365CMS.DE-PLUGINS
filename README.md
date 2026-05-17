# 365CMS Plugin-Repository

[![365CMS](https://img.shields.io/badge/365CMS-2.0%2B-blue)](https://365cms.de)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php)](https://php.net)
[![License](https://img.shields.io/badge/License-Proprietary-red)](LICENSE)

> Offizielles Plugin-Repository für das **365CMS**-System.  
> Alle Plugins folgen einer einheitlichen Architektur und sind lose gekoppelt.

---

## Enthaltene Plugins

| Plugin | Version | Status | Beschreibung |
|--------|---------|--------|--------------|
| [cms-companies](cms-companies/) | 1.0.0 | ✅ Stabil | Firmen-Profile mit Experten-Zuordnung & Partner-Status |
| [cms-events](cms-events/) | 1.0.0 | ✅ Stabil | Event-Verwaltung mit Speaker-Anbindung & Kalenderansicht |
| [cms-experts](cms-experts/) | 2.0.0 | ✅ Stabil | IT-Experten-Verzeichnis mit umfangreichen Meta-Daten |
| [cms-importer](cms-importer/) | 1.0.0 | ✅ Stabil | WordPress WXR-Importer für Posts & Pages |
| [cms-contact](cms-contact/) | 1.1.6 | ✅ Stabil | Kontaktformulare mit Mehrfach-Formularen, Templates, DSGVO-Hooks und gehärtetem Installer für FK-Kollisionen |
| [cms-downloads](cms-downloads/) | 1.0.0 | 🚀 Neu | Öffentliches Download-Management mit Kategorien, Typ-Presets und Archiv |
| [cms-newsletter](cms-newsletter/) | 1.0.0 | 🚀 Neu | Newsletter-Management mit Subscribern, Templates, Kampagnen und öffentlicher Anmeldung |
| [cms-promos](cms-promos/) | 1.0.0 | 🚀 Neu | Promo-Management für CTA-Flächen, Platzierungen, Banner und Klickziele |
| [cms-m365lic](cms-m365lic/) | 1.1.0 | 🚀 Neu | Microsoft-365-Lizenzberater mit Spezial-User-Zuweisung, Billing-Modellen, vollständigem Seed-Katalog und getrennten Public/Member/Spezial-Bereichen |
| [cms-m365tools](M365-PLUGINS/cms-m365tools/) | 1.29.9 | 🚀 Neu | Modulare Microsoft-365-Rechner- und Tool-Box mit 21 Public-Modulen, öffentlichem Plugin-Content ohne äußeren Theme-Header- oder Theme-Wrapper-Abstand inklusive spätem Head-Critical-CSS und Host-Klasse gegen Theme-/Customizer-Overrides, UX-optimierter Hub-Landingpage mit Hero-Direkteinstieg, Live-Suche, Tag-Filtern, ARIA-Radiogroup, Kategorie-Hash, Slash-Suchshortcut, Sticky-ToC, Kategorie-Heros, Kompass-Icons, Badges, Dark Mode und Back-to-top, M365LIC-Preisübernahme für Public-/Member-/Spezialpreise, Dienstleister-/Kontaktformular-CTA nach Toolseiten, eigenem Landingpage-Designer-Untermenü mit Content-, Layout-, Farb-, Button- und Sichtbarkeitsoptionen, gemeinsamem Matrixen-Unterpunkt für Read-only Lizenz- und Add-on-Matrix inklusive Design-Tab, zentraler Plugin-Einstellungsseite, globalen Paketpreis- und Abopreis-Unterpunkten, kurzen Modulmenüs, eigenem Admin-Unterpunkt je Modul, Einstellungs-Tabs für Anzeige, Preise, Workflows und Datenstand, gehärteter Legacy-Settings-Migration, ruhigem PHINIT-Public-Design, erweitertem All-Module-Best-Practice-Kompass mit konkreten Prüfpunkten je Tool, Power-Platform-Request-/Dataverse-Capacity-Checks, Power-Platform-Kosten plus Well-Architected-Review, Workspace-M365-TCO, Storage-Bedarf, Backup-Kosten, Lizenz-Audit, Preis-Tracker, ROI-, Telefonie-, Copilot- und Lizenztools |
| [cms-jobprofile-generator](cms-jobprofile-generator/) | 0.9.6 | 🧪 Beta | Stellenanzeigen-Generator mit Workflow-Genehmigung |
| [cms-organigramm](cms-organigramm/) | 0.1.0 | 🚧 In Entwicklung | Interaktives Organigramm mit Cross-Plugin-Integration |
| [cms-speakers](cms-speakers/) | 1.0.0 | ✅ Stabil | Speaker-Profile mit Topics & Präsentations-Historie |
| [cms-netimport](cms-netimport/) | 1.0.0 | 🚀 Neu | CSV-Netzwerkimport für Companies, Experts, Speakers und Events |

---

## Architektur-Übersicht

Alle Plugins folgen diesen Konventionen:

### Verzeichnisstruktur (Standard)

```
cms-[name]/
├── cms-[name].php          # Plugin-Einstiegspunkt (Singleton-Klasse)
├── README.md               # Plugin-spezifische Kurzreferenz
├── CHANGELOG.md            # Versionshistorie
├── update.json             # Auto-Update Manifest
├── admin/                  # Admin-Backend-Module
│   ├── class-admin-menu.php
│   └── modules/
│       └── trait-page-*.php
├── includes/               # Kern-Klassen
│   ├── class-database.php  # DB-Tabellen & Queries
│   ├── class-admin.php     # Admin-Hook-Registrierung
│   ├── class-member-dashboard.php  # Member-Frontend
│   ├── class-shortcode.php
│   └── class-template-loader.php
├── templates/              # PHP-Templates Publicsites
├── member/                  # Member-Ansichten
├── doc/                    # Dokumentation zum Plugin
└── assets/
    ├── css/
    └── js/
```

### Plugin-Muster (Singleton)

```php
final class CMS_MyPlugin {
    private static ?self $instance = null;

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
}
CMS_MyPlugin::instance();
```

### Hooks-Integration

Alle Plugins nutzen `CMS\Hooks` statt direkter Funktionsaufrufe:

```php
CMS\Hooks::addAction('cms_init', [$this, 'init_plugin'], 10);
CMS\Hooks::addAction('plugin_activated', [$this, 'on_activation'], 10);
CMS\Hooks::doAction('my_plugin_event', $payload);
CMS\Hooks::applyFilters('my_plugin_filter', $value, $context);
```

### Datenbank-Zugriff

```php
$db     = CMS\Database::instance();
$pdo    = $db->getPdo();
$prefix = $db->prefix();   // z. B. "cms_"

$stmt = $db->prepare("SELECT * FROM {$prefix}companies WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();
```

### Sicherheit

- `declare(strict_types=1)` in **jeder** PHP-Datei
- `if (!defined('ABSPATH')) exit;` als Guard
- CSRF via `CMS\Security::instance()->verifyToken()`
- Prepared Statements für alle DB-Operationen
- `htmlspecialchars()` bei allen HTML-Ausgaben

## Snyk-Status – 2026-04-04

- ✅ Repo-weites Snyk-Code-Audit für die First-Party-Plugins abgeschlossen
- ✅ Aktueller Stand: **0 Findings** für `e:\00-WPwork\365CMS.DE-PLUGINS`
- 🔒 In diesem Durchgang wurden unter anderem DOM-XSS-, Open-Redirect-, Insecure-Hash-, SQL- und PDF-Export-Flows in mehreren Plugins gehärtet
- 📝 Betroffene Plugin-Dokumentationen und Changelogs wurden parallel auf den Audit-Stand aktualisiert

---

## Cross-Plugin-Beziehungen

```
cms-companies ◄──── cms-experts     (company_experts M2M)
cms-companies ◄──── cms-speakers    (speaker company_id FK)
cms-companies ◄──── cms-events      (organizer reference)
cms-experts   ◄──── cms-speakers    (speaker expert_id FK)
cms-experts   ◄──── cms-events      (event_speakers M2M)
cms-speakers  ◄──── cms-events      (event_speakers M2M)
cms-companies ◄──── cms-organigramm (Root-Nodes)
cms-experts   ◄──── cms-organigramm (Mitarbeiter-Nodes)
cms-jobprofile-generator ◄── cms-organigramm (Vakante Stellen)
```

> **Regel für Cross-Plugin-Zugriff:** Immer mit `CMS\PluginManager::instance()->isPluginActive('slug')` absichern!

---

## Zentrales Dokumentations-Verzeichnis

Alle umfangreichen Referenzdokumentationen befinden sich unter:

```
DOC/
├── cms-companies/
├── cms-events/
├── cms-experts/
├── cms-importer/
├── cms-newsletter/
├── cms-promos/
├── cms-m365tools/
├── cms-jobprofile-generator/
├── cms-organigramm/
├── cms-speakers/
└── TASKS.md           # Zukünftige Plugin-Konzepte
```

---

## Entwicklung & Konventionen

- **PHP:** 8.1 Minimum, 8.2+ empfohlen
- **Datenbank:** MariaDB 10.6+ / MySQL 8.0+
- **Zeichensatz:** `utf8mb4` / `utf8mb4_unicode_ci`
- **Namenskonvention:** Tabellen-Präfix über `CMS\Database::instance()->prefix()`
- **Admin-Seiten:** Nutzen das Layout aus `CMS/assets/css/admin.css`
- **Member-Seiten:** Views in `member/`
- **Bootstrap:** Jedes Plugin registriert sich via `cms_init`-Hook
- **Öffentliche Archiv-/Landingpages:** Werden typischerweise über `register_routes` + `ThemeManager`-Header/Footer eingebunden

---

## Lizenz

Part of 365CMS — All Rights Reserved © 2024–2026 365 Network
