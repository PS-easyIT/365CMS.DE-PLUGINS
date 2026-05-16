# CMS M365 Calculator – Dokumentation

## Überblick

`cms-m365calculator` ist eine modulare Microsoft-365-Rechner-Toolbox für 365CMS. Das erste produktive Modul ist der **Shared-Mailbox vs. Lizenz-Rechner**.

## Public Routes

| Route | Zweck |
|---|---|
| `/m365-tools` | Übersicht aller Rechner-Module |
| `/m365-rechner` | Alternative Hub-Route |
| `/shared-mailbox-vs-lizenz` | Shared-Mailbox-Entscheidung und Kostenabschätzung |

## Designvorgaben

Das Plugin nutzt PHINIT-konforme Public-Komponenten und vermeidet statische Inline-Styles, große Gradients, Glassmorphism oder KI-Optik. Die Hub-Landingpage rendert Module ausschließlich aus der Tool-Registry.
