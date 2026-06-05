# CMS M365 Message Center – FeatureList

## 1) Standalone Message Center Archive

- **Feature name:** M365 Message Center Public Archive
- **Umsetzungsgrund:** Die bisherige Graph-Funktion aus `cms-m365landing` soll eigenständig, sicher und performanter betrieben werden.
- **Public:** `/m365-messagecenter` mit Suche, Service-/Kategorie-Filter, Sortierung, Pagination und Detailseiten je Meldung.
- **Admin:** Graph-Zugangsdaten, Abrufmenge, Cache-Hinweis, Standard-Sortierung und Darstellung.
- **Design:** Modernes Public-Layout mit Hero, Statuspanel, Filterkopf und lesbaren Karten.
- **Lesbarkeit:** Archivkarten kürzen nach 200 Wörtern mit `...`; Detailseiten zeigen den vollständigen gecachten Klartext-Body.

## 2) Secure Graph Fetch

- **Endpoint:** `GET /admin/serviceAnnouncement/messages`
- **Permission:** Microsoft Graph Application Permission `ServiceMessage.Read.All`
- **Sicherheit:** HTTPS-only, Allowlist für Microsoft-Hosts, keine Redirects, Response-Limit, kurze Timeouts, redigierte Logs.
- **Performance:** Public liest ausschließlich lokale DB-Caches.

## 3) Daily Message Center Cron

- **Hook:** `cms_cron_hourly` mit Tageswächter ab 12:00 Uhr.
- **Direktaufruf:** `cms_cron_m365messagecenter` als generischer Cron-Hook.
- **Sprache:** Graph-Abruf nutzt standardmäßig `de-DE`.

## 4) Public Detail Pages

- **Route:** `/{route_slug}/{messageId}` und `/en/{route_slug}/{messageId}`.
- **Inhalte:** Volltext, Graph-ID, Kategorie, Schweregrad, Services, Tags, Major-Change-Flag, Zeitpunkte und externer Microsoft-Link.
- **Abstand:** Plugin-Content hält zum Theme-Header und Theme-Footer maximal 25px Abstand.
