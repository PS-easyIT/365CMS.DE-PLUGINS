## Beschreibung

<!-- Was wurde geändert und warum? -->

## Änderungstyp

- [ ] 🐛 Bugfix
- [ ] ✨ Neues Feature
- [ ] ♻️ Refactoring
- [ ] 📝 Dokumentation
- [ ] 🔒 Sicherheits-Fix
- [ ] 🗄️ Datenbankänderung (Migration beachten!)

## Betroffenes Plugin

- [ ] cms-companies
- [ ] cms-events
- [ ] cms-experts
- [ ] cms-importer
- [ ] cms-jobprofile-generator
- [ ] cms-organigramm
- [ ] cms-speakers
- [ ] Mehrere / Übergreifend

## Checkliste

- [ ] `declare(strict_types=1)` und `ABSPATH`-Guard vorhanden
- [ ] Alle DB-Operationen als Prepared Statements
- [ ] CSRF-Token in jedem Formular / AJAX-Request
- [ ] Alle `$_POST`/`$_GET`-Werte sanitiert
- [ ] Alle HTML-Ausgaben per `htmlspecialchars()` escaped
- [ ] Cross-Plugin-Zugriffe mit `PluginManager::isPluginActive()` gesichert
- [ ] `update.json` aktualisiert (Version + Datum)
- [ ] `DOC/[plugin]/CHANGELOG.md` aktualisiert
- [ ] Admin-Seiten: `Auth::instance()->isAdmin()` geprüft
- [ ] Member-Seiten: `Auth::instance()->isLoggedIn()` geprüft
- [ ] DSGVO-Hooks registriert (falls personenbezogene Daten betroffen)