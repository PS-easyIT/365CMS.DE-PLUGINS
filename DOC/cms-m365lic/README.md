# CMS M365 License

Das Plugin `cms-m365lic` liefert einen konfigurierbaren Microsoft-365-Lizenzberater für öffentliche Besucher, Mitglieder und Spezialgruppen. Der Bedarf wird in Gruppen erfasst und automatisch auf Basis gepflegter SKU-Features ausgewertet.

## Kernfunktionen

- Bedarf in mehreren Benutzergruppen erfassen
- Paketempfehlung auf Basis einer Feature-Matrix
- Copilot-Voraussetzungen und Copilot-Add-ons berücksichtigen
- Preise pro Tier (`public`, `member`, `group`) pflegen
- PDF-Export der Auswertung
- Tageslimits je Kontext über gehashte Actor-Keys

## Besonderheiten

- Publicsite nutzt Header/Footer des aktiven Themes
- Seed-Katalog ist vollständig editierbar
- Häufige Business-/Teams-SKUs werden mit Startpreisen vorbelegt; CSP-/regionsabhängige Preise können weiterhin leer bleiben und werden im Ergebnis als offen gekennzeichnet
- Seed-Preisupdates werden bei Bestandsinstallationen übernommen, solange lokal noch kein eigener Preis gepflegt wurde
