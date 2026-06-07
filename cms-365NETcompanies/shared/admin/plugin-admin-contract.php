<?php
/**
 * Shared admin contract helpers for plugin admin pages.
 *
 * @package CMS_Companies
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Companies_Plugin_Admin_Contract
{
    public const ADMIN_SLUG = 'companies';
    public const ADMIN_BASE_PATH = '/admin/companies';

    /** @var array<int, string> */
    private const ALLOWED_VIEWS = ['overview', 'industries', 'tags', 'design', 'settings'];

    public static function normalize_view(?string $view): string
    {
        $candidate = strtolower(trim((string) $view));
        if ($candidate === '' || !in_array($candidate, self::ALLOWED_VIEWS, true)) {
            return 'overview';
        }

        return $candidate;
    }

    public static function current_view_from_request(): string
    {
        $rawView = $_GET['view'] ?? $_GET['tab'] ?? 'overview';
        return self::normalize_view(is_string($rawView) ? $rawView : 'overview');
    }

    public static function admin_url(string $view = 'overview', array $query = []): string
    {
        $params = $query;
        $normalizedView = self::normalize_view($view);
        if ($normalizedView !== 'overview') {
            $params['view'] = $normalizedView;
        }

        $queryString = http_build_query($params);
        $path = self::ADMIN_BASE_PATH . ($queryString !== '' ? '?' . $queryString : '');

        return rtrim((string) SITE_URL, '/') . $path;
    }

    public static function dispatch_view(array $callbacks, string $view): string
    {
        $resolvedView = self::normalize_view($view);
        if (isset($callbacks[$resolvedView]) && is_callable($callbacks[$resolvedView])) {
            try {
                $callbacks[$resolvedView]();
                return $resolvedView;
            } catch (\Throwable $e) {
                error_log('CMS Companies admin dispatch failed for view "' . $resolvedView . '": ' . $e->getMessage());
            }
        }

        if ($resolvedView !== 'overview' && isset($callbacks['overview']) && is_callable($callbacks['overview'])) {
            try {
                $callbacks['overview']();
                return 'overview';
            } catch (\Throwable $e) {
                error_log('CMS Companies admin fallback dispatch failed: ' . $e->getMessage());
            }
        }

        echo '<div class="alert alert-error">Die gewünschte Admin-Ansicht konnte nicht geladen werden.</div>';
        return 'overview';
    }
}
