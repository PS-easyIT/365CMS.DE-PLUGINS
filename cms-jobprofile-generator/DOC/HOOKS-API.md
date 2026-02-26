# Hooks-API – CMS Job Profile Generator

> Das Plugin registriert sowohl **eigene Hooks** (die es selbst auslöst) als auch **CMS-Framework-Hooks** (auf die es reagiert).  
> Alle Hooks verwenden `CMS\Hooks::addAction()` / `CMS\Hooks::addFilter()`.

---

## Inhaltsverzeichnis

1. [Vom Plugin abgehörte CMS-Hooks](#vom-plugin-abgehörte-cms-hooks)
2. [Vom Plugin ausgelöste Actions](#vom-plugin-ausgelöste-actions)
3. [Vom Plugin bereitgestellte Filter](#vom-plugin-bereitgestellte-filter)
4. [Code-Beispiele](#code-beispiele)

---

## Vom Plugin abgehörte CMS-Hooks

Diese Core-Hooks werden vom Plugin registriert und reagieren auf CMS-Ereignisse:

| Hook | Callback | Priorität | Beschreibung |
|---|---|---|---|
| `cms_init` | `CMS_JobProfileGenerator::init_plugin()` | 10 | DB-Versionsprüfung (`maybe_install`) |
| `plugin_activated` | `CMS_JobProfileGenerator::on_activation()` | 10 | DB-Tabellen anlegen bei Aktivierung |
| `plugin_uninstalled` | `CMS_JobProfileGenerator::on_uninstall()` | 10 | Alle DB-Tabellen + Abo-Spalten entfernen |
| `cms_admin_menu` | `CMS_JPG_Admin_Menu::register()` | 10 | 8 Admin-Menüpunkte registrieren |
| `head` | `CMS_JobProfileGenerator::enqueue_styles()` | 20 | `jobprofile-admin.css` ausgeben |
| `body_end` | `CMS_JobProfileGenerator::enqueue_scripts()` | 20 | `jobprofile-admin.js` ausgeben |
| `company_deleted` | `CMS_JobProfileGenerator::on_company_deleted()` | 10 | Publizierte Profile der gelöschten Firma auf `draft` + `company_id=NULL` setzen |
| `member_dashboard_init` | `CMS_JPG_MemberController::register_via_plugin_dashboard()` | 10 | Member-Menüpunkte registrieren |
| `cms_member_data_export_requested` | `CMS_JobProfileGenerator::handle_data_export()` | 10 | DSGVO Art. 20 – Profil- und Bewerbungsdaten exportieren |
| `cms_member_account_deletion_requested` | `CMS_JobProfileGenerator::handle_account_deletion()` | 10 | DSGVO Art. 17 – Profile trashен, CV-Dateien löschen, PII anonymisieren |

### Registrierung im Code

```php
// job-profile-generator.php – private function init_hooks()
CMS\Hooks::addAction('cms_init',         [$this, 'init_plugin'],    10);
CMS\Hooks::addAction('plugin_activated', [$this, 'on_activation'],  10);
CMS\Hooks::addAction('plugin_uninstalled', [$this, 'on_uninstall'], 10);
CMS\Hooks::addAction('company_deleted',  [$this, 'on_company_deleted'], 10);
CMS\Hooks::addAction('cms_admin_menu',   [CMS_JPG_Admin_Menu::class, 'register'], 10);
CMS\Hooks::addAction('head',             [$this, 'enqueue_styles'], 20);
CMS\Hooks::addAction('body_end',         [$this, 'enqueue_scripts'], 20);
CMS\Hooks::addAction('cms_member_data_export_requested',    [$this, 'handle_data_export'], 10);
CMS\Hooks::addAction('cms_member_account_deletion_requested', [$this, 'handle_account_deletion'], 10);
```

---

## Vom Plugin ausgelöste Actions

Das Plugin feuert eigene Actions, auf die Dritt-Plugins / Custom-Code reagieren können:

### `jpg_profile_saved`

Wird ausgelöst nach dem erfolgreichen Speichern eines Profils.

```php
CMS\Hooks::doAction('jpg_profile_saved', int $profile_id, array $data, bool $is_new);
```

| Parameter | Typ | Beschreibung |
|---|---|---|
| `$profile_id` | `int` | ID des gespeicherten Profils |
| `$data` | `array` | Alle übergebenen Formulardaten |
| `$is_new` | `bool` | `true` bei Ersterstellung, `false` bei Update |

### `jpg_profile_published`

Wird ausgelöst wenn ein Profil auf Status `published` gesetzt wird.

```php
CMS\Hooks::doAction('jpg_profile_published', int $profile_id);
```

### `jpg_profile_deleted`

Wird ausgelöst nach dem Löschen eines Profils.

```php
CMS\Hooks::doAction('jpg_profile_deleted', int $profile_id);
```

### `jpg_before_export`

Wird ausgelöst bevor ein Profil als HTML oder JSON exportiert wird.

```php
CMS\Hooks::doAction('jpg_before_export', int $profile_id, string $format);
// $format: 'html' | 'json'
```

---

## Vom Plugin bereitgestellte Filter

### `jpg_profile_data`

Erlaubt es, die Profildaten vor der Ausgabe zu modifizieren.

```php
// Signatur:
$data = CMS\Hooks::applyFilters('jpg_profile_data', array $data, int $profile_id);
```

**Verwendungsbeispiel:**

```php
// In einem eigenen Plugin oder in functions.php des Themes:
CMS\Hooks::addFilter('jpg_profile_data', function(array $data, int $id): array {
    // Standort-Normalisierung
    $data['location'] = strtoupper($data['location'] ?? '');
    return $data;
}, 10);
```

### `jpg_profile_html`

Erlaubt die Manipulation des generierten HTML vor der Ausgabe (Frontend).

```php
$html = CMS\Hooks::applyFilters('jpg_profile_html', string $html, int $profile_id);
```

### `jpg_export_json`

Erlaubt Anpassungen am JSON-Export-Array.

```php
$json = CMS\Hooks::applyFilters('jpg_export_json', array $exportData, int $profile_id);
```

### `jpg_task_list`

Erlaubt die Manipulation der Aufgabenliste vor der Speicherung.

```php
$tasks = CMS\Hooks::applyFilters('jpg_task_list', array $tasks, int $profile_id);
```

---

## Code-Beispiele

### Auf Profilpublizierung reagieren

```php
// Beispiel: Slack-Notification bei Veröffentlichung
CMS\Hooks::addAction('jpg_profile_published', function(int $profileId): void {
    $profile = CMS_JPG_Profiles::instance()->get($profileId);
    if (!$profile) return;

    // Externe Benachrichtigung versenden
    $message = sprintf('Neues Stellenprofil veröffentlicht: "%s"', $profile->title);
    // webhook_send($message); // eigene Implementation
}, 10);
```

### Profildaten vor Ausgabe transformieren

```php
CMS\Hooks::addFilter('jpg_profile_data', function(array $data, int $id): array {
    // Gehaltsspanne formatiert anhängen
    if (!empty($data['salary_min']) && !empty($data['salary_max'])) {
        $data['salary_range_formatted'] = sprintf(
            '%s – %s %s',
            number_format((float)$data['salary_min'], 0, ',', '.'),
            number_format((float)$data['salary_max'], 0, ',', '.'),
            $data['salary_currency'] ?? 'EUR'
        );
    }
    return $data;
}, 10);
```

### Eigene Validierung vor Export

```php
CMS\Hooks::addAction('jpg_before_export', function(int $profileId, string $format): void {
    if ($format !== 'pdf') return;
    $profile = CMS_JPG_Profiles::instance()->get($profileId);
    if (empty($profile->description)) {
        // Export blockieren: Exception wird vom Export-Handler gefangen
        throw new \RuntimeException('Profil hat keine Beschreibung – PDF-Export abgebrochen.');
    }
}, 10);
```

---

## Verwandte Dokumentation

- [DATABASE.md](DATABASE.md) – Datenbankschema der gespeicherten Profildaten
- [SECURITY.md](SECURITY.md) – Nonces, Sanitierung und Escaping
- [FRONTEND-ROUTING.md](FRONTEND-ROUTING.md) – Öffentliche Profil-URLs
