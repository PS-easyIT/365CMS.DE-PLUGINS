# CMS M365 Matrixen

`cms-m365matrices` stellt die reinen Microsoft-365-Lizenzmatrixseiten als eigenes Plugin bereit.

## Öffentliche Routen

- `/m365-lizenzmatrix` – Vollpaket-Matrix
- `/m365-addon-matrix` – Add-on-Matrix

## Datenbasis

Das Plugin nutzt die Runtime und gemeinsamen Options-/Settings-Tabellen von `cms-m365tools`. Es legt keine separaten Matrixdaten an.

## Admin

Der Adminpunkt `M365 Matrixen` enthält Tabs für:

- Lizenzmatrix: Texte, CTAs und Sichtbarkeit der Vollpaket-Seite
- Add-on-Matrix: Texte, Bereichs-Overlines, CTAs und Sichtbarkeit der Add-on-Seite
- Design: Außenlayout, Breiten, Abstände, Farben und globale Sichtbarkeitsdefaults

## Kompatibilität

Wenn `cms-m365matrices` aktiv ist, delegiert `cms-m365tools` die Matrix-Routen, Matrix-Registry-Einträge und den Matrix-Adminpunkt an dieses Plugin.
