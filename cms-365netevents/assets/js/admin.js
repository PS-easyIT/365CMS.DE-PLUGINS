(function () {
	'use strict';

	var activeMediaTarget = null;
	var activeMediaIsGallery = false;

	function byId(id) {
		return id ? document.getElementById(id) : null;
	}

	function updateImagePreview(input) {
		var preview = null;
		var value = input ? String(input.value || '').trim() : '';
		var image;

		if (!input) {
			return;
		}

		preview = input.id ? document.querySelector('[data-cms365-image-preview][data-input-id="' + input.id + '"]') : null;
		if (!preview) {
			var field = input.closest('.cms365-image-field, .cms365-media-field');
			preview = field ? field.querySelector('[data-cms365-image-preview]') : null;
		}
		if (!preview) {
			return;
		}

		preview.innerHTML = '';
		if (!value) {
			var empty = document.createElement('span');
			empty.textContent = 'Keine Vorschau';
			preview.appendChild(empty);
			return;
		}

		image = document.createElement('img');
		image.src = value;
		image.alt = 'Bildvorschau';
		image.loading = 'lazy';
		image.addEventListener('error', function () {
			preview.innerHTML = '<span>Vorschau nicht verfügbar</span>';
		});
		preview.appendChild(image);
	}

	function galleryUrls(input) {
		return String(input && input.value ? input.value : '')
			.split(/[\n,]+/)
			.map(function (item) { return item.trim(); })
			.filter(Boolean);
	}

	function updateGalleryPreview(input) {
		var preview = input && input.id ? document.querySelector('[data-cms365-gallery-preview][data-input-id="' + input.id + '"]') : null;
		var urls = input ? galleryUrls(input) : [];

		if (!preview) {
			return;
		}

		preview.innerHTML = '';
		if (!urls.length) {
			var empty = document.createElement('span');
			empty.textContent = 'Noch keine Galeriebilder ausgewählt.';
			preview.appendChild(empty);
			return;
		}

		urls.slice(0, 12).forEach(function (url) {
			var item = document.createElement('span');
			var img = document.createElement('img');
			img.src = url;
			img.alt = 'Galeriebild';
			img.loading = 'lazy';
			item.appendChild(img);
			preview.appendChild(item);
		});
	}

	function appendGalleryUrl(input, url) {
		var urls = galleryUrls(input);
		if (urls.indexOf(url) === -1) {
			urls.push(url);
		}
		input.value = urls.join('\n');
		input.dispatchEvent(new Event('input', { bubbles: true }));
		input.dispatchEvent(new Event('change', { bubbles: true }));
	}

	function selectMediaUrl(url) {
		if (!activeMediaTarget || !url) {
			return;
		}

		if (activeMediaIsGallery && activeMediaTarget.tagName === 'TEXTAREA') {
			appendGalleryUrl(activeMediaTarget, url);
			return;
		}

		activeMediaTarget.value = url;
		activeMediaTarget.dispatchEvent(new Event('input', { bubbles: true }));
		activeMediaTarget.dispatchEvent(new Event('change', { bubbles: true }));
		updateImagePreview(activeMediaTarget);
	}

	function itemUrl(item) {
		return String((item && (item.url || item.fileUrl || item.file_url || item.path)) || '').trim();
	}

	function renderMediaGrid(modal, items) {
		var grid = modal.querySelector('[data-media-picker-grid]');
		var status = modal.querySelector('[data-media-picker-status]');
		if (!grid) {
			return;
		}
		grid.innerHTML = '';
		if (!items.length) {
			if (status) {
				status.textContent = 'Keine Bilder gefunden.';
			}
			return;
		}
		if (status) {
			status.textContent = items.length + ' Bilder verfügbar';
		}
		items.forEach(function (item) {
			var url = itemUrl(item);
			var name = String((item && (item.name || item.title || item.path)) || 'Bild');
			if (!url) {
				return;
			}
			var button = document.createElement('button');
			button.type = 'button';
			button.className = 'cms365-media-picker-item';
			button.setAttribute('data-media-picker-select', '1');
			button.setAttribute('data-media-url', url);
			button.innerHTML = '<img src="' + url.replace(/"/g, '&quot;') + '" alt="" loading="lazy"><span></span>';
			button.querySelector('span').textContent = name;
			grid.appendChild(button);
		});
	}

	function loadMediaItems(modal) {
		var picker = modal.querySelector('[data-media-picker-modal]');
		var search = modal.querySelector('[data-media-picker-search]');
		var status = modal.querySelector('[data-media-picker-status]');
		var apiUrl = picker ? picker.getAttribute('data-api-url') || '/api/media' : '/api/media';
		var token = picker ? picker.getAttribute('data-csrf-token') || '' : '';
		var prefix = search ? String(search.value || '').trim() : '';
		var url = apiUrl + '?action=list_images' + (prefix ? '&filename_prefix=' + encodeURIComponent(prefix) : '') + (token ? '&csrf_token=' + encodeURIComponent(token) : '');

		if (status) {
			status.textContent = 'Lade Medien …';
		}

		fetch(url, { credentials: 'same-origin' })
			.then(function (response) { return response.json(); })
			.then(function (payload) {
				renderMediaGrid(modal, Array.isArray(payload.items) ? payload.items : []);
			})
			.catch(function () {
				if (status) {
					status.textContent = 'Mediathek konnte nicht geladen werden.';
				}
			});
	}

	function initMediaPicker() {
		var modal = byId('settingsMediaPickerModal');
		var instance = null;
		var searchTimer = null;

		if (!modal) {
			return;
		}

		function showModal() {
			if (window.bootstrap && window.bootstrap.Modal && typeof window.bootstrap.Modal.getOrCreateInstance === 'function') {
				instance = window.bootstrap.Modal.getOrCreateInstance(modal);
				instance.show();
			} else {
				modal.hidden = false;
				modal.classList.add('show');
				modal.style.display = 'block';
				modal.removeAttribute('aria-hidden');
			}
			loadMediaItems(modal);
		}

		function hideModal() {
			if (instance && typeof instance.hide === 'function') {
				instance.hide();
			} else {
				modal.classList.remove('show');
				modal.style.display = 'none';
				modal.setAttribute('aria-hidden', 'true');
			}
		}

		document.querySelectorAll('[data-open-media-picker]').forEach(function (button) {
			button.addEventListener('click', function () {
				activeMediaTarget = byId(button.getAttribute('data-target-input') || '');
				activeMediaIsGallery = button.getAttribute('data-gallery-target') === '1';
				var title = modal.querySelector('[data-media-picker-title]');
				if (title) {
					title.textContent = button.getAttribute('data-picker-title') || 'Bild auswählen';
				}
				showModal();
			});
		});

		modal.addEventListener('click', function (event) {
			var close = event.target.closest('[data-bs-dismiss="modal"], .btn-close');
			var select = event.target.closest('[data-media-picker-select="1"]');
			if (close) {
				hideModal();
				return;
			}
			if (select) {
				selectMediaUrl(select.getAttribute('data-media-url') || '');
				if (!activeMediaIsGallery) {
					hideModal();
				}
			}
		});

		var search = modal.querySelector('[data-media-picker-search]');
		if (search) {
			search.addEventListener('input', function () {
				window.clearTimeout(searchTimer);
				searchTimer = window.setTimeout(function () { loadMediaItems(modal); }, 250);
			});
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('form[data-confirm]').forEach(function (form) {
			form.addEventListener('submit', function (event) {
				var message = form.getAttribute('data-confirm') || 'Diese Aktion ausführen?';
				if (!window.confirm(message)) {
					event.preventDefault();
				}
			});
		});

		document.querySelectorAll('.cms365-multiselect').forEach(function (select) {
			select.addEventListener('dblclick', function (event) {
				if (event.target && event.target.tagName === 'OPTION') {
					event.target.selected = !event.target.selected;
				}
			});
		});

		document.querySelectorAll('[data-cms365-image-input]').forEach(function (input) {
			input.addEventListener('input', function () { updateImagePreview(input); });
			input.addEventListener('change', function () { updateImagePreview(input); });
			updateImagePreview(input);
		});

		document.querySelectorAll('[data-cms365-gallery-input]').forEach(function (input) {
			input.addEventListener('input', function () { updateGalleryPreview(input); });
			input.addEventListener('change', function () { updateGalleryPreview(input); });
			updateGalleryPreview(input);
		});

		document.querySelectorAll('[data-clear-media-input]').forEach(function (button) {
			button.addEventListener('click', function () {
				var input = byId(button.getAttribute('data-target-input') || '');
				if (input) {
					input.value = '';
					input.dispatchEvent(new Event('input', { bubbles: true }));
				}
			});
		});

		document.querySelectorAll('[data-clear-gallery-input]').forEach(function (button) {
			button.addEventListener('click', function () {
				var input = byId(button.getAttribute('data-target-input') || '');
				if (input) {
					input.value = '';
					input.dispatchEvent(new Event('input', { bubbles: true }));
				}
			});
		});

		document.querySelectorAll('input[name="tags"],input[name="categories"],input[name="specializations"],input[name="languages"]').forEach(function (input) {
			input.addEventListener('blur', function () {
				var seen = [];
				input.value = (input.value || '').split(/[,;\n]+/).map(function (item) {
					return item.trim();
				}).filter(function (item) {
					var key = item.toLowerCase();
					if (!item || seen.indexOf(key) !== -1) {
						return false;
					}
					seen.push(key);
					return true;
				}).join(', ');
			});
		});

		initMediaPicker();
	});
})();
(function(){'use strict';document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('form[data-confirm]').forEach(function(form){form.addEventListener('submit',function(event){var message=form.getAttribute('data-confirm')||'Diese Aktion ausführen?';if(!window.confirm(message)){event.preventDefault();}});});document.querySelectorAll('.cms365-multiselect').forEach(function(select){select.addEventListener('dblclick',function(event){if(event.target&&event.target.tagName==='OPTION'){event.target.selected=!event.target.selected;}});});document.querySelectorAll('[data-cms365-image-input]').forEach(function(input){var field=input.closest('.cms365-image-field');var preview=field?field.querySelector('[data-cms365-image-preview]'):null;var render=function(){var value=(input.value||'').trim();if(!preview){return;}preview.innerHTML='';if(!value){var empty=document.createElement('span');empty.textContent='Keine Vorschau';preview.appendChild(empty);return;}var img=document.createElement('img');img.src=value;img.alt='Bildvorschau';img.loading='lazy';img.addEventListener('error',function(){preview.innerHTML='<span>Vorschau nicht verfügbar</span>';});preview.appendChild(img);};input.addEventListener('input',render);render();});document.querySelectorAll('input[name="tags"],input[name="categories"],input[name="specializations"],input[name="languages"]').forEach(function(input){input.addEventListener('blur',function(){var seen=[];input.value=(input.value||'').split(/[,;\n]+/).map(function(item){return item.trim();}).filter(function(item){var key=item.toLowerCase();if(!item||seen.indexOf(key)!==-1){return false;}seen.push(key);return true;}).join(', ');});});});})();