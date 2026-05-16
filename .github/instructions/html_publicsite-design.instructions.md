---
applyTo: "*/templates/**/*.php,*/views/**/*.php,*/member/**/*.php"
---

# 365CMS / PHINIT — Plugin-Richtlinie (Public Templates & Design)

> **Verbindlich** für alle Frontend-Templates in `*/templates/`, `*/views/` und `*/member/`, die im Content-Bereich (zwischen Header und Footer) öffentlicher phinit.de-Seiten rendern.
>
> Diese Richtlinie vereint zwei Ebenen, die untrennbar zusammengehören: **wie HTML strukturiert wird** (semantisch, barrierefrei, wartbar) und **wie es aussieht** (themekonform, ohne KI-Handschrift). Ein Template ist erst dann fertig, wenn beide Ebenen erfüllt sind.
>
> Version 2.0 · 365CMS / PHINIT-Theme · Stand 2026

---

## 0. Geltungsbereich und zwei Grundprinzipien

Header, Hauptnavigation, Footer und Sidebar-Rahmen liefert das Theme. Ein Plugin-Template füllt ausschließlich den ihm zugewiesenen Content-Bereich und überschreibt diese Theme-Elemente **niemals**.

**Grundprinzip Struktur:** HTML beschreibt Bedeutung, nicht Aussehen. Ein `<div>` ist die letzte Wahl, nicht die erste. Wer ein semantisches Element durch ein `<div>` mit Klasse ersetzt, hat einen Fehler gemacht — auch wenn es optisch identisch aussieht.

**Grundprinzip Design:** Ein Besucher darf nicht erkennen können, wo das Theme aufhört und das Plugin anfängt. Ein Plugin-Bereich, der sich visuell vom restlichen Blog absetzt, ist ein Fehler — auch wenn er „für sich genommen schön" aussieht. Konsistenz schlägt Kreativität. Das Plugin ist Gast im Haus des Themes, kein Anbau mit eigener Fassade.

Das Design soll wirken, als hätte es ein Entwickler mit jahrzehntelanger Erfahrung gebaut — zurückhaltend, funktional, jeder Pixel begründet. Nicht wie ein generierter Baukasten. **Im Zweifel: weglassen.** Ein Template, das zu schlicht aussieht, fügt sich ein. Eines, das auffällt, ist falsch.

---

## TEIL A — HTML-STRUKTUR

## 1. Semantische Elemente

| Kontext | Pflicht-Element | Falsch |
|---------|----------------|--------|
| Haupt-Inhaltsbereich des Plugins | `<main>` (oder `<section>`, falls das Theme bereits ein `<main>` setzt) | `<div class="main-content">` |
| Seitenspalte / Widget | `<aside>` | `<div class="sidebar">` |
| Einzelner Inhalts-Artikel (Card) | `<article>` | `<div class="card">` |
| Navigationselemente (Paginierung, Breadcrumb, Filter-Tabs) | `<nav>` | `<div class="pagination">` |
| Plugin-eigener Kopfbereich | `<header>` | `<div class="hero">` |
| Plugin-eigener Abschluss | `<footer>` | `<div class="footer-area">` |
| Gruppierter Inhaltsblock | `<section>` | `<div class="section">` |
| Zeitangaben | `<time datetime="ISO-8601">` | `<span class="date">` |
| Tabellarische Daten | `<table>` mit `<thead>`/`<tbody>` | verschachtelte `<div>` als Pseudo-Tabelle |

**Heading-Hierarchie:** Setzt das Theme bereits ein `<h1>` (Seitentitel), beginnt das Plugin bei `<h2>`. Es werden **keine Heading-Level übersprungen** (`<h2>` → `<h4>` ist verboten). Genau eine `<h1>`-Ebene pro Seite — entweder vom Theme oder, falls das Theme keine setzt, vom Plugin.

### Beispiel: Korrekte Struktur

