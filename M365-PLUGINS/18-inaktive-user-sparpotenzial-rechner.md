# Inaktive-User-Sparpotenzial-Rechner

- **Priorität:** hoch
- **Datenquelle:** Preisdatei + Offboarding-/Inactive-Mailbox-/Shared-Mailbox-Regeln + Audit-Heuristiken
- **Aufwand:** niedrig bis mittel
- **SEO-Potenzial:** ★★★
- **Empfohlener Slug:** `/inaktive-user-sparpotenzial`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Der Rechner zeigt, wie viel Sparpotenzial in inaktiven, pausierten oder ausgeschiedenen Konten steckt – aber ohne gefährliche Pauschalempfehlungen. Er soll nicht einfach „Lizenz weg!“ rufen, sondern zwischen Offboarding, Shared Mailbox, Inactive Mailbox und echtem Downgrade unterscheiden.

## Verifizierte Microsoft-Leitplanken

### Lizenz entfernen ist möglich – Datenfolgen müssen aber beachtet werden

- Wenn ein Benutzerkonto gelöscht wird, ist die zugehörige Lizenz unmittelbar wieder verfügbar.
- Beim Entfernen einer Lizenz gelten jedoch Daten- und Retentionsfolgen, etwa für Exchange Online und OneDrive.

### Shared Mailbox ist ein valider Sparpfad – aber nicht universell

- Eine Shared Mailbox kann bis **50 GB** ohne eigene Lizenz speichern.
- Für Shared Mailboxes über 50 GB, mit Archiv oder Litigation Hold sind zusätzliche Lizenzen erforderlich.
- Eine Shared Mailbox ist nicht für Direktanmeldung gedacht.
- Der Zugriff erfolgt über Benutzer mit eigener lizenzierter Exchange-Online-Mailbox.
- Für viele gleichzeitige Nutzer ist die Shared Mailbox nicht beliebig skalierbar; Microsoft nennt einen Richtwert von **25 Nutzern**.

### Inactive Mailbox ist der Compliance-Pfad für ausgeschiedene Mitarbeitende

- Um eine Mailbox inaktiv zu machen, muss zuerst ein Hold greifen und anschließend das Benutzerkonto / die Mailbox gelöscht werden.
- Danach kann die Exchange-Online-Lizenz wiederverwendet werden.
- Inactive Mailboxes sind der offizielle Weg, um Maildaten ehemaliger Mitarbeitender rechtskonform vorzuhalten.

### F1/F3 ist kein Standardpfad für „inaktiv“

- F1/F3 ist ein Lizenzpfad für aktive Frontline-User, nicht der Standard-Container für ausgeschiedene oder stillgelegte Konten.
- Ein Downgrade auf F1/F3 ist nur sinnvoll, wenn es sich um weiterhin aktive Nutzer mit passendem Frontline-Profil handelt.

## Ziel des Tools

Ein schneller Sparpotenzial-Rechner für inaktive, pausierte oder ausgeschiedene Konten mit Empfehlung zur Deaktivierung, Shared-Mailbox-Konvertierung, Inactive-Mailbox-Pfad oder – nur falls passend – Downgrade.

## Gewünschte Ergebnis-Kategorien

1. `✅ Lizenz sofort freisetzbar`
2. `🟡 Shared Mailbox sinnvoll`
3. `🔵 Inactive Mailbox / Retention-Pfad nötig`
4. `🟠 Downgrade auf leichtere aktive Lizenz prüfen`
5. `🔴 Manuelle Compliance-Prüfung nötig`

## Kernfunktionen

- Eingabe des inaktiven Nutzeranteils
- Vergleich `beibehalten`, `löschen`, `Shared Mailbox`, `Inactive Mailbox`, `Downgrade`
- Monats- und Jahresersparnis
- Risiko-Hinweis für Datenhaltung, Retention und Reaktivierung
- Verweis auf Frontline-Check nur bei aktiven Low-Need-Usern

## Eingaben

### Nutzerstatus

- ausgeschieden
- pausiert / Sabbatical
- saisonal inaktiv
- aktiv, aber stark untergenutzt

### Mail- / Datenbedarf

- Mailbox behalten ja/nein
- OneDrive / Dateien relevant ja/nein
- Retention / Hold / eDiscovery nötig ja/nein
- Team-Zugriff auf alte Mailadresse nötig ja/nein

### Lizenzdaten

- aktueller Durchschnittsplan
- Anzahl betroffener Konten
- gewünschte Zieloption

## Ausgaben

- Einsparpotenzial pro Monat / Jahr
- empfohlene Maßnahme je Statusgruppe
- Größenordnung des Audit-Hebels
- Compliance-/Risiko-Hinweise

## Entscheidungslogik

### 1. Nutzerstatus trennen

- endgültig ausgeschieden
- temporär pausiert
- noch aktiv, aber leichtgewichtig

### 2. Mail-/Retention-Bedarf prüfen

- Shared Mailbox ausreichend?
- Inactive Mailbox nötig?
- Daten können übertragen und Konto gelöscht werden?

### 3. Sparpfad ableiten

