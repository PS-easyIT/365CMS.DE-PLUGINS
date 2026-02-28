# cms-speakers – Public Frontend Richtlinie

> Gilt für alle öffentlich zugänglichen Seiten des Speaker-Plugins:
> Archiv-/Übersichtsseite, Grid-Cards und Speaker-Detailseite (Single).
>
> Letzte Aktualisierung: 2026-02-27  
> Übergeordnete Richtlinie: `.github/instructions/plugin-cms-network-public.instructions.md`

---

## 1. Farbprofil – Violett / Lila

Das Speaker-Plugin nutzt eine edle Violett-Palette, die für Kreativität,
Charisma und Thought Leadership steht.

```css
:root {
    --sp-primary:        #8b5cf6;   /* Violet-500  – Hauptakzent, Links, Hover-Borders */
    --sp-primary-h:      #7c3aed;   /* Violet-600  – Hover-State */
    --sp-accent:         #a855f7;   /* Purple-500  – Sekundärakzent, Icons */
    --sp-secondary:      #6d28d9;   /* Violet-700  – Dunkelakzent, CTA */
    --sp-card-bg:        #faf5ff;   /* Violet-50   – Card-Hintergrund */
    --sp-card-top-bg:    #ede9fe;   /* Violet-100  – Kopfzone der Card */
    --sp-hdr-from:       #4c1d95;   /* Violet-900  – Archiv-Header Gradient Start */
    --sp-hdr-to:         #8b5cf6;   /* Violet-500  – Archiv-Header Gradient Ende */
    --sp-hdr-title:      #ffffff;   /* Weiß        – Header h1-Farbe auf dunklem BG */
    --sp-border:         #ddd6fe;   /* Violet-200  – Card-Rahmen (Ruhezustand) */
    --sp-text:           #1f2937;   /* Dunkelgrau  – Primärtext */
    --sp-text-m:         #6b7280;   /* Mittelgrau  – Position, Subtext */
    --sp-text-l:         #9ca3af;   /* Hellgrau    – Hinweistexte */
    --sp-radius:         16px;      /* Card-Eckradius */
    --sp-shadow:         0 4px 16px rgba(139,92,246,.08);
    --sp-shadow-lg:      0 12px 32px rgba(139,92,246,.16);
    --sp-card-height:    325px;     /* FEST – nicht ändern! */
}
```

**Rationale:**
- Violett/Lila signalisiert Kreativität, Ausdruck und Thought Leadership
- Weicher Violet-Hintergrund (`#faf5ff`) gibt der Card ein edles, gedämpftes Erscheinungsbild
- Dunkler Header-Gradient (Violet-900 → Violet-500) erzeugt eine starke, eindrucksvolle Bühne

---

## 2. Archiv-Seite (`archive-speaker.php`)

### 2.1 Datei-Struktur

```
templates/archive-speaker.php
assets/css/style.css              ← Archiv + Card-Styles
assets/js/script.js               ← Filter-Logik (Vanilla JS)
```

### 2.2 PHP-Variablen-Überschreibung

```php
$primary   = htmlspecialchars($settings['design_primary_color']      ?? '#8b5cf6');
$hdr_from  = htmlspecialchars($settings['archive_header_bg_from']    ?? '#4c1d95');
$hdr_to    = htmlspecialchars($settings['archive_header_bg_to']      ?? '#8b5cf6');
$hdr_title = htmlspecialchars($settings['archive_header_title_color'] ?? '#ffffff');
$card_bg   = htmlspecialchars($settings['design_card_bg']            ?? '#faf5ff');
$radius    = (int)($settings['design_border_radius'] ?? 16);

echo "<style>:root{
    --sp-primary:{$primary};
    --sp-hdr-from:{$hdr_from};
    --sp-hdr-to:{$hdr_to};
    --sp-hdr-title:{$hdr_title};
    --sp-card-bg:{$card_bg};
    --sp-radius:{$radius}px;
}</style>\n";
```

### 2.3 Seiten-Aufbau

```
.sp-archive
  ├── .sp-archive-header             ← Dunkler Violett-Gradient
  │     └── .sp-archive-header-inner
  │           ├── .sp-archive-icon     (🎤)
  │           ├── div > h1 + p         (Titel + Untertitel)
  │           └── .sp-archive-count    (Zähler-Chip)
  ├── .sp-filter-bar                 ← Such-/Filter-Leiste
  │     ├── .sp-filter-input         (🔍 Suche)
  │     ├── select (Thema / Topic)
  │     ├── select (Format)
  │     ├── select (Verfügbarkeit)
  │     └── [Reset-Button]
  ├── .sp-grid                       ← 3-spaltig → 2 → 1
  │     └── speaker-card.php × N
  └── .sp-pagination
```

