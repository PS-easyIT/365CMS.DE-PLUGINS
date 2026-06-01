
# Changelog – CMS M365 Tools

## 3.0.6 – 2026-06-01

- Admin-Untermenüpunkte direkt auf ihre jeweiligen Seiten-Callbacks gelegt, damit Sidebar-Klicks nicht mehr vom Sammel-Dispatcher oder Request-Page-Sync abhängen.
- Exakte Admin-Routen und Parent-Aliase bleiben als zusätzliche Absicherung für direkte `/admin/plugins/...`-Aufrufe erhalten.

## 3.0.5 – 2026-05-30

- M365-Audit-Fix: Lokale JSON-Kataloge werden defensiver geladen.
- Der Kataloglader normalisiert Dateinamen per `basename()`, prüft Lesbarkeit und Dateigröße und protokolliert JSON-Fehler statt fehlerhafte Inhalte still weiterzureichen.

## 3.0.4 – 2026-05-30

- Read-only Lizenzmatrix und Add-on-Matrix vollständig aus `cms-m365tools` entfernt; die Routen, Templates, JSON-Kataloge, Adminfelder und Matrix-Normalisierung liegen jetzt in `cms-m365matrices`.
- M365 Tools behält nur die gemeinsamen Options-/Settings-Tabellen für bestehende Konfigurationen und die übrigen interaktiven Rechner-/Tool-Module.
- Tool-Registry, Adminmenü, Frontend-Routen und Best-Practice-Modulzuordnungen um die ausgelagerten Matrixmodule bereinigt.

## 3.0.3 – 2026-05-30

- Matrix-Routen, Matrix-Registry und Matrix-Admineintrag werden an `cms-m365matrices` delegiert, sobald das neue Matrix-Plugin aktiv ist.
- Read-only-Matrix-Templates um gemeinsame Optionswerte für Außenlayout, Breiten, Abstände, Farben und zusätzliche Texte außerhalb der Tabellen erweitert.
- Bestehende Matrix-Admin-Feldliste hält dieselben Shared-Option-Keys vor, damit Alt-Admin und neues Matrix-Plugin kompatibel bleiben.

## 3.0.2 – 2026-05-18

- Root-Bootstrap unter `cms-m365tools` ergänzt, damit der 365CMS PluginManager den Slug `cms-m365tools` über `PLUGIN_PATH/cms-m365tools/cms-m365tools.php` aktivieren kann.

## 3.0.1 – 2026-05-18

- Compatibility-/Validation-Pass für 365CMS v3.x.x und PHP 8.4 abgeschlossen.
- Manifest um `min_php`/`requires_php` 8.4 ergänzt und Plugin-Version synchronisiert.
- Security-Hotspots erneut geprüft: Admin-CSRF, DOM-XSS-Sinks und öffentliche Route-/GET-Normalisierung bleiben auf dem bereits gehärteten 3.x-Stand.

## 3.0.0 – 2026-05-17

- 365CMS-3.0.0-Kompatibilität gesetzt und Plugin-Header, Konstanten sowie Update-Manifest auf `3.0.0` angehoben.
- Admin-Farbwert-Synchronisierung aus den View-Dateien in `assets/js/m365calculator-admin.js` ausgelagert; die Views enthalten damit keine Inline-`<script>`-Blöcke mehr.
- Admin-JavaScript wird zentral mit `defer` und `filemtime()`-Cache-Busting geladen.
- Public-Design-Token bereinigt: der statische Content-Top-Gap bleibt in den CSS-Dateien, während inline nur konfigurierbare Token ausgegeben werden.
- Prüfung nach OWASP DOM-XSS/CSRF und web.dev LCP/INP/Lazy-Loading-Leitplanken erneut als Modernisierungsbasis durchgeführt.

## 1.29.21 – 2026-05-17

- Erneuten vollständigen Public-/Admin-Audit für Sicherheit, Bugs, Weiterleitungen, Erkennung, Logikpfade und Geschwindigkeit durchgeführt.
- Admin-Zugriffsweiterleitung gehärtet: das Fallback-Ziel wird vor dem `Location`-Header auf lokale Pfade oder gültige HTTP(S)-URLs ohne Steuerzeichen begrenzt.
- Read-only-Matrixseiten stellen `history.scrollRestoration` beim Verlassen der Seite wieder auf den ursprünglichen Browserwert zurück.
- Landingpage-Suche beschleunigt: Section-Card-Listen werden einmalig gecacht statt bei jeder Eingabe neu per DOM-Query gesucht.
- Landingpage-Chip-Tastaturnavigation ist gegen leere Chip-Gruppen abgesichert; Hero-Suchscroll berechnet das Bewegungsverhalten nur noch einmal pro Klick.
- Teams-Phone-Score-Ausgaben werden als Integer ausgegeben und die Balkenbreite wird auf 0–100% begrenzt.

## 1.29.20 – 2026-05-17

- Scrollposition der Read-only-Matrixseiten korrigiert: Lizenzmatrix und Add-on-Matrix starten beim direkten Öffnen wieder oben am Contentheader.
- Der automatische Ergebnis-Fokus aus `m365calculator-public.js` wird auf `.m365calc-readonly-page` nicht mehr ausgeführt.
- Browser-Scroll-Restoration wird für Read-only-Matrixseiten ohne Hash-Ziel auf `manual` gesetzt und direkt auf Seitenanfang korrigiert.
- Ergebnis-Fokus und Smooth-Scroll bleiben für interaktive Rechnerseiten unverändert erhalten.

## 1.29.19 – 2026-05-17

- Erneuten vollständigen Public-/Admin-Audit für Sicherheit, Routing, Erkennung, Logikpfade und Geschwindigkeit durchgeführt.
- Public-Basisstyles gehärtet: `plugin-base.css` nutzt für Karten, Buttons, Inputs, Hinweise, Ergebnisboxen und Empty States jetzt den M365-UI-Radius mit maximal 2px statt PHINIT-Theme-Radien.
- Admin-Oberflächen optisch vereinheitlicht: Formularfelder, Alerts, Tabs, Callouts und Preistabellen sind auf 2px Radius reduziert.
- Frontend-Erkennung beschleunigt: Public-Routenliste, Toolbox-Erkennung, Rechnerseiten-Erkennung und aktueller Modulschlüssel werden pro Request gecacht.
- Audit erneut gegen Public-Wortfilter, JS-Sinks, SQL-Metadatenabfragen, Admin-CSRF, PHP-Lint, JSON und CSS-Radius-Ausreißer geprüft.

## 1.29.18 – 2026-05-17

- Admin-Speicherfix für Matrixen, zentrale Einstellungen, Landingpage Designer und Modulübersicht umgesetzt.
- MariaDB-kompatible Installer-Prüfung eingeführt: Tabellen- und Spaltenchecks laufen jetzt über `INFORMATION_SCHEMA` statt über `SHOW ... LIKE ?`.
- Der Save-Vorcheck kann dadurch Tabellen/Spalten prüfen, ohne einen SQL-Syntaxfehler nahe `?` auszulösen.
- Bestehende Migrationen und Legacy-Übernahme bleiben erhalten; `SHOW COLUMNS FROM` wird nur noch ohne Platzhalter für die sichere Spaltenliste einer bekannten Tabelle genutzt.

