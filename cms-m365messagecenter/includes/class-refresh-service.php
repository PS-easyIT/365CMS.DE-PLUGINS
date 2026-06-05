<?php
/**
 * CMS M365 Message Center – Refresh service.
 *
 * @package CMS_M365MessageCenter
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365MessageCenter_Refresh_Service
{
    private const CRON_HOUR = 12;

    /**
     * Fetch Microsoft Graph messages and update the local cache.
     *
     * @param array<string,mixed> $context
     * @return array{success:bool,count:int,error:string,skipped?:bool,reason?:string}
     */
    public static function refresh(array $context = []): array
    {
        try {
            CMS_M365MessageCenter_Installer::maybe_install();
            $repo = CMS_M365MessageCenter_Repository::instance();
            $settings = $repo->settings();
            $client = new CMS_M365MessageCenter_Graph_Client();
            $result = $client->fetch_messages($settings);

            if (empty($result['ok'])) {
                $error = CMS_M365MessageCenter_Repository::text((string) ($result['error'] ?? 'graph-load-failed'), 80);
                $repo->save_fetch_error($error);

                return ['success' => false, 'count' => 0, 'error' => $error];
            }

            $messages = self::filter_messages((array) ($result['messages'] ?? []), $settings);
            $count = $repo->replace_messages($messages);
            $repo->save_settings([
                'last_refresh_source' => CMS_M365MessageCenter_Repository::text((string) ($context['source'] ?? 'manual'), 80),
            ]);

            return ['success' => true, 'count' => $count, 'error' => ''];
        } catch (\Throwable $e) {
            self::log_exception('refresh_failed', $e);
            try {
                CMS_M365MessageCenter_Repository::instance()->save_fetch_error('refresh-exception');
            } catch (\Throwable $inner) {
                self::log_exception('refresh_error_save_failed', $inner);
            }

            return ['success' => false, 'count' => 0, 'error' => 'refresh-exception'];
        }
    }

    /**
     * Run from cron.php. The hourly cron invokes this after noon once per local day.
     * A direct generic cron call with task=cms_cron_m365messagecenter can force it.
     *
     * @param array<string,mixed> $context
     * @return array{success:bool,count:int,error:string,skipped?:bool,reason?:string}
     */
    public static function run_cron(array $context = []): array
    {
        try {
            CMS_M365MessageCenter_Installer::maybe_install();
            $repo = CMS_M365MessageCenter_Repository::instance();
            $settings = $repo->settings();

            if ((string) ($settings['cron_enabled'] ?? '1') !== '1') {
                return ['success' => true, 'count' => 0, 'error' => '', 'skipped' => true, 'reason' => 'cron-disabled'];
            }

            $force = !empty($context['force']);
            if (!$force && !self::is_due($settings)) {
                return ['success' => true, 'count' => 0, 'error' => '', 'skipped' => true, 'reason' => 'not-due'];
            }

            $result = self::refresh(['source' => 'cron']);
            if (!empty($result['success'])) {
                $repo->save_settings([
                    'last_cron_fetch_at' => date('Y-m-d H:i:s'),
                    'last_cron_fetch_date' => date('Y-m-d'),
                    'last_cron_fetch_error' => '',
                ]);
            } else {
                $repo->save_settings([
                    'last_cron_fetch_at' => date('Y-m-d H:i:s'),
                    'last_cron_fetch_error' => CMS_M365MessageCenter_Repository::text((string) ($result['error'] ?? 'cron-fetch-failed'), 80),
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            self::log_exception('cron_failed', $e);

            return ['success' => false, 'count' => 0, 'error' => 'cron-exception'];
        }
    }

    /** @param array<string,string> $settings */
    private static function is_due(array $settings): bool
    {
        $targetHour = max(0, min(23, (int) ($settings['cron_hour'] ?? self::CRON_HOUR)));
        $today = date('Y-m-d');
        if ((string) ($settings['last_cron_fetch_date'] ?? '') === $today) {
            return false;
        }

        return (int) date('G') >= $targetHour;
    }

    /** @param array<int,array<string,mixed>> $messages @param array<string,string> $settings @return array<int,array<string,mixed>> */
    private static function filter_messages(array $messages, array $settings): array
    {
        $serviceNeedles = self::needle_list((string) ($settings['service_filter'] ?? ''));
        $categoryNeedles = self::needle_list((string) ($settings['category_filter'] ?? ''));
        if ($serviceNeedles === [] && $categoryNeedles === []) {
            return $messages;
        }

        return array_values(array_filter($messages, static function (array $message) use ($serviceNeedles, $categoryNeedles): bool {
            if ($categoryNeedles !== []) {
                $category = strtolower((string) ($message['category'] ?? ''));
                $matched = false;
                foreach ($categoryNeedles as $needle) {
                    if ($needle !== '' && str_contains($category, $needle)) {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    return false;
                }
            }

            if ($serviceNeedles !== []) {
                $services = strtolower(implode(' ', array_map('strval', (array) ($message['services'] ?? []))));
                foreach ($serviceNeedles as $needle) {
                    if ($needle !== '' && str_contains($services, $needle)) {
                        return true;
                    }
                }
                return false;
            }

            return true;
        }));
    }

    /** @return array<int,string> */
    private static function needle_list(string $value): array
    {
        $parts = preg_split('/[\s,;|]+/', strtolower(trim($value))) ?: [];
        return array_values(array_filter(array_unique(array_map('trim', $parts))));
    }

    private static function log_exception(string $context, \Throwable $e): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CMS M365 Message Center refresh [' . $context . ']: ' . $e->getMessage());
        }
    }
}
