# Changelog – CMS M365 Linkcollection

## v1.0.2 — 30. Mai 2026

- M365-Audit-Fix: Admin-CSRF-Fallback arbeitet jetzt fail-closed, falls der Security-Service nicht verfügbar ist.
- Löschdialog übergibt Eintragsnamen als JSON-Literal statt als manuell gequoteten JavaScript-String.

## v1.0.1 — 30. Mai 2026

- Speaker-Verknüpfung inklusive optionalem Speaker-Button im Linkformular ergänzt.
- Bestehende Installationen migrieren `speaker_id`, `show_speaker_button` und den passenden Index automatisch nach.
- Public-Cards, Tabelle und PHINIT-Sidebar verwenden verknüpfte Profil-/Logo-Bilder automatisch vor dem eigenen Eintragsbild: Speaker-Foto → Expert-Foto → Company-Logo → Eintragsbild.
- Kategorie-Chips sind linksbündig ausgerichtet statt zentriert.

## v1.0.0 — 30. Mai 2026

- Initiales Plugin mit Admin-Verwaltung, Public-Übersicht, Startdaten und PHINIT-Sidebar-Widget.
- Seed-Daten nach Webrecherche um offizielle Microsoft-Blogs und internationale Intune-/Entra-/Windows-365-Community-Blogs erweitert.
- Einträge unterstützen Bilder, Untertitel/Schwerpunkt, Tags, Company-/Expert-Verknüpfungen und Widget-Markierung.
- Public-Design, Tabellenanzeige, sichtbare Spalten, Button-Labels und Sidebar-Rotation sind im Admin einstellbar.
- Public-Header ohne Meta-Zeilen; alle sichtbaren Public- und Sidebar-Texte sind im Content-Tab bearbeitbar.
- Beschreibungstexte wurden aus Public-Übersicht und Linkformular entfernt; Abstände zum Theme-Header/Footer sowie Bereichsabstände sind im Admin steuerbar.
