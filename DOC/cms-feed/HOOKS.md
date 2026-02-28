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
| GET | `/{archive_slug}` | `CMS_Feed_Public_Controller::route_archive()` |
| GET | `/{archive_slug}/:catSlug` | `CMS_Feed_Public_Controller::route_category()` |

### head

**Registriert in:** `CMS_Feed::init_hooks()`  
**Callback:** `CMS_Feed::enqueue_styles()`  
**Beschreibung:** Lädt CSS je nach Kontext (Admin: `feed-admin.css`, Public: `style.css` + Design-Tokens).

### body_end

**Registriert in:** `CMS_Feed::init_hooks()`  
**Callback:** `CMS_Feed::enqueue_scripts()`  
**Beschreibung:** Lädt JavaScript je nach Kontext (Admin: `admin.js`, Public: `script.js`).

### cms_cron_hourly

**Registriert in:** `CMS_Feed_Email_Digest::__construct()`  
**Callback:** `CMS_Feed_Email_Digest::process_digests()`  
**Beschreibung:** Prüft stündlich, welche Digests fällig sind, und versendet sie.

---

## Filter

### admin_menu_items

**Registriert in:** `CMS_Feed_Admin::__construct()`  
**Callback:** `CMS_Feed_Admin::add_menu_item(array $menuItems): array`  
**Beschreibung:** Fügt den „📡 Feeds"-Eintrag zum Admin-Menü hinzu.

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

Alle POST-Actions werden in `CMS_Feed_Admin::handle_post()` verarbeitet und erfordern ein gültiges CSRF-Token (`cms_feed_admin`).

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
