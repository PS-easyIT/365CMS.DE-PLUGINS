# CMS Events Manager Plugin

**Version:** 1.0.1  
**Requires:** 365CMS 2.0+

## Description

The CMS Events Manager plugin manages events with calendar view and detail pages. This is a required core plugin for 365CMS.

## Features

- ✅ Event management with date/time support
- ✅ Custom database tables with proper relationships
- ✅ Admin interface for managing events
- ✅ Frontend display with card grid layout
- ✅ Calendar view for events
- ✅ Detail pages for individual events
- ✅ Speaker assignments (supports both speaker and expert profiles)
- ✅ Online and physical event support
- ✅ Meta data support
- ✅ Shortcode support: `[cms_events]`
- ✅ Inline-freier Admin-/Member-Workflow für Modale, Bestätigungen, Formular-Toggles und Kalendernavigation

## Database Tables

### cms_events
Main table storing event information with fields:
- id, title, description, event_date, event_time
- end_date, end_time, location, address, category
- capacity, registration_url, image_url
- is_online, online_url, status, created_at, updated_at

### cms_event_speakers
Relationship table linking events to speakers/experts:
- id, event_id, speaker_id, speaker_type
- role, presentation_title, created_at

### cms_event_meta
Additional metadata for events:
- id, event_id, meta_key, meta_value, created_at

## Usage

### Admin Interface
Navigate to `/admin/events` to manage events.

### Frontend Display
- List upcoming events: `/events`
- Calendar view: `/events/calendar`
- View event detail: `/events/{id}`
- Shortcode: `[cms_events]` - Displays upcoming events in a grid

### Programmatic Access

```php
// Get upcoming events
$db = CMS\Database::instance();
$stmt = $db->prepare("
    SELECT * FROM {$db->prefix()}events 
    WHERE status = ? AND event_date >= CURDATE()
    ORDER BY event_date ASC
");
$stmt->execute(['published']);
$events = $stmt->fetchAll();

// Get event by ID
$stmt = $db->prepare("SELECT * FROM {$db->prefix()}events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

// Get speakers for an event
$stmt = $db->prepare("
    SELECT * FROM {$db->prefix()}event_speakers
    WHERE event_id = ?
");
$stmt->execute([$event_id]);
$speakers = $stmt->fetchAll();
```

## Hooks

### Actions
- `event_created` - Fired when a new event is created
- `event_speaker_assigned` - Fired when a speaker is assigned to an event

### Filters
- `event_card_content` - Modify event card HTML output

## Template Files

Templates can be added to the `templates/` directory:
- `archive-event.php` - Event list view
- `single-event.php` - Event detail view
- `event-card.php` - Card component
- `event-calendar.php` - Calendar view

## Event Types

The plugin supports both physical and online events:
- Physical events: Set location and address fields
- Online events: Set `is_online = TRUE` and provide `online_url`
- Hybrid events: Set both physical and online details

## Installation

The plugin is automatically activated during 365CMS setup. Database tables are created on first activation.

## License

Part of 365CMS Core - All Rights Reserved
