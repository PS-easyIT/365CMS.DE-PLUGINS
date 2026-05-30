# CMS M365 Linkcollection

`cms-m365linkcollection` verwaltet kuratierte Microsoft-365-Links für Blogs, MVP-Sites, Newsquellen und Tools. Der initiale Seed enthält zusätzlich per Webrecherche geprüfte offizielle Microsoft-Blogs und internationale Community-Blogs zu Intune, Entra, Windows 365, Security und Modern Workplace.

## Public

- Haupt-Route: `/m365-sites-blogs`
- Fallback-Route: `/m365-linkcollection`
- Ansichten: Cards, Tabelle oder beide – Auswahl erfolgt im Admin
- Filter: Kategorie und Suchbegriff
- Zusatzbuttons: Company/Speaker/Expert nur auf dieser Übersicht, wenn verknüpft und das Zielplugin aktiv ist
- Bilder: verknüpfte Speaker-/Expert-Fotos oder Company-Logos werden vor dem eigenen Eintragsbild verwendet

## Admin

- Menü: `M365 Links`
- Einträge mit Bild-URL, Alt-Text, Untertitel/Schwerpunkt, Tags, Company-/Speaker-/Expert-Verknüpfung, Sortierung, Status und Widget-Markierung
- Content-Tab für Seitentitel, Intro, Filter-/Ansichts-/Tabellen-/Pagination-Texte und Sidebar-Labels
- Design- und Anzeigeeinstellungen inklusive Farben, Bildhöhen, sichtbaren Tabellenspalten, Abständen und Sidebar-Rotation

## PHINIT-Integration

Das PHINIT-Theme kennt den Sidebar-Order-Key `linkcollection`. Wenn das Plugin aktiv ist, rendert `CMS_M365LINKCOLLECTION_Widget::render_phinit_sidebar_widget()` ein rotierendes Sidebar-Widget. Widget-Aktivierung, Mindesthöhe, Bildhöhe, Darstellung und sichtbare Inhalte sind über den Adminbereich einstellbar.
