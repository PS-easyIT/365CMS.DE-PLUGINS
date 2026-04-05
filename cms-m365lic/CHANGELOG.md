# Changelog

## [Unreleased] – 2026-04-04

- PDF-Export auf serverseitige Export-Snapshots umgestellt, sodass keine requestbasierten JSON-Auswertungen mehr direkt in das PDF-Rendering fließen.
- Export-Varianten werden im Frontend strikt auf feste Konstanten gemappt; Whitelabel-/Partnertexte werden vor dem Rendern zusätzlich normalisiert.
- Der PDF-Renderer arbeitet ohne dateibasierte Logo-Auflösung und der letzte Snyk-Befund für `cms-m365lic` ist damit geschlossen.

## 1.5.0 – 2026-03-18

- Public- und EU-Vergleichs-Frontend visuell näher an das Theme `cms-phinit` gezogen: dunkler Hero, Navy-/Gold-/Teal-Akzente, prägnantere Tabellenköpfe und editorialere Karten-/Button-Stile
- Dynamische Design-Tokens werden jetzt zentral über den Frontend-Controller in den Head injiziert; die früheren Inline-`<style>`-Blöcke in den Public-Templates entfallen

## 1.4.9 – 2026-03-17

- EU-Alternativen für zusätzliche Add-on- und Service-Kategorien ergänzt: `Endpoint Security & XDR`, `MDM & UEM`, `Identity & Access`, `Enterprise Projektmanagement`, `Low-Code & Automatisierung`, `Datenanalyse & BI`, `DMS/Archivierung/Compliance`, `Enterprise Cloud-Telefonie`
- Generative-KI-Liste gegenüber Copilot mit `Aleph Alpha`, `Mistral AI` und `DeepL Enterprise / Pro` nachgeschärft
- Seed-Logik für Alternativen erweitert, damit Bestandsinstallationen fehlende Default-Einträge automatisch dazubekommen statt nur leere Listen zu befüllen

## 1.4.8 – 2026-03-17

- Admin-Tab `Alternativen` um zwei eigene Bereiche erweitert: `EU-Alternativen` und `Europäische KI- & Copilot-Alternativen`
- Public-`EU-Vergleich` und die optionalen `EU-Alternativen` unter der Standard-Auswertung beziehen ihre Anbieter jetzt aus den Plugin-Einstellungen statt aus fest im Code verdrahteten Listen
- Neue EU-Kategorie `KI & Copilot` ergänzt, damit Copilot-/KI-Bedarfe auch europäische Gegenoptionen anzeigen können

## 1.4.7 – 2026-03-17

- Paketverwaltung im Admin so umgestellt, dass gepflegte Preise nun als Referenzwert für `1 Jahr · monatliche Zahlung (+5%)` interpretiert werden; Jahreszahlung und flexible Monatslaufzeit werden daraus abgeleitet
- Paketübersicht im Admin um eine direkte Monats-/Jahresübersicht pro Bereich (`Public`, `Member`, `Spezial`) erweitert
- Spezialpreise für alle aktuell im Katalog vorhandenen Produkte aus der gelieferten Jahrespreisliste heruntergerechnet und als `group_price` im Seed-Katalog hinterlegt

## 1.4.6 – 2026-03-17

- Öffentliche Seite `EU-Vergleich` von der manuellen Vorauswahl auf denselben 3-Schritt-Wizard wie die Standard-Auswertung umgestellt, inklusive mehrerer Bedarfsgruppen pro Anfrage
- Fehlende Public-CSRF-Tokens im EU-Vergleich ergänzt, damit `form_guard` und der plugin-spezifische Auswertungstoken sauber zusammenspielen und der 403-Fehler verschwindet
- Standard-Auswertung um getrennte Schalter für normale Alternativen und `EU-Alternativen` erweitert; zusätzlich lässt sich nun steuern, wie viele Treffer pro Kategorie/Bereich angezeigt werden (Standard 1, maximal 10)

## 1.4.5 – 2026-03-17

- Neue Public-Site `EU-Vergleich` ergänzt, um Microsoft-365-Pläne mit europäischen All-in-One- und Best-of-Breed-Anbietern zu vergleichen
- Öffentliche Navigation um einen eigenen Menüpunkt `EU-Vergleich` neben der regulären Auswertung erweitert
- Vergleichslogik für M365 vs. europäische Anbieter mit Summen für `Gesamtkosten M365`, `Gesamtkosten Alternativen` und Preisdelta ergänzt

## 1.4.4 – 2026-03-17

- Alternativanbieter anhand der nachgereichten Korrekturliste ergänzt und fehlende Monats-/Jahrespreise für Dropbox, Slack, Zoho Mail und Zoho Projects nachgezogen
- Storage-Vergleich um Nextcloud-nahe Optionen erweitert, darunter `Hetzner Storage Share NX11` und `IONOS Managed Nextcloud 3 TB+`
- Plugin-Dokumentation und Seed-Daten wieder auf denselben Alternativen-Stand synchronisiert

## 1.4.3 – 2026-03-17

- Preisbasis der Microsoft-Produkte im Paketkatalog gemäß bereitgestellter Referenzliste aus dem Anhang aktualisiert
- Betroffen sind Exchange, SharePoint, OneDrive, Microsoft 365/Office 365, Teams, Copilot, Intune, Power Platform, Project, Entra und Defender
- Öffentliche Seed-Preise für die Auswertung damit an den aktuellen gewünschten Katalogstand angeglichen

