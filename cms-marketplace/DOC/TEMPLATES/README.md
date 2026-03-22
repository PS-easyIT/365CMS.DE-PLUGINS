# Paket-Templates

Diese Vorlagen sind dafür gedacht, neue Marketplace-Pakete schnell vorzubereiten.

## Enthalten

- `standard-plugin/`
  - Vorlage für ein normales 365CMS-Plugin
  - inklusive Plugin-Bootstrap, Changelog und Marketplace-Metadaten

- `cms-update-package/`
  - Vorlage für ein 365CMS-Core- bzw. CMS-Update-Paket
  - inklusive zentralem Manifest, Update-Datei und Marketplace-Metadaten

## Vorgehen

1. passenden Vorlagenordner kopieren
2. Beispielwerte ersetzen
3. Version, Changelog, URLs, Anforderungen und SHA-256 anpassen
4. Paket als ZIP mit korrektem Root-Ordner bauen
5. im `cms-marketplace` Admin hochladen oder als Referenz für neue Releases verwenden
