# cms-experts – Public Frontend Richtlinie

> Gilt für alle öffentlich zugänglichen Seiten des Expert-Plugins:
> Archiv-/Übersichtsseite, Grid-Cards und Expert-Detailseite (Single).
>
> Letzte Aktualisierung: 2026-02-27  
> Übergeordnete Richtlinie: `.github/instructions/plugin-cms-network-public.instructions.md`

---

## 1. Farbprofil – Amber / Orange

Das Expert-Plugin nutzt eine warme Amber-Palette, die Kompetenz und Vertrauen
signalisiert ohne aufdringlich zu wirken.

```css
:root {
    --expert-primary:        #f59e0b;   /* Amber-400  – Hauptakzent, Links, Hover-Borders */
    --expert-primary-hover:  #d97706;   /* Amber-600  – Hover-State */
    --expert-accent:         #ea580c;   /* Orange-600 – Sekundärakzent, Icons */
    --expert-cta-color:      #c2410c;   /* Orange-700 – CTA-Button-Hintergrund */
    --expert-card-bg:        #fffdf4;   /* Warm-Weiß  – Card-Hintergrund */
    --expert-card-top-bg:    #fef3c7;   /* Amber-100  – Kopfzone der Card */
    --expert-hdr-from:       #f5ecd5;   /* Sanft Amber – Archiv-Header Gradient Start */
    --expert-hdr-to:         #ebe0c8;   /* Warm Beige  – Archiv-Header Gradient Ende */
    --expert-hdr-title:      #7c4700;   /* Dunkel-Amber – Header h1-Farbe */
    --expert-border:         #fde68a;   /* Amber-200  – Card-Rahmen (Ruhezustand) */
    --expert-text:           #1f2937;   /* Dunkelgrau  – Primärtext */
    --expert-text-light:     #6b7280;   /* Mittelgrau  – Position, Ort, Subtext */
    --expert-radius:         16px;      /* Card-Eckradius */
    --expert-shadow:         0 4px 16px rgba(245,158,11,.10);
    --expert-shadow-lg:      0 12px 32px rgba(245,158,11,.18);
    --expert-card-height:    325px;     /* FEST – nicht ändern! */
}
```

**Rationale:**
- Amber/Orange steht für Energie, Erfahrung und Kompetenz
- Warme Hintergründe (`#fffdf4`, `#fef3c7`) erzeugen eine einladende Atmosphäre
- Dunkle CTA-Farbe (`#c2410c`) sorgt für ausreichenden Kontrast auf hellem Card-Hintergrund

---

## 2. Archiv-Seite (`archive-expert.php`)

### 2.1 Datei-Struktur

```
templates/archive-expert.php
assets/css/style.css           ← Archiv + Card-Styles
assets/js/script.js            ← Filter-Logik (reines Vanilla JS)
```

### 2.2 PHP-Variablen-Überschreibung

Am Beginn des Templates wird ein `<style>`-Block ausgegeben, der alle
Admin-konfigurierten Farben in CSS-Variablen übersetzt:

```php
$primary    = htmlspecialchars($settings['design_primary_color']  ?? '#f59e0b');
$hdr_from   = htmlspecialchars($settings['archive_header_bg_from'] ?? '#f5ecd5');
$hdr_to     = htmlspecialchars($settings['archive_header_bg_to']   ?? '#ebe0c8');
$hdr_title  = htmlspecialchars($settings['archive_header_title_color'] ?? '#7c4700');
$card_bg    = htmlspecialchars($settings['design_card_bg']         ?? '#fffdf4');
$cta_color  = htmlspecialchars($settings['design_cta_color']       ?? '#c2410c');
$radius     = (int)($settings['design_border_radius'] ?? 16);

echo "<style>:root{
    --expert-primary:{$primary};
    --expert-hdr-from:{$hdr_from};
    --expert-hdr-to:{$hdr_to};
    --expert-hdr-title:{$hdr_title};
    --expert-card-bg:{$card_bg};
    --expert-cta-color:{$cta_color};
    --expert-radius:{$radius}px;
}</style>\n";
```

