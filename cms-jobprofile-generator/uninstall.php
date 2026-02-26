<?php
declare(strict_types=1);
/**
 * Deinstallations-Routine für den CMS Job Profile Generator.
 *
 * Wird vom 365CMS-Core ausgeführt, wenn das Plugin deinstalliert (nicht nur deaktiviert) wird.
 * Entfernt alle Plugin-Tabellen und Subscription-Plan-Erweiterungen vollständig.
 *
 * @package CMS_JobProfileGenerator
 * @since   0.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Sicherheitsprüfung: Nur ausführen wenn CMS-Kontext aktiv
if (!class_exists('CMS\Database')) {
    return;
}

// Installer-Klasse laden (falls noch nicht geschehen)
$installerFile = __DIR__ . '/includes/class-installer.php';
if (!class_exists('CMS_JPG_Installer') && file_exists($installerFile)) {
    require_once $installerFile;
}

if (class_exists('CMS_JPG_Installer')) {
    CMS_JPG_Installer::uninstall();
}
