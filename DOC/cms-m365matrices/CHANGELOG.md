# Changelog – CMS M365 Matrixen

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
