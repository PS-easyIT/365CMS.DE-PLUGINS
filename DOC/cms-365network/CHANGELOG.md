# Changelog – CMS 365NETWORK

## 1.0.18 – 2026-05-29

- Neuer Admin-Tab `↕️ Reihenfolge`: Featured Card, Hero, Kennzahlen, Teaser/Suche, Direkteinstieg und Toolbox können per Drag & Drop oder Hoch/Runter-Buttons sortiert werden.
- Die vier Direkteinstieg-Karten (`Events`, `Speaker`, `Firmen`, `Experten`) besitzen im Bereiche-Tab eine eigene sortierbare Reihenfolge.
- Bild-URL-Felder, aktuell die Featured-Bild-URL, besitzen eine direkte Auswahl aus der vorhandenen 365CMS-Mediathek inklusive Vorschau und Leeren-Aktion.
- Bild-URL-Sanitizer akzeptieren jetzt neben absoluten HTTP(S)-URLs auch interne Medienpfade wie `/uploads/...` und `/media-file?...`, damit Mediathek-Auswahlen gespeichert und öffentlich gerendert werden.

## 1.0.17 – 2026-05-29

- Hub-Suche auf den Core-Routenvertrag korrigiert: neue und bestehende Alt-Defaults nutzen `/search` statt `/suche`, damit Suchanfragen nicht mehr auf 404 laufen.
- Stale Hub-Defaults für `hub_band_search_url` und `hub_toolbox_all_url` werden beim Seed-/Settings-Lauf migriert, sofern sie noch auf den alten Standardwerten stehen.
- `Alle Tools` zeigt standardmäßig auf die aktuelle `cms-m365tools`-Public-Route `/m365-tools`.
- Public-Toolbox lädt weiterhin Legacy-Links aus `m365toolbox_links`, fällt aber bei aktivem `cms-m365tools` automatisch auf die aktuelle Tool-Registry zurück, wenn keine Legacy-Hub-Links vorhanden sind.

## 1.0.16 – 2026-05-28

- Plugin-Header um Core-Metadaten `Plugin Slug` und `Requires` ergänzt und Autor auf Andreas Hepp gesetzt.
- Aktivierung/Deaktivierung um `cms_register_hook`-kompatible globale `hub_install()`-/`hub_uninstall()`-Callbacks erweitert; Deaktivierung löscht keine Tabellen.
- Admin-Menü registriert sich über `cms_register_admin_menu()`, wenn der Core-Helper verfügbar ist; Fallback bleibt Core-kompatibel über bestehende Menü-Hooks.
- Admin-Capability, Nonce-Feld, Nonce-Prüfung, Admin-Notice und Redirect nutzen Core-Helper, sofern vorhanden, mit sicheren Fallbacks für bestehende 365CMS-Installationen.
- Inline-Redirect-Script aus der Admin-Bridge entfernt.
- Statistikwerte werden request-lokal gecacht; Toolbox-Limit bleibt integer-geclamped und wird explizit in SQL formatiert.

## 1.0.15 – 2026-05-28

- Hub-Sammel-Tab in konsistente eigene Bereichs-Tabs aufgeteilt: Featured Card, Hero, Kennzahlen, Teaser & Suche, Direkteinstieg und Toolbox.
- Jeder Bereich besitzt jetzt eigene Aktivierungs-, Text-/Content-, Layout- und Design-Gruppen im Adminbereich.
- Neue typisierte Hub-Settings für Layouts, Labels, Suchtexte, Stat-Labels, Kartenstile, Farben und Rundungen ergänzt.
- Public-Landingpage nutzt die neuen Settings direkt über CSS-Variablen und Modifier-Klassen; Änderungen wirken ohne ungenutzte Admin-Felder.
- Seitenlayout, Sidebar und Analytics bleiben als eigene übergreifende Tabs erhalten.

## 1.0.14 – 2026-05-28

- Toolbox-Aktivierung auf Core-Statusprüfung über `cms_plugin_active()`/`CMS\PluginManager` begrenzt; direkte Plugin-Tabellenprüfung entfernt.
- Featured-Bild-URLs im Hub-Admin und Public-Template auf absolute HTTP(S)-URLs begrenzt.
- Request-lokalen Settings-Cache für Basis- und Hub-Settings ergänzt; Speicherpfade invalidieren den Cache.
- Leere Bereichskacheln rendern ohne Inline-`onclick`; Escaping im Public-Template nutzt `ENT_SUBSTITUTE`.
- Best-Effort-Index `idx_toolbox_hub (show_on_hub, status, sort_order)` für vorhandene Toolbox-Linktabellen ergänzt.

## 1.0.13 – 2026-05-28