## 1.29.17 – 2026-05-17

- Erneuter vollständiger Audit mit Schwerpunkt auf Public-/Admin-Sicherheit, Weiterleitungen, Routenerkennung, Logikpfaden und Geschwindigkeit durchgeführt.
- Öffentliche POST-Aufrufe auf Rechnerseiten werden nicht mehr ausgewertet; sie werden per sicherem `303` auf den internen Pfad zurückgeführt. Dadurch entstehen keine öffentlichen Formular-/Sicherheitsmeldungen und keine Body-Datenverarbeitung auf Publicseiten.
- Landingpage-Card-Klicks verwenden nur noch vorhandene, serverseitig gefilterte Links; direkte Browser-Location-Zuweisung wurde entfernt.
- Routenerkennung beschleunigt: Public-Route-Map und normalisierter Request-Pfad werden pro Request gecacht und nicht mehrfach aufgebaut.
- Redirect-Ziele werden zusätzlich auf sichere interne Pfadzeichen begrenzt, bevor ein Location-Header gesetzt wird.

## 1.29.16 – 2026-05-17

- Vollständiger Public-/Admin-Audit für Sicherheit, Routing, Save-Logik, Public-Design und Performance durchgeführt.
- Admin-Speicherungen für Modulübersicht, Moduloptionen, Landingpage Designer, Matrixen, Preise und globale Einstellungen laufen jetzt atomar in Datenbanktransaktionen; bei Fehlern wird sauber zurückgerollt.
- Öffentliche Landingpage-Card-Navigation validiert Ziel-URLs im Browser zusätzlich auf gleiche Origin und sichere HTTP(S)-Pfade, bevor navigiert wird.
- Public-Design weiter vereinheitlicht: Landingpage- und Modul-Komponenten nutzen durchgängig maximal 2px Radius für Karten, Boxen, Formularflächen, Badges, Buttons und responsive Matrixkarten.
- Validierung erweitert: PHP-Lint, JSON-Parsing, Public-POST-Prüfung, Admin-CSRF-Formulare, JS-Sink-Suche, CSS-Radius-Suche und Scope-Check.

## 1.29.15 – 2026-05-17

- Speichern im Landingpage Designer und in Modul-/Global-Settings robuster gemacht: Optionswerte werden migrationssicher per Delete+Insert geschrieben und hängen nicht mehr von vorhandenen Unique-Indexes oder `ON DUPLICATE KEY UPDATE` ab.
- Admin-Save führt die Tabellenerstellung jetzt strikt aus, damit echte Tabellen-/SQL-Fehler nicht mehr vorher im Installer verschluckt werden.
- Tabellen-Neuanlage für Moduloptionen nutzt kürzere Index-Spalten, um ältere MySQL-/MariaDB-Indexlimits auf Shared-Hosting-Umgebungen sicherer zu unterstützen.
- Admin-Fehlermeldungen zeigen einen gekürzten technischen Hinweis, damit verbleibende DB-Probleme direkt im Backend erkennbar sind.
- Alte Matrix-Modul-Admin-URLs wie `m365tools-module-m365-lizenzmatrix` werden on-demand auf den zusammengeführten Matrixen-Bereich geroutet und lösen keinen 404-/Theme-Folgefehler mehr aus.

## 1.29.14 – 2026-05-17

- Admin-Speicheraktionen weiter gehärtet: Sicherheitsprüfungen schlagen nun geschlossen fehl, falls der CMS-Sicherheitsdienst fehlt oder der Sicherheitswert ungültig ist.
- Speicherpfade bleiben 500er-robust: fehlende Tabellen werden vor Save-Aktionen erneut geprüft/angelegt, Runtime-Fehler werden geloggt und als Admin-Hinweis ausgegeben.
- Public-Design-Erkennung robuster gemacht: Route-zu-Modul-Zuordnung wird zuerst aus der Modul-Registry abgeleitet und nur noch per Fallback ergänzt.
- Statischen Header-/Wrapper-Gap-Reset aus dem Inline-Head in `plugin-base.css` verschoben, damit der Public-Reset cachebar ist und nur dynamische Design-Tokens inline bleiben.
- Erzwungene systemseitige Dark-Mode-Farbüberschreibungen entfernt, damit die im Adminbereich gesetzten Landingpage- und Modulfarben immer Vorrang behalten.

## 1.29.13 – 2026-05-17

- Vollaudit für Public- und Admin-Flows umgesetzt: Admin-POST-Flows bleiben abgesichert, Eingaben werden feldtypbezogen normalisiert und DB-Tabellenbezeichner werden vor SQL-Nutzung gekapselt.
- Jede Modul-Adminseite erhält den neuen Tab `Public-Design` mit optionalem Modul-Override für Hintergrund, Surface, Header, Buttons, Text, Rahmen, Abschnittsabstand und maximal 2px Rundung.
- Public-Design-Tokens werden routegenau je Modul angewendet; Body-Klassen enthalten zusätzlich die aktive Modulkennung für gezielte Theme-/CSS-Diagnose.
- Read-only Lizenzmatrix und Add-on-Matrix übernehmen modulbezogene Design-Overrides ebenfalls und begrenzen Header-Rundungen konsistent auf maximal 2px.
- Frontend-Routenerkennung wurde zentralisiert, damit Assets, Body-Klassen, Header-Reset und Design-Tokens bei direkten Routen, Alias-/Unterpfad-Aufrufen und Modulseiten zuverlässig greifen.
- JSON-Kataloge sowie Modul-/Global-Optionszugriffe werden pro Request gecacht, um wiederholte Datenbank- und Dateisystemzugriffe auf Public- und Adminseiten zu reduzieren.

## 1.29.12 – 2026-05-17

- Landingpage und Public-Modulseiten erhalten innerhalb des Plugin-Hintergrunds 25px Abstand zwischen oberem Hintergrundrand und Contentheader/Headerbox.
- Die Admin-Farbe `Content-Hintergrund` im Landingpage Designer gilt nun ausdrücklich für Landingpage und Public-Modulseiten.
- Boxen, Contentheader und Buttons werden auf maximal 2px Rundung begrenzt.
- Karten, Toolbox-Elemente, Modulboxen und sekundäre Buttons nutzen eine dezente, leicht dunklere Surface-Fläche, damit sie sich sichtbar, aber ruhig vom Hintergrund abheben.

## 1.29.11 – 2026-05-17

- Den letzten sichtbaren 1–2px-Saum zwischen Theme-Header und M365TOOLS-Content pluginseitig geschlossen.
- Der M365TOOLS-Content-Host erhält auf öffentlichen Toolseiten einen minimalen 2px-Overlap, damit kein Theme-Hintergrund mehr zwischen Header und Plugin-Content durchscheint.
- Alle Modul-Publicseiten übernehmen nun die Landingpage-Design-Tokens für Hintergrund, Surface, Headerfläche, Karten, Buttons, Rundungen und Abschnittsabstände.
- `m365calculator-public.js` markiert Modul-Content-Hosts ebenfalls als `m365tools-content-host` und behandelt Zwischenknoten vor dem Content wie die Landingpage.

