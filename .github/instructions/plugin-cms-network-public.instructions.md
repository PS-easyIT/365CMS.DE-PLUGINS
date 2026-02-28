# CMS Network Public – Design & Frontend-Richtlinien

> Gilt für **alle öffentlichen Frontend-Seiten** (Archiv, Grid-Cards, Single Sites) der vier
> Network-Plugins: **cms-experts**, **cms-companies**, **cms-events**, **cms-speakers**.
>
> Letzte Aktualisierung: 2026-02-27

---

## 0. Designphilosophie

Das öffentliche Frontend folgt dem Grundsatz: **professionell und eigenständig, nicht
von der Stange**. Konkret bedeutet das:

- Dezente, harmonische Farbtöne – kein Overengineering, keine grellen Akzente
- Weiche Schatten (`box-shadow`) statt harter Rahmen
- Konsistentes Spacing-System (4-px-Raster) über alle vier Plugins
- Hover-Übergänge mit `cubic-bezier(0.4,0,0.2,1)` – weich und nicht abrupt
- Kein generischer Grid-Baukastencharakter – Cards haben Tiefe und Struktur
- Barrierefreiheit: Kontrastverhältnis > 4.5:1 für Lesetext
- Keine externen Icon-Fonts; SVG-Icons oder Unicode-Emojis

---

## 1. Farbsystem und CSS-Variablen

Jedes Plugin hat ein eigenes, klar definiertes Farbschema. Die Farben sind bewusst
gedämpft und harmonieren untereinander, falls mehrere Plugins auf einer Seite erscheinen.

### 1.1 Expert – Amber/Orange-Palette

```css
:root {
    --expert-primary:       #f59e0b;   /* Amber-400        – Hauptakzent    */
    --expert-primary-hover: #d97706;   /* Amber-600        – Hover          */
    --expert-accent:        #ea580c;   /* Orange-600       – Sekundärakzent */
    --expert-cta-color:     #c2410c;   /* Orange-700       – CTA-Button     */
    --expert-card-bg:       #fffdf4;   /* Warm-Weiß        – Card-BG        */
    --expert-card-top-bg:   #fef3c7;   /* Amber-100        – Header-Zone    */
    --expert-hdr-from:      #f5ecd5;   /* Sanftes Amber    – Archiv-Header  */
    --expert-hdr-to:        #ebe0c8;   /* Warmes Beige     – Archiv-Header  */
    --expert-hdr-title:     #7c4700;   /* Dunkel-Amber     – Überschrift    */
    --expert-border:        #fde68a;   /* Amber-200        – Card-Border    */
    --expert-text:          #1f2937;
    --expert-text-light:    #6b7280;
    --expert-radius:        16px;
    --expert-shadow:        0 4px 16px rgba(245,158,11,.10);
    --expert-shadow-lg:     0 12px 32px rgba(245,158,11,.18);
    --expert-card-height:   300px;     /* FEST – nie abweichen!             */
}
```

### 1.2 Company – Cyan/Teal-Palette

```css
:root {
    --co-primary:     #0891b2;   /* Cyan-600  – Hauptakzent        */
    --co-primary-d:   #0284c7;   /* Blue-600  – Hover              */
    --co-primary-x:   #0c4a6e;   /* Dunkel    – Überschriften      */
    --co-accent:      #e0f2fe;   /* Cyan-100  – Hintergrund-Töne   */
    --co-card-bg:     #ffffff;
    --co-hdr-from:    #e0f2fe;   /* Cyan-100  – Archiv-Header      */
    --co-hdr-to:      #bae6fd;   /* Sky-200   – Archiv-Header      */
    --co-hdr-title:   #0c4a6e;
    --co-border:      #bae6fd;   /* Sky-200   – Card-Border        */
    --co-text:        #1e293b;
    --co-text-m:      #475569;
    --co-radius:      12px;
    --co-shadow:      0 4px 16px rgba(8,145,178,.08);
    --co-shadow-h:    0 8px 28px rgba(8,145,178,.16);
    --co-card-height: 300px;     /* FEST – gleich wie Expert/Speaker */
}
```

