<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Marketplace_Public
{
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

            if (!class_exists('CMS\Security') || !\CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'cms_marketplace_public_submit')) {
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
            'type' => (string) ($input['type'] ?? 'plugin'),
            'slug' => trim((string) ($input['slug'] ?? '')),
            'name' => trim((string) ($input['name'] ?? '')),
            'version' => trim((string) ($input['version'] ?? '')),
            'author' => trim((string) ($input['author'] ?? '')),
            'description' => trim((string) ($input['description'] ?? '')),
            'category' => trim((string) ($input['category'] ?? '')),
            'homepage_url' => trim((string) ($input['homepage_url'] ?? '')),
            'docs_url' => trim((string) ($input['docs_url'] ?? '')),
            'changelog_url' => trim((string) ($input['changelog_url'] ?? '')),
            'icon_url' => trim((string) ($input['icon_url'] ?? '')),
            'screenshot_url' => trim((string) ($input['screenshot_url'] ?? '')),
            'requires_cms' => trim((string) ($input['requires_cms'] ?? '')),
            'requires_php' => trim((string) ($input['requires_php'] ?? '')),
            'tested_up_to' => trim((string) ($input['tested_up_to'] ?? '')),
            'released_on' => trim((string) ($input['released_on'] ?? '')),
            'notes' => trim((string) ($input['notes'] ?? '')),
            'is_paid' => !empty($input['is_paid']) ? 1 : 0,
            'price_amount' => trim((string) ($input['price_amount'] ?? '')),
            'price_currency' => trim((string) ($input['price_currency'] ?? 'EUR')),
            'contact_form_slug' => trim((string) ($input['contact_form_slug'] ?? '')),
            'submitter_name' => trim((string) ($input['submitter_name'] ?? '')),
            'submitter_email' => trim((string) ($input['submitter_email'] ?? '')),
        ];
    }
}
