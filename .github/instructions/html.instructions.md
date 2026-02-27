---
applyTo: "*/templates/**/*.php,*/views/**/*.php,*/member/**/*.php"
---

# 365CMS Plugin – HTML-Richtlinien (Public Templates)

> Gilt für alle Frontend-Templates in `*/templates/`, `*/views/` und `*/member/`.  
> Ziel: Semantisches, barrierefreies, wartbares HTML.

---

## 1. Semantische Elemente

| Kontext | Pflicht-Element | Falsch |
|---------|----------------|--------|
| Haupt-Inhaltsbereich | `<main>` | `<div class="main-content">` |
| Seitenspalte / Widget | `<aside>` | `<div class="sidebar">` |
| Einzelner Inhalts-Artikel (Card) | `<article>` | `<div class="card">` |
| Navigationselemente (Paginierung, Breadcrumb) | `<nav>` | `<div class="pagination">` |
| Seiten-Header / Hero des Templates | `<header>` | `<div class="hero">` |
| Seiten-Footer (Template-end) | `<footer>` | `<div class="footer-area">` |
| Gruppierter Inhaltsblock | `<section>` | `<div class="section">` |
| Zeitangaben | `<time datetime="ISO-8601">` | `<span class="date">` |

### Beispiel: Korrekte Struktur

```html
<!-- ✅ RICHTIG -->
<main class="co-archive-main">
    <header class="co-archive-header">
        <h1>Unternehmen</h1>
        <p>Alle Partner und Mitgliedsunternehmen.</p>
    </header>

    <nav class="co-filter-nav" aria-label="Unternehmensfilter">
        <form>...</form>
    </nav>

    <section class="co-grid-section">
        <ul class="co-grid" role="list">
            <?php foreach ($companies as $company): ?>
            <li>
                <article class="co-card">
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
    <div class="header">
        <div class="title">Unternehmen</div>
    </div>
    <div class="grid">
        <div class="card">...</div>
    </div>
</div>
```

---

## 2. Inline-Style-Regeln

### 2.1 VERBOTEN: Statische Inline-Styles

```php
/* ❌ NIEMALS – statische Layoutwerte inline */
<div style="margin-top: 40px; text-align: center;">…</div>
<div style="display: flex; gap: 1rem;">…</div>
<input style="max-width: 200px;">
<button style="border: none;">…</button>
<p style="font-size: 0.875rem; color: #64748b;">…</p>
```

**Lösung:** CSS-Klasse erstellen.

### 2.2 ERLAUBT: Dynamische Inline-Styes

```php
/* ✅ OK – Farbwert aus Datenbank, ändert sich pro Datensatz */
<div class="co-card" style="--co-tier-border: <?= htmlspecialchars($tier_color) ?>;">…</div>

/* ✅ OK – Berechneter Gradient pro Datensatz (z.B. aus crc32(Name)) */
<div class="co-card__avatar" style="background: <?= $avatar_gradient ?>;">…</div>

/* ✅ OK – PHP-berechneter Prozentwert */
<div class="progress-bar" style="width: <?= (int)$progress_pct ?>%;">…</div>

/* ✅ BEVORZUGT für konfigurierbare Farben – CSS-Var auf Element setzen */
<div class="ev-card" style="--ev-cat-color: <?= htmlspecialchars($category_color) ?>;">
    <span class="ev-cat-badge"><!-- nutzt var(--ev-cat-color) aus CSS --></span>
</div>
```

**Regel für Farben aus DB:** Immer als `--css-variable` auf dem Element setzen, nie direkt als `background:` oder `color:` Wert.

---

## 3. CSS in Templates (<style>-Blöcke)

### 3.1 ERLAUBT: `:root` Custom Property Injection

```php
<!-- ✅ RICHTIG: Nur :root Variablen und nicht anders machbare dynamische CSS-Werte -->
<style>
:root {
    --co-primary:    <?= htmlspecialchars($settings['design_primary_color'] ?? '#0891b2') ?>;
    --co-accent:     <?= htmlspecialchars($settings['design_accent_color'] ?? '#e0f2fe') ?>;
    --co-radius:     <?= (int)($settings['design_border_radius'] ?? 10) ?>px;
    --co-card-bg:    <?= htmlspecialchars($settings['design_card_bg'] ?? '#ffffff') ?>;
}
/* Nur wenn kein anderer Weg: einzelne dynamische Eigenschaft */
.co-grid { grid-template-columns: <?= $grid_cols ?>; }
</style>
```

