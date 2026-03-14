# CMS Promos – Datenbank

## Tabellen

### `cms_promo_placements`
- `id`
- `name`
- `slug`
- `description`
- `status`
- `max_items`
- `created_at`, `updated_at`

### `cms_promos`
- `id`
- `placement_id`
- `title`, `slug`, `teaser`
- `content_html`
- `target_url`
- `button_label`
- `image_url`
- `status`
- `start_at`, `end_at`
- `priority`
- `is_featured`
- `impression_count`, `click_count`
- `created_at`, `updated_at`

### `cms_promo_settings`
- `id`
- `setting_key`
- `setting_value`
