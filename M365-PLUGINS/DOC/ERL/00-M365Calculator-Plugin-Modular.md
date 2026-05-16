Baue die öffentliche Landingpage (Hub-Übersicht) für das bestehende 365CMS-Plugin
"cms-m365calculator".

ZIEL
Eine Public-Seite, die ALLE verfügbaren Rechner-/Berechnungs-Module des Plugins
als Karten-Übersicht zeigt. Jedes Modul wird mit Titel, Kurzbeschreibung und
einem passenden Icon dargestellt und verlinkt auf das jeweilige Modul.
Diese Seite ist die zentrale Einstiegsseite unter z.B. /tools/ oder
/m365-rechner/ und muss neue Module automatisch aufnehmen, ohne dass die
Landingpage-Datei pro Modul geändert werden muss.

VERBINDLICHE DESIGNVORGABE
Halte dich strikt an "365CMS-PHINIT-Plugin-Richtlinie-v2.md" im Workspace.
Insbesondere:
- Nur semantisches HTML (<main>/<section>, <article>, <nav>, <header>),
  keine <div>-Ersatzkonstruktionen.
- Keine statischen Inline-Styles. Keine Komponenten-CSS im <style>-Block.
- Ausschließlich --phinit-*-Tokens und die Klassen aus plugin-base.css
  (.phinit-card, .phinit-btn, .phinit-overline, .phinit-note ...).
- Genau EIN primärer Button pro sichtbarem Bereich.
- KEIN Nachbau des dunklen Theme-Sektionsbalkens, KEIN Plugin-Hero, der mit
  dem Theme-Featured konkurriert.
- Verbotsliste (Abschnitt 17) einhalten: keine Verläufe, keine zweite
  Akzentfarbe, keine Glow-Schatten, kein Emoji als UI-Element, keine
  Karten-in-Karten, kein zentrierter Textblock.
- Icons: einfarbige Inline-SVG (currentColor), 24x24 viewBox, aus einem
  schlichten Outline-Stil. KEINE Emoji, KEINE Icon-in-buntem-Kreis-Optik.
- Lighthouse Accessibility >= 95, Pflicht-Breakpoints 375/768/1280.

MODUL-REGISTRY (Architektur)
Module melden sich selbst bei der Landingpage an, statt fest eincodiert zu
werden. Erwarte/erzeuge eine Registry-Struktur, in die jedes Modul einen
Eintrag schreibt mit:
  - key            (z.B. 'shared-mailbox', 'm365lic')
  - title          (z.B. 'Shared-Mailbox vs. Lizenz-Rechner')
  - description    (1 Satz, max ~120 Zeichen, sachlich, kein Marketing)
  - icon           (Name/Slug eines Icons aus dem Plugin-Icon-Set)
  - url            (Permalink zum Modul)
  - category       (z.B. 'Lizenzen', 'Kosten', 'Migration')
  - status         ('live' | 'beta' | 'soon')
Die Landingpage liest diese Registry, gruppiert nach category und rendert
die Module sortiert (live zuerst, dann beta, 'soon' am Ende dezent
ausgegraut und nicht verlinkt).

Lege die Registry so an, dass das bereits existierende Modul
"Shared-Mailbox vs. Lizenz-Rechner" automatisch erscheint und der spätere
Lizenzberater (key 'm365lic') durch bloßes Registrieren ohne Änderung an
der Landingpage dazukommt. Falls noch keine Registry existiert, erstelle
sie als schlanken Mechanismus passend zur bestehenden Plugin-Struktur
(z.B. ein modules-Array/Hook, das jedes Modul-Bootstrap befüllt).

