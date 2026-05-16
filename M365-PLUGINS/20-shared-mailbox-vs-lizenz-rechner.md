# Shared-Mailbox vs. Lizenz-Rechner

- **Priorität:** hoch
- **Datenquelle:** Microsoft Learn / Exchange / Purview + eigene Regeldatei + `pricing.json`
- **Aufwand:** niedrig bis mittel
- **SEO-Potenzial:** ★★★
- **Empfohlener Slug:** `/shared-mailbox-vs-lizenz`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Der Rechner soll **nicht nur** zwischen „Shared Mailbox“ und „lizenzierter Mailbox“ unterscheiden, sondern belastbar beantworten:

1. **Ist eine Shared Mailbox fachlich geeignet?**
2. **Reicht eine Shared Mailbox ohne Zusatzlizenz?**
3. **Braucht die Shared Mailbox wegen Größe / Archiv / Hold / Compliance doch eine Lizenz oder ein Add-on?**
4. **Ist stattdessen eine reguläre Benutzer-Mailbox sinnvoller?**
5. **Ist eigentlich eine Microsoft-365-Group die bessere Lösung?**

Der Rechner ist damit gleichzeitig:

- Entscheidungshelfer
- Lizenz-Hinweis-Tool
- Sparpotenzial-Rechner
- Lead-Magnet für Audit, Cleanup und Tenant-Hygiene

## Verifizierte Microsoft-Leitplanken

Die folgenden Regeln sollten **hart** im Tool hinterlegt werden, weil sie direkt aus Microsoft-Dokumentation ableitbar sind.

### Shared Mailbox ohne Zusatzlizenz

- Eine Shared Mailbox kann **bis 50 GB** ohne eigene Lizenz betrieben werden.
- Benutzer, die auf die Shared Mailbox zugreifen, brauchen **selbst** eine lizenzierte Exchange-Online-Mailbox.
- Shared Mailboxes sind für **interne Benutzer** gedacht; externe Gmail-/Gast-Zugriffe sind kein regulärer Shared-Mailbox-Anwendungsfall.

### Wann eine Zusatzlizenz nötig ist

- **Mehr als 50 GB**: Lizenz nötig.
- **In-Place Archiving / Archivpostfach**: Lizenz nötig.
- **Litigation Hold / In-Place Hold**: Lizenz nötig.
- **Auto-expanding archiving**: nur mit passender Archiv-/Lizenzkombination.
- **Erweiterte Features** wie Microsoft Defender for Office 365, Purview eDiscovery (Premium) oder Retention-/Compliance-Funktionen können zusätzliche oder passende Feature-Lizenzen erfordern.

### Größen- und Archivierungsregeln

- Ohne Lizenz: Shared Mailbox **50 GB**.
- Mit passender Lizenz: **100 GB** Primärpostfach möglich.
- Mit Exchange Online Archiving / passender Berechtigung: Archivpostfach und bei Auto-Expanding bis **1,5 TB**.
- Auto-Expanding ist nur für **individuelle Benutzerpostfächer oder Shared Mailboxes** vorgesehen, **nicht** als Sammelarchiv für viele Personen.

### Nutzungs- und Governance-Regeln

- Shared Mailboxes sind **nicht** für direkten Login gedacht; das zugehörige Konto sollte **blockiert** bleiben.
- Microsoft nennt **25 Benutzer** als praktische Obergrenze; bei zu vielen gleichzeitigen Nutzern drohen Verbindungsprobleme oder doppelte Nachrichten.
- Man kann Benutzer **nicht zuverlässig am Löschen von Nachrichten hindern**; wenn genau das gefordert ist, ist eine Microsoft-365-Group oder ein anderes Modell oft besser.
- E-Mail aus einer Shared Mailbox kann laut Microsoft-Doku problematisch für Verschlüsselung sein, weil die Mailbox keinen eigenen Sicherheitskontext wie ein normales User-Konto hat.

### Outlook / Delegation / Automapping

- Für einen brauchbaren Betrieb sind meistens **Full Access** plus **Send As** relevant.
- **Automapping** funktioniert standardmäßig bei expliziten Benutzerberechtigungen, **nicht sauber über Security Groups**.
- Wenn die Shared Mailbox in der GAL versteckt wird, kann das das spätere Hinzufügen in Outlook für neue Mitglieder erschweren.

