<?php
/**
 * CMS M365 Calculator – JSON-Kataloge.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Catalog
{
    /**
     * @return array<string,mixed>
     */
    public static function rules(): array
    {
        return self::load_json('shared_mailbox_rules.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function license_matrix(): array
    {
        return self::load_json('mailbox_license_matrix.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function scenarios(): array
    {
        return self::load_json('shared_mailbox_scenarios.json');
    }

    /**
     * @return array<string,mixed>
     */
    public static function pricing(): array
    {
        return self::load_json('pricing.json');
    }

    /**
     * @return array<string,mixed>
     */
    private static function load_json(string $file): array
    {
        $path = CMS_M365CALCULATOR_PLUGIN_DIR . 'data/' . $file;
        if (!file_exists($path)) {
            return [];
        }

        $json = file_get_contents($path);
        if (!is_string($json) || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }
}
