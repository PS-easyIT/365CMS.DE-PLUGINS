# Teams Phone-Lizenz-Berater

- **Priorität:** hoch
- **Datenquelle:** Microsoft Learn + `pricing.json` + Provider-/Telefonie-Matrix + eigene Annahmen für SBC/Betrieb
- **Aufwand:** mittel bis hoch
- **SEO-Potenzial:** ★★★
- **Empfohlener Slug:** `/teams-phone-lizenzberater`
- **Stand der Quellenprüfung:** `15.05.2026`

## Zielbild

Das Tool soll **nicht nur** zwischen `Calling Plan`, `Operator Connect` und `Direct Routing` vergleichen, sondern belastbar beantworten:

1. **Hat der Nutzer überhaupt die richtige Teams-/Teams-Phone-Basislizenz?**
2. **Ist Microsoft als PSTN-Carrier sinnvoll oder sollte ein Drittanbieter / eigener Carrier genutzt werden?**
3. **Passt das Szenario eher zu `Calling Plan`, `Operator Connect`, `Direct Routing` oder einem Mischmodell?**
4. **Braucht der Kunde zusätzliche Bausteine wie `Audio Conferencing`, `Communication Credits`, `Shared Calling` oder Frontline-spezifische Varianten?**
5. **Welche technischen und betrieblichen Voraussetzungen machen ein Modell realistisch – und welche eher nicht?**

Der Berater ist damit gleichzeitig:

- Lizenz-Advisor
- PSTN-Konnektivitäts-Entscheider
- Kosten- und Komplexitätsvergleich
- Readiness-Check für Routing, Carrier und Betrieb
- Lead-Magnet für Voice-Assessment, Portierung und Tenant-Rollout

## Verifizierte Microsoft-Leitplanken

Die folgenden Regeln sollten im Tool als offizielle Leitplanken hinterlegt werden.

### Teams Phone ist nicht dasselbe wie PSTN-Konnektivität

- `Teams Phone Standard` ist ein **Add-on zu Teams** und liefert PBX-Funktionen wie Voicemail, Call Forwarding, Transfer, Auto Attendants und Call Queues.
- Ein PSTN-Modell ist **zusätzlich** nötig, wenn Nutzer externe Telefonnummern anrufen oder von dort erreicht werden sollen.
- Microsoft formuliert ausdrücklich: **Teams Phone-Lizenz und PSTN-Lösung sind getrennte Themen**.

### Calling Plan = Microsoft ist der PSTN-Carrier

- `Calling Plans` sind Microsofts Cloud-PSTN-Modell für Teams Phone.
- Es gibt `Domestic`, `International` und `Pay-As-You-Go Calling Plans`.
- Calling Plans sind **regionsabhängig**; Verfügbarkeit richtet sich nach dem Land / der Region der **User-Lizenz**, nicht nach der Organisationsadresse.
- Bei identischen Calling Plans im gleichen Land teilen Nutzer einen **gemeinsamen Minutenpool**.
- `Pay-As-You-Go Calling Plans` brauchen Finanzierung über:
	- `MCA` mit Post-Usage-Billing oder
	- `Communication Credits` bei passenden Vertragswelten.

### Operator Connect = Carrier-gemanagt, aber TeamsOnly nötig

- `Operator Connect` setzt einen teilnehmenden Provider aus dem Microsoft-Programm voraus.
- Microsoft nennt als Vorteile:
	- Nutzung bestehender Verträge
	- operator-managed Infrastruktur
	- schnellere Bereitstellung
	- Support über Carrier + Microsoft
- Für Zuweisung von Telefonnummern müssen Nutzer:
	- **Teams Phone lizenziert** sein
	- im **TeamsOnly**-Modus laufen

### Direct Routing = maximale Flexibilität, aber auch maximale Infrastrukturpflicht

- `Direct Routing` setzt laut Microsoft mindestens voraus:
	- `Teams Phone`
	- `Microsoft Teams`
	- **zertifizierten SBC**
	- öffentliche IP
	- öffentliches DNS
	- SBC-FQDN mit registrierter Tenant-Domain
	- öffentlich vertrauenswürdiges Zertifikat
- `*.onmicrosoft.com` darf **nicht** für den SBC-FQDN verwendet werden.
- `Direct Routing` wird von Microsoft **nicht in Islands Mode** unterstützt.
- Supportfälle laufen bei SBC-Themen zuerst über den **SBC-Hersteller / Vendor**.

