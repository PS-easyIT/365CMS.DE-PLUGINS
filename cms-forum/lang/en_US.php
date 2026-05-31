<?php
/**
 * CMS Forum – English Language File
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
    'forums'                    => 'Forums',
    'category'                  => 'Category',
    'categories'                => 'Categories',
    'thread'                    => 'Thread',
    'threads'                   => 'Threads',
    'post'                      => 'Post',
    'posts'                     => 'Posts',
    'reply'                     => 'Reply',
    'replies'                   => 'Replies',
    'views'                     => 'Views',
    'search'                    => 'Search',
    'profile'                   => 'Profile',

    // Navigation
    'breadcrumb.home'           => 'Forum',
    'breadcrumb.search'         => 'Search',
    'breadcrumb.label'          => 'Breadcrumb',
    'breadcrumb.homepage'       => 'Home',

    // Actions
    'action.create'             => 'Create',
    'action.edit'               => 'Edit',
    'action.delete'             => 'Delete',
    'action.save'               => 'Save',
    'action.cancel'             => 'Cancel',
    'action.back'               => 'Back',
    'action.submit'             => 'Submit',
    'action.close'              => 'Close',
    'action.new_thread'         => 'New Thread',
    'action.reply'              => 'Reply',
    'action.quote'              => 'Quote',
    'action.like'               => 'Like',
    'action.unlike'             => 'Unlike',
    'action.subscribe'          => 'Subscribe',
    'action.unsubscribe'        => 'Unsubscribe',
    'action.report'             => 'Report',
    'action.login'              => 'Log in',
    'action.subscribed'         => 'Subscribed',
    'action.lock'               => 'Lock',
    'action.unlock'             => 'Unlock',
    'action.pin'                => 'Pin',
    'action.unpin'              => 'Unpin',
    'action.move'               => 'Move',
    'action.restore'            => 'Restore',
    'action.approve'            => 'Approve',
    'action.ban'                => 'Ban',
    'action.unban'              => 'Unban',

    // Thread types
    'thread.type.normal'        => 'Normal',
    'thread.type.sticky'        => 'Sticky',
    'thread.type.announcement'  => 'Announcement',
    'thread.type'               => 'Type',
    'thread.title'              => 'Title',
    'thread.title_placeholder'  => 'Enter thread title...',
    'thread.content_placeholder'=> 'Your post...',
    'thread.create_in_forum'    => 'New thread in %s',
    'thread.create_in_forum_short' => 'in %s',
    'thread.create_first'       => 'Create first thread',
    'thread.status.solved'      => 'Solved',
    'thread.accepted_answer'    => 'This reply was marked as the solution.',
    'thread.mark_as_solution'   => 'Mark as solution',
    'thread.unmark_solution'    => 'Remove solution',
    'thread.last_post_by'       => 'by %s',
    'thread.reply_placeholder'  => 'Your reply...',
    'thread.reply_submit'       => 'Submit reply',
    'thread.closed_info'        => 'This thread is closed. New replies are not possible.',
    'thread.login_to_reply'     => 'to reply.',
    'thread.similar.title'      => 'Similar threads found',
    'thread.similar.hint'       => 'Check existing discussions before creating a new thread.',

    // Thread status
    'thread.status.open'        => 'Open',
    'thread.status.closed'      => 'Closed',
    'thread.status.deleted'     => 'Deleted',

    // Post
    'post.edited_by'            => 'Edited by %s on %s',
    'post.post_count'           => '%d posts',
    'post.like_count'           => '%d likes',

    // Poll
    'poll'                      => 'Poll',
    'poll.question'             => 'Question',
    'poll.question_placeholder' => 'Your poll question...',
    'poll.options'              => 'Options',
    'poll.option_number'        => 'Option %d',
    'poll.option_prefix'        => 'Option',
    'poll.add_option'           => 'Add option',
    'poll.remove_option'        => 'Remove',
    'poll.vote'                 => 'Vote',
    'poll.votes'                => '%d votes',
    'poll.multi_choice'         => 'Multiple choice',
    'poll.already_voted'        => 'You have already voted.',
    'poll.create'               => 'Create poll (optional)',

    // Report
    'report'                    => 'Report',
    'report.reason'             => 'Reason',
    'report.detail'             => 'Details (optional)',
    'report.submit'             => 'Submit report',
    'report.success'            => 'Report has been sent.',
    'report.reasons.spam'       => 'Spam',
    'report.reasons.offensive'  => 'Offensive',
    'report.reasons.off_topic'  => 'Off-topic',
    'report.reasons.duplicate'  => 'Duplicate',
    'report.reasons.other'      => 'Other',

    // Search
    'search.placeholder'        => 'Search forum…',
    'search.results'            => '%d results',
    'search.no_results'         => 'No results found.',
    'search.advanced'           => 'Advanced search',
    'search.date_from'          => 'From',
    'search.date_to'            => 'To',
    'search.in_forum'           => 'In forum',
    'search.all_forums'         => 'All forums',

    // User / Profile
    'user.rank'                 => 'Rank',
    'user.joined'               => 'Joined',
    'user.location'             => 'Location',
    'user.website'              => 'Website',
    'user.signature'            => 'Signature',
    'user.posts'                => 'Posts',
    'user.threads'              => 'Threads',
    'user.likes_received'       => 'Likes received',
    'user.banned'               => 'Banned',
    'user.ban_reason'           => 'Ban reason',
    'user.ban_expires'          => 'Ban expires',

    // Member settings
    'settings.title'            => 'Forum Settings',
    'settings.signature'        => 'Signature',
    'settings.signature_hint'   => 'Displayed below each of your posts.',
    'settings.location'         => 'Location',
    'settings.website'          => 'Website',
    'settings.notifications'    => 'Notifications',
    'settings.notify_reply'     => 'Notify me when someone replies to my threads',
    'settings.notify_quote'     => 'Notify me when someone quotes me',
    'settings.notify_mention'   => 'Notify me when someone mentions me',
    'settings.saved'            => 'Settings saved.',

    // Empty states
    'empty.forums'              => 'No forums yet.',
    'empty.threads'             => 'No threads in this forum yet.',
    'empty.posts'               => 'No posts yet.',
    'empty.search'              => 'Your search returned no results.',

    // Errors
    'error.not_found'           => 'Not found.',
    'error.forbidden'           => 'Access denied.',
    'error.csrf'                => 'Security check failed.',
    'error.flood'               => 'Please wait a moment before posting again.',
    'error.empty_title'         => 'Please enter a title.',
    'error.empty_content'       => 'Please enter a post.',
    'error.title_too_short'     => 'Title is too short (min. %d characters).',
    'error.content_too_short'   => 'Post is too short (min. %d characters).',
    'error.content_too_long'    => 'Post is too long (max. %d characters).',
    'error.thread_closed'       => 'This thread is closed.',
    'error.banned'              => 'Your account is banned.',
    'error.no_permission'       => 'You do not have permission for this action.',
    'error.not_logged_in'       => 'Not logged in.',
    'error.invalid_request'     => 'Invalid request.',
    'error.poll_not_found'      => 'Poll not found.',
    'error.poll_not_allowed'    => 'Poll voting is not allowed in this forum.',
    'error.poll_too_many'       => 'Too many options selected.',
    'error.thread_create_failed'=> 'Thread could not be created.',
    'error.post_create_failed'  => 'Post could not be created.',
    'error.reply_login_required'=> 'You must be logged in to reply.',
    'error.flood.thread_wait'   => 'Please wait %d more seconds before creating a new thread.',
    'error.flood.post_wait'     => 'Please wait %d more seconds.',
    'error.invalid_answer_selection' => 'This post cannot be marked as a solution.',

    // Success
    'success.thread_created'    => 'Thread has been created.',
    'success.post_created'      => 'Reply has been posted.',
    'success.post_edited'       => 'Post has been updated.',
    'success.thread_deleted'    => 'Thread has been deleted.',
    'success.thread_locked'     => 'Thread has been locked.',
    'success.thread_unlocked'   => 'Thread has been unlocked.',
    'success.thread_pinned'     => 'Thread has been pinned.',
    'success.thread_unpinned'   => 'Thread has been unpinned.',
    'success.thread_moved'      => 'Thread has been moved.',
    'success.accepted_answer_set' => 'Reply marked as solution.',
    'success.accepted_answer_removed' => 'Accepted answer removed.',

    // User fallback
    'user.deleted'              => 'Deleted',

    // Time (relative)
    'time.just_now'             => 'Just now',
    'time.minutes_ago'          => '%d minutes ago',
    'time.hours_ago'            => '%d hours ago',
    'time.days_ago'             => '%d days ago',
    'time.weeks_ago'            => '%d weeks ago',
    'time.months_ago'           => '%d months ago',
    'time.years_ago'            => '%d years ago',

    // Pagination
    'pagination.prev'           => '← Previous',
    'pagination.next'           => 'Next →',
    'pagination.page'           => 'Page %d of %d',

    // Admin
    'admin.dashboard'           => 'Dashboard',
    'admin.categories'          => 'Categories',
    'admin.forums'              => 'Forums',
    'admin.threads'             => 'Threads',
    'admin.users'               => 'Users',
    'admin.ranks'               => 'Ranks',
    'admin.permissions'         => 'Permissions',
    'admin.reports'             => 'Reports',
    'admin.settings'            => 'Settings',
    'admin.maintenance'         => 'Maintenance',
    'admin.recalculate_counts'  => 'Recalculate counters',
    'admin.recalculate_ranks'   => 'Recalculate ranks',
];
