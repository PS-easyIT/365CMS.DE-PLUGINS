/**
 * CMS Speakers - Frontend JS v2.0.0
 */
(function () {
    'use strict';

    // -- Color Syncing for Admin Color Pickers ----------------------------
    document.querySelectorAll('input[type="color"]').forEach(function (picker) {
        var textInput = picker.nextElementSibling;
        if (!textInput || textInput.type !== 'text') return;
        // Sync color -> text
        picker.addEventListener('input', function () {
            textInput.value = picker.value;
            textInput.name  = picker.name;
        });
        // Sync text -> color
        textInput.addEventListener('input', function () {
            if (/^#[0-9a-fA-F]{6}$/.test(textInput.value)) {
                picker.value = textInput.value;
            }
        });
    });

    // -- Archive: Filter Auto-Submit --------------------------------------
    document.querySelectorAll('.sp-filter-select').forEach(function (sel) {
        if (!sel.dataset.autosubmit) return; // already has onchange
    });

    // -- Search Clear Button ---------------------------------------------
    var searchInput = document.querySelector('.sp-search-bar input[name="search"]');
    if (searchInput && searchInput.value) {
        var clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.textContent = '\u2715';
        clearBtn.style.cssText = 'background:transparent;border:none;color:rgba(255,255,255,.7);cursor:pointer;font-size:1rem;padding:0 .5rem;';
        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            searchInput.form.submit();
        });
        searchInput.parentNode.insertBefore(clearBtn, searchInput.nextSibling);
    }

    // -- Lazy Image Loading Polyfill -------------------------------------
    if ('loading' in HTMLImageElement.prototype) {
        // Native lazy loading supported
    } else {
        document.querySelectorAll('img[loading="lazy"]').forEach(function (img) {
            img.src = img.dataset.src || img.src;
        });
    }

    // -- Admin: Topic Tag Sync already inline in meta-boxes --------------

    // -- Admin: Organizer Toggle (fallback if inline not loaded) ---------
    if (!window.spkToggleOrg) {
        window.spkToggleOrg = function (val) {
            var manual  = document.getElementById('nev_org_manual');
            var company = document.getElementById('nev_org_company');
            if (manual)  manual.style.display  = val === 'manual'  ? 'block' : 'none';
            if (company) company.style.display  = val === 'company' ? 'block' : 'none';
        };
    }

    // -- Smooth scroll for anchor links ----------------------------------
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            var target = document.querySelector(anchor.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // -- Card click: make entire card clickable --------------------------
    document.querySelectorAll('.sp-card').forEach(function (card) {
        var link = card.querySelector('.sp-card-link');
        if (!link) return;
        card.style.cursor = 'pointer';
        card.addEventListener('click', function (e) {
            if (e.target.closest('button, a, input')) return;
            window.location.href = link.href;
        });
    });

})();
