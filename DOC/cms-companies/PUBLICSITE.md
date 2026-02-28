# cms-companies – Public Frontend Richtlinie

> Gilt für alle öffentlich zugänglichen Seiten des Company-Plugins:
> Archiv-/Übersichtsseite, Grid-Cards und Company-Detailseite (Single).
>
> Letzte Aktualisierung: 2026-02-27  
> Übergeordnete Richtlinie: `.github/instructions/plugin-cms-network-public.instructions.md`

---

## 1. Farbprofil – Cyan / Teal

Das Company-Plugin nutzt eine frische Cyan-Palette, die Verlässlichkeit,
Professionalität und Klarheit kommuniziert.

```css
:root {
    --co-primary:     #0891b2;   /* Cyan-600   – Hauptakzent, Hover-Borders, Links */
    --co-primary-d:   #0284c7;   /* Blue-600   – Hover-State Buttons */
    --co-primary-x:   #0c4a6e;   /* Dunkel     – Überschriften, starke Akzente */
    --co-accent:      #e0f2fe;   /* Cyan-100   – Subtile Hintergrundtöne */
    --co-card-bg:     #ffffff;   /* Weiß       – Card-Hintergrund (klar, professionell) */
    --co-hdr-from:    #e0f2fe;   /* Cyan-100   – Archiv-Header Gradient Start */
    --co-hdr-to:      #bae6fd;   /* Sky-200    – Archiv-Header Gradient Ende */
    --co-hdr-title:   #0c4a6e;   /* Dunkel     – Header h1-Farbe */
    --co-border:      #bae6fd;   /* Sky-200    – Card-Rahmen (Ruhezustand) */
    --co-text:        #1e293b;   /* Dunkelgrau – Primärtext */
    --co-text-m:      #475569;   /* Mittelgrau – Subtext, Pills */
    --co-text-l:      #94a3b8;   /* Hellgrau   – Hinweistexte */
    --co-radius:      12px;      /* Card-Eckradius */
    --co-shadow:      0 4px 16px rgba(8,145,178,.08);
    --co-shadow-h:    0 8px 28px rgba(8,145,178,.16);
    --co-card-height: 325px;     /* FEST – nicht ändern! */
}
```

**Rationale:**
- Cyan/Teal steht für Verlässlichkeit, Seriosität und Internationalität
- Weißer Card-Hintergrund unterstreicht die Neutralität und Professionalität
- Der helle Header-Gradient ist dezent und lenkt nicht von den Unternehmenslogos ab

---

## 2. Archiv-Seite (`archive-company.php`)

### 2.1 Datei-Struktur

```
templates/archive-company.php
assets/css/style.css             ← Archiv + Card-Styles
assets/js/script.js              ← Filter-Logik (Vanilla JS)
```

### 2.2 PHP-Variablen-Überschreibung

Am Beginn des Templates wird ein `<style>`-Block ausgegeben, der alle
Admin-konfigurierten Farben in CSS-Variablen übersetzt:

```php
$primary   = htmlspecialchars($settings['design_primary_color']      ?? '#0891b2');
$hdr_from  = htmlspecialchars($settings['archive_header_bg_from']    ?? '#e0f2fe');
$hdr_to    = htmlspecialchars($settings['archive_header_bg_to']      ?? '#bae6fd');
$hdr_title = htmlspecialchars($settings['archive_header_title_color'] ?? '#0c4a6e');

echo "<style>:root{
    --co-primary:{$primary};
    --co-hdr-from:{$hdr_from};
    --co-hdr-to:{$hdr_to};
    --co-hdr-title:{$hdr_title};
}</style>\n";
```

### 2.3 Seiten-Aufbau

```
.co-archive
  ├── .co-archive-header             ← Heller Cyan-Gradient-Header
  │     └── .co-archive-header-inner
  │           ├── .co-archive-header-icon  (🏢)
  │           ├── div > h1 + p             (Titel + Untertitel)
  │           └── .co-archive-count        (Zähler-Chip)
  ├── .co-filter-bar                 ← Such-/Filter-Leiste (weiße Card)
  │     ├── .co-filter-input         (🔍 Suche)
  │     ├── select (Branche)
  │     ├── select (Partnertyp)
  │     └── [Reset-Button]
  ├── .co-grid                       ← 3-spaltig → 2 → 1
  │     └── company-card.php × N
  └── .co-pagination
```

---

## 3. Grid-Card (`company-card.php` + `.co-card`)

### 3.1 HTML-Struktur

