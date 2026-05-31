# cms-experts – Public Frontend Richtlinie

> Gilt für die öffentliche Experten-Übersicht (`/experts`), die Expert-Cards und die Expert-Detailseite.
>
> Letzte Aktualisierung: 2026-05-31  
> Stand: `cms-experts` 3.0.8

---

## 1. Publicsite-Zielbild

Die öffentliche Experten-Übersicht folgt dem Muster von `cms-events`:

- kein eigener Plugin-Hero und kein nachgebauter Theme-Kopfbereich,
- Filter direkt oberhalb der Ergebnisse,
- responsive Cards im Grid,
- Experten-spezifische Meta-Infos statt alter Social-/Banner-Optik,
- klickbare und per Tastatur erreichbare Cards.

Das Theme liefert Header, Navigation und Footer. Das Plugin rendert ausschließlich den Content-Bereich.

---

## 2. Archiv-Seite (`archive-expert.php`)

### Struktur

```text
main.experts-archive-wrapper
  nav.expert-filter-nav
    form.archive-filter-bar.expert-card-filter
      .phinit-field + label/input search
      .phinit-field + label/input city
      .phinit-field + label/select availability
      button.phinit-btn--primary
      a.phinit-btn--secondary reset
  section.experts-grid.expert-card-grid
    article.expert-overview-card × N
  nav.expert-pagination
```

### Filter

| Feld | Typ | Zweck |
|------|-----|-------|
| `q` | `input[type="search"]` | Suche nach Name, Firma, Skill oder Position |
| `city` | `input[type="text"]` | Standortfilter |
| `availability` | `select` | `available`, `limited`, `booked` |

Alle Filterfelder haben sichtbare Labels. Der Reset-Link wird nur gerendert, wenn mindestens ein Filter aktiv ist.

---

## 3. Expert-Card (`expert-card.php`)

### Meta-Infos

Die Overview-Card ist bewusst kompakt und zeigt die wichtigsten Entscheidungsinformationen:

- Avatar oder Initialen,
- Name und Position,
- Firma und Standort,
- Verfügbarkeit,
- MVP-/Premium-/Award-/Zertifiziert-Badge,
- bis zu zwei Spezialisierungen,
- Erfahrung in Jahren,
- Zertifikatsanzahl,
- bis zu vier Skills,
- Website-Hinweis oder Fallback-Info,
- Profil-CTA.

### Markup-Kern

```html
<article class="phinit-card expert-card expert-card--overview expert-overview-card"
         data-expert-card
         data-expert-url="..."
         role="link"
         tabindex="0">
    <div class="expert-overview-card__body">
        <div class="expert-card-badge-row">...</div>
        <header class="expert-card-main">...</header>
        <div class="expert-card-stat-row">...</div>
        <div class="expert-card-skills">...</div>
        <footer class="expert-card-footer">...</footer>
    </div>
</article>
```

Die Card bleibt als `<article>` semantisch eigenständig. Interne Links wie Profil, Website oder Company-Link bleiben echte `<a>`-Elemente.

---

## 4. Design-System

### Farben

`cms-experts` nutzt warme Amber-/Orange-Akzente, aber ausschließlich zurückhaltend:

- CTA und aktive Zustände: `--expert-card-accent`,
- Hover: `--expert-card-accent-hover`,
- helle Badge-Flächen: `--expert-card-accent-soft`,
- Kartenfläche: `--phinit-color-surface`,
- Text und Border: `--phinit-color-ink`, `--phinit-color-border`.

Alte Gradient-Hero-Flächen und feste Bannerbereiche werden auf der Übersicht nicht mehr verwendet.

### Layout

- Grid: `repeat(auto-fill, minmax(min(100%, 300px), 1fr))`
- Cards wachsen natürlich mit Inhalt.
- Keine feste Card-Höhe.
- Keine Social-Icon-Bänder in der Übersicht.
- CTA sitzt im Card-Footer und ist kompakt.

---

## 5. Interaktion

`assets/js/script.js` ergänzt:

- Auto-Submit beim Ändern des Availability-Selects,
- Card-Klick auf nicht-interaktive Bereiche,
- Tastaturnavigation mit `Enter` und `Space`,
- Schutz für echte Links, Buttons, Inputs, Selects und Textareas.

---

## 6. Accessibility

- Filterbereich nutzt `<nav aria-label="Expertenfilter">` und `<form role="search">`.
- Cards sind per `tabindex="0"` fokussierbar.
- Card-Link-Ziel ist über `aria-label` benannt.
- Profilbilder haben `alt` mit dem vollständigen Namen.
- Externe Website-Links nutzen `target="_blank" rel="noopener noreferrer"`.
- Empty State nutzt `role="status" aria-live="polite"`.

---

## 7. Detailseite

Die Detailseite (`single-expert.php`) rendert über `main.phinit-plugin.ex-v2`. Die Shell liegt bündig an Theme-Header und -Footer an, entfernt die PHINIT-Wrapper-Abstände per scoped `:has()` und füllt bei kurzem Inhalt den Bereich bis zum Theme-Footer.

Direkte Inhaltsbereiche bleiben zentriert auf maximal `1160px`:

- `nav.ex-bc` für Breadcrumb,
- `header.ex-hero` für Profilkopf,
- `.ex-bridge` für Über-mich/Kontakt,
- `.ex-body` für Hauptinhalt und Sidebar,
- `.ex-claim-banner` für Profil-Claim.

Unterhalb von Tablet-Breiten wechseln Bridge und Body auf eine Spalte. Mobile werden Skills, Zertifikate, Events, Kontaktlinks und Info-Rows ebenfalls einspaltig. Dark Mode ist für Shell, Cards, Sidebar, Tags, Social-Links, Projekt-/Eventkarten, Claim-Banner und Textfarben über `body.dark-mode` sowie `html.dark-mode body:not(.light-mode)` abgesichert.

---

## 8. Checkliste Public Overview

- [x] Kein separater Plugin-Hero im Archiv
- [x] Filter-first wie `cms-events`
- [x] Responsive Card-Grid
- [x] Expert-spezifische Meta-Infos statt Event-Meta
- [x] Keine festen Overview-Card-Höhen
- [x] Keine Social-Icon-Bänder in Overview-Cards
- [x] Sichtbare Labels in allen Filterfeldern
- [x] Cards klickbar und tastaturbedienbar