## 1.29.10 – 2026-05-17

- Header-Content-Abstand ausschließlich innerhalb von `cms-m365tools` nachgeschärft.
- Der M365TOOLS-Landingpage-Code markiert generische Zwischenknoten zwischen Theme-Header und eigenem Content als `m365tools-header-interstitial`.
- Plugin-CSS und spätes Critical-CSS blenden diese Zwischenknoten route-lokal aus und setzen den Content-Host auf volle Breite, weißen Hintergrund und ohne Schatten.
- Keine spezifische Abhängigkeit auf ein anderes Plugin; der Fix bleibt vollständig im M365TOOLS-Plugin.

## 1.29.9 – 2026-05-17

- Null-Abstand zum Theme-Header als spätes Critical-CSS im Head abgesichert (`output_edge_spacing_reset`, Priorität 120).
- Der Theme-Content-Host wird auf der Landingpage per JavaScript mit `m365tools-content-host` markiert, damit auch Browser-/Theme-Konstellationen ohne verlässliche Parent-Selector-Auswertung den Host-Reset erhalten.
- `#page.site`, `#content.site-content`, `.site-content.m365tools-content-host`, `#m365tools-landing` und `#m365calculator-landing` werden im späten Reset oben hart auf `0` gesetzt.

## 1.29.8 – 2026-05-17

- Null-Abstand zum Theme-Header robust nachgeschärft, weil 365Network die Plugin-Body-Klassen bisher nicht am `<body>` ausgegeben hat.
- ID-basierte Fallbacks für `#m365tools-landing` und `#m365calculator-landing` ergänzt.
- Zusätzliche `:has()`-Wrapper-Resets für `.site-content`, `.page-wrap`, `.site-main`, `.content-wrapper`, `.content-area`, `.content-area--page`, `.page-content` und `.entry-content` ergänzt.
- `margin-block-start` und `padding-block-start` ebenfalls auf `0` gesetzt, damit auch logical CSS-Abstände keine Restlücke erzeugen.

## 1.29.7 – 2026-05-17

- Verbleibenden Theme-Wrapper-Abstand oberhalb des öffentlichen M365-Plugin-Contents entfernt.
- Zusätzlich zu `main.phinit-plugin` werden nun auch `.page-wrap`, `.site-main`, `.content-wrapper`, `.content-area` und `.content-area--page` für M365-Toolseiten oben auf `0` gesetzt.
- Damit beginnt der Plugin-Content bündig direkt nach dem Theme-Header; gewünschte Abstände liegen ausschließlich innerhalb des Plugin-Contents.

## 1.29.6 – 2026-05-17

- Äußeren Abstand zwischen Theme-Header und M365-Plugin-Content entfernt.
- `main.phinit-plugin` startet auf öffentlichen M365-Tools-Seiten nun direkt nach dem Theme-Header mit `margin-top: 0`.
- Bestehende interne Plugin-Abstände bleiben erhalten; der Abstand entsteht damit nur noch im Plugin-Content selbst.

## 1.29.5 – 2026-05-17

- Hero-CTA final geschärft: Primärbutton führt als `Alle 21 Tools durchsuchen ↓` zum `#direkteinstieg`, scrollt weich zur Suche und fokussiert das Suchfeld.
- `Kontakt aufnehmen` ist im Hero jetzt ein ruhiger Textlink mit 13px Schrift, ohne Buttonrahmen.
- Sticky-Kategorie-Sidebar auf `top: 80px`, viewportbegrenzte Höhe und Scrollbereich angepasst.
- Aktive Kategorie im Sidebar-ToC wird per `IntersectionObserver` bei 0.3 Sichtbarkeit hervorgehoben und erhält 3px Navy-Kante, `font-weight: 500` und kurze Transition.
- Abstand zwischen Header/Kennzahlen und Direkteinstieg auf maximal 24px reduziert.
- Best-Practice-Kompass-Karten erhalten Tabler-Outline-Icons je Domäne und größere Beschreibungstexte mit 14px/1.65.
- Mobile Kategorieauswahl nutzt horizontale Filter-Chips; die Sidebar wird bis 768px ausgeblendet.
- Filter-Chips sind als ARIA-Radiogroup umgesetzt, inklusive `aria-checked`, roving focus und Pfeiltastensteuerung.
- Suchfeld unterstützt `/` als Tastaturkürzel und zeigt den Shortcut-Hinweis im Feld.
- Kategorie-Chips aktualisieren die URL per Hash; beim Laden wird ein passender Hash wieder als Filter aktiv.
- Scroll- und Transition-Verhalten respektiert `prefers-reduced-motion`.

## 1.29.4 – 2026-05-17

- M365-Tools-Landingpage nach UX-/UI-Review überarbeitet: Sticky Live-Suche, Kategorie-Chips und clientseitige Filterung ohne Reload ergänzt.
- Sticky Kategorie-Navigation als Desktop-Sidebar ergänzt; mobil wird die Navigation horizontal scrollbar unterhalb des Hero-/Finder-Bereichs geführt.
- Aktive Kategorie wird beim Scrollen per `IntersectionObserver` hervorgehoben.
- Toolcards erhalten größere Beschreibungstexte, besseren Zeilenabstand, stärkeren Textkontrast, volle Card-Klickfläche, Pointer-Cursor, Fokus-/Hover-State mit dezenter Bewegung und Navy-Border.
- Responsive Grid auf 3/2/1-Spalten-Logik verbessert und Touch-Ziele auf mindestens 44px ausgerichtet.
- CTA-Texte geschärft: Hero-Fallback führt direkt zur Toolsuche, Card-Buttons nutzen kontextuelle Texte wie `Rechner starten`, `Checkliste laden` oder `Tool öffnen`.
- Kategorie-Heros mit 24px SVG-Icon und 1-Satz-Beschreibung je Bereich ergänzt.
- `Beliebt`- und `Neu`-Badges für fokussierte Toolcards ergänzt.
- Back-to-top-Button ab Scrolltiefe ergänzt und automatischer Dark Mode über CSS Custom Properties eingebaut.
- Neues Vanilla-JS `assets/js/m365tools-landing.js` wird nur auf der Toolbox-Landingpage geladen.

## 1.29.3 – 2026-05-17

- Die Read-only `M365 Lizenzmatrix` und `M365 Add-on-Matrix` sind im Adminbereich nicht mehr als zwei separate Modul-Unterpunkte geführt, sondern im neuen Unterpunkt `Matrixen` gebündelt.
- Der neue Matrixbereich nutzt Tabs für `Lizenzmatrix`, `Add-on-Matrix` und `Design`.
- Lizenzmatrix- und Add-on-Matrix-Tab pflegen Headertexte, Buttontexte, Buttonziele, Matrix-Intro, CTA und bereichsspezifische Sichtbarkeit.
- Der Design-Tab steuert Contentheader-Stil, Header-Ausrichtung, Button-Layout, Button-Stil, Header-Rundung, Header-Farben und Button-Farben.
- Globale Design-Schalter können Contentheader, Header-Buttons, Einleitungsbereiche, Druckaktion, CTA, Hinweise, Quellenstand sowie Add-on-Bereichsheader und Paketkarten außerhalb der eigentlichen Matrixen abschalten.
- Public-Templates der beiden Matrixseiten übernehmen die neuen Einstellungen über globale Optionen und CSS-Variablen.

