# Changelog – CMS M365 Calculator

## 1.0.1 – 2026-05-16

- Hub-Landingpage `templates/landing.php` für alle registrierten Rechner-Module ergänzt.
- Tool-Registry auf `register()` mit `key`, `title`, `description`, `icon`, `url`, `category` und `status` umgestellt.
- Shared-Mailbox-Modul als selbstregistrierter `live`-Eintrag eingebunden.
- Inline-SVG-Icon-Helper mit Start-Icons für Mailbox, Lizenz, Calculator, Security, Storage und ROI ergänzt.
- Grid-CSS nach `assets/css/style.css` ausgelagert und Landingpage-Assets selektiv geladen.

## 1.0.0 – 2026-05-16

- Neues Plugin `cms-m365calculator` als modulare Microsoft-365-Rechner-Toolbox angelegt.
- Erstes Modul `shared-mailbox-vs-lizenz` implementiert.
- Entscheidungslogik für Shared Mailbox ohne Lizenz, Zusatzlizenz, Grenzfall, User-Mailbox und Microsoft 365 Group ergänzt.
- PHINIT-konformes Public Template mit `phinit-*` Basis-Komponenten erstellt.
- JSON-Kataloge für Regeln, Szenarien, Lizenzmatrix und Preisannahmen ergänzt.
