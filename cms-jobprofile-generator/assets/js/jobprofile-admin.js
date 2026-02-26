/**
 * Job Profile Generator – Admin JavaScript
 * Vanilla JS (ES2020+), no jQuery
 */

'use strict';

/* ═══════════════════════════════════════════════════════════
   SUNEDITOR INITIALISATION
   Targets every <textarea class="jpg-rich-editor">
═══════════════════════════════════════════════════════════ */
function jpgInitRichEditors() {
    if (typeof SUNEDITOR === 'undefined') return;

    const editorConfig = {
        lang: 'de',
        height: 240,
        buttonList: [
            ['bold', 'underline', 'italic', 'strike'],
            ['list', 'align'],
            ['link'],
            ['fullScreen']
        ],
    };

    document.querySelectorAll('textarea.jpg-rich-editor').forEach(function (el) {
        if (el.dataset.seInit) return;
        el.dataset.seInit = '1';
        try {
            SUNEDITOR.create(el, editorConfig);
        } catch (err) {
            console.warn('[JPG] SunEditor init failed on #' + el.id, err);
        }
    });
}

/* ═══════════════════════════════════════════════════════════
   MODAL HELPERS
═══════════════════════════════════════════════════════════ */
function jpgOpenModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('open');
    el.style.display = 'flex';
}

function jpgCloseModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('open');
    el.style.display = 'none';
}

// Close modal on backdrop click
window.addEventListener('click', function (e) {
    document.querySelectorAll('.jpg-modal').forEach(function (m) {
        if (e.target === m) jpgCloseModal(m.id);
    });
});

// Close modal on Escape
document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('.jpg-modal.open').forEach(function (m) {
        jpgCloseModal(m.id);
    });
});

/* ═══════════════════════════════════════════════════════════
   DELETE CONFIRM MODAL
   Usage: jpgConfirmDelete('Profil "Softwareentwickler"', formEl)
═══════════════════════════════════════════════════════════ */
function jpgConfirmDelete(label, formEl) {
    const modal = document.getElementById('jpg-confirm-modal');
    if (!modal) {
        // fallback: native dialog
        if (window.confirm('Wirklich löschen: ' + label + '?')) {
            if (formEl) formEl.submit();
        }
        return;
    }
    const msgEl = modal.querySelector('[data-confirm-label]');
    if (msgEl) msgEl.textContent = label;

    const confirmBtn = modal.querySelector('[data-confirm-submit]');
    const handler = function () {
        jpgCloseModal('jpg-confirm-modal');
        if (formEl) formEl.submit();
        confirmBtn.removeEventListener('click', handler);
    };
    confirmBtn.addEventListener('click', handler);
    jpgOpenModal('jpg-confirm-modal');
}

/* ═══════════════════════════════════════════════════════════
   CHARACTER COUNTER
   Call once per input/textarea.
   el: the input element
   counterId: id of the <span> showing the counter
   min: minimum chars (turns red below)
   max: soft max (turns yellow above)

   Usage in PHP: jpgCharCounter('title', 'title-counter', 10, 80, 100)
═══════════════════════════════════════════════════════════ */
function jpgCharCounter(inputId, counterId, min, softMax, hardMax) {
    const el      = document.getElementById(inputId);
    const counter = document.getElementById(counterId);
    if (!el || !counter) return;

    function update() {
        const len = el.value.length;
        counter.textContent = len + ' / ' + (hardMax || softMax);
        counter.className = 'jpg-char-counter';
        if (len < min)            counter.classList.add('error');
        else if (len > softMax)   counter.classList.add('warn');
        else                      counter.classList.add('ok');
    }

    el.addEventListener('input', update);
    update();
}

