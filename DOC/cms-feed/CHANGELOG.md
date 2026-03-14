# CMS Feed – Changelog

Alle nennenswerten Änderungen an diesem Plugin werden hier dokumentiert.

---

## [1.3.0] – 2026-03-14

### Hinzugefügt
- **👤 Member-Feed-Abos** – Neues persönliches Abo-Modell für `/member/feeds` im `cms-phinit` Theme
- **Mehrfachauswahl von Feed-Kanälen** – Mitglieder können einen oder mehrere Feeds in einer gemeinsamen Mail abonnieren
- **Neue Versandregeln für Member**
	- täglich um `09:00 Uhr`
	- täglich um `15:00 Uhr`
	- täglich `2×` um `09:00 Uhr` und `15:00 Uhr`
	- wöchentlich an frei wählbarem Wochentag um `09:00 Uhr` oder `15:00 Uhr`
- **Neue Tabelle `feed_member_subscriptions`** – Speichert Feed-Auswahl, Zieladresse, Versandrhythmus und letzten Versand-Slot je Member
- **Admin-Dashboard-Stat** – Anzahl aktiver Member-Feed-Abos sichtbar

### Geändert
- **Route-sensitives Asset-Loading** – Public CSS/JS von `cms-feed` wird nur noch auf echten Feed-Archiv-Routen geladen und nicht mehr global auf allen Frontend-Seiten
- **`cms_phinit/member/feeds.php` modernisiert** – Aus einem einfachen Kanal-Toggle wurde ein echtes Abo-Center mit Versandplan, Zusammenfassung und gruppierter Feed-Auswahl
- **`CMS_Feed_Email_Digest` erweitert** – Verarbeitet zusätzlich persönliche Member-Abos im stündlichen Cron-Lauf

### Dokumentation
- README, DATABASE, API und HOOKS um Member-Feed-Abos und Versand-Slots ergänzt

## [1.2.0] – 2026-02-28

### Hinzugefügt
- **📚 Feed-Katalog** – 300+ kuratierte RSS-Feeds in 10 Kategorien (IT-News, Security, Development, Cloud/Infra, Microsoft, Linux/OpenSource, AI/Data, Networking, Business-IT, Hardware) mit Ein-Klick-Import
- **Neue Klasse `CMS_Feed_Catalog`** – Statischer Feed-Katalog mit `get_catalog()`, `get_categories_overview()`, `import_feeds()` Methoden
- **Katalog-Tab im Admin** – Neuer Tab „📚 Katalog" mit visueller Kartenübersicht aller Katalog-Kategorien, Komplett-Import und Import in bestehende Bereiche
- **Duplikat-Erkennung** – `channel_url_exists()` prüft vor dem Import, ob ein Feed bereits existiert; Duplikate werden übersprungen
- **Auto-Kategorie-Erstellung** – Beim Katalog-Import wird automatisch ein neuer Bereich erstellt, falls kein Zielbereich gewählt wird
- **Bulk-Actions für Kanäle** – Mehrfachauswahl mit Checkboxen + Aktionen: Abrufen (max. 5 sofort, Rest per Cron-Queue), Aktivieren, Deaktivieren, Löschen
- **Bulk-Actions für Bereiche** – Mehrfachauswahl mit Checkboxen + Bulk-Löschen (inkl. aller zugehörigen Kanäle und Beiträge)
- **Fetch-Queue (Warteschlange)** – Neue DB-Tabelle `feed_fetch_queue` für asynchrone Verarbeitung großer Bulk-Abrufe; max. 5 Kanäle sofort, Rest wird per Cron verarbeitet
- **Neue Klasse `CMS_Feed_Cron`** – Verarbeitet die Fetch-Queue im Hintergrund via `cms_cron_hourly` (max. 5 Tasks pro Durchlauf), räumt alte Queue-Einträge auf
- **Bulk-Bestätigungsdialog** – Eigenes Modal statt `window.confirm()` für Bulk-Aktionen (konform mit Admin-Richtlinien)
- **Queue-Status im Dashboard** – Ausstehende Tasks werden als Stat-Card auf dem Dashboard angezeigt

### Behoben
- **Modals funktionieren nicht** – `openModal()`/`closeModal()` JS-Funktionen fehlten komplett. Das CMS-Core `admin.js` definiert keine Modal-Funktionen. Alle Erstellungs-Dialoge (Kanal, Bereich, Digest) konnten nie geöffnet werden. → Vollständiges Modal-System in Plugin-`admin.js` implementiert (öffnen, schließen, Escape-Taste, Klick-außerhalb)
- **Öffentliche Seiten ohne CMS-Theme** – `archive-feed.php` und `archive-category.php` renderten eigenes `<!DOCTYPE html>` statt das aktive Theme. → Auf `ThemeManager::getHeader()`/`getFooter()` umgestellt

---

## [1.1.0] – 2026-02-28

### Behoben
- **CSRF-Sicherheitscheck fehlgeschlagen** – Token wurde in `admin_page()` vor der POST-Verarbeitung neu generiert und überschrieb das Session-Token. Formulare (Anlegen, Einstellungen, Löschen etc.) schlugen dadurch immer fehl.
- **get_stats() doppelte Query** – Entfernte buggy erste Zeile, die prepare/execute ohne fetch ausführte.
- **Einstellungen Sub-Tab** – Nach Speichern von Design- oder Allgemein-Einstellungen wird jetzt der korrekte Sub-Tab beibehalten.

### Hinzugefügt
- **Admin: Versteckte Beiträge sichtbar** – Im Beiträge-Tab werden jetzt auch ausgeblendete Beiträge angezeigt, mit visueller Kennzeichnung „Ausgeblendet".
- **Filter-Parameter `include_hidden`** – `get_items()` und `count_items()` akzeptieren jetzt `include_hidden => true` um auch versteckte Items einzubeziehen (für Admin-Kontext).

### Dokumentation
- DATABASE.md erstellt (alle Tabellen, Felder, Indizes, Relationen)
- HOOKS.md erstellt (Actions, Filter, POST-Actions, CSS-Tokens)
- API.md erstellt (alle Klassen und öffentliche Methoden)
- CHANGELOG.md erstellt

---

## [1.0.0] – 2026-02-27

### Erstveröffentlichung
- RSS-Feed-Aggregator mit Unterstützung für RSS 2.0, RSS 1.0 (RDF) und Atom
- Bereiche/Kategorien zur thematischen Gruppierung
- Öffentliche Seiten (Archiv + individuelle Bereichsseiten)
- 3 Layout-Optionen: Grid, Liste, Magazin
- Design-Anpassung über Admin (Farben, Border-Radius, Spalten)
- E-Mail-Digest-System (1×–4× täglich)
- Volltextsuche über Beiträge
- Featured & Hidden Beiträge
- Auto-Cleanup für alte Beiträge
- Theme-Override für Templates
- Admin-Backend mit 6 Tabs: Dashboard, Kanäle, Bereiche, Beiträge, Digests, Einstellungen