### Mischmodelle sind offiziell möglich

- Microsoft dokumentiert ausdrücklich, dass `Direct Routing` mit `Calling Plan` oder `Operator Connect` für denselben User kombiniert werden kann.
- Das ist besonders relevant bei:
	- Third-Party-PBX-Anbindung
	- Sonderrouting
	- internationalen Filial- und Carrier-Szenarien

### Audio Conferencing ist ein separates Add-on

- `Audio Conferencing` ist **nicht** dasselbe wie `Teams Phone`.
- Für Dial-in-/Dial-out-Meeting-Szenarien braucht man ggf. ein separates Audio-Conferencing-Modell.
- Microsoft nennt außerdem `Communication Credits` als Mechanismus für Overage-/Dial-out-Szenarien.

### Frontline- und Shared-Calling-Sonderpfade sind real

- Für Frontline-Szenarien gibt es `Teams Phone Standard for Frontline Workers` mit klaren Nutzungsbedingungen.
- `Shared Calling` ist ein offizieller Sonderpfad für Nutzer, die **keine eigene Rufnummer** brauchen.
- Shared Calling nutzt die Nummer eines **Resource Accounts / Auto Attendant** und ist besonders sinnvoll für geringe PSTN-Nutzung und vereinfachtes Rufnummernmanagement.

## Ziel des Tools

Hilft bei der Auswahl des wirtschaftlich und technisch passenden Teams-Phone-Pfads und trennt dabei sauber zwischen:

- Basislizenzierung
- PSTN-Konnektivität
- Audio-/Overage-Zusatzthemen
- Carrier-/Infrastrukturaufwand
- Sonderpfaden wie `Shared Calling` oder `Frontline`

## Gewünschte Ergebnis-Kategorien

Der Berater sollte mindestens diese Zustände unterscheiden:

1. `✅ Calling Plan empfohlen`
2. `🟢 Operator Connect empfohlen`
3. `🟠 Direct Routing empfohlen`
4. `🟣 Mischmodell empfohlen`
5. `🔴 Sonderpfad / Architektur-Review nötig`

Zusätzlich kann ein Nebenergebnis markiert werden:

- `Audio Conferencing zusätzlich prüfen`
- `Communication Credits empfohlen`
- `Shared Calling statt persönlicher Nummer`
- `Frontline-Variante prüfen`

## Kernfunktionen

- Wizard für Voice-Szenarien nach Ländern, Nutzertypen und Carrier-Situation
- Prüfung der Teams-/Teams-Phone-Lizenzbasis
- Vergleich `Calling Plan`, `Operator Connect`, `Direct Routing` und Mischbetrieb
- Bewertung von Kosten, Komplexität, Portierungsaufwand, Flexibilität und Supportmodell
- Sonderpfad-Check für `Shared Calling`, `Audio Conferencing`, `Frontline`
- Ausgabe einer klaren Hauptempfehlung mit Begründung und technischer To-do-Liste
- CTA für Voice-Workshop, Portierung, Carrier-Abgleich und PDF-Export

## Eingaben

### Pflichtfelder

- Anzahl Nutzer
- Länder / Regionen / Standorte
- vorhandene Teams-Basislizenz
- Teams Phone bereits vorhanden `ja/nein`
- gewünschte externe Telefonie `ja/nein`

### Fachliche Entscheidungsfelder

- vorhandener Carrier soll bleiben `ja/nein`
- Carrier ist Operator-Connect-Teilnehmer `ja/nein/unbekannt`
- Microsoft als Carrier akzeptabel `ja/nein`
- bestehende PBX / Analoggeräte / Contact Center vorhanden `ja/nein`
- spezielle Routing-Regeln / Notruf-Sonderlogik nötig `ja/nein`
- persönliche Rufnummer für jeden Nutzer nötig `ja/nein`
- Dial-in-Meetings / Audio Conferencing relevant `ja/nein`
- Nutzer sind im TeamsOnly-Modus `ja/nein`

### Betriebsfelder

- eigener SBC / SBC-Provider vorhanden `ja/nein`
- internes Voice-/Netzwerk-Know-how vorhanden `ja/nein`
- Wunsch nach gemanagter Lösung `ja/nein`
- Anrufvolumen grob: gering / mittel / hoch
- viele Länder / Filialen / Sondercarrier `ja/nein`

### Zielgruppenfelder

- Wissensarbeiter / Frontline / Shared Devices / Common Area Phones
- Low-Volume-User ohne eigene DID `ja/nein`
- Resource-Account-/Auto-Attendant-Modell möglich `ja/nein`

