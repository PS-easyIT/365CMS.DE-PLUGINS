<?php
/**
 * Public Partial: M365 Tools content area only.
 *
 * @package CMS_M365TOOLS
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<style>
:root {
    --m365-tools-font-sans: var(--phinit-font-body, "IBM Plex Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif);
    --m365-tools-font-mono: var(--phinit-font-mono, "IBM Plex Mono", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace);
    --m365-tools-navy: var(--phinit-color-surface-alt, #0b1b34);
    --m365-tools-navy-soft: #13294d;
    --m365-tools-accent: var(--phinit-color-link, #1f6feb);
    --m365-tools-accent-dark: #1656bd;
    --m365-tools-accent-soft: color-mix(in srgb, var(--m365-tools-accent) 10%, transparent);
    --m365-tools-focus-ring: color-mix(in srgb, var(--m365-tools-accent) 46%, transparent);
    --m365-tools-bg: transparent;
    --m365-tools-surface: color-mix(in srgb, var(--phinit-color-surface, #ffffff) 94%, #fff7ed 6%);
    --m365-tools-surface-2: color-mix(in srgb, var(--phinit-color-surface, #ffffff) 84%, #f4ede4 16%);
    --m365-tools-surface-muted: color-mix(in srgb, var(--m365-tools-surface) 84%, #f6efe7 16%);
    --tag-bg: #eef1f6;
    --tag-text: #52617a;
    --tag-border: #e1e6ee;
    --m365-tools-tag-bg: var(--tag-bg, #eef1f6);
    --m365-tools-tag-text: var(--tag-text, #52617a);
    --m365-tools-tag-border: var(--tag-border, #e1e6ee);
    --m365-tools-ink: var(--phinit-color-ink, #14213d);
    --m365-tools-ink-soft: var(--phinit-color-ink-secondary, #4b5a72);
    --m365-tools-ink-faint: #8493a8;
    --m365-tools-line: color-mix(in srgb, var(--phinit-color-border, #e2e8f0) 88%, #eadfd2 12%);
    --m365-tools-line-strong: color-mix(in srgb, var(--m365-tools-line) 72%, var(--m365-tools-ink-soft) 28%);
    --m365-tools-c-lizenz: #1f6feb;
    --m365-tools-c-identitaet: #6c4adb;
    --m365-tools-c-schutz: #0f9d72;
    --m365-tools-c-service: #c2602f;
    --m365-tools-c-speicher: #2563eb;
    --m365-tools-c-netzwerk: #0d9488;
    --m365-tools-c-copilot: #7c3aed;
    --m365-tools-c-power: #c2602f;
    --m365-tools-c-migration: #1f6feb;
    --m365-tools-radius: 14px;
    --m365-tools-radius-lg: 18px;
    --m365-tools-radius-pill: 100px;
    --m365-tools-edge-gap: 25px;
    --m365-tools-shadow-card: 0 1px 2px rgba(42, 31, 20, 0.035), 0 8px 24px rgba(42, 31, 20, 0.045);
    --m365-tools-shadow-hover: 0 10px 28px rgba(42, 31, 20, 0.085);
}

.m365-tools,
.m365-tools * {
    box-sizing: border-box;
}

.m365-tools {
    background: transparent;
    color: var(--m365-tools-ink);
    font-family: var(--m365-tools-font-sans);
    line-height: 1.6;
    padding: 0 0 var(--m365-tools-edge-gap);
}

.m365-tools a {
    color: var(--m365-tools-accent);
    text-decoration: none;
}

.m365-tools svg {
    display: block;
}

.m365-tools [hidden] {
    display: none !important;
}

.m365-tools .wrap {
    margin: 0 auto;
    max-width: 1180px;
    padding: 0 20px;
    width: 100%;
}

.m365-tools .hero {
    padding: 0 0 38px;
}

.m365-tools .eyebrow {
    align-items: center;
    color: var(--m365-tools-accent);
    display: inline-flex;
    font-family: var(--m365-tools-font-mono);
    font-size: 11px;
    gap: 7px;
    letter-spacing: 0.12em;
    margin: 0 0 14px;
    text-transform: uppercase;
}

.m365-tools .eyebrow::before {
    background: var(--m365-tools-accent);
    border-radius: 2px;
    content: "";
    height: 2px;
    width: 22px;
}

.m365-tools .hero-grid {
    display: grid;
    gap: 28px;
}

.m365-tools h1,
.m365-tools h2,
.m365-tools h3,
.m365-tools p {
    margin-top: 0;
}

.m365-tools h1 {
    color: var(--m365-tools-ink);
    font-size: clamp(2rem, 8vw, 2.5rem);
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.15;
    margin-bottom: 12px;
}

.m365-tools .lead {
    color: var(--m365-tools-ink-soft);
    font-size: 17px;
    margin-bottom: 28px;
    max-width: 56ch;
}

.m365-tools .cta-btn {
    align-items: center;
    background: var(--m365-tools-accent);
    border: 1px solid var(--m365-tools-accent);
    border-radius: var(--m365-tools-radius-pill);
    color: #ffffff;
    display: inline-flex;
    font-size: 14px;
    font-weight: 600;
    gap: 8px;
    min-height: 44px;
    padding: 10px 22px;
    transition: background-color 0.15s ease, border-color 0.15s ease, transform 0.1s ease;
}

.m365-tools .cta-btn:hover,
.m365-tools .cta-btn:focus-visible {
    background: var(--m365-tools-accent-dark);
    border-color: var(--m365-tools-accent-dark);
}

.m365-tools .cta-btn:active {
    transform: scale(0.98);
}

.m365-tools .stats {
    display: grid;
    gap: 14px;
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.m365-tools .stat {
    background: var(--m365-tools-surface);
    border: 1px solid var(--m365-tools-line);
    border-radius: var(--m365-tools-radius);
    box-shadow: var(--m365-tools-shadow-card);
    min-width: 0;
    padding: 16px;
}

.m365-tools .stat .num {
    font-size: clamp(1.6rem, 7vw, 1.875rem);
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1;
}

.m365-tools .stat .lbl {
    color: var(--m365-tools-ink-faint);
    font-size: 11px;
    letter-spacing: 0.06em;
    margin-top: 6px;
    text-transform: uppercase;
}

.m365-tools .toolbar {
    padding: 8px 0 4px;
}

.m365-tools .search-label {
    color: var(--m365-tools-ink-soft);
    display: block;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 8px;
}

.m365-tools .search-box {
    align-items: center;
    background: var(--m365-tools-surface);
    border: 1px solid var(--m365-tools-line-strong);
    border-radius: var(--m365-tools-radius);
    display: flex;
    gap: 12px;
    min-height: 50px;
    padding: 0 16px;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.m365-tools .search-box:focus-within {
    border-color: var(--m365-tools-accent);
    box-shadow: 0 0 0 3px var(--m365-tools-focus-ring), var(--m365-tools-shadow-card);
}

.m365-tools .search-box svg {
    color: var(--m365-tools-ink-faint);
    flex: 0 0 auto;
    height: 20px;
    width: 20px;
}

.m365-tools .search-box input {
    background: transparent;
    border: 0;
    color: var(--m365-tools-ink);
    flex: 1 1 auto;
    font: inherit;
    font-size: 15px;
    min-height: 48px;
    min-width: 0;
    outline: 0;
}

.m365-tools .search-box input:focus-visible {
    border-radius: 8px;
    outline: 2px solid var(--m365-tools-focus-ring);
    outline-offset: 3px;
}

.m365-tools .filters {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    margin-top: 16px;
}

.m365-tools .chip {
    background: var(--m365-tools-surface);
    border: 1px solid var(--m365-tools-line-strong);
    border-radius: var(--m365-tools-radius-pill);
    color: var(--m365-tools-ink-soft);
    cursor: pointer;
    font: inherit;
    font-size: 13.5px;
    font-weight: 500;
    min-height: 38px;
    padding: 7px 16px;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.m365-tools .chip:hover,
.m365-tools .chip:focus-visible {
    border-color: var(--m365-tools-accent);
    color: var(--m365-tools-accent);
}

.m365-tools .chip:focus-visible {
    box-shadow: none;
    outline: 3px solid var(--m365-tools-focus-ring);
    outline-offset: 3px;
}

.m365-tools .chip .cnt {
    font-family: var(--m365-tools-font-mono);
    font-size: 11px;
    margin-left: 5px;
    opacity: 0.62;
}

.m365-tools .chip.active {
    background: var(--m365-tools-accent);
    border-color: var(--m365-tools-accent);
    color: #ffffff;
}

.m365-tools .chip.active .cnt {
    opacity: 0.84;
}

.m365-tools .section {
    padding: 32px 0 24px;
}

.m365-tools .section-head {
    align-items: baseline;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 6px;
}

.m365-tools .section-head h2,
.m365-tools .cat-head h2 {
    color: var(--m365-tools-ink);
    font-size: 24px;
    font-weight: 700;
    letter-spacing: -0.01em;
    margin: 0;
}

.m365-tools .section-tag {
    color: var(--m365-tools-ink-faint);
    font-family: var(--m365-tools-font-mono);
    font-size: 11px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.m365-tools .section-sub {
    color: var(--m365-tools-ink-soft);
    font-size: 15px;
    margin-bottom: 22px;
    max-width: 82ch;
}

.m365-tools .compass {
    display: grid;
    gap: 10px;
    grid-template-columns: 1fr;
}

.m365-tools .compass-card {
    background: color-mix(in srgb, var(--m365-tools-surface-muted) 76%, transparent);
    border: 1px solid color-mix(in srgb, var(--m365-tools-line) 72%, transparent);
    border-radius: var(--m365-tools-radius);
    box-shadow: none;
    padding: 12px;
    transition: border-color 0.15s ease, transform 0.15s ease;
}

.m365-tools .compass-card:hover {
    border-color: var(--m365-tools-line);
    transform: translateY(-1px);
}

.m365-tools .compass-card .ico,
.m365-tools .cat-head .badge,
.m365-tools .card-ico {
    align-items: center;
    display: flex;
    flex: 0 0 auto;
    justify-content: center;
}

.m365-tools .compass-card .ico {
    border-radius: 9px;
    height: 30px;
    margin-bottom: 8px;
    width: 30px;
}

.m365-tools .compass-card .ico svg {
    height: 16px;
    width: 16px;
}

.m365-tools .compass-card .t {
    color: var(--m365-tools-ink);
    font-size: 13.5px;
    font-weight: 600;
    margin-bottom: 3px;
}

.m365-tools .compass-card .d {
    color: var(--m365-tools-ink-soft);
    font-size: 12px;
    line-height: 1.5;
    margin-bottom: 0;
}

.m365-tools .catalog {
    display: grid;
    gap: 18px;
}

.m365-tools .cat-block {
    padding: 28px 0 0;
    scroll-margin-top: 80px;
}

.m365-tools .cat-head {
    align-items: center;
    display: flex;
    gap: 12px;
    margin-bottom: 4px;
}

.m365-tools .cat-head .badge {
    border-radius: 9px;
    height: 32px;
    width: 32px;
}

.m365-tools .cat-head .badge svg {
    height: 18px;
    width: 18px;
}

.m365-tools .cat-head h2 {
    font-size: 22px;
}

.m365-tools .modcount {
    color: var(--m365-tools-ink-faint);
    font-family: var(--m365-tools-font-mono);
    font-size: 11px;
    letter-spacing: 0.08em;
    margin-left: auto;
    text-transform: uppercase;
    white-space: nowrap;
}

.m365-tools .cat-sub {
    color: var(--m365-tools-ink-soft);
    font-size: 14.5px;
    margin-bottom: 20px;
    padding-left: 44px;
}

.m365-tools .tool-grid {
    display: grid;
    gap: 16px;
    grid-template-columns: 1fr;
}

.m365-tools .cat-block[data-cat="migration"] .tool-grid,
.m365-tools .cat-block[data-cat="power"] .tool-grid,
.m365-tools .cat-block[data-cat="teams"] .tool-grid {
    background: transparent;
    border: 0;
    box-shadow: none;
    justify-content: start;
    max-width: 380px;
    padding: 0;
}

.m365-tools .tool-card {
    background: var(--m365-tools-surface);
    border: 1px solid var(--m365-tools-line);
    border-radius: var(--m365-tools-radius-lg);
    box-shadow: var(--m365-tools-shadow-card);
    display: flex;
    flex-direction: column;
    min-width: 0;
    padding: 22px;
    position: relative;
    transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
}

.m365-tools .tool-card:hover,
.m365-tools .tool-card:focus-within {
    border-color: var(--m365-tools-line-strong);
    box-shadow: var(--m365-tools-shadow-hover);
    transform: translateY(-3px);
}

.m365-tools .cat-block[data-cat="migration"] .tool-card,
.m365-tools .cat-block[data-cat="power"] .tool-card,
.m365-tools .cat-block[data-cat="teams"] .tool-card {
    max-width: 380px;
    width: 100%;
}

.m365-tools .card-head {
    align-items: center;
    display: flex;
    gap: 12px;
    margin-bottom: 12px;
    min-width: 0;
}

.m365-tools .card-ico {
    background: var(--m365-tools-accent-soft);
    border-radius: var(--m365-tools-radius);
    color: var(--m365-tools-accent);
    height: 38px;
    width: 38px;
}

.m365-tools .card-ico svg {
    height: 20px;
    width: 20px;
}

.m365-tools .tool-card h3 {
    color: var(--m365-tools-ink);
    font-size: 16px;
    font-weight: 600;
    line-height: 1.35;
    margin: 0;
    padding-right: 64px;
}

.m365-tools .desc {
    color: var(--m365-tools-ink-soft);
    font-size: 13.5px;
    line-height: 1.55;
    margin-bottom: 16px;
}

.m365-tools .tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 16px;
}

.m365-tools .tag {
    background: var(--m365-tools-tag-bg);
    border: 1px solid var(--m365-tools-tag-border);
    border-radius: var(--m365-tools-radius-pill);
    color: var(--m365-tools-tag-text);
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.01em;
    line-height: 1.25;
    padding: 3px 10px;
}

.m365-tools .open-link {
    align-items: center;
    align-self: flex-start;
    color: var(--m365-tools-accent-dark);
    display: inline-flex;
    font-size: 15px;
    font-weight: 700;
    gap: 7px;
    margin-top: auto;
    min-height: 38px;
    padding: 5px 2px;
    text-decoration: underline;
    text-decoration-color: color-mix(in srgb, var(--m365-tools-accent) 36%, transparent);
    text-decoration-thickness: 2px;
    text-underline-offset: 5px;
    transition: color 0.15s ease, text-decoration-color 0.15s ease;
}

.m365-tools .open-link:hover,
.m365-tools .open-link:focus-visible {
    color: var(--m365-tools-accent);
    text-decoration-color: currentColor;
}

.m365-tools .open-link svg {
    height: 16px;
    transition: transform 0.15s ease;
    width: 16px;
}

.m365-tools .tool-card:hover .open-link svg,
.m365-tools .open-link:focus-visible svg {
    transform: translateX(3px);
}

.m365-tools .open-link:focus-visible {
    border-radius: 8px;
    box-shadow: none;
    outline: 3px solid var(--m365-tools-focus-ring);
    outline-offset: 3px;
}

.m365-tools .flag {
    border-radius: var(--m365-tools-radius-pill);
    font-family: var(--m365-tools-font-mono);
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 0.06em;
    padding: 4px 10px;
    position: absolute;
    right: 18px;
    text-transform: uppercase;
    top: 18px;
}

.m365-tools .flag.neu {
    background: #fff4e0;
    color: #9b5c00;
}

.m365-tools .flag.beliebt {
    background: #e6f9f0;
    color: #0a6b49;
}

.m365-tools .no-results {
    border: 1px dashed var(--m365-tools-line-strong);
    border-radius: var(--m365-tools-radius-lg);
    color: var(--m365-tools-ink-soft);
    margin-top: 34px;
    padding: 34px 22px;
    text-align: center;
}

.m365-tools .no-results-title {
    color: var(--m365-tools-ink);
    font-weight: 700;
    margin-bottom: 4px;
}

.m365-tools .no-results p:last-child {
    margin-bottom: 0;
}

.m365-tools a:focus-visible,
.m365-tools button:focus-visible,
.m365-tools input:focus-visible {
    outline: 3px solid var(--m365-tools-focus-ring);
    outline-offset: 2px;
}

@media (min-width: 520px) {
    .m365-tools .compass {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (min-width: 768px) {
    .m365-tools {
        padding: 0 0 var(--m365-tools-edge-gap);
    }

    .m365-tools .wrap {
        padding: 0 24px;
    }

    .m365-tools .hero-grid {
        align-items: end;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 32px;
    }

    .m365-tools .stats {
        display: flex;
    }

    .m365-tools .stat {
        min-width: 110px;
        padding: 16px 22px;
    }

    .m365-tools .compass {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .m365-tools .tool-grid {
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    }

    .m365-tools .cat-block[data-cat="migration"] .tool-grid,
    .m365-tools .cat-block[data-cat="power"] .tool-grid,
    .m365-tools .cat-block[data-cat="teams"] .tool-grid {
        grid-template-columns: minmax(0, min(100%, 380px));
        justify-content: start;
    }
}

@media (prefers-reduced-motion: reduce) {
    .m365-tools,
    .m365-tools * {
        scroll-behavior: auto !important;
        transition: none !important;
    }
}
</style>

<section class="m365-tools" id="m365tools-landing" data-m365-tools aria-labelledby="m365-tools-title">
    <section class="hero" aria-labelledby="m365-tools-title">
        <div class="wrap">
            <p class="eyebrow">Rechner &amp; Tools</p>
            <div class="hero-grid">
                <header>
                    <h1 id="m365-tools-title">M365 Tools</h1>
                    <p class="lead">Eine kuratierte Sammlung für Microsoft-365-Lizenzierung, Kosten, Speicher, Copilot, Telefonie, Migration und Betrieb.</p>
                    <a class="cta-btn" href="/kontakt">
                        Kontakt aufnehmen
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                </header>
                <dl class="stats" aria-label="M365 Tools Kennzahlen">
                    <div class="stat"><dd class="num">19</dd><dt class="lbl">Module</dt></div>
                    <div class="stat"><dd class="num">19</dd><dt class="lbl">Live</dt></div>
                    <div class="stat"><dd class="num">9</dd><dt class="lbl">Review</dt></div>
                </dl>
            </div>
        </div>
    </section>

    <section class="toolbar" aria-labelledby="m365-tools-filter-title">
        <div class="wrap">
            <h2 id="m365-tools-filter-title" class="search-label">Tools suchen und filtern</h2>
            <label class="search-box" for="m365ToolsSearch">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <input type="search" id="m365ToolsSearch" autocomplete="off" placeholder="Nach Tool, Thema oder Kategorie suchen …" aria-describedby="m365-tools-search-help">
            </label>
            <p id="m365-tools-search-help" class="section-tag">Suche und Kategorie wirken gemeinsam.</p>
            <nav class="filters" aria-label="M365 Tool Kategorien">
                <button type="button" class="chip active" data-cat="all" aria-pressed="true">Alle <span class="cnt">19</span></button>
                <button type="button" class="chip" data-cat="copilot" aria-pressed="false">Copilot <span class="cnt">3</span></button>
                <button type="button" class="chip" data-cat="exchange" aria-pressed="false">Exchange <span class="cnt">2</span></button>
                <button type="button" class="chip" data-cat="lizenzen" aria-pressed="false">Lizenzen <span class="cnt">9</span></button>
                <button type="button" class="chip" data-cat="migration" aria-pressed="false">Migration <span class="cnt">1</span></button>
                <button type="button" class="chip" data-cat="power" aria-pressed="false">Power Platform <span class="cnt">1</span></button>
                <button type="button" class="chip" data-cat="speicher" aria-pressed="false">Speicher <span class="cnt">2</span></button>
                <button type="button" class="chip" data-cat="teams" aria-pressed="false">Teams <span class="cnt">1</span></button>
            </nav>
        </div>
    </section>

    <section class="section" id="kompass" aria-labelledby="m365-kompass-title">
        <div class="wrap">
            <div class="section-head">
                <h2 id="m365-kompass-title">Best-Practice-Kompass</h2>
                <span class="section-tag">Querschnittsüberblick</span>
            </div>
            <p class="section-sub">Quereinstieg für Lizenzierung, Zugriff, Schutz, Servicegrenzen, Netzwerk, Performance, Copilot, Power Platform, Backup und Migration, gegen offizielle Microsoft-Quellen geprüft.</p>
            <div class="compass">
                <article class="compass-card"><div class="ico" style="background:#e8f1fe;color:var(--m365-tools-c-lizenz)" role="img" aria-label="Lizenz und Kosten"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 14l2 2 4-4"/><rect x="3" y="4" width="18" height="16" rx="2"/></svg></div><h3 class="t">Lizenz &amp; Kosten</h3><p class="d">Nutzerbasierte Add-ons und Renewal-Fenster prüfen.</p></article>
                <article class="compass-card"><div class="ico" style="background:#eee9fc;color:var(--m365-tools-c-identitaet)" role="img" aria-label="Identität und Zugriff"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg></div><h3 class="t">Identität &amp; Zugriff</h3><p class="d">Rechte, MFA und Conditional Access planen.</p></article>
                <article class="compass-card"><div class="ico" style="background:#e6f6ef;color:var(--m365-tools-c-schutz)" role="img" aria-label="Schutz und Compliance"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3l8 3v6c0 5-4 8-8 9-4-1-8-4-8-9V6z"/></svg></div><h3 class="t">Schutz &amp; Compliance</h3><p class="d">Defender, Purview und Aufbewahrung verbinden.</p></article>
                <article class="compass-card"><div class="ico" style="background:#fbeee4;color:var(--m365-tools-c-service)" role="img" aria-label="Servicegrenzen"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="6" rx="1"/><rect x="3" y="14" width="18" height="6" rx="1"/></svg></div><h3 class="t">Servicegrenzen</h3><p class="d">Mailbox- und Speicher-Limits gegenprüfen.</p></article>
                <article class="compass-card"><div class="ico" style="background:#e7eefe;color:var(--m365-tools-c-speicher)" role="img" aria-label="Speicher und Backup"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/></svg></div><h3 class="t">Speicher &amp; Backup</h3><p class="d">Pool, Schutzumfang und Restore-Ziele klären.</p></article>
                <article class="compass-card"><div class="ico" style="background:#e3f4f1;color:var(--m365-tools-c-netzwerk)" role="img" aria-label="Netzwerk und Performance"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 12h4l3 8 4-16 3 8h4"/></svg></div><h3 class="t">Netzwerk &amp; Performance</h3><p class="d">Latenz, WSS und Basiswerte messen.</p></article>
                <article class="compass-card"><div class="ico" style="background:#f0e9fd;color:var(--m365-tools-c-copilot)" role="img" aria-label="Copilot und KI"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3l1.8 4.2L18 9l-4.2 1.8L12 15l-1.8-4.2L6 9l4.2-1.8z"/><path d="M19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9z"/></svg></div><h3 class="t">Copilot &amp; KI</h3><p class="d">Datenzugriff und Pilotumfang absichern.</p></article>
                <article class="compass-card"><div class="ico" style="background:#fbeee4;color:var(--m365-tools-c-power)" role="img" aria-label="Power Platform Betrieb"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M13 2L3 14h7l-1 8 10-12h-7z"/></svg></div><h3 class="t">Power Platform Betrieb</h3><p class="d">ALM, Monitoring und Request-Kontingente.</p></article>
                <article class="compass-card"><div class="ico" style="background:#e8f1fe;color:var(--m365-tools-c-migration)" role="img" aria-label="Migration und Betrieb"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h13l-3-3M20 17H7l3 3"/></svg></div><h3 class="t">Migration &amp; Betrieb</h3><p class="d">Cutover, Hypercare und Rollback planen.</p></article>
            </div>
        </div>
    </section>

    <div class="wrap catalog" id="catalog">
        <section class="cat-block" data-cat="copilot" aria-labelledby="cat-copilot-title">
            <div class="cat-head"><span class="badge" style="background:#f0e9fd;color:var(--m365-tools-c-copilot)" role="img" aria-label="Kategorie Copilot"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3l1.8 4.2L18 9l-4.2 1.8L12 15l-1.8-4.2L6 9l4.2-1.8z"/></svg></span><h2 id="cat-copilot-title">Copilot</h2><span class="modcount">3 Module</span></div>
            <p class="cat-sub">Copilot-Eignung, Pilotierung, ROI und KI-Angebote bewerten.</p>
            <div class="tool-grid">
                <?php /* Dynamic CMS output: foreach ($tools['copilot'] as $tool) would replace this hardcoded tool card. */ ?>
                <article class="tool-card" data-cat="copilot" data-name="ai pack copilot pro vergleich" data-tags="lizenz kosten copilot ki">
                    <div class="card-head"><span class="card-ico" role="img" aria-label="Vergleichstool"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M16 3l4 4-4 4M8 21l-4-4 4-4M20 7H8M4 17h12"/></svg></span><h3>AI Pack vs. Copilot Pro Vergleich</h3></div>
                    <p class="desc">Vergleicht Copilot Chat, Microsoft 365 Copilot, Special-Copilots und Copilot Studio nach Use Case.</p>
                    <div class="tags"><span class="tag">Lizenz &amp; Kosten</span><span class="tag">Copilot &amp; KI</span></div>
                    <a class="open-link" href="/ai-pack-vs-copilot-pro">Tool öffnen <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </article>
                <?php /* Dynamic CMS output: foreach ($tools['copilot'] as $tool) would replace this hardcoded tool card. */ ?>
                <article class="tool-card" data-cat="copilot" data-name="copilot pilot phase rechner" data-tags="identitaet zugriff netzwerk performance">
                    <div class="card-head"><span class="card-ico" role="img" aria-label="Planungstool"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h8M8 11h8M8 15h5"/></svg></span><h3>Copilot Pilot-Phase-Rechner</h3></div>
                    <p class="desc">Erstellt Pilotgröße, Dauer, Budgetrahmen, Champion-Bedarf und Governance-Schritte für M365 Copilot.</p>
                    <div class="tags"><span class="tag">Identität &amp; Zugriff</span><span class="tag">Netzwerk &amp; Performance</span></div>
                    <a class="open-link" href="/copilot-pilot-rechner">Rechner starten <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </article>
                <?php /* Dynamic CMS output: foreach ($tools['copilot'] as $tool) would replace this hardcoded tool card. */ ?>
                <article class="tool-card" data-cat="copilot" data-name="copilot roi rechner" data-tags="copilot ki lizenz kosten migration betrieb">
                    <span class="flag neu">Neu</span>
                    <div class="card-head"><span class="card-ico" role="img" aria-label="ROI-Rechner"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19V5M4 19h16M8 16v-5M12 16V9M16 16v-8"/></svg></span><h3>Copilot ROI-Rechner</h3></div>
                    <p class="desc">Berechnet Business Case, Break-even-Minuten und eine Pilot- oder Rollout-Empfehlung für M365 Copilot.</p>
                    <div class="tags"><span class="tag">Copilot &amp; KI</span><span class="tag">Lizenz &amp; Kosten</span><span class="tag">Migration &amp; Betrieb</span></div>
                    <a class="open-link" href="/copilot-roi-rechner">Rechner starten <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </article>
            </div>
        </section>

        <section class="cat-block" data-cat="exchange" aria-labelledby="cat-exchange-title">
            <div class="cat-head"><span class="badge" style="background:#e8f1fe;color:var(--m365-tools-c-lizenz)" role="img" aria-label="Kategorie Exchange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg></span><h2 id="cat-exchange-title">Exchange</h2><span class="modcount">2 Module</span></div>
            <p class="cat-sub">Mailboxen, Archivierung und Exchange-Modernisierung sauber planen.</p>
            <div class="tool-grid">
                <?php /* Dynamic CMS output: foreach ($tools['exchange'] as $tool) would replace this hardcoded tool card. */ ?>
                <article class="tool-card" data-cat="exchange" data-name="on-prem exchange online roi" data-tags="migration betrieb servicegrenzen netzwerk performance">
                    <div class="card-head"><span class="card-ico" role="img" aria-label="Migrationstool"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h13l-3-3M20 17H7l3 3"/></svg></span><h3>On-Prem Exchange zu Exchange Online ROI</h3></div>
                    <p class="desc">Berechnet Vollkosten, Break-even, Migrationspfad und Management-Fact für die Migration nach Exchange Online.</p>
                    <div class="tags"><span class="tag">Migration &amp; Betrieb</span><span class="tag">Servicegrenzen</span><span class="tag">Netzwerk &amp; Performance</span></div>
                    <a class="open-link" href="/exchange-online-roi">Tool öffnen <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </article>
                <?php /* Dynamic CMS output: foreach ($tools['exchange'] as $tool) would replace this hardcoded tool card. */ ?>
                <article class="tool-card" data-cat="exchange" data-name="archive mailbox rechner" data-tags="servicegrenzen speicher backup schutz compliance">
                    <div class="card-head"><span class="card-ico" role="img" aria-label="Archiv-Rechner"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="5" rx="1"/><path d="M5 9v9a2 2 0 002 2h10a2 2 0 002-2V9M10 13h4"/></svg></span><h3>Archive Mailbox Rechner</h3></div>
                    <p class="desc">Prüft Archivgröße, Auto-expanding Archive, Shared-Mailbox-Sonderfälle, Hold und passende Lizenzpfade.</p>
                    <div class="tags"><span class="tag">Servicegrenzen</span><span class="tag">Speicher &amp; Backup</span><span class="tag">Schutz &amp; Compliance</span></div>
                    <a class="open-link" href="/m365-archive-mailbox-rechner">Rechner starten <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </article>
            </div>
        </section>

        <section class="cat-block" data-cat="lizenzen" aria-labelledby="cat-lizenzen-title">
            <div class="cat-head"><span class="badge" style="background:#e8f1fe;color:var(--m365-tools-c-lizenz)" role="img" aria-label="Kategorie Lizenzen"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 14l2 2 4-4"/><rect x="3" y="4" width="18" height="16" rx="2"/></svg></span><h2 id="cat-lizenzen-title">Lizenzen</h2><span class="modcount">9 Module</span></div>
            <p class="cat-sub">Lizenzmodelle, Add-ons, Audits und Sparpfade schnell einordnen.</p>
            <div class="tool-grid">
                <?php /* Dynamic CMS output: foreach ($tools['lizenzen'] as $tool) would replace this hardcoded tool card. */ ?>
                <article class="tool-card" data-cat="lizenzen" data-name="lizenz audit checkliste" data-tags="lizenz kosten netzwerk performance">
                    <span class="flag beliebt">Beliebt</span>
                    <div class="card-head"><span class="card-ico" role="img" aria-label="Checkliste"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 11l2 2 4-4"/><rect x="4" y="3" width="16" height="18" rx="2"/></svg></span><h3>Lizenz-Audit-Checkliste</h3></div>
                    <p class="desc">Interaktive Microsoft-365-Auditliste für Lizenzbestand, Offboarding, Shared Mailboxes, Copilot und Renewal.</p>
                    <div class="tags"><span class="tag">Lizenz &amp; Kosten</span><span class="tag">Netzwerk &amp; Performance</span></div>
                    <a class="open-link" href="/m365-lizenz-audit-checkliste">Checkliste laden <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </article>
                <?php /* Dynamic CMS output: foreach ($tools['lizenzen'] as $tool) would replace this hardcoded tool card. */ ?>
                <article class="tool-card" data-cat="lizenzen" data-name="m365 lizenzvergleich" data-tags="lizenz kosten servicegrenzen copilot ki">
                    <div class="card-head"><span class="card-ico" role="img" aria-label="Lizenzvergleich"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19V5M4 19h16M9 16V8M14 16v-4M19 16V6"/></svg></span><h3>M365-Lizenzvergleich</h3></div>
                    <p class="desc">Vergleicht M365-Pläne nach Desktop-Apps, Mail, Teams, Copilot, Security und Zusatzdiensten.</p>
                    <div class="tags"><span class="tag">Lizenz &amp; Kosten</span><span class="tag">Servicegrenzen</span><span class="tag">Copilot &amp; KI</span></div>
                    <a class="open-link" href="/m365-lizenzvergleich">Tool öffnen <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </article>
                <?php /* Dynamic CMS output: foreach ($tools['lizenzen'] as $tool) would replace this hardcoded tool card. */ ?>
                <article class="tool-card" data-cat="lizenzen" data-name="m365 add-on konfigurator" data-tags="lizenz kosten schutz compliance speicher backup">
                    <div class="card-head"><span class="card-ico" role="img" aria-label="Add-on-Konfigurator"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></span><h3>M365 Add-On-Konfigurator</h3></div>
                    <p class="desc">Prüft Add-ons, Prerequisites, Redundanzen, Verbrauchsanteile und Upgrade-Alternativen für M365.</p>
                    <div class="tags"><span class="tag">Lizenz &amp; Kosten</span><span class="tag">Schutz &amp; Compliance</span><span class="tag">Speicher &amp; Backup</span></div>
                    <a class="open-link" href="/m365-add-on-konfigurator">Tool öffnen <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </article>
                <?php /* Dynamic CMS output: foreach ($tools['lizenzen'] as $tool) would replace this hardcoded tool card. */ ?>
                <article class="tool-card" data-cat="lizenzen" data-name="m365 lizenz berater" data-tags="lizenz kosten identitaet zugriff">
                    <span class="flag beliebt">Beliebt</span>
                    <div class="card-head"><span class="card-ico" role="img" aria-label="Lizenz-Berater"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M5 21v-1a7 7 0 0114 0v1"/></svg></span><h3>M365-Lizenz-Berater</h3></div>
                    <p class="desc">Empfiehlt Basislizenzen, Add-ons und Mischmodelle für konkrete Microsoft-365-Anforderungen.</p>
                    <div class="tags"><span class="tag">Lizenz &amp; Kosten</span><span class="tag">Identität &amp; Zugriff</span></div>
                    <a class="open-link" href="/m365-lizenzberater">Tool öffnen <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </article>
                <?php /* Dynamic CMS output: foreach ($tools['lizenzen'] as $tool) would replace this hardcoded tool card. */ ?>
                <article class="tool-card" data-cat="lizenzen" data-name="annual vs monthly commitment rechner" data-tags="lizenz kosten migration betrieb">
                    <div class="card-head"><span class="card-ico" role="img" aria-label="Commitment-Rechner"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/></svg></span><h3>Annual vs. Monthly Commitment Rechner</h3></div>
                    <p class="desc">Vergleicht Monatslaufzeit, Jahresbindung, jährliche Abrechnung und Split-Strategie für variable M365-Seats.</p>
                    <div class="tags"><span class="tag">Lizenz &amp; Kosten</span><span class="tag">Migration &amp; Betrieb</span></div>
                    <a class="open-link" href="/m365-jahresvertrag-vs-monatsvertrag">Rechner starten <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </article>
                <?php /* Dynamic CMS output: foreach ($tools['lizenzen'] as $tool) would replace this hardcoded tool card. */ ?>
                <article class="tool-card" data-cat="lizenzen" data-name="frontline worker lizenz eignung check" data-tags="identitaet zugriff schutz compliance">
                    <div class="card-head"><span class="card-ico" role="img" aria-label="Frontline-Check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 20a6 6 0 0112 0M8 4a4 4 0 110 8 4 4 0 010-8M16 14a5 5 0 016 6"/></svg></span><h3>Frontline Worker Lizenz-Eignung-Check</h3></div>
                    <p class="desc">Prüft F1, F3, Mischmodell oder Enterprise-Bedarf für mobile, schichtbasierte und deskless Nutzergruppen.</p>
                    <div class="tags"><span class="tag">Identität &amp; Zugriff</span><span class="tag">Schutz &amp; Compliance</span></div>
                    <a class="open-link" href="/frontline-worker-lizenz-check">Tool öffnen <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </article>
                <?php /* Dynamic CMS output: foreach ($tools['lizenzen'] as $tool) would replace this hardcoded tool card. */ ?>
                <article class="tool-card" data-cat="lizenzen" data-name="microsoft preiserhoehung tracker" data-tags="lizenz kosten migration betrieb">
                    <div class="card-head"><span class="card-ico" role="img" aria-label="Preistracker"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 17l6-6 4 4 7-7M14 8h6v6"/></svg></span><h3>Microsoft-Preiserhöhung-Tracker</h3></div>
                    <p class="desc">Bewertet offizielle Microsoft-Preis-, Packaging- und Renewal-Ereignisse mit Budget- und Forecast-Trennung.</p>
                    <div class="tags"><span class="tag">Lizenz &amp; Kosten</span><span class="tag">Migration &amp; Betrieb</span></div>
                    <a class="open-link" href="/microsoft-preiserhoehung-tracker">Tool öffnen <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </article>
                <?php /* Dynamic CMS output: foreach ($tools['lizenzen'] as $tool) would replace this hardcoded tool card. */ ?>
                <article class="tool-card" data-cat="lizenzen" data-name="shared mailbox vs lizenz rechner" data-tags="lizenz kosten servicegrenzen schutz compliance">
                    <div class="card-head"><span class="card-ico" role="img" aria-label="Mailbox-Rechner"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg></span><h3>Shared-Mailbox vs. Lizenz-Rechner</h3></div>
                    <p class="desc">Prüft Eignung, Lizenzpflicht und Kostenanteil für Shared Mailboxes, inkl. 50-GB-Grenze und Hold-Sonderfällen.</p>
                    <div class="tags"><span class="tag">Lizenz &amp; Kosten</span><span class="tag">Servicegrenzen</span><span class="tag">Schutz &amp; Compliance</span></div>
                    <a class="open-link" href="/shared-mailbox-vs-lizenz">Rechner starten <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </article>
                <?php /* Dynamic CMS output: foreach ($tools['lizenzen'] as $tool) would replace this hardcoded tool card. */ ?>
                <article class="tool-card" data-cat="lizenzen" data-name="copilot lizenz pflicht checker" data-tags="copilot ki identitaet zugriff netzwerk performance">
                    <div class="card-head"><span class="card-ico" role="img" aria-label="Copilot-Lizenzprüfung"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3l8 3v6c0 5-4 8-8 9-4-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/></svg></span><h3>Copilot Lizenz-Pflicht-Checker</h3></div>
                    <p class="desc">Prüft Basislizenz, Copilot-Chat-Status und technische Readiness für Microsoft 365 Copilot.</p>
                    <div class="tags"><span class="tag">Copilot &amp; KI</span><span class="tag">Identität &amp; Zugriff</span><span class="tag">Netzwerk &amp; Performance</span></div>
                    <a class="open-link" href="/copilot-lizenz-check">Tool öffnen <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </article>
            </div>
        </section>

        <section class="cat-block" data-cat="migration" aria-labelledby="cat-migration-title">
            <div class="cat-head"><span class="badge" style="background:#e8f1fe;color:var(--m365-tools-c-migration)" role="img" aria-label="Kategorie Migration"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h13l-3-3M20 17H7l3 3"/></svg></span><h2 id="cat-migration-title">Migration</h2><span class="modcount">1 Modul</span></div>
            <p class="cat-sub">Umzugs- und TCO-Szenarien mit Kosten, Aufwand und Break-even bewerten.</p>
            <div class="tool-grid">
                <?php /* Dynamic CMS output: foreach ($tools['migration'] as $tool) would replace this hardcoded tool card. */ ?>
                <article class="tool-card" data-cat="migration" data-name="google workspace m365 tco rechner" data-tags="lizenz kosten netzwerk performance">
                    <div class="card-head"><span class="card-ico" role="img" aria-label="TCO-Rechner"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h13l-3-3M20 17H7l3 3"/></svg></span><h3>Google Workspace → M365 TCO-Rechner</h3></div>
                    <p class="desc">Vergleicht Google Workspace und Microsoft 365 über Lizenzkosten, Migration, Schulung und Aufwand.</p>
                    <div class="tags"><span class="tag">Lizenz &amp; Kosten</span><span class="tag">Netzwerk &amp; Performance</span></div>
                    <a class="open-link" href="/google-workspace-zu-m365-tco">Rechner starten <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </article>
            </div>
        </section>

        <section class="cat-block" data-cat="power" aria-labelledby="cat-power-title">
            <div class="cat-head"><span class="badge" style="background:#fbeee4;color:var(--m365-tools-c-power)" role="img" aria-label="Kategorie Power Platform"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M13 2L3 14h7l-1 8 10-12h-7z"/></svg></span><h2 id="cat-power-title">Power Platform</h2><span class="modcount">1 Modul</span></div>
            <p class="cat-sub">Power Apps, Automate, Dataverse, Credits und Governance realistisch kalkulieren.</p>
            <div class="tool-grid">
                <?php /* Dynamic CMS output: foreach ($tools['power'] as $tool) would replace this hardcoded tool card. */ ?>
                <article class="tool-card" data-cat="power" data-name="power platform kosten kalkulator" data-tags="power platform betrieb identitaet zugriff schutz compliance netzwerk performance">
                    <div class="card-head"><span class="card-ico" role="img" aria-label="Power-Platform-Kalkulator"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M13 2L3 14h7l-1 8 10-12h-7z"/></svg></span><h3>Power Platform Kosten-Kalkulator</h3></div>
                    <p class="desc">Bewertet Power Apps, Power Automate, Dataverse, Power Pages, Copilot Studio, Credits, Capacity und Governance.</p>
                    <div class="tags"><span class="tag">Power Platform Betrieb</span><span class="tag">Identität &amp; Zugriff</span><span class="tag">Schutz &amp; Compliance</span><span class="tag">Netzwerk &amp; Performance</span></div>
                    <a class="open-link" href="/power-platform-kosten-kalkulator">Tool öffnen <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </article>
            </div>
        </section>

        <section class="cat-block" data-cat="speicher" aria-labelledby="cat-speicher-title">
            <div class="cat-head"><span class="badge" style="background:#e7eefe;color:var(--m365-tools-c-speicher)" role="img" aria-label="Kategorie Speicher"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/></svg></span><h2 id="cat-speicher-title">Speicher</h2><span class="modcount">2 Module</span></div>
            <p class="cat-sub">SharePoint, OneDrive, Exchange und Backup-Speicherbedarf greifbar machen.</p>
            <div class="tool-grid">
                <?php /* Dynamic CMS output: foreach ($tools['speicher'] as $tool) would replace this hardcoded tool card. */ ?>
                <article class="tool-card" data-cat="speicher" data-name="m365 storage bedarf rechner" data-tags="speicher backup servicegrenzen netzwerk performance">
                    <div class="card-head"><span class="card-ico" role="img" aria-label="Storage-Rechner"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/></svg></span><h3>M365 Storage-Bedarf-Rechner</h3></div>
                    <p class="desc">Berechnet SharePoint-Pool, OneDrive-Quotas, Exchange-Postfächer, Archivbedarf, Wachstum und Change-Potenzial.</p>
                    <div class="tags"><span class="tag">Speicher &amp; Backup</span><span class="tag">Servicegrenzen</span><span class="tag">Netzwerk &amp; Performance</span></div>
                    <a class="open-link" href="/m365-storage-bedarfsrechner">Rechner starten <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </article>
                <?php /* Dynamic CMS output: foreach ($tools['speicher'] as $tool) would replace this hardcoded tool card. */ ?>
                <article class="tool-card" data-cat="speicher" data-name="m365 backup kosten rechner" data-tags="speicher backup schutz compliance servicegrenzen">
                    <div class="card-head"><span class="card-ico" role="img" aria-label="Backup-Rechner"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12a9 9 0 11-3-6.7M21 3v5h-5"/></svg></span><h3>M365 Backup-Kosten-Rechner</h3></div>
                    <p class="desc">Berechnet Microsoft-365-Backup-Kosten pro geschütztem GB und vergleicht Provider nach Schutzklassen.</p>
                    <div class="tags"><span class="tag">Speicher &amp; Backup</span><span class="tag">Schutz &amp; Compliance</span><span class="tag">Servicegrenzen</span></div>
                    <a class="open-link" href="/m365-backup-kostenrechner">Rechner starten <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </article>
            </div>
        </section>

        <section class="cat-block" data-cat="teams" aria-labelledby="cat-teams-title">
            <div class="cat-head"><span class="badge" style="background:#f0e9fd;color:var(--m365-tools-c-copilot)" role="img" aria-label="Kategorie Teams"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 20a6 6 0 0112 0M8 4a4 4 0 110 8 4 4 0 010-8M17 11a3 3 0 100-6M16 20a5 5 0 016 0"/></svg></span><h2 id="cat-teams-title">Teams</h2><span class="modcount">1 Modul</span></div>
            <p class="cat-sub">Telefonie, PSTN-Modelle und Teams-Phone-Optionen vergleichen.</p>
            <div class="tool-grid">
                <?php /* Dynamic CMS output: foreach ($tools['teams'] as $tool) would replace this hardcoded tool card. */ ?>
                <article class="tool-card" data-cat="teams" data-name="teams phone lizenz berater" data-tags="lizenz kosten servicegrenzen netzwerk performance">
                    <div class="card-head"><span class="card-ico" role="img" aria-label="Teams-Phone-Berater"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 4h4l2 5-3 2a11 11 0 005 5l2-3 5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z"/></svg></span><h3>Teams Phone-Lizenz-Berater</h3></div>
                    <p class="desc">Vergleicht Calling Plan, Operator Connect, Direct Routing, Mischmodell und Sonderfälle für Teams Phone.</p>
                    <div class="tags"><span class="tag">Lizenz &amp; Kosten</span><span class="tag">Servicegrenzen</span><span class="tag">Netzwerk &amp; Performance</span></div>
                    <a class="open-link" href="/teams-phone-lizenzberater">Tool öffnen <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </article>
            </div>
        </section>

        <section class="no-results" id="m365ToolsNoResults" role="status" aria-live="polite" hidden>
            <p class="no-results-title">Keine Tools für deine Auswahl gefunden.</p>
            <p>Bitte Suchbegriff anpassen oder einen anderen Kategorie-Chip wählen.</p>
        </section>
    </div>