### 1.3 Events – Blau-Palette

```css
:root {
    --ev-primary:     #3b82f6;   /* Blue-500  – Hauptakzent        */
    --ev-primary-h:   #1d4ed8;   /* Blue-700  – Hover              */
    --ev-accent:      #60a5fa;   /* Blue-400  – Sekundärakzent     */
    --ev-light:       #eff6ff;   /* Blue-50   – Hintergrund        */
    --ev-card-bg:     #f0f7ff;   /* Hellblau  – Card-BG            */
    --ev-hdr-from:    #1d4ed8;   /* Blue-700  – Archiv-Header      */
    --ev-hdr-to:      #3b82f6;   /* Blue-500  – Archiv-Header      */
    --ev-hdr-title:   #ffffff;
    --ev-border:      #bfdbfe;   /* Blue-200  – Card-Border        */
    --ev-cta:         #1e40af;   /* Blue-800  – CTA                */
    --ev-text:        #1e293b;
    --ev-text-m:      #475569;
    --ev-radius:      12px;
    --ev-shadow:      0 4px 16px rgba(59,130,246,.08);
    --ev-shadow-h:    0 12px 32px rgba(59,130,246,.16);
    --ev-card-height: 300px;     /* FEST – gleich wie alle anderen */
}
```

### 1.4 Speaker – Violett/Lila-Palette

```css
:root {
    --sp-primary:     #8b5cf6;   /* Violet-500 – Hauptakzent       */
    --sp-primary-h:   #7c3aed;   /* Violet-600 – Hover             */
    --sp-accent:      #a855f7;   /* Purple-500 – Sekundärakzent    */
    --sp-secondary:   #6d28d9;   /* Violet-700 – Dunkelakzent      */
    --sp-card-bg:     #faf5ff;   /* Violet-50  – Card-BG           */
    --sp-card-top-bg: #ede9fe;   /* Violet-100 – Header-Zone       */
    --sp-hdr-from:    #4c1d95;   /* Violet-900 – Archiv-Header     */
    --sp-hdr-to:      #8b5cf6;   /* Violet-500 – Archiv-Header     */
    --sp-hdr-title:   #ffffff;
    --sp-border:      #ddd6fe;   /* Violet-200 – Card-Border       */
    --sp-text:        #1f2937;
    --sp-text-m:      #6b7280;
    --sp-radius:      16px;
    --sp-shadow:      0 4px 16px rgba(139,92,246,.08);
    --sp-shadow-lg:   0 12px 32px rgba(139,92,246,.16);
    --sp-card-height: 300px;     /* FEST – gleich wie alle anderen */
}
```

### 1.5 Gemeinsame Neutral-Palette (alle Plugins)

```css
/* Einheitliche Werte für Text, Abstände und Schatten */
--network-text-base:   #1e293b;
--network-text-medium: #475569;
--network-text-subtle: #94a3b8;
--network-bg-page:     #f8fafc;   /* Seiten-Hintergrund */
--network-border:      #e2e8f0;
--network-radius-sm:   8px;
--network-radius-md:   12px;
--network-radius-lg:   16px;
--network-ease:        cubic-bezier(0.4, 0, 0.2, 1);
```

---

## 2. Card-Höhe – Bindende Vorgaben

**Card-Höhen sind im Archiv-Grid absolut bindend und dürfen nicht durch PHP überschrieben werden.**
- **expert & speaker:** Exakt 300px Höhe. Excerpts auf 2–3 Zeilen begrenzt.
- **company & event:** Exakt 300px Höhe. Excerpts auf 1–2 Zeilen begrenzt.

```css
/* Höhe für alle Cards erzwingen: */
height:     [WERT]px;
min-height: [WERT]px;
max-height: [WERT]px;
```

Der Card-Inhalt wird immer via `flex-direction: column` + `overflow: hidden` eingefasst, überlanger Text via `-webkit-line-clamp` abgeschnitten.

---

