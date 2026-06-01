<?php
/**
 * Shared tool-page header actions.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('cms_m365tools_tool_header_actions_html')) {
    function cms_m365tools_tool_header_actions_html(string $toolOverviewUrl, string $contactUrl): string
    {
        $esc = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $items = [];

        if ($toolOverviewUrl !== '') {
            $items[] = '<a class="phinit-btn phinit-btn--secondary m365calc-hero-action" href="' . $esc($toolOverviewUrl) . '">'
                . '<span class="m365calc-hero-action__icon" aria-hidden="true">'
                . '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h4A1.5 1.5 0 0 1 11 5.5v4A1.5 1.5 0 0 1 9.5 11h-4A1.5 1.5 0 0 1 4 9.5v-4Zm9 0A1.5 1.5 0 0 1 14.5 4h4A1.5 1.5 0 0 1 20 5.5v4a1.5 1.5 0 0 1-1.5 1.5h-4A1.5 1.5 0 0 1 13 9.5v-4Zm-9 9A1.5 1.5 0 0 1 5.5 13h4a1.5 1.5 0 0 1 1.5 1.5v4A1.5 1.5 0 0 1 9.5 20h-4A1.5 1.5 0 0 1 4 18.5v-4Zm9 0a1.5 1.5 0 0 1 1.5-1.5h4a1.5 1.5 0 0 1 1.5 1.5v4a1.5 1.5 0 0 1-1.5 1.5h-4a1.5 1.5 0 0 1-1.5-1.5v-4Z" fill="currentColor"/></svg>'
                . '</span>'
                . '<span>Alle Tools anzeigen</span>'
                . '</a>';
        }

        if ($contactUrl !== '') {
            $items[] = '<a class="phinit-btn phinit-btn--secondary m365calc-hero-action" href="' . $esc($contactUrl) . '">'
                . '<span class="m365calc-hero-action__icon" aria-hidden="true">'
                . '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M5.75 6A2.75 2.75 0 0 0 3 8.75v6.5A2.75 2.75 0 0 0 5.75 18h12.5A2.75 2.75 0 0 0 21 15.25v-6.5A2.75 2.75 0 0 0 18.25 6H5.75Zm0 1.5h12.5c.23 0 .45.05.64.14L12 12.18 5.11 7.64c.19-.09.41-.14.64-.14Zm-1.25 7.75V8.96l6.84 4.51a1.2 1.2 0 0 0 1.32 0l6.84-4.51v6.29c0 .69-.56 1.25-1.25 1.25H5.75c-.69 0-1.25-.56-1.25-1.25Z" fill="currentColor"/></svg>'
                . '</span>'
                . '<span>Kontaktanfrage</span>'
                . '</a>';
        }

        if ($items === []) {
            return '';
        }

        return '<nav class="m365calc-hero__global-actions" aria-label="Tool-Schnellzugriff">'
            . implode('', $items)
            . '</nav>';
    }
}