## 1.29.2 – 2026-05-17

- Landingpage Designer um deutlich feinere Sichtbarkeitsoptionen erweitert: Header-Overline, Header-Titel, Header-Intro, Header-Buttons, einzelne Header-Kennzahlen, Kategorie-Overline, Kategorie-Zähler, Review-Beschreibungen, Modultitel-Links, Tool-Buttons und Hinweise für inaktive Module sind separat schaltbar.
- Header-Buttons können nun mit eigenem Text und Ziel für Primär- und Sekundäraktion gepflegt werden.
- Modulbox-Buttons können ausgeblendet werden und unterstützen Zielmodi: jeweilige Toolseite, Primärbutton-Ziel, Sekundärbutton-Ziel oder eigenes globales Ziel.
- Neue Header-Designoptionen ergänzt: Header-Stil, Header-Ausrichtung und Button-Layout.
- Farbauswahl erweitert: eigene Farben für Contentheader-Hintergrund, Header-Text, Header-Sekundärtext, Header-Rahmen sowie Primär- und Sekundärbuttons.

## 1.29.1 – 2026-05-17

- `Landingpage Designer` aus den Tabs der zentralen Einstellungen herausgelöst und als eigener Admin-Untermenüpunkt unter `M365 Tools` ergänzt.
- Designer in eigene Tabs aufgeteilt: `Contentheader`, `Layouts & Boxen`, `Farben` und `Sichtbarkeit`.
- Neue Layoutoptionen ergänzt: Seitenbreite, Header-Varianten, Kategorie-Navigation, Kartenraster, kompaktes Raster, Liste, Verzeichnis und hervorgehobenes erstes Modul je Kategorie.
- Neue Designoptionen ergänzt: Box-Stil, Dichte, Kartenrundung, Mindestbreite, Abschnittsabstand und vollständige Landingpage-Farbpalette.
- Public-Landingpage wendet Designerwerte über CSS-Variablen und Layoutklassen an und kann Icons, Beschreibungen, Statuslabels, Review-Chips und Prüfpunkte gezielt ein- oder ausblenden.

## 1.29.0 – 2026-05-17

- Adminlayout pluginweit vereinheitlicht: Adminseiten nutzen die volle Contentbreite mit 25px Außenabstand und reduzierter, neutralerer Karten-/Tab-Optik.
- Neue zentrale Einstellungen `Dienstleister & Kontakt` ergänzt, inklusive Anbietername, CTA-Text, Kontaktformular-URL, Profil-Link, E-Mail, Telefon und Darstellungsmodus.
- Toolseiten zeigen den zentral gepflegten Dienstleister-Hinweis vor dem Footer, damit nach Rechneraufrufen ein einheitlicher nächster Schritt sichtbar wird.
- Neuer `Landingpage Designer` ergänzt: Header-Overline, Titel, Intro, Contentheader-Layout, Toolbox-Layout, Kartenbreite, Rundungen, Bereichssichtbarkeit, Review-Chips, Prüfpunkte und Öffnen-Button-Text sind adminseitig steuerbar.
- Landingpage liest die neuen Design- und Inhaltswerte direkt aus den globalen Optionen und rendert kompakte, Listen- oder Rasterlayouts ohne zusätzliche Tabellen.

## 1.28.1 – 2026-05-17

- Paketpreis-Adminseite optimiert: Public-, Member- und Spezialpreis werden je Paket in einer gemeinsamen Zeile nebeneinander angezeigt.
- Der Paketpreisbereich nutzt die volle Breite des Admincontent-Bereichs statt einer begrenzten Kartenbreite.
- Der Plugin-Adminbereich erhält oben 25px Abstand zum Fensterrand bzw. Admincontent-Rand.

## 1.28.0 – 2026-05-17

- Paketpreise werden bevorzugt aus dem `cms-m365lic` Seed-Katalog übernommen, sofern CMS M365 License aktiv ist; andernfalls bleiben lokale Fallback-Preise verfügbar.
- Globale Adminseite `Paketpreise` rendert jetzt M365-Pakete und Add-ons dynamisch mit Public-, Member- und Spezialpreis je SKU.
- `CMS_M365CALCULATOR_Catalog::pricing()` und `commitment_pricing()` leiten Referenzpreise und Laufzeitmodelle aus dem zentralen Paketkatalog ab.
- Abopreise & Laufzeiten berücksichtigen nun die M365LIC-Logik für Jahr/jährlich, Jahr/monatlich und Monat/flexibel mit zentral anpassbaren Aufschlägen.
- Modul-Unterpunkte im Adminmenü verwenden kurze Labels wie `Audit`, `Matrix`, `Teams Phone`, `Power Platform` oder `Workspace TCO`, damit die Sidebar kompakt bleibt.

## 1.27.0 – 2026-05-17

- Alle 21 Public-Module erneut gegen zusätzliche offizielle Microsoft-Learn-Quellen zu Endpoint-Webservice, Endpoint-Change-Management, Copilot-App-/Netzwerkanforderungen, Lizenzzuweisung, Gruppenlizenzierung und Microsoft 365 Backup geprüft.
- Admin-Menü logisch neu sortiert: zentrale Pluginbereiche stehen vor den Modul-Unterseiten, Module werden nach Fachkategorie und Priorität benannt.
- Neue zentrale Admin-Unterseite `Zentrale Einstellungen` mit Tabs für Allgemein, Review & Quellen, Workflow und System ergänzt.
- Neue eigene Admin-Unterpunkte `Paketpreise` sowie `Abopreise & Laufzeiten` für globale Preis-, Add-on-, Laufzeit-, Commitment- und Abrechnungsannahmen ergänzt.
- Globale Adminwerte werden ohne neue Tabelle über die vorhandene Optionsspeicherung gepflegt und können je Modul weiter überschrieben werden.
- Best-Practice- und Audit-Kataloge um Endpoint-Änderungsprozess, Gruppenlizenzierungsgrenzen, Copilot Office Feature Updates und Backup-PAYG-Kontext erweitert.

## 1.26.0 – 2026-05-17

- Alle 21 registrierten Public-Module erneut gegen offizielle Microsoft-Learn-Quellen zu M365-Endpunkten, Netzwerkplanung, Conditional Access, Defender for Office 365, Copilot Setup, Power-Platform-Requestgrenzen und Dataverse-Kapazität geprüft.
- `m365_best_practice_catalog.json` erweitert: Domänen enthalten jetzt konkrete Kontrollpunkte und jedes Modul bekommt eigene, landingpagefähige Prüfpunkte.
- Hub-Landingpage zeigt pro Tool neben Review-Domänen nun zwei konkrete aktuelle Prüfpunkte im ruhigen PHINIT-Layout.
- Lizenz-Audit-Checkliste um neue Punkte für Conditional-Access-Planung, Defender-Mail-Schutz, M365-Endpoints, Copilot-Setup und Power-Platform-Kapazität ergänzt.
- Power-Platform-Reifeprüfung bewertet zusätzliche Request-Last- und Dataverse-Kapazitätspfade mit offiziellen Quellen.
- Admin-Daten-Tab je Modul um Quellenprofil, Endpoint-/Netzwerkpfad, Schutz-/Datenzugriff und Servicegrenzen-/Kapazitätsreview ergänzt.

