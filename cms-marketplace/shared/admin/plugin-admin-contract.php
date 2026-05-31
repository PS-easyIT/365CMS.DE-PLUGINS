<?php
/**
 * Shared plugin admin contract helpers.
 *
 * @package CMS_SHARED_ADMIN
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('cms_plugin_admin_require_layout_helpers')) {
    /**
     * Loads core admin layout helpers when needed.
     */
    function cms_plugin_admin_require_layout_helpers(): void
    {
        $menuFile = ABSPATH . 'admin/partials/admin-menu.php';
        $basePath = realpath((string) ABSPATH);
        $menuRealPath = realpath($menuFile);

        if (
            !function_exists('renderAdminLayoutStart')
            && is_string($basePath)
            && is_string($menuRealPath)
            && str_starts_with($menuRealPath, rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR)
            && is_file($menuRealPath)
        ) {
            require_once $menuRealPath;
        }
    }
}

if (!function_exists('cms_plugin_admin_normalize_slug')) {
    /**
     * Normalizes admin page slug to the shared contract.
     */
    function cms_plugin_admin_normalize_slug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = (string) preg_replace('/[^a-z0-9_-]+/', '-', $slug);
        return trim($slug, '-');
    }
}

if (!function_exists('cms_plugin_admin_active_slug')) {
    /**
     * Returns active admin slug from request.
     */
    function cms_plugin_admin_active_slug(string $fallback): string
    {
        $requested = (string) ($_GET['page'] ?? $fallback);
        $normalized = cms_plugin_admin_normalize_slug($requested);
        $fallback = cms_plugin_admin_normalize_slug($fallback);

        return $normalized !== '' ? $normalized : $fallback;
    }
}

if (!function_exists('cms_plugin_admin_emit_notice')) {
    /**
     * Emits an admin notice and logs details for operators.
     */
    function cms_plugin_admin_emit_notice(string $message, string $type = 'error', string $logContext = ''): void
    {
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $safeType = $type === 'success' ? 'success' : 'error';

        if ($logContext !== '') {
            error_log('[cms-plugin-admin] ' . $logContext . ' :: ' . $message);
        }

        if (function_exists('cms_admin_notice')) {
            cms_admin_notice($message, $safeType);
            return;
        }

        $class = $safeType === 'success' ? 'alert alert-success' : 'alert alert-error';
        echo '<div class="' . $class . '" role="alert">' . $safeMessage . '</div>';
    }
}

if (!function_exists('cms_plugin_admin_shared_styles')) {
    /**
     * Outputs shared admin stylesheet once per request.
     */
    function cms_plugin_admin_shared_styles(): void
    {
        static $printed = false;
        if ($printed) {
            return;
        }
        $printed = true;

        $cssPath = __DIR__ . '/plugin-admin-shared.css';
        if (!is_file($cssPath)) {
            return;
        }

        $size = @filesize($cssPath);
        if (!is_int($size) || $size <= 0 || $size > 262144) {
            return;
        }

        $css = file_get_contents($cssPath);
        if (!is_string($css) || $css === '') {
            return;
        }

        echo "<style>\n" . $css . "\n</style>\n";
    }
}

if (!function_exists('cms_plugin_admin_layout_start')) {
    /**
     * Shared admin layout start wrapper.
     */
    function cms_plugin_admin_layout_start(string $title, string $activePageSlug): void
    {
        cms_plugin_admin_require_layout_helpers();
        cms_plugin_admin_shared_styles();

        if (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart($title, $activePageSlug);
        }

        echo '<div class="cms-plugin-admin-layout"><div class="cms-plugin-admin-layout__content">';
    }
}

if (!function_exists('cms_plugin_admin_layout_end')) {
    /**
     * Shared admin layout end wrapper.
     */
    function cms_plugin_admin_layout_end(): void
    {
        echo '</div></div>';

        if (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
        }
    }
}

if (!function_exists('cms_plugin_admin_dispatch_page')) {
    /**
     * Dispatches plugin admin pages with safe fallback behavior.
     *
     * @param array<string, callable|null> $callbackMap
     */
    function cms_plugin_admin_dispatch_page(array $callbackMap, string $defaultSlug, string $pluginSlug): void
    {
        $defaultSlug = cms_plugin_admin_normalize_slug($defaultSlug);
        $pluginSlug = cms_plugin_admin_normalize_slug($pluginSlug);
        $activeSlug = cms_plugin_admin_active_slug($defaultSlug);

        $resolvedSlug = array_key_exists($activeSlug, $callbackMap) ? $activeSlug : $defaultSlug;
        $callback = $callbackMap[$resolvedSlug] ?? null;

        if (!is_callable($callback)) {
            cms_plugin_admin_layout_start('Plugin Administration', $pluginSlug);
            cms_plugin_admin_emit_notice(
                'Die angeforderte Admin-Seite ist derzeit nicht verfuegbar. Bitte pruefen Sie die Plugin-Konfiguration.',
                'error',
                sprintf(
                    'missing admin callback plugin=%s requested=%s resolved=%s default=%s',
                    $pluginSlug,
                    $activeSlug,
                    $resolvedSlug,
                    $defaultSlug
                )
            );
            cms_plugin_admin_layout_end();
            return;
        }

        call_user_func($callback);
    }
}
