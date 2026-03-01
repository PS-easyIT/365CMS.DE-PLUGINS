<?php
/**
 * CMS Forum – Notification Service
 *
 * E-Mail- und interne Benachrichtigungen bei neuen Beiträgen,
 * Erwähnungen und Abonnement-Updates.
 *
 * @package CMS_Forum\Services
 */

declare(strict_types=1);

namespace CMS_Forum\Services;

if (!defined('ABSPATH')) {
    exit;
}

use CMS_Forum\Models\Subscription;

final class NotificationService
{
    private static ?self $instance = null;

    public static function instance(): static
    {
        return static::$instance ??= new static();
    }

    private function __construct() {}

    /**
     * Benachrichtigung über neuen Beitrag in einem Thread senden.
     */
    public function notifyNewPost(int $threadId, int $posterId, string $threadTitle): void
    {
        $subscribers = Subscription::instance()->findSubscribers('thread', $threadId);

        foreach ($subscribers as $sub) {
            // Den Verfasser selbst nicht benachrichtigen
            if ((int) $sub->user_id === $posterId) {
                continue;
            }

            if ($sub->notify_email && !empty($sub->email)) {
                $this->sendEmail(
                    $sub->email,
                    "Neue Antwort: {$threadTitle}",
                    $this->buildPostNotificationBody($sub->username, $threadTitle, $threadId)
                );
            }
        }
    }

    /**
     * Benachrichtigung über neuen Thread in einem Forum senden.
     */
    public function notifyNewThread(int $forumId, int $posterId, string $threadTitle, int $threadId): void
    {
        $subscribers = Subscription::instance()->findSubscribers('forum', $forumId);

        foreach ($subscribers as $sub) {
            if ((int) $sub->user_id === $posterId) {
                continue;
            }

            if ($sub->notify_email && !empty($sub->email)) {
                $this->sendEmail(
                    $sub->email,
                    "Neuer Thread: {$threadTitle}",
                    $this->buildThreadNotificationBody($sub->username, $threadTitle, $threadId)
                );
            }
        }
    }

    /**
     * E-Mail senden (nutzt die CMS-Mail-Funktion).
     */
    private function sendEmail(string $to, string $subject, string $body): void
    {
        // CMS-eigene Mail-Funktion aufrufen, wenn vorhanden
        if (function_exists('cms_mail')) {
            cms_mail($to, $subject, $body);
            return;
        }

        // Fallback: PHP mail()
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . (defined('SITE_NAME') ? SITE_NAME : '365CMS') . ' <noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '>',
        ];

        @mail($to, $subject, $body, implode("\r\n", $headers));
    }

    /**
     * E-Mail-Body für neue Beiträge generieren.
     */
    private function buildPostNotificationBody(string $username, string $threadTitle, int $threadId): string
    {
        $url = (defined('SITE_URL') ? SITE_URL : '') . '/forum/thread/' . $threadId;

        return <<<HTML
        <p>Hallo {$username},</p>
        <p>es gibt eine neue Antwort in einem Thread, den du abonniert hast:</p>
        <p><strong>{$threadTitle}</strong></p>
        <p><a href="{$url}">Zum Thread</a></p>
        <p style="color:#64748b;font-size:.85rem;">Du erhältst diese E-Mail, weil du den Thread abonniert hast. Du kannst das Abo jederzeit im Forum beenden.</p>
        HTML;
    }

    /**
     * E-Mail-Body für neue Threads generieren.
     */
    private function buildThreadNotificationBody(string $username, string $threadTitle, int $threadId): string
    {
        $url = (defined('SITE_URL') ? SITE_URL : '') . '/forum/thread/' . $threadId;

        return <<<HTML
        <p>Hallo {$username},</p>
        <p>es gibt einen neuen Thread in einem Forum, das du abonniert hast:</p>
        <p><strong>{$threadTitle}</strong></p>
        <p><a href="{$url}">Zum Thread</a></p>
        <p style="color:#64748b;font-size:.85rem;">Du erhältst diese E-Mail, weil du das Forum abonniert hast.</p>
        HTML;
    }
}
