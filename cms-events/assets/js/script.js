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

		var cards = Array.prototype.slice.call(root.querySelectorAll('[data-cms-events-card]'));
		var empty = root.querySelector('[data-cms-events-empty]');
		var controls = {
			category: root.querySelector('[data-cms-events-filter="category"]'),
			month: root.querySelector('[data-cms-events-filter="month"]'),
			year: root.querySelector('[data-cms-events-filter="year"]'),
			search: root.querySelector('[data-cms-events-filter="search"]')
		};

		function applyFilters() {
			var selectedCategory = normalize(controls.category && controls.category.value);
			var selectedMonth = normalize(controls.month && controls.month.value);
			var selectedYear = normalize(controls.year && controls.year.value);
			var searchTerm = normalize(controls.search && controls.search.value);
			var visibleCount = 0;

			cards.forEach(function (card) {
				var matchesCategory = !selectedCategory || normalize(card.dataset.category) === selectedCategory;
				var matchesMonth = !selectedMonth || normalize(card.dataset.month) === selectedMonth;
				var matchesYear = !selectedYear || normalize(card.dataset.year) === selectedYear;
				var matchesSearch = !searchTerm || normalize(card.dataset.name).indexOf(searchTerm) !== -1;
				var isVisible = matchesCategory && matchesMonth && matchesYear && matchesSearch;

				card.classList.toggle('hidden', !isVisible);
				if (isVisible) {
					visibleCount += 1;
				}
			});

			if (empty) {
				empty.hidden = visibleCount > 0;
			}
		}

		function resetFilters() {
			Object.keys(controls).forEach(function (key) {
				if (controls[key]) {
					controls[key].value = '';
				}
			});
			applyFilters();
		}

		Object.keys(controls).forEach(function (key) {
			if (controls[key]) {
				controls[key].addEventListener(key === 'search' ? 'input' : 'change', applyFilters);
			}
		});

		root.querySelectorAll('[data-cms-events-reset]').forEach(function (button) {
			button.addEventListener('click', resetFilters);
		});

		applyFilters();
	}

	function init() {
		bindMemberEventForm();
		bindPublicEventFilters();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init, { once: true });
	} else {
		init();
	}
})();
