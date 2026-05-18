# CMS M365 Tools – Root Bootstrap

Dieser Ordner ist ein 365CMS-3.x-kompatibler Bootstrap für den Core-PluginManager.

Der eigentliche Plugin-Code liegt weiterhin unter:

- `M365-PLUGINS/cms-m365tools/`

Warum dieser Proxy existiert:

- Der Core-PluginManager erwartet aktive Plugins unter `PLUGIN_PATH/<slug>/<slug>.php`.
- Ohne diesen Root-Bootstrap kann `cms-m365tools` nicht über den Slug `cms-m365tools` aktiviert werden.
- Der Bootstrap lädt die kanonische Plugin-Datei und setzt kompatible Asset-URLs für das verschachtelte Repository-Layout.
