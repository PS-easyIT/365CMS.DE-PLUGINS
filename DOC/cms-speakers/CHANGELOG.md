# CMS Speakers – Changelog

## [3.0.18] – 2026-05-31

- **Hotfix i18n:** `single-speaker.php` löst Detailseiten-Labels jetzt zusätzlich direkt über den zentralen `CMS/lang`-YAML-Katalog auf, falls `TranslationService` oder `__()` den Original-Key zurückgeben.
- **Frontend:** Breadcrumb, Hero, Content-Bereiche und Sidebar zeigen dadurch keine rohen `cms_speakers.detail.*` Keys mehr, auch wenn der globale Translator-Fallback auf der Laufzeitumgebung nicht greift.

## [3.0.17] – 2026-05-31

- **Public-Detailseite:** `single-speaker.php` wurde auf das fertige PHINIT-Referenzlayout umgebaut: Navy-Hero mit Avatar/Initialen, Name, Auftritte-Pill, Verfügbarkeitsbadge, Rollenlinie und Topic-/Format-Tags.
- **Content-Struktur:** Unterhalb des Heros rendert ein responsives Zwei-Spalten-Grid mit Bio, Themen/Formaten/Skills und Vorträgen links sowie Buchungs-CTA, Social Media, Details und weiteren Speakern rechts.
- **i18n & Icons:** Alle sichtbaren UI-Labels laufen über die zentrale Übersetzungsmechanik (`CMS/lang/de.yaml`, `CMS/lang/en.yaml`); Icon-Fonts wurden im Detailtemplate durch Inline-SVG ersetzt.
- **Assets:** `single.css` wird nur noch auf Speaker-Detailseiten (`/speakers/{slug}`) geladen, nicht mehr auf der Übersicht.
- **Designsystem:** Die Detailseite nutzt die PHINIT Navy-/Amber-Palette aus der Referenz, bleibt auf maximal `1160px` Contentbreite und enthält Dark-Mode-Tokens.

## [3.0.16] – 2026-05-31

- **Public-Detailseite:** `single-speaker.php` nutzt nun die vollständige Experts-Detailseiten-Struktur mit Hero, Bio-/Kontakt-Bridge, Haupt-/Sidebar-Grid, Inhaltssektionen und Auftrittskarten.
- **Speaker-Feldmapping:** Experts-spezifische Blöcke wurden auf Speaker-Daten abgebildet: Themen, Formate, Skills, Zielgruppe, Vortragsstil, Sprachen, Reisebereitschaft, Verfügbarkeit, Honorar, Vortragstitel (`speaker_events.topic`), Events und Social Links.
- **Designsystem:** Die Struktur-/Spacing-/Typografie-Regeln wurden übernommen, die Farbgebung bleibt jedoch vollständig über Speaker-Variablen (`--sp-*`, `--cms-speaker-*`) gesteuert.
- **Responsive & Dark Mode:** Die 1160px-Contentbegrenzung, full-bleed Shell, mobile Grid-Umbrüche und Dark-Mode-Tokens sind für die neue Struktur final abgesichert.

## [3.0.15] – 2026-05-31

- **Public-Detailseite:** Breadcrumb, Profil-/Contentbereich und Anfrage-Spalte liegen nun durchgehend in der Theme-Contentbreite von maximal `1160px`.
- **Responsive:** Das Detailseiten-Grid wechselt unterhalb von Tablet-Breiten sauber auf eine Spalte; Profilkopf, Sessions und CTA-Buttons bleiben auf kleinen Displays lesbar.
- **Dark Mode:** Profilkopf, Inhaltskarten, Sessions, Booking-Card, Related Speaker, Tags, Social-Links und Textfarben nutzen explizite Dark-Mode-Tokens.

## [3.0.14] – 2026-05-31

- **Public-Suchbereich:** Die Hauptaktion der Speakers-Filterleiste ist nun ein primärer Button „Suchen“ statt „Filter zurücksetzen“.
- **Layout:** Die Speakers-Filterleiste wird final auf die `1160px`-Contentbreite begrenzt und kann nicht mehr durch spätere Button-/Filterregeln auf volle Fensterbreite laufen.

## [3.0.13] – 2026-05-31

