# Rolle
Senior PHP-Entwickler spezialisiert auf komplexe, interaktive 365CMS Plugins und Cross-Plugin-Datenintegrationen.

# Aufgabe
Implementiere das eigenständige Plugin „CMS Organigramm“ (`cms-organigramm`). Dieses Plugin generiert interaktive Unternehmensstrukturen, indem es Daten aus den bestehenden Plugins `cms-companies`, `cms-experts` und `cms-jobprofile-generator` visuell verknüpft. Liefere ausschließlich produktionsreifen Code ohne Markdown-Erklärungen.

# Technische Anforderungen (High to Low Priority)

## 1. Plugin-Struktur & Architektur
* **Verzeichnis & Namespace:** Pfad `plugins/cms-organigramm/`. Namespace `CMS365\Plugins\Organigramm`.
* **Sicherheit:** `declare(strict_types=1);` und `if (!defined('ABSPATH')) exit;` in jeder PHP-Datei.
* **Pattern:** Nutze das Singleton-Pattern (`OrganigrammPlugin::instance()`).
* **Datenmodell:** Eigene Tabellen für die Hierarchie (z.B. `cms_organigramm_trees`, `cms_organigramm_edges` für Parent-Child-Beziehungen), da die reinen Experten-Tabellen keine Hierarchie/Reporting-Lines abbilden.

## 2. Cross-Plugin Integration (Strict Rule: Lose Kopplung)
Das Organigramm aggregiert Daten aus anderen Plugins. Alle Zugriffe müssen mit `if (CMS\PluginManager::instance()->isPluginActive('...'))` abgesichert werden.
* **cms-companies:** Dienen als Root-Nodes (Das Unternehmen) oder Sub-Nodes (Abteilungen/Tochtergesellschaften).
* **cms-experts:** Dienen als Mitarbeiter-Nodes (Name, Foto, Rolle). Werden via `cms_company_experts` dem Root-Node zugeordnet.
* **cms-jobprofile-generator:** Dienen als "Vakante Stellen"-Nodes. Zeigen offene Positionen direkt im Organigramm an (z.B. gestrichelte Umrandung).

## 3. Strict Vanilla Strategy (Frontend & Interaktivität)
* Das Organigramm (Frontend & Backend-Builder) MUSS **ausschließlich mit Vanilla JavaScript (ES6+), CSS3 Flexbox/Grid und Inline-SVG** gezeichnet werden. 
* **Strikes Verbot** von externen Diagramm-Bibliotheken (kein D3.js, React Flow, GoJS, jQuery, Tailwind).
* Funktionen: Drag & Drop (Native HTML5 API) für Nodes im Builder, Zoom & Pan (via CSS `transform: scale`), Node-Expand/Collapse.

## 4. Admin Menüstruktur (5x5 Tabs)
Implementiere folgende Menüstruktur über den Hook `admin_menu`:
1.  **Dashboard**
    * Tabs: 1. Übersicht | 2. Alle Organigramme | 3. Entwürfe | 4. Papierkorb | 5. Statistiken (Views)
2.  **Visual Builder (Editor)**
    * Tabs: 1. Canvas (Visual Editor) | 2. Nodes verwalten | 3. Hierarchie-Listenansicht | 4. Layout-Settings | 5. Live-Preview
3.  **Datenquellen & Sync**
    * Tabs: 1. Companies Sync | 2. Experts Sync | 3. Vacant Jobs Sync | 4. Manuelle Nodes | 5. Sync-Logs
4.  **Design & Export**
    * Tabs: 1. Theme-Presets | 2. Node-Styling (Farben/CSS) | 3. Typografie | 4. PDF/Image Export-Settings | 5. Embed-Codes (Shortcodes)
5.  **Einstellungen**
    * Tabs: 1. Allgemein | 2. Berechtigungen | 3. Caching (`CMS\CacheManager`) | 4. Limit-Warnungen | 5. System-Info

