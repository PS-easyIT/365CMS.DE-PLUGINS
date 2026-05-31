# CMS M365 Landing – Changelog

## 1.0.17 – 2026-05-31

- Importer-/Medienbildpfade in der Beitragssektion robuster normalisiert: `images/importer/...`, `media-file?path=...` und absolute Medien-URLs werden als Hauptdomain-`/uploads/...`-URLs ausgegeben.
- Beitragsbilder werden im Template nicht mehr erneut durch die Theme-Media-Normalisierung geschickt, damit Zusatzdomains keine Media-Proxy-404 für Importer-Pfade auslösen.

## 1.0.16 – 2026-05-31

- Beitragsquelle erweitert: Kategorie inkl. aller Unterkategorien oder alle News/alle veröffentlichten Beiträge.
- Beitragsanzahl im Adminbereich auf letzte 6 oder 9 Beiträge steuerbar.
- Leere Beitrags-Overline, Titel und Intro bleiben leer und werden public nicht ausgegeben.
- Beitragslinks, Kategorie-Links und Beitragsbilder werden für SEO und Medienverfügbarkeit konsequent über die Hauptdomain aufgebaut.

## 1.0.15 – 2026-05-31

- PHINIT-Card-Styles (`content-cards.css`, `homepage-blog.css`) werden auf der M365-Landingseite gezielt mitgeladen, damit das Startseiten-Grid-Design auch auf Plugin-Domains greift.

## 1.0.14 – 2026-05-31

- Beitragssektion auf das echte CMS-PHINIT-Startseiten-Grid-Markup (`post-card`, `post-card-thumb`, `post-card-meta`) umgestellt.
- EditorJS-/JSON-Inhalte werden für Teaser jetzt als Klartext extrahiert, damit keine Rohdaten in den Cards sichtbar sind.

## 1.0.13 – 2026-05-31

- TypeError in der Admin-Auswahl behoben: numerische Select-Optionswerte werden vor dem Escaping sicher in Strings gewandelt.
- Escaping-Helfer akzeptiert jetzt skalare Werte defensiv, damit Admin-Rendering nicht wegen Integer-Werten abbricht.

## 1.0.12 – 2026-05-31

- Adminseite mit eigenem Wiederherstellungsmodus versehen, damit selbst Repository-/Layout-/CSRF-Fehler nicht mehr in die Core-Fehlerkarte fallen.
- Plugin-Hooks für FTP-Mischstände defensiv registriert; fehlende oder zeitversetzt hochgeladene Klassen lösen beim Init keine harte Admin-Ausnahme mehr aus.

## 1.0.11 – 2026-05-31

- Adminseite gegen unvollständige FTP-Uploads, fehlende Logs und Live-Datenbank-Zwischenstände gehärtet.
- Installer-/Repository-Lesefehler brechen die Admin-Shell nicht mehr ab; die Seite bleibt mit Fallback-Werten nutzbar und zeigt den Initialisierungsfehler als Admin-Hinweis.

## 1.0.10 – 2026-05-31

- Root-Routing für hinterlegte Zusatzdomains robuster gemacht, indem die Frontend-Routen auch im eigentlichen `register_routes`-Hook erneut registriert werden.
- Optionalen Beitragsbereich ergänzt: Admin-Auswahl einer CMS-Kategorie, Ausgabe der letzten sechs veröffentlichten Beiträge als PHINIT-Grid-Cards mit drei Spalten.
- Beitragsbereich kann gezielt nur auf hinterlegten Zusatzdomains angezeigt werden.

## 1.0.9 – 2026-05-31

- Domain-Mapping ergänzt: Eine oder mehrere Zusatzdomains können die M365-Landingpage direkt auf `/` ausliefern.
- Die Hauptdomain bleibt geschützt und nutzt weiterhin die normale Startseite.
- Admin-Tab `Domains` ergänzt; Eingaben werden wie bei Hubsites normalisiert und dedupliziert.

## 1.0.8 – 2026-05-30

- Hintergrundfarbe nutzt eine eigene Landing-CSS-Variable, damit andere M365-Styles sie nicht wieder auf Weiß überschreiben.
- Admin-Farbfelder speichern jetzt den Textwert bevorzugt und synchronisieren Picker und Hex-Feld in beide Richtungen.
- Versteckte Farbwerte behalten beim Speichern anderer Tabs ihren Default statt leer auf einen Fallback zu kippen.

## 1.0.7 – 2026-05-30

- Seitenhintergrund nutzt jetzt `#edf1f6` als Standard und bleibt nach dem Speichern erhalten.
- Alte leere oder weiße Hintergrund-Defaults werden defensiv auf `#edf1f6` migriert, ohne eigene Farbanpassungen zu überschreiben.
- Bereichscard-Bilder werden nicht mehr zugeschnitten, laufen über die volle Breite und sind auf maximal `205px` Höhe begrenzt.

## 1.0.6 – 2026-05-30

- Card-CTA `zum Bereich ->` sitzt jetzt rechts unten in jeder Bereichscard.

## 1.0.5 – 2026-05-30

- Bereichscards werden jetzt selbst als Link gerendert, nicht nur mit innerem Link.
- Ziel-URLs ohne führenden Slash und leere Ziel-URLs mit vorhandenem Slug werden automatisch auf öffentliche Pfade normalisiert.
- Dezenter CTA-Text `zum Bereich ->` wird für jede verlinkbare Card sichtbar ausgegeben.

## 1.0.4 – 2026-05-30

- Content-Header-Bild wird links neben Seitentitel, Untertitel und Einleitung angezeigt.
- Header-Bildhöhe ist im Adminbereich über `Header-Bildhöhe in px` steuerbar.
- Bildbreite passt sich proportional an die Höhe an; Headerbilder werden nicht mehr beschnitten.

## 1.0.3 – 2026-05-30

- Bereichscards sind jetzt vollflächig anklickbar, wenn ein Ziel-Link hinterlegt ist.
- Dezenter CTA-Hinweis `Zum Bereich →` je verlinkter Card ergänzt.
- Hover- und Fokuszustände für klickbare Cards verbessert.

## 1.0.2 – 2026-05-30

- Abstand zum Theme-Header gegen globale M365-Tools-Overrides abgesichert.
- Header-Button-Ziele werden im Adminbereich mit Standard-Slugs vorausgefüllt.
- Standard-Zielpresets werden direkt im Header-Tab angezeigt.
- Leere versteckte Zahlenwerte fallen auf die vorgesehenen Defaults zurück, statt auf `0` zu kippen.

## 1.0.1 – 2026-05-30

- Headerbild für den Content Header ergänzt; steuerbar per URL oder Mediathek.
- Drei Layoutvarianten ergänzt: Standard, Kompakt und Spotlight mit Headerbild-Spalte.
- Abstand zum Theme-Header und Theme-Footer im Adminbereich steuerbar gemacht.
- Seitentitel nutzt nun die volle Header-Card-Breite und ist etwas kleiner skaliert.
- Standard-Ziele und Slugs der M365-Plugins als Admin-Vorschläge bei Cards ergänzt.

## 1.0.0 – 2026-05-30

- Neues Plugin `cms-m365landing` erstellt.
- Zentrale Landingpage unter `/m365` für Matrixen, Azure Services, Tutorials und M365 Tools.
- Adminbereich für Content Header, Cards, Sichtbarkeit und Design.
- Cards mit Mediathek-Bild, Icon-Fallback, Ziel-Link, Button-Text, Sortierung und Aktiv-Status.
- Public Layout im M365-Design mit maximal drei Cards nebeneinander.
