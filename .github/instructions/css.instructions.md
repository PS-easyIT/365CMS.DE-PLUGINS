---
applyTo: "*/assets/css/*.css,*/assets/css/**/*.css"
---

# 365CMS Plugin – CSS-Architektur & Design-Richtlinien

> Gilt für alle Public-CSS-Dateien in `*/assets/css/`.  
> Ziel: Klares, menschliches Design – kein "typisches KI-Produkt-Look".

---

## 1. Datei-Struktur pro Plugin

```
assets/css/
├── style.css      # Haupt-Stylesheet (Archive, Card, Filter, Pagination, Buttons)
├── single.css     # Detail-/Single-Seiten (Hero, Sidebar, Sections, Breadcrumb)
└── member.css     # Member-Dashboard-Bereich (optional, wenn Member-UI komplex)
```

**Lade-Reihenfolge im PHP:**
```php
// style.css – immer laden (Archive + Card)
echo '<link rel="stylesheet" href="' . CMS_PLUGIN_URL . 'assets/css/style.css?v=' . filemtime($path . '/style.css') . '">';
// single.css – nur auf Detail-Seiten laden
if ($is_single_page) {
    echo '<link rel="stylesheet" href="' . CMS_PLUGIN_URL . 'assets/css/single.css?v=' . filemtime($path . '/single.css') . '">';
}
```

---

## 2. CSS Custom Properties (Design Tokens)

### 2.1 Datei-Struktur der Variablen-Deklaration

```css
/* In style.css: Fallback-Werte (werden durch PHP überschrieben) */
:root {
  /* Primärfarben (aus Admin-Einstellungen, via PHP <style>:root{} überschrieben) */
  --[prefix]-primary:    #0891b2;  /* Adaptiv */
  --[prefix]-accent:     #e0f2fe;  /* Adaptiv */
  --[prefix]-radius:     12px;     /* Adaptiv */
  --[prefix]-card-bg:    #ffffff;  /* Adaptiv */

  /* Farbpalette (FEST, niemals per JS/PHP überschreiben) */
  --[prefix]-text:       #1e293b;
  --[prefix]-text-m:     #475569;
  --[prefix]-text-l:     #94a3b8;
  --[prefix]-border:     #e2e8f0;
  --[prefix]-bg:         #f8fafc;
  --[prefix]-white:      #ffffff;

  /* Schatten – zurückhaltend, niemals unübersichtlich */
  --[prefix]-shadow:     0 1px 3px rgba(0,0,0,.05), 0 4px 12px rgba(0,0,0,.06);
  --[prefix]-shadow-h:   0 4px 16px rgba(0,0,0,.10);

  /* Transition */
  --[prefix]-ease:       all .2s cubic-bezier(.4,0,.2,1);
}
```

### 2.2 Dynamische Variablen per PHP injizieren

**Nur `:root`-Variablen** dürfen inline per PHP in Templates übergeben werden:

```php
/* ✅ RICHTIG – nur CSS-Variablen inline */
<style>
:root {
  --co-primary: <?= htmlspecialchars($settings['design_primary_color'] ?? '#0891b2') ?>;
  --co-radius:  <?= (int)($settings['design_border_radius'] ?? 12) ?>px;
  --co-card-bg: <?= htmlspecialchars($settings['design_card_bg'] ?? '#ffffff') ?>;
}
/* Nur wenn der Wert nicht anders übergeben werden kann (z.B. grid cols): */
.co-grid { grid-template-columns: <?= $grid_cols ?>; }
</style>

/* ❌ FALSCH – ganzer Stylesheet-Block inline  */
<style>
.co-card { border-radius: 12px; padding: 1.5rem; ... }
</style>
```

---

## 3. Klassen-Konventionen (BEM-orientiert)

### 3.1 Prä-Fixes pro Plugin

| Plugin | Prefix | Beispiel |
|--------|--------|---------|
| cms-companies | `.co-` | `.co-card`, `.co-archive` |
| cms-events | `.ev-` | `.ev-card`, `.ev-archive` |
| cms-experts | `.expert-` | `.expert-card`, `.experts-archive-wrapper` |
| cms-speakers | `.sp-` | `.sp-card`, `.sp-archive` |
| cms-jobprofile-generator | `.jpg-` | `.jpg-card`, `.jpg-archive` |

### 3.2 Block-Element-Modifier Muster