```html
<!-- ✅ RICHTIG -->
<main class="phinit-plugin co-archive-main">
    <header class="co-archive-header">
        <p class="phinit-overline">Verzeichnis</p>
        <h1>Unternehmen</h1>
        <p>Alle Partner und Mitgliedsunternehmen.</p>
    </header>

    <nav class="co-filter-nav" aria-label="Unternehmensfilter">
        <form role="search">...</form>
    </nav>

    <section class="co-grid-section">
        <ul class="co-grid" role="list">
            <?php foreach ($companies as $company): ?>
            <li>
                <article class="phinit-card co-card">
                    ...
                </article>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <nav class="co-pagination" aria-label="Seitennavigation">
        ...
    </nav>
</main>

<!-- ❌ FALSCH -->
<div class="main">
    <div class="header"><div class="title">Unternehmen</div></div>
    <div class="grid"><div class="card">...</div></div>
</div>
```

---

## 2. Inline-Style-Regeln

### 2.1 VERBOTEN: Statische Inline-Styles

Statische Layoutwerte gehören niemals ins `style`-Attribut. Sie sind der Hauptgrund, warum generierte Templates „fast richtig, aber irgendwie daneben" wirken, und machen jede zentrale Designänderung unmöglich.

```php
/* ❌ NIEMALS – statische Layoutwerte inline */
<div style="margin-top: 40px; text-align: center;">…</div>
<div style="display: flex; gap: 1rem;">…</div>
<input style="max-width: 200px;">
<button style="border: none;">…</button>
<p style="font-size: 0.875rem; color: #64748b;">…</p>
```

**Lösung:** CSS-Klasse erstellen, die ausschließlich `--phinit-*`-Tokens verwendet (siehe Teil B).

### 2.2 ERLAUBT: Dynamische Inline-Styles

Nur datengetriebene Werte, die sich pro Datensatz ändern und nicht anders abbildbar sind, dürfen inline gesetzt werden — und auch dann **ausschließlich als CSS-Variable auf dem Element**, nie als direkter `background:`- oder `color:`-Wert.

```php
/* ✅ OK – Token-Wert aus DB, pro Datensatz unterschiedlich */
<article class="phinit-card co-card" style="--co-tier-border: <?= htmlspecialchars($tier_color, ENT_QUOTES) ?>;">…</article>

/* ✅ OK – PHP-berechneter Prozentwert */
<div class="phinit-progress" style="--phinit-progress: <?= (int)$progress_pct ?>%;">…</div>

/* ✅ BEVORZUGT – konfigurierbare Kategoriefarbe als CSS-Var */
<article class="phinit-card ev-card" style="--ev-cat-color: <?= htmlspecialchars($category_color, ENT_QUOTES) ?>;">
    <span class="ev-cat-badge"><!-- nutzt var(--ev-cat-color) aus CSS --></span>
</article>
```

**Wichtige Einschränkung gegenüber Altbestand:** Dynamische Farbwerte sind nur erlaubt, wenn sie aus einer kontrollierten Quelle stammen (Plugin-Einstellung, definierte Kategorie). Frei aus Nutzereingabe oder zufällig generierte Gradients (z. B. `crc32`-Avatare) sind im PHINIT-Theme **nicht zulässig** — sie erzeugen genau die bunte Beliebigkeit, die als KI-Design wahrgenommen wird. Avatare/Platzhalter verwenden eine einzige, ruhige Token-Fläche (`--phinit-color-surface-alt`) mit Initialen.

---

## 3. CSS in Templates (`<style>`-Blöcke)

### 3.1 ERLAUBT: `:root`-Variablen-Injection

Nur Werte, die aus Plugin-Einstellungen kommen und zur Build-Zeit nicht bekannt sind, dürfen über einen `<style>`-Block ins `:root` injiziert werden. Diese Injection **mappt nach Möglichkeit auf die Theme-Tokens**, statt eigene Parallel-Variablen einzuführen.

```php
<!-- ✅ RICHTIG: Plugin-Settings auf Theme-Tokens mappen -->
<style>
:root {
    /* Nur falls das Plugin konfigurierbare Werte hat – sonst ganz weglassen */
    --co-radius: <?= (int)($settings['design_border_radius'] ?? 10) ?>px;
}
/* Nur wenn kein anderer Weg: genau EINE dynamische Eigenschaft */
.co-grid { grid-template-columns: repeat(<?= (int)$grid_cols ?>, 1fr); }
</style>
```

### 3.2 VERBOTEN: CSS-Klassen-Definitionen inline

