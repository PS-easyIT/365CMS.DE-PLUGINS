# CMS Companies – Sicherheitskonzept

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
