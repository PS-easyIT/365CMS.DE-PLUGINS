# CMS M365 Message Center – Changelog

## 1.0.5 – 2026-06-05

- 🟢 Public-Übersichtskarten zeigen pro Message-Center-Meldung einen deutlich sichtbaren Button `Original im M365 Admin Center`.
- 🟢 In der Public-Übersicht sitzt die Karten-Fußzeile jetzt links mit Tags/Badges und rechts mit dem Original-M365-Admin-Center-Button.
- 🟡 Wenn Microsoft Graph keinen `details.externalLink` liefert, wird ein Admin-Center-Fallback aus der Graph-Message-ID erzeugt.
- 🟢 Die maximale Content-Breite der Publicsite ist im Adminbereich einstellbar; Standard ist `1160px`.

## 1.0.4 – 2026-06-05

- 🟢 Public-Übersichtskarten und Detailseiten zeigen den externen Original-Link jetzt eindeutig als Button `Original im M365 Admin Center`.
- 🟡 Der Button nutzt weiterhin den sicher gecachten Graph-`externalLink` und bleibt über die Admin-Option `Externe Microsoft-Links anzeigen` steuerbar.

## 1.0.3 – 2026-06-05

- 🟢 Admin-Schalter ergänzt, um Detailseiten, Info-Kachelbereich mit Cache-/Aktualisierungsdaten, Filterbereich, Auszüge und externe Links unabhängig zu aktivieren/deaktivieren.
- 🟢 Drei Public-Layouts ergänzt: `Standard`, `Kompakt` und `Liste`.
- 🔴 Public-Badges sind auf maximal 4px Radius begrenzt; Message-Center-Nachrichtenkarten nutzen maximal 2px Radius.
- 🟡 Detailrouten leiten bei deaktivierten Detailseiten zurück zur Übersicht und Archivkarten entfernen dann Detail-Links sauber.

## 1.0.2 – 2026-06-05

- 🔴 Public-Abstand zwischen Theme-Header/Footer und Message-Center-Content auf maximal 25px begrenzt.
- 🟢 Archivkarten sind über Titel und Button anklickbar und führen auf eine Detailseite je Meldung.
- 🟢 Übersichts-Auszüge werden nach 200 Wörtern mit `...` gekürzt.
- 🟢 Detailroute `/{route_slug}/{messageId}` ergänzt und zeigt Body, Metadaten, Services, Tags, Zeitpunkte und externen Microsoft-Link.
- 🟡 Cache-Schema um `body_content` erweitert, damit neue Abrufe den vollständigen Klartext der Graph-Meldung speichern.

## 1.0.1 – 2026-06-05

- 🎨 Publicsite mit modernem Hero, Statuspanel, Filterkopf und klareren Meldungskarten überarbeitet.
- 🇩🇪 Microsoft Graph Message Center wird standardmäßig mit `Accept-Language: de-DE` abgerufen.
- ⏱️ Automatischer Abruf per `cron.php` ergänzt: einmal täglich ab 12:00 Uhr über den stündlichen Cron-Hook.
- 🔁 Gemeinsamer Refresh-Service für manuellen Admin-Abruf und Cron-Abruf ergänzt.
- 🧾 Adminbereich zeigt zusätzlich den letzten Cron-Abruf an.

## 1.0.0 – 2026-06-05

- 🟢 Eigenständiges Plugin `cms-m365messagecenter` erstellt.
- 🔐 Microsoft-Graph-Abruf läuft ausschließlich im Adminbereich und nutzt Application Permission `ServiceMessage.Read.All`.
- 🟠 Publicsite liest nur aus lokalem Cache, damit Besucher keine Graph-Requests auslösen.
- 🟢 Public-Archiv mit Suche, Service-/Kategorie-Filter, Sortierung, Richtung und Pagination ergänzt.
- 🛡️ Graph-Client mit festen Microsoft-Hosts, HTTPS-only, ohne Redirects, kurzen Timeouts, Response-Limit und redigierten Fehlerlogs gehärtet.
