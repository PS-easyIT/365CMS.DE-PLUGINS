<?php
/**
 * CMS Forum – Standard-Konfiguration
 *
 * @package CMS_Forum\Config
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Standard-Einstellungen für das Forum-Plugin.
 * Werden bei der Installation in die Datenbank geschrieben
 * und können im Admin-Bereich überschrieben werden.
 */
return [
    // Allgemein
    'forum_title'           => 'Community Forum',
    'forum_description'     => 'Diskutiere mit der Community über aktuelle Themen.',
    'forum_enabled'         => '1',

    // Threads & Beiträge
    'threads_per_page'      => '20',
    'posts_per_page'        => '15',
    'min_title_length'      => '5',
    'max_title_length'      => '200',
    'min_post_length'       => '10',
    'max_post_length'       => '50000',

    // Berechtigungen
    'guests_can_read'       => '1',
    'guests_can_search'     => '1',
    'members_can_post'      => '1',
    'members_can_create_threads' => '1',
    'members_can_upload'    => '1',
    'members_can_edit_time' => '30',   // Minuten, 0 = unbegrenzt
    'members_can_delete_own' => '0',

    // Flood-Control
    'flood_interval_post'   => '30',   // Sekunden zwischen Beiträgen
    'flood_interval_thread' => '120',  // Sekunden zwischen Threads

    // Anhänge
    'attachments_enabled'   => '1',
    'max_attachment_size'   => '5242880',   // 5 MB in Bytes
    'max_attachments_per_post' => '3',
    'allowed_extensions'    => 'jpg,jpeg,png,gif,webp,pdf,zip',

    // Umfragen
    'polls_enabled'         => '1',
    'max_poll_options'      => '10',
    'poll_change_vote'      => '1',

    // BBCode
    'bbcode_enabled'        => '1',
    'smileys_enabled'       => '1',
    'img_tag_enabled'       => '1',
    'video_tag_enabled'     => '1',

    // Benachrichtigungen
    'email_notifications'   => '1',
    'notification_digest'   => 'instant',   // instant, daily, weekly

    // Design
    'primary_color'         => '#3b82f6',
    'border_radius'         => '8',
    'card_bg'               => '#ffffff',
    'show_online_users'     => '1',
    'show_forum_stats'      => '1',
    'show_breadcrumbs'      => '1',

    // SEO
    'seo_title_format'      => '{thread_title} – {forum_title}',
    'seo_noindex_empty'     => '1',

    // Moderation
    'auto_lock_days'        => '0',    // 0 = nie automatisch sperren
    'censor_words'          => '',
    'censor_replacement'    => '***',
    'require_approval'      => '0',    // Beiträge erst nach Freigabe
];
