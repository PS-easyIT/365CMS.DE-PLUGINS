<?php
/**
 * @package CMS_Promos
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Promos_Repository
{
    private static ?self $instance = null;

    /** @var array<string,string> */
    public const THEME_HOOK_OPTIONS = [
        'manual' => 'Nur manuell / Archiv',
        'body_start' => 'body_start – direkt nach <body>',
        'after_header' => 'after_header – unter dem Header',
        'home_content' => 'home_content – im Startseiten-Content',
        'before_footer' => 'before_footer – oberhalb des Footers',
    ];

    private \CMS\Database $db;
    private string $prefix;

    /** @var array<string,string> */
    private const DEFAULT_SETTINGS = [
        'archive_title' => 'Promotions & Highlights',
        'archive_description' => 'Zentrale Übersicht aktiver Kampagnen, CTA-Flächen und Teaser-Aktionen.',
        'default_button_label' => 'Mehr erfahren',
        'default_target_behavior' => 'same_tab',
    ];

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->db = \CMS\Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    public function seed_defaults(): void
    {
        foreach (self::DEFAULT_SETTINGS as $key => $value) {
            $exists = $this->db->prepare("SELECT id FROM {$this->prefix}promo_settings WHERE setting_key = ? LIMIT 1");
            $exists->execute([$key]);
            if ($exists->fetchColumn() !== false) {
                continue;
            }

            $stmt = $this->db->prepare("INSERT INTO {$this->prefix}promo_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
        }

        $placementStmt = $this->db->prepare("SELECT id FROM {$this->prefix}promo_placements LIMIT 1");
        $placementStmt->execute();
        if ($placementStmt->fetchColumn() === false) {
            $stmt = $this->db->prepare("INSERT INTO {$this->prefix}promo_placements (name, slug, description, status, theme_hook, hook_priority, max_items) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute(['Homepage Hero', 'homepage-hero', 'Prominente Hero-Fläche auf der Startseite.', 'active', 'after_header', 5, 1]);
            $stmt->execute(['Sidebar CTA', 'sidebar-cta', 'Seitliche CTA-Boxen und Conversion-Elemente.', 'active', 'before_footer', 20, 3]);
            $stmt->execute(['Home Content Highlights', 'home-content-highlights', 'Promo-Karten im Inhaltsbereich der Startseite.', 'active', 'home_content', 15, 2]);
        }

        $this->assign_default_theme_hooks();
    }

    public function get_dashboard_stats(): array
    {
        return [
            'promos' => (int) $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}promos"),
            'active_promos' => (int) $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}promos WHERE status = 'active'"),
            'featured_promos' => (int) $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}promos WHERE is_featured = 1"),
            'placements' => (int) $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}promo_placements"),
            'impressions' => (int) $this->db->get_var("SELECT COALESCE(SUM(impression_count), 0) FROM {$this->prefix}promos"),
            'clicks' => (int) $this->db->get_var("SELECT COALESCE(SUM(click_count), 0) FROM {$this->prefix}promos"),
        ];
    }

    public function get_settings(): array
    {
        $stmt = $this->db->prepare("SELECT setting_key, setting_value FROM {$this->prefix}promo_settings");
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $settings = self::DEFAULT_SETTINGS;
        foreach ($rows as $row) {
            $settings[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }
        return $settings;
    }

    public function save_settings(array $post): array
    {
        $settings = [
            'archive_title' => $this->clean_text($post['archive_title'] ?? ''),
            'archive_description' => $this->clean_textarea($post['archive_description'] ?? ''),
            'default_button_label' => $this->clean_text($post['default_button_label'] ?? 'Mehr erfahren'),
            'default_target_behavior' => in_array(($post['default_target_behavior'] ?? 'same_tab'), ['same_tab', 'new_tab'], true) ? (string) $post['default_target_behavior'] : 'same_tab',
        ];

        foreach ($settings as $key => $value) {
            $exists = $this->db->prepare("SELECT id FROM {$this->prefix}promo_settings WHERE setting_key = ? LIMIT 1");
            $exists->execute([$key]);
            if ($exists->fetchColumn() !== false) {
                $stmt = $this->db->prepare("UPDATE {$this->prefix}promo_settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->execute([$value, $key]);
            } else {
                $stmt = $this->db->prepare("INSERT INTO {$this->prefix}promo_settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->execute([$key, $value]);
            }
        }

        return ['success' => true, 'message' => 'Promo-Einstellungen gespeichert.'];
    }

    public function get_placements(): array
    {
        $sql = "SELECT p.*, (SELECT COUNT(*) FROM {$this->prefix}promos pr WHERE pr.placement_id = p.id) AS promo_count
                FROM {$this->prefix}promo_placements p
                ORDER BY CASE WHEN p.theme_hook = 'manual' THEN 1 ELSE 0 END ASC, p.theme_hook ASC, p.hook_priority ASC, p.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function get_theme_hook_options(): array
    {
        return self::THEME_HOOK_OPTIONS;
    }

    public function get_theme_hook_label(?string $hook): string
    {
        $hook = (string) ($hook ?? 'manual');
        return self::THEME_HOOK_OPTIONS[$hook] ?? self::THEME_HOOK_OPTIONS['manual'];
    }

    public function get_placement(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->prefix}promo_placements WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function get_placement_by_slug(string $slug): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->prefix}promo_placements WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function save_placement(array $post): array
    {
        $id = (int) ($post['placement_id'] ?? 0);
        $name = $this->clean_text($post['name'] ?? '');
        if ($name === '') {
            return ['success' => false, 'error' => 'Bitte einen Platzierungsnamen angeben.'];
        }

        $slug = $this->ensure_unique_placement_slug($this->slugify($post['slug'] ?? $name), $id);
        $status = ($post['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $themeHook = $this->normalize_theme_hook($post['theme_hook'] ?? 'manual');
        $values = [$name, $slug, $this->clean_textarea($post['description'] ?? ''), $status, $themeHook, (int) ($post['hook_priority'] ?? 10), max(1, (int) ($post['max_items'] ?? 3))];

        if ($id > 0) {
            $stmt = $this->db->prepare("UPDATE {$this->prefix}promo_placements SET name = ?, slug = ?, description = ?, status = ?, theme_hook = ?, hook_priority = ?, max_items = ? WHERE id = ?");
            $stmt->execute([...$values, $id]);
            return ['success' => true, 'message' => 'Platzierung aktualisiert.'];
        }

        $stmt = $this->db->prepare("INSERT INTO {$this->prefix}promo_placements (name, slug, description, status, theme_hook, hook_priority, max_items) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($values);
        return ['success' => true, 'message' => 'Platzierung angelegt.'];
    }

    public function delete_placement(int $id): array
    {
        $stmt = $this->db->prepare("UPDATE {$this->prefix}promos SET placement_id = NULL WHERE placement_id = ?");
        $stmt->execute([$id]);
        $stmt = $this->db->prepare("DELETE FROM {$this->prefix}promo_placements WHERE id = ?");
        $stmt->execute([$id]);
        return ['success' => true, 'message' => 'Platzierung gelöscht.'];
    }

    public function get_promos(): array
    {
        $sql = "SELECT pr.*, p.name AS placement_name, p.slug AS placement_slug, p.theme_hook AS placement_theme_hook
                FROM {$this->prefix}promos pr
                LEFT JOIN {$this->prefix}promo_placements p ON p.id = pr.placement_id
                ORDER BY pr.priority DESC, pr.updated_at DESC, pr.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function get_active_promos(?string $placementSlug = null): array
    {
        $sql = "SELECT pr.*, p.name AS placement_name, p.slug AS placement_slug, p.max_items
                FROM {$this->prefix}promos pr
                LEFT JOIN {$this->prefix}promo_placements p ON p.id = pr.placement_id
                WHERE pr.status = 'active'
                  AND (pr.start_at IS NULL OR pr.start_at <= NOW())
                  AND (pr.end_at IS NULL OR pr.end_at >= NOW())";
        $params = [];
        if ($placementSlug !== null && $placementSlug !== '') {
            $sql .= ' AND p.slug = ?';
            $params[] = $placementSlug;
        }
        $sql .= ' ORDER BY pr.is_featured DESC, pr.priority DESC, pr.updated_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function get_hook_placements(string $themeHook): array
    {
        $themeHook = $this->normalize_theme_hook($themeHook);
        if ($themeHook === 'manual') {
            return [];
        }

        $sql = "SELECT p.*, (SELECT COUNT(*) FROM {$this->prefix}promos pr WHERE pr.placement_id = p.id AND pr.status = 'active') AS promo_count
                FROM {$this->prefix}promo_placements p
                WHERE p.status = 'active' AND p.theme_hook = ?
                ORDER BY p.hook_priority ASC, p.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$themeHook]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function get_active_promos_for_placement(int $placementId, int $limit = 0): array
    {
        $sql = "SELECT pr.*, p.name AS placement_name, p.slug AS placement_slug, p.theme_hook AS placement_theme_hook
                FROM {$this->prefix}promos pr
                INNER JOIN {$this->prefix}promo_placements p ON p.id = pr.placement_id
                WHERE pr.placement_id = ?
                  AND pr.status = 'active'
                  AND p.status = 'active'
                  AND (pr.start_at IS NULL OR pr.start_at <= NOW())
                  AND (pr.end_at IS NULL OR pr.end_at >= NOW())
                ORDER BY pr.is_featured DESC, pr.priority DESC, pr.updated_at DESC, pr.id DESC";

        if ($limit > 0) {
            $sql .= ' LIMIT ' . max(1, $limit);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$placementId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function get_promo(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->prefix}promos WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function get_promo_by_slug(string $slug): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->prefix}promos WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function save_promo(array $post): array
    {
        $id = (int) ($post['promo_id'] ?? 0);
        $title = $this->clean_text($post['title'] ?? '');
        if ($title === '') {
            return ['success' => false, 'error' => 'Bitte einen Promo-Titel angeben.'];
        }

        $slug = $this->ensure_unique_promo_slug($this->slugify($post['slug'] ?? $title), $id);
        $status = (string) ($post['status'] ?? 'draft');
        if (!in_array($status, ['draft', 'active', 'paused', 'archived'], true)) {
            $status = 'draft';
        }

        $placementId = max(0, (int) ($post['placement_id'] ?? 0));
        if ($placementId === 0) {
            $placementId = null;
        }

        $values = [
            $placementId,
            $title,
            $slug,
            $this->clean_text($post['teaser'] ?? ''),
            $this->clean_html($post['content_html'] ?? ''),
            $this->clean_url($post['target_url'] ?? ''),
            $this->clean_text($post['button_label'] ?? 'Mehr erfahren'),
            $this->clean_url($post['image_url'] ?? ''),
            $status,
            $this->normalize_datetime($post['start_at'] ?? ''),
            $this->normalize_datetime($post['end_at'] ?? ''),
            max(0, (int) ($post['priority'] ?? 0)),
            !empty($post['is_featured']) ? 1 : 0,
        ];

        if ($id > 0) {
            $stmt = $this->db->prepare("UPDATE {$this->prefix}promos SET placement_id = ?, title = ?, slug = ?, teaser = ?, content_html = ?, target_url = ?, button_label = ?, image_url = ?, status = ?, start_at = ?, end_at = ?, priority = ?, is_featured = ? WHERE id = ?");
            $stmt->execute([...$values, $id]);
            return ['success' => true, 'message' => 'Promo aktualisiert.'];
        }

        $stmt = $this->db->prepare("INSERT INTO {$this->prefix}promos (placement_id, title, slug, teaser, content_html, target_url, button_label, image_url, status, start_at, end_at, priority, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($values);
        return ['success' => true, 'message' => 'Promo angelegt.'];
    }

    public function delete_promo(int $id): array
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->prefix}promos WHERE id = ?");
        $stmt->execute([$id]);
        return ['success' => true, 'message' => 'Promo gelöscht.'];
    }

    public function increment_click(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE {$this->prefix}promos SET click_count = click_count + 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function increment_impression(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE {$this->prefix}promos SET impression_count = impression_count + 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    private function normalize_datetime(string $value): ?string
    {
        $value = trim($value);
        return $value !== '' ? str_replace('T', ' ', $value) . ':00' : null;
    }

    private function clean_text(string $value): string
    {
        return trim(strip_tags($value));
    }

    private function clean_textarea(string $value): string
    {
        return trim(strip_tags($value));
    }

    private function clean_html(string $value): string
    {
        return trim(strip_tags($value, '<p><a><strong><em><ul><ol><li><br><h2><h3><h4><span>'));
    }

    private function clean_url(string $value): string
    {
        $value = trim($value);
        return filter_var($value, FILTER_VALIDATE_URL) ? $value : '';
    }

    private function normalize_theme_hook(string $value): string
    {
        $value = trim($value);
        return array_key_exists($value, self::THEME_HOOK_OPTIONS) ? $value : 'manual';
    }

    private function slugify(string $value): string
    {
        $slug = strtolower(trim(strip_tags($value)));
        $slug = preg_replace('/[^a-z0-9\-]+/', '-', $slug) ?? '';
        return trim($slug, '-') ?: 'promo';
    }

    private function ensure_unique_placement_slug(string $slug, int $ignoreId = 0): string
    {
        $base = $slug;
        $i = 2;
        while (true) {
            $stmt = $this->db->prepare("SELECT id FROM {$this->prefix}promo_placements WHERE slug = ? AND id != ? LIMIT 1");
            $stmt->execute([$slug, $ignoreId]);
            if ($stmt->fetchColumn() === false) {
                return $slug;
            }
            $slug = $base . '-' . $i;
            $i++;
        }
    }

    private function assign_default_theme_hooks(): void
    {
        $defaults = [
            'homepage-hero' => ['after_header', 5],
            'sidebar-cta' => ['before_footer', 20],
            'home-content-highlights' => ['home_content', 15],
        ];

        foreach ($defaults as $slug => [$themeHook, $priority]) {
            $stmt = $this->db->prepare("UPDATE {$this->prefix}promo_placements SET theme_hook = ?, hook_priority = ? WHERE slug = ? AND (theme_hook IS NULL OR theme_hook = '' OR theme_hook = 'manual')");
            $stmt->execute([$themeHook, $priority, $slug]);
        }
    }

    private function ensure_unique_promo_slug(string $slug, int $ignoreId = 0): string
    {
        $base = $slug;
        $i = 2;
        while (true) {
            $stmt = $this->db->prepare("SELECT id FROM {$this->prefix}promos WHERE slug = ? AND id != ? LIMIT 1");
            $stmt->execute([$slug, $ignoreId]);
            if ($stmt->fetchColumn() === false) {
                return $slug;
            }
            $slug = $base . '-' . $i;
            $i++;
        }
    }
}
