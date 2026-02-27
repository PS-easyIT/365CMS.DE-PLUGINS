# API-Referenz – cms-speakers

> Vollständige PHP-Klassendokumentation für `CMS_Speakers`.

---

## Klassen-Übersicht

| Klasse | Datei | Zweck |
|--------|-------|-------|
| `CMS_Speakers` | `cms-speakers.php` | Haupt-Singleton, Hook-Registrierung |
| `CMS_Speakers_Database` | `includes/class-database.php` | DB-Operationen |
| `CMS_Speakers_Admin` | `admin/class-admin-pages.php` | Admin-Trait-Shell |
| `CMS_Speakers_Admin_Menu` | `admin/class-admin-menu.php` | Menü-Registrierung |
| `CMS_Speakers_Frontend` | `includes/class-frontend.php` | Shortcodes, Template-Routing |
| `CMS_Speakers_Member` | `includes/class-member-dashboard.php` | Member-Profil-Verwaltung |

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
| `register_admin_menu()` | `cms_admin_menu` | 10 |
| `render_member_dashboard()` | `cms_member_dashboard` | 20 |
| `enqueue_styles()` | `head` | 10 |
| `enqueue_scripts()` | `body_end` | 10 |
| `dsgvo_export()` | `dsgvo_export_data` | 10 |
| `dsgvo_delete()` | `dsgvo_delete_data` | 10 |

---

## CMS_Speakers_Database

```php
CMS_Speakers_Database::instance(): self
```

### Speaker-CRUD

#### `getAll(array $filters = [], int $limit = 50, int $offset = 0): array`

**Filter-Parameter:**

| Schlüssel | Typ | Beschreibung |
|-----------|-----|--------------|
| `status` | string | `active`, `inactive`, `pending`, `all` |
| `format` | string | `keynote`, `workshop`, `panel`, `moderation`, `training`, `consulting` |
| `topic_id` | int | Filter nach Themen-ID |
| `language` | string | ISO-Code, z. B. `de`, `en` |
| `available` | bool | Nur verfügbare Speaker |
| `search` | string | Volltextsuche (Name, Titel, Bio) |
| `fee_max` | int | Max. Honorar (€) |
| `travel_radius` | int | Reiseradius in km |

---

#### `getById(int $id): ?array`

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

#### `create(array $data): int`

**Pflichtfelder:** `first_name`, `last_name`  
**Rückgabe:** Neue ID  
**Feuert:** `speaker_created` mit `(int $id, array $data)`

---

#### `update(int $id, array $data): bool`

**Feuert:** `speaker_updated` mit `(int $id, array $data)`

---

#### `delete(int $id): bool`

Löscht Speaker inklusive Topics und Event-Zuordnungen.  
**Feuert:** `speaker_deleted` mit `(int $id)`

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

#### `addTopic(int $speakerId, array $topicData): int`

**Felder:** `topic_name` (Pflicht), `topic_slug`, `topic_category`, `description`  
**Rückgabe:** Neue Topic-ID

---

#### `updateTopic(int $topicId, array $data): bool`

---

#### `deleteTopic(int $topicId): bool`

---

### Event-Zuordnungen

#### `getEvents(int $speakerId): array`

Gibt alle Events zurück, denen dieser Speaker zugeordnet ist.

**Voraussetzung:** `cms-events` muss aktiv sein.

```php
if (CMS\PluginManager::instance()->isPluginActive('cms-events')) {
    $events = CMS_Speakers_Database::instance()->getEvents(7);
}
```

---

#### `assignToEvent(int $speakerId, int $eventId, array $meta = []): bool`

**Meta-Optionen:** `role` (`keynote`, `panel`, `workshop`), `presentation_title`, `presentation_duration`  
**Feuert:** `speaker_event_assigned` mit `(int $speakerId, int $eventId)`

---

#### `removeFromEvent(int $speakerId, int $eventId): bool`

---

### Meta-Felder

#### `getMeta(int $speakerId, string $key): mixed`

#### `getAllMeta(int $speakerId): array`

#### `setMeta(int $speakerId, string $key, mixed $value): void`

#### `deleteMeta(int $speakerId, string $key): void`

---

### Präsentationen

#### `getPresentations(int $speakerId): array`

Gibt alle gespeicherten Vortragstitel / Präsentationen zurück (aus Meta-JSON oder eigenem Feld).

---

## CMS_Speakers_Frontend

### Shortcodes

#### `[speakers_list]`

| Attribut | Standard | Beschreibung |
|----------|---------|--------------|
| `limit` | `12` | Max. Anzahl |
| `status` | `active` | Speaker-Status |
| `format` | `''` | Vortragformat-Filter |
| `topic` | `''` | Themen-Slug-Filter |
| `language` | `''` | Sprach-Filter (ISO) |
| `template` | `grid` | `grid` oder `list` |
| `show_filter` | `true` | Filter-Leiste anzeigen |
| `orderby` | `last_name` | Sortierfeld |

**Beispiele:**
```html
[speakers_list limit="8" format="keynote" language="de"]
[speakers_list topic="digitalisierung" template="list"]
```

---

#### `[speaker_profile id="7"]`

| Attribut | Standard | Beschreibung |
|----------|---------|--------------|
| `id` | — | **Pflicht** – Speaker-ID |
| `template` | `full` | `full`, `card`, `compact` |
| `show_topics` | `true` | Themen anzeigen |
| `show_events` | `true` | Vergangene Events anzeigen |
| `show_contact` | `true` | Kontaktbereich anzeigen |

---

### Template-Variablen

```php
$speaker['id']                  // int
$speaker['first_name']          // string
$speaker['last_name']           // string
$speaker['gender']              // 'male'|'female'|'diverse'|'unknown'
$speaker['speaker_title']       // string
$speaker['bio_short']           // string
$speaker['bio_long']            // string (HTML erlaubt)
$speaker['languages']           // CSV: 'de,en'
$speaker['formats']             // CSV: 'keynote,workshop'
$speaker['fee_min']             // int (€)
$speaker['fee_max']             // int (€)
$speaker['travel_radius']       // int (km)
$speaker['travel_international']// int (0|1)
$speaker['status']              // 'active'|'inactive'|'pending'
$speaker['photo_url']           // string|null
$speaker['website_url']         // string|null
$speaker['linkedin_url']        // string|null

$topics                         // Array aus getTopics()
$events                         // Array aus getEvents() (nur wenn cms-events aktiv)
```

---

## AJAX-Endpunkte

| Action-Key | Berechtigung | Beschreibung |
|-----------|-------------|--------------|
| `speaker_save` | Admin | Speaker erstellen/aktualisieren |
| `speaker_delete` | Admin | Speaker löschen |
| `speaker_topic_add` | Admin | Thema hinzufügen |
| `speaker_topic_delete` | Admin | Thema entfernen |
| `speaker_event_assign` | Admin | Event zuordnen |
| `speaker_event_remove` | Admin | Event-Zuordnung entfernen |
| `speaker_availability` | Member (eigenes Profil) | Verfügbarkeit setzen |
| `speaker_meta_save` | Member (eigenes Profil) | Meta-Felder speichern |

**Antwortformat:**
```json
{ "success": true, "data": { "id": 7 } }
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
