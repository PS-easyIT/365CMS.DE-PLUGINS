# CMS M365 Message Center – Changelog

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
