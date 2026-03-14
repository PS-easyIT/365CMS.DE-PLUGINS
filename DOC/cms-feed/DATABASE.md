# CMS Feed – Datenbankstruktur

> Alle Tabellen verwenden das Prefix `{prefix}` (z.B. `cms_`).  
> Engine: InnoDB, Charset: `utf8mb4_unicode_ci`

---

## Tabellen-Übersicht

| Tabelle | Zweck |
|---------|-------|
| `{prefix}feed_categories` | Bereiche / Kategorien für Feed-Gruppierung |
| `{prefix}feed_channels` | RSS-Feed-Quellen (Kanäle) |
| `{prefix}feed_items` | Gecachte Feed-Beiträge |
| `{prefix}feed_settings` | Key-Value-Einstellungen |
| `{prefix}feed_digests` | E-Mail-Digest-Konfigurationen |
| `{prefix}feed_member_subscriptions` | Persönliche Feed-Abos aus dem Memberbereich |
| `{prefix}feed_subscriptions` | Legacy-Kompatibilität für ältere Theme-Versionen |
| `{prefix}feed_fetch_queue` | Warteschlange für asynchrone Bulk-Abrufe |

---

## feed_categories

Thematische Bereiche, in die Feeds gruppiert werden (z.B. „Security", „Tech News").

| Spalte | Typ | Default | Beschreibung |
|--------|-----|---------|-------------|
| `id` | `INT UNSIGNED` AI PK | – | Primärschlüssel |
| `name` | `VARCHAR(150)` NOT NULL | – | Anzeigename |
| `slug` | `VARCHAR(150)` NOT NULL | – | URL-Slug (UNIQUE) |
| `description` | `TEXT` | NULL | Optionale Beschreibung |
| `icon` | `VARCHAR(10)` | `'📰'` | Emoji-Icon |
| `is_public` | `TINYINT(1)` NOT NULL | `1` | Öffentlich sichtbar |
| `sort_order` | `INT` | `0` | Reihenfolge |
| `layout` | `VARCHAR(30)` NOT NULL | `'grid'` | Darstellung: `grid`, `list`, `magazine` |
| `items_per_page` | `INT UNSIGNED` NOT NULL | `20` | Einträge pro Seite auf Public-Seite |
| `created_at` | `TIMESTAMP` | `CURRENT_TIMESTAMP` | Erstellzeitpunkt |
| `updated_at` | `TIMESTAMP` | auto-update | Letzte Änderung |

**Indizes:** `unique_slug (slug)`, `idx_public (is_public)`, `idx_sort (sort_order)`

---

## feed_channels

Einzelne RSS-/Atom-Feed-Quellen, jeweils einem Bereich zugeordnet.

| Spalte | Typ | Default | Beschreibung |
|--------|-----|---------|-------------|
| `id` | `INT UNSIGNED` AI PK | – | Primärschlüssel |
| `category_id` | `INT UNSIGNED` NOT NULL | – | FK → feed_categories.id |
| `name` | `VARCHAR(255)` NOT NULL | – | Kanalname |
| `feed_url` | `VARCHAR(500)` NOT NULL | – | RSS/Atom-Feed-URL |
| `site_url` | `VARCHAR(500)` | NULL | Website-URL der Quelle |
| `description` | `TEXT` | NULL | Kanalbeschreibung |
| `icon_url` | `VARCHAR(500)` | NULL | Favicon/Icon-URL (auto-detected) |
| `is_active` | `TINYINT(1)` NOT NULL | `1` | Kanal aktiv |
| `fetch_interval` | `INT UNSIGNED` NOT NULL | `60` | Abrufintervall in Minuten |
| `max_items` | `INT UNSIGNED` NOT NULL | `50` | Max. importierte Beiträge pro Abruf |
| `last_fetched_at` | `TIMESTAMP` | NULL | Zeitpunkt des letzten Abrufs |
| `last_error` | `TEXT` | NULL | Letzter Fehlermeldung (NULL = OK) |
| `item_count` | `INT UNSIGNED` NOT NULL | `0` | Aktuelle Anzahl gespeicherter Beiträge |
| `created_at` | `TIMESTAMP` | `CURRENT_TIMESTAMP` | Erstellzeitpunkt |
| `updated_at` | `TIMESTAMP` | auto-update | Letzte Änderung |

**Indizes:** `idx_category (category_id)`, `idx_active (is_active)`, `idx_fetch (last_fetched_at)`

---

## feed_items

Gecachte Artikel aus den RSS-Feeds.

