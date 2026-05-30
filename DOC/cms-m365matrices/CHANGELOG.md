# Changelog – CMS M365 Matrixen

## 1.1.6 – 2026-05-30

- Kontakt-/Lizenzcheck-CTAs auf allen Matrixseiten vereinheitlicht.
- Dezenter Orange-Zustand und dunkeloranger Hover mit weißer Schrift ergänzt.
- Einzeilige Darstellung der Kontaktbuttons abgesichert.

## 1.1.5 – 2026-05-30

- Copilot-Kontakt-CTA im Public-Template in den ersten Paketbereich verschoben.
- Button wird oben rechts in der Card, dezent und ohne Textumbruch angezeigt.
- Admin-Beschreibung für die Copilot-CTA-Option an die neue Position angepasst.

## 1.1.4 – 2026-05-30

- Preislabels in den Copilot-Übersichtskarten und Tabellenköpfen ergänzt.
- Business-/Enterprise-Preise sowie enthaltene/PAYG-Hinweise werden nun direkt in jeder Copilot-Area angezeigt.
- Copilot-Public-Template unterstützt `price_label` für nicht rein numerische Preise und Preisbereiche.

## 1.1.3 – 2026-05-30

- Copilot-Lizenzmatrix inhaltlich erweitert: deutsche Preis-/Planhinweise, Business-300-Nutzer-Grenze, private Copilot-KI-Limits und Copilot Chat ohne Add-on.
- Zusätzliche Fakten zu Standard-/Prioritätszugriff, neuesten Modellen, Notebooks/Pages/Create, Work/Web-Grounding und App-Erlebnis ergänzt.
- Datenschutz-/Governance-Abschnitt um Websuche-Policies, generierte Bing-Abfragen, EUDB-/DPA-Abgrenzung und Audit-Hinweise erweitert.
- Agents-/Studio-Abschnitt um Agent Builder, Azure-/PAYG-Abrechnung, enthaltene M365-Copilot-B2E-Nutzung, Copilot-Credit-Raten, Quotas und Enforcement ergänzt.
- Betrieb/Rollout um Copilot-Chat-Pinning, App-Zugriff, Teams-Policies und Kanal-Governance erweitert.

## 1.1.2 – 2026-05-30

- Admin-Tab-Inhaltsbereich nutzt nun die volle verfügbare Breite mit 25px Innenabstand links und rechts.
- Begrenzende Maximalbreiten der Einstellungs-Card und Formularfelder entfernt.

## 1.1.1 – 2026-05-30

- Copilot-Matrix um seitenbezogene Design-Overrides, Inhaltsverzeichnisoptionen und Zusatztexte erweitert.
- Admin-Übersicht mit Publicseiten-Schnelllinks für Lizenzmatrix, Add-on-Matrix und Copilot-Matrix ergänzt.
- Adminlayout an `cms-events` angelehnt und mit 25px Innenabstand im Plugin-Content versehen.

## 1.1.0 – 2026-05-30

- Neue Public-Route `/m365-copilot-matrix` für die Microsoft Copilot Lizenzmatrix ergänzt.
- Copilot-Matrix-Datenquelle mit offiziellen Quellen zu Copilot Chat, Microsoft 365 Copilot, Copilot Studio, Agents, App-Funktionen, Datenschutz, Quotas und Copilot Credits ergänzt.
- Admin-Tab `Copilot-Matrix` für Texte, CTAs, Sichtbarkeit, Hinweise und Quellen ergänzt.
- API-Dokumentation, Datenbank-Mapping und Public-Routen um die Copilot-Matrix erweitert.

## 1.0.6 – 2026-05-30

- Matrix-Tabellen responsiv gehärtet: Tabellen-Wrapper scrollen auf Desktop- und Zwischenbreiten horizontal, statt Inhalte zu quetschen.
- Auf kleinen Viewports bleibt die Kartenansicht der Read-only-Tabellen aktiv und nutzt volle Breite ohne Tabellenüberlauf.

## 1.0.5 – 2026-05-30

- Eigener Admin-Tab `Inhaltsverzeichnis` ergänzt.
- Add-on-Inhaltsverzeichnis ist jetzt steuerbar: Sichtbarkeit, Überschrift, maximale Spaltenzahl, Textgröße und einzeilige Darstellung.
- Hover-Unterstreichungen der Inhaltsverzeichnis-Links werden unterdrückt; Standardlayout zeigt maximal drei Bereiche pro Reihe.

## 1.0.4 – 2026-05-30

- Add-on-Inhaltsverzeichnis typografisch verbessert und auf maximal vier Bereiche pro Reihe begrenzt.
- Responsive Verhalten ergänzt: Desktop vier Spalten, Tablet zwei Spalten, Mobile eine Spalte.

## 1.0.3 – 2026-05-30

- Add-on-Inhaltsverzeichnis kompakter dargestellt: eine Überschrift, reine Bereichstitel und keine Untertitel/Beschreibungen mehr.

## 1.0.2 – 2026-05-30

- Add-on-Matrix zeigt unter dem Header ein Inhaltsverzeichnis mit Sprunglinks zu allen sichtbaren Add-on-Bereichen.
- Add-on-Bereiche besitzen stabile Anker-IDs und Scroll-Abstand, damit Sprungziele nicht direkt am oberen Rand kleben.

## 1.0.1 – 2026-05-30

- Add-on-Matrix blendet den Intro-/Ergebnisbereich nun vollständig aus, wenn dieser Bereich deaktiviert wird; die leere Public-Card entfällt.
- Gespeicherte Header-Titel und Introtexte werden für Public-Template, Theme-Header und SEO-Service synchron verwendet.

## 1.0.0 – 2026-05-30

- Eigenständiges Plugin für reine M365 Lizenz- und Add-on-Matrixseiten erstellt.
- Public-Routen `/m365-lizenzmatrix` und `/m365-addon-matrix` registriert.
- Eigene Matrix-Runtime, lokale JSON-Kataloge, Public-Templates und Assets ergänzt.
- Gemeinsame M365-Tools-Options-Tabellen für bestehende Matrix-Konfigurationen angebunden.
- Adminbereich für Texte, Sichtbarkeit, CTAs, Außenlayout, Farben, Breiten und Abstände ergänzt.
- `cms-m365tools` `3.0.4` entfernt die alten Matrix-Routen, Templates, JSON-Kataloge, Adminfelder und Registry-Einträge vollständig.