### 2.3 Seiten-Aufbau

```
.expert-archive
  ├── .expert-archive-header          ← Gradient-Header
  │     ├── .expert-archive-header-inner
  │     │     ├── .expert-archive-icon   (🧑‍💼)
  │     │     ├── div > h1 + p           (Titel + Untertitel)
  │     │     └── .expert-archive-count  (Zähler-Chip)
  ├── .expert-filter-bar              ← Such-/Filter-Leiste
  │     ├── .expert-filter-input      (🔍 Suche)
  │     ├── select (Spezialisierung)
  │     ├── select (Verfügbarkeit)
  │     └── [Reset-Button]
  ├── .expert-grid                    ← 3-spaltig → 2 → 1
  │     └── expert-card.php × N
  └── .expert-pagination
```

### 2.4 Header-CSS

```css
.expert-archive-header {
    background: linear-gradient(135deg,
        var(--expert-hdr-from, #f5ecd5) 0%,
        var(--expert-hdr-to,   #ebe0c8) 100%);
    padding: 2.5rem 2rem;
    border-radius: 0;
}

.expert-archive-header h1 {
    font-size:   1.875rem;
    font-weight: 800;
    color:       var(--expert-hdr-title, #7c4700);
    margin:      0 0 0.3rem;
}

.expert-archive-count {
    margin-left:     auto;
    background:      rgba(255,255,255,.2);
    backdrop-filter: blur(4px);
    border-radius:   10px;
    padding:         0.6rem 1.1rem;
    color:           var(--expert-hdr-title, #7c4700);
    text-align:      center;
}
```

---

## 3. Grid-Card (`expert-card.php` + `.expert-card--overview`)

### 3.1 HTML-Struktur

```html
<article class="expert-card expert-card--overview">

    <!-- Badges: absolute, top: 0 -->
    <div class="expert-card-status-badges">
        <!-- Partner-Badge (links) -->
        <span class="expert-card-corner-badge badge-partner"><span>Partner</span></span>
        <!-- Zertifiziert-Badge (links) -->
        <span class="expert-card-corner-badge badge-certified"><span>Zertifiziert</span></span>
    </div>
    <!-- Verfügbarkeits-Badge (rechts) -->
    <span class="expert-card-corner-badge expert-card-availability-badge expert-card-availability--available">
        <span>Verfügbar</span>
    </span>

    <!-- Kopfzone: Avatar + Textinfo -->
    <header class="expert-card-top">
        <div class="expert-card-avatar">
            <!-- Foto oder Initialen-Placeholder -->
        </div>
        <div class="expert-card-header-text">
            <h3 class="expert-card-name"><a href="{url}">{Name}</a></h3>
            <div class="expert-card-title-row">
                <p class="expert-card-title">{Position}</p>
                <!-- optional: Erfahrungs-Badge -->
            </div>
            <div class="expert-card-info-stack">
                <div class="expert-card-location-row">
                    <span class="expert-card-location">📍 {Stadt}</span>
                    <span class="expert-card-company">{Unternehmen}</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Fachrichtungs-Banner -->
    <div class="expert-card-expertise-section">
        <span class="expertise-label">FACHRICHTUNG:</span>
        <span class="expertise-value">{Spezialisierung 1 · Spezialisierung 2}</span>
    </div>

    <!-- Body: Skills + Social -->
    <div class="expert-card-body">
        <div class="expert-card-competencies">
            <div class="expert-card-skills">
                <span class="skill-pill">{Skill}</span>
                <!-- max. 8 Pills -->
            </div>
        </div>
        <div class="expert-card-social">
            <!-- LinkedIn · Website · E-Mail (SVG-Icons) -->
        </div>
    </div>

    <!-- CTA (flush am Boden) -->
    <a href="{url}" class="expert-card-cta">
        Profil ansehen <span class="cta-arrow">›</span>
    </a>

</article>
```

### 3.2 Card-CSS (Pflicht-Regeln)

