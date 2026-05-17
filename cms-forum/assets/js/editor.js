/**
 * CMS Forum – BBCode Editor
 *
 * Provides a simple BBCode toolbar that inserts tags
 * around selected text in a textarea.
 *
 * @package CMS_Forum
 * @version 1.0.0
 */
(function () {
    'use strict';

    /* ── Tag Map ───────────────────────────────────────────── */

    const TAG_MAP = {
        bold:       { open: '[b]',     close: '[/b]' },
        italic:     { open: '[i]',     close: '[/i]' },
        underline:  { open: '[u]',     close: '[/u]' },
        strike:     { open: '[s]',     close: '[/s]' },
        code:       { open: '[code]',  close: '[/code]' },
        quote:      { open: '[quote]', close: '[/quote]' },
        list:       { open: '[list]\n[*]', close: '\n[/list]' },
        spoiler:    { open: '[spoiler]', close: '[/spoiler]' },
        size:       null, // needs prompt
        color:      null, // needs prompt
        url:        null, // needs prompt
        img:        null, // needs prompt
        youtube:    null, // needs prompt
    };

    /* ── Insert Helper ─────────────────────────────────────── */

    function insertTag(textarea, openTag, closeTag, placeholder = '') {
        if (!textarea) return;

        const start = textarea.selectionStart;
        const end   = textarea.selectionEnd;
        const text  = textarea.value;
        const sel   = text.substring(start, end);
        const inner = sel || placeholder;

        const before = text.substring(0, start);
        const after  = text.substring(end);

        textarea.value = before + openTag + inner + closeTag + after;

        // Move cursor
        const cursorPos = sel
            ? start + openTag.length + sel.length + closeTag.length
            : start + openTag.length + placeholder.length;

        textarea.selectionStart = sel ? start + openTag.length : start + openTag.length;
        textarea.selectionEnd   = sel ? start + openTag.length + sel.length : cursorPos;
        textarea.focus();
    }

    /* ── Prompt Tags ───────────────────────────────────────── */

    function insertUrl(textarea) {
        const url  = prompt('URL eingeben:', 'https://');
        if (!url) return;
        const text = textarea.value.substring(textarea.selectionStart, textarea.selectionEnd) || prompt('Link-Text:', 'Link') || 'Link';
        insertTag(textarea, `[url=${url}]`, '[/url]', text);
    }

    function insertImage(textarea) {
        const url = prompt('Bild-URL eingeben:', 'https://');
        if (!url) return;
        insertTag(textarea, '[img]', '[/img]', url);
    }

    function insertYouTube(textarea) {
        const url = prompt('YouTube-URL eingeben:', 'https://www.youtube.com/watch?v=');
        if (!url) return;
        insertTag(textarea, '[youtube]', '[/youtube]', url);
    }

    function insertSize(textarea) {
        const size = prompt('Schriftgröße (10-30):', '14');
        if (!size) return;
        const s = Math.min(30, Math.max(10, parseInt(size, 10) || 14));
        const sel = textarea.value.substring(textarea.selectionStart, textarea.selectionEnd) || 'Text';
        insertTag(textarea, `[size=${s}]`, '[/size]', sel);
    }

    function insertColor(textarea) {
        const color = prompt('Farbe (z.B. red, #ff0000):', '#333333');
        if (!color) return;
        const sel = textarea.value.substring(textarea.selectionStart, textarea.selectionEnd) || 'Text';
        insertTag(textarea, `[color=${color}]`, '[/color]', sel);
    }

    /* ── Click Handler ─────────────────────────────────────── */

    function handleToolbarClick(e) {
        const btn = e.target.closest('.cmsforum-editor__btn');
        if (!btn) return;

        // Find the associated textarea
        const editorWrap = btn.closest('.cmsforum-editor');
        if (!editorWrap) return;
        const textarea = editorWrap.querySelector('.cmsforum-editor__textarea');
        if (!textarea) return;

        const action = btn.dataset.bbcode;
        if (!action) return;

        e.preventDefault();

        // Handle actions that need prompts
        switch (action) {
            case 'url':     return insertUrl(textarea);
            case 'img':     return insertImage(textarea);
            case 'youtube': return insertYouTube(textarea);
            case 'size':    return insertSize(textarea);
            case 'color':   return insertColor(textarea);
        }

        // Handle direct tags
        const tag = TAG_MAP[action];
        if (tag) {
            insertTag(textarea, tag.open, tag.close);
        }
    }

    /* ── Tab Key in Textarea ───────────────────────────────── */

    function handleTabKey(e) {
        if (e.key !== 'Tab' || !e.target.classList.contains('cmsforum-editor__textarea')) return;
        e.preventDefault();
        const ta = e.target;
        const start = ta.selectionStart;
        ta.value = ta.value.substring(0, start) + '    ' + ta.value.substring(ta.selectionEnd);
        ta.selectionStart = ta.selectionEnd = start + 4;
    }

    /* ── Ctrl+B/I/U Shortcuts ──────────────────────────────── */

    function handleShortcuts(e) {
        if (!e.ctrlKey && !e.metaKey) return;
        if (!e.target.classList.contains('cmsforum-editor__textarea')) return;

        const map = { 'b': 'bold', 'i': 'italic', 'u': 'underline' };
        const action = map[e.key.toLowerCase()];
        if (!action) return;

        e.preventDefault();
        const tag = TAG_MAP[action];
        if (tag) insertTag(e.target, tag.open, tag.close);
    }

    /* ── Character Counter ─────────────────────────────────── */

    function initCharCounter() {
        document.querySelectorAll('.cmsforum-editor__textarea[data-max-length]').forEach((ta) => {
            const max     = parseInt(ta.dataset.maxLength, 10);
            const counter = ta.closest('.cmsforum-editor')?.querySelector('.cmsforum-editor__counter');
            if (!counter || !max) return;

            const update = () => {
                const remaining = max - ta.value.length;
                counter.textContent = `${ta.value.length} / ${max}`;
                counter.style.color = remaining < 100 ? '#ef4444' : '';
            };

            ta.addEventListener('input', update);
            update();
        });
    }

    /* ── Poll Option Adder ─────────────────────────────────── */

    function initPollOptions() {
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-action="add-poll-option"]');
            if (!btn) return;
            e.preventDefault();

            const container = btn.closest('.cmsforum-poll-options')?.querySelector('.cmsforum-poll-options__list')
                           || btn.previousElementSibling;
            if (!container) return;

            const count = container.querySelectorAll('input[name="poll_options[]"]').length;
            if (count >= 20) return;

            const div = document.createElement('div');
            div.className = 'cmsforum-form-group';
            div.style.display = 'flex';
            div.style.gap = '.5rem';
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'poll_options[]';
            input.className = 'cmsforum-input';
            input.placeholder = `Option ${count + 1}`;
            input.maxLength = 255;
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'cmsforum-btn cmsforum-btn--sm cmsforum-btn--danger';
            removeButton.dataset.action = 'remove-poll-option';
            removeButton.textContent = '✕';
            div.append(input, removeButton);
            container.appendChild(div);
        });

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-action="remove-poll-option"]');
            if (!btn) return;
            e.preventDefault();
            const group = btn.closest('.cmsforum-form-group');
            if (group) group.remove();
        });
    }

    /* ── Init ──────────────────────────────────────────────── */

    document.addEventListener('DOMContentLoaded', () => {
        document.addEventListener('click', handleToolbarClick);
        document.addEventListener('keydown', handleTabKey);
        document.addEventListener('keydown', handleShortcuts);
        initCharCounter();
        initPollOptions();
    });
})();
