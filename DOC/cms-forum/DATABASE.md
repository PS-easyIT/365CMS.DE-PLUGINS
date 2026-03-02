# CMS Forum – Datenbank-Dokumentation

> **DB-Version:** 1.0.0  
> **Prefix:** `cmsforum_` (über `$db->prefix()`)

---

## Tabellen-Übersicht

| Tabelle | Beschreibung | Zeilen (initial) |
|---------|-------------|-------------------|
| `cmsforum_config` | Plugin-Einstellungen (Key/Value) | ~21 |
| `cmsforum_categories` | Foren-Kategorien | 1 (Seed) |
| `cmsforum_forums` | Einzelne Foren | 2 (Seed) |
| `cmsforum_threads` | Diskussionsthemen | 0 |
| `cmsforum_posts` | Einzelne Beiträge | 0 |
| `cmsforum_user_meta` | Forum-spezifische Benutzerdaten | 0 |
| `cmsforum_ranks` | Rang-Definitionen | 6 (Seed) |
| `cmsforum_permissions` | Foren-Berechtigungen pro Gruppe | 0 |
| `cmsforum_subscriptions` | Thread-Abonnements | 0 |
| `cmsforum_attachments` | Dateianhänge | 0 |
| `cmsforum_polls` | Umfragen | 0 |
| `cmsforum_poll_options` | Umfrage-Optionen | 0 |
| `cmsforum_poll_votes` | Abgegebene Stimmen | 0 |
| `cmsforum_likes` | Beitrags-Likes | 0 |
| `cmsforum_reports` | Meldungen | 0 |
| `cmsforum_mod_log` | Moderations-Protokoll | 0 |
| `cmsforum_read_tracking` | Gelesen-Status pro Thread/User | 0 |

---

## Tabellen-Definitionen

### cmsforum_config

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | INT UNSIGNED AUTO_INCREMENT | PK |
| `config_key` | VARCHAR(100) UNIQUE | Einstellungsschlüssel |
| `config_value` | TEXT | Wert |

---

### cmsforum_categories

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | INT UNSIGNED AUTO_INCREMENT | PK |
| `name` | VARCHAR(255) | Kategoriename |
| `slug` | VARCHAR(255) UNIQUE | URL-Slug |
| `description` | TEXT NULL | Beschreibung |
| `sort_order` | INT DEFAULT 0 | Sortierung |
| `is_active` | TINYINT(1) DEFAULT 1 | Aktiv/Inaktiv |
| `created_at` | TIMESTAMP | Erstelldatum |

---

### cmsforum_forums

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | INT UNSIGNED AUTO_INCREMENT | PK |
| `category_id` | INT UNSIGNED | FK → categories.id |
| `parent_id` | INT UNSIGNED NULL | Unterforum (selbstreferenzierend) |
| `name` | VARCHAR(255) | Forumsname |
| `slug` | VARCHAR(255) UNIQUE | URL-Slug |
| `description` | TEXT NULL | Beschreibung |
| `icon` | VARCHAR(50) DEFAULT 'forum' | SVG-Icon ID |
| `sort_order` | INT DEFAULT 0 | Sortierung |
| `is_active` | TINYINT(1) DEFAULT 1 | Aktiv/Inaktiv |
| `thread_count` | INT UNSIGNED DEFAULT 0 | Gecacht: Anzahl Threads |
| `post_count` | INT UNSIGNED DEFAULT 0 | Gecacht: Anzahl Posts |
| `last_thread_id` | INT UNSIGNED NULL | Letzter Thread |
| `last_post_at` | TIMESTAMP NULL | Zeitpunkt letzter Post |
| `created_at` | TIMESTAMP | Erstelldatum |

**Indizes:** `idx_category` (category_id), `idx_parent` (parent_id), `idx_slug` (slug)

---

### cmsforum_threads

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | INT UNSIGNED AUTO_INCREMENT | PK |
| `forum_id` | INT UNSIGNED | FK → forums.id |
| `user_id` | INT UNSIGNED | Ersteller |
| `title` | VARCHAR(255) | Thread-Titel |
| `slug` | VARCHAR(255) | URL-Slug |
| `status` | ENUM('open','closed','deleted') DEFAULT 'open' | Status |
| `type` | ENUM('normal','sticky','announcement') DEFAULT 'normal' | Typ |
| `is_pinned` | TINYINT(1) DEFAULT 0 | Angepinnt |
| `view_count` | INT UNSIGNED DEFAULT 0 | Aufrufe |
| `reply_count` | INT UNSIGNED DEFAULT 0 | Gecacht: Antworten |
| `last_post_id` | INT UNSIGNED NULL | Letzter Beitrag |
| `last_post_user_id` | INT UNSIGNED NULL | Letzter Autor |
| `last_post_at` | TIMESTAMP NULL | Zeitpunkt letzter Post |
| `created_at` | TIMESTAMP | Erstelldatum |
| `updated_at` | TIMESTAMP ON UPDATE | Aktualisierungsdatum |