## 1.4.2 – 2026-03-17

- Kuratierte Standardliste für Alternativanbieter je Bereich ergänzt: `Mail`, `Office & Produktivität`, `Storage & Dateien`, `Zusammenarbeit & Meetings`, `Projektmanagement`, `Identität & Sicherheit`
- Seed-Daten mit 3 bis 6 Vergleichseinträgen je Bereich auf Basis offiziell recherchierter Pricing-Seiten von Google, Zoho, Proton, Dropbox, Slack, Asana und MeisterTask ergänzt
- Leere Bestandsinstallationen werden bei der Plugin-Initialisierung automatisch mit der neuen Alternativen-Liste vorbefüllt, ohne manuell gepflegte Admin-Einträge zu überschreiben

## 1.4.1 – 2026-03-17

- Optionalen Alternativanbieter-Block unter der M365-Auswertung ergänzt; Aktivierung erfolgt direkt im Rechner per Checkbox neben der Laufzeit
- Neuen Admin-Tab `Alternativen` eingebaut, um Jahres- und Monatspreise je Kategorie/Anbieter separat zu pflegen
- Alternativpreise bewusst ohne prozentuale Billing-Aufschläge umgesetzt: Jahresmodelle nutzen den gepflegten Jahrespreis, Monatslaufzeit den expliziten Monatspreis

## 1.2.0 – 2026-03-15

- PDF-Export auf echten PDF-Download über den 365CMS-PDF-/Dompdf-Stack umgestellt; der frühere HTML-Fallback wird nicht mehr als Scheindownload ausgeliefert
- Public-Wizard von 2 auf 3 Schritte erweitert: `Quick Check`, `Advanced / Expertenoptionen` und `Add-ons & Security`
- Add-on-Katalog um `Microsoft Entra ID P1/P2`, `Defender for Business`, `Defender for Office 365 Plan 1/2` sowie `Defender for Endpoint Plan 1/2` ergänzt und mit EUR-Referenzwerten vorbelegt
- Ergebnis-Erklärungen für Terminalserver-/RDS-Anforderungen präzisiert, damit die Wahl einer Shared-Activation-fähigen Lizenz nachvollziehbar begründet wird
- Public-Frontend räumlicher, freundlicher und guideline-näher gestaltet, ohne die kompakte Bedarfsanalyse aufzugeben

## 1.1.1 – 2026-03-15

- Standardwährung, Seed-Katalog und Darstellung durchgängig auf Euro (EUR / €) vereinheitlicht
- Public-/Member-Frontend mit Hero-Pills, KPI-Karten, klarerer Ergebnishierarchie und besser lesbarer Preispräsentation verfeinert
- Admin-Dashboard, Spezial-User-Ansicht sowie Paket-/Settings-Formulare visuell gestrafft und auf EUR-only-Verwaltung ausgerichtet
- Public-Rechner um das Bedarfsmerkmal `Terminalserver / Shared Activation` erweitert, damit RDS-/Terminalserver-Szenarien gezielt auf passende Enterprise-/SCA-fähige SKUs gematcht werden

## 1.1.0 – 2026-03-15

- Spezial-User-Verwaltung im Admin ergänzt und Spezialseite nur noch für explizit zugewiesene 365CMS-Benutzer freigeschaltet
- Laufzeit-/Zahlungslogik mit drei Modellen eingebaut: jährlich, jährlich monatlich (+5%) und monatlich (+20%)
- Seed-Katalog vollständig mit Startpreisen befüllt und um `pricing_basis` für Benutzer- vs. Fixpreis-SKUs erweitert
- Paketverwaltung, Dashboard, PDF und Frontend auf neue Billing-Logik und Spezialzugriffe umgestellt

## 1.0.3 – 2026-03-15

- Öffentliche Rechnerseite fest auf den Public-Preiskontext verdrahtet; der Preis-Kontext ist im Public-Formular nicht mehr umschaltbar
- Geschützte Member- und Spezialbereiche über den 365CMS-Mitgliederbereich ergänzt (`/member/plugin/m365-license` und `/member/plugin/m365-license-special`)
- Auswertung und PDF-Export leiten den Preiskontext nun serverseitig aus dem Zugriffsbereich ab statt aus manipulierbaren Formularfeldern

## 1.0.2 – 2026-03-15

- Public-POST-CSRF-Flow an den globalen `form_guard` des Routers angepasst
- Seed-Preis-Synchronisierung für Bestandsinstallationen korrigiert, sodass neue Seed-Preise nicht mehr von alten `null`-Werten blockiert werden
- Startpreise für häufige Business-/Teams-Pakete ergänzt und fehlende Preiswarnung im Frontend präzisiert

## 1.0.1 – 2026-03-15

- Public-Rendering auf korrekte Theme-Header/Footer-Integration umgestellt
- Seed-Katalog um zusätzliche Microsoft-365-, Teams-, SharePoint-, OneDrive-, Visio- und Project-Pakete erweitert
- Enthaltene Services für bestehende Seed-Pakete ergänzt und Seed-Synchronisierung für Bestandsinstallationen verbessert

## 1.0.0 – 2026-03-15

- Erstes Release des CMS M365 License Plugins
- Seed-Katalog für Microsoft 365 Basislizenzen, Frontline, Copilot und Add-ons
- Adminbereich für Paketverwaltung und Konfiguration
- Öffentliche Bedarfsanalyse mit festem Public-Kontext
- PDF-Export und Tageslimit-Logik
