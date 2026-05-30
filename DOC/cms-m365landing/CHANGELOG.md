# CMS M365 Landing – Changelog

## 1.0.6 – 2026-05-30

- Card-CTA `zum Bereich ->` sitzt jetzt rechts unten in jeder Bereichscard.

## 1.0.5 – 2026-05-30

- Bereichscards werden jetzt selbst als Link gerendert, nicht nur mit innerem Link.
- Ziel-URLs ohne führenden Slash und leere Ziel-URLs mit vorhandenem Slug werden automatisch auf öffentliche Pfade normalisiert.
- Dezenter CTA-Text `zum Bereich ->` wird für jede verlinkbare Card sichtbar ausgegeben.

## 1.0.4 – 2026-05-30

- Content-Header-Bild wird links neben Seitentitel, Untertitel und Einleitung angezeigt.
- Header-Bildhöhe ist im Adminbereich über `Header-Bildhöhe in px` steuerbar.
- Bildbreite passt sich proportional an die Höhe an; Headerbilder werden nicht mehr beschnitten.

## 1.0.3 – 2026-05-30

- Bereichscards sind jetzt vollflächig anklickbar, wenn ein Ziel-Link hinterlegt ist.
- Dezenter CTA-Hinweis `Zum Bereich →` je verlinkter Card ergänzt.
- Hover- und Fokuszustände für klickbare Cards verbessert.

## 1.0.2 – 2026-05-30

- Header-Abstand greift nun auch bei geladenen M365-Tools-Basisstyles mit `!important`-Regeln.
- Header-Button-Linkfelder zeigen wieder die Standardziele `/m365-lizenzmatrix` und `/m365-addon-matrix`.
- Numeric-Settings behalten Defaultwerte, wenn ältere Installationen noch leere Werte enthalten.

## 1.0.1 – 2026-05-30

- Headerbild im Content Header ergänzt, inklusive Mediathek-Auswahl.
- Drei Public-Layouts und steuerbare Header-/Footer-Abstände ergänzt.
- Adminformular zeigt Standard-Slugs und Ziel-URLs für die M365-Pluginseiten an.
- Titeltypografie im Hero verkleinert und auf volle Card-Breite ausgerichtet.

## 1.0.0 – 2026-05-30

- Neues Plugin `cms-m365landing` erstellt.
- Zentrale Public Landingpage für M365 Matrixen, Azure Services, Tutorials und M365 Tools.
- Adminsteuerung für Cards, Texte, Sichtbarkeit und Design.
- Mediathek-Bilder pro Card mit Icon-Fallback.
- Registry-Eintrag und Update-Manifest ergänzt.
