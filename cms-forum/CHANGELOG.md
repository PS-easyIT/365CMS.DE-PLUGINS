# Changelog

## 3.0.2 - 2026-05-31

- Admin-Menüeintrag wird in der Core-Sidebar mit `365CMS | ` vorangestellt, damit 365CMS-Plugins gemeinsam sortiert werden.

## 3.0.1 - 2026-05-17

### Security
- AJAX-Aktionen für Likes, Abonnements, Meldungen und Umfragen auf die registrierten API-Routen synchronisiert und mit expliziten CSRF-Daten versehen.
- JSON-Endpunkte mit `Content-Type: application/json; charset=UTF-8` und `X-Content-Type-Options: nosniff` gehärtet.
- Suchparameter, Datumsfilter, Thread-/Post-Inhalte und Poll-Eingaben serverseitig begrenzt und normalisiert.
- BBCode-URLs gegen riskante Protokolle, Credentials sowie lokale/private Hosts gehärtet.

### Bugfixes
- DSGVO-Export repariert: Das PDO-Statement wird nun korrekt ausgeführt und danach ausgelesen.
- Poll-Optionen werden wieder in die korrekte Spalte `option_text` geschrieben; Votes auf fremde Option-IDs werden abgewiesen.
- Frontend-Markup und JavaScript für Report-Modal, Like-, Subscribe- und Poll-Aktionen sind wieder kompatibel.

### UX & Kompatibilität
- Public-Thread- und Suchansichten stärker escaped, ohne Inline-Handler und mit sauberem Theme-Header/Footer gerendert.
- Update-Metadaten auf PHP 8.4 und 365CMS 3.x.x aktualisiert.

## 3.0.0 - 2026-05-17

### Security
- CSRF-Token-Ausgaben in Admin-, Frontend- und Member-Views mit `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` gehärtet.
- Inline generierter Reply-CSRF-Token in der Thread-Ansicht sicher escaped.
- Poll-Prozentwerte auf 0 bis 100 begrenzt, bevor sie in CSS-Breiten ausgegeben werden.

### Kompatibilität
- Plugin-Version und Update-Metadaten auf 365CMS 3.0.0 aktualisiert.
- Asset-URL-Ausgaben in der Hauptdatei mit explizitem Attribute-Escaping gehärtet.
