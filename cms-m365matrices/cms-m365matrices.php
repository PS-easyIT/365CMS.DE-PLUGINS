<?php
/**
 * Plugin Name: CMS M365 Matrixen
 * Plugin URI: https://365network.de/cms-m365matrices
 * Description: Kompatibilitäts-Bootstrap für die reinen M365 Matrixen im Repository-Unterordner M365-PLUGINS.
 * Version: 1.0.6
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_M365MATRICES
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$canonicalPluginFile = dirname(__DIR__) . '/M365-PLUGINS/cms-m365matrices/cms-m365matrices.php';
if (!is_file($canonicalPluginFile)) {
    error_log('CMS M365 Matrixen bootstrap missing: ' . $canonicalPluginFile);
    return;
}

defined('CMS_M365MATRICES_VERSION') || define('CMS_M365MATRICES_VERSION', '1.0.6');
defined('CMS_M365MATRICES_PLUGIN_URL') || define('CMS_M365MATRICES_PLUGIN_URL', '/plugins/M365-PLUGINS/cms-m365matrices/');

require_once $canonicalPluginFile;
