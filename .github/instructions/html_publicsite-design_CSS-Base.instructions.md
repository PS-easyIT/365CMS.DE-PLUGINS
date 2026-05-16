/* ==========================================================================
   PHINIT plugin-base.css
   Normative Basis für alle Plugins im Content-Bereich von phinit.de
   Version 1.0 · 365CMS / PHINIT-Theme
   --------------------------------------------------------------------------
   Diese Datei ist die verbindliche technische Umsetzung der
   "PHINIT Plugin-Design-Richtlinie". Plugins binden sie ein und ergänzen
   ausschliesslich Struktur/Layout. Werte werden NIE ueberschrieben.

   Die :root-Definition unten ist nur ein FALLBACK. Liefert das Theme die
   --phinit-*-Variablen, gewinnen dessen Werte automatisch (var() mit
   Fallback). Plugins definieren KEINE eigenen --phinit-*-Variablen.
   ========================================================================== */

/* --------------------------------------------------------------------------
   1. Token-Fallbacks (greifen nur, wenn das Theme sie nicht setzt)
   -------------------------------------------------------------------------- */
:root {
  /* Farben */
  --phinit-color-bg:            #dde6f0;
  --phinit-color-surface:       #ffffff;
  --phinit-color-surface-alt:   #1b2a44;
  --phinit-color-ink:           #1a2233;
  --phinit-color-ink-secondary: #5a6678;
  --phinit-color-ink-on-dark:   #e8edf5;
  --phinit-color-border:        #dde3ec;
  --phinit-color-accent:        #f5a623;
  --phinit-color-accent-ink:    #1a2233;
  --phinit-color-link:          #1f5fa8;
  --phinit-color-success:       #3b6d11;
  --phinit-color-warning:       #854f0b;
  --phinit-color-danger:        #a32d2d;

  /* Typografie */
  --phinit-font-display: 'Space Grotesk', system-ui, -apple-system, sans-serif;
  --phinit-font-body:    'Inter', system-ui, -apple-system, sans-serif;
  --phinit-font-mono:    ui-monospace, 'SF Mono', 'Cascadia Code', monospace;

  --phinit-text-xs:   0.75rem;
  --phinit-text-sm:   0.8125rem;
  --phinit-text-base: 0.9375rem;
  --phinit-text-lg:   1.0625rem;
  --phinit-text-h3:   1.25rem;
  --phinit-text-h2:   1.5rem;
  --phinit-text-h1:   1.875rem;

  --phinit-leading-body:  1.7;
  --phinit-leading-tight: 1.25;

  /* Abstaende (4er-Skala) */
  --phinit-space-1:  4px;
  --phinit-space-2:  8px;
  --phinit-space-3:  12px;
  --phinit-space-4:  16px;
  --phinit-space-5:  24px;
  --phinit-space-6:  32px;
  --phinit-space-8:  48px;
  --phinit-space-10: 64px;

  /* Radien */
  --phinit-radius-sm: 6px;
  --phinit-radius-md: 10px;

  /* Schatten (genau zwei) */
  --phinit-shadow-card:   0 1px 2px rgba(20, 34, 68, 0.06);
  --phinit-shadow-raised: 0 4px 16px rgba(20, 34, 68, 0.10);

  /* Layout */
  --phinit-content-max: 68ch;
  --phinit-transition:  0.2s ease;
}

/* --------------------------------------------------------------------------
   2. Plugin-Wrapper
   Kein eigener Hintergrund, kein Rahmen, kein vertikaler Aussenabstand.
   Das Theme regelt den Abstand zu Header/Footer.
   -------------------------------------------------------------------------- */
.phinit-plugin {
  font-family: var(--phinit-font-body);
  font-size: var(--phinit-text-base);
  line-height: var(--phinit-leading-body);
  color: var(--phinit-color-ink);
  -webkit-font-smoothing: antialiased;
}

.phinit-plugin *,
.phinit-plugin *::before,
.phinit-plugin *::after {
  box-sizing: border-box;
}

/* Plugin darf nicht aus dem Content-Bereich ausbrechen */
.phinit-plugin {
  max-width: 100%;
  overflow-wrap: break-word;
}

/* Fliesstext-Breite begrenzen */
.phinit-plugin .phinit-prose {
  max-width: var(--phinit-content-max);
}

.phinit-plugin .phinit-prose p,
.phinit-plugin .phinit-prose ul,
.phinit-plugin .phinit-prose ol {
  margin: 0 0 var(--phinit-space-4);
}

.phinit-plugin a {
  color: var(--phinit-color-link);
  text-decoration: none;
}
.phinit-plugin a:hover {
  text-decoration: underline;
}

/* --------------------------------------------------------------------------
   3. Ueberschriften
   Plugin beginnt bei h2 (h1 liefert das Theme). Display-Schrift, Gewicht 500.
   -------------------------------------------------------------------------- */