## 3. Archivseiten-Aufbau

Alle Archivseiten folgen diesem einheitlichen Aufbau:

```
┌─────────────────────────────────────────────────────┐
│  .{prefix}-archive-header   (Gradient, 300px+)      │
│    Icon  ·  h1-Titel  ·  Untertitel  ·  Zähler      │
├─────────────────────────────────────────────────────┤
│  .{prefix}-filter-bar  (weiße Card, Sticky möglich) │
│    🔍 Textsuche  ·  Select-Filter  ·  Reset-Btn     │
├─────────────────────────────────────────────────────┤
│  .{prefix}-grid                                     │
│    3-spaltig (≥1025px) → 2-spaltig → 1-spaltig      │
│    gap: 1.25rem · padding: 0 1.5rem                 │
├─────────────────────────────────────────────────────┤
│  .{prefix}-pagination  (zentriert, gap)             │
└─────────────────────────────────────────────────────┘
```

### 3.1 Archiv-Header

- Höhe: `min-height: 160px` (darf sich dehnen)
- Hintergrund: `linear-gradient(135deg, var(--{p}-hdr-from) 0%, var(--{p}-hdr-to) 100%)`
- `border-radius: 0` (full-bleed, kein Radius oben)
- `padding: 2.5rem 2rem`
- Innenlayout: Flexbox, `align-items: center`, `gap: 1.25rem`, `flex-wrap: wrap`
- Zähler-Chip: `background: rgba(255,255,255,.2)`, `backdrop-filter: blur(4px)`, `border-radius: 10px`
- Titelfarbe: aus CSS-Variable `--{p}-hdr-title` (konfigurierbar per Admin)

### 3.2 Filter-Bar

- `background: #ffffff`, `border: 1px solid var(--co-border)`, `border-radius: 12px`
- `padding: 10px`, `gap: 10px`, `margin: 1.5rem`
- Suchfeld: `flex-grow: 1`, `min-width: 150px`, Icon absolut links (`padding-left: 34px`)
- Selects: gleiche Höhe wie Suchfeld (`height: 38px`)
- Focus-Ring: `box-shadow: 0 0 0 3px rgba({plugin-color},.15)`
- Reset-Button: `btn-ghost`-Style, nur sichtbar wenn Filter aktiv

### 3.3 Grid-Konfiguration

```css
.{prefix}-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
    padding: 0 1.5rem;
}
@media (max-width: 1024px) { grid-template-columns: repeat(2, 1fr); }
@media (max-width: 640px)  { grid-template-columns: 1fr; }
```

---

## 4. Gridcard-Anatomie

### 4.1 People Cards – Expert (350 px) & Speaker (300 px)

#### Expert-Card (350 px)

Die Expert-Card bietet mehr Platz für Expertise und Auszeichnungen.

```
┌──────────────────────────────────────────┐  ← 350px
│  [Ribbon oben-links: Partner/Certified]  │  ← abs. oder flow-Ribbon
│  [Ribbon oben-rechts: Verfügbarkeit]     │  ← abs. oder flow-Ribbon
│  ┌──────────────────────────────────────┐│
│  │ .expert-card-top                    ││  ← ~90px Hintergrund (card-top-bg)
│  │   [Avatar 56×56]  Name              ││
│  │   rund, Initials  Position / Titel  ││
│  │               📍 Stadt · Unternehmen ││
│  └──────────────────────────────────────┘│
│  ─── Expertise-Banner ───────────────────  │  ← ~28px
│  ┌──────────────────────────────────────┐│
│  │ .expert-card-body                   ││  ← flex-grow: 1
│  │   [Expert-Skills & Zertifikationen] ││
│  │   (max 8–10 Pills sichtbar)         ││
│  └──────────────────────────────────────┘│
│  ─── Social Icons (LinkedIn, Web, Mail) ─ │  ← ~32px
│  ┌──────────────────────────────────────┐│
│  │ .expert-card-cta                    ││  ← ~50px (mt-auto)
│  │   "Profil ansehen →"                ││
│  └──────────────────────────────────────┘│
└──────────────────────────────────────────┘
```

