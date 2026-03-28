<?php
/**
 * Shortcode Handler für CMS Events
 *
 * @package CMS_Events
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Events_Shortcode
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->init_hooks();
    }

    private function init_hooks(): void
    {
        if (class_exists('CMS\Hooks')) {
            \CMS\Hooks::addFilter('cms_content', [$this, 'process_content_tags'], 20);
        }
    }

    /**
     * Verarbeitet alle [cms_events*]-Tags im Seiten-Content.
     */
    public function process_content_tags(string $content): string
    {
        $tags = [
            'cms_events'          => 'render_events_grid',
            'cms_event'           => 'render_single_event',
            'cms_events_calendar' => 'render_events_calendar',
            'cms_upcoming_events' => 'render_upcoming_events',
        ];
        foreach ($tags as $tag => $method) {
            $content = preg_replace_callback(
                '/\[' . preg_quote($tag, '/') . '([^\]]*?)\]/',
                function (array $m) use ($method): string {
                    return $this->{$method}($this->parse_atts($m[1]));
                },
                $content
            );
        }
        return $content;
    }

    /**
     * Parst Attribut-String aus einem Tag.
     */
    private function parse_atts(string $str): array
    {
        $parsed = [];
        preg_match_all('/(\w+)\s*=\s*["\']?([^"\'>\s]*)["\']?/', $str, $p, PREG_SET_ORDER);
        foreach ($p as $pair) {
            $parsed[$pair[1]] = $pair[2];
        }
        return $parsed;
    }

    public function render_events_grid(array $atts = []): string
    {
        $atts = array_merge([
            'limit'        => 12,
            'category'     => '',
            'city'         => '',
            'upcoming'     => true,
            'columns'      => 3,
            'show_filters' => true,
        ], $atts);

        $db_manager = CMS_Events_Database::instance();
        
        $args = [
            'status' => 'published',
            'limit' => (int)$atts['limit'],
        ];

        if ($atts['upcoming']) {
            $args['upcoming'] = true;
        }

        if (!empty($atts['category'])) {
            $args['category'] = $atts['category'];
        }

        if (!empty($atts['city'])) {
            $args['city'] = $atts['city'];
        }

        $events = $db_manager->get_events($args);

        ob_start();
        
        $template_loader = CMS_Events_Template_Loader::instance();
        $template_loader->render_template('archive-event', [
            'events' => $events,
            'columns' => (int)$atts['columns'],
            'show_filters' => (bool)$atts['show_filters'],
        ]);

        return ob_get_clean();
    }

    public function render_single_event(array $atts = []): string
    {
        $atts = array_merge(['id' => 0], $atts);

        $event_id = (int)$atts['id'];
        
        if ($event_id <= 0) {
            return '<p class="error">Ungültige Event-ID.</p>';
        }

        $db_manager = CMS_Events_Database::instance();
        $event = $db_manager->get_event($event_id);

        if (!$event || $event->status !== 'published') {
            return '<p class="error">Event nicht gefunden.</p>';
        }

        $speakers = $db_manager->get_event_speakers($event_id);

        ob_start();
        
        $template_loader = CMS_Events_Template_Loader::instance();
        $template_loader->render_template('single-event', [
            'event' => $event,
            'speakers' => $speakers,
        ]);

        return ob_get_clean();
    }

    public function render_events_calendar(array $atts = []): string
    {
        $atts = array_merge([
            'month'    => date('Y-m'),
            'view'     => 'month',
            'category' => '',
        ], $atts);

        $atts['month'] = preg_match('/^\d{4}-\d{2}$/', (string) $atts['month'])
            ? (string) $atts['month']
            : date('Y-m');

        $atts['view'] = in_array((string) $atts['view'], ['month', 'week'], true)
            ? (string) $atts['view']
            : 'month';

        if (!empty($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', (string)$_GET['month'])) {
            $atts['month'] = (string)$_GET['month'];
        }

        if (!empty($_GET['view']) && in_array((string) $_GET['view'], ['month', 'week'], true)) {
            $atts['view'] = (string) $_GET['view'];
        }

        $db_manager = CMS_Events_Database::instance();
        
        $args = [
            'status' => 'published',
            'month' => $atts['month'],
        ];

        if (!empty($atts['category'])) {
            $args['category'] = $atts['category'];
        }

        $events = $db_manager->get_events($args);
        $currentMonth = new DateTime($atts['month'] . '-01');
        $previousMonth = (clone $currentMonth)->modify('-1 month')->format('Y-m');
        $nextMonth = (clone $currentMonth)->modify('+1 month')->format('Y-m');

        ob_start();
        ?>
        <div class="events-calendar-widget">
            <div class="calendar-header">
                <a class="btn-prev calendar-nav-btn" href="<?= htmlspecialchars($this->buildCalendarNavigationUrl($previousMonth, (string)$atts['view'], (string)$atts['category']), ENT_QUOTES) ?>" aria-label="Vorheriger Monat">‹</a>
                <h3><?= date('F Y', strtotime($atts['month'] . '-01')) ?></h3>
                <a class="btn-next calendar-nav-btn" href="<?= htmlspecialchars($this->buildCalendarNavigationUrl($nextMonth, (string)$atts['view'], (string)$atts['category']), ENT_QUOTES) ?>" aria-label="Nächster Monat">›</a>
            </div>
            
            <div class="calendar-view-<?= htmlspecialchars($atts['view'], ENT_QUOTES) ?>">
                <?php if ($atts['view'] === 'month'): ?>
                    <?php $this->render_month_view($events, $atts['month']); ?>
                <?php else: ?>
                    <?php $this->render_week_view($events, $atts['month']); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function buildCalendarNavigationUrl(string $month, string $view, string $category): string
    {
        $params = $_GET;
        $params['month'] = $month;

        if ($view !== '') {
            $params['view'] = $view;
        }

        if ($category !== '') {
            $params['category'] = $category;
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $query = http_build_query($params);

        return (string)$path . ($query !== '' ? '?' . $query : '');
    }

    public function render_upcoming_events(array $atts = []): string
    {
        $atts = array_merge([
            'limit'    => 5,
            'category' => '',
        ], $atts);

        $db_manager = CMS_Events_Database::instance();
        
        $args = [
            'status' => 'published',
            'upcoming' => true,
            'limit' => (int)$atts['limit'],
        ];

        if (!empty($atts['category'])) {
            $args['category'] = $atts['category'];
        }

        $events = $db_manager->get_events($args);

        if (empty($events)) {
            return '<p class="no-events">Keine kommenden Events gefunden.</p>';
        }

        ob_start();
        ?>
        <div class="upcoming-events-widget">
            <h3>Kommende Events</h3>
            <ul class="events-list">
                <?php foreach ($events as $event): ?>
                    <li class="event-item">
                        <div class="event-date">
                            <span class="day"><?= date('d', strtotime($event->event_date)) ?></span>
                            <span class="month"><?= date('M', strtotime($event->event_date)) ?></span>
                        </div>
                        <div class="event-info">
                            <h4>
                                <a href="/events/<?= $event->id ?>">
                                    <?= CMS\Security::instance()->escape($event->title) ?>
                                </a>
                            </h4>
                            <?php if ($event->city): ?>
                                <p class="event-location">
                                    <?= $event->is_online ? '🌐 Online' : '📍 ' . CMS\Security::instance()->escape($event->city) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a href="/events" class="btn btn-primary">Alle Events ansehen</a>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_month_view(array $events, string $month): void
    {
        $first_day = new DateTime($month . '-01');
        $last_day = new DateTime($first_day->format('Y-m-t'));
        
        $start_week_day = (int)$first_day->format('N'); // 1=Monday
        $days_in_month = (int)$first_day->format('t');

        echo '<div class="calendar-grid">';
        
        // Wochentage Header
        echo '<div class="calendar-weekdays">';
        $weekdays = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
        foreach ($weekdays as $day) {
            echo '<div class="weekday">' . $day . '</div>';
        }
        echo '</div>';

        // Kalendertage
        echo '<div class="calendar-days">';
        
        // Leere Tage am Anfang
        for ($i = 1; $i < $start_week_day; $i++) {
            echo '<div class="calendar-day empty"></div>';
        }

        // Tage des Monats
        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = $first_day->format('Y-m-') . str_pad((string)$day, 2, '0', STR_PAD_LEFT);
            $day_events = array_filter($events, fn($e) => $e->event_date === $date);
            
            $has_events = count($day_events) > 0 ? 'has-events' : '';
            echo '<div class="calendar-day ' . $has_events . '" data-date="' . $date . '">';
            echo '<span class="day-number">' . $day . '</span>';
            
            if (count($day_events) > 0) {
                echo '<div class="day-events-count">' . count($day_events) . '</div>';
            }
            
            echo '</div>';
        }

        echo '</div>'; // calendar-days
        echo '</div>'; // calendar-grid
    }

    private function render_week_view(array $events, string $month): void
    {
        $start_date = new DateTime($month . '-01');
        
        echo '<div class="week-view">';
        
        for ($i = 0; $i < 7; $i++) {
            $date = clone $start_date;
            $date->modify("+{$i} days");
            
            $day_events = array_filter($events, fn($e) => $e->event_date === $date->format('Y-m-d'));
            
            echo '<div class="week-day">';
            echo '<div class="week-day-header">';
            echo '<span class="day-name">' . $date->format('l') . '</span>';
            echo '<span class="day-date">' . $date->format('d.m.Y') . '</span>';
            echo '</div>';
            
            echo '<div class="week-day-events">';
            foreach ($day_events as $event) {
                echo '<div class="week-event">';
                echo '<a href="/events/' . $event->id . '">' . CMS\Security::instance()->escape($event->title) . '</a>';
                echo '</div>';
            }
            echo '</div>';
            
            echo '</div>';
        }
        
        echo '</div>';
    }
}
