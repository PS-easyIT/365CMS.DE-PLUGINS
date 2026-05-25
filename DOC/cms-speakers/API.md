# API-Referenz – cms-speakers

> Vollständige PHP-Klassendokumentation für `CMS_Speakers`.

---

## Klassen-Übersicht

| Klasse | Datei | Zweck |
|--------|-------|-------|
| `CMS_Speakers` | `cms-speakers.php` | Haupt-Singleton, Hook-Registrierung |
| `CMS_Speakers_Database` | `includes/class-database.php` | DB-Operationen |
| `CMS_Speakers_Post_Type` | `includes/class-post-type.php` | Public-/Admin-Routen, Controller |
| `CMS_Speakers_Admin` | `includes/class-admin.php` | Admin-Menü und Admin-Views |
| `CMS_Speakers_Meta_Boxes` | `includes/class-meta-boxes.php` | Admin-Formularbereiche |
| `CMS_Speakers_Template_Loader` | `includes/class-template-loader.php` | Template-Lookup und Card-Rendering |
| `CMS_Speakers_Shortcode` | `includes/class-shortcode.php` | `[cms_speakers]`-Content-Filter |
| `CMS_Speakers_Member_Dashboard` | `includes/class-member-dashboard.php` | Member-Dashboard-Integration |

---

## CMS_Speakers (Haupt-Klasse)

```php
CMS_Speakers::instance(): self
```

### Registrierte Hooks

| Methode | Hook | Priorität |
|---------|------|-----------|
| `init_plugin()` | `cms_init` | 10 |
| `on_activation()` | `plugin_activated` (slug = `cms-speakers`) | 10 |
| `on_deactivation()` | `plugin_deactivated` (slug = `cms-speakers`) | 10 |
| `on_uninstall()` | `plugin_uninstalled` (slug = `cms-speakers`) | 10 |
| `enqueue_styles()` | `head` | 10 |
| `enqueue_scripts()` | `body_end` | 10 |

---

## CMS_Speakers_Database

```php
CMS_Speakers_Database::instance(): self
```

### Speaker-CRUD

#### `get_speakers(array $args = []): array`

**Filter-Parameter:**

| Schlüssel | Typ | Beschreibung |
|-----------|-----|--------------|
| `status` | string | `active`, `inactive`, `pending`, `all` |
| `format` | string | `keynote`, `workshop`, `panel`, `moderation`, `training`, `consulting` |
| `availability` | string | `available`, `limited`, `booked` |
| `search` | string | Volltextsuche (Name, Titel, Bio) |
| `travel_radius` | string | `local`, `regional`, `national`, `international`, `worldwide` |
| `limit` / `offset` | int | Pagination, Limit wird auf 200 begrenzt |

---

#### `get_speaker(int $id): ?object`

Gibt alle Felder aus `cms_speakers` zurück.

```php
$speaker = CMS_Speakers_Database::instance()->getById(7);
/*
[
    'id'            => 7,
    'user_id'       => 42,
    'first_name'    => 'Anna',
    'last_name'     => 'Beispiel',
    'gender'        => 'female',
    'speaker_title' => 'KI-Expertin & Keynote-Speakerin',
    'bio_short'     => '...',
    'bio_long'      => '...',
    'languages'     => 'de,en',
    'formats'       => 'keynote,workshop',
    'fee_min'       => 2500,
    'fee_max'       => 8000,
    'travel_radius' => 500,
    'travel_international' => 1,
    'status'        => 'active',
    ...
]
*/
```

---

#### `save_speaker(array $data, int $id = 0): int|false`

**Pflichtfelder:** `first_name`, `last_name`  
**Rückgabe:** Neue oder aktualisierte ID  
**Feuert:** `speaker_created` bei Inserts und `speaker_updated` bei Updates mit `(int $id, array $data)`

---

#### `delete_speaker(int $id): bool`

Löscht Speaker inklusive Topics und Event-Zuordnungen.  

---

### Themen (Topics)

#### `getTopics(int $speakerId): array`

```php
$topics = CMS_Speakers_Database::instance()->getTopics(7);
/*
[
    ['id' => 1, 'topic_name' => 'Künstliche Intelligenz', 'topic_slug' => 'ki', ...],
    ...
]
*/
```

---