## Ausgaben

- empfohlener PSTN-Hauptpfad
- erforderliche Basis- und Zusatzlizenzen
- grober Monats- und Jahreskostenrahmen
- Support- und Betriebsmodell
- technische Voraussetzungen und Blocker
- Portierungs- und Rollout-Hinweise
- Alternative Modelle und Sonderpfade

## Entscheidungslogik

### Regeln für `Calling Plan`

- Länderverfügbarkeit gegeben
- Microsoft darf Carrier sein
- geringe bis mittlere Komplexität
- kein starker Bedarf an eigener Carrier-/SBC-Steuerung
- einfache Rollouts / Standard-Voice-Szenarien

### Regeln für `Operator Connect`

- Carrier ist Teilnehmer des Programms oder Wechsel ist akzeptabel
- TeamsOnly ist erreichbar
- Kunde möchte **wenig eigene Infrastruktur**
- Carrier-Management und Support sollen möglichst ausgelagert sein

### Regeln für `Direct Routing`

- bestehender Carrier soll bleiben, auch wenn er nicht Operator Connect bietet
- Third-Party-PBX, Analoggeräte, Sonderrouting oder Survivability relevant
- Länder-/Filiallandschaft ist komplex
- interner oder externer Partner kann SBC, Zertifikate, DNS, Routing und Betrieb verantworten

### Regeln für `Mischmodell`

- unterschiedliche Nutzergruppen / Länder brauchen unterschiedliche PSTN-Modelle
- Standard-User können mit Calling Plan / Operator Connect laufen, Sonderfälle via Direct Routing
- PBX-/Carrier-Altlasten sollen schrittweise migriert werden

### Regeln für `Sonderpfad / Architektur-Review`

- Nutzer sind nicht TeamsOnly, aber Operator Connect wäre sonst passend
- Direct Routing wäre fachlich richtig, aber Zertifikats-/DNS-/SBC-/Support-Modell fehlt
- kein persönlicher Nummernbedarf -> `Shared Calling` prüfen
- Frontline-Nutzer -> Frontline-Lizenzpfad prüfen
- Dial-in-Fokus statt PSTN-Telefonie -> `Audio Conferencing` gesondert prüfen

## Bewertungsmodell

Neben den harten Regeln sollte das Tool ein Score-Modell nutzen:

- `+3` Microsoft als Carrier gewünscht -> `Calling Plan`
- `+3` vorhandener teilnehmender Carrier + TeamsOnly -> `Operator Connect`
- `+4` PBX / Analog / komplexes Routing / Carrier-Bindung -> `Direct Routing`
- `+2` geringer PSTN-Bedarf ohne persönliche Nummer -> `Shared Calling`
- `-3` fehlender TeamsOnly-Status gegen `Operator Connect`
- `-4` kein SBC-/Partner-/Infra-Modell gegen `Direct Routing`
- `-2` Calling-Plan-Land nicht verfügbar gegen `Calling Plan`
- `+2` heterogene Länder- und Nutzertypenlandschaft -> `Mischmodell`

## Benötigte Daten

### 1. Basis-Matrix `teams_phone_base_eligibility.json`

- `base_sku`
- `includes_teams`
- `requires_teams_standalone`
- `supports_teams_phone_addon`
- `supports_frontline_phone_addon`

### 2. PSTN-Modellregeln `teams_pstn_model_rules.json`

- `model`
- `requires_teams_phone`
- `requires_teams_only`
- `requires_sbc`
- `requires_operator_program`
- `requires_microsoft_as_carrier`
- `supports_mixed_mode`

### 3. Länder- und Verfügbarkeitsmatrix `teams_country_availability.json`

- `country`
- `calling_plan_available`
- `audio_conferencing_available`
- `communication_credits_available`
- `notes`

### 4. Provider- und Carrier-Katalog `teams_voice_providers.json`

- `provider_name`
- `is_operator_connect_partner`
- `countries`
- `pricing_reference`
- `managed_service_level`

### 5. Infrastruktur-Regeln `teams_direct_routing_requirements.json`

- zertifizierter SBC
- Zertifikate
- DNS / FQDN / Domain-Regeln
- Ports / IP-Bereiche
- Support Boundary

### 6. Kostenannahmen `teams_phone_cost_assumptions.json`

