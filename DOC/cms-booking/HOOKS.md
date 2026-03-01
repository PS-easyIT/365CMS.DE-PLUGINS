# CMS Booking – Hooks-Referenz

> Actions und Filter des CMS-Booking-Plugins

---

## Actions

### `booking_register_providers`

Wird ausgelöst, damit externe Plugins eigene Anbietertypen registrieren können.

```php
CMS\Hooks::addAction('booking_register_providers', function () {
    CMS_Booking_Integration::register_provider_type('mein-typ', [
        'label'          => 'Mein Typ',
        'icon'           => '🔧',
        'source_table'   => 'meine_tabelle',
        'name_column'    => 'name',
        'email_column'   => 'email',
        'user_id_column' => 'user_id',
        'single_route'   => '/mein-bereich/{slug}',
        'contact_template' => 'booking-simple',
        'fields_map'     => [],
    ]);
});
```

**Zeitpunkt:** `cms_init` bei Priorität 50

---

### `booking_created`

Wird nach dem Anlegen einer neuen Buchung ausgelöst.

```php
CMS\Hooks::addAction('booking_created', function (int $bookingId, array $data) {
    // Buchung nachbearbeiten
}, 10);
```

**Parameter:** `$bookingId` (int), `$data` (array – Buchungsdaten)

---

### `booking_status_confirmed`

Wird beim Bestätigen einer Buchung ausgelöst.

```php
CMS\Hooks::addAction('booking_status_confirmed', function (int $bookingId) {
    // Z. B. Meeting-Link generieren
});
```

---

### `booking_status_cancelled`

Wird beim Stornieren einer Buchung ausgelöst.

```php
CMS\Hooks::addAction('booking_status_cancelled', function (int $bookingId) {
    // Z. B. Zeitfenster wieder freigeben
});
```

---

### `booking_status_completed`

Wird beim Abschließen einer Buchung ausgelöst.

```php
CMS\Hooks::addAction('booking_status_completed', function (int $bookingId) {
    // Z. B. Feedback-E-Mail senden
});
```

---

### `booking_status_no_show`

Wird bei Markierung als Nicht-Erschienen ausgelöst.

```php
CMS\Hooks::addAction('booking_status_no_show', function (int $bookingId) {
    // Z. B. No-Show-Zähler erhöhen
});
```

---

## DSGVO-Hooks

### `dsgvo_export_data`

CMS Booking exportiert alle Buchungsdaten eines Benutzers.

```php
// Automatisch registriert bei Plugin-Aktivierung
CMS\Hooks::addAction('dsgvo_export_data', [$bookings, 'export_user_data']);
```

**Exportierte Daten:** Alle Buchungen inkl. Metadaten

---

### `dsgvo_delete_data`

CMS Booking anonymisiert Buchungsdaten (Name → „Gelöscht", E-Mail → „deleted@...").

```php
CMS\Hooks::addAction('dsgvo_delete_data', [$bookings, 'delete_user_data']);
```

---

## Eingebaute Provider-Typen

Bei aktiver Integration werden folgende Typen automatisch registriert:

| Type-Slug | Plugin | Label | Icon |
|---|---|---|---|
| `expert` | cms-experts | Experten | 🎓 |
| `speaker` | cms-speakers | Speaker | 🎤 |
| `event` | cms-events | Events | 📅 |
| `company` | cms-companies | Unternehmen | 🏢 |

### Konfigurations-Felder pro Typ

| Feld | Beschreibung | Pflicht |
|---|---|---|
| `label` | Anzeigename des Typs | ✅ |
| `icon` | Emoji-Icon | ✅ |
| `source_table` | DB-Tabelle ohne Prefix | ✅ |
| `name_column` | Spalte für Anzeigename | ✅ |
| `email_column` | Spalte für E-Mail | ✅ |
| `user_id_column` | Spalte für User-ID-Verknüpfung | ❌ |
| `single_route` | URL-Pattern für Single-Page | ❌ |
| `contact_template` | Standard-cms-contact-Template | ❌ |
| `fields_map` | Mapping zusätzlicher Felder | ❌ |

---

## Rendering-Helfer

### `CMS_Booking_Integration::render_booking_button()`

Gibt einen HTML-Link-Button für eine Single-Page aus.

```php
echo CMS_Booking_Integration::render_booking_button(
    'expert',       // Provider-Typ
    42,             // Quell-ID
    'Max Experte',  // Anzeigename
    'max@test.de'   // E-Mail
);
// → <a href="/booking/max-experte" class="booking-btn-inline">📅 Termin buchen</a>
```

### `CMS_Booking_Integration::render_booking_widget()`

Gibt ein Widget mit Leistungsliste und Preisen aus.

```php
echo CMS_Booking_Integration::render_booking_widget('expert', 42, 'Max Experte', 'max@test.de');
```