### 3.2 VERBOTEN: CSS-Klassen-Definitionen inline

```php
<!-- ❌ FALSCH: Vollständige CSS-Blöcke in Templates -->
<style>
.co-card {
    border-radius: 12px;
    padding: 1.25rem;
    background: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,.1);
}
.co-card__title { font-size: 1.125rem; font-weight: 700; }
</style>
```

**Lösung:** In `assets/css/style.css` oder `assets/css/single.css` auslagern.

---

## 4. Bilder

```html
<!-- ✅ PFLICHT: loading="lazy" auf allen Template-Bildern -->
<img
    src="<?= htmlspecialchars($logo_url) ?>"
    alt="<?= htmlspecialchars($company_name) ?> Logo"
    loading="lazy"
    width="80"
    height="80"
    class="co-card__logo"
>

<!-- ✅ Responsive Bilder mit srcset wenn verfügbar -->
<img
    src="<?= $img_url ?>"
    srcset="<?= $img_url_2x ?> 2x"
    alt="<?= htmlspecialchars($img_alt) ?>"
    loading="lazy"
>

<!-- ❌ FALSCH: Kein alt-Attribut, kein lazy loading -->
<img src="<?= $logo ?>">
```

**Regeln:**
- `alt`-Attribut ist **Pflicht** – leer (`alt=""`) nur für rein dekorative Bilder
- `width` und `height` angeben um Layout-Shift zu vermeiden
- `loading="lazy"` auf allen Bildern außer dem ersten (above-the-fold Hero-Bild)
- Kein `style="width:...;height:..."` – Dimensionen per CSS-Klasse

---

## 5. Links & Buttons

```html
<!-- ✅ Link für Navigation -->
<a href="<?= htmlspecialchars($url) ?>" class="co-btn co-btn--ghost">
    Details
</a>

<!-- ✅ Button für Aktionen (kein href) -->
<button type="button" class="co-btn co-btn--primary" aria-label="Firma merken">
    <svg ...></svg>  <!-- Icon ohne Text -->
</button>

<!-- ✅ Externer Link -->
<a href="<?= htmlspecialchars($ext_url) ?>"
   target="_blank"
   rel="noopener noreferrer"
   class="co-btn co-btn--external"
>
    Website besuchen
    <svg aria-hidden="true"><!-- Extern-Icon --></svg>
</a>

<!-- ❌ FALSCH -->
<a href="javascript:void(0)" onclick="doAction()">Aktion</a> <!-- → button type="button" -->
<div onclick="..." style="cursor:pointer;">Klick</div>        <!-- → button oder a -->
```

**Regeln:**
- `<a>` nur für echte Navigation (URL-Änderung)
- `<button>` für JavaScript-Aktionen (Modal öffnen, Favorit setzen, Filter zurücksetzen)
- Externe Links: immer `target="_blank" rel="noopener noreferrer"`
- Icon-only-Buttons: immer `aria-label`

---

## 6. Formulare & Filter

```html
<!-- ✅ RICHTIG: Suchformular mit Semantik -->
<form role="search" method="GET" class="co-filter-form">
    <label for="filter-city" class="co-filter-label">Stadt</label>
    <select id="filter-city" name="city" class="co-filter-select">
        <option value="">Alle Städte</option>
        <?php foreach ($cities as $city): ?>
        <option value="<?= htmlspecialchars($city) ?>"
                <?= $filter_city === $city ? 'selected' : '' ?>>
            <?= htmlspecialchars($city) ?>
        </option>
        <?php endforeach; ?>
    </select>

    <input type="search"
           id="filter-keyword"
           name="q"
           class="co-filter-input"
           value="<?= htmlspecialchars($filter_q) ?>"
           placeholder="Stichwort ..."
           aria-label="Stichwort-Suche">

    <button type="submit" class="co-btn co-btn--primary">Suchen</button>
    <a href="?" class="co-btn co-btn--reset">Zurücksetzen</a>
</form>
```

---

## 7. Leere Zustände (Empty States)

```html
<!-- ✅ RICHTIG: Semantisch + ohne Inline-Style -->
<div class="co-empty-state" role="status" aria-live="polite">
    <p class="co-empty-state__icon" aria-hidden="true">🔍</p>
    <p class="co-empty-state__title">Keine Ergebnisse gefunden</p>
    <p class="co-empty-state__body">
        Probiere andere Suchbegriffe oder
        <a href="?">setze den Filter zurück</a>.
    </p>
</div>

<!-- ❌ FALSCH -->
<div style="text-align: center; padding: 50px; color: #64748b;">
    Keine Ergebnisse vorhanden.
</div>
```

