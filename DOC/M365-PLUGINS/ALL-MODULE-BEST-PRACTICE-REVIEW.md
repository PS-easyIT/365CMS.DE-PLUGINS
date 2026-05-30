# CMS M365 Tools – All-Module-Best-Practice-Review

Stand: `2026-05-17`  
Plugin-Version: `1.27.0`

Dieses Dokument beschreibt die erneute Prüfung aller 21 Public-Module gegen offizielle Microsoft-Learn-Quellen. Die maschinenlesbare Umsetzung liegt in `cms-m365tools/data/m365_best_practice_catalog.json`; die Landingpage `/m365-tools` zeigt daraus Review-Domänen und konkrete Prüfpunkte je Modul.

## Geprüfte Quellbereiche

- Microsoft 365 Network Connectivity Principles, Managing Microsoft 365 Endpoints, IP Address and URL Web Service, Network Planning and Performance sowie URLs/IP Address Ranges.
- Microsoft 365 Admin Activity Reports und Datenschutz-/Rollenaspekte für Nutzungsberichte.
- Microsoft 365 Lizenzzuweisung und Gruppenlizenzierung inklusive Standort, Fehlerlisten, Gruppenlimits und Änderungsfenstern.
- Exchange Online, SharePoint Online und Teams Servicegrenzen.
- Entra Conditional Access Overview und Planungsleitfaden.
- Defender for Office 365 Overview und Deployment Guide.
- Microsoft 365 Copilot Licensing, Requirements und Setup inklusive App-, Postfach-, Update- und WSS-Anforderungen.
- Microsoft 365 Backup Overview inklusive PAYG-Modell, Workloads, Restore-Performance und Append-only-Ansatz.
- Power Platform Well-Architected Security und Performance Efficiency.
- Power Platform API Request Limits and Allocations sowie Dataverse Capacity Storage.

## Review-Domänen

| Domäne | Schwerpunkt |
|---|---|
| Lizenz & Kosten | Nutzerbestand, Gruppenlogik, Add-ons, Laufzeiten, Renewal und Usage Reports |
| Identität & Zugriff | Least Privilege, Conditional Access, Pilotgruppen, Notfallkonten und Reviews |
| Schutz & Compliance | Defender, Purview, Mail-Authentifizierung, Aufbewahrung, Datenzugriff |
| Servicegrenzen | Exchange, SharePoint, OneDrive, Teams, Copilot und Power Platform |
| Speicher & Backup | Tenant-Pool, Sitegrenzen, Postfachwachstum, Dataverse und Restore-Ziele |
| Netzwerk & Performance | Lokaler Egress, DNS, Endpoint-Webservice, WSS, Proxy/VPN-Pfade und Messwerte |
| Copilot & KI | Basislizenz, primäres Exchange-Online-Postfach, Apps, Datenfreigaben und Pilotmetriken |
| Power Platform Betrieb | Umgebungen, Datenrichtlinien, ALM, Monitoring, Requests und Dataverse-Kapazität |
| Migration & Betrieb | Quellsysteme, Durchsatz, Change, Hypercare, Reporting und Betriebsübergabe |

## Modulabdeckung

