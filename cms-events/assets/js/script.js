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

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bindMemberEventForm, { once: true });
	} else {
		bindMemberEventForm();
	}
})();
