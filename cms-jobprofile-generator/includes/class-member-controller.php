<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Member-Bereich Controller – Job Profile Generator
 *
 * Schlanker Shell-Controller: Singleton, Basis-Properties und Auth/CSRF-Helfer.
 * Alle Feature-Gruppen sind in separate Traits ausgelagert.
 *
 * @since   0.1.0
 * @package CMS_JobProfileGenerator
 */

// ── Trait-Dateien laden ───────────────────────────────────────────────────────
require_once __DIR__ . '/member/trait-member-hooks.php';
require_once __DIR__ . '/member/trait-member-dsgvo.php';
require_once __DIR__ . '/member/trait-member-jobs.php';
require_once __DIR__ . '/member/trait-member-inline.php';
require_once __DIR__ . '/member/trait-member-applications.php';
require_once __DIR__ . '/member/trait-member-my-applications.php';
require_once __DIR__ . '/member/trait-member-approvals.php';
require_once __DIR__ . '/member/trait-member-settings.php';

class CMS_JPG_Member_Controller
{
    use CMS_JPG_Member_Hooks_Trait;
    use CMS_JPG_Member_Dsgvo_Trait;
    use CMS_JPG_Member_Jobs_Trait;
    use CMS_JPG_Member_Inline_Trait;
    use CMS_JPG_Member_Applications_Trait;
    use CMS_JPG_Member_MyApplications_Trait;
    use CMS_JPG_Member_Approvals_Trait;
    use CMS_JPG_Member_Settings_Trait;

    private static ?self $instance = null;

    /** @var \CMS\Auth */
    private \CMS\Auth $auth;

    /** @var \CMS\Database */
    private \CMS\Database $db;

    /** @var string */
    private string $p;

    /** @var int|null */
    private ?int $userId = null;

    public static function instance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->auth = \CMS\Auth::instance();
        $this->db   = \CMS\Database::instance();
        $this->p    = $this->db->getPrefix();

        $this->register_hooks();
        $this->register_routes();
    }

    // ── Auth-Guard ────────────────────────────────────────────────────────────

    private function require_auth(): void
    {
        if (!$this->auth->isLoggedIn()) {
            $redirect = (string) ($_SERVER['REQUEST_URI'] ?? '/member/jobs');
            if ($redirect === '' || !str_starts_with($redirect, '/')) {
                $redirect = '/member/jobs';
            }
            header('Location: /login?redirect=' . rawurlencode($redirect), true, 303);
            exit;
        }
        $this->userId = method_exists($this->auth, 'getUserId') ? (int) $this->auth->getUserId() : 0;
    }

    // ── CSRF-Helfer ───────────────────────────────────────────────────────────

    private function generate_token(string $action): string
    {
        if (class_exists('CMS\\Security')) {
            return \CMS\Security::instance()->generateToken('member_jpg_' . $action);
        }
        return bin2hex(random_bytes(16));
    }

    private function verify_token(string $action): bool
    {
        $token = (string) ($_POST['_jpg_csrf'] ?? '');
        if (class_exists('CMS\\Security')) {
            return \CMS\Security::instance()->verifyToken($token, 'member_jpg_' . $action);
        }
        return false;
    }

    // ── Eingabe-Helfer ────────────────────────────────────────────────────────

    /**
     * @param  'text'|'html'|'int'|'url'|'email' $type
     * @return string|int
     */
    private function getPost(string $key, string $type = 'text'): string|int
    {
        $raw = $_POST[$key] ?? '';
        return match ($type) {
            'int'   => (int) $raw,
            'email' => (string) filter_var($raw, FILTER_VALIDATE_EMAIL),
            'url'   => $this->sanitize_public_url((string) $raw),
            'html'  => (string) $raw,  // SunEditor-Inhalte; sanitizeHtml() in Logik anwenden
            default => sanitize_text_field((string) $raw),
        };
    }

    private function sanitize_public_url(string $url): string
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return '';
        }
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || !empty($parts['user']) || !empty($parts['pass'])) {
            return '';
        }
        $host = strtolower(trim((string) ($parts['host'] ?? ''), '[]'));
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return '';
        }
        if (filter_var($host, FILTER_VALIDATE_IP) && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return '';
        }
        return $url;
    }
}