.phinit-plugin h1,
.phinit-plugin h2,
.phinit-plugin h3 {
  font-family: var(--phinit-font-display);
  font-weight: 500;
  line-height: var(--phinit-leading-tight);
  color: var(--phinit-color-ink);
  margin: 0 0 var(--phinit-space-4);
}
.phinit-plugin h1 { font-size: var(--phinit-text-h1); }
.phinit-plugin h2 { font-size: var(--phinit-text-h2); }
.phinit-plugin h3 { font-size: var(--phinit-text-h3); }

/* Sektions-Overline (NICHT der dunkle Theme-Balken nachbauen) */
.phinit-plugin .phinit-overline {
  font-family: var(--phinit-font-body);
  font-size: var(--phinit-text-xs);
  font-weight: 500;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--phinit-color-ink-secondary);
  padding-bottom: var(--phinit-space-2);
  border-bottom: 1px solid var(--phinit-color-border);
  margin: 0 0 var(--phinit-space-5);
}

/* Vertikaler Rhythmus zwischen logischen Bloecken */
.phinit-plugin > * + * {
  margin-top: var(--phinit-space-8);
}

/* --------------------------------------------------------------------------
   4. Karten
   -------------------------------------------------------------------------- */
.phinit-card {
  background: var(--phinit-color-surface);
  border: 1px solid var(--phinit-color-border);
  border-radius: var(--phinit-radius-md);
  box-shadow: var(--phinit-shadow-card);
  padding: var(--phinit-space-5);
}

/* Kategorie-/Status-Markierung NUR ueber linken Border */
.phinit-card--accent  { border-left: 3px solid var(--phinit-color-accent); }
.phinit-card--success { border-left: 3px solid var(--phinit-color-success); }
.phinit-card--warning { border-left: 3px solid var(--phinit-color-warning); }
.phinit-card--danger  { border-left: 3px solid var(--phinit-color-danger); }

/* Keine Karten in Karten */
.phinit-card .phinit-card {
  box-shadow: none;
  border-width: 0 0 0 3px;
  border-radius: 0;
  padding: var(--phinit-space-3) 0;
}

/* --------------------------------------------------------------------------
   5. Buttons (genau drei Rollen)
   -------------------------------------------------------------------------- */
.phinit-btn {
  display: inline-flex;
  align-items: center;
  gap: var(--phinit-space-2);
  min-height: 44px;
  padding: var(--phinit-space-3) var(--phinit-space-5);
  font-family: var(--phinit-font-body);
  font-size: var(--phinit-text-base);
  font-weight: 500;
  border-radius: var(--phinit-radius-sm);
  border: 1px solid transparent;
  cursor: pointer;
  text-decoration: none;
  transition: background var(--phinit-transition),
              border-color var(--phinit-transition),
              filter var(--phinit-transition);
}
.phinit-btn:active { transform: scale(0.98); }

.phinit-btn--primary {
  background: var(--phinit-color-accent);
  color: var(--phinit-color-accent-ink);
}
.phinit-btn--primary:hover { filter: brightness(0.95); text-decoration: none; }

.phinit-btn--secondary {
  background: transparent;
  color: var(--phinit-color-ink);
  border-color: var(--phinit-color-border);
}
.phinit-btn--secondary:hover {
  background: var(--phinit-color-bg);
  border-color: var(--phinit-color-accent);
  text-decoration: none;
}

.phinit-btn--link {
  min-height: auto;
  padding: var(--phinit-space-2) 0;
  background: none;
  border: none;
  color: var(--phinit-color-link);
}
.phinit-btn--link .phinit-arrow {
  display: inline-block;
  transition: transform var(--phinit-transition);
}
.phinit-btn--link:hover { text-decoration: none; }
.phinit-btn--link:hover .phinit-arrow { transform: translateX(4px); }

/* --------------------------------------------------------------------------
   6. Formularelemente
   -------------------------------------------------------------------------- */
.phinit-field {
  margin-bottom: var(--phinit-space-4);
}
.phinit-field label {
  display: block;
  font-size: var(--phinit-text-sm);
  color: var(--phinit-color-ink-secondary);
  margin-bottom: var(--phinit-space-2);
}
.phinit-field .phinit-required {
  color: var(--phinit-color-ink-secondary);
  font-weight: 400;
}
.phinit-input,
.phinit-select,
.phinit-textarea {
  width: 100%;
  padding: var(--phinit-space-3);
  font-family: var(--phinit-font-body);
  font-size: var(--phinit-text-base);
  color: var(--phinit-color-ink);
  background: var(--phinit-color-surface);
  border: 1px solid var(--phinit-color-border);
  border-radius: var(--phinit-radius-sm);
}
.phinit-input:focus,
.phinit-select:focus,
.phinit-textarea:focus {
  outline: 2px solid var(--phinit-color-accent);
  outline-offset: 1px;
}
.phinit-field-error {
  display: block;
  margin-top: var(--phinit-space-2);
  font-size: var(--phinit-text-sm);
  color: var(--phinit-color-danger);
}