```css
/* Feste Höhe – UNVERÄNDERLICH */
.expert-card--overview {
    height:     325px;
    min-height: 325px;
    max-height: 325px;
    display:    flex;
    flex-direction: column;
    overflow:   hidden;
    position:   relative;
    background: var(--expert-card-bg, #fffdf4);
    border:     1px solid var(--expert-border, #fde68a);
    border-radius: var(--expert-radius, 16px);
    box-shadow: var(--expert-shadow);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Hover */
.expert-card--overview:hover {
    transform:    translateY(-4px);
    box-shadow:   var(--expert-shadow-lg);
    border-color: var(--expert-primary);
}

/* Kopfzone */
.expert-card-top {
    background: var(--expert-card-top-bg, #fef3c7);
    padding:    0.75rem 0.875rem;
    display:    flex;
    gap:        0.75rem;
    align-items: flex-start;
    flex-shrink: 0;
}

/* Avatar */
.expert-card-avatar img,
.expert-avatar-placeholder {
    width:         56px;
    height:        56px;
    border-radius: 50%;
    object-fit:    cover;
    flex-shrink:   0;
}
.expert-avatar-placeholder {
    background: linear-gradient(135deg, var(--expert-primary), var(--expert-accent));
    display:    flex;
    align-items: center;
    justify-content: center;
    font-size:  1.25rem;
    font-weight: 700;
    color:      #fff;
}

/* Fachrichtungs-Banner */
.expert-card-expertise-section {
    padding:    0.3rem 0.875rem;
    font-size:  0.7rem;
    font-weight: 600;
    background: rgba(245,158,11,.07);
    flex-shrink: 0;
    white-space: nowrap;
    overflow:   hidden;
    text-overflow: ellipsis;
}
.expertise-label { color: var(--expert-text-light); margin-right: 0.25rem; }
.expertise-value { color: var(--expert-primary-hover, #d97706); }

/* Skill-Pills */
.skill-pill {
    display:       inline-block;
    padding:       2px 8px;
    background:    rgba(245,158,11,.10);
    color:         #92400e;
    border-radius: 6px;
    font-size:     0.72rem;
    font-weight:   500;
    white-space:   nowrap;
}

/* CTA (flush) */
.expert-card-cta {
    display:         flex;
    align-items:     center;
    justify-content: center;
    padding:         0.65rem 1rem;
    background:      var(--expert-cta-color, #c2410c);
    color:           #fff;
    font-size:       0.875rem;
    font-weight:     600;
    text-decoration: none;
    margin-top:      auto;
    border-radius:   0 0 var(--expert-radius, 16px) var(--expert-radius, 16px);
    transition:      background 0.15s ease;
}
.expert-card-cta:hover { background: #9a3412; }
```

### 3.3 Zonen-Höhenverteilung (325 px total)

| Zone | Klasse | Höhe (ca.) |
|------|--------|-----------|
| Badges | `.expert-card-status-badges` | 0–17 px |
| Kopfzone | `.expert-card-top` | ~72 px |
| Fachrichtungs-Banner | `.expert-card-expertise-section` | ~26 px |
| Body (Skills + Social) | `.expert-card-body` | flex-grow: 1 (~130 px) |
| CTA | `.expert-card-cta` | ~40 px |

### 3.4 Badge-Farb-Matrix

| Badge | Hintergrund | Text |
|-------|------------|------|
| Partner | `#d97706` (Amber-600) | `#fff` |
| Zertifiziert | `#059669` (Emerald-600) | `#fff` |
| Verfügbar | `#d1fae5` | `#065f46` |
| Begrenzt | `#fef3c7` | `#92400e` |
| Ausgebucht | `#fee2e2` | `#991b1b` |

---

## 4. Single Site (`single-expert.php`)

### 4.1 Header-Zone

```
.expert-single-header   min-height: 300px
  background: linear-gradient(135deg, #fef3c7, #fde68a)
              oder: Hintergrundbild mit rgba(254,243,199,.85) Overlay

  ├── .expert-sh-avatar     (120×120px, rund, weißer Ring)
  ├── .expert-sh-text
  │     ├── h1              (Name – clamp(1.5rem,4vw,2.25rem), fw:800)
  │     ├── p.expert-sh-position  (Position @ Unternehmen)
  │     └── .expert-sh-badges     (Verfügbarkeit · Standort · Erfahrung)
  └── .expert-sh-social     (LinkedIn, Website, Mail – Icon-Links)
```