## 1.25.0 – 2026-05-17

- Admin-Menü erweitert: Jedes registrierte Public-Modul erhält genau einen eigenen Unterpunkt unter `M365 Tools`.
- Neue Modul-Einstellungsseite mit URL-basierten Tabs ergänzt: Übersicht, Anzeige, Preise & Annahmen, Workflow sowie Daten & Regeln.
- Anzeige-Tab nutzt die vorhandenen Modul-Overrides für Sichtbarkeit, Status, Sortierung, Titel und Beschreibung.
- Neue Tabelle `cms_m365tools_module_options` ergänzt, um Preisannahmen, Workflow-Vorgaben und Daten-/Regelhinweise modulbezogen zu speichern.
- Modul-Tabs werden aus der Tool-Registry und einer zentralen Admin-Konfiguration generiert; Copilot-, Exchange-, Teams-, Storage-, Backup-, Power-Platform-, Migration- und Preis-Tracker-Module bekommen passende Zusatzfelder.
- Admin-CSS für PHINIT-konforme Tabs, Formular-Grids, Übersichtsblöcke und Modul-Aktionen ergänzt.

## 1.24.1 – 2026-05-17

- Installer gegen ältere Modulsettings-Tabellen gehärtet, die noch nicht alle Override-Spalten enthalten.
- Migration von `cms_m365calculator_module_settings` nach `cms_m365tools_module_settings` liest vorhandene Legacy-Spalten jetzt spaltenbewusst und setzt sichere Standardwerte für fehlende Felder.
- Bestehende `cms_m365tools_module_settings`-Tabellen werden beim Installer-Lauf um fehlende Spalten ergänzt.
- Installer-Fehler werden protokolliert und blockieren Public- oder Admin-Seiten nicht mehr mit einem 500-Fehler.
- Bootstrap gegen doppelte Legacy-/Neu-Ladung abgesichert, damit parallel vorhandene Altinstallationen keinen Klassen-ReDeclare-Fatal auslösen.

## 1.24.0 – 2026-05-17

- Publicsites visuell überarbeitet, damit sie stärker wie gewachsene PHINIT-Seiten und nicht wie generierte SaaS-Kacheln wirken.
- Hub-Landingpage mit klarer Intro-Zone, Kennzahlen, Kategorie-Schnellnavigation, ruhigeren Bereichsköpfen und reduzierten Tool-Cards neu strukturiert.
- Gemeinsame Public-CSS-Schicht geglättet: weniger Pill-Optik, reduzierte Schriftgewichte, dezentere Fortschrittsbalken, funktionale Statuskanten und keine vollflächig eingefärbten Statuskarten.
- Modulicons, Badges, Review-Chips, Tabellenlabels, FAQ-Summarys, Audit-Items und Chart-Balken auf ruhigere PHINIT-Tokens und bessere Scanbarkeit angepasst.
- Public-Constraint beibehalten: keine technischen Formular-Prüfhinweise auf öffentlichen Seiten.

## 1.23.0 – 2026-05-17

- Alle Public-Module erneut gegen offizielle Microsoft-Learn-Quellen zu Best Practices, Schutz, Performance, Lizenzierung, Servicegrenzen, Backup, Copilot, Teams, Exchange, SharePoint, Entra und Power Platform geprüft.
- Neuen Katalog `m365_best_practice_catalog.json` ergänzt, der alle Module zentral den Review-Domänen Lizenz & Kosten, Identität & Zugriff, Schutz & Compliance, Servicegrenzen, Speicher & Backup, Netzwerk & Performance, Copilot & KI, Power Platform Betrieb sowie Migration & Betrieb zuordnet.
- Hub-Landingpage zeigt nun den All-Module-Best-Practice-Kompass und pro Modul fokussierte Review-Chips im PHINIT-Layout.
- Lizenz-Audit-Checkliste um neue Querschnittsprüfpunkte für privilegierte Rollen, Conditional Access, Mail-Schutz, Nutzungsberichte, Netzwerk-Basiswerte, SharePoint-/OneDrive-/Teams-Grenzen, Copilot-Datenzugriff und Wiederherstellungsziele erweitert.
- Deep Links der Audit-Checkliste um Exchange Online ROI und Copilot ROI ergänzt; öffentliche Texte bleiben frei von technischen Formular-Prüfmeldungen.

## 1.22.0 – 2026-05-17

- Power Platform Kosten-Kalkulator um Microsoft Well-Architected-, Security-, ALM-, Performance- und Operational-Excellence-Review erweitert.
- Neue Eingabefelder für Umgebungsstrategie, Datenrichtlinien, Identität/Rollen, Zugangsdaten, ALM, Monitoring, Performance-Ziele und Datenlebenszyklus ergänzt.
- Engine `CMS_M365CALCULATOR_Power_Platform_Cost_Calculator` gibt nun `best_practices` mit Score, Prüfpunkten, kritischen Punkten, Quellen und nächsten Schritten zurück.
- Governance-Katalog `power_platform_governance_rules.json` um Microsoft-Learn-Quellen und Best-Practice-Regeln für Security, Datenrichtlinien, Managed Environments, Performance, Datenmodell, Betrieb und ALM erweitert.
- Public Template zeigt den Best-Practice-Review im PHINIT-Layout, ohne serverseitige Speicherung und ohne technische Formular-Prüftexte.

## 1.21.0 – 2026-05-17

- Neues Modul `power-platform-cost-calculator` unter `/power-platform-kosten-kalkulator` ergänzt.
- Neue JSON-Kataloge `power_platform_products.json`, `power_platform_use_cases.json`, `power_platform_connector_rules.json`, `power_platform_capacity_catalog.json` und `power_platform_governance_rules.json` für Power Apps, Power Automate, Dataverse for Teams, Power Pages, Copilot Studio, Credits, Storage, Requests, PAYG, Capacity und Governance ergänzt.
- Engine `CMS_M365CALCULATOR_Power_Platform_Cost_Calculator` mit Eingabe-Normalisierung, Seeded-Rechte-Prüfung, Connector-Regeln, Dataverse-for-Teams-Fit, Kostenblöcken, Credit-Verbrauch, Capacity-Kosten, Warnungen und nächsten Schritten implementiert.
- Public Template `page-power-platform-cost-calculator.php` im PHINIT-Layout mit Szenarioformular, Ergebnis-Card, Kostenblöcken, Seeded-/Dataverse-Fit, Warnungen, nächsten Schritten und Quellenblock ergänzt.
- Tool-Registry, Frontend-Route, Katalogloader, Update-Manifest, README, API-, Datenbank-, Hooks- und Modul-Dokumentation synchronisiert.
- Public-Constraint beibehalten: keine serverseitige Speicherung und keine öffentlichen Formular-Prüfhinweise.

