<?php
/**
 * CMS M365 Calculator – modulare Tool-Registry.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Tool_Registry
{
    /** @var array<string,array<string,mixed>> */
    private static array $tools = [];

    private static bool $bootstrapped = false;

    /**
     * Module registrieren sich über diese Methode.
     *
     * Beispiel für ein späteres m365lic-Modul:
     * CMS_M365CALCULATOR_Tool_Registry::register([
     *     'key' => 'm365lic',
     *     'title' => 'Microsoft 365 Lizenzberater',
     *     'description' => 'Ermittelt passende Microsoft-365-Lizenzen anhand konkreter Anforderungen.',
     *     'icon' => 'license',
     *     'url' => '/m365-lizenzberater',
     *     'category' => 'Lizenzen',
     *     'status' => 'beta',
     * ]);
     *
     * @param array<string,mixed> $tool
     */
    public static function register(array $tool): void
    {
        $normalized = self::normalize($tool);
        if ($normalized === []) {
            return;
        }

        self::$tools[(string) $normalized['key']] = $normalized;
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function tools(): array
    {
        self::bootstrap();

        if (class_exists('CMS\\Hooks') && method_exists('CMS\\Hooks', 'applyFilters')) {
            $filtered = \CMS\Hooks::applyFilters('m365calculator_tools', self::$tools);
            if (is_array($filtered)) {
                foreach ($filtered as $tool) {
                    if (is_array($tool)) {
                        self::register($tool);
                    }
                }
            }
        }

        return self::$tools;
    }

    /**
     * @return array<string,array<int,array<string,mixed>>>
     */
    public static function grouped_by_category(): array
    {
        $groups = [];
        foreach (self::ordered_tools() as $tool) {
            $category = (string) ($tool['category'] ?? 'Weitere Tools');
            $groups[$category] ??= [];
            $groups[$category][] = $tool;
        }

        ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);

        return $groups;
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function get(string $key): ?array
    {
        $tools = self::tools();
        return is_array($tools[$key] ?? null) ? $tools[$key] : null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function ordered_tools(): array
    {
        $tools = array_values(self::tools());
        $statusOrder = ['live' => 0, 'beta' => 1, 'soon' => 2];

        usort($tools, static function (array $left, array $right) use ($statusOrder): int {
            $leftStatus = $statusOrder[(string) ($left['status'] ?? 'soon')] ?? 99;
            $rightStatus = $statusOrder[(string) ($right['status'] ?? 'soon')] ?? 99;

            if ($leftStatus !== $rightStatus) {
                return $leftStatus <=> $rightStatus;
            }

            $priority = ((int) ($left['priority'] ?? 100)) <=> ((int) ($right['priority'] ?? 100));
            if ($priority !== 0) {
                return $priority;
            }

            return strcmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
        });

        return $tools;
    }

    private static function bootstrap(): void
    {
        if (self::$bootstrapped) {
            return;
        }
        self::$bootstrapped = true;

        self::register_shared_mailbox_module();
    }

    private static function register_shared_mailbox_module(): void
    {
        self::register([
            'key' => 'shared-mailbox',
            'title' => 'Shared-Mailbox vs. Lizenz-Rechner',
            'description' => 'Prüft Eignung, Lizenzpflicht und Kostenannahmen für Shared Mailboxes.',
            'icon' => 'mailbox',
            'url' => '/shared-mailbox-vs-lizenz',
            'category' => 'Lizenzen',
            'status' => 'live',
            'priority' => 10,
        ]);
    }

    /**
     * @param array<string,mixed> $tool
     * @return array<string,mixed>
     */
    private static function normalize(array $tool): array
    {
        $key = self::clean_key((string) ($tool['key'] ?? ''));
        if ($key === '') {
            return [];
        }

        $status = in_array((string) ($tool['status'] ?? 'soon'), ['live', 'beta', 'soon'], true)
            ? (string) $tool['status']
            : 'soon';

        return [
            'key' => $key,
            'title' => self::limit_text(trim(strip_tags((string) ($tool['title'] ?? $key))), 90),
            'description' => self::limit_text(trim(strip_tags((string) ($tool['description'] ?? ''))), 140),
            'icon' => self::clean_key((string) ($tool['icon'] ?? 'calculator')) ?: 'calculator',
            'url' => self::sanitize_url((string) ($tool['url'] ?? '')),
            'category' => self::limit_text(trim(strip_tags((string) ($tool['category'] ?? 'Weitere Tools'))), 60),
            'status' => $status,
            'priority' => max(0, min(1000, (int) ($tool['priority'] ?? 100))),
        ];
    }

    private static function limit_text(string $value, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length);
        }

        return substr($value, 0, $length);
    }

    private static function clean_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($value)), '-');
    }

    private static function sanitize_url(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, '/') && !str_starts_with($url, '//') && !str_contains($url, "\0")) {
            return $url;
        }

        $parts = parse_url($url);
        $scheme = is_array($parts) ? strtolower((string) ($parts['scheme'] ?? '')) : '';

        if (in_array($scheme, ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false) {
            return $url;
        }

        return '';
    }
}
