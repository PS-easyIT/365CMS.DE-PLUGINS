# CMS M365 Copilot

Copilot-Landingpage für 365CMS mit Fokus auf Beratung und Lizenzvertrieb.

## Features

- Content Header mit 3 Layouts (Bild links / Full-width Background / Overlay)
- Optionales Dienstleistungsband mit 3 Layouts, nahtlos an den Header angedockt
- Drei frei konfigurierbare Bereichscards
- Beitragssektion aus Kategorie "Microsoft Copilot" (Name/Slug, Anzahl konfigurierbar)
- Globale Layout-/Spacing-Variablen als CSS-Variablen
- Serverseitiges Rendering, Vanilla JS nur optional, mobile-first

## Admin

- Plugin-Menü: **M365 Copilot**
- Eine gruppierte Einstellungsseite mit Bereichen:
  - Content Header
  - Dienstleistungsband
  - Bereichscards
  - Beiträge
  - Global Layout & Spacing

## Sicherheit

- CSRF-Schutz über `CMS\Security::instance()->generateToken()/verifyToken()`
- Sanitizing/Validierung bei allen Eingaben
- HTML-Ausgabe mit `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`

## Technisch

- PHP 8.4
- Keine zusätzlichen externen Dependencies
- Self-contained Plugin, keine Core-/Theme-Modifikationen
