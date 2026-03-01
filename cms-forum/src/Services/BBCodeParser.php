<?php
/**
 * CMS Forum – BBCode Parser
 *
 * Leichtgewichtiger BBCode-Parser mit Sicherheitsregeln.
 * Maximale Verschachtelungstiefe: 5 Ebenen.
 * Erlaubte URL-Protokolle: http, https, mailto.
 *
 * @package CMS_Forum\Services
 */

declare(strict_types=1);

namespace CMS_Forum\Services;

if (!defined('ABSPATH')) {
    exit;
}

final class BBCodeParser
{
    private const int MAX_NESTING = 5;
    private const array ALLOWED_PROTOCOLS = ['http', 'https', 'mailto'];

    private static ?self $instance = null;

    public static function instance(): static
    {
        return static::$instance ??= new static();
    }

    private function __construct() {}

    /**
     * BBCode in HTML umwandeln.
     */
    public function parse(string $text): string
    {
        // 1. HTML-Entities escapen (Sicherheit vor dem Parsen)
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // 2. Zeilenumbrüche standardisieren
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // 3. BBCode-Tags parsen
        $text = $this->parseFormatting($text);
        $text = $this->parseLinks($text);
        $text = $this->parseMedia($text);
        $text = $this->parseStructure($text);
        $text = $this->parseLists($text);

        // 4. Zeilenumbrüche in <br> umwandeln (außerhalb von Code-Blöcken)
        $text = $this->convertNewlines($text);

        return $text;
    }

    /**
     * BBCode-Tags entfernen (für Vorschau/Excerpts).
     */
    public function stripBBCode(string $text): string
    {
        return preg_replace('/\[.*?\]/', '', $text) ?? $text;
    }

    /**
     * Formatierungstags: [b], [i], [u], [s], [color=], [size=], [font=]
     */
    private function parseFormatting(string $text): string
    {
        $replacements = [
            '/\[b\](.*?)\[\/b\]/si'   => '<strong>$1</strong>',
            '/\[i\](.*?)\[\/i\]/si'   => '<em>$1</em>',
            '/\[u\](.*?)\[\/u\]/si'   => '<u>$1</u>',
            '/\[s\](.*?)\[\/s\]/si'   => '<del>$1</del>',
        ];

        foreach ($replacements as $pattern => $replace) {
            $text = preg_replace($pattern, $replace, $text) ?? $text;
        }

        // [color=X]...[/color]
        $text = preg_replace_callback(
            '/\[color=([a-zA-Z#0-9]+)\](.*?)\[\/color\]/si',
            function (array $m): string {
                $color = preg_match('/^(#[0-9a-fA-F]{3,6}|[a-zA-Z]+)$/', $m[1]) ? $m[1] : '#000';
                return '<span style="color:' . $color . ';">' . $m[2] . '</span>';
            },
            $text
        ) ?? $text;

        // [size=X]...[/size] (1-7 oder Pixelwert 8-72)
        $text = preg_replace_callback(
            '/\[size=(\d+)\](.*?)\[\/size\]/si',
            function (array $m): string {
                $size = max(8, min(72, (int) $m[1]));
                return '<span style="font-size:' . $size . 'px;">' . $m[2] . '</span>';
            },
            $text
        ) ?? $text;

        return $text;
    }

    /**
     * Links: [url], [url=X], [email]
     */
    private function parseLinks(string $text): string
    {
        // [url=X]Text[/url]
        $text = preg_replace_callback(
            '/\[url=([^\]]+)\](.*?)\[\/url\]/si',
            function (array $m): string {
                $url = $this->sanitizeUrl($m[1]);
                return $url ? '<a href="' . $url . '" rel="noopener noreferrer" target="_blank">' . $m[2] . '</a>' : $m[2];
            },
            $text
        ) ?? $text;

        // [url]X[/url]
        $text = preg_replace_callback(
            '/\[url\](.*?)\[\/url\]/si',
            function (array $m): string {
                $url = $this->sanitizeUrl($m[1]);
                return $url ? '<a href="' . $url . '" rel="noopener noreferrer" target="_blank">' . $url . '</a>' : $m[1];
            },
            $text
        ) ?? $text;

        // [email]X[/email]
        $text = preg_replace_callback(
            '/\[email\](.*?)\[\/email\]/si',
            function (array $m): string {
                $email = filter_var(html_entity_decode($m[1]), FILTER_VALIDATE_EMAIL);
                return $email ? '<a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a>' : $m[1];
            },
            $text
        ) ?? $text;

        return $text;
    }

    /**
     * Medien: [img], [youtube]
     */
    private function parseMedia(string $text): string
    {
        // [img]URL[/img]
        $text = preg_replace_callback(
            '/\[img\](.*?)\[\/img\]/si',
            function (array $m): string {
                $url = $this->sanitizeUrl($m[1]);
                if (!$url || !str_starts_with($url, 'http')) {
                    return $m[1];
                }
                return '<img src="' . $url . '" alt="Bild" loading="lazy" class="cmsforum-bbcode-img">';
            },
            $text
        ) ?? $text;

        // [youtube]ID_oder_URL[/youtube]
        $text = preg_replace_callback(
            '/\[youtube\](.*?)\[\/youtube\]/si',
            function (array $m): string {
                $id = $this->extractYoutubeId(html_entity_decode($m[1]));
                if (!$id) {
                    return $m[1];
                }
                return '<div class="cmsforum-video-embed"><iframe src="https://www.youtube-nocookie.com/embed/' . htmlspecialchars($id)
                    . '" allowfullscreen loading="lazy" referrerpolicy="no-referrer"></iframe></div>';
            },
            $text
        ) ?? $text;

        return $text;
    }

