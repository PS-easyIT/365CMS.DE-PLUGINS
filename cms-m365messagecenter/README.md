# CMS M365 Message Center

Eigenständiges 365CMS-Plugin für Microsoft-365-Message-Center-Meldungen.

## Funktionen

- Adminbereich: Graph-Zugangsdaten, Public-Route und Cache-Verhalten konfigurieren.
- Sicherer manueller Abruf der Microsoft Graph Service Communications API.
- Deutscher Graph-Abruf per `Accept-Language: de-DE` als Standard.
- Automatischer Tagesabruf per `cron.php` einmal täglich ab 12:00 Uhr.
- Lokale Cache-Tabelle für Message-Center-Meldungen.
- Moderne Publicsite unter `/m365-messagecenter` mit Statuspanel, Suche, Service-/Kategorie-Filter, Sortierung, Richtung, Pagination und Detailseiten je Meldung.
- Übersichts-Auszüge werden nach 200 Wörtern mit `...` gekürzt; Detailseiten zeigen den vollständigen gecachten Meldungstext.
- Abstand zwischen Theme-Header/Footer und Plugin-Content ist auf maximal 25px begrenzt.

## Microsoft Graph Berechtigung

Für den App-only-Abruf wird nur die Application Permission `ServiceMessage.Read.All` benötigt. Nach dem Setzen der Berechtigung ist Admin Consent in Microsoft Entra erforderlich.

## Sicherheits- und Performance-Vertrag

- Publicseiten greifen niemals direkt auf Microsoft Graph zu.
- Client-Secret wird im Adminformular nicht zurückgegeben; leeres Secret-Feld erhält das gespeicherte Secret.
- Graph-Requests sind auf bekannte Microsoft-Hosts und HTTPS beschränkt.
- Keine Redirects, kurze Timeouts, Response-Limit und JSON-Parsing mit Fehlerpfaden.
- Graph-HTML-Inhalte werden zu Klartext-Auszügen reduziert und im Publicbereich immer escaped.
- Neue Graph-Abrufe speichern zusätzlich einen vollständigen Klartext-Body für die Detailansicht.

## Cron

Das Plugin registriert sich auf `cms_cron_hourly` und führt den Abruf nur aus, wenn seit 12:00 Uhr Ortszeit noch kein Tagesabruf gespeichert wurde. Zusätzlich kann der generische Core-Cron-Hook `cms_cron_m365messagecenter` direkt genutzt werden, z. B. für manuelle Wartungsläufe.