### 4.2 2-Spalten-Layout

```css
.expert-single-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 2rem;
    padding: 2rem 1.5rem;
    max-width: 1140px;
    margin: 0 auto;
    align-items: start;
}
/* Sidebar sticky */
.expert-single-sidebar {
    position: sticky;
    top: 2rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}
@media (max-width: 768px) {
    .expert-single-layout {
        grid-template-columns: 1fr;
    }
}
```

### 4.3 Content-Sektionen (Main)

| Sektion | Icon | Inhalt |
|---------|------|--------|
| Über mich / Vita | 👤 | Bio-Text (WYSIWYG), full-width |
| Expertise | 🧠 | Spezialisierungen als Tag-Liste + vertiefende Pills |
| Skills & Technologien | ⚙️ | Skill-Pills aus der DB (alle anzeigen) |
| Formate & Referenzen | 🎤 | Formate (Keynote, Workshop …), Referenzprojekte |

### 4.4 Sidebar-Cards

```html
<!-- Kontakt -->
<div class="expert-sidebar-card">
    <h3 class="expert-sidebar-title">Kontakt</h3>
    <a href="mailto:{email}" class="expert-btn-primary expert-btn-block">
        ✉️ Nachricht senden
    </a>
    <!-- Telefon, LinkedIn, Website -->
</div>

<!-- Unternehmen (cross-plugin: cms-companies) -->
<div class="expert-sidebar-card">
    <h3 class="expert-sidebar-title">Unternehmen</h3>
    <!-- Logo + Name + Link zur Company-Seite -->
</div>

<!-- Events (cross-plugin: cms-events) -->
<div class="expert-sidebar-card">
    <h3 class="expert-sidebar-title">Spricht auf</h3>
    <!-- Kompakte Event-Liste: Datum + Titel + Link -->
</div>
```

---

## 5. Filter-Felder (Archivseite)

| Feld | Typ | DB-Spalte / Quelle |
|------|-----|--------------------|
| Freitext | `<input type="search">` | `first_name`, `last_name`, `company_name` |
| Spezialisierung | `<select>` | `cms_expert_specializations` |
| Verfügbarkeit | `<select>` | `experts.availability` |
| Standort | `<select>` | `experts.location_city` |

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
| cms-companies | Sidebar: Zugehöriges Unternehmen | `PluginManager::isPluginActive('cms-companies')` |
| cms-events | Sidebar: Speaker auf Events | `PluginManager::isPluginActive('cms-events')` |
| cms-speakers | — (Trennung: Expert ≠ Speaker) | — |

---

## 8. SEO & Accessibility

- `<article>` als semantischer Wrapper der Card
- `<h3>` für Card-Name (nicht `<h2>`, da Grid-Kontext)
- Alle Bilder: `alt`-Attribut = Vollname des Experts
- Links mit `rel="noopener"` bei `target="_blank"`
- Verfügbarkeits-Badges: Kontrastverhältnis ≥ 4.5:1 sicherstellen
- `aria-label` auf reinen Icon-Links (Social Icons ohne Text)

---

## 9. Checkliste (Expert Public Frontend)

- [ ] `--expert-card-height: 325px` in CSS + PHP unveränderlich
- [ ] `overflow: hidden` auf `.expert-card--overview`
- [ ] Skills per `-webkit-line-clamp: 2` auf 2 Zeilen begrenzt (Pill-Bereich auf `overflow: auto`)
- [ ] Hover: `translateY(-4px)` + `--expert-shadow-lg`
- [ ] CTA flush am Kartenrand (kein Abstand unten)
- [ ] PHP: CSRF + `htmlspecialchars()` überall
- [ ] Cross-Plugin: `PluginManager::isPluginActive()` Guard
- [ ] Assets via `filemtime()` mit Cache-Buster
- [ ] `<article>` als Card-Wrapper, `<h3>` für Name