Vollständige Komponenten-Styles im Template sind verboten. Sie umgehen das Token-System, lassen sich nicht zentral pflegen und driften unweigerlich vom Theme weg.

```php
<!-- ❌ FALSCH: Komponenten-CSS im Template -->
<style>
.co-card { border-radius: 12px; padding: 1.25rem; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,.1); }
.co-card__title { font-size: 1.125rem; font-weight: 700; }
</style>
```

**Lösung:** In `assets/css/style.css` bzw. `assets/css/single.css` auslagern und dort **ausschließlich `--phinit-*`-Tokens** verwenden (siehe Teil B, Abschnitt 13).

---

## 4. Bilder

```html
<!-- ✅ PFLICHT: loading="lazy", alt, width/height -->
<img
    src="<?= htmlspecialchars($logo_url, ENT_QUOTES) ?>"
    alt="<?= htmlspecialchars($company_name, ENT_QUOTES) ?> Logo"
    loading="lazy"
    width="80" height="80"
    class="co-card__logo">

<!-- ✅ Responsive mit srcset wenn verfügbar -->
<img src="<?= htmlspecialchars($img_url, ENT_QUOTES) ?>"
     srcset="<?= htmlspecialchars($img_url_2x, ENT_QUOTES) ?> 2x"
     alt="<?= htmlspecialchars($img_alt, ENT_QUOTES) ?>"
     loading="lazy" width="..." height="...">

<!-- ❌ FALSCH -->
<img src="<?= $logo ?>">
```

**Regeln:** `alt` ist Pflicht (leer `alt=""` nur für rein dekorative Bilder). `width`/`height` immer angeben, um Layout-Shift zu vermeiden. `loading="lazy"` auf allen Bildern außer dem ersten above-the-fold-Bild. Niemals `style="width:…;height:…"` — Dimensionen über CSS-Klasse aus Tokens. Konsistentes Seitenverhältnis (16:9 für Artikel-Thumbnails) über `aspect-ratio` in der CSS-Klasse, nicht inline.

---

## 5. Links & Buttons

Strukturregel (was) und Design-Regel (wie) greifen hier ineinander: Es gibt **genau drei Button-Rollen** (Teil B, Abschnitt 11), und das HTML-Element folgt der Funktion.

```html
<!-- ✅ Link für echte Navigation (URL-Änderung) -->
<a href="<?= htmlspecialchars($url, ENT_QUOTES) ?>" class="phinit-btn phinit-btn--secondary">
    Details
</a>

<!-- ✅ Button für JS-Aktionen (kein href) -->
<button type="button" class="phinit-btn phinit-btn--secondary" aria-label="Firma merken">
    <svg aria-hidden="true">...</svg>
</button>

<!-- ✅ Externer Link -->
<a href="<?= htmlspecialchars($ext_url, ENT_QUOTES) ?>"
   target="_blank" rel="noopener noreferrer"
   class="phinit-btn phinit-btn--link">
    Website besuchen <span class="phinit-arrow" aria-hidden="true">→</span>
</a>

<!-- ❌ FALSCH -->
<a href="javascript:void(0)" onclick="doAction()">Aktion</a>   <!-- → <button type="button"> -->
<div onclick="..." style="cursor:pointer;">Klick</div>          <!-- → <button> oder <a> -->
```

**Regeln:** `<a>` nur für echte Navigation, `<button>` für JS-Aktionen. Externe Links immer `target="_blank" rel="noopener noreferrer"`. Icon-only-Buttons immer `aria-label`. Pro sichtbarem Bereich **genau ein** primärer Button — niemals zwei primäre Buttons gleicher Gewichtung nebeneinander. Buttons werden nicht zentriert in einer leeren Fläche platziert (typisches generiertes Muster), sondern bündig im Bezug zu ihrem Inhalt.

---

## 6. Formulare & Filter