---

## 3. Grid-Card (`speaker-card.php` + `.sp-card`)

### 3.1 HTML-Struktur

```html
<article class="sp-card">

    <!-- Badges: oben links (absolut) -->
    <div class="sp-card-badges">
        <span class="sp-badge sp-badge-featured">⭐ Featured</span>
        <span class="sp-badge sp-badge-verified">✓ Verifiziert</span>
    </div>
    <!-- Verfügbarkeits-Badge: oben rechts (absolut) -->
    <span class="sp-avail-badge sp-avail--available">Verfügbar</span>

    <!-- Kopfzone: Avatar + Textinfo -->
    <header class="sp-card-top">
        <div class="sp-card-avatar">
            <!-- Foto oder Initialen-Placeholder -->
        </div>
        <div class="sp-card-header-text">
            <h3 class="sp-card-name"><a href="{url}">{Vollname}</a></h3>
            <p class="sp-card-position">{Position}</p>
            <p class="sp-card-company">{Unternehmen}</p>
            <div class="sp-card-meta">
                <span class="sp-card-location">📍 {Stadt}</span>
            </div>
        </div>
    </header>

    <!-- Topics-Banner -->
    <div class="sp-card-topics-banner">
        <span class="sp-topics-label">THEMEN:</span>
        <span class="sp-topics-value">{Topic 1 · Topic 2}</span>
    </div>

    <!-- Body: Topic-Pills + Social -->
    <div class="sp-card-body">
        <div class="sp-card-pills">
            <span class="sp-topic-pill">{Topic}</span>
            <!-- max. 6 Pills -->
        </div>
        <div class="sp-card-social">
            <!-- LinkedIn · Xing · Twitter · GitHub (SVG-Icons) -->
        </div>
    </div>

    <!-- CTA (flush am Boden) -->
    <a href="{url}" class="sp-card-cta">
        Profil ansehen <span>›</span>
    </a>

</article>
```

### 3.2 Card-CSS (Pflicht-Regeln)

```css
/* Feste Höhe – UNVERÄNDERLICH */
.sp-card {
    height:     var(--sp-card-height, 325px);
    min-height: var(--sp-card-height, 325px);
    max-height: var(--sp-card-height, 325px);
    display:    flex;
    flex-direction: column;
    overflow:   hidden;
    position:   relative;
    background: var(--sp-card-bg, #faf5ff);
    border:     1px solid var(--sp-border, #ddd6fe);
    border-radius: var(--sp-radius, 16px);
    box-shadow: var(--sp-shadow);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Hover */
.sp-card:hover {
    transform:    translateY(-4px);
    box-shadow:   var(--sp-shadow-lg);
    border-color: var(--sp-primary);
}

/* Kopfzone */
.sp-card-top {
    background:  var(--sp-card-top-bg, #ede9fe);
    padding:     0.75rem 0.875rem;
    display:     flex;
    gap:         0.75rem;
    align-items: flex-start;
    flex-shrink: 0;
}

/* Avatar */
.sp-card-avatar img,
.sp-avatar-placeholder {
    width:         56px;
    height:        56px;
    border-radius: 50%;
    object-fit:    cover;
    flex-shrink:   0;
}
.sp-avatar-placeholder {
    background: linear-gradient(135deg, var(--sp-primary), var(--sp-accent));
    display:    flex;
    align-items: center;
    justify-content: center;
    font-size:  1.25rem;
    font-weight: 700;
    color:      #fff;
}

/* Topics-Banner */
.sp-card-topics-banner {
    padding:    0.3rem 0.875rem;
    font-size:  0.7rem;
    font-weight: 600;
    background: rgba(139,92,246,.06);
    flex-shrink: 0;
    white-space: nowrap;
    overflow:   hidden;
    text-overflow: ellipsis;
}
.sp-topics-label { color: var(--sp-text-m); margin-right: 0.25rem; }
.sp-topics-value { color: var(--sp-primary-h, #7c3aed); }

/* Topic-Pills */
.sp-topic-pill {
    display:       inline-block;
    padding:       2px 8px;
    background:    rgba(139,92,246,.10);
    color:         #5b21b6;
    border-radius: 6px;
    font-size:     0.72rem;
    font-weight:   500;
    white-space:   nowrap;
}

/* Social Icons */
.sp-card-social {
    display:     flex;
    gap:         0.5rem;
    padding:     0 0.875rem 0.5rem;
    flex-shrink: 0;
    margin-top:  auto;
}
.sp-card-social a {
    display:        flex;
    align-items:    center;
    justify-content: center;
    width:          28px;
    height:         28px;
    border-radius:  6px;
    background:     rgba(139,92,246,.08);
    color:          var(--sp-primary, #8b5cf6);
    text-decoration: none;
    transition:     background 0.15s ease;
}
.sp-card-social a:hover { background: rgba(139,92,246,.18); }
.sp-card-social a.social-disabled {
    opacity:        0.3;
    pointer-events: none;
}
.social-icon { width: 16px; height: 16px; }

/* CTA (flush) */
.sp-card-cta {
    display:         flex;
    align-items:     center;
    justify-content: center;
    padding:         0.65rem 1rem;
    background:      var(--sp-secondary, #6d28d9);
    color:           #fff;
    font-size:       0.875rem;
    font-weight:     600;
    text-decoration: none;
    margin-top:      auto;
    border-radius:   0 0 var(--sp-radius, 16px) var(--sp-radius, 16px);
    transition:      background 0.15s ease;
    gap:             0.3rem;
}
.sp-card-cta:hover { background: #5b21b6; }
```

