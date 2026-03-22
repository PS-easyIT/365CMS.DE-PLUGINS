# 365CMS Update-Bereich auf der zentralen Marketplace-Site

## Ziel

Der zentrale Marketplace auf `365cms.de` soll nicht nur Plugins und Themes, sondern auch zentrale 365CMS-Core-Updates bereitstellen.

Diese Dokumentation beschreibt nur das Zielbild. Eine Umsetzung im Haupt-CMS erfolgt später.

## Geplanter Adminbereich im Plugin `cms-marketplace`

Im zentralen Adminbereich soll es später einen eigenen Bereich für `365CMS Updates` geben.

Dort soll ein Administrator zentral:

- eine neue 365CMS-Core-ZIP hochladen
- Versionsdaten pflegen
- Mindestanforderungen hinterlegen
- Changelog und Release-Hinweise setzen
- die neue Version freigeben

## Geplante öffentliche Struktur

```text
/marketplace/
└── core/
    └── 365cms/
        ├── update.json
        ├── 365cms-<version>.zip
        └── changelog.md
```

## Geplante Metadaten in `update.json`

Beispielstruktur:

```json
{
  "slug": "365cms-core",
  "name": "365CMS",
  "type": "core",
  "version": "2.7.0",
  "min_php": "8.4",
  "min_mysql": "8.0",
  "released": "2026-03-22",
  "download_url": "https://365cms.de/marketplace/core/365cms/365cms-2.7.0.zip",
  "changelog_url": "https://365cms.de/marketplace/core/365cms/changelog.md",
  "checksum_sha256": "<sha256>",
  "notes": "Zentrale Core-Version für alle 365CMS Installationen.",
  "critical": false
}
```

## Zielverhalten auf späteren 365CMS-Installationen

Später soll jede 365CMS-Installation optional auf diesen zentralen Feed zeigen können.

Gewünschter Ablauf:

1. Dashboard prüft den zentralen Core-Feed
2. Wenn eine höhere Version verfügbar ist, erscheint ein Hinweis im Dashboard
3. Der Administrator kann das Update herunterladen
4. ZIP wird entpackt
5. Integrität wird per SHA-256 geprüft
6. Core-Dateien werden aktualisiert
7. Optional wird ein Wartungsmodus und ein Rollback-Backup verwendet

## Spätere Core-Anpassungen in 365CMS

Für die spätere Umsetzung im Haupt-CMS werden voraussichtlich Anpassungen an folgenden Bereichen nötig sein:

- `CMS/core/Services/UpdateService.php`
- Dashboard-/Admin-Module für Update-Hinweise
- Download-/Entpack-Logik für Core-Updates
- Backup-/Rollback-Mechanismus
- Wartungsmodus während des Updates

## Sicherheitsanforderungen

Für den späteren Update-Bereich sollten mindestens gelten:

- nur HTTPS
- SHA-256-Pflicht für Core-ZIP-Dateien
- Host-Allowlist für `365cms.de`
- optionale Signaturprüfung in einer späteren Ausbaustufe
- vor dem Update automatisches Backup
- Logging aller zentral verteilten Core-Releases

## Nicht Teil der aktuellen Umsetzung

Aktuell wird nur die Dokumentation angelegt.

Noch nicht umgesetzt sind:

- Admin-UI für Core-Pakete im Plugin
- öffentliche Core-Feed-Erzeugung
- Abruf/Anzeige im 365CMS-Dashboard
- Download-, Entpack- und Update-Prozess im Haupt-CMS