```html
<div class="co-card [co-card--sponsor|co-card--top|co-card--partner]"
     style="--co-tier-border:{tier-color}">

    <!-- Ribbon (oben rechts, absolut) -->
    <div class="co-card-ribbon co-card-ribbon--[sponsor|top|partner]"
         style="--co-ribbon-bg:{color}">★ Sponsor</div>

    <!-- Kopfzone: Avatar + Identität -->
    <div class="co-card-head">
        <div class="co-card-avatar [co-card-avatar--logo]"
             style="background:{gradient}">
            <!-- Logo <img> oder Initialen-Text -->
        </div>
        <div class="co-card-identity">
            <h3 class="co-card-name"><a href="{url}">{Firmenname}</a></h3>
            <span class="co-card-industry">{Branche}</span>
        </div>
    </div>

    <!-- Info-Pills -->
    <div class="co-card-pills">
        <span class="co-card-pill">📍 {Stadt}</span>
        <span class="co-card-pill">👥 {Mitarbeiteranzahl}</span>
        <span class="co-card-pill">📅 Seit {Jahr}</span>
    </div>

    <!-- Kurzbeschreibung -->
    <p class="co-card-excerpt">{Beschreibung max. 110 Zeichen}…</p>

    <!-- Footer (flush am Boden) -->
    <div class="co-card-footer">
        <a href="{url}" class="co-btn co-btn-primary co-btn-block">
            Details ansehen
        </a>
        <a href="{website}" target="_blank" rel="noopener noreferrer"
           class="co-btn co-btn-ghost">🌐</a>
    </div>

</div>
```

### 3.2 Card-CSS (Pflicht-Regeln)

```css
/* Feste Höhe – UNVERÄNDERLICH */
.co-card {
    height:     325px;
    min-height: 325px;
    max-height: 325px;
    display:    flex;
    flex-direction: column;
    overflow:   hidden;
    position:   relative;
    background: var(--co-card-bg, #ffffff);
    border:     1px solid var(--co-border, #bae6fd);
    border-radius: var(--co-radius, 12px);
    box-shadow: var(--co-shadow);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Tier-Rahmen (Sponsor / Top-Partner / Partner) */
.co-card--sponsor,
.co-card--top,
.co-card--partner {
    border-color: var(--co-tier-border, var(--co-border));
}

/* Hover */
.co-card:hover {
    transform:    translateY(-4px);
    box-shadow:   var(--co-shadow-h);
    border-color: var(--co-primary);
}

/* Ribbon */
.co-card-ribbon {
    position:    absolute;
    top:         0;
    right:       0;
    padding:     3px 10px 3px 14px;
    font-size:   0.72rem;
    font-weight: 700;
    color:       #fff;
    background:  var(--co-ribbon-bg, #0891b2);
    border-radius: 0 var(--co-radius, 12px) 0 10px;
    z-index:     2;
    white-space: nowrap;
}

/* Kopfzone */
.co-card-head {
    padding:     0.875rem;
    display:     flex;
    gap:         0.75rem;
    align-items: center;
    flex-shrink: 0;
}

/* Avatar */
.co-card-avatar {
    width:         56px;
    height:        56px;
    border-radius: 12px;
    object-fit:    cover;
    flex-shrink:   0;
    display:       flex;
    align-items:   center;
    justify-content: center;
    font-size:     1.1rem;
    font-weight:   700;
    color:         #fff;
    overflow:      hidden;
}
.co-card-avatar--logo img {
    width:      100%;
    height:     100%;
    object-fit: contain;
    padding:    4px;
}

/* Info-Pills */
.co-card-pills {
    display:   flex;
    flex-wrap: wrap;
    gap:       4px;
    padding:   0 0.875rem 0.5rem;
    flex-shrink: 0;
}
.co-card-pill {
    display:       inline-flex;
    align-items:   center;
    gap:           3px;
    padding:       2px 8px;
    background:    rgba(8,145,178,.07);
    color:         var(--co-text-m, #475569);
    border-radius: 6px;
    font-size:     0.75rem;
    font-weight:   500;
    white-space:   nowrap;
}

/* Excerpt */
.co-card-excerpt {
    padding:       0 0.875rem;
    font-size:     0.82rem;
    color:         var(--co-text-m, #475569);
    margin:        0;
    flex-grow:     1;
    overflow:      hidden;
    display:       -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    line-height:   1.5;
}

/* Footer */
.co-card-footer {
    display:    flex;
    gap:        8px;
    padding:    0.75rem 0.875rem;
    margin-top: auto;
    border-top: 1px solid var(--co-border, #bae6fd);
    flex-shrink: 0;
}
.co-btn-block { flex: 1; justify-content: center; }
```

### 3.3 Zonen-Höhenverteilung (325 px total)

| Zone | Klasse | Höhe (ca.) |
|------|--------|-----------|
| Ribbon | `.co-card-ribbon` | 0–22 px (abs.) |
| Kopfzone | `.co-card-head` | ~76 px |
| Info-Pills | `.co-card-pills` | ~32 px |
| Excerpt | `.co-card-excerpt` | flex-grow: 1 (~105 px) |
| Footer | `.co-card-footer` | ~50 px |

### 3.4 Tier-Ribbon Farbmatrix

| Tier | Standard-Farbe | CSS-Variable |
|------|---------------|--------------|
| Sponsor | `#7c3aed` (Violet-700) | `--co-ribbon-bg` per inline style |
| Top-Partner | `#d97706` (Amber-600) | `--co-ribbon-bg` per inline style |
| Partner | `#9ca3af` (Gray-400) | `--co-ribbon-bg` per inline style |

Farben sind im Admin konfigurierbar und werden via `style="--co-ribbon-bg:{color}"` übergeben.

### 3.5 Avatar-Gradient-Palette (deterministisch)

