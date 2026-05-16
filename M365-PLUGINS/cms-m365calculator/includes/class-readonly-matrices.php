<?php
/**
 * CMS M365 Calculator – ReadOnly Lizenz- und Add-on-Matrizen.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_ReadOnly_Matrices
{
    /**
     * @return array<string,mixed>
     */
    public static function suite_matrix(): array
    {
        return self::normalize_suite_matrix(CMS_M365CALCULATOR_Catalog::readonly_suite_matrix());
    }

    /**
     * @return array<string,mixed>
     */
    public static function addon_matrix(): array
    {
        return self::normalize_addon_matrix(CMS_M365CALCULATOR_Catalog::readonly_addon_matrix());
    }

    public static function render_suite_matrix_page(): string
    {
        return CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-readonly-suite-matrix.php';
    }

    public static function render_addon_matrix_page(): string
    {
        return CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-readonly-addon-matrix.php';
    }

    /**
     * @param array<string,mixed> $catalog
     * @return array<string,mixed>
     */
    private static function normalize_suite_matrix(array $catalog): array
    {
        $columns = self::normalize_packages(is_array($catalog['columns'] ?? null) ? $catalog['columns'] : []);
        $groups = self::normalize_groups(is_array($catalog['groups'] ?? null) ? $catalog['groups'] : [], array_column($columns, 'slug'));
        $meta = is_array($catalog['meta'] ?? null) ? $catalog['meta'] : [];

        return [
            'meta' => $meta,
            'columns' => $columns,
            'groups' => $groups,
            'counts' => [
                'columns' => count($columns),
                'groups' => count($groups),
                'rows' => self::count_rows($groups),
            ],
            'sources' => is_array($meta['sources'] ?? null) ? array_values(array_map('strval', $meta['sources'])) : [],
            'notes' => is_array($meta['notes'] ?? null) ? array_values(array_map('strval', $meta['notes'])) : [],
        ];
    }

    /**
     * @param array<string,mixed> $catalog
     * @return array<string,mixed>
     */
    private static function normalize_addon_matrix(array $catalog): array
    {
        $areas = [];
        foreach (is_array($catalog['areas'] ?? null) ? $catalog['areas'] : [] as $area) {
            if (!is_array($area)) {
                continue;
            }

            $packages = self::normalize_packages(is_array($area['packages'] ?? null) ? $area['packages'] : []);
            $areas[] = [
                'key' => self::clean_key((string) ($area['key'] ?? 'area')),
                'label' => self::limit_text(trim(strip_tags((string) ($area['label'] ?? 'Add-ons'))), 120),
                'description' => self::limit_text(trim(strip_tags((string) ($area['description'] ?? ''))), 240),
                'packages' => $packages,
                'rows' => self::normalize_rows(is_array($area['rows'] ?? null) ? $area['rows'] : [], array_column($packages, 'slug')),
            ];
        }

        $meta = is_array($catalog['meta'] ?? null) ? $catalog['meta'] : [];

        return [
            'meta' => $meta,
            'areas' => $areas,
            'counts' => [
                'areas' => count($areas),
                'packages' => array_sum(array_map(static fn(array $area): int => count($area['packages'] ?? []), $areas)),
                'rows' => array_sum(array_map(static fn(array $area): int => count($area['rows'] ?? []), $areas)),
            ],
            'sources' => is_array($meta['sources'] ?? null) ? array_values(array_map('strval', $meta['sources'])) : [],
            'notes' => is_array($meta['notes'] ?? null) ? array_values(array_map('strval', $meta['notes'])) : [],
        ];
    }

    /**
     * @param array<int,mixed> $packages
     * @return array<int,array<string,mixed>>
     */
    private static function normalize_packages(array $packages): array
    {
        $normalized = [];
        foreach ($packages as $package) {
            if (!is_array($package)) {
                continue;
            }

            $slug = self::clean_key((string) ($package['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $normalized[] = [
                'slug' => $slug,
                'name' => self::limit_text(trim(strip_tags((string) ($package['name'] ?? $slug))), 120),
                'short' => self::limit_text(trim(strip_tags((string) ($package['short'] ?? $package['name'] ?? $slug))), 80),
                'family' => self::limit_text(trim(strip_tags((string) ($package['family'] ?? ''))), 80),
                'badge' => self::limit_text(trim(strip_tags((string) ($package['badge'] ?? ''))), 80),
                'billing' => self::limit_text(trim(strip_tags((string) ($package['billing'] ?? ''))), 80),
                'max_users' => self::limit_text(trim(strip_tags((string) ($package['max_users'] ?? ''))), 80),
                'price_month' => is_numeric($package['price_month'] ?? null) ? (float) $package['price_month'] : null,
                'is_placeholder' => !empty($package['is_placeholder']),
            ];
        }

        return $normalized;
    }

    /**
     * @param array<int,mixed> $groups
     * @param array<int,string> $allowedSlugs
     * @return array<int,array<string,mixed>>
     */
    private static function normalize_groups(array $groups, array $allowedSlugs): array
    {
        $normalized = [];
        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }

            $rows = self::normalize_rows(is_array($group['rows'] ?? null) ? $group['rows'] : [], $allowedSlugs);
            if ($rows === []) {
                continue;
            }

            $normalized[] = [
                'key' => self::clean_key((string) ($group['key'] ?? 'group')),
                'label' => self::limit_text(trim(strip_tags((string) ($group['label'] ?? 'Matrix'))), 120),
                'rows' => $rows,
            ];
        }

        return $normalized;
    }

    /**
     * @param array<int,mixed> $rows
     * @param array<int,string> $allowedSlugs
     * @return array<int,array<string,mixed>>
     */
    private static function normalize_rows(array $rows, array $allowedSlugs): array
    {
        $allowed = array_fill_keys(array_map('strval', $allowedSlugs), true);
        $normalized = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $values = [];
            $sourceValues = is_array($row['values'] ?? null) ? $row['values'] : [];
            foreach ($allowed as $slug => $_) {
                $values[$slug] = self::normalize_cell(is_array($sourceValues[$slug] ?? null) ? $sourceValues[$slug] : []);
            }

            $normalized[] = [
                'label' => self::limit_text(trim(strip_tags((string) ($row['label'] ?? ''))), 160),
                'description' => self::limit_text(trim(strip_tags((string) ($row['description'] ?? ''))), 240),
                'values' => $values,
            ];
        }

        return $normalized;
    }

    /**
     * @param array<string,mixed> $cell
     * @return array<string,string>
     */
    private static function normalize_cell(array $cell): array
    {
        $allowed = ['included', 'partial', 'addon', 'prerequisite', 'not_included', 'warning', 'separate', 'note'];
        $status = self::clean_key((string) ($cell['status'] ?? 'note'));

        return [
            'status' => in_array($status, $allowed, true) ? $status : 'note',
            'label' => self::limit_text(trim(strip_tags((string) ($cell['label'] ?? '—'))), 100),
            'note' => self::limit_text(trim(strip_tags((string) ($cell['note'] ?? ''))), 220),
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $groups
     */
    private static function count_rows(array $groups): int
    {
        return array_sum(array_map(static fn(array $group): int => count($group['rows'] ?? []), $groups));
    }

    private static function clean_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($value)), '-');
    }

    private static function limit_text(string $value, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length);
        }

        return substr($value, 0, $length);
    }
}
