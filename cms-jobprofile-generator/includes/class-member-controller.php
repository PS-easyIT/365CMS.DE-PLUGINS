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
require_once __DIR__ . '/member/trait-member-approvals.php';
require_once __DIR__ . '/member/trait-member-settings.php';

class CMS_JPG_Member_Controller
{
    use CMS_JPG_Member_Hooks_Trait;
    use CMS_JPG_Member_Dsgvo_Trait;
    use CMS_JPG_Member_Jobs_Trait;
    use CMS_JPG_Member_Inline_Trait;
    use CMS_JPG_Member_Applications_Trait;
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
            header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/member/jobs'));
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
        $token = $_POST['_jpg_csrf'] ?? '';
        if (class_exists('CMS\\Security')) {
            return \CMS\Security::instance()->verifyToken($token, 'member_jpg_' . $action);
        }
        return !empty($token); // Fallback
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
            'url'   => (string) filter_var($raw, FILTER_VALIDATE_URL),
            'html'  => (string) $raw,  // SunEditor-Inhalte; sanitizeHtml() in Logik anwenden
            default => sanitize_text_field((string) $raw),
        };
    }
}