---

## 8. Breadcrumb-Navigation

```html
<nav class="co-breadcrumb" aria-label="Breadcrumb">
    <ol class="co-breadcrumb__list">
        <li class="co-breadcrumb__item">
            <a href="/" class="co-breadcrumb__link">Start</a>
        </li>
        <li class="co-breadcrumb__item" aria-hidden="true">›</li>
        <li class="co-breadcrumb__item">
            <a href="/companies/" class="co-breadcrumb__link">Unternehmen</a>
        </li>
        <li class="co-breadcrumb__item" aria-hidden="true">›</li>
        <li class="co-breadcrumb__item">
            <span class="co-breadcrumb__current" aria-current="page">
                <?= htmlspecialchars($company_name) ?>
            </span>
        </li>
    </ol>
</nav>
```

---

## 9. Pagination

```html
<nav class="co-pagination" aria-label="Seitennavigation">
    <?php if ($page > 1): ?>
    <a href="?page=<?= $page - 1 ?>"
       class="co-pagination__btn co-pagination__btn--prev"
       rel="prev"
       aria-label="Vorherige Seite">
        ← Zurück
    </a>
    <?php endif; ?>

    <span class="co-pagination__info">
        Seite <?= $page ?> von <?= $total_pages ?>
    </span>

    <?php if ($page < $total_pages): ?>
    <a href="?page=<?= $page + 1 ?>"
       class="co-pagination__btn co-pagination__btn--next"
       rel="next"
       aria-label="Nächste Seite">
        Weiter →
    </a>
    <?php endif; ?>
</nav>
```

---

## 10. PHP-Ausgabe-Escaping

```php
/* ✅ Immer escapen – keine Ausnahmen bei User-Daten */
<?= htmlspecialchars($value) ?>
<?= htmlspecialchars($value, ENT_QUOTES) ?>   /* für Attribute */

/* ✅ URLs */
<?= htmlspecialchars($url) ?>

/* ✅ Ganzzahlen: kein escaping notwendig, aber int-Cast sicher */
<?= (int)$count ?>

/* ✅ Ausnahme: geprüfte/verarbeitete HTML-Inhalte aus WYSIWYG */
<?= $sanitized_html ?>  /* Nur nach strip_tags() / White-List-Filterung */

/* ❌ NIEMALS */
<?= $user_value ?>         /* ohne Escaping */
<?php echo $_GET['id'] ?>  /* rohe User-Eingabe */
```

---

## 11. Ladeoptimierung

```html
<!-- ✅ CSS im <head>, JS am Body-Ende -->
<head>
    <link rel="stylesheet" href="...style.css?v=...">
    <link rel="stylesheet" href="...single.css?v=...">
</head>
<body>
    <!-- Inhalt -->
    <script src="...script.js?v=..." defer></script>
</body>

<!-- ✅ Conditional: single.css nur auf Detail-Seiten -->
<?php if ($template_type === 'single'): ?>
<link rel="stylesheet" href="<?= $css_path ?>single.css?v=<?= $css_v ?>">
<?php endif; ?>
```

---

## 12. Checkliste vor dem Commit (Templates)

- [ ] Kein `<style>` Block außer `:root` Variablen-Injection + max. 1-2 dynamische CSS-Regeln
- [ ] Kein statisches `style="..."` Attribut vorhanden
- [ ] `<main>`, `<article>`, `<nav>`, `<header>`, `<aside>` korrekt eingesetzt
- [ ] Alle Bilder haben `loading="lazy"` (außer first above-the-fold image)
- [ ] Alle Bilder haben `alt`-Attribut
- [ ] Alle externen Links haben `rel="noopener noreferrer"`
- [ ] Icon-only-Buttons haben `aria-label`
- [ ] Alle `$_GET` / `$_POST` / DB-Werte per `htmlspecialchars()` escaped
- [ ] Pagination mit `<nav aria-label="...">` ausgezeichnet
- [ ] Breadcrumb mit `aria-current="page"` auf aktuellem Element
- [ ] Empty States ohne Inline-Styles using `.plugin-empty-state` CSS-Klasse