```css
/* Block */
.co-card { }

/* Element */
.co-card__head { }
.co-card__title { }
.co-card__footer { }

/* Modifier */
.co-card--sponsor { }
.co-card--featured { }
.co-card--compact { }
```

---

## 4. Design-Prinzipien – Anti-KI-Look

### 4.1 Was VERMIEDEN werden muss (typischer "AI-Produkt-Look")

```css
/* ❌ VERMEIDEN */
.element {
  /* Glassmorphismus überall */
  backdrop-filter: blur(20px);
  background: rgba(255,255,255,0.1);

  /* Alles mit Gradient */
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

  /* Riesige Border-Radius (pill-shape für alles) */
  border-radius: 50px;

  /* Überall auffällige Schatten */
  box-shadow: 0 25px 50px rgba(0,0,0,0.4);

  /* Neon-Farben */
  color: #00f5ff;
  border-color: rgba(0,240,255,0.3);

  /* Übertriebene Animationen */
  animation: float 3s ease-in-out infinite;
  transform: translateY(-10px);
}
```

### 4.2 Was BEVORZUGT wird (menschlich, editorial, klar)

```css
/* ✅ BEVORZUGEN */
.element {
  /* Klare, einfarbige Hintergründe */
  background: #ffffff;

  /* Dezente Umrandung statt reiner Schatten */
  border: 1px solid #e2e8f0;
  border-radius: 5px;  /* nicht zu rund */

  /* Zurückhaltender Schatten */
  box-shadow: 0 1px 3px rgba(0,0,0,.05), 0 4px 12px rgba(0,0,0,.06);

  /* Lesbare, gut kontrastierte Farben */
  color: #1e293b;

  /* Nur dann subtile Hover-Transition */
  transition: box-shadow .2s, transform .15s;
}

.element:hover {
  box-shadow: 0 4px 16px rgba(0,0,0,.10);
  transform: translateY(-2px);
}
```

---

## 5. Farb-Systematik

### 5.1 Graustufen (für Text, Ränder, Hintergründe)

```css
/* Standard-Graustufen (Tailwind-inspiriert, angepasst) */
--color-900: #0f172a;  /* Tiefste Überschriften    */
--color-800: #1e293b;  /* Primärtext               */
--color-700: #334155;  /* Sekundärtext             */
--color-600: #475569;  /* Beschreibungstext        */
--color-400: #94a3b8;  /* Platzhalter, Labels      */
--color-300: #cbd5e1;  /* Dezente Trennlinien      */
--color-200: #e2e8f0;  /* Standard-Border          */
--color-100: #f1f5f9;  /* Zebrastreifen, Trennlinie*/
--color-50:  #f8fafc;  /* Seiten-Hintergrund       */
```

### 5.2 Akzentfarben

- Akzentfarben kommen **nur** im Kontext von Aktions-Elementen (Links, Buttons, Badges, aktive Zustände)
- Hintergründe sehr **helle Tints** der Primärfarbe (`opacity: 0.08` bis `0.12`)
- Text auf farbigen Hintergründen: **voller Kontrast** (`#fff` auf `--primary` ODER dunkles Farbton auf hellem Tint)

---

## 6. Typografie

```css
/* Schriften: nur System-Fonts, kein CDN */
body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  font-size: 1rem;
  line-height: 1.6;
  color: #1e293b;
}

/* Heading-Scale: augmented fourth (1.25) */
h1 { font-size: clamp(1.5rem, 3vw, 2rem);   font-weight: 700; line-height: 1.2; }
h2 { font-size: clamp(1.25rem, 2.5vw, 1.5rem); font-weight: 700; line-height: 1.3; }
h3 { font-size: 1.125rem;  font-weight: 700; line-height: 1.4; }
h4 { font-size: 1rem;      font-weight: 600; }

/* Kleine Labels/Badges: */
.label-xs {
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
```

---

## 7. Karten-Muster

### 7.1 Standard-Card

```css
.plugin-card {
  background: var(--co-card-bg, #fff);
  border: 1px solid var(--co-border, #e2e8f0);
  border-radius: var(--co-radius, 10px);
  padding: 1.25rem;
  box-shadow: var(--co-shadow);
  transition: box-shadow .2s, transform .15s;
}

.plugin-card:hover {
  box-shadow: var(--co-shadow-h);
  transform: translateY(-2px);
}
```

**Keine** farbigen Gradienten als Card-Hintergrund (außer für Partner/Featured-Varianten).

