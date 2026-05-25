# CMS Feed – Changelog

Alle nennenswerten Änderungen an diesem Plugin werden hier dokumentiert.

---

## [3.0.4] – 2026-05-25

### Behoben
- `CMS_Feed_Cron` hängt jetzt direkt am regulären `/cron.php`-Tick: `task=all` und `task=mail-queue` reihen bei jedem Lauf nach `fetch_interval` fällige Kanäle ein und verarbeiten anschließend einen Queue-Batch.
- Neuer expliziter Feed-Cron-Task `task=feeds` bzw. Hook `cms_cron_feeds`, damit Feed-Aktualisierungen separat über CLI, Web-Cron oder den Systembereich gestartet werden können.
- Der Core-Cron-Runner kennt den Task `feeds`, gibt Feed-Ergebnisse bei direkter Ausführung strukturiert zurück und zeigt die Feed-CLI-/URL-Aufrufe im Cron-Status an.
- `limit` begrenzt den Feed-Batch, `force=1` reiht alle aktiven Kanäle ein; hängen gebliebene `processing`-Jobs werden vor dem Lauf wieder freigegeben.

---

## [3.0.3] – 2026-05-25

### Behoben
- Lifecycle-Callbacks `cms_feed_activate()`, `cms_feed_deactivate()` und `cms_feed_uninstall()` ergänzt, damit der Core-PluginManager Aktivierung, Deaktivierung und Uninstall ohne fehlende Callback- bzw. Method-Fehler ausführen kann.
- Pflicht-Includes im Plugin-Bootstrap werden nicht mehr still übersprungen, sondern schlagen kontrolliert mit protokollierter RuntimeException fehl.
- Datenbankschema-Migration ergänzt fehlende Spalten, Indexe und Foreign-Key-Constraints und bereinigt Orphan-Relationen vor FK-Erstellung.
- `drop_tables()` für vollständigen Uninstall hinzugefügt und Löschroutinen für Bereiche/Kanäle um Queue- sowie Member-Abo-Cleanup erweitert.
- Admin-POST-Handler normalisieren skalare Werte, Arrays, Enums und Farben zentral; manipulierte Array-Payloads in POST-Feldern verursachen keine PHP-8.4-TypeErrors mehr.
- Public-Routen normalisieren Query-Parameter arraysicher und liefern bei Template-/DB-Ausnahmen gerenderte 500-Fallbacks statt White-Screen/Blank-200.
- Plugin-Ausnahmen werden zentral über `CMS_Feed_Error_Handler` im 365CMS-Logger-Channel `plugin-cms-feed` protokolliert und anschließend über die nativen Theme-Fehlerseiten `error.php` bzw. `404.php` gerendert.
- Mail-Digest-Testversand aktualisiert `last_sent_at` nicht mehr und prüft Zieladressen vor dem MailService-/Queue-Aufruf.
- Bootstrap, Konstanten und alle Feed-Klassendateien sind gegen versehentliches erneutes Laden geschützt, damit Alt-/Doppel-Include-Pfade keine `Cannot redeclare class CMS_Feed_*`-Fatals mehr auslösen.

### Hinzugefügt
- Whitelabel-/Embed-Route `/{archive_slug}/embed` für das vorhandene Standalone-Template.
- Explizite HTTP-Statuscodes für CSRF-Fehler (`403`), unbekannte Admin-Aktionen (`400`), fehlende Feed-Bereiche (`404`) und Public-/Template-Ausnahmen (`500`).

---

## [3.0.2] – 2026-05-22

### Behoben
- **Admin-500er unter PHP 8.4 beseitigt** – Feed-Admin-Zahlen, Datumswerte und Query-Parameter werden jetzt typisiert bzw. arraysicher normalisiert, bevor sie an strikte PHP-Formatter wie `number_format()`, `date()` oder `rawurlencode()` übergeben werden.
- **Menüintegration robuster** – Der Admin-Menüpfad fällt bei `parse_url()`-Randfällen sauber auf einen leeren String zurück, statt bei `str_starts_with()` einen TypeError zu riskieren.

### Technisch
- Besonders relevant nach dem letzten Security-Hardening: manipulierte oder unerwartet strukturierte Query-Parameter wie `q[]`, `cat[]`, `page[]` oder `stab[]` können die Feed-Admin-View nicht mehr aus dem Tritt bringen.

---

## [3.0.1] – 2026-05-17

### Geändert
- Feed-Card-Links und Bilder werden vor der Ausgabe nochmals gegen unsichere Schemes, Credentials und lokale/private Hosts geprüft.
- Public-Templates nutzen explizites `ENT_QUOTES`/`UTF-8`-Escaping und textbasierte Such-/Badge-Controls für bessere Accessibility und weniger dekorative UI-Last.

## [1.3.6] – 2026-05-03

### Behoben
- **Feed-Digests hängen jetzt an der zentralen Mail-Infrastruktur** – `CMS_Feed_Email_Digest` nutzt bei aktiver Queue `MailQueueService` und fällt sonst auf `MailService` zurück, statt direkt `mail()` aufzurufen.
- **Member-Feed-Abos profitieren von Cron-Retries** – fällige Abo-Mails werden nicht mehr am stündlichen Feed-Cron vorbei versendet, sondern über denselben SMTP/OAuth-, Logging- und Retry-Pfad wie andere CMS-Mails verarbeitet.

### Technisch
- Digest-Mails setzen nun nachvollziehbare `X-365CMS-*`-Quellheader und übernehmen optionale Feed-Absender nur nach E-Mail-Validierung.