**Spezifische Regeln Expert:**
- Avatar-Größe: `56×56 px`, `border-radius: 50%`, `object-fit: cover`
- Avatar-Placeholder: `background: linear-gradient(135deg, primary, accent)`, Initialen weiß
- Name-Link: `font-size: 1rem`, `font-weight: 700`, kein Underline, `color: var(--expert-text)`
- Position/Titel: `font-size: 0.8rem`, `color: var(--expert-text-light)`, max. 1 Zeile
- Expertise-Banner: `background: rgba(245,158,11,.08)`, `font-size: 0.7rem`, `font-weight: 600`
- Skill-Pills: `background: rgba(245,158,11,.1)`, `color: var(--expert-primary-hover)`, `border-radius: 6px`, `padding: 2px 8px`, `font-size: 0.72rem`
- Zertifikations-Badges: Icon + Text, max. 3 sichtbar
- CTA: `background: var(--expert-cta-color)`, `color: #fff`, `font-weight: 600`, voll-breit, kein Radius unten (flush mit Card-Kante)

#### Speaker-Card (300 px)

Die Speaker-Card ist kompakter und fokussiert auf Themen und Verfügbarkeit.

```
┌──────────────────────────────────────────┐  ← 300px
│  [Ribbon oben-links: Featured]           │  ← abs. oder flow-Ribbon
│  [Ribbon oben-rechts: Verfügbar]         │  ← abs. oder flow-Ribbon
│  ┌──────────────────────────────────────┐│
│  │ .sp-card-top                        ││  ← ~72px Hintergrund (card-top-bg)
│  │   [Avatar 56×56]  Name              ││
│  │   rund, Initials  Position / Titel  ││
│  │               📍 Stadt · Unternehmen ││
│  └──────────────────────────────────────┘│
│  ─── Topics-Banner ───────────────────────  │  ← ~28px
│  ┌──────────────────────────────────────┐│
│  │ .sp-card-body                       ││  ← flex-grow: 1
│  │   [Topic-Pills – max. 6 sichtbar]  ││
│  │   (overflow:auto bei Bedarf)        ││
│  └──────────────────────────────────────┘│
│  ─── Social Icons (LinkedIn, Xing, …) ─── │  ← ~30px
│  ┌──────────────────────────────────────┐│
│  │ .sp-card-cta                        ││  ← ~40px (mt-auto)
│  │   "Profil ansehen →"                ││
│  └──────────────────────────────────────┘│
└──────────────────────────────────────────┘
```

**Spezifische Regeln Speaker:**
- Avatar-Größe: `56×56 px`, `border-radius: 50%`, `object-fit: cover`
- Avatar-Placeholder: `background: linear-gradient(135deg, primary, accent)`, Initialen weiß
- Name-Link: `font-size: 1rem`, `font-weight: 700`, kein Underline, `color: var(--sp-text)`
- Position/Titel: `font-size: 0.8rem`, `color: var(--sp-text-m)`, max. 1 Zeile
- Topics-Banner: `background: rgba(139,92,246,.08)`, `font-size: 0.7rem`, `font-weight: 600`
- Topic-Pills: `background: rgba(139,92,246,.1)`, `color: var(--sp-primary-h)`, `border-radius: 6px`, `padding: 2px 8px`, `font-size: 0.72rem`, max. 6 sichtbar
- CTA: `background: var(--sp-secondary)`, `color: #fff`, `font-weight: 600`, voll-breit, kein Radius unten (flush mit Card-Kante)

### 4.2 Entity-Cards (Company & Event) – 275 px

Entity-Cards fokussieren auf Erkennungswert (Logo/Datum) und schnelle Informationsaufnahme.

**Company-Card-Anatomie:**