## 1.20.0 – 2026-05-17

- Neues Modul `workspace-m365-tco-calculator` unter `/google-workspace-zu-m365-tco` ergänzt.
- Neue JSON-Kataloge `google_workspace_plans.json`, `m365_target_plans.json`, `workspace_to_m365_mapping.json` und `migration_defaults.json` für Google-Planpreise, M365-Zielpläne, Mapping, Projektkosten, Hypercare, Parallelbetrieb und Migrationsleitplanken ergänzt.
- Engine `CMS_M365CALCULATOR_Workspace_M365_TCO_Calculator` mit Eingabe-Normalisierung, Auto-Mapping, 3-Jahres-TCO, Richtungslogik, Break-even, Delta-Auswertung, Warnungen, nächsten Schritten und Quellenstand implementiert.
- Public Template `page-workspace-m365-tco-calculator.php` im PHINIT-Layout mit Szenarioformular, TCO-KPIs, Plattformvergleich, Kostenentwicklung, Projektannahmen und Quellenblock ergänzt.
- Tool-Registry, Frontend-Route, Katalogloader, Update-Manifest, README, API-, Datenbank-, Hooks- und Modul-Dokumentation synchronisiert.
- Public-Constraint beibehalten: keine serverseitige Speicherung und keine öffentlichen Formular-Prüfhinweise.

## 1.19.0 – 2026-05-17

- Neues Modul `m365-storage-needs-calculator` unter `/m365-storage-bedarfsrechner` ergänzt.
- Neue JSON-Kataloge `sharepoint_storage_rules.json`, `onedrive_quota_presets.json`, `exchange_storage_rules.json` und `storage_growth_assumptions.json` für SharePoint-Pool, OneDrive-Quotas, Exchange-/Archivgrenzen, Wachstumsannahmen, Zusatzspeicher und Quellen ergänzt.
- Engine `CMS_M365CALCULATOR_Storage_Needs_Calculator` mit Eingabe-Normalisierung, SharePoint-/OneDrive-/Exchange-Trennung, Forecast, Puffer, Cleanup-Potenzial, Zusatzspeicherrechnung und Kapazitätsstatus implementiert.
- Public Template `page-storage-needs-calculator.php` im PHINIT-Layout mit Eingabe-Card, Status-Card, Bereichs-KPIs, Leitplanken-Tabelle, Detailwerten und Quellenstand ergänzt.
- Lizenz-Audit-Deep-Link `storage_review` auf den neuen Storage-Bedarfs-Rechner ergänzt.
- Public-Constraint beibehalten: keine serverseitige Speicherung und keine öffentlichen Formular-Prüfhinweise.

## 1.18.0 – 2026-05-17

- Neues Modul `m365-backup-cost-calculator` unter `/m365-backup-kostenrechner` ergänzt.
- Neue JSON-Kataloge `microsoft_backup_baseline.json`, `backup_providers.json` und `backup_comparison_rules.json` für offizielle Microsoft-Baseline, manuell gepflegte Providerdaten, Scoring und FAQ ergänzt.
- Engine `CMS_M365CALCULATOR_Backup_Cost_Calculator` mit Eingabe-Normalisierung, geschützter GB-Baseline, Provider-Normalisierung, Kostenranking, Workload-/Retention-/Restore-Bewertung und Empfehlung implementiert.
- Public Template `page-backup-cost-calculator.php` im PHINIT-Layout mit Baseline-KPI, Providervergleich, Detailkarten, FAQ und Quellenstand ergänzt.
- Lizenz-Audit-Deep-Link `backup_review` auf den neuen Backup-Kosten-Rechner umgestellt.
- Public-Constraint beibehalten: keine serverseitige Speicherung und keine öffentlichen Formular-Prüfhinweise.

## 1.17.0 – 2026-05-17

- Modul `copilot-pilot-calculator` unter `/copilot-pilot-rechner` fachlich erweitert.
- Microsoft-Quellenstand für Adoption, Lizenzierung, App-/Netzwerkanforderungen, Setup, Governance, Reporting, Feedback und Daten-/Compliance-Readiness auf `2026-05-17` aktualisiert.
- JSON-Kataloge `copilot_pilot_sizes.json`, `copilot_rollout_templates.json` und `copilot_readiness_checklist.json` um zusätzliche Quellen, Messkriterien, Governance-/Preflight-Punkte und FAQ-Bausteine ergänzt.
- Engine `CMS_M365CALCULATOR_Copilot_Pilot_Calculator` gibt Preflight-Checkliste und FAQ normalisiert an das Public Template aus.
- Public Template `page-copilot-pilot-calculator.php` um Governance-Checkliste vor Pilotstart und FAQ-Bereich ergänzt.
- Public-Constraint beibehalten: keine serverseitige Speicherung, keine öffentlichen Sicherheits-Hinweise und keine Abhängigkeit von Formular-Sicherheitsmeldungen.

## 1.16.0 – 2026-05-17

- Plugin auf `cms-m365tools` umbenannt.
- Plugin-Ordner und Hauptdatei auf `M365-PLUGINS/cms-m365tools/cms-m365tools.php` umgestellt.
- Öffentliche Plugin-URL, Update-Manifest, Plugin-Registry, Landingpage-Titel und Theme-Body-Klasse auf den neuen Slug erweitert.
- Interne Klassen und Legacy-Konstanten bleiben aus Kompatibilitätsgründen mit dem bisherigen Präfix lauffähig.
- Zentrale Dokumentation nach `DOC/cms-m365tools` verschoben und Einzeldokumente für alle 17 Public-Module unter `DOC/cms-m365tools/modules/` ergänzt.

## 1.15.0 – 2026-05-17

- Neues Modul `license-audit-checklist` unter `/m365-lizenz-audit-checkliste` ergänzt.
- Neue JSON-Kataloge `license_audit_checklist.json`, `license_audit_deeplinks.json` und `audit_pdf_template.json` für Auditpunkte, Tool-Deep-Links und druckfreundliche Zusammenfassungen ergänzt.
- Engine `CMS_M365CALCULATOR_License_Audit_Checklist` mit Eingabe-Normalisierung, Priorisierung, Audit-Druckstruktur, Deep-Link-Auswahl und Quellenstand implementiert.
- Public Template `page-license-audit-checklist.php` im PHINIT-Layout mit Rahmenauswahl, interaktiver Checkliste, Browser-Fortschritt, offener/erledigter Zusammenfassung, Detailrechnern und Quellenblock ergänzt.
- Gemeinsames Public-JavaScript und CSS um clientseitige Audit-Fortschrittsverwaltung, Fortschrittsbalken und druckfreundliche Summary erweitert.
- Plugin-Beschreibung, Tool-Registry, Frontend-Route, README und Update-Metadaten auf `1.15.0` angehoben.

## 1.14.0 – 2026-05-17