Der Avatar-Gradient wird deterministisch aus dem Firmennamen per `crc32()` berechnet:

```php
$palettes = [
    ['#0891b2','#0284c7'],  // Cyan/Blue
    ['#7c3aed','#a855f7'],  // Violet/Purple
    ['#059669','#34d399'],  // Emerald
    ['#d97706','#f59e0b'],  // Amber
    ['#e11d48','#fb7185'],  // Rose
    ['#1d4ed8','#3b82f6'],  // Blue
];
$cp = $palettes[abs(crc32($company->name)) % count($palettes)];
```

---

## 4. Single Site (`single-company.php`)

### 4.1 Header-Zone

```
.co-single-header   min-height: 300px
  background: linear-gradient(135deg, #e0f2fe, #bae6fd)
              oder: Keyvisual-Bild + rgba(14,116,144,.8) Overlay

  ├── .co-sh-logo         (max 120×80px, weißer Hintergrund, padding, runder Schatten)
  ├── .co-sh-text
  │     ├── h1            (Firmenname – clamp(1.5rem,4vw,2.25rem), fw:800)
  │     ├── .co-sh-industry  (Branche-Badge)
  │     └── .co-sh-meta   (📍 Stadt · 👥 Mitarbeiter · 📅 Gründungsjahr)
  └── .co-sh-tier-badge   (Sponsor / Top-Partner / Partner Badge)
```

### 4.2 2-Spalten-Layout

```css
.co-single-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 2rem;
    padding: 2rem 1.5rem;
    max-width: 1140px;
    margin: 0 auto;
    align-items: start;
}
@media (max-width: 768px) {
    .co-single-layout { grid-template-columns: 1fr; }
}
```

### 4.3 Content-Sektionen (Main)

| Sektion | Icon | Inhalt |
|---------|------|--------|
| Unternehmensprofil | 🏢 | Volltext-Beschreibung (WYSIWYG) |
| Leistungen & Produkte | ⚙️ | Tags / Pills + Freitext |
| Philosophie & Werte | 💡 | Freitext |
| Team-Highlights | 👥 | Optionale Expert-Verlinkungen |

### 4.4 Sidebar-Cards

```html
<!-- Kontakt & Website -->
<div class="co-sidebar-card">
    <h3 class="co-sidebar-title">Kontakt</h3>
    <a href="{website}" class="co-btn-primary co-btn-block">🌐 Website besuchen</a>
    <!-- E-Mail, Telefon, Adresse -->
</div>

<!-- Experten (cross-plugin: cms-experts) -->
<div class="co-sidebar-card">
    <h3 class="co-sidebar-title">Unsere Experten</h3>
    <!-- Kompakte Expert-Cards: Avatar + Name + Link -->
</div>

<!-- Events (cross-plugin: cms-events) -->
<div class="co-sidebar-card">
    <h3 class="co-sidebar-title">Gesponserte Events</h3>
    <!-- Event-Liste: Datum + Titel + Link -->
</div>
```

---

## 5. Filter-Felder (Archivseite)

| Feld | Typ | DB-Spalte / Quelle |
|------|-----|--------------------|
| Freitext | `<input type="search">` | `companies.name`, `companies.description` |
| Branche | `<select>` | `companies.industry` (DISTINCT) |
| Partnertyp | `<select>` | `is_sponsor`, `is_top_partner`, `is_partner` |
| Standort | `<select>` | `companies.location_city` (DISTINCT) |

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
| cms-experts | Single Sidebar: Zugehörige Experten | `PluginManager::isPluginActive('cms-experts')` |
| cms-events | Single Sidebar: Gesponserte Events | `PluginManager::isPluginActive('cms-events')` |
| cms-speakers | Optional: Sprecher mit Company-Bezug | `PluginManager::isPluginActive('cms-speakers')` |

---

## 8. SEO & Accessibility

- `<div class="co-card">` mit `role="article"` oder semantischer Wrapper
- `<h3>` für Firmenname in Card (kein `<h2>` im Grid-Kontext)
- Logo-Bild: `alt` = Firmenname
- `aria-label="Website von {Firmenname} besuchen"` auf externen Links
- Ribbon-Badges: Kontrastverhältnis ≥ 4.5:1 prüfen (weiß auf Farbe)

---

## 9. Checkliste (Company Public Frontend)

- [ ] `--co-card-height: 325px` in CSS gesetzt, `height/min-height/max-height` alle drei
- [ ] `overflow: hidden` auf `.co-card`
- [ ] Ribbon via CSS-Variable `--co-ribbon-bg`, Farbe aus Admin-Settings
- [ ] Avatar-Gradient deterministisch aus `crc32(name)` generiert
- [ ] Excerpt per `-webkit-line-clamp: 3` begrenzt
- [ ] Hover: `translateY(-4px)` + `--co-shadow-h`
- [ ] Footer flush am Kartenrand (`margin-top: auto`)
- [ ] PHP: CSRF + `htmlspecialchars()` überall
- [ ] Cross-Plugin: `PluginManager::isPluginActive()` Guard
- [ ] Assets via `filemtime()` mit Cache-Buster
