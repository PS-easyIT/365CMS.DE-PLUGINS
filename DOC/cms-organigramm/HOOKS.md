# Hooks-Referenz – cms-organigramm

> Alle geplanten Actions und Filter für das cms-organigramm Plugin (v0.1.0+).

---

## Actions

### `organigramm_chart_created`

Wird ausgelöst, nachdem ein neues Organigramm erfolgreich erstellt wurde.

```php
CMS\Hooks::doAction('organigramm_chart_created', int $chartId, array $data);
```

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `$chartId` | int | ID des neuen Organigramms |
| `$data` | array | Die gespeicherten Daten |

**Anwendungsfall:** Cache invalidieren, Notification senden.

---

### `organigramm_chart_updated`

```php
CMS\Hooks::doAction('organigramm_chart_updated', int $chartId, array $data);
```

---

### `organigramm_chart_deleted`

```php
CMS\Hooks::doAction('organigramm_chart_deleted', int $chartId);
```

**Hinweis:** Alle Nodes und Edges werden per `ON DELETE CASCADE` automatisch gelöscht.

---

### `organigramm_node_added`

```php
CMS\Hooks::doAction('organigramm_node_added', int $nodeId, int $chartId, array $data);
```

---

### `organigramm_node_updated`

```php
CMS\Hooks::doAction('organigramm_node_updated', int $nodeId, array $data);
```

---

### `organigramm_node_deleted`

```php
CMS\Hooks::doAction('organigramm_node_deleted', int $nodeId, int $chartId);
```

---

### `organigramm_node_moved`

Wird ausgelöst, wenn ein Knoten einen neuen Parent-Knoten bekommt (Drag & Drop im Builder).

```php
CMS\Hooks::doAction('organigramm_node_moved', int $nodeId, ?int $newParentId, int $chartId);
```

---

### `organigramm_chart_published`

```php
CMS\Hooks::doAction('organigramm_chart_published', int $chartId);
```

---

### `organigramm_chart_archived`

```php
CMS\Hooks::doAction('organigramm_chart_archived', int $chartId);
```

---

## Filter

### `organigramm_chart_data`

Modifiziert die Chart-Konfigurationsdaten, bevor sie gespeichert werden.

```php
$data = CMS\Hooks::applyFilters('organigramm_chart_data', array $data, int $chartId);
```

**Beispiel:**
```php
CMS\Hooks::addFilter('organigramm_chart_data', function(array $data, int $chartId): array {
    $data['settings']['defaultColor'] = '#0ea5e9';
    return $data;
}, 10);
```

---

### `organigramm_node_data`

```php
$data = CMS\Hooks::applyFilters('organigramm_node_data', array $data, int $chartId);
```

---

### `organigramm_node_reference`

Erlaubt das Anreichern eines Nodes mit Daten aus der verknüpften Entität.

```php
$nodeData = CMS\Hooks::applyFilters(
    'organigramm_node_reference',
    array $nodeData,
    string $referenceType,
    int $referenceId
);
```

**Beispiel:** cms-experts reichert Nodes mit Foto und Jobbezeichnung an:
```php
CMS\Hooks::addFilter('organigramm_node_reference', function(array $node, string $type, int $refId): array {
    if ($type !== 'expert') return $node;
    $expert = CMS_Experts_Database::instance()->getById($refId);
    if ($expert) {
        $node['label']     = $expert['first_name'] . ' ' . $expert['last_name'];
        $node['sublabel']  = $expert['job_title'] ?? '';
        $node['photo_url'] = $expert['photo_url'] ?? null;
    }
    return $node;
}, 10);
```

---

### `organigramm_export_data`

Modifiziert die Export-Daten vor dem Download (JSON/SVG/PNG).

```php
$exportData = CMS\Hooks::applyFilters('organigramm_export_data', array $exportData, int $chartId, string $format);
```

---

### `organigramm_shortcode_output`

Modifiziert das fertige HTML-Output des Shortcodes.

```php
$html = CMS\Hooks::applyFilters('organigramm_shortcode_output', string $html, int $chartId, array $atts);
```

---

### `organigramm_query_args`

Modifiziert die Query-Parameter beim Abrufen von Organigramm-Listen.

```php
$args = CMS\Hooks::applyFilters('organigramm_query_args', array $args);
```

---

## DSGVO-Hooks

Da Nodes Referenzen auf Personen-Datensätze enthalten können:

```php
// Beim DSGVO-Export: Alle Charts mit Referenz auf diesen User zurückgeben
CMS\Hooks::addAction('dsgvo_export_data', function(int $userId, array &$exportData): void {
    // Alle Node-Referenzen mit reference_type='expert' und reference_id=expert.user_id
    // in exportData['organigramm_references'] ablegen
}, 10);

// Beim DSGVO-Löschen: Referenzen anonymisieren (nicht löschen, da strukturelle Daten)
CMS\Hooks::addAction('dsgvo_delete_data', function(int $userId): void {
    // reference_type und reference_id für entsprechende Nodes auf NULL setzen
    // label auf 'Anonymisiert' setzen
}, 10);
```

---

## Nutzungsbeispiele

### Benachrichtigung bei Veröffentlichung

```php
CMS\Hooks::addAction('organigramm_chart_published', function(int $chartId): void {
    // z. B. E-Mail an Orga-Admin senden
    $chart = CMS_Organigramm_Database::instance()->getById($chartId);
    // ... Benachrichtigungslogik
}, 10);
```

### CMS-Companies Integration

```php
CMS\Hooks::addFilter('organigramm_node_reference', function(array $node, string $type, int $refId): array {
    if ($type !== 'company') return $node;
    if (!CMS\PluginManager::instance()->isPluginActive('cms-companies')) return $node;
    
    $db = CMS\Database::instance();
    $p  = $db->prefix();
    $company = $db->prepare("SELECT name, logo_url FROM {$p}companies WHERE id = ?")->execute([$refId])->fetch();
    if ($company) {
        $node['label']     = $company['name'];
        $node['photo_url'] = $company['logo_url'] ?? null;
    }
    return $node;
}, 10);
```
