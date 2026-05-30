# CMS M365 Matrixen

`cms-m365matrices` kapselt die reinen Microsoft-365-Read-only-Matrixseiten als eigenes Plugin aus:

- `/m365-lizenzmatrix` – Vollpaket-Matrix für Microsoft 365 Business und Enterprise
- `/m365-addon-matrix` – Add-on-Matrix nach Exchange, SharePoint/OneDrive, Teams, Copilot, Security, Purview und Power Platform

Das Plugin legt **keine eigenen Matrix-Tabellen** an. Es verwendet die bestehenden gemeinsamen Tabellen von `cms-m365tools`:

- `cms_m365tools_module_settings`
- `cms_m365tools_module_options`

Die Admin-Tabs `Lizenzmatrix`, `Add-on-Matrix` und `Design` schreiben weiterhin in die globalen Optionsgruppen `matrix-suite`, `matrix-addon` und `matrix-design`. Dadurch bleiben Darstellung, Texte, CTAs und Sichtbarkeit zwischen der bisherigen M365-Tools-Umgebung und dem neuen Matrix-Plugin identisch.

## Steuerbare Außenbereiche

Die Matrix-Tabellen selbst bleiben datengetrieben. Alles darum herum kann im Adminbereich gesteuert werden:

- Texte für Header, Intros, Buttons, Hinweis- und Quellenbereiche
- Sichtbarkeit von Contentheader, Einleitungsbereich, CTA, Druckbutton, Hinweisen und Quellen
- Add-on-spezifische Bereichs-Overline und Paketkarten-/Bereichsheader-Anzeige
- Seitenbreite, seitlicher Innenabstand, Abstand oben und Abschnittsabstände
- Außenfarben für Seitenhintergrund, Flächen, Text, Sekundärtext, Header und Buttons

Die Werte landen in denselben Optionsgruppen wie bisher und werden in den Public-Templates ausschließlich als CSS-Variablen ausgegeben.

## Abhängigkeit

Die Matrix-Runtime nutzt die vorhandenen Katalog-, Settings- und Template-Klassen aus `cms-m365tools`. Wenn `cms-m365tools` parallel aktiv ist, blendet es seine eigenen Matrix-Routen aus, damit `cms-m365matrices` die öffentlichen Matrixseiten besitzt.

## Admin

Der neue Adminpunkt heißt `M365 Matrixen` und pflegt nur die Matrix-Konfiguration. Komplexe Rechner-, Landingpage- und Preisbereiche bleiben im Plugin `cms-m365tools`.