<?php
/**
 * CMS Forum – Public i18n helper.
 *
 * @package CMS_Forum\Helpers
 */

declare(strict_types=1);

namespace CMS_Forum\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

final class PublicI18n
{
    /** @var array<string,string>|null */
    private static ?array $de = null;
    /** @var array<string,string>|null */
    private static ?array $en = null;

    public static function lang(): string
    {
        self::bootSharedHelpers();

        if (function_exists('cms_plugin_public_language')) {
            return cms_plugin_public_language();
        }

        return 'de';
    }

    public static function forumPath(string $path = '', ?string $lang = null): string
    {
        self::bootSharedHelpers();

        $cleanPath = trim($path, '/');
        $forumPath = $cleanPath === '' ? 'forum' : ('forum/' . $cleanPath);
        $lang = $lang ?? self::lang();

        if (function_exists('cms_plugin_public_localized_path')) {
            return cms_plugin_public_localized_path($forumPath, $lang);
        }

        if ($lang === 'en') {
            return '/en/' . $forumPath;
        }

        return '/' . $forumPath;
    }

    public static function loginPath(?string $lang = null): string
    {
        self::bootSharedHelpers();

        $lang = $lang ?? self::lang();

        if (function_exists('cms_plugin_public_localized_path')) {
            return cms_plugin_public_localized_path('login', $lang);
        }

        return $lang === 'en' ? '/en/login' : '/login';
    }

    public static function t(string $key, string $fallback = '', mixed ...$args): string
    {
        self::bootLang();
        $lang = self::lang();
        $map = $lang === 'en' ? (self::$en ?? []) : (self::$de ?? []);
        $text = $map[$key] ?? ((self::$de[$key] ?? '') ?: $fallback ?: $key);

        if ($args === []) {
            return $text;
        }

        return (string) sprintf($text, ...$args);
    }

    private static function bootSharedHelpers(): void
    {
        static $booted = false;
        if ($booted) {
            return;
        }

        $shared = dirname(CMS_FORUM_DIR, 1) . '/shared/public/plugin-public-i18n.php';
        if (file_exists($shared)) {
            require_once $shared;
        }

        $booted = true;
    }

    private static function bootLang(): void
    {
        if (self::$de !== null && self::$en !== null) {
            return;
        }

        $de = CMS_FORUM_DIR . 'lang/de_DE.php';
        $en = CMS_FORUM_DIR . 'lang/en_US.php';

        self::$de = file_exists($de) ? (require $de) : [];
        self::$en = file_exists($en) ? (require $en) : [];
    }
}
