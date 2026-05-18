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

    public function __construct(private readonly CMS_Marketplace_Service $service)
    {
    }

    public function isCurrentRequest(): bool
    {
        return $this->resolveCurrentSection() !== null;
    }

    public function shouldHandleBeforeRouting(): bool
    {
        return (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') && $this->resolveCurrentSection() === 'submit';
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

            $router->addRoute('GET', $path, function () use ($section): void {
                $this->handleRequest($section);
            });
        }

        $submitPath = (string) ($routes['submit'] ?? '');
        if ($submitPath !== '') {
            $router->addRoute('GET', $submitPath, function (): void {
                $this->handleRequest('submit');
            });
        }
    }

    public function handleRequest(?string $forcedSection = null): void
    {
        $section = $forcedSection ?? $this->resolveCurrentSection();
        if ($section === null) {
            return;
        }

        if (!headers_sent()) {
            http_response_code(200);
            header('Content-Type: text/html; charset=UTF-8');
            header('X-Content-Type-Options: nosniff');
        }

        if ($section !== 'submit') {
            $pageTitle = match ($section) {
                'plugins' => 'Marketplace Plugins',
                'themes' => 'Marketplace Themes',
                'cms' => 'Marketplace CMS',
                default => '365CMS Marketplace',
            };
            $pageDescription = match ($section) {
                'plugins' => 'Alle freigegebenen 365CMS-Plugins mit Installations- und Update-Metadaten.',
                'themes' => 'Alle freigegebenen 365CMS-Themes mit Installations- und Update-Metadaten.',
                'cms' => 'Freigegebene 365CMS-Core-Pakete und Update-Dateien.',
                default => 'Öffentliche Übersicht aller freigegebenen Bereiche des 365CMS Marketplace.',
            };
            $overview = $this->service->getPublicOverviewPayload();
            $sections = $this->service->getPublicSections();
            $entries = $this->service->getPublicSectionEntries($section);
            $publicUrls = $this->service->getPublicUrls();
            $publicRouteMap = $this->service->getPublicRouteMap();
            $siteUrl = defined('SITE_URL') ? (string) SITE_URL : '';

            include CMS_MARKETPLACE_PLUGIN_DIR . 'templates/public-marketplace.php';
            exit;
        }

        $message = null;
        $messageType = 'success';
        $values = $this->getDefaultValues();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $values = array_merge($values, $this->extractSubmittedValues($_POST));

            if ($this->isHoneypotFilled($_POST)) {
                $message = 'Danke, deine Einreichung wurde entgegengenommen.';
                $messageType = 'success';
                $values = $this->getDefaultValues();
            } elseif ($this->recordAndCheckRateLimit()) {
                $message = 'Bitte warte kurz, bevor du eine weitere Einreichung absendest.';
                $messageType = 'error';
            } elseif (!class_exists('CMS\\Security') || !\CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'cms_marketplace_public_submit')) {
                $message = 'Sicherheitscheck fehlgeschlagen.';
                $messageType = 'error';
            } else {
                $result = $this->service->submitPublicItem($_POST, $_FILES['package_zip'] ?? null);
                $message = (string) ($result['message'] ?? 'Aktion abgeschlossen.');
                $messageType = !empty($result['success']) ? 'success' : 'error';

                if (!empty($result['success'])) {
                    $values = $this->getDefaultValues();
                }
            }
        }

        $csrfToken = class_exists('CMS\Security') ? \CMS\Security::instance()->generateToken('cms_marketplace_public_submit') : '';
        $submitUrl = $this->service->getPublicSubmissionUrl();
        $siteUrl = defined('SITE_URL') ? (string) SITE_URL : '';

        include CMS_MARKETPLACE_PLUGIN_DIR . 'templates/public-submit.php';
        exit;
    }

    private function isPublicSubmissionRequest(): bool
    {
        return $this->resolveCurrentSection() === 'submit';
    }

    private function resolveCurrentSection(): ?string
    {
        return $this->service->resolvePublicSectionFromRequestUri((string) ($_SERVER['REQUEST_URI'] ?? '/'));
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

        return mb_substr(trim($value), 0, max(1, $maxLength), 'UTF-8');
    }

    private function sanitizeSubmittedTextarea(mixed $value, int $maxLength): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", strip_tags((string) $value));
        $value = str_replace("\0", '', $value);

        return mb_substr(trim($value), 0, max(1, $maxLength), 'UTF-8');
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
        if ($ip === '') {
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
            $raw = is_int($size) && $size >= 0 && $size <= 4096 ? file_get_contents($file) : '';
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
        }

        return false;
    }
}