### Konvertierung bestehender Benutzer-Mailboxen

- Vor der Konvertierung zu Shared muss die Mailbox **lizenziert** sein.
- Nach der Konvertierung kann die Lizenz entfernt werden, **wenn** die Mailbox klein genug ist und keine lizenzpflichtigen Features verwendet.
- Das Benutzerkonto darf nicht einfach gelöscht werden; es dient als **Anchor** der Shared Mailbox.
- Wenn das frühere Benutzerkonto bestehen bleibt, muss der **Sign-in konsequent blockiert** werden.

### Abgrenzung zu Microsoft 365 Groups

- Wenn externe Zusammenarbeit, breitere Kollaboration, Planner/SharePoint/Dateien, dynamische Mitgliedschaft oder größere Gruppen-Szenarien wichtig sind, ist häufig **Microsoft 365 Group** statt Shared Mailbox die bessere Empfehlung.

## Ziel des Tools

Hilft bei der Entscheidung, wann eine Shared Mailbox ausreicht, wann eine **lizenzierte Shared Mailbox** nötig ist, wann eine **reguläre Benutzer-Mailbox** besser passt und wann stattdessen eine **Microsoft-365-Group** empfohlen werden sollte.

## Gewünschte Ergebnis-Kategorien

Der Rechner sollte nicht nur „ja / nein“ liefern, sondern diese fünf Zielzustände unterscheiden:

1. `✅ Shared Mailbox ohne Zusatzlizenz geeignet`
2. `🟡 Shared Mailbox geeignet, aber mit Zusatzlizenz / Add-on`
3. `🟠 Grenzfall – Shared Mailbox technisch möglich, Governance prüfen`
4. `🔴 Reguläre Benutzer-Mailbox empfohlen`
5. `🔵 Microsoft 365 Group statt Shared Mailbox empfohlen`

## Kernfunktionen

- Entscheidungsassistent mit praxisnahen Fragen zu Login, Größe, Archiv, Compliance, Delegation und Collaboration
- Trennung zwischen **fachlicher Eignung** und **Lizenzpflicht**
- Empfehlung `Shared Mailbox`, `Shared Mailbox + Lizenz`, `lizenzierte Mailbox` oder `Microsoft 365 Group`
- Kostenersparnis gegenüber voll lizenzierter Lösung
- Warnungen zu 50-GB-Grenze, 25-Nutzer-Praxisgrenze, Archiv, Hold, Automapping und Hidden-GAL-Sonderfällen
- Sonderpfad für **Konvertierung einer bestehenden Benutzer-Mailbox**
- Sonderpfad für **Hybrid-Umgebungen**
- Ausgabe mit klaren Begründungen und Microsoft-Hinweisen
- PDF-Export und Audit-/Beratungs-CTA

## Eingaben

### Pflichtfelder

- Mailbox-Zweck
  - `info@`
  - `support@`
  - `sales@`
  - `reception@`
  - Funktionspostfach allgemein
  - ehemalige Benutzer-Mailbox
- eigenes Login für die Mailbox nötig `ja/nein`
- Anzahl interner Nutzer mit Zugriff
- erwartete Größe jetzt
- erwartete Größe in 12 Monaten

### Fachliche Entscheidungsfelder

- externe Benutzer sollen direkt zugreifen `ja/nein`
- nur intern oder auch externe Zusammenarbeit nötig
- Send As / Send on Behalf nötig
- gemeinsamer Kalender wichtig `ja/nein`
- Löschschutz / revisionsnahe Nutzung gewünscht `ja/nein`
- Mailbox soll mobil genutzt werden `ja/nein`
- Outlook-Automapping gewünscht `ja/nein`

### Lizenz- / Compliance-Felder

- Archivpostfach nötig `ja/nein`
- Litigation Hold / In-Place Hold nötig `ja/nein`
- Retention Policy / Purview-Funktionen nötig `ja/nein`
- Defender for Office 365 / erweiterte Security-Funktionen nötig `ja/nein`
- Verschlüsselung als Mailbox-Anforderung relevant `ja/nein`

