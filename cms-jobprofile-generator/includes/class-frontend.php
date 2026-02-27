<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend-Router & Controller für den Job Profile Generator
 *
 * Registriert öffentliche Routen und handelt Anfragen:
 * - /jobs/:slug       → Theme-integrierte Ansicht
 * - /career/:slug     → Whitelabel-Standalone
 * - /api/jobs/:slug/pdf → PDF-Download
 *
 * @since   0.0.1
 * @package CMS_JobProfileGenerator
 */
class CMS_JPG_Frontend
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Routen werden direkt registriert, da die Instanziierung
        // über den register_routes Hook im Bootstrap erfolgt.
        $this->register_routes();
        $this->register_hooks();
    }

    // ── Route-Registrierung ──────────────────────────────────────────────────

    public function register_routes(): void
    {
        $router = \CMS\Router::instance();

        // Öffentliche Job-Übersicht: /jobs (muss vor /jobs/:slug registriert werden)
        $router->addRoute('GET', '/jobs', [$this, 'render_jobs_list']);

        // Theme-integriert: /jobs/:slug
        $router->addRoute('GET', '/jobs/:slug', [$this, 'render_integrated']);

        // Bewerbungs-POST: /jobs/:slug/apply
        $router->addRoute('POST', '/jobs/:slug/apply', [$this, 'handle_apply']);

        // Whitelabel: /career/:slug
        $router->addRoute('GET', '/career/:slug', [$this, 'render_whitelabel']);

        // PDF-Export: /api/jobs/:slug/pdf
        $router->addRoute('GET', '/api/jobs/:slug/pdf', [$this, 'handle_pdf_export']);
    }

    // ── Content-Filter-Hooks ─────────────────────────────────────────────────

    /**
     * Registriert CMS\Hooks-Filter für Cross-Plugin-Integration.
     *
     * @since 0.1.0
     */
    private function register_hooks(): void
    {
        if (!class_exists('CMS\\Hooks')) {
            return;
        }

        // Phase 6.2 – Jobs auf Firmenprofil anhängen
        if (class_exists('CMS\\PluginManager')
            && in_array('cms-companies', \CMS\PluginManager::instance()->getActivePlugins(), true)) {
            \CMS\Hooks::addFilter('page_content', [$this, 'inject_company_jobs'], 20);
        }
    }

    /**
     * Phase 6.2 – Hängt eine Job-Liste an das Firmenprofil an.
     *
     * Wird als Filter auf `page_content` registriert.
     * Prüft, ob die aktuelle URL `/companies/` enthält und fügt
     * die zugehörigen Jobs als HTML-Block an den Seiteninhalt an.
     *
     * @param  string $content Bisheriger Seiteninhalt
     * @return string Erweiterter Seiteninhalt
     */
    public function inject_company_jobs(string $content): string
    {
        // Nur auf Firmenprofilseiten aktiv
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (!str_contains($requestUri, '/companies/')) {
            return $content;
        }

        // Firmen-Slug aus URL extrahieren: /companies/:slug
        if (!preg_match('#/companies/([^/?#]+)#', $requestUri, $m)) {
            return $content;
        }
        $companySlug = preg_replace('/[^a-z0-9\-_]/', '', strtolower($m[1]));

        try {
            $db = \CMS\Database::instance();
            $p  = $db->getPrefix();

            // Firmen-ID ermitteln
            $company = $db->get_row(
                "SELECT id, name FROM {$p}companies WHERE slug = ? AND status = 'active'",
                [$companySlug]
            );
            if (!$company) {
                return $content;
            }

            // Zugehörige veröffentlichte Jobs laden
            $jobs = $db->get_results(
                "SELECT id, title, slug, location, employment_type, experience_level
                 FROM {$p}jpg_profiles
                 WHERE company_id = ? AND status = 'published'
                 ORDER BY created_at DESC",
                [(int) $company->id]
            );

            if (empty($jobs)) {
                return $content;
            }

            // HTML-Block aufbauen
            $html = '<section class="jpg-company-jobs" style="margin-top:2.5rem;">';
            $html .= '<h2 style="font-size:1.25rem;font-weight:700;margin-bottom:1rem;color:#1e293b;">💼 Offene Stellen bei ' . htmlspecialchars($company->name, ENT_QUOTES) . '</h2>';
            $html .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem;">';

            foreach ($jobs as $job) {
                $typeLabel = match ($job->employment_type ?? '') {
                    'fulltime'  => 'Vollzeit',
                    'parttime'  => 'Teilzeit',
                    'contract'  => 'Freelance',
                    'temporary' => 'Befristet',
                    'intern'    => 'Praktikum',
                    'minijob'   => 'Minijob',
                    default     => $job->employment_type ?? '',
                };
                $loc  = htmlspecialchars($job->location ?? '', ENT_QUOTES);
                $type = htmlspecialchars($typeLabel, ENT_QUOTES);
                $url  = htmlspecialchars(SITE_URL . '/jobs/' . $job->slug, ENT_QUOTES);
                $html .= '<a href="' . $url . '" style="display:block;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:1.25rem;text-decoration:none;color:inherit;transition:box-shadow .2s;" '
                    . 'onmouseover="this.style.boxShadow=\'0 4px 12px rgba(0,0,0,.1)\'" onmouseout="this.style.boxShadow=\'none\'">';
                $html .= '<div style="font-weight:700;font-size:1rem;color:#1e293b;margin-bottom:.4rem;">' . htmlspecialchars($job->title, ENT_QUOTES) . '</div>';
                if ($loc) {
                    $html .= '<div style="font-size:.85rem;color:#64748b;">📍 ' . $loc . '</div>';
                }
                if ($type) {
                    $html .= '<div style="font-size:.85rem;color:#64748b;margin-top:.2rem;">💼 ' . $type . '</div>';
                }
                $html .= '<div style="margin-top:.75rem;font-size:.82rem;color:var(--admin-primary,#3b82f6);font-weight:600;">Zur Stelle →</div>';
                $html .= '</a>';
            }

            $html .= '</div></section>';

            return $content . $html;
        } catch (\Throwable $e) {
            // Fehler ignorieren – Seiteninhalt unverändert zurückgeben
            return $content;
        }
    }

    // ── Theme-integrierte Ansicht (/jobs/:slug) ──────────────────────────────

    // ── Öffentliche Job-Übersicht (/jobs) ────────────────────────────────────

    public function render_jobs_list(): void
    {
        $db  = \CMS\Database::instance();
        $p   = $db->getPrefix();

        // Filter aus GET-Parametern (optional, bewerberseitig)
        $companyFilter  = sanitize_text_field((string) ($_GET['company']  ?? ''));
        $typeFilter     = sanitize_text_field((string) ($_GET['type']     ?? ''));
        $locationFilter = sanitize_text_field((string) ($_GET['location'] ?? ''));
        // Phase 14.3: Neue Filter
        $categoryFilter = sanitize_text_field((string) ($_GET['category'] ?? ''));
        $remoteFilter   = sanitize_text_field((string) ($_GET['remote']   ?? ''));
        $salaryMin      = (int) ($_GET['salary_min'] ?? 0);
        $page           = max(1, (int) ($_GET['page'] ?? 1));
        $perPage        = 20;
        $offset         = ($page - 1) * $perPage;

        try {
            $where  = "p.status = 'published' AND p.show_in_listing = 1";
            $params = [];

            if ($companyFilter !== '') {
                $where   .= " AND c.name LIKE ?";
                $params[] = '%' . $companyFilter . '%';
            }
            if ($typeFilter !== '' && in_array($typeFilter, ['fulltime','parttime','freelance','internship','mini'], true)) {
                $where   .= " AND p.employment_type = ?";
                $params[] = $typeFilter;
            }
            if ($locationFilter !== '') {
                $where   .= " AND p.location LIKE ?";
                $params[] = '%' . $locationFilter . '%';
            }
            // Phase 14.3: Neue Filter in SQL
            if ($categoryFilter !== '') {
                $where   .= " AND jc.name LIKE ?";
                $params[] = '%' . $categoryFilter . '%';
            }
            if ($remoteFilter !== '' && in_array($remoteFilter, ['onsite','hybrid','remote'], true)) {
                $where   .= " AND p.remote_option = ?";
                $params[] = $remoteFilter;
            }
            if ($salaryMin > 0) {
                $where   .= " AND (p.salary_min >= ? OR p.salary_max >= ?)";
                $params[] = $salaryMin;
                $params[] = $salaryMin;
            }

            $totalCount = (int) $db->get_var(
                "SELECT COUNT(*)
                   FROM {$p}jpg_profiles p
                   LEFT JOIN {$p}companies c ON c.id = p.company_id
                   LEFT JOIN {$p}jpg_job_categories jc ON jc.id = p.job_category_id
                  WHERE {$where}",
                $params
            );

            $countParams   = $params;
            $params[]      = $perPage;
            $params[]      = $offset;

            $profiles = $db->get_results(
                "SELECT p.id, p.title, p.slug, p.summary, p.location,
                        p.employment_type, p.remote_option, p.salary_min,
                        p.salary_max, p.created_at, p.company_id,
                        p.job_category_id,
                        c.name AS company_name, c.website AS company_website,
                        jc.name AS category_name
                   FROM {$p}jpg_profiles p
                   LEFT JOIN {$p}companies c ON c.id = p.company_id
                   LEFT JOIN {$p}jpg_job_categories jc ON jc.id = p.job_category_id
                  WHERE {$where}
                  ORDER BY p.created_at DESC
                  LIMIT ? OFFSET ?",
                $params
            ) ?: [];

            // Phase 14.3: Kategorien für Filter-Dropdown laden
            $allCategories = $db->get_results(
                "SELECT DISTINCT jc.name FROM {$p}jpg_job_categories jc
                 INNER JOIN {$p}jpg_profiles p ON p.job_category_id = jc.id
                 WHERE p.status = 'published' AND p.show_in_listing = 1
                 ORDER BY jc.name ASC", []
            ) ?: [];
        } catch (\Throwable $e) {
            $profiles      = [];
            $totalCount    = 0;
            $allCategories = [];
        }

        $pages          = (int) ceil($totalCount / $perPage);
        $typeLabels     = [
            'fulltime'    => 'Vollzeit',
            'parttime'    => 'Teilzeit',
            'freelance'   => 'Freiberuflich',
            'internship'  => 'Praktikum',
            'mini'        => 'Minijob',
        ];
        $remoteLabels   = [
            'onsite'  => 'Vor Ort',
            'hybrid'  => 'Hybrid',
            'remote'  => 'Remote',
        ];

        if (class_exists('CMS\ThemeManager')) {
            \CMS\ThemeManager::instance()->render('jobs-list', compact(
                'profiles', 'totalCount', 'pages', 'page',
                'companyFilter', 'typeFilter', 'locationFilter',
                'categoryFilter', 'remoteFilter', 'salaryMin',
                'typeLabels', 'remoteLabels', 'allCategories'
            ));
        } else {
            extract(compact(
                'profiles', 'totalCount', 'pages', 'page',
                'companyFilter', 'typeFilter', 'locationFilter',
                'categoryFilter', 'remoteFilter', 'salaryMin',
                'typeLabels', 'remoteLabels', 'allCategories'
            ));
            include JPG_DIR . 'views/public/jobs-list.php';
        }
    }

    // ── Einzelne Job-Ansicht (/jobs/:slug) ────────────────────────────────────

    public function render_integrated(string $slug): void
    {
        $profile = $this->load_profile($slug);

        if (!$profile) {
            http_response_code(404);
            if (class_exists('CMS\ThemeManager')) {
                \CMS\ThemeManager::instance()->render('404');
            }
            return;
        }

        // DSGVO-konformes Tracking
        $this->track_view($profile);

        // SEO-Meta-Tags via SEOService
        $this->set_seo_meta($profile);

        // Öffentliches CSS laden
        $this->enqueue_public_css($profile);

        // Template rendern (innerhalb des aktiven Themes, falls Template vorhanden)
        $data = $this->prepare_template_data($profile);

        $themeHasTemplate = false;
        if (class_exists('CMS\ThemeManager')) {
            $themePath        = \CMS\ThemeManager::instance()->getThemePath();
            $themeHasTemplate = file_exists($themePath . 'job-single.php');
        }

        if ($themeHasTemplate) {
            \CMS\ThemeManager::instance()->render('job-single', $data);
        } else {
            // Eigenes Template mit Theme-Header/-Footer
            if (class_exists('CMS\ThemeManager')) {
                $tm = \CMS\ThemeManager::instance();
                $tm->getHeader();
                extract($data, EXTR_SKIP);
                include JPG_DIR . 'views/public/single-integrated.php';
                $tm->getFooter();
            } else {
                extract($data, EXTR_SKIP);
                include JPG_DIR . 'views/public/single-integrated.php';
            }
        }
    }

    // ── Whitelabel-Ansicht (/career/:slug) ───────────────────────────────────

    public function render_whitelabel(string $slug): void
    {
        // Feature-Gate: Whitelabel nur mit aktiver Feature-Flag
        if (function_exists('user_has_feature') && !user_has_feature('whitelabel_jobs')) {
            // Fallback zur normalen Ansicht
            $this->render_integrated($slug);
            return;
        }

        $profile = $this->load_profile($slug);

        if (!$profile) {
            http_response_code(404);
            echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>404</title></head>';
            echo '<body style="font-family:sans-serif;text-align:center;padding:4rem;color:#64748b;">';
            echo '<h1>404</h1><p>Diese Stellenanzeige wurde nicht gefunden.</p></body></html>';
            return;
        }

        $this->track_view($profile);

        $data = $this->prepare_template_data($profile);

        // Branding-CSS für Whitelabel zusammenbauen
        $brandingCss = $this->build_branding_css();

        $data['brandingCss'] = $brandingCss;
        extract($data);

        include JPG_DIR . 'views/public/single-whitelabel.php';
    }

    // ── PDF-Export (/api/jobs/:slug/pdf) ──────────────────────────────────────

    public function handle_pdf_export(string $slug): void
    {
        // Rate-Limiting
        if (!$this->check_rate_limit('pdf_export', 5, 60)) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Rate limit exceeded. Max 5 PDFs pro Minute.']);
            exit;
        }

        $profile = $this->load_profile($slug);

        if (!$profile) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Profil nicht gefunden.']);
            exit;
        }

        $data = $this->prepare_template_data($profile);

        // HTML-Rendering über Export-Klasse
        $html = CMS_JPG_Export::instance()->render_html($profile->id);

        // Prüfe ob mPDF verfügbar
        if (!class_exists('\\Mpdf\\Mpdf')) {
            // Fallback: HTML-Download
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $profile->slug . '.html"');
            echo $html;
            exit;
        }

        try {
            $mpdf = new \Mpdf\Mpdf([
                'mode'          => 'utf-8',
                'format'        => 'A4',
                'margin_left'   => 15,
                'margin_right'  => 15,
                'margin_top'    => 16,
                'margin_bottom' => 16,
            ]);
            $mpdf->SetTitle($profile->title);
            $mpdf->SetAuthor($data['company'] ?? '365CMS');
            $mpdf->WriteHTML($html);

            $mpdf->Output($profile->slug . '.pdf', 'D');
        } catch (\Throwable $e) {
            error_log('CMS_JPG_Frontend::handle_pdf_export() mPDF error: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'PDF-Generierung fehlgeschlagen.']);
        }
        exit;
    }

    // ── Bewerbung verarbeiten (POST /jobs/:slug/apply) ────────────────────────

    /**
     * Verarbeitet eine Stellenbewerbung aus dem öffentlichen Bewerbungsformular.
     *
     * Ablauf:
     * 1. Rate-Limiting (Spam-Schutz)
     * 2. Honeypot-Prüfung
     * 3. CSRF-Verifikation
     * 4. Eingabe-Sanitierung
     * 5. Datei-Upload-Validierung (CV/PDF)
     * 6. Datenbank-Insert in jpg_applications
     * 7. NotificationService triggern
     * 8. JSON-Antwort zurückgeben
     */
    public function handle_apply(string $slug): void
    {
        // Sicherstellen, dass kein gepuffertes HTML die JSON-Antwort verunreinigt
        // (kann passieren wenn der CMS-Router ob_start() für Template-Ausgabe initiert hat)
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        // ── 1. Rate-Limiting ──────────────────────────────────────────────────
        if (!$this->check_rate_limit('apply_job', 3, 300)) {
            http_response_code(429);
            echo json_encode(['success' => false, 'error' => 'Zu viele Anfragen. Bitte warte 5 Minuten.']);
            exit;
        }

        // ── 2. Honeypot (verstecktes Feld muss leer sein) ────────────────────
        if (!empty($_POST['_hp_name'] ?? '')) {
            http_response_code(200); // Täuscht Bots
            echo json_encode(['success' => true]);
            exit;
        }

        // ── 3. CSRF-Verifikation ──────────────────────────────────────────────
        $token = $_POST['_jpg_csrf'] ?? '';
        $tokenValid = false;
        if (class_exists('CMS\\Security')) {
            $tokenValid = \CMS\Security::instance()->verifyToken($token, 'jpg_apply_' . $slug);
        }
        if (!$tokenValid) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen. Bitte die Seite neu laden.']);
            exit;
        }

        // ── 4. Profil laden & Status prüfen ──────────────────────────────────
        $profile = $this->load_profile($slug);
        if (!$profile) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Stellenanzeige nicht gefunden.']);
            exit;
        }

        // ── 5. Eingaben sanitieren ────────────────────────────────────────────
        $name        = $this->sanitize_text($_POST['applicant_name'] ?? '');
        $email       = filter_var($_POST['applicant_email'] ?? '', FILTER_VALIDATE_EMAIL);
        $coverLetter = $this->sanitize_html($_POST['cover_letter'] ?? '');
        $phone       = $this->sanitize_text($_POST['applicant_phone'] ?? '');

        if (empty($name) || strlen($name) < 2) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Bitte gib deinen vollständigen Namen an.']);
            exit;
        }
        if (!$email) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Bitte gib eine gültige E-Mail-Adresse an.']);
            exit;
        }
        if (empty($coverLetter) || strlen(strip_tags($coverLetter)) < 20) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Bitte verfasse ein kurzes Anschreiben (mind. 20 Zeichen).']);
            exit;
        }

        // ── 6. CV-Upload (optional) ───────────────────────────────────────────
        $cvFilePath  = null;
        $cvFileToken = null;
        if (!empty($_FILES['cv_file']['name'])) {
            $upload = $this->handle_cv_upload($_FILES['cv_file']);
            if (!$upload['success']) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => $upload['error']]);
                exit;
            }
            $cvFilePath  = $upload['path'];
            $cvFileToken = $upload['token'];
        }

        // ── 7. Datenbankinsert ────────────────────────────────────────────────
        try {
            $db = \CMS\Database::instance();
            $p  = $db->getPrefix();

            $db->execute(
                "INSERT INTO {$p}jpg_applications
                    (job_id, applicant_name, applicant_email, applicant_phone,
                     cover_letter, cv_file_path, cv_file_token, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'new', NOW(), NOW())",
                [
                    (int) $profile->id,
                    $name,
                    (string) $email,
                    $phone,
                    $coverLetter,
                    $cvFilePath,
                    $cvFileToken,
                ]
            );
            $applicationId = $db->lastInsertId();
        } catch (\Throwable $e) {
            error_log('CMS_JPG_Frontend::handle_apply() DB error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Bewerbung konnte nicht gespeichert werden. Bitte versuche es erneut.']);
            exit;
        }

        // ── 8. Notification-Trigger (Task 5.3) ───────────────────────────────
        $this->trigger_application_notifications($profile, $name, (string) $email, (int) $applicationId);

        echo json_encode([
            'success' => true,
            'message' => 'Deine Bewerbung wurde erfolgreich eingereicht. Wir melden uns bei dir!',
        ]);
        exit;
    }

    /**
     * Löst In-App- und E-Mail-Benachrichtigungen für den Job-Inserenten aus.
     *
     * @since 0.3.0
     */
    private function trigger_application_notifications(
        object $profile,
        string $applicantName,
        string $applicantEmail,
        int    $applicationId
    ): void {
        if (!class_exists('CMS\\Services\\NotificationService')) {
            return;
        }

        try {
            // Job-Ersteller laden
            $db = \CMS\Database::instance();
            $p  = $db->getPrefix();
            $owner = $db->get_row(
                "SELECT id, email, display_name FROM {$p}users WHERE id = ?",
                [(int) $profile->created_by]
            );
            if (!$owner) {
                return;
            }

            // Prüfen ob Benachrichtigung gewünscht ist
            $metaKey = 'jpg_notify_applications';
            $meta = $db->get_var(
                "SELECT meta_value FROM {$p}user_meta WHERE user_id = ? AND meta_key = ?",
                [(int) $owner->id, $metaKey]
            );
            if ($meta === '0') {
                return; // User hat Benachrichtigung deaktiviert
            }

            $notifData = [
                'user_id'    => (int) $owner->id,
                'type'       => 'new_application',
                'title'      => 'Neue Bewerbung: ' . $profile->title,
                'message'    => sprintf(
                    '%s hat sich auf deine Stelle „%s" beworben.',
                    $applicantName,
                    $profile->title
                ),
                'link'       => '/member/jobs/applications?job_id=' . (int) $profile->id,
                'meta'       => [
                    'job_id'          => (int) $profile->id,
                    'application_id'  => $applicationId,
                    'applicant_name'  => $applicantName,
                    'applicant_email' => $applicantEmail,
                ],
            ];

            \CMS\Services\NotificationService::create($notifData);
        } catch (\Throwable $e) {
            error_log('CMS_JPG_Frontend::trigger_application_notifications() error: ' . $e->getMessage());
        }
    }

    /**
     * Verarbeitet den CV-Dateiupload sicher.
     *
     * @param  array<string, mixed> $file  $_FILES-Element
     * @return array{success: bool, path?: string, token?: string, error?: string}
     */
    private function handle_cv_upload(array $file): array
    {
        // Nur PDF/DOCX/DOC erlaubt
        $allowed = ['application/pdf', 'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowed, true)) {
            return ['success' => false, 'error' => 'Nur PDF oder Word-Dateien sind erlaubt.'];
        }

        $maxSize = 5 * 1024 * 1024; // 5 MB
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'Datei zu groß (max. 5 MB).'];
        }

        $uploadDir = (defined('ABSPATH') ? ABSPATH : dirname(__DIR__, 4)) . '/uploads/applications/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0750, true);
        }

        $token    = bin2hex(random_bytes(24));
        $ext      = $mimeType === 'application/pdf' ? 'pdf' : 'docx';
        $fileName = $token . '.' . $ext;
        $destPath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['success' => false, 'error' => 'Datei konnte nicht gespeichert werden.'];
        }

        // Relativer Pfad (relativ zu UPLOADS_PATH / ABSPATH/uploads/) wird gespeichert,
        // damit download_file() ihn korrekt mit dem Upload-Basisverzeichnis zusammensetzen kann.
        return ['success' => true, 'path' => 'applications/' . $fileName, 'token' => $token];
    }

    /**
     * Sanitiert einfachen Text (kein HTML erlaubt).
     */
    private function sanitize_text(string $value): string
    {
        if (function_exists('sanitize_text_field')) {
            return sanitize_text_field($value);
        }
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES);
    }

    /**
     * Sanitiert HTML (erlaubte Tags für Anschreiben).
     */
    private function sanitize_html(string $value): string
    {
        if (class_exists('CMS\\Security')
            && method_exists(\CMS\Security::instance(), 'sanitizeHtml')) {
            return \CMS\Security::instance()->sanitizeHtml($value);
        }
        return strip_tags($value, '<p><br><strong><em><ul><ol><li>');
    }

    // ── Profil laden ─────────────────────────────────────────────────────────

    private function load_profile(string $slug): ?object
    {
        $db = \CMS\Database::instance();
        $p  = $db->getPrefix();

        // Caching (falls CacheManager verfügbar)
        $cacheKey = 'job_profile_' . $slug;
        if (class_exists('CMS\CacheManager')) {
            $cached = \CMS\CacheManager::instance()->get($cacheKey);
            if ($cached !== null && $cached !== false) {
                // Cache kann Array liefern (Serialisierungs-Rundreise) → sicher zu object casten
                return is_array($cached) ? (object) $cached : (object) $cached;
            }
        }

        $profile = $db->get_row(
            "SELECT p.*, jc.name AS category_name, jc.color AS category_color
             FROM {$p}jpg_profiles p
             LEFT JOIN {$p}jpg_job_categories jc ON jc.id = p.job_category_id
             WHERE p.slug = ? AND p.status = 'published'",
            [$slug]
        );

        // get_row() kann in bestimmten DB-Adaptern ein Array zurückgeben → Normalisierung
        if (is_array($profile)) {
            $profile = (object) $profile;
        }

        if ($profile && class_exists('CMS\CacheManager')) {
            \CMS\CacheManager::instance()->set($cacheKey, $profile, 1800); // 30 Min
        }

        return $profile ?: null;
    }

    // ── Template-Daten vorbereiten ───────────────────────────────────────────

    private function prepare_template_data(object $profile): array
    {
        $profiles = CMS_JPG_Profiles::instance();

        return [
            'profile'      => $profile,
            'tasks'        => $profiles->get_tasks((int) $profile->id),
            'requirements' => $profiles->get_requirements((int) $profile->id),
            'benefits'     => $profiles->getResolvedBenefits((int) $profile->id),
            'skills'       => $profiles->get_skills((int) $profile->id),
            'company'      => $profile->company_name ?? '',
            'jsonld'       => $this->build_jsonld($profile),
            // Phase 6.3 – cms-experts Team-Sektion
            'experts'      => $this->load_company_experts($profile),
            // Bewerbungsformular CSRF-Token (Task 5.3)
            'applyCsrf'    => class_exists('CMS\\Security')
                ? \CMS\Security::instance()->generateToken('jpg_apply_' . $profile->slug)
                : bin2hex(random_bytes(16)),
        ];
    }

    /**
     * Phase 6.3 – Lädt Experten der verknüpften Firma.
     *
     * Prüft ob `cms-experts` aktiv ist, bevor DB-Abfragen ausgeführt werden.
     *
     * @param  object $profile Job-Profil-Objekt
     * @return array<object>   Liste der Experten-Objekte (leer wenn Plugin inaktiv)
     */
    private function load_company_experts(object $profile): array
    {
        if (empty($profile->company_id)) {
            return [];
        }
        if (!class_exists('CMS\\PluginManager')
            || !in_array('cms-experts', \CMS\PluginManager::instance()->getActivePlugins(), true)) {
            return [];
        }
        try {
            $db = \CMS\Database::instance();
            $p  = $db->getPrefix();
            return $db->get_results(
                "SELECT e.id, e.name, e.title AS job_title, e.avatar_url, e.bio_short
                 FROM {$p}cms_experts e
                 INNER JOIN {$p}cms_company_experts ce ON ce.expert_id = e.id
                 WHERE ce.company_id = ? AND e.active = 1
                 ORDER BY ce.sort_order ASC, e.name ASC
                 LIMIT 8",
                [(int) $profile->company_id]
            ) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ── SEO Meta-Tags ────────────────────────────────────────────────────────

    private function set_seo_meta(object $profile): void
    {
        if (!class_exists('CMS\Services\SEOService')) {
            return;
        }

        try {
            $seo         = \CMS\Services\SEOService::instance();
            $title       = $profile->title . ' – Stellenanzeige';
            $description = mb_substr(strip_tags($profile->summary ?? $profile->description ?? ''), 0, 160);
            $url         = SITE_URL . '/jobs/' . $profile->slug;

            $seo->setTitle($title);
            $seo->setDescription($description);
            $seo->setCanonical($url);

            // Open Graph Tags
            if (method_exists($seo, 'setOpenGraph')) {
                $seo->setOpenGraph([
                    'og:type'        => 'website',
                    'og:title'       => $title,
                    'og:description' => $description,
                    'og:url'         => $url,
                    'og:site_name'   => defined('SITE_NAME') ? SITE_NAME : '',
                ]);
            } else {
                // Fallback: Direct meta injection via SEOService::addMeta() if available
                if (method_exists($seo, 'addMeta')) {
                    $seo->addMeta('og:type',        'website',     'property');
                    $seo->addMeta('og:title',       $title,        'property');
                    $seo->addMeta('og:description', $description,  'property');
                    $seo->addMeta('og:url',         $url,          'property');
                    if (defined('SITE_NAME')) {
                        $seo->addMeta('og:site_name', SITE_NAME, 'property');
                    }
                }
            }
        } catch (\Throwable $e) {
            // SEOService nicht verfügbar – ignorieren
        }
    }

    // ── DSGVO-Tracking ───────────────────────────────────────────────────────

    private function track_view(object $profile): void
    {
        // View-Counter
        try {
            $db = \CMS\Database::instance();
            $p  = $db->getPrefix();
            $db->execute(
                "UPDATE {$p}jpg_profiles SET views = views + 1 WHERE id = ?",
                [(int) $profile->id]
            );
        } catch (\Throwable $e) {
            // Ignorieren – kein kritischer Fehler
        }

        // TrackingService (DSGVO-konform)
        if (class_exists('CMS\Services\TrackingService')) {
            try {
                \CMS\Services\TrackingService::instance()->trackPageView([
                    'page_type' => 'job_profile',
                    'entity_id' => (int) $profile->id,
                    'slug'      => $profile->slug,
                ]);
            } catch (\Throwable $e) {
                // Ignorieren
            }
        }

        // Stat-Logging
        CMS_JPG_Profiles::instance()->log_stat((int) $profile->id, 'view');
    }

    // ── Public CSS mit Custom Properties injizieren ──────────────────────────

    private function enqueue_public_css(object $profile): void
    {
        $cssFile = JPG_DIR . 'assets/css/public.css';
        if (!file_exists($cssFile)) {
            return;
        }

        // Basis-CSS
        echo '<link rel="stylesheet" href="'
            . htmlspecialchars(JPG_URL . 'assets/css/public.css')
            . '?v=' . filemtime($cssFile) . '">' . "\n";

        // Custom Branding Injection (Phase 4.3)
        if (function_exists('user_has_feature') && user_has_feature('custom_branding')) {
            $this->inject_custom_branding();
        }
    }

    /**
     * Injiziert Custom-Branding CSS aus den Corporate-Design-Einstellungen.
     */
    private function inject_custom_branding(): void
    {
        $css = $this->build_branding_css();
        if (!empty($css)) {
            echo '<style id="jpg-custom-branding">' . $css . '</style>' . "\n";
        }
    }

    /**
     * Baut Branding-CSS-String aus Corporate-Design-Settings.
     */
    private function build_branding_css(): string
    {
        try {
            $db = \CMS\Database::instance();
            $p  = $db->getPrefix();

            $settings = $db->get_results(
                "SELECT setting_key, setting_value FROM {$p}jpg_settings
                 WHERE setting_key LIKE 'cd_%'"
            );

            $cd = [];
            foreach ($settings as $s) {
                $cd[$s->setting_key] = $s->setting_value;
            }

            if (empty($cd)) {
                return '';
            }

            $css = ':root {';
            if (!empty($cd['cd_primary_color'])) {
                $css .= '--jpg-primary: ' . htmlspecialchars($cd['cd_primary_color']) . ';';
            }
            if (!empty($cd['cd_secondary_color'])) {
                $css .= '--jpg-secondary: ' . htmlspecialchars($cd['cd_secondary_color']) . ';';
            }
            if (!empty($cd['cd_font_body'])) {
                $css .= '--jpg-font-body: ' . htmlspecialchars($cd['cd_font_body']) . ';';
            }
            if (!empty($cd['cd_font_heading'])) {
                $css .= '--jpg-font-heading: ' . htmlspecialchars($cd['cd_font_heading']) . ';';
            }
            $css .= '}';

            return $css;
        } catch (\Throwable $e) {
            return '';
        }
    }

    // ── Google for Jobs JSON-LD ──────────────────────────────────────────────

    private function build_jsonld(object $profile): string
    {
        $jsonld = [
            '@context'       => 'https://schema.org/',
            '@type'          => 'JobPosting',
            'title'          => $profile->title,
            'description'    => $profile->description ?? $profile->summary ?? '',
            'datePosted'     => ($profile->published_at ?? $profile->created_at ?? date('Y-m-d')),
            'employmentType' => $this->map_employment_type($profile->employment_type ?? 'fulltime'),
            'jobLocation'    => [
                '@type'   => 'Place',
                'address' => [
                    '@type'          => 'PostalAddress',
                    'addressLocality'=> $profile->location ?? '',
                    'addressCountry' => 'DE',
                ],
            ],
        ];

        // Gehalt
        if (!empty($profile->salary_min) || !empty($profile->salary_max)) {
            $jsonld['baseSalary'] = [
                '@type'    => 'MonetaryAmount',
                'currency' => 'EUR',
                'value'    => [
                    '@type'    => 'QuantitativeValue',
                    'minValue' => (float) ($profile->salary_min ?? 0),
                    'maxValue' => (float) ($profile->salary_max ?? 0),
                    'unitText' => 'YEAR',
                ],
            ];
        }

        // hiringOrganization – aus cms_companies wenn vorhanden (Phase 6.4)
        $jsonld['hiringOrganization'] = $this->get_hiring_organization($profile);

        return json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    private function get_hiring_organization(object $profile): array
    {
        $org = [
            '@type' => 'Organization',
            'name'  => 'Unbekannt',
        ];

        // Versuche aus cms_companies (Cross-Plugin, Phase 6)
        if (!empty($profile->company_id)
            && class_exists('CMS\PluginManager')
            && in_array('cms-companies', \CMS\PluginManager::instance()->getActivePlugins(), true)) {
            try {
                $db = \CMS\Database::instance();
                $p  = $db->getPrefix();
                $company = $db->get_row(
                    "SELECT name, logo_url, website FROM {$p}companies WHERE id = ?",
                    [(int) $profile->company_id]
                );
                if ($company) {
                    $org['name'] = $company->name;
                    if (!empty($company->logo_url)) {
                        $org['logo'] = $company->logo_url;
                    }
                    if (!empty($company->website)) {
                        $org['sameAs'] = $company->website;
                    }
                    return $org;
                }
            } catch (\Throwable $e) {
                // Fallback auf CD-Settings
            }
        }

        // Fallback: Corporate Design Settings
        try {
            $db = \CMS\Database::instance();
            $p  = $db->getPrefix();
            $cdName = $db->get_var(
                "SELECT setting_value FROM {$p}jpg_settings WHERE setting_key = 'cd_company_name'",
                []
            );
            if ($cdName) {
                $org['name'] = $cdName;
            }
            $cdLogo = $db->get_var(
                "SELECT setting_value FROM {$p}jpg_settings WHERE setting_key = 'cd_logo_url'",
                []
            );
            if ($cdLogo) {
                $org['logo'] = $cdLogo;
            }
        } catch (\Throwable $e) {
            // Ignorieren
        }

        return $org;
    }

    private function map_employment_type(string $type): string
    {
        return match ($type) {
            'fulltime'  => 'FULL_TIME',
            'parttime'  => 'PART_TIME',
            'contract'  => 'CONTRACTOR',
            'temporary' => 'TEMPORARY',
            'intern'    => 'INTERN',
            'volunteer' => 'VOLUNTEER',
            'minijob'   => 'PART_TIME',
            default     => 'FULL_TIME',
        };
    }

    // ── Rate-Limiting ────────────────────────────────────────────────────────

    /**
     * Einfaches dateibasiertes Rate-Limiting.
     *
     * @param string $action  Aktion (z.B. 'pdf_export')
     * @param int    $max     Maximale Anfragen
     * @param int    $window  Zeitfenster in Sekunden
     */
    private function check_rate_limit(string $action, int $max, int $window): bool
    {
        // CMS-Core Rate-Limiting bevorzugen
        if (class_exists('CMS\Security')
            && method_exists(\CMS\Security::instance(), 'checkRateLimit')) {
            return \CMS\Security::instance()->checkRateLimit($action, $max, $window);
        }

        // Fallback: Session-basiert
        $key = 'jpg_rate_' . $action;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $now  = time();
        $data = $_SESSION[$key] ?? ['count' => 0, 'reset' => $now + $window];

        if ($now > $data['reset']) {
            $data = ['count' => 0, 'reset' => $now + $window];
        }

        if ($data['count'] >= $max) {
            return false;
        }

        $data['count']++;
        $_SESSION[$key] = $data;

        return true;
    }
}
