---
applyTo: "**/*"
---

# 365CMS Plugin-Repository – Dokumentations-Mapping

- Zentrales DOC-Verzeichnis: `DOC/` im Repo-Root
- Pro Plugin: `DOC/[plugin-slug]/` mit README, DATABASE, HOOKS, API, CHANGELOG
- Zukünftige Plugins und Ideen: `DOC/TASKS.md`
- Plugin-Architektur-Überblick: `README.md` im Repo-Root
- Copilot-Instruktionen: `.github/copilot-instructions.md`
- Spezifische Regeln: `.github/instructions/*.instructions.md`

## Vor neuen Aufgaben prüfen

1. `README.md` (Root) – Plugin-Übersicht und Architektur
2. `DOC/[plugin-slug]/README.md` – Plugin-Dokumentation
3. `DOC/[plugin-slug]/DATABASE.md` – Tabellen und Schemas
4. `DOC/[plugin-slug]/HOOKS.md` – Events und Filter
5. `.github/copilot-instructions.md` – Allgemeine Konventionen
