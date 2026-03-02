<?php
/**
 * CMS Forum – Slug Helper
 *
 * URL-freundliche Slugs aus Texten generieren.
 *
 * @package CMS_Forum\Helpers
 */

declare(strict_types=1);

namespace CMS_Forum\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

final class SlugHelper
{
    /**
     * Slug aus einem Text erzeugen.
     */
    public static function generate(string $text, int $maxLength = 80): string
    {
        // Deutsche Umlaute ersetzen
        $text = strtr($text, [
            'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
            'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue',
        ]);

        // Kleinbuchstaben
        $text = mb_strtolower($text, 'UTF-8');

        // Nur alphanumerische Zeichen und Bindestriche
        $text = preg_replace('/[^a-z0-9\-]/', '-', $text) ?? $text;

        // Mehrfache Bindestriche zusammenfassen
        $text = preg_replace('/-{2,}/', '-', $text) ?? $text;

        // Trim
        $text = trim($text, '-');

        // Maxlänge
        if (mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength);
            $text = rtrim($text, '-');
        }

        return $text ?: 'untitled';
    }

    /**
     * Eindeutigen Slug für eine Tabelle erzeugen.
     */
    public static function unique(string $text, string $table, string $column = 'slug', ?int $excludeId = null): string
    {
        $slug = self::generate($text);
        $db   = \CMS\Database::instance();
        $p    = $db->prefix();

        $suffix = 0;
        $candidate = $slug;

        while (true) {
            $sql    = "SELECT id FROM {$p}{$table} WHERE {$column} = ?";
            $params = [$candidate];

            if ($excludeId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            if (!$stmt->fetch()) {
                return $candidate;
            }

            $suffix++;
            $candidate = $slug . '-' . $suffix;
        }
    }
}
