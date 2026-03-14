# 365CMS Plugins – Zukünftige Plugin-Konzepte & Roadmap

> Letzte Aktualisierung: 2026-02-27  
> Diese Datei dokumentiert geplante und vorgeschlagene Plugins für das 365CMS-Ökosystem.

---

## Priorisierung

| Priorität | Label | Beschreibung |
|-----------|-------|--------------|
| 🔴 | **Hoch** | Kern-Funktionalität, unmittelbarer Bedarf |
| 🟡 | **Mittel** | Wertvolle Erweiterung, mittelfristiger Bedarf |
| 🟢 | **Niedrig** | Nice-to-have, langfristiger Bedarf |
| 🔵 | **Konzept** | Ideen ohne konkreten Zeitplan |

---

## 🔴 Hoch – Kurzfristig

### CMS-Forum (`cms-forum`)

**Beschreibung:** Vollständiges Community-Forum mit Kategorien, Themen, Posts, Reactions und Moderations-Tools.

**Kernfunktionen:**
- Forum-Kategorien mit Zugriffsrechten (RBAC-Integration)
- Thread-Erstellung mit SunEditor WYSIWYG
- Post-Reactions (👍 👎 ❤️ 🎉)
- Moderations-Tools (Pin, Lock, Delete, Move)
- Benachrichtigungen bei neuen Antworten
- Experten-Badge im Forum-Profil (→ cms-experts)
- Search & Filter
- Markdown-Support

**Datenbank:** `cms_forum_categories`, `cms_forum_threads`, `cms_forum_posts`, `cms_forum_reactions`, `cms_forum_subscriptions`

**Integrationen:** cms-experts (Expert-Badge), cms-users (RBAC), CMS Notifications

---

### CMS-Newsletter (`cms-newsletter`)

> **Status-Update (2026-03-14):** Eine erste vollständige Plugin-Basis mit Admin-Dashboard, Subscriber-Verwaltung, Templates, Kampagnen, Settings und öffentlicher Anmeldeseite ist inzwischen umgesetzt. Die folgenden Punkte bleiben als Ausbau-/Produktions-Roadmap relevant.

**Beschreibung:** Vollständiger Newsletter-Dienst mit Template-Builder, Subscriber-Management und Statistiken.

**Kernfunktionen:**
- Subscriber-Management (Double Opt-In via DSGVO)
- Drag & Drop E-Mail-Template-Builder
- Kampagnen planen und versenden
- A/B-Test-Support
- Öffnungs- und Klick-Tracking
- Automatisierungen (Welcome-Serie, Event-Reminders)
- Listen-Segmentierung (nach Profil-Attributen)
- Bounce-Handling
- Abmelde-Center (DSGVO-konform)

**Datenbank:** `cms_newsletter_subscribers`, `cms_newsletter_campaigns`, `cms_newsletter_templates`, `cms_newsletter_sends`, `cms_newsletter_stats`

**Integrationen:** cms-events (Event-Ankündigungen), cms-experts (Expert-Updates), CMS Users

---

### CMS-Marketplace (`cms-marketplace`)

**Beschreibung:** Plugin-/Theme-Marketplace mit Kauf, Bewertungen und automatischen Updates.

**Kernfunktionen:**
- Produkte (Plugins, Themes, Templates) listen
- Kauf-Workflow mit Payment-Integration
- Lizenz-Verwaltung (Token-basiert)
- Automatische Update-Benachrichtigungen
- Ratings & Reviews
- Entwickler-Dashboard (Einnahmen, Statistiken)
- Free / Premium-Tier

**Datenbank:** `cms_marketplace_products`, `cms_marketplace_licenses`, `cms_marketplace_purchases`, `cms_marketplace_reviews`

---

## 🟡 Mittel – Mittelfristig

### CMS-Booking (`cms-booking`)

**Beschreibung:** Buchungs- und Terminverwaltungs-System für Experten und Speaker.

**Kernfunktionen:**
- Verfügbarkeitskalender (Experten & Speaker)
- Buchungsanfragen mit Bestätigungs-Workflow
- Zeitslot-Konfiguration (Dauer, Puffer, Max. Buchungen)
- Automatische Bestätigungs-E-Mails
- iCal/Google Calendar Export
- Zoom/Teams-Integration (Meeting-Link automatisch)
- Zahlungs-Integration (Honorar-Buchung)

**Integrationen:** cms-experts (Verfügbarkeit), cms-speakers (Buchung), cms-events (Event-Slots)

---

### CMS-Reviews (`cms-reviews`)

**Beschreibung:** Bewertungs- und Rezensions-System für Experten, Speaker und Firmen.

**Kernfunktionen:**
- Sternebewertungen (1–5) mit Kommentar
- Kategorisierte Bewertungskriterien
- Verifizierte Käufer / Auftraggeber
- Review-Moderation
- Aggregierte Bewertungsanzeige auf Profil-Seiten
- Response-Funktion (auf Reviews antworten)
- SEO-konforme Schema.org-Ausgabe

**Integrationen:** cms-experts, cms-speakers, cms-companies

---

### CMS-Analytics-Dashboard (`cms-analytics`)

**Beschreibung:** Erweitertes Analytics-Dashboard für Profil-Views, Event-Aufrufe, Stellenanzeigen-Performance.

**Kernfunktionen:**
- Profil-View-Tracking (Experts, Speakers, Companies)
- Event-Klick-/Anmelde-Tracking
- Stellenanzeigen-Performance (Aufrufe, Bewerbungen)
- Vergleichs-Charts (Zeitraum-Vergleich)
- Heatmaps für interaktive Elemente
- Export als CSV / PDF
- DSGVO-konforme Session-Aggregation (keine individuelle Verfolgung)

