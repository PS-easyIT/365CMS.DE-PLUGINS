<?php
/**
 * Meta Boxes Handler für CMS Events
 *
 * @package CMS_Events
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('CMS_Events_Meta_Boxes', false)) {
    return;
}

final class CMS_Events_Meta_Boxes
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
        CMS\Hooks::addAction('event_form_fields', [$this, 'render_basic_fields'], 10);
        CMS\Hooks::addAction('event_form_fields', [$this, 'render_datetime_fields'], 20);
        CMS\Hooks::addAction('event_form_fields', [$this, 'render_location_fields'], 30);
        CMS\Hooks::addAction('event_form_fields', [$this, 'render_capacity_fields'], 40);
        CMS\Hooks::addAction('event_form_fields', [$this, 'render_status_fields'], 50);
        CMS\Hooks::addAction('event_form_fields', [$this, 'render_speaker_assignment'], 60);
    }

    public function render_basic_fields($event = null): void
    {
        $title = $event->title ?? '';
        $description = $event->description ?? '';
        $category = $event->category ?? '';

        ?>
        <div class="meta-box event-basic-info">
            <h3>Event-Informationen</h3>
            <div class="form-group">
                <label for="title">Event-Titel</label>
                <input 
                    type="text" 
                    id="title" 
                    name="title" 
                    value="<?= CMS\Security::instance()->escape($title) ?>" 
                    class="form-control"
                    placeholder="z.B. Cloud Computing Summit 2026"
                />
            </div>

            <div class="form-group">
                <label for="description">Beschreibung</label>
                <?php
                if (class_exists('CMS\\Services\\EditorService')) {
                    echo \CMS\Services\EditorService::getInstance()->render(
                        'description',
                        $description,
                        ['height' => 340]
                    );
                } else { ?>
                    <textarea
                        id="description"
                        name="description"
                        rows="10"
                        class="form-control"
                        placeholder="Detaillierte Event-Beschreibung, Agenda, Highlights..."
                    ><?= CMS\Security::instance()->escape($description) ?></textarea>
                <?php } ?>
            </div>

            <div class="form-group">
                <label for="category">Kategorie</label>
                <select id="category" name="category" class="form-control">
                    <option value="">-- Bitte wählen --</option>
                    <?php foreach ($this->get_categories() as $value => $label): ?>
                        <option value="<?= htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') ?>" <?= $category === $value ? 'selected' : '' ?>>
                            <?= CMS\Security::instance()->escape($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php
    }

    public function render_datetime_fields($event = null): void
    {
        $event_date = $event->event_date ?? '';
        $event_time = $event->event_time ?? '';
        $end_date = $event->end_date ?? '';
        $end_time = $event->end_time ?? '';

        ?>
        <div class="meta-box event-datetime">
            <h3>Datum & Uhrzeit</h3>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="event_date">Startdatum</label>
                    <input 
                        type="date" 
                        id="event_date" 
                        name="event_date" 
                        value="<?= CMS\Security::instance()->escape($event_date) ?>" 
                        class="form-control"
                    />
                </div>
                <div class="form-group col-md-6">
                    <label for="event_time">Startzeit</label>
                    <input 
                        type="time" 
                        id="event_time" 
                        name="event_time" 
                        value="<?= CMS\Security::instance()->escape($event_time) ?>" 
                        class="form-control"
                    />
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="end_date">Enddatum</label>
                    <input 
                        type="date" 
                        id="end_date" 
                        name="end_date" 
                        value="<?= CMS\Security::instance()->escape($end_date) ?>" 
                        class="form-control"
                    />
                    <small class="form-text text-muted">Leer lassen für eintägiges Event</small>
                </div>
                <div class="form-group col-md-6">
                    <label for="end_time">Endzeit</label>
                    <input 
                        type="time" 
                        id="end_time" 
                        name="end_time" 
                        value="<?= CMS\Security::instance()->escape($end_time) ?>" 
                        class="form-control"
                    />
                </div>
            </div>
        </div>
        <?php
    }

    public function render_location_fields($event = null): void
    {
        $is_online = (bool)($event->is_online ?? false);
        $online_url = $event->online_url ?? '';
        $location = $event->location ?? '';
        $city = $event->city ?? '';

        ?>
        <div class="meta-box event-location">
            <h3>Veranstaltungsort</h3>
            
            <div class="form-group">
                <div class="form-check">
                    <input 
                        type="checkbox" 
                        id="is_online" 
                        name="is_online" 
                        value="1" 
                        <?= $is_online ? 'checked' : '' ?>
                        class="form-check-input"
                        data-ev-meta-toggle="location"
                    />
                    <label for="is_online" class="form-check-label">
                        Online-Event
                    </label>
                </div>
            </div>

            <div id="online-fields"<?= $is_online ? '' : ' hidden' ?>>
                <div class="form-group">
                    <label for="online_url">Online-URL</label>
                    <input 
                        type="url" 
                        id="online_url" 
                        name="online_url" 
                        value="<?= CMS\Security::instance()->escape($online_url) ?>" 
                        class="form-control"
                        placeholder="https://zoom.us/j/123456789"
                    />
                </div>
            </div>

            <div id="physical-fields"<?= !$is_online ? '' : ' hidden' ?>>
                <div class="form-group">
                    <label for="location">Veranstaltungsort</label>
                    <input 
                        type="text" 
                        id="location" 
                        name="location" 
                        value="<?= CMS\Security::instance()->escape($location) ?>" 
                        class="form-control"
                        placeholder="z.B. Kongresszentrum Messe Berlin"
                    />
                </div>

                <div class="form-group">
                    <label for="city">Stadt</label>
                    <input 
                        type="text" 
                        id="city" 
                        name="city" 
                        value="<?= CMS\Security::instance()->escape($city) ?>" 
                        class="form-control"
                        placeholder="z.B. Berlin"
                    />
                </div>
            </div>
        </div>
        <?php
    }

    public function render_capacity_fields($event = null): void
    {
        $capacity = $event->capacity ?? '';
        $registration_url = $event->registration_url ?? '';

        ?>
        <div class="meta-box event-capacity">
            <h3>Kapazität & Anmeldung</h3>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="capacity">Maximale Teilnehmerzahl</label>
                    <input 
                        type="number" 
                        id="capacity" 
                        name="capacity" 
                        value="<?= CMS\Security::instance()->escape($capacity) ?>" 
                        class="form-control"
                        min="0"
                        placeholder="0 = unbegrenzt"
                    />
                </div>
                <div class="form-group col-md-6">
                    <label for="registration_url">Anmelde-URL</label>
                    <input 
                        type="url" 
                        id="registration_url" 
                        name="registration_url" 
                        value="<?= CMS\Security::instance()->escape($registration_url) ?>" 
                        class="form-control"
                        placeholder="https://ticketing.example.com"
                    />
                </div>
            </div>
        </div>
        <?php
    }

    public function render_status_fields($event = null): void
    {
        $status = $event->status ?? 'draft';

        ?>
        <div class="meta-box event-status">
            <h3>Status</h3>
            <div class="form-group">
                <label for="status">Veröffentlichungsstatus</label>
                <select id="status" name="status" class="form-control">
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Entwurf</option>
                    <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Veröffentlicht</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Abgesagt</option>
                    <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Abgeschlossen</option>
                </select>
            </div>
        </div>
        <?php
    }

    public function render_speaker_assignment($event = null): void
    {
        if (!$event || !isset($event->id)) {
            echo '<div class="admin-card ev-info-box"><p>\u{1f4a1} <strong>Speaker-Zuordnung</strong> ist nach dem ersten Speichern verf\u{00fc}gbar.</p></div>';
            return;
        }
        $event_id   = (int)$event->id;
        $db         = CMS_Events_Database::instance();
        $assigned   = $db->get_event_speakers($event_id);
        $available  = $db->get_available_speakers();
        $csrf       = CMS\Security::instance()->generateToken('event_speaker');
        ?>
           <div class="admin-card" id="ev-speaker-box"
               data-ev-speaker-endpoint-add="/admin/events/speaker/add"
               data-ev-speaker-endpoint-remove-base="/admin/events/speaker/remove/"
             data-ev-speaker-csrf="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"
             data-ev-speaker-event-id="<?= $event_id ?>"
             data-ev-speaker-empty-message="Noch keine Person zugeordnet.">
            <h3>&#128100; Speaker &amp; Experten</h3>
            <div class="alert alert-error ev-speaker-message" data-ev-speaker-message hidden></div>

            <div id="ev-assigned-speakers">
                <?php if (empty($assigned)): ?>
                    <p class="form-text ev-text-muted">Noch keine Person zugeordnet.</p>
                <?php else: ?>
                    <?php foreach ($assigned as $sp): ?>
                    <?php $this->render_speaker_row($sp); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="ev-speaker-form-shell">
                <h4 class="ev-speaker-form-title">&#10133; Hinzuf&uuml;gen</h4>
                <div class="ev-speaker-form-grid">
                    <div class="form-group ev-form-group--inline-reset">
                        <label class="form-label">Typ</label>
                        <select id="ev_sp_type" class="form-control" data-ev-speaker-type>
                            <option value="speaker">Speaker</option>
                            <option value="expert">Experte</option>
                        </select>
                    </div>
                    <div class="form-group ev-form-group--inline-reset">
                        <label class="form-label">Person</label>
                        <select id="ev_sp_person" class="form-control" data-ev-speaker-person>
                            <option value="">-- w&auml;hlen --</option>
                            <?php foreach ($available['speakers'] as $sp): ?>
                                <option value="<?= (int)$sp->id ?>" data-ev-speaker-option="speaker">
                                    <?= CMS\Security::instance()->escape($sp->last_name . ', ' . $sp->first_name) ?>
                                </option>
                            <?php endforeach; ?>
                            <?php foreach ($available['experts'] as $sp): ?>
                                <option value="<?= (int)$sp->id ?>" data-ev-speaker-option="expert" hidden>
                                    <?= CMS\Security::instance()->escape($sp->last_name . ', ' . $sp->first_name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group ev-form-group--inline-reset">
                        <label class="form-label">Vortragstitel</label>
                        <input type="text" id="ev_sp_title" class="form-control" placeholder="z.B. Cloud-Native" data-ev-speaker-title>
                    </div>
                    <div class="form-group ev-form-group--inline-reset">
                        <label class="form-label">Session-Zeit</label>
                        <input type="time" id="ev_sp_time" class="form-control" data-ev-speaker-time>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" data-ev-speaker-add>Hinzuf&uuml;gen</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_speaker_row(object $sp): void
    {
        $id    = (int)$sp->id;
        $name  = CMS\Security::instance()->escape($sp->speaker_name ?? 'Unbekannt');
        $type  = $sp->speaker_type ?? 'speaker';
        $label = $type === 'expert' ? 'Experte' : 'Speaker';
        $bgCls = $type === 'expert' ? 'role-badge member' : 'status-badge active';
        $title = CMS\Security::instance()->escape($sp->presentation_title ?? '');
        $time  = htmlspecialchars(substr((string)($sp->session_time ?? ''), 0, 5), ENT_QUOTES, 'UTF-8');
        ?>
        <div class="ev-sp-row" id="ev-sp-row-<?= $id ?>">
            <span class="<?= $bgCls ?>"><?= $label ?></span>
            <strong class="ev-sp-row-name"><?= $name ?></strong>
            <?php if ($title): ?>
                <span class="ev-sp-row-title"><?= $title ?></span>
            <?php endif; ?>
            <?php if ($time): ?>
                <span class="status-badge inactive">&#128336; <?= $time ?></span>
            <?php endif; ?>
            <button type="button"
                    class="btn btn-sm btn-danger"
                    data-ev-speaker-remove="<?= $id ?>"
                    aria-label="Speaker-Zuordnung entfernen">&#128465;</button>
        </div>
        <?php
    }

    private function get_categories(): array
    {
        return [
            'conference' => 'Konferenz',
            'workshop' => 'Workshop',
            'webinar' => 'Webinar',
            'meetup' => 'Meetup',
            'training' => 'Training',
            'hackathon' => 'Hackathon',
            'networking' => 'Networking',
            'other' => 'Sonstiges',
        ];
    }
}
