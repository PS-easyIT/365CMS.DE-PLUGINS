<?php
/**
 * CMS Beratung – SEO meta and structured data.
 *
 * @package CMS_Beratung
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Beratung_SEO
{
    /** @param array<string,mixed> $page */
    public static function output_head(array $page): void
    {
        $settings = CMS_Beratung_Settings::all();
        $title = trim((string) ($page['meta_title'] ?? '')) ?: (string) ($page['public_title'] ?? 'CMS Beratung');
        $description = trim((string) ($page['meta_description'] ?? ''));
        $robots = [];
        if (($settings['seo_noindex_per_page_enabled'] ?? '1') === '1' && !empty($page['noindex'])) {
            $robots[] = 'noindex';
        }
        if (($settings['seo_nofollow_per_page_enabled'] ?? '1') === '1' && !empty($page['nofollow'])) {
            $robots[] = 'nofollow';
        }

        if (($settings['seo_meta_title_enabled'] ?? '1') === '1') {
            echo '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>' . "\n";
        }
        if (($settings['seo_meta_description_enabled'] ?? '1') === '1' && $description !== '') {
            echo '<meta name="description" content="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }
        if ($robots !== []) {
            echo '<meta name="robots" content="' . htmlspecialchars(implode(', ', $robots), ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }
        if (($settings['seo_canonical_enabled'] ?? '1') === '1') {
            $canonical = trim((string) ($page['canonical_url'] ?? ''));
            if ($canonical === '') {
                $base = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
                $canonical = $base . '/beratung/' . trim((string) ($page['slug'] ?? ''), '/');
            }
            echo '<link rel="canonical" href="' . htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }
        if (($settings['seo_open_graph_enabled'] ?? '1') === '1') {
            echo '<meta property="og:title" content="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">' . "\n";
            if ($description !== '') {
                echo '<meta property="og:description" content="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '">' . "\n";
            }
            echo '<meta property="og:type" content="website">' . "\n";
        }
        if (($settings['seo_twitter_card_enabled'] ?? '1') === '1') {
            echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        }
        self::output_schema($page, $settings);
        self::output_faq_schema($page, $settings);
    }

    /** @param array<string,mixed> $page @param array<string,string> $settings */
    private static function output_schema(array $page, array $settings): void
    {
        if (($settings['seo_service_schema_enabled'] ?? '1') !== '1') {
            return;
        }
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => (string) ($page['public_title'] ?? 'CMS Beratung'),
            'description' => (string) ($page['meta_description'] ?? ''),
            'provider' => [
                '@type' => 'Organization',
                'name' => '365CMS',
            ],
            'areaServed' => 'DE',
            'serviceType' => 'Microsoft 365 Consulting',
        ];
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
    }

    /** @param array<string,mixed> $page @param array<string,string> $settings */
    private static function output_faq_schema(array $page, array $settings): void
    {
        if (($settings['seo_faq_schema_enabled'] ?? '1') !== '1') {
            return;
        }
        $sections = is_array($page['sections'] ?? null) ? $page['sections'] : [];
        $entities = [];
        foreach ($sections as $section) {
            if (!is_array($section) || ($section['type'] ?? '') !== 'faq' || empty($section['enabled']) || empty($section['faq_schema_enabled'])) {
                continue;
            }
            $cards = is_array($section['cards'] ?? null) ? $section['cards'] : [];
            foreach ($cards as $card) {
                if (!is_array($card) || empty($card['enabled'])) {
                    continue;
                }
                $question = trim((string) ($card['question'] ?? $card['title'] ?? ''));
                $answer = trim((string) ($card['answer'] ?? $card['text'] ?? ''));
                if ($question === '' || $answer === '') {
                    continue;
                }
                $entities[] = [
                    '@type' => 'Question',
                    'name' => $question,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $answer,
                    ],
                ];
            }
        }
        if ($entities === []) {
            return;
        }
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
    }
}
