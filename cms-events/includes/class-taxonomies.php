<?php
/**
 * Taxonomies Handler für CMS Events
 *
 * @package CMS_Events
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('CMS_Events_Taxonomies', false)) {
    return;
}

final class CMS_Events_Taxonomies
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
            CMS\Hooks::addAction('cms_init', [$this, 'register_taxonomies'], 30);
        }
    }

    public function register_taxonomies(): void
    {
        $this->register_event_categories();
    }

    private function register_event_categories(): void
    {
        if (!function_exists('register_taxonomy')) {
            $this->create_default_categories();
            return;
        }

        $labels = [
            'name' => 'Event-Kategorien',
            'singular_name' => 'Event-Kategorie',
            'menu_name' => 'Kategorien',
            'all_items' => 'Alle Kategorien',
            'edit_item' => 'Kategorie bearbeiten',
            'view_item' => 'Kategorie ansehen',
            'update_item' => 'Kategorie aktualisieren',
            'add_new_item' => 'Neue Kategorie',
            'new_item_name' => 'Neue Kategorie',
            'search_items' => 'Kategorien durchsuchen',
            'not_found' => 'Keine Kategorien gefunden',
        ];

        $args = [
            'labels' => $labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => false,
            'rewrite' => [
                'slug' => 'event-kategorie',
                'with_front' => false,
                'hierarchical' => true,
            ],
        ];

        register_taxonomy('event_category', 'event', $args);

        // Default Kategorien erstellen falls noch nicht vorhanden
        $this->create_default_categories();
    }

    private function create_default_categories(): void
    {
        $db = CMS\Database::instance();
        
        $default_categories = [
            'conference' => 'Konferenz',
            'workshop' => 'Workshop',
            'webinar' => 'Webinar',
            'meetup' => 'Meetup',
            'training' => 'Training',
            'hackathon' => 'Hackathon',
            'networking' => 'Networking',
            'seminar' => 'Seminar',
        ];

        foreach ($default_categories as $slug => $name) {
            $stmt = $db->prepare("SELECT id FROM {$db->prefix()}event_categories WHERE slug = ? LIMIT 1");
            $stmt->execute([$slug]);
            $exists = $stmt->fetch();

            if (empty($exists)) {
                $db->insert(
                    'event_categories',
                    [
                        'name' => $name,
                        'slug' => $slug,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]
                );
            }
        }
    }

    public function get_categories(): array
    {
        $db = CMS\Database::instance();
        return $db->query("SELECT * FROM {$db->prefix()}event_categories ORDER BY name ASC");
    }

    public function get_category(int $category_id): ?object
    {
        $db = CMS\Database::instance();
        $result = $db->query(
            "SELECT * FROM {$db->prefix()}event_categories WHERE id = ?",
            [$category_id]
        );

        return $result[0] ?? null;
    }

    public function get_category_by_slug(string $slug): ?object
    {
        $db = CMS\Database::instance();
        $result = $db->query(
            "SELECT * FROM {$db->prefix()}event_categories WHERE slug = ?",
            [$slug]
        );

        return $result[0] ?? null;
    }
}
