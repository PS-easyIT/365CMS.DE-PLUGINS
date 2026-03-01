# CMS Booking – Datenbank-Schema

> **DB_VERSION:** 1  
> **Prefix:** `{cms_}` (konfigurierbar via `Database::getPrefix()`)

---

## Tabellen-Übersicht

| Tabelle | Beschreibung | FK |
|---|---|---|
| `booking_providers` | Buchungsanbieter (Personen/Firmen) | `user_id` → users |
| `booking_services` | Buchbare Leistungen je Anbieter | `provider_id` → booking_providers |
| `booking_availability` | Wochenplan + Datums-Overrides | `provider_id` → booking_providers |
| `bookings` | Buchungen (Kernentität) | `provider_id`, `service_id`, `user_id` |
| `booking_meta` | Key-Value-Metadaten zu Buchungen | `booking_id` → bookings |
| `booking_settings` | Globale Plugin-Einstellungen | – |

---

## booking_providers

| Spalte | Typ | Beschreibung |
|---|---|---|
| `id` | INT UNSIGNED PK AI | Primärschlüssel |
| `source_plugin` | VARCHAR(100) | Quell-Plugin-Slug (z. B. `cms-experts`) |
| `source_id` | INT UNSIGNED | ID im Quell-Plugin |
| `user_id` | INT UNSIGNED NULL | Verknüpfter CMS-Benutzer |
| `display_name` | VARCHAR(255) | Anzeigename |
| `slug` | VARCHAR(255) UNIQUE | URL-Slug |
| `email` | VARCHAR(255) NULL | Kontakt-E-Mail |
| `phone` | VARCHAR(50) NULL | Telefon |
| `description` | TEXT NULL | Beschreibung |
| `timezone` | VARCHAR(50) | Standard: `Europe/Berlin` |
| `currency` | VARCHAR(10) | Standard: `EUR` |
| `settings_json` | JSON NULL | Zusätzliche Provider-Einstellungen |
| `status` | ENUM('active','inactive') | Standard: `active` |
| `created_at` | TIMESTAMP | Default CURRENT_TIMESTAMP |
| `updated_at` | TIMESTAMP | Auto-Update |

**Indizes:** `UNIQUE(source_plugin, source_id)`, `idx_user(user_id)`, `idx_status(status)`, `UNIQUE(slug)`

---

## booking_services

| Spalte | Typ | Beschreibung |
|---|---|---|
| `id` | INT UNSIGNED PK AI | Primärschlüssel |
| `provider_id` | INT UNSIGNED FK | Zugehöriger Anbieter |
| `title` | VARCHAR(255) | Leistungstitel |
| `slug` | VARCHAR(255) | URL-Slug (unique pro Provider) |
| `description` | TEXT NULL | Beschreibung |
| `duration_min` | INT UNSIGNED | Dauer in Minuten (Standard: 60) |
| `buffer_min` | INT UNSIGNED | Pauze nach Termin (Standard: 15) |
| `max_bookings` | INT UNSIGNED | Max. gleichzeitige Buchungen pro Slot (Standard: 1) |
| `price_cents` | INT UNSIGNED | Preis in Cent (0 = kostenlos) |
| `location_type` | ENUM('online','onsite','hybrid') | Ort-Typ |
| `meeting_url` | VARCHAR(500) NULL | Online-Meeting-Link |
| `booking_type` | ENUM('confirmation','instant','request') | Buchungstyp |
| `contact_template` | VARCHAR(100) NULL | cms-contact-Template-Slug |
| `sort_order` | INT | Sortierung (Standard: 0) |
| `status` | ENUM('active','inactive') | Standard: `active` |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

**Indizes:** `FK(provider_id)`, `idx_slug(provider_id, slug)`

---

## booking_availability

