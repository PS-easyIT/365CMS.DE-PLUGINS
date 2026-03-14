# CMS Newsletter

`cms-newsletter` erweitert 365CMS um eine eigene Newsletter-Verwaltung mit öffentlicher Anmeldeseite, Subscriber-Management, Templates, Kampagnenplanung und konfigurierbaren Compliance-Standards.

## Kernbereiche

- **Dashboard**: Kennzahlen zu Kontakten, Kampagnen und Versandbereitschaft
- **Abonnenten**: Verwaltung von E-Mail-Adressen, Segmenten und Statuswerten
- **Templates**: HTML-/Text-Vorlagen für wiederkehrende Kampagnen
- **Kampagnen**: Segmentierte Versandplanung inkl. Zeitfenster und Recipient Count
- **Einstellungen**: Absenderdaten, Double Opt-In, öffentliche Texte

## Öffentliche Oberfläche

- `GET /newsletter` zeigt die Newsletter-Landingpage mit Formular
- `POST /newsletter/subscribe` übernimmt neue Anmeldungen
- `GET /newsletter/unsubscribe/:token` setzt Kontakte auf `unsubscribed`
