<?php

declare(strict_types=1);

namespace CmsKnowledgebase\Support;

if (!defined('ABSPATH')) {
    exit;
}

final class Defaults
{
    /**
     * @return array<string, string>
     */
    public static function settings(): array
    {
        return [
            'enable_autolink' => '1',
            'enable_tooltips' => '1',
            'enable_output_buffer' => '0',
            'max_links_per_page' => '6',
            'nofollow_links' => '0',
            'open_links_new_tab' => '0',
            'archive_title' => 'Knowledgebase',
            'archive_intro' => 'Hilfreiche Begriffe, Hintergründe und kurze Erklärungen aus deinem CMS direkt im Kontext verlinkt.',
            'glossary_title' => 'Glossar',
            'glossary_intro' => 'Alle wichtigen Begriffe kompakt erklärt – ideal zum schnellen Nachschlagen und für interne Verlinkungen.',
            'show_search' => '1',
            'show_category_sidebar' => '1',
            'show_keyword_badges' => '1',
            'show_related_entries' => '1',
            'related_posts_limit' => '4',
            'show_nav_link' => '0',
            'nav_label' => 'Knowledgebase',
            'content_max_width' => '1200',
            'sidebar_width' => '300',
            'design_accent_color' => '#0d9488',
            'design_accent_hover_color' => '#0f766e',
            'design_surface_color' => '#ffffff',
            'design_border_color' => '#dbe1ea',
            'design_border_radius' => '14',
            'tooltip_background_color' => '#111827',
            'tooltip_text_color' => '#f8fafc',
        ];
    }
}
