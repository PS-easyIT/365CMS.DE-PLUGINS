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
                <label for="title">Event-Titel <span class="required">*</span></label>
                <input 
                    type="text" 
                    id="title" 
                    name="title" 
                    value="<?= CMS\Security::instance()->escape($title) ?>" 
                    required 
                    class="form-control"
                    placeholder="z.B. Cloud Computing Summit 2026"
                />
            </div>

            <div class="form-group">
                <label for="description">Beschreibung</label>
                <?php
                if (class_exists('\CMS\Services\EditorService')) {
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
                        <option value="<?= $value ?>" <?= $category === $value ? 'selected' : '' ?>>
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
                    <label for="event_date">Startdatum <span class="required">*</span></label>
                    <input 
                        type="date" 
                        id="event_date" 
                        name="event_date" 
                        value="<?= CMS\Security::instance()->escape($event_date) ?>" 
                        required 
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
                        onchange="toggleLocationFields()"
                    />
                    <label for="is_online" class="form-check-label">
                        Online-Event
                    </label>
                </div>
            </div>

            <div id="online-fields" style="<?= $is_online ? '' : 'display:none;' ?>">
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

            <div id="physical-fields" style="<?= !$is_online ? '' : 'display:none;' ?>">
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

            <script>
            function toggleLocationFields() {
                const isOnline = document.getElementById('is_online').checked;
                document.getElementById('online-fields').style.display = isOnline ? 'block' : 'none';
                document.getElementById('physical-fields').style.display = isOnline ? 'none' : 'block';
            }
            </script>
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
            echo '<div class="admin-card" style="background:#f0f9ff;border-color:#bae6fd;"><p style="margin:0;color:#0369a1;font-size:.875rem;">\u{1f4a1} <strong>Speaker-Zuordnung</strong> ist nach dem ersten Speichern verf\u{00fc}gbar.</p></div>';
            return;
        }
        $event_id   = (int)$event->id;
        $db         = CMS_Events_Database::instance();
        $assigned   = $db->get_event_speakers($event_id);
        $available  = $db->get_available_speakers();
        $csrf       = CMS\Security::instance()->generateToken('event_speaker');
        ?>
        <div class="admin-card" id="ev-speaker-box">
            <h3>&#128100; Speaker &amp; Experten</h3>

            <div id="ev-assigned-speakers">
                <?php if (empty($assigned)): ?>
                    <p class="form-text" style="color:#64748b;">Noch keine Person zugeordnet.</p>
                <?php else: ?>
                    <?php foreach ($assigned as $sp): ?>
                    <?php $this->render_speaker_row($sp); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div style="margin-top:1.25rem;border-top:1px solid #f1f5f9;padding-top:1.25rem;">
                <h4 style="margin:0 0 .875rem;font-size:.9rem;font-weight:700;color:#374151;">&#10133; Hinzuf&uuml;gen</h4>
                <div style="display:grid;grid-template-columns:130px 1fr 1fr 140px auto;gap:.75rem;align-items:end;flex-wrap:wrap;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Typ</label>
                        <select id="ev_sp_type" class="form-control" onchange="evLoadSpOptions()">
                            <option value="speaker">Speaker</option>
                            <option value="expert">Experte</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Person</label>
                        <select id="ev_sp_person" class="form-control">
                            <option value="">-- w&auml;hlen --</option>
                            <?php foreach ($available['speakers'] as $sp): ?>
                                <option value="<?= (int)$sp->id ?>">
                                    <?= CMS\Security::instance()->escape($sp->last_name . ', ' . $sp->first_name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Vortragstitel</label>
                        <input type="text" id="ev_sp_title" class="form-control" placeholder="z.B. Cloud-Native">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Session-Zeit</label>
                        <input type="time" id="ev_sp_time" class="form-control">
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" onclick="evAddSpeaker()">Hinzuf&uuml;gen</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        const EV_SP_DATA = {
            speakers: <?= json_encode(array_map(fn($s) => ['id' => (int)$s->id, 'name' => $s->last_name . ', ' . $s->first_name], $available['speakers'])) ?>,
            experts:  <?= json_encode(array_map(fn($s) => ['id' => (int)$s->id, 'name' => $s->last_name . ', ' . $s->first_name], $available['experts'])) ?>,
        };
        function evLoadSpOptions() {
            const type = document.getElementById('ev_sp_type').value;
            const sel  = document.getElementById('ev_sp_person');
            const list = type === 'expert' ? EV_SP_DATA.experts : EV_SP_DATA.speakers;
            sel.innerHTML = '<option value="">-- w\u{00e4}hlen --</option>';
            list.forEach(s => { const o = document.createElement('option'); o.value = s.id; o.textContent = s.name; sel.appendChild(o); });
        }
        async function evAddSpeaker() {
            const type  = document.getElementById('ev_sp_type').value;
            const spId  = document.getElementById('ev_sp_person').value;
            const title = document.getElementById('ev_sp_title').value;
            const time  = document.getElementById('ev_sp_time').value;
            if (!spId) { alert('Bitte eine Person w\u{00e4}hlen.'); return; }
            const fd = new FormData();
            fd.append('csrf_token', '<?= $csrf ?>');
            fd.append('event_id', '<?= $event_id ?>');
            fd.append('speaker_id', spId);
            fd.append('speaker_type', type);
            fd.append('presentation_title', title);
            fd.append('session_time', time);
            try {
                const res = await fetch('<?= SITE_URL ?>/admin/events/speaker/add', { method: 'POST', body: fd });
                const d = await res.json();
                if (d.success) { location.reload(); }
                else { alert('Fehler: ' + (d.error ?? 'Unbekannt')); }
            } catch(e) { alert('Netzwerkfehler: ' + e.message); }
        }
        async function evRemoveSpeaker(assignmentId) {
            if (!confirm('Speaker entfernen?')) return;
            const fd = new FormData();
            fd.append('csrf_token', '<?= $csrf ?>');
            try {
                const res = await fetch('<?= SITE_URL ?>/admin/events/speaker/remove/' + assignmentId, { method: 'POST', body: fd });
                const d = await res.json();
                if (d.success) {
                    document.getElementById('ev-sp-row-' + assignmentId)?.remove();
                    if (!document.querySelector('#ev-assigned-speakers .ev-sp-row')) {
                        document.getElementById('ev-assigned-speakers').innerHTML = '<p class="form-text" style="color:#64748b;">Noch keine Person zugeordnet.</p>';
                    }
                } else { alert('Fehler beim Entfernen'); }
            } catch(e) { alert('Netzwerkfehler'); }
        }
        </script>
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
        $time  = htmlspecialchars(substr($sp->session_time ?? '', 0, 5));
        ?>
        <div class="ev-sp-row" id="ev-sp-row-<?= $id ?>"
             style="display:flex;align-items:center;gap:.75rem;padding:.55rem .25rem;
                    border-bottom:1px solid #f1f5f9;flex-wrap:wrap;">
            <span class="<?= $bgCls ?>"><?= $label ?></span>
            <strong style="flex:1;min-width:120px;"><?= $name ?></strong>
            <?php if ($title): ?>
                <span style="color:#64748b;font-size:.875rem;"><?= $title ?></span>
            <?php endif; ?>
            <?php if ($time): ?>
                <span class="status-badge inactive">&#128336; <?= $time ?></span>
            <?php endif; ?>
            <button type="button"
                    style="padding:.25rem .6rem;font-size:.78rem;background:#fee2e2;color:#991b1b;
                           border:1px solid #fca5a5;border-radius:6px;cursor:pointer;"
                    onclick="evRemoveSpeaker(<?= $id ?>)">&#128465;</button>
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
