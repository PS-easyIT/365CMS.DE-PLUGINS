<?php
/**
 * CMS Forum – Time Helper
 *
 * Relative Zeitangaben ("vor X Minuten") und Formatierungen.
 *
 * @package CMS_Forum\Helpers
 */

declare(strict_types=1);

namespace CMS_Forum\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

final class TimeHelper
{
    /**
     * Zeitangabe relativ formatieren ("vor X Minuten").
     * Zeigt absolute Zeit wenn älter als 7 Tage.
     */
    public static function relative(string $datetime): string
    {
        $time = strtotime($datetime);
        if ($time === false) {
            return $datetime;
        }

        $diff = time() - $time;

        if ($diff < 0) {
            return self::format($datetime);
        }

        if ($diff < 60) {
            return 'gerade eben';
        }

        if ($diff < 3600) {
            $min = (int) floor($diff / 60);
            return "vor {$min} " . ($min === 1 ? 'Minute' : 'Minuten');
        }

        if ($diff < 86400) {
            $hours = (int) floor($diff / 3600);
            return "vor {$hours} " . ($hours === 1 ? 'Stunde' : 'Stunden');
        }

        if ($diff < 604800) { // 7 Tage
            $days = (int) floor($diff / 86400);
            if ($days === 1) {
                return 'gestern, ' . date('H:i', $time);
            }
            return "vor {$days} Tagen";
        }

        return self::format($datetime);
    }

    /**
     * Datum + Uhrzeit im deutschen Format.
     */
    public static function format(string $datetime, string $format = 'd.m.Y, H:i'): string
    {
        $time = strtotime($datetime);
        return $time !== false ? date($format, $time) : $datetime;
    }

    /**
     * Nur Datum im deutschen Format.
     */
    public static function formatDate(string $datetime): string
    {
        return self::format($datetime, 'd.m.Y');
    }

    /**
     * HTML <time>-Element erzeugen.
     */
    public static function tag(string $datetime, bool $relative = true): string
    {
        $display = $relative ? self::relative($datetime) : self::format($datetime);
        $iso     = date('c', strtotime($datetime) ?: time());

        return '<time datetime="' . htmlspecialchars($iso) . '" title="' . htmlspecialchars(self::format($datetime)) . '">'
            . htmlspecialchars($display) . '</time>';
    }
}
