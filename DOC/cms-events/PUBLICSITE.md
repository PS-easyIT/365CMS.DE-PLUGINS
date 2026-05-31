# cms-events – Public Frontend Richtlinie

> Gilt für alle öffentlich zugänglichen Seiten des Events-Plugins:
> Archiv-/Übersichtsseite, Grid-Cards und Event-Detailseite (Single).
>
> Letzte Aktualisierung: 2026-05-31  
> Stand: `cms-events` 3.0.28  
> Übergeordnete Richtlinie: `.github/instructions/plugin-cms-network-public.instructions.md`

---

## 1. Farbprofil – Blau

Das Events-Plugin nutzt eine klare Blau-Palette, die Dynamik, Vertrauen und
eine moderne Konferenz-Atmosphäre vermittelt.

```css
:root {
    --ev-primary:        #3b82f6;   /* Blue-500   – Hauptakzent, Datum-Block, Links */
    --ev-primary-h:      #1d4ed8;   /* Blue-700   – Hover-State, Archiv-Header-Start */
    --ev-accent:         #60a5fa;   /* Blue-400   – Icons, Tag-Hintergründe */
    --ev-light:          #eff6ff;   /* Blue-50    – subtile Hintergründe */
    --ev-card-bg:        #f0f7ff;   /* Hellblau   – Card-Hintergrund */
    --ev-hdr-from:       #1d4ed8;   /* Blue-700   – Archiv-Header Gradient Start */
    --ev-hdr-to:         #3b82f6;   /* Blue-500   – Archiv-Header Gradient Ende */
    --ev-hdr-title:      #ffffff;   /* Weiß       – Header h1-Farbe auf dunklem BG */
    --ev-border:         #bfdbfe;   /* Blue-200   – Card-Rahmen (Ruhezustand) */
    --ev-cta:            #1e40af;   /* Blue-800   – CTA-Button-Hintergrund */
    --ev-text:           #1e293b;   /* Dunkelgrau – Primärtext */
    --ev-text-m:         #475569;   /* Mittelgrau – Subtext, Pills */
    --ev-text-l:         #94a3b8;   /* Hellgrau   – Hinweistexte */
    --ev-radius:         12px;      /* Card-Eckradius */
    --ev-shadow:         0 4px 16px rgba(59,130,246,.08);
    --ev-shadow-h:       0 12px 32px rgba(59,130,246,.16);
    /* Badge-Spezialfarben */
    --ev-online-badge:   #059669;   /* Emerald – Online-Farbe Text */
    --ev-online-badge-bg:#d1fae5;   /* Emerald – Online-Farbe BG */
    --ev-cancelled-bg:   #fee2e2;   /* Rot – Abgesagt BG */
    --ev-cancelled-color:#991b1b;   /* Rot – Abgesagt Text */
    --ev-card-height:    325px;     /* FEST – nicht ändern! */
}
```

**Rationale:**
- Kräftiges Blau wirkt dynamisch und professionell wie bei Tech-Konferenzen
- Heller Hintergrund (`#f0f7ff`) signalisiert den „Event-Charakter" ohne Bilder zu benötigen
- Dunkler Header-Gradient (Dunkelblau → Mittelblau) erzeugt Tiefe und Dramatik

---

## 2. Archiv-Seite (`archive-event.php`)

### 2.1 Datei-Struktur

```
templates/archive-event.php
assets/css/style.css              ← Archiv + Card-Styles
assets/js/script.js               ← Filter-Logik (Vanilla JS)
```

### 2.2 PHP-Variablen-Überschreibung

```php
$primary   = htmlspecialchars($settings['design_primary_color']      ?? '#3b82f6');
$hdr_from  = htmlspecialchars($settings['archive_header_bg_from']    ?? '#1d4ed8');
$hdr_to    = htmlspecialchars($settings['archive_header_bg_to']      ?? '#3b82f6');
$hdr_title = htmlspecialchars($settings['archive_header_title_color'] ?? '#ffffff');

echo "<style>:root{
    --ev-primary:{$primary};
    --ev-primary-h:{$hdr_from};
    --ev-hdr-from:{$hdr_from};
    --ev-hdr-to:{$hdr_to};
    --ev-hdr-title:{$hdr_title};
}</style>\n";
```