/* ═══════════════════════════════════════════════════════════
   DRAG & DROP SORT (HTML5 native)
   Initialise on a <ul class="jpg-sortable-list">
   Sets data-order hidden input values after each drop.
═══════════════════════════════════════════════════════════ */
function jpgInitDnd(listId) {
    const list = document.getElementById(listId);
    if (!list) return;

    let dragging = null;

    list.addEventListener('dragstart', function (e) {
        dragging = e.target.closest('li');
        if (!dragging) return;
        dragging.style.opacity = '.45';
        e.dataTransfer.effectAllowed = 'move';
    });

    list.addEventListener('dragend', function () {
        if (dragging) dragging.style.opacity = '';
        list.querySelectorAll('li').forEach(function (li) {
            li.classList.remove('jpg-drag-over');
        });
        dragging = null;
        jpgUpdateOrder(list);
    });

    list.addEventListener('dragover', function (e) {
        e.preventDefault();
        const over = e.target.closest('li');
        if (!over || over === dragging) return;
        const rect = over.getBoundingClientRect();
        const after = e.clientY > rect.top + rect.height / 2;
        list.querySelectorAll('li').forEach(function (li) {
            li.classList.remove('jpg-drag-over');
        });
        over.classList.add('jpg-drag-over');
        if (after) {
            over.after(dragging);
        } else {
            over.before(dragging);
        }
    });

    list.addEventListener('dragleave', function (e) {
        const over = e.target.closest('li');
        if (over) over.classList.remove('jpg-drag-over');
    });

    list.addEventListener('drop', function (e) {
        e.preventDefault();
    });
}

function jpgUpdateOrder(list) {
    list.querySelectorAll('li').forEach(function (li, idx) {
        const orderInput = li.querySelector('input[name$="[sort_order]"]');
        if (orderInput) orderInput.value = idx + 1;

        // also update the visible number if present
        const numEl = li.querySelector('.jpg-task-num');
        if (numEl) numEl.textContent = (idx + 1) + '.';
    });
}

/* ═══════════════════════════════════════════════════════════
   GENERATOR: ADD / REMOVE TASK ROW
═══════════════════════════════════════════════════════════ */
function jpgAddTask() {
    const list = document.getElementById('jpg-task-list');
    if (!list) return;

    const idx  = list.querySelectorAll('li').length;
    const li   = document.createElement('li');
    li.className = 'jpg-task-item';
    li.draggable = true;
    li.innerHTML =
        '<span class="jpg-drag-handle" aria-hidden="true">⠿</span>' +
        '<span class="jpg-task-num" style="font-size:.8rem;color:#94a3b8;flex-shrink:0;">' + (idx + 1) + '.</span>' +
        '<input type="text" name="tasks[' + idx + '][description]" class="form-control" ' +
            'placeholder="Aufgabe beschreiben …" maxlength="255">' +
        '<input type="hidden" name="tasks[' + idx + '][sort_order]" value="' + (idx + 1) + '">' +
        '<button type="button" class="jpg-task-remove" onclick="jpgRemoveTask(this)" title="Entfernen">✕</button>';

    list.appendChild(li);
    li.querySelector('input[type="text"]').focus();
    jpgInitDnd('jpg-task-list'); // re-init DnD after DOM change
}

function jpgRemoveTask(btn) {
    const li   = btn.closest('li');
    const list = li ? li.closest('ul') : null;
    if (!li) return;

    const count = list ? list.querySelectorAll('li').length : 0;
    if (count <= 3) {
        jpgShowToast('⚠️ Mindestens 3 Aufgaben sind erforderlich.', 'warn');
        return;
    }

    li.remove();
    if (list) jpgUpdateOrder(list);
}