```html
<!-- ✅ RICHTIG: sichtbares Label IMMER über dem Feld, nie nur Placeholder -->
<form role="search" method="GET" class="co-filter-form">
    <div class="phinit-field">
        <label for="filter-city">Stadt</label>
        <select id="filter-city" name="city" class="phinit-select">
            <option value="">Alle Städte</option>
            <?php foreach ($cities as $city): ?>
            <option value="<?= htmlspecialchars($city, ENT_QUOTES) ?>"
                    <?= $filter_city === $city ? 'selected' : '' ?>>
                <?= htmlspecialchars($city) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="phinit-field">
        <label for="filter-keyword">Stichwort</label>
        <input type="search" id="filter-keyword" name="q" class="phinit-input"
               value="<?= htmlspecialchars($filter_q, ENT_QUOTES) ?>"
               placeholder="z. B. Exchange">
    </div>

    <button type="submit" class="phinit-btn phinit-btn--primary">Suchen</button>
    <a href="?" class="phinit-btn phinit-btn--link">Zurücksetzen</a>
</form>
```

**Regeln:** Jedes Feld hat ein **sichtbares** `<label>` mit `for`-Bezug — der Placeholder ist Beispiel, kein Ersatz fürs Label. Pflichtfeld-Markierung als dezentes Wort „erforderlich" in Sekundärfarbe, nicht als roter Stern. Validierungsfehler unter dem Feld mit konkretem Text und `aria-describedby`-Bindung, nie nur eine rote Umrandung. Fokus-Zustand ist der Akzent-Outline aus Teil B (kein Glow).

---

## 7. Leere Zustände (Empty States)

```html
<!-- ✅ RICHTIG: semantisch, ohne Inline-Style, ohne Emoji als UI -->
<div class="phinit-empty-state" role="status" aria-live="polite">
    <p class="phinit-empty-state__title">Keine Ergebnisse gefunden</p>
    <p class="phinit-empty-state__body">
        Andere Suchbegriffe versuchen oder
        <a href="?">den Filter zurücksetzen</a>.
    </p>
</div>

<!-- ❌ FALSCH: Inline-Style + Emoji als Grafikelement -->
<div style="text-align:center; padding:50px; color:#64748b;">
    🔍 Keine Ergebnisse vorhanden.
</div>
```

Hinweis zur Design-Konsistenz: Ein dekoratives Linien-Icon ist zulässig (einfarbig, aus dem Icon-Set des Themes, `aria-hidden="true"`). Emoji als UI-Element ist es nicht.

---

## 8. Breadcrumb-Navigation

```html
<nav class="phinit-breadcrumb" aria-label="Breadcrumb">
    <ol class="phinit-breadcrumb__list">
        <li><a href="/" class="phinit-breadcrumb__link">Start</a></li>
        <li aria-hidden="true" class="phinit-breadcrumb__sep">›</li>
        <li><a href="/companies/" class="phinit-breadcrumb__link">Unternehmen</a></li>
        <li aria-hidden="true" class="phinit-breadcrumb__sep">›</li>
        <li>
            <span class="phinit-breadcrumb__current" aria-current="page">
                <?= htmlspecialchars($company_name) ?>
            </span>
        </li>
    </ol>
</nav>
```

---

## 9. Pagination

```html
<nav class="phinit-pagination" aria-label="Seitennavigation">
    <?php if ($page > 1): ?>
    <a href="?page=<?= (int)($page - 1) ?>" rel="prev"
       class="phinit-btn phinit-btn--secondary" aria-label="Vorherige Seite">
        ← Zurück
    </a>
    <?php endif; ?>

    <span class="phinit-pagination__info">
        Seite <?= (int)$page ?> von <?= (int)$total_pages ?>
    </span>

    <?php if ($page < $total_pages): ?>
    <a href="?page=<?= (int)($page + 1) ?>" rel="next"
       class="phinit-btn phinit-btn--secondary" aria-label="Nächste Seite">
        Weiter →
    </a>
    <?php endif; ?>
</nav>
```

Auf Mobile sichtbar genug platziert (früher Pagination-Hinweis bei langen Listen). Aktive Seitenzahl, falls dargestellt, in Akzentfarbe — der einzige farbig gefüllte Zustand hier.

---

## 10. PHP-Ausgabe-Escaping

