(function () {
	'use strict';

	function bindMemberEventForm() {
		var onlineToggle = document.querySelector('[data-ev-member-online-toggle]');
		var onlineWrap = document.getElementById('ev-online-wrap');
		var locationWrap = document.getElementById('ev-location-wrap');
		var priceType = document.querySelector('[data-ev-member-price-type]');
		var priceWrap = document.getElementById('ev-price-wrap');

		function syncOnline() {
			if (!onlineToggle || !onlineWrap || !locationWrap) {
				return;
			}

			onlineWrap.hidden = !onlineToggle.checked;
			locationWrap.hidden = onlineToggle.checked;
		}

		function syncPrice() {
			if (!priceType || !priceWrap) {
				return;
			}

			priceWrap.hidden = priceType.value !== 'paid';
		}

		onlineToggle?.addEventListener('change', syncOnline);
		priceType?.addEventListener('change', syncPrice);

		syncOnline();
		syncPrice();
	}

	function normalize(value) {
		return String(value || '').trim().toLocaleLowerCase();
	}

	function bindPublicEventFilters() {
		var root = document.querySelector('[data-cms-events-filter-root]');
		if (!root) {
			return;
		}

		var form = root.querySelector('[data-cms-events-filter-form]');
		var archiveUrl = root.getAttribute('data-cms-events-archive-url') || (form ? form.getAttribute('action') : '') || window.location.pathname;
		var dateFilterActive = root.getAttribute('data-cms-events-date-filter-active') === '1';
		var searchTimer = 0;
		var controls = {
			category: root.querySelector('[data-cms-events-filter="category"]'),
			month: root.querySelector('[data-cms-events-filter="month"]'),
			year: root.querySelector('[data-cms-events-filter="year"]'),
			search: root.querySelector('[data-cms-events-filter="search"]')
		};

		function appendValue(params, key, value) {
			var normalizedValue = String(value || '').trim();
			if (normalizedValue !== '' && normalizedValue !== '0') {
				params.set(key, normalizedValue);
			}
		}

		function buildFilterUrl(includeDateFilter) {
			var params = new URLSearchParams();
			appendValue(params, 'category', controls.category && controls.category.value);
			appendValue(params, 'search', controls.search && controls.search.value);

			if (includeDateFilter) {
				params.set('month', String((controls.month && controls.month.value) || '0'));
				params.set('year', String((controls.year && controls.year.value) || '0'));
			}

			var query = params.toString();
			return archiveUrl + (query ? '?' + query : '');
		}

		function navigateWithFilters(includeDateFilter) {
			window.location.href = buildFilterUrl(includeDateFilter);
		}

		function resetFilters() {
			window.location.href = archiveUrl;
		}

		if (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				navigateWithFilters(dateFilterActive);
			});
		}

		if (controls.category) {
			controls.category.addEventListener('change', function () {
				navigateWithFilters(dateFilterActive);
			});
		}

		if (controls.month) {
			controls.month.addEventListener('change', function () {
				dateFilterActive = true;
				navigateWithFilters(true);
			});
		}

		if (controls.year) {
			controls.year.addEventListener('change', function () {
				dateFilterActive = true;
				navigateWithFilters(true);
			});
		}

		if (controls.search) {
			controls.search.addEventListener('input', function () {
				window.clearTimeout(searchTimer);
				searchTimer = window.setTimeout(function () {
					navigateWithFilters(dateFilterActive);
				}, 350);
			});
		}

		root.querySelectorAll('[data-cms-events-reset]').forEach(function (button) {
			button.addEventListener('click', resetFilters);
		});
	}

	function bindPublicEventCards() {
		document.querySelectorAll('[data-cms-events-card][data-event-url]').forEach(function (card) {
			var navigate = function () {
				var url = card.getAttribute('data-event-url');
				if (url) {
					window.location.href = url;
				}
			};

			card.addEventListener('click', function (event) {
				if (event.target instanceof Element && event.target.closest('a, button, input, select, textarea')) {
					return;
				}

				navigate();
			});

			card.addEventListener('keydown', function (event) {
				if (event.key === 'Enter' || event.key === ' ') {
					event.preventDefault();
					navigate();
				}
			});
		});
	}

	function init() {
		bindMemberEventForm();
		bindPublicEventFilters();
		bindPublicEventCards();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init, { once: true });
	} else {
		init();
	}
})();
