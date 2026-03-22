(function () {
    'use strict';

    function normalizeHex(value) {
        var normalized = String(value || '').trim();
        if (!normalized.startsWith('#')) {
            normalized = '#' + normalized;
        }

        if (!/^#[0-9a-fA-F]{6}$/.test(normalized)) {
            return null;
        }

        return normalized.toUpperCase();
    }

    function initColorSync() {
        document.querySelectorAll('[data-color-sync]').forEach(function (wrapper) {
            var colorInput = wrapper.querySelector('input[type="color"]');
            var textInput = wrapper.querySelector('input[type="text"]');

            if (!colorInput || !textInput) {
                return;
            }

            var syncTextFromColor = function () {
                var normalized = normalizeHex(colorInput.value);
                if (normalized !== null) {
                    textInput.value = normalized;
                }
            };

            var syncColorFromText = function () {
                var normalized = normalizeHex(textInput.value);
                if (normalized !== null) {
                    textInput.value = normalized;
                    colorInput.value = normalized;
                }
            };

            colorInput.addEventListener('input', syncTextFromColor);
            colorInput.addEventListener('change', syncTextFromColor);
            textInput.addEventListener('input', syncColorFromText);
            textInput.addEventListener('change', syncColorFromText);

            syncTextFromColor();
        });
    }

    function insertSnippet(textarea, snippet) {
        var start = textarea.selectionStart || 0;
        var end = textarea.selectionEnd || 0;
        var current = textarea.value || '';
        textarea.value = current.slice(0, start) + snippet + current.slice(end);
        textarea.focus();
        textarea.selectionStart = textarea.selectionEnd = start + snippet.length;
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function snippetFor(type) {
        switch (type) {
            case 'paragraph':
                return '<p>Textabsatz</p>';
            case 'heading2':
                return '<h2>Zwischenüberschrift</h2>';
            case 'strong':
                return '<strong>Wichtiger Hinweis</strong>';
            case 'list':
                return '<ul>\n    <li>Erster Punkt</li>\n    <li>Zweiter Punkt</li>\n</ul>';
            case 'table':
                return '<table>\n    <thead>\n        <tr><th>Spalte 1</th><th>Spalte 2</th></tr>\n    </thead>\n    <tbody>\n        <tr><td>{{cms_prefix}}posts</td><td>Beispielwert</td></tr>\n    </tbody>\n</table>';
            case 'prefix':
                return '{{cms_prefix}}';
            default:
                return '';
        }
    }

    function initCodeEditors() {
        document.querySelectorAll('[data-code-editor]').forEach(function (editor) {
            var textarea = editor.querySelector('[data-code-editor-input]');
            var preview = editor.querySelector('[data-code-editor-preview]');

            if (!textarea || !preview) {
                return;
            }

            var renderPreview = function () {
                preview.innerHTML = textarea.value || '<p><em>Noch kein Inhalt vorhanden.</em></p>';
            };

            editor.querySelectorAll('[data-editor-insert]').forEach(function (button) {
                button.addEventListener('click', function () {
                    insertSnippet(textarea, snippetFor(button.getAttribute('data-editor-insert')));
                    renderPreview();
                });
            });

            editor.querySelectorAll('[data-editor-toggle]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var mode = button.getAttribute('data-editor-toggle');
                    var previewMode = mode === 'preview';
                    textarea.hidden = previewMode;
                    preview.hidden = !previewMode;
                    editor.setAttribute('data-editor-mode', mode || 'code');
                    renderPreview();
                });
            });

            textarea.addEventListener('input', renderPreview);
            renderPreview();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initColorSync();
        initCodeEditors();
    });
})();
