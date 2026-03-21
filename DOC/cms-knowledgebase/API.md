# API-Übersicht

## Bootstrap

- `CMS_Knowledgebase::instance()` – startet Hooks, Assets und Upgrade-Checks

## Namespaced Klassen

- `CmsKnowledgebase\Database\Installer` – Tabellen anlegen und Version verwalten
- `CmsKnowledgebase\Repository\EntryRepository` – CRUD, Settings, Dashboard-Statistiken
- `CmsKnowledgebase\Service\Linker` – Auto-Linking und Output-Buffer-Fallback
- `CmsKnowledgebase\Http\PublicController` – `/kb`-Routen und öffentliche Ausgabe
- `CmsKnowledgebase\Admin\Menu` – Admin-Menüeinträge
- `CmsKnowledgebase\Admin\Pages` – Admin-Rendering und POST-Handling
- `CmsKnowledgebase\Support\LoggerFactory` – PSR-3-kompatiblen Logger bereitstellen