```
┌──────────────────────────────────────────┐  ← 300px
│  [Ribbon oben-rechts: Partner/Sponsor]   │  ← abs. oder flow-Ribbon
│  ┌──────────────────────────────────────┐│
│  │ .co-card-head                        ││  ← ~70px
│  │   [Avatar 48×48]   Firmenname        ││
│  │   Logo oder        Branche-Tag       ││
│  │   Initialen-Chip                     ││
│  └──────────────────────────────────────┘│
│  ─ Info-Pills (📍 Stadt · 👥 Mitarb.) ─ │  ← ~28px
│  ┌──────────────────────────────────────┐│
│  │ .co-card-excerpt                     ││  ← flex-grow:1
│  │   max. 1–2 Zeilen, line-clamp        ││
│  └──────────────────────────────────────┘│
│  ┌──────────────────────────────────────┐│
│  │ .co-card-footer                      ││  ← ~44px (mt-auto)
│  │   [Details ansehen]  [🌐 Website]   ││
│  └──────────────────────────────────────┘│
└──────────────────────────────────────────┘
```

**Event-Card-Anatomie:**

```
┌──────────────────────────────────────────┐  ← 300px
│  [Ribbon: Featured / Heute / Vergangen]  │  ← abs. oder flow-Ribbon
│  ┌──────────────────────────────────────┐│
│  │ .ev-card-head                        ││  ← ~60px
│  │   [Datum-Block DD/MMM]  Event-Titel  ││
│  │   starke Typografie     Kategorie    ││
│  └──────────────────────────────────────┘│
│  ─ Info-Pills (📍 · 🎤 · 🪑 · 💶) ──── │  ← ~28px
│  [Tag-Pills maximal 3]                   │  ← ~24px
│  ┌──────────────────────────────────────┐│
│  │ .ev-card-excerpt                     ││  ← flex-grow:1
│  │   max. 1–2 Zeilen, line-clamp        ││
│  └──────────────────────────────────────┘│
│  ┌──────────────────────────────────────┐│
│  │ .ev-card-footer                      ││  ← ~44px (mt-auto)
│  │   [Details →]  [Anmelden]            ││
│  └──────────────────────────────────────┘│
└──────────────────────────────────────────┘
```

### 4.3 Card Hover-Verhalten (alle vier Typen)

```css
.{prefix}-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--{p}-shadow-lg);
    border-color: var(--{p}-primary);   /* subtile Akzentlinie */
}
```

Transition: `all 0.2s cubic-bezier(0.4,0,0.2,1)` – **nicht** langsamer.

### 4.4 Status-Badges und Ribbons

| Plugin | Badge-Position links | Badge-Position rechts |
|--------|--------------------|-----------------------|
| Expert | `Partner`, `Zertifiziert` (Tab-Badges, top-left) | Verfügbarkeits-Badge |
| Speaker | `Featured`, `Verifiziert` (Tab-Badges, top-left) | Verfügbarkeits-Badge |
| Company | — | Ribbon: `Sponsor` / `Top-Partner` / `Partner` |
| Events | Ribbon: `Featured` / `Heute` / `Vergangen` / `Abgesagt` | — |

Tab-Badges (Expert/Speaker): `position: absolute; top: 0; border-radius: 0 0 8px 8px; padding: 1px 10px; font-size: 0.65rem; font-weight: 700; max-height: 17px`

Ribbons (Company/Event): schräge oder horizontale Leiste, `font-size: 0.72rem`, `font-weight: 700`, CSS-Variable für Hintergrundfarbe

---

## 5. Single Site (Detailseiten)

### 5.1 Header – einheitliches Konzept

**Alle Single Sites**: `min-height: 300px`, Gradient-Hintergrund, `position: relative`

