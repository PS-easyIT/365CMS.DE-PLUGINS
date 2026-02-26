/**
 * Job Profile Generator – Wizard Stepper Logic
 * Handles multi-step tab navigation within the Generator page.
 *
 * @package CMS_JobProfileGenerator
 * @since   0.0.1
 */

'use strict';

const JPGWizard = (function () {

    let currentStep = 1;
    let totalSteps  = 5;
    const PREFIX    = 'jpg-wizard-step-';

    /**
     * Initialise the wizard stepper.
     * @param {number} steps  Total number of steps (default: 5)
     * @param {number} start  Step to show initially (default: 1)
     */
    function init(steps, start) {
        totalSteps  = steps || 5;
        currentStep = start || 1;
        showStep(currentStep);
        updateProgress();
    }

    /**
     * Navigate to a specific step.
     * @param {number} step  Target step number (1-based)
     */
    function goTo(step) {
        if (step < 1 || step > totalSteps) return;

        // Optional: validate current step before leaving
        if (step > currentStep && !validateStep(currentStep)) {
            return;
        }

        currentStep = step;
        showStep(step);
        updateProgress();
        updateTabHighlight(step);
    }

    /** Show next step */
    function next() {
        goTo(currentStep + 1);
    }

    /** Show previous step */
    function prev() {
        goTo(currentStep - 1);
    }

    /**
     * Show a specific step panel and hide all others.
     * @param {number} step
     */
    function showStep(step) {
        for (let i = 1; i <= totalSteps; i++) {
            const panel = document.getElementById(PREFIX + i);
            if (panel) {
                panel.style.display = (i === step) ? 'block' : 'none';
            }
        }
    }

    /**
     * Update progress bar (if exists).
     */
    function updateProgress() {
        const bar = document.getElementById('jpg-wizard-progress');
        if (!bar) return;
        const pct = Math.round((currentStep / totalSteps) * 100);
        bar.style.width = pct + '%';

        const label = document.getElementById('jpg-wizard-progress-label');
        if (label) {
            label.textContent = 'Schritt ' + currentStep + ' von ' + totalSteps;
        }
    }

    /**
     * Update tab highlight (set .active on the correct tab button).
     * @param {number} step
     */
    function updateTabHighlight(step) {
        document.querySelectorAll('.jpg-wizard-tab').forEach(function (tab, idx) {
            tab.classList.toggle('active', idx + 1 === step);
        });
    }

    /**
     * Basic per-step validation.
     * Override this for custom validation logic.
     * @param {number} step
     * @returns {boolean}
     */
    function validateStep(step) {
        const panel = document.getElementById(PREFIX + step);
        if (!panel) return true;

        // Check HTML5 required fields within the step
        const required = panel.querySelectorAll('[required]');
        let valid = true;

        required.forEach(function (field) {
            if (!field.value || !field.value.trim()) {
                field.classList.add('jpg-invalid');
                valid = false;
            } else {
                field.classList.remove('jpg-invalid');
            }
        });

        if (!valid && typeof jpgShowToast === 'function') {
            jpgShowToast('⚠️ Bitte alle Pflichtfelder ausfüllen.', 'warn');
        }

        return valid;
    }

    /** Return current step number. */
    function getCurrent() {
        return currentStep;
    }

    // Public API
    return {
        init:       init,
        goTo:       goTo,
        next:       next,
        prev:       prev,
        getCurrent: getCurrent,
        validate:   validateStep,
    };

})();

// Make available globally
window.JPGWizard = JPGWizard;
