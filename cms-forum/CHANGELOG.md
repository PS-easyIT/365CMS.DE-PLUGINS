# Changelog

## 3.0.0 - 2026-05-17

### Security
- CSRF-Token-Ausgaben in Admin-, Frontend- und Member-Views mit `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` gehärtet.
- Inline generierter Reply-CSRF-Token in der Thread-Ansicht sicher escaped.
- Poll-Prozentwerte auf 0 bis 100 begrenzt, bevor sie in CSS-Breiten ausgegeben werden.

### Kompatibilität
- Plugin-Version und Update-Metadaten auf 365CMS 3.0.0 aktualisiert.
- Asset-URL-Ausgaben in der Hauptdatei mit explizitem Attribute-Escaping gehärtet.
