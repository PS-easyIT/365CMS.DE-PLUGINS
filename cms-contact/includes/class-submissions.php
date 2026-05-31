<?php
/**
 * CMS Contact – Submissions Service
 *
 * Verwaltet eingehende Kontaktanfragen (Speicherung, Abfrage, E-Mail-Versand).
 *
 * @package CMS_Contact
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Contact_Submissions
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

    // ── Erstellen ─────────────────────────────────────────────────────────────

    /**
     * Neue Submission speichern
     */
    public function create(array $data, array $meta = []): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->prefix}contact_submissions
             (form_id, user_id, sender_name, sender_email, subject, message, user_agent, ip_address, status, is_spam)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            (int) $data['form_id'],
            !empty($data['user_id']) ? (int) $data['user_id'] : null,
            $data['sender_name']  ?? null,
            $data['sender_email'] ?? null,
            $data['subject']      ?? null,
            $data['message']      ?? null,
            $data['user_agent']   ?? (substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)),
            $data['ip_address']   ?? $this->get_client_ip(),
            'unread',
            (int) ($data['is_spam'] ?? 0),
        ]);

        $submissionId = (int) $this->pdo->lastInsertId();

        // Meta-Daten speichern
        if (!empty($meta)) {
            $this->save_meta($submissionId, $meta);
        }

        return $submissionId;
    }

    /**
     * Meta-Daten zu einer Submission speichern
     */
    public function save_meta(int $submissionId, array $meta): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->prefix}contact_submission_meta (submission_id, meta_key, meta_value) VALUES (?, ?, ?)"
        );

        foreach ($meta as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $stmt->execute([$submissionId, $key, (string) $value]);
        }
    }

    // ── Lesen ─────────────────────────────────────────────────────────────────

    /**
     * Submissions mit Filtern und Paginierung
     */
    public function get_all(array $filters = [], int $offset = 0, int $limit = 20): array
    {
        $offset = max(0, $offset);
        $limit = max(1, $limit);
        $where  = [];
        $params = [];

        if (!empty($filters['form_id'])) {
            $where[]  = 's.form_id = ?';
            $params[] = (int) $filters['form_id'];
        }

        if (!empty($filters['status'])) {
            $where[]  = 's.status = ?';
            $params[] = $filters['status'];
        }

        if (isset($filters['is_spam'])) {
            $where[]  = 's.is_spam = ?';
            $params[] = (int) $filters['is_spam'];
        }

        if (!empty($filters['search'])) {
            $where[]  = '(s.sender_name LIKE ? OR s.sender_email LIKE ? OR s.subject LIKE ? OR s.message LIKE ? OR s.ip_address LIKE ?)';
            $search   = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters['date_from'])) {
            $where[]  = 's.created_at >= ?';
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $where[]  = 's.created_at <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT s.*, f.title AS form_title, f.slug AS form_slug
                FROM {$this->prefix}contact_submissions s
                LEFT JOIN {$this->prefix}contact_forms f ON f.id = s.form_id
                {$whereSQL}
                ORDER BY s.created_at DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }
        $stmt->bindValue(count($params) + 1, $limit, \PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Anzahl mit Filtern
     */
    public function count(array $filters = []): int
    {
        $where  = [];
        $params = [];

        if (!empty($filters['form_id'])) {
            $where[]  = 'form_id = ?';
            $params[] = (int) $filters['form_id'];
        }

        if (!empty($filters['status'])) {
            $where[]  = 'status = ?';
            $params[] = $filters['status'];
        }

        if (isset($filters['is_spam'])) {
            $where[]  = 'is_spam = ?';
            $params[] = (int) $filters['is_spam'];
        }

        if (!empty($filters['search'])) {
            $where[]  = '(sender_name LIKE ? OR sender_email LIKE ? OR subject LIKE ? OR message LIKE ? OR ip_address LIKE ?)';
            $search   = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM {$this->prefix}contact_submissions {$whereSQL}"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Einzelne Submission per ID
     */
    public function get_by_id(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT s.*, f.title AS form_title, f.slug AS form_slug
             FROM {$this->prefix}contact_submissions s
             LEFT JOIN {$this->prefix}contact_forms f ON f.id = s.form_id
             WHERE s.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Einzelne Submission eines Users per ID.
     */
    public function get_user_submission_by_id(int $userId, int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT s.*, f.title AS form_title, f.slug AS form_slug
             FROM {$this->prefix}contact_submissions s
             LEFT JOIN {$this->prefix}contact_forms f ON f.id = s.form_id
             WHERE s.id = ? AND s.user_id = ?
             LIMIT 1"
        );
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Meta-Daten einer Submission laden
     */
    public function get_meta(int $submissionId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT meta_key, meta_value FROM {$this->prefix}contact_submission_meta WHERE submission_id = ? ORDER BY id"
        );
        $stmt->execute([$submissionId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $meta = [];
        foreach ($rows as $row) {
            $meta[$row['meta_key']] = $row['meta_value'];
        }
        return $meta;
    }

    /**
     * Meta-Daten für mehrere Submissions in einem Rutsch laden.
     *
     * @param int[] $submissionIds
     * @return array<int, array<string, string>>
     */
    public function get_meta_for_submissions(array $submissionIds): array
    {
        $submissionIds = array_values(array_filter(array_map('intval', $submissionIds), static fn (int $id): bool => $id > 0));
        if ($submissionIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($submissionIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT submission_id, meta_key, meta_value
             FROM {$this->prefix}contact_submission_meta
             WHERE submission_id IN ({$placeholders})
             ORDER BY submission_id, id"
        );
        $stmt->execute($submissionIds);

        $grouped = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $submissionId = (int) ($row['submission_id'] ?? 0);
            if ($submissionId <= 0) {
                continue;
            }

            $grouped[$submissionId][(string) $row['meta_key']] = (string) ($row['meta_value'] ?? '');
        }

        return $grouped;
    }

    // ── Status ────────────────────────────────────────────────────────────────

    /**
     * Status einer Submission ändern
     */
    public function update_status(int $id, string $status): bool
    {
        $allowed = ['unread', 'read', 'replied', 'archived', 'spam'];
        if (!in_array($status, $allowed, true)) {
            $this->log_security_event('invalid_status_attempt', null, $status, ['submission_id' => $id]);
            return false;
        }

        $extra = '';
        if ($status === 'read') {
            $extra = ', read_at = NOW()';
        } elseif ($status === 'replied') {
            $extra = ', replied_at = NOW()';
        } elseif ($status === 'spam') {
            $extra = ', is_spam = 1';
        }

        $stmt = $this->pdo->prepare(
            "UPDATE {$this->prefix}contact_submissions SET status = ?{$extra} WHERE id = ?"
        );
        return $stmt->execute([$status, $id]);
    }

    /**
     * Als gelesen markieren
     */
    public function mark_read(int $id): bool
    {
        return $this->update_status($id, 'read');
    }

    /**
     * Submission eines Users als gelesen markieren.
     */
    public function mark_user_submission_read(int $userId, int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->prefix}contact_submissions
             SET status = 'read', read_at = NOW()
             WHERE id = ? AND user_id = ? AND status = 'unread'"
        );

        return $stmt->execute([$id, $userId]);
    }

    // ── Löschen ───────────────────────────────────────────────────────────────

    /**
     * Submission löschen (CASCADE löscht auch Meta)
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->prefix}contact_submissions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Eine Submission eines Users löschen.
     */
    public function delete_user_submission(int $userId, int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM {$this->prefix}contact_submissions WHERE id = ? AND user_id = ?"
        );

        return $stmt->execute([$id, $userId]);
    }

    /**
     * Alle Submissions eines bestimmten Users löschen (DSGVO)
     */
    public function delete_user_submissions(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM {$this->prefix}contact_submissions WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
    }

    /**
     * Alle Submissions eines Users exportieren (DSGVO)
     */
    public function get_user_submissions(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT s.*, f.title AS form_title, f.slug AS form_slug
             FROM {$this->prefix}contact_submissions s
             LEFT JOIN {$this->prefix}contact_forms f ON f.id = s.form_id
             WHERE s.user_id = ?
             ORDER BY s.created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Alte Submissions automatisch löschen
     */
    public function cleanup_old(int $days): int
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM {$this->prefix}contact_submissions WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        $stmt->execute([$days]);
        return $stmt->rowCount();
    }

    /**
     * Alle als Spam markierten Submissions löschen
     */
    public function cleanup_spam(): int
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM {$this->prefix}contact_submissions WHERE status = 'spam'"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }

    // ── E-Mail-Versand ────────────────────────────────────────────────────────

    /**
     * Benachrichtigungs-E-Mail an den Empfänger senden
     */
    public function send_notification(array $form, array $submission, array $meta = []): bool
    {
        $recipient = trim((string) ($form['recipient'] ?? ''));
        if (empty($recipient)) {
            $recipient = trim($this->get_setting('admin_email'));
        }

        if (empty($recipient)) {
            // Legacy-Fallback aus älteren Installationen laden
            $recipient = trim($this->get_setting('global_recipient'));
        }

        if (empty($recipient) || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->log_error('notification skipped due to missing recipient');
            return false;
        }

        $subjectPrefix = $form['subject_prefix'] ?? '[Kontakt]';
        $subject       = $subjectPrefix . ' ' . ($submission['subject'] ?? 'Neue Kontaktanfrage');

        // E-Mail-Body erstellen
        $body  = "Neue Kontaktanfrage über das Formular: " . ($form['title'] ?? 'Unbekannt') . "\n\n";
        $body .= "Name: "    . ($submission['sender_name']  ?? '-') . "\n";
        $body .= "E-Mail: "  . ($submission['sender_email'] ?? '-') . "\n";
        $body .= "Betreff: " . ($submission['subject']      ?? '-') . "\n\n";
        $body .= "Nachricht:\n" . ($submission['message']   ?? '-') . "\n\n";
        $body .= "IP-Adresse: " . ($submission['ip_address'] ?? '-') . "\n";
        $body .= "User-Agent: " . ($submission['user_agent'] ?? '-') . "\n\n";

        if (!empty($meta)) {
            $body .= "Zusätzliche Felder:\n";
            $body .= str_repeat('-', 40) . "\n";
            foreach ($meta as $key => $value) {
                $body .= ucfirst(str_replace('_', ' ', $key)) . ": " . $value . "\n";
            }
        }

        $body .= "\n---\nGesendet am: " . date('d.m.Y H:i') . "\n";

        $headers = [
            'X-365CMS-Source' => 'cms-contact-notification',
            'X-365CMS-Test-Source' => 'cms-contact-notification',
        ];

        $fromHeader = $this->build_from_header();
        if ($fromHeader !== null) {
            $headers['From'] = $fromHeader;
        }

        $replyTo = $submission['sender_email'] ?? '';
        if (filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $headers['Reply-To'] = $replyTo;
        }

        // CC-Empfänger
        if (!empty($form['cc_recipients'])) {
            $ccList = array_map('trim', explode(',', $form['cc_recipients']));
            $validCc = [];
            foreach ($ccList as $cc) {
                if (filter_var($cc, FILTER_VALIDATE_EMAIL)) {
                    $validCc[] = $cc;
                }
            }

            if ($validCc !== []) {
                $headers['Cc'] = implode(', ', $validCc);
            }
        }

        return $this->send_plain_mail($recipient, $subject, $body, $headers);
    }

    /**
     * Bestätigungs-E-Mail an den Absender
     */
    public function send_confirmation(array $form, array $submission): bool
    {
        unset($form);

        if ($this->get_setting('send_confirmation') !== '1') {
            return false;
        }

        $senderEmail = $submission['sender_email'] ?? '';
        if (empty($senderEmail) || !filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $fromName  = $this->get_setting('from_name') ?: '365CMS Kontakt';

        $subject = 'Ihre Kontaktanfrage wurde empfangen';
        $body    = "Hallo " . ($submission['sender_name'] ?? '') . ",\n\n";
        $body   .= "vielen Dank für Ihre Nachricht! Wir haben Ihre Anfrage erhalten und werden uns schnellstmöglich bei Ihnen melden.\n\n";
        $body   .= "Mit freundlichen Grüßen\n";
        $body   .= ($fromName) . "\n";

        $headers = [
            'X-365CMS-Source' => 'cms-contact-confirmation',
            'X-365CMS-Test-Source' => 'cms-contact-confirmation',
        ];

        $fromHeader = $this->build_from_header();
        if ($fromHeader !== null) {
            $headers['From'] = $fromHeader;
        }

        return $this->send_plain_mail($senderEmail, $subject, $body, $headers);
    }

    // ── Statistiken ───────────────────────────────────────────────────────────

    /**
     * Globale Statistiken
     */
    public function get_global_stats(): array
    {
        $p = $this->prefix;

        $stmt = $this->pdo->query(
            "SELECT
                COUNT(*)                                             AS total,
                SUM(CASE WHEN status = 'unread' THEN 1 ELSE 0 END)  AS unread,
                SUM(CASE WHEN is_spam = 1 THEN 1 ELSE 0 END)        AS spam,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS last_7_days,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS last_30_days
             FROM {$p}contact_submissions"
        );
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return [
            'total'       => (int) ($row['total'] ?? 0),
            'unread'      => (int) ($row['unread'] ?? 0),
            'spam'        => (int) ($row['spam'] ?? 0),
            'last_7_days' => (int) ($row['last_7_days'] ?? 0),
            'last_30_days' => (int) ($row['last_30_days'] ?? 0),
        ];
    }

    /**
     * Trend-Daten (letzte 7 Tage)
     */
    public function get_trend(int $days = 7): array
    {
        $p = $this->prefix;

        $stmt = $this->pdo->prepare(
            "SELECT DATE(created_at) AS day, COUNT(*) AS cnt
             FROM {$p}contact_submissions
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) AND is_spam = 0
             GROUP BY DATE(created_at)
             ORDER BY day"
        );
        $stmt->execute([$days]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $trend = [];
        foreach ($rows as $row) {
            $trend[$row['day']] = (int) $row['cnt'];
        }
        return $trend;
    }

    /**
     * Sicherheitsereignis protokollieren (z. B. CSRF/Captcha/Rate-Limit/Antispam).
     *
     * @param array<string, scalar|null> $details
     */
    public function log_security_event(string $eventType, ?int $formId = null, string $reason = '', array $details = []): void
    {
        try {
            $normalizedType = strtolower(trim($eventType));
            if (!preg_match('/^[a-z0-9_-]{2,50}$/', $normalizedType)) {
                $normalizedType = 'unknown';
            }

            $normalizedReason = trim($reason);
            if ($normalizedReason === '') {
                $normalizedReason = null;
            } else {
                $normalizedReason = mb_substr($normalizedReason, 0, 100);
            }

            $eventDetails = [];
            foreach ($details as $key => $value) {
                if ($value === null) {
                    continue;
                }
                $eventDetails[(string) $key] = (string) $value;
            }

            $detailsJson = $eventDetails !== []
                ? (json_encode($eventDetails, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null)
                : null;

            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->prefix}contact_security_events
                 (form_id, event_type, reason, ip_address, user_agent, details_json)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $formId !== null && $formId > 0 ? $formId : null,
                $normalizedType,
                $normalizedReason,
                $this->get_client_ip(),
                mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                $detailsJson,
            ]);
        } catch (\Throwable $e) {
            $this->log_error('failed to log security event', ['event_type' => $eventType, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Sicherheits-Statistik für Dashboard.
     *
     * @return array<string,int>
     */
    public function get_security_event_stats(int $hours = 24): array
    {
        $hours = max(1, $hours);

        $stmt = $this->pdo->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN event_type = 'csrf_failed' THEN 1 ELSE 0 END) AS csrf_failed,
                SUM(CASE WHEN event_type = 'captcha_failed' THEN 1 ELSE 0 END) AS captcha_failed,
                SUM(CASE WHEN event_type = 'rate_limited' THEN 1 ELSE 0 END) AS rate_limited,
                SUM(CASE WHEN event_type = 'antispam_rejected' THEN 1 ELSE 0 END) AS antispam_rejected,
                SUM(CASE WHEN event_type = 'invalid_status_attempt' THEN 1 ELSE 0 END) AS invalid_status_attempt
             FROM {$this->prefix}contact_security_events
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)"
        );
        $stmt->execute([$hours]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'csrf_failed' => (int) ($row['csrf_failed'] ?? 0),
            'captcha_failed' => (int) ($row['captcha_failed'] ?? 0),
            'rate_limited' => (int) ($row['rate_limited'] ?? 0),
            'antispam_rejected' => (int) ($row['antispam_rejected'] ?? 0),
            'invalid_status_attempt' => (int) ($row['invalid_status_attempt'] ?? 0),
        ];
    }

    public function get_recent_security_events(int $limit = 20): array
    {
        $limit = max(1, min($limit, 100));
        $stmt = $this->pdo->prepare(
            "SELECT e.*, f.title AS form_title
             FROM {$this->prefix}contact_security_events e
             LEFT JOIN {$this->prefix}contact_forms f ON f.id = e.form_id
             ORDER BY e.created_at DESC, e.id DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ── Hilfsmethoden ─────────────────────────────────────────────────────────

    private function get_setting(string $key): string
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT setting_value FROM {$this->prefix}contact_settings WHERE setting_key = ?"
            );
            $stmt->execute([$key]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ? (string) $row['setting_value'] : '';
        } catch (\Throwable $e) {
            $this->log_error('failed to fetch setting', ['key' => $key, 'error' => $e->getMessage()]);
            return '';
        }
    }

    private function build_from_header(): ?string
    {
        $fromEmail = trim($this->get_setting('from_email'));
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $fromName = trim(str_replace(["\r", "\n", '"', '\\'], ' ', $this->get_setting('from_name')));
        $fromName = preg_replace('/\s+/u', ' ', $fromName) ?? '';
        if ($fromName === '') {
            return $fromEmail;
        }

        return '"' . $fromName . '" <' . $fromEmail . '>';
    }

    private function get_client_ip(): string
    {
        if (class_exists('CMS\\Security')) {
            return (string) \CMS\Security::getClientIp();
        }

        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    private function send_plain_mail(string $to, string $subject, string $body, array $headers = []): bool
    {
        if (class_exists('\\CMS\\Services\\MailQueueService')) {
            $queue = \CMS\Services\MailQueueService::getInstance();
            if ($queue->shouldQueue($headers)) {
                $result = $queue->enqueuePlain($to, $subject, $body, $headers, null, 'cms-contact');
                if (!empty($result['success'])) {
                    return true;
                }
            }
        }

        if (class_exists('\\CMS\\Services\\MailService')) {
            return \CMS\Services\MailService::getInstance()->sendPlain($to, $subject, $body, $headers);
        }

        if (function_exists('cms_mail')) {
            $headers['Content-Type'] = 'text/plain; charset=UTF-8';
            return cms_mail($to, $subject, $body, $headers);
        }

        $this->log_error('mail send skipped because no mail backend is available');
        return false;
    }

    /**
     * @param array<string, scalar|null> $context
     */
    private function log_error(string $message, array $context = []): void
    {
        $parts = [];
        foreach ($context as $key => $value) {
            if ($value === null) {
                continue;
            }
            $parts[] = $key . '=' . (string) $value;
        }

        $contextText = $parts !== [] ? ' [' . implode(' ', $parts) . ']' : '';
        error_log('[cms-contact][submissions] ' . $message . $contextText);
    }
}
