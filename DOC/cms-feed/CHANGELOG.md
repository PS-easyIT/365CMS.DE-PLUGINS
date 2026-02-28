# CMS Feed – Changelog

Alle nennenswerten Änderungen an diesem Plugin werden hier dokumentiert.

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