### Betriebsfelder

- Cloud-only oder Hybrid
- bestehende Benutzer-Mailbox soll konvertiert werden `ja/nein`
- aktueller Lizenztyp des Kontos / Ziel-SKU
- Anzahl betroffener Mailboxen

## Ausgaben

- klare Hauptempfehlung
- juristisch/fachlich verständliche Begründung
- Liste der auslösenden Regeln
- Ergebnisstatus: lizenzfrei / lizenzpflichtig / reguläre Mailbox / Group
- geschätztes Einsparpotenzial gegenüber einer voll lizenzierten Benutzer-Mailbox
- Hinweis, welche Zusatzlizenz oder welches Add-on nötig wäre
- Sonderhinweise für Hybrid, Konvertierung, Automapping, Hidden GAL und direkte Anmeldung
- CTA: `Lizenz-Audit anfragen`, `Shared-Mailbox-Bestand prüfen`, `PDF exportieren`

## Entscheidungslogik

### Harte Ausschlussregeln für „Shared Mailbox ohne Zusatzlizenz“

- eigenes Benutzer-Login erforderlich
- Postfachgröße > `50 GB`
- Archivpostfach benötigt
- Litigation Hold / In-Place Hold benötigt
- Purview-/Retention-/Defender-Funktionen benötigt, die eigene Lizenzierung verlangen

### Harte Ausschlussregeln für „Shared Mailbox generell“

- externe Personen sollen direkt als Benutzer auf die Mailbox zugreifen
- Szenario ist eigentlich eine persönliche Arbeitsmailbox eines einzelnen Mitarbeiters
- Collaboration-Anforderung ist breiter als Mail + Kalender und erfordert eher Group-/Workspace-Funktionen

### Regeln für „Microsoft 365 Group statt Shared Mailbox“

- externe Zusammenarbeit ist gewünscht
- viele Nutzer / großes Kollaborationsszenario
- Planner/Dateien/SharePoint/Workspace sind Teil des Bedarfs
- Löschverhalten soll weniger mailboxzentriert sein

### Regeln für „Shared Mailbox mit Zusatzlizenz“

- Shared-Mailbox-Use-Case ist fachlich korrekt, **aber**
  - > 50 GB erwartet
  - Archive Mailbox gewünscht
  - Litigation Hold nötig
  - Auto-Expanding Archiving nötig
  - Retention-/Purview-/Defender-Features lizenzpflichtig

### Regeln für „Reguläre Benutzer-Mailbox empfohlen“

- dedizierte Identität und persönlicher Login nötig
- persönlicher, individueller Arbeitskontext statt Funktionspostfach
- mailboxbezogene Verschlüsselung / persönlicher Sicherheitskontext erforderlich
- Alt-User-Mailbox soll weiter als echte Benutzeridentität bestehen

## Bewertungsmodell

Neben den harten Regeln sollte das Tool mit einem einfachen Score arbeiten:

- `+3` klassischer Funktionspostfach-Use-Case
- `+2` mehrere interne Bearbeiter
- `+2` gemeinsamer Kalender genügt
- `-4` eigenes Login nötig
- `-4` externe direkte Zugriffe nötig
- `-3` >25 aktive Nutzer
- `-3` erweiterte Compliance/Retention/Hold nötig
- `-2` breite Kollaboration statt gemeinsamer Inbox

Der Score entscheidet **nicht allein**, sondern erklärt Grenzfälle.

## Benötigte Daten

### 1. Regeldatei `shared_mailbox_rules.json`

- `requires_direct_login`
- `requires_external_access`
- `max_unlicensed_storage_gb`
- `practical_user_limit`
- `supports_archive`
- `supports_litigation_hold`
- `supports_autoexpanding_archive`
- `requires_group_instead_flags`

### 2. Lizenz-Matrix `mailbox_license_matrix.json`

Nicht auf Produktnamen hart verdrahten, sondern auf Fähigkeiten mappen:

- `includes_exchange_mailbox`
- `includes_exo_plan1`
- `includes_exo_plan2_features`
- `supports_archive_addon`
- `supports_litigation_hold`
- `supports_retention`
- `supports_defender_o365`
- `price_monthly`
- `price_annual`

### 3. Use-Case-Bibliothek `shared_mailbox_scenarios.json`

- `info@`
- `support@`
- `sales@`
- `reception@`
- `ehemaliger-mitarbeiter`
- `team-assistenz`
- `projektpostfach`

Je Datensatz:

- Kurzbeschreibung
- empfohlener Standardpfad
- typische Warnhinweise
- empfohlene CTA

### 4. Preisdatei `pricing.json`

Mindestens für:

- Exchange Online Plan 1
- Exchange Online Plan 2
- Exchange Online Archiving Add-on
- relevante M365-Suiten, falls du im Ergebnis Bundle-Empfehlungen nennen willst

## Verifizierte Weblinks / Quellenbasis

### A. Kernwissen Shared Mailbox

1. **About shared mailboxes in Microsoft 365**  
	https://learn.microsoft.com/en-us/microsoft-365/admin/email/about-shared-mailboxes?view=o365-worldwide  
	Relevanz: Grundregeln, 50-GB-Limit, interne Nutzung, 25-User-Praxisgrenze, Sign-in, Verschlüsselung, Grenzen.

2. **Create a shared mailbox**  
	https://learn.microsoft.com/en-us/microsoft-365/admin/email/create-a-shared-mailbox?view=o365-worldwide  
	Relevanz: Erstellung, Delegation, Send As / Send on Behalf, Automapping-Hinweise, Lizenz entfernen.

3. **Configure Microsoft 365 shared mailbox settings**  
	https://learn.microsoft.com/en-us/microsoft-365/admin/email/configure-a-shared-mailbox?view=o365-worldwide  
	Relevanz: Sent Items, Forwarding, Automatic Replies, Litigation Hold, GAL-Sichtbarkeit, Permissions.

4. **Convert a user mailbox to a shared mailbox**  
	https://learn.microsoft.com/en-us/microsoft-365/admin/email/convert-user-mailbox-to-shared-mailbox?view=o365-worldwide  
	Relevanz: Konvertierung, Anchor-Account, Lizenz vor/nach Conversion, Passwort-/Sign-in-Risiken.

### B. Limits und Lizenzregeln

5. **Exchange Online limits**  
	https://learn.microsoft.com/en-us/office365/servicedescriptions/exchange-online-service-description/exchange-online-limits  
	Relevanz: Mailbox-Storage-Limits, Shared-Mailbox-Limits, Hold-/Archive-Regeln, 50/100 GB, 1.5-TB-Archiv.

6. **Exchange Online Archiving service description**  
	https://learn.microsoft.com/en-us/office365/servicedescriptions/exchange-online-archiving-service-description/exchange-online-archiving-service-description  
	Relevanz: Welche Pläne/Add-ons Archivierung unterstützen, Auto-Expanding, Hold, Einzelentitäts-Regel.

7. **Learn about auto-expanding archiving**  
	https://learn.microsoft.com/en-us/microsoft-365/compliance/autoexpanding-archiving  
	Relevanz: Funktionsweise, 1.5 TB, Limitierungen, Growth-Rate, Such-/Hold-Auswirkungen.

8. **Microsoft 365 security & compliance licensing guidance**  
	https://learn.microsoft.com/en-us/office365/servicedescriptions/microsoft-365-service-descriptions/microsoft-365-tenantlevel-services-licensing-guidance/microsoft-365-security-compliance-licensing-guidance  
	Relevanz: Referenz für Purview-/Defender-/Compliance-Zusatzlizenzierung.

### C. Compliance / Hold

9. **Place a mailbox on Litigation Hold**  
	https://learn.microsoft.com/en-us/microsoft-365/compliance/ediscovery-create-a-litigation-hold?view=o365-worldwide  
	Relevanz: Hold-Verhalten, Recoverable Items, Archivbezug, Admin-Implikationen.

### D. Abgrenzung zu Alternativen