## [1.3.5] – 2026-04-02

### Behoben
- **Überfällige Feed-Queues werden zwischen zwei Stundenläufen jetzt weiter abgebaut** – `CMS_Feed_Cron` hängt zusätzlich am Core-Hook `cms_cron_mail_queue` und verarbeitet dort bei jedem regulären Cron-Tick einen kleinen Batch bereits eingereihter Feed-Tasks.
- **Hängen gebliebene Queue-Jobs blockieren keine Kanäle mehr dauerhaft** – Verwaiste `processing`-Einträge werden nach 20 Minuten automatisch wieder auf `pending` gesetzt und können beim nächsten Lauf erneut verarbeitet werden.
- **Feed-Abrufe funktionieren jetzt auch ohne `allow_url_fopen` zuverlässig weiter** – `CMS_Feed_RSS_Fetcher` nutzt bei fehlgeschlagenem oder deaktiviertem Stream-Zugriff automatisch cURL als Fallback, statt Shared-Hosting-Setups still mit „Feed konnte nicht geladen werden“ stehen zu lassen.

### Technisch
- **Stündlicher Lauf bleibt fürs Einreihen zuständig** – `cms_cron_hourly` reiht weiterhin priorisierte und regulär fällige Kanäle ein, verarbeitet einen ersten Batch und übernimmt Cleanup; der neue Minuten-Worker drainiert nur bereits bestehende Queue-Einträge.
- **Core-/Plugin-Cron besser verzahnt** – Der Core feuert `cms_cron_mail_queue` nun auch während `task=all`/`task=mail-queue` als echten Hook mit Kontext-Flag, sodass Plugins wie `cms-feed` an jedem Cron-Lauf andocken können, ohne die Mail-Queue doppelt auszuführen.

## [1.3.4] – 2026-03-18

### Geändert
- **Admin-Interaktionen vollständig datengetrieben** – `page-admin.php` nutzt für Bulk-Aktionen, Bearbeiten-/Löschen-Buttons, Katalog-Auswahl und Settings-Tabs jetzt `data-*`-Hooks statt direkter `onclick`-Aufrufe oder `javascript:void(0)`-Links.
- **Zentrales Confirm-/Modal-Handling weiterverwendet** – `assets/js/admin.js` bindet Import-, Lösch- und Modal-Aktionen jetzt zentral, inklusive neutraler Bestätigungen für Imports und destruktiver Warnungen für Löschaktionen.

### Verbessert
- **Admin-View deutlich wartbarer** – Der veraltete Inline-Skriptblock am Ende von `admin/views/page-admin.php` entfällt; Modal-Resets, Tab-Wechsel, Katalog-Selektion und Delete-Dialoge leben jetzt vollständig im bestehenden Admin-JavaScript.

## [1.3.3] – 2026-03-18

### Geändert
- **Consent-Handling auf öffentlichen Feed-Seiten zentralisiert** – Das Public-Skript wird jetzt auf allen Feed-Routen geladen, sodass sowohl Archiv- als auch Consent-Seiten unmittelbar auf Änderungen der Cookie-Einwilligung reagieren können.
- **Template-Inlines weiter reduziert** – Verbleibende Inline-Styles und das Sonder-Skript in `consent-required.php`, `archive-feed.php`, `archive-category.php` und `whitelabel-feed.php` wurden in die bestehenden Public-Assets ausgelagert.

### Verbessert
- **Wartbarkeit der Public-Templates** – Consent-CTA, Zurücksetzen-Links und Reload-Logik folgen jetzt konsistent dem bestehenden `assets/css/style.css` / `assets/js/script.js`-Pfad statt eigenen Template-Sonderwegen.

## [1.3.2] – 2026-03-17

### Behoben
- **Automatisches Nachladen der Feeds funktioniert wieder** – Das Plugin hing korrekt am Hook `cms_cron_hourly`, aber der bisherige Core-Cron-Endpunkt (`CMS/cron.php` im Repo, deployed typischerweise als `/cron.php`) löste diesen Hook nie aus. Bestehende Cron-Aufrufe für `task=mail-queue` triggern den stündlichen Feed-/Digest-Lauf jetzt automatisch mit.

### Technisch
- **Kompatibler Core-Cron-Bridge-Fix** – Der Core unterstützt jetzt zusätzlich `task=hourly` und `task=all`; der stündliche Hook wird intern auf höchstens einen echten Lauf pro Stunde gedrosselt, damit häufigere Mail-Queue-Crons keine Feed-Doppelverarbeitung verursachen.

## [1.3.1] – 2026-03-16

### Geändert
- **Stündlicher Feed-Cron priorisiert `cms-phinit`-Homepage-Feeds** – Die in `feed1_channel_id` und `feed2_channel_id` gewählten Startseiten-Kanäle werden bei jedem `cms_cron_hourly`-Lauf bevorzugt in die Fetch-Queue eingereiht, solange die Feed-Sektion aktiv ist
- **Automatische Prüfung aller fälligen Kanäle bleibt aktiv** – Zusätzlich zu den Homepage-Kanälen werden weiterhin alle regulär nach `fetch_interval` fälligen Feed-Kanäle verarbeitet
- **Automatisches 7-Tage-Cleanup** – Nicht hervorgehobene Feed-Beiträge älter als 7 Tage werden jetzt bei jedem stündlichen Cron-Lauf automatisch gelöscht
- **Admin-Cleanup an 7-Tage-Policy angepasst** – Schnellaktionen und Standardwerte im Admin nutzen jetzt 7 Tage statt 90 Tage als Default

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
