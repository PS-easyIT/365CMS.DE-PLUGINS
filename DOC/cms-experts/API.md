# API-Referenz – cms-experts

> Vollständige PHP-Klassendokumentation für `CMS_Experts`.

---

## Klassen-Übersicht

| Klasse | Datei | Zweck |
|--------|-------|-------|
| `CMS_Experts` | `cms-experts.php` | Haupt-Singleton, Hook-Registrierung |
| `CMS_Experts_Database` | `includes/class-database.php` | DB-Operationen (Experten, Meta, Skills) |
| `CMS_Experts_Admin` | `admin/class-admin-pages.php` | Admin-Trait-Shell |
| `CMS_Experts_Admin_Menu` | `admin/class-admin-menu.php` | Menü-Registrierung |
| `CMS_Experts_Frontend` | `includes/class-frontend.php` | Shortcodes, Template-Routing |
| `CMS_Experts_Member` | `includes/class-member-dashboard.php` | Member-Widget + Self-Edit |

---

## CMS_Experts (Haupt-Klasse)

```php
CMS_Experts::instance(): self
```

### Hooks, die registriert werden

| Methode | Hook | Priorität |
|---------|------|-----------|
| `init_plugin()` | `cms_init` | 10 |
| `on_activation()` | `plugin_activated` (slug = `cms-experts`) | 10 |
| `register_admin_menu()` | `cms_admin_menu` | 10 |
| `render_member_dashboard()` | `cms_member_dashboard` | 20 |
| `enqueue_styles()` | `head` | 10 |
| `enqueue_scripts()` | `body_end` | 10 |
| `dsgvo_export()` | `dsgvo_export_data` | 10 |
| `dsgvo_delete()` | `dsgvo_delete_data` | 10 |

---

## CMS_Experts_Database

```php
CMS_Experts_Database::instance(): self
```

### Experten-CRUD

#### `getAll(array $filters = [], int $limit = 50, int $offset = 0): array`

Gibt eine Liste von Experten zurück.

**Parameter:**

| Name | Typ | Standard | Beschreibung |
|------|-----|---------|--------------|
| `$filters['status']` | string | `'active'` | `active`, `inactive`, `all` |
| `$filters['skill']` | string | — | Skill-Slug-Filter |
| `$filters['available']` | bool | — | Nur verfügbare Experten |
| `$filters['search']` | string | — | Volltextsuche (Name, Berufsbezeichnung) |
| `$limit` | int | 50 | Max. Ergebnisse |
| `$offset` | int | 0 | Paginierungs-Offset |

**Rückgabe:** Array von Experten-Rows (alle Felder aus `cms_experts`)

---

#### `getById(int $id): ?array`

```php
$expert = CMS_Experts_Database::instance()->getById(42);
// ['id' => 42, 'first_name' => 'Max', 'last_name' => 'Mustermann', ...]
```

---

#### `create(array $data): int`

Erstellt einen neuen Experten-Datensatz.

**Pflichtfelder:** `first_name`, `last_name`  
**Optionale Felder:** alle anderen `cms_experts`-Spalten  
**Rückgabe:** Neue ID (`lastInsertId`)  
**Feuert:** Action `expert_created` mit `(int $id, array $data)`

---

#### `update(int $id, array $data): bool`

**Rückgabe:** `true` bei Erfolg  
**Feuert:** Action `expert_updated` mit `(int $id, array $data)`

---

#### `delete(int $id): bool`

Löscht Experte und alle zugehörigen Meta/Skills/Zertifikate (Cascade).  
**Feuert:** Action `expert_deleted` mit `(int $id)`

---

### Meta-Felder

#### `getMeta(int $expertId, string $key): mixed`

```php
$grade = CMS_Experts_Database::instance()->getMeta(42, 'availability_grade');
// z. B. '80'
```

---

#### `getAllMeta(int $expertId): array`

Gibt alle Meta-Felder als `['key' => 'value']` zurück.

---

#### `setMeta(int $expertId, string $key, mixed $value): void`

Upsert: Erstellt oder aktualisiert einen Meta-Eintrag.

