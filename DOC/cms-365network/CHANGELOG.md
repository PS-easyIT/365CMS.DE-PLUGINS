# Changelog – CMS 365NETWORK

## 1.0.38 – 2026-05-31

- Im Hero-Layout mit Bild oben wird die Suche jetzt als eigenes, leicht helleres Band unter Logo und Untertext angezeigt.
- Suchfeld und Button bleiben im Suchband angepasst in einer Reihe.
- Der Untertext steht wieder direkt unter dem Logo, wenn er aktiviert ist.

## 1.0.37 – 2026-05-31

- Der CTA der Bereichskarten (`… entdecken`) sitzt jetzt immer in der rechten unteren Ecke der Card.
- Die CTA-Ausrichtung bleibt unabhängig von Textlänge und Card-Höhe stabil.

## 1.0.36 – 2026-05-31

- Im Hero-Layout mit Bild oben und Text darunter nutzt die Suche jetzt die volle Contentbreite in einer Zeile mit Button daneben.
- Der Untertext wird in dieser Darstellung unter dem Suchfeld angezeigt, nicht mehr direkt unter dem Logo/Bild.
- Suchfeld und Button behalten die angepasste `4px`-Rundung.

## 1.0.35 – 2026-05-31

- In den Hero-Split-Layouts nutzt das Suchband jetzt die volle verfügbare Text-/Restspaltenbreite und bleibt bündig zum darunterliegenden Content.
- Suchfeld und Suchbutton im Hero haben jetzt `4px` Rundung.
- Hero-Bilder werden in Split-Layouts unten ausgerichtet; bei großer Hero-Höhe ist der Abstand zum unteren Hero-Rand auf maximal `50px` begrenzt.

## 1.0.34 – 2026-05-31

- Die Hero-Split-Layouts sind jetzt sauber gespiegelt: `image-left` startet links bündig am Content-Rand, `image-right` endet rechts bündig am Content-Rand.
- Der Abstand zwischen Bildspalte und Textbereich bleibt in beiden Richtungen pixelbasiert auf maximal `50px` begrenzt.

## 1.0.33 – 2026-05-31

- Im Hero-Layout `image-left` beginnt das Bild jetzt bündig am linken Content-Rand.
- Die Bildspalte und das enthaltene Logo/Bild werden links ausgerichtet und damit mit dem darunterliegenden Content-Container abgestimmt.

## 1.0.32 – 2026-05-31

- Der Hero-Bereich verwendet keinen Farbverlauf mehr; der Hintergrund ist jetzt eine klare Farbe aus `hub_hero_bg_color`.
- Hero-Titel, Untertitel, Label, Highlight, Suchbox-Icon und Hero-Button sind farblich auf `hub_hero_text_color` und `hub_hero_accent_color` abgestimmt.
- Die Hero-Farb-Fallbacks verwenden wieder das passende Navy/Weiß/Akzent-Set, falls keine Adminwerte vorhanden sind.

## 1.0.31 – 2026-05-31

- In den Hero-Layouts `image-left` und `image-right` startet das Suchband jetzt bündig am Text-/Content-Anfang.
- Der Abstand zwischen Logo/Bild und Textbereich ist pixelbasiert auf `50px` begrenzt.
- Das Logo/Bild wird in seitlichen Layouts zur Textseite ausgerichtet, damit keine zusätzliche optische Lücke durch zentriertes `object-fit: contain` entsteht.

## 1.0.30 – 2026-05-31

- Wenn ein Hero-Bild/Logo gesetzt ist und `Label über H1` leer bleibt, ist der Abstand zwischen Bild und oberem Hero-Hintergrundrand auf maximal `25px` begrenzt.
- Das gilt auch für die seitlichen Hero-Bildlayouts `image-left` und `image-right`.

## 1.0.29 – 2026-05-31

- Ein leerer Wert im Hero-Feld `Label über H1` blendet das Label jetzt wirklich aus.
- Das Landing-Template verwendet für dieses Feld keinen Default-Fallback mehr, wenn im Admin bewusst kein Text gesetzt ist.

## 1.0.28 – 2026-05-31

- Die Hero-Split-Layouts nutzen jetzt fest `67%` Text und `33%` Bild.
- Bei `image-right` steht das Bild rechts in der 33%-Spalte, bei `image-left` links; der Text nutzt jeweils den restlichen Bereich.
- Das Hero-Bild füllt im Seitenlayout die Bildspalte aus, bleibt aber weiterhin per Höhe und Objekt-Anpassung kontrolliert.

## 1.0.27 – 2026-05-31

