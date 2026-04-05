/**
 * CMS Booking – Public JavaScript
 * Kalender-Widget, Slot-Laden, Formular-Stepper
 *
 * @package CMS_Booking
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', init);

    function init() {
        initCalendar();
    }

    /* =================================================================== */
    /*  Kalender                                                            */
    /* =================================================================== */

    function initCalendar() {
        const el = document.getElementById('bookingCalendar');
        if (!el) return;

        const providerId    = parseInt(el.dataset.providerId, 10);
        const serviceId     = parseInt(el.dataset.serviceId, 10);
        const apiUrl        = el.dataset.apiUrl || '/api/booking/slots';
        const availableDates = JSON.parse(el.dataset.availableDates || '[]');
        const availSet       = new Set(availableDates);

        let currentYear  = new Date().getFullYear();
        let currentMonth = new Date().getMonth();
        let selectedDate = null;

        render();

        function render() {
            const firstDay  = new Date(currentYear, currentMonth, 1);
            const lastDay   = new Date(currentYear, currentMonth + 1, 0);
            const startDow  = (firstDay.getDay() + 6) % 7; // Mo=0
            const daysInMonth = lastDay.getDate();
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const monthNames = [
                'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
                'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'
            ];
            const dayLabels = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

            let html = '<div class="booking-cal-header">';
            html += '<button type="button" data-cal-prev>‹</button>';
            html += '<span>' + monthNames[currentMonth] + ' ' + currentYear + '</span>';
            html += '<button type="button" data-cal-next>›</button>';
            html += '</div>';

            html += '<div class="booking-cal-grid">';
            dayLabels.forEach(function (d) {
                html += '<div class="cal-day-label">' + d + '</div>';
            });

            // Leere Zellen vor dem 1.
            for (let i = 0; i < startDow; i++) {
                html += '<div class="cal-day empty"></div>';
            }

            for (let d = 1; d <= daysInMonth; d++) {
                const dateStr = currentYear + '-' +
                    String(currentMonth + 1).padStart(2, '0') + '-' +
                    String(d).padStart(2, '0');
                const dateObj = new Date(currentYear, currentMonth, d);
                const isToday = dateObj.getTime() === today.getTime();
                const isAvail = availSet.has(dateStr);
                const isSel   = selectedDate === dateStr;

                let cls = 'cal-day';
                if (isToday) cls += ' today';
                if (isAvail) cls += ' available';
                if (isSel) cls += ' selected';

                html += '<div class="' + cls + '" data-date="' + dateStr + '">' + d + '</div>';
            }

            html += '</div>';
            el.innerHTML = html;

            // Events
            el.querySelector('[data-cal-prev]').addEventListener('click', function () {
                currentMonth--;
                if (currentMonth < 0) { currentMonth = 11; currentYear--; }
                render();
            });
            el.querySelector('[data-cal-next]').addEventListener('click', function () {
                currentMonth++;
                if (currentMonth > 11) { currentMonth = 0; currentYear++; }
                render();
            });

            el.querySelectorAll('.cal-day.available').forEach(function (cell) {
                cell.addEventListener('click', function () {
                    selectedDate = this.dataset.date;
                    document.getElementById('bookingDate').value = selectedDate;
                    render();
                    loadSlots(providerId, serviceId, selectedDate, apiUrl);
                    showStep('step-time');
                });
            });
        }
    }

    /* =================================================================== */
    /*  Slots laden                                                         */
    /* =================================================================== */

    function loadSlots(providerId, serviceId, date, apiUrl) {
        const container = document.getElementById('bookingSlots');
        if (!container) return;

        container.innerHTML = '<p class="text-muted">⏳ Zeitfenster werden geladen…</p>';

        const url = apiUrl + '/' + providerId + '/' + date + '?service_id=' + serviceId;

        fetch(url)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success || !data.slots || data.slots.length === 0) {
                    container.innerHTML = '<p class="text-muted">Keine freien Zeiten an diesem Tag.</p>';
                    return;
                }

                container.textContent = '';
                data.slots.forEach(function (slot) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'booking-slot-btn';
                    btn.dataset.time = String(slot);
                    btn.textContent = String(slot) + ' Uhr';
                    container.appendChild(btn);
                });

                container.querySelectorAll('.booking-slot-btn').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        container.querySelectorAll('.booking-slot-btn').forEach(function (b) {
                            b.classList.remove('selected');
                        });
                        this.classList.add('selected');
                        document.getElementById('startTime').value = this.dataset.time;
                        showStep('step-contact');
                        showStep('step-submit');
                        updateSummary();
                    });
                });
            })
            .catch(function () {
                container.innerHTML = '<p class="text-muted">Fehler beim Laden der Zeitfenster.</p>';
            });
    }

    /* =================================================================== */
    /*  Step-Steuerung                                                      */
    /* =================================================================== */

    function showStep(stepId) {
        var step = document.getElementById(stepId);
        if (step) step.style.display = '';
    }

    /* =================================================================== */
    /*  Zusammenfassung                                                     */
    /* =================================================================== */

    function updateSummary() {
        var summary = document.getElementById('bookingSummary');
        if (!summary) return;

        var date = document.getElementById('bookingDate').value;
        var time = document.getElementById('startTime').value;

        if (date && time) {
            var parts = date.split('-');
            var formatted = parts[2] + '.' + parts[1] + '.' + parts[0];
            summary.textContent = '📅 ' + formatted + ' um ' + time + ' Uhr';
        }
    }

})();
