# CMS Companies – Aufgaben, Features & Ideen

> Zukünftige Verbesserungen und Feature-Ideen für das Firmen-Plugin.  
> Priorität: 🔴 Hoch · 🟡 Mittel · 🟢 Niedrig · 🔵 Idee/Vision

---

## V2.8.0 – Audit- und Maßnahmenplan

### Ziel des Durchgangs

- [ ] Plugin vollständig auf **365CMS V2.8.0-Zielstand** dokumentarisch und technisch prüfen
- [x] **Keine Core-Änderungen** vornehmen
- [x] erkannte Security-, Speed- und Best-Practice-Probleme direkt im Plugin beheben

### Security

- [x] alle Admin- und Member-POST-/Save-Flows identifizieren
- [x] Ownership-Schutz für Firmen im Member-Bereich gegen fremde IDs verifizieren
- [x] Filter-, Status- und Partnerwerte nur per Whitelist akzeptieren
- [x] Logo-, Website- und Kontaktfelder validieren und sicher ausgeben
- [x] Experten-Zuordnungen und Delete-/Status-Aktionen gegen CSRF und IDOR prüfen

### Speed

- [x] Listenabfragen auf harte Integer-Validierung für `limit` und `offset` prüfen
- [ ] Firmen-, Experten- und Partner-Lookups auf N+1-Muster prüfen
- [ ] Asset-Ladung und wiederholte Initialisierung auf unnötige Kosten prüfen
- [ ] Sortierung und Filter auf indexfreundliche Nutzung prüfen

### Best Practices

- [x] Versions- und Update-Metadaten konsistent halten
- [x] Query-Bausteine mit Whitelists und Fallbacks absichern
- [ ] Cross-Plugin-Abhängigkeiten defensiv prüfen
- [x] Fehlerbehandlung und Rückgabewerte vereinheitlichen
- [x] Doku in `README.md`, `CHANGELOG.md` und `SECURITY.md` nach Fixes aktualisieren

### Abschlusskriterien

- [ ] keine offenen kritischen Berechtigungs- oder Query-Härtungsprobleme
- [ ] keine unvalidierten Paginierungs-/Filterpfade
- [ ] Doku- und Auditstand konsistent nachvollziehbar

### Audit-Zwischenstand 2026-03-28

- [x] Beschädigte `get_companies()`-Logik in der DB-Klasse repariert und wieder syntaktisch stabil hergestellt.
- [x] Firmenlisten und Zählabfragen für `limit`/`offset` sowie `status => 'any'` defensiv vereinheitlicht.
- [x] Member-Dashboard so angepasst, dass eigene `pending`-Einträge konsistent gezählt und angezeigt werden.
- [x] Member-Create-Handler für E-Mail-, URL-, Industry-, Company-Size-, Jahres- und Tag-Daten restriktiver validiert.
- [x] Single-Template für Firmenbeschreibung auf sichere Textausgabe mit Escaping und Zeilenumbruch-Rendering umgestellt.
- [x] Intern zusammengesetzte Breadcrumb-, Experten-, Speaker- und Register-Links im Single-Template konsequent im Attribut-Kontext escaped.
- [x] Reset- und Fallback-Links im Archive-Template für interne Navigationsziele konsequent im Attribut-Kontext escaped.
- [x] Detail-Links aus `cms_company_url()` im Card-Template ebenfalls konsequent im Attribut-Kontext escaped.
- [x] Auch Pagination-Links im Archive-Template mit zusammengesetzten Query-Parametern konsequent im Attribut-Kontext escaped.
- [x] Auch dynamische `style`-Attributwerte für Avatar-Gradienten, Ribbon-Farben und Card-Rahmen in Single- und Card-Templates konsequent escaped.
- [x] Zentrale `save_company()`-Persistenz verweigert für Nicht-Admins Updates auf fremde Company-IDs und entschärft damit latente Member-IDOR-Pfade.
- [x] Plugin-Bootstrap instanziiert hook-registrierende Klassen jetzt frühzeitig, damit Admin-Sidebar-Eintrag und Admin-Routen nach Aktivierung zuverlässig verfügbar sind.
- [x] Spezial-Handler für Experten-Zuordnungen (`admin_expert_assign`/`admin_expert_remove`) als Admin-only mit CSRF-Schutz verifiziert; aktuell kein Member-IDOR-Pfad vorhanden.
- [x] Admin-Asset-Ausgabe gegen fehlende Dateien abgesichert und `filemtime()` robust vereinheitlicht.
- [x] Renderpfade für Logo-, Website-, Mail- und Telefonfelder in Single- und Card-Templates per zusätzlicher Renderzeit-Validierung gegen Alt- und Bestandsdaten gehärtet.
- [x] Ownership-Stand präzisiert: Member-Dashboard bietet derzeit nur Create + Liste; kein realer Member-Edit- oder Experten-Relations-Update-Pfad vorhanden, zentrale `save_company()`-Härtung bleibt als Vorsorge aktiv.
- [x] Früher Plugin-Bootstrap zusätzlich gegen Aktivierungs-/Lade-Fatals gehärtet: Komponenten werden nur bei verfügbarem Core-Kontext (`CMS\Hooks`, `CMS\Database`) instanziiert.
- [x] Tabellenaufbau beim Aktivieren vollständig entschärft: Auch Fehler bereits beim initialen Datenbankzugriff in `create_tables()` werden jetzt geloggt statt als Fatal an den Aktivierungs-Flow zurückzureichen.
- [x] Heutige Audit-/Doku-Änderungen auf plugin-eigene Release-Versionen umgestellt: Doku-Release `1.0.1`, technisches Audit-/Stabilitäts-Release `1.1.0`.