SEITENAUFBAU (semantisch)
<main class="phinit-plugin">
  <header>
    <p class="phinit-overline">Rechner & Tools</p>
    <h1>M365 Rechner</h1>
    <p class="phinit-prose">Ein, zwei sachliche Sätze, was hier zu finden
       ist. Kein Marketing-Ton.</p>
  </header>

  Pro Kategorie:
  <section aria-labelledby="cat-<key>">
    <h2 id="cat-<key>"><Kategoriename></h2>
    <ul class="phinit-tool-grid" role="list">
      <li>
        <article class="phinit-card phinit-card--accent">
          <div class="phinit-tool-card__icon"> <Inline-SVG> </div>
          <h3>
            <a href="<url>"><Titel></a>
            <!-- bei beta/soon: dezentes Status-Label, kein knalliges Badge -->
          </h3>
          <p><Beschreibung></p>
          <a href="<url>" class="phinit-btn phinit-btn--link">
            Öffnen <span class="phinit-arrow" aria-hidden="true">→</span>
          </a>
        </article>
      </li>
      ... weitere Module ...
    </ul>
  </section>

  Empty-Fallback (falls eine Kategorie leer ist) über
  .phinit-empty-state, semantisch, ohne Inline-Style.
</main>

GRID
Das Tool-Grid ist die EINZIGE plugin-eigene CSS-Ergänzung, die erlaubt ist
(reine Layout-Anordnung, keine Farben/Schatten/Radien):
  .phinit-tool-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: var(--phinit-space-5);
    list-style: none; padding: 0; margin: 0;
  }
  @media (max-width: 768px) {
    .phinit-tool-grid { grid-template-columns: 1fr; }
  }
Lege diese Regeln in assets/css/style.css ab, NICHT inline. Alle anderen
visuellen Eigenschaften kommen aus plugin-base.css / Tokens.

ICON-HANDLING
Erzeuge ein kleines Icon-Set als Inline-SVG-Helper (PHP-Funktion oder
Partial), das anhand des icon-Slugs das passende SVG zurückgibt
(currentColor, 24x24, stroke-width konsistent). Mindestens diese Icons
für den Start: 'mailbox' (Shared-Mailbox), 'license' (Lizenzberater),
'calculator' (Default-Fallback), 'shield' (Security/Compliance),
'storage' (Storage), 'roi' (ROI/Kosten). Fehlt ein Icon, wird der
Default 'calculator' genutzt. Icons sitzen in
.phinit-tool-card__icon (Größe und Farbe per Token, KEIN farbiger
Kreis-Hintergrund).

SICHERHEIT & ESCAPING
Alle Registry-Werte (title, description, url, category) per
htmlspecialchars(..., ENT_QUOTES) ausgeben. URLs zusätzlich validieren.
Keine rohe Ausgabe.

RESPONSIVE & A11Y
- 375/768/1280 testen, kein horizontales Scrollen.
- Karten-Link als echtes <a>, ganze Karte optional klickbar via
  ::after-Overlay-Link-Technik, aber Tastaturfokus muss am sichtbaren
  Link liegen.
- 'soon'-Module: aria-disabled, nicht fokussierbar, visuell nur leicht
  reduzierte Deckkraft (Token-gesteuert), KEIN auffälliges "Coming Soon"-
  Banner.
- prefers-reduced-motion respektieren (kommt aus plugin-base.css).

LIEFERUMFANG
1. Die Landingpage-Template-Datei (Pfad passend zur bestehenden
   Plugin-Struktur, z.B. templates/landing.php).
2. Den Registry-Mechanismus + den Registry-Eintrag des bestehenden
   Shared-Mailbox-Moduls als Beispiel.
3. Den Icon-SVG-Helper mit den genannten Start-Icons.
4. Die Grid-CSS-Ergänzung in assets/css/style.css.
5. Kurze Notiz, wie ein neues Modul (z.B. m365lic) sich registriert -
   als Code-Kommentar im Registry-File.

Erkläre vor der Umsetzung kurz, wo du die Registry verankerst und warum,
dann setze um. Halte die Landingpage so schlank wie möglich -
im Zweifel weglassen.
```