- **Public-Suchbereich:** Die Speakers-Übersicht nutzt nun das einheitliche Companies-basierte Filterdesign mit gleicher Feldhöhe, Label-Rhythmik, Button-Optik, voller `1160px`-Contentbreite und responsiven Umbrüchen.
- **Plugin-Anpassung:** Topic-Auswahl und Speaker-Suche behalten ihre Speaker-spezifische Filterlogik und verwenden die Speaker-Akzentfarben.

## [3.0.12] – 2026-05-31

- **Public-Übersicht:** Die Shell liegt bündig an Theme-Header und -Footer an; der eigentliche Content startet intern bei `25px`, bleibt responsiv und ist auf maximal `1160px` begrenzt.

## [3.0.10] – 2026-05-30

- **Speaker-Archiv:** Die öffentliche Übersicht rendert wieder als responsives Card-Grid statt als horizontale Liste und orientiert sich visuell an der CMS-Events-Übersicht.
- **Card-Inhalte:** Cards zeigen nun Avatar/Initialen, Featured-/Verified-/Topic-Badges, Verfügbarkeit, Ort, Vortragsformate, Bio-Auszug, Footer-Meta und einen kompakten Profil-CTA.
- **Layout-Fokus:** Der separate Archivkopf wurde entfernt, sodass die Seite wie die Event-Übersicht direkt mit Filter und Cards startet; Filter- und Keyboard-Navigation bleiben erhalten.

## [3.0.9] – 2026-05-27

- **Speaker-Archiv:** Horizontale Listenkarte intern auf ein 3-Zonen-Layout umgestellt: 56px Avatar links, Hauptcontent mit Name/Rolle, Topic-Zeile und zweizeiliger Bio sowie Profilbutton rechts oben.
- **Layout-Rhythmus:** Topic-Tags und Bio liegen nun in eigenen Zeilen statt gequetscht in einer gemeinsamen Meta-Ebene; die Bio bleibt per 2-Line-Clamp kompakt.
- **Scope:** Filter-Bar, Pagination, Detailseite, DB-Queries und Klick-/Keyboard-Logik bleiben unverändert; Inline-Handler wurden weiterhin bewusst vermieden.

## [3.0.8] – 2026-05-27

- **Speaker-Archiv:** Vertikale 3-Spalten-Cards durch eine einspaltige, horizontale Kompaktliste ersetzt: Avatar/Initialen links, Name/Rolle/Topics/Bio/Eventzähler rechts.
- **Interaktion:** Karten bleiben per Vanilla-JS klickbar und sind zusätzlich per Tastatur (`Enter`/`Space`) über `data-href` erreichbar; Inline-Handler wurden bewusst nicht verwendet.
- **Filter:** Topic-/Suche-Filter zeigen sichtbare Karten als `flex`, damit das neue horizontale Layout beim Filtern erhalten bleibt.

## [3.0.7] – 2026-05-27

- **Speaker-Archiv:** Avatar-Placeholder durch serverseitige Initialen ersetzt, Topic-Tags auf ruhige Navy/Subtle-Tags umgestellt, Profil-CTA als goldener Outline-Button vereinheitlicht und Karten per Vanilla-JS klickbar gemacht.
- **Filter/Empty-State:** Filterfelder und Reset-Button optisch an CMS Events angeglichen; gefilterte Leerergebnisse zeigen nun einen eigenen `ti-users-off` Empty State.
- **Detailseite:** Hero-Profilkarte, Bio, Sessions, Social-Links, Related Speaker und Kontakt-CTA nach Screenshot-Vorgabe verfeinert; Kontaktbutton bleibt bewusst blau.

## [3.0.6] – 2026-05-26

- **Publicsite-Design:** Speaker-Archiv, Such-/Topic-Filter, Cards, Topic-Pills, Social-Icons und Pagination folgen nun dem gleichen PHINIT-Designsystem wie CMS Events.
- **Detailseite:** Profil, Sessions, Related Speaker und Sticky-Anfragekarte wurden optisch vereinheitlicht.
- **Kontakt:** Speaker-Profile zeigen zusätzlich E-Mail als Social-Link; die Anfragekarte enthält beschriftete Kontaktlinks für LinkedIn, Website und E-Mail.

## [3.0.3] – 2026-05-25

### Geändert

