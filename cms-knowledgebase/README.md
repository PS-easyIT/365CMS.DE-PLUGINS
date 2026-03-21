# CMS Knowledgebase

CMS Knowledgebase erweitert 365CMS um eine kleine, performante Wissensdatenbank mit automatisch verlinkten Fachbegriffen, Tooltip-Vorschauen und öffentlichen KB-Seiten unter `/kb`.

## Features

- Auto-Linking für Fokusbegriffe und Synonyme
- Tooltip-Vorschau mit Vanilla JavaScript
- Öffentliche Übersicht und Detailseiten unter `/kb` und `/kb/{slug}`
- Admin-CRUD für Einträge und Plugin-Einstellungen
- PSR-3-kompatibles Logging über den vorhandenen CMS-Logger
- Optionaler Output-Buffer-Fallback, falls ein Theme den Filter `content_render` nicht nutzt

## Struktur

- `cms-knowledgebase.php` – Bootstrap, Hooks, Autoloading
- `src/` – Namespaced Klassen unter `CmsKnowledgebase\`
- `admin/views/` – Admin-Templates
- `templates/` – Öffentliche Templates
- `assets/` – CSS und Vanilla-JS

## Hinweise

- Für zuverlässiges Auto-Linking ist der Filter `content_render` der bevorzugte Weg.
- Der Output-Buffer-Fallback ist standardmäßig aktiv, damit bestehende Themes trotzdem profitieren.
- Der bestehende leere Tippfehler-Ordner `cms-knowledbase` wurde absichtlich nicht überschrieben.