### 2.3 Seiten-Aufbau

```
.ev-archive
  ├── .ev-archive-header             ← Dunkler Blau-Gradient-Header
  │     └── .ev-archive-header-inner
  │           ├── .ev-archive-icon     (📅)
  │           ├── div > h1 + p         (Titel + Untertitel)
  │           └── .ev-archive-count    (Zähler-Chip)
  ├── .ev-filter-bar                 ← Filter-Leiste
  │     ├── .ev-filter-input         (🔍 Suche)
  │     ├── select (Kategorie)
  │     ├── select (Zeitraum: Kommend / Vergangen)
  │     ├── select (Online / Präsenz)
  │     └── [Reset-Button]
  ├── .ev-grid                       ← 3-spaltig → 2 → 1
  │     └── event-card.php × N
  └── .ev-pagination
```

---

## 3. Grid-Card (`event-card.php` + `.ev-card`)

### 3.1 HTML-Struktur

```html
<div class="ev-card [ev-card--featured|ev-card--past|ev-card--today]">

    <!-- Ribbon (Status / Kategorie) -->
    <div class="ev-card-ribbon [ev-ribbon-featured|ev-ribbon-today|ev-ribbon-past|ev-ribbon-cancelled]">
        ⭐ Featured  /  🔴 Heute  /  Vergangen  /  Abgesagt  /  {Kategorie}
    </div>

    <!-- Kopfzone: Datum-Block + Titel -->
    <div class="ev-card-head">
        <div class="ev-date-block">
            <span class="ev-date-day">15</span>
            <span class="ev-date-mon">Mär</span>
            <span class="ev-date-year">2026</span>
        </div>
        <div class="ev-card-identity">
            <h3 class="ev-card-title"><a href="{url}">{Titel}</a></h3>
            <div class="ev-card-category">{Kategorie}</div>
        </div>
    </div>

    <!-- Info-Pills (Ort · Speaker-Anzahl · Kapazität · Preis) -->
    <div class="ev-card-pills">
        <span class="ev-pill">📍 {Stadt}</span>
        <span class="ev-pill ev-pill-speakers">🎤 {Anzahl}</span>
        <span class="ev-pill ev-pill-capacity">🪑 {Kapazität}</span>
        <span class="ev-pill ev-pill-free">✅ Frei</span>
    </div>

    <!-- Tag-Pills -->
    <div class="ev-card-tags">
        <span class="ev-tag-pill">{Tag}</span>
        <!-- max. 4 Tags -->
    </div>

    <!-- Kurzbeschreibung -->
    <p class="ev-card-excerpt">{Beschreibung max. 120 Zeichen}…</p>

    <!-- Footer -->
    <div class="ev-card-footer">
        <a href="{url}" class="ev-btn ev-btn-primary">Details →</a>
        <a href="{reg_url}" target="_blank" rel="noopener"
           class="ev-btn ev-btn-ghost">Anmelden</a>
    </div>

</div>
```

### 3.2 Card-CSS (Pflicht-Regeln)

