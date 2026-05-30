# CMS M365 Linkcollection

Kuratierte Link-Sammlung für Microsoft-365-Blogs, MVP-Sites, Community-News und Tools. Der initiale Import enthält zusätzlich per Webrecherche geprüfte offizielle Microsoft-Blogs und internationale Community-Blogs zu Intune, Entra, Windows 365, Security und Modern Workplace.

## Stand

- Version: `1.0.1`
- Public-Route: `/m365-sites-blogs`
- Erstimport: 30. Mai 2026

## Funktionen

- Kategorien für MVPs, Community-/News-Sites, offizielle Microsoft-Blogs, internationale Community-Blogs und Open-Source-/Community-Tools
- Einträge mit Titel, Untertitel/Schwerpunkt, URL, Bild, Tags und Sortierung
- Optionale Verknüpfung zu `cms-companies`, `cms-speakers` und `cms-experts`
- Public-Cards, Tabelle und PHINIT-Sidebar bevorzugen automatisch das verknüpfte Speaker-/Expert-Foto bzw. Company-Logo vor dem eigenen Eintragsbild
- Zusatzbuttons auf der Linkcollection-Übersicht, wenn Company/Speaker/Expert hinterlegt und das jeweilige Plugin aktiv ist
- Konfigurierbare Public-Ausgabe mit Cards, Tabelle, sichtbaren Spalten, Farben, Bildhöhen und Abständen
- Bearbeitbare Public- und Sidebar-Texte über eigenen Admin-Content-Tab; Header-Meta-Zeilen werden nicht ausgegeben
- PHINIT-Startseiten-Sidebar-Widget mit rotierenden Linkkarten, Bild oder Initialen-Platzhalter; Höhe, Aussehen und Aktivierung sind einstellbar

## Public-Ausgabe

Die Übersicht wird unter `/m365-sites-blogs` ausgegeben. Alternativ ist `/m365-linkcollection` als technische Fallback-Route verfügbar.

## Admin

Im Admin-Menü erscheint `M365 Links`. Dort können Einträge gepflegt, Texte bearbeitet und die Darstellung konfiguriert werden.
