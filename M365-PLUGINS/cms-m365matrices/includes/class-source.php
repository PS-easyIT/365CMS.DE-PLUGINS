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
        return CMS_M365MATRICES_PLUGIN_DIR . 'templates/' . ltrim($template, '/\\');
    }

    public static function asset_file(string $asset): string
    {
        return CMS_M365MATRICES_PLUGIN_DIR . 'assets/' . ltrim($asset, '/\\');
    }

    public static function asset_url(string $asset): string
    {
        $asset = ltrim($asset, '/');
        $baseUrl = defined('CMS_M365MATRICES_PLUGIN_URL') ? (string) CMS_M365MATRICES_PLUGIN_URL : '/plugins/cms-m365matrices/';

        return rtrim($baseUrl, '/') . '/assets/' . $asset;
    }
}