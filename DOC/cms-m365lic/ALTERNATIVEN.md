# Alternativanbieter für `cms-m365lic`

Diese Datei dokumentiert die im Plugin `cms-m365lic` vorbelegten Alternativanbieter inklusive der Preise, wie sie aktuell im Plugin gepflegt sind.

## Preislogik

- `Preis 1 Jahr` = Wert für `annual_upfront` und `annual_monthly`
- `Preis monatlich` = Wert für `monthly_flex`
- Für Alternativen werden **keine** prozentualen Aufschläge aus der Microsoft-365-Laufzeitlogik berechnet.
- Fehlt ein Wert, wird im Frontend für dieses Laufzeitmodell kein Preis ausgegeben.

## Mail

| Anbieter | Preis 1 Jahr | Preis monatlich |
|---|---:|---:|
| Google Workspace Business Starter | 6,80 € | 8,10 € |
| Proton Mail Essentials | 6,99 € | 7,99 € |
| Proton Mail Professional | 9,99 € | 10,99 € |
| Zoho Mail Lite | 0,90 € | 1,20 € |
| Zoho Mail Premium | 3,60 € | 4,80 € |

## Office & Produktivität

| Anbieter | Preis 1 Jahr | Preis monatlich |
|---|---:|---:|
| Google Workspace Business Plus | 21,10 € | 25,30 € |
| Google Workspace Business Standard | 13,60 € | 16,20 € |
| Proton Business Suite | 12,99 € | 14,99 € |
| Zoho Workplace Professional | 5,40 € | 6,30 € |
| Zoho Workplace Standard | 2,70 € | 3,60 € |

## Storage & Dateien

| Anbieter | Preis 1 Jahr | Preis monatlich |
|---|---:|---:|
| Dropbox Advanced | 18,00 € | 24,00 € |
| Dropbox Standard | 12,00 € | 15,00 € |
| Google Workspace Business Standard | 13,60 € | 16,20 € |
| Hetzner Storage Share NX11 (1 TB / 3 Nutzer) | 4,29 € | 4,29 € |
| IONOS Managed Nextcloud 3 TB+ (25 Nutzer) | 30,00 € | 30,00 € |
| Proton Business Suite | 12,99 € | 14,99 € |
| Zoho Workplace Professional | 5,40 € | 6,30 € |

## Zusammenarbeit & Meetings

| Anbieter | Preis 1 Jahr | Preis monatlich |
|---|---:|---:|
| Google Workspace Business Standard | 13,60 € | 16,20 € |
| Slack Business+ | 15,00 € | 18,00 € |
| Slack Pro | 6,75 € | 8,25 € |
| Zoho Workplace Professional | 5,40 € | 6,30 € |
| Zoho Workplace Standard | 2,70 € | 3,60 € |

## Projektmanagement

| Anbieter | Preis 1 Jahr | Preis monatlich |
|---|---:|---:|
| Asana Advanced | 24,99 € | 30,49 € |
| Asana Starter | 10,99 € | 13,49 € |
| MeisterTask Business | 24,00 € | 31,00 € |
| MeisterTask Pro | 13,50 € | 17,50 € |
| Zoho Projects Enterprise | 9,00 € | 10,00 € |
| Zoho Projects Premium | 4,00 € | 5,00 € |

## Identität & Sicherheit

| Anbieter | Preis 1 Jahr | Preis monatlich |
|---|---:|---:|
| Dropbox Advanced | 18,00 € | 24,00 € |
| Google Workspace Business Plus | 21,10 € | 25,30 € |
| Proton Business Suite | 12,99 € | 14,99 € |
| Slack Business+ | 15,00 € | 18,00 € |
| Zoho Workplace Professional | 5,40 € | 6,30 € |

## Quelle im Code

Die Werte stammen aktuell aus `CMS_M365LIC_Catalog::default_alternative_offers()` in:

- `cms-m365lic/includes/class-catalog.php`

## Stand

- Dokumentationsstand: `2026-03-17`
- Bezogen auf Plugin-Version: `1.4.6`
