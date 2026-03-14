# CMS Newsletter – Datenbank

## Tabellen

### `cms_newsletter_subscribers`
- `id` – Primärschlüssel
- `email` – eindeutige E-Mail-Adresse
- `first_name`, `last_name` – optionale Personendaten
- `status` – `pending`, `active`, `unsubscribed`, `bounced`
- `source` – Herkunft (`admin`, `public`, Import)
- `segment_slug` – logisches Segment für Kampagnen
- `optin_token` – Token für Unsubscribe / Opt-In-Workflows
- `confirmed_at`, `last_sent_at` – Zeitstempel
- `created_at`, `updated_at`

### `cms_newsletter_templates`
- `id`
- `name`
- `subject`
- `content_html`
- `content_text`
- `status`
- `created_at`, `updated_at`

### `cms_newsletter_campaigns`
- `id`
- `template_id` – optionale Verknüpfung zum Template
- `name`, `subject`, `preview_text`
- `segment_slug`
- `status` – `draft`, `ready`, `scheduled`, `sent`
- `scheduled_at`, `sent_at`
- `recipient_count`
- `created_at`, `updated_at`

### `cms_newsletter_sends`
- `id`
- `campaign_id`
- `subscriber_id`
- `status`
- `opened_at`, `clicked_at`
- `last_error`
- `created_at`

### `cms_newsletter_settings`
- `id`
- `setting_key`
- `setting_value`
