<?php
/**
 * E-Mail-Digest-System für CMS Feed
 *
 * Sendet konfigurierten Empfängern zu gewählten Zeitpunkten
 * eine zusammengefasste E-Mail mit neuen Feed-Einträgen.
 *
 * @package CMS_Feed
 * @since   1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Feed_Email_Digest
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        if (class_exists('CMS\Hooks')) {
            CMS\Hooks::addAction('cms_cron_hourly', [$this, 'process_digests'], 10);
        }
    }

    /**
     * Fällige Digests verarbeiten und versenden.
     */
    public function process_digests(): array
    {
        $db      = CMS_Feed_Database::instance();
        $digests = $db->get_active_digests_due();
        $results = [
            'digests' => [],
            'member_subscriptions' => [],
        ];

        foreach ($digests as $digest) {
            $results['digests'][$digest['id']] = $this->send_digest($digest);
        }

        $now = new \DateTimeImmutable('now');
        foreach ($db->get_active_member_subscriptions() as $subscription) {
            if (!$this->is_member_subscription_due($subscription, $now)) {
                continue;
            }

            $results['member_subscriptions'][$subscription['id']] = $this->send_member_subscription($subscription, $now);
        }

        return $results;
    }

    /**
     * Einen einzelnen Digest versenden.
     */
    public function send_digest(array $digest, bool $markSent = true): bool
    {
        $db = CMS_Feed_Database::instance();
        $s  = $db->get_settings();

        $categoryIds = json_decode($digest['category_ids'] ?? '[]', true);
        if (empty($categoryIds)) {
            return false;
        }

        // Zeitpunkt seit letzter Sendung bestimmen
        $since = $digest['last_sent_at'] ?? date('Y-m-d H:i:s', strtotime('-24 hours'));

        // Items sammeln
        $allItems = [];
        foreach ($categoryIds as $catId) {
            $items = $db->get_items([
                'category_id' => (int) $catId,
                'since'       => $since,
            ], 0, (int) ($s['digest_max_items'] ?? 20));
            $allItems = array_merge($allItems, $items);
        }

        if (empty($allItems)) {
            // Keine neuen Items, trotzdem als gesendet markieren
            if ($markSent) {
                $db->update_digest_sent((int) $digest['id']);
            }
            return true;
        }

        // Nach Datum sortieren (neueste zuerst)
        usort($allItems, fn($a, $b) => strtotime($b['pub_date']) - strtotime($a['pub_date']));

        // Max Items begrenzen
        $maxItems = (int) ($s['digest_max_items'] ?? 20);
        $allItems = array_slice($allItems, 0, $maxItems);

        // E-Mail erstellen
        $subject = str_replace('{date}', date('d.m.Y'), $s['digest_subject'] ?? 'Feed-Digest – {date}');
        $html    = $this->build_email_html($digest, $allItems, $s);

        // E-Mail senden
        $fromName  = $s['digest_from_name'] ?? '365 CMS Feed';
        $fromEmail = $s['digest_from_email'] ?? '';

        $sent = $this->send_email(
            $digest['email'],
            $subject,
            $html,
            $fromName,
            $fromEmail
        );

        if ($sent && $markSent) {
            $db->update_digest_sent((int) $digest['id']);
        }

        return $sent;
    }

    /**
     * Digest-E-Mail als HTML aufbauen.
     */
    private function build_email_html(array $digest, array $items, array $settings): string
    {
        $primaryColor = htmlspecialchars((string) ($settings['color_primary'] ?? '#0891b2'), ENT_QUOTES, 'UTF-8');
        $digestName   = htmlspecialchars((string) ($digest['name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $date         = date('d.m.Y');
        $itemCount    = count($items);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$digestName} – {$date}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
    <div style="max-width:640px;margin:0 auto;padding:20px;">

        <!-- Header -->
        <div style="background:{$primaryColor};color:#fff;padding:24px 28px;border-radius:10px 10px 0 0;">
            <h1 style="margin:0;font-size:1.25rem;font-weight:700;">📰 {$digestName}</h1>
            <p style="margin:6px 0 0;font-size:.875rem;opacity:.85;">{$date} – {$itemCount} neue Beiträge</p>
        </div>

        <!-- Items -->
        <div style="background:#fff;padding:16px 28px;border-radius:0 0 10px 10px;">
HTML;

        foreach ($items as $i => $item) {
            $title       = htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES, 'UTF-8');
            $link        = htmlspecialchars((string) ($item['link'] ?? '#'), ENT_QUOTES, 'UTF-8');
            $source      = htmlspecialchars((string) ($item['channel_name'] ?? ''), ENT_QUOTES, 'UTF-8');
            $pubTs       = strtotime((string) ($item['pub_date'] ?? ''));
            $pubDate     = $pubTs ? date('d.m.Y H:i', $pubTs) : '';
            $description = htmlspecialchars(cms_feed_substr(strip_tags((string) ($item['description'] ?? '')), 0, 200), ENT_QUOTES, 'UTF-8');
            $border      = $i > 0 ? 'border-top:1px solid #e2e8f0;' : '';

            $html .= <<<HTML

            <div style="{$border}padding:16px 0;">
                <a href="{$link}" style="color:#1e293b;text-decoration:none;font-weight:600;font-size:.95rem;line-height:1.4;" target="_blank" rel="noopener noreferrer">{$title}</a>
                <p style="margin:6px 0 0;font-size:.8rem;color:#64748b;">{$source} · {$pubDate}</p>
                <p style="margin:8px 0 0;font-size:.875rem;color:#475569;line-height:1.5;">{$description}</p>
            </div>
HTML;
        }

        $html .= <<<HTML

        </div>

        <!-- Footer -->
        <div style="text-align:center;padding:16px;font-size:.75rem;color:#94a3b8;">
            Dieser Digest wurde automatisch von 365CMS.DE Feed generiert.<br>
            Frequenz: {$this->get_frequency_label((int)$digest['frequency'])}
        </div>

    </div>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * E-Mail senden.
     */
    private function send_email(string $to, string $subject, string $html, string $fromName, string $fromEmail): bool
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $headers = [
            'X-365CMS-Source' => 'cms-feed-digest',
            'X-365CMS-Test-Source' => 'cms-feed-digest',
        ];

        $fromHeader = $this->build_from_header($fromName, $fromEmail);
        if ($fromHeader !== null) {
            $headers['From'] = $fromHeader;
        }

        try {
            if (class_exists('\\CMS\\Services\\MailQueueService')) {
                $queue = \CMS\Services\MailQueueService::getInstance();
                if ($queue->shouldQueue($headers)) {
                    $result = $queue->enqueue($to, $subject, $html, $headers, null, 'cms-feed-digest');
                    if (!empty($result['success'])) {
                        return true;
                    }
                }
            }

            if (class_exists('\\CMS\\Services\\MailService')) {
                return \CMS\Services\MailService::getInstance()->send($to, $subject, $html, $headers);
            }
        } catch (\Throwable $e) {
            error_log('CMS Feed Digest: Mail dispatch failed – ' . $e->getMessage());
        }

        return false;
    }

    private function build_from_header(string $fromName, string $fromEmail): ?string
    {
        $fromEmail = trim($fromEmail);
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $fromName = trim(str_replace(["\r", "\n", '"', '\\'], ' ', $fromName));
        $fromName = preg_replace('/\s+/u', ' ', $fromName) ?? '';

        if ($fromName === '') {
            return $fromEmail;
        }

        return '"' . $fromName . '" <' . $fromEmail . '>';
    }

    /**
     * Frequenz-Label zurückgeben.
     */
    public function get_frequency_label(int $frequency): string
    {
        return match ($frequency) {
            1 => '1× täglich',
            2 => '2× täglich',
            3 => '3× täglich',
            4 => '4× täglich',
            default => $frequency . '× täglich',
        };
    }

    public function get_member_schedule_label(array $subscription): string
    {
        $frequency = ($subscription['frequency'] ?? 'daily') === 'weekly' ? 'weekly' : 'daily';

        if ($frequency === 'weekly') {
            $weeklyDay = max(1, min(7, (int) ($subscription['weekly_day'] ?? 1)));
            $weeklyTime = in_array($subscription['weekly_time'] ?? '09', ['09', '15'], true)
                ? (string) $subscription['weekly_time']
                : '09';

            return sprintf(
                'Wöchentlich am %s um %s:00 Uhr',
                $this->get_weekday_label($weeklyDay),
                $weeklyTime
            );
        }

        return match ($subscription['daily_mode'] ?? '09') {
            '15' => 'Täglich um 15:00 Uhr',
            '09_15' => 'Täglich um 09:00 und 15:00 Uhr',
            default => 'Täglich um 09:00 Uhr',
        };
    }

    /**
     * Manuell einen Test-Digest senden.
     */
    public function send_test_digest(int $digestId): bool
    {
        $db     = CMS_Feed_Database::instance();
        $digest = $db->get_digest($digestId);

        if (!$digest) {
            return false;
        }

        $digest['last_sent_at'] = null;

        return $this->send_digest($digest, false);
    }

    private function send_member_subscription(array $subscription, \DateTimeImmutable $now): bool
    {
        $db = CMS_Feed_Database::instance();
        $settings = $db->get_settings();
        $channelIds = $db->get_member_subscription_channel_ids($subscription);

        if ($channelIds === []) {
            return false;
        }

        $windowStart = $this->resolve_member_window_start($subscription, $now);
        if ($windowStart === null) {
            return false;
        }

        $since = !empty($subscription['last_sent_at'])
            ? (string) $subscription['last_sent_at']
            : $this->get_member_fallback_since($subscription, $windowStart)->format('Y-m-d H:i:s');

        $maxItems = (int) ($settings['digest_max_items'] ?? 20);
        $items = $db->get_recent_items_for_channels($channelIds, $since, $maxItems);

        if ($items !== []) {
            usort($items, static fn (array $left, array $right): int => strtotime((string) $right['pub_date']) <=> strtotime((string) $left['pub_date']));
            $items = array_slice($items, 0, max(1, $maxItems));
        }

        if ($items === []) {
            $db->mark_member_subscription_sent($subscription, $windowStart->format('Y-m-d H:i:s'));
            return true;
        }

        $subjectTemplate = trim((string) ($settings['digest_subject'] ?? 'Dein Feed-Abo – {date}'));
        if ($subjectTemplate === '') {
            $subjectTemplate = 'Dein Feed-Abo – {date}';
        }

        $subject = str_replace('{date}', $now->format('d.m.Y'), $subjectTemplate);
        $html = $this->build_member_subscription_html($subscription, $items, $settings);

        $sent = $this->send_email(
            (string) ($subscription['email'] ?? ''),
            $subject,
            $html,
            (string) ($settings['digest_from_name'] ?? '365 CMS Feed'),
            (string) ($settings['digest_from_email'] ?? '')
        );

        if ($sent) {
            $db->mark_member_subscription_sent($subscription, $windowStart->format('Y-m-d H:i:s'));
        }

        return $sent;
    }

    private function is_member_subscription_due(array $subscription, \DateTimeImmutable $now): bool
    {
        $windowStart = $this->resolve_member_window_start($subscription, $now);
        if ($windowStart === null) {
            return false;
        }

        if (empty($subscription['last_sent_at'])) {
            return true;
        }

        try {
            $lastSentAt = new \DateTimeImmutable((string) $subscription['last_sent_at']);
        } catch (\Throwable) {
            return true;
        }

        return $lastSentAt < $windowStart;
    }

    private function resolve_member_window_start(array $subscription, \DateTimeImmutable $now): ?\DateTimeImmutable
    {
        $currentHour = (int) $now->format('G');
        $frequency = ($subscription['frequency'] ?? 'daily') === 'weekly' ? 'weekly' : 'daily';

        if ($frequency === 'weekly') {
            $weeklyDay = max(1, min(7, (int) ($subscription['weekly_day'] ?? 1)));
            $weeklyTime = in_array($subscription['weekly_time'] ?? '09', ['09', '15'], true)
                ? (string) $subscription['weekly_time']
                : '09';
            $targetHour = (int) $weeklyTime;

            if ((int) $now->format('N') !== $weeklyDay || $currentHour !== $targetHour) {
                return null;
            }

            return $now->setTime($targetHour, 0, 0);
        }

        $dailyMode = in_array($subscription['daily_mode'] ?? '09', ['09', '15', '09_15'], true)
            ? (string) $subscription['daily_mode']
            : '09';

        $allowedHours = match ($dailyMode) {
            '15' => [15],
            '09_15' => [9, 15],
            default => [9],
        };

        if (!in_array($currentHour, $allowedHours, true)) {
            return null;
        }

        return $now->setTime($currentHour, 0, 0);
    }

    private function get_member_fallback_since(array $subscription, \DateTimeImmutable $windowStart): \DateTimeImmutable
    {
        return (($subscription['frequency'] ?? 'daily') === 'weekly')
            ? $windowStart->modify('-7 days')
            : $windowStart->modify('-24 hours');
    }

    private function build_member_subscription_html(array $subscription, array $items, array $settings): string
    {
        $primaryColor = htmlspecialchars($settings['color_primary'] ?? '#0891b2');
        $digestTitle = 'Dein Feed-Abo';
        $scheduleLabel = htmlspecialchars($this->get_member_schedule_label($subscription));
        $date = date('d.m.Y');
        $itemCount = count($items);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$digestTitle} – {$date}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
    <div style="max-width:640px;margin:0 auto;padding:20px;">
        <div style="background:{$primaryColor};color:#fff;padding:24px 28px;border-radius:10px 10px 0 0;">
            <h1 style="margin:0;font-size:1.25rem;font-weight:700;">📡 {$digestTitle}</h1>
            <p style="margin:6px 0 0;font-size:.875rem;opacity:.85;">{$date} – {$itemCount} neue Beiträge</p>
            <p style="margin:8px 0 0;font-size:.8rem;opacity:.78;">{$scheduleLabel}</p>
        </div>

        <div style="background:#fff;padding:16px 28px;border-radius:0 0 10px 10px;">
HTML;

        foreach ($items as $index => $item) {
            $title       = htmlspecialchars((string) ($item['title'] ?? 'Beitrag'));
            $link        = htmlspecialchars((string) ($item['link'] ?? '#'));
            $source      = htmlspecialchars((string) ($item['channel_name'] ?? 'Feed'));
            $category    = htmlspecialchars((string) ($item['category_name'] ?? ''));
            $pubDate     = !empty($item['pub_date']) ? date('d.m.Y H:i', strtotime((string) $item['pub_date'])) : '';
            $description = htmlspecialchars(cms_feed_substr(strip_tags((string) ($item['description'] ?? '')), 0, 220));
            $border      = $index > 0 ? 'border-top:1px solid #e2e8f0;' : '';
            $meta        = trim($source . ($category !== '' ? ' · ' . $category : '') . ($pubDate !== '' ? ' · ' . $pubDate : ''));

            $html .= <<<HTML

            <div style="{$border}padding:16px 0;">
                <a href="{$link}" style="color:#1e293b;text-decoration:none;font-weight:600;font-size:.95rem;line-height:1.4;" target="_blank" rel="noopener noreferrer">{$title}</a>
                <p style="margin:6px 0 0;font-size:.8rem;color:#64748b;">{$meta}</p>
                <p style="margin:8px 0 0;font-size:.875rem;color:#475569;line-height:1.5;">{$description}</p>
            </div>
HTML;
        }

        $html .= <<<HTML

        </div>

        <div style="text-align:center;padding:16px;font-size:.75rem;color:#94a3b8;">
            Dieses Feed-Abo wurde automatisch von 365CMS.DE Feed versendet.<br>
            Versand: {$scheduleLabel}
        </div>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    private function get_weekday_label(int $weekday): string
    {
        return match ($weekday) {
            1 => 'Montag',
            2 => 'Dienstag',
            3 => 'Mittwoch',
            4 => 'Donnerstag',
            5 => 'Freitag',
            6 => 'Samstag',
            7 => 'Sonntag',
            default => 'Montag',
        };
    }
}
