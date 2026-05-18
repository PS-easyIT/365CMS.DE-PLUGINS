<?php
/**
 * @package CMS_Newsletter
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Newsletter_Repository
{
    private static ?self $instance = null;

    private \CMS\Database $db;
    private string $prefix;

    /** @var array<string,string> */
    private const DEFAULT_SETTINGS = [
        'sender_name' => '365 Network',
        'sender_email' => 'newsletter@example.com',
        'reply_to_email' => 'reply@example.com',
        'require_double_opt_in' => '1',
        'default_segment' => 'general',
        'archive_title' => 'Newsletter',
        'archive_description' => 'Bleib über neue Inhalte, Events und Produkt-Updates auf dem Laufenden.',
        'subscribe_intro' => 'Melde dich für Produkt-News, Event-Hinweise und neue Fachbeiträge an.',
        'footer_note' => 'Du kannst dich jederzeit wieder mit einem Klick abmelden.',
    ];

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->db = \CMS\Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    public function seed_defaults(): void
    {
        foreach (self::DEFAULT_SETTINGS as $key => $value) {
            $stmt = $this->db->prepare("SELECT id FROM {$this->prefix}newsletter_settings WHERE setting_key = ? LIMIT 1");
            $stmt->execute([$key]);
            if ($stmt->fetchColumn() !== false) {
                continue;
            }

            $insert = $this->db->prepare("INSERT INTO {$this->prefix}newsletter_settings (setting_key, setting_value) VALUES (?, ?)");
            $insert->execute([$key, $value]);
        }

        $templateStmt = $this->db->prepare("SELECT id FROM {$this->prefix}newsletter_templates LIMIT 1");
        $templateStmt->execute();
        if ($templateStmt->fetchColumn() === false) {
            $insert = $this->db->prepare("INSERT INTO {$this->prefix}newsletter_templates (name, subject, content_html, content_text, status) VALUES (?, ?, ?, ?, ?)");
            $insert->execute([
                'Standard-Template',
                'Neuigkeiten von 365CMS',
                '<h2>Hallo {{first_name}}</h2><p>hier sind die aktuellen Neuigkeiten aus dem 365CMS-Universum.</p><p><a href="{{site_url}}">Zur Website</a></p>',
                "Hallo {{first_name}},\n\nhier sind die aktuellen Neuigkeiten aus dem 365CMS-Universum.\n\n{{site_url}}",
                'active',
            ]);
        }
    }

    public function get_dashboard_stats(): array
    {
        return [
            'subscribers' => (int) $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}newsletter_subscribers"),
            'active_subscribers' => (int) $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}newsletter_subscribers WHERE status = 'active'"),
            'pending_subscribers' => (int) $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}newsletter_subscribers WHERE status = 'pending'"),
            'templates' => (int) $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}newsletter_templates"),
            'campaigns' => (int) $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}newsletter_campaigns"),
            'ready_campaigns' => (int) $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}newsletter_campaigns WHERE status IN ('ready', 'scheduled')"),
            'sent_entries' => (int) $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}newsletter_sends WHERE status IN ('sent', 'opened', 'clicked')"),
        ];
    }

    public function get_settings(): array
    {
        $stmt = $this->db->prepare("SELECT setting_key, setting_value FROM {$this->prefix}newsletter_settings");
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $settings = self::DEFAULT_SETTINGS;

        foreach ($rows as $row) {
            $settings[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }

        return $settings;
    }

    public function save_settings(array $post): array
    {
        $settings = [
            'sender_name' => $this->clean_text($post['sender_name'] ?? ''),
            'sender_email' => $this->clean_email($post['sender_email'] ?? ''),
            'reply_to_email' => $this->clean_email($post['reply_to_email'] ?? ''),
            'require_double_opt_in' => !empty($post['require_double_opt_in']) ? '1' : '0',
            'default_segment' => $this->clean_slug($post['default_segment'] ?? 'general'),
            'archive_title' => $this->clean_text($post['archive_title'] ?? 'Newsletter'),
            'archive_description' => $this->clean_textarea($post['archive_description'] ?? ''),
            'subscribe_intro' => $this->clean_textarea($post['subscribe_intro'] ?? ''),
            'footer_note' => $this->clean_textarea($post['footer_note'] ?? ''),
        ];

        foreach ($settings as $key => $value) {
            $exists = $this->db->prepare("SELECT id FROM {$this->prefix}newsletter_settings WHERE setting_key = ? LIMIT 1");
            $exists->execute([$key]);
            if ($exists->fetchColumn() !== false) {
                $stmt = $this->db->prepare("UPDATE {$this->prefix}newsletter_settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->execute([$value, $key]);
            } else {
                $stmt = $this->db->prepare("INSERT INTO {$this->prefix}newsletter_settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->execute([$key, $value]);
            }
        }

        return ['success' => true, 'message' => 'Newsletter-Einstellungen gespeichert.'];
    }

    public function get_subscribers(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->prefix}newsletter_subscribers ORDER BY created_at DESC, id DESC");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function get_recent_subscribers(int $limit = 6): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->prefix}newsletter_subscribers ORDER BY created_at DESC, id DESC LIMIT " . max(1, $limit));
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function get_subscriber(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->prefix}newsletter_subscribers WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function save_subscriber(array $post): array
    {
        $id = (int) ($post['subscriber_id'] ?? 0);
        $existing = $id > 0 ? $this->get_subscriber($id) : null;
        $email = $this->clean_email($post['email'] ?? '');
        if ($email === '') {
            return ['success' => false, 'error' => 'Bitte eine gültige E-Mail-Adresse angeben.'];
        }

        $duplicate = $this->db->prepare("SELECT id FROM {$this->prefix}newsletter_subscribers WHERE email = ? AND id != ? LIMIT 1");
        $duplicate->execute([$email, $id]);
        if ($duplicate->fetchColumn() !== false) {
            return ['success' => false, 'error' => 'Diese E-Mail-Adresse ist bereits im Newsletter vorhanden.'];
        }

        $status = (string) ($post['status'] ?? 'pending');
        if (!in_array($status, ['pending', 'active', 'unsubscribed', 'bounced'], true)) {
            $status = 'pending';
        }

        $token = (string) ($existing['optin_token'] ?? '');
        if ($token === '') {
            $token = $this->create_token();
        }

        $values = [
            $email,
            $this->clean_text($post['first_name'] ?? ''),
            $this->clean_text($post['last_name'] ?? ''),
            $status,
            $this->clean_text($post['source'] ?? 'admin'),
            $this->clean_slug($post['segment_slug'] ?? 'general'),
            $token,
            $status === 'active' ? date('Y-m-d H:i:s') : null,
        ];

        if ($id > 0) {
            $stmt = $this->db->prepare("UPDATE {$this->prefix}newsletter_subscribers SET email = ?, first_name = ?, last_name = ?, status = ?, source = ?, segment_slug = ?, optin_token = ?, confirmed_at = ? WHERE id = ?");
            $stmt->execute([...$values, $id]);
            return ['success' => true, 'message' => 'Abonnent aktualisiert.'];
        }

        $stmt = $this->db->prepare("INSERT INTO {$this->prefix}newsletter_subscribers (email, first_name, last_name, status, source, segment_slug, optin_token, confirmed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($values);
        return ['success' => true, 'message' => 'Abonnent angelegt.'];
    }

    public function delete_subscriber(int $id): array
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->prefix}newsletter_subscribers WHERE id = ?");
        $stmt->execute([$id]);
        return ['success' => true, 'message' => 'Abonnent gelöscht.'];
    }

    public function confirm_subscriber(string $token): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->prefix}newsletter_subscribers SET status = 'active', confirmed_at = NOW() WHERE optin_token = ? AND status = 'pending'");
        $stmt->execute([$token]);
        return $stmt->rowCount() > 0;
    }

    public function unsubscribe_by_token(string $token): void
    {
        $stmt = $this->db->prepare("UPDATE {$this->prefix}newsletter_subscribers SET status = 'unsubscribed' WHERE optin_token = ?");
        $stmt->execute([$token]);
    }

    public function get_templates(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->prefix}newsletter_templates ORDER BY updated_at DESC, id DESC");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function get_template(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->prefix}newsletter_templates WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function save_template(array $post): array
    {
        $id = (int) ($post['template_id'] ?? 0);
        $name = $this->clean_text($post['name'] ?? '');
        $subject = $this->clean_text($post['subject'] ?? '');
        if ($name === '' || $subject === '') {
            return ['success' => false, 'error' => 'Template-Name und Betreff sind Pflicht.'];
        }

        $status = (string) ($post['status'] ?? 'draft');
        if (!in_array($status, ['draft', 'active'], true)) {
            $status = 'draft';
        }

        $values = [
            $name,
            $subject,
            $this->clean_html($post['content_html'] ?? ''),
            $this->clean_textarea($post['content_text'] ?? ''),
            $status,
        ];

        if ($id > 0) {
            $stmt = $this->db->prepare("UPDATE {$this->prefix}newsletter_templates SET name = ?, subject = ?, content_html = ?, content_text = ?, status = ? WHERE id = ?");
            $stmt->execute([...$values, $id]);
            return ['success' => true, 'message' => 'Template aktualisiert.'];
        }

        $stmt = $this->db->prepare("INSERT INTO {$this->prefix}newsletter_templates (name, subject, content_html, content_text, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute($values);
        return ['success' => true, 'message' => 'Template erstellt.'];
    }

    public function delete_template(int $id): array
    {
        $stmt = $this->db->prepare("UPDATE {$this->prefix}newsletter_campaigns SET template_id = NULL WHERE template_id = ?");
        $stmt->execute([$id]);
        $stmt = $this->db->prepare("DELETE FROM {$this->prefix}newsletter_templates WHERE id = ?");
        $stmt->execute([$id]);
        return ['success' => true, 'message' => 'Template gelöscht.'];
    }

    public function get_campaigns(): array
    {
        $sql = "SELECT c.*, t.name AS template_name
                FROM {$this->prefix}newsletter_campaigns c
                LEFT JOIN {$this->prefix}newsletter_templates t ON t.id = c.template_id
                ORDER BY COALESCE(c.scheduled_at, c.updated_at) DESC, c.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function get_recent_campaigns(int $limit = 5): array
    {
        $sql = "SELECT c.*, t.name AS template_name
                FROM {$this->prefix}newsletter_campaigns c
                LEFT JOIN {$this->prefix}newsletter_templates t ON t.id = c.template_id
                ORDER BY COALESCE(c.scheduled_at, c.updated_at) DESC, c.id DESC
                LIMIT " . max(1, $limit);
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function get_campaign(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->prefix}newsletter_campaigns WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function save_campaign(array $post): array
    {
        $id = (int) ($post['campaign_id'] ?? 0);
        $name = $this->clean_text($post['name'] ?? '');
        $subject = $this->clean_text($post['subject'] ?? '');
        if ($name === '' || $subject === '') {
            return ['success' => false, 'error' => 'Kampagnenname und Betreff sind Pflicht.'];
        }

        $status = (string) ($post['status'] ?? 'draft');
        if (!in_array($status, ['draft', 'ready', 'scheduled', 'sent'], true)) {
            $status = 'draft';
        }

        $segment = $this->clean_slug($post['segment_slug'] ?? '');
        $recipientCount = $this->count_active_subscribers($segment);
        $scheduledAt = trim((string) ($post['scheduled_at'] ?? ''));
        $scheduledAt = $scheduledAt !== '' ? str_replace('T', ' ', $scheduledAt) . ':00' : null;
        $templateId = max(0, (int) ($post['template_id'] ?? 0));
        if ($templateId === 0) {
            $templateId = null;
        }

        $values = [
            $templateId,
            $name,
            $subject,
            $this->clean_text($post['preview_text'] ?? ''),
            $segment,
            $status,
            $scheduledAt,
            $status === 'sent' ? date('Y-m-d H:i:s') : null,
            $recipientCount,
        ];

        if ($id > 0) {
            $stmt = $this->db->prepare("UPDATE {$this->prefix}newsletter_campaigns SET template_id = ?, name = ?, subject = ?, preview_text = ?, segment_slug = ?, status = ?, scheduled_at = ?, sent_at = ?, recipient_count = ? WHERE id = ?");
            $stmt->execute([...$values, $id]);
            return ['success' => true, 'message' => 'Kampagne aktualisiert.'];
        }

        $stmt = $this->db->prepare("INSERT INTO {$this->prefix}newsletter_campaigns (template_id, name, subject, preview_text, segment_slug, status, scheduled_at, sent_at, recipient_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($values);
        return ['success' => true, 'message' => 'Kampagne gespeichert.'];
    }

    public function delete_campaign(int $id): array
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->prefix}newsletter_sends WHERE campaign_id = ?");
        $stmt->execute([$id]);
        $stmt = $this->db->prepare("DELETE FROM {$this->prefix}newsletter_campaigns WHERE id = ?");
        $stmt->execute([$id]);
        return ['success' => true, 'message' => 'Kampagne gelöscht.'];
    }

    private function count_active_subscribers(string $segment = ''): int
    {
        if ($segment !== '') {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->prefix}newsletter_subscribers WHERE status = 'active' AND segment_slug = ?");
            $stmt->execute([$segment]);
            return (int) $stmt->fetchColumn();
        }

        return (int) $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}newsletter_subscribers WHERE status = 'active'");
    }

    private function clean_text(string $value): string
    {
        return mb_substr(trim(strip_tags($value)), 0, 255);
    }

    private function clean_textarea(string $value): string
    {
        return mb_substr(trim(strip_tags($value)), 0, 2000);
    }

    private function clean_html(string $value): string
    {
        $html = trim(strip_tags($value, '<p><a><strong><em><ul><ol><li><br><h2><h3><h4><table><thead><tbody><tr><td><th>'));
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace('/\s+style\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace_callback('/\s+href\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', static function (array $matches): string {
            $href = html_entity_decode((string) ($matches[2] ?? $matches[3] ?? $matches[4] ?? ''), ENT_QUOTES, 'UTF-8');
            if (strlen($href) > 2048 || preg_match('/[[:cntrl:]]/', $href) === 1) {
                return '';
            }
            $scheme = strtolower((string) (parse_url($href, PHP_URL_SCHEME) ?? ''));
            if ($scheme !== '' && !in_array($scheme, ['http', 'https', 'mailto'], true)) {
                return '';
            }
            return ' href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"';
        }, $html) ?? '';

        return mb_substr($html, 0, 20000);
    }

    private function clean_slug(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9\-]+/', '-', $slug) ?? '';
        return mb_substr(trim($slug, '-') ?: 'general', 0, 80);
    }

    private function clean_email(string $value): string
    {
        $email = trim($value);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }

    private function create_token(): string
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (\Throwable) {
            return hash('sha256', (string) microtime(true) . '-' . uniqid('', true));
        }
    }
}
