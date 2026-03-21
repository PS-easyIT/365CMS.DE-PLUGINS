<?php

declare(strict_types=1);

namespace CmsKnowledgebase\Repository;

use CMS\Database;
use PDO;
use CmsKnowledgebase\Support\Defaults;
use CmsKnowledgebase\Support\LoggerFactory;

if (!defined('ABSPATH')) {
    exit;
}

final class EntryRepository
{
    private static ?self $instance = null;

    private $logger;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->logger = LoggerFactory::create();
    }

    /**
     * @return array<string, int>
     */
    public function getDashboardStats(): array
    {
        $db = Database::instance();
        $entriesTable = $this->entriesTable();
        $stats = [
            'entries' => 0,
            'active_entries' => 0,
            'categories' => 0,
            'tooltip_entries' => 0,
        ];

        $query = "SELECT
            COUNT(*) AS entries,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_entries,
            SUM(CASE WHEN tooltip_text IS NOT NULL AND tooltip_text <> '' THEN 1 ELSE 0 END) AS tooltip_entries,
            COUNT(DISTINCT CASE WHEN category IS NOT NULL AND category <> '' THEN category END) AS categories
            FROM {$entriesTable}";

        $stmt = $db->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (is_array($row)) {
            foreach ($stats as $key => $value) {
                $stats[$key] = (int) ($row[$key] ?? $value);
            }
        }

        return $stats;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getEntries(array $filters = []): array
    {
        return $this->getEntriesByColumns('*', $filters);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getEntryList(array $filters = []): array
    {
        return $this->getEntriesByColumns(
            'id, title, keyword, slug, excerpt, tooltip_text, synonyms, category, priority, is_active, is_case_sensitive, is_whole_word, max_links_per_page, created_at, updated_at',
            $filters
        );
    }

    public function getEntry(int $id): ?array
    {
        $db = Database::instance();
        $stmt = $db->prepare('SELECT * FROM ' . $this->entriesTable() . ' WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($entry) ? $entry : null;
    }

    public function getEntryBySlug(string $slug): ?array
    {
        $db = Database::instance();
        $stmt = $db->prepare('SELECT * FROM ' . $this->entriesTable() . ' WHERE slug = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$slug]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($entry) ? $entry : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getActiveEntriesForLinking(): array
    {
        $entries = $this->getEntriesByColumns(
            'id, title, keyword, slug, excerpt, tooltip_text, synonyms, is_case_sensitive, is_whole_word, max_links_per_page',
            ['status' => 'active']
        );

        foreach ($entries as &$entry) {
            $terms = [$entry['keyword'] ?? ''];
            $synonyms = preg_split('/[\r\n,]+/', (string) ($entry['synonyms'] ?? '')) ?: [];
            foreach ($synonyms as $synonym) {
                $synonym = trim((string) $synonym);
                if ($synonym !== '') {
                    $terms[] = $synonym;
                }
            }

            $terms = array_values(array_unique(array_filter(array_map(static fn($value): string => trim((string) $value), $terms))));
            usort($terms, static fn(string $left, string $right): int => mb_strlen($right, 'UTF-8') <=> mb_strlen($left, 'UTF-8'));
            $entry['terms'] = $terms;
            $entry['url'] = SITE_URL . '/kb/' . rawurlencode((string) ($entry['slug'] ?? ''));
        }
        unset($entry);

        return $entries;
    }

    public function countActiveEntries(): int
    {
        $db = Database::instance();
        $stmt = $db->prepare('SELECT COUNT(*) FROM ' . $this->entriesTable() . ' WHERE is_active = 1');
        $stmt->execute();
        $count = $stmt->fetchColumn();

        return (int) $count;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCategories(): array
    {
        $db = Database::instance();
        $stmt = $db->prepare('SELECT category, COUNT(*) AS entry_count FROM ' . $this->entriesTable() . " WHERE is_active = 1 AND category IS NOT NULL AND category <> '' GROUP BY category ORDER BY category ASC");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRelatedEntries(array $entry, int $limit = 5): array
    {
        $db = Database::instance();
        $table = $this->entriesTable();
        $category = trim((string) ($entry['category'] ?? ''));

        if ($category !== '') {
            $stmt = $db->prepare("SELECT * FROM {$table} WHERE is_active = 1 AND id <> ? AND category = ? ORDER BY priority ASC, title ASC LIMIT {$limit}");
            $stmt->execute([(int) ($entry['id'] ?? 0), $category]);
        } else {
            $stmt = $db->prepare("SELECT * FROM {$table} WHERE is_active = 1 AND id <> ? ORDER BY priority ASC, title ASC LIMIT {$limit}");
            $stmt->execute([(int) ($entry['id'] ?? 0)]);
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    }

    /**
     * @return array<string, string>
     */
    public function getSettings(): array
    {
        $db = Database::instance();
        $table = $this->settingsTable();
        $settings = Defaults::settings();

        $stmt = $db->prepare("SELECT setting_key, setting_value FROM {$table}");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (!is_array($row) || empty($row['setting_key'])) {
                    continue;
                }

                $settings[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
            }
        }

        return $settings;
    }

    public function saveSettings(array $input): array
    {
        $db = Database::instance();
        $table = $this->settingsTable();
        $stmt = $db->prepare("INSERT INTO {$table} (setting_key, setting_value)
            VALUES (:setting_key, :setting_value)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $existingSettings = $this->getSettings();
        $submittedTab = (string) ($input['redirect_tab'] ?? 'general');

        $checkboxKeys = [
            'enable_autolink',
            'enable_tooltips',
            'enable_output_buffer',
            'nofollow_links',
            'open_links_new_tab',
            'show_nav_link',
            'show_search',
            'show_category_sidebar',
            'show_keyword_badges',
            'show_related_entries',
        ];

        $numberRanges = [
            'max_links_per_page' => [1, 25],
            'content_max_width' => [720, 1600],
            'sidebar_width' => [220, 420],
            'design_border_radius' => [0, 32],
        ];

        $colorDefaults = [
            'design_accent_color' => '#0d9488',
            'design_accent_hover_color' => '#0f766e',
            'design_surface_color' => '#ffffff',
            'design_border_color' => '#dbe1ea',
            'tooltip_background_color' => '#111827',
            'tooltip_text_color' => '#f8fafc',
        ];

        $tabKeys = [
            'general' => [
                'enable_autolink',
                'enable_tooltips',
                'enable_output_buffer',
                'max_links_per_page',
                'nofollow_links',
                'open_links_new_tab',
                'archive_title',
                'archive_intro',
                'show_search',
                'show_category_sidebar',
                'show_keyword_badges',
                'show_related_entries',
                'show_nav_link',
                'nav_label',
            ],
            'design' => [
                'content_max_width',
                'sidebar_width',
                'design_accent_color',
                'design_accent_hover_color',
                'design_surface_color',
                'design_border_color',
                'design_border_radius',
                'tooltip_background_color',
                'tooltip_text_color',
            ],
        ];

        $activeKeys = $tabKeys[$submittedTab] ?? array_keys(Defaults::settings());
        $activeKeyLookup = array_fill_keys($activeKeys, true);

        foreach (Defaults::settings() as $key => $default) {
            $isActiveKey = isset($activeKeyLookup[$key]);
            $value = $existingSettings[$key] ?? $default;

            if ($isActiveKey) {
                if (in_array($key, $checkboxKeys, true)) {
                    $value = $input[$key] ?? '0';
                } else {
                    $value = $input[$key] ?? $value;
                }
            }

            if ($isActiveKey && isset($colorDefaults[$key]) && isset($input[$key . '_text']) && trim((string) $input[$key . '_text']) !== '') {
                $value = (string) $input[$key . '_text'];
            }

            if (in_array($key, $checkboxKeys, true)) {
                $value = $this->isTruthy($value) ? '1' : '0';
            } elseif (isset($numberRanges[$key])) {
                [$min, $max] = $numberRanges[$key];
                $value = (string) max($min, min($max, (int) $value));
            } elseif (isset($colorDefaults[$key])) {
                $value = $this->sanitizeColor((string) $value, $colorDefaults[$key]);
            } elseif ($key === 'nav_label') {
                $value = $this->sanitizeText((string) $value, 40);
            } elseif ($key === 'archive_title') {
                $value = $this->sanitizeText((string) $value, 120);
            } elseif ($key === 'archive_intro') {
                $value = $this->sanitizeTextarea((string) $value);
            } else {
                $value = $this->sanitizeText((string) $value, 1000);
            }

            $stmt->execute([
                'setting_key' => $key,
                'setting_value' => (string) $value,
            ]);
        }

        return ['success' => true, 'message' => 'Knowledgebase-Einstellungen gespeichert.'];
    }

    /**
     * @return array<string, string>
     */
    public function getPublicDesignTokens(): array
    {
        $settings = $this->getSettings();

        return [
            '--kb-accent' => $this->sanitizeColor((string) ($settings['design_accent_color'] ?? ''), '#0d9488'),
            '--kb-accent-hover' => $this->sanitizeColor((string) ($settings['design_accent_hover_color'] ?? ''), '#0f766e'),
            '--kb-surface' => $this->sanitizeColor((string) ($settings['design_surface_color'] ?? ''), '#ffffff'),
            '--kb-border' => $this->sanitizeColor((string) ($settings['design_border_color'] ?? ''), '#dbe1ea'),
            '--kb-radius' => (string) max(0, min(32, (int) ($settings['design_border_radius'] ?? 14))) . 'px',
            '--kb-content-max-width' => (string) max(720, min(1600, (int) ($settings['content_max_width'] ?? 1200))) . 'px',
            '--kb-sidebar-width' => (string) max(220, min(420, (int) ($settings['sidebar_width'] ?? 300))) . 'px',
            '--kb-tooltip-bg' => $this->sanitizeColor((string) ($settings['tooltip_background_color'] ?? ''), '#111827'),
            '--kb-tooltip-text' => $this->sanitizeColor((string) ($settings['tooltip_text_color'] ?? ''), '#f8fafc'),
        ];
    }

    public function saveEntry(array $input): array
    {
        $db = Database::instance();
        $table = $this->entriesTable();
        $id = (int) ($input['entry_id'] ?? 0);
        $title = $this->sanitizeText((string) ($input['title'] ?? ''), 255);
        $keyword = $this->sanitizeText((string) ($input['keyword'] ?? ''), 190);
        $slugInput = $this->sanitizeText((string) ($input['slug'] ?? ''), 190);
        $slugSource = $slugInput !== '' ? $slugInput : ($title !== '' ? $title : $keyword);
        $slug = $this->generateUniqueSlug($slugSource, $id);
        $excerpt = $this->sanitizeTextarea((string) ($input['excerpt'] ?? ''));
        $content = $this->sanitizeRichText((string) ($input['content'] ?? ''));
        $tooltipText = $this->sanitizeTextarea((string) ($input['tooltip_text'] ?? ''));
        $synonyms = $this->sanitizeSynonyms((string) ($input['synonyms'] ?? ''));
        $category = $this->sanitizeText((string) ($input['category'] ?? ''), 120);
        $priority = max(1, min(9999, (int) ($input['priority'] ?? 100)));
        $isActive = $this->isTruthy($input['is_active'] ?? '0') ? 1 : 0;
        $caseSensitive = $this->isTruthy($input['is_case_sensitive'] ?? '0') ? 1 : 0;
        $wholeWord = $this->isTruthy($input['is_whole_word'] ?? '1') ? 1 : 0;
        $maxLinksPerPage = max(1, min(20, (int) ($input['max_links_per_page'] ?? 1)));

        if ($title === '' || $keyword === '') {
            return ['success' => false, 'error' => 'Titel und Fokusbegriff sind erforderlich.'];
        }

        if ($id > 0) {
            $stmt = $db->prepare("UPDATE {$table} SET
                title = :title,
                keyword = :keyword,
                slug = :slug,
                excerpt = :excerpt,
                content = :content,
                tooltip_text = :tooltip_text,
                synonyms = :synonyms,
                category = :category,
                priority = :priority,
                is_active = :is_active,
                is_case_sensitive = :is_case_sensitive,
                is_whole_word = :is_whole_word,
                max_links_per_page = :max_links_per_page
                WHERE id = :id");
            $stmt->execute([
                'id' => $id,
                'title' => $title,
                'keyword' => $keyword,
                'slug' => $slug,
                'excerpt' => $excerpt,
                'content' => $content,
                'tooltip_text' => $tooltipText,
                'synonyms' => $synonyms,
                'category' => $category,
                'priority' => $priority,
                'is_active' => $isActive,
                'is_case_sensitive' => $caseSensitive,
                'is_whole_word' => $wholeWord,
                'max_links_per_page' => $maxLinksPerPage,
            ]);

            $this->logger->info('Knowledgebase-Eintrag aktualisiert.', ['entry_id' => $id, 'slug' => $slug]);

            return ['success' => true, 'message' => 'Knowledgebase-Eintrag aktualisiert.', 'id' => $id];
        }

        $stmt = $db->prepare("INSERT INTO {$table}
            (title, keyword, slug, excerpt, content, tooltip_text, synonyms, category, priority, is_active, is_case_sensitive, is_whole_word, max_links_per_page)
            VALUES
            (:title, :keyword, :slug, :excerpt, :content, :tooltip_text, :synonyms, :category, :priority, :is_active, :is_case_sensitive, :is_whole_word, :max_links_per_page)");
        $stmt->execute([
            'title' => $title,
            'keyword' => $keyword,
            'slug' => $slug,
            'excerpt' => $excerpt,
            'content' => $content,
            'tooltip_text' => $tooltipText,
            'synonyms' => $synonyms,
            'category' => $category,
            'priority' => $priority,
            'is_active' => $isActive,
            'is_case_sensitive' => $caseSensitive,
            'is_whole_word' => $wholeWord,
            'max_links_per_page' => $maxLinksPerPage,
        ]);

        $newId = (int) $db->lastInsertId();
        $this->logger->info('Knowledgebase-Eintrag erstellt.', ['entry_id' => $newId, 'slug' => $slug]);

        return ['success' => true, 'message' => 'Knowledgebase-Eintrag angelegt.', 'id' => $newId];
    }

    public function deleteEntry(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige Eintrags-ID.'];
        }

        $db = Database::instance();
        $stmt = $db->prepare('DELETE FROM ' . $this->entriesTable() . ' WHERE id = ?');
        $stmt->execute([$id]);

        $this->logger->info('Knowledgebase-Eintrag gelöscht.', ['entry_id' => $id]);

        return ['success' => true, 'message' => 'Knowledgebase-Eintrag gelöscht.'];
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     * @return array<string, int>
     */
    public function importPresetEntries(array $entries, string $packageKey = ''): array
    {
        if ($entries === []) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];
        }

        $normalizedEntries = [];
        $slugs = [];

        foreach ($entries as $index => $entry) {
            $slugSource = (string) ($entry['slug'] ?? $entry['title'] ?? $entry['keyword'] ?? ('knowledgebase-entry-' . ($index + 1)));
            $slug = $this->slugify($slugSource);
            if ($slug === '') {
                $slug = 'knowledgebase-entry-' . ($index + 1);
            }

            $entry['slug'] = $slug;
            $normalizedEntries[] = $entry;
            $slugs[] = $slug;
        }

        $existingEntries = $this->getExistingEntriesBySlug($slugs);
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($normalizedEntries as $entry) {
            $slug = (string) ($entry['slug'] ?? '');
            $existingEntry = $slug !== '' ? ($existingEntries[$slug] ?? null) : null;
            if (is_array($existingEntry)) {
                if ($this->isGeneratedPlaceholderEntry($existingEntry)) {
                    $result = $this->saveEntry([
                        ...$entry,
                        'entry_id' => (string) ($existingEntry['id'] ?? 0),
                        'is_active' => (string) ($existingEntry['is_active'] ?? $entry['is_active'] ?? '1'),
                        'priority' => (string) ($existingEntry['priority'] ?? $entry['priority'] ?? '100'),
                        'is_case_sensitive' => (string) ($existingEntry['is_case_sensitive'] ?? $entry['is_case_sensitive'] ?? '0'),
                        'is_whole_word' => (string) ($existingEntry['is_whole_word'] ?? $entry['is_whole_word'] ?? '1'),
                        'max_links_per_page' => (string) ($existingEntry['max_links_per_page'] ?? $entry['max_links_per_page'] ?? '1'),
                    ]);

                    if ((bool) ($result['success'] ?? false)) {
                        ++$updated;
                        continue;
                    }

                    ++$errors;
                    continue;
                }

                ++$skipped;
                continue;
            }

            $result = $this->saveEntry($entry);
            if ((bool) ($result['success'] ?? false)) {
                ++$created;
                continue;
            }

            ++$errors;
        }

        $this->logger->info('Knowledgebase-Preset-Import verarbeitet.', [
            'package' => $packageKey,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    private function entriesTable(): string
    {
        return Database::instance()->prefix() . 'kb_entries';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getEntriesByColumns(string $columns, array $filters = []): array
    {
        $db = Database::instance();
        $table = $this->entriesTable();
        $conditions = [];
        $params = [];

        if (($filters['status'] ?? '') === 'active') {
            $conditions[] = 'is_active = 1';
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $conditions[] = '(title LIKE ? OR keyword LIKE ? OR synonyms LIKE ? OR excerpt LIKE ?)';
            $needle = '%' . $search . '%';
            $params[] = $needle;
            $params[] = $needle;
            $params[] = $needle;
            $params[] = $needle;
        }

        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '') {
            $conditions[] = 'category = ?';
            $params[] = $category;
        }

        $sql = "SELECT {$columns} FROM {$table}";
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY is_active DESC, priority ASC, title ASC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    private function settingsTable(): string
    {
        return Database::instance()->prefix() . 'kb_settings';
    }

    private function generateUniqueSlug(string $source, int $ignoreId = 0): string
    {
        $slug = $this->slugify($source);
        if ($slug === '') {
            $slug = 'knowledgebase-entry';
        }

        $db = Database::instance();
        $table = $this->entriesTable();
        $candidate = $slug;
        $suffix = 2;

        while (true) {
            $query = "SELECT id FROM {$table} WHERE slug = ?";
            $params = [$candidate];
            if ($ignoreId > 0) {
                $query .= ' AND id <> ?';
                $params[] = $ignoreId;
            }
            $query .= ' LIMIT 1';

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $exists = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($exists)) {
                return $candidate;
            }

            $candidate = $slug . '-' . $suffix;
            ++$suffix;
        }
    }

    private function slugify(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = preg_replace('/[^\p{L}\p{N}]+/u', '-', $value) ?? '';
        $value = trim($value, '-');
        return substr($value, 0, 190);
    }

    /**
     * @param array<int, string> $slugs
     * @return array<string, bool>
     */
    private function getExistingEntriesBySlug(array $slugs): array
    {
        $slugs = array_values(array_unique(array_filter(array_map(
            fn(string $slug): string => $this->slugify($slug),
            $slugs
        ))));

        if ($slugs === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($slugs), '?'));
        $stmt = Database::instance()->prepare('SELECT * FROM ' . $this->entriesTable() . " WHERE slug IN ({$placeholders})");
        $stmt->execute($slugs);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $lookup = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (!is_array($row) || empty($row['slug'])) {
                    continue;
                }

                $lookup[(string) $row['slug']] = $row;
            }
        }

        return $lookup;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function isGeneratedPlaceholderEntry(array $entry): bool
    {
        $excerpt = trim((string) ($entry['excerpt'] ?? ''));
        $tooltip = trim((string) ($entry['tooltip_text'] ?? ''));
        $content = trim((string) ($entry['content'] ?? ''));

        if ($excerpt === '' && $tooltip === '' && $content === '') {
            return true;
        }

        $markers = [
            'kompakt erklärt – als Startpunkt für interne Verlinkung',
            'ist ein Standardbegriff aus dem Bereich',
            'kurz erklärt – ein Standardbegriff',
            'Nutze diesen Standardartikel als Startpunkt',
            '<h2>Typischer Einsatz</h2>',
            '<h2>Worauf sollte man achten?</h2>',
            '<h2>Praxis-Hinweis</h2>',
            '<h2>Lizenz &amp; Verfügbarkeit</h2>',
            'Standardpaket-Generator',
            'allgemeine Einordnung, Lizenz- und Verfügbarkeitsinfos',
        ];

        $haystack = $excerpt . "\n" . $tooltip . "\n" . $content;
        foreach ($markers as $marker) {
            if (str_contains($haystack, $marker)) {
                return true;
            }
        }

        return false;
    }

    private function sanitizeText(string $value, int $maxLength = 255): string
    {
        $value = function_exists('sanitize_text_field') ? sanitize_text_field($value) : trim(strip_tags($value));
        return mb_substr($value, 0, $maxLength, 'UTF-8');
    }

    private function sanitizeTextarea(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = trim(strip_tags($value));
        return mb_substr($value, 0, 2000, 'UTF-8');
    }

    private function sanitizeRichText(string $value): string
    {
        return trim(strip_tags($value, '<p><a><strong><em><ul><ol><li><br><blockquote><code><pre><h2><h3><h4>'));
    }

    private function sanitizeSynonyms(string $value): string
    {
        $items = preg_split('/[\r\n,]+/', $value) ?: [];
        $items = array_filter(array_map(fn(string $item): string => $this->sanitizeText($item, 190), $items));
        return implode("\n", array_values(array_unique($items)));
    }

    private function sanitizeColor(string $value, string $fallback): string
    {
        $value = trim($value);
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1 ? strtoupper($value) : $fallback;
    }

    private function isTruthy(mixed $value): bool
    {
        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }
}
