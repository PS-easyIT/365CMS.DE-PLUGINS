<?php
/**
 * CMS M365 Calculator – Inline-SVG Icon Helper.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Icons
{
    public static function svg(string $icon): string
    {
        $icon = in_array($icon, ['mailbox', 'license', 'calculator', 'shield', 'storage', 'roi'], true) ? $icon : 'calculator';
        $attrs = ' aria-hidden="true" focusable="false" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"';

        return match ($icon) {
            'mailbox' => '<svg' . $attrs . '><path d="M4 7h16v10H4z"/><path d="M4 8l8 5 8-5"/><path d="M7 17v2"/><path d="M17 17v2"/></svg>',
            'license' => '<svg' . $attrs . '><path d="M7 4h10a2 2 0 0 1 2 2v14l-4-2-3 2-3-2-4 2V6a2 2 0 0 1 2-2z"/><path d="M9 8h6"/><path d="M9 12h6"/></svg>',
            'shield' => '<svg' . $attrs . '><path d="M12 3l7 3v5c0 4.5-2.8 8.4-7 10-4.2-1.6-7-5.5-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/></svg>',
            'storage' => '<svg' . $attrs . '><ellipse cx="12" cy="6" rx="7" ry="3"/><path d="M5 6v6c0 1.7 3.1 3 7 3s7-1.3 7-3V6"/><path d="M5 12v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"/></svg>',
            'roi' => '<svg' . $attrs . '><path d="M4 19V5"/><path d="M4 19h16"/><path d="M7 15l4-4 3 3 5-7"/><path d="M16 7h3v3"/></svg>',
            default => '<svg' . $attrs . '><rect x="5" y="4" width="14" height="16" rx="2"/><path d="M8 8h8"/><path d="M8 12h2"/><path d="M14 12h2"/><path d="M8 16h2"/><path d="M14 16h2"/></svg>',
        };
    }
}
