(() => {
  const root = document.querySelector('.cms-beratung');
  if (!root) return;

  root.addEventListener('click', (event) => {
    try {
      const link = event.target instanceof Element ? event.target.closest('a[href^="#"]') : null;
      if (!link) return;
      const href = link.getAttribute('href') || '';
      if (href.length < 2) return;
      const target = document.querySelector(href);
      if (!target) return;
      event.preventDefault();
      target.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });

      if (window.cmsTracking && link.classList.contains('cms-beratung-card__link')) {
        window.cmsTracking.track?.('cms_beratung_button_click', { href });
      }
    } catch (_) {
      // Frontend-Enhancement darf die Seite nie blockieren.
    }
  });

  root.addEventListener('click', (event) => {
    try {
      const button = event.target instanceof Element ? event.target.closest('.cms-beratung-faq button[aria-controls]') : null;
      if (!button) return;
      const faq = button.closest('.cms-beratung-faq');
      const panel = document.getElementById(button.getAttribute('aria-controls'));
      if (!faq || !panel) return;
      const allowMultiple = faq.dataset.allowMultiple === '1';
      const willOpen = button.getAttribute('aria-expanded') !== 'true';
      if (!allowMultiple) {
        faq.querySelectorAll('button[aria-controls]').forEach((other) => {
          const otherPanel = document.getElementById(other.getAttribute('aria-controls'));
          other.setAttribute('aria-expanded', 'false');
          other.closest('.cms-beratung-faq__item')?.classList.remove('is-open');
          if (otherPanel) otherPanel.hidden = true;
        });
      }
      button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      button.closest('.cms-beratung-faq__item')?.classList.toggle('is-open', willOpen);
      panel.hidden = !willOpen;
    } catch (_) {
      // FAQ bleibt als HTML lesbar, auch wenn Enhancement fehlschlägt.
    }
  });
})();
