# Changelog – CMS 365NETWORK

## 1.0.2 – 2026-05-27

- Speicherbug behoben: Beim Speichern eines einzelnen Admin-Tabs bleiben alle Einstellungen anderer Tabs erhalten.
- Checkboxen werden weiterhin korrekt pro aktivem Tab gespeichert, ohne inaktive Tab-Werte zu überschreiben.
- Public-Vorschauen für Events, Speaker, Firmen und Experten erhalten kanonische Detail-URLs mit Fallback-Slug-Generierung.
- Preview-Bilder mit festen `width`/`height`-Attributen ergänzt, um Layout-Shift zu reduzieren.
- Audit auf unsichere Funktionen, `ORDER BY RAND`, Inline-Style-Ausreißer und 365CMS-Kompatibilität für die geänderten Bereiche durchgeführt.

## 1.0.1 – 2026-05-27

- Analytics-Tab ergänzt.
- Optionaler Matomo-/SEO-Analyse-Code kann hinterlegt und in `head` oder `body_end` ausgegeben werden.
- Ausgabe ist strikt auf die 365NETWORK-Public-Site begrenzt: interne Landingpage-Route oder Root der konfigurierten Zusatzdomain.
- Andere CMS-Seiten laden diesen Code nicht.

## 1.0.0 – 2026-05-27

- Neues Plugin `cms-365network` erstellt.
- Domainbasierte Landingpage für konfigurierbare Zusatzdomains ergänzt.
- Interne Vorschau-Route `/365network` ergänzt.
- Admin-Einstellungen für Domain, Content, Layout, Sidebar und Bereichskarten umgesetzt.
- Modernes Public-Layout mit Hero, Featured Card, vier Bereichskarten und optionaler Sidebar erstellt.
- Dynamische Vorschauen für kommende Events sowie zufällige Speaker, Firmen und Experten ergänzt.
- Defensive Cross-Plugin-Abfragen mit Plugin-Aktivstatus, Tabellenprüfung und Fallbacks implementiert.
