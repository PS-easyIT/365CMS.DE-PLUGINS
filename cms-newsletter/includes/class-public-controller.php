<?php
/**
 * @package CMS_Newsletter
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Newsletter_Public_Controller
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function register_routes($router): void
    {
        $router->addRoute('GET', '/newsletter', [$this, 'archive_page']);
        $router->addRoute('POST', '/newsletter/subscribe', [$this, 'handle_subscribe']);
        $router->addRoute('GET', '/newsletter/unsubscribe/:token', [$this, 'unsubscribe_page']);
    }

    public function archive_page(): void
    {
        $repository = CMS_Newsletter_Repository::instance();
        $settings = $repository->get_settings();
        $stats = $repository->get_dashboard_stats();
        $campaigns = array_slice($repository->get_campaigns(), 0, 3);
        $notice = (string) ($_GET['newsletter_notice'] ?? '');
        $message = match ($notice) {
            'subscribed' => 'Danke! Deine Anmeldung wurde gespeichert.',
            'double-opt-in' => 'Fast geschafft – prüfe bitte dein Postfach zur Bestätigung.',
            'exists' => 'Diese E-Mail-Adresse ist bereits im Newsletter erfasst.',
            'invalid' => 'Bitte gib eine gültige E-Mail-Adresse ein.',
            'unsubscribed' => 'Du wurdest erfolgreich vom Newsletter abgemeldet.',
            default => '',
        };
        $messageType = in_array($notice, ['invalid'], true) ? 'error' : 'success';
        $csrfToken = class_exists('CMS\\Security') ? \CMS\Security::instance()->generateToken('newsletter_subscribe') : '';
        $theme = class_exists('CMS\\ThemeManager') ? \CMS\ThemeManager::instance() : null;

        if ($theme !== null) {
            $theme->getHeader();
        }

        include CMS_NEWSLETTER_PLUGIN_DIR . 'templates/archive-newsletter.php';

        if ($theme !== null) {
            $theme->getFooter();
        }
    }

    public function handle_subscribe(): void
    {
        $security = class_exists('CMS\\Security') ? \CMS\Security::instance() : null;
        if ($security !== null && !$security->verifyToken($_POST['csrf_token'] ?? '', 'newsletter_subscribe')) {
            header('Location: ' . SITE_URL . '/newsletter?newsletter_notice=invalid');
            exit;
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ' . SITE_URL . '/newsletter?newsletter_notice=invalid');
            exit;
        }

        $repository = CMS_Newsletter_Repository::instance();
        $settings = $repository->get_settings();
        $status = !empty($settings['require_double_opt_in']) ? 'pending' : 'active';
        $result = $repository->save_subscriber([
            'email' => $email,
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'last_name' => trim((string) ($_POST['last_name'] ?? '')),
            'segment_slug' => trim((string) ($_POST['segment_slug'] ?? 'general')),
            'status' => $status,
            'source' => 'public',
        ]);

        if (!($result['success'] ?? false) && str_contains((string) ($result['error'] ?? ''), 'bereits')) {
            header('Location: ' . SITE_URL . '/newsletter?newsletter_notice=exists');
            exit;
        }

        header('Location: ' . SITE_URL . '/newsletter?newsletter_notice=' . (!empty($settings['require_double_opt_in']) ? 'double-opt-in' : 'subscribed'));
        exit;
    }

    public function unsubscribe_page(string $token): void
    {
        CMS_Newsletter_Repository::instance()->unsubscribe_by_token($token);
        header('Location: ' . SITE_URL . '/newsletter?newsletter_notice=unsubscribed');
        exit;
    }
}