</section>

<script>
(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
            return;
        }

        callback();
    }

    function normalize(value) {
        return String(value || '').trim().toLowerCase();
    }

    ready(function () {
        var root = document.querySelector('.m365-tools[data-m365-tools]');
        if (!root) {
            return;
        }

        var search = root.querySelector('#m365ToolsSearch');
        var chips = Array.prototype.slice.call(root.querySelectorAll('.chip[data-cat]'));
        var sections = Array.prototype.slice.call(root.querySelectorAll('.cat-block[data-cat]'));
        var cards = Array.prototype.slice.call(root.querySelectorAll('.tool-card[data-name][data-tags]'));
        var empty = root.querySelector('#m365ToolsNoResults');
        var activeCat = 'all';

        function cardHaystack(card) {
            if (card.dataset.searchCache) {
                return card.dataset.searchCache;
            }

            var title = card.querySelector('h3');
            var desc = card.querySelector('.desc');
            return normalize([
                card.getAttribute('data-name'),
                card.getAttribute('data-tags'),
                title ? title.textContent : '',
                desc ? desc.textContent : ''
            ].join(' '));
        }

        function applyFilters() {
            var query = normalize(search ? search.value : '');
            var anyVisible = false;

            sections.forEach(function (section) {
                var sectionCat = normalize(section.getAttribute('data-cat'));
                var catMatches = activeCat === 'all' || activeCat === sectionCat;
                var sectionHasVisibleCard = false;
                var sectionCards = Array.prototype.slice.call(section.querySelectorAll('.tool-card[data-name][data-tags]'));

                sectionCards.forEach(function (card) {
                    var cardCat = normalize(card.getAttribute('data-cat') || sectionCat);
                    var cardCatMatches = activeCat === 'all' || activeCat === cardCat;
                    var textMatches = query === '' || cardHaystack(card).indexOf(query) !== -1;
                    var visible = catMatches && cardCatMatches && textMatches;

                    card.hidden = !visible;
                    if (visible) {
                        sectionHasVisibleCard = true;
                        anyVisible = true;
                    }
                });

                section.hidden = !(catMatches && sectionHasVisibleCard);
            });

            if (empty) {
                empty.hidden = anyVisible;
            }
        }

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                chips.forEach(function (item) {
                    item.classList.remove('active');
                    item.setAttribute('aria-pressed', 'false');
                });

                chip.classList.add('active');
                chip.setAttribute('aria-pressed', 'true');
                activeCat = normalize(chip.getAttribute('data-cat')) || 'all';
                applyFilters();
            });
        });

        if (search) {
            search.addEventListener('input', applyFilters);
        }

        cards.forEach(function (card) {
            card.dataset.searchCache = cardHaystack(card);
        });

        applyFilters();
    });
}());
</script>