#### `save_topics(int $speaker_id, array $topics): void`

Ersetzt die Themen eines Speakers vollständig. Akzeptiert Strings oder Arrays mit `name`/`desc`.

---

### Event-Zuordnungen

#### `get_events(int $speaker_id, bool $public_only = false): array`

Gibt manuelle Speaker-Auftritte aus `cms_speaker_events` zurück. Optionale Company-Daten werden nur gejoint, wenn `cms_companies` existiert.

#### `save_event(int $speaker_id, array $data): int|false`

Speichert einen manuellen Auftritt. Felder werden per Allow-List und Enum-Whitelists normalisiert.

#### `delete_event(int $id): bool`

Löscht einen manuellen Auftritt.

### Settings

#### `get_settings(): array`

Liest Defaults, Legacy-Settings und anschließend die Core-SettingsService-Gruppe `cms-speakers`.

#### `save_settings(array $settings): void`

Speichert primär in `CMS\Services\SettingsService`; bei fehlendem Service fällt die Methode auf `cms_speaker_plugin_settings` zurück.

#### `drop_tables(): void`

Uninstall-Cleanup: entfernt Speaker-Tabellen und bereinigt die SettingsService-Gruppe.

---

## CMS_Speakers_Shortcode

### Shortcodes

#### `[cms_speakers]`

| Attribut | Standard | Beschreibung |
|----------|---------|--------------|
| `limit` | `12` | Max. Anzahl |
| `travel` | `null` | Reisebereitschaft: `local`, `regional`, `national`, `international`, `worldwide` |
| `featured` | `false` | Nur Featured-Speaker (`1`) |

**Beispiele:**
```html
[cms_speakers limit="8" travel="national" featured="1"]
```

---

### Template-Variablen

```php
$speaker['id']                  // int
$speaker['first_name']          // string
$speaker['last_name']           // string
$speaker['gender']              // ''|'m'|'f'|'d'
$speaker['title']               // string|null
$speaker['short_bio']           // string|null
$speaker['bio']                 // string|null
$speaker['languages']           // JSON-Array
$speaker['formats']             // JSON-Array
$speaker['speaking_fee_min']    // decimal|null
$speaker['speaking_fee_max']    // decimal|null
$speaker['travel_radius']       // local|regional|national|international|worldwide
$speaker['status']              // active|inactive|draft|pending|deleted
$speaker['photo_url']           // string|null
$speaker['website']             // string|null
$speaker['linkedin']            // string|null

$topics                         // Array aus getTopics()
$events                         // manuelle und optionale cms-events-Auftritte
```

---

## HTTP-/AJAX-Endpunkte

| Route | Methode | Berechtigung | Beschreibung |
|-------|---------|-------------|--------------|
| `/speakers` | GET | öffentlich | Speaker-Archiv |
| `/speakers/:slug` | GET | öffentlich | Speaker-Detailseite |
| `/admin/speakers` | GET | Admin | Dashboard/Liste |
| `/admin/speakers/save` | POST | Admin + CSRF | Speaker speichern |
| `/admin/speakers/delete/:id` | POST | Admin + CSRF | Speaker löschen |
| `/admin/speakers/approve/:id` | POST | Admin + CSRF | Pending-Speaker freigeben |
| `/admin/speakers/event/add` | POST | Admin + CSRF | Auftritt per JSON speichern |
| `/admin/speakers/event/delete/:id` | POST | Admin + CSRF | Auftritt per JSON löschen |
| `/admin/speakers/settings/save` | POST | Admin + CSRF | Einstellungen speichern |

Alle POST-only Routen besitzen einen GET-Fallback mit `405 Method Not Allowed` und `Allow: POST`.

**Antwortformat:**
```json
{ "success": true, "id": 7 }
{ "success": false, "error": "Thema nicht gefunden" }
```

---

## Fehler-Codes

| Code | Bedeutung |
|------|-----------|
| `csrf_failed` | CSRF-Token ungültig |
| `not_found` | Speaker nicht gefunden |
| `not_authorized` | Keine Berechtigung |
| `validation_error` | first_name oder last_name fehlt |
| `plugin_inactive` | Benötigtes Plugin nicht aktiv (z. B. cms-events) |
| `db_error` | Datenbank-Fehler |
