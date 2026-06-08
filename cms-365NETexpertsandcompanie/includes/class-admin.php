<?php
/**
 * Admin-UI für 365NET Experts & Companie.
 *
 * @package CMS_365NET_ExpertsAndCompanie
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_365NET_Experts_And_Companie_Admin
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
        $this->loadSharedAdminContract();

        if (class_exists('CMS\\Hooks')) {
            CMS\Hooks::addAction('cms_admin_menu', [$this, 'registerAdminMenu'], 10);
            CMS\Hooks::addFilter('admin_menu_items', [$this, 'addMenuItem'], 10);
        }
    }

    private function loadSharedAdminContract(): void
    {
        $contractFile = dirname(__DIR__, 2) . '/shared/admin/plugin-admin-contract.php';
        if (is_file($contractFile)) {
            require_once $contractFile;
        }

        $menuFile = ABSPATH . 'admin/partials/admin-menu.php';
        if (is_file($menuFile) && !function_exists('renderAdminLayoutStart')) {
            require_once $menuFile;
        }
    }

    public function registerAdminMenu(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            '365 | Experts & Companie',
            '365 | Experts & Companie',
            'manage_options',
            'experts-companie',
            [self::class, 'bridgeOverview'],
            '🤝',
            46
        );

        if (!function_exists('add_submenu_page')) {
            return;
        }

        add_submenu_page('experts-companie', 'Übersicht', 'Übersicht', 'manage_options', 'experts-companie', [self::class, 'bridgeOverview']);
        add_submenu_page('experts-companie', 'Public Hub', 'Public Hub', 'manage_options', 'experts-companie-public', [self::class, 'bridgePublic']);
        add_submenu_page('experts-companie', 'Experts verwalten', 'Experts', 'manage_options', 'experts-companie-experts', [self::class, 'bridgeExperts']);
        add_submenu_page('experts-companie', 'Companies verwalten', 'Companies', 'manage_options', 'experts-companie-companies', [self::class, 'bridgeCompanies']);
        add_submenu_page('experts-companie', 'Einstellungen', 'Einstellungen', 'manage_options', 'experts-companie-settings', [self::class, 'bridgeSettings']);
        add_submenu_page('experts-companie', 'Expert anlegen', '+ Expert', 'manage_options', 'experts-companie-expert-new', [self::class, 'bridgeExpertNew']);
        add_submenu_page('experts-companie', 'Company anlegen', '+ Company', 'manage_options', 'experts-companie-company-new', [self::class, 'bridgeCompanyNew']);
    }

    public static function bridgeOverview(): void
    {
        CMS\Router::instance()->redirect('/admin/experts-companie');
    }

    public static function bridgePublic(): void
    {
        CMS\Router::instance()->redirect('/experts');
    }

    public static function bridgeExperts(): void
    {
        CMS\Router::instance()->redirect('/admin/experts-companie/experts');
    }

    public static function bridgeCompanies(): void
    {
        CMS\Router::instance()->redirect('/admin/experts-companie/companies');
    }

    public static function bridgeSettings(): void
    {
        CMS\Router::instance()->redirect('/admin/experts-companie/settings');
    }

    public static function bridgeExpertNew(): void
    {
        CMS\Router::instance()->redirect('/admin/experts-companie/experts/new');
    }

    public static function bridgeCompanyNew(): void
    {
        CMS\Router::instance()->redirect('/admin/experts-companie/companies/new');
    }

    public function addMenuItem(array $items): array
    {
        $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $items[] = [
            'type' => 'item',
            'slug' => 'experts-companie',
            'label' => '365 | Experts & Companie',
            'icon' => '🤝',
            'url' => '/admin/experts-companie',
            'active' => str_starts_with($path, '/admin/experts-companie'),
        ];

        return $items;
    }

    public function renderOverview(array $data): void
    {
        $expertsSeedCount = max(0, (int) ($data['experts_seed_count'] ?? 0));
        $companiesSeedCount = max(0, (int) ($data['companies_seed_count'] ?? 0));
        $csrfSeed = trim((string) ($data['csrf_seed'] ?? ''));

        $this->start('365 | Experts & Companie', 'experts-companie');
        ?>
        <div class="cms-excomp-admin-header">
            <div>
                <h2>🤝 365 | Experts & Companie</h2>
                <p>Vollständig eigenständige Verwaltung für Experts und Companies – ohne Datenzugriff auf alte Einzel-Plugins.</p>
            </div>
            <div class="cms-excomp-admin-actions">
                <a class="btn btn-primary" href="<?= htmlspecialchars(rtrim((string) SITE_URL, '/') . '/admin/experts-companie/experts', ENT_QUOTES, 'UTF-8') ?>">Experts verwalten</a>
                <a class="btn btn-secondary" href="<?= htmlspecialchars(rtrim((string) SITE_URL, '/') . '/admin/experts-companie/companies', ENT_QUOTES, 'UTF-8') ?>">Companies verwalten</a>
                <a class="btn btn-secondary" href="<?= htmlspecialchars(rtrim((string) SITE_URL, '/') . '/admin/experts-companie/settings', ENT_QUOTES, 'UTF-8') ?>">Einstellungen</a>
                <a class="btn btn-secondary" href="<?= htmlspecialchars(rtrim((string) SITE_URL, '/') . '/experts', ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Public Experts</a>
                <a class="btn btn-secondary" href="<?= htmlspecialchars(rtrim((string) SITE_URL, '/') . '/companies', ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Public Companies</a>
            </div>
        </div>

        <div class="cms-excomp-admin-grid">
            <article class="admin-card cms-excomp-admin-card cms-excomp-admin-card--experts">
                <h3>Experts-Daten</h3>
                <p class="cms-excomp-state is-active">Standalone aktiv</p>
                <ul>
                    <li><strong>Seed-Datensätze (Plugin):</strong> <?= (int) $expertsSeedCount ?></li>
                    <li><strong>Quelle:</strong> <code>defaults/experts.csv</code></li>
                </ul>
            </article>

            <article class="admin-card cms-excomp-admin-card cms-excomp-admin-card--companies">
                <h3>Companies-Daten</h3>
                <p class="cms-excomp-state is-active">Standalone aktiv</p>
                <ul>
                    <li><strong>Seed-Datensätze (Plugin):</strong> <?= (int) $companiesSeedCount ?></li>
                    <li><strong>Quelle:</strong> <code>defaults/companies.csv</code></li>
                </ul>
            </article>
        </div>

        <div class="admin-card cms-excomp-admin-note cms-excomp-admin-note--actions">
            <h3>Seed-Daten aktualisieren</h3>
            <p>Die Seed-Daten sind fest im Plugin eingebettet. Mit diesem Button werden sie neu in die plugin-eigenen Tabellen geschrieben.</p>
            <form method="POST" action="<?= htmlspecialchars(rtrim((string) SITE_URL, '/') . '/admin/experts-companie/seed-refresh', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfSeed, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-secondary">🔄 Seed neu einspielen</button>
            </form>
        </div>
        <?php
        $this->end();
    }

    /** @param array<string, string> $settings */
    public function renderSettingsForm(array $settings): void
    {
        $csrf = trim((string) ($settings['csrf'] ?? ''));
        $showNav = ($settings['show_nav_link'] ?? '0') === '1';
        $navLabel = trim((string) ($settings['nav_label'] ?? 'Experts & Companies'));

        $expertsArchiveKicker = trim((string) ($settings['experts_archive_kicker'] ?? '365 Network · Expert Directory'));
        $expertsArchiveTitle = trim((string) ($settings['experts_archive_title'] ?? 'Experts'));
        $expertsArchiveDescription = trim((string) ($settings['experts_archive_description'] ?? ''));
        $expertsSearchPlaceholder = trim((string) ($settings['experts_search_placeholder'] ?? 'Name, Firma, Position, Skills …'));

        $companiesArchiveKicker = trim((string) ($settings['companies_archive_kicker'] ?? '365 Network · Company Directory'));
        $companiesArchiveTitle = trim((string) ($settings['companies_archive_title'] ?? 'Companies'));
        $companiesArchiveDescription = trim((string) ($settings['companies_archive_description'] ?? ''));
        $companiesSearchPlaceholder = trim((string) ($settings['companies_search_placeholder'] ?? 'Name, Branche, Beschreibung …'));

        $searchButtonLabel = trim((string) ($settings['search_button_label'] ?? 'Suchen'));
        $resetButtonLabel = trim((string) ($settings['reset_button_label'] ?? 'Zurücksetzen'));
        $websiteButtonLabel = trim((string) ($settings['website_button_label'] ?? 'Website'));

        $layoutContentMaxWidth = trim((string) ($settings['layout_content_max_width'] ?? '1160px'));
        $layoutPagePaddingTop = trim((string) ($settings['layout_page_padding_top'] ?? '24px'));
        $layoutPagePaddingBottom = trim((string) ($settings['layout_page_padding_bottom'] ?? '40px'));
        $layoutGridGap = trim((string) ($settings['layout_grid_gap'] ?? '18px'));
        $layoutSectionGap = trim((string) ($settings['layout_section_gap'] ?? '20px'));

        $styleRadiusCard = trim((string) ($settings['style_radius_card'] ?? '2px'));
        $styleRadiusButton = trim((string) ($settings['style_radius_button'] ?? '2px'));
        $styleRadiusSurface = trim((string) ($settings['style_radius_surface'] ?? '4px'));
        $styleRadiusHero = trim((string) ($settings['style_radius_hero'] ?? '4px'));

        $colorBg = trim((string) ($settings['color_bg'] ?? '#f8fafc'));
        $colorText = trim((string) ($settings['color_text'] ?? '#0f172a'));
        $colorPrimary = trim((string) ($settings['color_primary'] ?? '#1d4ed8'));
        $colorHeroStart = trim((string) ($settings['color_hero_start'] ?? '#172554'));
        $colorHeroEnd = trim((string) ($settings['color_hero_end'] ?? '#1e40af'));
        $colorExpertAccent = trim((string) ($settings['color_expert_accent'] ?? '#f97316'));
        $colorCompanyAccent = trim((string) ($settings['color_company_accent'] ?? '#16a34a'));
        $colorCardBg = trim((string) ($settings['color_card_bg'] ?? '#ffffff'));
        $colorBorder = trim((string) ($settings['color_border'] ?? '#e2e8f0'));

        $this->start('Experts & Companie Einstellungen', 'experts-companie');
        ?>
        <div class="cms-excomp-admin-header">
            <div>
                <h2>⚙️ Einstellungen</h2>
                <p>Steuere Navigation, Texte, Layout-Abstände, Rundungen und Farben für die Publicseiten zentral im Adminbereich.</p>
            </div>
            <div class="cms-excomp-admin-actions">
                <a class="btn btn-secondary" href="<?= htmlspecialchars(rtrim((string) SITE_URL, '/') . '/admin/experts-companie', ENT_QUOTES, 'UTF-8') ?>">Zur Übersicht</a>
            </div>
        </div>

        <?php if (isset($_GET['saved'])): ?>
            <div class="admin-card cms-excomp-admin-note"><p>✅ Einstellungen wurden gespeichert.</p></div>
        <?php elseif ((string) ($_GET['error'] ?? '') === 'csrf'): ?>
            <div class="admin-card cms-excomp-admin-note"><p>❌ Sicherheitsprüfung fehlgeschlagen. Bitte erneut versuchen.</p></div>
        <?php endif; ?>

        <form method="POST" action="<?= htmlspecialchars(rtrim((string) SITE_URL, '/') . '/admin/experts-companie/settings/save', ENT_QUOTES, 'UTF-8') ?>" class="admin-card cms-excomp-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

            <h3>Navigation</h3>
            <div class="cms-excomp-form-checks">
                <?php $this->checkField('show_nav_link', 'Im Hauptmenü anzeigen', $showNav); ?>
            </div>

            <div class="cms-excomp-form-grid">
                <?php $this->textField('nav_label', 'Navigationslabel', $navLabel); ?>
            </div>

            <h3>Texte – Experts Archiv</h3>
            <div class="cms-excomp-form-grid">
                <?php $this->textField('experts_archive_kicker', 'Experts Kicker', $expertsArchiveKicker); ?>
                <?php $this->textField('experts_archive_title', 'Experts Titel', $expertsArchiveTitle); ?>
                <?php $this->textField('experts_search_placeholder', 'Experts Such-Placeholder', $expertsSearchPlaceholder); ?>
            </div>
            <?php $this->textAreaField('experts_archive_description', 'Experts Beschreibung', $expertsArchiveDescription, 2); ?>

            <h3>Texte – Companies Archiv</h3>
            <div class="cms-excomp-form-grid">
                <?php $this->textField('companies_archive_kicker', 'Companies Kicker', $companiesArchiveKicker); ?>
                <?php $this->textField('companies_archive_title', 'Companies Titel', $companiesArchiveTitle); ?>
                <?php $this->textField('companies_search_placeholder', 'Companies Such-Placeholder', $companiesSearchPlaceholder); ?>
            </div>
            <?php $this->textAreaField('companies_archive_description', 'Companies Beschreibung', $companiesArchiveDescription, 2); ?>

            <h3>Texte – Buttons</h3>
            <div class="cms-excomp-form-grid">
                <?php $this->textField('search_button_label', 'Such-Button', $searchButtonLabel); ?>
                <?php $this->textField('reset_button_label', 'Reset-Button', $resetButtonLabel); ?>
                <?php $this->textField('website_button_label', 'Website-Button (Single)', $websiteButtonLabel); ?>
            </div>

            <h3>Layout & Abstände</h3>
            <div class="cms-excomp-form-grid">
                <?php $this->textField('layout_content_max_width', 'Content-Breite (z. B. 1160px)', $layoutContentMaxWidth); ?>
                <?php $this->textField('layout_page_padding_top', 'Abstand Header → Content', $layoutPagePaddingTop); ?>
                <?php $this->textField('layout_page_padding_bottom', 'Abstand Content → Footer', $layoutPagePaddingBottom); ?>
                <?php $this->textField('layout_grid_gap', 'Grid-Abstand', $layoutGridGap); ?>
                <?php $this->textField('layout_section_gap', 'Abschnitt-Abstand', $layoutSectionGap); ?>
            </div>

            <h3>Rundungen (2px / max 4px empfohlen)</h3>
            <div class="cms-excomp-form-grid">
                <?php $this->textField('style_radius_card', 'Cards', $styleRadiusCard); ?>
                <?php $this->textField('style_radius_button', 'Buttons', $styleRadiusButton); ?>
                <?php $this->textField('style_radius_surface', 'Container/Flächen', $styleRadiusSurface); ?>
                <?php $this->textField('style_radius_hero', 'Hero', $styleRadiusHero); ?>
            </div>

            <h3>Farben</h3>
            <div class="cms-excomp-form-grid">
                <?php $this->textField('color_bg', 'Hintergrund', $colorBg); ?>
                <?php $this->textField('color_text', 'Text', $colorText); ?>
                <?php $this->textField('color_primary', 'Primärfarbe', $colorPrimary); ?>
                <?php $this->textField('color_hero_start', 'Hero Start', $colorHeroStart); ?>
                <?php $this->textField('color_hero_end', 'Hero Ende', $colorHeroEnd); ?>
                <?php $this->textField('color_expert_accent', 'Expert Akzent', $colorExpertAccent); ?>
                <?php $this->textField('color_company_accent', 'Company Akzent', $colorCompanyAccent); ?>
                <?php $this->textField('color_card_bg', 'Card Hintergrund', $colorCardBg); ?>
                <?php $this->textField('color_border', 'Border', $colorBorder); ?>
            </div>

            <div class="cms-excomp-form-actions">
                <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
            </div>
        </form>
        <?php
        $this->end();
    }

    public function renderExpertsOverview(array $data): void
    {
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $search = trim((string) ($data['search'] ?? ''));
        $sort = trim((string) ($data['sort'] ?? 'az'));
        if (!in_array($sort, ['az', 'za', 'date_old_new', 'date_new_old'], true)) {
            $sort = 'az';
        }
        $sortOptions = [
            'az' => 'A–Z',
            'za' => 'Z–A',
            'date_old_new' => 'Datum alt→neu',
            'date_new_old' => 'Datum neu→alt',
        ];
        $baseUrl = rtrim((string) SITE_URL, '/') . '/admin/experts-companie/experts';
        $buildSortUrl = static function (string $targetSort) use ($baseUrl, $search): string {
            $query = ['sort' => $targetSort];
            if ($search !== '') {
                $query['q'] = $search;
            }

            return $baseUrl . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        };

        $this->start('Experts verwalten', 'experts-companie');
        ?>
        <div class="cms-excomp-admin-header">
            <div>
                <h2>🧡 Experts verwalten</h2>
                <p>Bearbeite Expert:innen direkt im kombinierten Plugin (inklusive Langbeschreibung mit mindestens 250 Wörtern).</p>
            </div>
            <div class="cms-excomp-admin-actions">
                <a class="btn btn-primary" href="<?= htmlspecialchars(rtrim((string) SITE_URL, '/') . '/admin/experts-companie/experts/new', ENT_QUOTES, 'UTF-8') ?>">+ Neuer Expert</a>
                <a class="btn btn-secondary" href="<?= htmlspecialchars(rtrim((string) SITE_URL, '/') . '/admin/experts-companie', ENT_QUOTES, 'UTF-8') ?>">Zur Übersicht</a>
            </div>
        </div>

        <form class="cms-excomp-admin-search" method="GET" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort, ENT_QUOTES, 'UTF-8') ?>">
            <input type="search" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Name, Firma, Skills ...">
            <button type="submit" class="btn btn-secondary">Suchen</button>
            <?php if ($search !== ''): ?><a class="btn btn-secondary" href="<?= htmlspecialchars($buildSortUrl($sort), ENT_QUOTES, 'UTF-8') ?>">Reset</a><?php endif; ?>
        </form>

        <div class="cms-excomp-admin-actions cms-excomp-admin-sort">
            <?php foreach ($sortOptions as $sortKey => $sortLabel): ?>
                <a class="btn <?= $sortKey === $sort ? 'btn-primary' : 'btn-secondary' ?>" href="<?= htmlspecialchars($buildSortUrl($sortKey), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($sortLabel, ENT_QUOTES, 'UTF-8') ?></a>
            <?php endforeach; ?>
        </div>

        <div class="admin-card cms-excomp-admin-table-wrap">
            <table class="cms-excomp-admin-table">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Position / Firma</th>
                    <th>Standort</th>
                    <th>Status</th>
                    <th>Aktion</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="5">Keine Experts gefunden.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php $name = trim((string) ($row->first_name ?? '') . ' ' . (string) ($row->last_name ?? '')); ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($name !== '' ? $name : ('#' . (int) ($row->id ?? 0)), ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td>
                                <div><?= htmlspecialchars((string) ($row->position ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <small><?= htmlspecialchars((string) ($row->company ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td><?= htmlspecialchars((string) ($row->location_city ?? $row->city ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="cms-excomp-state <?= ((string) ($row->status ?? 'active')) === 'active' ? 'is-active' : 'is-inactive' ?>"><?= htmlspecialchars((string) ($row->status ?? 'active'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><a class="btn btn-secondary btn-sm" href="<?= htmlspecialchars(rtrim((string) SITE_URL, '/') . '/admin/experts-companie/experts/edit/' . (int) ($row->id ?? 0), ENT_QUOTES, 'UTF-8') ?>">Bearbeiten</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        $this->end();
    }

    public function renderCompaniesOverview(array $data): void
    {
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $search = trim((string) ($data['search'] ?? ''));
        $sort = trim((string) ($data['sort'] ?? 'az'));
        if (!in_array($sort, ['az', 'za', 'date_old_new', 'date_new_old'], true)) {
            $sort = 'az';
        }
        $sortOptions = [
            'az' => 'A–Z',
            'za' => 'Z–A',
            'date_old_new' => 'Datum alt→neu',
            'date_new_old' => 'Datum neu→alt',
        ];
        $baseUrl = rtrim((string) SITE_URL, '/') . '/admin/experts-companie/companies';
        $buildSortUrl = static function (string $targetSort) use ($baseUrl, $search): string {
            $query = ['sort' => $targetSort];
            if ($search !== '') {
                $query['q'] = $search;
            }

            return $baseUrl . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        };

        $this->start('Companies verwalten', 'experts-companie');
        ?>
        <div class="cms-excomp-admin-header">
            <div>
                <h2>💚 Companies verwalten</h2>
                <p>Bearbeite Unternehmensdaten inklusive Partner-Level und Langbeschreibung direkt im Plugin.</p>
            </div>
            <div class="cms-excomp-admin-actions">
                <a class="btn btn-primary" href="<?= htmlspecialchars(rtrim((string) SITE_URL, '/') . '/admin/experts-companie/companies/new', ENT_QUOTES, 'UTF-8') ?>">+ Neue Company</a>
                <a class="btn btn-secondary" href="<?= htmlspecialchars(rtrim((string) SITE_URL, '/') . '/admin/experts-companie', ENT_QUOTES, 'UTF-8') ?>">Zur Übersicht</a>
            </div>
        </div>

        <form class="cms-excomp-admin-search" method="GET" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort, ENT_QUOTES, 'UTF-8') ?>">
            <input type="search" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Name, Branche ...">
            <button type="submit" class="btn btn-secondary">Suchen</button>
            <?php if ($search !== ''): ?><a class="btn btn-secondary" href="<?= htmlspecialchars($buildSortUrl($sort), ENT_QUOTES, 'UTF-8') ?>">Reset</a><?php endif; ?>
        </form>

        <div class="cms-excomp-admin-actions cms-excomp-admin-sort">
            <?php foreach ($sortOptions as $sortKey => $sortLabel): ?>
                <a class="btn <?= $sortKey === $sort ? 'btn-primary' : 'btn-secondary' ?>" href="<?= htmlspecialchars($buildSortUrl($sortKey), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($sortLabel, ENT_QUOTES, 'UTF-8') ?></a>
            <?php endforeach; ?>
        </div>

        <div class="admin-card cms-excomp-admin-table-wrap">
            <table class="cms-excomp-admin-table">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Branche</th>
                    <th>Standort</th>
                    <th>Partner</th>
                    <th>Aktion</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="5">Keine Companies gefunden.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $partner = ((int) ($row->is_sponsor ?? 0) === 1) ? 'Sponsor' : (((int) ($row->is_top_partner ?? 0) === 1) ? 'Top-Partner' : (((int) ($row->is_partner ?? 0) === 1) ? 'Partner' : '—'));
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars((string) ($row->name ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= htmlspecialchars((string) ($row->industry ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($row->location_city ?? $row->city ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($partner, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><a class="btn btn-secondary btn-sm" href="<?= htmlspecialchars(rtrim((string) SITE_URL, '/') . '/admin/experts-companie/companies/edit/' . (int) ($row->id ?? 0), ENT_QUOTES, 'UTF-8') ?>">Bearbeiten</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        $this->end();
    }

    public function renderExpertForm(array $data): void
    {
        $mode = (string) ($data['mode'] ?? 'new');
        $item = is_object($data['item'] ?? null) ? $data['item'] : null;
        $csrf = trim((string) ($data['csrf'] ?? ''));
        $speakers = is_array($data['speakers'] ?? null) ? $data['speakers'] : [];
        $companies = is_array($data['companies'] ?? null) ? $data['companies'] : [];
        $isEdit = $mode === 'edit' && $item !== null;

        $selectedLinkedCompanyId = (int) ($item->linked_company_id ?? 0);
        $selectedLinkedSpeakerId = (int) ($item->linked_speaker_id ?? 0);

        $this->start($isEdit ? 'Expert bearbeiten' : 'Expert anlegen', 'experts-companie');
        ?>
        <div class="cms-excomp-admin-header">
            <div>
                <h2><?= $isEdit ? '✏️ Expert bearbeiten' : '➕ Expert anlegen' ?></h2>
                <p>Wenn die Bio zu kurz ist, ergänzt das Plugin beim Speichern automatisch auf mindestens 250 Wörter.</p>
            </div>
            <div class="cms-excomp-admin-actions">
                <a class="btn btn-secondary" href="<?= htmlspecialchars(rtrim((string) SITE_URL, '/') . '/admin/experts-companie/experts', ENT_QUOTES, 'UTF-8') ?>">Zur Experts-Liste</a>
            </div>
        </div>

        <form method="POST" action="<?= htmlspecialchars(rtrim((string) SITE_URL, '/') . '/admin/experts-companie/experts/save', ENT_QUOTES, 'UTF-8') ?>" class="admin-card cms-excomp-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" value="<?= (int) ($item->id ?? 0) ?>">

            <div class="cms-excomp-form-grid">
                <?php $this->textField('first_name', 'Vorname', (string) ($item->first_name ?? '')); ?>
                <?php $this->textField('last_name', 'Nachname', (string) ($item->last_name ?? '')); ?>
                <?php $this->textField('position', 'Position', (string) ($item->position ?? '')); ?>
                <?php $this->textField('company', 'Unternehmen', (string) ($item->company ?? '')); ?>
                <?php $this->textField('city', 'Stadt', (string) ($item->location_city ?? $item->city ?? '')); ?>
                <?php $this->textField('country', 'Land', (string) ($item->country ?? '')); ?>
                <?php $this->textField('website', 'Website', (string) ($item->website ?? '')); ?>
                <?php $this->textField('availability', 'Verfügbarkeit', (string) ($item->availability ?? 'available')); ?>
                <?php $this->textField('experience_years', 'Erfahrung (Jahre)', (string) ($item->experience_years ?? '')); ?>
                <?php $this->textField('hourly_rate', 'Stundensatz', (string) ($item->hourly_rate ?? '')); ?>
                <?php $this->textField('daily_rate', 'Tagessatz', (string) ($item->daily_rate ?? '')); ?>
                <?php $this->textField('status', 'Status (active/inactive)', (string) ($item->status ?? 'active')); ?>
            </div>

            <div class="cms-excomp-form-grid">
                <label class="cms-excomp-form-field">
                    <span>Verknüpfte Company (Verzeichnis)</span>
                    <select name="linked_company_id">
                        <option value="">— keine —</option>
                        <?php foreach ($companies as $company): ?>
                            <?php
                            if (!is_object($company)) {
                                continue;
                            }
                            $companyId = (int) ($company->id ?? 0);
                            if ($companyId <= 0) {
                                continue;
                            }
                            $companyName = trim((string) ($company->name ?? ''));
                            if ($companyName === '') {
                                $companyName = 'Company #' . $companyId;
                            }
                            ?>
                            <option value="<?= $companyId ?>"<?= $selectedLinkedCompanyId === $companyId ? ' selected' : '' ?>><?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="cms-excomp-form-field">
                    <span>Verknüpfter Speaker</span>
                    <select name="linked_speaker_id">
                        <option value="">— keine —</option>
                        <?php foreach ($speakers as $speaker): ?>
                            <?php
                            if (!is_object($speaker)) {
                                continue;
                            }
                            $speakerId = (int) ($speaker->id ?? 0);
                            if ($speakerId <= 0) {
                                continue;
                            }
                            $speakerName = trim((string) ($speaker->display_name ?? ''));
                            if ($speakerName === '') {
                                $speakerName = 'Speaker #' . $speakerId;
                            }
                            $speakerTopic = trim((string) ($speaker->topic ?? ''));
                            $speakerLabel = $speakerTopic !== '' ? ($speakerName . ' — ' . $speakerTopic) : $speakerName;
                            ?>
                            <option value="<?= $speakerId ?>"<?= $selectedLinkedSpeakerId === $speakerId ? ' selected' : '' ?>><?= htmlspecialchars($speakerLabel, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <?php $this->textAreaField('skills_general', 'Skills (Allgemein)', (string) ($item->skills_general ?? ''), 3); ?>
            <?php $this->textAreaField('skills_tech', 'Skills (Technik)', (string) ($item->skills_tech ?? ''), 3); ?>
            <?php $this->textAreaField('skills_soft', 'Skills (Soft)', (string) ($item->skills_soft ?? ''), 3); ?>
            <?php $this->textAreaField('awards', 'Awards', (string) ($item->awards ?? ''), 2); ?>
            <?php $this->textAreaField('certifications', 'Zertifikate', (string) ($item->certifications ?? ''), 2); ?>
            <?= $this->editor('biography_json', (string) ($item->biography_json ?? ''), (string) ($item->biography ?? ''), 'Biografie') ?>

            <div class="cms-excomp-form-actions">
                <button type="submit" class="btn btn-primary">💾 Speichern</button>
            </div>
        </form>
        <?php
        $this->end();
    }

    public function renderCompanyForm(array $data): void
    {
        $mode = (string) ($data['mode'] ?? 'new');
        $item = is_object($data['item'] ?? null) ? $data['item'] : null;
        $csrf = trim((string) ($data['csrf'] ?? ''));
        $experts = is_array($data['experts'] ?? null) ? $data['experts'] : [];
        $speakers = is_array($data['speakers'] ?? null) ? $data['speakers'] : [];
        $isEdit = $mode === 'edit' && $item !== null;
        $selectedLinkedExpertId = (int) ($item->linked_expert_id ?? 0);
        $selectedLinkedSpeakerId = (int) ($item->linked_speaker_id ?? 0);

        $this->start($isEdit ? 'Company bearbeiten' : 'Company anlegen', 'experts-companie');
        ?>
        <div class="cms-excomp-admin-header">
            <div>
                <h2><?= $isEdit ? '✏️ Company bearbeiten' : '➕ Company anlegen' ?></h2>
                <p>Auch hier wird eine zu kurze Beschreibung automatisch auf eine ausführliche Fassung erweitert.</p>
            </div>
            <div class="cms-excomp-admin-actions">
                <a class="btn btn-secondary" href="<?= htmlspecialchars(rtrim((string) SITE_URL, '/') . '/admin/experts-companie/companies', ENT_QUOTES, 'UTF-8') ?>">Zur Companies-Liste</a>
            </div>
        </div>

        <form method="POST" action="<?= htmlspecialchars(rtrim((string) SITE_URL, '/') . '/admin/experts-companie/companies/save', ENT_QUOTES, 'UTF-8') ?>" class="admin-card cms-excomp-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" value="<?= (int) ($item->id ?? 0) ?>">

            <div class="cms-excomp-form-grid">
                <?php $this->textField('name', 'Name', (string) ($item->name ?? '')); ?>
                <?php $this->textField('industry', 'Branche', (string) ($item->industry ?? '')); ?>
                <?php $this->textField('company_size', 'Firmengröße', (string) ($item->company_size ?? '')); ?>
                <?php $this->textField('employee_count', 'Mitarbeiter', (string) ($item->employee_count ?? '')); ?>
                <?php $this->textField('founded_year', 'Gründungsjahr', (string) ($item->founded_year ?? '')); ?>
                <?php $this->textField('website', 'Website', (string) ($item->website ?? '')); ?>
                <?php $this->textField('email', 'E-Mail', (string) ($item->email ?? '')); ?>
                <?php $this->textField('phone', 'Telefon', (string) ($item->phone ?? '')); ?>
                <?php $this->textField('city', 'Stadt', (string) ($item->location_city ?? $item->city ?? '')); ?>
                <?php $this->textField('zip', 'PLZ', (string) ($item->zip ?? '')); ?>
                <?php $this->textField('country', 'Land', (string) ($item->country ?? '')); ?>
                <?php $this->textField('status', 'Status (active/inactive)', (string) ($item->status ?? 'active')); ?>
            </div>

            <div class="cms-excomp-form-grid">
                <label class="cms-excomp-form-field">
                    <span>Verknüpfter Expert</span>
                    <select name="linked_expert_id">
                        <option value="">— keine —</option>
                        <?php foreach ($experts as $expert): ?>
                            <?php
                            if (!is_object($expert)) {
                                continue;
                            }
                            $expertId = (int) ($expert->id ?? 0);
                            if ($expertId <= 0) {
                                continue;
                            }
                            $expertName = trim((string) (($expert->first_name ?? '') . ' ' . ($expert->last_name ?? '')));
                            if ($expertName === '') {
                                $expertName = 'Expert #' . $expertId;
                            }
                            $expertCompany = trim((string) ($expert->company ?? ''));
                            $expertLabel = $expertCompany !== '' ? ($expertName . ' — ' . $expertCompany) : $expertName;
                            ?>
                            <option value="<?= $expertId ?>"<?= $selectedLinkedExpertId === $expertId ? ' selected' : '' ?>><?= htmlspecialchars($expertLabel, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="cms-excomp-form-field">
                    <span>Verknüpfter Speaker</span>
                    <select name="linked_speaker_id">
                        <option value="">— keine —</option>
                        <?php foreach ($speakers as $speaker): ?>
                            <?php
                            if (!is_object($speaker)) {
                                continue;
                            }
                            $speakerId = (int) ($speaker->id ?? 0);
                            if ($speakerId <= 0) {
                                continue;
                            }
                            $speakerName = trim((string) ($speaker->display_name ?? ''));
                            if ($speakerName === '') {
                                $speakerName = 'Speaker #' . $speakerId;
                            }
                            $speakerTopic = trim((string) ($speaker->topic ?? ''));
                            $speakerLabel = $speakerTopic !== '' ? ($speakerName . ' — ' . $speakerTopic) : $speakerName;
                            ?>
                            <option value="<?= $speakerId ?>"<?= $selectedLinkedSpeakerId === $speakerId ? ' selected' : '' ?>><?= htmlspecialchars($speakerLabel, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <div class="cms-excomp-form-checks">
                <?php $this->checkField('is_partner', 'Partner', (int) ($item->is_partner ?? 0) === 1); ?>
                <?php $this->checkField('is_top_partner', 'Top-Partner', (int) ($item->is_top_partner ?? 0) === 1); ?>
                <?php $this->checkField('is_sponsor', 'Sponsor', (int) ($item->is_sponsor ?? 0) === 1); ?>
            </div>

            <?= $this->editor('description_json', (string) ($item->description_json ?? ''), (string) ($item->description ?? ''), 'Beschreibung') ?>

            <div class="cms-excomp-form-actions">
                <button type="submit" class="btn btn-primary">💾 Speichern</button>
            </div>
        </form>
        <?php
        $this->end();
    }

    private function textField(string $name, string $label, string $value): void
    {
        echo '<label class="cms-excomp-form-field"><span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span><input type="text" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"></label>';
    }

    private function textAreaField(string $name, string $label, string $value, int $rows = 4): void
    {
        echo '<label class="cms-excomp-form-field"><span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span><textarea name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" rows="' . max(2, $rows) . '">' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</textarea></label>';
    }

    private function editor(string $name, string $jsonValue, string $fallbackText, string $label): string
    {
        $jsonValue = trim($jsonValue);
        if ($jsonValue !== '') {
            $decoded = json_decode($jsonValue, true);
            if (!is_array($decoded) || !isset($decoded['blocks']) || !is_array($decoded['blocks']) || $decoded['blocks'] === []) {
                $jsonValue = '';
            }
        }

        if ($jsonValue === '' && $fallbackText !== '') {
            $jsonValue = json_encode([
                'time' => time() * 1000,
                'blocks' => array_map(
                    static fn(string $part): array => [
                        'type' => 'paragraph',
                        'data' => ['text' => htmlspecialchars(trim($part), ENT_QUOTES, 'UTF-8')],
                    ],
                    array_filter(preg_split('/\n{2,}/', $fallbackText) ?: [])
                ),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        if (class_exists('CMS\\Services\\EditorJs\\EditorJsAssetService')) {
            $service = new CMS\Services\EditorJs\EditorJsAssetService();
            return $service->render($name, $jsonValue, [
                'height' => 460,
                'context' => '365netexpertsandcompanie',
                'aria_label' => $label,
                'content_width' => 960,
            ]);
        }

        return '<label class="cms-excomp-form-field"><span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span><textarea class="form-control" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" rows="12">' . htmlspecialchars($jsonValue, ENT_QUOTES, 'UTF-8') . '</textarea></label>';
    }

    private function checkField(string $name, string $label, bool $checked): void
    {
        echo '<label class="cms-excomp-check"><input type="checkbox" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="1"' . ($checked ? ' checked' : '') . '><span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span></label>';
    }

    private function start(string $title, string $activePage): void
    {
        if (function_exists('cms_plugin_admin_layout_start')) {
            cms_plugin_admin_layout_start($title, $activePage);
            if (class_exists('CMS_365NET_Experts_And_Companie')) {
                CMS_365NET_Experts_And_Companie::printInlineStyle('admin.css', 'cms-excomp-admin-inline');
            }
            return;
        }

        if (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart($title, $activePage);
            echo '<div class="cms-plugin-admin-layout"><div class="cms-plugin-admin-layout__content">';
            if (class_exists('CMS_365NET_Experts_And_Companie')) {
                CMS_365NET_Experts_And_Companie::printInlineStyle('admin.css', 'cms-excomp-admin-inline');
            }
            return;
        }

        require_once ABSPATH . 'admin/partials/header.php';
        require_once ABSPATH . 'admin/partials/sidebar.php';
        if (class_exists('CMS_365NET_Experts_And_Companie')) {
            CMS_365NET_Experts_And_Companie::printInlineStyle('admin.css', 'cms-excomp-admin-inline');
        }
    }

    private function end(): void
    {
        if (function_exists('cms_plugin_admin_layout_end')) {
            cms_plugin_admin_layout_end();
            return;
        }

        echo '</div></div>';

        if (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
            return;
        }

        require_once ABSPATH . 'admin/partials/footer.php';
    }
}
