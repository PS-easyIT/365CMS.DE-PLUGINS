<?php
/**
 * CMS Forum – Deutsche Sprachdatei
 *
 * @package CMS_Forum
 * @version 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return [
    // General
    'forum'                     => 'Forum',
    'forums'                    => 'Foren',
    'category'                  => 'Kategorie',
    'categories'                => 'Kategorien',
    'thread'                    => 'Thema',
    'threads'                   => 'Themen',
    'post'                      => 'Beitrag',
    'posts'                     => 'Beiträge',
    'reply'                     => 'Antwort',
    'replies'                   => 'Antworten',
    'views'                     => 'Aufrufe',
    'search'                    => 'Suchen',
    'profile'                   => 'Profil',

    // Navigation
    'breadcrumb.home'           => 'Forum',
    'breadcrumb.search'         => 'Suche',
    'breadcrumb.label'          => 'Brotkrumen-Navigation',
    'breadcrumb.homepage'       => 'Startseite',

    // Actions
    'action.create'             => 'Erstellen',
    'action.edit'               => 'Bearbeiten',
    'action.delete'             => 'Löschen',
    'action.save'               => 'Speichern',
    'action.cancel'             => 'Abbrechen',
    'action.back'               => 'Zurück',
    'action.submit'             => 'Absenden',
    'action.close'              => 'Schließen',
    'action.new_thread'         => 'Neues Thema',
    'action.reply'              => 'Antworten',
    'action.quote'              => 'Zitieren',
    'action.like'               => 'Gefällt mir',
    'action.unlike'             => 'Gefällt mir nicht mehr',
    'action.subscribe'          => 'Abonnieren',
    'action.unsubscribe'        => 'Abo beenden',
    'action.report'             => 'Melden',
    'action.login'              => 'Anmelden',
    'action.subscribed'         => 'Abonniert',
    'action.lock'               => 'Schließen',
    'action.unlock'             => 'Öffnen',
    'action.pin'                => 'Anheften',
    'action.unpin'              => 'Lösen',
    'action.move'               => 'Verschieben',
    'action.restore'            => 'Wiederherstellen',
    'action.approve'            => 'Freigeben',
    'action.ban'                => 'Sperren',
    'action.unban'              => 'Entsperren',

    // Thread types
    'thread.type.normal'        => 'Normal',
    'thread.type.sticky'        => 'Angepinnt',
    'thread.type.announcement'  => 'Ankündigung',
    'thread.type'               => 'Typ',
    'thread.title'              => 'Titel',
    'thread.title_placeholder'  => 'Themen-Titel eingeben...',
    'thread.content_placeholder'=> 'Dein Beitrag...',
    'thread.create_in_forum'    => 'Neuer Thread in %s',
    'thread.create_in_forum_short' => 'in %s',
    'thread.create_first'       => 'Erstes Thema erstellen',
    'thread.status.solved'      => 'Gelöst',
    'thread.accepted_answer'    => 'Diese Antwort wurde als Lösung markiert.',
    'thread.mark_as_solution'   => 'Als Lösung markieren',
    'thread.unmark_solution'    => 'Lösung entfernen',
    'thread.last_post_by'       => 'von %s',
    'thread.reply_placeholder'  => 'Deine Antwort...',
    'thread.reply_submit'       => 'Antwort absenden',
    'thread.closed_info'        => 'Dieses Thema ist geschlossen. Neue Antworten sind nicht möglich.',
    'thread.login_to_reply'     => 'um zu antworten.',
    'thread.similar.title'      => 'Ähnliche Themen gefunden',
    'thread.similar.hint'       => 'Prüfe bestehende Diskussionen, bevor du ein neues Thema erstellst.',

    // Thread status
    'thread.status.open'        => 'Offen',
    'thread.status.closed'      => 'Geschlossen',
    'thread.status.deleted'     => 'Gelöscht',

    // Post
    'post.edited_by'            => 'Bearbeitet von %s am %s',
    'post.post_count'           => '%d Beiträge',
    'post.like_count'           => '%d Likes',

    // Poll
    'poll'                      => 'Umfrage',
    'poll.question'             => 'Frage',
    'poll.question_placeholder' => 'Deine Umfrage-Frage...',
    'poll.options'              => 'Optionen',
    'poll.option_number'        => 'Option %d',
    'poll.option_prefix'        => 'Option',
    'poll.add_option'           => 'Option hinzufügen',
    'poll.remove_option'        => 'Entfernen',
    'poll.vote'                 => 'Abstimmen',
    'poll.votes'                => '%d Stimmen',
    'poll.multi_choice'         => 'Mehrfachauswahl',
    'poll.already_voted'        => 'Du hast bereits abgestimmt.',
    'poll.create'               => 'Umfrage erstellen (optional)',

    // Report
    'report'                    => 'Meldung',
    'report.reason'             => 'Grund',
    'report.detail'             => 'Details (optional)',
    'report.submit'             => 'Meldung absenden',
    'report.success'            => 'Meldung wurde gesendet.',
    'report.reasons.spam'       => 'Spam',
    'report.reasons.offensive'  => 'Beleidigung',
    'report.reasons.off_topic'  => 'Off-Topic',
    'report.reasons.duplicate'  => 'Duplikat',
    'report.reasons.other'      => 'Sonstiges',

    // Search
    'search.placeholder'        => 'Forum durchsuchen…',
    'search.results'            => '%d Ergebnisse',
    'search.no_results'         => 'Keine Ergebnisse gefunden.',
    'search.advanced'           => 'Erweiterte Suche',
    'search.date_from'          => 'Von',
    'search.date_to'            => 'Bis',
    'search.in_forum'           => 'Im Forum',
    'search.all_forums'         => 'Alle Foren',

    // User / Profile
    'user.rank'                 => 'Rang',
    'user.joined'               => 'Dabei seit',
    'user.location'             => 'Standort',
    'user.website'              => 'Webseite',
    'user.signature'            => 'Signatur',
    'user.posts'                => 'Beiträge',
    'user.threads'              => 'Themen',
    'user.likes_received'       => 'Likes erhalten',
    'user.banned'               => 'Gesperrt',
    'user.ban_reason'           => 'Sperrgrund',
    'user.ban_expires'          => 'Sperre bis',

    // Member settings
    'settings.title'            => 'Forum-Einstellungen',
    'settings.signature'        => 'Signatur',
    'settings.signature_hint'   => 'Wird unter jedem deiner Beiträge angezeigt.',
    'settings.location'         => 'Standort',
    'settings.website'          => 'Webseite',
    'settings.notifications'    => 'Benachrichtigungen',
    'settings.notify_reply'     => 'Bei Antworten auf meine Themen benachrichtigen',
    'settings.notify_quote'     => 'Bei Zitaten benachrichtigen',
    'settings.notify_mention'   => 'Bei Erwähnungen benachrichtigen',
    'settings.saved'            => 'Einstellungen gespeichert.',

    // Empty states
    'empty.forums'              => 'Noch keine Foren vorhanden.',
    'empty.threads'             => 'Noch keine Themen in diesem Forum.',
    'empty.posts'               => 'Noch keine Beiträge.',
    'empty.search'              => 'Deine Suche ergab keine Treffer.',

    // Errors
    'error.not_found'           => 'Nicht gefunden.',
    'error.forbidden'           => 'Zugriff verweigert.',
    'error.csrf'                => 'Sicherheitscheck fehlgeschlagen.',
    'error.flood'               => 'Bitte warte einen Moment, bevor du erneut postest.',
    'error.empty_title'         => 'Bitte gib einen Titel ein.',
    'error.empty_content'       => 'Bitte gib einen Beitrag ein.',
    'error.title_too_short'     => 'Der Titel ist zu kurz (min. %d Zeichen).',
    'error.content_too_short'   => 'Der Beitrag ist zu kurz (min. %d Zeichen).',
    'error.content_too_long'    => 'Der Beitrag ist zu lang (max. %d Zeichen).',
    'error.thread_closed'       => 'Dieses Thema ist geschlossen.',
    'error.banned'              => 'Dein Konto ist gesperrt.',
    'error.no_permission'       => 'Du hast keine Berechtigung für diese Aktion.',
    'error.not_logged_in'       => 'Nicht eingeloggt.',
    'error.invalid_request'     => 'Ungültige Anfrage.',
    'error.poll_not_found'      => 'Umfrage nicht gefunden.',
    'error.poll_not_allowed'    => 'Abstimmungen sind in diesem Forum nicht erlaubt.',
    'error.poll_too_many'       => 'Zu viele Optionen ausgewählt.',
    'error.thread_create_failed'=> 'Thread konnte nicht erstellt werden.',
    'error.post_create_failed'  => 'Beitrag konnte nicht erstellt werden.',
    'error.reply_login_required'=> 'Du musst eingeloggt sein, um zu antworten.',
    'error.flood.thread_wait'   => 'Bitte warte noch %d Sekunden, bevor du einen neuen Thread erstellst.',
    'error.flood.post_wait'     => 'Bitte warte noch %d Sekunden.',
    'error.invalid_answer_selection' => 'Dieser Beitrag kann nicht als Lösung markiert werden.',

    // Success
    'success.thread_created'    => 'Thema wurde erstellt.',
    'success.post_created'      => 'Antwort wurde gesendet.',
    'success.post_edited'       => 'Beitrag wurde aktualisiert.',
    'success.thread_deleted'    => 'Thema wurde gelöscht.',
    'success.thread_locked'     => 'Thema wurde geschlossen.',
    'success.thread_unlocked'   => 'Thema wurde geöffnet.',
    'success.thread_pinned'     => 'Thema wurde angepinnt.',
    'success.thread_unpinned'   => 'Thema wurde gelöst.',
    'success.thread_moved'      => 'Thema wurde verschoben.',
    'success.accepted_answer_set' => 'Antwort als Lösung markiert.',
    'success.accepted_answer_removed' => 'Akzeptierte Antwort entfernt.',

    // User fallback
    'user.deleted'              => 'Gelöscht',

    // Time (relative)
    'time.just_now'             => 'Gerade eben',
    'time.minutes_ago'          => 'vor %d Minuten',
    'time.hours_ago'            => 'vor %d Stunden',
    'time.days_ago'             => 'vor %d Tagen',
    'time.weeks_ago'            => 'vor %d Wochen',
    'time.months_ago'           => 'vor %d Monaten',
    'time.years_ago'            => 'vor %d Jahren',

    // Pagination
    'pagination.prev'           => '← Zurück',
    'pagination.next'           => 'Weiter →',
    'pagination.page'           => 'Seite %d von %d',

    // Admin
    'admin.dashboard'           => 'Dashboard',
    'admin.categories'          => 'Kategorien',
    'admin.forums'              => 'Foren',
    'admin.threads'             => 'Themen',
    'admin.users'               => 'Benutzer',
    'admin.ranks'               => 'Ränge',
    'admin.permissions'         => 'Berechtigungen',
    'admin.reports'             => 'Meldungen',
    'admin.settings'            => 'Einstellungen',
    'admin.maintenance'         => 'Wartung',
    'admin.recalculate_counts'  => 'Zähler neu berechnen',
    'admin.recalculate_ranks'   => 'Ränge neu berechnen',
];