| Spalte | Typ | Default | Beschreibung |
|--------|-----|---------|-------------|
| `id` | `INT UNSIGNED` AI PK | – | Primärschlüssel |
| `channel_id` | `INT UNSIGNED` NOT NULL | – | FK → feed_channels.id |
| `category_id` | `INT UNSIGNED` NOT NULL | – | FK → feed_categories.id (Denormalisierung für schnelle Queries) |
| `guid` | `VARCHAR(500)` NOT NULL | – | Eindeutige Feed-Item-ID |
| `title` | `VARCHAR(500)` NOT NULL | – | Beitragstitel |
| `link` | `VARCHAR(500)` NOT NULL | – | Original-URL des Artikels |
| `description` | `TEXT` | NULL | Zusammenfassung / Auszug |
| `content` | `LONGTEXT` | NULL | Vollständiger Inhalt (wenn verfügbar) |
| `author` | `VARCHAR(255)` | NULL | Autorenname |
| `image_url` | `VARCHAR(500)` | NULL | Beitragsbild-URL |
| `pub_date` | `DATETIME` NOT NULL | – | Veröffentlichungsdatum |
| `is_featured` | `TINYINT(1)` NOT NULL | `0` | Hervorgehoben |
| `is_hidden` | `TINYINT(1)` NOT NULL | `0` | Ausgeblendet (nur im Admin sichtbar) |
| `created_at` | `TIMESTAMP` | `CURRENT_TIMESTAMP` | Importzeitpunkt |

**Indizes:** `unique_guid_channel (guid(191), channel_id)`, `idx_channel`, `idx_category`, `idx_pub_date`, `idx_featured`, `idx_hidden`

---

## feed_settings

Key-Value-Tabelle für globale Plugin-Einstellungen.

| Spalte | Typ | Default | Beschreibung |
|--------|-----|---------|-------------|
| `setting_key` | `VARCHAR(100)` PK | – | Einstellungsschlüssel |
| `setting_value` | `TEXT` | NULL | Einstellungswert |
| `updated_at` | `TIMESTAMP` | auto-update | Letzte Änderung |

### Standard-Schlüssel

| Key | Default | Beschreibung |
|-----|---------|-------------|
| `archive_title` | `Feed-Übersicht` | Seitentitel der Public-Seite |
| `archive_description` | `Aktuelle Nachrichten…` | Beschreibungstext |
| `archive_slug` | `feeds` | URL-Slug der Hauptseite |
| `per_page` | `20` | Einträge pro Seite |
| `color_primary` | `#0891b2` | Primärfarbe |
| `color_accent` | `#e0f2fe` | Akzentfarbe (hell) |
| `color_hdr_from` | `#0c4a6e` | Header-Gradient Start |
| `color_hdr_to` | `#0891b2` | Header-Gradient Ende |
| `color_hdr_title` | `#ffffff` | Header-Textfarbe |
| `color_card_bg` | `#ffffff` | Card-Hintergrund |
| `color_card_border` | `#e2e8f0` | Card-Rand |
| `border_radius` | `10` | Border-Radius in px |
| `grid_columns` | `auto` | Spalten: `auto`, `2`, `3`, `4` |
| `show_source` | `1` | Quellname anzeigen |
| `show_date` | `1` | Datum anzeigen |
| `show_image` | `1` | Beitragsbild anzeigen |
| `show_excerpt` | `1` | Zusammenfassung anzeigen |
| `excerpt_length` | `160` | Zeichenlänge der Zusammenfassung |
| `open_in_new_tab` | `1` | Links in neuem Tab öffnen |
| `digest_from_name` | `365 CMS Feed Digest` | Absendername für Digests |
| `digest_from_email` | _(leer)_ | Absender-E-Mail |
| `digest_subject` | `Dein Feed-Digest – {date}` | Betreff-Vorlage |
| `digest_max_items` | `20` | Max. Items pro Digest-Mail |

---

## feed_digests

Konfigurationen für automatische E-Mail-Zusammenfassungen.

| Spalte | Typ | Default | Beschreibung |
|--------|-----|---------|-------------|
| `id` | `INT UNSIGNED` AI PK | – | Primärschlüssel |
| `name` | `VARCHAR(150)` NOT NULL | – | Digest-Name |
| `email` | `VARCHAR(255)` NOT NULL | – | Empfänger-E-Mail |
| `category_ids` | `TEXT` NOT NULL | – | JSON-Array der Bereich-IDs |
| `frequency` | `TINYINT UNSIGNED` NOT NULL | `1` | Frequenz: 1=1×, 2=2×, 3=3×, 4=4× pro Tag |
| `is_active` | `TINYINT(1)` NOT NULL | `1` | Digest aktiv |
| `last_sent_at` | `TIMESTAMP` | NULL | Letzter Versandzeitpunkt |
| `created_at` | `TIMESTAMP` | `CURRENT_TIMESTAMP` | Erstellzeitpunkt |
| `updated_at` | `TIMESTAMP` | auto-update | Letzte Änderung |

**Indizes:** `idx_active (is_active)`, `idx_email (email)`

---

## feed_member_subscriptions

Persönliche Mail-Abos für eingeloggte Mitglieder. Ein Datensatz entspricht einem Benutzer mit eigener Feed-Auswahl und Versandregel.

