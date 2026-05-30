# Changelog – CMS M365 Matrixen

## 1.1.9 – 2026-05-30

- Fehlende Standardwerte für den Add-on-Kontakt-/Lizenzcheck-CTA werden beim Installer nachgezogen.
- Add-on-Matrix behandelt leere gespeicherte Sichtbarkeitswerte wieder als Standardwert.
- Kontaktbutton ist damit in der Add-on-Übersicht im Standard aktiv, ohne bewusst deaktivierte Werte zu überschreiben.

## 1.1.8 – 2026-05-30

- Erste Zeilen der Microsoft-365-Lizenzmatrix fachlich auf Basis offizieller Microsoft-Quellen ausgebaut.
- Zellquellen für Zielgruppe, Benutzerlimit, Web-/Desktop-Apps, Shared Computer Activation, Exchange-Mailbox, Mailboxgröße und Archivpfade ergänzt.
- Add-on-Zellen der Lizenzmatrix mit Deep-Links zu den passenden Bereichen der Add-on-Matrix versehen.
- Public-Renderer unterstützt optionale Zell-Links ohne Änderung der bestehenden Matrix-Grundstruktur.

## 1.1.7 – 2026-05-30

- Admin-Sidebar-Menü auf einen direkten Top-Level-Eintrag reduziert.
- Eigenes aufklappendes Untermenü entfernt, damit `M365 Matrixen` analog zu `M365 Azure` als direkter Menüpunkt erscheint.

## 1.1.6 – 2026-05-30

- Kontakt-/Lizenzcheck-Buttons auf Lizenzmatrix, Add-on-Matrix und Copilot-Matrix optisch vereinheitlicht.
- CTAs nutzen jetzt ein dezentes Orange im Normalzustand und ein dunkleres Orange mit weißer Schrift im Hover-/Fokuszustand.
- Kontaktbuttons bleiben auf allen Matrixseiten einzeilig ohne Textumbruch.

## 1.1.5 – 2026-05-30

- Copilot-Kontakt-/Lizenzcheck-Button aus dem allgemeinen Introbereich in den ersten Paketbereich verschoben.
- Button sitzt nun oben rechts in der ersten Copilot-Area-Card, analog zur Matrix-/Paketlogik.
- CTA optisch zurückgenommen als Secondary-Button und mit einzeiliger Darstellung ohne Textumbruch gehärtet.
- Admin-Hilfetext zur Copilot-CTA-Position aktualisiert.

## 1.1.4 – 2026-05-30

- Copilot-Paketübersichten in allen Bereichen mit sichtbaren Preislabels ergänzt.
- Business- und Enterprise-Spalten zeigen nun direkt `15,60–18,20 € / Monat` bzw. `26,00 € / Monat` statt Platzhalter.
- Copilot Chat, Studio Teams und Copilot Studio Standalone zeigen in Karten und Tabellenköpfen `Enthalten`, `Enthalten/begrenzt` bzw. `Credits/PAYG`.
- Public-Renderer der Copilot-Matrix unterstützt nun frei formatierte `price_label`-Angaben zusätzlich zu numerischen Monatswerten.

## 1.1.3 – 2026-05-30

- Copilot-Matrix mit weiteren offiziellen Microsoft-/Support-Quellen ergänzt.
- Neue Matrixzeilen zu deutschen Preis-/Planhinweisen, Business-300-Nutzer-Grenze, privaten KI-Limits, Standard- vs. Prioritätszugriff und GPT-5.X-Hinweisen ergänzt.
- Websuche-Governance inklusive Admin-/User-Steuerung, generierten Bing-Abfragen, EUDB-/DPA-Hinweisen und Audit-Kontext erweitert.
- Agents-/Copilot-Studio-Bereich um Azure-Abonnement/PAYG, enthaltene B2E-Nutzung mit Microsoft 365 Copilot, zusätzliche Copilot-Credit-Raten, Quotas und 125%-Enforcement ergänzt.
- Rollout-Bereich um Pinning/App-Zugriff für Copilot Chat, Teams-App-Policies und Kanal-Governance erweitert.

## 1.1.2 – 2026-05-30

- Admin-Tab-Inhaltsbereich auf volle verfügbare Breite erweitert.
- Künstliche Maximalbreiten der Einstellungs-Card und Formularfelder entfernt; die 25px Innenabstände links und rechts bleiben über die Admin-Shell erhalten.

## 1.1.1 – 2026-05-30

- Copilot-Matrix um eigene Design-Overrides für Header, Buttons, Breiten, Abstände, Farben und Inhaltsverzeichnis erweitert.
- Zusätzliche Copilot-Texte für Hinweisblock, Quellenintro und Drucken-Button ergänzt.
- Adminbereich an das `cms-events`-Layout angelehnt: 25px Innenabstand, Panel-Header, Publicseiten-Schnelllink-Karten und responsive Aktionen.
- Schnelllink-Übersicht für Lizenzmatrix, Add-on-Matrix und Copilot-Matrix ergänzt.

## 1.1.0 – 2026-05-30

- Neue Public-Route `/m365-copilot-matrix` für eine eigenständige Microsoft Copilot Lizenzmatrix ergänzt.
- Neue Datenquelle `readonly_copilot_matrix.json` mit offiziellen Microsoft-/Support-Quellen zu Copilot Chat, Microsoft 365 Copilot, Copilot Studio, Agents, Datenschutz, App-Funktionen, Quotas und Copilot Credits ergänzt.
- Public-Template und Admin-Tab `Copilot-Matrix` für steuerbare Headertexte, CTAs, Sichtbarkeit, Hinweise und Quellen ergänzt.
- Frontend-Body-Class, SEO-Texte und Admin-Schnellzugriff für die Copilot-Matrix ergänzt.

## 1.0.0 – 2026-05-30

- Neues eigenständiges Plugin für die reinen M365 Lizenz- und Add-on-Matrixen ergänzt.
- Öffentliche Routen `/m365-lizenzmatrix` und `/m365-addon-matrix` aus der M365-Tools-Matrix-Runtime angebunden.
- Eigener Adminpunkt `M365 Matrixen` mit Tabs für Lizenzmatrix, Add-on-Matrix und Design umgesetzt.
- Steuerung für Design, Layout, Farben, Breiten und Texte außerhalb der Matrix-Tabellen ergänzt.
- Gemeinsame Speicherung in `cms_m365tools_module_options` (`matrix-suite`, `matrix-addon`, `matrix-design`) beibehalten.