- Neues Modul `microsoft-price-tracker` unter `/microsoft-preiserhoehung-tracker` ergänzt.
- Neue JSON-Kataloge `microsoft_price_events.json`, `microsoft_price_changes.json`, `microsoft_inventory_mapping.json` und `microsoft_price_forecast_rules.json` für offizielle Microsoft-Preis-, Packaging-, SKU-, Renewal- und Forecast-Daten ergänzt.
- Engine `CMS_M365CALCULATOR_Microsoft_Price_Tracker` mit Filterlogik, Bestandsmapping, Renewal-Bewertung, Preisdelta, Forecast-Trennung und visuellen Chart-Zeilen implementiert.
- Public Template `page-microsoft-price-tracker.php` im PHINIT-Layout mit Filterformular, SKU-Bestand, Budgetwirkung, offiziellem Timeline-Table, Forecast-Bereich und Jahresvergleich-Chart ergänzt.
- Plugin-Beschreibung, Tool-Registry, Frontend-Route, README und Update-Metadaten auf `1.14.0` angehoben.

## 1.13.0 – 2026-05-16

- Neues Modul `teams-phone-advisor` unter `/teams-phone-lizenzberater` ergänzt.
- Neue JSON-Kataloge `teams_phone_base_eligibility.json`, `teams_pstn_model_rules.json`, `teams_country_availability.json`, `teams_voice_providers.json`, `teams_direct_routing_requirements.json` und `teams_phone_cost_assumptions.json` für Teams-Phone-Lizenzbasis, PSTN-Modelle, Länderannahmen, Provider, Direct-Routing-Voraussetzungen und Kostenannahmen ergänzt.
- Engine `CMS_M365CALCULATOR_Teams_Phone_Advisor` mit Normalisierung, Modell-Scoring, Readiness-Bewertung, Add-on-Bedarf, Kostenrahmen, Direct-Routing-Prüfung und Shared-Calling-Erkennung implementiert.
- Public Template `page-teams-phone-advisor.php` im PHINIT-Layout mit Lizenzbasis, PSTN-Modell, Betriebsanforderungen, Modellvergleich, Zusatzbausteinen, Voraussetzungen und Quellenstand ergänzt.
- Plugin-Beschreibung, Tool-Registry, Frontend-Route, README und Update-Metadaten auf `1.13.0` angehoben.

## 1.12.0 – 2026-05-16

- Neues Modul `exchange-online-roi` unter `/exchange-online-roi` ergänzt.
- Neue JSON-Kataloge `exchange_online_plans.json`, `onprem_exchange_cost_defaults.json` und `exchange_migration_velocity.json` für Cloud-Planpreise, On-Prem-Vollkosten, Refresh-Annahmen und Migrationsrichtwerte ergänzt.
- Engine `CMS_M365CALCULATOR_Exchange_Online_ROI_Calculator` mit Vollkostenrechnung, Exchange-Online-Zielkosten, 36-/60-Monats-Break-even, Migrationsfenster, Risikowert und Management-Fazit implementiert.
- Public Template `page-exchange-online-roi.php` im PHINIT-Layout mit Ist-/Soll-Kosten, Delta-Verlauf, Migrationspfad, Planvergleich und Quellenstand ergänzt.
- Plugin-Beschreibung, Tool-Registry, Frontend-Route, README und Update-Metadaten auf `1.12.0` angehoben.

## 1.11.0 – 2026-05-16

- Neues Modul `frontline-worker-license-check` unter `/frontline-worker-lizenz-check` ergänzt.
- Neue JSON-Kataloge `frontline_user_type_matrix.json`, `frontline_plan_matrix.json` und `frontline_industry_presets.json` für Rollenprofile, Gerätemodelle, Planbewertung, Branchenpresets und Preisannahmen ergänzt.
- Engine `CMS_M365CALCULATOR_Frontline_Worker_Check` mit Normalisierung, Frontline-Fit, F1-/F3-/Enterprise-Scoring, Gerätehinweisen, Risikobewertung und Sparpotenzial implementiert.
- Public Template `page-frontline-worker-check.php` im PHINIT-Layout mit Rollenprofil, App-Bedarf, Planvergleich, Quellenstand und druckbarem Ergebnis ergänzt.
- Tool-Registry, Frontend-Route, Plugin-Beschreibung und Update-Metadaten auf `1.11.0` angehoben.

## 1.10.0 – 2026-05-16

- Neues Modul `copilot-pilot-calculator` unter `/copilot-pilot-rechner` ergänzt.
- Neue JSON-Kataloge `copilot_pilot_sizes.json`, `copilot_rollout_templates.json` und `copilot_readiness_checklist.json` für Pilotstufen, Rollout-Zeitpläne und Readiness-Faktoren ergänzt.
- Engine `CMS_M365CALCULATOR_Copilot_Pilot_Calculator` mit Normalisierung, Readiness-Scoring, Pilotgrößenempfehlung, Budgetprüfung, Champion-Bedarf, Timeline und nächsten Schritten implementiert.
- Public Template `page-copilot-pilot-calculator.php` im PHINIT-Layout ergänzt.
- Add-on-Matrix trennt die bisherigen kombinierten Security-/Identity-/Device-Inhalte in eigene Bereiche für Intune, Entra ID, Defender und Purview.
- Version und Update-Metadaten auf `1.10.0` angehoben.

## 1.9.0 – 2026-05-16

- Neues Modul `ai-pack-vs-copilot-pro` unter `/ai-pack-vs-copilot-pro` ergänzt.
- Neue JSON-Kataloge `ai_product_catalog.json`, `ai_use_case_matrix.json` und `ai_dynamic_offers.json` für Produktpfade, Use-Case-Scoring und volatile Angebotslabels ergänzt.
- Engine `CMS_M365CALCULATOR_AI_Product_Comparison` mit Normalisierung, Scoring, Alternativen, Vergleichstabelle, Angebotslabel-Einordnung und nächsten Schritten implementiert.
- Public Template `page-ai-product-comparison.php` im PHINIT-Layout ergänzt.
- Öffentliche Fachtexte der vorhandenen Rechner neutralisiert und nicht-mutierende Public-Formulare auf sharebare Anfrageparameter umgestellt.

## 1.8.0 – 2026-05-16

- Neues Modul `m365-archive-mailbox` als Archive Mailbox Rechner unter `/m365-archive-mailbox-rechner` ergänzt.
- Neue JSON-Kataloge `archive_mailbox_plans.json` und `archive_mailbox_assumptions.json` für Mailboxgrößen, Archivkapazitäten, Auto-expanding Archive, Shared-/Resource-Sonderfälle und Quellen ergänzt.
- Archive-Engine mit Anfrage-Normalisierung, 12-Monats-Projektion, Auto-expanding-Eignung, Hold-/Purview-Hinweisen und Add-on-Kostenschätzung implementiert.
- Lizenzmatrix um konkrete Mailbox-, Archiv-, Shared-Mailbox-, SharePoint-, OneDrive-, Defender-for-Office-365- und Purview-Level erweitert.
- Add-on-Matrix um Intune Plan 2, Intune Suite, Entra Suite, Entra ID Governance, Defender for Endpoint P1/P2, Defender for Identity, Defender for Cloud Apps und neue Purview-Paketgruppe erweitert.

