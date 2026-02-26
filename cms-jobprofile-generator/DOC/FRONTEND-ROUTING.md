# Frontend-Routing – CMS Job Profile Generator

> Beschreibt alle öffentlichen Routen des Plugins, die Template-Hierarchie, Caching-Strategie und das optionale Whitelabel-Routing.

---

## Inhaltsverzeichnis

1. [Überblick der Routen](#überblick-der-routen)
2. [Theme-integriertes Routing (`/jobs/:slug`)](#theme-integriertes-routing-jobsslug)
3. [Whitelabel-Routing (`/career/:slug`)](#whitelabel-routing-careerslug)
4. [PDF-Export-Endpunkt](#pdf-export-endpunkt)
5. [Caching](#caching)
6. [DSGVO-Page-View-Tracking](#dsgvo-page-view-tracking)
7. [Template-Variablen](#template-variablen)
8. [Custom Branding Injection](#custom-branding-injection)

---

## Überblick der Routen

| Route | Template | Abo-Pflicht | Beschreibung |
|---|---|---|---|
| `/jobs/:slug` | `templates/public-single.php` | Nein | Standard: Im CMS-Theme eingebettet |
| `/career/:slug` | `templates/whitelabel-single.php` | `feature_whitelabel_jobs` | Vollständig eigenständiges HTML5-Dokument |
| `/api/jobs/:slug/pdf` | – (Response) | Nein | PDF-Download (on-the-fly) |

> Die öffentlichen Frontend-Routen sind implementiert und über den CMS-Hook `routes_registered` registriert. Das Whitelabel-Routing erfordert das Feature-Flag `feature_whitelabel_jobs` im Abo-Plan des Profil-Erstellers.

---

## Theme-integriertes Routing (`/jobs/:slug`)

### Funktionsweise

1. Der CMS-Router erkennt `/jobs/{slug}` und sucht nach einem veröffentlichten Profil.
2. Das Plugin stellt das Template bereit, das in den CMS-Theme-Rahmen eingebettet wird.
3. Header und Footer werden über `CMS\ThemeManager` gerendert.

### Router-Registrierung

```php
// Registriert in CMS_JobProfileGenerator::init_hooks():
CMS\Hooks::addAction('routes_registered', function(): void {
    \CMS\Router::addRoute('GET', '/jobs/([a-z0-9\-]+)', function(string $slug): void {
        $profile = CMS_JPG_Profiles::instance()->get_by_slug($slug);
        if (!$profile || $profile->status !== 'published') {
            \CMS\Router::notFound();
            return;
        }
        // Caching (15 Minuten)
        $html = \CMS\CacheManager::instance()->remember(
            'jpg_profile_' . $profile->id,
            900,
            fn() => CMS_JPG_Export::instance()->render_html($profile->id)
        );
        // Page-View tracken
        CMS_JPG_Profiles::instance()->log_stat($profile->id);
        // Template laden
        require JPG_DIR . 'templates/public-single.php';
    });
}, 10);
```

### Template (`templates/public-single.php`)

```php
<?php
// Alle Ausgaben sind escaped – der $html-String wurde bereits
// durch sanitizeHtml() gefiltert (kommt aus render_html())
\CMS\ThemeManager::instance()->getHeader();
?>
<main class="jpg-profile-page">
    <article class="jpg-profile">
        <h1><?php echo htmlspecialchars($profile->title); ?></h1>
        <div class="jpg-profile-content">
            <?php echo $html; // sanitizeHtml() bereits angewendet ?>
        </div>
    </article>
</main>
<?php
\CMS\ThemeManager::instance()->getFooter();
```

> Alle Ausgaben werden zuvor durch `\CMS\Security::instance()->escapeOutput()` geleitet oder kommen aus dem `sanitizeHtml()`-geprüften Template-Cache.

---

## Whitelabel-Routing (`/career/:slug`)

### Voraussetzung

Das Whitelabel-Routing ist nur aktiv, wenn der Profil-Ersteller das Feature `feature_whitelabel_jobs` in seinem Abo-Plan hat:

```php
// Prüfung beim Routenaufruf (geplant, Phase 4.2):
$ownerId = $profile->user_id;
if (!\CMS\SubscriptionManager::instance()->user_has_feature($ownerId, 'feature_whitelabel_jobs')) {
    // Fallback: Weiterleitung zur Standard-Route
    header('Location: /jobs/' . $profile->slug, true, 302);
    exit;
}
```

### Unterschied zu Standard-Template

| Merkmal | `/jobs/:slug` | `/career/:slug` |
|---|---|---|
| CMS-Theme-Header | ✅ vorhanden | ❌ kein CMS-Header |
| CMS-Theme-Footer | ✅ vorhanden | ❌ kein CMS-Footer |
| HTML-Grundgerüst | Vom Theme bestimmt | Eigenständiges `<!DOCTYPE html>` |
| Unternehmens-Branding | Optional | Vollständig anpassbar |
| Abo-Pflicht | Nein | `feature_whitelabel_jobs` |

### Template-Struktur (`templates/whitelabel-single.php`)

```html
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profile->title . ' – ' . $companyName); ?></title>
    <!-- Custom Branding CSS-Variablen -->
    <?php echo $brandingStyleTag; // sanitized <style>-Tag ?>
</head>
<body class="jpg-whitelabel-body">
    <header class="jpg-wl-header">
        <?php if ($logoUrl): ?>
            <img src="<?php echo esc_url($logoUrl); ?>" alt="<?php echo htmlspecialchars($companyName); ?>">
        <?php else: ?>
            <span><?php echo htmlspecialchars($companyName); ?></span>
        <?php endif; ?>
    </header>
    <main class="jpg-wl-main">
        <!-- Profil-Inhalte -->
    </main>
    <footer class="jpg-wl-footer">
        <p><?php echo htmlspecialchars($footerText); ?></p>
    </footer>
</body>
</html>
```

---

## PDF-Export-Endpunkt

### Route

```
GET /api/jobs/{slug}/pdf
```

### Verhalten

```php
// Geplant, Phase 4.4 – Wird in Controller implementiert:
// 1. Profil laden & Zugriff prüfen
// 2. HTML aus CMS_JPG_Export::render_html($id) generieren
// 3. mPDF/TCPDF rendern
// 4. HTTP-Header setzen + PDF streamen

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="stellenprofil-' . $slug . '.pdf"');
// Rate-Limiting: max. 10 PDFs pro Minute pro IP (Phase 5.2)
```

### Rate-Limiting (geplant, Phase 5.2)

```php
$ip        = \CMS\Request::instance()->getClientIp();
$keyPrefix = 'jpg_pdf_rate_' . md5($ip);
$count     = (int)\CMS\CacheManager::instance()->get($keyPrefix, 0);

if ($count >= 10) {
    http_response_code(429);
    die('Rate-Limit überschritten.');
}
\CMS\CacheManager::instance()->set($keyPrefix, $count + 1, 60);
```

---

## Caching

### Fragment-Caching für öffentliche Profile

Das HTML eines veröffentlichten Profils wird für **15 Minuten** gecacht.

```php
$cacheKey = 'jpg_profile_html_' . $profile->id;
$cached   = \CMS\CacheManager::instance()->get($cacheKey);

if ($cached === null) {
    $cached = CMS_JPG_Export::instance()->render_html($profile->id);
    \CMS\CacheManager::instance()->set($cacheKey, $cached, 900); // 15 min
}

echo $cached;
```

### Cache-Invalidierung

Beim Speichern oder Veröffentlichen eines Profils wird der Cache sofort ungültig:

```php
// In CMS_JPG_Profiles::save() nach erfolgreichem UPDATE:
\CMS\CacheManager::instance()->delete('jpg_profile_html_' . $profileId);
```

### Nicht gecachte Inhalte

- Admin-Seiten (nie gecacht)
- Statistik-Abfragen (direkte DB-Abfragen)
- AJAX-Vorschau im Generator-Wizard

---

## DSGVO-Page-View-Tracking

Das Plugin trackt Profilaufrufe **ohne personenbezogene Daten**:

- Keine IP-Adressen
- Keine Session-IDs
- Keine User-Agents
- Nur: Profil-ID + Datum + Zähler

```php
// Wird im Frontend-Controller aufgerufen:
CMS_JPG_Profiles::instance()->log_stat(int $profileId): void;

// Internally: INSERT ... ON DUPLICATE KEY UPDATE view_count = view_count + 1
// Tabelle jpg_stats: (profile_id, view_date, view_count)
```

Die gesammelten Daten sind im Dashboard unter **Tab 5: Statistiken** einsehbar.

---

## Template-Variablen

Folgende Variablen stehen in `public-single.php` und `whitelabel-single.php` zur Verfügung:

| Variable | Typ | Beschreibung |
|---|---|---|
| `$profile` | `object` | Profil-Stammdaten aus `jpg_profiles` |
| `$tasks` | `array` | Sortierte Aufgabenliste |
| `$requirements` | `array` | Anforderungen mit `type` (must/nice) |
| `$benefits` | `array` | Ausgewählte Benefits (inkl. `source`-Feld: `company`/`category`/`job`) |
| `$experts` | `array` | Ansprechpartner-Objekte (leer wenn `cms-experts` inaktiv oder keine company_id) |
| `$html` | `string` | Fertig gerendertes, gesäubertes HTML |
| `$companyName` | `string` | Aus Plugin-Einstellungen |
| `$primaryColor` | `string` | Hex-Farbe für Branding |
| `$logoUrl` | `string` | Logo-URL (leer wenn nicht gesetzt) |
| `$footerText` | `string` | Footer-Text aus Einstellungen |
| `$brandingStyleTag` | `string` | Fertiger `<style>`-Block (bereits sanitiert) |
| `$applyCsrf` | `string` | CSRF-Token für das Bewerbungs-Modal |

---

## Custom Branding Injection

Wenn der Profil-Ersteller `feature_custom_branding` besitzt, wird ein dynamischer `<style>`-Block in den `<head>` injiziert:

```php
// Geplant, Phase 4.3:
$primaryColor   = $settings['jpg_primary_color']   ?? '#3b82f6';
$secondaryColor = $settings['jpg_secondary_color'] ?? '#1e293b';

// CSS-Variablen-Werte streng validieren (nur Hex-Farben erlaubt)
$primaryColor   = preg_match('/^#[0-9a-fA-F]{6}$/', $primaryColor)   ? $primaryColor   : '#3b82f6';
$secondaryColor = preg_match('/^#[0-9a-fA-F]{6}$/', $secondaryColor) ? $secondaryColor : '#1e293b';

$brandingStyleTag = sprintf(
    '<style>:root { --jpg-primary: %s; --jpg-secondary: %s; }</style>',
    $primaryColor,
    $secondaryColor
);
// $brandingStyleTag wird direkt im <head> ausgegeben (Werte bereits validiert)
```

---

## Verwandte Dokumentation

- [INSTALLATION.md](INSTALLATION.md) – Plugin aktivieren & konfigurieren
- [DATABASE.md](DATABASE.md) – Tabellenstruktur für `jpg_profiles` & `jpg_stats`
- [SECURITY.md](SECURITY.md) – XSS-Escaping & Sanitierung im Frontend
- [HOOKS-API.md](HOOKS-API.md) – Filter `jpg_profile_html` für Template-Erweiterungen
