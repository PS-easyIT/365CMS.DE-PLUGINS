# Changelog – CMS Contact

Alle nennenswerten Änderungen an diesem Plugin werden hier dokumentiert.

## [3.0.3] – 2026-08-23

### Hinzugefügt

- Jedes Kontaktformular besitzt eine eigene, formatierbare „Kontaktformular Footer-Beschreibung“.
- Die Footer-Beschreibung wird in allen sechs Standard- und vier Booking-Templates unter Formularfeldern und Absende-Button ausgegeben.
- Datenbankmigration `5` ergänzt `contact_forms.footer_description` automatisch bei bestehenden Installationen.

### Verbessert

- Beide Beschreibungseditoren verwenden dieselbe EditorJS-Asset-Instanz und laden ihre umfangreichen Assets dadurch nur einmal pro Adminseite.
- Das Admin-Design folgt jetzt dem ruhigen Matrix-/Azure-Muster mit 25-px-Abschnittsrhythmus, 10-px-Radien, dezenten Flächen, einfachen Rahmen und ohne schwebende Kartenanimationen.

## [3.0.2] – 2026-08-23

### Hinzugefügt

- Formularbeschreibungen nutzen beim Erstellen und Bearbeiten den zentralen EditorJS-Blockeditor des CMS.

### Geändert

- Beschreibungen werden serverseitig als sichere EditorJS-Blockdaten gespeichert und in allen zehn Public-Templates formatiert ausgegeben; vorhandener Plaintext bleibt kompatibel.
- Der Admin-Inhaltsbereich ist schlichter gestaltet und besitzt links sowie rechts jeweils `10px` Abstand.

## [3.0.1] – 2026-05-31

### Geändert

- Der Admin-Menüeintrag wird in der Core-Sidebar mit `365CMS | ` vorangestellt, damit 365CMS-Plugins gemeinsam sortiert werden.

## [Unreleased] – 2026-05-15

### Sicherheitsfixes

- Public- und AJAX-Routen senden zusätzliche Security-Header (`nosniff`, `SAMEORIGIN`, restriktive Referrer-Policy), solange noch keine Header verschickt wurden.
- Legacy-Redirects von `/kontakt` kodieren Query-Parameter per `http_build_query()` und bleiben strikt auf lokale `/contact`-Ziele begrenzt.
- Formular-Redirects werden auch ohne Core-Helfer nur noch als interne Pfade akzeptiert; externe Fallback-Redirects werden verworfen.
- Öffentliche Template-CSRF-Attribute und zentrale Admin-CSRF-Attribute werden explizit mit `ENT_QUOTES` und `UTF-8` escaped.
- URL-Felder akzeptieren serverseitig nur noch `http`/`https`; Map-Embeds sind auf Google-Maps-Embed und OpenStreetMap-Embed-Allowlist begrenzt.

### Design & Performance

- Public-Templates wurden von dekorativer Emoji-UI in Alerts, Buttons und Buchungsbadges bereinigt und bleiben dadurch ruhiger im PHINIT-Layout.
- JSON-Antworten des AJAX-Endpunkts verwenden konsistente UTF-8-/Slash-Flags und vermeiden unnötige Encoding-Artefakte.

## [1.1.9] – 2026-05-15

### Fixed

- Deutschsprachige Legacy-Links unter `/kontakt` und `/kontakt/{slug}` leiten jetzt per 301 auf die kanonischen Kontaktformular-Routen `/contact` bzw. `/contact/{slug}` weiter. Dadurch laufen bestehende Theme- oder Menüeinträge nicht mehr in eine 404-Seite.

## [1.1.8] – 2026-05-09

### Sicherheitsfixes

- Öffentliche Kontaktformulare nutzen jetzt zusätzlich den zentralen `CMS\Services\AntispamService`, sodass globale AntiSpam-Regeln aus `/admin/antispam` auch für `cms-contact` greifen.
- Die Formular-Templates senden dafür einen serverseitig prüfbaren Start-Timestamp mit, damit `antispam_min_time` nicht länger nur bei Kommentaren wirkt.

### Verbessert

- Das Kontakt-Plugin behält weiterhin sein lokales Mathe-Captcha und das sessionbasierte Erfolgs-Rate-Limit, ergänzt diese Prüfungen jetzt aber um denselben globalen Blacklist-/Linklimit-/User-Agent-Vertrag wie der Core.

## [1.1.7] – 2026-05-03

### Sicherheitsfixes

- Repo-weites Snyk-Audit erneut bestätigt: `cms-contact` bleibt ohne offene Findings.
- Der Installer-Hotfix mit eindeutigen Foreign-Key-Namen ist als aktueller Audit-Stand dokumentiert und bleibt Teil der abgesicherten Neuinstallations-Story.

## [1.1.6] – 2026-04-04

### Fixed

- Der Installer erzeugt seine Foreign-Key-Constraints jetzt mit präfix- und tabellenspezifischen Namen statt mit generischen Bezeichnern wie `fk_field_form` oder `fk_submission_form`.
- Neuinstallationen und Erstaktivierungen scheitern dadurch nicht mehr mit MySQL/InnoDB-Fehler `errno: 121` („Duplicate key on write or update“), wenn im selben Schema bereits gleichnamige Constraints existieren.

### Geändert

- `cms-contact` wurde auf Version `1.1.6` angehoben und das Update-Manifest auf den Hotfix-Stand synchronisiert.

## [1.1.5] – 2026-03-29

### Sicherheitsfixes

- Member-Redirects härten die Zielpfade jetzt auf den internen Kontaktbereich, sodass manipulierte Request-URLs keine offenen Weiterleitungen mehr auslösen.
- Formular-CSS wird vor der Ausgabe zusätzlich bereinigt, um problematische Konstrukte wie `@import`, `expression()` oder `javascript:` im Inline-Style-Block zu entschärfen.

