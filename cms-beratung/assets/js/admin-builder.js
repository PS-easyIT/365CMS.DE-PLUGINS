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
    faq: 'FAQ Accordion',
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
    faq: 'FAQ Modul',
    trust: 'Trust Bereich',
    technology: 'Technologie Bereich',
    cta: 'CTA Band',
    divider: 'Trenner',
    html: 'Freier HTML Bereich'
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

  const renderSectionBuilder = () => {
    const mount = $('#beratung-builder');
    const target = $('#' + (mount?.dataset?.target || ''));
    if (!mount || !target) return;

    let sections = parseJson(target, []);
    if (!Array.isArray(sections)) sections = [];

    const sync = () => syncJson(target, sections);
    const rerender = () => { sync(); renderSectionBuilder(); };

    mount.className = 'beratung-builder';
    mount.innerHTML = '';
    const toolbar = document.createElement('div');
    toolbar.className = 'beratung-builder__toolbar';
    toolbar.innerHTML = `<div><button type="button" class="beratung-btn" data-add-basic>Bereich hinzufügen</button></div><label>Spezialmodul hinzufügen <select data-add-module><option value="">Bitte wählen</option>${optionHtml(MODULE_PRESETS, '')}</select></label><span>Bereiche und Cards können per Drag and Drop sortiert werden.</span>`;
    $('[data-add-basic]', toolbar).addEventListener('click', () => { sections.push(newSection(sections.length)); rerender(); });
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

      const updateVisibility = () => updateSectionVisibility(box, section);
      bindInputs(box, section, sync, (field) => {
        if (field === 'type') {
          if (section.type === 'steps') section.card_type = 'step';
          if (section.type === 'comparison') section.card_type = 'problem_solution';
          if (section.type === 'technology') section.display_style ||= 'icon_grid';
        }
        updateVisibility();
      });

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

  renderHeroBuilder();
  renderSectionBuilder();
})();