| Modul | Aktualisierte Prüfpunkte |
|---|---|
| Shared-Mailbox vs. Lizenz-Rechner | 50/100-GB-Pfade, Archiv/Hold, Zugriffsnutzer und Mail-Schutz |
| M365 Lizenzmatrix | Servicegrenzen, Schutzfunktionen und Segmenttrennung |
| M365-Lizenzvergleich | Copilot-Basispläne, Exchange/SharePoint/Teams-Grenzen und Add-on-Alternativen |
| M365 Add-on-Matrix | Prerequisites, Defender-Mail-Schutz, Power-Platform-Capacity und Redundanzen |
| M365 Add-On-Konfigurator | Backup, Storage, Copilot, Mail-Schutz, Identität und Verbrauchsmodelle |
| Annual vs. Monthly Commitment | stabiler Kernbestand, saisonale Seats, Renewal und Preisänderungen |
| Archive Mailbox Rechner | Auto-expanding Archive, Recoverable Items, Hold und Shared-Mailbox-Sonderfälle |
| AI Pack vs. Copilot Pro | Datenzugriff, Zielgruppe, Chatpfade und Datenschutz |
| Copilot Pilot-Phase-Rechner | Pilotmetriken, Apps, WSS, OneDrive, Teams, Oversharing und Purview |
| Frontline Worker Lizenz-Check | F1/F3-Fit, Gerätefreigabe, Conditional Access und App-Schutz |
| Exchange Online ROI | Mailboxgrenzen, Migration, Netzwerkbasis und Hypercare |
| Teams Phone Lizenzberater | PSTN-Modell, UDP-Medienpfade, Standortanforderungen und Teams-Grenzen |
| Microsoft-Preiserhöhung-Tracker | Preis-/Packaging-Ereignisse, SKU-Mapping, Laufzeitmodell und Budgetwirkung |
| Lizenz-Audit-Checkliste | Rollen, Conditional Access, Defender, Reports, Endpoints, Backup, Copilot und Power Platform |
| M365 Backup-Kosten-Rechner | Workload-Scope, Retention, Restore, Trust Boundary und Testbudget |
| M365 Storage-Bedarfs-Rechner | SharePoint-Pool, Sitegrenzen, Datei-/Pfadlänge, Dataverse und Exchange-Archiv |
| Workspace ↔ M365 TCO | Netzwerkbasis, Parallelbetrieb, Schulung, Hypercare, Zielgrenzen und Adoption |
| Power Platform Kosten-Kalkulator | Request-Kontingente, Dataverse Database/File/Log, ALM, Security und Performance |
| M365-Lizenz-Berater | Personas, Gruppenlogik, Servicegrenzen, Add-ons und Nutzungsberichte |
| Copilot Lizenz-Pflicht-Checker | Basislizenzen, primäres Exchange-Online-Postfach, Apps, Teams-Transkription, Office Feature Updates, WSS und Sonderpostfächer |
| Copilot ROI-Rechner | Pilotdaten, Adoption, Zeitwert, Datenfreigaben, Purview und Updatekanal |

## Umsetzung im Plugin

- `m365_best_practice_catalog.json` enthält jetzt `controls` je Review-Domäne und `module_checks` je Tool-Key.
- Der Katalog enthält ab `1.27.0` zusätzliche Quellen und Prüfpunkte zu Endpoint-Änderungen, Gruppenlizenzierungsgrenzen, Copilot-App-/Netzwerkanforderungen und Backup-PAYG.
- `templates/landing.php` rendert pro Tool maximal zwei konkrete Prüfpunkte zusätzlich zu den Review-Chips.
- `license_audit_checklist.json` enthält neue Punkte für Conditional-Access-Planung, Defender-Mail-Schutz, M365-Endpoints, Endpoint-Änderungsprozess, Copilot-Setup sowie Power-Platform-Requests und Dataverse-Kapazität.
- `power_platform_governance_rules.json` und `power_platform_capacity_catalog.json` enthalten aktualisierte Request- und Dataverse-Capacity-Regeln.
- `CMS_M365CALCULATOR_Power_Platform_Cost_Calculator::evaluate_power_platform_best_practices()` bewertet hohe Request-Last und vorhandene Dataverse-Kapazitätsplanung zusätzlich.
- `CMS_M365CALCULATOR_Admin_Module_Config` bietet je Modul Admin-Felder für Quellenprofil, Endpoint-/Netzwerkpfad, Schutz-/Datenzugriff und Servicegrenzen-/Kapazität.
- Der Adminbereich bietet ab `1.27.0` zusätzlich zentrale Einstellungen, Paketpreise sowie Abopreise & Laufzeiten als eigene Unterpunkte; Modulseiten folgen danach logisch nach Fachkategorie.