- Lizenz freisetzen
- Shared Mailbox
- Inactive Mailbox
- Downgrade / Rechteanpassung

## Bewertungsmodell

- `Lizenzkostenhebel`
- `Retention-Risiko`
- `Zugriffsbedarf`
- `Reaktivierungswahrscheinlichkeit`

## Benötigte Daten

### 1. `inactive_user_actions.json`

- Statusgruppe
- empfohlene Maßnahmen
- harte Ausschlüsse

### 2. `shared_mailbox_rules.json`

- 50-GB-Regel
- Archiv-/Hold-Hinweise
- Delegationsregeln

### 3. `pricing.json`

- Ist-Lizenzen
- mögliche Zielpfade

## Verifizierte Weblinks / Quellenbasis

1. **Assign or unassign licenses for users in the Microsoft 365 admin center**  
	https://learn.microsoft.com/en-us/microsoft-365/admin/manage/assign-licenses-to-users

2. **Delete a user from your organization**  
	https://learn.microsoft.com/en-us/microsoft-365/admin/add-users/delete-a-user

3. **About shared mailboxes in Microsoft 365**  
	https://learn.microsoft.com/en-us/microsoft-365/admin/email/about-shared-mailboxes

4. **Create and manage inactive mailboxes**  
	https://learn.microsoft.com/en-us/purview/create-and-manage-inactive-mailboxes

5. **Understand frontline worker user types and licensing**  
	https://learn.microsoft.com/en-us/microsoft-365/frontline/flw-licensing-options?view=o365-worldwide

## Tool-Flow

### Schritt 1 – Betroffene Konten klassifizieren

- ausgeschieden
- pausiert
- untergenutzt

### Schritt 2 – Daten- und Mailbedarf prüfen

- Shared Mailbox?
- Inactive Mailbox?
- komplettes Entfernen?

### Schritt 3 – Sparpotenzial ausgeben

- monatlich
- jährlich
- nächste Admin-Schritte

## UI/UX nach `cms-m365lic`

- minimalistisch und conversion-stark
- großer Einsparungsblock im Ergebnis
- Maßnahmenkarten mit `sicher`, `prüfen`, `nicht empfohlen`
- CTA zur Lizenzprüfung / Audit

## Ergebnislogik / Textbausteine

### `Lizenz freisetzen`

- kein aktiver Bedarf
- keine besonderen Retention-Anforderungen

### `Shared Mailbox sinnvoll`

- Team muss weiter Zugriff auf Mailadresse haben
- Grenzen und Lizenzfolgen bleiben im grünen Bereich

### `Inactive Mailbox nötig`

- Mitarbeiter ausgeschieden
- Maildaten müssen aus Compliance-Gründen erhalten bleiben

## Benötigte Bausteine / Funktionen

- `calculate_inactive_user_savings()`
- `classify_inactive_user_path()`
- `build_inactive_user_recommendation()`
- `render_inactive_user_savings_page()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if employee_left && retention_required === true => inactive_mailbox_path`
- `if mailbox_needed_for_team && mailbox_size <= 50GB && no_hold === true => shared_mailbox_path`
- `if user_still_active && lightweight_mobile_only === true => downgrade_review`
- `if shared_mailbox_has_archive_or_hold === true => additional_license_warning`
- `if deleting_user_but_onedrive_data_needed === true => transfer_data_warning`

## Admin / Pflege

- Preispflege
- Pflege der Standardempfehlungen
- Pflege von Compliance-Hinweisen

## SEO- und Content-Bausteine

### Fokus-Keywords

- `inaktive user lizenzen sparen`
- `m365 ausgeschiedene mitarbeiter lizenz`
- `shared mailbox statt benutzerlizenz`
- `inactive mailbox microsoft 365`

### Empfohlene FAQ-Blöcke

- Kann ich die Lizenz eines ausgeschiedenen Mitarbeiters sofort entfernen?
- Wann ist eine Shared Mailbox ausreichend?
- Wann brauche ich eine Inactive Mailbox?
- Warum ist F1/F3 kein Standardpfad für inaktive Nutzer?

## MVP

- 4 Maßnahmenoptionen
- Einsparungsrechnung
- Compliance-Hinweise
- CTA zum Audit

## Phase 2

- Branchen-Benchmarks
- PDF-Summary
- Kombi mit Frontline-Check
- CSV-Import aus Userlisten

## Akzeptanzkriterien

- Tool unterscheidet korrekt zwischen Shared Mailbox, Inactive Mailbox und echtem Löschen.
- F1/F3 wird nicht fälschlich als generischer Inaktiv-Pfad empfohlen.
- Daten- und Retentionsfolgen werden sichtbar gemacht.
- Ergebnis liefert konkrete Einsparung plus nächste Admin-Schritte.

## Offene Pflegepunkte

- OneDrive-/Retention-Hinweise bei Deleted Users weiter ausbauen.
- Pricing-Mappings mit tatsächlichen Zielplänen pflegen.
- Shared-Mailbox-Sonderfälle mit größerem Nutzerkreis beobachten.