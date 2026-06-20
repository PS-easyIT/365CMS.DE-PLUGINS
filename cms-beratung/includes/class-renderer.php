<?php
/**
 * CMS Beratung – frontend renderer.
 *
 * @package CMS_Beratung
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Beratung_Renderer
{
    /** @param array<string,mixed> $page @param array{success:bool,message:string} $formResult */
    public static function render(array $page, array $formResult = ['success' => false, 'message' => '']): void
    {
        $settings = CMS_Beratung_Settings::all();
        $design = self::design_tokens($page, $settings);
        $sections = is_array($page['sections'] ?? null) ? $page['sections'] : [];
        $m365Faq = CMS_Beratung_Storage::instance()->m365_faq_config();
        $anchors = array_values(array_filter($sections, static fn(array $section): bool => !empty($section['enabled']) && trim((string) ($section['title'] ?? '')) !== '' && trim((string) ($section['anchor_id'] ?? $section['id'] ?? '')) !== ''));
        if (!empty($m365Faq['enabled'])) {
            $anchors[] = ['anchor_id' => (string) ($m365Faq['anchor_id'] ?? 'faq'), 'title' => (string) ($m365Faq['title'] ?? 'FAQ')];
        }
        $csrfToken = class_exists('CMS\\Security') ? (string) \CMS\Security::instance()->generateToken('beratung_form_' . (int) ($page['id'] ?? 0)) : '';

        include CMS_BERATUNG_PLUGIN_DIR . 'templates/landingpage.php';
    }

    /** @param array<string,mixed> $page @param array<string,string> $settings @return array<string,string> */
    private static function design_tokens(array $page, array $settings): array
    {
        $design = is_array($page['design'] ?? null) ? $page['design'] : [];
        if (empty($page['custom_design_enabled']) || !empty($page['use_global_settings'])) {
            $design = [];
        }

        $keys = [
            'primary_color', 'secondary_color', 'accent_color', 'background_color', 'text_color', 'heading_color',
            'button_color', 'button_text_color', 'card_background_color', 'card_border_color',
        ];
        $tokens = [];
        foreach ($keys as $key) {
            $tokens[$key] = (string) ($design[$key] ?? $settings[$key] ?? CMS_Beratung_Settings::defaults()[$key] ?? '#ffffff');
        }
        $tokens['background_color'] = 'transparent';
        $tokens['border_radius'] = (string) max(2, min(6, (int) ($settings['border_radius'] ?? 4))) . 'px';
        $tokens['spacing'] = (string) max(12, min(25, (int) ($settings['spacing'] ?? 20))) . 'px';
        $tokens['content_width'] = (string) max(720, min(1160, (int) ($page['max_content_width'] ?? $settings['content_width'] ?? 1160))) . 'px';
        $tokens['card_shadow'] = (($settings['card_shadow_enabled'] ?? '1') === '1') ? '0 8px 24px rgba(15, 23, 42, .08)' : 'none';

        return $tokens;
    }

    public static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public static function nl(string $value): string
    {
        return nl2br(self::esc($value));
    }

    public static function safe_html(string $value): string
    {
        $value = preg_replace('#<(script|style|iframe|object|embed|form|input|button|textarea|select)\\b[^>]*>.*?</\\1>#is', '', $value) ?? '';
        $value = preg_replace('/\\s+on[a-z]+\\s*=\\s*("[^"]*"|\'[^\']*\'|[^\\s>]+)/i', '', $value) ?? '';
        $value = preg_replace('/(href|src)\\s*=\\s*("|\')\\s*javascript:[^"\']*\\2/i', '$1="#"', $value) ?? '';
        $allowed = '<p><br><strong><b><em><i><u><span><small><mark><ul><ol><li><a><blockquote><code><pre><h2><h3><h4><h5><h6><div><section><article><figure><figcaption><img><table><thead><tbody><tr><th><td><hr>';
        return strip_tags($value, $allowed);
    }
}
