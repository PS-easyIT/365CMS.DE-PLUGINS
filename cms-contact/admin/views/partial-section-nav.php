<?php declare(strict_types=1); if (!defined('ABSPATH')) exit;
/**
 * Contact Admin – Section Navigation (Tabs)
 *
 * Erwartet: $activeSection (string) – 'dashboard' | 'forms' | 'submissions' | 'settings'
 */
$activeSection = $activeSection ?? 'dashboard';
$_navItems = [
    'dashboard'   => ['icon' => '📊', 'label' => 'Dashboard'],
    'forms'       => ['icon' => '📋', 'label' => 'Formulare'],
    'submissions' => ['icon' => '📩', 'label' => 'Nachrichten'],
    'settings'    => ['icon' => '⚙️', 'label' => 'Einstellungen'],
];
?>
<!-- Section-Nav -->
<nav class="lp-section-nav">
    <?php foreach ($_navItems as $_section => $_nav): ?>
    <a href="?section=<?php echo $_section; ?>" class="lp-section-nav__item <?php echo $activeSection === $_section ? 'active' : ''; ?>">
        <span class="lp-section-nav__icon"><?php echo $_nav['icon']; ?></span> <?php echo $_nav['label']; ?>
    </a>
    <?php endforeach; ?>
</nav>
