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

final class CMS_Feed_Cron
{
    private static ?self $instance = null;

    /** Max. Kanäle pro Cron-Durchlauf */
    private const BATCH_SIZE = 5;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        if (class_exists('CMS\Hooks')) {
            CMS\Hooks::addAction('cms_cron_hourly', [$this, 'process_queue'], 20);
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
    public function process_queue(): array
    {
        $db      = CMS_Feed_Database::instance();
        $fetcher = CMS_Feed_RSS_Fetcher::instance();

        $result = [
            'queued'    => 0,
            'processed' => 0,
            'success'   => 0,
            'failed'    => 0,
            'new_items' => 0,
        ];

        $result['queued'] = $fetcher->enqueue_due_channels();

        // Ausstehende Tasks holen (max. BATCH_SIZE)
        $tasks = $db->get_pending_queue_tasks(self::BATCH_SIZE);

        if (empty($tasks)) {
            // Nebenbei alte Einträge aufräumen
            $db->cleanup_queue(7);
            return $result;
        }

        foreach ($tasks as $task) {
            $result['processed']++;

            try {
                $fetchResult = $fetcher->fetch_channel((int) $task['channel_id']);

                if ($fetchResult['success']) {
                    $db->update_queue_task((int) $task['id'], 'done');
                    $result['success']++;
                    $result['new_items'] += $fetchResult['new_items'] ?? 0;
                } else {
                    $db->update_queue_task(
                        (int) $task['id'],
                        'failed',
                        $fetchResult['error'] ?? 'Unbekannter Fehler'
                    );
                    $result['failed']++;
                }
            } catch (\Throwable $e) {
                $db->update_queue_task(
                    (int) $task['id'],
                    'failed',
                    $e->getMessage()
                );
                $result['failed']++;
                error_log('CMS Feed Cron: Fehler bei Kanal #' . $task['channel_id'] . ' – ' . $e->getMessage());
            }
        }

        // Alte erledigte Einträge aufräumen (älter als 7 Tage)
        $db->cleanup_queue(7);

        return $result;
    }

    /**
     * Queue-Status abrufen (für Dashboard-Anzeige).
     */
    public function get_status(): array
    {
        return CMS_Feed_Database::instance()->get_queue_stats();
    }
}