```php
CMS_Experts_Database::instance()->setMeta(42, 'availability_grade', '80');
```

---

#### `deleteMeta(int $expertId, string $key): void`

Löscht einen einzelnen Meta-Eintrag.

---

### Skills

#### `getSkills(int $expertId): array`

```php
$skills = CMS_Experts_Database::instance()->getSkills(42);
// [['id' => 1, 'skill_name' => 'PHP', 'level' => 'expert', ...], ...]
```

---

#### `addSkill(int $expertId, array $skillData): int`

**Felder:** `skill_name` (Pflicht), `skill_level`, `skill_type`, `years_experience`  
**Rückgabe:** Neue Skill-ID

---

#### `updateSkill(int $skillId, array $data): bool`

---

#### `deleteSkill(int $skillId): bool`

---

### Zertifikate

#### `getCertifications(int $expertId): array`

---

#### `addCertification(int $expertId, array $data): int`

**Felder:** `cert_name` (Pflicht), `issuer`, `issue_date`, `expiry_date`, `cert_url`

---

#### `deleteCertification(int $certId): bool`

---

## CMS_Experts_Frontend

### Shortcodes

#### `[experts_list]`

Zeigt eine gefilterte Experten-Liste.

| Attribut | Standard | Beschreibung |
|----------|---------|--------------|
| `limit` | `12` | Anzahl der Experten |
| `status` | `active` | Experten-Status |
| `skill` | `''` | Nach Skill filtern |
| `template` | `grid` | `grid` oder `list` |
| `show_filter` | `true` | Skill-Filter anzeigen |
| `orderby` | `last_name` | Sortierfeld |
| `order` | `ASC` | `ASC` oder `DESC` |

**Beispiele:**
```html
[experts_list limit="6" skill="php" template="grid"]
[experts_list status="active" show_filter="false"]
```

---

#### `[expert_profile id="42"]`

Zeigt das vollständige Profil eines Experten.

| Attribut | Standard | Beschreibung |
|----------|---------|--------------|
| `id` | — | **Pflicht** – Experten-ID |
| `template` | `full` | `full`, `card`, `compact` |
| `show_contact` | `true` | Kontaktbereich anzeigen |
| `tabs` | `true` | Tab-Navigation anzeigen |

---

### Template-Variablen (in Views)

Alle Views erhalten `$expert` (Row-Array aus `getById()`) und `$meta` (Array aus `getAllMeta()`):

```php
$expert['id']              // int
$expert['first_name']      // string
$expert['last_name']       // string
$expert['job_title']       // string
$expert['status']          // 'active'|'inactive'

$meta['availability_grade']     // '0'–'100'
$meta['available_from']         // 'YYYY-MM-DD' oder leer
$meta['homepage_url']           // URL
$meta['badge_top10']            // '1' oder ''
$meta['tech_expertise']         // JSON-Array
$meta['career_stations']        // JSON-Array
```

Vollständige Meta-Referenz: [META-FIELDS.md](META-FIELDS.md)

---

## AJAX-Endpunkte

Alle Endpunkte sind POST-only und erfordern `csrf_token`.

| Action-Key | Berechtigung | Beschreibung |
|-----------|-------------|--------------|
| `expert_save` | Admin | Experte erstellen/aktualisieren |
| `expert_delete` | Admin | Experten löschen |
| `expert_meta_save` | Admin + Member (eigenes Profil) | Meta-Felder speichern |
| `expert_skill_add` | Admin | Skill hinzufügen |
| `expert_skill_delete` | Admin | Skill entfernen |
| `expert_availability` | Member (eigenes Profil) | Verfügbarkeit ändern |

**Beispiel-Antwortformat:**
```json
{ "success": true, "data": { "id": 42 } }
{ "success": false, "error": "Sicherheitscheck fehlgeschlagen" }
```

---

## Fehler-Codes

| Code | Bedeutung |
|------|-----------|
| `csrf_failed` | CSRF-Token ungültig |
| `not_found` | Experte nicht gefunden |
| `not_authorized` | Keine Berechtigung |
| `validation_error` | Pflichtfeld fehlt |
| `db_error` | Datenbank-Fehler |