```css
/* Feste Höhe – UNVERÄNDERLICH */
.ev-card {
    height:     325px;
    min-height: 325px;
    max-height: 325px;
    display:    flex;
    flex-direction: column;
    overflow:   hidden;
    position:   relative;
    background: var(--ev-card-bg, #f0f7ff);
    border:     1px solid var(--ev-border, #bfdbfe);
    border-radius: var(--ev-radius, 12px);
    box-shadow: var(--ev-shadow);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Hover */
.ev-card:hover {
    transform:    translateY(-4px);
    box-shadow:   var(--ev-shadow-h);
    border-color: var(--ev-primary);
}

/* Status-Modifiers */
.ev-card--featured { border-color: #f59e0b; }  /* Goldener Rahmen */
.ev-card--past     { opacity: .75; }
.ev-card--today    { border-color: #ef4444; }

/* Ribbon */
.ev-card-ribbon {
    padding:     4px 10px;
    font-size:   0.72rem;
    font-weight: 700;
    text-align:  center;
    flex-shrink: 0;
    white-space: nowrap;
    overflow:    hidden;
    text-overflow: ellipsis;
}
.ev-ribbon-featured  { background: #fef3c7; color: #92400e; }
.ev-ribbon-today     { background: #fee2e2; color: #991b1b; }
.ev-ribbon-past      { background: #f1f5f9; color: #64748b; }
.ev-ribbon-cancelled { background: var(--ev-cancelled-bg); color: var(--ev-cancelled-color); }

/* Kopfzone */
.ev-card-head {
    padding:     0.75rem 0.875rem 0.5rem;
    display:     flex;
    gap:         0.75rem;
    align-items: flex-start;
    flex-shrink: 0;
}

/* Datum-Block */
.ev-date-block {
    display:       flex;
    flex-direction: column;
    align-items:   center;
    background:    var(--ev-primary, #3b82f6);
    color:         #fff;
    border-radius: 8px;
    padding:       6px 10px;
    min-width:     48px;
    flex-shrink:   0;
    line-height:   1.1;
}
.ev-date-day  { font-size: 1.2rem; font-weight: 800; }
.ev-date-mon  { font-size: 0.65rem; font-weight: 600; text-transform: uppercase; }
.ev-date-year { font-size: 0.6rem; opacity: .8; }

/* Info-Pills */
.ev-card-pills {
    display:   flex;
    flex-wrap: wrap;
    gap:       4px;
    padding:   0 0.875rem 0.4rem;
    flex-shrink: 0;
}
.ev-pill {
    display:       inline-flex;
    align-items:   center;
    gap:           3px;
    padding:       2px 8px;
    background:    rgba(59,130,246,.08);
    color:         var(--ev-text-m, #475569);
    border-radius: 6px;
    font-size:     0.75rem;
    font-weight:   500;
    white-space:   nowrap;
}
.ev-pill-online  { background: var(--ev-online-badge-bg); color: var(--ev-online-badge); }
.ev-pill-free    { background: #d1fae5; color: #065f46; }
.ev-pill-price   { background: #fef3c7; color: #92400e; }

/* Tag-Pills */
.ev-card-tags {
    display:   flex;
    flex-wrap: wrap;
    gap:       4px;
    padding:   0 0.875rem 0.4rem;
    flex-shrink: 0;
}
.ev-tag-pill {
    padding:       2px 8px;
    background:    rgba(59,130,246,.1);
    color:         #1d4ed8;
    border-radius: 6px;
    font-size:     0.7rem;
    font-weight:   500;
}

/* Excerpt */
.ev-card-excerpt {
    padding:   0 0.875rem;
    font-size: 0.82rem;
    color:     var(--ev-text-m, #475569);
    margin:    0;
    flex-grow: 1;
    overflow:  hidden;
    display:   -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

/* Footer */
.ev-card-footer {
    display:    flex;
    gap:        8px;
    padding:    0.6rem 0.875rem;
    margin-top: auto;
    border-top: 1px solid var(--ev-border, #bfdbfe);
    flex-shrink: 0;
}
```

### 3.3 Zonen-Höhenverteilung (325 px total)

| Zone | Klasse | Höhe (ca.) |
|------|--------|-----------|
| Ribbon | `.ev-card-ribbon` | ~26 px |
| Kopfzone | `.ev-card-head` | ~68 px |
| Info-Pills | `.ev-card-pills` | ~30 px |
| Tag-Pills | `.ev-card-tags` | ~26 px |
| Excerpt | `.ev-card-excerpt` | flex-grow: 1 (~87 px) |
| Footer | `.ev-card-footer` | ~44 px |