```
┌─────────────────────────────────────────────────────────────┐
│  .{prefix}-single-header    min-height: 300px               │
│  Background: Gradient  oder  Bild + Overlay                 │
│                                                             │
│       Expert / Speaker            Company / Event           │
│   ┌─────────────────────┐   ┌──────────────────────────┐  │
│   │ [Avatar 96–120 px]  │   │ [Logo / Event-Visual]    │  │
│   │  Name  (h1)         │   │  Name  (h1)              │  │
│   │  Position (h2/p)    │   │  Ort / Datum (Badge)     │  │
│   │  [Social-Icons]     │   │  Kategorie               │  │
│   └─────────────────────┘   └──────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

- `h1`: `font-size: clamp(1.5rem, 4vw, 2.25rem)`, `font-weight: 800`
- Untertitel/Position: `font-size: 1.1rem`, `opacity: 0.85`
- Badge im Header (Datum, Ort, Status): `background: rgba(255,255,255,.2)`, `backdrop-filter: blur(6px)`, `border-radius: 8px`, `padding: 0.4rem 0.9rem`
- Kein Text-Shadow auf dunklem Hintergrund; Lesbarkeit durch ausreichenden Kontrast sicherstellen

### 5.2 Layout: 2-Spaltig (Content + Sidebar)

```
┌───────────────────────────────────────────────────────┐
│  .{prefix}-single-layout                             │
│  display: grid; grid-template-columns: 1fr 300px;   │
│  gap: 2rem; padding: 2rem 1.5rem; max-width: 1140px; │
├──────────────────────────────────┬────────────────────┤
│  main  (.{prefix}-single-main)   │  aside (sidebar)   │
│  flex-direction: column          │  sticky-top: 2rem  │
│  gap: 1.5rem                     │  gap: 1.25rem      │
│                                  │  display: flex     │
│  Expert / Speaker:               │  flex-direction:   │
│   - Vita / Bio                   │  column            │
│   - Expertise / Themenspektrum   │                    │
│   - Referenzen / Formate         │  Expert / Speaker: │
│                                  │   - Kontakt-Card   │
│  Company:                        │   - Company-Link   │
│   - Unternehmensprofil           │   - Events-Liste   │
│   - Leistungen / Produkte        │                    │
│   - Philosophie                  │  Company:          │
│                                  │   - Kontakt-Card   │
│  Events:                         │   - Website/Map    │
│   - Beschreibung                 │   - Expert-Liste   │
│   - Agenda / Zeitplan            │   - Events-Liste   │
│   - Speaker-Lane                 │                    │
│                                  │  Events:           │
│                                  │   - Anmelde-CTA    │
│                                  │   - Key-Facts      │
│                                  │   - Sponsor-Liste  │
│                                  │   - Speaker-Liste  │
└──────────────────────────────────┴────────────────────┘
```

**Responsive:** Unterhalb `768px` wechselt das Grid auf `1fr` (einspaltig), Sidebar rutscht unter Main.

### 5.3 Content-Sektionen (Single)

```html
<section class="{prefix}-section">
    <h2 class="{prefix}-section-title">
        <span class="{prefix}-section-icon">🧠</span>
        Expertise
    </h2>
    <div class="{prefix}-section-body">
        <!-- Inhalt -->
    </div>
</section>
```

- Section-Titel: `font-size: 1.2rem`, `font-weight: 700`, `border-bottom: 2px solid var(--{p}-primary)`, `padding-bottom: 0.5rem`, `color: var(--network-text-base)`
- Section-Icon: Emoji als Orientierungspunkt, `margin-right: 0.5rem`
- `{prefix}-section-body`: `padding-top: 1rem`

### 5.4 Sidebar-Cards (Single)

```html
<div class="{prefix}-sidebar-card">
    <h3 class="{prefix}-sidebar-card-title">Kontakt</h3>
    <!-- Inhalt -->
</div>
```

- `background: #ffffff`, `border: 1px solid var(--network-border)`, `border-radius: 12px`
- `padding: 1.25rem`, `box-shadow: 0 2px 8px rgba(0,0,0,.05)`
- Titel: `font-size: 0.875rem`, `font-weight: 700`, `text-transform: uppercase`, `letter-spacing: 0.05em`, `color: var(--network-text-subtle)`, `margin-bottom: 0.75rem`
- CTA-Button in Sidebar: volle Breite, Plugin-Primärfarbe

---

## 6. CSS-Namenskonventionen und Präfixe

