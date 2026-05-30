# Changelog – CMS M365 Linkcollection

## v1.0.2 — 30. Mai 2026

| Typ | Bereich | Beschreibung |
|---|---|---|
| 🛡️ security | Admin | CSRF-Fallback auf fail-closed gehärtet, falls `CMS\Security` nicht verfügbar ist. |
| 🛡️ security | Admin | Löschdialog nutzt JSON-Encoding für Eintragsnamen in JavaScript-Kontexten. |

## v1.0.1 — 30. Mai 2026

| Typ | Bereich | Beschreibung |
|---|---|---|
| 🟢 feat | Admin | Speaker-Verknüpfung und optionaler Speaker-Button für Linkeinträge ergänzt. |
| 🟢 feat | Public | Cards, Tabelle und PHINIT-Sidebar bevorzugen verknüpfte Speaker-/Expert-Fotos bzw. Company-Logos vor dem eigenen Eintragsbild. |
| 🟢 feat | Datenbank | Migration ergänzt `speaker_id`, `show_speaker_button` und `idx_speaker` für bestehende Tabellen. |
| 🔵 change | Design | Kategorie-Chips werden linksbündig ausgerichtet. |

## v1.0.0 — 30. Mai 2026

| Typ | Bereich | Beschreibung |
|---|---|---|
| 🟢 feat | Plugin | Neues Plugin `cms-m365linkcollection` für kuratierte Microsoft-365-Links erstellt. |
| 🟢 feat | Datenbank | Kategorien, Linkeinträge, Bilder, Company-/Expert-Verknüpfungen und Einstellungen werden in eigenen Tabellen gepflegt. |
| 🟢 feat | Public | Übersicht `/m365-sites-blogs` mit Card-/Tabellenansicht, Suche, Kategorien und konfigurierbarer Anzeige. |
| 🟢 feat | Admin | Content-Tab für Seitentitel, Intro, Filter-/Tabellen-/Pagination-Labels und Sidebar-Texte ergänzt. |
| 🔵 change | Public | Header-Meta-Zeilen entfernt, Ansichtsschalter ausgeblendet und Abstand zum Theme-Header/Footer adminseitig steuerbar gemacht. |
| 🔵 change | Admin | Beschreibungsfeld aus Linkformular entfernt; Untertitel/Schwerpunkt reicht als Zusatztext. |
| 🟢 feat | PHINIT | Sidebar-Widget kann im Admin deaktiviert sowie in Höhe und Aussehen angepasst werden. |
| 🟢 feat | Seed | Startdaten für Microsoft MVPs Deutschland, Community-/News-Sites, offizielle Microsoft-Blogs, internationale Community-Blogs und Open-Source-/Community-Tools importiert. |
| 🟢 feat | PHINIT | Startseiten-Sidebar-Widget mit rotierenden Links, Bild oder Platzhalter ergänzt. |