| Spalte | Typ | Default | Beschreibung |
|--------|-----|---------|-------------|
| `id` | `INT UNSIGNED` AI PK | – | Primärschlüssel |
| `user_id` | `INT UNSIGNED` NOT NULL | – | Zugehöriger Member-User |
| `email` | `VARCHAR(255)` NOT NULL | – | Zieladresse für den Versand |
| `channel_ids` | `LONGTEXT` NOT NULL | – | JSON-Array der ausgewählten `feed_channels.id` |
| `frequency` | `VARCHAR(20)` NOT NULL | `'daily'` | `daily` oder `weekly` |
| `daily_mode` | `VARCHAR(20)` NOT NULL | `'09'` | `09`, `15`, `09_15` |
| `weekly_day` | `TINYINT UNSIGNED` NOT NULL | `1` | Wochentag `1=Montag` bis `7=Sonntag` |
| `weekly_time` | `VARCHAR(5)` NOT NULL | `'09'` | Wochen-Slot `09` oder `15` |
| `is_active` | `TINYINT(1)` NOT NULL | `1` | Versand aktiv/pausiert |
| `last_sent_at` | `DATETIME` | `NULL` | Letzter erfolgreich abgearbeiteter Versand-Slot |
| `created_at` | `TIMESTAMP` | `CURRENT_TIMESTAMP` | Erstellzeitpunkt |
| `updated_at` | `TIMESTAMP` | auto-update | Letzte Änderung |

**Indizes:** `unique_user (user_id)`, `idx_active (is_active)`, `idx_frequency (frequency)`

---

## feed_subscriptions

Legacy-Kompatibilitätstabelle für ältere `cms-phinit`-/Theme-Stände, die noch ein row-per-feed-Modell erwarten. Neue Implementierungen sollen `feed_member_subscriptions` verwenden.

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `user_id` | `INT UNSIGNED` | Member-ID |
| `channel_id` | `INT UNSIGNED` | Legacy-Kanal-ID für ältere Theme-Abfragen |
| `feed_id` | `INT UNSIGNED` | Einzelner abonnierter Kanal |
| `email` | `VARCHAR(255)` | Zieladresse |
| `frequency` | `VARCHAR(20)` | `daily` oder `weekly` |
| `daily_mode` | `VARCHAR(20)` | `09`, `15`, `09_15` |
| `weekly_day` | `TINYINT` | `1..7` |
| `weekly_time` | `VARCHAR(5)` | `09` oder `15` |
| `is_active` | `TINYINT(1)` | Aktiv/pausiert |
| `last_sent_at` | `DATETIME` | Letzter verarbeiteter Slot |

Die Tabelle wird vom Plugin automatisch mit dem neuen Abo-Modell synchronisiert und zusätzlich als Fallback gelesen, falls eine alte Theme-Datei noch darauf zugreift. Aus Kompatibilitätsgründen werden sowohl `channel_id` als auch `feed_id` gepflegt.

---

## feed_fetch_queue

Warteschlange für Bulk-Feed-Abrufe. Wird befüllt, wenn mehr als 5 Kanäle gleichzeitig abgerufen werden sollen. Die Verarbeitung erfolgt per Cron (max. 5 pro Durchlauf).

| Spalte | Typ | Default | Beschreibung |
|--------|-----|---------|-------------|
| `id` | `INT UNSIGNED` AI PK | – | Primärschlüssel |
| `channel_id` | `INT UNSIGNED` NOT NULL | – | Referenz auf `feed_channels.id` |
| `status` | `VARCHAR(20)` NOT NULL | `'pending'` | Status: `pending`, `processing`, `done`, `failed` |
| `error` | `TEXT` | NULL | Fehlermeldung (bei `failed`) |
| `created_at` | `TIMESTAMP` | `CURRENT_TIMESTAMP` | Einreihungszeitpunkt |
| `processed_at` | `TIMESTAMP` | NULL | Verarbeitungszeitpunkt |

**Indizes:** `idx_status (status)`, `idx_channel (channel_id)`, `idx_created (created_at)`

---

## Relationale Beziehungen

```
feed_categories 1 ─── N feed_channels
feed_categories 1 ─── N feed_items
feed_channels   1 ─── N feed_items
feed_channels   1 ─── N feed_fetch_queue
feed_digests.category_ids ──── N:M feed_categories (JSON)
feed_member_subscriptions.channel_ids ──── N:M feed_channels (JSON)
```

- Beim Löschen eines **Bereichs** werden auch alle zugehörigen **Kanäle**, **Beiträge** und **Queue-Einträge** kaskadierend gelöscht.
- Beim Löschen eines **Kanals** werden alle zugehörigen **Beiträge** und **Queue-Einträge** kaskadierend gelöscht.
- Der **Cleanup** löscht Beiträge älter als X Tage, behält aber Featured-Beiträge.
- Die **Queue** wird automatisch bereinigt: erledigte/fehlgeschlagene Tasks älter als 7 Tage werden per Cron entfernt.
