<?php
/**
 * Router-/Controller-Klasse für 365NET Experts & Companie.
 *
 * @package CMS_365NET_ExpertsAndCompanie
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_365NET_Experts_And_Companie_Post_Type
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        if (class_exists('CMS\\Hooks')) {
            CMS\Hooks::addAction('register_routes', [$this, 'registerRoutes'], 10);
            CMS\Hooks::addAction('main_nav', [$this, 'addMenuItem'], 10);
        }
    }

    public function registerRoutes($router): void
    {
        $router->addRoute('GET', '/experts', [$this, 'expertsPage']);
        $router->addRoute('GET', '/experts/:id', [$this, 'singleExpert']);
        $router->addRoute('GET', '/companies', [$this, 'companiesPage']);
        $router->addRoute('GET', '/companies/:id', [$this, 'singleCompany']);
        $router->addRoute('GET', '/experts-companie', [$this, 'archivePage']);
        $router->addRoute('GET', '/experts-and-companie', [$this, 'archivePage']);
        $router->addRoute('GET', '/experts-companies', [$this, 'archivePage']);

        $router->addRoute('GET', '/admin/experts-companie', [$this, 'adminOverview']);
        $router->addRoute('POST', '/admin/experts-companie/seed-refresh', [$this, 'adminSeedRefresh']);
        $router->addRoute('GET', '/admin/experts-companie/settings', [$this, 'adminSettings']);
        $router->addRoute('POST', '/admin/experts-companie/settings/save', [$this, 'adminSettingsSave']);

        $router->addRoute('GET', '/admin/experts-companie/experts', [$this, 'adminExperts']);
        $router->addRoute('GET', '/admin/experts-companie/experts/new', [$this, 'adminExpertNew']);
        $router->addRoute('GET', '/admin/experts-companie/experts/edit/:id', [$this, 'adminExpertEdit']);
        $router->addRoute('POST', '/admin/experts-companie/experts/save', [$this, 'adminExpertSave']);

        $router->addRoute('GET', '/admin/experts-companie/companies', [$this, 'adminCompanies']);
        $router->addRoute('GET', '/admin/experts-companie/companies/new', [$this, 'adminCompanyNew']);
        $router->addRoute('GET', '/admin/experts-companie/companies/edit/:id', [$this, 'adminCompanyEdit']);
        $router->addRoute('POST', '/admin/experts-companie/companies/save', [$this, 'adminCompanySave']);
    }

    public function addMenuItem(): void
    {
        $settings = CMS_365NET_Experts_And_Companie_Database::instance()->getSettings();
        if (($settings['show_nav_link'] ?? '0') !== '1') {
            return;
        }

        $label = trim((string) ($settings['nav_label'] ?? 'Experts & Companies'));
        if ($label === '') {
            $label = 'Experts & Companies';
        }

        $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $isActive = str_starts_with($path, '/experts')
            || str_starts_with($path, '/companies')
            || str_starts_with($path, '/experts-companie')
            || str_starts_with($path, '/experts-and-companie')
            || str_starts_with($path, '/experts-companies');

        echo '<a href="' . htmlspecialchars(rtrim((string) SITE_URL, '/') . '/experts', ENT_QUOTES, 'UTF-8') . '" class="nav-link ' . ($isActive ? 'active' : '') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
    }

    public function expertsPage(): void
    {
        $this->renderPublicByType('experts', 'archive-experts');
    }

    public function companiesPage(): void
    {
        $this->renderPublicByType('companies', 'archive-companies');
    }

    public function archivePage(): void
    {
        $this->renderPublicByType('all', 'archive-experts-companie');
    }

    public function singleExpert(string $id = ''): void
    {
        $expertId = $this->parsePositiveId($id);
        if ($expertId <= 0) {
            $this->renderSimpleError('Expert nicht gefunden.');
            return;
        }

        $db = CMS_365NET_Experts_And_Companie_Database::instance();
        $expert = $db->getExpertPublicById($expertId);
        if ($expert === null) {
            $this->renderSimpleError('Expert nicht gefunden.');
            return;
        }

        $linkedCompany = null;
        $linkedCompanyId = (int) ($expert->linked_company_id ?? 0);
        if ($linkedCompanyId > 0) {
            $linkedCompany = $db->getCompanyPublicById($linkedCompanyId);
        }

        $linkedSpeaker = null;
        $linkedSpeakerId = (int) ($expert->linked_speaker_id ?? 0);
        if ($linkedSpeakerId > 0) {
            $linkedSpeaker = $db->getLinkedSpeaker($linkedSpeakerId);
        }
        if ($linkedSpeaker === null) {
            $linkedSpeaker = $db->getSpeakerByLinkedExpert($expertId);
        }

        $theme = CMS\ThemeManager::instance();
        $theme->getHeader();
        CMS_365NET_Experts_And_Companie_Template_Loader::instance()->render('single-expert', [
            'expert' => $expert,
            'linkedCompany' => $linkedCompany,
            'linkedSpeaker' => $linkedSpeaker,
        ]);
        $theme->getFooter();
    }

    public function singleCompany(string $id = ''): void
    {
        $companyId = $this->parsePositiveId($id);
        if ($companyId <= 0) {
            $this->renderSimpleError('Company nicht gefunden.');
            return;
        }

        $db = CMS_365NET_Experts_And_Companie_Database::instance();
        $company = $db->getCompanyPublicById($companyId);
        if ($company === null) {
            $this->renderSimpleError('Company nicht gefunden.');
            return;
        }

        $linkedExpert = null;
        $linkedExpertId = (int) ($company->linked_expert_id ?? 0);
        if ($linkedExpertId > 0) {
            $linkedExpert = $db->getExpertPublicById($linkedExpertId);
        }

        $linkedSpeaker = null;
        $linkedSpeakerId = (int) ($company->linked_speaker_id ?? 0);
        if ($linkedSpeakerId > 0) {
            $linkedSpeaker = $db->getLinkedSpeaker($linkedSpeakerId);
        }

        $theme = CMS\ThemeManager::instance();
        $theme->getHeader();
        CMS_365NET_Experts_And_Companie_Template_Loader::instance()->render('single-company', [
            'company' => $company,
            'linkedExpert' => $linkedExpert,
            'linkedSpeaker' => $linkedSpeaker,
            'linkedExperts' => $db->getExpertsByLinkedCompany($companyId, 8),
            'linkedSpeakers' => $db->getSpeakersByLinkedCompany($companyId, 8),
        ]);
        $theme->getFooter();
    }

    private function renderPublicByType(string $routeType, string $template): void
    {
        if (!class_exists('CMS_365NET_Experts_And_Companie_Database')) {
            $this->renderSimpleError('Die Datenbankverbindung ist derzeit nicht verfügbar.');
            return;
        }

        $search = $this->cleanText((string) ($_GET['q'] ?? ''), 120);
        $city = $this->cleanText((string) ($_GET['city'] ?? ''), 80);
        $type = $routeType;
        if ($routeType === 'all') {
            $type = $this->allowValue((string) ($_GET['type'] ?? 'all'), ['all', 'experts', 'companies']) ?? 'all';
        }

        $db = CMS_365NET_Experts_And_Companie_Database::instance();

        $experts = [];
        $companies = [];

        if ($type === 'all' || $type === 'experts') {
            $experts = $db->getExpertsPublic($search, $city, 30, 0);
            foreach ($experts as $expert) {
                $expert->detail_url = rtrim((string) SITE_URL, '/') . '/experts/' . (int) ($expert->id ?? 0);

                $linkedCompanyId = (int) ($expert->linked_company_id ?? 0);
                $linkedSpeakerId = (int) ($expert->linked_speaker_id ?? 0);
                $linkedSpeaker = $linkedSpeakerId > 0 ? $db->getLinkedSpeaker($linkedSpeakerId) : null;
                if ($linkedSpeaker === null) {
                    $linkedSpeaker = $db->getSpeakerByLinkedExpert((int) ($expert->id ?? 0));
                }

                $expert->linked_company = $linkedCompanyId > 0 ? $db->getCompanyById($linkedCompanyId) : null;
                $expert->linked_speaker = $linkedSpeaker;
            }
        }

        if ($type === 'all' || $type === 'companies') {
            $companies = $db->getCompaniesPublic($search, $city, 30, 0);
            foreach ($companies as $company) {
                $company->detail_url = rtrim((string) SITE_URL, '/') . '/companies/' . (int) ($company->id ?? 0);
                $company->linked_expert = null;
                $company->linked_speaker = null;

                $linkedExpertId = (int) ($company->linked_expert_id ?? 0);
                if ($linkedExpertId > 0) {
                    $company->linked_expert = $db->getExpertById($linkedExpertId);
                }

                $linkedSpeakerId = (int) ($company->linked_speaker_id ?? 0);
                if ($linkedSpeakerId > 0) {
                    $company->linked_speaker = $db->getLinkedSpeaker($linkedSpeakerId);
                }

                $company->linked_experts = $db->getExpertsByLinkedCompany((int) ($company->id ?? 0), 3);
                $company->linked_speakers = $db->getSpeakersByLinkedCompany((int) ($company->id ?? 0), 3);
            }
        }

        $theme = CMS\ThemeManager::instance();
        $theme->getHeader();
        CMS_365NET_Experts_And_Companie_Template_Loader::instance()->render($template, [
            'experts' => $experts,
            'companies' => $companies,
            'filters' => [
                'q' => $search,
                'city' => $city,
                'type' => $type,
            ],
        ]);
        $theme->getFooter();
    }

    public function adminOverview(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        if (!class_exists('CMS_365NET_Experts_And_Companie_Database')) {
            CMS\Router::instance()->redirect('/admin?error=db');
            return;
        }

        $stats = CMS_365NET_Experts_And_Companie_Database::instance()->getOverviewStats();
        $stats['csrf_seed'] = $this->csrfToken('excomp_admin');

        CMS_365NET_Experts_And_Companie_Admin::instance()->renderOverview($stats);
    }

    public function adminSeedRefresh(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        if (!$this->verifyCsrf('excomp_admin')) {
            CMS\Router::instance()->redirect('/admin/experts-companie?error=csrf');
            return;
        }

        CMS_365NET_Experts_And_Companie_Database::instance()->ensureSchema(true);
        CMS\Router::instance()->redirect('/admin/experts-companie?seeded=1');
    }

    public function adminSettings(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $settings = CMS_365NET_Experts_And_Companie_Database::instance()->getSettings();
        $settings['csrf'] = $this->csrfToken('excomp_settings_form');

        CMS_365NET_Experts_And_Companie_Admin::instance()->renderSettingsForm($settings);
    }

    public function adminSettingsSave(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        if (!$this->verifyCsrf('excomp_settings_form')) {
            CMS\Router::instance()->redirect('/admin/experts-companie/settings?error=csrf');
            return;
        }

        CMS_365NET_Experts_And_Companie_Database::instance()->saveSettings($_POST);
        CMS\Router::instance()->redirect('/admin/experts-companie/settings?saved=1');
    }

    public function adminExperts(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $search = $this->cleanText((string) ($_GET['q'] ?? ''), 120);
        $rows = CMS_365NET_Experts_And_Companie_Database::instance()->getExpertsAdmin($search, 400);
        $csrf = $this->csrfToken('excomp_expert_form');

        CMS_365NET_Experts_And_Companie_Admin::instance()->renderExpertsOverview([
            'rows' => $rows,
            'search' => $search,
            'csrf' => $csrf,
        ]);
    }

    public function adminExpertNew(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $db = CMS_365NET_Experts_And_Companie_Database::instance();

        CMS_365NET_Experts_And_Companie_Admin::instance()->renderExpertForm([
            'mode' => 'new',
            'item' => null,
            'csrf' => $this->csrfToken('excomp_expert_form'),
            'speakers' => $db->getAvailableSpeakers(300),
            'companies' => $db->getCompaniesAdmin('', 300),
        ]);
    }

    public function adminExpertEdit(string $id = ''): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $expert = CMS_365NET_Experts_And_Companie_Database::instance()->getExpertById((int) $id);
        if (!$expert) {
            CMS\Router::instance()->redirect('/admin/experts-companie/experts?error=notfound');
            return;
        }

        $db = CMS_365NET_Experts_And_Companie_Database::instance();

        CMS_365NET_Experts_And_Companie_Admin::instance()->renderExpertForm([
            'mode' => 'edit',
            'item' => $expert,
            'csrf' => $this->csrfToken('excomp_expert_form'),
            'speakers' => $db->getAvailableSpeakers(300),
            'companies' => $db->getCompaniesAdmin('', 300),
        ]);
    }

    public function adminExpertSave(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        if (!$this->verifyCsrf('excomp_expert_form')) {
            CMS\Router::instance()->redirect('/admin/experts-companie/experts?error=csrf');
            return;
        }

        $id = max(0, (int) ($_POST['id'] ?? 0));
        $payload = [
            'id' => $id,
            'first_name' => $this->cleanText((string) ($_POST['first_name'] ?? ''), 120),
            'last_name' => $this->cleanText((string) ($_POST['last_name'] ?? ''), 120),
            'company' => $this->cleanText((string) ($_POST['company'] ?? ''), 255),
            'position' => $this->cleanText((string) ($_POST['position'] ?? ''), 255),
            'city' => $this->cleanText((string) ($_POST['city'] ?? ''), 120),
            'country' => $this->cleanText((string) ($_POST['country'] ?? ''), 120),
            'website' => $this->cleanText((string) ($_POST['website'] ?? ''), 600),
            'availability' => $this->cleanText((string) ($_POST['availability'] ?? ''), 80),
            'experience_years' => $this->cleanText((string) ($_POST['experience_years'] ?? ''), 10),
            'hourly_rate' => $this->cleanText((string) ($_POST['hourly_rate'] ?? ''), 20),
            'daily_rate' => $this->cleanText((string) ($_POST['daily_rate'] ?? ''), 20),
            'skills_general' => $this->cleanText((string) ($_POST['skills_general'] ?? ''), 2000),
            'skills_tech' => $this->cleanText((string) ($_POST['skills_tech'] ?? ''), 2000),
            'skills_soft' => $this->cleanText((string) ($_POST['skills_soft'] ?? ''), 2000),
            'awards' => $this->cleanText((string) ($_POST['awards'] ?? ''), 2000),
            'certifications' => $this->cleanText((string) ($_POST['certifications'] ?? ''), 2000),
            'biography_json' => (string) ($_POST['biography_json'] ?? ''),
            'biography' => $this->cleanText((string) ($_POST['biography'] ?? ($_POST['biography_json'] ?? '')), 18000),
            'linked_company_id' => max(0, (int) ($_POST['linked_company_id'] ?? 0)),
            'linked_speaker_id' => max(0, (int) ($_POST['linked_speaker_id'] ?? 0)),
            'status' => $this->allowValue((string) ($_POST['status'] ?? 'active'), ['active', 'inactive']) ?? 'active',
        ];

        $saved = CMS_365NET_Experts_And_Companie_Database::instance()->saveExpert($payload);
        if ($saved === false) {
            $target = $id > 0 ? '/admin/experts-companie/experts/edit/' . $id : '/admin/experts-companie/experts/new';
            CMS\Router::instance()->redirect($target . '?error=save');
            return;
        }

        CMS\Router::instance()->redirect('/admin/experts-companie/experts?saved=1');
    }

    public function adminCompanies(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $search = $this->cleanText((string) ($_GET['q'] ?? ''), 120);
        $rows = CMS_365NET_Experts_And_Companie_Database::instance()->getCompaniesAdmin($search, 400);

        CMS_365NET_Experts_And_Companie_Admin::instance()->renderCompaniesOverview([
            'rows' => $rows,
            'search' => $search,
            'csrf' => $this->csrfToken('excomp_company_form'),
        ]);
    }

    public function adminCompanyNew(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $db = CMS_365NET_Experts_And_Companie_Database::instance();

        CMS_365NET_Experts_And_Companie_Admin::instance()->renderCompanyForm([
            'mode' => 'new',
            'item' => null,
            'csrf' => $this->csrfToken('excomp_company_form'),
            'experts' => $db->getExpertsAdmin('', 300),
            'speakers' => $db->getAvailableSpeakers(300),
        ]);
    }

    public function adminCompanyEdit(string $id = ''): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $company = CMS_365NET_Experts_And_Companie_Database::instance()->getCompanyById((int) $id);
        if (!$company) {
            CMS\Router::instance()->redirect('/admin/experts-companie/companies?error=notfound');
            return;
        }

        $db = CMS_365NET_Experts_And_Companie_Database::instance();

        CMS_365NET_Experts_And_Companie_Admin::instance()->renderCompanyForm([
            'mode' => 'edit',
            'item' => $company,
            'csrf' => $this->csrfToken('excomp_company_form'),
            'experts' => $db->getExpertsAdmin('', 300),
            'speakers' => $db->getAvailableSpeakers(300),
        ]);
    }

    public function adminCompanySave(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        if (!$this->verifyCsrf('excomp_company_form')) {
            CMS\Router::instance()->redirect('/admin/experts-companie/companies?error=csrf');
            return;
        }

        $id = max(0, (int) ($_POST['id'] ?? 0));
        $payload = [
            'id' => $id,
            'name' => $this->cleanText((string) ($_POST['name'] ?? ''), 255),
            'email' => $this->cleanText((string) ($_POST['email'] ?? ''), 180),
            'phone' => $this->cleanText((string) ($_POST['phone'] ?? ''), 80),
            'industry' => $this->cleanText((string) ($_POST['industry'] ?? ''), 255),
            'company_size' => $this->cleanText((string) ($_POST['company_size'] ?? ''), 80),
            'website' => $this->cleanText((string) ($_POST['website'] ?? ''), 600),
            'city' => $this->cleanText((string) ($_POST['city'] ?? ''), 120),
            'zip' => $this->cleanText((string) ($_POST['zip'] ?? ''), 30),
            'country' => $this->cleanText((string) ($_POST['country'] ?? ''), 120),
            'founded_year' => $this->cleanText((string) ($_POST['founded_year'] ?? ''), 10),
            'employee_count' => $this->cleanText((string) ($_POST['employee_count'] ?? ''), 20),
            'linked_expert_id' => max(0, (int) ($_POST['linked_expert_id'] ?? 0)),
            'linked_speaker_id' => max(0, (int) ($_POST['linked_speaker_id'] ?? 0)),
            'description_json' => (string) ($_POST['description_json'] ?? ''),
            'description' => $this->cleanText((string) ($_POST['description'] ?? ($_POST['description_json'] ?? '')), 20000),
            'is_partner' => isset($_POST['is_partner']) ? 1 : 0,
            'is_top_partner' => isset($_POST['is_top_partner']) ? 1 : 0,
            'is_sponsor' => isset($_POST['is_sponsor']) ? 1 : 0,
            'status' => $this->allowValue((string) ($_POST['status'] ?? 'active'), ['active', 'inactive']) ?? 'active',
        ];

        $saved = CMS_365NET_Experts_And_Companie_Database::instance()->saveCompany($payload);
        if ($saved === false) {
            $target = $id > 0 ? '/admin/experts-companie/companies/edit/' . $id : '/admin/experts-companie/companies/new';
            CMS\Router::instance()->redirect($target . '?error=save');
            return;
        }

        CMS\Router::instance()->redirect('/admin/experts-companie/companies?saved=1');
    }

    private function requireAdmin(): bool
    {
        if (!class_exists('CMS\\Auth') || !CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return false;
        }

        return true;
    }

    private function csrfToken(string $action): string
    {
        if (!class_exists('CMS\\Security')) {
            return '';
        }

        return CMS\Security::instance()->generateToken($action);
    }

    private function verifyCsrf(string $action): bool
    {
        if (!class_exists('CMS\\Security')) {
            return false;
        }

        return CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), $action);
    }

    private function cleanText(string $value, int $maxLen = 120): string
    {
        $value = trim(strip_tags($value));
        return function_exists('mb_substr') ? (string) mb_substr($value, 0, $maxLen, 'UTF-8') : substr($value, 0, $maxLen);
    }

    private function allowValue(string $value, array $allowed): ?string
    {
        $value = trim($value);
        return in_array($value, $allowed, true) ? $value : null;
    }

    private function parsePositiveId(string $value): int
    {
        $value = trim($value);
        if ($value === '' || preg_match('/^\d+$/', $value) !== 1) {
            return 0;
        }

        return max(0, (int) $value);
    }

    private function renderSimpleError(string $message): void
    {
        $theme = CMS\ThemeManager::instance();
        $theme->getHeader();
        echo '<main class="cms-excomp-public"><div class="cms-excomp-container"><div class="cms-excomp-empty"><h2>Hinweis</h2><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></div></div></main>';
        $theme->getFooter();
    }
}