- Featured Card als erste Hub-Komponente vor dem Hero positioniert, wenn sie aktiviert ist.
- Leeren Icon-/Bild-Placeholder entfernt: Ohne Bild-URL rendert die Card einspaltig mit dezentem Gold-Akzentstreifen.
- Bild-Variante rendert nur noch mit echter `hub_featured_image_url`; auf kleinen Viewports stapelt das Layout sauber.
- Hub-Admin-Settings um Section `featured` erweitert: Sichtbarkeit, Darstellung, Label, Titel, Beschreibung, Button und Bild-URL.
- Die Toolbox-Section bleibt im Hub-Admin sichtbar; die neue Featured-Section steht davor.

## 1.0.12 – 2026-05-28

- Neue Tabelle `cms_network_hub_settings` für typisierte Hub-Bereichssettings ergänzt und beim Plugin-Installer mit Defaults befüllt.
- Neuer Admin-Tab `Hub` bündelt Hero, Zähler-Kacheln, Teaser-Band, Direkteinstieg und Toolbox mit Text-, Textarea-, Bool- und Integer-Feldern.
- Public-Landingpage liest die neuen Hub-Settings direkt: Sichtbarkeit, Texte, Buttons, URLs, Icons, Suchparameter und Toolbox-Limit sind nun administrierbar.
- Direkteinstieg-Kacheln können einzeln ausgeblendet werden; leere Kacheln sind sichtbar, aber nicht mehr klickbar.
- Admin-CSS für das gescopte Hub-Settings-Formular und Toggle-Switches ergänzt.

## 1.0.11 – 2026-05-28

- Optionalen M365-Toolbox-Bereich nach den Direkteinstieg-Kacheln ergänzt.
- Anzeige ist defensiv an ein aktives Toolbox-Plugin (`m365toolbox`, `cms-m365toolbox` oder `cms-m365tools`) und vorhandene aktive `show_on_hub`-Links aus `m365toolbox_links` gebunden.
- Tool-Karten rendern Label, URL, Tabler-Icon und Beschreibung sicher escaped; ungültige Icons fallen auf `ti-link` zurück.
- Gescope Styles für 3-/2-/1-spaltiges Grid, ruhige Hover-Zustände und Reduced-Motion ergänzt.

## 1.0.10 – 2026-05-28

- `Nächstes Event` und Landingpage-Suche optisch getrennt: beide Elemente stehen nun untereinander als volle Zeilen.
- Event-Teaser mit Label/Icon, Titel-Link, Meta-Zeile und separatem `Zum Event`-CTA gestaltet.
- Suchleiste auf eine kompakte Full-Width-Bar mit Gold-Button umgestellt.
- Leere Bereichskacheln zeigen nur noch `Demnächst verfügbar`; der `Alle … ansehen`-Link erscheint ausschließlich bei vorhandenen Einträgen.

## 1.0.9 – 2026-05-28

- Bereichskacheln mit sprechenden Linktexten wie `Alle Events ansehen` und `Alle Speaker ansehen` statt generischem `Öffnen`.
- Standard-Subtexte der vier Bereichskacheln individueller formuliert; Admin-Texte bleiben weiterhin vorrangig.
- Landingpage um eine kompakte Suche ergänzt.
- `Nächstes Event`-Teaser ergänzt, wenn kommende Events vorhanden sind.
- Leere Bereiche zeigen dezent `Demnächst verfügbar`, ohne die oberen Stat-Zähler oder die Zählerlogik zu verändern.

## 1.0.8 – 2026-05-28

- Direkteinstieg-Karten wieder als vertikale Kacheln gestaltet: Content unten, dekorativer Iconblock oben rechts.
- Iconblock ist großformatig, Navy, leicht rotiert und wird im Hover gold.
- Linktext in den Bereichskarten auf kompaktes `Öffnen` reduziert.
- Hero, obere Stat-Kacheln, CTAs und Zählerlogik unverändert gelassen.

## 1.0.7 – 2026-05-28

- Landingpage-Zähler auf API-first umgestellt: Events, Speaker, Firmen und Experten verwenden zuerst die jeweiligen Plugin-Database-APIs.
- Direkter SQL-Fallback zählt erst danach und wird nicht mehr durch eine vorgelagerte Tabellenprüfung blockiert.
- Tabellenauflösung nutzt `INFORMATION_SCHEMA` mit Prefix- und No-Prefix-Kandidat; falls das nicht verfügbar ist, versucht der Counter-Fallback den erwarteten Prefix-Tabellennamen direkt.
- Fehler im Statistikpfad werden jetzt per `error_log()` sichtbar, statt komplett lautlos als `0` zu enden.

## 1.0.6 – 2026-05-28