---

### CMS-Certificates (`cms-certificates`)

**Beschreibung:** Ausstellen und Verwalten von Zertifikaten für abgeschlossene Kurse oder Veranstaltungen.

**Kernfunktionen:**
- PDF-Zertifikat-Generierung (anpassbares Template)
- QR-Code zur Verifizierung
- Öffentliche Verifikations-URL
- Automatische Ausstellung nach Event-Teilnahme
- Blockchain-Hash (optional, für Non-Repudiation)
- Zertifikat-Galerie im Experten/Member-Profil

**Integrationen:** cms-events (Teilnahme-Zertifikat), cms-experts (Zertifikats-Anzeige)

---

### CMS-Subscription-Gating (`cms-gating`)

**Beschreibung:** Content-Gating auf Basis von Abo-Plänen – bestimmte Seiten/Bereiche nur für Mitglieder.

**Kernfunktionen:**
- Page/Post-Level Zugriffssteuerung per Abo-Tier
- Shortcode `[cms_gate plan="premium"]...[/cms_gate]`
- Blurring/Teaser-Modus (Inhalt angedeutet)
- Conversion-Tracking (Gating → Abo-Abschluss)
- A/B-Test für Gate-Seiten

---

## 🟢 Niedrig – Langfristig

### CMS-Chatbot (`cms-chatbot`)

**Beschreibung:** KI-gestützter Chatbot, der Experten-Profile, Events und Jobs recherchiert und empfiehlt.

**Kernfunktionen:**
- Intent-Detection (Experten suchen, Event anfragen, Stelle melden)
- Integration mit cms-experts/events Search API
- LLM-Backend (OpenAI API oder lokales Modell)
- Conversation-History pro Session
- Escalation zu menschlichem Agent

---

### CMS-Live-Stream (`cms-livestream`)

**Beschreibung:** Live-Streaming-Integration für Events (Zoom, YouTube Live, eigener RTMP-Server).

**Kernfunktionen:**
- Stream-URL-Verwaltung pro Event
- Zugangskontrolle (Ticket-Gating)
- Chat-Integration während des Streams
- Replay-Archiv nach dem Event
- VOD-Player mit Kapitel-Markierungen

**Integrationen:** cms-events (Stream-URL), cms-speakers (Host-Profil)

---

### CMS-Mentoring (`cms-mentoring`)

**Beschreibung:** Mentoring-Matching zwischen erfahrenen Experten und Einsteigern.

**Kernfunktionen:**
- Profil-Matching via Skill-Matrix
- Mentoring-Anfrage-Workflow
- Session-Planung (→ cms-booking)
- Fortschritts-Tracking (Goals, Milestones)
- Abschluss-Zertifikat (→ cms-certificates)

---

### CMS-Job-Board-Aggregator (`cms-jobboard`)

**Beschreibung:** Externe Job-Aggregation und Cross-Posting von Stellenanzeigen.

**Kernfunktionen:**
- Import von externen Job-Boards (Indeed, StepStone API)
- Cross-Posting zu externen Plattformen
- Duplicate-Detection
- UTM-Tracking für Klick-Quellen
- Ablaufdatum-Verwaltung

**Integrationen:** cms-jobprofile-generator (als Quelle)

---

## 🔵 Konzept-Ideen

| Konzept | Kurzbeschreibung |
|---------|-----------------|
| **CMS-Wiki** | Internes Wiki/Knowledge-Base mit Markdown, Versionierung, Zugriffsrechten |
| **CMS-Badges** | Gamification-System mit Achievement-Badges für aktive Member |
| **CMS-Referral** | Empfehlungs-Marketing-System mit Tracking & Prämien |
| **CMS-API-Gateway** | Öffentliche REST-API für alle Plugin-Daten mit OAuth2 |
| **CMS-Mobile-App** | React Native App für das Member-Portal |
| **CMS-White-Label** | Multi-Tenant-System für Wiederverkäufer |
| **CMS-AI-Profiler** | KI-gestützte Profil-Optimierung für Experten/Speaker |
| **CMS-Contract-Manager** | Vertrags-Management für Freelancer-Engagements |
| **CMS-Invoice** | Rechnungs-Generator für Freelancer (→ cms-booking, cms-experts) |
| **CMS-Portfolio** | Erweitertes Portfolio-System mit Medien-Gallery und Case Studies |

---

## Plugin-Entwicklungs-Checkliste (für neue Plugins)

Vor Implementierung sicherstellen:

- [ ] Eindeutiger Plugin-Slug (`cms-xxxxx`)
- [ ] `declare(strict_types=1)` + ABSPATH-Guard in jeder PHP-Datei
- [ ] Singleton-Pattern (`::instance()`)
- [ ] Alle Hooks via `CMS\Hooks::addAction()`
- [ ] DB-Tabellen via `CMS_Xxx_Installer::create_tables()` bei Aktivierung
- [ ] Admin-Bereich unter `admin/` mit Traits für Module
- [ ] Member-Bereich unter `member/` / `views/member/`
- [ ] CSRF-Token in allen Formularen
- [ ] Eingabe-Sanitierung (sanitize_text_field, FILTER_VALIDATE_*)
- [ ] Ausgabe-Escaping (htmlspecialchars)
- [ ] Cross-Plugin-Zugriffe mit `PluginManager::isPluginActive()` absichern
- [ ] `update.json` für Auto-Update
- [ ] Einträge in `/DOC/PLUGINNAME/` anlegen (README, DATABASE, HOOKS, API, CHANGELOG)
- [ ] In `index.json` eintragen
- [ ] DSGVO-Hooks registrieren (`dsgvo_export_data`, `dsgvo_delete_data`)