### 3.4 Ribbon-/Status-Matrix

| Status | Ribbon-Text | Hintergrund | Textfarbe |
|--------|------------|------------|----------|
| featured | ⭐ Featured | `#fef3c7` | `#92400e` |
| today | 🔴 Heute | `#fee2e2` | `#991b1b` |
| past | Vergangen | `#f1f5f9` | `#64748b` |
| cancelled | Abgesagt | `#fee2e2` | `#991b1b` |
| default | Kategorie-Name | `rgba(59,130,246,.1)` | `#1d4ed8` |

---

## 4. Single Site (`single-event.php`)

> Aktueller Publicsite-Stand ab `cms-events` 3.0.28: Das aktive Detail-Template rendert `main.phinit-plugin.cms-events-wrap.cms-events-detail`. Die Detail-Shell liegt bündig am Theme-Header und -Footer, füllt kurze Seiten bis zum Footer und begrenzt Breadcrumb sowie Detail-Grid auf maximal `1160px` Contentbreite. Responsive und Dark Mode werden im finalen Detail-Override in `assets/css/single.css` abgesichert.

### 4.1 Header-Zone

```
.ev-single-header   min-height: 300px
  background: linear-gradient(135deg, #1d4ed8, #3b82f6)
              oder: Event-Keyvisual + rgba(30,64,175,.8) Overlay

  ├── .ev-sh-date-block    (großer Datum-Block, 80×80px)
  ├── .ev-sh-text
  │     ├── h1             (Event-Titel – clamp(1.5rem,4vw,2.25rem), fw:800, color:#fff)
  │     ├── .ev-sh-category (Kategorie-Badge)
  │     └── .ev-sh-meta    (📍 Ort · 🕐 Uhrzeit · 💶 Preis-Badge)
  └── .ev-sh-actions
        ├── [Anmelden-Button]
        └── [Teilen-Button]
```

### 4.2 2-Spalten-Layout

```css
.ev-single-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 2rem;
    padding: 2rem 1.5rem;
    max-width: 1140px;
    margin: 0 auto;
    align-items: start;
}
@media (max-width: 768px) {
    .ev-single-layout { grid-template-columns: 1fr; }
    /* Sidebar über Main auf Mobile */
    .ev-single-sidebar { order: -1; }
}
```

### 4.3 Content-Sektionen (Main)

| Sektion | Icon | Inhalt |
|---------|------|--------|
| Beschreibung | 📋 | Volltext (WYSIWYG) |
| Agenda | 📅 | Zeitplan als Liste (Uhrzeit + Punkt) |
| Speaker-Lane | 🎤 | Speaker-Cards (Mini-Version, cross-plugin) |
| Location | 📍 | Adresse + Karte (wenn vorhanden) |

### 4.4 Sidebar-Cards

```html
<!-- Anmelde-CTA -->
<div class="ev-sidebar-card ev-sidebar-card--cta">
    <div class="ev-price-display">
        <!-- Kostenlos / Preis in EUR -->
    </div>
    <a href="{reg_url}" class="ev-btn ev-btn-primary ev-btn-block" target="_blank" rel="noopener noreferrer">
        🎟️ Jetzt anmelden
    </a>
    <p class="ev-sidebar-hint">🪑 Noch {n} Plätze verfügbar</p>
</div>

<!-- Key-Facts -->
<div class="ev-sidebar-card">
    <h3 class="ev-sidebar-title">Key-Facts</h3>
    <ul class="ev-facts-list">
        <li>📅 {Datum} · {Uhrzeit}</li>
        <li>📍 {Ort / Online}</li>
        <li>🎤 {Anzahl} Speaker</li>
        <li>🪑 {Kapazität} Plätze</li>
    </ul>
</div>

<!-- Sponsoren (cross-plugin: cms-companies) -->
<div class="ev-sidebar-card">
    <h3 class="ev-sidebar-title">Sponsoren</h3>
    <!-- Company-Logo-Raster -->
</div>

<!-- Speaker (cross-plugin: cms-speakers / cms-experts) -->
<div class="ev-sidebar-card">
    <h3 class="ev-sidebar-title">Speaker</h3>
    <!-- Avatar + Name + Position je Speaker -->
</div>
```

