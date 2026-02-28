<?php
/**
 * Shortcode Handler für CMS Companies
 *
 * @package CMS_Companies
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Companies_Shortcode
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
        $this->init_hooks();
    }

    private function init_hooks(): void
    {
        if (class_exists('CMS\Hooks')) {
            \CMS\Hooks::addFilter('cms_content', [$this, 'process_content_tags'], 20);
        }
    }

    /**
     * Verarbeitet alle [cms_compan*]-Tags im Seiten-Content.
     */
    public function process_content_tags(string $content): string
    {
        $tags = [
            'cms_companies'        => 'render_companies_grid',
            'cms_company_partners' => 'render_partners',
            'cms_company'          => 'render_single_company',
        ];
        foreach ($tags as $tag => $method) {
            $content = preg_replace_callback(
                '/\[' . preg_quote($tag, '/') . '([^\]]*?)\]/',
                function (array $m) use ($method): string {
                    return $this->{$method}($this->parse_atts($m[1]));
                },
                $content
            );
        }
        return $content;
    }

    /**
     * Parst Attribut-String aus einem Tag.
     */
    private function parse_atts(string $str): array
    {
        $parsed = [];
        preg_match_all('/(\w+)\s*=\s*["\']?([^"\'>\s]*)["\']?/', $str, $p, PREG_SET_ORDER);
        foreach ($p as $pair) {
            $parsed[$pair[1]] = $pair[2];
        }
        return $parsed;
    }

    public function render_companies_grid(array $atts = []): string
    {
        $atts = array_merge([
            'limit'        => 12,
            'industry'     => '',
            'city'         => '',
            'partner_only' => false,
            'columns'      => 3,
            'show_filters' => true,
        ], $atts);

        $db_manager = CMS_Companies_Database::instance();
        
        $args = [
            'status' => 'active',
            'limit' => (int)$atts['limit'],
        ];

        if (!empty($atts['industry'])) {
            $args['industry'] = $atts['industry'];
        }

        if (!empty($atts['city'])) {
            $args['city'] = $atts['city'];
        }

        if ($atts['partner_only']) {
            $args['partner_only'] = true;
        }

        $companies = $db_manager->get_companies($args);

        ob_start();
        
        $template_loader = CMS_Companies_Template_Loader::instance();
        $template_loader->render_template('archive-company', [
            'companies' => $companies,
            'columns' => (int)$atts['columns'],
            'show_filters' => (bool)$atts['show_filters'],
        ]);

        return ob_get_clean();
    }

    public function render_single_company(array $atts = []): string
    {
        $atts = array_merge(['id' => 0], $atts);

        $company_id = (int)$atts['id'];
        
        if ($company_id <= 0) {
            return '<p class="error">Ungültige Firmen-ID.</p>';
        }

        $db_manager = CMS_Companies_Database::instance();
        $company = $db_manager->get_company($company_id);

        if (!$company || $company->status !== 'active') {
            return '<p class="error">Firma nicht gefunden.</p>';
        }

        $experts  = $db_manager->get_company_experts($company_id);

        $speakers = [];
        if (class_exists('CMS_Speakers_Database')) {
            try {
                $db   = \CMS\Database::instance();
                $stmt = $db->prepare(
                    "SELECT * FROM {$db->prefix()}speakers WHERE company_id = ? AND status = 'active' ORDER BY last_name, first_name"
                );
                $stmt->execute([$company_id]);
                $speakers = $stmt->fetchAll();
            } catch (\Throwable $e) {
                error_log('CMS_Companies: Speaker-Query failed: ' . $e->getMessage());
            }
        }

        ob_start();

        $template_loader = CMS_Companies_Template_Loader::instance();
        $template_loader->render_template('single-company', [
            'company'  => $company,
            'experts'  => $experts,
            'speakers' => $speakers,
        ]);

        return ob_get_clean();
    }

    public function render_partners(array $atts = []): string
    {
        $atts = array_merge([
            'type'    => 'all',
            'limit'   => 20,
            'columns' => 4,
        ], $atts);

        $db = CMS\Database::instance();
        
        $where = ["status = 'active'"];
        
        switch ($atts['type']) {
            case 'sponsor':
                $where[] = "is_sponsor = 1";
                break;
            case 'top_partner':
                $where[] = "is_top_partner = 1";
                break;
            case 'partner':
                $where[] = "is_partner = 1";
                break;
            default:
                $where[] = "(is_partner = 1 OR is_top_partner = 1 OR is_sponsor = 1)";
        }

        $where_clause = implode(' AND ', $where);
        $limit = (int)$atts['limit'];

        $companies = $db->query("
            SELECT * FROM {$db->prefix()}companies 
            WHERE {$where_clause}
            ORDER BY 
                is_sponsor DESC,
                is_top_partner DESC,
                is_partner DESC, 
                name ASC
            LIMIT {$limit}
        ");

        if (empty($companies)) {
            return '<p class="no-companies">Keine Partner gefunden.</p>';
        }

        ob_start();
        ?>
        <div class="company-partners-grid columns-<?= (int)$atts['columns'] ?>">
            <?php foreach ($companies as $company): ?>
                <div class="company-partner-card">
                    <?php
                    $template_loader = CMS_Companies_Template_Loader::instance();
                    $template_loader->render_template('company-card', [
                        'company' => $company,
                    ]);
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