| Plugin | CSS-Klassen-Präfix | JS-Datei-Präfix |
|--------|-------------------|-----------------|
| cms-experts | `.expert-` | `expert` |
| cms-companies | `.co-` | `co` |
| cms-events | `.ev-` | `ev` |
| cms-speakers | `.sp-` | `sp` |

**Pflicht:** Jede CSS-Klasse im Plugin-Stylesheet trägt den entsprechenden Präfix.
Niemals generische Klassen wie `.card`, `.btn`, `.grid` ohne Präfix verwenden.

### 6.1 Block-Element-Modifier (BEM-ähnlich)

```
.{prefix}-card                  ← Block
.{prefix}-card-head             ← Element
.{prefix}-card--featured        ← Modifier (State)
.{prefix}-card--past            ← Modifier (Status)
```

---

## 7. Typografie (Frontend-Cards und Single Sites)

| Element | font-size | font-weight | Farbe |
|---------|-----------|-------------|-------|
| Archiv-Titel (h1) | `1.875rem` | `800` | `var(--{p}-hdr-title)` |
| Card-Name / Titel | `1rem` | `700` | `var(--network-text-base)` |
| Card-Position | `0.8rem` | `400–500` | `var(--network-text-medium)` |
| Card-Excerpt | `0.82rem` | `400` | `var(--network-text-medium)` |
| Pill-Labels | `0.72rem` | `500` | `Plugin-Primärfarbe` |
| Badge/Ribbon | `0.65–0.72rem` | `700` | Weiß oder Kontrastfarbe |
| Section-Titel (Single) | `1.2rem` | `700` | `var(--network-text-base)` |
| Sidebar-Titel | `0.875rem` | `700` | `var(--network-text-subtle)` |

**Schriftfamilie:** Erbt vom Theme; kein eigener Font-Import im Plugin-CSS.

---

## 8. Buttons im Frontend

```css
/* Primär-CTA (Card-Footer, Sidebar) */
.{prefix}-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.55rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    border-radius: 8px;
    border: none;
    background: var(--{p}-primary);
    color: #ffffff;
    text-decoration: none;
    transition: background 0.2s var(--network-ease);
    cursor: pointer;
}
.{prefix}-btn-primary:hover { background: var(--{p}-primary-hover); }

/* Ghost / Sekundär */
.{prefix}-btn-ghost {
    background: #f1f5f9;
    color: var(--network-text-medium);
    ...
}
.{prefix}-btn-ghost:hover { background: #e2e8f0; }
```

- CTA am Fuß der Card: `width: 100%`, `justify-content: center`, `border-radius: 0 0 {radius} {radius}` (flush)
- Max. 2 Buttons pro Card-Footer: primär + ghost/sekundär

---

## 9. Paginierung

```html
<div class="{prefix}-pagination">
    <a href="?page=N-1" class="{prefix}-page-btn">&larr; Zurück</a>
    <span class="{prefix}-page-info">Seite N von M</span>
    <a href="?page=N+1" class="{prefix}-page-btn">Weiter &rarr;</a>
</div>
```

```css
.{prefix}-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.75rem;
    padding: 2rem 1.5rem 1rem;
}
.{prefix}-page-btn {
    padding: 0.4rem 1rem;
    border: 1px solid var(--network-border);
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--network-text-medium);
    text-decoration: none;
    background: #fff;
    transition: all 0.15s var(--network-ease);
}
.{prefix}-page-btn:hover {
    border-color: var(--{p}-primary);
    color: var(--{p}-primary);
}
.{prefix}-page-info {
    font-size: 0.875rem;
    color: var(--network-text-subtle);
}
```

---

## 10. Responsives Verhalten

| Breakpoint | Archiv-Grid | Single Layout |
|------------|-------------|---------------|
| ≥ 1025 px  | 3 Spalten   | 2-spaltig (1fr + 300px) |
| 641–1024px | 2 Spalten   | 2-spaltig (1fr + 260px) |
| ≤ 640 px   | 1 Spalte    | 1-spaltig (Sidebar unter Main) |

