# CMS Job Profile Generator – Hooks-Referenz

## Actions

### `job_profile_created`
```php
CMS\Hooks::doAction('job_profile_created', int $profile_id, int $user_id, array $data);
```

### `job_profile_updated`
```php
CMS\Hooks::doAction('job_profile_updated', int $profile_id, array $new_data, array $old_data);
```

### `job_profile_published`
Ausgelöst wenn eine Stelle auf `published` gesetzt wird.
```php
CMS\Hooks::doAction('job_profile_published', int $profile_id);
```

### `job_profile_archived`
```php
CMS\Hooks::doAction('job_profile_archived', int $profile_id);
```

### `job_workflow_approved`
Ausgelöst nach einer Genehmigung.
```php
CMS\Hooks::doAction('job_workflow_approved', int $profile_id, int $step_id, int $approver_id);
```

### `job_workflow_rejected`
Ausgelöst nach einer Ablehnung.
```php
CMS\Hooks::doAction('job_workflow_rejected', int $profile_id, int $step_id, int $approver_id, string $comment);
```

### `job_application_received`
Ausgelöst bei neuer Bewerbung.
```php
CMS\Hooks::doAction('job_application_received', int $application_id, int $profile_id, array $applicant_data);
```

### `job_application_status_changed`
```php
CMS\Hooks::doAction('job_application_status_changed', int $application_id, string $old_status, string $new_status);
```

---

## Filters

### `job_profile_card_content`
```php
CMS\Hooks::applyFilters('job_profile_card_content', string $html, array $profile): string;
```

### `job_listing_query_args`
```php
CMS\Hooks::applyFilters('job_listing_query_args', array $args): array;
// Default:
[
    'status'          => 'published',
    'show_in_listing' => true,
    'category_id'     => null,
    'company_id'      => null,
    'remote_policy'   => null,
    'limit'           => 20,
    'offset'          => 0,
    'orderby'         => 'published_at',
    'order'           => 'DESC',
]
```

### `job_workflow_approvers`
Dynamisches Hinzufügen von Genehmigern zu einem Schritt.
```php
CMS\Hooks::applyFilters('job_workflow_approvers', array $approver_ids, int $step_id, int $profile_id): array;
```

### `job_application_cv_filename`
Dateiname beim CV-Download.
```php
CMS\Hooks::applyFilters('job_application_cv_filename', string $filename, array $application): string;
```

---

## CMS-Core-Hooks

| Hook | Callback | Priorität |
|------|----------|-----------|
| `cms_init` | `CMS_JobProfile_Generator::init()` | 10 |
| `plugin_activated` | `CMS_JPG_Installer::install()` | 10 |
| `cms_admin_menu` | `CMS_JPG_Admin_Menu::register()` | 10 |
| `cms_member_dashboard` | `CMS_JPG_Member_Controller::render()` | 15 |
| `cms_frontend_route` | `CMS_JPG_Frontend::handle_route()` | 10 |
| `dsgvo_export_data` | `CMS_JobProfile_Generator::export_user_data()` | 10 |
| `dsgvo_delete_data` | `CMS_JobProfile_Generator::delete_user_data()` | 10 |
