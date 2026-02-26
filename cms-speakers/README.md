# CMS Speakers Directory Plugin

**Version:** 1.0.0  
**Requires:** 365CMS 2.0+

## Description

The CMS Speakers Directory plugin manages speaker profiles with card views and detail pages. This is a required core plugin for 365CMS.

## Features

- ✅ Speaker profile management
- ✅ Custom database tables with proper relationships
- ✅ Admin interface for managing speakers
- ✅ Frontend display with card grid layout
- ✅ Detail pages for individual speakers
- ✅ Speaking topics management
- ✅ Past presentations tracking
- ✅ Expert profile linking (speakers can be linked to expert profiles)
- ✅ Availability tracking
- ✅ Meta data support
- ✅ Shortcode support: `[cms_speakers]`

## Database Tables

### cms_speakers
Main table storing speaker profiles with fields:
- id, name, email, title, biography
- photo_url, company, website, linkedin, twitter
- expert_id (link to expert profile), speaking_topics, languages
- fee_range, availability, status, created_at, updated_at

### cms_speaker_topics
Speaker speaking topics with expertise levels:
- id, speaker_id, topic, expertise_level, created_at

### cms_speaker_presentations
Past speaking engagements:
- id, speaker_id, title, event_name, presentation_date
- video_url, slides_url, description, created_at

### cms_speaker_meta
Additional metadata for speakers:
- id, speaker_id, meta_key, meta_value, created_at

## Usage

### Admin Interface
Navigate to `/admin/speakers` to manage speaker profiles.

### Frontend Display
- List all speakers: `/speakers`
- View speaker detail: `/speakers/{id}`
- Shortcode: `[cms_speakers]` - Displays all speakers in a grid

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

// Get past presentations
$stmt = $db->prepare("
    SELECT * FROM {$db->prefix()}speaker_presentations 
    WHERE speaker_id = ? 
    ORDER BY presentation_date DESC
");
$stmt->execute([$speaker_id]);
$presentations = $stmt->fetchAll();
```

## Hooks

### Actions
- `speaker_created` - Fired when a new speaker profile is created
- `speaker_presentation_added` - Fired when a presentation is added

### Filters
- `speaker_card_content` - Modify speaker card HTML output

## Template Files

Templates can be added to the `templates/` directory:
- `archive-speaker.php` - Speaker list view
- `single-speaker.php` - Speaker detail view
- `speaker-card.php` - Card component

## Expert Integration

Speakers can be linked to expert profiles via the `expert_id` field. This allows:
- Displaying expert's technical expertise on speaker profile
- Cross-referencing between expert and speaker directories
- Unified profile management for individuals who are both experts and speakers

## Availability Status

Speakers have an availability field with the following values:
- `available` - Currently available for speaking engagements
- `limited` - Limited availability
- `unavailable` - Not currently available

## Installation

The plugin is automatically activated during 365CMS setup. Database tables are created on first activation.

## License

Part of 365CMS Core - All Rights Reserved