```php
/* ✅ Immer escapen – keine Ausnahmen bei User-/DB-Daten */
<?= htmlspecialchars($value) ?>
<?= htmlspecialchars($value, ENT_QUOTES) ?>   /* PFLICHT in Attributen */
<?= htmlspecialchars($url, ENT_QUOTES) ?>     /* URLs */
<?= (int)$count ?>                            /* Ganzzahlen: int-Cast */

/* ✅ Ausnahme: nur nach strip_tags()/White-List-Filterung */
<?= $sanitized_html ?>

/* ❌ NIEMALS */
<?= $user_value ?>
<?php echo $_GET['id'] ?>
```

**Regel:** In Attributwerten **immer** `ENT_QUOTES` — gilt auch für die dynamischen CSS-Variablen aus Abschnitt 2.2. Integer immer per `(int)`-Cast, auch in URLs (`?page=<?= (int)$page ?>`).

---

## TEIL B — VISUELLES DESIGN

## 11. Design-Tokens — die einzige erlaubte Wertquelle

Templates und ihre CSS-Dateien definieren **keine** festen Farben, Abstände, Schriftgrößen oder Radien. Alle visuellen Werte stammen aus CSS-Custom-Properties, die das Theme im `:root` bereitstellt. Das Plugin konsumiert sie ausschließlich. Jede Variable wird mit Fallback verwendet, damit das Template auch isoliert (Vorschau ohne Theme) nicht bricht — der Fallback ist Notbremse, nicht Normalfall.

### 11.1 Farb-Tokens

| Variable | Verwendung | Fallback |
|---|---|---|
| `--phinit-color-bg` | Seitenhintergrund | `#dde6f0` |
| `--phinit-color-surface` | Karten/Boxen (weiß) | `#ffffff` |
| `--phinit-color-surface-alt` | Sekundärfläche, Avatare | `#1b2a44` |
| `--phinit-color-ink` | Primärtext | `#1a2233` |
| `--phinit-color-ink-secondary` | Sekundärtext, Meta, Datum | `#5a6678` |
| `--phinit-color-ink-on-dark` | Text auf dunkler Fläche | `#e8edf5` |
| `--phinit-color-border` | Trennlinien, Rahmen | `#dde3ec` |
| `--phinit-color-accent` | Akzent (Orange) — CTAs, aktive Zustände | `#f5a623` |
| `--phinit-color-accent-ink` | Text auf Akzent | `#1a2233` |
| `--phinit-color-link` | Fließtext-Links | `#1f5fa8` |
| `--phinit-color-success` | Erfolg/Positiv | `#3b6d11` |
| `--phinit-color-warning` | Warnung | `#854f0b` |
| `--phinit-color-danger` | Fehler/Kritisch | `#a32d2d` |

Der Akzent ist **eine** Farbe, sparsam eingesetzt: primäre Buttons, aktiver Tab/Schritt, dünner Hervorhebungs-Border. Niemals als Flächenfarbe ganzer Sektionen, niemals für Fließtext, niemals zwei Akzentfarben nebeneinander. Status-Farben nur funktional gebunden (Validierung, Score), nie dekorativ.

### 11.2 Typografie-Tokens

| Variable | Verwendung | Fallback |
|---|---|---|
| `--phinit-font-display` | Überschriften | `'Space Grotesk', system-ui, sans-serif` |
| `--phinit-font-body` | Fließtext, UI | `'Inter', system-ui, sans-serif` |
| `--phinit-font-mono` | Code, IDs, technische Werte | `ui-monospace, monospace` |
| `--phinit-text-xs` … `--phinit-text-h1` | Größenskala | `0.75rem` … `1.875rem` |
| `--phinit-leading-body` / `--phinit-leading-tight` | Zeilenhöhen | `1.7` / `1.25` |

Genau drei Schriftgewichte: 400, 500 (Überschriften/Buttons), 600 (sparsam für starke Betonung). Fließtext auf maximal `68ch` Zeilenbreite (`.phinit-prose`).

### 11.3 Abstände, Radien, Schatten

4er-Abstandsskala (`--phinit-space-1` = 4px … `--phinit-space-10` = 64px). Frei gewählte px-Margins sind unzulässig. Genau zwei Radien (`--phinit-radius-sm` 6px, `--phinit-radius-md` 10px). Genau zwei Schatten: `--phinit-shadow-card` (ruhend) und `--phinit-shadow-raised` (nur Hover/Overlay). Glühende, farbige oder Neon-Schatten sind verboten — die deutlichste KI-Signatur.

