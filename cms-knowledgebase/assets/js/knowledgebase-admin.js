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
            case 'info-table':
                return '<div class="cms-kb-table-module cms-kb-table-module--info">\n    <p><strong>Infomodul:</strong> Kurze Einordnung oder Lesetipp oberhalb der Tabelle.</p>\n    [site-table id="1"]\n</div>';
            case 'kb-template':
                return '<p>Einleitung: Erkläre den Begriff kurz, verständlich und praxisnah.</p>\n\n<h2>Vorteile</h2>\n<ul>\n    <li>Vorteil 1 mit konkretem Nutzen</li>\n    <li>Vorteil 2 mit Bezug zum Alltag</li>\n    <li>Vorteil 3 mit technischem Mehrwert</li>\n</ul>\n\n<h2>Praxisbeispiel</h2>\n<p>Zeige an einem kurzen Szenario, wann und wie der Begriff oder die Lösung eingesetzt wird.</p>\n\n<h2>Fazit</h2>\n<p>Fasse die wichtigste Aussage knapp zusammen und gib eine klare Empfehlung oder Einordnung.</p>';
            case 'site-table':
                return '[site-table id="1"]';
            case 'prefix':
                return '{{cms_prefix}}';
            default:
                return '';
        }
    }

    function getLineStart(value, cursorPosition) {
        return value.lastIndexOf('\n', Math.max(0, cursorPosition - 1)) + 1;
    }

    function getLineIndentation(line) {
        var match = String(line || '').match(/^\s*/);
        return match ? match[0] : '';
    }

    function insertAtCursor(textarea, text, selectionOffset) {
        var start = textarea.selectionStart || 0;
        var end = textarea.selectionEnd || 0;
        var current = textarea.value || '';
        textarea.value = current.slice(0, start) + text + current.slice(end);
        textarea.focus();
        var offset = typeof selectionOffset === 'number' ? selectionOffset : text.length;
        textarea.selectionStart = textarea.selectionEnd = start + offset;
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function handleTabKey(textarea, event) {
        event.preventDefault();
        insertAtCursor(textarea, '    ');
    }

    function handleEnterKey(textarea, event) {
        var value = textarea.value || '';
        var cursor = textarea.selectionStart || 0;
        var lineStart = getLineStart(value, cursor);
        var currentLine = value.slice(lineStart, cursor);
        var trimmedLine = currentLine.trim();
        var baseIndent = getLineIndentation(currentLine);
        var nextIndent = baseIndent;

        if (/^<(ul|ol|table|thead|tbody|tfoot|tr)\b[^>]*>$/i.test(trimmedLine)) {
            nextIndent += '    ';
        } else if (/^<li\b[^>]*>.*$/i.test(trimmedLine) && !/<\/li>\s*$/i.test(trimmedLine)) {
            nextIndent += '    ';
        } else if (/^<(th|td)\b[^>]*>.*$/i.test(trimmedLine) && !/<\/(th|td)>\s*$/i.test(trimmedLine)) {
            nextIndent += '    ';
        }

        event.preventDefault();
        insertAtCursor(textarea, '\n' + nextIndent);
    }

    function isUnsafePreviewUrl(value) {
        return /^\s*(javascript|data|vbscript):/i.test(String(value || ''));
    }

    function sanitizePreviewNode(node) {
        if (node.nodeType !== Node.ELEMENT_NODE) {
            return;
        }

        var blockedTags = ['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT', 'EMBED', 'LINK', 'META'];
        if (blockedTags.indexOf(node.tagName) !== -1) {
            node.remove();
            return;
        }

        Array.from(node.attributes).forEach(function (attribute) {
            var name = attribute.name.toLowerCase();
            var value = attribute.value || '';
            if (name.indexOf('on') === 0 || ((name === 'href' || name === 'src' || name === 'xlink:href') && isUnsafePreviewUrl(value))) {
                node.removeAttribute(attribute.name);
            }
        });

        Array.from(node.childNodes).forEach(sanitizePreviewNode);
    }

    function buildPreviewFragment(value) {
        var markup = String(value || '').trim();
        var fragment = document.createDocumentFragment();

        if (!markup) {
            var empty = document.createElement('div');
            empty.className = 'kb-code-editor__empty-preview';
            var title = document.createElement('strong');
            title.textContent = 'Noch kein Inhalt vorhanden.';
            var hint = document.createElement('span');
            hint.textContent = 'Schreibe HTML oder füge ein Snippet ein – die Preview aktualisiert sich automatisch.';
            empty.append(title, hint);
            fragment.appendChild(empty);
            return fragment;
        }

        markup = markup
            .replace(/\{\{cms_prefix\}\}|\{cms_prefix\}|\[cms_prefix\]|%cms_prefix%|\{\{table_prefix\}\}|\{table_prefix\}/gi, '<span class="kb-code-editor__token">cms_prefix</span>')
            .replace(/\[(site-table|table)\s+id\s*=\s*["']?(\d+)["']?\s*\/?\]/gi, function (_match, type, id) {
                var label = String(type || '').toLowerCase() === 'table' ? 'TablePress-Import' : '365CMS Site-Table';
                return '<div class="kb-code-editor__shortcode-card"><strong>📊 ' + label + '</strong><span>Shortcode #' + id + ' wird public als Tabelle gerendert.</span></div>';
            })
            .replace(/<div class="cms-kb-table-module cms-kb-table-module--info">/gi, '<div class="kb-code-editor__info-module kb-code-editor__info-module--info">');

        var parsed = new DOMParser().parseFromString(markup, 'text/html');
        Array.from(parsed.body.childNodes).forEach(sanitizePreviewNode);
        Array.from(parsed.body.childNodes).forEach(function (child) {
            fragment.appendChild(document.importNode(child, true));
        });

        return fragment;
    }

    function initCodeEditors() {
        document.querySelectorAll('[data-code-editor]').forEach(function (editor) {
            var textarea = editor.querySelector('[data-code-editor-input]');
            var preview = editor.querySelector('[data-code-editor-preview]');
            var status = editor.querySelector('[data-code-editor-status]');
            var snippetPicker = editor.querySelector('[data-editor-snippet]');

            if (!textarea || !preview) {
                return;
            }

            var setMode = function (mode) {
                var nextMode = mode || 'split';
                editor.setAttribute('data-editor-mode', nextMode);

                editor.querySelectorAll('[data-editor-toggle]').forEach(function (toggleButton) {
                    var isActive = toggleButton.getAttribute('data-editor-toggle') === nextMode;
                    toggleButton.classList.toggle('is-active', isActive);
                    toggleButton.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });

                if (status) {
                    status.textContent = nextMode === 'preview'
                        ? 'Modus: Vorschau'
                        : (nextMode === 'code' ? 'Modus: Code' : 'Modus: Live-Preview');
                }

                renderPreview();
            };

            var renderPreview = function () {
                preview.replaceChildren(buildPreviewFragment(textarea.value));
            };

            editor.querySelectorAll('[data-editor-insert]').forEach(function (button) {
                button.addEventListener('click', function () {
                    insertSnippet(textarea, snippetFor(button.getAttribute('data-editor-insert')));
                    renderPreview();
                });
            });

            if (snippetPicker) {
                snippetPicker.addEventListener('change', function () {
                    var snippetType = snippetPicker.value || '';
                    if (!snippetType) {
                        return;
                    }

                    insertSnippet(textarea, snippetFor(snippetType));
                    snippetPicker.value = '';
                    renderPreview();
                });
            }

            editor.querySelectorAll('[data-editor-toggle]').forEach(function (button) {
                button.addEventListener('click', function () {
                    setMode(button.getAttribute('data-editor-toggle') || 'split');
                });
            });

            textarea.addEventListener('keydown', function (event) {
                if (event.key === 'Tab') {
                    handleTabKey(textarea, event);
                    return;
                }

                if (event.key === 'Enter') {
                    handleEnterKey(textarea, event);
                }
            });

            textarea.addEventListener('input', renderPreview);
            renderPreview();
            setMode(window.matchMedia('(max-width: 920px)').matches ? 'code' : 'split');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initColorSync();
        initCodeEditors();
    });
})();