## 1.7.0 – 2026-05-16

- Neues Modul `m365-commitment-calculator` als Annual vs. Monthly Commitment Rechner unter `/m365-jahresvertrag-vs-monatsvertrag` ergänzt.
- Neue JSON-Kataloge `commitment_pricing.json`, `commitment_assumptions.json` und `commitment_channel_notes.json` für Preisannahmen, Schwellenwerte und Kanalhinweise ergänzt.
- Commitment-Engine mit Anfrage-Normalisierung, Monatslaufzeit, Jahreslaufzeit, jährlicher Abrechnung und Split-Strategie implementiert.
- Matrixseiten sprachlich von technischen Labels bereinigt und als Gesamtübersicht ohne horizontales Scrollen optimiert.
- Add-on-Matrix um Exchange-, SharePoint- und OneDrive-Größen-/Limitdetails erweitert.

## 1.6.0 – 2026-05-16

- Neue Public Site `m365-lizenzmatrix` unter `/m365-lizenzmatrix` ergänzt.
- Neue Public Site `m365-addon-matrix` unter `/m365-addon-matrix` ergänzt.
- JSON-Kataloge `readonly_suite_matrix.json` und `readonly_addon_matrix.json` für statische Vollpaket- und Add-on-Matrizen ergänzt.
- Engine `CMS_M365CALCULATOR_ReadOnly_Matrices` ergänzt, die Kataloge lädt und normalisiert.
- Vollpaket-Matrix listet Business Basic, Business Standard, Business Premium, Microsoft 365 E3 und Microsoft 365 E5 nebeneinander.
- Add-on-Matrix gruppiert Exchange, SharePoint/OneDrive/Backup, Teams/Telefonie, Copilot/KI, Security/Identity/Devices und Power Platform untereinander.

## 1.5.0 – 2026-05-16

- Neues Modul `m365-add-on-konfigurator` als Add-On-Konfigurator unter `/m365-add-on-konfigurator` ergänzt.
- JSON-Kataloge `addon_configurator_addons.json`, `addon_overlap_rules.json` und `consumption_modules.json` ergänzt.
- Konfigurator-Engine `CMS_M365CALCULATOR_Addon_Configurator` mit Prerequisite-Prüfung, Redundanz-Erkennung, automatischen Zusatzpfaden, Upgrade-vs-Add-on-Vergleich und Verbrauchslogik implementiert.
- Public Template `page-addon-configurator.php` mit linkem Konfigurator, rechtem SKU-Summenblock, Status-Tags und Add-on-Tabelle ergänzt.
- Teams Phone, Calling Plans, Resource Accounts, Copilot, Power Platform Premium und Microsoft 365 Backup als Sonderlogiken abgebildet.

## 1.4.0 – 2026-05-16

- Neues Modul `m365-lizenzvergleich` als Lizenz-Vergleichstabelle unter `/m365-lizenzvergleich` ergänzt.
- JSON-Kataloge `plan_comparison_feature_matrix.json`, `plan_comparison_badges.json` und `plan_comparison_notes.json` ergänzt.
- Vergleichsengine `CMS_M365CALCULATOR_License_Comparison` mit GET-Filtern, Spaltenauswahl, Statusmodell, Badges, Sortierung und Differenzmarkierung implementiert.
- Public Template `page-license-comparison.php` mit PHINIT-Komponenten, Feature-Matrix, Plan-Karten und Quellenblock ergänzt.
- Öffentliche Pluginseiten erhalten einen Plugin-eigenen Abstand von 25px zum Theme-Header.

## 1.3.0 – 2026-05-16

- Neues Modul `copilot-roi` als Copilot ROI-Rechner unter `/copilot-roi-rechner` ergänzt.
- ROI-Kataloge `copilot_pricing.json`, `copilot_readiness_rules.json`, `roi_assumptions.json` und `persona_roi_presets.json` ergänzt.
- ROI-Engine mit Readiness-Gate, konservativem/realistischem/optimistischem Szenario, Break-even-Minuten, Payback, Jahres-ROI und ungenutzter Potenzialquote implementiert.
- Public Template `page-copilot-roi.php` mit PHINIT-Komponenten, KPI-Karten, Szenariovergleich, Readiness-Status und 12-Monats-Chart ergänzt.
- Tool-Registry und Frontend-Routing um das Live-Modul `copilot-roi` erweitert.

## 1.2.0 – 2026-05-16

- Neues Modul `m365lic` als M365-Lizenzberater unter `/m365-lizenzberater` ergänzt.
- Alte `cms-m365lic`-Paket-, Feature-, Persona- und Preisdaten in JSON-Kataloge für das modulare Plugin migriert.
- Lizenzberater-Engine für bis zu fünf Nutzergruppen, Basislizenz-Empfehlungen, Add-ons, Alternativen, Kosten und Warnhinweise ergänzt.
- Adminbereich um pro-Modul-Steuerung für Sichtbarkeit, Status, Priorität, Titel und Beschreibung erweitert.
- Installer-Tabelle `m365calculator_module_settings` ergänzt.
- Landingpage und öffentliche Rechnerseiten auf PHINIT-kompatible 1200px Contentbreite und klarere Kartenstruktur angepasst.

## 1.1.0 – 2026-05-16

- Neues Modul `copilot-lizenz-check` als Copilot Lizenz-Pflicht-Checker ergänzt.
- Copilot-Eligibility-Matrix, technische Prerequisite-Regeln und Upgrade-Pfade als JSON-Kataloge ergänzt.
- Checker-Logik trennt Basislizenz, Copilot Chat, technische Readiness, gemischte Tenants und Upgrade-Pfade.
- Public Template `page-copilot-license-check.php` mit PHINIT-Komponenten, Readiness-Status und Quellenblock ergänzt.
- Hub-Registry um das neue Live-Modul mit Inline-SVG-Icon erweitert.
- Gemeinsames Rechner-JavaScript für mehrere Module verallgemeinert.

## 1.0.1 – 2026-05-16

- Hub-Landingpage `templates/landing.php` für alle registrierten Rechner-Module ergänzt.
- Tool-Registry auf `register()` mit `key`, `title`, `description`, `icon`, `url`, `category` und `status` umgestellt.
- Shared-Mailbox-Modul als selbstregistrierter `live`-Eintrag eingebunden.
- Inline-SVG-Icon-Helper mit Start-Icons für Mailbox, Lizenz, Calculator, Security, Storage und ROI ergänzt.
- Grid-CSS nach `assets/css/style.css` ausgelagert und Landingpage-Assets selektiv geladen.

## 1.0.0 – 2026-05-16

- Neues Plugin als modulare Microsoft-365-Rechner-Toolbox angelegt.
- Erstes Modul `shared-mailbox-vs-lizenz` implementiert.
- Entscheidungslogik für Shared Mailbox ohne Lizenz, Zusatzlizenz, Grenzfall, User-Mailbox und Microsoft 365 Group ergänzt.
- PHINIT-konformes Public Template mit `phinit-*` Basis-Komponenten erstellt.
- JSON-Kataloge für Regeln, Szenarien, Lizenzmatrix und Preisannahmen ergänzt.