- Teams Phone Preis
- Calling-Plan-Varianten
- Carrier-/Operator-Kosten
- SBC-/Managed-Service-Kosten
- Audio-Conferencing-/Credit-Annahmen
- Portierungs- und Setup-Kosten

## Verifizierte Weblinks / Quellenbasis

### A. Basis- und Licensing-Überblick

1. **What is Teams Phone**  
	https://learn.microsoft.com/en-us/microsoftteams/what-is-phone-system-in-office-365  
	Relevanz: Abgrenzung Teams Phone vs. PSTN-Lösung, PBX-Funktionen, Zusatzmodell.

2. **Teams calling overview**  
	https://learn.microsoft.com/en-us/microsoftteams/cloud-voice-landing-page  
	Relevanz: Voice-Architektur, Administration, Policy- und Netzwerk-Kontext.

3. **Microsoft Teams add-on licenses**  
	https://learn.microsoft.com/en-us/microsoftteams/teams-add-on-licensing/microsoft-teams-add-on-licensing  
	Relevanz: Teams Phone, Calling Plans, Audio Conferencing, Frontline-Varianten, Shared Space.

### B. PSTN-Konnektivität

4. **PSTN connectivity options**  
	https://learn.microsoft.com/en-us/microsoftteams/pstn-connectivity  
	Relevanz: Vergleich Calling Plan, Operator Connect, Teams Phone Mobile und Direct Routing.

5. **Microsoft Teams Calling Plans**  
	https://learn.microsoft.com/en-us/microsoftteams/calling-plans-for-office-365  
	Relevanz: Domestic / International / Pay-As-You-Go, Minutes Pooling, Funding-Logik.

6. **Country and region availability for Audio Conferencing and Calling Plans**  
	https://learn.microsoft.com/en-us/microsoftteams/calling-plan-overview  
	Relevanz: Länderlogik für Calling Plans, Audio Conferencing, Credits und Service Numbers.

7. **Plan for Operator Connect**  
	https://learn.microsoft.com/en-us/microsoftteams/operator-connect-plan  
	Relevanz: TeamsOnly-Pflicht, Carrier-Modell, Support- und Betriebslogik.

8. **Plan Direct Routing**  
	https://learn.microsoft.com/en-us/microsoftteams/direct-routing-plan  
	Relevanz: SBC-, Domain-, DNS-, Zertifikats-, Port- und Support-Anforderungen.

### C. Sonderpfade

9. **Plan for Shared Calling**  
	https://learn.microsoft.com/en-us/microsoftteams/shared-calling-plan  
	Relevanz: Sonderpfad für Low-Volume-User ohne persönliche Rufnummer.

> Wichtiger Pflegehinweis: ältere oder anders benannte Teams-Phone-Lizenzlinks können 404 liefern; die belastbare Referenz ist aktuell die Add-on-Licensing-Übersicht plus die spezifischen Voice-Planungsseiten.

## Tool-Flow

### Schritt 1 – Lizenz- und Nutzerbasis

- Welche Teams-/Teams-Phone-Basis liegt vor?
- Welche Nutzergruppen und Länder sind betroffen?

### Schritt 2 – PSTN-Modell & Betrieb

- Microsoft oder Dritt-Carrier?
- TeamsOnly, Carrier-Programm, SBC-/Infra-Situation
- PBX, Analog, Routing, Portierung

### Schritt 3 – Ergebnis & Kosten

- Hauptmodell / Mischmodell
- Zusatzlizenzen und Credits
- Betrieb, Aufwand, Risiken und nächste Schritte

## UI/UX nach `cms-m365lic`

- dunkler Hero mit Fokus auf `Calling Plan vs. Operator Connect vs. Direct Routing`
- 3-Schritt-Wizard mit Länder-, Carrier- und Betriebsfragen
- KPI-Karten für:
	- empfohlenes Modell
	- monatlicher Kostenrahmen
	- Betriebsaufwand
	- Flexibilitätsgrad
- A/B/C-Vergleichskarten für die Hauptmodelle
- separater Block `Sonderfälle`: Audio Conferencing, Shared Calling, Frontline

## Ergebnislogik / Textbausteine

### `Calling Plan empfohlen`

- Microsoft als PSTN-Carrier akzeptiert
- Land verfügbar
- Setup eher standardisiert
- geringe bis mittlere Komplexität

### `Operator Connect empfohlen`

- passender Carrier vorhanden
- TeamsOnly erreichbar
- geringe Eigeninfrastruktur gewünscht

### `Direct Routing empfohlen`

