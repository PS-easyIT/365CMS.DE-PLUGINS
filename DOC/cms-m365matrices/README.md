# CMS M365 Matrixen

`cms-m365matrices` stellt die reinen Microsoft-365-Lizenzmatrixseiten als eigenes Plugin bereit.

## Öffentliche Routen

- `/m365-lizenzmatrix` – Vollpaket-Matrix
- `/m365-addon-matrix` – Add-on-Matrix
- `/m365-copilot-matrix` – Copilot-Lizenzmatrix

## Datenbasis

Das Plugin besitzt eigene Runtime-Klassen, lokale JSON-Kataloge, Public-Templates und Assets. Für Konfigurationen nutzt es weiterhin die gemeinsamen Options-/Settings-Tabellen `cms_m365tools_module_settings` und `cms_m365tools_module_options`, damit bestehende Matrix-Optionen erhalten bleiben.

## Admin

Der Adminpunkt `M365 Matrixen` enthält Tabs für:

- Lizenzmatrix: Texte, CTAs und Sichtbarkeit der Vollpaket-Seite
- Add-on-Matrix: Texte, Bereichs-Overlines, CTAs und Sichtbarkeit der Add-on-Seite
- Copilot-Matrix: Texte, Bereichs-Overlines, CTAs und Sichtbarkeit der Copilot-Seite
- Inhaltsverzeichnis: Sichtbarkeit, Überschrift, Spaltenzahl, Textgröße und einzeilige Darstellung der Matrix-Sprungnavigationen
- Design: Außenlayout, Breiten, Abstände, Farben und globale Sichtbarkeitsdefaults

Die Copilot-Matrix besitzt zusätzlich eigene Design-Overrides für Header-Stil, Ausrichtung, Buttonlayout, Breiten, Abstände, Farben und Inhaltsverzeichnis. In der Admin-Übersicht gibt es für jede Publicseite eine Schnelllink-Karte zum Öffnen und Bearbeiten.

## Kompatibilität

Seit `cms-m365tools` `3.0.4` sind Matrix-Routen, Matrix-Registry-Einträge, Matrix-Adminfelder, Templates, JSON-Kataloge und die Matrix-Normalisierung vollständig aus `cms-m365tools` entfernt. `cms-m365matrices` ist alleiniger Besitzer der reinen Matrixseiten.
