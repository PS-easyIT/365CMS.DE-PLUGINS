<?php

declare(strict_types=1);

namespace CmsKnowledgebase\Service;

use CmsKnowledgebase\Repository\EntryRepository;
use CmsKnowledgebase\Support\LoggerFactory;

if (!defined('ABSPATH')) {
    exit;
}

final class StandardPackages
{
    private static ?self $instance = null;

    private EntryRepository $repository;

    private $logger;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->repository = EntryRepository::instance();
        $this->logger = LoggerFactory::create();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPackages(): array
    {
        $packages = [];

        foreach ($this->packageDefinitions() as $key => $package) {
            $entryTitles = array_map(
                static fn(array $entry): string => (string) ($entry['title'] ?? ''),
                $package['entries']
            );

            $packages[] = [
                'key' => $key,
                'label' => (string) $package['label'],
                'description' => (string) $package['description'],
                'accent' => (string) $package['accent'],
                'file_name' => (string) $package['file_name'],
                'entry_count' => count($package['entries']),
                'sample_terms' => array_values(array_filter(array_slice($entryTitles, 0, 4))),
            ];
        }

        return $packages;
    }

    /**
     * @return array<int, string>
     */
    public function getCsvFilenameWarnings(): array
    {
        $directory = CMS_KNOWLEDGEBASE_PLUGIN_DIR . 'csv_kb';
        if (!is_dir($directory)) {
            return [];
        }

        $files = glob($directory . DIRECTORY_SEPARATOR . '*.csv');
        if (!is_array($files) || $files === []) {
            return [];
        }

        natcasesort($files);
        $warnings = [];

        foreach ($files as $filePath) {
            $fileName = basename($filePath);
            if (preg_match('/_Glossar\.csv$/i', $fileName) === 1) {
                continue;
            }

            if (stripos($fileName, 'Glossar') === false) {
                continue;
            }

            $warnings[] = sprintf(
                'Dateiname „%s“ wird ignoriert. Erwartet wird das Format „Kategorie_Glossar.csv“, z. B. „Cloud_Security_Glossar.csv“.',
                $fileName
            );
        }

        return $warnings;
    }

