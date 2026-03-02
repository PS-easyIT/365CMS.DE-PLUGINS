# CMS Forum – Hooks-Dokumentation

> **Version:** 1.0.0

---

## Actions

### Plugin-Lifecycle

| Hook | Parameter | Beschreibung |
|------|-----------|-------------|
| `cms_init` | – | Plugin wird initialisiert, Abhängigkeiten geladen |
| `plugin_activated` | `string $slug` | Plugin wurde aktiviert (Tabellen erstellen) |
| `plugin_uninstalled` | `string $slug` | Plugin wurde deinstalliert (Tabellen löschen) |

### Admin

| Hook | Parameter | Beschreibung |
|------|-----------|-------------|
| `cms_admin_menu` | – | Admin-Menü wird aufgebaut, Forum-Untermenüs registriert |

### Frontend

| Hook | Parameter | Beschreibung |
|------|-----------|-------------|
| `register_routes` | `Router $router` | Forum-Routen werden registriert |
| `head` | – | CSS-Dateien werden eingebunden |
| `body_end` | – | JavaScript-Dateien werden eingebunden |

### Member-Bereich

| Hook | Parameter | Beschreibung |
|------|-----------|-------------|
| `cms_member_dashboard` | – | Forum-Widget im Member-Dashboard rendern |

### DSGVO

| Hook | Parameter | Beschreibung |
|------|-----------|-------------|
| `dsgvo_export_data` | `int $userId, array &$data` | Forum-Daten zum Export hinzufügen |
| `dsgvo_delete_data` | `int $userId` | Forum-Daten anonymisieren/löschen |

---

## Registrierte Routen

Alle Routen werden über `ForumController::register_routes()` beim Hook `register_routes` registriert:

### GET-Routen

```
GET /forum                        → ForumController::index()
GET /forum/search                 → ForumController::search()
GET /forum/user/:id               → ForumController::userProfile()
GET /forum/:slug                  → ForumController::showForum()
GET /forum/:slug/new-thread       → ForumController::newThread()
GET /forum/thread/:id             → ThreadController::show()
GET /forum/post/:id/edit          → PostController::edit()
```

### POST-Routen

```
POST /forum/:slug/new-thread      → ThreadController::create()
POST /forum/thread/:id/reply      → ThreadController::reply()
POST /forum/post/:id/edit         → PostController::update()
POST /forum/post/:id/like         → PostController::toggleLike()
POST /forum/post/:id/report       → PostController::report()
POST /forum/thread/:id/subscribe  → ForumController → ThreadController
```

### Moderations-Routen (POST, via ForumController AJAX)

```
POST /forum/moderate/thread/:id/lock     → ModeratorController::lockThread()
POST /forum/moderate/thread/:id/pin      → ModeratorController::pinThread()
POST /forum/moderate/thread/:id/delete   → ModeratorController::deleteThread()
POST /forum/moderate/thread/:id/move     → ModeratorController::moveThread()
POST /forum/moderate/post/:id/delete     → ModeratorController::deletePost()
POST /forum/moderate/post/:id/restore    → ModeratorController::restorePost()
POST /forum/moderate/post/:id/approve    → ModeratorController::approvePost()
POST /forum/moderate/report/:id/resolve  → ModeratorController::resolveReport()
```

---

## Asset-Hooks

### CSS (Hook: `head`, Priorität 10)

| Datei | Bedingung |
|-------|-----------|
| `assets/css/style.css` | Immer auf Forum-Seiten |
| `assets/css/single.css` | Nur auf Thread-Detail-Seiten |
| `assets/css/member.css` | Nur im Member-Bereich |

### JavaScript (Hook: `body_end`, Priorität 10)

| Datei | Bedingung |
|-------|-----------|
| `assets/js/forum.js` | Immer auf Forum-Seiten |
| `assets/js/editor.js` | Seiten mit BBCode-Editor |
| `assets/js/notifications.js` | Eingeloggte Benutzer |

### Admin-Assets

| Datei | Bedingung |
|-------|-----------|
| `assets/css/cms-forum-admin.css` | Admin-Forum-Seiten |
| `assets/js/admin-sort.js` | Admin-Sortierseiten |

---

## Erweiterbarkeit

Das Plugin bietet aktuell keine eigenen `doAction()`- oder `applyFilters()`-Hooks für externe Plugins. Geplant für v1.1:

- `cmsforum_before_post_save` – Vor dem Speichern eines Beitrags
- `cmsforum_after_post_save` – Nach dem Speichern
- `cmsforum_before_thread_create` – Vor Thread-Erstellung
- `cmsforum_render_post_actions` – Zusätzliche Buttons unter Beiträgen
- `cmsforum_bbcode_parse` – BBCode-Parser erweitern
