# HOOKS

## Verwendete Hooks

### Plugin-Hooks
- `cms_init` → Plugin-Bootstrap
- `plugin_activated` → Aktivierungs-Initialisierung

### Core-/Admin-Hooks
- `register_routes` → Admin-Route `/admin/netimport`
- `admin_menu_items` → Menüeintrag „NetImport“

### Zielplugin-Hooks (indirekt über Save-Methoden)
- `company_created`
- `expert_registered`
- `speaker_created`
- `event_created`

## Eigenes Signal
- `netimport_ready` → wird bei Aktivierung ausgelöst
