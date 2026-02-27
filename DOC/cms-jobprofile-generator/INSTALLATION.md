# Installation & Aktivierung – CMS Job Profile Generator

> Voraussetzungen: 365CMS ≥ 0.20.0 · PHP ≥ 8.2 · MySQL 5.7+ / MariaDB 10.4+

---

## Inhaltsverzeichnis

1. [Voraussetzungen prüfen](#voraussetzungen-prüfen)
2. [Plugin installieren](#plugin-installieren)
3. [Plugin aktivieren](#plugin-aktivieren)
4. [Datenbankmigrationen](#datenbankmigrationen)
5. [Grundkonfiguration](#grundkonfiguration)
6. [Deinstallation](#deinstallation)

---

## Voraussetzungen prüfen

Bevor das Plugin aktiviert wird, müssen folgende Bedingungen erfüllt sein:

| Anforderung | Mindestwert | Prüfung |
|---|---|---|
| 365CMS | 0.20.0 | `System-Info`-Tab in den Einstellungen |
| PHP | 8.2 | `phpinfo()` oder `php -v` |
| MySQL / MariaDB | 5.7 / 10.4 | `SELECT VERSION()` |
| Schreibrecht | `uploads/` | Dateisystempermissions |
| Keine Abhängigkeiten | – | Keine weiteren Plugins erforderlich |

> Das Plugin hat **keine Plugin-Abhängigkeiten**. Der `Requires:`-Header im Plugin-Kommentarblock ist absichtlich leer.

---

## Plugin installieren

### Option A – Manuell per FTP/SFTP

1. Das Plugin-Verzeichnis `cms-jobprofile-generator/` kopieren nach:
   ```
   {cms-root}/plugins/cms-jobprofile-generator/
   ```
2. Sicherstellen, dass die Einstiegsdatei vorhanden ist:
   ```
   {cms-root}/plugins/cms-jobprofile-generator/job-profile-generator.php
   ```

### Option B – ZIP-Upload im Admin

1. Admin-Backend → **Plugins** → Bereich „Neues Plugin installieren"
2. ZIP-Datei hochladen (max. 50 MB)
3. Das CMS entpackt die Datei automatisch unter `plugins/`

---

## Plugin aktivieren

1. Admin-Backend → **Plugins**
2. Plugin `CMS Job Profile Generator` in der Liste finden
3. Auf **▶ Aktivieren** klicken
4. Der `PluginManager` führt automatisch aus:
   - Sicherheits-Scan (Blacklist verbotener Funktionen)
   - Abhängigkeits-Check
   - `plugin_activated`-Hook → `CMS_JPG_Installer::install()`

Nach erfolgreicher Aktivierung erscheint im Admin-Menü der Punkt **📋 Job Profile Generator**.

### Was bei Aktivierung passiert (intern)

```php
// PluginManager::activatePlugin() löst aus:
Hooks::doAction('plugin_activated', 'cms-jobprofile-generator');

// CMS_JobProfileGenerator::on_activation() fängt ab:
if ($plugin === 'cms-jobprofile-generator') {
    CMS_JPG_Installer::install();
}
```

---

## Datenbankmigrationen

`CMS_JPG_Installer::install()` legt beim ersten Aktivieren **19 Tabellen** an (mit dem CMS-Datenbankpräfix `$db->getPrefix()`):

| Tabelle | Verwendung |
|---|---|
| `{prefix}jpg_profiles` | Stellenprofile (Stammdaten) |
| `{prefix}jpg_profile_tasks` | Aufgaben je Profil (sortierbar) |
| `{prefix}jpg_profile_requirements` | Anforderungen je Profil (must/nice) |
| `{prefix}jpg_profile_benefits` | m:n Zuordnung Profil ↔ Benefit |
| `{prefix}jpg_profile_skills` | m:n Zuordnung Profil ↔ Skill |
| `{prefix}jpg_applications` | Bewerbungen mit CV-Upload |
| `{prefix}jpg_workflow_steps` | Konfigurierbare Genehmigungsschritte |
| `{prefix}jpg_workflow_history` | Audit-Log aller Workflow-Aktionen |
| `{prefix}jpg_team_approvers` | Team-Genehmiger (ergänzend zu Rollen) |
| `{prefix}jpg_benefits_catalog` | Benefit-Katalog-Einträge |
| `{prefix}jpg_requirement_items` | Anforderungs-Bausteine (60+ Seed-Einträge) |
| `{prefix}jpg_skill_matrix` | Skill-Bibliothek |
| `{prefix}jpg_job_categories` | Stellenkategorien |
| `{prefix}jpg_text_modules` | Wiederverwendbare Textbausteine |
| `{prefix}jpg_templates` | PDF/Web/E-Mail-Vorlagen |
| `{prefix}jpg_departments` | Abteilungen pro Unternehmen |
| `{prefix}jpg_department_benefits` | m:n Abteilung ↔ Benefit |
| `{prefix}jpg_department_requirements` | m:n Abteilung ↔ Anforderung |
| `{prefix}jpg_admin_access_log` | Audit-Log für Admin-Zugriffe |

> Vollständiges Schema mit allen Spalten: [DATABASE.md](DATABASE.md)

### Versionierung

Die DB-Version wird in `{prefix}jpg_settings` gespeichert:

```php
// class-installer.php
private static function store_db_version(): void
{
    $db = \CMS\Database::instance();
    $db->insert($db->getPrefix() . 'jpg_settings', [
        'option_name'  => 'jpg_db_version',
        'option_value' => JPG_DB_VERSION,
    ]);
}
```

Bei jedem `cms_init`-Hook prüft `maybe_install()` die gespeicherte Version gegen `JPG_DB_VERSION`. Ist sie veraltet, wirdin die DB-Migration ausgeführt.

---

## Grundkonfiguration

Nach der Aktivierung empfehlen sich folgende Einstellungen:

1. **Einstellungen → Tab Allgemein**
   - Standard-Status: `Entwurf` oder `Veröffentlicht`
   - Profile pro Seite: `20`
   - Slug-Präfix: gewünschtes URL-Präfix (z. B. `stelle`)

2. **Einstellungen → Tab Berechtigungen**
   - Festlegen, welche CMS-Rollen Profile erstellen/bearbeiten/veröffentlichen dürfen

3. **Einstellungen → Tab Benachrichtigungen**
   - Benachrichtigungs-E-Mail eintragen

4. **Design → Tab Corporate**
   - Primärfarbe und Unternehmensname hinterlegen

5. **🏢 Unternehmens-Übersicht** *(sichtbar wenn `cms-companies` aktiv ist)*
   - Standard-Benefits für jedes Unternehmen zuweisen – werden beim Erstellen neuer Profile im Generator-Wizard (Tab „Benefits") automatisch vorausgewählt

6. **Bibliotheken → Tab Anforderungs-Liste**
   - Eigene Anforderungs-Bausteine (Gruppe, Titel, Reihenfolge) anlegen – stehen im Generator-Wizard (Tab „Anforderungen") als Picker zur Verfügung

---

## Deinstallation

1. Admin-Backend → **Plugins** → Plugin **deaktivieren**
2. Nach Deaktivierung erscheint der Button „🗑️ Löschen"
3. Bei der Löschung durch den `PluginManager`:
   - Plugin-Verzeichnis wird vollständig entfernt
   - Die Datenbanktabellen werden **nicht automatisch gelöscht** (Datensicherheit)

### Manuelle DB-Bereinigung

Um alle Plugin-Daten zu entfernen, folgenden SQL-Block ausführen:

```sql
SET @p = 'cms_';   -- Datenbankpräfix anpassen

DROP TABLE IF EXISTS
  `cms_jpg_company_default_benefits`,
  `cms_jpg_requirement_items`,
  `cms_jpg_workflow_history`,
  `cms_jpg_workflow_steps`,
  `cms_jpg_stats`,
  `cms_jpg_settings`,
  `cms_jpg_templates`,
  `cms_jpg_job_categories`,
  `cms_jpg_benefits`,
  `cms_jpg_profile_skills`,
  `cms_jpg_skills`,
  `cms_jpg_text_modules`,
  `cms_jpg_profile_benefits`,
  `cms_jpg_profile_requirements`,
  `cms_jpg_profile_tasks`,
  `cms_jpg_profiles`;
```

> **Achtung:** Dieser Vorgang ist unwiderruflich.
