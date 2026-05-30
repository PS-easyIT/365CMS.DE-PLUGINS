# CMS M365 Matrixen

`cms-m365matrices` kapselt die reinen Microsoft-365-Read-only-Matrixseiten als eigenes Plugin aus:

- `/m365-lizenzmatrix` – Vollpaket-Matrix für Microsoft 365 Business und Enterprise
- `/m365-addon-matrix` – Add-on-Matrix nach Exchange, SharePoint/OneDrive, Teams, Copilot, Security, Purview und Power Platform
- `/m365-copilot-matrix` – Copilot-Lizenzmatrix für Microsoft Copilot, Copilot Chat, Microsoft 365 Copilot und Copilot Studio

Das Plugin legt **keine eigenen Matrix-Tabellen** an. Es verwendet die bestehenden gemeinsamen Tabellen von `cms-m365tools`:

- `cms_m365tools_module_settings`
- `cms_m365tools_module_options`

Die Admin-Tabs `Lizenzmatrix`, `Add-on-Matrix`, `Copilot-Matrix`, `Inhaltsverzeichnis` und `Design` schreiben weiterhin in die globalen Optionsgruppen `matrix-suite`, `matrix-addon`, `matrix-copilot`, `matrix-toc` und `matrix-design`. Dadurch bleiben Darstellung, Texte, CTAs und Sichtbarkeit zwischen der bisherigen M365-Tools-Umgebung und dem neuen Matrix-Plugin identisch.

## Steuerbare Außenbereiche

Die Matrix-Tabellen selbst bleiben datengetrieben. Alles darum herum kann im Adminbereich gesteuert werden:

- Texte für Header, Intros, Buttons, Hinweis- und Quellenbereiche
- Sichtbarkeit von Contentheader, Einleitungsbereich, CTA, Druckbutton, Hinweisen und Quellen
- Add-on-spezifische Bereichs-Overline und Paketkarten-/Bereichsheader-Anzeige
- Matrix-Inhaltsverzeichnisse mit Überschrift, maximaler Spaltenzahl, Textgröße und einzeiliger Darstellung
- Seitenbreite, seitlicher Innenabstand, Abstand oben und Abschnittsabstände
- Außenfarben für Seitenhintergrund, Flächen, Text, Sekundärtext, Header und Buttons
- Copilot-spezifische Design-Overrides für Header, Buttons, Breiten, Abstände, Farben und Inhaltsverzeichnis

Der Adminbereich enthält außerdem eine Schnelllink-Übersicht für jede Publicseite. Von dort kann jede Matrix direkt geöffnet oder der passende Einstellungs-Tab bearbeitet werden.

Die Werte landen in denselben Optionsgruppen wie bisher und werden in den Public-Templates ausschließlich als CSS-Variablen ausgegeben.

## Abhängigkeit

Die Matrix-Runtime, JSON-Kataloge, Public-Templates und Assets liegen vollständig in `cms-m365matrices`. Das Plugin nutzt nur weiterhin die gemeinsamen Tabellen `cms_m365tools_module_settings` und `cms_m365tools_module_options`, damit bestehende Matrix-Optionen erhalten bleiben.

## Admin

Der neue Adminpunkt heißt `M365 Matrixen`, orientiert sich optisch am `cms-events`-Adminlayout und pflegt nur die Matrix-Konfiguration. Komplexe Rechner-, Landingpage- und Preisbereiche bleiben im Plugin `cms-m365tools`.