    /**
     * @return array{success: bool, message?: string, error?: string}
     */
    public function importPackage(string $packageKey): array
    {
        $definitions = $this->packageDefinitions();
        if (!isset($definitions[$packageKey])) {
            return ['success' => false, 'error' => 'Unbekanntes Standardpaket.'];
        }

        $package = $definitions[$packageKey];
        if ($package['entries'] === []) {
            return [
                'success' => false,
                'error' => sprintf('Die CSV-Datei „%s“ enthält aktuell keine importierbaren Einträge.', (string) $package['file_name']),
            ];
        }

        $result = $this->repository->importPresetEntries($this->buildEntries($package), $packageKey);

        $this->logger->info('Knowledgebase-CSV-Paket importiert.', [
            'package' => $packageKey,
            'file_name' => (string) $package['file_name'],
            'created' => (int) ($result['created'] ?? 0),
            'updated' => (int) ($result['updated'] ?? 0),
            'skipped' => (int) ($result['skipped'] ?? 0),
            'errors' => (int) ($result['errors'] ?? 0),
        ]);

        $this->repository->saveCsvImportStatus(
            (int) ($result['created'] ?? 0) + (int) ($result['updated'] ?? 0),
            (int) ($result['created'] ?? 0),
            (int) ($result['updated'] ?? 0),
            (int) ($result['skipped'] ?? 0),
            (string) ($package['label'] ?? $packageKey)
        );

        return [
            'success' => ((int) ($result['created'] ?? 0) + (int) ($result['updated'] ?? 0) + (int) ($result['skipped'] ?? 0)) > 0,
            'message' => sprintf(
                'CSV-Paket „%s“ verarbeitet: %d neu angelegt, %d aktualisiert, %d übersprungen%s.',
                (string) $package['label'],
                (int) ($result['created'] ?? 0),
                (int) ($result['updated'] ?? 0),
                (int) ($result['skipped'] ?? 0),
                (int) ($result['errors'] ?? 0) > 0 ? sprintf(', %d mit Fehlern', (int) ($result['errors'] ?? 0)) : ''
            ),
        ];
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function importAllPackages(): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($this->packageDefinitions() as $packageKey => $package) {
            if ($package['entries'] === []) {
                continue;
            }

            $result = $this->repository->importPresetEntries($this->buildEntries($package), $packageKey);
            $created += (int) ($result['created'] ?? 0);
            $updated += (int) ($result['updated'] ?? 0);
            $skipped += (int) ($result['skipped'] ?? 0);
            $errors += (int) ($result['errors'] ?? 0);
        }

        $this->repository->saveCsvImportStatus($created + $updated, $created, $updated, $skipped, 'Alle CSV-Pakete');

        return [
            'success' => ($created + $updated + $skipped) > 0,
            'message' => sprintf(
                'Alle CSV-Pakete verarbeitet: %d neu angelegt, %d aktualisiert, %d übersprungen%s.',
                $created,
                $updated,
                $skipped,
                $errors > 0 ? sprintf(', %d mit Fehlern', $errors) : ''
            ),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function packageDefinitions(): array
    {
        $definitions = [];

        foreach ($this->discoverCsvPackages() as $packageKey => $csvPackage) {
            $entries = $this->loadCsvEntries((string) $csvPackage['file_name']);

            $definitions[$packageKey] = [
                'label' => (string) $csvPackage['label'],
                'description' => sprintf('Glossar-Import aus %s.', (string) $csvPackage['file_name']),
                'accent' => $this->defaultAccentForPackageKey($packageKey),
                'file_name' => (string) $csvPackage['file_name'],
                'entries' => $entries,
            ];
        }

        return $definitions;
    }

    /**
     * @return array<string, array{file_name: string, label: string}>
     */
    private function discoverCsvPackages(): array
    {
        $directory = CMS_KNOWLEDGEBASE_PLUGIN_DIR . 'csv_kb';
        if (!is_dir($directory)) {
            return [];
        }

        $files = glob($directory . DIRECTORY_SEPARATOR . '*_Glossar.csv');
        if (!is_array($files) || $files === []) {
            return [];
        }

        natcasesort($files);
        $packages = [];

        foreach ($files as $filePath) {
            $fileName = basename($filePath);
            $baseName = preg_replace('/_Glossar\.csv$/i', '', $fileName) ?? '';
            $baseName = trim($baseName);
            if ($baseName === '') {
                continue;
            }

            $packageKey = $this->buildPackageKeyFromCsvBaseName($baseName);
            if ($packageKey === '') {
                continue;
            }

            $packages[$packageKey] = [
                'file_name' => $fileName,
                'label' => $this->buildCategoryLabelFromCsvBaseName($baseName),
            ];
        }

        return $packages;
    }

    /**
     * @param array<string, mixed> $package
     * @return array<int, array<string, mixed>>
     */
    private function buildEntries(array $package): array
    {
        $entries = [];

        foreach ($package['entries'] as $index => $entry) {
            $title = trim((string) ($entry['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $keyword = trim((string) ($entry['keyword'] ?? $title));
            $synonyms = $this->normalizeSynonyms($entry['synonyms'] ?? []);
            $shortDescription = trim((string) ($entry['csv_short_description'] ?? ''));
            $longDescription = trim((string) ($entry['csv_long_description'] ?? ''));
            $licenseInfo = trim((string) ($entry['csv_license_info'] ?? ''));
            $links = is_array($entry['csv_links'] ?? null) ? $entry['csv_links'] : [];

            $entries[] = [
                'title' => $title,
                'keyword' => $keyword,
                'slug' => $title,
                'excerpt' => $this->buildCsvExcerpt($title, $shortDescription, $longDescription),
                'tooltip_text' => $this->buildCsvTooltip($title, $shortDescription, $longDescription),
                'synonyms' => implode("\n", $synonyms),
                'category' => (string) $package['label'],
                'priority' => 100 + ((int) $index * 10),
                'content' => $this->buildCsvContent($shortDescription, $longDescription, $licenseInfo, $links),
                'is_active' => '1',
                'is_whole_word' => '1',
                'is_case_sensitive' => '0',
                'max_links_per_page' => '1',
            ];
        }

        return $entries;
    }

    /**
     * @param mixed $synonyms
     * @return array<int, string>
     */
    private function normalizeSynonyms(mixed $synonyms): array
    {
        if (!is_array($synonyms)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn(mixed $value): string => trim((string) $value),
            $synonyms
        )));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadCsvEntries(string $fileName): array
    {
        $path = CMS_KNOWLEDGEBASE_PLUGIN_DIR . 'csv_kb/' . $fileName;
        if (!is_file($path)) {
            return [];
        }

        $delimiter = $this->detectDelimiter($path);
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return [];
        }

        $header = fgetcsv($handle, 0, $delimiter);
        if (!is_array($header)) {
            fclose($handle);
            return [];
        }

        $headerMap = array_map([$this, 'normalizeCsvColumnName'], $header);
        $entries = [];

        while (($rowData = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (!is_array($rowData) || $this->isEmptyCsvRow($rowData)) {
                continue;
            }

            $row = [];
            foreach ($headerMap as $index => $column) {
                if ($column === '') {
                    continue;
                }

                $row[$column] = trim((string) ($rowData[$index] ?? ''));
            }

            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $entries[] = [
                'title' => $title,
                'keyword' => trim((string) ($row['keyword'] ?? $title)),
                'synonyms' => $this->parseSynonyms((string) ($row['synonyms'] ?? '')),
                'csv_short_description' => (string) ($row['short_description'] ?? ''),
                'csv_long_description' => (string) ($row['long_description'] ?? ''),
                'csv_license_info' => (string) ($row['license_info'] ?? ''),
                'csv_links' => $this->parseCsvLinks((string) ($row['links'] ?? '')),
            ];
        }

        fclose($handle);

        return $entries;
    }

    private function detectDelimiter(string $path): string
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return ';';
        }

        $firstLine = fgets($handle);
        fclose($handle);

        if (!is_string($firstLine) || trim($firstLine) === '') {
            return ';';
        }

        $candidates = [
            ';' => substr_count($firstLine, ';'),
            ',' => substr_count($firstLine, ','),
            "\t" => substr_count($firstLine, "\t"),
        ];
        arsort($candidates);
        $delimiter = array_key_first($candidates);

        return is_string($delimiter) ? $delimiter : ';';
    }

    /**
     * @param array<int, mixed> $rowData
     */
    private function isEmptyCsvRow(array $rowData): bool
    {
        foreach ($rowData as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeCsvColumnName(string $value): string
    {
        $value = trim(str_replace("\xEF\xBB\xBF", '', $value));
        $value = trim($value, " \t\n\r\0\x0B\"");
        $value = mb_strtolower($value, 'UTF-8');
        $value = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return match ($value) {
            'eintrag', 'titel', 'title', 'begriff', 'term' => 'title',
            'keyword', 'fokusbegriff', 'suchbegriff' => 'keyword',
            'synonyme', 'synonym', 'keywords', 'alias', 'aliases' => 'synonyms',
            'kurzbeschreibung', 'short description', 'short_description', 'beschreibung kurz' => 'short_description',
            'langbeschreibung', 'beschreibung', 'long description', 'long_description', 'beschreibung lang', 'details' => 'long_description',
            'lizenz-info', 'lizenzinfo', 'license info', 'license_info', 'lizenz', 'lizenz / verfuegbarkeit', 'lizenz & verfuegbarkeit' => 'license_info',
            'ms learn / weblinks', 'weblinks', 'links', 'link', 'quellen', 'urls', 'url' => 'links',
            default => '',
        };
    }

    /**
     * @return array<int, string>
     */
    private function parseSynonyms(string $rawSynonyms): array
    {
        $rawSynonyms = trim($rawSynonyms);
        if ($rawSynonyms === '') {
            return [];
        }

        $parts = preg_split('/\r\n|\r|\n|\s*\|\s*|\s*;\s*|\s*,\s*/', $rawSynonyms) ?: [];
        $synonyms = [];
        $seen = [];

        foreach ($parts as $part) {
            $synonym = trim($part);
            if ($synonym === '') {
                continue;
            }

            $normalized = mb_strtolower($synonym, 'UTF-8');
            if (isset($seen[$normalized])) {
                continue;
            }

            $seen[$normalized] = true;
            $synonyms[] = $synonym;
        }

        return $synonyms;
    }

    private function buildPackageKeyFromCsvBaseName(string $baseName): string
    {
        $key = mb_strtolower(trim($baseName), 'UTF-8');
        $key = str_replace('_', '-', $key);
        $key = preg_replace('/[^a-z0-9\-]+/', '-', $key) ?? '';

        return trim($key, '-');
    }

    private function buildCategoryLabelFromCsvBaseName(string $baseName): string
    {
        $label = str_replace('_', ' & ', trim($baseName));
        $label = preg_replace('/\s+/', ' ', $label) ?? $label;

        return trim($label);
    }

    private function defaultAccentForPackageKey(string $packageKey): string
    {
        $palette = [
            '#0EA5E9',
            '#2563EB',
            '#DC2626',
            '#7C3AED',
            '#0F766E',
            '#F59E0B',
            '#4F46E5',
            '#BE123C',
        ];

        $hash = abs(crc32($packageKey));

        return $palette[$hash % count($palette)];
    }

    private function buildCsvExcerpt(string $title, string $shortDescription, string $longDescription): string
    {
        $excerpt = $shortDescription !== '' ? $shortDescription : $longDescription;
        if ($excerpt === '') {
            $excerpt = $title;
        }

        return mb_substr(trim($excerpt), 0, 280, 'UTF-8');
    }

    private function buildCsvTooltip(string $title, string $shortDescription, string $longDescription): string
    {
        $tooltip = $shortDescription !== '' ? $shortDescription : $longDescription;
        if ($tooltip === '') {
            $tooltip = $title;
        }

        return mb_substr(trim($tooltip), 0, 220, 'UTF-8');
    }

    /**
     * @param array<int, array{label: string, url: string}> $links
     */
    private function buildCsvContent(string $shortDescription, string $longDescription, string $licenseInfo, array $links): string
    {
        $sections = [];

        if ($longDescription !== '') {
            $sections[] = '<h2>Allgemeine Infos</h2><p>' . htmlspecialchars($longDescription, ENT_QUOTES, 'UTF-8') . '</p>';
        } elseif ($shortDescription !== '') {
            $sections[] = '<h2>Allgemeine Infos</h2><p>' . htmlspecialchars($shortDescription, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        if ($shortDescription !== '' && $longDescription !== '' && mb_strtolower($shortDescription, 'UTF-8') !== mb_strtolower($longDescription, 'UTF-8')) {
            $sections[] = '<h2>Kurzbeschreibung</h2><p>' . htmlspecialchars($shortDescription, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        if ($licenseInfo !== '') {
            $sections[] = '<h2>Lizenz &amp; Verfügbarkeit</h2><p>' . htmlspecialchars($licenseInfo, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        $linksHtml = $this->buildLinksList($links);
        if ($linksHtml !== '') {
            $sections[] = '<h2>Weiterführende Links</h2><ul>' . $linksHtml . '</ul>';
        }

        return implode('', $sections);
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function parseCsvLinks(string $rawLinks): array
    {
        $rawLinks = str_replace(["\r\n", "\r"], "\n", trim($rawLinks));
        if ($rawLinks === '') {
            return [];
        }

        $parts = preg_split('/\n+|\s*\|\s*|,\s*(?=https?:\/\/)|;\s*(?=https?:\/\/)/', $rawLinks) ?: [];
        $links = [];
        $seen = [];

        foreach ($parts as $part) {
            $url = trim($part, " \t\n\r\0\x0B\"");
            if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false || isset($seen[$url])) {
                continue;
            }

            $seen[$url] = true;
            $links[] = [
                'label' => $this->labelForCsvLink($url),
                'url' => $url,
            ];
        }

        return $links;
    }

    private function labelForCsvLink(string $url): string
    {
        $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST), 'UTF-8');

        return match (true) {
            str_contains($host, 'learn.microsoft.com') => 'Microsoft Learn',
            str_contains($host, 'support.microsoft.com') => 'Microsoft Support',
            str_contains($host, 'microsoft.com') => 'Microsoft',
            str_contains($host, 'wikipedia.org') => 'Wikipedia',
            str_contains($host, 'google.com') => 'Google',
            $host !== '' => preg_replace('/^www\./', '', $host) ?? $host,
            default => 'Weblink',
        };
    }

    /**
     * @param array<int, array{label: string, url: string}> $items
     */
    private function buildLinksList(array $items): string
    {
        $html = [];

        foreach ($items as $item) {
            $label = trim((string) ($item['label'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));
            if ($label === '' || $url === '') {
                continue;
            }

            $html[] = sprintf(
                '<li><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></li>',
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            );
        }

        return implode('', $html);
    }
}
