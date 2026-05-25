<?php
/**
 * Cron-Job-Handler für CMS Feed
 *
 * Verarbeitet die Fetch-Queue im Hintergrund:
 * - Holt ausstehende Tasks aus der Warteschlange (max. 5 pro Durchlauf)
 * - Ruft die RSS-Feeds ab und aktualisiert den Status
 * - Räumt alte Queue-Einträge auf
 *
 * @package CMS_Feed
 * @since   1.2.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('CMS_Feed_Cron', false)) {
    return;
}

final class CMS_Feed_Cron
{
    private static ?self $instance = null;

    /** Max. Kanäle pro Cron-Durchlauf */
    private const BATCH_SIZE = 5;
    private const MAX_BATCH_SIZE = 25;
    private const AUTO_CLEANUP_DAYS = 7;
    private const HOMEPAGE_THEME_SLUG = 'cms-phinit';

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        if (class_exists('CMS\Hooks')) {
            CMS\Hooks::addAction('cms_cron_mail_queue', [$this, 'run_cron_tick'], 20);
            CMS\Hooks::addAction('cms_cron_hourly', [$this, 'process_queue'], 20);
            CMS\Hooks::addAction('cms_cron_feeds', [$this, 'run_cron_tick'], 10);
        }
    }

    /**
     * Ausstehende Queue-Tasks verarbeiten.
     *
     * Wird per cms_cron_hourly aufgerufen. Holt max. BATCH_SIZE Aufgaben
     * aus der Warteschlange und führt den RSS-Abruf durch.
     *
     * @return array Ergebnis-Array mit processed/success/failed Zähler
     */
    public function process_queue(array $context = []): array
    {
        $context['source'] = (string) ($context['source'] ?? 'cms_cron_hourly');

        return $this->run_cron_tick($context);
    }

    /**
     * Zentraler Feed-Cron-Tick für `/cron.php`.
     *
     * Dieser Worker wird über `task=all`, `task=mail-queue`, `task=hourly`
     * und den expliziten Hook `cms_cron_feeds` erreicht. Er reiht fällige
     * Kanäle anhand ihres `fetch_interval` ein und verarbeitet anschließend
     * einen begrenzten Batch aus der Queue.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function run_cron_tick(array $context = []): array
    {
        $db      = CMS_Feed_Database::instance();
        $fetcher = CMS_Feed_RSS_Fetcher::instance();

        $result = [
            'executed'  => true,
            'mode'      => 'feed-cron-tick',
            'queued'    => 0,
            'requeued'  => 0,
            'processed' => 0,
            'success'   => 0,
            'failed'    => 0,
            'new_items' => 0,
            'cleaned_up' => 0,
        ];

        $result['requeued'] += $db->release_stale_processing_tasks();

        if (!empty($context['force'])) {
            $result['queued'] += $db->add_to_fetch_queue($this->get_active_channel_ids());
        } else {
            if ($this->should_enqueue_priority_channels($context)) {
                $result['queued'] += $db->add_to_fetch_queue($this->get_priority_channel_ids());
            }
            $result['queued'] += $fetcher->enqueue_due_channels();
        }

        $result = $this->merge_results($result, $this->drain_pending_queue($context));

        // Alte erledigte Einträge aufräumen (älter als 7 Tage)
        $db->cleanup_queue(7);
        $result['cleaned_up'] = $db->cleanup_old_items(self::AUTO_CLEANUP_DAYS);

        return $result;
    }

    /**
     * Bereits eingereihte Queue-Tasks in kleinen Batches verarbeiten.
     *
    * Wird intern vom zentralen Cron-Tick genutzt. Neue fällige Kanäle werden
    * vorher in `run_cron_tick()` eingereiht; diese Methode übernimmt nur den
    * kontrollierten Batch-Abruf bereits ausstehender Queue-Tasks.
     *
     * @param array<string, mixed> $context
    * @return array{queued:int, processed:int, success:int, failed:int, new_items:int, cleaned_up:int}
     */
    public function drain_pending_queue(array $context = []): array
    {
        $db      = CMS_Feed_Database::instance();
        $fetcher = CMS_Feed_RSS_Fetcher::instance();

        $result = [
            'queued' => 0,
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'new_items' => 0,
            'cleaned_up' => 0,
        ];

        $tasks = $db->get_pending_queue_tasks($this->resolve_batch_size($context));
        if (empty($tasks)) {
            return $result;
        }

        foreach ($tasks as $task) {
            $result['processed']++;

            try {
                $fetchResult = $fetcher->fetch_channel((int) $task['channel_id']);

                if (!empty($fetchResult['success'])) {
                    $db->update_queue_task((int) $task['id'], 'done');
                    $result['success']++;
                    $result['new_items'] += (int) ($fetchResult['new_items'] ?? 0);
                    continue;
                }

                $db->update_queue_task(
                    (int) $task['id'],
                    'failed',
                    (string) ($fetchResult['error'] ?? 'Unbekannter Fehler')
                );
                $result['failed']++;
            } catch (\Throwable $e) {
                $db->update_queue_task(
                    (int) $task['id'],
                    'failed',
                    $e->getMessage()
                );
                $result['failed']++;
                CMS_Feed_Error_Handler::instance()->log_exception('CMS Feed Cron: Kanalverarbeitung fehlgeschlagen.', $e, 'error', [
                    'scope' => 'cron.drain_channel',
                    'channel_id' => (int) ($task['channel_id'] ?? 0),
                    'queue_id' => (int) ($task['id'] ?? 0),
                ]);
            }
        }

        return $result;
    }

    /**
     * Bevorzugte Kanäle aus der aktiven cms-phinit-Startseite laden.
     *
     * Diese Kanäle werden bei jedem stündlichen Cron-Lauf erneut geprüft,
     * damit die Homepage-Feeds frischer sind als "irgendwann bei nächster Gelegenheit".
     *
     * @return array<int>
     */
    private function get_priority_channel_ids(): array
    {
        if (!class_exists('\CMS\Services\ThemeCustomizer')) {
            return [];
        }

        try {
            $customizer = \CMS\Services\ThemeCustomizer::instance();
            if ($customizer->getTheme() !== self::HOMEPAGE_THEME_SLUG) {
                return [];
            }

            $showFeeds = filter_var($customizer->get('homepage', 'show_feed_section', true), FILTER_VALIDATE_BOOLEAN);
            if (!$showFeeds) {
                return [];
            }

            $candidateIds = array_values(array_unique(array_filter([
                (int) $customizer->get('homepage', 'feed1_channel_id', 0),
                (int) $customizer->get('homepage', 'feed2_channel_id', 0),
            ], static fn (int $channelId): bool => $channelId > 0)));

            if ($candidateIds === []) {
                return [];
            }

            $db = CMS_Feed_Database::instance();

            return array_values(array_filter(
                $candidateIds,
                static function (int $channelId) use ($db): bool {
                    $channel = $db->get_channel($channelId);
                    return is_array($channel) && !empty($channel['is_active']);
                }
            ));
        } catch (\Throwable $e) {
            CMS_Feed_Error_Handler::instance()->log_exception('CMS Feed Cron: Homepage-Feed-Priorisierung fehlgeschlagen.', $e, 'warning', ['scope' => 'cron.priority_channels']);
            return [];
        }
    }

    /**
     * @return array<int>
     */
    private function get_active_channel_ids(): array
    {
        $channels = CMS_Feed_Database::instance()->get_channels();

        return array_values(array_unique(array_filter(
            array_map(
                static fn (array $channel): int => !empty($channel['is_active']) ? (int) ($channel['id'] ?? 0) : 0,
                $channels
            ),
            static fn (int $channelId): bool => $channelId > 0
        )));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function should_enqueue_priority_channels(array $context): bool
    {
        $source = (string) ($context['source'] ?? '');

        return $source === 'cms_cron_hourly' || $source === 'hourly' || !empty($context['hourly']);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolve_batch_size(array $context): int
    {
        $limit = filter_var($context['limit'] ?? null, FILTER_VALIDATE_INT);
        if ($limit === false || $limit === null) {
            return self::BATCH_SIZE;
        }

        return min(self::MAX_BATCH_SIZE, max(1, (int) $limit));
    }

    /**
     * Queue-Status abrufen (für Dashboard-Anzeige).
     */
    public function get_status(): array
    {
        return CMS_Feed_Database::instance()->get_queue_stats();
    }

    /**
     * @param array{queued:int, processed:int, success:int, failed:int, new_items:int, cleaned_up:int} $base
     * @param array{queued:int, processed:int, success:int, failed:int, new_items:int, cleaned_up:int} $append
     * @return array{queued:int, processed:int, success:int, failed:int, new_items:int, cleaned_up:int}
     */
    private function merge_results(array $base, array $append): array
    {
        foreach (['queued', 'requeued', 'processed', 'success', 'failed', 'new_items', 'cleaned_up'] as $key) {
            $base[$key] = (int) ($base[$key] ?? 0) + (int) ($append[$key] ?? 0);
        }

        return $base;
    }
}