- Zähler der Landingpage final entkoppelt von `PluginManager::isPluginActive()`/Klassen-Ladezeitpunkt.
- Events, Speaker, Firmen und Experten werden gezählt, sobald die jeweilige Tabelle vorhanden ist; damit bleiben die Card-Zähler sichtbar, auch wenn das Plugin-Load-Timing im Landingpage-Renderpfad abweicht.
- Status-Fallback aus `1.0.4` bleibt erhalten: bevorzugte öffentliche Statuswerte werden zuerst gezählt, danach nicht gelöschte Datensätze.

## 1.0.5 – 2026-05-28

- 365NETWORK-Hub-Landingpage nach PHINIT-Farbregeln neu gestaltet: Navy für Text/Struktur, Gold für CTAs, Links und Hover-Zustände.
- Hero-Karte mit prägnantem Hub-Label, kompaktem Einzeiler-Subtext und klarer CTA-Zeile umgebaut.
- Statistikbereich von Dashboard-Kacheln auf klickbare Metric-Cards mit Tabler-Icons umgestellt.
- Direkteinstieg auf klickbare 2x2-Bereichskarten mit linkem Icon-Block, Count-Hinweis und Gold-Link umgebaut.
- Styling vollständig unter `.cms-network-hub-wrap` gescopt; keine Core-Header-/Navigation-/Footer-Anpassungen.
- DB-Abfragen und `COUNT()`-/Zählerlogik unverändert gelassen.

## 1.0.4 – 2026-05-28

- Statistik-Zählung der Landingpage robuster gemacht: Events, Speaker, Firmen und Experten fallen nicht mehr pauschal auf `0`, wenn `PluginManager::isPluginActive()` im Public-Renderpfad zu strikt/zu früh auswertet, die Plugin-Klasse aber bereits geladen ist.
- Events zählen neben `published` auch kompatible öffentliche Statuswerte wie `active` und `completed`.
- Statistik-Fallback ergänzt: Wenn keine bevorzugten öffentlichen Statuswerte gefunden werden, werden nicht gelöschte Datensätze gezählt, damit bestehende Installationen mit abweichenden Statuswerten sichtbar bleiben.
- Vorschau-Abfragen nutzen dieselbe robuste Integrationsprüfung und flexible Status-Filterung.

## 1.0.3 – 2026-05-28

- Featured Card im Public-Layout ohne aktive Sidebar auf eine einspaltige Content-Breite umgestellt.
- Seitlicher Content-Abstand nur für die Featured Card gesetzt; die Bereichskarten bleiben wieder bündig im normalen Content-Raster.
- Bereichskarten optisch überarbeitet: Icon sitzt nun oben rechts, die rechte obere Ecke erhält eine dezente dunklere Dreiecksfläche.
- Responsive Rückfall ergänzt, damit der Featured-Card-Inset auf kleinen Viewports nicht zu engem Content führt.

## 1.0.2 – 2026-05-27

- Speicherbug behoben: Beim Speichern eines einzelnen Admin-Tabs bleiben alle Einstellungen anderer Tabs erhalten.
- Checkboxen werden weiterhin korrekt pro aktivem Tab gespeichert, ohne inaktive Tab-Werte zu überschreiben.
- Public-Vorschauen für Events, Speaker, Firmen und Experten erhalten kanonische Detail-URLs mit Fallback-Slug-Generierung.
- Preview-Bilder mit festen `width`/`height`-Attributen ergänzt, um Layout-Shift zu reduzieren.
- Audit auf unsichere Funktionen, `ORDER BY RAND`, Inline-Style-Ausreißer und 365CMS-Kompatibilität für die geänderten Bereiche durchgeführt.

## 1.0.1 – 2026-05-27

- Analytics-Tab ergänzt.
- Optionaler Matomo-/SEO-Analyse-Code kann hinterlegt und in `head` oder `body_end` ausgegeben werden.
- Ausgabe ist strikt auf die 365NETWORK-Public-Site begrenzt: interne Landingpage-Route oder Root der konfigurierten Zusatzdomain.
- Andere CMS-Seiten laden diesen Code nicht.

## 1.0.0 – 2026-05-27

- Neues Plugin `cms-365network` erstellt.
- Domainbasierte Landingpage für konfigurierbare Zusatzdomains ergänzt.
- Interne Vorschau-Route `/365network` ergänzt.
- Admin-Einstellungen für Domain, Content, Layout, Sidebar und Bereichskarten umgesetzt.
- Modernes Public-Layout mit Hero, Featured Card, vier Bereichskarten und optionaler Sidebar erstellt.
- Dynamische Vorschauen für kommende Events sowie zufällige Speaker, Firmen und Experten ergänzt.
- Defensive Cross-Plugin-Abfragen mit Plugin-Aktivstatus, Tabellenprüfung und Fallbacks implementiert.