10. **Compare types of groups in Microsoft 365**  
	 https://learn.microsoft.com/en-us/microsoft-365/admin/create-groups/compare-groups?view=o365-worldwide  
	 Relevanz: Wann besser Microsoft 365 Group statt Shared Mailbox.

### E. Hybrid / Exchange Admin / PowerShell

11. **Create shared mailboxes in the Exchange admin center**  
	 https://learn.microsoft.com/en-us/Exchange/collaboration/shared-mailboxes/create-shared-mailboxes?preserve-view=true.&view=o365-worldwide  
	 Relevanz: Hybrid-/On-Prem-/EAC-Pfad, Delegation, Send on Behalf per PowerShell.

12. **Block Microsoft 365 user accounts with PowerShell**  
	 https://learn.microsoft.com/en-us/microsoft-365/enterprise/block-user-accounts-with-microsoft-365-powershell?view=o365-worldwide  
	 Relevanz: Direktanmeldung des Shared-Mailbox-Kontos blockieren.

13. **How to remove automapping for a shared mailbox**  
	 https://learn.microsoft.com/en-us/office365/troubleshoot/administration/remove-automapping-for-shared-mailbox  
	 Relevanz: Automapping-Randfälle, Performance-Hinweise, Outlook-Support.

### F. Offizielle Microsoft-Referenzen für Preis-/Planpflege

Diese Links sind offiziell von Microsoft referenziert. Die Inhalts-Extraktion war in dieser Session teilweise redirect-/rendering-anfällig, sie sollten aber als Pflegequellen in die Doku:

14. **Microsoft 365 Business Plans (offizieller Vergleich)**  
	 https://aka.ms/M365BusinessPlans

15. **Microsoft 365 Enterprise Plans (offizieller Vergleich)**  
	 https://aka.ms/M365EnterprisePlans

16. **Exchange Online Archiving Produktseite**  
	 https://www.microsoft.com/microsoft-365/exchange/microsoft-exchange-online-archiving-email

17. **Exchange Online Planvergleich**  
	 https://www.microsoft.com/microsoft-365/exchange/compare-microsoft-exchange-online-plans

> Empfehlung: Preise **nicht live scrapen**, sondern zentral in `pricing.json` manuell pflegen und mit Quartals-Review gegen diese Referenzlinks abgleichen.

## Tool-Flow

### Schritt 1 – Use Case & Zugriff

- Worum geht es? Funktionspostfach, Support, ehemaliger Mitarbeiter, persönlicher Benutzer?
- eigenes Login nötig?
- interne oder externe Nutzer?
- wie viele Nutzer greifen zu?

### Schritt 2 – Größe, Archiv, Compliance

- aktuelle / erwartete Größe
- Archiv nötig?
- Hold / Retention / eDiscovery nötig?
- erweiterte Security-/Purview-Anforderungen?

### Schritt 3 – Betrieb & Ergebnis

- Cloud-only vs. Hybrid
- bestehende Mailbox konvertieren?
- Ergebnisstatus
- Kosten / Einsparung / nächste Schritte

## UI/UX nach `cms-m365lic`

- kompakter Hero mit klarem Problemstatement: `Braucht diese Shared Mailbox wirklich eine Lizenz?`
- 3-stufiger Mini-Wizard statt langem Formular
- dunkler Hero in Navy/Gold/Teal
- KPI-Karten für:
  - Empfehlung
  - Lizenzbedarf
  - Einsparpotenzial
  - Risiko-/Grenzfallstatus
- Ergebnis-Karte mit Ampel und Begründungsliste
- zusätzlicher Bereich `Wichtige Microsoft-Hinweise`
- Szenarien-Karten: `info@`, `support@`, `ehemaliger Mitarbeiter`, `Teampostfach`

## Ergebnislogik / Textbausteine

### `Shared Mailbox ohne Zusatzlizenz geeignet`

- kein eigener Login
- rein internes Funktionspostfach
- <= 50 GB
- kein Archiv / Hold / Purview-Sonderfall

### `Shared Mailbox mit Zusatzlizenz`

- Funktionspostfach passt fachlich
- aber > 50 GB oder Archiv / Hold / Compliance nötig

### `Reguläre Mailbox empfohlen`