    /**
     * Strukturelemente: [quote], [code], [spoiler], [hr], [align=], [indent]
     */
    private function parseStructure(string $text): string
    {
        // [quote=User]...[/quote]
        $text = preg_replace(
            '/\[quote=([^\]]+)\](.*?)\[\/quote\]/si',
            '<blockquote class="cmsforum-quote"><cite>$1 schrieb:</cite><div>$2</div></blockquote>',
            $text
        ) ?? $text;

        // [quote]...[/quote]
        $text = preg_replace(
            '/\[quote\](.*?)\[\/quote\]/si',
            '<blockquote class="cmsforum-quote"><div>$1</div></blockquote>',
            $text
        ) ?? $text;

        // [code]...[/code]
        $text = preg_replace(
            '/\[code\](.*?)\[\/code\]/si',
            '<pre class="cmsforum-code"><code>$1</code></pre>',
            $text
        ) ?? $text;

        // [spoiler=Titel]...[/spoiler]
        $text = preg_replace(
            '/\[spoiler=([^\]]+)\](.*?)\[\/spoiler\]/si',
            '<details class="cmsforum-spoiler"><summary>$1</summary><div>$2</div></details>',
            $text
        ) ?? $text;

        // [spoiler]...[/spoiler]
        $text = preg_replace(
            '/\[spoiler\](.*?)\[\/spoiler\]/si',
            '<details class="cmsforum-spoiler"><summary>Spoiler</summary><div>$1</div></details>',
            $text
        ) ?? $text;

        // [hr]
        $text = str_replace('[hr]', '<hr class="cmsforum-hr">', $text);

        // [align=X]...[/align]
        $text = preg_replace_callback(
            '/\[align=(left|center|right|justify)\](.*?)\[\/align\]/si',
            function (array $m): string {
                return '<div style="text-align:' . $m[1] . ';">' . $m[2] . '</div>';
            },
            $text
        ) ?? $text;

        // [indent]...[/indent]
        $text = preg_replace(
            '/\[indent\](.*?)\[\/indent\]/si',
            '<div style="margin-left:2rem;">$1</div>',
            $text
        ) ?? $text;

        return $text;
    }

    /**
     * Listen: [list], [list=1], [*]
     */
    private function parseLists(string $text): string
    {
        // Geordnete Liste [list=1]
        $text = preg_replace_callback(
            '/\[list=1\](.*?)\[\/list\]/si',
            function (array $m): string {
                $items = preg_replace('/\[\*\]\s*/', '<li>', $m[1]) ?? $m[1];
                return '<ol class="cmsforum-list">' . $items . '</ol>';
            },
            $text
        ) ?? $text;

        // Ungeordnete Liste [list]
        $text = preg_replace_callback(
            '/\[list\](.*?)\[\/list\]/si',
            function (array $m): string {
                $items = preg_replace('/\[\*\]\s*/', '<li>', $m[1]) ?? $m[1];
                return '<ul class="cmsforum-list">' . $items . '</ul>';
            },
            $text
        ) ?? $text;

        return $text;
    }

    /**
     * URL auf erlaubte Protokolle prüfen.
     */
    private function sanitizeUrl(string $url): string
    {
        $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');
        $url = trim($url);

        // javascript: blockieren
        if (preg_match('/^\s*javascript\s*:/i', $url)) {
            return '';
        }

        // Protokoll prüfen
        $parsed = parse_url($url);
        if (isset($parsed['scheme']) && !in_array(strtolower($parsed['scheme']), self::ALLOWED_PROTOCOLS, true)) {
            return '';
        }

        // Wenn kein Protokoll, http:// voranstellen
        if (!isset($parsed['scheme']) && !str_starts_with($url, 'mailto:')) {
            $url = 'https://' . $url;
        }

        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }

    /**
     * YouTube-Video-ID extrahieren.
     */
    private function extractYoutubeId(string $input): string
    {
        $input = trim($input);

        // Direkte ID (11 Zeichen, alphanumerisch + Bindestriche)
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $input)) {
            return $input;
        }

        // URL parsen
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $input, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Zeilenumbrüche in BR umwandeln (nicht in Code-Blöcken).
     */
    private function convertNewlines(string $text): string
    {
        // Code-Blöcke schützen
        $parts = preg_split('/(<pre class="cmsforum-code">.*?<\/pre>)/si', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return nl2br($text);
        }

        $result = '';
        foreach ($parts as $i => $part) {
            if ($i % 2 === 0) {
                $result .= nl2br($part);
            } else {
                $result .= $part;
            }
        }

        return $result;
    }
}
