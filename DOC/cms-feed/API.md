# CMS Feed – API-Referenz

> PHP-Klassen und ihre öffentlichen Methoden.

---

## CMS_Feed (Hauptklasse)

**Datei:** `cms-feed.php`  
**Pattern:** Singleton (`CMS_Feed::instance()`)

| Methode | Beschreibung |
|---------|-------------|
| `instance(): self` | Singleton-Instanz |
| `register_routes($router): void` | Registriert alle Routen |
| `on_activation(string $plugin): void` | Hook: Plugin-Aktivierung |
| `init_plugin(): void` | Initialisiert alle Subklassen |
| `enqueue_styles(): void` | CSS einbinden (Head-Hook) |
| `enqueue_scripts(): void` | JS einbinden (Body-End-Hook) |
| `get_version(): string` | Plugin-Version |
| `get_plugin_dir(): string` | Absoluter Plugin-Pfad |
| `get_plugin_url(): string` | Relativer Plugin-URL-Pfad |

---

## CMS_Feed_Database

**Datei:** `includes/class-database.php`  
**Pattern:** Singleton

### Tabellen & Setup

| Methode | Beschreibung |
|---------|-------------|
| `create_tables(): void` | Erstellt alle DB-Tabellen (idempotent) |
| `seed_defaults(): void` | Füllt Standard-Einstellungen (INSERT IGNORE) |
| `get_table_names(): array` | Liste aller Tabellennamen mit Prefix |

### Settings

| Methode | Beschreibung |
|---------|-------------|
| `get_settings(): array` | Alle Einstellungen als `[key => value]` |
| `get_setting(string $key, string $default = ''): string` | Einzelne Einstellung |
| `update_settings(array $data): void` | Einstellungen upserten |

### Categories (Bereiche)

| Methode | Beschreibung |
|---------|-------------|
| `get_categories(): array` | Alle Bereiche (sortiert) |
| `get_category(int $id): ?array` | Bereich per ID |
| `get_category_by_slug(string $slug): ?array` | Bereich per Slug |
| `get_public_categories(): array` | Nur öffentliche Bereiche |
| `save_category(array $data): int` | Erstellen/Aktualisieren, gibt ID zurück |
| `delete_category(int $id): void` | Bereich + alle Daten löschen |

### Channels (Kanäle)

| Methode | Beschreibung |
|---------|-------------|
| `get_channels(int $categoryId = 0): array` | Alle oder nach Bereich gefiltert |
| `get_channel(int $id): ?array` | Kanal per ID |
| `save_channel(array $data): int` | Erstellen/Aktualisieren, gibt ID zurück |
| `delete_channel(int $id): void` | Kanal + Beiträge löschen |
| `update_channel_fetch(int $id, ?string $error, int $itemCount): void` | Fetch-Status aktualisieren |

### Items (Beiträge)

| Methode | Beschreibung |
|---------|-------------|
| `insert_item(array $data): bool` | Beitrag importieren (INSERT IGNORE) |
| `get_items(array $filters, int $offset, int $limit): array` | Beiträge mit Filtern |
| `count_items(array $filters): int` | Anzahl Beiträge zählen |
| `toggle_item_featured(int $id): void` | Featured-Status toggeln |
| `toggle_item_hidden(int $id): void` | Hidden-Status toggeln |
| `delete_item(int $id): void` | Einzelnen Beitrag löschen |
| `cleanup_old_items(int $days = 90): int` | Alte Beiträge löschen (nicht Featured) |

**Filter-Parameter für `get_items()` / `count_items()`:**

| Key | Typ | Beschreibung |
|-----|-----|-------------|
| `category_id` | `int` | Nach Bereich filtern |
| `channel_id` | `int` | Nach Kanal filtern |
| `search` | `string` | Volltextsuche (Titel + Beschreibung) |
| `since` | `string` | Nur Beiträge ab diesem Datum |
| `is_featured` | `int` | Featured-Status (0/1) |
| `include_hidden` | `bool` | Auch versteckte Beiträge einbeziehen |

### Digests

| Methode | Beschreibung |
|---------|-------------|
| `get_digests(): array` | Alle Digests |
| `get_digest(int $id): ?array` | Digest per ID |
| `save_digest(array $data): int` | Erstellen/Aktualisieren |
| `delete_digest(int $id): void` | Digest löschen |
| `update_digest_sent(int $id): void` | Sendezeitpunkt aktualisieren |
| `get_active_digests_due(): array` | Fällige Digests laden |

### Statistiken

| Methode | Beschreibung |
|---------|-------------|
| `get_stats(): array` | Dashboard-Statistiken |

**Rückgabe von `get_stats()`:**
```php
[
    'categories'      => int,  // Anzahl Bereiche
    'channels'        => int,  // Anzahl Kanäle
    'channels_active' => int,  // Aktive Kanäle
    'items'           => int,  // Sichtbare Beiträge
    'items_today'     => int,  // Heute importiert
    'digests'         => int,  // Aktive Digests
    'channels_errors' => int,  // Kanäle mit Fehlern
]
```

---

## CMS_Feed_RSS_Fetcher

**Datei:** `includes/class-rss-fetcher.php`  
**Pattern:** Singleton

| Methode | Beschreibung |
|---------|-------------|
| `fetch_all_due(): array` | Alle fälligen Kanäle abrufen |
| `fetch_channel(int $channelId): array` | Einzelnen Kanal abrufen |

**Rückgabe von `fetch_channel()`:**
```php
[
    'success'   => bool,
    'error'     => ?string,
    'new_items' => int,
]
```

**Unterstützte Formate:** RSS 2.0, RSS 1.0 (RDF), Atom

---

## CMS_Feed_Public_Controller

**Datei:** `includes/class-public-controller.php`  
**Pattern:** Singleton

| Methode | Beschreibung |
|---------|-------------|
| `register_routes($router): void` | Public-Routen registrieren |
| `route_archive(): void` | Hauptarchiv rendern |
| `route_category(string $catSlug): void` | Bereichsseite rendern |

---

## CMS_Feed_Template_Loader

**Datei:** `includes/class-template-loader.php`  
**Pattern:** Singleton

| Methode | Beschreibung |
|---------|-------------|
| `render_template(string $name, array $data): void` | Template rendern |
| `locate_template(string $name): ?string` | Template-Pfad finden (Theme → Plugin) |
| `get_template_part(string $slug, string $name, array $data): void` | Template-Teil laden |
| `buffer_template(string $name, array $data): string` | Template als String puffern |

**Theme-Override:** Templates aus `{theme}/cms-feed/` haben Vorrang vor Plugin-Templates.

---

## CMS_Feed_Email_Digest

**Datei:** `includes/class-email-digest.php`  
**Pattern:** Singleton

| Methode | Beschreibung |
|---------|-------------|
| `process_digests(): array` | Alle fälligen Digests verarbeiten |
| `send_digest(array $digest): bool` | Einzelnen Digest versenden |
| `send_test_digest(int $digestId): bool` | Test-E-Mail senden |
| `get_frequency_label(int $frequency): string` | Frequenz als Text |

---

## CMS_Feed_Admin

**Datei:** `includes/class-admin.php`  
**Pattern:** Singleton

| Methode | Beschreibung |
|---------|-------------|
| `admin_page(): void` | Router-Callback für `/admin/feeds` |
| `add_menu_item(array $menuItems): array` | Admin-Menü-Filter |
| `render_list(array $data): void` | Admin-Oberfläche rendern |
