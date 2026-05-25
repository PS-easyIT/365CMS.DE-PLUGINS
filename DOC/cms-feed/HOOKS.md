# CMS Feed – Hooks-Referenz

> Alle Hooks nutzen das `CMS\Hooks`-System.  
> Priorität ist standardmäßig `10`.

---

## Actions (registriert vom Plugin)

### cms_init

**Registriert in:** `CMS_Feed::init_hooks()`  
**Callback:** `CMS_Feed::init_plugin()`  
**Beschreibung:** Initialisiert alle Plugin-Klassen als Singletons.

### plugin_activated

**Registriert in:** `CMS_Feed::init_hooks()`  
**Callback:** `CMS_Feed::on_activation(string $plugin)`  
**Beschreibung:** Erstellt DB-Tabellen und füllt Standardwerte, wenn das Plugin aktiviert wird.  
**Parameter:** `$plugin` – Plugin-Slug (reagiert nur auf `'cms-feed'`)

### register_routes

**Registriert in:** `CMS_Feed::init_hooks()`  
**Callback:** `CMS_Feed::register_routes($router)`  
**Beschreibung:** Registriert alle Admin- und Public-Routen am CMS-Router.

**Routen:**
| Methode | Pfad | Handler |
|---------|------|---------|
| GET/POST | `/admin/feeds` | `CMS_Feed_Admin::admin_page()` |
| GET/POST | `/admin/plugins/feeds/feeds` | `CMS_Feed_Admin::render_dispatch()` via `cms_admin_menu` / `add_menu_page()` |
| GET | `/{archive_slug}` | `CMS_Feed_Public_Controller::route_archive()` |
| GET | `/{archive_slug}/embed` | `CMS_Feed_Public_Controller::route_whitelabel()` |
| GET | `/feed/:catSlug` | `CMS_Feed_Public_Controller::route_category()` |
| GET | `/{archive_slug}/:catSlug` | `CMS_Feed_Public_Controller::route_category()` |

Seit `3.0.3` normalisieren die Public-Routen Query-Parameter arraysicher und liefern bei Template-/DB-Ausnahmen die native 365CMS-Theme-Fehlerseite (`error.php`). Fehlende oder nicht öffentliche Bereiche antworten mit `404` und nutzen die native Theme-404-Seite.

### Fehlerseiten & Logging

`CMS_Feed_Error_Handler` bündelt Plugin-Ausnahmen:

- Logging erfolgt bevorzugt über `CMS\Logger::instance()->withChannel('plugin-cms-feed')` inklusive Request-Kontext und Exception-Metadaten.
- Public/Admin-Fehler werden mit passendem HTTP-Status über `ThemeManager::render('error', ['error_code' => ..., 'error_title' => ..., 'error_message' => ...])` ausgegeben.
- 404-Fälle verwenden `ThemeManager::render('404')`.
- Nur wenn der CMS-Logger oder die native Fehlerseite selbst fehlschlagen, greift ein minimaler Fallback auf `error_log()` bzw. einfache HTML-Ausgabe.

### Plugin-Lifecycle

**Registriert über Core-PluginManager-Konventionen:** freie Funktionen in `cms-feed.php`

| Funktion | Zeitpunkt | Verhalten |
|----------|-----------|-----------|
| `cms_feed_activate()` | Aktivierung | Lädt Abhängigkeiten, erstellt/migriert Tabellen, ergänzt Indexe/FKs und seedet Defaults. |
| `cms_feed_deactivate()` | Deaktivierung | Gibt hängen gebliebene `processing`-Queue-Tasks frei. |
| `cms_feed_uninstall()` | Plugin-Löschung | Ruft `CMS_Feed_Database::drop_tables()` auf und entfernt Plugin-Tabellen in FK-sicherer Reihenfolge. |

### cms_admin_menu