---

## 12. Komponenten — themekonformer Standardsatz

Plugins erfinden keine neuen Button-, Input-, Tab- oder Karten-Stile. Sie verwenden die Klassen aus `plugin-base.css`:

- **Karten** `.phinit-card` — `--phinit-color-surface`, `--phinit-radius-md`, `--phinit-shadow-card`, 1px-Border. Kategorie/Status über `border-left: 3px` (`.phinit-card--accent|success|warning|danger`), nie über farbige Flächen. **Keine Karten in Karten.**
- **Buttons** `.phinit-btn` mit `--primary` (genau einer pro Bereich), `--secondary` (Standard, auch „Weiter lesen"), `--link` (tertiär, mit Pfeil-Hover-Animation). Mindesthöhe 44px.
- **Formulare** `.phinit-field` + `.phinit-input|select|textarea` — sichtbares Label, Fokus = Akzent-Outline ohne Glow.
- **Tabellen** `.phinit-table` — Kopf in Versalien-Sekundärfarbe, 0.5px-Trennlinien, keine Zebra-Streifen, kein Liniengitter, Zahlen rechtsbündig (`.phinit-num`).
- **Hinweise** `.phinit-note--info|success|warning|danger` — flach, Farbe nur im linken 3px-Border, kein farbig gefüllter Hintergrund, kein Icon-im-Kreis.
- **Schritte/Tabs** `.phinit-steps`/`.phinit-step` — aktiver Schritt via Akzent-Unterstrich, kein Verlaufs-Fortschrittsbalken, keine Erfolgs-Animation.
- **Sektionskopf** `.phinit-overline` — kleiner Versalien-Overline mit 1px-Trennlinie. **Niemals den dunklen Navy-Sektionsbalken des Themes nachbauen** — den nutzt ausschließlich das Theme; ein Plugin, das ihn imitiert, verwischt die Hierarchie.

---

## 13. Auslagerung & Token-Bindung in den CSS-Dateien

Komponenten-CSS lebt in `assets/css/style.css` (Listen/Archiv) bzw. `assets/css/single.css` (Detailseiten) und bindet `plugin-base.css` als Basis ein. **Jede** visuelle Eigenschaft dort referenziert ein `--phinit-*`-Token. Eine Suche in den Plugin-CSS-Dateien nach rohen Hex-Codes oder freien `px`-Margins muss leer bleiben (ausgenommen die Token-Fallback-Definitionen selbst). Plugin-eigenes CSS ist auf **Layout-Anordnung** beschränkt (Grid-Spalten, Reihenfolge, Plugin-Struktur) — niemals auf Farbe, Schrift, Abstand, Radius oder Schatten.

---

## 14. Bewegung & Interaktion

Erlaubt: Hover-Übergänge auf interaktiven Elementen (`transition: … .2s ease`), Pfeil-Wandern bei Link-Buttons (`translateX(4px)`), dezentes Akkordeon-Aufklappen (`max-height`-Transition), `:active`-Feedback (kurzes `scale(0.98)` oder Helligkeitswechsel). Verboten: scroll-getriggerte Einblendungen, Parallax, Auto-Play-Karussells ohne Nutzerkontrolle, pulsierende CTAs, Ripple, Konfetti, Marken-Spinner. Jede Bewegung respektiert `@media (prefers-reduced-motion: reduce)` und schaltet sich dort vollständig ab.

---

## 15. Responsive

Mobil-first. Pflicht-Prüfpunkte vor jedem Release: **375px, 768px, 1280px**. Auf `≤768px`: einspaltig, Karten gestapelt, Tabellen horizontal scrollbar in abgegrenztem Container oder als gestapelte Label-Wert-Paare (nie abgeschnitten). Touch-Targets durchgängig `≥44px`. Kein horizontales Scrollen der Gesamtseite. Teaser-Texte auf Mobile per `line-clamp: 2` begrenzen.

---

## 16. Barrierefreiheit (nicht verhandelbar)

Semantisches HTML (Teil A), korrekte Heading-Hierarchie ohne Sprünge, Kontrast ≥ 4,5:1 (Text) bzw. 3:1 (große Schrift/UI-Grenzen). Jedes interaktive Element tastaturerreichbar mit sichtbarem Fokus (Akzent-Outline). Bilder mit `alt` (dekorativ: leeres `alt`). Formularfehler via `aria-describedby` gebunden. Farbe nie einziger Informationsträger. Zielwert: Lighthouse Accessibility ≥ 95.

---

## 17. Verbotsliste — die KI-Design-Signaturen

Im Plugin-Content-Bereich ausnahmslos untersagt: Verlaufsflächen als Dekoration · mehr als eine Akzentfarbe · glühende/farbige Schatten, Neon-Ränder · Glassmorphism, Blur-Hintergründe · Emoji als UI-Element oder Aufzählungszeichen · zentrierte mehrzeilige Fließtextblöcke · drei oder mehr Schriftgewichte · vollflächig farbig gefüllte Hinweisboxen · Karten in Karten in Karten · Plugin-eigene Hero-Bereiche, die mit dem Theme-Featured konkurrieren · Auto-Play-Karussells · Konfetti-/Pulse-/Bounce-Animationen · Icon-in-buntem-Kreis · Schlagschatten als Standardzustand statt nur bei Hover · leere „atmende" Flächen ohne Funktion (Whitespace ist streng über die Token-Skala gebunden) · mittig schwebende Solo-Buttons · zwei gleichwertige primäre Buttons nebeneinander · zufällige/`crc32`-generierte Avatar-Gradients · lange Titel, mitten im Wort mit „…" abgeschnitten.

---

## 18. Checkliste vor dem Commit

**HTML-Struktur**
- [ ] `<main>`/`<section>`, `<article>`, `<nav>`, `<header>`, `<aside>`, `<table>` korrekt statt `<div>`
- [ ] Heading-Hierarchie ohne Sprünge, genau eine `<h1>`-Ebene pro Seite
- [ ] Alle Bilder mit `loading="lazy"` (außer first above-the-fold), `alt`, `width`/`height`
- [ ] Externe Links mit `rel="noopener noreferrer"`, Icon-only-Buttons mit `aria-label`
- [ ] `<a>` nur für Navigation, `<button type="button">` für JS-Aktionen
- [ ] Alle `$_GET`/`$_POST`/DB-Werte per `htmlspecialchars()` (`ENT_QUOTES` in Attributen)
- [ ] Pagination/Breadcrumb mit `<nav aria-label>` bzw. `aria-current="page"`
- [ ] Empty States ohne Inline-Style, ohne Emoji-als-Grafik

**Inline-Style & CSS**
- [ ] Kein statisches `style="…"` vorhanden
- [ ] Dynamische Inline-Styles nur als `--css-variable` aus kontrollierter Quelle
- [ ] Kein `<style>`-Block außer `:root`-Injection + max. 1 dynamische Regel
- [ ] Plugin-CSS-Dateien: keine rohen Hex-Codes / freien px-Margins (nur `--phinit-*`)

**Design & Konsistenz**
- [ ] Nebeneinandergelegt mit einer Theme-Standardseite ist optisch kein Bruch erkennbar
- [ ] Genau ein primärer Button pro sichtbarem Bereich
- [ ] Kein Nachbau des dunklen Theme-Sektionsbalkens / Theme-Hero
- [ ] Verbotsliste (Abschnitt 17) durchgegangen — kein Punkt trifft zu

**Responsive & A11y**
- [ ] 375 / 768 / 1280 px geprüft, kein horizontales Scrollen, nichts abgeschnitten
- [ ] Touch-Targets ≥ 44px, Tastaturnavigation vollständig, Fokus sichtbar
- [ ] `prefers-reduced-motion` deaktiviert alle Bewegung
- [ ] Lighthouse Accessibility ≥ 95

---

## 19. Referenz-Implementierung

`plugin-base.css` ist die normative technische Umsetzung dieser Richtlinie (Token-Fallbacks, alle Komponentenklassen, Responsive- und Reduced-Motion-Regeln). Jedes Plugin bindet sie ein und ergänzt nur Struktur. Ihre Werte werden niemals überschrieben.

**Im Zweifel gilt: weglassen.** Ein Plugin, das zu schlicht aussieht, fügt sich ein. Ein Plugin, das auffällt, ist falsch.