- Hero-Bildposition und Bildgröße respektieren die Admin-Auswahl jetzt zuverlässig auf Public-Landing- und Suchseiten.
- Die dynamischen Public-CSS-Variablen werden während des tatsächlichen Renderpfads garantiert ausgegeben, damit `hub_hero_image_width` und `hub_hero_image_height` nicht auf CSS-Fallbacks zurückfallen.
- Die Layouts `image-left` und `image-right` rendern das Hero-Bild auch im Modus `Bild ersetzt sichtbare H1` seitlich statt oben im Titelbereich.

## 1.0.26 – 2026-05-31

- Die Bereichskarten `Events`, `Speaker`, `Firmen` und `Experten` nutzen jetzt abgestufte Blautöne statt der bisherigen goldenen Akzente.
- Das Bereichs-Icon sitzt oben rechts als kompaktes Corner-Badge auf einer diagonalen Dreieck-Fläche.
- Count-Badge, CTA-Farbe, Hover-Border und Dark Mode wurden passend zum blauen Card-System abgestimmt.

## 1.0.25 – 2026-05-31

- Der Hero-Bereich unterstützt konfigurierbare Bildmaße über `hub_hero_image_width` und `hub_hero_image_height`; der Default ist `250 × 200px`.
- `hub_hero_layout` besitzt jetzt drei Public-Layouts: zentriert untereinander, Titel/Suche links mit Bild rechts und Bild links mit Titel/Suche rechts.
- Über `hub_hero_image_mode` kann das Hero-Bild entweder die sichtbare H1 ersetzen oder zusätzlich gemeinsam mit Titel, Untertitel und Suche angezeigt werden.
- Die Hero-Ausgabe nutzt weiterhin eine Screenreader-H1, wenn das Bild die sichtbare H1 ersetzt, und setzt `width`/`height`-Attribute gegen Layout-Shift.

## 1.0.24 – 2026-05-31

- Die Direkteinstieg-Bereichskarten sind im Public-Hub optisch überarbeitet: klares Icon-Feld, dezente Akzentkante, Count-Badge rechts und bessere Text-/CTA-Hierarchie.
- Das Bereichskarten-Raster ist jetzt auf maximal zwei Karten pro Reihe begrenzt; Desktop zeigt ein ruhiges `2 × 2`-Layout, mobil wird weiterhin sauber gestapelt.
- Die Standard- und Legacy-Reihenfolge der vier Karten ist `Events`, `Speaker`, `Experts`, `Firmen`, damit die Reihen `Events/Speaker` und `Experts/Firmen` entstehen.

## 1.0.23 – 2026-05-31

- Im Bereich `Nächstes Event` bleibt die Jahreszahl in der Kalender-Datebox wieder mittig ausgerichtet.
- Die allgemeine Meta-`small`-Regel wirkt nur noch auf den Event-Text neben dem Kalender und nicht mehr auf Monat/Tag/Jahr innerhalb der Datebox.

## 1.0.22 – 2026-05-31

- Die 365NETWORK-Publicsite begrenzt alle sichtbaren Rundungen auf maximal `2px`; gespeicherte Hub-Radius-Settings und dynamische CSS-Variablen werden serverseitig gekappt.
- Harte Preview-Radien für Buttons, Karten, Badges, Avatare, Icon-Flächen und Rotator-Pfeile wurden auf `2px` vereinheitlicht.
- Der Bereich `Im Fokus` zeigt keine Pagination-/Dot-Leiste unter dem Band mehr; die Steuerung bleibt ausschließlich über die Pfeile links und rechts erhalten.
- Das Public-JavaScript erzeugt keine Spotlight-Dots mehr und aktualisiert nur noch die Slides per Pfeilsteuerung bzw. optionalem Autoplay.

## 1.0.21 – 2026-05-31