### 3.3 Zonen-Höhenverteilung (325 px total)

| Zone | Klasse | Höhe (ca.) |
|------|--------|-----------|
| Badges | `.sp-card-badges` | 0–17 px |
| Kopfzone | `.sp-card-top` | ~74 px |
| Topics-Banner | `.sp-card-topics-banner` | ~26 px |
| Topic-Pills | `.sp-card-pills` | ~40 px |
| Social Icons | `.sp-card-social` | ~38 px |
| CTA | `.sp-card-cta` | ~40 px |

### 3.4 Badge-Farb-Matrix

| Badge | Hintergrund | Text |
|-------|------------|------|
| Featured | `linear-gradient(135deg, #fbbf24, #f59e0b)` | `#fff` |
| Verifiziert | `linear-gradient(135deg, #059669, #047857)` | `#fff` |
| Verfügbar | `#d1fae5` | `#065f46` |
| Begrenzt | `#fef3c7` | `#92400e` |
| Ausgebucht | `#fee2e2` | `#991b1b` |

### 3.5 Social-Icon-Set (Speaker)

Speaker haben ein umfangreicheres Social-Profil als Experts:

| Icon | Netzwerk | Ziel-URL-Feld |
|------|---------|--------------|
| LinkedIn | LinkedIn | `$s->linkedin` |
| 𝕏 | Twitter/X | `$s->twitter` |
| XING | Xing | `$s->xing` |
| GitHub | GitHub | `$s->github` |
| GitLab | GitLab | `$s->gitlab` |
| 🌐 | Website | `$s->website` |
| ✉️ | E-Mail | `$s->email` |

---

## 4. Single Site (`single-speaker.php`)

### 4.1 Header-Zone

```
.sp-single-header   min-height: 300px
  background: linear-gradient(135deg, #4c1d95, #8b5cf6)
              oder: Hintergrundbild mit rgba(76,29,149,.85) Overlay

  ├── .sp-sh-avatar     (120×120px, rund, weißer Ring 3px)
  ├── .sp-sh-text
  │     ├── h1          (Name – clamp(1.5rem,4vw,2.25rem), fw:800, color:#fff)
  │     ├── p.sp-sh-position  (Position @ Unternehmen, opacity:.85)
  │     └── .sp-sh-badges     (Verfügbarkeit · Honorar-Range · Reiseradius)
  └── .sp-sh-social     (LinkedIn, Xing, Twitter, Github, Web – Icon-Links, weiß)
```

### 4.2 2-Spalten-Layout

```css
.sp-single-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 2rem;
    padding: 2rem 1.5rem;
    max-width: 1140px;
    margin: 0 auto;
    align-items: start;
}
.sp-single-sidebar {
    position: sticky;
    top: 2rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}
@media (max-width: 768px) {
    .sp-single-layout { grid-template-columns: 1fr; }
}
```

### 4.3 Content-Sektionen (Main)