/* ═══════════════════════════════════════════════════════════
   GENERATOR: ADD / REMOVE REQUIREMENT ROW
═══════════════════════════════════════════════════════════ */
function jpgAddReq() {
    const container = document.getElementById('jpg-req-list');
    if (!container) return;

    const idx = container.querySelectorAll('.jpg-req-item').length;
    const div = document.createElement('div');
    div.className = 'jpg-req-item';
    div.innerHTML =
        '<button type="button" class="jpg-req-type must" onclick="jpgToggleReqType(this)">Pflicht</button>' +
        '<input type="hidden" name="requirements[' + idx + '][type]" value="must">' +
        '<input type="text" name="requirements[' + idx + '][description]" class="form-control" ' +
            'placeholder="Anforderung …" maxlength="255">' +
        '<button type="button" class="jpg-task-remove" onclick="this.closest(\'.jpg-req-item\').remove()" title="Entfernen">✕</button>';

    container.appendChild(div);
    div.querySelector('input[type="text"]').focus();
}

function jpgToggleReqType(btn) {
    const hidden = btn.nextElementSibling; // hidden type input
    if (btn.classList.contains('must')) {
        btn.classList.replace('must', 'nice');
        btn.textContent = 'Optional';
        if (hidden) hidden.value = 'nice';
    } else {
        btn.classList.replace('nice', 'must');
        btn.textContent = 'Pflicht';
        if (hidden) hidden.value = 'must';
    }
}

/* ═══════════════════════════════════════════════════════════
   GENERATOR: BENEFIT TOGGLE
═══════════════════════════════════════════════════════════ */
function jpgToggleBenefit(el) {
    const checkbox = el.querySelector('input[type="checkbox"]');
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
        el.classList.toggle('selected', checkbox.checked);
    }
}

/* ═══════════════════════════════════════════════════════════
   GENERATOR: REVIEW TAB – HTML PREVIEW
═══════════════════════════════════════════════════════════ */
function jpgPreviewHtml(profileId, nonce, url) {
    const iframe  = document.getElementById('jpg-preview-frame');
    const spinner = document.getElementById('jpg-preview-spinner');
    if (!iframe) return;

    if (spinner) spinner.style.display = 'block';

    const formData = new FormData();
    formData.append('ajax',        '1');
    formData.append('action',      'preview_html');
    formData.append('profile_id',  profileId);
    formData.append('_jpg_nonce',  nonce);

    fetch(url, { method: 'POST', body: formData })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (spinner) spinner.style.display = 'none';
            if (data.success && data.html) {
                iframe.srcdoc = data.html;
            } else {
                iframe.srcdoc = '<p style="padding:1rem;color:#ef4444;">Vorschau konnte nicht geladen werden.</p>';
            }
        })
        .catch(function (err) {
            if (spinner) spinner.style.display = 'none';
            console.error('[JPG] Preview fetch error:', err);
        });
}

/* ═══════════════════════════════════════════════════════════
   LIBRARIES: INLINE EDIT
   type: 'text-module' | 'skill' | 'benefit' | 'category'
   data: plain object with form field values
═══════════════════════════════════════════════════════════ */
function jpgLibEdit(type, id, data) {
    const container = document.getElementById('jpg-lib-form-' + type);
    if (!container) return;

    // populate hidden id field
    const idField = container.querySelector('[name$="[id]"]');
    if (idField) idField.value = id;

    // populate all named fields
    Object.keys(data).forEach(function (key) {
        const field = container.querySelector('[name="' + key + '"]');
        if (!field) return;
        if (field.type === 'checkbox') {
            field.checked = data[key] == 1 || data[key] === true;
        } else {
            field.value = data[key] !== null ? data[key] : '';
        }
    });

    // scroll to form
    container.scrollIntoView({ behavior: 'smooth', block: 'start' });

    // update heading
    const heading = container.querySelector('.jpg-form-heading');
    if (heading) heading.textContent = '✏️ Bearbeiten';
}

function jpgLibReset(type) {
    const container = document.getElementById('jpg-lib-form-' + type);
    if (!container) return;
    const form = container.querySelector('form');
    if (form) form.reset();
    const heading = container.querySelector('.jpg-form-heading');
    if (heading) heading.textContent = '➕ Neu anlegen';
}