---

## 5. Filter-Felder (Archivseite)

| Feld | Typ | DB-Spalte / Quelle |
|------|-----|--------------------|
| Freitext | `<input type="search">` | `events.title`, `events.description` |
| Kategorie | `<select>` | `events.category` (DISTINCT) |
| Zeitraum | `<select>` | Berechnet: kommend / heute / vergangen |
| Format | `<select>` | `events.is_online` (Online / Präsenz) |
| Preis | `<select>` | `events.price_type` (kostenlos / kostenpflichtig) |

---

## 6. Responsive-Breakpoints

| Breakpoint | Grid | Card |
|------------|------|------|
| ≥ 1025 px  | 3 Spalten | 325 px Höhe |
| 641–1024 px | 2 Spalten | 325 px Höhe |
| ≤ 640 px   | 1 Spalte  | 325 px Höhe |

---

## 7. Cross-Plugin-Verknüpfungen

| Ziel-Plugin | Verwendung | Guard |
|-------------|-----------|-------|
| cms-speakers | Single Main + Sidebar: Speaker-Liste | `PluginManager::isPluginActive('cms-speakers')` |
| cms-experts | Single Sidebar: Expert als Speaker | `PluginManager::isPluginActive('cms-experts')` |
| cms-companies | Single Sidebar: Sponsoren | `PluginManager::isPluginActive('cms-companies')` |

---

## 8. Zeitliche Korrektheit

Events werden nach aktuellem Datum kategorisiert:

```php
$ev_ts    = $ev_date ? strtotime($ev_date) : 0;
$today_ts = strtotime('today');
$is_past  = $ev_ts && $ev_ts < $today_ts;
$is_today = $ev_ts && $ev_ts >= $today_ts && $ev_ts < ($today_ts + 86400);
```

- Vergangene Events erhalten `opacity: 0.75` + Status-Ribbon „Vergangen"
- Heute-Events erhalten roten Ribbon „🔴 Heute"
- Abgesagte Events (`status === 'cancelled'`) blenden den Anmelde-Button aus

---

## 9. SEO & Accessibility

- `<div class="ev-card">` mit `role="article"` oder in `<article>` wrappen
- `<h3>` für Event-Titel in Card
- Event-Schema.org Markup auf Single Site: `<script type="application/ld+json">`
- `aria-label="Vergangenes Event"` auf Cards mit `ev-card--past`
- Datum-Block: Zeitstempel via `<time datetime="{YYYY-MM-DD}">` ausgeben

---

## 10. Checkliste (Events Public Frontend)

- [ ] `--ev-card-height: 325px` in CSS, alle drei Höhen-Properties gesetzt
- [ ] `overflow: hidden` auf `.ev-card`
- [ ] Ribbon immer sichtbar (auch ohne Status = Kategorie-Name)
- [ ] Vergangene Events: `opacity: .75` + kein Anmelde-Button
- [ ] Abgesagte Events: kein Anmelde-Button, Ribbon „Abgesagt"
- [ ] Excerpt per `-webkit-line-clamp: 2` begrenzt
- [ ] Datum-Block: `<time datetime="...">` für SEO
- [ ] Hover: `translateY(-4px)` + `--ev-shadow-h`
- [ ] PHP: CSRF + `htmlspecialchars()` überall
- [ ] Cross-Plugin: `PluginManager::isPluginActive()` Guard
- [ ] Assets via `filemtime()` mit Cache-Buster
