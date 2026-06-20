(() => {
  const $ = (selector, scope = document) => scope.querySelector(selector);
  const $$ = (selector, scope = document) => [...scope.querySelectorAll(selector)];
  const text = (value) => String(value ?? '');

  const SECTION_TYPES = {
    text: 'Textbereich',
    card_grid: 'Card Grid',
    image_cards: 'Bildkarten Bereich',
    services: 'Meine Leistungen',
    offers: 'Angebotsbereich',
    comparison: 'Vergleich / Infografik',
    steps: 'Ablauf Modul',
    cta: 'CTA Band',
    trust: 'Trust Bereich',
    technology: 'Technologie Bereich',
    contact: 'Kontaktbereich',
    divider: 'Trenner / Separator',
    html: 'Freier HTML Bereich',
    infographic: 'Infografik Bereich'
  };

  const MODULE_PRESETS = {
    services: 'Meine Leistungen',
    steps: 'So läuft die Zusammenarbeit ab',
    comparison: 'Vergleichsmodul',
    trust: 'Trust Bereich',
    technology: 'Technologie Bereich',
    cta: 'CTA Band',
    divider: 'Trenner',
    html: 'Freier HTML Bereich'
  };

  const LANDINGPAGE_PROPOSALS = {
    copilot_readiness: 'Copilot Readiness Landingpage',
    security_review: 'Microsoft 365 Security Review',
    governance_workshop: 'SharePoint Governance Workshop',
    admin_automation: 'Admin Workshop & PowerShell Automatisierung'
  };

  const CARD_TYPES = {
    text: 'Klassische Text Card',
    image_label: 'Bild Card mit Label',
    offer_icon_tab: 'Angebots Card mit Icon Reiter',
    step: 'Step Card',
    problem_solution: 'Problem Lösung Card'
  };

  const TARGET_TYPES = {
    internal: 'Interne URL',
    external: 'Externe URL',
    anchor: 'Anker auf der Seite',
    contact: 'Kontaktformular',
    email: 'E-Mail Link',
    phone: 'Telefon Link',
    bookings: 'Microsoft Bookings Link',
    download: 'Download Link'
  };

  let mediaFieldCounter = 0;
  let mediaPickerModal = null;
  let activeMediaInput = null;
  const mediaPickerState = { items: [], loaded: false, query: '' };

  const mediaConfig = (() => {
    const element = $('#beratung-media-config');
    if (!element) return {};
    try {
      const parsed = JSON.parse(element.textContent || '{}');
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (_) {
      return {};
    }
  })();

  const parseJson = (textarea, fallback) => {
    try {
      const data = JSON.parse(textarea.value || '');
      return data && typeof data === 'object' ? data : fallback;
    } catch (_) {
      return fallback;
    }
  };

  const syncJson = (textarea, value) => { textarea.value = JSON.stringify(value, null, 2); };
  const optionHtml = (options, selected) => Object.entries(options).map(([value, label]) => `<option value="${value}"${String(value) === String(selected) ? ' selected' : ''}>${label}</option>`).join('');

  const normalizeMediaUrl = (value) => {
    const raw = text(value).trim();
    if (!raw) return '';
    if (/^https?:\/\//i.test(raw)) {
      try {
        const url = new URL(raw, window.location.origin);
        if (url.origin === window.location.origin) return `${url.pathname}${url.search}${url.hash}`;
      } catch (_) {
        return raw;
      }
    }
    if (/^(uploads|media|media-file)(\/|\?|$)/i.test(raw)) return `/${raw.replace(/^\/+/, '')}`;
    return raw;
  };

  const showMediaMessage = (type, message) => {
    if (typeof window.cmsAlert === 'function') {
      window.cmsAlert(type === 'danger' ? 'danger' : 'success', message);
      return;
    }
    console[type === 'danger' ? 'error' : 'log'](message);
  };

  const fetchMediaJson = (url, options = {}) => fetch(url, options).then((response) => response.json().catch(() => ({})).then((payload) => {
    if (!response.ok || payload?.success === 0 || payload?.success === false) {
      throw new Error(payload?.message || payload?.error || 'Media-Anfrage fehlgeschlagen.');
    }
    return payload;
  }));

  const updateMediaPreview = (input, preview) => {
    if (!preview) return;
    const value = normalizeMediaUrl(input.value);
    preview.innerHTML = '';
    if (!value) {
      preview.hidden = true;
      return;
    }
    const image = document.createElement('img');
    image.src = value;
    image.alt = 'Bildvorschau';
    image.loading = 'lazy';
    preview.append(image);
    preview.hidden = false;
  };

  const setInputMediaValue = (input, value) => {
    input.value = normalizeMediaUrl(value);
    input.dispatchEvent(new Event('input', { bubbles: true }));
  };

  const closeMediaPicker = () => {
    if (mediaPickerModal) mediaPickerModal.classList.remove('is-open');
    activeMediaInput = null;
  };

  const ensureMediaPickerModal = () => {
    if (mediaPickerModal) return mediaPickerModal;
    const modal = document.createElement('div');
    modal.className = 'beratung-media-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.innerHTML = `
      <div class="beratung-media-modal__panel">
        <div class="beratung-media-modal__head">
          <div><h2>Bild aus der Mediathek auswählen</h2><p>Ein Klick übernimmt das Bild direkt in das aktive Feld.</p></div>
          <button type="button" class="beratung-media-modal__close" data-media-close aria-label="Schließen">×</button>
        </div>
        <div class="beratung-media-modal__toolbar">
          <input type="search" placeholder="Mediathek durchsuchen …" data-media-search>
          <div class="beratung-media-modal__status" data-media-status>Lade Medien …</div>
        </div>
        <div class="beratung-media-modal__body"><div class="beratung-media-grid" data-media-grid></div></div>
      </div>`;
    document.body.append(modal);
    $('[data-media-close]', modal)?.addEventListener('click', closeMediaPicker);
    modal.addEventListener('click', (event) => { if (event.target === modal) closeMediaPicker(); });
    $('[data-media-search]', modal)?.addEventListener('input', (event) => {
      mediaPickerState.query = text(event.target.value).trim().toLowerCase();
      renderMediaItems();
    });
    $('[data-media-grid]', modal)?.addEventListener('click', (event) => {
      const button = event.target.closest('[data-media-url]');
      if (!button || !activeMediaInput) return;
      setInputMediaValue(activeMediaInput, button.dataset.mediaUrl || '');
      closeMediaPicker();
    });
    mediaPickerModal = modal;
    return modal;
  };

  const renderMediaItems = () => {
    const modal = ensureMediaPickerModal();
    const grid = $('[data-media-grid]', modal);
    const status = $('[data-media-status]', modal);
    if (!grid) return;
    const query = mediaPickerState.query;
    const items = query ? mediaPickerState.items.filter((item) => `${item.name || ''} ${item.path || ''}`.toLowerCase().includes(query)) : mediaPickerState.items;
    grid.innerHTML = '';
    if (status) status.textContent = query ? `${items.length} Treffer` : `${items.length} Medien verfügbar`;
    if (!items.length) {
      grid.innerHTML = '<div class="beratung-media-empty">Keine passenden Bilder gefunden.</div>';
      return;
    }
    items.forEach((item) => {
      const url = normalizeMediaUrl(item.url || '');
      if (!url) return;
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'beratung-media-item';
      button.dataset.mediaUrl = url;
      button.innerHTML = `<span class="beratung-media-item__image"><img src="${esc(url)}" alt="${esc(item.name || 'Bild')}" loading="lazy"></span><span class="beratung-media-item__meta"><span class="beratung-media-item__name">${esc(item.name || 'Bild')}</span><span class="beratung-media-item__path">${esc(item.path || url)}</span></span>`;
      grid.append(button);
    });
  };

  const loadMediaItems = () => {
    if (mediaPickerState.loaded) {
      renderMediaItems();
      return Promise.resolve();
    }
    const modal = ensureMediaPickerModal();
    const status = $('[data-media-status]', modal);
    const libraryUrl = mediaConfig.libraryUrl || '/api/media';
    const csrfToken = mediaConfig.csrfToken || '';
    if (status) status.textContent = 'Lade Medien …';
    return fetchMediaJson(`${libraryUrl}?action=list_images`, {
      method: 'GET',
      headers: csrfToken ? { 'X-CSRF-Token': csrfToken } : {},
      credentials: 'same-origin'
    }).then((payload) => {
      mediaPickerState.items = Array.isArray(payload.items) ? payload.items : [];
      mediaPickerState.loaded = true;
      renderMediaItems();
    }).catch((error) => {
      if (status) status.textContent = 'Laden fehlgeschlagen';
      const grid = $('[data-media-grid]', modal);
      if (grid) grid.innerHTML = `<div class="beratung-media-empty">${esc(error.message || 'Medien konnten nicht geladen werden.')}</div>`;
    });
  };

  const openMediaPicker = (input) => {
    activeMediaInput = input;
    const modal = ensureMediaPickerModal();
    modal.classList.add('is-open');
    loadMediaItems();
    window.setTimeout(() => $('[data-media-search]', modal)?.focus(), 50);
  };

  const uploadMediaFile = (file, input, button) => {
    if (!file) return;
    const uploadUrl = mediaConfig.uploadUrl || '/api/cms-beratung/media-upload';
    const csrfToken = mediaConfig.csrfToken || '';
    const maxSizeMb = Number(mediaConfig.maxSizeMb || 10);
    const maxSize = maxSizeMb * 1024 * 1024;
    const extension = text(file.name).split('.').pop().toLowerCase();
    const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'ico'];
    if (file.type && !file.type.startsWith('image/') && !allowedExtensions.includes(extension)) {
      showMediaMessage('danger', 'Bitte eine Bilddatei auswählen.');
      return;
    }
    if (file.size > maxSize) {
      showMediaMessage('danger', `Das Bild ist größer als ${maxSizeMb} MB.`);
      return;
    }
    const formData = new FormData();
    formData.append('file', file, file.name);
    formData.append('csrf_token', csrfToken);
    button.disabled = true;
    button.textContent = 'Upload …';
    fetchMediaJson(uploadUrl, {
      method: 'POST',
      headers: csrfToken ? { 'X-CSRF-Token': csrfToken } : {},
      body: formData,
      credentials: 'same-origin'
    }).then((payload) => {
      const url = payload?.file?.url || payload?.url || '';
      setInputMediaValue(input, url);
      mediaPickerState.loaded = false;
      showMediaMessage('success', `Bild wurde in ${mediaConfig.uploadFolder || '/uploads/beratung/'} hochgeladen.`);
    }).catch((error) => {
      showMediaMessage('danger', error.message || 'Upload fehlgeschlagen.');
    }).finally(() => {
      button.disabled = false;
      button.textContent = 'Upload';
    });
  };

  const enhanceMediaFields = (root) => {
    $$('input[data-field="image_url"], input[data-field="background_image_url"], input[data-card-field="image_url"], input[data-card-field="logo_url"]', root).forEach((input) => {
      if (input.dataset.beratungMediaEnhanced === '1') return;
      input.dataset.beratungMediaEnhanced = '1';
      input.id ||= `beratung-media-field-${++mediaFieldCounter}`;
      input.placeholder ||= '/uploads/beratung/... oder https://...';
      const label = input.closest('label');
      if (label) label.classList.add('beratung-media-label');
      const tools = document.createElement('div');
      tools.className = 'beratung-media-tools';
      tools.innerHTML = '<button type="button" class="beratung-media-btn" data-media-upload>Upload</button><button type="button" class="beratung-media-btn" data-media-library>Mediathek</button><button type="button" class="beratung-media-btn is-danger" data-media-clear>Leeren</button><input type="file" accept="image/*" hidden data-media-file><small class="beratung-media-note">Uploads werden im Ordner /uploads/beratung gespeichert.</small>';
      const preview = document.createElement('div');
      preview.className = 'beratung-media-preview';
      preview.hidden = true;
      input.insertAdjacentElement('afterend', tools);
      tools.insertAdjacentElement('afterend', preview);
      const fileInput = $('[data-media-file]', tools);
      const uploadButton = $('[data-media-upload]', tools);
      uploadButton?.addEventListener('click', () => fileInput?.click());
      fileInput?.addEventListener('change', () => uploadMediaFile(fileInput.files?.[0] || null, input, uploadButton));
      $('[data-media-library]', tools)?.addEventListener('click', () => openMediaPicker(input));
      $('[data-media-clear]', tools)?.addEventListener('click', () => setInputMediaValue(input, ''));
      input.addEventListener('input', () => updateMediaPreview(input, preview));
      updateMediaPreview(input, preview);
    });
  };

  const bindInputs = (root, model, sync, callback = null) => {
    $$('[data-field]', root).forEach((input) => {
      const field = input.dataset.field;
      if (input.type === 'checkbox') input.checked = Boolean(model[field]);
      else input.value = text(model[field]);
      input.addEventListener('input', () => {
        if (input.type === 'checkbox') model[field] = input.checked;
        else if (input.type === 'number') model[field] = Number(input.value || 0);
        else model[field] = input.value;
        if (callback) callback(field);
        sync();
      });
      input.addEventListener('change', () => input.dispatchEvent(new Event('input')));
    });
  };

  const buttonDefaults = (textValue = '', target = '', style = 'primary') => ({ text: textValue, target, target_type: 'internal', style });

  const renderHeroBuilder = () => {
    const mount = $('#beratung-hero-builder');
    const target = $('#' + (mount?.dataset?.target || ''));
    if (!mount || !target) return;

    const hero = parseJson(target, {});
    hero.enabled ??= true;
    hero.image_position ??= 'left';
    hero.image_flush ??= true;
    hero.image_height ??= 520;
    hero.image_width ??= 46;
    hero.image_fit ??= 'cover';
    hero.badge_show ??= true;
    hero.vertical_align ??= 'center';
    hero.mobile_order ??= 'image-first';
    hero.button_1 ??= { text: 'Beratung anfragen', target: '#kontakt', target_type: 'contact', style: 'primary' };
    hero.button_2 ??= { text: 'Leistungen ansehen', target: '#leistungen', target_type: 'anchor', style: 'ghost' };
    hero.button_3 ??= buttonDefaults('', '', 'secondary');

    const sync = () => syncJson(target, hero);
    mount.className = 'beratung-builder beratung-hero-builder';
    mount.innerHTML = `
      <div class="beratung-builder__section is-hero">
        <div class="beratung-builder__section-head"><strong>Hero Bereich</strong><span>Content Header</span></div>
        <div class="beratung-builder__grid">
          <label><input data-field="enabled" type="checkbox"> Hero aktivieren</label>
          <label><input data-field="badge_show" type="checkbox"> Badge anzeigen</label>
          <label><input data-field="image_flush" type="checkbox"> Bild nahtlos am Rand anzeigen</label>
          <label>Bildposition <select data-field="image_position"><option value="left">links</option><option value="right">rechts</option></select></label>
          <label>Mobile Reihenfolge <select data-field="mobile_order"><option value="image-first">Bild zuerst</option><option value="text-first">Text zuerst</option></select></label>
          <label>Vertikale Ausrichtung <select data-field="vertical_align"><option value="start">oben</option><option value="center">mittig</option><option value="end">unten</option></select></label>
          <label>Bild URL / Mediathek-Pfad <input data-field="image_url" placeholder="/uploads/... oder https://..."></label>
          <label>Bild Alt Text <input data-field="image_alt"></label>
          <label>Bildhöhe <input data-field="image_height" type="number" min="180" max="900"></label>
          <label>Bildbreite in % <input data-field="image_width" type="number" min="25" max="70"></label>
          <label>Bild Zuschnitt <select data-field="image_fit"><option value="cover">Cover</option><option value="contain">Contain</option></select></label>
          <label>Badge Text <input data-field="badge_text"></label>
          <label>Haupttitel <input data-field="title"></label>
          <label>Untertitel <input data-field="subtitle"></label>
          <label>Beschreibungstext <textarea data-field="description" rows="3"></textarea></label>
          <label>Trust Hinweis <input data-field="trust_text"></label>
          <label>Hintergrundfarbe <input data-field="background_color" type="color"></label>
          <label>Textfarbe <input data-field="text_color" type="color"></label>
        </div>
        <div class="beratung-builder__cards is-buttons"></div>
      </div>`;
    bindInputs(mount, hero, sync);
    enhanceMediaFields(mount);
    renderButtons($('.is-buttons', mount), hero, sync, ['button_1', 'button_2', 'button_3']);
    sync();
  };

  const renderButtons = (wrap, model, sync, keys) => {
    keys.forEach((key, index) => {
      model[key] ??= buttonDefaults();
      const button = model[key];
      const box = document.createElement('div');
      box.className = 'beratung-builder__card is-button';
      box.innerHTML = `
        <strong>Button ${index + 1}</strong>
        <label>Text <input data-button-field="text"></label>
        <label>Zieltyp <select data-button-field="target_type">${optionHtml(TARGET_TYPES, button.target_type || 'internal')}</select></label>
        <label>Ziel <input data-button-field="target"></label>
        <label>Stil <select data-button-field="style"><option value="primary">Primär</option><option value="secondary">Sekundär</option><option value="ghost">Ghost</option><option value="link">Link</option></select></label>`;
      $$('[data-button-field]', box).forEach((input) => {
        const field = input.dataset.buttonField;
        input.value = text(button[field]);
        input.addEventListener('input', () => { button[field] = input.value; sync(); });
        input.addEventListener('change', () => input.dispatchEvent(new Event('input')));
      });
      wrap.append(box);
    });
  };

  const baseSection = (index, type = 'card_grid') => ({
    id: `${type}-${index + 1}`,
    anchor_id: `${type}-${index + 1}`,
    enabled: true,
    type,
    internal_name: MODULE_PRESETS[type] || 'Neuer Bereich',
    eyebrow: '',
    title: MODULE_PRESETS[type] || 'Neuer Bereich',
    intro: '',
    background_color: '#ffffff',
    background_image_url: '',
    text_color: '#111827',
    padding_top: 56,
    padding_bottom: 56,
    max_width: 1200,
    text_align: 'left',
    columns: type === 'technology' ? 4 : 3,
    card_type: type === 'steps' ? 'step' : type === 'comparison' ? 'problem_solution' : 'text',
    card_design: 'standard',
    equal_height: true,
    categories_enabled: type === 'services',
    note_text: '',
    display_style: type === 'technology' ? 'icon_grid' : 'cards',
    auto_number: true,
    connector: true,
    title_band_enabled: type === 'comparison',
    title_band_text: '',
    title_band_background_color: '#1e3a8a',
    title_band_text_color: '#ffffff',
    comparison_variant: 'two_columns',
    border_enabled: true,
    shadow_enabled: true,
    mobile_stack: true,
    faq_allow_multiple: false,
    faq_icon_style: 'plus',
    faq_open_behavior: 'none',
    faq_question_background_color: '#ffffff',
    faq_question_text_color: '#111827',
    faq_answer_background_color: '#f8fafc',
    faq_schema_enabled: true,
    button_1_text: '',
    button_1_target: '',
    button_1_target_type: 'internal',
    button_1_style: 'primary',
    button_2_text: '',
    button_2_target: '',
    button_2_target_type: 'internal',
    button_2_style: 'ghost',
    divider_type: 'line',
    divider_title: '',
    divider_subtitle: '',
    divider_icon: '',
    divider_line_color: '#dbeafe',
    divider_width: 100,
    divider_mobile_behavior: 'stack',
    html: '',
    cards: []
  });

  const newSection = (index, type = 'card_grid') => {
    const section = baseSection(index, type);
    if (type === 'services') {
      section.anchor_id = 'leistungen';
      section.eyebrow = 'Meine Leistungen';
      section.title = 'Microsoft 365 und Copilot Beratung aus der Praxis';
      section.intro = 'Frei sortierbare Leistungen für Tenant, Security, Compliance, Governance, Automatisierung und Workshops.';
      section.button_1_text = 'Beratung anfragen';
      section.button_1_target = '#kontakt';
      section.button_1_target_type = 'contact';
      section.note_text = 'Alle Leistungen können als Einzeltermin, Workshop oder Projektbegleitung kombiniert werden.';
      section.cards = ['Microsoft 365 Tenant Check', 'Copilot Readiness Check', 'Entra ID Security Review'].map((title) => newCard('text', { title, category: 'Microsoft 365', icon: '✓' }));
    }
    if (type === 'faq') {
      section.anchor_id = 'faq';
      section.eyebrow = 'FAQ';
      section.title = 'Häufige Fragen zur Microsoft 365 Beratung';
      section.cards = [newCard('text', { question: 'Was ist Microsoft 365 Copilot?', answer: 'Microsoft 365 Copilot verbindet KI-Funktionen mit Microsoft 365 Apps und Unternehmensdaten.', default_open: false })];
    }
    if (type === 'divider') {
      section.title = '';
      section.intro = '';
      section.divider_title = 'Nächster Abschnitt';
    }
    return section;
  };

  const newCard = (type = 'text', overrides = {}) => ({
    enabled: true,
    card_type: type,
    title: 'Neue Card',
    text: '',
    extra_text: '',
    icon: '💡',
    category: '',
    metric: '',
    name: '',
    logo_url: '',
    question: '',
    answer: '',
    default_open: false,
    image_url: '',
    image_alt: '',
    image_height: 230,
    image_fit: 'cover',
    label: '',
    label_position: 'left',
    label_style: 'filled',
    button_label: '',
    button_url: '',
    badge: '',
    featured: false,
    background_color: '#ffffff',
    text_color: '#111827',
    border_enabled: true,
    border_color: '#dbeafe',
    shadow_enabled: true,
    hover_enabled: true,
    tab_enabled: true,
    icon_background: '#eff6ff',
    icon_color: '#2563eb',
    card_size: 'medium',
    step_number: '',
    auto_number: true,
    connector: true,
    problem_title: '',
    problem_text: '',
    solution_title: '',
    solution_text: '',
    risk_level: 'medium',
    recommendation: '',
    ...overrides
  });

  const proposalHero = (key) => ({
    enabled: true,
    image_url: '',
    image_position: 'left',
    image_flush: true,
    image_height: 520,
    image_width: 46,
    image_fit: 'cover',
    image_alt: LANDINGPAGE_PROPOSALS[key] || 'Microsoft 365 Beratung',
    badge_text: key === 'security_review' ? 'Security · Entra ID · Defender' : key === 'governance_workshop' ? 'SharePoint · OneDrive · Governance' : key === 'admin_automation' ? 'Admin Workshop · PowerShell · Betrieb' : 'Copilot · Readiness · Governance',
    badge_show: true,
    title: LANDINGPAGE_PROPOSALS[key] || 'Microsoft 365 Beratung',
    subtitle: 'Strukturierter Vorschlag mit Platzhaltertexten für eine hochwertige Beratungs-Landingpage.',
    description: 'Nutze diesen Vorschlag als Startpunkt und passe Inhalte, Cards und CTA direkt im Builder an. FAQs werden zentral im Menüpunkt M365 FAQs gepflegt.',
    button_1: { text: 'Beratung anfragen', target: '#kontakt', target_type: 'contact', style: 'primary' },
    button_2: { text: 'Leistungen ansehen', target: '#leistungen', target_type: 'anchor', style: 'ghost' },
    button_3: { text: 'Ablauf ansehen', target: '#ablauf', target_type: 'anchor', style: 'secondary' },
    trust_text: 'Platzhalter: Praxisnahe Beratung mit Fokus auf Sicherheit, Governance und Betrieb.',
    background_color: '#f8fafc',
    text_color: '#111827',
    vertical_align: 'center',
    mobile_order: 'image-first'
  });

  const proposalSections = (key) => {
    const services = newSection(0, 'services');
    const steps = newSection(1, 'steps');
    const comparison = newSection(2, 'comparison');
    const trust = newSection(4, 'trust');
    const cta = newSection(5, 'cta');

    services.cards = [];
    const serviceTitles = key === 'security_review'
      ? ['Entra ID Security Review', 'Conditional Access Bewertung', 'Microsoft Defender Review', 'Secure Score Einordnung', 'Admin Rollen Check', 'Dokumentation und Maßnahmenplan']
      : key === 'governance_workshop'
        ? ['SharePoint Governance Check', 'OneDrive Freigaben Review', 'Site Lifecycle Konzept', 'Berechtigungsmodell', 'Vorlagen und Namenskonzept', 'Admin Workshop']
        : key === 'admin_automation'
          ? ['Admin Workshop', 'PowerShell Automatisierung', 'Tenant Dokumentation', 'Exchange Online Analyse', 'Betriebsübergabe', 'Projektbegleitung']
          : ['Microsoft 365 Tenant Check', 'Copilot Readiness Check', 'SharePoint Governance', 'Microsoft Purview Beratung', 'Entra ID Security Review', 'Admin Workshop'];
    services.cards = serviceTitles.map((title, index) => newCard('text', {
      icon: ['🏢', '🤖', '🔐', '🧭', '🛡️', '⚙️'][index % 6],
      category: index < 2 ? 'Analyse' : index < 4 ? 'Governance' : 'Umsetzung',
      title,
      text: 'Platzhaltertext: Beschreibe hier Nutzen, Vorgehen und Ergebnis dieser Leistung.'
    }));

    steps.cards = ['Erstgespräch', 'Zielklärung', 'Technische Analyse', 'Bewertung', 'Maßnahmenplan', 'Umsetzung'].map((title, index) => newCard('step', {
      title,
      text: 'Platzhaltertext: Kurze Beschreibung dieses Schrittes.',
      auto_number: true,
      connector: index < 5
    }));

    comparison.title_band_text = key === 'copilot_readiness' ? 'Copilot bereit vs. Copilot riskant eingeführt' : key === 'security_review' ? 'Ohne Security Review vs. mit klarer Maßnahmenliste' : 'Ist-Zustand vs. Zielbild';
    comparison.cards = [
      newCard('text', { icon: '⚠️', title: 'Ist Zustand', text: 'Platzhalter: Unklare Berechtigungen, gewachsene Strukturen und fehlende Governance.', extra_text: 'Typische Folge: Risiken bleiben unsichtbar.' }),
      newCard('text', { icon: '✅', title: 'Zielbild', text: 'Platzhalter: Transparente Struktur, priorisierte Maßnahmen und klare Verantwortlichkeiten.', extra_text: 'Typische Folge: sicherer Betrieb und bessere Entscheidungsgrundlagen.' })
    ];

    trust.cards = ['Praxis aus echten Admin Umgebungen', 'Fokus auf Sicherheit und Betrieb', 'Verständliche Dokumentation'].map((title, index) => newCard('text', {
      icon: ['🏢', '🛡️', '📘'][index],
      metric: index === 0 ? '20+ Jahre' : '',
      title,
      text: 'Platzhaltertext: Erläutere hier den Vertrauensfaktor.'
    }));

    cta.eyebrow = 'Nächster Schritt';
    cta.title = 'Bereit für den nächsten Schritt?';
    cta.intro = 'Platzhaltertext: Lade Besucher zu einem unverbindlichen Gespräch oder einer Buchung ein.';
    cta.button_1_text = 'Beratung anfragen';
    cta.button_1_target = '#kontakt';
    cta.button_1_target_type = 'contact';
    cta.button_2_text = 'Leistungen ansehen';
    cta.button_2_target = '#leistungen';
    cta.button_2_target_type = 'anchor';
    cta.background_color = '#1e3a8a';
    cta.text_color = '#ffffff';

    return [services, steps, comparison, trust, cta];
  };

  const renderSectionBuilder = () => {
    const mount = $('#beratung-builder');
    const target = $('#' + (mount?.dataset?.target || ''));
    if (!mount || !target) return;

    let sections = parseJson(target, []);
    if (!Array.isArray(sections)) sections = [];
    sections = sections.filter((section) => section?.type !== 'faq');

    const sync = () => syncJson(target, sections);
    const rerender = () => { sync(); renderSectionBuilder(); };

    mount.className = 'beratung-builder';
    mount.innerHTML = '';
    const toolbar = document.createElement('div');
    toolbar.className = 'beratung-builder__toolbar';
    toolbar.innerHTML = `<div><button type="button" class="beratung-btn" data-add-basic>Bereich hinzufügen</button></div><label>Landingpage Vorschlag laden <select data-landingpage-proposal><option value="">Bitte wählen</option>${optionHtml(LANDINGPAGE_PROPOSALS, '')}</select></label><label>Spezialmodul hinzufügen <select data-add-module><option value="">Bitte wählen</option>${optionHtml(MODULE_PRESETS, '')}</select></label><span>Bereiche und Cards können per Drag and Drop sortiert werden.</span>`;
    $('[data-add-basic]', toolbar).addEventListener('click', () => { sections.push(newSection(sections.length)); rerender(); });
    $('[data-landingpage-proposal]', toolbar).addEventListener('change', (event) => {
      const value = event.target.value;
      if (!value) return;
      sections = proposalSections(value);
      const heroTarget = $('#hero_json');
      if (heroTarget) syncJson(heroTarget, proposalHero(value));
      rerender();
      renderHeroBuilder();
    });
    $('[data-add-module]', toolbar).addEventListener('change', (event) => {
      const value = event.target.value;
      if (!value) return;
      sections.push(newSection(sections.length, value));
      rerender();
    });
    mount.append(toolbar);

    sections.forEach((section, index) => {
      section.cards = Array.isArray(section.cards) ? section.cards : [];
      section.type ??= 'card_grid';
      section.card_type ??= 'text';
      section.anchor_id ??= section.id ?? `bereich-${index + 1}`;
      const box = document.createElement('section');
      box.className = 'beratung-builder__section';
      box.draggable = true;
      box.dataset.index = String(index);
      box.innerHTML = sectionTemplate(section, index);

      const updatePreview = () => { const preview = $('.beratung-live-preview', box); if (preview) preview.innerHTML = sectionPreview(section); };
      const updateVisibility = () => { updateSectionVisibility(box, section); updatePreview(); };
      bindInputs(box, section, sync, (field) => {
        if (field === 'type') {
          if (section.type === 'steps') section.card_type = 'step';
          if (section.type === 'comparison') section.card_type = 'problem_solution';
          if (section.type === 'technology') section.display_style ||= 'icon_grid';
        }
        updateVisibility();
      });
      enhanceMediaFields(box);

      const cardsWrap = $('.beratung-builder__cards', box);
      section.cards.forEach((card, cardIndex) => cardsWrap.append(renderCard(card, section, cardIndex, sync, rerender)));

      $$('[data-action]', box).forEach((button) => button.addEventListener('click', () => {
        const action = button.dataset.action;
        if (action === 'delete') sections.splice(index, 1);
        if (action === 'duplicate') sections.splice(index + 1, 0, JSON.parse(JSON.stringify(section)));
        if (action === 'add-card') section.cards.push(newCard(section.card_type));
        rerender();
      }));

      box.addEventListener('dragstart', (event) => event.dataTransfer?.setData('text/plain', `section:${index}`));
      box.addEventListener('dragover', (event) => event.preventDefault());
      box.addEventListener('drop', (event) => {
        event.preventDefault();
        const raw = event.dataTransfer?.getData('text/plain') || '';
        if (!raw.startsWith('section:')) return;
        const from = Number(raw.split(':')[1]);
        if (Number.isNaN(from) || from === index) return;
        const [moved] = sections.splice(from, 1);
        sections.splice(index, 0, moved);
        rerender();
      });

      updateVisibility();
      mount.append(box);
    });
    sync();
  };

  const esc = (value) => text(value).replace(/[&<>"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[char]));

  const cardPreview = (card, index, sectionType) => {
    if (card.enabled === false) return '';
    if (sectionType === 'faq') return `<div class="beratung-preview-faq"><button type="button">${esc(card.question || card.title || 'FAQ Frage')}<span>+</span></button><p>${esc(card.answer || card.text || 'Antwort Platzhalter')}</p></div>`;
    if (sectionType === 'steps') return `<article class="beratung-preview-card is-step"><b>${card.auto_number !== false ? index + 1 : esc(card.step_number || index + 1)}</b><h4>${esc(card.title || 'Schritt')}</h4><p>${esc(card.text || 'Platzhaltertext für diesen Schritt.')}</p></article>`;
    if (sectionType === 'technology') return `<article class="beratung-preview-card is-tech"><i>${esc(card.icon || '☁️')}</i><h4>${esc(card.name || card.title || 'Technologie')}</h4><p>${esc(card.text || '')}</p></article>`;
    if (sectionType === 'trust') return `<article class="beratung-preview-card is-trust">${card.metric ? `<strong>${esc(card.metric)}</strong>` : ''}<i>${esc(card.icon || '✓')}</i><h4>${esc(card.title || 'Trust Element')}</h4><p>${esc(card.text || 'Platzhaltertext')}</p></article>`;
    return `<article class="beratung-preview-card"><span>${esc(card.category || card.badge || '')}</span><i>${esc(card.icon || '💡')}</i><h4>${esc(card.title || 'Card Titel')}</h4><p>${esc(card.text || card.extra_text || 'Platzhaltertext für diese Card.')}</p></article>`;
  };

  const sectionPreview = (section) => {
    const cards = Array.isArray(section.cards) ? section.cards : [];
    if (section.type === 'cta') return `<div class="beratung-preview-section is-cta" style="background:${esc(section.background_color || '#1e3a8a')};color:${esc(section.text_color || '#fff')}"><small>${esc(section.eyebrow || 'CTA')}</small><h3>${esc(section.title || 'CTA Titel')}</h3><p>${esc(section.intro || 'Beschreibung für den CTA Bereich.')}</p><div><button>${esc(section.button_1_text || 'Button 1')}</button>${section.button_2_text ? `<button class="ghost">${esc(section.button_2_text)}</button>` : ''}</div></div>`;
    if (section.type === 'divider') return `<div class="beratung-preview-divider"><span>${esc(section.divider_icon || '—')}</span><strong>${esc(section.divider_title || 'Trenner')}</strong><p>${esc(section.divider_subtitle || '')}</p></div>`;
    if (section.type === 'html') return `<div class="beratung-preview-section"><small>Freier HTML Bereich</small><h3>${esc(section.title || 'HTML Bereich')}</h3><p>HTML wird sicher gefiltert. Vorschau zeigt bewusst nur eine neutrale Darstellung.</p></div>`;
    if (section.type === 'comparison') return `<div class="beratung-preview-section"><small>${esc(section.eyebrow || 'Vergleich')}</small><h3>${esc(section.title || 'Vergleich')}</h3>${section.title_band_text ? `<div class="beratung-preview-band">${esc(section.title_band_text)}</div>` : ''}<div class="beratung-preview-grid cols-${Math.min(3, Math.max(1, Number(section.columns || 2)))}">${cards.map((card, index) => cardPreview(card, index, 'card_grid')).join('')}</div></div>`;
    return `<div class="beratung-preview-section"><small>${esc(section.eyebrow || SECTION_TYPES[section.type] || 'Bereich')}</small><h3>${esc(section.title || section.internal_name || 'Bereichstitel')}</h3><p>${esc(section.intro || 'Platzhalter Beschreibung für diesen Bereich.')}</p><div class="beratung-preview-grid cols-${Math.min(4, Math.max(1, Number(section.columns || 3)))}">${cards.slice(0, 8).map((card, index) => cardPreview(card, index, section.type)).join('')}</div>${section.note_text ? `<em>${esc(section.note_text)}</em>` : ''}</div>`;
  };

  const sectionTemplate = (section, index) => `
    <div class="beratung-builder__section-head">
      <strong>☰ Bereich ${index + 1}: ${text(section.internal_name || section.title || SECTION_TYPES[section.type] || 'Ohne Titel')}</strong>
      <div class="beratung-builder__actions"><button type="button" data-action="duplicate">Duplizieren</button><button type="button" data-action="delete">Löschen</button></div>
    </div>
    <div class="beratung-builder__grid">
      <label><input data-field="enabled" type="checkbox"> Modul aktivieren</label>
      <label><input data-field="equal_height" type="checkbox"> Gleiche Card Höhe</label>
      <label class="field-services"><input data-field="categories_enabled" type="checkbox"> Leistungskategorien aktivieren</label>
      <label class="field-faq"><input data-field="faq_allow_multiple" type="checkbox"> Mehrere FAQ Einträge gleichzeitig geöffnet</label>
      <label class="field-faq"><input data-field="faq_schema_enabled" type="checkbox"> FAQ Schema aktivieren</label>
      <label class="field-steps"><input data-field="auto_number" type="checkbox"> Nummerierung automatisch</label>
      <label class="field-steps"><input data-field="connector" type="checkbox"> Verbindungslinie anzeigen</label>
      <label class="field-comparison"><input data-field="title_band_enabled" type="checkbox"> Titelband aktivieren</label>
      <label class="field-comparison"><input data-field="border_enabled" type="checkbox"> Rahmen aktivieren</label>
      <label class="field-comparison"><input data-field="shadow_enabled" type="checkbox"> Schatten aktivieren</label>
      <label class="field-comparison"><input data-field="mobile_stack" type="checkbox"> Mobile Darstellung untereinander</label>
      <label>Bereichstyp <select data-field="type">${optionHtml(SECTION_TYPES, section.type)}</select></label>
      <label>Card Typ <select data-field="card_type">${optionHtml(CARD_TYPES, section.card_type)}</select></label>
      <label>Interner Name <input data-field="internal_name"></label>
      <label>Oberzeile <input data-field="eyebrow"></label>
      <label>Öffentliche Überschrift / Titel <input data-field="title"></label>
      <label>Anker ID <input data-field="anchor_id"></label>
      <label>Beschreibung <textarea data-field="intro" rows="2"></textarea></label>
      <label>Spaltenanzahl <select data-field="columns"><option>1</option><option>2</option><option>3</option><option>4</option></select></label>
      <label>Card Design <select data-field="card_design"><option value="standard">Standard</option><option value="compact">Kompakt</option><option value="bordered">Rahmen</option><option value="filled">Gefüllt</option><option value="minimal">Minimal</option><option value="accent">Akzent</option></select></label>
      <label class="field-steps field-technology field-cta">Darstellung <select data-field="display_style"><option value="cards">Cards</option><option value="horizontal">Horizontal</option><option value="vertical">Vertikal</option><option value="icon_grid">Icon Grid</option><option value="logo_strip">Logo Leiste</option><option value="compact">Kompakt</option><option value="large">Groß</option></select></label>
      <label class="field-comparison">Vergleichsvariante <select data-field="comparison_variant"><option value="two_columns">Zwei Spalten Vergleich</option><option value="three_columns">Drei Spalten Vergleich</option><option value="current_target">Ist Zustand gegen Zielbild</option><option value="problem_solution">Problem gegen Lösung</option><option value="genai_agentic">GenAI gegen Agentic AI</option><option value="copilot_search">Copilot gegen klassische Suche</option><option value="governance">Ohne Governance gegen mit Governance</option></select></label>
      <label class="field-comparison">Titelband Text <input data-field="title_band_text"></label>
      <label class="field-comparison">Titelband Hintergrund <input data-field="title_band_background_color" type="color"></label>
      <label class="field-comparison">Titelband Textfarbe <input data-field="title_band_text_color" type="color"></label>
      <label>Hintergrundfarbe <input data-field="background_color" type="color"></label>
      <label>Textfarbe <input data-field="text_color" type="color"></label>
      <label>Hintergrundbild <input data-field="background_image_url"></label>
      <label>Innenabstand oben <input data-field="padding_top" type="number" min="0" max="180"></label>
      <label>Innenabstand unten <input data-field="padding_bottom" type="number" min="0" max="180"></label>
      <label>Maximale Inhaltsbreite <input data-field="max_width" type="number" min="720" max="1800"></label>
      <label>Textausrichtung <select data-field="text_align"><option value="left">links</option><option value="center">zentriert</option><option value="right">rechts</option></select></label>
      <label class="field-services field-trust">Optionaler Hinweistext <textarea data-field="note_text" rows="2"></textarea></label>
      <label class="field-faq">FAQ Icon Stil <select data-field="faq_icon_style"><option value="plus">Plus</option><option value="chevron">Chevron</option><option value="question">Fragezeichen</option></select></label>
      <label class="field-faq">Standard Öffnung <select data-field="faq_open_behavior"><option value="none">Kein Eintrag offen</option><option value="first">Erster Eintrag offen</option><option value="custom">Individuell pro Eintrag</option></select></label>
      <label class="field-faq">Frage Hintergrund <input data-field="faq_question_background_color" type="color"></label>
      <label class="field-faq">Frage Textfarbe <input data-field="faq_question_text_color" type="color"></label>
      <label class="field-faq">Antwort Hintergrund <input data-field="faq_answer_background_color" type="color"></label>
      <label class="field-cta field-services field-steps field-trust">Button 1 Text <input data-field="button_1_text"></label>
      <label class="field-cta field-services field-steps field-trust">Button 1 Zieltyp <select data-field="button_1_target_type">${optionHtml(TARGET_TYPES, section.button_1_target_type || 'internal')}</select></label>
      <label class="field-cta field-services field-steps field-trust">Button 1 Ziel <input data-field="button_1_target"></label>
      <label class="field-cta field-services field-steps field-trust">Button 1 Stil <select data-field="button_1_style"><option value="primary">Primär</option><option value="secondary">Sekundär</option><option value="ghost">Ghost</option><option value="link">Link</option></select></label>
      <label class="field-cta">Button 2 Text <input data-field="button_2_text"></label>
      <label class="field-cta">Button 2 Zieltyp <select data-field="button_2_target_type">${optionHtml(TARGET_TYPES, section.button_2_target_type || 'internal')}</select></label>
      <label class="field-cta">Button 2 Ziel <input data-field="button_2_target"></label>
      <label class="field-cta">Button 2 Stil <select data-field="button_2_style"><option value="primary">Primär</option><option value="secondary">Sekundär</option><option value="ghost">Ghost</option><option value="link">Link</option></select></label>
      <label class="field-divider">Trenner Typ <select data-field="divider_type"><option value="line">Einfacher Strich</option><option value="title_band">Titelband</option><option value="icon">Icon Trenner</option><option value="color_block">Farbblock</option><option value="wave">Wellenform</option><option value="quote">Zitatband</option><option value="cta">CTA Trenner</option><option value="numbered">Nummerierter Abschnittstrenner</option><option value="spacer">Abstand ohne sichtbaren Trenner</option></select></label>
      <label class="field-divider">Trenner Titel <input data-field="divider_title"></label>
      <label class="field-divider">Trenner Untertitel <textarea data-field="divider_subtitle" rows="2"></textarea></label>
      <label class="field-divider">Trenner Icon <input data-field="divider_icon"></label>
      <label class="field-divider">Linienfarbe <input data-field="divider_line_color" type="color"></label>
      <label class="field-divider">Breite in % <input data-field="divider_width" type="number" min="20" max="100"></label>
      <label class="field-divider">Mobile Verhalten <select data-field="divider_mobile_behavior"><option value="stack">Stapeln</option><option value="compact">Kompakt</option><option value="hide_visual">Visuelles Element ausblenden</option></select></label>
      <label class="field-html is-html">Freier HTML Bereich <textarea data-field="html" rows="6"></textarea><small>Nur Admin-Benutzer. Skripte werden serverseitig gefiltert.</small></label>
    </div>
    <div class="beratung-preview-label">Live-nahe Vorschau</div>
    <div class="beratung-live-preview">${sectionPreview(section)}</div>
    <div class="beratung-builder__cards"></div>
    <button type="button" data-action="add-card">Eintrag / Card hinzufügen</button>`;

  const updateSectionVisibility = (box, section) => {
    const groups = ['services', 'steps', 'comparison', 'faq', 'trust', 'technology', 'cta', 'divider', 'html'];
    groups.forEach((group) => $$(`.field-${group}`, box).forEach((field) => { field.hidden = section.type !== group; }));
    $('.beratung-builder__cards', box).hidden = ['cta', 'divider', 'html', 'contact'].includes(section.type);
    $('[data-action="add-card"]', box).hidden = ['cta', 'divider', 'html', 'contact'].includes(section.type);
  };

  const renderCard = (card, section, cardIndex, sync, rerender) => {
    card.card_type ??= section.card_type || 'text';
    if (section.type === 'faq') card.card_type = 'text';
    const box = document.createElement('div');
    box.className = `beratung-builder__card type-${card.card_type}`;
    box.draggable = true;
    box.innerHTML = `
      <div class="beratung-builder__section-head">
        <strong>☰ ${section.type === 'faq' ? 'FAQ' : 'Card'} ${cardIndex + 1}: ${CARD_TYPES[card.card_type] || 'Card'}</strong>
        <div class="beratung-builder__actions"><button type="button" data-card-action="duplicate">Duplizieren</button><button type="button" data-card-action="delete">Löschen</button></div>
      </div>
      <div class="beratung-builder__grid">
        <label><input data-card-field="enabled" type="checkbox"> Eintrag aktivieren</label>
        <label><input data-card-field="featured" type="checkbox"> Hervorgehoben</label>
        <label><input data-card-field="border_enabled" type="checkbox"> Rahmen aktivieren</label>
        <label><input data-card-field="shadow_enabled" type="checkbox"> Schatten aktivieren</label>
        <label><input data-card-field="hover_enabled" type="checkbox"> Hover Effekt</label>
        <label class="card-general">Card Typ <select data-card-field="card_type">${optionHtml(CARD_TYPES, card.card_type)}</select></label>
        <label class="card-general card-service card-tech card-trust card-comparison">Icon <input data-card-field="icon"></label>
        <label class="card-service">Kategorie <input data-card-field="category"></label>
        <label class="card-general card-service card-tech card-trust card-step">Titel / Name <input data-card-field="title"></label>
        <label class="card-tech">Technologie Name <input data-card-field="name"></label>
        <label class="card-general card-service card-tech card-trust card-step">Text / Kurzbeschreibung <textarea data-card-field="text" rows="2"></textarea></label>
        <label class="card-service card-comparison">Zusatztext / Typischer Einsatz <textarea data-card-field="extra_text" rows="2"></textarea></label>
        <label class="card-trust">Kennzahl optional <input data-card-field="metric"></label>
        <label class="card-tech">Logo URL <input data-card-field="logo_url"></label>
        <label class="card-faq">Frage <input data-card-field="question"></label>
        <label class="card-faq">Antwort <textarea data-card-field="answer" rows="4"></textarea></label>
        <label class="card-faq"><input data-card-field="default_open" type="checkbox"> Standardmäßig geöffnet</label>
        <label class="field-offer">Icon Hintergrund <input data-card-field="icon_background" type="color"></label>
        <label class="field-offer">Icon Farbe <input data-card-field="icon_color" type="color"></label>
        <label class="field-image">Bild <input data-card-field="image_url"></label>
        <label class="field-image">Bild Alt Text <input data-card-field="image_alt"></label>
        <label class="field-image">Bildhöhe <input data-card-field="image_height" type="number" min="120" max="520"></label>
        <label class="field-image">Bild Zuschnitt <select data-card-field="image_fit"><option value="cover">Cover</option><option value="contain">Contain</option></select></label>
        <label class="field-image field-text">Badge / Label <input data-card-field="label"></label>
        <label class="field-image">Label Position <select data-card-field="label_position"><option value="left">links</option><option value="center">mittig</option><option value="right">rechts</option></select></label>
        <label class="field-image">Label Stil <select data-card-field="label_style"><option value="border">Rahmen</option><option value="filled">gefüllt</option><option value="transparent">transparent</option></select></label>
        <label class="field-text">Badge <input data-card-field="badge"></label>
        <label class="field-text field-image field-offer field-step field-problem card-service card-trust">Button Text <input data-card-field="button_label"></label>
        <label class="field-text field-image field-offer field-step field-problem card-service card-trust">Button Ziel <input data-card-field="button_url"></label>
        <label>Hintergrundfarbe <input data-card-field="background_color" type="color"></label>
        <label>Textfarbe <input data-card-field="text_color" type="color"></label>
        <label>Rahmenfarbe <input data-card-field="border_color" type="color"></label>
        <label class="field-offer"><input data-card-field="tab_enabled" type="checkbox"> Reiter aktivieren</label>
        <label class="field-offer">Card Größe <select data-card-field="card_size"><option value="small">klein</option><option value="medium">mittel</option><option value="large">groß</option></select></label>
        <label class="field-step card-step">Schritt Nummer <input data-card-field="step_number"></label>
        <label class="field-step card-step"><input data-card-field="auto_number" type="checkbox"> Automatische Nummerierung</label>
        <label class="field-step card-step"><input data-card-field="connector" type="checkbox"> Verbindungslinie anzeigen</label>
        <label class="field-problem card-comparison">Spalte 1 / Problem Titel <input data-card-field="problem_title"></label>
        <label class="field-problem card-comparison">Spalte 1 / Problem Text <textarea data-card-field="problem_text" rows="2"></textarea></label>
        <label class="field-problem card-comparison">Spalte 2 / Lösung Titel <input data-card-field="solution_title"></label>
        <label class="field-problem card-comparison">Spalte 2 / Lösung Text <textarea data-card-field="solution_text" rows="2"></textarea></label>
        <label class="field-problem">Risiko Level <select data-card-field="risk_level"><option value="low">niedrig</option><option value="medium">mittel</option><option value="high">hoch</option></select></label>
        <label class="field-problem">Empfohlene Maßnahme <textarea data-card-field="recommendation" rows="2"></textarea></label>
      </div>`;

    const updateVisibility = () => {
      box.dataset.cardType = card.card_type;
      ['field-text', 'field-image', 'field-offer', 'field-step', 'field-problem', 'card-service', 'card-tech', 'card-trust', 'card-faq', 'card-comparison', 'card-step'].forEach((cls) => $$(`.${cls}`, box).forEach((field) => { field.hidden = true; }));
      $$('.card-general', box).forEach((field) => { field.hidden = false; });
      if (section.type === 'services') $$('.card-service', box).forEach((field) => { field.hidden = false; });
      if (section.type === 'technology') $$('.card-tech', box).forEach((field) => { field.hidden = false; });
      if (section.type === 'trust') $$('.card-trust', box).forEach((field) => { field.hidden = false; });
      if (section.type === 'faq') $$('.card-faq', box).forEach((field) => { field.hidden = false; });
      if (section.type === 'comparison') $$('.card-comparison', box).forEach((field) => { field.hidden = false; });
      if (section.type === 'steps') $$('.card-step', box).forEach((field) => { field.hidden = false; });
      const map = { text: '.field-text', image_label: '.field-image', offer_icon_tab: '.field-offer', step: '.field-step', problem_solution: '.field-problem' };
      if (!['services', 'technology', 'trust', 'faq', 'comparison', 'steps'].includes(section.type)) $$(map[card.card_type] || '.field-text', box).forEach((field) => { field.hidden = false; });
    };

    $$('[data-card-field]', box).forEach((input) => {
      const field = input.dataset.cardField;
      if (input.type === 'checkbox') input.checked = Boolean(card[field]);
      else input.value = text(card[field]);
      input.addEventListener('input', () => {
        if (input.type === 'checkbox') card[field] = input.checked;
        else if (input.type === 'number') card[field] = Number(input.value || 0);
        else card[field] = input.value;
        updateVisibility();
        sync();
      });
      input.addEventListener('change', () => input.dispatchEvent(new Event('input')));
    });
    enhanceMediaFields(box);

    $$('[data-card-action]', box).forEach((button) => button.addEventListener('click', () => {
      if (button.dataset.cardAction === 'delete') section.cards.splice(cardIndex, 1);
      if (button.dataset.cardAction === 'duplicate') section.cards.splice(cardIndex + 1, 0, JSON.parse(JSON.stringify(card)));
      rerender();
    }));

    box.addEventListener('dragstart', (event) => event.dataTransfer?.setData('text/plain', `card:${cardIndex}`));
    box.addEventListener('dragover', (event) => event.preventDefault());
    box.addEventListener('drop', (event) => {
      event.preventDefault();
      const raw = event.dataTransfer?.getData('text/plain') || '';
      if (!raw.startsWith('card:')) return;
      const from = Number(raw.split(':')[1]);
      if (Number.isNaN(from) || from === cardIndex) return;
      const [moved] = section.cards.splice(from, 1);
      section.cards.splice(cardIndex, 0, moved);
      rerender();
    });

    updateVisibility();
    return box;
  };

  const renderDesignPresetSelector = () => {
    const select = $('#beratung-design-preset');
    const target = $('#design_json');
    if (!select || !target) return;
    select.addEventListener('change', () => {
      const option = select.selectedOptions?.[0];
      const raw = option?.dataset?.design || '';
      if (!raw) return;
      try {
        const design = JSON.parse(raw);
        if (design && typeof design === 'object') {
          target.value = JSON.stringify(design, null, 2);
          const customToggle = $('input[name="custom_design_enabled"]');
          const globalToggle = $('input[name="use_global_settings"]');
          if (customToggle) customToggle.checked = true;
          if (globalToggle) globalToggle.checked = false;
        }
      } catch (_) {
        // Preset-Auswahl darf den Editor nie blockieren.
      }
    });
  };

  renderHeroBuilder();
  renderSectionBuilder();
  renderDesignPresetSelector();
})();
