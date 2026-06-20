<?php
/**
 * CMS Beratung – JSON import/export.
 *
 * @package CMS_Beratung
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Beratung_Import_Export
{
    /** @param array<string,mixed> $page @return array<string,mixed> */
    public static function export_page(array $page): array
    {
        return [
            'schema' => 'cms-beratung-landingpage/v1',
            'exported_at' => date('c'),
            'plugin_version' => CMS_BERATUNG_VERSION,
            'landingpage' => self::sanitize_landingpage_payload($page),
        ];
    }

    public static function export_json(array $page): string
    {
        return json_encode(self::export_page($page), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /** @return array<string,mixed>|null */
    public static function decode_import_json(string $json): ?array
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return null;
        }
        $page = isset($decoded['landingpage']) && is_array($decoded['landingpage']) ? $decoded['landingpage'] : $decoded;
        return self::sanitize_landingpage_payload($page);
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    public static function sanitize_landingpage_payload(array $data): array
    {
        $statuses = CMS_Beratung_Settings::statuses();
        $templates = CMS_Beratung_Settings::templates();
        $hero = self::sanitize_hero($data['hero'] ?? $data['hero_json'] ?? []);
        $sections = self::sanitize_sections($data['sections'] ?? $data['sections_json'] ?? []);
        $design = self::sanitize_design($data['design'] ?? $data['design_json'] ?? []);

        $payload = [
            'id' => (int) ($data['id'] ?? 0),
            'tenant_id' => !empty($data['tenant_id']) ? (int) $data['tenant_id'] : null,
            'internal_title' => self::limit(CMS_Beratung_Settings::text((string) ($data['internal_title'] ?? 'Neue Beratungs Landingpage')), 255),
            'public_title' => self::limit(CMS_Beratung_Settings::text((string) ($data['public_title'] ?? $data['internal_title'] ?? 'Beratung')), 255),
            'slug' => CMS_Beratung_Settings::slug((string) ($data['slug'] ?? ''), 'beratung'),
            'meta_title' => self::limit(CMS_Beratung_Settings::text((string) ($data['meta_title'] ?? '')), 255),
            'meta_description' => self::limit(CMS_Beratung_Settings::text((string) ($data['meta_description'] ?? '')), 500),
            'focus_keyword' => self::limit(CMS_Beratung_Settings::text((string) ($data['focus_keyword'] ?? '')), 160),
            'status' => array_key_exists((string) ($data['status'] ?? ''), $statuses) ? (string) $data['status'] : 'draft',
            'template' => array_key_exists((string) ($data['template'] ?? ''), $templates) ? (string) $data['template'] : 'standard',
            'max_content_width' => max(720, min(1800, (int) ($data['max_content_width'] ?? 1200))),
            'custom_design_enabled' => !empty($data['custom_design_enabled']) ? 1 : 0,
            'use_global_settings' => array_key_exists('use_global_settings', $data) ? (!empty($data['use_global_settings']) ? 1 : 0) : 1,
            'show_header' => array_key_exists('show_header', $data) ? (!empty($data['show_header']) ? 1 : 0) : 1,
            'show_footer' => array_key_exists('show_footer', $data) ? (!empty($data['show_footer']) ? 1 : 0) : 1,
            'show_breadcrumb' => array_key_exists('show_breadcrumb', $data) ? (!empty($data['show_breadcrumb']) ? 1 : 0) : 1,
            'show_toc' => !empty($data['show_toc']) ? 1 : 0,
            'show_anchor_nav' => array_key_exists('show_anchor_nav', $data) ? (!empty($data['show_anchor_nav']) ? 1 : 0) : 1,
            'noindex' => !empty($data['noindex']) ? 1 : 0,
            'nofollow' => !empty($data['nofollow']) ? 1 : 0,
            'canonical_url' => CMS_Beratung_Settings::public_url((string) ($data['canonical_url'] ?? '')),
            'custom_css_class' => self::css_class((string) ($data['custom_css_class'] ?? '')),
            'hero_json' => json_encode($hero, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'design_json' => json_encode($design, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'sections_json' => json_encode($sections, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'hero' => $hero,
            'sections' => $sections,
            'design' => $design,
            'created_by' => !empty($data['created_by']) ? (int) $data['created_by'] : null,
            'updated_by' => !empty($data['updated_by']) ? (int) $data['updated_by'] : null,
        ];

        return $payload;
    }

    /** @param mixed $raw @return array<string,mixed> */
    public static function sanitize_hero(mixed $raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($raw)) {
            $raw = [];
        }

        $button = static function (int $number) use ($raw): array {
            $data = is_array($raw['button_' . $number] ?? null) ? $raw['button_' . $number] : [];
            $style = (string) ($data['style'] ?? $raw['button_' . $number . '_style'] ?? 'primary');
            $targetType = (string) ($data['target_type'] ?? $raw['button_' . $number . '_target_type'] ?? 'internal');
            return [
                'text' => self::limit(CMS_Beratung_Settings::text((string) ($data['text'] ?? $raw['button_' . $number . '_text'] ?? '')), 80),
                'target' => self::button_target((string) ($data['target'] ?? $raw['button_' . $number . '_target'] ?? ''), $targetType),
                'target_type' => self::choice($targetType, ['internal', 'external', 'anchor', 'contact', 'email', 'phone', 'bookings', 'download'], 'internal'),
                'style' => self::choice($style, ['primary', 'secondary', 'ghost', 'link'], 'primary'),
            ];
        };

        return [
            'enabled' => array_key_exists('enabled', $raw) ? !empty($raw['enabled']) : true,
            'image_url' => CMS_Beratung_Settings::image_url((string) ($raw['image_url'] ?? '')),
            'image_position' => self::choice((string) ($raw['image_position'] ?? 'left'), ['left', 'right'], 'left'),
            'image_flush' => array_key_exists('image_flush', $raw) ? !empty($raw['image_flush']) : true,
            'image_height' => max(180, min(900, (int) ($raw['image_height'] ?? 520))),
            'image_width' => max(25, min(70, (int) ($raw['image_width'] ?? 46))),
            'image_fit' => self::choice((string) ($raw['image_fit'] ?? 'cover'), ['cover', 'contain'], 'cover'),
            'image_alt' => self::limit(CMS_Beratung_Settings::text((string) ($raw['image_alt'] ?? '')), 180),
            'badge_text' => self::limit(CMS_Beratung_Settings::text((string) ($raw['badge_text'] ?? '')), 120),
            'badge_show' => array_key_exists('badge_show', $raw) ? !empty($raw['badge_show']) : true,
            'title' => self::limit(CMS_Beratung_Settings::text((string) ($raw['title'] ?? '')), 255),
            'subtitle' => self::limit(CMS_Beratung_Settings::text((string) ($raw['subtitle'] ?? '')), 255),
            'description' => self::limit(CMS_Beratung_Settings::text((string) ($raw['description'] ?? '')), 1200),
            'button_1' => $button(1),
            'button_2' => $button(2),
            'button_3' => $button(3),
            'trust_text' => self::limit(CMS_Beratung_Settings::text((string) ($raw['trust_text'] ?? '')), 255),
            'background_color' => CMS_Beratung_Settings::color((string) ($raw['background_color'] ?? '#f8fafc'), '#f8fafc'),
            'text_color' => CMS_Beratung_Settings::color((string) ($raw['text_color'] ?? '#111827'), '#111827'),
            'vertical_align' => self::choice((string) ($raw['vertical_align'] ?? 'center'), ['start', 'center', 'end'], 'center'),
            'mobile_order' => self::choice((string) ($raw['mobile_order'] ?? 'image-first'), ['image-first', 'text-first'], 'image-first'),
        ];
    }

    /** @param mixed $raw @return array<int,array<string,mixed>> */
    public static function sanitize_sections(mixed $raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($raw)) {
            return [];
        }
        $sections = [];
        foreach (array_values($raw) as $index => $section) {
            if (!is_array($section)) {
                continue;
            }
            $columns = (int) ($section['columns'] ?? 3);
            $type = self::choice((string) ($section['type'] ?? 'card_grid'), ['text', 'card_grid', 'image_cards', 'services', 'offers', 'comparison', 'faq', 'steps', 'cta', 'trust', 'technology', 'contact', 'divider', 'html', 'infographic', 'cards'], 'card_grid');
            $button1Type = (string) ($section['button_1_target_type'] ?? 'internal');
            $button2Type = (string) ($section['button_2_target_type'] ?? 'internal');
            $sections[] = [
                'id' => self::section_id((string) ($section['id'] ?? 'section-' . ($index + 1))),
                'enabled' => array_key_exists('enabled', $section) ? !empty($section['enabled']) : true,
                'type' => $type === 'cards' ? 'card_grid' : $type,
                'internal_name' => self::limit(CMS_Beratung_Settings::text((string) ($section['internal_name'] ?? $section['title'] ?? 'Bereich ' . ($index + 1))), 160),
                'eyebrow' => self::limit(CMS_Beratung_Settings::text((string) ($section['eyebrow'] ?? '')), 120),
                'title' => self::limit(CMS_Beratung_Settings::text((string) ($section['title'] ?? '')), 255),
                'intro' => self::limit(CMS_Beratung_Settings::text((string) ($section['intro'] ?? $section['text'] ?? '')), 1200),
                'anchor_id' => CMS_Beratung_Settings::slug((string) ($section['anchor_id'] ?? $section['anchor'] ?? $section['id'] ?? 'bereich-' . ($index + 1)), 'bereich-' . ($index + 1)),
                'background_color' => CMS_Beratung_Settings::color((string) ($section['background_color'] ?? $section['background'] ?? '#ffffff'), '#ffffff'),
                'background_image_url' => CMS_Beratung_Settings::image_url((string) ($section['background_image_url'] ?? '')),
                'padding_top' => max(0, min(180, (int) ($section['padding_top'] ?? 56))),
                'padding_bottom' => max(0, min(180, (int) ($section['padding_bottom'] ?? 56))),
                'max_width' => max(720, min(1800, (int) ($section['max_width'] ?? 1200))),
                'text_align' => self::choice((string) ($section['text_align'] ?? 'left'), ['left', 'center', 'right'], 'left'),
                'card_type' => self::choice((string) ($section['card_type'] ?? 'text'), ['text', 'image_label', 'offer_icon_tab', 'step', 'problem_solution'], 'text'),
                'card_design' => self::choice((string) ($section['card_design'] ?? 'standard'), ['standard', 'compact', 'bordered', 'filled', 'minimal', 'accent'], 'standard'),
                'equal_height' => !empty($section['equal_height']),
                'text_color' => CMS_Beratung_Settings::color((string) ($section['text_color'] ?? '#111827'), '#111827'),
                'columns' => max(1, min(4, $columns)),
                'anchor' => CMS_Beratung_Settings::slug((string) ($section['anchor'] ?? $section['title'] ?? 'bereich-' . ($index + 1)), 'bereich-' . ($index + 1)),
                'categories_enabled' => !empty($section['categories_enabled']),
                'note_text' => self::limit(CMS_Beratung_Settings::text((string) ($section['note_text'] ?? '')), 500),
                'display_style' => self::choice((string) ($section['display_style'] ?? 'cards'), ['cards', 'horizontal', 'vertical', 'icon_grid', 'logo_strip', 'compact', 'large'], 'cards'),
                'auto_number' => array_key_exists('auto_number', $section) ? !empty($section['auto_number']) : true,
                'connector' => array_key_exists('connector', $section) ? !empty($section['connector']) : true,
                'title_band_enabled' => !empty($section['title_band_enabled']),
                'title_band_text' => self::limit(CMS_Beratung_Settings::text((string) ($section['title_band_text'] ?? '')), 255),
                'title_band_background_color' => CMS_Beratung_Settings::color((string) ($section['title_band_background_color'] ?? '#1e3a8a'), '#1e3a8a'),
                'title_band_text_color' => CMS_Beratung_Settings::color((string) ($section['title_band_text_color'] ?? '#ffffff'), '#ffffff'),
                'comparison_variant' => self::choice((string) ($section['comparison_variant'] ?? 'two_columns'), ['two_columns', 'three_columns', 'current_target', 'problem_solution', 'genai_agentic', 'copilot_search', 'governance'], 'two_columns'),
                'border_enabled' => array_key_exists('border_enabled', $section) ? !empty($section['border_enabled']) : true,
                'shadow_enabled' => array_key_exists('shadow_enabled', $section) ? !empty($section['shadow_enabled']) : true,
                'mobile_stack' => array_key_exists('mobile_stack', $section) ? !empty($section['mobile_stack']) : true,
                'faq_allow_multiple' => !empty($section['faq_allow_multiple']),
                'faq_icon_style' => self::choice((string) ($section['faq_icon_style'] ?? 'plus'), ['plus', 'chevron', 'question'], 'plus'),
                'faq_open_behavior' => self::choice((string) ($section['faq_open_behavior'] ?? 'none'), ['none', 'first', 'custom'], 'none'),
                'faq_question_background_color' => CMS_Beratung_Settings::color((string) ($section['faq_question_background_color'] ?? '#ffffff'), '#ffffff'),
                'faq_question_text_color' => CMS_Beratung_Settings::color((string) ($section['faq_question_text_color'] ?? '#111827'), '#111827'),
                'faq_answer_background_color' => CMS_Beratung_Settings::color((string) ($section['faq_answer_background_color'] ?? '#f8fafc'), '#f8fafc'),
                'faq_schema_enabled' => array_key_exists('faq_schema_enabled', $section) ? !empty($section['faq_schema_enabled']) : true,
                'button_1_text' => self::limit(CMS_Beratung_Settings::text((string) ($section['button_1_text'] ?? $section['cta_text'] ?? '')), 90),
                'button_1_target' => self::button_target((string) ($section['button_1_target'] ?? $section['cta_target'] ?? ''), $button1Type),
                'button_1_target_type' => self::choice($button1Type, ['internal', 'external', 'anchor', 'contact', 'email', 'phone', 'bookings', 'download'], 'internal'),
                'button_1_style' => self::choice((string) ($section['button_1_style'] ?? $section['cta_style'] ?? 'primary'), ['primary', 'secondary', 'ghost', 'link'], 'primary'),
                'button_2_text' => self::limit(CMS_Beratung_Settings::text((string) ($section['button_2_text'] ?? '')), 90),
                'button_2_target' => self::button_target((string) ($section['button_2_target'] ?? ''), $button2Type),
                'button_2_target_type' => self::choice($button2Type, ['internal', 'external', 'anchor', 'contact', 'email', 'phone', 'bookings', 'download'], 'internal'),
                'button_2_style' => self::choice((string) ($section['button_2_style'] ?? 'ghost'), ['primary', 'secondary', 'ghost', 'link'], 'ghost'),
                'divider_type' => self::choice((string) ($section['divider_type'] ?? 'line'), ['line', 'title_band', 'icon', 'color_block', 'wave', 'quote', 'cta', 'numbered', 'spacer'], 'line'),
                'divider_title' => self::limit(CMS_Beratung_Settings::text((string) ($section['divider_title'] ?? $section['title'] ?? '')), 180),
                'divider_subtitle' => self::limit(CMS_Beratung_Settings::text((string) ($section['divider_subtitle'] ?? $section['intro'] ?? '')), 500),
                'divider_icon' => self::limit(CMS_Beratung_Settings::text((string) ($section['divider_icon'] ?? '')), 40),
                'divider_line_color' => CMS_Beratung_Settings::color((string) ($section['divider_line_color'] ?? '#dbeafe'), '#dbeafe'),
                'divider_width' => max(20, min(100, (int) ($section['divider_width'] ?? 100))),
                'divider_mobile_behavior' => self::choice((string) ($section['divider_mobile_behavior'] ?? 'stack'), ['stack', 'compact', 'hide_visual'], 'stack'),
                'cards' => self::sanitize_cards($section['cards'] ?? []),
                'html' => self::limit(self::safe_html((string) ($section['html'] ?? '')), 12000),
            ];
        }
        return $sections;
    }

    /** @param mixed $raw @return array<int,array<string,mixed>> */
    private static function sanitize_cards(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $cards = [];
        foreach (array_values($raw) as $card) {
            if (!is_array($card)) {
                continue;
            }
            $cardType = self::choice((string) ($card['card_type'] ?? $card['type'] ?? 'text'), ['text', 'image_label', 'offer_icon_tab', 'step', 'problem_solution'], 'text');
            $cards[] = [
                'enabled' => array_key_exists('enabled', $card) ? !empty($card['enabled']) : true,
                'card_type' => $cardType,
                'icon' => self::limit(CMS_Beratung_Settings::text((string) ($card['icon'] ?? '')), 40),
                'icon_background' => CMS_Beratung_Settings::color((string) ($card['icon_background'] ?? '#eff6ff'), '#eff6ff'),
                'icon_color' => CMS_Beratung_Settings::color((string) ($card['icon_color'] ?? '#2563eb'), '#2563eb'),
                'image_url' => CMS_Beratung_Settings::image_url((string) ($card['image_url'] ?? '')),
                'image_alt' => self::limit(CMS_Beratung_Settings::text((string) ($card['image_alt'] ?? '')), 160),
                'image_height' => max(120, min(520, (int) ($card['image_height'] ?? 230))),
                'image_fit' => self::choice((string) ($card['image_fit'] ?? 'cover'), ['cover', 'contain'], 'cover'),
                'label' => self::limit(CMS_Beratung_Settings::text((string) ($card['label'] ?? '')), 120),
                'label_position' => self::choice((string) ($card['label_position'] ?? 'left'), ['left', 'center', 'right'], 'left'),
                'label_style' => self::choice((string) ($card['label_style'] ?? 'filled'), ['border', 'filled', 'transparent'], 'filled'),
                'category' => self::limit(CMS_Beratung_Settings::text((string) ($card['category'] ?? '')), 120),
                'name' => self::limit(CMS_Beratung_Settings::text((string) ($card['name'] ?? $card['title'] ?? '')), 160),
                'title' => self::limit(CMS_Beratung_Settings::text((string) ($card['title'] ?? '')), 255),
                'text' => self::limit(CMS_Beratung_Settings::text((string) ($card['text'] ?? '')), 1000),
                'extra_text' => self::limit(CMS_Beratung_Settings::text((string) ($card['extra_text'] ?? $card['typical_use'] ?? '')), 1000),
                'metric' => self::limit(CMS_Beratung_Settings::text((string) ($card['metric'] ?? '')), 80),
                'logo_url' => CMS_Beratung_Settings::image_url((string) ($card['logo_url'] ?? '')),
                'question' => self::limit(CMS_Beratung_Settings::text((string) ($card['question'] ?? $card['title'] ?? '')), 255),
                'answer' => self::limit(CMS_Beratung_Settings::text((string) ($card['answer'] ?? $card['text'] ?? '')), 1800),
                'default_open' => !empty($card['default_open']),
                'button_label' => self::limit(CMS_Beratung_Settings::text((string) ($card['button_label'] ?? '')), 80),
                'button_url' => CMS_Beratung_Settings::public_url((string) ($card['button_url'] ?? '')),
                'badge' => self::limit(CMS_Beratung_Settings::text((string) ($card['badge'] ?? '')), 80),
                'featured' => !empty($card['featured']),
                'background_color' => CMS_Beratung_Settings::color((string) ($card['background_color'] ?? '#ffffff'), '#ffffff'),
                'text_color' => CMS_Beratung_Settings::color((string) ($card['text_color'] ?? '#111827'), '#111827'),
                'border_enabled' => array_key_exists('border_enabled', $card) ? !empty($card['border_enabled']) : true,
                'border_color' => CMS_Beratung_Settings::color((string) ($card['border_color'] ?? '#dbeafe'), '#dbeafe'),
                'shadow_enabled' => array_key_exists('shadow_enabled', $card) ? !empty($card['shadow_enabled']) : true,
                'hover_enabled' => array_key_exists('hover_enabled', $card) ? !empty($card['hover_enabled']) : true,
                'tab_enabled' => !empty($card['tab_enabled']),
                'card_size' => self::choice((string) ($card['card_size'] ?? 'medium'), ['small', 'medium', 'large'], 'medium'),
                'step_number' => self::limit(CMS_Beratung_Settings::text((string) ($card['step_number'] ?? '')), 20),
                'auto_number' => !empty($card['auto_number']),
                'connector' => !empty($card['connector']),
                'problem_title' => self::limit(CMS_Beratung_Settings::text((string) ($card['problem_title'] ?? '')), 180),
                'problem_text' => self::limit(CMS_Beratung_Settings::text((string) ($card['problem_text'] ?? '')), 800),
                'solution_title' => self::limit(CMS_Beratung_Settings::text((string) ($card['solution_title'] ?? '')), 180),
                'solution_text' => self::limit(CMS_Beratung_Settings::text((string) ($card['solution_text'] ?? '')), 800),
                'risk_level' => self::choice((string) ($card['risk_level'] ?? 'medium'), ['low', 'medium', 'high'], 'medium'),
                'recommendation' => self::limit(CMS_Beratung_Settings::text((string) ($card['recommendation'] ?? '')), 500),
                'sort_order' => (int) ($card['sort_order'] ?? 0),
            ];
        }
        return $cards;
    }

    private static function choice(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private static function button_target(string $value, string $type): string
    {
        $value = trim($value);
        if ($type === 'contact') {
            return '#kontakt';
        }
        if ($type === 'anchor') {
            $anchor = CMS_Beratung_Settings::slug(ltrim($value, '#'), 'kontakt');
            return '#' . $anchor;
        }
        if ($type === 'email') {
            return filter_var($value, FILTER_VALIDATE_EMAIL) ? 'mailto:' . $value : CMS_Beratung_Settings::public_url($value);
        }
        if ($type === 'phone') {
            $phone = preg_replace('/[^0-9+]/', '', $value) ?? '';
            return $phone !== '' ? 'tel:' . $phone : '';
        }
        return CMS_Beratung_Settings::public_url($value);
    }

    /** @param mixed $raw @return array<string,string> */
    private static function sanitize_design(mixed $raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($raw)) {
            return [];
        }
        $allowed = ['primary_color', 'secondary_color', 'accent_color', 'background_color', 'text_color', 'heading_color', 'button_color', 'button_text_color', 'card_background_color', 'card_border_color'];
        $design = [];
        foreach ($allowed as $key) {
            if (isset($raw[$key])) {
                $design[$key] = CMS_Beratung_Settings::color((string) $raw[$key], CMS_Beratung_Settings::defaults()[$key] ?? '#ffffff');
            }
        }
        return $design;
    }

    private static function css_class(string $value): string
    {
        $value = trim((string) preg_replace('/[^a-z0-9_\-\s]+/i', '', strip_tags($value)));
        return self::limit((string) preg_replace('/\s+/', ' ', $value), 120);
    }

    private static function section_id(string $value): string
    {
        return self::limit(CMS_Beratung_Settings::slug($value, 'section'), 80);
    }

    private static function safe_html(string $value): string
    {
        $value = preg_replace('#<(script|style|iframe|object|embed|form|input|button|textarea|select)\b[^>]*>.*?</\1>#is', '', $value) ?? '';
        $value = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $value) ?? '';
        $value = preg_replace('/(href|src)\s*=\s*("|\')\s*javascript:[^"\']*\2/i', '$1="#"', $value) ?? '';
        $allowed = '<p><br><strong><b><em><i><u><span><small><mark><ul><ol><li><a><blockquote><code><pre><h2><h3><h4><h5><h6><div><section><article><figure><figcaption><img><table><thead><tbody><tr><th><td><hr>';
        return strip_tags($value, $allowed);
    }

    private static function limit(string $value, int $length): string
    {
        return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
    }
}