- **Bootstrap:** Hauptklasse und Konstanten sind redeclare-sicher; hook-registrierende Komponenten werden bereits bei verfügbarem `CMS\Hooks` geladen und nicht mehr von der DB-Verfügbarkeit blockiert.
- **Admin-Menü:** Der Sidebar-Eintrag `speakers` lädt das Dashboard serverseitig und nutzt den Core-Helper `includes/functions/admin-menu.php` statt einer JS-Weiterleitung.
- **DB-Migration:** Spaltenprüfungen laufen über `INFORMATION_SCHEMA.COLUMNS`; interpolierte `SHOW COLUMNS ... LIKE`-Queries wurden entfernt.
- **Settings:** Plugin-Einstellungen nutzen primär `CMS\Services\SettingsService` mit Legacy-Fallback auf `cms_speaker_plugin_settings`.
- **HTTP-Fehlerpfade:** POST-only Admin-Endpunkte besitzen 405-Fallbacks; AJAX-Endpunkte liefern Statuscodes, JSON und `X-Content-Type-Options: nosniff`.
- **Public Errors:** Öffentliche Archive-/Detailrouten loggen Exceptions und rendern native/fallback Error-Seiten statt White-Screens.
- **Uninstall:** `drop_tables()` entfernt Speaker-Tabellen und bereinigt die `cms-speakers`-Settings-Gruppe.

## [3.0.1] – 2026-05-18

### Geändert

- **Security-Pass:** CSRF-Token-Prüfungen in Admin- und Member-Pfaden string-cast-sicher gemacht.
- **URL-Härtung:** Public-Templates, Admin-Saves und zentrale Persistenz validieren HTTP/HTTPS-URLs restriktiver und blocken Credentials, lokale/private Ziele und Steuerzeichen.
- **Input-Normalisierung:** GET-Filter, Settings, Event-Daten und Textfelder werden begrenzt und über feste Allow-Lists normalisiert.
- **Publicsite-UX:** Archive-, Card- und Single-Template von Emoji-Deko befreit und Ausgabe-Escaping auf UTF-8/ENT_QUOTES nachgezogen.
- **Admin-UX:** Menü-/Header-/Button-Labels beruhigt, Asset-URLs escaped und kritische Inline-Eventhandler auf data-Attribut-Listener umgestellt.

## [1.1.0] – 2026-03-28

### Geändert

- **Statusschema:** Speaker-Status und Datenbankschema wurden für `pending` und `deleted` harmonisiert; bestehende Installationen werden per Migration nachgezogen.
- **Listenhärtung:** `get_speakers()` normalisiert `limit`, `offset` und `ORDER BY` jetzt defensiv über feste Whitelists.
- **Dashboard-/Admin-Logik:** Member- und Admin-Listen berücksichtigen Speaker über den tatsächlichen Statusfluss konsistent, inklusive eigener `pending`-Profile.
- **Admin-Save:** `gender`, `travel_radius`, `availability`, `status` sowie Array-/Link-Felder werden restriktiver normalisiert.
- **Member-Create:** Gender-, Format-, Travel-, Availability-, Topic- und Link-Daten werden im Member-Create-Handler jetzt ebenfalls restriktiv normalisiert.
- **Template-Escaping:** Die Speaker-Bio im Single-Template wird nicht mehr roh ausgegeben, sondern sicher escaped und mit Zeilenumbrüchen gerendert.
- **Link-Escaping:** Intern zusammengesetzte Breadcrumb-, Company-, Kontakt- und Register-Links im Single-Template werden jetzt ebenfalls konsequent im Attribut-Kontext escaped.
- **Archive-Link-Escaping:** Reset- und Pagination-Links im Archive-Template behandeln interne URLs und Query-Parameter jetzt ebenfalls konsequent im `href`-Attribut-Kontext.
- **Card-Link-Escaping:** Detail-Links aus `$speaker_url` werden im Card-Template jetzt ebenfalls konsequent im `href`-Attribut-Kontext escaped.
- **Card-CTA-Escaping:** Auch der CTA-Link aus `$speaker_url` im Card-Template wird jetzt konsequent im `href`-Attribut-Kontext escaped.
- **Style-/Title-Escaping:** Auch Avatar-Gradient im `style`-Attribut und Event-Zähler im `title`-Attribut des Single-Templates werden jetzt konsequent escaped.
- **Title-Attribut-Escaping:** Auch interne Social-Icon-Labels im `title`-Attribut des Single-Templates werden jetzt konsequent escaped.
- **Renderzeit-Validierung:** Foto-, Website-, Mail-, Telefon- und Social-Link-Felder werden in Card- und Single-Templates jetzt zusätzlich gegen Alt- und Bestandsdaten validiert, bevor sie in `src`, `href`, `mailto:` oder `tel:` gerendert werden.
- **Member-Dashboard-Status:** Der aktuelle Rechte-Stand wurde nachgezogen: Im Member-Bereich existiert derzeit nur ein Create-/Listen-Flow, aber kein realer Edit- oder Update-Pfad für bestehende Speaker.
- **Ownership-Härtung:** Die zentrale `save_speaker()`-Persistenz blockiert für Nicht-Admins jetzt Updates auf fremde Speaker-IDs und entschärft damit latente IDOR-/Fremd-ID-Pfade.
- **Bootstrap-Fix:** Hook-registrierende Klassen werden jetzt bereits beim Plugin-Start instanziiert, sodass Admin-Sidebar-Eintrag und Admin-Routen nicht mehr von `cms_init` abhängen.
- **Bootstrap-Guard:** Der frühe Bootstrap wird jetzt zusätzlich nur bei verfügbarem Core-Kontext (`CMS\Hooks`, `CMS\Database`) ausgeführt und entschärft damit Aktivierungs-/Lade-Fatals.
- **DB-Fatal-Guard:** Der komplette Tabellenaufbau inklusive initialem Datenbankzugriff wird im Aktivierungs-/Init-Pfad jetzt defensiv abgefangen und nur noch geloggt statt als Fatal nach oben weitergereicht.
- **Assets:** Bootstrap- und Admin-Assets verwenden konsistente, dateigeprüfte Versionswerte.

