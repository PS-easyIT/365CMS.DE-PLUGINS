<?php
/**
 * CMS Contact – Forms Service
 *
 * CRUD-Operationen für Kontaktformulare.
 *
 * @package CMS_Contact
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Contact_Forms
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

    // ── Verfügbare Templates ──────────────────────────────────────────────────

    public static function get_available_templates(): array
    {
        return [
            'classic'   => ['name' => 'Classic',       'icon' => '📋', 'description' => 'Klassisches Kontaktformular mit klarer Struktur'],
            'modern'    => ['name' => 'Modern',        'icon' => '✨', 'description' => 'Modernes Card-basiertes Design mit Animationen'],
            'split'     => ['name' => 'Split Screen',  'icon' => '📐', 'description' => 'Zweispaltig: Info-Bereich links, Formular rechts'],
            'minimal'   => ['name' => 'Minimal',       'icon' => '🎯', 'description' => 'Reduziertes, cleanes Design ohne Ablenkung'],
            'business'  => ['name' => 'Business',      'icon' => '🏢', 'description' => 'Professionelles Business-Layout mit Kartenbereich'],
            'fullwidth' => ['name' => 'Fullwidth',     'icon' => '🖥️', 'description' => 'Breitbild-Hero mit zentriertem Formular'],
        ];
    }

    // ── CRUD ──────────────────────────────────────────────────────────────────

    /**
     * Alle Formulare laden
     */
    public function get_all(string $status = ''): array
    {
        $sql    = "SELECT * FROM {$this->prefix}contact_forms";
        $params = [];

        if ($status !== '') {
            $sql   .= " WHERE status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Einzelnes Formular per ID
     */
    public function get_by_id(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->prefix}contact_forms WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Formular per Slug
     */
    public function get_by_slug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->prefix}contact_forms WHERE slug = ? AND status = 'active'");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Neues Formular erstellen
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->prefix}contact_forms
             (title, slug, template, description, recipient, cc_recipients, subject_prefix,
              success_message, redirect_url, enable_captcha, enable_honeypot, rate_limit, status, custom_css, settings_json)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $data['title']           ?? 'Neues Formular',
            $data['slug']            ?? $this->generate_slug($data['title'] ?? 'kontakt'),
            $data['template']        ?? 'classic',
            $data['description']     ?? null,
            $data['recipient']       ?? null,
            $data['cc_recipients']   ?? null,
            $data['subject_prefix']  ?? null,
            $data['success_message'] ?? 'Vielen Dank für Ihre Nachricht!',
            $data['redirect_url']    ?? null,
            (int) ($data['enable_captcha']  ?? 0),
            (int) ($data['enable_honeypot'] ?? 1),
            (int) ($data['rate_limit']      ?? 3),
            $data['status']          ?? 'active',
            $data['custom_css']      ?? null,
            isset($data['settings_json']) ? json_encode($data['settings_json']) : null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Formular aktualisieren
     */
    public function update(int $id, array $data): bool
    {
        $sets   = [];
        $params = [];

        $allowed = [
            'title', 'slug', 'template', 'description', 'recipient', 'cc_recipients',
            'subject_prefix', 'success_message', 'redirect_url', 'enable_captcha',
            'enable_honeypot', 'rate_limit', 'status', 'custom_css', 'settings_json',
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[]   = "{$field} = ?";
                $value    = $data[$field];

                if ($field === 'settings_json' && is_array($value)) {
                    $value = json_encode($value);
                }
                if (in_array($field, ['enable_captcha', 'enable_honeypot', 'rate_limit'], true)) {
                    $value = (int) $value;
                }

                $params[] = $value;
            }
        }

        if (empty($sets)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE {$this->prefix}contact_forms SET " . implode(', ', $sets) . " WHERE id = ?";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Formular löschen (CASCADE löscht Felder + Submissions)
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->prefix}contact_forms WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Anzahl der Formulare
     */
    public function count(string $status = ''): int
    {
        $sql    = "SELECT COUNT(*) FROM {$this->prefix}contact_forms";
        $params = [];

        if ($status !== '') {
            $sql   .= " WHERE status = ?";
            $params[] = $status;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Prüft ob ein Slug bereits vergeben ist
     */
    public function slug_exists(string $slug, int $excludeId = 0): bool
    {
        $sql    = "SELECT id FROM {$this->prefix}contact_forms WHERE slug = ?";
        $params = [$slug];

        if ($excludeId > 0) {
            $sql     .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    /**
     * Slug aus Titel generieren
     */
    public function generate_slug(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[äÄ]/', 'ae', $slug);
        $slug = preg_replace('/[öÖ]/', 'oe', $slug);
        $slug = preg_replace('/[üÜ]/', 'ue', $slug);
        $slug = preg_replace('/ß/', 'ss', $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        if (empty($slug)) {
            $slug = 'kontakt';
        }

        // Eindeutigkeit sicherstellen
        $base = $slug;
        $i    = 1;
        while ($this->slug_exists($slug)) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    /**
     * Statistiken für ein Formular
     */
    public function get_stats(int $formId): array
    {
        $p = $this->prefix;

        $stmt = $this->pdo->prepare(
            "SELECT
                COUNT(*)                                           AS total,
                SUM(CASE WHEN status = 'unread' THEN 1 ELSE 0 END)  AS unread,
                SUM(CASE WHEN status = 'read'   THEN 1 ELSE 0 END)  AS read_count,
                SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) AS replied,
                SUM(CASE WHEN is_spam = 1 THEN 1 ELSE 0 END)        AS spam,
                MAX(created_at)                                      AS last_submission
             FROM {$p}contact_submissions
             WHERE form_id = ?"
        );
        $stmt->execute([$formId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return [
            'total'           => (int) ($row['total'] ?? 0),
            'unread'          => (int) ($row['unread'] ?? 0),
            'read'            => (int) ($row['read_count'] ?? 0),
            'replied'         => (int) ($row['replied'] ?? 0),
            'spam'            => (int) ($row['spam'] ?? 0),
            'last_submission' => $row['last_submission'] ?? null,
        ];
    }
}
