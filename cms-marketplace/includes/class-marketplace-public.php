<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Marketplace_Public
{
    private const PUBLIC_SUBMIT_MIN_INTERVAL = 30;
    private const PUBLIC_SUBMIT_WINDOW = 3600;
    private const PUBLIC_SUBMIT_MAX_ATTEMPTS = 10;
    private const PUBLIC_RATE_FILE_MAX_BYTES = 4096;

    public function __construct(private readonly CMS_Marketplace_Service $service)
    {
    }

    public function isCurrentRequest(): bool
    {
        return $this->resolveCurrentSection() !== null;
    }

    public function shouldHandleBeforeRouting(): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return false;
        }

        $section = $this->resolveCurrentSection();
        return in_array($section, ['submit', 'security_report'], true);
    }

    public function registerRoutes(object $router): void
    {
        if (!method_exists($router, 'addRoute')) {
            return;
        }

        $routes = $this->service->getPublicRouteMap();

        foreach (['overview', 'plugins', 'themes', 'cms'] as $section) {
            $path = (string) ($routes[$section] ?? '');
            if ($path === '') {
                continue;
            }

            foreach ($this->expandLocalizedPaths($path) as $localizedPath) {
                $router->addRoute('GET', $localizedPath, function () use ($section): void {
                    $this->handleRequest($section);
                });
            }
        }

        $submitPath = (string) ($routes['submit'] ?? '');
        if ($submitPath !== '') {
            foreach ($this->expandLocalizedPaths($submitPath) as $localizedPath) {
                $router->addRoute('GET', $localizedPath, function (): void {
                    $this->handleRequest('submit');
                });
            }
        }

        $securityPath = (string) ($routes['security_report'] ?? '');
        if ($securityPath !== '') {
            foreach ($this->expandLocalizedPaths($securityPath) as $localizedPath) {
                $router->addRoute('GET', $localizedPath, function (): void {
                    $this->handleRequest('security_report');
                });
            }
        }
    }

    public function handleRequest(?string $forcedSection = null): void
    {
        $section = $forcedSection ?? $this->resolveCurrentSection();
        if ($section === null) {
            return;
        }
        $lang = $this->resolveCurrentLanguage();
        $publicCssUrl = $this->resolvePublicCssUrl($section);

        if (!headers_sent()) {
            http_response_code(200);
            header('Content-Type: text/html; charset=UTF-8');
            header('X-Content-Type-Options: nosniff');
        }

        if ($section === 'security_report') {
            $this->renderSecurityReportPage($lang, $publicCssUrl);
            exit;
        }

        if ($section !== 'submit') {
            $pageTitle = match ($section) {
                'plugins' => $this->i18nText($lang, 'Marketplace Plugins', 'Marketplace Plugins'),
                'themes' => $this->i18nText($lang, 'Marketplace Themes', 'Marketplace Themes'),
                'cms' => $this->i18nText($lang, 'Marketplace CMS', 'Marketplace CMS'),
                default => '365CMS Marketplace',
            };
            $pageDescription = match ($section) {
                'plugins' => $this->i18nText($lang, 'Alle freigegebenen 365CMS-Plugins mit Installations- und Update-Metadaten.', 'All published 365CMS plugins with install and update metadata.'),
                'themes' => $this->i18nText($lang, 'Alle freigegebenen 365CMS-Themes mit Installations- und Update-Metadaten.', 'All published 365CMS themes with install and update metadata.'),
                'cms' => $this->i18nText($lang, 'Freigegebene 365CMS-Core-Pakete und Update-Dateien.', 'Published 365CMS core packages and update files.'),
                default => $this->i18nText($lang, 'Öffentliche Übersicht aller freigegebenen Bereiche des 365CMS Marketplace.', 'Public overview of all published sections of the 365CMS marketplace.'),
            };
            $overview = $this->service->getPublicOverviewPayload();
            $sections = $this->service->getPublicSections($lang);
            $entries = $this->service->getPublicSectionEntries($section);
            $publicUrls = $this->service->getPublicUrls();
            $publicRouteMap = $this->service->getLocalizedPublicRouteMap($lang);
            $siteUrl = defined('SITE_URL') ? (string) SITE_URL : '';

            $templatePath = CMS_MARKETPLACE_PLUGIN_DIR . 'templates/public-marketplace.php';
            if (is_file($templatePath)) {
                include $templatePath;
            }
            exit;
        }

        $message = null;
        $messageType = 'success';
        $values = $this->getDefaultValues();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $values = array_merge($values, $this->extractSubmittedValues($_POST));

            if ($this->isHoneypotFilled($_POST)) {
                $message = $this->i18nText($lang, 'Danke, deine Einreichung wurde entgegengenommen.', 'Thanks, your submission has been received.');
                $messageType = 'success';
                $values = $this->getDefaultValues();
            } elseif ($this->recordAndCheckRateLimit()) {
                $message = $this->i18nText($lang, 'Bitte warte kurz, bevor du eine weitere Einreichung absendest.', 'Please wait a moment before sending another submission.');
                $messageType = 'error';
            } elseif (!class_exists('CMS\\Security') || !\CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'cms_marketplace_public_submit')) {
                $message = $this->i18nText($lang, 'Sicherheitscheck fehlgeschlagen.', 'Security check failed.');
                $messageType = 'error';
            } else {
                $result = $this->service->submitPublicItem($_POST, $_FILES['package_zip'] ?? null);
                $message = (string) ($result['message'] ?? $this->i18nText($lang, 'Aktion abgeschlossen.', 'Action completed.'));
                $messageType = !empty($result['success']) ? 'success' : 'error';

                if (!empty($result['success'])) {
                    $values = $this->getDefaultValues();
                }
            }
        }

        $csrfToken = class_exists('CMS\Security') ? \CMS\Security::instance()->generateToken('cms_marketplace_public_submit') : '';
        $submitUrl = $this->service->getPublicSubmissionUrl();
        $siteUrl = defined('SITE_URL') ? (string) SITE_URL : '';

        $templatePath = CMS_MARKETPLACE_PLUGIN_DIR . 'templates/public-submit.php';
        if (is_file($templatePath)) {
            include $templatePath;
        }
        exit;
    }

    private function resolveCurrentSection(): ?string
    {
        return $this->service->resolvePublicSectionFromRequestUri((string) ($_SERVER['REQUEST_URI'] ?? '/'));
    }

    private function resolveCurrentLanguage(): string
    {
        if (function_exists('cms_plugin_public_language')) {
            return cms_plugin_public_language();
        }

        return 'de';
    }

    private function expandLocalizedPaths(string $path): array
    {
        if (!function_exists('cms_plugin_public_localized_path')) {
            return [$path];
        }

        $de = cms_plugin_public_localized_path($path, 'de');
        $en = cms_plugin_public_localized_path($path, 'en');
        return array_values(array_unique(array_filter([$de, $en], static fn (string $value): bool => $value !== '')));
    }

    private function i18nText(string $lang, string $de, string $en): string
    {
        if (function_exists('cms_plugin_public_i18n_value')) {
            return cms_plugin_public_i18n_value(['text' => $de, 'text_en' => $en], 'text', $lang, $de);
        }

        return $lang === 'en' ? $en : $de;
    }

    private function getDefaultValues(): array
    {
        return [
            'type' => 'plugin',
            'slug' => '',
            'name' => '',
            'version' => '',
            'author' => '',
            'description' => '',
            'category' => '',
            'homepage_url' => '',
            'docs_url' => '',
            'changelog_url' => '',
            'icon_url' => '',
            'screenshot_url' => '',
            'requires_cms' => '',
            'requires_php' => '',
            'tested_up_to' => '',
            'released_on' => date('Y-m-d'),
            'notes' => '',
            'is_paid' => 0,
            'price_amount' => '',
            'price_currency' => 'EUR',
            'contact_form_slug' => '',
            'submitter_name' => '',
            'submitter_email' => '',
        ];
    }

    private function getDefaultSecurityReportValues(): array
    {
        return [
            'type' => $this->sanitizeSubmittedType($_GET['type'] ?? ''),
            'slug' => $this->sanitizeSubmittedText($_GET['slug'] ?? '', 120),
            'version' => $this->sanitizeSubmittedText($_GET['version'] ?? '', 50),
            'title' => '',
            'details' => '',
            'reporter_name' => '',
            'reporter_email' => '',
        ];
    }

    private function extractSubmittedSecurityValues(array $input): array
    {
        return [
            'type' => $this->sanitizeSubmittedType($input['type'] ?? ''),
            'slug' => $this->sanitizeSubmittedText($input['slug'] ?? '', 120),
            'version' => $this->sanitizeSubmittedText($input['version'] ?? '', 50),
            'title' => $this->sanitizeSubmittedText($input['title'] ?? '', 190),
            'details' => $this->sanitizeSubmittedTextarea($input['details'] ?? '', 4000),
            'reporter_name' => $this->sanitizeSubmittedText($input['reporter_name'] ?? '', 190),
            'reporter_email' => $this->sanitizeSubmittedText($input['reporter_email'] ?? '', 190),
        ];
    }

    private function renderSecurityReportPage(string $lang, string $publicCssUrl): void
    {
        $message = null;
        $messageType = 'success';
        $values = $this->getDefaultSecurityReportValues();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $values = array_merge($values, $this->extractSubmittedSecurityValues($_POST));

            if ($this->isHoneypotFilled($_POST)) {
                $message = $this->i18nText($lang, 'Danke. Die Sicherheitsmeldung wurde entgegengenommen.', 'Thanks. The security report has been received.');
                $messageType = 'success';
                $values = $this->getDefaultSecurityReportValues();
            } elseif ($this->recordAndCheckRateLimit()) {
                $message = $this->i18nText($lang, 'Bitte warte kurz, bevor du eine weitere Meldung sendest.', 'Please wait a moment before sending another report.');
                $messageType = 'error';
            } elseif (!class_exists('CMS\\Security') || !\CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'cms_marketplace_public_security_report')) {
                $message = $this->i18nText($lang, 'Sicherheitscheck fehlgeschlagen.', 'Security check failed.');
                $messageType = 'error';
            } else {
                $result = $this->service->submitSecurityReport($_POST, $lang);
                $message = (string) ($result['message'] ?? $this->i18nText($lang, 'Aktion abgeschlossen.', 'Action completed.'));
                $messageType = !empty($result['success']) ? 'success' : 'error';
                if (!empty($result['success'])) {
                    $values = $this->getDefaultSecurityReportValues();
                }
            }
        }

        $csrfToken = class_exists('CMS\Security') ? \CMS\Security::instance()->generateToken('cms_marketplace_public_security_report') : '';
        $submitUrl = $this->service->getPublicSecurityReportUrl([], $lang);
        $siteUrl = defined('SITE_URL') ? (string) SITE_URL : '';
        $templatePath = CMS_MARKETPLACE_PLUGIN_DIR . 'templates/public-security-report.php';
        if (is_file($templatePath)) {
            include $templatePath;
        }
    }

    private function extractSubmittedValues(array $input): array
    {
        return [
            'type' => $this->sanitizeSubmittedType($input['type'] ?? 'plugin'),
            'slug' => $this->sanitizeSubmittedText($input['slug'] ?? '', 120),
            'name' => $this->sanitizeSubmittedText($input['name'] ?? '', 190),
            'version' => $this->sanitizeSubmittedText($input['version'] ?? '', 50),
            'author' => $this->sanitizeSubmittedText($input['author'] ?? '', 190),
            'description' => $this->sanitizeSubmittedTextarea($input['description'] ?? '', 1200),
            'category' => $this->sanitizeSubmittedText($input['category'] ?? '', 120),
            'homepage_url' => $this->sanitizeSubmittedText($input['homepage_url'] ?? '', 2048),
            'docs_url' => $this->sanitizeSubmittedText($input['docs_url'] ?? '', 2048),
            'changelog_url' => $this->sanitizeSubmittedText($input['changelog_url'] ?? '', 2048),
            'icon_url' => $this->sanitizeSubmittedText($input['icon_url'] ?? '', 2048),
            'screenshot_url' => $this->sanitizeSubmittedText($input['screenshot_url'] ?? '', 2048),
            'requires_cms' => $this->sanitizeSubmittedText($input['requires_cms'] ?? '', 30),
            'requires_php' => $this->sanitizeSubmittedText($input['requires_php'] ?? '', 30),
            'tested_up_to' => $this->sanitizeSubmittedText($input['tested_up_to'] ?? '', 30),
            'released_on' => $this->sanitizeSubmittedDate($input['released_on'] ?? ''),
            'notes' => $this->sanitizeSubmittedTextarea($input['notes'] ?? '', 2000),
            'is_paid' => !empty($input['is_paid']) ? 1 : 0,
            'price_amount' => $this->sanitizeSubmittedText($input['price_amount'] ?? '', 20),
            'price_currency' => $this->sanitizeSubmittedText($input['price_currency'] ?? 'EUR', 10),
            'contact_form_slug' => $this->sanitizeSubmittedText($input['contact_form_slug'] ?? '', 255),
            'submitter_name' => $this->sanitizeSubmittedText($input['submitter_name'] ?? '', 190),
            'submitter_email' => $this->sanitizeSubmittedText($input['submitter_email'] ?? '', 190),
        ];
    }

    private function sanitizeSubmittedType(mixed $value): string
    {
        $value = strtolower(trim((string) $value));
        return in_array($value, ['cms', 'plugin', 'theme'], true) ? $value : 'plugin';
    }

    private function sanitizeSubmittedText(mixed $value, int $maxLength): string
    {
        $value = trim(strip_tags((string) $value));
        $value = preg_replace('/[[:cntrl:]]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return $this->limitUtf8(trim($value), max(1, $maxLength));
    }

    private function sanitizeSubmittedTextarea(mixed $value, int $maxLength): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", strip_tags((string) $value));
        $value = str_replace("\0", '', $value);

        return $this->limitUtf8(trim($value), max(1, $maxLength));
    }

    private function sanitizeSubmittedDate(mixed $value): string
    {
        $value = trim((string) $value);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : '';
    }

    private function isHoneypotFilled(array $input): bool
    {
        return trim((string) ($input['company_website'] ?? '')) !== '';
    }

    private function recordAndCheckRateLimit(): bool
    {
        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        $directory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'cms-marketplace-rate';
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            return false;
        }

        $file = $directory . DIRECTORY_SEPARATOR . hash('sha256', $ip) . '.json';
        $now = time();
        $attempts = [];

        if (is_file($file)) {
            $size = @filesize($file);
            $raw = is_int($size) && $size >= 0 && $size <= self::PUBLIC_RATE_FILE_MAX_BYTES ? @file_get_contents($file) : '';
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($decoded)) {
                $attempts = array_values(array_filter(array_map('intval', $decoded), static function (int $timestamp) use ($now): bool {
                    return $timestamp >= ($now - self::PUBLIC_SUBMIT_WINDOW);
                }));
            }
        }

        $lastAttempt = $attempts !== [] ? max($attempts) : 0;
        if ($lastAttempt > 0 && ($now - $lastAttempt) < self::PUBLIC_SUBMIT_MIN_INTERVAL) {
            return true;
        }

        if (count($attempts) >= self::PUBLIC_SUBMIT_MAX_ATTEMPTS) {
            return true;
        }

        $attempts[] = $now;
        $json = json_encode($attempts);
        if (is_string($json)) {
            file_put_contents($file, $json, LOCK_EX);
            @chmod($file, 0600);
        }

        return false;
    }

    private function resolvePublicCssUrl(string $section): string
    {
        $fileName = in_array($section, ['submit', 'security_report'], true) ? 'public-submit.css' : 'public-marketplace.css';
        $cssFile = CMS_MARKETPLACE_PLUGIN_DIR . 'assets/css/' . $fileName;
        if (!is_file($cssFile)) {
            return '';
        }

        return CMS_MARKETPLACE_PLUGIN_URL . 'assets/css/' . rawurlencode($fileName) . '?v=' . (int) filemtime($cssFile);
    }

    private function limitUtf8(string $value, int $maxLength): string
    {
        if ($maxLength < 1) {
            return '';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $maxLength, 'UTF-8');
        }

        return substr($value, 0, $maxLength);
    }
}