/* --------------------------------------------------------------------------
   7. Tabellen (fuer tabellarische Daten zwingend)
   -------------------------------------------------------------------------- */
.phinit-table {
  width: 100%;
  border-collapse: collapse;
  font-size: var(--phinit-text-sm);
}
.phinit-table th {
  text-align: left;
  font-size: var(--phinit-text-xs);
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--phinit-color-ink-secondary);
  padding: var(--phinit-space-2) var(--phinit-space-3);
  border-bottom: 1px solid var(--phinit-color-border);
}
.phinit-table td {
  padding: var(--phinit-space-3);
  border-bottom: 0.5px solid var(--phinit-color-border);
  vertical-align: top;
}
.phinit-table tr:last-child td { border-bottom: none; }
.phinit-table tbody tr:hover td { background: var(--phinit-color-bg); }
.phinit-table .phinit-num { text-align: right; font-variant-numeric: tabular-nums; }

/* Mobile: Tabelle in scrollbarem Container */
.phinit-table-wrap {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

/* --------------------------------------------------------------------------
   8. Hinweisboxen (flach, Farbe nur im linken Border)
   -------------------------------------------------------------------------- */
.phinit-note {
  background: var(--phinit-color-surface);
  border: 1px solid var(--phinit-color-border);
  border-left-width: 3px;
  border-radius: var(--phinit-radius-sm);
  padding: var(--phinit-space-3) var(--phinit-space-4);
  font-size: var(--phinit-text-sm);
  color: var(--phinit-color-ink);
}
.phinit-note--info    { border-left-color: var(--phinit-color-link); }
.phinit-note--success { border-left-color: var(--phinit-color-success); }
.phinit-note--warning { border-left-color: var(--phinit-color-warning); }
.phinit-note--danger  { border-left-color: var(--phinit-color-danger); }

/* --------------------------------------------------------------------------
   9. Schritte / Tabs (fuer Tool-Plugins)
   -------------------------------------------------------------------------- */
.phinit-steps {
  display: flex;
  gap: var(--phinit-space-5);
  border-bottom: 1px solid var(--phinit-color-border);
  margin-bottom: var(--phinit-space-6);
}
.phinit-step {
  position: relative;
  padding: 0 0 var(--phinit-space-3);
  font-size: var(--phinit-text-sm);
  color: var(--phinit-color-ink-secondary);
  background: none;
  border: none;
  cursor: pointer;
}
.phinit-step::after {
  content: '';
  position: absolute;
  left: 0; bottom: -1px;
  width: 0; height: 2px;
  background: var(--phinit-color-accent);
  transition: width var(--phinit-transition);
}
.phinit-step[aria-current="step"] {
  color: var(--phinit-color-ink);
  font-weight: 500;
}
.phinit-step[aria-current="step"]::after { width: 100%; }

/* Ergebnis-Anzeige: nuechtern, keine Show */
.phinit-result {
  padding: var(--phinit-space-5);
  background: var(--phinit-color-surface);
  border: 1px solid var(--phinit-color-border);
  border-radius: var(--phinit-radius-md);
}
.phinit-result__value {
  font-family: var(--phinit-font-display);
  font-size: var(--phinit-text-h1);
  font-weight: 600;
  color: var(--phinit-color-ink);
}
.phinit-result__caption {
  font-size: var(--phinit-text-sm);
  color: var(--phinit-color-ink-secondary);
  margin-top: var(--phinit-space-2);
}

/* --------------------------------------------------------------------------
   10. Responsive (mobil-first Pflichtpruefpunkte: 375 / 768 / 1280)
   -------------------------------------------------------------------------- */
@media (max-width: 768px) {
  .phinit-plugin { font-size: var(--phinit-text-base); }
  .phinit-grid { grid-template-columns: 1fr !important; }
  .phinit-steps { overflow-x: auto; -webkit-overflow-scrolling: touch; }
  .phinit-btn { width: 100%; justify-content: center; }
  .phinit-btn--link { width: auto; }
}

/* --------------------------------------------------------------------------
   11. Reduced Motion (verbindlich)
   -------------------------------------------------------------------------- */
@media (prefers-reduced-motion: reduce) {
  .phinit-plugin *,
  .phinit-plugin *::before,
  .phinit-plugin *::after {
    transition: none !important;
    animation: none !important;
  }
  .phinit-btn:active { transform: none; }
  .phinit-btn--link:hover .phinit-arrow { transform: none; }
}

/* ==========================================================================
   ENDE plugin-base.css
   Plugin-eigenes CSS darf NUR Layout-Anordnung ergaenzen
   (Grid-Spalten, Reihenfolge, plugin-spezifische Struktur).
   Jede visuelle Eigenschaft kommt aus den Tokens / Klassen oben.
   Im Zweifel: weglassen.
   ========================================================================== */