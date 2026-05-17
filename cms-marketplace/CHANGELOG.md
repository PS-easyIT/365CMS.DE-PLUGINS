# Changelog – 365CMS Marketplace

## 3.0.0 – 2026-05-17

- Kompatibilität auf 365CMS 3.0.0 angehoben.
- ZIP-Uploads nach OWASP-Empfehlungen gehärtet: Signatur-/MIME-Prüfung, maximale Paketgröße, maximale Eintragszahl, entpackte Gesamtgröße und Root-Ordner-Validierung.
- Zip-Slip-, absolute Pfad-, Traversal- und Symlink-Einträge in Paketen blockiert.
- Marketplace-Speicherpfade und Directory-Preview mit Realpath-Containment abgesichert.
- Öffentliche Einreichung um Honeypot und Rate-Limit ergänzt.
- Externe Marketplace-URLs auf sichere HTTP(S)-Ziele ohne Credentials und ohne lokale/private Hostnamen begrenzt.

## 1.0.0

- Erste Version mit Marketplace-Verwaltung, ZIP-Paketen und öffentlichen JSON-Feeds.