- hoher Flexibilitätsbedarf
- bestehende PBX / Carrier / Sonderrouting
- SBC-/Partner-/Betriebsmodell vorhanden

### `Mischmodell empfohlen`

- Länder, Nutzergruppen oder Migrationspfade unterscheiden sich stark
- Standardfälle laufen cloud-nativ, Sonderfälle per Direct Routing

### `Sonderpfad / Review nötig`

- Shared Calling sinnvoller als persönliche DID
- Audio Conferencing statt Telefonieproblem
- Frontline-Lizenzierung oder TeamsOnly-Blocker offen

## Benötigte Bausteine / Funktionen

- `load_teams_phone_base_eligibility()`
- `load_teams_pstn_model_rules()`
- `load_teams_country_availability()`
- `load_teams_voice_providers()`
- `load_teams_direct_routing_requirements()`
- `validate_teams_phone_input()`
- `evaluate_teams_phone_scenario()`
- `evaluate_teams_phone_readiness()`
- `compare_teams_phone_models()`
- `calculate_teams_phone_costs()`
- `calculate_voice_addon_needs()`
- `render_teams_phone_advisor_page()`
- `export_teams_phone_pdf()`

## Beispielhafte Entscheidungsregeln für die Implementierung

- `if teams_phone_license == false => base_license_gap`
- `if calling_plan_available == true and microsoft_carrier_ok == true and complexity == 'low' => calling_plan`
- `if operator_connect_partner == true and teams_only == true and managed_service_preferred == true => operator_connect`
- `if needs_pbx_integration == true or analog_devices == true or keep_existing_carrier == true => direct_routing`
- `if direct_routing == candidate and (sbc_available == false and partner_available == false) => architecture_review`
- `if user_needs_personal_number == false and pstn_usage == 'low' => shared_calling_candidate`
- `if dial_in_focus == true => audio_conferencing_review`
- `if user_type == 'frontline' => frontline_license_path`
- `if heterogeneous_countries == true => mixed_model_candidate`

## Admin / Pflege

- Pflege von Teams-Phone-Preisen und Calling-Plan-Varianten
- Pflege von Provider-/Operator-Connect-Partnerdaten
- Pflege der Länderverfügbarkeit
- Pflege technischer Direct-Routing-Hinweise
- Review der Microsoft-Voice-Quellen bei größeren Teams-Lizenzänderungen

## SEO- und Content-Bausteine

### Fokus-Keywords

- `teams phone lizenz`
- `calling plan vs operator connect`
- `direct routing teams`
- `teams phone kosten`
- `operator connect teams`
- `shared calling teams`

### Empfohlene FAQ-Blöcke

- Brauche ich für Teams Phone noch einen Carrier?
- Was ist der Unterschied zwischen Calling Plan und Operator Connect?
- Wann ist Direct Routing sinnvoll?
- Brauche ich Audio Conferencing zusätzlich?
- Können Calling Plan und Direct Routing kombiniert werden?
- Wann ist Shared Calling besser als eine persönliche Rufnummer?

## MVP

- Calling Plan, Operator Connect, Direct Routing
- Lizenz- und Kostenrahmen
- technische Hauptvoraussetzungen
- Sonderhinweise für Audio Conferencing / Shared Calling

## Phase 2

- tiefere Länderlogik und Provider-Matrix
- Portierungs- und Nummernmanagement-Modul
- PDF-Telefonie-Empfehlung
- Partner-/Carrier-CTA

## Akzeptanzkriterien

- Tool trennt sauber zwischen Teams Phone und PSTN-Konnektivität
- Tool berücksichtigt Länderverfügbarkeit und TeamsOnly-Anforderung korrekt
- Tool bewertet Direct Routing nicht nur preislich, sondern auch infrastrukturell
- Tool kann Mischmodelle abbilden
- Tool weist Sonderpfade wie Shared Calling oder Audio Conferencing sichtbar aus
- alle Carrier-/Preisannahmen bleiben adminseitig pflegbar

## Offene Pflegepunkte

- Carrier- und Minutenpreise ändern sich häufig; deshalb nur katalogbasiert pflegen
- Calling-Plan-Verfügbarkeit regelmäßig mit Microsoft-Länderseite abgleichen
- Direct-Routing-Infrastruktur nicht zu stark vereinfachen; SBC-/Cert-/DNS-Themen sind echte Projektkosten
- alte Teams-Licensing-URLs können veralten; bevorzugt Add-on-Licensing- und Voice-Planungsseiten verwenden