### 7.2 Hero/Header (Archive + Single)

```css
/* Gradient-Header: OK für Archive-Seite (aus User-Konfiguration) */
.plugin-archive-header {
  background: linear-gradient(135deg, var(--plugin-hdr-from), var(--plugin-hdr-to));
  padding: 2.5rem 2rem;
}

/* Single/Detail-Seite: KEIN Full-Gradient. Stattdessen: heller Akzent-Tint */
.plugin-hero {
  background: var(--plugin-detail-hdr-bg, #f8fafc);
  border-bottom: 3px solid var(--plugin-primary);
}
```

---

## 8. Buttons

```css
/* Primär */
.plugin-btn {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .5rem 1.125rem;
  border-radius: 8px;  /* NICHT 50px */
  font-weight: 600;
  font-size: .875rem;
  border: none;
  cursor: pointer;
  transition: background .15s, transform .12s, box-shadow .15s;
  text-decoration: none;
}

.plugin-btn-primary {
  background: var(--plugin-primary);
  color: #fff;
}
/* NUR dezenter Schatten – kein Neon-Glow */
.plugin-btn-primary:hover {
  filter: brightness(1.08);  /* statt kompl. Gradienten-Wechsel */
  transform: translateY(-1px);
}

/* Ghost */
.plugin-btn-ghost {
  background: transparent;
  border: 1px solid var(--plugin-border, #e2e8f0);
  color: var(--plugin-text-m, #475569);
}
```

---

## 9. Inline-Style-Regeln

### Was DARF inline bleiben

```php
/* ✅ Per-Item-Wert, der aus der Datenbank oder einer Berechnung kommt */
<div style="background: <?= $avatarGradient ?>;">…</div>

/* ✅ Pro-Element CSS-Variable setzen (für Tier-Farben) */
<div class="co-card" style="--co-tier-border: <?= $tierColor ?>">…</div>

/* ✅ PHP-berechneter Wert, der sich pro Datensatz unterscheidet */
<div style="width: <?= $progressPercent ?>%">…</div>
```

### Was NICHT inline sein darf

```php
/* ❌ Statische Design-Werte */
<div style="margin-top: 40px; text-align: center;">…</div>

/* ❌ Layoutwerte die für die gesamte Seite gelten */
<div style="display: flex; gap: 1rem; flex-wrap: wrap;">…</div>

/* ❌ Inhaltsbereich mit fester Breite */
<div style="max-width: 200px;">…</div>  <!-- → CSS-Klasse .filter-narrow -->
```

---

## 10. Responsive Design

```css
/* Mobile-First Breakpoints */
/* xs: < 480px */
/* sm: 480px - 640px */
/* md: 640px - 768px */
/* lg: 768px - 1024px */
/* xl: > 1024px */

/* Grid-Pattern: immer auto-fill statt fester Spaltenanzahl */
.plugin-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.25rem;
  padding: 0 1.5rem;
}

/* Für konfigurierbare Grid-Spalten: CSS-Variable nutzen */
.plugin-grid--config {
  grid-template-columns: var(--plugin-grid-cols, repeat(auto-fill, minmax(280px, 1fr)));
}
/* Im PHP: inline nur die Variable setzen */
/* <div class="plugin-grid" style="--plugin-grid-cols: repeat(3, 1fr)"> */

@media (max-width: 1024px) {
  .plugin-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
  .plugin-grid { grid-template-columns: 1fr; padding: 0 .75rem; }
}
```

---

## 11. Vermeidbare Muster (Checkliste)

- [ ] ❌ `backdrop-filter: blur()` auf mehr als 1–2 Elementen pro Seite
- [ ] ❌ `linear-gradient()` auf mehr als 2–3 Elementen pro Seite (Header + Avatar = OK)
- [ ] ❌ `border-radius > 20px` bei Karten (erlaubt bei Pills/Badges)
- [ ] ❌ `animation: ...` außer für Loading-States
- [ ] ❌ `box-shadow` mit mehr als `0.3` alpha-Wert
- [ ] ❌ Mehr als 3 unterschiedliche Schriftgrößen unter 1rem auf einer Seitenansicht
- [ ] ❌ Leuchtenede Randfarben (`border-color` mit hellen Neon-Tönen)
- [ ] ❌ Inline-Styles für statische Layoutwerte
- [ ] ❌ `<style>`-Blöcke mit vollständigen CSS-Klassen-Definitionen in Templates
