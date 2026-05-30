# CMS M365 Landing

Zentrale Landingpage für die M365-Plugin-Welt. Das Plugin bündelt Lizenzmatrixen, M365 Add-ons, Copilot, Azure Services, Tutorials und einzelne M365 Tools in einer steuerbaren Public-Seite.

## Öffentliche Seite

- Standard-Route: `/m365`
- Admin steuerbar über `M365 Landing`
- Cards werden aus `m365landing_cards` geladen und nach `matrix`, `areas` und `tools` gruppiert.
- Falls eine Card ein Bild besitzt, wird dieses angezeigt; sonst greift der Icon-/Emoji-Fallback.
- Der Content Header unterstützt ein optionales Headerbild aus Mediathek oder URL.
- Es stehen drei Layoutvarianten zur Verfügung: Standard, Kompakt und Spotlight.

## Adminbereiche

- Dashboard: Kennzahlen und Schnellzugriffe
- Karten & Bereiche: Cards erstellen, bearbeiten, sortieren, aktivieren/deaktivieren
- Inhalte & Design: Header-Texte, Headerbild, Sektions-Texte, Sichtbarkeit, Abstände, Layout und Design-Tokens
- System: Versionen und Inhaltszählung
