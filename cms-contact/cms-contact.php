<?php
/**
 * Plugin Name: CMS Contact
 * Plugin URI:  https://365network.de/cms-contact
 * Description: Kontaktformular-Plugin mit bis zu 6 Templates, benutzerdefinierten Metafeldern und mehreren Formularen unter verschiedenen Slugs
 * Version:     3.0.1
 * Author:      365 Network
 * Author URI:  https://365network.de
 *
 * @package CMS_Contact
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// ── Konstanten ────────────────────────────────────────────────────────────────
define('CMS_CONTACT_VERSION',    '3.0.1');
define('CMS_CONTACT_DB_VERSION', '4');
define('CMS_CONTACT_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_CONTACT_PLUGIN_URL', '/plugins/cms-contact/');

final class CMS_Contact
{
    private static ?self $instance = null;
    private const MEMBER_SECTION_PATH = '/member/plugin/contact';
    private string $version;
    private string $plugin_dir;
    private string $plugin_url;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->version    = CMS_CONTACT_VERSION;
        $this->plugin_dir = CMS_CONTACT_PLUGIN_DIR;
        $this->plugin_url = CMS_CONTACT_PLUGIN_URL;
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies(): void
    {
        $includes = $this->plugin_dir . 'includes/';
        $admin    = $this->plugin_dir . 'admin/';
        $sharedI18n = dirname($this->plugin_dir) . '/shared/public/plugin-public-i18n.php';

        if (file_exists($sharedI18n)) {
            require_once $sharedI18n;
        }

        $files = [
            $includes . 'class-installer.php',
            $includes . 'class-forms.php',
            $includes . 'class-fields.php',
            $includes . 'class-submissions.php',
            $includes . 'class-frontend.php',
            $admin    . 'class-admin-menu.php',
            $admin    . 'class-admin-pages.php',
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }

    private function init_hooks(): void
    {
        if (!class_exists('CMS\Hooks')) {
            return;
        }

        \CMS\Hooks::addAction('cms_init',            [$this, 'init_plugin'],                10);
        \CMS\Hooks::addAction('plugin_activated',     [$this, 'on_activation'],              10);
        \CMS\Hooks::addAction('plugin_uninstalled',   [$this, 'on_uninstall'],               10);
        \CMS\Hooks::addAction('cms_admin_menu',       [$this, 'start_admin_output_buffer'], 1);
        \CMS\Hooks::addAction('cms_admin_menu',       [CMS_Contact_Admin_Menu::class, 'register'], 10);
        \CMS\Hooks::addAction('register_routes',      [CMS_Contact_Frontend::class, 'instance'],   10);
        \CMS\Hooks::addAction('member_dashboard_init', [$this, 'register_member_section'],          20);
        \CMS\Hooks::addAction('head',                 [$this, 'enqueue_styles'],             20);
        \CMS\Hooks::addAction('body_end',             [$this, 'enqueue_scripts'],            20);

        // DSGVO-Hooks
        \CMS\Hooks::addAction('dsgvo_export_data',    [$this, 'export_user_data'],           10);
        \CMS\Hooks::addAction('dsgvo_delete_data',    [$this, 'delete_user_data'],           10);
    }

    /**
     * Output-Buffering für Admin-POST-Requests starten.
     *
     * Muss VOR renderAdminLayoutStart() laufen (cms_admin_menu Priorität 1),
     * damit header()-Redirects in den Trait-POST-Handlern funktionieren,
     * auch nachdem der Router bereits HTML ausgegeben hat.
     */
    public function start_admin_output_buffer(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            return;
        }
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/admin/plugins/contact/') !== false) {
            ob_start();
        }
    }

    public function on_activation(string $plugin): void
    {
        if ($plugin === 'cms-contact' && class_exists('CMS_Contact_Installer')) {
            CMS_Contact_Installer::install();
        }
    }

    public function on_uninstall(string $plugin): void
    {
        if ($plugin === 'cms-contact' && class_exists('CMS_Contact_Installer')) {
            CMS_Contact_Installer::uninstall();
        }
    }

    public function init_plugin(): void
    {
        if (class_exists('CMS_Contact_Installer')) {
            CMS_Contact_Installer::maybe_install();
        }
    }

    private function should_enqueue_public_assets(): bool
    {
        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $path = (string) (parse_url($requestUri, PHP_URL_PATH) ?? '');

        if ($path === '') {
            return false;
        }

        $path = '/' . trim($path, '/');
        if ($path === '/') {
            return false;
        }

        if ($path === '/contact' || $path === '/kontakt' || $path === '/en/contact') {
            return true;
        }

        return preg_match('#^/(?:en/contact|contact|kontakt)/[^/]+(?:/.*)?$#', $path) === 1;
    }

    public function enqueue_styles(): void
    {
        if (!$this->should_enqueue_public_assets()) {
            return;
        }

        $css = $this->plugin_dir . 'assets/css/contact-public.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars($this->plugin_url . 'assets/css/contact-public.css')
                . '?v=' . filemtime($css) . '">' . "\n";
        }
    }

    public function enqueue_scripts(): void
    {
        if (!$this->should_enqueue_public_assets()) {
            return;
        }

        $js = $this->plugin_dir . 'assets/js/contact-public.js';
        if (file_exists($js)) {
            echo '<script src="'
                . htmlspecialchars($this->plugin_url . 'assets/js/contact-public.js')
                . '?v=' . filemtime($js) . '" defer></script>' . "\n";
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Member Dashboard                                                    */
    /* ------------------------------------------------------------------ */

    public function register_member_section(\CMS\Member\PluginDashboardRegistry $registry): void
    {
        $registry->register([
            'plugin'          => 'cms-contact',
            'slug'            => 'contact',
            'label'           => 'Kontaktanfragen',
            'icon'            => '📩',
            'color'           => '#2563eb',
            'category'        => 'plugins',
            'priority'        => 80,
            'admin_url'       => '/admin/plugins/contact/submissions',
            'stats_callback'  => [$this, 'get_member_stats'],
            'post_callback'   => [$this, 'handle_member_post'],
            'render_callback' => [$this, 'render_member_page'],
        ]);
    }

    public function get_member_stats(object $user): array
    {
        $count = class_exists('CMS_Contact_Submissions')
            ? count(CMS_Contact_Submissions::instance()->get_user_submissions((int) $user->id))
            : 0;

        return [
            'count' => $count,
            'label' => $count === 1 ? 'Anfrage' : 'Anfragen',
        ];
    }

    public function handle_member_post(object $user, array $params = []): void
    {
        $submissionService = class_exists('CMS_Contact_Submissions')
            ? CMS_Contact_Submissions::instance()
            : null;

        if ($submissionService === null) {
            $this->set_member_flash('error', 'Der Kontaktbereich ist aktuell nicht verfügbar.');
            $this->redirect_member_section();
        }

        $action = (string) ($_POST['member_contact_action'] ?? '');
        if ($action === '') {
            $this->render_member_page($user, $params);
            return;
        }

        if (!class_exists('CMS\\Security')
            || !\CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'contact_member_actions')) {
            $this->set_member_flash('error', 'Sicherheitscheck fehlgeschlagen. Bitte versuche es erneut.');
            $this->redirect_member_section();
        }

        $submissionId = (int) ($_POST['submission_id'] ?? 0);
        if ($submissionId <= 0
            || $submissionService->get_user_submission_by_id((int) $user->id, $submissionId) === null) {
            $this->set_member_flash('error', 'Die gewünschte Kontaktanfrage wurde nicht gefunden.');
            $this->redirect_member_section();
        }

        if ($action === 'delete_submission') {
            $deleted = $submissionService->delete_user_submission((int) $user->id, $submissionId);
            $this->set_member_flash(
                $deleted ? 'success' : 'error',
                $deleted
                    ? 'Die Kontaktanfrage wurde erfolgreich gelöscht.'
                    : 'Die Kontaktanfrage konnte nicht gelöscht werden.'
            );
            $this->redirect_member_section(['view' => null]);
        }

        $this->set_member_flash('error', 'Die gewünschte Aktion ist nicht verfügbar.');
        $this->redirect_member_section(['view' => $submissionId]);
    }

    public function render_member_page(object $user, array $params = []): void
    {
        $submissionService = class_exists('CMS_Contact_Submissions')
            ? CMS_Contact_Submissions::instance()
            : null;

        $submissions = $submissionService !== null
            ? $submissionService->get_user_submissions((int) $user->id)
            : [];

        $selectedSubmission = null;
        $selectedMeta = [];
        $selectedId = isset($_GET['view']) ? max(0, (int) $_GET['view']) : 0;
        if ($selectedId > 0 && $submissionService !== null) {
            $selectedSubmission = $submissionService->get_user_submission_by_id((int) $user->id, $selectedId);
            if ($selectedSubmission !== null) {
                $selectedMeta = $submissionService->get_meta((int) $selectedSubmission['id']);
                if (($selectedSubmission['status'] ?? '') === 'unread') {
                    $submissionService->mark_user_submission_read((int) $user->id, (int) $selectedSubmission['id']);
                    $selectedSubmission['status'] = 'read';
                    foreach ($submissions as &$submissionRow) {
                        if ((int) ($submissionRow['id'] ?? 0) === (int) $selectedSubmission['id']) {
                            $submissionRow['status'] = 'read';
                            break;
                        }
                    }
                    unset($submissionRow);
                }
            }
        }

        $flash = $this->consume_member_flash();
        $csrfToken = class_exists('CMS\\Security')
            ? \CMS\Security::instance()->generateToken('contact_member_actions')
            : '';

        $statusMap = $this->get_member_status_map();
        $totalSubmissions = count($submissions);
        $unreadSubmissions = count(array_filter($submissions, static function (array $submission): bool {
            return ($submission['status'] ?? 'unread') === 'unread';
        }));
        $formsUsed = count(array_unique(array_filter(array_map(static function (array $submission): string {
            return trim((string) ($submission['form_name'] ?? $submission['form_title'] ?? ''));
        }, $submissions))));
        $latestDate = !empty($submissions[0]['created_at'])
            ? $this->format_member_date((string) $submissions[0]['created_at'])
            : 'Noch keine';
        ?>
        <div class="cms-member-section">
            <?php if (!empty($flash['message'])): ?>
                <div class="member-alert <?php echo ($flash['type'] ?? 'success') === 'error' ? 'member-alert-error' : 'member-alert-success'; ?>">
                    <?php echo htmlspecialchars((string) $flash['message'], ENT_QUOTES); ?>
                </div>
            <?php endif; ?>

            <div class="member-dashboard-overview member-dashboard-overview--analytics contact-member-overview" aria-label="Kontaktstatistik">
                <article class="member-overview-card">
                    <span class="member-overview-card__icon" aria-hidden="true">📩</span>
                    <strong><?php echo (int) $totalSubmissions; ?></strong>
                    <span class="contact-member-overview__label">Gesamte Kontaktanfragen</span>
                </article>
                <article class="member-overview-card">
                    <span class="member-overview-card__icon" aria-hidden="true">🆕</span>
                    <strong><?php echo (int) $unreadSubmissions; ?></strong>
                    <span class="contact-member-overview__label">Neue, noch ungelesene Anfragen</span>
                </article>
                <article class="member-overview-card">
                    <span class="member-overview-card__icon" aria-hidden="true">🗂️</span>
                    <strong><?php echo (int) $formsUsed; ?></strong>
                    <span class="contact-member-overview__label">Formulare mit Einsendungen</span>
                </article>
                <article class="member-overview-card">
                    <span class="member-overview-card__icon" aria-hidden="true">🕒</span>
                    <strong><?php echo htmlspecialchars($latestDate, ENT_QUOTES); ?></strong>
                    <span class="contact-member-overview__label">Letzte Anfrage</span>
                </article>
            </div>

            <?php if ($selectedId > 0 && $selectedSubmission === null): ?>
                <div class="member-alert member-alert-error">
                    Die ausgewählte Kontaktanfrage konnte nicht geladen werden oder gehört nicht zu deinem Account.
                </div>
            <?php endif; ?>

            <?php if ($selectedSubmission !== null): ?>
                <?php
                $selectedStatus = $statusMap[$selectedSubmission['status'] ?? 'unread'] ?? $statusMap['unread'];
                $messageText = trim((string) ($selectedSubmission['message'] ?? ''));
                $subjectText = trim((string) ($selectedSubmission['subject'] ?? ''));
                $senderName = trim((string) ($selectedSubmission['sender_name'] ?? ''));
                $senderEmail = trim((string) ($selectedSubmission['sender_email'] ?? ''));
                $formName = trim((string) ($selectedSubmission['form_name'] ?? $selectedSubmission['form_title'] ?? 'Kontaktformular'));
                ?>
                <section class="contact-member-detail" aria-labelledby="contact-member-detail-title">
                    <header class="contact-member-detail__header">
                        <div class="contact-member-detail__intro">
                            <span class="contact-member-detail__eyebrow">Anfrage #<?php echo (int) $selectedSubmission['id']; ?></span>
                            <h4 id="contact-member-detail-title"><?php echo htmlspecialchars($subjectText !== '' ? $subjectText : 'Kontaktanfrage ohne Betreff', ENT_QUOTES); ?></h4>
                            <p>
                                <?php echo htmlspecialchars($senderName !== '' ? $senderName : 'Unbekannter Absender', ENT_QUOTES); ?>
                                <?php if ($senderEmail !== ''): ?>
                                    · <a href="mailto:<?php echo htmlspecialchars($senderEmail, ENT_QUOTES); ?>"><?php echo htmlspecialchars($senderEmail, ENT_QUOTES); ?></a>
                                <?php endif; ?>
                                · über <?php echo htmlspecialchars($formName, ENT_QUOTES); ?>
                            </p>
                        </div>

                        <div class="contact-member-actions">
                            <a href="<?php echo htmlspecialchars($this->build_member_section_url(['view' => null]), ENT_QUOTES); ?>" class="contact-member-action">
                                Zur Liste
                            </a>
                            <form method="post" class="member-inline-form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                                <input type="hidden" name="submission_id" value="<?php echo (int) $selectedSubmission['id']; ?>">
                                <button type="submit" name="member_contact_action" value="delete_submission" class="contact-member-action contact-member-action--danger">
                                    Löschen
                                </button>
                            </form>
                        </div>
                    </header>

                    <div class="contact-member-detail__meta">
                        <div>
                            <span>Status</span>
                            <strong><span class="contact-member-status <?php echo htmlspecialchars($selectedStatus['class'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($selectedStatus['label'], ENT_QUOTES); ?></span></strong>
                        </div>
                        <div>
                            <span>Formular</span>
                            <strong><?php echo htmlspecialchars($formName, ENT_QUOTES); ?></strong>
                        </div>
                        <div>
                            <span>Eingegangen</span>
                            <strong><?php echo htmlspecialchars($this->format_member_date((string) ($selectedSubmission['created_at'] ?? '')), ENT_QUOTES); ?></strong>
                        </div>
                        <div>
                            <span>Absender</span>
                            <strong><?php echo htmlspecialchars($senderName !== '' ? $senderName : '—', ENT_QUOTES); ?></strong>
                        </div>
                    </div>

                    <div class="contact-member-detail__block contact-member-detail__message">
                        <h5>Nachricht</h5>
                        <p><?php echo nl2br(htmlspecialchars($messageText !== '' ? $messageText : 'Für diese Anfrage wurde keine Nachricht hinterlegt.', ENT_QUOTES)); ?></p>
                    </div>

                    <?php if (!empty($selectedMeta)): ?>
                        <div class="contact-member-detail__block">
                            <h5>Zusätzliche Angaben</h5>
                            <dl class="contact-member-detail__fields">
                                <?php foreach ($selectedMeta as $metaKey => $metaValue): ?>
                                    <div class="contact-member-detail__field">
                                        <dt><?php echo htmlspecialchars($this->format_member_meta_label((string) $metaKey), ENT_QUOTES); ?></dt>
                                        <dd><?php echo nl2br(htmlspecialchars((string) $metaValue, ENT_QUOTES)); ?></dd>
                                    </div>
                                <?php endforeach; ?>
                            </dl>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if (empty($submissions)): ?>
                <div class="member-empty-state" role="status" aria-live="polite">
                    <p class="member-empty-state__icon member-empty-state__icon--compact" aria-hidden="true">📭</p>
                    <p><strong>Keine Kontaktanfragen vorhanden</strong></p>
                    <p>Neue Nachrichten aus deinen Formularen erscheinen automatisch hier im Memberbereich.</p>
                </div>
            <?php else: ?>
                <div class="users-table-container">
                    <table class="users-table contact-member-table">
                        <thead>
                            <tr>
                                <th>Formular</th>
                                <th>Anfrage</th>
                                <th>Status</th>
                                <th>Datum</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $row): ?>
                            <?php
                                $rowId = (int) ($row['id'] ?? 0);
                                $rowStatus = $statusMap[$row['status'] ?? 'unread'] ?? $statusMap['unread'];
                                $rowSubject = trim((string) ($row['subject'] ?? ''));
                                $rowSenderName = trim((string) ($row['sender_name'] ?? ''));
                                $rowSenderEmail = trim((string) ($row['sender_email'] ?? ''));
                                $rowFormName = trim((string) ($row['form_name'] ?? $row['form_title'] ?? '—'));
                                $rowActiveClass = $selectedSubmission !== null && $rowId === (int) ($selectedSubmission['id'] ?? 0)
                                    ? ' is-active'
                                    : '';
                            ?>
                            <tr class="contact-member-row<?php echo $rowActiveClass; ?>">
                                <td><?php echo htmlspecialchars($rowFormName !== '' ? $rowFormName : '—', ENT_QUOTES); ?></td>
                                <td>
                                    <div class="contact-member-table__subject">
                                        <strong><?php echo htmlspecialchars($rowSubject !== '' ? $rowSubject : 'Kontaktanfrage ohne Betreff', ENT_QUOTES); ?></strong>
                                        <span>
                                            <?php echo htmlspecialchars($rowSenderName !== '' ? $rowSenderName : 'Unbekannter Absender', ENT_QUOTES); ?>
                                            <?php if ($rowSenderEmail !== ''): ?>
                                                · <?php echo htmlspecialchars($rowSenderEmail, ENT_QUOTES); ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="contact-member-status <?php echo htmlspecialchars($rowStatus['class'], ENT_QUOTES); ?>">
                                        <?php echo htmlspecialchars($rowStatus['label'], ENT_QUOTES); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($this->format_member_date((string) ($row['created_at'] ?? '')), ENT_QUOTES); ?></td>
                                <td>
                                    <div class="contact-member-table__actions">
                                        <a href="<?php echo htmlspecialchars($this->build_member_section_url(['view' => $rowId]), ENT_QUOTES); ?>" class="contact-member-action contact-member-action--primary">
                                            Ansicht
                                        </a>
                                        <form method="post" class="member-inline-form">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                                            <input type="hidden" name="submission_id" value="<?php echo $rowId; ?>">
                                            <button type="submit" name="member_contact_action" value="delete_submission" class="contact-member-action contact-member-action--danger">
                                                Löschen
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * DSGVO Art. 20 – Daten-Export
     */
    public function export_user_data(int $userId): array
    {
        if (!class_exists('CMS_Contact_Submissions')) {
            return [];
        }
        return CMS_Contact_Submissions::instance()->get_user_submissions($userId);
    }

    /**
     * DSGVO Art. 17 – Datenlöschung
     */
    public function delete_user_data(int $userId): void
    {
        if (!class_exists('CMS_Contact_Submissions')) {
            return;
        }
        CMS_Contact_Submissions::instance()->delete_user_submissions($userId);
    }

    public function get_version(): string    { return $this->version; }
    public function get_plugin_dir(): string { return $this->plugin_dir; }
    public function get_plugin_url(): string { return $this->plugin_url; }

    private function build_member_section_url(array $overrides = []): string
    {
        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/member/plugin/contact');
        $requestPath = (string) (parse_url($requestUri, PHP_URL_PATH) ?? '');
        $path = str_starts_with($requestPath, self::MEMBER_SECTION_PATH)
            ? $requestPath
            : self::MEMBER_SECTION_PATH;

        $query = [];
        $rawQuery = parse_url($requestUri, PHP_URL_QUERY);
        if (is_string($rawQuery) && $rawQuery !== '') {
            parse_str($rawQuery, $query);
        }

        foreach ($overrides as $key => $value) {
            if ($value === null || $value === '') {
                unset($query[$key]);
                continue;
            }

            $query[$key] = $value;
        }

        $queryString = http_build_query($query);

        return $path . ($queryString !== '' ? '?' . $queryString : '');
    }

    private function redirect_member_section(array $overrides = []): void
    {
        $target = $this->build_member_section_url($overrides);

        if (function_exists('safe_redirect')) {
            safe_redirect($target);
        } else {
            header('Location: ' . $target, true, 302);
        }

        exit;
    }

    private function set_member_flash(string $type, string $message): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION['cms_contact_member_flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    private function consume_member_flash(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE || !isset($_SESSION['cms_contact_member_flash'])) {
            return [];
        }

        $flash = $_SESSION['cms_contact_member_flash'];
        unset($_SESSION['cms_contact_member_flash']);

        return is_array($flash) ? $flash : [];
    }

    private function get_member_status_map(): array
    {
        return [
            'unread' => ['label' => 'Neu', 'class' => 'contact-member-status--unread'],
            'read' => ['label' => 'Gelesen', 'class' => 'contact-member-status--read'],
            'replied' => ['label' => 'Beantwortet', 'class' => 'contact-member-status--replied'],
            'archived' => ['label' => 'Archiviert', 'class' => 'contact-member-status--archived'],
            'spam' => ['label' => 'Spam', 'class' => 'contact-member-status--spam'],
        ];
    }

    private function format_member_date(string $value): string
    {
        if ($value === '') {
            return '—';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return $value;
        }

        return date('d.m.Y H:i', $timestamp);
    }

    private function format_member_meta_label(string $key): string
    {
        $label = trim(str_replace(['_', '-'], ' ', $key));
        if ($label === '') {
            return 'Zusatzfeld';
        }

        return ucfirst($label);
    }
}

CMS_Contact::instance();
