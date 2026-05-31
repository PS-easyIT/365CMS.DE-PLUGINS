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
        return class_exists('CMS_M365MATRICES_Settings')
            && class_exists('CMS_M365MATRICES_ReadOnly_Matrices');
    }

    public static function template_path(string $template): string
    {
        $relativePath = self::sanitize_relative_path($template);
        if ($relativePath === '') {
            return '';
        }

        return CMS_M365MATRICES_PLUGIN_DIR . 'templates/' . $relativePath;
    }

    public static function asset_file(string $asset): string
    {
        $relativePath = self::sanitize_relative_path($asset);
        if ($relativePath === '') {
            return '';
        }

        return CMS_M365MATRICES_PLUGIN_DIR . 'assets/' . $relativePath;
    }

    public static function asset_url(string $asset): string
    {
        $asset = self::sanitize_relative_path($asset);
        if ($asset === '') {
            return '';
        }

        $baseUrl = defined('CMS_M365MATRICES_PLUGIN_URL') ? (string) CMS_M365MATRICES_PLUGIN_URL : '/plugins/cms-m365matrices/';

        return rtrim($baseUrl, '/') . '/assets/' . $asset;
    }

    private static function sanitize_relative_path(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        $path = ltrim($path, '/');
        if ($path === '' || str_contains($path, "\0")) {
            return '';
        }

        $segments = explode('/', $path);
        $safeSegments = [];
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return '';
            }

            if (preg_match('/^[a-zA-Z0-9._-]+$/', $segment) !== 1) {
                return '';
            }

            $safeSegments[] = $segment;
        }

        return implode('/', $safeSegments);
    }
}