<?php
/**
 * CMS M365 Matrixen – gemeinsame Runtime-Quelle.
 *
 * @package CMS_M365MATRICES
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365MATRICES_Source
{
    public static function load_runtime(): bool
    {
        self::define_shared_calculator_constants();

        $files = [
            'includes/class-catalog.php',
            'includes/class-settings.php',
            'includes/class-readonly-matrices.php',
        ];

        foreach ($files as $relativeFile) {
            $path = self::shared_file($relativeFile);
            if ($path !== '' && file_exists($path)) {
                require_once $path;
            }
        }

        return class_exists('CMS_M365CALCULATOR_Catalog')
            && class_exists('CMS_M365CALCULATOR_Settings')
            && class_exists('CMS_M365CALCULATOR_ReadOnly_Matrices');
    }

    public static function template_path(string $template): string
    {
        return self::shared_file('templates/' . ltrim($template, '/'));
    }

    public static function asset_file(string $asset): string
    {
        return self::shared_file('assets/' . ltrim($asset, '/'));
    }

    public static function asset_url(string $asset): string
    {
        $asset = ltrim($asset, '/');
        $baseUrl = defined('CMS_M365CALCULATOR_PLUGIN_URL')
            ? (string) CMS_M365CALCULATOR_PLUGIN_URL
            : '/plugins/cms-m365tools/';

        return rtrim($baseUrl, '/') . '/assets/' . $asset;
    }

    public static function shared_dir(): string
    {
        if (defined('CMS_M365CALCULATOR_PLUGIN_DIR') && is_dir((string) CMS_M365CALCULATOR_PLUGIN_DIR)) {
            return rtrim((string) CMS_M365CALCULATOR_PLUGIN_DIR, '/\\') . '/';
        }

        $candidate = dirname(CMS_M365MATRICES_PLUGIN_DIR) . '/cms-m365tools/';
        if (is_dir($candidate)) {
            return $candidate;
        }

        return '';
    }

    private static function define_shared_calculator_constants(): void
    {
        $sharedDir = self::shared_dir();
        if ($sharedDir === '') {
            return;
        }

        defined('CMS_M365CALCULATOR_VERSION') || define('CMS_M365CALCULATOR_VERSION', CMS_M365MATRICES_VERSION);
        defined('CMS_M365CALCULATOR_PLUGIN_DIR') || define('CMS_M365CALCULATOR_PLUGIN_DIR', $sharedDir);
        defined('CMS_M365CALCULATOR_PLUGIN_URL') || define('CMS_M365CALCULATOR_PLUGIN_URL', '/plugins/cms-m365tools/');
    }

    private static function shared_file(string $relativeFile): string
    {
        $sharedDir = self::shared_dir();
        if ($sharedDir === '') {
            return '';
        }

        return $sharedDir . ltrim($relativeFile, '/\\');
    }
}