- Auf Mobile: Card-Höhe weiterhin `300px` (keine Änderung notwendig – Cards haben genug Inhalt)
- Filter-Bar: `flex-wrap: wrap`, Each Selektor auf Mobile `width: 100%`
- Archiv-Header: Padding reduziert auf `1.5rem`, Zähler-Chip rutscht in neue Zeile

---

## 11. PHP-Template-Konventionen (Frontend)

### 11.1 CSS-Variablen per PHP überschreiben

Jedes Archiv-Template schreibt einen individuellen `<style>`-Block am Anfang des Wrappers,
der die CSS-Variablen aus den Admin-Einstellungen befüllt:

```php
$primary = htmlspecialchars($settings['design_primary_color'] ?? '#f59e0b');
echo '<style>:root{--expert-primary:' . $primary . ';}</style>';
```

### 11.2 Datenausgabe

- `htmlspecialchars($value)` für alle dynamischen Werte in HTML-Attributen und Text-Nodes
- `CMS\Security::instance()->escape($value)` als Alias erlaubt (selbes Ergebnis)
- `mb_substr(strip_tags($html), 0, 120)` für Excerpts
- Schlug-Generierung: `CMS_{Plugin}_Database::generate_slug($entity)` – nie direkt `$entity->slug` ohne Fallback

### 11.3 Asset-Einbindung

```php
// Versionsstempel via filemtime() – kein manueller ?v= Parameter
$css = CMS_EXPERTS_PLUGIN_DIR . 'assets/css/style.css';
echo '<link rel="stylesheet" href="' . CMS_EXPERTS_PLUGIN_URL . 'assets/css/style.css?v=' . filemtime($css) . '">' . "\n";
```

---

## 12. Übersichtstabelle: Plugin-Merkmale

| Merkmal | Expert | Company | Events | Speaker |
|---------|--------|---------|--------|---------|
| CSS-Präfix | `.expert-` | `.co-` | `.ev-` | `.sp-` |
| Primärfarbe | `#f59e0b` Amber | `#0891b2` Cyan | `#3b82f6` Blau | `#8b5cf6` Violett |
| Card-Hintergrund | `#fffdf4` | `#ffffff` | `#f0f7ff` | `#faf5ff` |
| Card-Höhe | **350 px** | **300 px** | **300 px** | **300 px** |
| Card-Radius | `16px` | `12px` | `12px` | `16px` |
| Header-Gradient | Warm-Amber | Cyan-Sky | Dunkelblau | Dunkelviolett |
| Avatar | Rund, Foto/Initialen | Rund, Logo/Initialen | Datum-Block | Rund, Foto/Initialen |
| Pills | Skills (max. 8) | Info-Facts | Info + Tags (max. 4+4) | Topics (max. 6) |
| Status-System | Verfügbarkeit | Partner-Tier | Vergangen/Heute/Featured | Verfügbarkeit |
| Social-Icons | LinkedIn, Web, Mail | Website | — | LinkedIn, Xing, Twitter, Github |
| Single-Header | Porträt + Name | Keyvisual + Logo | Event-Visual + Datum | Porträt + Name |

---

## 13. Checkliste vor dem Commit (Frontend)

- [ ] CSS-Variablen vollständig in `:root` definiert
- [ ] Kein `height` auf Card-Content-Bereichen (nur auf `.{prefix}-card` selbst)
- [ ] `overflow: hidden` auf `.{prefix}-card` gesetzt
- [ ] Excerpts per `line-clamp` beschränkt (2–3 Zeilen)
- [ ] Hover-Transition max. `0.2s`
- [ ] Kein Icon-Font geladen (SVG oder Emoji)
- [ ] Alle dynamischen Werte mit `htmlspecialchars()` escaped
- [ ] `filemtime()` als Cache-Buster für Assets
- [ ] Breakpoints: 3 → 2 → 1 Spalte im Grid
- [ ] Single Site: 2-spaltig ab `768px`
- [ ] Sidebar sticky (`position: sticky; top: 2rem`) auf Desktop