---

## 🔴 Hohe Priorität

### 1. Erweiterte Firmenprofile
- [ ] Gründungsjahr, Rechtsform als Felder
- [ ] Mehrere Standorte pro Firma (Multi-Location mit Karte)
- [ ] Abteilungen / Geschäftsbereiche als Unterstruktur
- [ ] Firmen-Zertifizierungen (ISO, TÜV, etc.) als Badge-System
- [ ] Firmenlogo-Upload mit automatischer Größenanpassung

### 2. DSGVO-Compliance
- [ ] `dsgvo_export_data` Hook implementieren (Firmendaten des Users)
- [ ] `dsgvo_delete_data` Hook implementieren (Firmenprofile des Users)
- [ ] Datenschutzhinweis auf Firmenprofil-Seiten

### 3. SECURITY.md vervollständigen
- [ ] CSRF-Absicherung aller AJAX-Endpunkte dokumentieren
- [x] Auth-Matrix (Admin/Member/Guest) aktuell halten

### 4. Member-Dashboard erweitern
- [ ] Eigene Firma bearbeiten (Name, Beschreibung, Logo)
- [ ] Mitarbeiter/Experten der eigenen Firma sehen und zuordnen
- [ ] Firmenstatistiken: Profilaufrufe, Experten-Anzahl
- [ ] Firmen-Events: Eigene Veranstaltungen verlinkt aus cms-events

---

## 🟡 Mittlere Priorität

### 5. Erweiterte Such- und Filterfunktionen
- [ ] Filter nach Branche, Größe, Standort, Partner-Status
- [ ] Geo-basierte Suche (PLZ-Radius)
- [ ] Autocomplete bei Firmen-Auswahl in anderen Plugins
- [ ] Alphabet-Navigation (A–Z) auf der Archivseite

### 6. Partner-Programm
- [ ] Partner-Tiers erweitern (Bronze, Silber, Gold, Platin)
- [ ] Partner-Badge auf der Firmenkarte (mit Tooltip)
- [ ] Partner-Landingpage: Alle Partner nach Tier sortiert
- [ ] Partner-Bewerbungsformular im Member-Bereich

### 7. Import/Export
- [ ] CSV-Import für Massenerstellung von Firmenprofilen
- [ ] CSV-Export aller Firmen (mit Meta-Daten)
- [ ] vCard-Integration (Firmen-Kontaktdaten als .vcf)

### 8. Performance
- [ ] Firmen-Cache (Transient-Cache für häufig angefragte Profile)
- [ ] Lazy-Loading für Firmenlogos auf der Archivseite
- [ ] AJAX-Paginierung und Filter (statt Page-Reload)

### 9. Branchen-Taxonomie
- [ ] Hierarchische Branchenstruktur (Hauptbranche → Unterbranche)
- [ ] Branchen-Icons (Emoji oder SVG)
- [ ] Branchenfilter auf Public-Seite als Dropdown oder Tag-Cloud
- [ ] Branchen-Seeded-Daten: Vordefinierte IT-Branchen

---

## 🟢 Niedrige Priorität

### 10. Cross-Plugin-Vertiefung
- [ ] Organigramm-Integration: Firmenstruktur als Organigramm (cms-organigramm)
- [ ] Jobprofile-Generator: Offene Stellen auf Firmenprofil anzeigen
- [ ] Speaker-Liste: Firmen-Speaker auf Firmenprofil anzeigen
- [ ] Feed-Integration: Firmen-RSS-Feed auf Firmenprofil einbetten

### 11. Public-Seite Verbesserungen
- [ ] Firmen-Vergleich (2–3 Firmen nebeneinander)
- [ ] Firmen-Karte (Map-View mit allen Standorten)
- [ ] „Ähnliche Firmen" Empfehlung auf Single-Seite
- [ ] Bewertungen / Reviews (Cross-Plugin mit zukünftigem cms-reviews)

### 12. API
- [ ] REST-API für Firmendaten (GET /api/companies, GET /api/companies/{id})
- [ ] Webhook bei Firmen-Erstellung/-Änderung
- [ ] oEmbed-Support für Firmenkarten

---

## 🔵 Ideen / Vision

### 13. Firmen-Insights
- [ ] Profil-Vollständigkeits-Score (wie LinkedIn)
- [ ] Aktivitäts-Tracking: Letzte Aktualisierung, Experten-Änderungen
- [ ] Firmen-Rangliste basierend auf Aktivität/Experten/Events

### 14. Subscription-Modell
- [ ] Premium-Firmenprofile (erweiterte Sichtbarkeit, Top-Platzierung)
- [ ] Firmen-Abonnement mit Stripe/PayPal-Integration
- [ ] Unterschiedliche Feature-Sets pro Plan (Basis, Pro, Enterprise)

### 15. Social-Integration
- [ ] Automatisches Abrufen von Firmeninfos via LinkedIn-API
- [ ] Social-Media-Feed der Firma auf dem Profil
- [ ] Firmen-News-Aggregator (ähnlich cms-feed, aber firmenspezifisch)
