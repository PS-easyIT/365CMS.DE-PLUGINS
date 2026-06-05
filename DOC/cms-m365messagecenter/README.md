# CMS M365 Message Center

## Zweck

`cms-m365messagecenter` stellt Microsoft-365-Message-Center-Meldungen als eigenständiges Plugin bereit. Der Graph-Abruf ist vom M365-Landing-Plugin getrennt und läuft nur im Adminbereich. Die Publicsite nutzt ausschließlich lokal gecachte Daten.

## Features

- Microsoft Graph Message Center Abruf per Client-Credentials-Flow.
- Least-Privilege Application Permission: `ServiceMessage.Read.All`.
- Lokale Cache-Tabelle für schnelle Public-Ausgabe.
- Modernes Public-Archiv mit Hero, Statuspanel, Suche, Service-Filter, Kategorie-Filter, Sortierung, Richtung und Pagination.
- Admin steuerbare Public-Bereiche: Detailseiten, Status-/Cache-Kachelbereich, Filterbereich, Auszüge und externe Links.
- Maximal drei Public-Layouts: `standard`, `compact`, `list`.
- Max. Public-Content-Breite ist im Adminbereich einstellbar; Standard ist `1160px`.
- Übersichtskarten können je Meldung per Button `Original im M365 Admin Center` öffnen; ohne Graph-`externalLink` wird ein Admin-Center-Fallback aus der Graph-ID genutzt.
- Public-Badges haben maximal 4px Radius, Message-Center-Nachrichtenkarten maximal 2px.
- Admin-Dashboard mit Cache-Status, letzten Meldungen und manuellem Refresh.
- Deutscher Graph-Abruf per `Accept-Language: de-DE` als Standard.
- Automatischer Abruf per `cron.php` einmal täglich ab 12:00 Uhr.

## Sicherheit

- Publicsite löst keine externen Requests aus.
- Graph-Requests sind auf feste Microsoft-Hosts beschränkt.
- HTTPS-only, keine Redirects, kurze Timeouts und Response-Limit.
- Secrets werden nicht im Formularwert ausgegeben.
- Alle Public-Ausgaben werden mit `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` escaped.

## Cron-Verhalten

Das Plugin hängt am Core-Hook `cms_cron_hourly` und prüft selbst, ob der tägliche Abruf ab 12:00 Uhr bereits gelaufen ist. Für direkte Wartungsläufe registriert es zusätzlich `cms_cron_m365messagecenter`.
