# CMS Projects

`cms-projects` ist ein 365CMS-Plugin für JIRA-ähnliche Projektsteuerung mit Projekt-Dashboards, Boards und Widgets.

## Enthalten

- Projektverwaltung im Adminbereich
- Projekt-Dashboard je Projekt
- Board-Typen:
  - `Kanban`
  - `DIY`
  - `MIRO`
  - `FLOW`
  - `Roadmap`
- Widgets für `Public`, `Member` oder `beide` Bereiche
- Public-Routen:
  - `/projects`
  - `/projects/<slug>`
- Member-Bereich:
  - `/member/plugin/projects`

## Widgets

Widgets können aktuell als folgende Typen gespeichert werden:

- `text`
- `checklist`
- `links`
- `stats`
- `timeline`

## Content-Tags

Das Plugin verarbeitet im Seiteninhalt folgende Tags:

```text
[cms_projects project="mein-projekt" scope="public"]
[cms_projects project="mein-projekt" scope="member"]
[cms_projects_widget project="mein-projekt" scope="public"]
[cms_projects_widget project="mein-projekt" scope="member" type="stats"]
```

## MVP-Stand

Dieses erste MVP enthält bereits:

- Datenbanktabellen für Projekte, Boards und Widgets
- Admin-Maske zum Anlegen von Projekten
- Board- und Widget-Erstellung pro Projekt
- Member-Dashboard-Integration
- öffentliche Projektseiten

## Nächste sinnvolle Ausbaustufen

- Aufgaben/Tickets je Board als eigene Datensätze
- Kommentare, Verantwortliche und Fälligkeiten
- Drag-and-drop für Board-Spalten
- Dateien, Releases und Activity-Feed
- Rollen/Rechte je Projekt
