<?php
/**
 * CMS Booking – Verfügbarkeiten
 *
 * Wochenplan- und Einzeltag-Verwaltung, Slot-Berechnung.
 *
 * @package CMS_Booking
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Booking_Availability
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct() {}

    /* ================================================================== */
    /*  CRUD                                                               */
    /* ================================================================== */

    /**
     * Verfügbarkeits-Slot anlegen.
     */
    public function create(array $data): int
    {
        $db = \CMS\Database::instance();
        $p  = $db->getPrefix();

        $fields = [
            'provider_id'   => (int) ($data['provider_id']),
            'day_of_week'   => isset($data['day_of_week']) ? (int) $data['day_of_week'] : null,
            'specific_date' => isset($data['specific_date']) ? (string) $data['specific_date'] : null,
            'start_time'    => (string) $data['start_time'],
            'end_time'      => (string) $data['end_time'],
            'is_blocked'    => (int) ($data['is_blocked'] ?? 0),
        ];

        $cols = implode(', ', array_keys($fields));
        $ph   = implode(', ', array_fill(0, count($fields), '?'));
        $db->prepare("INSERT INTO {$p}booking_availability ({$cols}) VALUES ({$ph})")
            ->execute(array_values($fields));

        return (int) $db->getPdo()->lastInsertId();
    }

    /**
     * Slot löschen.
     */
    public function delete(int $id): bool
    {
        $db   = \CMS\Database::instance();
        $stmt = $db->prepare("DELETE FROM {$db->getPrefix()}booking_availability WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Alle Slots eines Anbieters löschen (Wochenplan).
     */
    public function delete_weekly(int $providerId): bool
    {
        $db = \CMS\Database::instance();
        $stmt = $db->prepare(
            "DELETE FROM {$db->getPrefix()}booking_availability
             WHERE provider_id = ? AND specific_date IS NULL"
        );
        $stmt->execute([$providerId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Wochenplan ersetzen (alle alten löschen, neue einfügen).
     *
     * @param  int      $providerId
     * @param  array    $weekly  [['day_of_week' => 0, 'start_time' => '09:00', 'end_time' => '17:00'], …]
     */
    public function replace_weekly(int $providerId, array $weekly): void
    {
        $this->delete_weekly($providerId);

        foreach ($weekly as $slot) {
            $this->create([
                'provider_id' => $providerId,
                'day_of_week' => (int) $slot['day_of_week'],
                'start_time'  => (string) $slot['start_time'],
                'end_time'    => (string) $slot['end_time'],
                'is_blocked'  => 0,
            ]);
        }
    }

    /**
     * Wochenplan laden.
     */
    public function get_weekly(int $providerId): array
    {
        $db   = \CMS\Database::instance();
        $stmt = $db->prepare(
            "SELECT * FROM {$db->getPrefix()}booking_availability
             WHERE provider_id = ? AND specific_date IS NULL AND is_blocked = 0
             ORDER BY day_of_week ASC, start_time ASC"
        );
        $stmt->execute([$providerId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Spezifische Datums-Einträge laden (Urlaub / Sondertermine).
     */
    public function get_date_overrides(int $providerId, string $from, string $to): array
    {
        $db   = \CMS\Database::instance();
        $stmt = $db->prepare(
            "SELECT * FROM {$db->getPrefix()}booking_availability
             WHERE provider_id = ? AND specific_date BETWEEN ? AND ?
             ORDER BY specific_date ASC, start_time ASC"
        );
        $stmt->execute([$providerId, $from, $to]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Abwesenheit / Blockierung für ein spezifisches Datum anlegen.
     */
    public function block_date(int $providerId, string $date, string $startTime = '00:00', string $endTime = '23:59'): int
    {
        return $this->create([
            'provider_id'   => $providerId,
            'specific_date' => $date,
            'start_time'    => $startTime,
            'end_time'      => $endTime,
            'is_blocked'    => 1,
        ]);
    }

    /* ================================================================== */
    /*  Slot-Berechnung                                                    */
    /* ================================================================== */

    /**
     * Verfügbare Zeitslots für einen Anbieter an einem bestimmten Datum berechnen.
     *
     * @param  int     $providerId
     * @param  string  $date         Y-m-d
     * @param  int     $durationMin  Dauer je Slot in Minuten
     * @param  int     $bufferMin    Puffer danach
     * @return array   ['09:00', '10:15', …]
     */
    public function get_available_slots(
        int    $providerId,
        string $date,
        int    $durationMin = 60,
        int    $bufferMin   = 15
    ): array {
        // 1. Wochentag bestimmen (0=Mo … 6=So)
        $ts  = strtotime($date);
        $dow = ((int) date('N', $ts)) - 1; // 1=Mo → 0

        // 2. Basis-Slots aus Wochenplan
        $weekly = $this->get_weekly($providerId);
        $ranges = [];
        foreach ($weekly as $slot) {
            if ((int) $slot['day_of_week'] === $dow) {
                $ranges[] = [
                    'start' => $slot['start_time'],
                    'end'   => $slot['end_time'],
                ];
            }
        }

        // 3. Datum-Overrides anwenden
        $overrides = $this->get_date_overrides($providerId, $date, $date);
        foreach ($overrides as $ov) {
            if ((int) $ov['is_blocked'] === 1) {
                // Range entfernen / einschränken
                $ranges = $this->subtract_range($ranges, $ov['start_time'], $ov['end_time']);
            } else {
                // Zusätzliche Verfügbarkeit
                $ranges[] = [
                    'start' => $ov['start_time'],
                    'end'   => $ov['end_time'],
                ];
            }
        }

        // 4. Bereits gebuchte Zeiten subtrahieren
        if (class_exists('CMS_Booking_Bookings')) {
            $booked = CMS_Booking_Bookings::instance()->get_booked_ranges($providerId, $date);
            foreach ($booked as $b) {
                $ranges = $this->subtract_range($ranges, $b['start'], $b['end']);
            }
        }

        // 5. Slots generieren
        $stepMin = $durationMin + $bufferMin;
        $slots   = [];

        foreach ($ranges as $range) {
            $current = strtotime("{$date} {$range['start']}");
            $end     = strtotime("{$date} {$range['end']}");

            while (($current + $durationMin * 60) <= $end) {
                $slots[] = date('H:i', $current);
                $current += $stepMin * 60;
            }
        }

        sort($slots);
        return array_unique($slots);
    }

    /**
     * Prüfe ob ein bestimmter Slot verfügbar ist.
     */
    public function is_slot_available(
        int    $providerId,
        string $date,
        string $startTime,
        int    $durationMin = 60,
        int    $bufferMin   = 15
    ): bool {
        $available = $this->get_available_slots($providerId, $date, $durationMin, $bufferMin);
        return in_array($startTime, $available, true);
    }

    /**
     * Nächste verfügbare Tage (für Kalender).
     *
     * @return array  ['2026-02-01', '2026-02-02', …]
     */
    public function get_available_dates(int $providerId, int $durationMin = 60, int $days = 30): array
    {
        $dates = [];
        $start = new \DateTime('today');
        $end   = (new \DateTime('today'))->modify("+{$days} days");

        while ($start <= $end) {
            $date  = $start->format('Y-m-d');
            $slots = $this->get_available_slots($providerId, $date, $durationMin, 0);
            if (!empty($slots)) {
                $dates[] = $date;
            }
            $start->modify('+1 day');
        }

        return $dates;
    }

    /* ================================================================== */
    /*  Range-Arithmetik                                                   */
    /* ================================================================== */

    /**
     * Subtrahiere einen Zeitraum von einer Liste an Ranges.
     *
     * @param  array   $ranges  [['start' => 'HH:MM', 'end' => 'HH:MM'], …]
     * @param  string  $subStart
     * @param  string  $subEnd
     * @return array
     */
    private function subtract_range(array $ranges, string $subStart, string $subEnd): array
    {
        $result = [];
        foreach ($ranges as $r) {
            if ($subEnd <= $r['start'] || $subStart >= $r['end']) {
                $result[] = $r;
                continue;
            }
            if ($subStart > $r['start']) {
                $result[] = ['start' => $r['start'], 'end' => $subStart];
            }
            if ($subEnd < $r['end']) {
                $result[] = ['start' => $subEnd, 'end' => $r['end']];
            }
        }
        return $result;
    }

    /* ================================================================== */
    /*  Wochentag-Labels                                                   */
    /* ================================================================== */

    public static function day_labels(): array
    {
        return [
            0 => 'Montag',
            1 => 'Dienstag',
            2 => 'Mittwoch',
            3 => 'Donnerstag',
            4 => 'Freitag',
            5 => 'Samstag',
            6 => 'Sonntag',
        ];
    }

    public static function day_label(int $day): string
    {
        return self::day_labels()[$day] ?? '';
    }
}
