<?php
/**
 * Public Template: M365 Tools Landingpage shell.
 *
 * Header, navigation and footer are provided by the active CMS theme. The
 * actual content area lives in templates/m365-tools-content.php.
 *
 * @package CMS_M365TOOLS
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'M365 Tools']);
}

include CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/m365-tools-content.php';

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getFooter();
}