**Registriert in:** `CMS_Feed_Admin::__construct()`  
**Callback:** `CMS_Feed_Admin::register_menu()`  
**Beschreibung:** Registriert `CMS Feed` als Plugin-Seite für die aktuelle 365CMS-Admin-Sidebar über `add_menu_page()`.

### head

**Registriert in:** `CMS_Feed::init_hooks()`  
**Callback:** `CMS_Feed::enqueue_styles()`  
**Beschreibung:** Lädt CSS je nach Kontext (Admin: `feed-admin.css`, Public: `style.css` + Design-Tokens).

### body_end

**Registriert in:** `CMS_Feed::init_hooks()`  
**Callback:** `CMS_Feed::enqueue_scripts()`  
**Beschreibung:** Lädt JavaScript je nach Kontext (Admin: `admin.js`, Public: `script.js`). Public-Assets werden nur auf echten Feed-Archiv-Routen geladen.

### cms_cron_hourly

**Registriert in:** `CMS_Feed_Email_Digest::__construct()`  
**Callback:** `CMS_Feed_Email_Digest::process_digests()`  
**Beschreibung:** Prüft stündlich, welche Digests fällig sind, und versendet sie. Seit `1.3.0` verarbeitet derselbe Lauf zusätzlich persönliche Member-Feed-Abos (`daily`, `daily 2×`, `weekly`).

### cms_cron_hourly (Feed-Queue)

**Registriert in:** `CMS_Feed_Cron::__construct()` (Priorität 20)  
**Callback:** `CMS_Feed_Cron::process_queue()`  
**Beschreibung:** Priorisiert bei aktivem `cms-phinit` die auf der Startseite ausgewählten Feed-Kanäle, reiht zusätzlich alle regulär fälligen Kanäle in die Fetch-Queue ein, verarbeitet direkt einen ersten Batch und bereinigt nicht hervorgehobene Beiträge älter als 7 Tage automatisch.

**Wichtig:** Seit dem Core-Fix vom `2026-03-17` wird `cms_cron_hourly` über den Core-Cron-Endpunkt (im Repo `CMS/cron.php`, in typischen FTP-Deployments als `/cron.php`) auch bei bestehenden Aufrufen von `task=mail-queue` automatisch mit ausgelöst, aber intern auf höchstens einen echten Lauf pro Stunde gedrosselt. Zusätzlich sind die Tasks `hourly` und `all` verfügbar.

### cms_cron_mail_queue (Feed-Queue-Drain)

**Registriert in:** `CMS_Feed_Cron::__construct()` (Priorität 20)  
**Callback:** `CMS_Feed_Cron::drain_pending_queue()`  
**Beschreibung:** Verarbeitet bei jedem regulären Mail-Queue-/`all`-Cron-Lauf einen kleinen Batch bereits eingereihter Feed-Tasks. Dadurch schrumpfen Rückstaus zwischen zwei stündlichen Läufen weiter, ohne dass neue fällige Kanäle minütlich doppelt eingereiht werden.

**Wichtig:** Der Core feuert `cms_cron_mail_queue` jetzt auch dann als echten Hook, wenn `cron.php` die Mail-Queue intern bereits direkt verarbeitet hat. Das Kontext-Flag `mail_queue_already_handled` verhindert dabei nur die doppelte Mail-Verarbeitung, nicht aber zusätzliche Plugin-Worker wie `cms-feed`.

Zusätzlich werden hängen gebliebene `feed_fetch_queue`-Einträge vor dem nächsten Drain-Lauf automatisch wieder auf `pending` gesetzt, sobald sie länger als 20 Minuten in `processing` stehen und noch kein `processed_at` besitzen.

---

## Filter / Legacy

### admin_menu_items

**Registriert in:** `CMS_Feed_Admin::__construct()`  
**Callback:** `CMS_Feed_Admin::add_menu_item(array $menuItems): array`  
**Beschreibung:** Legacy-Fallback für ältere Admin-Menüs. Die aktuelle Sidebar nutzt primär `cms_admin_menu` + `add_menu_page()`.