**Indizes:** `idx_forum` (forum_id), `idx_user` (user_id), `idx_status` (status), `idx_slug` (slug), `idx_pinned_last` (is_pinned DESC, last_post_at DESC)

---

### cmsforum_posts

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | INT UNSIGNED AUTO_INCREMENT | PK |
| `thread_id` | INT UNSIGNED | FK → threads.id |
| `user_id` | INT UNSIGNED | Autor |
| `content` | MEDIUMTEXT | BBCode-Inhalt |
| `is_first_post` | TINYINT(1) DEFAULT 0 | Erster Beitrag im Thread |
| `is_approved` | TINYINT(1) DEFAULT 1 | Freigegeben |
| `is_deleted` | TINYINT(1) DEFAULT 0 | Soft-Delete |
| `like_count` | INT UNSIGNED DEFAULT 0 | Gecacht: Likes |
| `edit_count` | INT UNSIGNED DEFAULT 0 | Bearbeitungszähler |
| `edited_by` | INT UNSIGNED NULL | Letzter Bearbeiter |
| `edited_at` | TIMESTAMP NULL | Letzte Bearbeitung |
| `ip_address` | VARCHAR(45) NULL | IP-Adresse |
| `created_at` | TIMESTAMP | Erstelldatum |

**Indizes:** `idx_thread` (thread_id), `idx_user` (user_id), `idx_approved` (is_approved), `idx_deleted` (is_deleted)

---

### cmsforum_user_meta

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | INT UNSIGNED AUTO_INCREMENT | PK |
| `user_id` | INT UNSIGNED UNIQUE | FK → users.id |
| `rank_id` | INT UNSIGNED NULL | Aktueller Rang |
| `post_count` | INT UNSIGNED DEFAULT 0 | Gecacht: Gesamtbeiträge |
| `thread_count` | INT UNSIGNED DEFAULT 0 | Gecacht: Erstellte Threads |
| `likes_received` | INT UNSIGNED DEFAULT 0 | Gecacht: Erhaltene Likes |
| `signature` | VARCHAR(500) NULL | Signatur |
| `location` | VARCHAR(100) NULL | Standort |
| `website` | VARCHAR(255) NULL | Webseite |
| `is_banned` | TINYINT(1) DEFAULT 0 | Gesperrt |
| `ban_reason` | VARCHAR(255) NULL | Sperrgrund |
| `ban_expires` | TIMESTAMP NULL | Sperr-Ablauf |
| `last_post_at` | TIMESTAMP NULL | Letzter Beitrag |
| `notify_reply` | TINYINT(1) DEFAULT 1 | Benachrichtigung bei Antwort |
| `notify_quote` | TINYINT(1) DEFAULT 1 | Benachrichtigung bei Zitat |
| `notify_mention` | TINYINT(1) DEFAULT 1 | Benachrichtigung bei Erwähnung |
| `created_at` | TIMESTAMP | Erstelldatum |
| `updated_at` | TIMESTAMP ON UPDATE | Aktualisierungsdatum |

---

### cmsforum_ranks

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | INT UNSIGNED AUTO_INCREMENT | PK |
| `name` | VARCHAR(100) | Rangname |
| `min_posts` | INT UNSIGNED DEFAULT 0 UNIQUE | Mindest-Beiträge |
| `color` | VARCHAR(7) DEFAULT '#64748b' | Rang-Farbe (Hex) |
| `icon` | VARCHAR(50) NULL | Optional: Icon |
| `is_special` | TINYINT(1) DEFAULT 0 | Spezieller Rang (manuell) |
| `sort_order` | INT DEFAULT 0 | Sortierung |

**Seed-Daten:** Neuling (0), Mitglied (10), Aktives Mitglied (50), Experte (200), Meister (500), Veteran (1000)

---

### cmsforum_permissions

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | INT UNSIGNED AUTO_INCREMENT | PK |
| `forum_id` | INT UNSIGNED | FK → forums.id |
| `group_name` | VARCHAR(50) | Benutzergruppe (z. B. 'guest', 'member', 'moderator', 'admin') |
| `can_view` | TINYINT(1) DEFAULT 1 | Forum sehen |
| `can_read` | TINYINT(1) DEFAULT 1 | Threads lesen |
| `can_post` | TINYINT(1) DEFAULT 0 | Antworten |
| `can_create_thread` | TINYINT(1) DEFAULT 0 | Thema erstellen |
| `can_edit_own` | TINYINT(1) DEFAULT 0 | Eigene bearbeiten |
| `can_delete_own` | TINYINT(1) DEFAULT 0 | Eigene löschen |
| `can_upload` | TINYINT(1) DEFAULT 0 | Upload |
| `can_vote` | TINYINT(1) DEFAULT 0 | Abstimmen |

