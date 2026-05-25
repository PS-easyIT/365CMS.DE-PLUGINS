# Changelog – 365CMS Marketplace

## 3.0.2 – 2026-05-25

- Die Schema-Migration prüft fehlende Spalten jetzt über `INFORMATION_SCHEMA.COLUMNS` statt über `SHOW COLUMNS ... LIKE ?`.
- Dadurch verschwinden MariaDB/PDO-Prepare-Fehler beim Booten wie `Syntax error ... near '?'` für `bs_marketplace_items`.
- Spaltennamen für automatische Migrationen werden vor dem `ALTER TABLE` zusätzlich auf Identifier-Zeichen begrenzt.

## 3.0.1 – 2026-05-17

- PHP-Anforderung auf 8.4 angehoben und Plugin-Version auf `3.0.1` aktualisiert.
- Öffentliche Einreichungsfelder werden längenbegrenzt normalisiert, bevor sie erneut gerendert oder gespeichert werden.
- Public-HTML-Antworten senden `X-Content-Type-Options: nosniff`.
- Rate-Limit-Dateien werden größenbegrenzt gelesen und mit `LOCK_EX` geschrieben.
- Directory-Ansicht überspringt Symlinks und codiert öffentliche Datei-URLs segmentweise.
- Publicsite-Design ruhiger gestaltet: Systemfont, neutrale Flächen, reduzierte Schatten statt generischer Gradient-Optik.

## 3.0.0 – 2026-05-17

- Kompatibilität auf 365CMS 3.0.0 angehoben.
- ZIP-Uploads nach OWASP-Empfehlungen gehärtet: Signatur-/MIME-Prüfung, maximale Paketgröße, maximale Eintragszahl, entpackte Gesamtgröße und Root-Ordner-Validierung.
- Zip-Slip-, absolute Pfad-, Traversal- und Symlink-Einträge in Paketen blockiert.
- Marketplace-Speicherpfade und Directory-Preview mit Realpath-Containment abgesichert.
- Öffentliche Einreichung um Honeypot und Rate-Limit ergänzt.
- Externe Marketplace-URLs auf sichere HTTP(S)-Ziele ohne Credentials und ohne lokale/private Hostnamen begrenzt.

## 1.0.0

- Erste Version mit Marketplace-Verwaltung, ZIP-Paketen und öffentlichen JSON-Feeds.
