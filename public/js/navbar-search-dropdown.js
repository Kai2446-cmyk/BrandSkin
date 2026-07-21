(() => {
  const panel = document.querySelector('[data-navbar-search-panel]');
  const input = document.querySelector('[data-navbar-search-input]');
  const results = document.querySelector('[data-navbar-search-results]');
  const empty = document.querySelector('[data-navbar-search-empty]');
  const openButtons = [...document.querySelectorAll('[data-search-open]')];
  const closeButton = document.querySelector('[data-navbar-search-close]');
  if (!panel || !input || !results || !openButtons.length) return;

  const items = [...results.querySelectorAll('[data-navbar-search-item]')];
  const MAX_VISIBLE = 6;
  const normalize = value => (value || '')
    .toLocaleLowerCase('id-ID')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim();

  const setTop = () => {
    const header = document.querySelector('.site-header');
    if (!header) return;
    const rect = header.getBoundingClientRect();
    document.documentElement.style.setProperty('--navbar-search-top', `${Math.max(0, rect.bottom)}px`);
  };

  const render = () => {
    const keyword = normalize(input.value);
    let visible = 0;

    items.forEach(item => {
      const matched = keyword !== '' && normalize(item.dataset.searchText).includes(keyword);
      const shouldShow = matched && visible < MAX_VISIBLE;
      item.hidden = !shouldShow;
      if (shouldShow) visible += 1;
    });

    results.hidden = keyword === '';
    if (empty) empty.hidden = keyword === '' || visible > 0;
  };

  const open = () => {
    setTop();
    panel.hidden = false;
    requestAnimationFrame(() => {
      panel.classList.add('is-open');
      document.body.classList.add('navbar-search-open');
      input.focus();
    });
    input.value = '';
    render();
  };

  const close = () => {
    panel.classList.remove('is-open');
    document.body.classList.remove('navbar-search-open');
    input.value = '';
    results.hidden = true;
    window.setTimeout(() => { panel.hidden = true; }, 260);
  };

  openButtons.forEach(button => button.addEventListener('click', event => {
    event.preventDefault();
    panel.hidden ? open() : close();
  }));
  closeButton?.addEventListener('click', close);
  input.addEventListener('input', render);
  window.addEventListener('resize', () => { if (!panel.hidden) setTop(); });
  document.addEventListener('keydown', event => { if (event.key === 'Escape' && !panel.hidden) close(); });
  document.addEventListener('click', event => {
    if (!panel.hidden && !panel.contains(event.target) && !event.target.closest('[data-search-open]')) close();
  });
})();
