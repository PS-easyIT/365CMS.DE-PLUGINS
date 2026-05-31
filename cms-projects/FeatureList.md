# CMS Projects - Feature Ideas (Research-Only)

## 1) Configurable WIP Limits per Board Column
- **Feature name:** Configurable WIP limits per board column
- **Short description:** Allow admins to set max tickets per board column and show non-blocking warnings when a limit is reached.
- **Why it fits this plugin:** The plugin already supports kanban-like project boards and drag-and-drop ticket moves; WIP constraints improve flow control without changing core board concepts.
- **Rough effort (low/medium/high):** Medium
- **Source link(s):**
  - https://www.atlassian.com/software/jira/features/kanban-boards
  - https://confluence.atlassian.com/agile063/tutorial-tracking-a-kanban-team-600867555.html

## 2) Dependency Links Between Tasks
- **Feature name:** Task predecessor/successor dependency tracking
- **Short description:** Let users define predecessor/successor links between tasks and flag scheduling conflicts in admin dashboards.
- **Why it fits this plugin:** Projects in this plugin already combine boards, widgets, and due dates; dependencies would add planning depth while staying in the existing project management scope.
- **Rough effort (low/medium/high):** High
- **Source link(s):**
  - https://learn.microsoft.com/en-us/azure/devops/boards/plans/track-dependencies?view=azure-devops
  - https://learn.microsoft.com/en-us/training/modules/manage-delivery-plans/6-track-dependencies-delivery-plans

## 3) Automation Rules for Project/Task Lifecycle
- **Feature name:** Rule-based project automation hooks
- **Short description:** Add optional rules like auto-transition, auto-archive completed tasks, and auto-set status based on due date or checklist completion.
- **Why it fits this plugin:** The plugin already has structured entities and admin actions; rules would reduce manual overhead and keep board state consistent at scale.
- **Rough effort (low/medium/high):** High
- **Source link(s):**
  - https://docs.github.com/en/issues/planning-and-tracking-with-projects/automating-your-project
  - https://docs.github.com/en/issues/planning-and-tracking-with-projects/automating-your-project/using-the-api-to-manage-projects

## 4) Structured Audit Trail for Admin Mutations
- **Feature name:** Structured audit trail for admin changes
- **Short description:** Store immutable-like, structured records for create/update/delete/move actions (who, what, when, result) and expose a read-only audit view for administrators.
- **Why it fits this plugin:** The plugin performs state-changing admin operations on projects, boards, widgets, and tasks; auditability improves incident response and accountability.
- **Rough effort (low/medium/high):** Medium
- **Source link(s):**
  - https://cheatsheetseries.owasp.org/cheatsheets/Logging_Cheat_Sheet.html
  - https://github.com/OWASP/ASVS/blob/v5.0.0/5.0/en/0x25-V16-Security-Logging-and-Error-Handling.md