## 5. Security & Subscription Gating (365CMS Standards)
* **Limits:** Prüfe beim Anlegen neuer Organigramme Limitierungen aus dem Abo-System (z.B. `limit_organigramms` oder Node-Limits pro Chart) via `CMS\SubscriptionManager`.
* **Features:** Exporte (PDF/SVG) und Custom Branding (Node-Farben) mit `user_has_feature('organigramm_export')` gaten.
* **CSRF & DB:** Nutze `$security->generateNonce()`/`verifyNonce()` für den Editor-Autosave. Verwende `$db = CMS\Database::instance()` und Prepared Statements für alle Struktur-Speicherungen.
* **XSS:** Sichere Node-Namen und Job-Titel in der SVG/DOM-Ausgabe zwingend mit `CMS\Security::instance()->escapeOutput()` ab.

# Aufgabe
Implementiere die Member-Dashboard-Integration (`/member/...`) und die öffentliche Ansicht (`/org/:slug`) für das Plugin „CMS Organigramm“. Liefere ausschließlich produktionsreifen Code ohne Erklärungen. Halte dich strikt an die 365CMS-Sicherheits- und Architekturvorgaben.

# Technische Anforderungen (High to Low Priority)

## 1. Member-Dashboard Integration (`/member/organigramm`)
* **Routing:** Registriere die Route `/member/organigramm` über den Hook `routes_registered`.
* **Security & Auth:** Prüfe zwingend `$auth = CMS\Auth::instance(); if (!$auth->isLoggedIn()) { exit; }` bevor die Seite gerendert wird.
* **Menü-Integration:** Nutze den Filter `member_menu_items`, um das Organigramm in der Member-Sidebar (Kategorie: `plugins`) anzuzeigen.
* **Dashboard Widget:** Nutze den Filter `member_dashboard_widgets`, um auf der Startseite `/member` eine Kachel mit Kurzinformationen (z.B. "Du hast 2 aktive Organigramme") anzuzeigen.

## 2. Subscription-System & Limits (Strikte Einhaltung)
* **Plugin-Zugriff:** Verberge den Menüpunkt und blockiere die Route, falls `!user_can_access_plugin('cms-organigramm')`.
* **Limit-Prüfung:** Zeige die Warnung `display_resource_limit_warning('organigramms', 'Organigramme')` über der Liste der eigenen Organigramme an.
* **Erstellung blockieren:** Prüfe beim Speichern eines neuen Organigramms zwingend `if (!user_can_create_resource('organigramms')) { ... }`.

## 3. Formulare & Datensicherheit im Member-Bereich
* **CSRF-Schutz:** Generiere für alle Formulare im Member-Bereich Nonces via `CMS\Security::instance()->generateNonce('member_org_save')` und prüfe diese bei POST-Requests mit `verifyNonce()`.
* **SQL-Sicherheit:** Wenn der eingeloggte User seine Organigramme abfragt, MUSS die Query zwingend an seine ID gebunden sein: `SELECT * FROM {$p}organigramm_charts WHERE user_id = ?` (Prepared Statement).
* **XSS-Schutz:** Jede dynamische Ausgabe (Titel des Organigramms, Nodes) muss im Template durch `htmlspecialchars()` oder `$security->escapeOutput()` maskiert werden.

## 4. Öffentliche Ansicht (`/org/:slug`)
* **Routing:** Registriere eine Public-Route für das Teilen des Organigramms (z.B. für Externe oder Bewerber).
* **Zugriffskontrolle:** Prüfe, ob das Organigramm den Status `published` hat.
* **Layout:** Diese Ansicht benötigt keine Member-Sidebar. Nutze eine saubere Fullscreen-Ansicht, die nur das Vanilla JS / SVG-Canvas lädt, damit User per Maus/Touch navigieren können.
* **Feature-Gating:** Zeige Branding (eigene Farben/Logos) im Public-View nur an, wenn der Ersteller (User-ID) das Abo-Feature `feature_organigramm_branding` besitzt (`user_has_feature('organigramm_branding', $ownerId)`).

## 5. UI/UX (Strict Vanilla Strategy)
* Verwende ausschließlich Vanilla PHP, Vanilla JS (ES6+) und CSS3 Custom Properties.
* Keine externen Frameworks. Optische Anpassung an die Member-Templates des Core-Systems (Nutze bestehende CSS-Klassen wie `.member-card`, `.btn-primary` falls anwendbar).

# Anweisung an die KI
Generiere auf Zuruf die entsprechenden Dateien (z.B. `includes/class-member.php`, `templates/member/archive.php`). Beachte bei der Ausgabe im Member-Bereich, dass Variablen, die aus dem Backend kommen, konsequent sanitized und escaped werden müssen.