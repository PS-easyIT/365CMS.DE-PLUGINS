# CMS M365 Matrixen – Root Bootstrap

Dieser Ordner ist ein 365CMS-3.x-kompatibler Bootstrap für den Core-PluginManager.

Der eigentliche Plugin-Code liegt unter:

- `M365-PLUGINS/cms-m365matrices/`

Warum dieser Proxy existiert:

- Der Core-PluginManager erwartet aktive Plugins unter `PLUGIN_PATH/<slug>/<slug>.php`.
- Ohne diesen Root-Bootstrap kann `cms-m365matrices` nicht über den Slug `cms-m365matrices` aktiviert werden.
- Der Bootstrap lädt die kanonische Plugin-Datei und setzt kompatible Asset-URLs für das verschachtelte Repository-Layout.
- Die Matrixdaten und Matrixoptionen bleiben weiterhin in den gemeinsamen `cms_m365tools_*` Tabellen.
