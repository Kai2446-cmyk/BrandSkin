(() => {
  'use strict';

  const header = document.querySelector('.site-header');
  const hero = document.querySelector('.hero-section');
  if (!header || !hero) return;

  document.body.classList.add('has-landing-hero');

  const updateHeader = () => {
    const threshold = Math.max(24, Math.min(110, hero.offsetHeight * 0.08));
    header.classList.toggle('is-scrolled', window.scrollY > threshold);
  };

  updateHeader();
  window.addEventListener('scroll', updateHeader, { passive: true });
  window.addEventListener('resize', updateHeader, { passive: true });
})();
