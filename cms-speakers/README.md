# CMS Speakers Directory Plugin

**Version:** 3.0.15
**Requires:** 365CMS 3.0+ / PHP 8.4+

## Description

The CMS Speakers Directory plugin manages speaker profiles with card views and detail pages. This is a required core plugin for 365CMS.

## Features

- ✅ Speaker profile management
- ✅ Custom database tables with proper relationships
- ✅ Admin interface for managing speakers
- ✅ Frontend display with event-style responsive card grid layout
- ✅ PHINIT-abgestimmtes Publicsite-Design für Archiv, Filter, Cards und Detailseite
- ✅ Public-Filterleiste mit primärem „Suchen“-Button und maximal 1160px Contentbreite
- ✅ Detail pages for individual speakers mit maximal 1160px Contentbreite, Responsive Layout und Dark Mode
- ✅ Speaking topics management
- ✅ Past presentations tracking
- ✅ Expert profile linking (speakers can be linked to expert profiles)
- ✅ Availability tracking
- ✅ Meta data support
- ✅ Shortcode support: `[cms_speakers]`

## Database Tables

### cms_speakers
Main table storing speaker profiles with fields:
- id, user_id, first_name, last_name, title, gender
- company/company_id, email, phone, bio, short_bio, photo_url
- location fields, website and social URLs
- languages/formats/skills/recognitions JSON fields
- travel radius, fee range, availability, status, badges and profile views

### cms_speaker_topics
Speaker topics:
- id, speaker_id, topic_name, topic_desc, sort_order, created_at

### cms_speaker_events
Past speaking engagements:
- id, speaker_id, event_title, event_type, event_date/event_date_end
- organizer_type, company_id, cms_event_id, topic, description
- video_url, slides_url, event_url, is_public, created_at

### Settings
Runtime settings are stored primarily in the 365CMS `SettingsService` group `cms-speakers`. The legacy `cms_speaker_plugin_settings` table remains as migration/fallback storage.

## Usage

### Admin Interface
Navigate to `/admin/speakers` to manage speaker profiles.

### Frontend Display
- List all speakers in the public card overview: `/speakers`
- View speaker detail: `/speakers/{id}`
- Shortcode: `[cms_speakers]` - Displays all speakers in a grid
- Profile zeigen Social-/Kontaktlinks inklusive E-Mail; die Detailseite enthält eine beschriftete Anfragebox.

### Programmatic Access

```php
// Get all active speakers
$db = CMS\Database::instance();
$stmt = $db->prepare("SELECT * FROM {$db->prefix()}speakers WHERE status = ?");
$stmt->execute(['active']);
$speakers = $stmt->fetchAll();

// Get speaker by ID
$stmt = $db->prepare("SELECT * FROM {$db->prefix()}speakers WHERE id = ?");
$stmt->execute([$speaker_id]);
$speaker = $stmt->fetch();

// Get speaker topics
$stmt = $db->prepare("SELECT * FROM {$db->prefix()}speaker_topics WHERE speaker_id = ?");
$stmt->execute([$speaker_id]);
$topics = $stmt->fetchAll();

// Get past speaking events
$stmt = $db->prepare("
    SELECT * FROM {$db->prefix()}speaker_events 
    WHERE speaker_id = ? 
    ORDER BY event_date DESC, created_at DESC
");
$stmt->execute([$speaker_id]);
$events = $stmt->fetchAll();
```

## Hooks

### Actions
- `speaker_created` - Fired when a new speaker profile is created: `(int $speaker_id, array $data)`
- `speaker_updated` - Fired when a speaker profile is updated: `(int $speaker_id, array $data)`
- `cms_speakers_activated` / `cms_speakers_deactivated` - Fired on plugin lifecycle events

### Filters
- `speaker_card_content` - Modify speaker card HTML output

## Template Files

Templates can be added to the `templates/` directory:
- `archive-speaker.php` - Speaker list view
- `single-speaker.php` - Speaker detail view
- `speaker-card.php` - Card component

## Cross-Plugin Integration

Speakers can be linked to companies via `company_id` and to CMS events through `cms_event_id` in speaker events. All cross-plugin queries are defensive and keep working when the optional target tables are not installed.

## Availability Status

Speakers have an availability field with the following values:
- `available` - Currently available for speaking engagements
- `limited` - Limited availability
- `booked` - Currently booked out

## Installation

The plugin is automatically activated during 365CMS setup. Database tables are created on first activation.

## License

Part of 365CMS Core - All Rights Reserved
