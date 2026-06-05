# CMS M365 Message Center – FeatureList

## 1) Standalone Message Center Archive

- **Feature name:** M365 Message Center Public Archive
- **Umsetzungsgrund:** Die bisherige Graph-Funktion aus `cms-m365landing` soll eigenständig, sicher und performanter betrieben werden.
- **Public:** `/m365-messagecenter` mit Suche, Service-/Kategorie-Filter, Sortierung, Pagination und Detailseiten je Meldung.
- **Admin:** Graph-Zugangsdaten, Abrufmenge, Cache-Hinweis, Standard-Sortierung, Darstellung, Public-Schalter, Layoutwahl und max. Public-Content-Breite.
- **Design:** Modernes Public-Layout mit Hero, Statuspanel, Filterkopf und lesbaren Karten.
- **Layouts:** Maximal drei Varianten: `standard`, `compact`, `list`.
- **Radius-Vertrag:** Badges max. 4px, Message-Center-Nachrichtenkarten max. 2px.
- **Lesbarkeit:** Archivkarten kürzen nach 200 Wörtern mit `...`; Detailseiten zeigen den vollständigen gecachten Klartext-Body.
- **Original-Link:** Jede Übersichtskarte kann einen Button `Original im M365 Admin Center` anzeigen; wenn Graph keinen externen Link liefert, wird ein Admin-Center-Fallback aus der Message-ID genutzt.
- **Breite:** Public-Content ist standardmäßig auf 1160px begrenzt und im Adminbereich zwischen 900px und 1600px einstellbar.

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
- **Admin-Schalter:** Detailseiten können deaktiviert werden; Archivkarten entfernen dann Detail-Links und Detailrouten leiten zur Übersicht zurück.

## 5) Public Display Controls

- **Schalter:** Detailseiten, Status-/Cache-Kachelbereich, Filterbereich, Meldungsauszüge und externe Microsoft-Links.
- **Statuspanel:** Zeigt Cache-Anzahl, letzte Aktualisierung und Graph-Sprache nur wenn aktiviert.
