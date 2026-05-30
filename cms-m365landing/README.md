# CMS M365 Landing

`cms-m365landing` stellt eine zentrale, öffentlich erreichbare Landingpage für M365-Inhalte bereit. Die Seite bündelt Matrixen, Azure Services, Tutorials und die M365 Tools in administrierbaren Cards.

## Features

- Öffentliche Route standardmäßig unter `/m365`
- Content Header mit Overline, Titel, Intro und zwei Buttons
- Drei Matrix-Cards nebeneinander für M365, Add-ons und Copilot
- Dezenter optischer Trenner zwischen Matrixen und weiteren Bereichen
- Bereiche für Azure Services, Tutorials und weitere M365-Themen
- Tool-Sammlung mit maximal drei Cards pro Reihe
- Jede Card steuerbar: Bereich, Titel, Kurzzeile, Beschreibung, Icon, Mediathek-Bild, Link, Button-Text, Sortierung und Status
- Design steuerbar: Farben, Breite, Abstände, Radius, Icongröße und Bildhöhe
- Adminbereich als direkter Sidebar-Menüpunkt `M365 Landing`

## Datenbank

Das Plugin legt zwei Tabellen an:

- `cms_m365landing_settings` – globale Texte, Sichtbarkeit und Designwerte
- `cms_m365landing_cards` – Matrix-, Bereichs- und Tool-Cards

Der Tabellenpräfix wird über die 365CMS-Datenbankklasse ermittelt.

## Abhängigkeiten

Das Plugin funktioniert eigenständig. Für ein konsistentes Frontend lädt es – falls vorhanden – die Public-Styles der bestehenden M365-Tools/Matrixen nach.
