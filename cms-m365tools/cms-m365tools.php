<?php
/**
 * Plugin Name: CMS M365 Tools
 * Plugin URI: https://365network.de/cms-m365tools
 * Description: Kompatibilitäts-Bootstrap für die M365 Tools im Repository-Unterordner M365-PLUGINS.
 * Version: 3.0.4
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_M365TOOLS
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$canonicalPluginFile = dirname(__DIR__) . '/M365-PLUGINS/cms-m365tools/cms-m365tools.php';
if (!is_file($canonicalPluginFile)) {
    error_log('CMS M365 Tools bootstrap missing: ' . $canonicalPluginFile);
    return;
}

defined('CMS_M365TOOLS_VERSION') || define('CMS_M365TOOLS_VERSION', '3.0.4');
defined('CMS_M365TOOLS_PLUGIN_URL') || define('CMS_M365TOOLS_PLUGIN_URL', '/plugins/M365-PLUGINS/cms-m365tools/');
defined('CMS_M365CALCULATOR_VERSION') || define('CMS_M365CALCULATOR_VERSION', CMS_M365TOOLS_VERSION);
defined('CMS_M365CALCULATOR_PLUGIN_URL') || define('CMS_M365CALCULATOR_PLUGIN_URL', CMS_M365TOOLS_PLUGIN_URL);

require_once $canonicalPluginFile;
