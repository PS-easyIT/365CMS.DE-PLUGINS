# CMS Companies – Aufgaben, Features & Ideen

> Zukünftige Verbesserungen und Feature-Ideen für das Firmen-Plugin.  
> Priorität: 🔴 Hoch · 🟡 Mittel · 🟢 Niedrig · 🔵 Idee/Vision

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
- [ ] Auth-Matrix (Admin/Member/Guest) aktuell halten

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
