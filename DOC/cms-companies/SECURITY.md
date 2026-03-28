# CMS Companies – Sicherheitskonzept für 365CMS V2.8.0

## Zweck

Dieses Dokument beschreibt den Sicherheits-Zielstand und die Audit-Schwerpunkte für `cms-companies` im Rahmen der Anpassung an **365CMS V2.8.0**.

Schwerpunkte sind:

- Firmen-CRUD im Admin- und Member-Bereich
- Experten-Zuordnungen
- Status-, Partner- und Filterlogik
- Kontakt-, Logo- und Website-Felder
- öffentliche Archiv- und Detailseiten

## Authentifizierung & Autorisierung

| Bereich | Prüfung |
|---------|---------|
| Admin-Backend | `CMS\Auth::instance()->isAdmin()` |
| Member-Dashboard | `CMS\Auth::instance()->isLoggedIn()` |
| Eigene Firma bearbeiten | `$company['user_id'] === Auth::instance()->getUserId()` oder `isAdmin()` |
| Firmen anderer Nutzer | Nur sichtbar, nicht bearbeitbar (für Member) |

## CSRF-Schutz

Alle POST-Formulare und AJAX-Anfragen müssen einen gültigen CSRF-Token enthalten:

```php
// Generierung (oben auf der Seite)
$csrfToken = Security::instance()->generateToken('companies_page');

// Prüfung (im POST-Handler)
if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'companies_page')) {
    $error = 'Sicherheitscheck fehlgeschlagen';
    // Handler abbrechen
}
```

## Eingabe-Sanitierung

```php
$name     = sanitize_text_field($_POST['name'] ?? '');
$email    = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$website  = filter_var($_POST['website'] ?? '', FILTER_VALIDATE_URL) ?: '';
$industry = sanitize_text_field($_POST['industry'] ?? '');
$id       = (int)($_POST['id'] ?? 0);
```

## Ausgabe-Escaping

```php
echo htmlspecialchars($company['name']);
echo htmlspecialchars($company['email'], ENT_QUOTES);
// Logo-URLs:
echo htmlspecialchars($company['logo_url'], ENT_QUOTES);
```

* Beschreibungsfelder auf öffentlichen Single-Seiten nicht roh rendern, sondern als Text escapen und nur kontrolliert mit `nl2br()` umbrechen.
* Intern zusammengesetzte Profil-, Breadcrumb- und Register-Links ebenfalls im `href`-Attribut-Kontext escapen.
* Reset- und Fallback-Links im Archiv für interne Navigationsziele ebenfalls nur escaped im `href`-Attribut ausgeben.
* Auch aus Helper-Funktionen wie `cms_company_url()` erzeugte Card-Links nur escaped im `href`-Attribut rendern.
* Auch Pagination-Links im Archiv mit zusammengesetzten Query-Parametern nur escaped im `href`-Attribut rendern.
* Dynamische Farb- und Gradientwerte in `style`-Attributen von Cards und Single-Templates ebenfalls nur escaped ausgeben.
* Zentrale Save-Pfade für Firmen müssen bei Updates für Nicht-Admins den `user_id`-Besitz des Datensatzes gegenprüfen und fremde IDs verwerfen.

## SQL-Injection-Prävention

- Ausschließlich PDO Prepared Statements
- Direktes String-Interpolieren von Benutzereingaben in SQL ist verboten
- `$db->prefix()` für Tabellennamen (kein Benutzereingabe-Einfluss)

## Datei-Upload-Sicherheit

- Logos werden ausschließlich über den Media-Proxy des CMS referenziert
- Kein direktes Datei-Upload in diesem Plugin (Nutzung von CMS Media Library)

## Berechtigungsmatrix

| Aktion | Admin | Member (eigene Firma) | Gast |
|--------|-------|----------------------|------|
| Firmen ansehen | ✅ | ✅ | ✅ |
| Firma erstellen | ✅ | ✅ (eine) | ❌ |
| Eigene Firma bearbeiten | ✅ | ✅ | ❌ |
| Fremde Firma bearbeiten | ✅ | ❌ | ❌ |
| Firma löschen | ✅ | ❌ | ❌ |
| Status ändern | ✅ | ❌ | ❌ |
| Partner-Status vergeben | ✅ | ❌ | ❌ |
| Experten zuordnen | ✅ | ✅ (eigene Firma) | ❌ |