/* ═══════════════════════════════════════════════════════════
   DESIGN: TEMPLATE EDITOR PREFILL
═══════════════════════════════════════════════════════════ */
function jpgEditTemplate(id, name, content, css, isDefault) {
    const form = document.getElementById('jpg-template-form');
    if (!form) return;

    form.querySelector('[name="template_id"]').value  = id;
    form.querySelector('[name="name"]').value          = name;
    form.querySelector('[name="content"]').value       = content;
    form.querySelector('[name="custom_css"]').value    = css || '';
    form.querySelector('[name="is_default"]').checked  = isDefault == 1;

    const h = form.querySelector('.jpg-form-heading');
    if (h) h.textContent = '✏️ Template bearbeiten';

    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function jpgTplReset() {
    const form = document.getElementById('jpg-template-form');
    if (!form) return;
    form.reset();
    form.querySelector('[name="template_id"]').value = '0';
    const h = form.querySelector('.jpg-form-heading');
    if (h) h.textContent = '➕ Neues Template';
}

/* ═══════════════════════════════════════════════════════════
   DESIGN: COLOR PICKER SYNC (text ↔ color input)
═══════════════════════════════════════════════════════════ */
function jpgSyncColor(textId, pickerId) {
    const text   = document.getElementById(textId);
    const picker = document.getElementById(pickerId);
    if (!text || !picker) return;

    text.addEventListener('input', function () {
        if (/^#[0-9a-fA-F]{6}$/.test(text.value)) {
            picker.value = text.value;
        }
    });
    picker.addEventListener('input', function () {
        text.value = picker.value;
    });
}

/* ═══════════════════════════════════════════════════════════
   DESIGN: LIVE FONT PREVIEW
═══════════════════════════════════════════════════════════ */
function jpgUpdateFontPreview(type) {
    const select  = document.getElementById('font-' + type + '-select');
    const preview = document.getElementById('font-' + type + '-preview');
    if (!select || !preview) return;

    const font = select.value || 'inherit';
    preview.style.fontFamily = font;
}

/* ═══════════════════════════════════════════════════════════
   TOAST NOTIFICATION
═══════════════════════════════════════════════════════════ */
function jpgShowToast(message, type) {
    let toast = document.getElementById('jpg-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'jpg-toast';
        toast.style.cssText = [
            'position:fixed',
            'bottom:1.5rem',
            'right:1.5rem',
            'padding:.75rem 1.25rem',
            'border-radius:8px',
            'font-size:.875rem',
            'font-weight:600',
            'z-index:99999',
            'box-shadow:0 4px 16px rgba(0,0,0,.18)',
            'transition:opacity .3s',
            'pointer-events:none',
        ].join(';');
        document.body.appendChild(toast);
    }

    const colors = {
        success: { bg: '#d1fae5', color: '#065f46' },
        error:   { bg: '#fee2e2', color: '#991b1b' },
        warn:    { bg: '#fef3c7', color: '#92400e' },
        info:    { bg: '#dbeafe', color: '#1e40af' },
    };
    const c = colors[type] || colors.info;
    toast.textContent     = message;
    toast.style.background = c.bg;
    toast.style.color      = c.color;
    toast.style.opacity    = '1';

    clearTimeout(toast._timer);
    toast._timer = setTimeout(function () {
        toast.style.opacity = '0';
    }, 3200);
}

/* ═══════════════════════════════════════════════════════════
   BOOT
═══════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function () {
    // Rich editors
    jpgInitRichEditors();

    // DnD task list (generator page)
    if (document.getElementById('jpg-task-list')) {
        jpgInitDnd('jpg-task-list');
    }

    // Color picker syncs (design page)
    jpgSyncColor('primary_color',   'primary_color_picker');
    jpgSyncColor('secondary_color', 'secondary_color_picker');

    // Font preview (design page)
    ['body', 'heading'].forEach(function (t) {
        const sel = document.getElementById('font-' + t + '-select');
        if (sel) sel.addEventListener('change', function () { jpgUpdateFontPreview(t); });
    });
});