**Rückgabe:** Array mit zusätzlichem Menüeintrag:
```php
[
    'type'   => 'item',
    'slug'   => 'feeds',
    'label'  => 'Feeds',
    'icon'   => '📡',
    'url'    => '/admin/feeds',
    'active' => bool,
]
```

---

## CSS Custom Properties (Design-Tokens)

Werden über `CMS_Feed::inject_design_tokens()` als `:root`-Variablen injiziert:

| Variable | Default | Quelle (Setting-Key) |
|----------|---------|---------------------|
| `--fd-primary` | `#0891b2` | `color_primary` |
| `--fd-accent` | `#e0f2fe` | `color_accent` |
| `--fd-hdr-from` | `#0c4a6e` | `color_hdr_from` |
| `--fd-hdr-to` | `#0891b2` | `color_hdr_to` |
| `--fd-hdr-title` | `#ffffff` | `color_hdr_title` |
| `--fd-card-bg` | `#ffffff` | `color_card_bg` |
| `--fd-card-border` | `#e2e8f0` | `color_card_border` |
| `--fd-radius` | `10px` | `border_radius` |

---

## POST-Actions (Admin-Backend)

Alle POST-Actions werden in `CMS_Feed_Admin::handle_post()` verarbeitet und erfordern ein gültiges CSRF-Token (`cms_feed_admin`). Seit `3.0.3` werden alle POST-Felder zentral arraysicher normalisiert; ungültige Tokens setzen `403`, unbekannte Aktionen `400` und interne Fehler `500`.

| Action | Tab | Beschreibung |
|--------|-----|-------------|
| `save_channel` | channels | Kanal erstellen/aktualisieren |
| `delete_channel` | channels | Kanal + Beiträge löschen |
| `save_category` | categories | Bereich erstellen/aktualisieren |
| `delete_category` | categories | Bereich + Kanäle + Beiträge löschen |
| `fetch_now` | dashboard/channels | Feed(s) sofort abrufen |
| `toggle_hidden` | items | Beitrag aus-/einblenden |
| `toggle_featured` | items | Beitrag hervorheben/entfernen |
| `delete_item` | items | Einzelnen Beitrag löschen |
| `save_digest` | digests | Digest erstellen/aktualisieren |
| `delete_digest` | digests | Digest löschen |
| `test_digest` | digests | Test-Digest-E-Mail senden |
| `save_settings` | settings | Allgemeine Einstellungen speichern |
| `save_design` | settings | Design-Einstellungen speichern |
| `save_digest_settings` | digests | Digest-Grundeinstellungen speichern |
| `cleanup` | settings/dashboard | Alte Beiträge aufräumen |
| `import_catalog` | catalog | Feeds aus Katalog-Kategorie komplett oder als Auswahl importieren |
| `bulk_delete_channels` | channels | Mehrere Kanäle + Beiträge löschen |
| `bulk_activate_channels` | channels | Mehrere Kanäle aktivieren |
| `bulk_deactivate_channels` | channels | Mehrere Kanäle deaktivieren |
| `bulk_fetch_channels` | channels | Mehrere Kanäle abrufen (max. 5 sofort, Rest in Queue) |
| `bulk_delete_categories` | categories | Mehrere Bereiche + Kanäle + Beiträge löschen |

---

## Member-Integration (`cms-phinit`)

- Theme-Seite: `/member/feeds`
- Benötigt aktive Klassen `CMS_Feed_Database` und `CMS_Feed_Email_Digest`
- Speichert persönliche Abos in `{prefix}feed_member_subscriptions`
- Versand-Slots werden stündlich ausgewertet:
    - täglich `09:00`
    - täglich `15:00`
    - täglich `09:00 + 15:00`
    - wöchentlich an frei gewähltem Wochentag um `09:00` oder `15:00`
