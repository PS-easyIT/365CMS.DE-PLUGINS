<?php
/**
 * Shortcode Handler für CMS Experts
 *
 * @package CMS_Experts
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Experts_Shortcode
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
        $this->register_shortcodes();
    }

    /**
     * Registriert Shortcodes
     */
    private function register_shortcodes(): void
    {
        // Filter für Content, um Shortcodes zu ersetzen
        CMS\Hooks::addFilter('content', [$this, 'process_shortcodes'], 10);
    }

    /**
     * Verarbeitet Shortcodes im Content
     */
    public function process_shortcodes($content)
    {
        // [cms_experts] - Expert Grid
        if (strpos($content, '[cms_experts') !== false) {
            $content = preg_replace_callback(
                '/\[cms_experts([^\]]*)\]/',
                [$this, 'render_experts_grid'],
                $content
            );
        }

        // [cms_expert id="123"] - Single Expert
        if (strpos($content, '[cms_expert ') !== false) {
            $content = preg_replace_callback(
                '/\[cms_expert\s+id="(\d+)"\]/',
                [$this, 'render_single_expert'],
                $content
            );
        }

        return $content;
    }

    /**
     * Rendert Expert Grid
     */
    private function render_experts_grid($matches): string
    {
        // Parse Attribute
        $attributes = $this->parse_shortcode_attributes($matches[1] ?? '');
        
        $limit = (int)($attributes['limit'] ?? 12);
        $availability = $attributes['availability'] ?? null;

        $db_manager = CMS_Experts_Database::instance();
        $args = [
            'status' => 'active',
            'limit' => $limit,
        ];

        if ($availability) {
            $args['availability'] = $availability;
        }

        $experts = $db_manager->get_experts($args);

        ob_start();
        ?>
        <div class="expert-grid">
            <?php foreach ($experts as $expert): ?>
                <?php echo CMS_Experts_Template_Loader::instance()->render_expert_card($expert); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Rendert Single Expert
     */
    private function render_single_expert($matches): string
    {
        $expert_id = (int)($matches[1] ?? 0);
        
        if ($expert_id <= 0) {
            return '';
        }

        $db_manager = CMS_Experts_Database::instance();
        $expert = $db_manager->get_expert($expert_id);

        if (!$expert) {
            return '';
        }

        return CMS_Experts_Template_Loader::instance()->render_expert_card($expert);
    }

    /**
     * Parsed Shortcode Attribute
     */
    private function parse_shortcode_attributes(string $attr_string): array
    {
        $attributes = [];
        
        if (preg_match_all('/(\w+)="([^"]*)"/', $attr_string, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attributes[$match[1]] = $match[2];
            }
        }

        return $attributes;
    }
}
