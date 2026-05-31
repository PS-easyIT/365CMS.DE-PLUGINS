<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Marketplace_Service
{
    private const MAX_PACKAGE_SIZE = 52428800;
    private const MAX_UNCOMPRESSED_PACKAGE_SIZE = 262144000;
    private const MAX_ZIP_ENTRIES = 2000;
    private const MAX_ZIP_ENTRY_NAME_LENGTH = 512;
    private const MAX_PREVIEW_SOURCE_BYTES = 524288;
    private const MAX_PREVIEW_BYTES = 4000;
    private const SYNC_STATE_FILE = '.marketplace-sync-state.json';
    private const SECURITY_REPORTS_FILE = 'security-reports.json';

    private ?array $settingsCache = null;
    private ?string $lastSyncedStateDigest = null;

    public function __construct(private readonly CMS_Marketplace_Repository $repository)
    {
    }

    public function boot(): void
    {
        $this->repository->ensureTable();
        $this->ensureStorageDirectories();

        if ($this->shouldSyncPublicCatalogs()) {
            $this->syncPublicCatalogs();
        }
    }

    public function getItems(?string $type = null): array
    {
        return $this->repository->getAll($this->normalizeTypeOrNull($type));
    }

    public function findItem(int $id): ?array
    {
        return $this->repository->findById($id);
    }

    public function getSummary(): array
    {
        $all = $this->repository->getAll();

        $summary = [
            'total' => count($all),
            'cms' => 0,
            'plugins' => 0,
            'themes' => 0,
            'published' => 0,
            'drafts' => 0,
            'paid' => 0,
        ];

        foreach ($all as $item) {
            if (($item['type'] ?? '') === 'cms') {
                $summary['cms']++;
            }

            if (($item['type'] ?? '') === 'plugin') {
                $summary['plugins']++;
            }

            if (($item['type'] ?? '') === 'theme') {
                $summary['themes']++;
            }

            if (!empty($item['is_paid'])) {
                $summary['paid']++;
            }

            if (!empty($item['is_published'])) {
                $summary['published']++;
            } else {
                $summary['drafts']++;
            }
        }

        return $summary;
    }

    public function getLatestPublishedItems(?string $type = null): array
    {
        $types = $type !== null && $type !== ''
            ? [$this->normalizeType($type)]
            : ['cms', 'plugin', 'theme'];

        $items = [];
        foreach ($types as $entryType) {
            if ($entryType === '') {
                continue;
            }

            $published = $this->repository->getPublishedByType($entryType);
            foreach ($this->reduceToLatestVersions($published) as $item) {
                $items[] = $item;
            }
        }

        usort($items, static function (array $a, array $b): int {
            $typeCompare = strcmp((string) ($a['type'] ?? ''), (string) ($b['type'] ?? ''));
            if ($typeCompare !== 0) {
                return $typeCompare;
            }

            return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return $items;
    }

    public function getPublicRouteMap(): array
    {
        $settings = $this->getSettings();
        $publicBase = '/marketplace-public';

        return [
            'overview' => $publicBase,
            'plugins' => $publicBase . '/plugins',
            'themes' => $publicBase . '/themes',
            'cms' => $publicBase . '/cms',
            'submit' => !empty($settings['public_submission_enabled'])
                ? $this->normalizePublicPath((string) ($settings['public_submission_path'] ?? '/marketplace-submit'))
                : '',
            'security_report' => !empty($settings['security_reports_enabled'])
                ? $this->normalizePublicPath((string) ($settings['security_report_path'] ?? '/marketplace-security-report'))
                : '',
        ];
    }

    public function getLocalizedPublicRouteMap(string $lang = 'de'): array
    {
        $routes = $this->getPublicRouteMap();
        $localized = [];
        foreach ($routes as $key => $route) {
            if (!is_string($route) || $route === '') {
                $localized[$key] = '';
                continue;
            }

            $localized[$key] = $this->localizePublicPath($route, $lang);
        }

        return $localized;
    }

    public function resolvePublicSectionFromRequestUri(string $requestUri): ?string
    {
        $path = $this->normalizeComparableRequestPath($requestUri);
        foreach ($this->getPublicRouteMap() as $section => $route) {
            if ($path === $this->normalizeComparableRoutePath($route)) {
                return $section;
            }
        }

        return null;
    }

    public function getPublicSections(string $lang = 'de'): array
    {
        $routes = $this->getLocalizedPublicRouteMap($lang);
        $siteUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
        $summary = $this->getSummary();
        $publicUrls = $this->getPublicUrls();

        $text = [
            'plugins_label' => $this->i18nText('plugins_label', $lang, 'Plugins', 'Plugins'),
            'plugins_description' => $this->i18nText('plugins_description', $lang, 'Verfügbare 365CMS-Plugins mit Installations- und Update-Metadaten.', 'Available 365CMS plugins with install and update metadata.'),
            'themes_label' => $this->i18nText('themes_label', $lang, 'Themes', 'Themes'),
            'themes_description' => $this->i18nText('themes_description', $lang, 'Verfügbare 365CMS-Themes mit Installations- und Update-Metadaten.', 'Available 365CMS themes with install and update metadata.'),
            'cms_label' => $this->i18nText('cms_label', $lang, 'CMS', 'CMS'),
            'cms_description' => $this->i18nText('cms_description', $lang, '365CMS-Core-Pakete, Update-Kanäle und zentrale Update-Metadaten.', '365CMS core packages, update channels, and central update metadata.'),
            'submit_label' => $this->i18nText('submit_label', $lang, 'Einreichung', 'Submission'),
            'submit_description' => $this->i18nText('submit_description', $lang, 'Öffentliche Einreichung für neue Plugins, Themes und CMS-Pakete.', 'Public submission for new plugins, themes, and CMS packages.'),
            'security_label' => $this->i18nText('security_label', $lang, 'Sicherheitsmeldung', 'Security Report'),
            'security_description' => $this->i18nText('security_description', $lang, 'Melde Sicherheitsprobleme zu veröffentlichten Paketen mit separatem Intake-Workflow.', 'Report security issues for published packages through a dedicated intake workflow.'),
        ];

        $sections = [
            [
                'key' => 'plugins',
                'label' => $text['plugins_label'],
                'description' => $text['plugins_description'],
                'count' => (int) ($summary['plugins'] ?? 0),
                'url' => $siteUrl . ($routes['plugins'] ?? '/marketplace/plugins'),
                'feed_url' => $publicUrls['plugins_index'] ?? '',
            ],
            [
                'key' => 'themes',
                'label' => $text['themes_label'],
                'description' => $text['themes_description'],
                'count' => (int) ($summary['themes'] ?? 0),
                'url' => $siteUrl . ($routes['themes'] ?? '/marketplace/themes'),
                'feed_url' => $publicUrls['themes_index'] ?? '',
            ],
            [
                'key' => 'cms',
                'label' => $text['cms_label'],
                'description' => $text['cms_description'],
                'count' => (int) ($summary['cms'] ?? 0),
                'url' => $siteUrl . ($routes['cms'] ?? '/marketplace/cms'),
                'feed_url' => $publicUrls['cms_update'] ?? '',
            ],
        ];

        if (!empty($routes['submit'])) {
            $sections[] = [
                'key' => 'submit',
                'label' => $text['submit_label'],
                'description' => $text['submit_description'],
                'count' => 0,
                'url' => $siteUrl . $routes['submit'],
                'feed_url' => '',
            ];
        }

        if (!empty($routes['security_report'])) {
            $sections[] = [
                'key' => 'security_report',
                'label' => $text['security_label'],
                'description' => $text['security_description'],
                'count' => 0,
                'url' => $siteUrl . $routes['security_report'],
                'feed_url' => '',
            ];
        }

        return $sections;
    }

    public function getPublicOverviewPayload(): array
    {
        return [
            'generated_at' => gmdate('c'),
            'site' => defined('SITE_URL') ? (string) SITE_URL : '',
            'sections' => $this->getPublicSections(),
            'latest' => [
                'cms' => array_map([$this, 'buildPublicCatalogEntry'], $this->getLatestPublishedItems('cms')),
                'plugins' => array_map([$this, 'buildPublicCatalogEntry'], $this->getLatestPublishedItems('plugin')),
                'themes' => array_map([$this, 'buildPublicCatalogEntry'], $this->getLatestPublishedItems('theme')),
            ],
        ];
    }

    public function getPublicSectionEntries(string $section): array
    {
        return match ($section) {
            'plugins' => array_map([$this, 'buildPublicCatalogEntry'], $this->getLatestPublishedItems('plugin')),
            'themes' => array_map([$this, 'buildPublicCatalogEntry'], $this->getLatestPublishedItems('theme')),
            'cms' => array_map([$this, 'buildPublicCatalogEntry'], $this->getLatestPublishedItems('cms')),
            default => [],
        };
    }

    public function getFormDefaults(string $type): array
    {
        $type = $this->normalizeType($type);
        $settings = $this->getSettings();

        return match ($type) {
            'cms' => [
                'type' => 'cms',
                'slug' => (string) ($settings['cms_default_slug'] ?? '365cms'),
                'author' => (string) ($settings['cms_default_author'] ?? '365 Network'),
                'requires_cms' => (string) ($settings['cms_default_requires_cms'] ?? ''),
                'requires_php' => (string) ($settings['cms_default_requires_php'] ?? ''),
                'price_currency' => (string) ($settings['default_currency'] ?? 'EUR'),
            ],
            'theme' => [
                'type' => 'theme',
                'slug' => '',
                'author' => (string) ($settings['theme_default_author'] ?? ''),
                'requires_cms' => (string) ($settings['theme_default_requires_cms'] ?? ''),
                'requires_php' => (string) ($settings['theme_default_requires_php'] ?? ''),
                'price_currency' => (string) ($settings['default_currency'] ?? 'EUR'),
            ],
            default => [
                'type' => 'plugin',
                'slug' => '',
                'author' => (string) ($settings['plugin_default_author'] ?? ''),
                'requires_cms' => (string) ($settings['plugin_default_requires_cms'] ?? ''),
                'requires_php' => (string) ($settings['plugin_default_requires_php'] ?? ''),
                'price_currency' => (string) ($settings['default_currency'] ?? 'EUR'),
            ],
        };
    }

    public function saveItem(array $input, ?array $uploadedFile = null, ?int $id = null, array $options = []): array
    {
        $type = $this->normalizeType((string) ($input['type'] ?? ''));
        $slug = $this->normalizeSlug((string) ($input['slug'] ?? ''));
        $name = $this->sanitizeText((string) ($input['name'] ?? ''), 190);
        $version = $this->normalizeVersion((string) ($input['version'] ?? ''));
        $author = $this->sanitizeText((string) ($input['author'] ?? ''), 190);
        $description = $this->sanitizeTextarea((string) ($input['description'] ?? ''), 1200);
        $category = $this->sanitizeText((string) ($input['category'] ?? ''), 120);
        $requiresCms = $this->sanitizeText((string) ($input['requires_cms'] ?? ''), 30);
        $requiresPhp = $this->sanitizeText((string) ($input['requires_php'] ?? ''), 30);
        $testedUpTo = $this->sanitizeText((string) ($input['tested_up_to'] ?? ''), 30);
        $notes = $this->sanitizeTextarea((string) ($input['notes'] ?? ''), 2000);
        $isPaid = !empty($input['is_paid']);
        $priceAmount = $this->normalizePriceAmount($input['price_amount'] ?? null);
        $priceCurrency = $this->normalizeCurrency((string) ($input['price_currency'] ?? 'EUR'));
        $contactFormSlug = $this->normalizeContactFormSlug((string) ($input['contact_form_slug'] ?? ''));
        $submissionSource = $this->normalizeSubmissionSource((string) ($options['submission_source'] ?? $input['submission_source'] ?? 'admin'));
        $submitterName = $this->sanitizeText((string) ($options['submitter_name'] ?? $input['submitter_name'] ?? ''), 190);
        $submitterEmail = $this->sanitizeEmail((string) ($options['submitter_email'] ?? $input['submitter_email'] ?? ''));

        if ($type === '' || $slug === '' || $name === '' || $version === '') {
            return ['success' => false, 'message' => 'Typ, Slug, Name und Version sind Pflichtfelder.'];
        }

        if ($isPaid) {
            if ($priceAmount === null || (float) $priceAmount <= 0) {
                return ['success' => false, 'message' => 'Für kostenpflichtige Einträge muss ein gültiger Preis angegeben werden.'];
            }

            if ($contactFormSlug === '') {
                return ['success' => false, 'message' => 'Für kostenpflichtige Einträge muss ein Kontaktformular-Slug oder Pfad hinterlegt werden.'];
            }
        }

        if ($submissionSource === 'public') {
            if ($submitterName === '' || $submitterEmail === '') {
                return ['success' => false, 'message' => 'Für öffentliche Einreichungen sind Name und E-Mail erforderlich.'];
            }
        }

        $existing = $id !== null ? $this->repository->findById($id) : null;
        if ($id !== null && $existing === null) {
            return ['success' => false, 'message' => 'Der gewählte Marketplace-Eintrag wurde nicht gefunden.'];
        }

        if ($existing !== null) {
            if (!isset($options['submission_source']) && !array_key_exists('submission_source', $input)) {
                $submissionSource = (string) ($existing['submission_source'] ?? $submissionSource);
            }

            if (!isset($options['submitter_name']) && !array_key_exists('submitter_name', $input)) {
                $submitterName = (string) ($existing['submitter_name'] ?? $submitterName);
            }

            if (!isset($options['submitter_email']) && !array_key_exists('submitter_email', $input)) {
                $submitterEmail = (string) ($existing['submitter_email'] ?? $submitterEmail);
            }
        }

        $duplicate = $this->repository->findByTypeSlugVersion($type, $slug, $version);
        if ($duplicate !== null && $id !== (int) ($duplicate['id'] ?? 0)) {
            return ['success' => false, 'message' => 'Für diesen Typ, Slug und diese Version existiert bereits ein Eintrag.'];
        }

        $packageData = $existing !== null ? [
            'package_file_name' => (string) ($existing['package_file_name'] ?? ''),
            'package_storage_path' => (string) ($existing['package_storage_path'] ?? ''),
            'package_sha256' => (string) ($existing['package_sha256'] ?? ''),
            'package_size' => (int) ($existing['package_size'] ?? 0),
        ] : [
            'package_file_name' => '',
            'package_storage_path' => '',
            'package_sha256' => '',
            'package_size' => 0,
        ];

        if ($this->hasUploadedFile($uploadedFile)) {
            $packageResult = $this->storePackage($type, $slug, $version, $uploadedFile);
            if (($packageResult['success'] ?? false) !== true) {
                return $packageResult;
            }

            $packageData = [
                'package_file_name' => (string) ($packageResult['package_file_name'] ?? ''),
                'package_storage_path' => (string) ($packageResult['package_storage_path'] ?? ''),
                'package_sha256' => (string) ($packageResult['package_sha256'] ?? ''),
                'package_size' => (int) ($packageResult['package_size'] ?? 0),
            ];
        } elseif (!$isPaid && $existing === null) {
            return ['success' => false, 'message' => 'Für neue Einträge muss eine ZIP-Datei hochgeladen werden.'];
        } elseif (!$isPaid && $existing !== null && empty($packageData['package_file_name'])) {
            return ['success' => false, 'message' => 'Für nicht-kostenpflichtige Einträge ist ein Paket erforderlich.'];
        }

        $isPublished = !empty($input['is_published']) && empty($options['force_unpublished']);
        $releasedOn = trim((string) ($input['released_on'] ?? ''));
        if ($releasedOn === '' && $isPublished) {
            $releasedOn = date('Y-m-d');
        }

        if ($isPublished) {
            $guardrailCandidate = [
                'type' => $type,
                'slug' => $slug,
                'name' => $name,
                'version' => $version,
                'docs_url' => $this->sanitizeUrl((string) ($input['docs_url'] ?? '')),
                'changelog_url' => $this->sanitizeUrl((string) ($input['changelog_url'] ?? '')),
                'package_sha256' => (string) ($packageData['package_sha256'] ?? ''),
                'created_at' => (string) ($existing['created_at'] ?? ''),
            ];
            $guardrailViolations = $this->getPublishGuardrailViolations($guardrailCandidate);
            if ($guardrailViolations !== []) {
                return ['success' => false, 'message' => 'Freigabe blockiert: ' . implode(' ', $guardrailViolations)];
            }
        }

        $saveId = $this->repository->save([
            'type' => $type,
            'slug' => $slug,
            'name' => $name,
            'version' => $version,
            'author' => $author,
            'description' => $description,
            'category' => $category,
            'homepage_url' => $this->sanitizeUrl((string) ($input['homepage_url'] ?? '')),
            'docs_url' => $this->sanitizeUrl((string) ($input['docs_url'] ?? '')),
            'changelog_url' => $this->sanitizeUrl((string) ($input['changelog_url'] ?? '')),
            'icon_url' => $this->sanitizeUrl((string) ($input['icon_url'] ?? '')),
            'screenshot_url' => $this->sanitizeUrl((string) ($input['screenshot_url'] ?? '')),
            'requires_cms' => $requiresCms,
            'requires_php' => $requiresPhp,
            'tested_up_to' => $testedUpTo,
            'notes' => $notes,
            'released_on' => $releasedOn,
            'is_paid' => $isPaid,
            'price_amount' => $isPaid ? $priceAmount : null,
            'price_currency' => $isPaid ? $priceCurrency : 'EUR',
            'contact_form_slug' => $isPaid ? $contactFormSlug : '',
            'submission_source' => $submissionSource,
            'submitter_name' => $submitterName,
            'submitter_email' => $submitterEmail,
            'is_published' => $isPublished,
            'package_file_name' => $packageData['package_file_name'],
            'package_storage_path' => $packageData['package_storage_path'],
            'package_sha256' => $packageData['package_sha256'],
            'package_size' => $packageData['package_size'],
        ], $id);

        if ($saveId === false) {
            return ['success' => false, 'message' => 'Der Marketplace-Eintrag konnte nicht gespeichert werden.'];
        }

        $this->syncPublicCatalogs();

        return ['success' => true, 'message' => 'Marketplace-Eintrag wurde gespeichert.', 'id' => (int) $saveId];
    }

    public function submitPublicItem(array $input, ?array $uploadedFile = null): array
    {
        return $this->saveItem($input, $uploadedFile, null, [
            'force_unpublished' => true,
            'submission_source' => 'public',
            'submitter_name' => trim((string) ($input['submitter_name'] ?? '')),
            'submitter_email' => $this->sanitizeEmail((string) ($input['submitter_email'] ?? '')),
        ]);
    }

    public function togglePublished(int $id, bool $published): array
    {
        $item = $this->repository->findById($id);
        if ($item === null) {
            return ['success' => false, 'message' => 'Der Marketplace-Eintrag wurde nicht gefunden.'];
        }

        if ($published) {
            $guardrailViolations = $this->getPublishGuardrailViolations($item);
            if ($guardrailViolations !== []) {
                return ['success' => false, 'message' => 'Freigabe blockiert: ' . implode(' ', $guardrailViolations)];
            }
        }

        $result = $this->repository->setPublished($id, $published);
        if (!$result) {
            return ['success' => false, 'message' => 'Der Veröffentlichungsstatus konnte nicht geändert werden.'];
        }

        $this->syncPublicCatalogs();

        return [
            'success' => true,
            'message' => $published ? 'Eintrag wurde für den Marketplace freigegeben.' : 'Eintrag wurde aus dem Marketplace zurückgezogen.',
        ];
    }

    public function getPublishGuardrailViolations(array $item): array
    {
        $settings = $this->getSettings();
        if (empty($settings['publish_guardrails_enabled'])) {
            return [];
        }

        $violations = [];
        if (!empty($settings['guardrail_require_docs_url']) && (string) ($item['docs_url'] ?? '') === '') {
            $violations[] = 'Dokumentations-URL ist erforderlich.';
        }

        if (!empty($settings['guardrail_require_changelog_url']) && (string) ($item['changelog_url'] ?? '') === '') {
            $violations[] = 'Changelog-URL ist erforderlich.';
        }

        if (!empty($settings['guardrail_require_checksum']) && (string) ($item['package_sha256'] ?? '') === '') {
            $violations[] = 'SHA-256-Prüfsumme ist erforderlich.';
        }

        $minimumAgeHours = max(0, (int) ($settings['guardrail_min_age_hours'] ?? 0));
        if ($minimumAgeHours > 0) {
            $createdAt = trim((string) ($item['created_at'] ?? ''));
            $createdTimestamp = $createdAt !== '' ? strtotime($createdAt) : false;
            if (!is_int($createdTimestamp) || $createdTimestamp <= 0) {
                $violations[] = 'Mindestalter-Regel konnte nicht geprüft werden (fehlendes Erstellungsdatum).';
            } else {
                $ageHours = (int) floor(max(0, time() - $createdTimestamp) / 3600);
                if ($ageHours < $minimumAgeHours) {
                    $violations[] = 'Eintrag muss mindestens ' . $minimumAgeHours . ' Stunde(n) alt sein.';
                }
            }
        }

        return $violations;
    }

    public function syncPublicCatalogs(): void
    {
        if (!$this->shouldSyncPublicCatalogs()) {
            return;
        }

        $this->ensureStorageDirectories();

        foreach (['cms', 'plugin', 'theme'] as $type) {
            $published = $this->repository->getPublishedByType($type);
            $latestEntries = $this->reduceToLatestVersions($published);
            $publicItems = [];
            $activeSlugs = [];

            foreach ($latestEntries as $item) {
                $publicItems[] = $this->buildPublicCatalogEntry($item);
                $activeSlugs[] = (string) ($item['slug'] ?? '');
                $this->writeItemMetadataFiles($item);
            }

            $this->cleanupTypeMetadata($type, $activeSlugs);
            $this->writeTypeIndex($type, $publicItems);

            if ($type === 'cms') {
                $latestCms = $publicItems[0] ?? [];
                if ($latestCms !== []) {
                    $this->writeJsonFile($this->getTypeBasePath('cms') . DIRECTORY_SEPARATOR . 'update.json', $latestCms);
                    $this->writeJsonFile($this->getTypeBasePath('cms') . DIRECTORY_SEPARATOR . 'manifest.json', $latestCms);
                }
            }
        }

        $this->writeJsonFile($this->getStorageBasePath() . DIRECTORY_SEPARATOR . 'index.json', $this->getPublicOverviewPayload());

        $stateDigest = $this->repository->getStateDigest();
        $this->lastSyncedStateDigest = $stateDigest;
        $this->writeSyncStateDigest($stateDigest);
    }

    public function getPublicUrls(): array
    {
        return [
            'plugins_index' => $this->getTypeBaseUrl('plugin') . '/index.json',
            'themes_index' => $this->getTypeBaseUrl('theme') . '/index.json',
            'plugins_root' => $this->getTypeBaseUrl('plugin'),
            'themes_root' => $this->getTypeBaseUrl('theme'),
            'cms_root' => $this->getStorageBaseUrl() . '/core/365cms',
            'cms_update' => $this->getStorageBaseUrl() . '/core/365cms/update.json',
            'submit' => $this->getPublicSubmissionUrl(),
            'security_report' => $this->getPublicSecurityReportUrl(),
        ];
    }

    public function getPublicSubmissionUrl(): string
    {
        $settings = $this->getSettings();
        if (empty($settings['public_submission_enabled'])) {
            return '';
        }

        $siteUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
        $path = $this->normalizePublicPath((string) ($settings['public_submission_path'] ?? '/marketplace-submit'));
        return $siteUrl . $this->localizePublicPath($path);
    }

    public function getPublicSecurityReportUrl(array $query = [], ?string $lang = null): string
    {
        $settings = $this->getSettings();
        if (empty($settings['security_reports_enabled'])) {
            return '';
        }

        $siteUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
        $path = $this->normalizePublicPath((string) ($settings['security_report_path'] ?? '/marketplace-security-report'));
        $url = $siteUrl . $this->localizePublicPath($path, $lang);
        if ($query === []) {
            return $url;
        }

        $queryString = http_build_query($query);
        return $queryString !== '' ? ($url . '?' . $queryString) : $url;
    }

    public function getPublicSubmissionPaths(): array
    {
        $settings = $this->getSettings();
        if (empty($settings['public_submission_enabled'])) {
            return [];
        }

        $paths = [
            $this->normalizePublicPath((string) ($settings['public_submission_path'] ?? '/marketplace-submit')),
            '/marketplace/einreichen',
            '/marketplace/submit',
        ];

        $paths = array_values(array_unique(array_filter($paths, static fn (string $path): bool => $path !== '')));
        return $paths;
    }

    public function getSettings(): array
    {
        if ($this->settingsCache !== null) {
            return $this->settingsCache;
        }

        $defaults = $this->getDefaultSettings();
        $file = $this->getSettingsFilePath();
        if (!is_file($file)) {
            $this->settingsCache = $defaults;
            return $this->settingsCache;
        }

        $size = @filesize($file);
        if (!is_int($size) || $size < 0 || $size > 65536) {
            $this->settingsCache = $defaults;
            return $this->settingsCache;
        }

        $raw = file_get_contents($file);
        if (!is_string($raw) || trim($raw) === '') {
            $this->settingsCache = $defaults;
            return $this->settingsCache;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $this->settingsCache = $defaults;
            return $this->settingsCache;
        }

        $this->settingsCache = $this->normalizeSettings(array_merge($defaults, $decoded));
        return $this->settingsCache;
    }

    public function saveSettings(array $input): array
    {
        $settings = $this->normalizeSettings($input);
        $file = $this->getSettingsFilePath();
        $dir = dirname($file);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return ['success' => false, 'message' => 'Das Verzeichnis für die Marketplace-Einstellungen konnte nicht erstellt werden.'];
        }

        $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($json) || file_put_contents($file, $json . PHP_EOL, LOCK_EX) === false) {
            return ['success' => false, 'message' => 'Die Marketplace-Einstellungen konnten nicht gespeichert werden.'];
        }

        $this->settingsCache = $settings;

        return ['success' => true, 'message' => 'Die Marketplace-Einstellungen wurden gespeichert.', 'settings' => $settings];
    }

    public function getDirectorySnapshot(string $scope = 'all'): array
    {
        $scope = strtolower(trim($scope));
        $scope = in_array($scope, ['all', 'cms', 'plugin', 'theme'], true) ? $scope : 'all';
        $settings = $this->getSettings();
        $maxDepth = max(1, min(6, (int) ($settings['directory_view_depth'] ?? 3)));
        $rootPath = $this->getDirectoryRootPath($scope);
        $rootUrl = $this->getDirectoryRootUrl($scope);

        return [
            'scope' => $scope,
            'root_path' => $rootPath,
            'root_url' => $rootUrl,
            'exists' => is_dir($rootPath),
            'max_depth' => $maxDepth,
            'entries' => $this->collectDirectoryEntries($rootPath, '', 0, $maxDepth),
        ];
    }

    public function getDirectoryEntryDetails(string $scope, string $relativePath): array
    {
        $scope = in_array($scope, ['all', 'cms', 'plugin', 'theme'], true) ? $scope : 'all';
        $relativePath = $this->normalizeDirectoryRelativePath($relativePath);
        $rootPath = $this->getDirectoryRootPath($scope);
        $rootUrl = $this->getDirectoryRootUrl($scope);

        if ($relativePath === '') {
            return [
                'exists' => is_dir($rootPath),
                'type' => 'dir',
                'name' => basename($rootPath),
                'relative_path' => '',
                'absolute_path' => $rootPath,
                'public_url' => $rootUrl,
                'preview' => '',
                'sha256' => '',
                'size' => 0,
                'modified_at' => is_dir($rootPath) ? date('Y-m-d H:i', $this->safeFileMTime($rootPath)) : '',
            ];
        }

        $absolutePath = $rootPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $resolvedPath = $this->resolveContainedExistingPath($rootPath, $relativePath);
        if ($resolvedPath === null) {
            return [
                'exists' => false,
                'type' => '',
                'name' => basename($relativePath),
                'relative_path' => $relativePath,
                'absolute_path' => $absolutePath,
                'public_url' => $this->buildDirectoryPublicUrl($rootUrl, $relativePath),
                'preview' => '',
                'sha256' => '',
                'size' => 0,
                'modified_at' => '',
            ];
        }

        $absolutePath = $resolvedPath;
        $isFile = is_file($absolutePath);
        return [
            'exists' => true,
            'type' => $isFile ? 'file' : 'dir',
            'name' => basename($absolutePath),
            'relative_path' => $relativePath,
            'absolute_path' => $absolutePath,
            'public_url' => $this->buildDirectoryPublicUrl($rootUrl, $relativePath),
            'preview' => $isFile ? $this->buildFilePreview($absolutePath) : '',
            'sha256' => $isFile ? (string) (hash_file('sha256', $absolutePath) ?: '') : '',
            'size' => $isFile ? $this->safeFileSize($absolutePath) : 0,
            'modified_at' => date('Y-m-d H:i', $this->safeFileMTime($absolutePath)),
        ];
    }

    public function getSecurityReports(): array
    {
        $reports = $this->readSecurityReports();
        usort($reports, static function (array $a, array $b): int {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        });

        return $reports;
    }

    public function submitSecurityReport(array $input, string $lang = 'de'): array
    {
        $type = $this->normalizeType((string) ($input['type'] ?? ''));
        $slug = $this->normalizeSlug((string) ($input['slug'] ?? ''));
        $version = $this->normalizeVersion((string) ($input['version'] ?? ''));
        $title = $this->sanitizeText((string) ($input['title'] ?? ''), 190);
        $details = $this->sanitizeTextarea((string) ($input['details'] ?? ''), 4000);
        $reporterName = $this->sanitizeText((string) ($input['reporter_name'] ?? ''), 190);
        $reporterEmail = $this->sanitizeEmail((string) ($input['reporter_email'] ?? ''));

        if ($type === '' || $slug === '' || $version === '' || $title === '' || $details === '' || $reporterName === '' || $reporterEmail === '') {
            return ['success' => false, 'message' => $this->i18nText('security_required_fields', $lang, 'Bitte fülle alle Pflichtfelder aus.', 'Please fill out all required fields.')];
        }

        $item = $this->repository->findByTypeSlugVersion($type, $slug, $version);
        if ($item === null || empty($item['is_published'])) {
            return ['success' => false, 'message' => $this->i18nText('security_item_not_found', $lang, 'Für Typ, Slug und Version wurde kein veröffentlichtes Paket gefunden.', 'No published package was found for type, slug, and version.')];
        }

        $reports = $this->readSecurityReports();
        $nextId = 1;
        foreach ($reports as $report) {
            $nextId = max($nextId, (int) ($report['id'] ?? 0) + 1);
        }

        $now = gmdate('c');
        $reports[] = [
            'id' => $nextId,
            'created_at' => $now,
            'updated_at' => $now,
            'status' => 'new',
            'status_note' => '',
            'lang' => $lang === 'en' ? 'en' : 'de',
            'type' => $type,
            'slug' => $slug,
            'version' => $version,
            'title' => $title,
            'details' => $details,
            'reporter_name' => $reporterName,
            'reporter_email' => $reporterEmail,
            'item_name' => (string) ($item['name'] ?? ''),
        ];

        if (!$this->writeSecurityReports($reports)) {
            return ['success' => false, 'message' => $this->i18nText('security_save_failed', $lang, 'Die Sicherheitsmeldung konnte nicht gespeichert werden.', 'The security report could not be saved.')];
        }

        return ['success' => true, 'message' => $this->i18nText('security_saved', $lang, 'Danke. Die Sicherheitsmeldung wurde übermittelt und wird intern geprüft.', 'Thank you. The security report was submitted and will be reviewed internally.')];
    }

    public function updateSecurityReportStatus(int $reportId, string $status, string $statusNote = ''): array
    {
        $allowedStatuses = ['new', 'triaged', 'resolved', 'rejected'];
        if (!in_array($status, $allowedStatuses, true)) {
            return ['success' => false, 'message' => 'Ungültiger Sicherheitsstatus.'];
        }

        $reports = $this->readSecurityReports();
        $updated = false;
        foreach ($reports as &$report) {
            if ((int) ($report['id'] ?? 0) !== $reportId) {
                continue;
            }

            $report['status'] = $status;
            $report['status_note'] = $this->sanitizeTextarea($statusNote, 500);
            $report['updated_at'] = gmdate('c');
            $updated = true;
            break;
        }
        unset($report);

        if (!$updated) {
            return ['success' => false, 'message' => 'Sicherheitsmeldung wurde nicht gefunden.'];
        }

        if (!$this->writeSecurityReports($reports)) {
            return ['success' => false, 'message' => 'Sicherheitsstatus konnte nicht gespeichert werden.'];
        }

        return ['success' => true, 'message' => 'Sicherheitsstatus wurde aktualisiert.'];
    }

    public function getPublicEntryUrls(array $item): array
    {
        $type = $this->normalizeType((string) ($item['type'] ?? ''));
        $slug = $this->normalizeSlug((string) ($item['slug'] ?? ''));
        if ($type === '' || $slug === '') {
            return [];
        }

        if ($type === 'cms') {
            $cmsBaseUrl = $this->getTypeBaseUrl('cms');

            return [
                'base' => $cmsBaseUrl,
                'manifest' => $cmsBaseUrl . '/manifest.json',
                'update' => $cmsBaseUrl . '/update.json',
                'download' => !empty($item['is_paid']) ? '' : (!empty($item['package_file_name']) ? $cmsBaseUrl . '/' . rawurlencode((string) $item['package_file_name']) : ''),
                'purchase' => !empty($item['is_paid']) ? $this->buildContactFormUrl((string) ($item['contact_form_slug'] ?? '')) : '',
            ];
        }

        $baseUrl = $this->getTypeBaseUrl($type) . '/' . rawurlencode($slug);

        return [
            'base' => $baseUrl,
            'manifest' => $baseUrl . '/manifest.json',
            'update' => $baseUrl . '/update.json',
            'download' => !empty($item['is_paid']) ? '' : (!empty($item['package_file_name']) ? $baseUrl . '/' . rawurlencode((string) $item['package_file_name']) : ''),
            'purchase' => !empty($item['is_paid']) ? $this->buildContactFormUrl((string) ($item['contact_form_slug'] ?? '')) : '',
        ];
    }

    private function storePackage(string $type, string $slug, string $version, ?array $uploadedFile): array
    {
        if ($uploadedFile === null) {
            return ['success' => false, 'message' => 'Es wurde keine Paketdatei übergeben.'];
        }

        $errorCode = (int) ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => $this->getUploadErrorMessage($errorCode)];
        }

        $tmpName = (string) ($uploadedFile['tmp_name'] ?? '');
        if ($tmpName === '' || !is_file($tmpName) || !$this->isAcceptableUploadedFile($tmpName)) {
            return ['success' => false, 'message' => 'Die hochgeladene Paketdatei ist ungültig.'];
        }

        $size = (int) ($uploadedFile['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_PACKAGE_SIZE) {
            return ['success' => false, 'message' => 'ZIP-Datei ist leer oder überschreitet 50 MB.'];
        }

        $originalName = (string) ($uploadedFile['name'] ?? 'package.zip');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'zip') {
            return ['success' => false, 'message' => 'Es sind nur ZIP-Pakete erlaubt.'];
        }

        if (!$this->hasZipSignature($tmpName) || !$this->hasAllowedZipMimeType($tmpName)) {
            return ['success' => false, 'message' => 'Die Paketdatei ist keine gültige ZIP-Datei.'];
        }

        if (!class_exists(\ZipArchive::class)) {
            return ['success' => false, 'message' => 'Die PHP-Erweiterung ZipArchive ist für den Marketplace erforderlich.'];
        }

        $zip = new \ZipArchive();
        if ($zip->open($tmpName) !== true) {
            return ['success' => false, 'message' => 'Die hochgeladene ZIP-Datei konnte nicht geöffnet werden.'];
        }

        $validZip = $this->validateZipEntries($zip, $slug);
        $zip->close();

        if (!$validZip) {
            return ['success' => false, 'message' => 'Das Paket enthält unsichere Pfade oder keinen passenden Root-Ordner.'];
        }

        $targetDir = $this->getItemDirectoryPath($type, $slug);
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            return ['success' => false, 'message' => 'Zielverzeichnis für das Paket konnte nicht erstellt werden.'];
        }

        if (!$this->isContainedPath($targetDir, $this->getStorageBasePath())) {
            return ['success' => false, 'message' => 'Zielverzeichnis für das Paket ist ungültig.'];
        }

        $targetFileName = $slug . '-' . $version . '.zip';
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $targetFileName;

        if (!$this->isContainedPath($targetPath, $this->getStorageBasePath(), false)) {
            return ['success' => false, 'message' => 'Zielpfad für das Paket ist ungültig.'];
        }

        if (!$this->moveUploadedFile($tmpName, $targetPath)) {
            return ['success' => false, 'message' => 'Das Paket konnte nicht in das Marketplace-Verzeichnis verschoben werden.'];
        }

        $sha256 = hash_file('sha256', $targetPath);
        if (!is_string($sha256) || $sha256 === '') {
            return ['success' => false, 'message' => 'Die SHA-256-Prüfsumme des Pakets konnte nicht berechnet werden.'];
        }

        return [
            'success' => true,
            'package_file_name' => $targetFileName,
            'package_storage_path' => $targetPath,
            'package_sha256' => strtolower($sha256),
            'package_size' => (int) filesize($targetPath),
        ];
    }

    private function reduceToLatestVersions(array $items): array
    {
        $latestBySlug = [];

        foreach ($items as $item) {
            $slug = (string) ($item['slug'] ?? '');
            if ($slug === '') {
                continue;
            }

            if (!isset($latestBySlug[$slug])) {
                $latestBySlug[$slug] = $item;
                continue;
            }

            $currentVersion = (string) ($latestBySlug[$slug]['version'] ?? '0.0.0');
            $candidateVersion = (string) ($item['version'] ?? '0.0.0');
            if (version_compare($candidateVersion, $currentVersion, '>')) {
                $latestBySlug[$slug] = $item;
            }
        }

        ksort($latestBySlug);
        return array_values($latestBySlug);
    }

    private function buildPublicCatalogEntry(array $item): array
    {
        $urls = $this->getPublicEntryUrls($item);
        $isPaid = !empty($item['is_paid']);
        $priceAmount = $isPaid ? $this->normalizePriceAmount($item['price_amount'] ?? null) : null;
        $priceCurrency = $isPaid ? $this->normalizeCurrency((string) ($item['price_currency'] ?? 'EUR')) : 'EUR';
        $priceLabel = $isPaid && $priceAmount !== null ? $this->formatPriceLabel($priceAmount, $priceCurrency) : '';
        $securityReportUrl = $this->getPublicSecurityReportUrl([
            'type' => (string) ($item['type'] ?? ''),
            'slug' => (string) ($item['slug'] ?? ''),
            'version' => (string) ($item['version'] ?? ''),
        ]);

        return [
            'slug' => (string) ($item['slug'] ?? ''),
            'name' => (string) ($item['name'] ?? ''),
            'type' => (string) ($item['type'] ?? ''),
            'version' => (string) ($item['version'] ?? ''),
            'author' => (string) ($item['author'] ?? ''),
            'description' => (string) ($item['description'] ?? ''),
            'category' => (string) ($item['category'] ?? ''),
            'download_url' => (string) ($urls['download'] ?? ''),
            'package_url' => (string) ($urls['download'] ?? ''),
            'manifest' => (string) ($urls['manifest'] ?? ''),
            'update_url' => (string) ($urls['update'] ?? ''),
            'purchase_url' => (string) ($urls['purchase'] ?? ''),
            'security_report_url' => $securityReportUrl,
            'security_disclosure_url' => $securityReportUrl,
            'is_paid' => $isPaid,
            'is_commercial' => $isPaid,
            'price_amount' => $priceAmount,
            'price_currency' => $isPaid ? $priceCurrency : '',
            'price' => $priceLabel,
            'contact_form_slug' => $isPaid ? (string) ($item['contact_form_slug'] ?? '') : '',
            'purchase_type' => $isPaid ? 'contact_form' : 'download',
            'sha256' => (string) ($item['package_sha256'] ?? ''),
            'checksum_sha256' => (string) ($item['package_sha256'] ?? ''),
            'package_size' => (int) ($item['package_size'] ?? 0),
            'homepage_url' => (string) ($item['homepage_url'] ?? ''),
            'docs_url' => (string) ($item['docs_url'] ?? ''),
            'changelog_url' => (string) ($item['changelog_url'] ?? ''),
            'icon_url' => (string) ($item['icon_url'] ?? ''),
            'screenshot' => (string) ($item['screenshot_url'] ?? ''),
            'requires_cms' => (string) ($item['requires_cms'] ?? ''),
            'min_cms_version' => (string) ($item['requires_cms'] ?? ''),
            'requires_php' => (string) ($item['requires_php'] ?? ''),
            'min_php' => (string) ($item['requires_php'] ?? ''),
            'tested_up_to' => (string) ($item['tested_up_to'] ?? ''),
            'released' => (string) ($item['released_on'] ?? ''),
            'notes' => (string) ($item['notes'] ?? ''),
            'submission_source' => (string) ($item['submission_source'] ?? 'admin'),
        ];
    }

    private function writeItemMetadataFiles(array $item): void
    {
        $type = $this->normalizeType((string) ($item['type'] ?? ''));
        $slug = $this->normalizeSlug((string) ($item['slug'] ?? ''));
        if ($type === '' || $slug === '') {
            return;
        }

        $dir = $this->getItemDirectoryPath($type, $slug);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return;
        }

        $entry = $this->buildPublicCatalogEntry($item);
        $manifest = array_merge($entry, [
            'id' => (int) ($item['id'] ?? 0),
            'published_at' => (string) ($item['published_at'] ?? ''),
            'created_at' => (string) ($item['created_at'] ?? ''),
            'updated_at' => (string) ($item['updated_at'] ?? ''),
        ]);

        $this->writeJsonFile($dir . DIRECTORY_SEPARATOR . 'manifest.json', $manifest);
        $this->writeJsonFile($dir . DIRECTORY_SEPARATOR . 'update.json', $manifest);
    }

    private function writeTypeIndex(string $type, array $entries): void
    {
        $key = $this->getPayloadCollectionKey($type);
        $baseUrl = $this->getTypeBaseUrl($type);
        $payload = [
            'generated_at' => gmdate('c'),
            'site' => defined('SITE_URL') ? (string) SITE_URL : '',
            'base_url' => $baseUrl,
            $key => $entries,
        ];

        $this->writeJsonFile($this->getTypeBasePath($type) . DIRECTORY_SEPARATOR . 'index.json', $payload);
    }

    private function cleanupTypeMetadata(string $type, array $activeSlugs): void
    {
        $basePath = $this->getTypeBasePath($type);
        if (!is_dir($basePath)) {
            return;
        }

        if ($type === 'cms') {
            return;
        }

        $activeLookup = array_fill_keys($activeSlugs, true);

        foreach (new \DirectoryIterator($basePath) as $item) {
            if ($item->isDot() || !$item->isDir()) {
                continue;
            }

            $slug = $item->getFilename();
            if (isset($activeLookup[$slug])) {
                continue;
            }

            $dir = $item->getPathname();
            foreach (['manifest.json', 'update.json'] as $fileName) {
                $filePath = $dir . DIRECTORY_SEPARATOR . $fileName;
                if (is_file($filePath)) {
                    $this->safeUnlink($filePath);
                }
            }
        }
    }

    private function ensureStorageDirectories(): void
    {
        foreach ([$this->getStorageBasePath(), $this->getTypeBasePath('cms'), $this->getTypeBasePath('plugin'), $this->getTypeBasePath('theme')] as $path) {
            if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
                $this->logError('Failed creating storage directory: ' . $path);
            }
        }
    }

    private function getStorageBasePath(): string
    {
        return rtrim((string) ABSPATH, '/\\') . DIRECTORY_SEPARATOR . 'marketplace';
    }

    private function getStorageBaseUrl(): string
    {
        $siteUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
        return $siteUrl . '/marketplace';
    }

    private function getTypeBasePath(string $type): string
    {
        return match ($type) {
            'cms' => $this->getStorageBasePath() . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . '365cms',
            default => $this->getStorageBasePath() . DIRECTORY_SEPARATOR . $this->getCollectionKey($type),
        };
    }

    private function getTypeBaseUrl(string $type): string
    {
        return match ($type) {
            'cms' => $this->getStorageBaseUrl() . '/core/365cms',
            default => $this->getStorageBaseUrl() . '/' . $this->getCollectionKey($type),
        };
    }

    private function getItemDirectoryPath(string $type, string $slug): string
    {
        if ($type === 'cms') {
            return $this->getTypeBasePath('cms');
        }

        return $this->getTypeBasePath($type) . DIRECTORY_SEPARATOR . $slug;
    }

    private function getCollectionKey(string $type): string
    {
        return $type === 'theme' ? 'themes' : 'plugins';
    }

    private function getPayloadCollectionKey(string $type): string
    {
        return match ($type) {
            'cms' => 'packages',
            'theme' => 'themes',
            default => 'plugins',
        };
    }

    private function hasUploadedFile(?array $uploadedFile): bool
    {
        return is_array($uploadedFile)
            && (int) ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
            && !empty($uploadedFile['tmp_name']);
    }

    private function moveUploadedFile(string $source, string $target): bool
    {
        if (!$this->isContainedPath($target, $this->getStorageBasePath(), false)) {
            return false;
        }

        if (is_file($target)) {
            if (!$this->safeUnlink($target)) {
                return false;
            }
        }

        if (is_uploaded_file($source)) {
            return move_uploaded_file($source, $target);
        }

        if (PHP_SAPI !== 'cli') {
            return false;
        }

        if (rename($source, $target)) {
            return true;
        }

        if (!copy($source, $target)) {
            return false;
        }

        $this->safeUnlink($source);
        return true;
    }

    private function writeJsonFile(string $path, array $payload): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            $this->logError('Failed creating JSON target directory: ' . $directory);
            return;
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($json)) {
            $this->logError('Failed encoding JSON payload for path: ' . $path);
            return;
        }

        if (file_put_contents($path, $json . PHP_EOL, LOCK_EX) === false) {
            $this->logError('Failed writing JSON file: ' . $path);
        }
    }

    private function validateZipEntries(\ZipArchive $zip, string $expectedSlug): bool
    {
        $expectedSlug = trim($expectedSlug, '/\\');
        if ($expectedSlug === '') {
            return false;
        }

        if ($zip->numFiles < 1 || $zip->numFiles > self::MAX_ZIP_ENTRIES) {
            return false;
        }

        $hasFileEntries = false;
        $totalUncompressedSize = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entryName = $zip->getNameIndex($index);
            if (!is_string($entryName) || $entryName === '') {
                return false;
            }

            if (strlen($entryName) > self::MAX_ZIP_ENTRY_NAME_LENGTH || str_contains($entryName, "\0")) {
                return false;
            }

            if ($entryName[0] === '/' || $entryName[0] === '\\') {
                return false;
            }

            $normalized = str_replace('\\', '/', $entryName);
            $normalized = ltrim($normalized, '/');
            if ($normalized === ''
                || str_contains($normalized, '../')
                || str_contains($normalized, '..\\')
                || preg_match('~^[A-Za-z]:/~', $normalized) === 1
            ) {
                return false;
            }

            $segments = array_values(array_filter(explode('/', rtrim($normalized, '/')), static fn (string $segment): bool => $segment !== ''));
            if ($segments === []) {
                continue;
            }

            foreach ($segments as $segment) {
                if ($segment === '.' || $segment === '..') {
                    return false;
                }
            }

            if ($segments[0] !== $expectedSlug) {
                return false;
            }

            if ($this->zipEntryIsSymlink($zip, $index)) {
                return false;
            }

            $entryStats = $zip->statIndex($index);
            if (!is_array($entryStats)) {
                return false;
            }

            $isDirectory = str_ends_with($normalized, '/');
            if (!$isDirectory) {
                $entrySize = max(0, (int) ($entryStats['size'] ?? 0));
                $totalUncompressedSize += $entrySize;
                if ($totalUncompressedSize > self::MAX_UNCOMPRESSED_PACKAGE_SIZE) {
                    return false;
                }

                $hasFileEntries = true;
            }
        }

        return $hasFileEntries;
    }

    private function isAcceptableUploadedFile(string $tmpName): bool
    {
        if (is_uploaded_file($tmpName)) {
            return true;
        }

        return PHP_SAPI === 'cli' && is_file($tmpName);
    }

    private function hasZipSignature(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if (!is_resource($handle)) {
            return false;
        }

        $signature = (string) fread($handle, 4);
        fclose($handle);

        return in_array($signature, ["PK\x03\x04", "PK\x05\x06", "PK\x07\x08"], true);
    }

    private function hasAllowedZipMimeType(string $path): bool
    {
        if (!class_exists(\finfo::class)) {
            return true;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($path);
        return in_array($mime, ['application/zip', 'application/x-zip', 'application/x-zip-compressed', 'application/octet-stream'], true);
    }

    private function zipEntryIsSymlink(\ZipArchive $zip, int $index): bool
    {
        if (!method_exists($zip, 'getExternalAttributesIndex')) {
            return false;
        }

        $opsys = 0;
        $attributes = 0;
        if (!$zip->getExternalAttributesIndex($index, $opsys, $attributes)) {
            return false;
        }

        if ($opsys !== 3) {
            return false;
        }

        $mode = ($attributes >> 16) & 0170000;
        return $mode === 0120000;
    }

    private function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Die hochgeladene Datei ist zu groß.',
            UPLOAD_ERR_PARTIAL => 'Die Datei wurde nur teilweise hochgeladen.',
            UPLOAD_ERR_NO_TMP_DIR => 'Temporäres Upload-Verzeichnis fehlt.',
            UPLOAD_ERR_CANT_WRITE => 'Die hochgeladene Datei konnte nicht geschrieben werden.',
            UPLOAD_ERR_EXTENSION => 'Der Upload wurde durch eine PHP-Erweiterung gestoppt.',
            default => 'Es wurde keine gültige ZIP-Datei hochgeladen.',
        };
    }

    private function sanitizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (strlen($url) > 2048 || preg_match('/[[:cntrl:]]/', $url) === 1 || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return '';
        }
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            return '';
        }

        if (!empty($parts['user']) || !empty($parts['pass']) || !$this->isPublicUrlHost($host)) {
            return '';
        }

        return $url;
    }

    private function isPublicUrlHost(string $host): bool
    {
        $host = strtolower(trim($host, " \t\n\r\0\x0B[]"));
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }

        return preg_match('/^[a-z0-9.-]+$/', $host) === 1;
    }

    private function sanitizeEmail(string $value): string
    {
        $value = strtolower(trim($value));
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : '';
    }

    private function buildContactFormUrl(string $contactFormSlug): string
    {
        $contactFormSlug = trim($contactFormSlug);
        if ($contactFormSlug === '') {
            return '';
        }

        if (filter_var($contactFormSlug, FILTER_VALIDATE_URL)) {
            return $this->sanitizeUrl($contactFormSlug);
        }

        $contactFormSlug = $this->normalizeRelativePublicPath($contactFormSlug);
        if ($contactFormSlug === '') {
            return '';
        }

        $settings = $this->getSettings();
        $siteUrl = trim((string) ($settings['contact_form_base_url'] ?? ''));
        if ($siteUrl === '') {
            $siteUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
        }
        $siteUrl = rtrim($siteUrl, '/');
        if ($siteUrl === '') {
            return '/' . ltrim($contactFormSlug, '/');
        }

        return $siteUrl . '/' . ltrim($contactFormSlug, '/');
    }

    private function normalizeContactFormSlug(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $this->sanitizeUrl($value);
        }

        return $this->normalizeRelativePublicPath($value);
    }

    private function normalizeRelativePublicPath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = preg_replace('/[\x00-\x1F\x7F]/', '', $path) ?? '';
        $path = ltrim($path, '/');
        if ($path === '' || str_starts_with($path, '//')) {
            return '';
        }

        $segments = array_values(array_filter(explode('/', $path), static fn (string $segment): bool => $segment !== ''));
        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '..') {
                return '';
            }
        }

        return implode('/', $segments);
    }

    private function normalizePriceAmount(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = str_replace(',', '.', trim((string) $value));
        if (!is_numeric($value)) {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function formatPriceLabel(string $priceAmount, string $priceCurrency): string
    {
        $symbol = strtoupper($priceCurrency) === 'EUR' ? '€' : strtoupper($priceCurrency);
        return number_format((float) $priceAmount, 2, ',', '.') . ' ' . $symbol;
    }

    private function normalizeCurrency(string $value): string
    {
        $value = strtoupper(trim($value));
        if ($value === '') {
            $settings = $this->getSettings();
            $value = $this->sanitizeCurrencyCode((string) ($settings['default_currency'] ?? 'EUR'));
        }

        return $this->sanitizeCurrencyCode($value);
    }

    private function getDefaultSettings(): array
    {
        return [
            'public_submission_enabled' => true,
            'public_submission_path' => '/marketplace-submit',
            'security_reports_enabled' => true,
            'security_report_path' => '/marketplace-security-report',
            'default_currency' => 'EUR',
            'contact_form_base_url' => defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '',
            'directory_view_depth' => 3,
            'show_file_sizes' => true,
            'publish_guardrails_enabled' => true,
            'guardrail_require_docs_url' => true,
            'guardrail_require_changelog_url' => false,
            'guardrail_require_checksum' => true,
            'guardrail_min_age_hours' => 0,
            'cms_updates_enabled' => false,
            'cms_update_channel' => 'stable',
            'cms_update_notes' => '',
            'cms_default_slug' => '365cms',
            'cms_default_author' => '365 Network',
            'cms_default_requires_cms' => '3.0.0',
            'cms_default_requires_php' => '8.4',
            'plugin_default_author' => '365 Network',
            'plugin_default_requires_cms' => '3.0.0',
            'plugin_default_requires_php' => '8.4',
            'theme_default_author' => '365 Network',
            'theme_default_requires_cms' => '3.0.0',
            'theme_default_requires_php' => '8.4',
        ];
    }

    private function normalizeSettings(array $settings): array
    {
        $defaults = $this->getDefaultSettings();
        $merged = array_merge($defaults, $settings);

        return [
            'public_submission_enabled' => !empty($merged['public_submission_enabled']),
            'public_submission_path' => $this->normalizePublicPath((string) ($merged['public_submission_path'] ?? '/marketplace-submit')),
            'security_reports_enabled' => !empty($merged['security_reports_enabled']),
            'security_report_path' => $this->normalizePublicPath((string) ($merged['security_report_path'] ?? '/marketplace-security-report')),
            'default_currency' => $this->sanitizeCurrencyCode((string) ($merged['default_currency'] ?? 'EUR')),
            'contact_form_base_url' => $this->sanitizeUrl((string) ($merged['contact_form_base_url'] ?? '')),
            'directory_view_depth' => max(1, min(6, (int) ($merged['directory_view_depth'] ?? 3))),
            'show_file_sizes' => !empty($merged['show_file_sizes']),
            'publish_guardrails_enabled' => !empty($merged['publish_guardrails_enabled']),
            'guardrail_require_docs_url' => !empty($merged['guardrail_require_docs_url']),
            'guardrail_require_changelog_url' => !empty($merged['guardrail_require_changelog_url']),
            'guardrail_require_checksum' => !empty($merged['guardrail_require_checksum']),
            'guardrail_min_age_hours' => max(0, min(720, (int) ($merged['guardrail_min_age_hours'] ?? 0))),
            'cms_updates_enabled' => !empty($merged['cms_updates_enabled']),
            'cms_update_channel' => in_array((string) ($merged['cms_update_channel'] ?? 'stable'), ['stable', 'beta', 'dev'], true) ? (string) $merged['cms_update_channel'] : 'stable',
            'cms_update_notes' => trim((string) ($merged['cms_update_notes'] ?? '')),
            'cms_default_slug' => $this->normalizeSlug((string) ($merged['cms_default_slug'] ?? '365cms')) ?: '365cms',
            'cms_default_author' => trim((string) ($merged['cms_default_author'] ?? '365 Network')),
            'cms_default_requires_cms' => trim((string) ($merged['cms_default_requires_cms'] ?? '')),
            'cms_default_requires_php' => trim((string) ($merged['cms_default_requires_php'] ?? '')),
            'plugin_default_author' => trim((string) ($merged['plugin_default_author'] ?? '')),
            'plugin_default_requires_cms' => trim((string) ($merged['plugin_default_requires_cms'] ?? '')),
            'plugin_default_requires_php' => trim((string) ($merged['plugin_default_requires_php'] ?? '')),
            'theme_default_author' => trim((string) ($merged['theme_default_author'] ?? '')),
            'theme_default_requires_cms' => trim((string) ($merged['theme_default_requires_cms'] ?? '')),
            'theme_default_requires_php' => trim((string) ($merged['theme_default_requires_php'] ?? '')),
        ];
    }

    private function getSettingsFilePath(): string
    {
        return CMS_MARKETPLACE_PLUGIN_DIR . 'data' . DIRECTORY_SEPARATOR . 'settings.json';
    }

    private function normalizePublicPath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = preg_replace('/[[:cntrl:]?#]+/', '', $path) ?? '';
        $path = '/' . ltrim($path, '/');
        $path = preg_replace('~/+~', '/', $path) ?? '/marketplace-submit';
        $path = rtrim($this->limitUtf8($path, 120), '/');
        $segments = array_values(array_filter(explode('/', ltrim($path, '/')), static fn (string $segment): bool => $segment !== ''));
        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '..') {
                return '/marketplace-submit';
            }
        }

        return $path !== '' ? $path : '/marketplace-submit';
    }

    private function sanitizeCurrencyCode(string $value): string
    {
        $value = preg_replace('/[^A-Z]/', '', strtoupper(trim($value))) ?: 'EUR';
        return $value !== '' ? $value : 'EUR';
    }

    private function normalizeRequestPath(string $requestUri): string
    {
        $path = (string) (parse_url($requestUri, PHP_URL_PATH) ?? '/');
        if (defined('SITE_URL_PATH') && SITE_URL_PATH !== '/' && str_starts_with($path, (string) SITE_URL_PATH)) {
            $path = substr($path, strlen((string) SITE_URL_PATH)) ?: '/';
        }

        if (class_exists('\\CMS\\Services\\ContentLocalizationService')) {
            try {
                $context = \CMS\Services\ContentLocalizationService::getInstance()->resolveRequestContext($path);
                $baseUri = (string) ($context['base_uri'] ?? $path);
                if ($baseUri !== '') {
                    $path = $baseUri;
                }
            } catch (\Throwable) {
            }
        }

        $path = '/' . ltrim($path, '/');
        $path = rtrim((string) preg_replace('#/+#', '/', $path), '/');
        return $path !== '' ? $path : '/';
    }

    private function normalizeComparableRequestPath(string $requestUri): string
    {
        $path = (string) (parse_url($requestUri, PHP_URL_PATH) ?? '/');
        $path = $this->normalizeComparableRoutePath($path);

        if (function_exists('cms_plugin_public_path_without_lang')) {
            return cms_plugin_public_path_without_lang($path);
        }

        if ($path === 'en') {
            return '';
        }

        if (str_starts_with($path, 'en/')) {
            return substr($path, 3) ?: '';
        }

        return $path;
    }

    private function normalizeComparableRoutePath(string $path): string
    {
        if (function_exists('cms_plugin_public_normalize_path')) {
            return cms_plugin_public_normalize_path($path);
        }

        $path = trim($path);
        $path = (string) preg_replace('#/+#', '/', $path);
        return strtolower(trim($path, '/'));
    }

    private function localizePublicPath(string $path, ?string $lang = null): string
    {
        $path = $this->normalizePublicPath($path);
        $language = $lang ?? $this->resolveCurrentPublicLanguage();
        if (function_exists('cms_plugin_public_localized_path')) {
            return cms_plugin_public_localized_path($path, $language);
        }

        if ($language === 'en') {
            return '/en' . $path;
        }

        return $path;
    }

    private function resolveCurrentPublicLanguage(): string
    {
        if (function_exists('cms_plugin_public_language')) {
            return cms_plugin_public_language();
        }

        return 'de';
    }

    private function i18nText(string $key, string $lang, string $de, string $en): string
    {
        if (function_exists('cms_plugin_public_i18n_value')) {
            return cms_plugin_public_i18n_value([$key => $de, $key . '_en' => $en], $key, $lang, $de);
        }

        return $lang === 'en' ? $en : $de;
    }

    private function normalizeDirectoryRelativePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = preg_replace('/[[:cntrl:]]+/', '', $path) ?? '';
        $path = ltrim($path, '/');
        if ($path === '' || str_contains($path, '../') || str_contains($path, '..\\')) {
            return '';
        }

        $segments = array_values(array_filter(explode('/', $path), static fn (string $segment): bool => $segment !== ''));
        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '..') {
                return '';
            }
        }

        return $this->limitUtf8(implode('/', $segments), 512);
    }

    private function getSecurityReportsFilePath(): string
    {
        return CMS_MARKETPLACE_PLUGIN_DIR . 'data' . DIRECTORY_SEPARATOR . self::SECURITY_REPORTS_FILE;
    }

    private function readSecurityReports(): array
    {
        $file = $this->getSecurityReportsFilePath();
        if (!is_file($file)) {
            return [];
        }

        $size = @filesize($file);
        if (!is_int($size) || $size < 0 || $size > 1024 * 1024) {
            return [];
        }

        $raw = @file_get_contents($file);
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $reports = [];
        foreach ($decoded as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $reports[] = [
                'id' => (int) ($entry['id'] ?? 0),
                'created_at' => (string) ($entry['created_at'] ?? ''),
                'updated_at' => (string) ($entry['updated_at'] ?? ''),
                'status' => (string) ($entry['status'] ?? 'new'),
                'status_note' => (string) ($entry['status_note'] ?? ''),
                'lang' => (string) ($entry['lang'] ?? 'de'),
                'type' => (string) ($entry['type'] ?? ''),
                'slug' => (string) ($entry['slug'] ?? ''),
                'version' => (string) ($entry['version'] ?? ''),
                'title' => (string) ($entry['title'] ?? ''),
                'details' => (string) ($entry['details'] ?? ''),
                'reporter_name' => (string) ($entry['reporter_name'] ?? ''),
                'reporter_email' => (string) ($entry['reporter_email'] ?? ''),
                'item_name' => (string) ($entry['item_name'] ?? ''),
            ];
        }

        return $reports;
    }

    private function writeSecurityReports(array $reports): bool
    {
        $file = $this->getSecurityReportsFilePath();
        $dir = dirname($file);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }

        $json = json_encode(array_values($reports), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($json)) {
            return false;
        }

        return file_put_contents($file, $json . PHP_EOL, LOCK_EX) !== false;
    }

    private function buildFilePreview(string $absolutePath): string
    {
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        if (!in_array($extension, ['json', 'txt', 'md', 'log', 'php', 'css', 'js', 'html', 'xml', 'yml', 'yaml'], true)) {
            return '';
        }

        $fileSize = @filesize($absolutePath);
        if (!is_int($fileSize) || $fileSize < 0 || $fileSize > self::MAX_PREVIEW_SOURCE_BYTES) {
            return '';
        }

        $handle = @fopen($absolutePath, 'rb');
        if (!is_resource($handle)) {
            return '';
        }

        $contents = (string) fread($handle, self::MAX_PREVIEW_BYTES + 1);
        fclose($handle);

        if (!is_string($contents) || $contents === '') {
            return '';
        }

        $contents = trim($contents);
        if ($contents === '') {
            return '';
        }

        return $this->limitUtf8($contents, self::MAX_PREVIEW_BYTES);
    }

    private function resolveContainedExistingPath(string $rootPath, string $relativePath): ?string
    {
        $rootReal = realpath($rootPath);
        if (!is_string($rootReal)) {
            return null;
        }

        $candidate = $rootReal . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $candidateReal = realpath($candidate);
        if (!is_string($candidateReal) || !$this->pathStartsWith($candidateReal, $rootReal)) {
            return null;
        }

        return $candidateReal;
    }

    private function isContainedPath(string $path, string $rootPath, bool $mustExist = true): bool
    {
        $rootReal = realpath($rootPath);
        if (!is_string($rootReal)) {
            return false;
        }

        $pathReal = $mustExist ? realpath($path) : realpath(dirname($path));
        if (!is_string($pathReal)) {
            return false;
        }

        $candidate = $mustExist ? $pathReal : $pathReal . DIRECTORY_SEPARATOR . basename($path);
        return $this->pathStartsWith($candidate, $rootReal);
    }

    private function pathStartsWith(string $path, string $rootPath): bool
    {
        $path = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $rootPath = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rootPath), DIRECTORY_SEPARATOR);

        if (DIRECTORY_SEPARATOR === '\\') {
            $path = strtolower($path);
            $rootPath = strtolower($rootPath);
        }

        return $path === $rootPath || str_starts_with($path, $rootPath . DIRECTORY_SEPARATOR);
    }

    private function getDirectoryRootPath(string $scope): string
    {
        return match ($scope) {
            'plugin' => $this->getTypeBasePath('plugin'),
            'theme' => $this->getTypeBasePath('theme'),
            'cms' => $this->getStorageBasePath() . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . '365cms',
            default => $this->getStorageBasePath(),
        };
    }

    private function getDirectoryRootUrl(string $scope): string
    {
        return match ($scope) {
            'plugin' => $this->getTypeBaseUrl('plugin'),
            'theme' => $this->getTypeBaseUrl('theme'),
            'cms' => $this->getStorageBaseUrl() . '/core/365cms',
            default => $this->getStorageBaseUrl(),
        };
    }

    private function collectDirectoryEntries(string $path, string $relativePath, int $depth, int $maxDepth): array
    {
        if (!is_dir($path) || $depth >= $maxDepth) {
            return [];
        }

        $entries = [];
        try {
            $iterator = new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS);
            $items = iterator_to_array($iterator, false);
        } catch (\Throwable) {
            return [];
        }

        usort($items, static function (\SplFileInfo $a, \SplFileInfo $b): int {
            if ($a->isDir() !== $b->isDir()) {
                return $a->isDir() ? -1 : 1;
            }

            return strcasecmp($a->getFilename(), $b->getFilename());
        });

        foreach ($items as $item) {
            if ($item->isLink()) {
                continue;
            }

            $name = $item->getFilename();
            $itemRelativePath = ltrim($relativePath . '/' . $name, '/');
            $entries[] = [
                'name' => $name,
                'relative_path' => $itemRelativePath,
                'type' => $item->isDir() ? 'dir' : 'file',
                'depth' => $depth,
                'size' => $item->isFile() ? (int) $item->getSize() : 0,
                'modified_at' => date('Y-m-d H:i', (int) $item->getMTime()),
            ];

            if ($item->isDir()) {
                foreach ($this->collectDirectoryEntries($item->getPathname(), $itemRelativePath, $depth + 1, $maxDepth) as $child) {
                    $entries[] = $child;
                }
            }
        }

        return $entries;
    }

    private function shouldSyncPublicCatalogs(): bool
    {
        $stateDigest = $this->repository->getStateDigest();
        if ($stateDigest === '') {
            return true;
        }

        if ($this->lastSyncedStateDigest === $stateDigest) {
            return false;
        }

        $storedDigest = $this->readSyncStateDigest();
        if ($storedDigest === $stateDigest && $this->hasCoreCatalogFiles()) {
            $this->lastSyncedStateDigest = $stateDigest;
            return false;
        }

        return true;
    }

    private function hasCoreCatalogFiles(): bool
    {
        $requiredFiles = [
            $this->getStorageBasePath() . DIRECTORY_SEPARATOR . 'index.json',
            $this->getTypeBasePath('plugin') . DIRECTORY_SEPARATOR . 'index.json',
            $this->getTypeBasePath('theme') . DIRECTORY_SEPARATOR . 'index.json',
        ];

        foreach ($requiredFiles as $requiredFile) {
            if (!is_file($requiredFile)) {
                return false;
            }
        }

        return true;
    }

    private function getSyncStateFilePath(): string
    {
        return $this->getStorageBasePath() . DIRECTORY_SEPARATOR . self::SYNC_STATE_FILE;
    }

    private function readSyncStateDigest(): string
    {
        $file = $this->getSyncStateFilePath();
        if (!is_file($file)) {
            return '';
        }

        $raw = file_get_contents($file);
        if (!is_string($raw) || trim($raw) === '') {
            return '';
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return '';
        }

        $digest = (string) ($decoded['state_digest'] ?? '');
        return preg_match('/^[a-f0-9]{40}$/', $digest) === 1 ? $digest : '';
    }

    private function writeSyncStateDigest(string $digest): void
    {
        if (preg_match('/^[a-f0-9]{40}$/', $digest) !== 1) {
            return;
        }

        $file = $this->getSyncStateFilePath();
        $payload = [
            'state_digest' => $digest,
            'synced_at' => gmdate('c'),
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            return;
        }

        if (file_put_contents($file, $json . PHP_EOL, LOCK_EX) === false) {
            $this->logError('Failed writing sync state file: ' . $file);
        }
    }

    private function safeUnlink(string $path): bool
    {
        if (!is_file($path)) {
            return true;
        }

        if (!@unlink($path)) {
            $this->logError('Failed deleting file: ' . $path);
            return false;
        }

        return true;
    }

    private function safeFileSize(string $path): int
    {
        $size = @filesize($path);
        return is_int($size) && $size >= 0 ? $size : 0;
    }

    private function safeFileMTime(string $path): int
    {
        $mtime = @filemtime($path);
        return is_int($mtime) && $mtime > 0 ? $mtime : time();
    }

    private function logError(string $message): void
    {
        error_log('[cms-marketplace] ' . $message);
    }

    private function normalizeSubmissionSource(string $value): string
    {
        $value = strtolower(trim($value));
        return in_array($value, ['admin', 'public'], true) ? $value : 'admin';
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));
        return in_array($type, ['cms', 'plugin', 'theme'], true) ? $type : '';
    }

    private function normalizeTypeOrNull(?string $type): ?string
    {
        if ($type === null || trim($type) === '') {
            return null;
        }

        $type = $this->normalizeType($type);
        return $type !== '' ? $type : null;
    }

    private function normalizeSlug(string $slug): string
    {
        return $this->limitUtf8(preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($slug))) ?? '', 120);
    }

    private function normalizeVersion(string $version): string
    {
        return $this->limitUtf8(preg_replace('/[^0-9A-Za-z._-]/', '', trim($version)) ?? '', 50);
    }

    private function sanitizeText(string $value, int $maxLength): string
    {
        $value = trim(strip_tags($value));
        $value = preg_replace('/[[:cntrl:]]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return $this->limitUtf8(trim($value), max(1, $maxLength));
    }

    private function sanitizeTextarea(string $value, int $maxLength): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", strip_tags($value));
        $value = str_replace("\0", '', $value);

        return $this->limitUtf8(trim($value), max(1, $maxLength));
    }

    private function buildDirectoryPublicUrl(string $rootUrl, string $relativePath): string
    {
        $segments = array_values(array_filter(explode('/', str_replace('\\', '/', $relativePath)), static fn (string $segment): bool => $segment !== ''));
        $encoded = array_map(static fn (string $segment): string => rawurlencode($segment), $segments);

        return rtrim($rootUrl, '/') . ($encoded !== [] ? '/' . implode('/', $encoded) : '');
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
