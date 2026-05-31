<?php
/**
 * CMS Contact – Fields Service
 *
 * CRUD für benutzerdefinierte Formularfelder.
 *
 * @package CMS_Contact
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Contact_Fields
{
    private static ?self $instance = null;
    private \PDO $pdo;
    private string $prefix;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $db           = \CMS\Database::instance();
        $this->pdo    = $db->getPdo();
        $this->prefix = $db->getPrefix();
    }

    // ── Verfügbare Feldtypen ──────────────────────────────────────────────────

    public static function get_field_types(): array
    {
        return [
            'text'     => ['label' => 'Text',           'icon' => '📝'],
            'email'    => ['label' => 'E-Mail',         'icon' => '📧'],
            'tel'      => ['label' => 'Telefon',        'icon' => '📞'],
            'number'   => ['label' => 'Zahl',           'icon' => '🔢'],
            'url'      => ['label' => 'URL',            'icon' => '🔗'],
            'date'     => ['label' => 'Datum',          'icon' => '📅'],
            'textarea' => ['label' => 'Textbereich',    'icon' => '📄'],
            'select'   => ['label' => 'Dropdown',       'icon' => '📋'],
            'radio'    => ['label' => 'Radio-Buttons',  'icon' => '🔘'],
            'checkbox' => ['label' => 'Checkbox',       'icon' => '☑️'],
            'hidden'   => ['label' => 'Versteckt',      'icon' => '👁️‍🗨️'],
        ];
    }

    public static function get_field_widths(): array
    {
        return [
            'full'    => 'Volle Breite (100%)',
            'half'    => 'Halbe Breite (50%)',
            'third'   => 'Drittel (33%)',
            'two-third' => 'Zwei Drittel (66%)',
        ];
    }

    // ── CRUD ──────────────────────────────────────────────────────────────────

    /**
     * Alle Felder eines Formulars laden (sortiert)
     */
    public function get_by_form(int $formId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->prefix}contact_fields
             WHERE form_id = ?
             ORDER BY field_order ASC, id ASC"
        );
        $stmt->execute([$formId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Einzelnes Feld per ID
     */
    public function get_by_id(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->prefix}contact_fields WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Neues Feld erstellen
     */
    public function create(array $data): int
    {
        // Nächste Sortierung ermitteln
        if (!isset($data['field_order'])) {
            $stmt = $this->pdo->prepare(
                "SELECT COALESCE(MAX(field_order), 0) + 1 FROM {$this->prefix}contact_fields WHERE form_id = ?"
            );
            $stmt->execute([$data['form_id']]);
            $data['field_order'] = (int) $stmt->fetchColumn();
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->prefix}contact_fields
             (form_id, field_name, field_label, field_type, placeholder, default_value,
              options_json, validation, is_required, is_system, field_order, field_width, css_class, description)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $optionsJson = null;
        if (!empty($data['options_json'])) {
            $optionsJson = is_array($data['options_json'])
                ? json_encode($data['options_json'])
                : $data['options_json'];
        }

        $stmt->execute([
            (int) $data['form_id'],
            $data['field_name']    ?? $this->sanitize_field_name($data['field_label'] ?? 'field'),
            $data['field_label']   ?? 'Neues Feld',
            $data['field_type']    ?? 'text',
            $data['placeholder']   ?? null,
            $data['default_value'] ?? null,
            $optionsJson,
            $data['validation']    ?? null,
            (int) ($data['is_required'] ?? 0),
            (int) ($data['is_system']   ?? 0),
            (int) $data['field_order'],
            $data['field_width']   ?? 'full',
            $data['css_class']     ?? null,
            $data['description']   ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Feld aktualisieren
     */
    public function update(int $id, array $data): bool
    {
        $sets   = [];
        $params = [];

        $allowed = [
            'field_name', 'field_label', 'field_type', 'placeholder', 'default_value',
            'options_json', 'validation', 'is_required', 'is_system', 'field_order',
            'field_width', 'css_class', 'description',
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "{$field} = ?";
                $value  = $data[$field];

                if ($field === 'options_json' && is_array($value)) {
                    $value = json_encode($value);
                }
                if (in_array($field, ['is_required', 'is_system', 'field_order'], true)) {
                    $value = (int) $value;
                }

                $params[] = $value;
            }
        }

        if (empty($sets)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE {$this->prefix}contact_fields SET " . implode(', ', $sets) . " WHERE id = ?";

        return $this->pdo->prepare($sql)->execute($params);
    }

    /**
     * Feld löschen
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->prefix}contact_fields WHERE id = ? AND is_system = 0");
        return $stmt->execute([$id]);
    }

    /**
     * Feld-Reihenfolge aktualisieren
     */
    public function update_order(int $formId, array $orderedIds): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->prefix}contact_fields SET field_order = ? WHERE id = ? AND form_id = ?"
        );

        foreach ($orderedIds as $order => $fieldId) {
            $stmt->execute([$order + 1, (int) $fieldId, $formId]);
        }

        return true;
    }

    /**
     * Anzahl der Felder eines Formulars
     */
    public function count_by_form(int $formId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->prefix}contact_fields WHERE form_id = ?");
        $stmt->execute([$formId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Anzahl der Felder pro Formular in einer Sammelabfrage.
     *
     * @param int[] $formIds
     * @return array<int, int>
     */
    public function count_by_form_ids(array $formIds): array
    {
        $formIds = array_values(array_filter(array_map('intval', $formIds), static fn (int $id): bool => $id > 0));
        if ($formIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($formIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT form_id, COUNT(*) AS cnt
             FROM {$this->prefix}contact_fields
             WHERE form_id IN ({$placeholders})
             GROUP BY form_id"
        );
        $stmt->execute($formIds);

        $counts = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $counts[(int) ($row['form_id'] ?? 0)] = (int) ($row['cnt'] ?? 0);
        }

        return $counts;
    }

    /**
     * Standard-Felder für ein neues Formular anlegen
     */
    public function create_default_fields(int $formId): void
    {
        $defaults = [
            ['name',    'Name',      'text',     1, 1, 1, 'Ihr vollständiger Name',  'half'],
            ['email',   'E-Mail',    'email',    1, 1, 2, 'Ihre E-Mail-Adresse',     'half'],
            ['subject', 'Betreff',   'text',     0, 0, 3, 'Betreff Ihrer Nachricht', 'full'],
            ['message', 'Nachricht', 'textarea', 1, 0, 4, 'Ihre Nachricht an uns',   'full'],
        ];

        foreach ($defaults as [$name, $label, $type, $required, $system, $order, $placeholder, $width]) {
            $this->create([
                'form_id'      => $formId,
                'field_name'   => $name,
                'field_label'  => $label,
                'field_type'   => $type,
                'is_required'  => $required,
                'is_system'    => $system,
                'field_order'  => $order,
                'placeholder'  => $placeholder,
                'field_width'  => $width,
            ]);
        }
    }

    /**
     * Felder eines Formulars duplizieren
     */
    public function duplicate_fields(int $sourceFormId, int $targetFormId): void
    {
        $fields = $this->get_by_form($sourceFormId);

        foreach ($fields as $field) {
            unset($field['id'], $field['created_at']);
            $field['form_id'] = $targetFormId;
            $this->create($field);
        }
    }

    // ── Hilfsmethoden ─────────────────────────────────────────────────────────

    /**
     * Feldnamen aus Label generieren
     */
    private function sanitize_field_name(string $label): string
    {
        $name = strtolower(trim($label));
        $name = preg_replace('/[äÄ]/', 'ae', $name);
        $name = preg_replace('/[öÖ]/', 'oe', $name);
        $name = preg_replace('/[üÜ]/', 'ue', $name);
        $name = preg_replace('/ß/', 'ss', $name);
        $name = preg_replace('/[^a-z0-9]+/', '_', $name);
        $name = trim($name, '_');

        return $name ?: 'field';
    }
}