## [1.0.1] – 2026-03-28

### Geändert

- **Dokumentation:** README, Aufgabenplanung und Sicherheitsdokumentation auf den Zielstand für **365CMS V2.8.0** erweitert.
- **Audit-Fokus:** Security-, Speed- und Best-Practice-Prüfschritte für `cms-speakers` konkretisiert, insbesondere für Ownership, Link-Validierung, Template-Escaping und Cross-Plugin-Referenzen.
- **Planung:** Verweis auf den zentralen Abarbeitungsplan `DOC/365CMS-V2.8.0-PLUGIN-AUDIT-PLAN.md` ergänzt.

## [1.0.0] – 2026-02-21

### Hinzugefügt

- **Kern:** Singleton-Hauptklasse `CMS_Speakers`
- **Datenbank:** `cms_speakers`, `cms_speaker_topics`, `cms_speaker_events`, `cms_speaker_meta`
- **Basis-Profil:** Namen, Titel, Geschlecht, Position, Firma, Kontakt, Bio, Kurzbiografie, Foto
- **Social-Media:** LinkedIn, XING, Twitter, Instagram, YouTube, GitHub, GitLab, Website
- **Vortrags-Formate:** keynote, workshop, panel, moderation, interview, webinar, conference, training
- **Event-History:** Auftritte mit Typ, Datum, Ort, Audience-Größe, Video/Slides-URL
- **Cross-Plugin:** `expert_id` (→ cms-experts), `company_id` (→ cms-companies), `cms_event_id` (← cms-events)
- **Verfügbarkeit:** `available`, `limited`, `booked`
- **Badging:** `is_featured`, `is_verified`, `profile_views`-Counter
- **Reise-Radius:** local / regional / national / international / worldwide
- **Honorar:** Preisspanne (min/max), Zielgruppe, Stil
- **Admin-Backend:** CRUD unter `/admin/speakers`
- **Member-Dashboard:** Eigenes Speaker-Profil
- **Shortcode:** `[cms_speakers]`
- **Templates:** `archive-speaker.php`, `speaker-card.php`, `single-speaker.php`
- **Hooks:** `speaker_created`, `speaker_updated`, `speaker_presentation_added`
- **Filter:** `speaker_card_content`, `speaker_query_args`
- **Sicherheit:** CSRF, PDO Prepared Statements, XSS-Escaping