| Spalte | Typ | Beschreibung |
|---|---|---|
| `id` | INT UNSIGNED PK AI | |
| `provider_id` | INT UNSIGNED FK | |
| `day_of_week` | TINYINT NULL | 0=Mo, 1=Di, … 6=So (NULL bei Datums-Override) |
| `specific_date` | DATE NULL | Spezifisches Datum (NULL bei Wochenplan) |
| `start_time` | TIME | Beginn |
| `end_time` | TIME | Ende |
| `is_blocked` | TINYINT(1) | 0=verfügbar, 1=blockiert |
| `created_at` | TIMESTAMP | |

**Indizes:** `FK(provider_id)`, `idx_day(provider_id, day_of_week)`, `idx_date(provider_id, specific_date)`

---

## bookings

| Spalte | Typ | Beschreibung |
|---|---|---|
| `id` | INT UNSIGNED PK AI | Buchungsnummer |
| `provider_id` | INT UNSIGNED FK | Anbieter |
| `service_id` | INT UNSIGNED FK | Leistung |
| `user_id` | INT UNSIGNED NULL | Eingeloggt? → verknüpfter Benutzer |
| `customer_name` | VARCHAR(255) | Kundenname |
| `customer_email` | VARCHAR(255) | Kunden-E-Mail |
| `customer_phone` | VARCHAR(50) NULL | Telefon |
| `booking_date` | DATE | Termindatum |
| `start_time` | TIME | Beginn |
| `end_time` | TIME | Ende |
| `duration_min` | INT UNSIGNED | Buchungsdauer |
| `status` | ENUM('pending','confirmed','cancelled','completed','no_show') | Standard: `pending` |
| `location_type` | ENUM('online','onsite','hybrid') | |
| `meeting_url` | VARCHAR(500) NULL | |
| `price_cents` | INT UNSIGNED | Preis in Cent |
| `currency` | VARCHAR(10) | Währung |
| `notes` | TEXT NULL | Kundennachricht |
| `internal_notes` | TEXT NULL | Interne Notizen |
| `ical_uid` | VARCHAR(255) UNIQUE | Eindeutige iCal-ID |
| `contact_submission_id` | INT UNSIGNED NULL | Verknüpfung zu cms-contact |
| `cancelled_at` | TIMESTAMP NULL | Stornierungszeitpunkt |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

**Indizes:** `FK(provider_id)`, `FK(service_id)`, `idx_user(user_id)`, `idx_status(status)`, `idx_date(booking_date)`, `UNIQUE(ical_uid)`

---

## booking_meta

| Spalte | Typ | Beschreibung |
|---|---|---|
| `id` | INT UNSIGNED PK AI | |
| `booking_id` | INT UNSIGNED FK CASCADE | |
| `meta_key` | VARCHAR(255) | Schlüssel |
| `meta_value` | TEXT NULL | Wert |

**Indizes:** `UNIQUE(booking_id, meta_key)`

---

## booking_settings

| Spalte | Typ | Beschreibung |
|---|---|---|
| `setting_key` | VARCHAR(100) PK | Eindeutiger Schlüssel |
| `setting_value` | TEXT NULL | Wert |
| `updated_at` | TIMESTAMP | |

**Standard-Einstellungen (14):**

| Key | Default | Beschreibung |
|---|---|---|
| `admin_email` | _(leer)_ | Admin-E-Mail für Benachrichtigungen |
| `from_name` | `Buchungssystem` | Absendername |
| `from_email` | _(leer)_ | Absender-E-Mail |
| `default_duration` | `60` | Standard-Dauer (Min.) |
| `default_buffer` | `15` | Standard-Puffer (Min.) |
| `default_timezone` | `Europe/Berlin` | Zeitzone |
| `default_currency` | `EUR` | Währung |
| `booking_advance_min` | `1` | Min. Vorlaufzeit (Tage) |
| `booking_advance_max` | `90` | Max. Vorlaufzeit (Tage) |
| `cancellation_hours` | `24` | Stornierungsfrist (Std.) |
| `auto_confirm` | `0` | Auto-Bestätigung (0/1) |
| `send_reminders` | `1` | Erinnerungen senden (0/1) |
| `reminder_hours` | `24` | Erinnerung X Std. vorher |
| `primary_color` | `#3b82f6` | Primärfarbe für Frontend |
