# CMS Speakers – Dokumentation

**Plugin:** `cms-speakers`  
**Version:** 1.0.0  
**Namespace:** `CMS_Speakers`  
**Mindest-CMS-Version:** 365CMS 2.0+  
**PHP:** 8.1+

---

## Übersicht

Das **CMS Speakers**-Plugin verwaltet professionelle Speaker-Profile mit Vortragshistorie, Themengebieten, Honorarrahmen und Event-Anbindung.

### Kernfunktionen

| Bereich | Funktion |
|---------|----------|
| **Basis-Profil** | Name, Titel, Firma, Bio, Foto, Kontakt |
| **Social Media** | LinkedIn, XING, Twitter, GitHub/GitLab, YouTube, Instagram |
| **Themen** | Speaker-Topics mit Beschreibung und Sortierung |
| **Event-History** | Auftritte: Keynote, Workshop, Panel, Webinar, … |
| **Honorar** | Preisspanne (min. / max.) + Reise-Radius |
| **Verknüpfung** | Optional: Verlinkung mit Experten-Profil (`expert_id`) |
| **Company-Link** | Optional: Firmenzugehörigkeit via `company_id` |
| **Verfügbarkeit** | `available`, `limited`, `booked` |
| **Badges** | `is_featured`, `is_verified` |
| **Admin-Backend** | Vollständige CRUD unter `/admin/speakers` |
| **Member-Dashboard** | Eigenes Speaker-Profil verwalten |
| **Shortcode** | `[cms_speakers]` |
| **Öffentliche Routen** | `/speakers`, `/speakers/{id}` |

---

## Dateistruktur

```
cms-speakers/
├── cms-speakers.php
├── README.md
├── update.json
├── includes/
│   ├── class-database.php
│   ├── class-admin.php
│   ├── class-member-dashboard.php
│   ├── class-meta-boxes.php
│   ├── class-post-type.php
│   ├── class-shortcode.php
│   └── class-template-loader.php
├── templates/
│   ├── archive-speaker.php
│   ├── speaker-card.php
│   └── single-speaker.php
└── assets/
```

---

## Weitere Dokumente

| Dokument | Inhalt |
|----------|--------|
| [DATABASE.md](DATABASE.md) | Tabellen-Schemas |
| [HOOKS.md](HOOKS.md) | Actions & Filter |
| [API.md](API.md) | Methoden-Referenz |
| [CHANGELOG.md](CHANGELOG.md) | Versionshistorie |

---

## Schnellstart

```php
$db = CMS\Database::instance();
$p  = $db->prefix();

// Alle verfügbaren Speaker
$stmt = $db->prepare("SELECT * FROM {$p}speakers WHERE status='active' AND availability='available'");
$stmt->execute();
$speakers = $stmt->fetchAll();

// Vorträge eines Speakers
$stmt = $db->prepare("SELECT * FROM {$p}speaker_events WHERE speaker_id=? ORDER BY event_date DESC");
$stmt->execute([$speaker_id]);
$talks = $stmt->fetchAll();
```

---

## Cross-Plugin-Integration

| Plugin | Richtung | Beschreibung |
|--------|----------|--------------|
| `cms-experts` | → | `expert_id` verlinkt Speaker mit Experten-Profil |
| `cms-companies` | → | `company_id` verlinkt Speaker mit Firmenprofil |
| `cms-events` | ← | Events können Speaker via `cms_event_speakers` einladen |

---

## Speaker-Formate

Gespeichert als JSON-Array im `formats`-Feld:

| Format | Beschreibung |
|--------|-------------|
| `keynote` | Hauptvortrag |
| `workshop` | Praktischer Workshop |
| `panel` | Podiumsdiskussion |
| `moderation` | Moderation |
| `interview` | Interview / Podcast |
| `webinar` | Online-Seminar |
| `training` | Schulung |
