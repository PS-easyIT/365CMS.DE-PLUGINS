# AI Pack vs. Copilot Pro Vergleich

## Modul

- Registry-Key: `ai-pack-vs-copilot-pro`
- Route: `/ai-pack-vs-copilot-pro`
- Engine: `CMS_M365CALCULATOR_AI_Product_Comparison`
- Template: `templates/page-ai-product-comparison.php`
- Status: `live`

## Zweck

Vergleicht Copilot Chat, Microsoft 365 Copilot, Copilot Pro, Copilot Studio, GitHub Copilot, Security Copilot und weitere dynamische AI-Angebote nach Rolle, Datenquelle, Zielbild und Governance-Anforderung.

## Datenquellen

- `data/ai_product_catalog.json`
- `data/ai_use_case_matrix.json`
- `data/ai_dynamic_offers.json`

## Ergebnis

- Produktempfehlung
- Alternativen
- Vergleichstabelle
- Angebotslabel-Einordnung
- nächste Schritte

## Pflege

Volatile Microsoft-Angebotsnamen primär in `ai_dynamic_offers.json` aktualisieren, damit Engine und Template stabil bleiben.