- Die Public-HubSite wurde an das `365network-hub-vorschau.html`-Design angelehnt, jedoch bewusst ohne eigenen Plugin-Header und ohne Menüband: Header und Navigation kommen ausschließlich vom aktiven Theme.
- Der Hub startet direkt mit dem nahtlosen Partnerband unter dem Theme-Header; danach folgen dunkler zentrierter Hero, vier Bereichskarten, nächste Events, Spotlight-Rotator und Partner-Spalten.
- Neue Hub-Settings-Sections `partnerband`, `next-events`, `spotlight` und `partner-columns` ergänzt; alle sichtbaren Texte, Links, Sichtbarkeiten, Limits und Farben sind im Adminbereich steuerbar.
- Die Standard-Reihenfolge der Hub-Bereiche folgt jetzt dem headerlosen Preview-Aufbau: `partnerband,hero,areas,next-events,spotlight,partner-columns,toolbox,featured,stats,band`; das Partnerband wird zusätzlich bei älteren gespeicherten Reihenfolgen öffentlich nach oben gezogen.
- Das Landing-Template wurde datengetrieben neu aufgebaut und nutzt Inline-SVGs statt Tabler-Icon-Fonts oder Inline-Styles.
- Die Featured Card wird in der Preview-Landingpage wieder innerhalb der konfigurierten Bereichs-Reihenfolge ausgegeben; leere neue Hub-Felder fallen auf ältere `featured_*`-Settings zurück.
- Der Hero-Bereich unterstützt `hub_hero_image_url`: Ein Bild aus URL oder 365CMS-Mediathek ersetzt die sichtbare H1, während die H1 barrierefrei als Screenreader-Überschrift erhalten bleibt.
- Die Companies-/Experts-Cards in den Partner-Spalten verwenden ein gleichmäßiges Grid mit festen Mindesthöhen, damit die Zeilen auf beiden Seiten bündig wirken.
- Public-CSS ergänzt ein Full-Bleed-Layout mit maximaler Content-Breite, responsiven Kartenrastern und Dark-Mode-Variablen.
- Neues Public-JavaScript steuert den Spotlight-Rotator, unterstützt optionale Autoplay-Pausen und respektiert `prefers-reduced-motion`.
- Partner Companies werden aus echten `cms_companies`-Partnerflags (`is_partner`, `is_top_partner`) geladen; Partner Experts verwenden bewusst aktive Expertenprofile, da die Experten-Tabelle kein Partner-Flag besitzt.

## 1.0.20 – 2026-05-29

- Die Suche der 365NETWORK-Landingpage ist jetzt eine eigene Plugin-Route unter `/365network/search` und damit vollständig von der globalen 365CMS-Suche `/search` getrennt.
- Die Landingpage-Searchbox zeigt direkt auf diese neue Route; alte Defaults wie `/suche` oder `/search` werden für die Hub-Search-URL auf `/365network/search` migriert.
- Die Suchergebnisse greifen ausschließlich auf die vier Netzwerk-Plugins zurück: `cms-events`, `cms-speakers`, `cms-companies` und `cms-experts`.
- Events, Speaker, Firmen und Experten werden gruppiert ausgegeben; jede Gruppe nutzt nur vorhandene Tabellen/Spalten und öffentliche Statuswerte.
- Alle Suchabfragen laufen über Prepared Statements mit LIKE-Parametern und defensiven Tabellen-/Spalten-Whitelists.

## 1.0.19 – 2026-05-29

- Hub- und Basis-Einstellungen leeren nach dem Speichern immer explizit den 365CMS-Public-Cache, damit Plugin-Änderungen nicht von einer global deaktivierten Auto-Clear-Option blockiert werden.
- Bestehende Performance-Hooks `performance_cache_purged` und `performance_cdn_purge_requested` werden für `cms-365network` ausgelöst, damit angebundene CDN-/Reverse-Proxy-Caches ebenfalls reagieren können.
- Textänderungen in Hero, Featured Card, Teaser/Suche, Direkteinstieg und Toolbox werden dadurch nicht mehr von einer alten gecachten Public-Ausgabe überdeckt.
- Ältere `network_hub_settings`-Tabellen werden beim Laden/Speichern auf das aktuelle `setting_type`-Enum inklusive `textarea` migriert, damit Textarea-Felder zuverlässig persistieren.
- Legacy-Dubletten in `network_hub_settings` werden bereinigt und ein UNIQUE-Key auf `setting_key` wird nachgezogen. Dadurch können neue Default-Seeds gespeicherte Admin-Texte nicht mehr in der Public-Ausgabe überdecken.
- Hub-Speichern meldet nur noch Erfolg, wenn die geschriebenen Werte direkt aus der Datenbank zurückgelesen und verifiziert werden konnten.
- Featured-Bild-URLs werden robuster normalisiert: `uploads/...`, `media/...`, `media-file?...` sowie Pfade mit Leerzeichen werden als nutzbare Public-URLs gespeichert/gerendert.
- Der Mediathek-Picker erhält einen Fallback-Dialog, falls die Bootstrap-/Tabler-Modal-API auf der Plugin-Adminseite nicht global verfügbar ist.
- Featured-Bilder erhalten im Admin eine steuerbare Bildhöhe in Pixeln; die Ausgabe nutzt `object-fit: contain` mit mittiger Ausrichtung, damit das komplette Bild sichtbar bleibt und nicht zugeschnitten wird.
- Der Hero-Bereich bietet ein Höhen-Layout mit den Optionen `Kompakt`, `Normal` und `Groß`.
- Die Hero-Hauptüberschrift nutzt jetzt die volle Kachelbreite statt auf `780px` begrenzt zu werden.
- Public-Kachel-Links bleiben beim Hover/Fokus ohne Textunterstreichung, auch wenn das aktive Theme allgemeine Link-Hover-Regeln setzt.

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
