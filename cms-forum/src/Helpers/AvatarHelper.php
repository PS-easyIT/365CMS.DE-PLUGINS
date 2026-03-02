<?php
/**
 * CMS Forum – Avatar Helper
 *
 * Avatar-Rendering mit Fallback auf Initialen-Avatar.
 *
 * @package CMS_Forum\Helpers
 */

declare(strict_types=1);

namespace CMS_Forum\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

final class AvatarHelper
{
    private const array COLORS = [
        '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6',
        '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1',
    ];

    /**
     * Avatar-HTML erzeugen.
     *
     * @param int    $size CSS-Größe in Pixel
     */
    public static function render(string $username, ?string $avatarUrl = null, int $size = 40): string
    {
        if (!empty($avatarUrl)) {
            return '<img src="' . htmlspecialchars($avatarUrl) . '" '
                . 'alt="' . htmlspecialchars($username) . '" '
                . 'class="cmsforum-avatar" '
                . 'width="' . $size . '" height="' . $size . '" '
                . 'loading="lazy">';
        }

        // Initialen-Avatar als SVG
        return self::generateInitials($username, $size);
    }

    /**
     * Initialen-Avatar als Inline-SVG.
     */
    private static function generateInitials(string $username, int $size): string
    {
        $initials = self::getInitials($username);
        $color    = self::getColor($username);
        $fontSize = (int) round($size * 0.4);

        return '<svg class="cmsforum-avatar" width="' . $size . '" height="' . $size . '" '
            . 'viewBox="0 0 ' . $size . ' ' . $size . '" '
            . 'aria-label="Avatar ' . htmlspecialchars($username) . '" role="img">'
            . '<rect width="' . $size . '" height="' . $size . '" rx="' . (int) round($size * 0.2) . '" fill="' . $color . '"/>'
            . '<text x="50%" y="50%" dominant-baseline="central" text-anchor="middle" '
            . 'fill="#fff" font-size="' . $fontSize . '" font-weight="600" font-family="-apple-system,BlinkMacSystemFont,sans-serif">'
            . htmlspecialchars($initials)
            . '</text></svg>';
    }

    /**
     * 1–2 Initialen aus dem Benutzernamen extrahieren.
     */
    private static function getInitials(string $username): string
    {
        $username = trim($username);
        if ($username === '') {
            return '?';
        }

        $parts = preg_split('/[\s._-]+/', $username, 2);
        if ($parts === false || count($parts) === 0) {
            return mb_strtoupper(mb_substr($username, 0, 1));
        }

        $initials = mb_strtoupper(mb_substr($parts[0], 0, 1));
        if (count($parts) > 1 && $parts[1] !== '') {
            $initials .= mb_strtoupper(mb_substr($parts[1], 0, 1));
        }

        return $initials;
    }

    /**
     * Deterministische Farbe basierend auf dem Benutzernamen.
     */
    private static function getColor(string $username): string
    {
        $hash = crc32($username);
        return self::COLORS[abs($hash) % count(self::COLORS)];
    }
}