**Unique:** `(forum_id, group_name)`

---

### cmsforum_subscriptions

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | INT UNSIGNED AUTO_INCREMENT | PK |
| `user_id` | INT UNSIGNED | FK → users.id |
| `thread_id` | INT UNSIGNED | FK → threads.id |
| `created_at` | TIMESTAMP | Erstelldatum |

**Unique:** `(user_id, thread_id)`

---

### cmsforum_attachments

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | INT UNSIGNED AUTO_INCREMENT | PK |
| `post_id` | INT UNSIGNED | FK → posts.id |
| `user_id` | INT UNSIGNED | Uploader |
| `filename` | VARCHAR(255) | Originaler Dateiname |
| `filepath` | VARCHAR(500) | Server-Pfad |
| `filesize` | INT UNSIGNED DEFAULT 0 | Größe in Bytes |
| `mime_type` | VARCHAR(100) | MIME-Typ |
| `download_count` | INT UNSIGNED DEFAULT 0 | Downloads |
| `created_at` | TIMESTAMP | Upload-Datum |

---

### cmsforum_polls

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | INT UNSIGNED AUTO_INCREMENT | PK |
| `thread_id` | INT UNSIGNED UNIQUE | FK → threads.id (1:1) |
| `question` | VARCHAR(255) | Frage |
| `is_multi_choice` | TINYINT(1) DEFAULT 0 | Mehrfachauswahl |
| `total_votes` | INT UNSIGNED DEFAULT 0 | Gecacht: Gesamtstimmen |
| `created_at` | TIMESTAMP | Erstelldatum |

---

### cmsforum_poll_options

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | INT UNSIGNED AUTO_INCREMENT | PK |
| `poll_id` | INT UNSIGNED | FK → polls.id |
| `option_text` | VARCHAR(255) | Optionstext |
| `vote_count` | INT UNSIGNED DEFAULT 0 | Stimmen |
| `sort_order` | INT DEFAULT 0 | Sortierung |

---

### cmsforum_poll_votes

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | INT UNSIGNED AUTO_INCREMENT | PK |
| `poll_id` | INT UNSIGNED | FK → polls.id |
| `option_id` | INT UNSIGNED | FK → poll_options.id |
| `user_id` | INT UNSIGNED | Abstimmer |
| `created_at` | TIMESTAMP | Zeitpunkt |

**Unique:** `(poll_id, user_id, option_id)`

---

### cmsforum_likes

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | INT UNSIGNED AUTO_INCREMENT | PK |
| `post_id` | INT UNSIGNED | FK → posts.id |
| `user_id` | INT UNSIGNED | Liker |
| `created_at` | TIMESTAMP | Zeitpunkt |

**Unique:** `(post_id, user_id)`

---

### cmsforum_reports

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | INT UNSIGNED AUTO_INCREMENT | PK |
| `post_id` | INT UNSIGNED | Gemeldeter Beitrag |
| `reporter_id` | INT UNSIGNED | Meldender Benutzer |
| `reason` | VARCHAR(50) | Grund (spam, offensive, off_topic, duplicate, other) |
| `detail` | TEXT NULL | Details |
| `status` | ENUM('open','resolved','dismissed') DEFAULT 'open' | Status |
| `resolved_by` | INT UNSIGNED NULL | Bearbeiter |
| `resolved_at` | TIMESTAMP NULL | Erledigt am |
| `created_at` | TIMESTAMP | Erstelldatum |

**Indizes:** `idx_post` (post_id), `idx_status` (status)

---

### cmsforum_mod_log

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | INT UNSIGNED AUTO_INCREMENT | PK |
| `user_id` | INT UNSIGNED | Moderator |
| `action` | VARCHAR(50) | Aktion (z. B. 'lock_thread') |
| `target_type` | VARCHAR(20) | Zieltyp ('thread', 'post', 'user') |
| `target_id` | INT UNSIGNED | Ziel-ID |
| `detail` | TEXT NULL | Details |
| `created_at` | TIMESTAMP | Zeitpunkt |

**Indizes:** `idx_user` (user_id), `idx_target` (target_type, target_id)

---

### cmsforum_read_tracking

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | INT UNSIGNED AUTO_INCREMENT | PK |
| `user_id` | INT UNSIGNED | Benutzer |
| `thread_id` | INT UNSIGNED | Thread |
| `last_read_at` | TIMESTAMP | Zuletzt gelesen |

**Unique:** `(user_id, thread_id)`

---

## Relationen (Entity-Relationship)

```
categories 1──n forums
forums     1──n threads
forums     1──n forums (parent_id, Unterforen)
threads    1──n posts
threads    1──1 polls
polls      1──n poll_options
polls      1──n poll_votes
posts      1──n attachments
posts      1──n likes
posts      1──n reports
threads    1──n subscriptions
threads    1──n read_tracking
users      1──1 user_meta
```
