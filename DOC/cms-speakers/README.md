# CMS Speakers – Dokumentation

**Plugin:** `cms-speakers`  
**Version:** 3.0.15
**Namespace:** `CMS_Speakers`  
**Aktueller Laufzeitstand:** 365CMS 3.0+  
**Audit-/Dokustand:** Public-Detailseiten-Polish am 2026-05-31
**PHP:** 8.4+

---

## Übersicht

Das **CMS Speakers**-Plugin verwaltet professionelle Speaker-Profile mit Vortragshistorie, Themengebieten, Honorarrahmen und Event-Anbindung.

## 3.0.3-Audit-Status

- Die Dokumentation ist auf den **Audit- und Zielstand für 365CMS 3.x** angehoben.
- Änderungen erfolgen ausschließlich im Plugin; **der 365CMS-Core bleibt unberührt**.
- Der Fokus des aktuellen Durchgangs liegt auf **Security**, **HTTP-Fehlerpfaden**, **Best Practices**, **Admin-Menü-Integration** und defensiver Cross-Plugin-Integration.
- Version 3.0.3 bringt redeclare-sicheren Bootstrap, SettingsService-Anbindung, `INFORMATION_SCHEMA`-Migrationen, 405-/JSON-Fehlerpfade und Uninstall-Cleanup.
- Version 3.0.6 gleicht Archiv, Filterbar, Cards, Topic-Pills, Detailprofil und Anfragebox an das PHINIT-Publicsite-Design an und ergänzt E-Mail-/Kontaktlinks im Profil.
- Version 3.0.7 verfeinert den Screenshot-Stand Mai 2026 mit Initialen-Avataren, goldenen Outline-CTAs, blauem Kontaktbutton, Empty State, Social-Icons und Related-Speaker-Hover.
- Version 3.0.8 stellt die Speaker-Archivkarten auf ein horizontales, kompaktes Listenlayout mit 72px Avatar links, Inhaltsblock rechts und Keyboard-Navigation um.
- Version 3.0.9 verfeinert die horizontale Speaker-Liste auf ein klar getrenntes 3-Zonen-Layout mit 56px Avatar, Name/Rolle, Topic-Zeile, zweizeiliger Bio und rechts oben ausgerichtetem Profilbutton.
- Version 3.0.10 stellt die öffentliche Speaker-Übersicht wieder auf ein responsives Event-ähnliches Card-Grid um: Filter zuerst, danach Cards mit Avatar/Initialen, Speaker-Badges, Verfügbarkeit, Ort/Formaten, Bio, Footer-Meta und Profil-CTA.
- Version 3.0.14 setzt in der Public-Speakers-Filterleiste einen primären „Suchen“-Button als Hauptaktion und begrenzt den Suchbereich final auf maximal `1160px` Contentbreite.
- Version 3.0.15 begrenzt die Public-Speakers-Detailseite inklusive Breadcrumb, Profil-/Contentbereich und Anfrage-Spalte auf maximal `1160px`, verbessert die responsiven Umbrüche und sichert die Dark-Mode-Darstellung der Detailkarten ab.

### Kernfunktionen

| Bereich | Funktion |
|---------|----------|
| **Basis-Profil** | Name, Titel, Firma, Bio, Foto, Kontakt |
| **Social Media** | LinkedIn, XING, Twitter, GitHub/GitLab, YouTube, Instagram |
| **Themen** | Speaker-Topics mit Beschreibung und Sortierung |
| **Event-History** | Auftritte: Keynote, Workshop, Panel, Webinar, … |
| **Honorar** | Preisspanne (min. / max.) + Reise-Radius |
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
| [SECURITY.md](SECURITY.md) | Sicherheitskonzept |

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