- persönliche Identität / eigenes Login nötig
- mailboxspezifische Sicherheits- oder Personenanforderung

### `Microsoft 365 Group empfohlen`

- externe Zusammenarbeit / Workspace-Funktionen / größere Kollaboration nötig

## Benötigte Bausteine / Funktionen

- `load_shared_mailbox_rules()`
- `load_shared_mailbox_scenarios()`
- `load_mailbox_license_matrix()`
- `validate_shared_mailbox_input()`
- `evaluate_shared_mailbox_use_case()`
- `evaluate_shared_mailbox_license_need()`
- `evaluate_shared_mailbox_alternative()`
- `calculate_shared_mailbox_savings()`
- `build_shared_mailbox_result_viewmodel()`
- `render_shared_mailbox_page()`
- `export_shared_mailbox_pdf()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if requires_direct_login === true => regular_mailbox`
- `if external_access_required === true => m365_group_or_other`
- `if estimated_size_gb > 50 => shared_mailbox_with_license`
- `if archive_needed === true => shared_mailbox_with_license`
- `if litigation_hold_needed === true => shared_mailbox_with_license`
- `if advanced_compliance_needed === true => shared_mailbox_with_license_or_bundle_review`
- `if delegate_count > 25 => warning_or_group_recommendation`
- `if deletion_protection_required === true => group_or_process_warning`
- `if collaboration_workspace_required === true => m365_group`

## Admin / Pflege

- Pflege der Regeldatei und Lizenzmatrix
- Pflege der Preise in `pricing.json`
- Pflege von Standard-Szenarien und FAQ-Texten
- Pflege der Microsoft-Referenzlinks mit Standdatum
- quartalsweiser Review für:
  - Exchange Online Limits
  - Archiving Service Description
  - Purview-/Compliance-Lizenzhinweise

## SEO- und Content-Bausteine

### Fokus-Keywords

- `shared mailbox lizenz`
- `braucht shared mailbox eine lizenz`
- `shared mailbox 50 gb`
- `shared mailbox litigation hold`
- `shared mailbox archivierung`
- `shared mailbox vs microsoft 365 group`
- `user mailbox in shared mailbox umwandeln`

### Empfohlene FAQ-Blöcke

- Braucht eine Shared Mailbox in Microsoft 365 immer eine Lizenz?
- Was passiert bei mehr als 50 GB?
- Wann brauche ich Exchange Online Plan 2?
- Kann ich eine Shared Mailbox archivieren?
- Kann ein externer Nutzer auf eine Shared Mailbox zugreifen?
- Wann ist eine Microsoft-365-Group besser?
- Was passiert beim Umwandeln einer User-Mailbox in eine Shared Mailbox?

## MVP

- kompakter Wizard mit 8 bis 12 Fragen
- 5 Ergebniszustände
- einfache Sparpotenzialrechnung
- Microsoft-Hinweisblock
- PDF-Export

## Phase 2

- Sammelrechner für viele Mailboxen
- CSV-Import aus Auditlisten
- Gruppenanalyse `x Mailboxen im Bestand`
- spezielle Conversion-Variante für Offboarding
- Member-/Partner-Version mit EK-/VK-Sicht

## Akzeptanzkriterien

- Tool unterscheidet sauber zwischen **fachlicher Eignung** und **Lizenzpflicht**
- Tool weist explizit auf 50-GB-, Archiv- und Hold-Sonderfälle hin
- Tool empfiehlt in Collaboration-Fällen nicht blind Shared Mailbox, sondern ggf. Microsoft 365 Group
- Tool berücksichtigt Hybrid-Szenarien und Konvertierungsfälle
- Tool listet die auslösenden Regeln transparent auf
- alle Fachregeln sind im Admin oder in Datenfiles pflegbar

## Offene Pflegepunkte

- Preisquellen auf `microsoft.com` sind render-/redirect-anfällig; Preise deshalb manuell pflegen
- Produkt-/Lizenz-Mapping auf `Plan1`, `Plan2`, `Archiving Add-on` und Suite-Bundles sauber normalisieren
- Purview-/Retention-Lizenzfälle regelmäßig gegen Microsoft-Servicebeschreibungen prüfen