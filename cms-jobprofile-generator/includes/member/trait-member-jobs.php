<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Trait: Stellenanzeigen Routen-Handler (standalone /member/jobs/*)
 *
 * @package CMS_JobProfileGenerator
 */
trait CMS_JPG_Member_Jobs_Trait
{
    // ── Routen-Handler ────────────────────────────────────────────────────────

    /** GET|POST /member/jobs – Meine Stellenanzeigen */
    public function render_list(): void
    {
        $this->require_auth();

        $canCreate  = !function_exists('user_can_create_resource') || user_can_create_resource('job_profiles');
        $bulkNotice = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
            if ($this->verify_token('list_action')) {
                $bulkAction = sanitize_key($_POST['bulk_action'] ?? '');
                $rawIds     = array_filter(array_map('intval', (array)($_POST['profile_ids'] ?? [])));
                if (!empty($rawIds)) {
                    $ph = implode(',', array_fill(0, count($rawIds), '?'));
                    try {
                        if ($bulkAction === 'delete') {
                            $this->db->query(
                                "DELETE FROM {$this->p}jpg_profiles WHERE id IN ({$ph}) AND created_by = ?",
                                [...$rawIds, $this->userId]
                            );
                            $bulkNotice = count($rawIds) . ' Stelle(n) gelöscht.';
                        } elseif ($bulkAction === 'archive') {
                            $this->db->query(
                                "UPDATE {$this->p}jpg_profiles SET status = 'archived' WHERE id IN ({$ph}) AND created_by = ?",
                                [...$rawIds, $this->userId]
                            );
                            $bulkNotice = count($rawIds) . ' Stelle(n) archiviert.';
                        }
                    } catch (\Throwable $e) { /* ignore */ }
                }
            }
        }

        try {
            $profiles = $this->db->get_results(
                "SELECT id, title, status, workflow_status, workflow_step,
                        location, employment_type, views, created_at, slug
                 FROM {$this->p}jpg_profiles
                 WHERE created_by = ?
                 ORDER BY created_at DESC",
                [$this->userId]
            ) ?: [];
        } catch (\Throwable $e) {
            $profiles = [];
        }

        $appCountMap = [];
        $appTrend7   = [];
        try {
            $rows = $this->db->get_results(
                "SELECT a.job_id, COUNT(*) AS cnt
                 FROM {$this->p}jpg_applications a
                 INNER JOIN {$this->p}jpg_profiles p ON p.id = a.job_id
                 WHERE p.created_by = ? AND a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 GROUP BY a.job_id",
                [$this->userId]
            ) ?: [];
            foreach ($rows as $r) {
                $appCountMap[(int)$r->job_id] = (int)$r->cnt;
            }
            $t7rows = $this->db->get_results(
                "SELECT DATE(a.created_at) AS d, COUNT(*) AS cnt
                 FROM {$this->p}jpg_applications a
                 INNER JOIN {$this->p}jpg_profiles p ON p.id = a.job_id
                 WHERE p.created_by = ? AND a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 GROUP BY DATE(a.created_at) ORDER BY d ASC",
                [$this->userId]
            ) ?: [];
            foreach ($t7rows as $r) {
                $appTrend7[$r->d] = (int)$r->cnt;
            }
        } catch (\Throwable $e) { /* Analytics optional */ }

        $quotaUsed  = count($profiles);
        $quotaLimit = function_exists('get_user_resource_limit') ? (int)get_user_resource_limit('job_profiles') : -1;
        $totalViews = array_sum(array_column($profiles, 'views'));
        $totalApps  = array_sum($appCountMap);

        $csrf = $this->generate_token('list_action');
        $this->render_with_layout('views/member/page-jobs-list.php',
            compact('profiles', 'canCreate', 'csrf', 'bulkNotice',
                    'appCountMap', 'appTrend7', 'quotaUsed', 'quotaLimit', 'totalViews', 'totalApps'));
    }

    /** GET+POST /member/jobs/create – Neues Profil erstellen */
    public function render_create(): void
    {
        $this->require_auth();

        if (function_exists('user_can_create_resource') && !user_can_create_resource('job_profiles')) {
            $this->render_limit_error();
            return;
        }

        $notice = '';
        $error  = '';
        $newId  = 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verify_token('create')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                [$notice, $error, $newId] = $this->save_profile_post(0);
                if ($newId > 0 && empty($error)) {
                    header('Location: /member/jobs/edit/' . $newId . '?created=1');
                    exit;
                }
            }
        }

        $categories    = [];
        $allBenefits   = [];
        $workflowSteps = [];
        $tasks         = [];
        $requirements  = [];
        $benefitIds    = [];
        $createPrefill = $this->build_create_prefill();
        try {
            $categories    = CMS_JPG_JobCategories::instance()->get_all();
            $allBenefits   = CMS_JPG_BenefitsCatalog::instance()->get_grouped();
            $workflowSteps = class_exists('CMS_JPG_Workflow')
                ? CMS_JPG_Workflow::instance()->get_steps()
                : [];
        } catch (\Throwable $e) {
            $categories = [];
        }

        $csrf = $this->generate_token('create');
        $this->render_with_layout('views/member/page-jobs-create.php',
            compact('categories', 'allBenefits', 'tasks', 'requirements', 'benefitIds', 'workflowSteps', 'csrf', 'notice', 'error', 'createPrefill'));
    }

    /** GET+POST /member/jobs/edit/:id – Profil bearbeiten */
    public function render_edit(string $id): void
    {
        $this->require_auth();

        $profileId = (int) $id;
        $profile   = $this->load_own_profile($profileId);

        if (!$profile) {
            http_response_code(404);
            $this->render_with_layout('views/member/page-jobs-list.php', [
                'profiles'  => [],
                'canCreate' => true,
                'csrf'      => $this->generate_token('list_action'),
                'error'     => 'Profil nicht gefunden oder kein Zugriff.',
            ]);
            return;
        }

        $notice = !empty($_GET['created']) ? 'Profil angelegt – jetzt vervollständigen!' : '';
        $error  = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verify_token('edit')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                [$notice, $error] = $this->save_profile_post($profileId);
                $profile = $this->load_own_profile($profileId);
            }
        }

        $categories     = CMS_JPG_JobCategories::instance()->get_all();
        $allBenefits    = CMS_JPG_BenefitsCatalog::instance()->get_grouped();
        $tasks          = CMS_JPG_Profiles::instance()->get_tasks($profileId);
        $requirements   = CMS_JPG_Profiles::instance()->get_requirements($profileId);
        $benefitIds     = CMS_JPG_Profiles::instance()->get_benefit_ids($profileId);
        $workflowSteps  = class_exists('CMS_JPG_Workflow') ? CMS_JPG_Workflow::instance()->get_steps() : [];
        $workflowHistory= class_exists('CMS_JPG_Workflow') ? CMS_JPG_Workflow::instance()->get_history($profileId) : [];

        $csrf   = $this->generate_token('edit');
        $wfCsrf = $this->generate_token('workflow_submit');
        $this->render_with_layout('views/member/page-jobs-edit.php',
            compact('profile', 'categories', 'allBenefits', 'tasks', 'requirements',
                    'benefitIds', 'workflowSteps', 'workflowHistory', 'csrf', 'wfCsrf', 'notice', 'error'));
    }

    /** GET /member/jobs/duplicate/:id – Profil als Entwurf klonen */
    public function render_duplicate(string $id): void
    {
        $this->require_auth();
        $newId = $this->duplicate_profile((int) $id);
        if ($newId > 0) {
            header('Location: /member/jobs/edit/' . $newId . '?duplicated=1');
        } else {
            header('Location: /member/jobs?error=duplicate');
        }
        exit;
    }

    /** POST /member/jobs/workflow/submit/:id – Profil zur Genehmigung einreichen */
    public function handle_workflow_submit(string $id): void
    {
        $this->require_auth();

        $profileId = (int) $id;

        if (!$this->verify_token('workflow_submit')) {
            $_SESSION['error'] = 'Sicherheitscheck fehlgeschlagen.';
            header('Location: /member/jobs/edit/' . $profileId);
            exit;
        }

        if (!class_exists('CMS_JPG_Workflow')) {
            $_SESSION['error'] = 'Workflow nicht verfügbar.';
            header('Location: /member/jobs/edit/' . $profileId);
            exit;
        }

        $ok = CMS_JPG_Workflow::instance()->submit_for_approval($profileId, $this->userId);
        if ($ok) {
            $_SESSION['success'] = '✅ Stellenanzeige wurde zur Genehmigung eingereicht.';
        } else {
            $_SESSION['error'] = '❌ Einreichen fehlgeschlagen. Bitte prüfe das Profil und versuche es erneut.';
        }

        header('Location: /member/jobs/edit/' . $profileId);
        exit;
    }

    // ── Layout-Wrapper ────────────────────────────────────────────────────────

    /**
     * Rendert eine View eingebettet in das vollständige Member-Layout.
     *
     * @param string              $viewFile
     * @param array<string,mixed> $vars
     */
    private function render_with_layout(string $viewFile, array $vars = []): void
    {
        if (!function_exists('renderMemberSidebar') || !function_exists('renderMemberSidebarStyles')) {
            $partialFile = ABSPATH . 'member/partials/member-menu.php';
            if (file_exists($partialFile)) {
                require_once $partialFile;
            }
        }

        extract($vars, EXTR_SKIP);
        $baseUrl = '/member/jobs';

        ob_start();
        include JPG_DIR . $viewFile;
        $pageContent = ob_get_clean();

        $siteUrl  = defined('SITE_URL')  ? SITE_URL  : '';
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'CMS';
        $coreMainCssUrl = function_exists('cms_asset_url')
            ? cms_asset_url('css/main.css')
            : $siteUrl . '/assets/css/main.css';
        $coreAdminCssUrl = function_exists('cms_asset_url')
            ? cms_asset_url('css/admin.css')
            : $siteUrl . '/assets/css/admin.css?v=20260222b';
        $coreMemberCssUrl = function_exists('cms_asset_url')
            ? cms_asset_url('css/member.css')
            : $siteUrl . '/assets/css/member.css';

        $flashSuccess = $_SESSION['success'] ?? null;
        $flashError   = $_SESSION['error']   ?? null;
        unset($_SESSION['success'], $_SESSION['error']);

        echo '<!DOCTYPE html><html lang="de"><head>' . "\n";
        echo '<meta charset="UTF-8">' . "\n";
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        echo '<title>Stellenanzeigen – ' . htmlspecialchars($siteName) . '</title>' . "\n";
        echo '<link rel="stylesheet" href="' . htmlspecialchars($coreMainCssUrl, ENT_QUOTES) . '">' . "\n";
        echo '<link rel="stylesheet" href="' . htmlspecialchars($coreAdminCssUrl, ENT_QUOTES) . '">' . "\n";
        echo '<link rel="stylesheet" href="' . htmlspecialchars($coreMemberCssUrl, ENT_QUOTES) . '">' . "\n";
        if (function_exists('renderMemberSidebarStyles')) {
            renderMemberSidebarStyles();
        }
        echo '</head>' . "\n";
        echo '<body class="member-body">' . "\n";

        if (class_exists('CMS\\Member\\PluginDashboardRegistry')) {
            \CMS\Member\PluginDashboardRegistry::instance()->init();
        }

        if (function_exists('renderMemberSidebar')) {
            renderMemberSidebar('member-jobs');
        }

        echo '<div class="member-content">' . "\n";

        if ($flashSuccess) {
            echo '<div class="member-alert member-alert-success" style="margin-bottom:1.25rem;">'
                . '<span class="alert-icon">✓</span>'
                . '<span>' . htmlspecialchars($flashSuccess) . '</span></div>' . "\n";
        }
        if ($flashError) {
            echo '<div class="member-alert member-alert-error" style="margin-bottom:1.25rem;">'
                . '<span class="alert-icon">✕</span>'
                . '<span>' . htmlspecialchars($flashError) . '</span></div>' . "\n";
        }

        echo $pageContent;
        echo '</div>' . "\n";
        echo '</body></html>' . "\n";
    }

    // ── Profil-Speichern ─────────────────────────────────────────────────────

    /** @return array{string, string, int} [notice, error, id] */
    private function save_profile_post(int $id): array
    {
        $notice = '';
        $error  = '';

        $title = $this->getPost('title');
        if (empty($title)) {
            return [$notice, 'Stellentitel ist erforderlich.', $id];
        }

        $slugInput = sanitize_text_field($_POST['slug'] ?? '');
        $autoSlug  = $this->generate_unique_slug($slugInput ?: $title, $id);

        $data = [
            'title'           => $title,
            'slug'            => $autoSlug,
            'job_category_id' => $this->getPost('job_category_id', 'int'),
            'status'          => 'draft',
            'summary'         => $this->getPost('summary'),
            'location'        => $this->getPost('location'),
            'employment_type' => $this->getPost('employment_type'),
            'salary_min'      => $this->getPost('salary_min'),
            'salary_max'      => $this->getPost('salary_max'),
            'remote_option'   => $this->getPost('remote_option'),
            'show_in_listing' => isset($_POST['show_in_listing']) ? 1 : 0,
            'created_by'      => $this->userId,
            'updated_by'      => $this->userId,
        ];

        if (class_exists('CMS\\Security') && method_exists(\CMS\Security::instance(), 'sanitizeHtml')) {
            $data['description'] = \CMS\Security::instance()->sanitizeHtml($_POST['description'] ?? '');
        } else {
            $data['description'] = strip_tags(
                $_POST['description'] ?? '',
                '<p><br><strong><em><b><i><u><ul><ol><li><a><h1><h2><h3><h4><blockquote><img><table><tr><td><th><thead><tbody><tfoot><span><div>'
            );
        }

        try {
            if ($id === 0 && function_exists('user_can_create_resource')
                && !user_can_create_resource('job_profiles')) {
                return [$notice, 'Ihr Profil-Limit ist erreicht. Bitte upgraden Sie Ihren Plan.', 0];
            }
            $newId = CMS_JPG_Profiles::instance()->save($data, $id);
        } catch (\Throwable $e) {
            return [$notice, 'Speichern fehlgeschlagen: ' . $e->getMessage(), $id];
        }

        if ($newId <= 0) {
            return [$notice, 'Profil konnte nicht gespeichert werden.', $id];
        }

        $rawTasks = $_POST['tasks'] ?? [];
        if (is_array($rawTasks) && !empty(array_filter($rawTasks))) {
            $tasks = array_filter(
                array_map(fn($t) => sanitize_text_field((string) $t), $rawTasks),
                fn($t) => $t !== ''
            );
            CMS_JPG_Profiles::instance()->save_tasks($newId, array_values($tasks));
        }

        $reqTexts = $_POST['req_text'] ?? [];
        $reqTypes = $_POST['req_type'] ?? [];
        if (is_array($reqTexts)) {
            $reqs = [];
            foreach ($reqTexts as $i => $txt) {
                $txt = sanitize_text_field((string) $txt);
                if ($txt !== '') {
                    $reqs[] = [
                        'text' => $txt,
                        'type' => in_array($reqTypes[$i] ?? '', ['must', 'nice', 'optional'], true)
                                  ? $reqTypes[$i] : 'must',
                    ];
                }
            }
            CMS_JPG_Profiles::instance()->save_requirements($newId, $reqs);
        }

        $rawBenefitIds = $_POST['benefit_ids'] ?? [];
        if (is_array($rawBenefitIds)) {
            $benefitIds = array_values(array_filter(array_map('intval', $rawBenefitIds)));
            CMS_JPG_Profiles::instance()->save_benefits($newId, $benefitIds);
        }

        if (function_exists('update_resource_usage')) {
            update_resource_usage('job_profiles', 1, $this->userId);
        }

        $notice = 'Profil gespeichert (Entwurf). Zur Veröffentlichung bitte Admin-Freigabe beantragen.';
        return [$notice, $error, $newId];
    }

    // ── Duplizierer ───────────────────────────────────────────────────────────

    private function duplicate_profile(int $id): int
    {
        $src = $this->load_own_profile($id);
        if (!$src) {
            return 0;
        }
        $newTitle = $src->title . ' (Kopie)';
        $data     = [
            'title'           => $newTitle,
            'slug'            => $this->generate_unique_slug($newTitle, 0),
            'job_category_id' => $src->job_category_id ?? null,
            'status'          => 'draft',
            'summary'         => $src->summary ?? '',
            'description'     => $src->description ?? '',
            'location'        => $src->location ?? '',
            'employment_type' => $src->employment_type ?? 'fulltime',
            'experience_level'=> $src->experience_level ?? 'mid',
            'salary_min'      => $src->salary_min ?? null,
            'salary_max'      => $src->salary_max ?? null,
            'remote_option'   => $src->remote_option ?? 'onsite',
            'show_in_listing' => (int)($src->show_in_listing ?? 0),
            'is_private'      => (int)($src->is_private ?? 0),
            'created_by'      => $this->userId,
            'updated_by'      => $this->userId,
        ];
        try {
            $newId = CMS_JPG_Profiles::instance()->save($data, 0);
            if ($newId <= 0) {
                return 0;
            }
            $tasks = CMS_JPG_Profiles::instance()->get_tasks($id);
            if (!empty($tasks)) {
                CMS_JPG_Profiles::instance()->save_tasks($newId,
                    array_column((array)$tasks, 'task_text'));
            }
            $reqs = CMS_JPG_Profiles::instance()->get_requirements($id);
            if (!empty($reqs)) {
                $reqArr = array_map(fn($r): array => [
                    'text' => $r->req_text ?? '',
                    'type' => $r->req_type ?? 'must',
                ], (array)$reqs);
                CMS_JPG_Profiles::instance()->save_requirements($newId, $reqArr);
            }
            $benefitIds = CMS_JPG_Profiles::instance()->get_benefit_ids($id);
            if (!empty($benefitIds)) {
                CMS_JPG_Profiles::instance()->save_benefits($newId, $benefitIds);
            }
            return $newId;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function generate_unique_slug(string $title, int $existingId): string
    {
        $base = strtolower((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
        $base = trim($base, '-');
        $base = substr($base, 0, 80);
        if ($base === '') {
            $base = 'stelle';
        }
        $slug    = $base;
        $counter = 1;
        while (true) {
            try {
                $conflict = $this->db->get_var(
                    "SELECT id FROM {$this->p}jpg_profiles WHERE slug = ? AND id != ?",
                    [$slug, $existingId]
                );
            } catch (\Throwable $e) {
                break;
            }
            if (!$conflict) {
                break;
            }
            $slug = $base . '-' . $counter;
            $counter++;
        }
        return $slug;
    }

    /**
     * @return array<string,string|int>
     */
    private function build_create_prefill(): array
    {
        $prefill = [
            'title' => '',
            'slug' => '',
            'job_category_id' => 0,
            'location' => '',
            'employment_type' => 'Vollzeit',
            'remote_option' => 'none',
            'experience_level' => 'mid',
            'salary_min' => '',
            'salary_max' => '',
            'summary' => '',
            'description' => '',
        ];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $prefill;
        }

        $prefill['title'] = sanitize_text_field((string) ($_POST['title'] ?? ''));
        $prefill['slug'] = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) ($_POST['slug'] ?? ''))) ?: '';
        $prefill['job_category_id'] = max(0, (int) ($_POST['job_category_id'] ?? 0));
        $prefill['location'] = sanitize_text_field((string) ($_POST['location'] ?? ''));

        $employmentType = (string) ($_POST['employment_type'] ?? 'Vollzeit');
        $prefill['employment_type'] = in_array($employmentType, ['Vollzeit', 'Teilzeit', 'Freelance', 'Praktikum', 'Ausbildung'], true)
            ? $employmentType
            : 'Vollzeit';

        $remoteOption = (string) ($_POST['remote_option'] ?? 'none');
        $prefill['remote_option'] = in_array($remoteOption, ['none', 'hybrid', 'full'], true)
            ? $remoteOption
            : 'none';

        $experienceLevel = (string) ($_POST['experience_level'] ?? 'mid');
        $prefill['experience_level'] = in_array($experienceLevel, ['junior', 'mid', 'senior', 'lead'], true)
            ? $experienceLevel
            : 'mid';

        $prefill['salary_min'] = preg_replace('/[^0-9]/', '', (string) ($_POST['salary_min'] ?? '')) ?: '';
        $prefill['salary_max'] = preg_replace('/[^0-9]/', '', (string) ($_POST['salary_max'] ?? '')) ?: '';
        $prefill['summary'] = sanitize_textarea_field((string) ($_POST['summary'] ?? ''));
        $prefill['description'] = sanitize_textarea_field((string) ($_POST['description'] ?? ''));

        return $prefill;
    }

    // ── Datenzugriff ─────────────────────────────────────────────────────────

    private function load_own_profile(int $id): ?object
    {
        if ($id <= 0 || $this->userId === null) {
            return null;
        }
        $isAdmin = method_exists($this->auth, 'isAdmin') ? $this->auth->isAdmin() : false;
        try {
            if ($isAdmin) {
                // Admins dürfen jedes Profil laden und bearbeiten
                return $this->db->get_row(
                    "SELECT * FROM {$this->p}jpg_profiles WHERE id = ?",
                    [$id]
                );
            }
            return $this->db->get_row(
                "SELECT * FROM {$this->p}jpg_profiles
                 WHERE id = ? AND created_by = ?",
                [$id, $this->userId]
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function render_limit_error(): void
    {
        if (function_exists('display_upgrade_notice')) {
            display_upgrade_notice('Ihr aktuelles Abo erlaubt keine weiteren Job-Profile.');
        } else {
            http_response_code(403);
            echo '<div class="alert alert-error">❌ Limit erreicht. Bitte upgraden Sie Ihr Abo.</div>';
        }
    }

    /**
     * GET /member/jobs/pdf/:id – PDF-Export für Inserenten (Phase 14.3)
     *
     * Gibt das Stellenprofil als PDF aus (mPDF falls verfügbar, sonst HTML-Fallback).
     */
    public function download_pdf(string $id): void
    {
        $this->require_auth();

        $profile = $this->load_own_profile((int)$id);
        if (!$profile) {
            http_response_code(403);
            echo 'Kein Zugriff auf dieses Profil.';
            exit;
        }

        if (!class_exists('CMS_JPG_Export')) {
            http_response_code(500);
            echo 'Export-Klasse nicht geladen.';
            exit;
        }

        $html = CMS_JPG_Export::instance()->render_html((int)$id);

        if (class_exists('\\Mpdf\\Mpdf')) {
            try {
                $mpdf = new \Mpdf\Mpdf([
                    'mode'        => 'utf-8',
                    'format'      => 'A4',
                    'margin_top'  => 15,
                    'margin_left' => 15,
                    'margin_right'=> 15,
                    'margin_bottom' => 15,
                ]);
                $mpdf->SetTitle($profile->title ?? 'Stellenprofil');
                $mpdf->WriteHTML($html);
                $filename = 'job-' . preg_replace('/[^a-z0-9\-_]/i', '-', $profile->title ?? 'profil') . '.pdf';
                $mpdf->Output($filename, 'D');
                exit;
            } catch (\Throwable $e) {
                // Fallthrough zu HTML-Ausgabe
            }
        }

        // HTML-Fallback
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        exit;
    }
}