| Sektion | Icon | Inhalt |
|---------|------|--------|
| Über mich | 👤 | Bio-Text (WYSIWYG) |
| Themenspektrum | 🎯 | Topic-Pills (alle) + Beschreibungstext |
| Formate | 🎤 | Keynote / Workshop / Moderation / Webinar etc. |
| Referenzen & Kunden | 🏆 | Logos / Namen / Zitate |
| Skills & Technologie | ⚙️ | Skill-Pills aus der DB |

### 4.4 Sidebar-Cards

```html
<!-- Kontakt & Buchung -->
<div class="sp-sidebar-card sp-sidebar-card--cta">
    <h3 class="sp-sidebar-title">Anfrage stellen</h3>
    <a href="mailto:{email}" class="sp-btn-primary sp-btn-block">
        ✉️ Anfrage senden
    </a>
    <p class="sp-sidebar-hint">
        💶 Honorar: {fee_min}–{fee_max} EUR
    </p>
</div>

<!-- Unternehmen (cross-plugin: cms-companies) -->
<div class="sp-sidebar-card">
    <h3 class="sp-sidebar-title">Unternehmen</h3>
    <!-- Logo + Name + Link zur Company-Seite -->
</div>

<!-- Events (cross-plugin: cms-events) -->
<div class="sp-sidebar-card">
    <h3 class="sp-sidebar-title">Spricht auf</h3>
    <!-- Kompakte Event-Liste: Datum + Titel + Link -->
</div>
```

---

## 5. Filter-Felder (Archivseite)

| Feld | Typ | DB-Spalte / Quelle |
|------|-----|--------------------|
| Freitext | `<input type="search">` | `first_name`, `last_name`, `company` |
| Thema / Topic | `<select>` | `cms_speaker_topics` |
| Format | `<select>` | `speakers.formats` (JSON) |
| Verfügbarkeit | `<select>` | `speakers.availability` |
| Reiseradius | `<select>` | `speakers.travel_radius` |

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
| cms-companies | Sidebar: Unternehmen des Speakers | `PluginManager::isPluginActive('cms-companies')` |
| cms-events | Sidebar: Events auf denen der Speaker spricht | `PluginManager::isPluginActive('cms-events')` |
| cms-experts | Keine direkte Verknüpfung (getrennte Entitäten) | — |

---

## 8. Honorar-/Reiseradius-Anzeige

Feinfühlige Darstellung des Honorars:

```php
if ($fee_min && $fee_max) {
    $fee_str = number_format($fee_min, 0, ',', '.')
             . '–'
             . number_format($fee_max, 0, ',', '.') . ' EUR';
} elseif ($fee_min) {
    $fee_str = 'ab ' . number_format($fee_min, 0, ',', '.') . ' EUR';
} else {
    $fee_str = ''; // Kein Honorar anzeigen wenn nicht gepflegt
}
```

Reiseradius-Labels:
- `local` → Regional
- `national` → National (D-A-CH)
- `international` → International
- `online_only` → Online

---

## 9. SEO & Accessibility

- `<article>` als semantischer Wrapper der Card
- `<h3>` für Speaker-Name in Card
- Avatar-Bild: `alt` = Vollname des Speakers
- `aria-label="{Netzwerk} von {Name}"` auf Social-Icon-Links
- Alle Social-Links: `target="_blank" rel="noopener noreferrer"`
- `aria-hidden="true"` auf deaktivierte Social-Icons (`.social-disabled`)

---

## 10. Checkliste (Speaker Public Frontend)

- [ ] `--sp-card-height: 325px` als CSS-Variable, alle drei Höhen-Properties gesetzt
- [ ] `overflow: hidden` auf `.sp-card`
- [ ] Topic-Pills: `overflow` im Pill-Container auf `hidden` oder `auto`
- [ ] Social Icons: SVG, kein Icon-Font
- [ ] Deaktivierte Social-Links: `.social-disabled` mit `pointer-events: none`
- [ ] CTA flush am Kartenrand (kein Abstand zum Card-Bottom)
- [ ] Hover: `translateY(-4px)` + `--sp-shadow-lg`
- [ ] PHP: CSRF + `htmlspecialchars()` überall
- [ ] Slug via `CMS_Speakers_Database::generate_slug($speaker)`
- [ ] Cross-Plugin: `PluginManager::isPluginActive()` Guard
- [ ] Assets via `filemtime()` mit Cache-Buster
- [ ] `<article>` als Card-Wrapper, `<h3>` für Name
