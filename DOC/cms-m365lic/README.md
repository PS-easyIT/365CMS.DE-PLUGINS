# CMS M365 License

Das Plugin `cms-m365lic` liefert einen konfigurierbaren Microsoft-365-Lizenzberater für öffentliche Besucher, Mitglieder und Spezialgruppen. Der Bedarf wird in Gruppen erfasst und automatisch auf Basis gepflegter SKU-Features ausgewertet.

## Kernfunktionen

- Bedarf in mehreren Benutzergruppen erfassen
- Paketempfehlung auf Basis einer Feature-Matrix
- Copilot-Voraussetzungen und Copilot-Add-ons berücksichtigen
- Preise pro Tier (`public`, `member`, `group`) pflegen und getrennt ausspielen
- Laufzeit- und Zahlungsart je Bereich auswählen und serverseitig hochrechnen
- PDF-Export der Auswertung
- Tageslimits je Kontext über gehashte Actor-Keys
- Spezial-User direkt auf 365CMS-Benutzer zuweisen

## Besonderheiten

- Publicsite nutzt Header/Footer des aktiven Themes
- Member- und Spezialrechner laufen ausschließlich im geschützten 365CMS-Mitgliederbereich
- Seed-Katalog ist vollständig editierbar
- Alle Seed-SKUs werden mit EUR-Startpreisen vorbelegt; Fixpreis-SKUs wie Copilot Studio/Security Copilot sind als monatlicher Tenantpreis markiert
- Seed-Preisupdates werden bei Bestandsinstallationen übernommen, solange lokal noch kein eigener Preis gepflegt wurde
- Spezialbereich wird nur für im Plugin zugewiesene Benutzer registriert
- Publicsite, Memberbereich und Adminbereich wurden für bessere Lesbarkeit und schnellere Orientierung UI-seitig nachgeschärft
