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
        $results = [];

        foreach ($digests as $digest) {
            $results[$digest['id']] = $this->send_digest($digest);
        }

        return $results;
    }

    /**
     * Einen einzelnen Digest versenden.
     */
    public function send_digest(array $digest): bool
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
            $db->update_digest_sent((int) $digest['id']);
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

        if ($sent) {
            $db->update_digest_sent((int) $digest['id']);
        }

        return $sent;
    }

    /**
     * Digest-E-Mail als HTML aufbauen.
     */
    private function build_email_html(array $digest, array $items, array $settings): string
    {
        $primaryColor = htmlspecialchars($settings['color_primary'] ?? '#0891b2');
        $digestName   = htmlspecialchars($digest['name']);
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
            $title       = htmlspecialchars($item['title']);
            $link        = htmlspecialchars($item['link']);
            $source      = htmlspecialchars($item['channel_name'] ?? '');
            $pubDate     = date('d.m.Y H:i', strtotime($item['pub_date']));
            $description = htmlspecialchars(mb_substr(strip_tags($item['description'] ?? ''), 0, 200));
            $border      = $i > 0 ? 'border-top:1px solid #e2e8f0;' : '';

            $html .= <<<HTML

            <div style="{$border}padding:16px 0;">
                <a href="{$link}" style="color:#1e293b;text-decoration:none;font-weight:600;font-size:.95rem;line-height:1.4;" target="_blank">{$title}</a>
                <p style="margin:6px 0 0;font-size:.8rem;color:#64748b;">{$source} · {$pubDate}</p>
                <p style="margin:8px 0 0;font-size:.875rem;color:#475569;line-height:1.5;">{$description}</p>
            </div>
HTML;
        }

        $html .= <<<HTML

        </div>

        <!-- Footer -->
        <div style="text-align:center;padding:16px;font-size:.75rem;color:#94a3b8;">
            Dieser Digest wurde automatisch von 365 CMS Feed generiert.<br>
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
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";

        if ($fromEmail) {
            $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
        }

        return mail($to, $subject, $html, $headers);
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

        // last_sent_at temporär auf NULL setzen für Test
        $original = $digest['last_sent_at'];
        $digest['last_sent_at'] = null;

        $result = $this->send_digest($digest);

        // last_sent_at nicht aktualisieren bei Testversand – ist bereits in send_digest passiert

        return $result;
    }
}
