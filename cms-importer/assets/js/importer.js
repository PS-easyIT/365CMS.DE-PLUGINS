/**
 * CMS WordPress Importer – Admin JS  v2.0
 * Drag&Drop, AJAX-Upload, Tabs, Folder-Import, Progress
 */
(function () {
    'use strict';

    // ── Tabs ───────────────────────────────────────────────────────────────
    document.querySelectorAll('.ci-tab[data-tab]').forEach(function (tab) {
        tab.addEventListener('click', function () {
            var target = tab.dataset.tab;
            document.querySelectorAll('.ci-tab').forEach(function (t) {
                t.classList.toggle('ci-tab--active', t.dataset.tab === target);
            });
            document.querySelectorAll('.ci-tab-panel').forEach(function (p) {
                p.classList.toggle('ci-tab-panel--active', p.id === 'tab-' + target);
            });
        });
    });

    // ── Upload-Formular ────────────────────────────────────────────────────
    var form         = document.getElementById('js-import-form');
    var fileInput    = document.getElementById('wxr_file');
    var nameEl       = document.getElementById('js-filename');
    var submitBtn    = document.getElementById('js-submit-btn');
    var btnText      = document.getElementById('js-btn-text');
    var btnSpin      = document.getElementById('js-btn-spin');
    var noticeEl     = document.getElementById('js-import-notice');
    var progressEl   = document.getElementById('js-progress');
    var progFill     = document.getElementById('js-prog-fill');
    var progLabel    = document.getElementById('js-prog-label');
    var btnSelect    = document.getElementById('js-btn-select');
    var btnUpload    = document.getElementById('js-btn-upload');
    var uploadStatus = document.getElementById('js-upload-status');
    var uploadedFile = document.getElementById('js-uploaded-file');

    if (!form || !fileInput) { return; }

    // ── Schritt 1: Datei auswählen ──────────────────────────────────────
    if (btnSelect) {
        btnSelect.addEventListener('click', function () { fileInput.click(); });
    }

    fileInput.addEventListener('change', function () {
        var file = fileInput.files[0] || null;
        if (file) {
            if (nameEl)       { nameEl.textContent = '\u2713 ' + file.name + ' (' + fmtBytes(file.size) + ')'; }
            if (btnUpload)    { btnUpload.disabled = false; }
            if (submitBtn)    { submitBtn.disabled = true; }
            if (uploadedFile) { uploadedFile.value = ''; }
            if (uploadStatus) { uploadStatus.textContent = ''; uploadStatus.className = 'ci-wizard-step__status'; }
            setStepState(2, 'active');
            setStepState(3, 'pending');
        } else {
            if (nameEl)    { nameEl.textContent = ''; }
            if (btnUpload) { btnUpload.disabled = true; }
            if (submitBtn) { submitBtn.disabled = true; }
            setStepState(2, 'pending');
            setStepState(3, 'pending');
        }
    });

    // ── Schritt 2: Hochladen ──────────────────────────────────────────
    if (btnUpload) {
        btnUpload.addEventListener('click', function () {
            var file = fileInput.files[0];
            if (!file) { return; }
            if (!file.name.toLowerCase().endsWith('.xml')) {
                showNotice('Nur .xml-Dateien sind erlaubt.', 'error');
                return;
            }
            if (file.size > 52428800) {
                showNotice('Datei zu gro\u00df (max. 50\u202fMB). Gew\u00e4hlt: ' + fmtBytes(file.size), 'error');
                return;
            }

            btnUpload.disabled = true;
            if (uploadStatus) { uploadStatus.textContent = '\u23f3 Hochladen\u2026'; uploadStatus.className = 'ci-wizard-step__status ci-wizard-step__status--busy'; }
            setProgress(0, 'Hochladen\u2026');
            if (progressEl) { progressEl.hidden = false; }

            var fd  = new FormData(form);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.pathname, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.upload.addEventListener('progress', function (ev) {
                if (ev.lengthComputable) {
                    setProgress(Math.round((ev.loaded / ev.total) * 100),
                        'Hochladen\u2026 ' + fmtBytes(ev.loaded) + ' / ' + fmtBytes(ev.total));
                }
            });

            xhr.addEventListener('load', function () {
                if (progressEl) { progressEl.hidden = true; }
                if (xhr.status !== 200) {
                    btnUpload.disabled = false;
                    if (uploadStatus) { uploadStatus.textContent = ''; uploadStatus.className = 'ci-wizard-step__status'; }
                    showNotice('Server-Fehler: HTTP\u00a0' + xhr.status, 'error');
                    return;
                }
                var resp;
                try { resp = JSON.parse(xhr.responseText); } catch (_) {
                    btnUpload.disabled = false;
                    showNotice('Ung\u00fcltige Server-Antwort.', 'error');
                    return;
                }
                if (resp.success) {
                    if (uploadedFile) { uploadedFile.value = resp.filename || ''; }
                    if (uploadStatus) {
                        uploadStatus.textContent = '\u2713 ' + resp.filename + (resp.size ? ' (' + resp.size + ')' : '');
                        uploadStatus.className   = 'ci-wizard-step__status ci-wizard-step__status--ok';
                    }
                    if (submitBtn) { submitBtn.disabled = false; }
                    setStepState(2, 'done');
                    setStepState(3, 'active');
                } else {
                    btnUpload.disabled = false;
                    if (uploadStatus) { uploadStatus.textContent = ''; uploadStatus.className = 'ci-wizard-step__status'; }
                    showNotice(resp.error || 'Upload fehlgeschlagen.', 'error');
                }
            });

            xhr.addEventListener('error', function () {
                btnUpload.disabled = false;
                if (progressEl) { progressEl.hidden = true; }
                if (uploadStatus) { uploadStatus.textContent = ''; uploadStatus.className = 'ci-wizard-step__status'; }
                showNotice('Netzwerkfehler beim Upload.', 'error');
            });

            xhr.send(fd);
        });
    }

    // ── Schritt 3: Import starten ─────────────────────────────────────
    if (submitBtn) {
        submitBtn.addEventListener('click', function () {
            var filename = uploadedFile ? uploadedFile.value : '';
            if (!filename) {
                showNotice('Bitte zun\u00e4chst eine Datei hochladen (Schritt\u00a02).', 'error');
                return;
            }

            setBusy(true);
            setProgress(0, 'Importiere\u2026');
            if (progressEl) { progressEl.hidden = false; }

            var fd = new FormData();
            var nonceEl = form.querySelector('[name="_nonce"]');
            fd.append('_nonce',      nonceEl ? nonceEl.value : '');
            fd.append('cms_action',  'cms_importer_folder_import');
            fd.append('import_file', filename);
            form.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
                if (cb.checked) { fd.append(cb.name, cb.value); }
            });

            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.pathname, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.addEventListener('load', function () {
                setProgress(100, 'Fertig!');
                if (progressEl) { progressEl.hidden = true; }
                if (xhr.status !== 200) {
                    setBusy(false);
                    showNotice('Server-Fehler: HTTP\u00a0' + xhr.status, 'error');
                    return;
                }
                var resp;
                try { resp = JSON.parse(xhr.responseText); } catch (_) {
                    setBusy(false);
                    showNotice('Ung\u00fcltige Server-Antwort.', 'error');
                    return;
                }
                if (resp.success) {
                    var r   = resp.result || {};
                    var msg = resp.message || 'Import abgeschlossen.';
                    showNotice(msg, r.errors > 0 ? 'warning' : 'success');
                    if (r.total !== undefined) { updateStats(r); }
                    setTimeout(function () { window.location.reload(); }, 2000);
                } else {
                    setBusy(false);
                    showNotice(resp.error || 'Import fehlgeschlagen.', 'error');
                }
            });

            xhr.addEventListener('error', function () {
                setBusy(false);
                if (progressEl) { progressEl.hidden = true; }
                showNotice('Netzwerkfehler beim Import.', 'error');
            });

            xhr.send(fd);
        });
    }

    // ── Ordner-Import-Formulare ────────────────────────────────────────────
    document.querySelectorAll('.js-folder-import-form').forEach(function (ff) {
        ff.addEventListener('submit', function (e) {
            e.preventDefault();

            var btn    = ff.querySelector('.js-folder-import-btn');
            var notice = document.getElementById('js-folder-notice');

            if (btn) { btn.disabled = true; btn.textContent = '\u8635 Importiere\u2026'; }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.pathname, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.addEventListener('load', function () {
                if (btn) { btn.disabled = false; btn.textContent = '\u25b6 Importieren'; }

                var resp;
                try { resp = JSON.parse(xhr.responseText); }
                catch (_) {
                    showFolderNotice('Ung\u00fcltige Server-Antwort.', 'error', notice);
                    return;
                }

                if (resp.success) {
                    var r   = resp.result || {};
                    var msg = resp.message || 'Import abgeschlossen.';
                    showFolderNotice(msg, r.errors > 0 ? 'warning' : 'success', notice);
                    if (r.total !== undefined) { updateStats(r); }
                    setTimeout(function () { window.location.reload(); }, 2200);
                } else {
                    showFolderNotice(resp.error || 'Fehler beim Import.', 'error', notice);
                }
            });

            xhr.addEventListener('error', function () {
                if (btn) { btn.disabled = false; btn.textContent = '\u25b6 Importieren'; }
                showFolderNotice('Netzwerkfehler.', 'error', notice);
            });

            xhr.send(new FormData(ff));
        });
    });

    // ── Hilfsfunktionen ────────────────────────────────────────────────────

    function setBusy(busy) {
        if (submitBtn) { submitBtn.disabled = busy; }
        if (btnText) btnText.hidden =  busy;
        if (btnSpin) btnSpin.hidden = !busy;
    }

    function setStepState(num, state) {
        var steps = { 1: document.getElementById('ci-step-1'), 2: document.getElementById('ci-step-2'), 3: document.getElementById('ci-step-3') };
        var seps  = { 1: document.getElementById('ci-sep-1'),  2: document.getElementById('ci-sep-2') };
        var el    = steps[num];
        if (!el) { return; }
        el.className = 'ci-wizard-step' +
            (state === 'active' ? ' ci-wizard-step--active' : state === 'done' ? ' ci-wizard-step--done' : '');
        var sep = seps[num];
        if (sep) { sep.className = 'ci-wizard-sep' + (state === 'done' ? ' ci-wizard-sep--done' : ''); }
    }

    function setProgress(pct, label) {
        if (progFill)  progFill.style.width   = pct + '%';
        if (progLabel && label) progLabel.textContent = label;
    }

    function showNotice(msg, type) {
        if (!noticeEl) { return; }
        noticeEl.className   = 'ci-notice ci-notice--' + type;
        noticeEl.textContent = msg;
        noticeEl.hidden      = false;
        noticeEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function showFolderNotice(msg, type, el) {
        if (!el) { return; }
        el.className   = 'ci-notice ci-notice--' + type;
        el.textContent = msg;
        el.hidden      = false;
    }

    function updateStats(r) {
        var box = document.getElementById('js-stats-box');
        if (!box) { return; }
        box.hidden = false;
        var vals = box.querySelectorAll('.ci-stat__val');
        var data = [r.total || 0, r.imported || 0, r.skipped || 0, r.errors || 0, r.images_downloaded || 0, r.meta_keys || 0];
        vals.forEach(function (el, i) { if (data[i] !== undefined) el.textContent = data[i]; });
    }

    function fmtBytes(b) {
        if (b < 1024)    { return b + ' B'; }
        if (b < 1048576) { return (b / 1024).toFixed(1) + ' KB'; }
        return (b / 1048576).toFixed(1) + ' MB';
    }

})();