### Verbessert

- Die globale Empfängeradresse aus den Kontakt-Einstellungen greift jetzt auch wirklich für Benachrichtigungen; ältere Installationen mit `global_recipient` bleiben kompatibel.
- Bestätigungs-E-Mails werden nur noch verschickt, wenn die Option im Admin aktiviert ist.
- Serverseitige Feldfehler bleiben nach Redirect erhalten und werden direkt am betroffenen Formularfeld angezeigt.
- Die Submission-Liste lädt Zusatz-Metadaten jetzt gesammelt statt pro Zeile einzeln und vermeidet damit unnötige N+1-Datenbankabfragen.
- Member-Ansichten laden Formular-Titel jetzt per Join mit und zeigen dadurch verwendete Formulare konsistent an.
- Die Feldverwaltung validiert Templates, Breiten und Regex-Formate strenger und blendet Options-Editoren nur noch bei tatsächlich unterstützten Auswahlfeldern ein.

### Geändert

- Die irreführende, aber nicht implementierte Auswahl `Datei-Upload` wird im Feld-Builder nicht länger angeboten.
- Die Settings-Seite respektiert den aktiven Tab jetzt auch nach POST-Aktionen und Wartungs-Tasks.

## [1.1.4] – 2026-03-28

### Geändert

- Kontaktanfragen speichern wieder die technische Absender-IP in `contact_submissions`, damit eingereichte Anfragen im Admin nachvollziehbar bleiben.
- Bestehende Installationen ergänzen die Spalte `ip_address` nun per Migration automatisch erneut, statt sie weiter zu entfernen.
- Die Freitextsuche in den Einreichungen findet jetzt zusätzlich auch nach gespeicherter IP-Adresse.

### Verbessert

- Nachrichtenliste, Detailansicht und Benachrichtigungs-E-Mail zeigen die erfasste IP-Adresse gemeinsam mit dem User-Agent an.

## [1.1.3] – 2026-03-19

### Geändert

- Die Speicherung von IP-Adressen in `cms-contact` wurde vollständig entfernt; bestehende Installationen räumen die veraltete Spalte per Migration auf.
- Das bisherige IP-basierte Rate-Limiting wurde auf eine sessionbasierte Begrenzung erfolgreicher Einsendungen umgestellt.
- Alle Frontend-Formulare verlangen jetzt standardmäßig eine ausdrückliche Datenschutz-Einwilligung mit Link zur Datenschutzerklärung.
- Die Admin-Ansicht zeigt bei Nachrichten jetzt sichtbar an, ob und wann die Datenschutz-Einwilligung bestätigt wurde.

### Hinzugefügt

- Neue globale Einstellungen für `Datenschutz-URL` und `Datenschutz-Einwilligung verpflichtend`.

## [1.1.2] – 2026-03-17

### Geändert

- Die restlichen Admin-Views (`Neues Formular`, Formularliste, Nachrichtenliste, Nachrichten-Detail) verwenden jetzt ebenfalls konsequent `contact-admin.css` und `contact-admin.js` statt zusätzlicher Inline-Styles oder Inline-Skripte.
- Template-Auswahl, Lösch-Modal, Bulk-Checkboxen und Status-/Detail-Aktionen wurden auf zentrale Datenattribute und wiederverwendbare Admin-JavaScript-Initialisierung umgestellt.

### Verbessert

- Konsistentere Admin-Layouts für Formulare, Listen, Pagination und Modale vereinfachen Wartung, reduzieren Markup-Rauschen und halten die Plugin-Views näher an den 365CMS-Admin-Konventionen.

## [1.1.1] – 2026-03-14

### Geändert

- Installer und Versionsverwaltung an die aktuellen 365CMS-Settings-Spalten `option_name` / `option_value` angepasst
- Plugin-Metadaten für das 365CMS-Kompatibilitätsupdate aktualisiert

## [1.1.0] – 2025-06-28

### Hinzugefügt

- **4 Booking-Templates** für das CMS-Booking-Plugin:
  - `booking-simple` – Schlichtes Buchungs-Kontaktformular
  - `booking-expert` – Split-Layout für Experten-Beratung
  - `booking-event` – Event-/Speaker-Buchungsformular
  - `booking-service` – Business-Dienstleistungsbuchung
- Dynamische Template-Erkennung: Booking-Templates werden nur angezeigt, wenn die Template-Dateien vorhanden sind
- CMS-Contact funktioniert weiterhin vollständig ohne CMS-Booking

## [1.0.0] – 2025-06-27

### Hinzugefügt

- **Kontaktformular-Builder** mit Drag-and-Drop-Feldverwaltung
- **6 Design-Templates**: Classic, Modern, Split Screen, Minimal, Business, Fullwidth
- **Formular-Engine**: 8 Feldtypen (Text, E-Mail, Telefon, Textarea, Select, Checkbox, Radio, Hidden)
- **Einreichungsverwaltung** mit Status-Workflow und Sternmarkierung
- **E-Mail-Benachrichtigungen** an Admin und Absender (Auto-Reply)
- **Spam-Schutz**: Honeypot-Feld und Mathe-Captcha
- **Statistik-Dashboard** mit Balkendiagramm (letzte 30 Tage)
- **Admin-Backend** mit 5 Bereichen: Dashboard, Formulare, Einreichungen, Templates, Einstellungen
- **DSGVO-konform**: Datenexport und -löschung via CMS-Hooks
- CSRF-Token-Schutz in allen Formularen
- Responsives Design für alle Templates
