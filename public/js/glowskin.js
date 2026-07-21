document.addEventListener('DOMContentLoaded', () => {

  // Profile dropdown
  document.querySelectorAll('[data-profile-toggle]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const menu = btn.closest('.profile-menu')?.querySelector('[data-profile-dropdown]');
      if (menu) menu.classList.toggle('hidden');
      document.querySelector('[data-search-panel]')?.classList.add('hidden');
      document.body.classList.remove('search-open');
    });
  });

  document.addEventListener('click', () => {
    document.querySelectorAll('[data-profile-dropdown]').forEach((menu) => menu.classList.add('hidden'));
  });

  // Navbar live product search
  const searchPanel = document.querySelector('[data-search-panel]');
  const searchInput = document.querySelector('[data-live-search-input]');
  const searchResults = document.querySelector('[data-live-search-results]');
  const searchOpenButtons = document.querySelectorAll('[data-search-open]');
  const searchCloseButtons = document.querySelectorAll('[data-search-close]');
  let searchTimer = null;
  let searchRequest = null;

  const escapeHtml = (value = '') => String(value).replace(/[&<>'"]/g, (char) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
  }[char]));

  const productImageUrl = (path) => {
    if (!path) return '/assets/images/no_image.png';
    if (/^https?:\/\//i.test(path) || path.startsWith('/')) return path;
    return '/' + path.replace(/^\/+/, '');
  };

  function setSearchEmpty(message) {
    if (!searchResults) return;
    searchResults.innerHTML = `<div class="live-search-empty">${escapeHtml(message)}</div>`;
  }

  function renderProducts(items) {
    if (!items || !items.length) {
      return '<div class="live-search-empty">Produk yang sesuai belum ditemukan.</div>';
    }

    return items.map((item) => `
      <a class="live-search-item" href="${escapeHtml(item.url)}">
        <img class="live-search-thumb" src="${escapeHtml(productImageUrl(item.image))}" alt="${escapeHtml(item.title)}">
        <div class="live-search-info">
          <div class="live-search-type">${escapeHtml(item.category || 'Produk')}</div>
          <p class="live-search-title">${escapeHtml(item.title)}</p>
          <div class="live-search-meta">
            <span>${escapeHtml(item.sold || 0)} terjual</span>
            <span>${escapeHtml(item.rating || 'Belum ada rating')}</span>
          </div>
        </div>
        <strong class="live-search-price">${escapeHtml(item.price || '')}</strong>
      </a>
    `).join('');
  }

  function closeSearch() {
    searchPanel?.classList.add('hidden');
    searchPanel?.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('search-open');
  }

  function openSearch() {
    searchPanel?.classList.remove('hidden');
    searchPanel?.setAttribute('aria-hidden', 'false');
    document.body.classList.add('search-open');
    document.querySelectorAll('[data-profile-dropdown]').forEach((menu) => menu.classList.add('hidden'));
    setTimeout(() => searchInput?.focus(), 60);
  }

  searchOpenButtons.forEach((btn) => btn.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    openSearch();
  }));

  searchCloseButtons.forEach((btn) => btn.addEventListener('click', closeSearch));

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeSearch();
  });

  if (searchInput) {
    searchInput.addEventListener('input', () => {
      const q = searchInput.value.trim();
      clearTimeout(searchTimer);
      searchRequest?.abort();

      if (!q) {
        setSearchEmpty('Mulai ketik untuk mencari produk.');
        return;
      }

      searchResults.innerHTML = '<div class="global-product-search-loading">Mencari produk terbaik...</div>';
      searchTimer = setTimeout(async () => {
        searchRequest = new AbortController();
        try {
          const response = await fetch(`/search/live?q=${encodeURIComponent(q)}`, {
            headers: { Accept: 'application/json' },
            signal: searchRequest.signal
          });
          if (!response.ok) throw new Error('Search request failed');
          const data = await response.json();
          searchResults.innerHTML = renderProducts(data.products || []);
        } catch (error) {
          if (error.name !== 'AbortError') setSearchEmpty('Pencarian belum bisa dimuat. Silakan coba lagi.');
        }
      }, 180);
    });
  }

  const slides = [...document.querySelectorAll('.gs-slide')];
  let current = 0;

  function setSlide(i) {
    if (!slides.length) return;
    slides[current]?.classList.remove('active');
    current = (i + slides.length) % slides.length;
    slides[current]?.classList.add('active');
  }

  if (slides.length > 1) {
    setInterval(() => setSlide(current + 1), 5200);
  }

  document.querySelectorAll('[data-products-tab]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const target = btn.dataset.productsTab;
      const activePanel = document.querySelector('[data-products-panel].active');
      const nextPanel = document.querySelector(`[data-products-panel="${target}"]`);
      if (!nextPanel || nextPanel === activePanel) return;

      document.querySelectorAll('[data-products-tab]').forEach((tab) => {
        const isActive = tab.dataset.productsTab === target;
        tab.classList.toggle('tab-underline-active', isActive);
        tab.style.color = isActive ? '#111' : '#888';
      });

      document.querySelectorAll('[data-featured-label]').forEach((el) => {
        el.classList.add('is-changing');
        setTimeout(() => {
          el.textContent = target === 'new' ? 'NEW\nARRIVAL' : 'BEST\nSELLER';
          el.classList.remove('is-changing');
        }, 170);
      });

      if (activePanel) activePanel.classList.remove('active');
      nextPanel.classList.add('active');
      nextPanel.querySelectorAll('.product-card-hover').forEach((card) => {
        card.style.animation = 'none';
        card.offsetHeight;
        card.style.animation = '';
      });
    });
  });

  document.querySelectorAll('[data-scroll-left]').forEach((btn) => {
    btn.addEventListener('click', () => document.querySelector(btn.dataset.scrollLeft)?.scrollBy({ left: -280, behavior: 'smooth' }));
  });

  document.querySelectorAll('[data-scroll-right]').forEach((btn) => {
    btn.addEventListener('click', () => document.querySelector(btn.dataset.scrollRight)?.scrollBy({ left: 280, behavior: 'smooth' }));
  });

  document.querySelectorAll('[data-skin]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.skin;
      document.querySelectorAll('[data-skin]').forEach((b) => b.classList.toggle('active', b.dataset.skin === id));
      document.querySelectorAll('[data-skin-panel]').forEach((p) => p.classList.toggle('active', p.dataset.skinPanel === id));
    });
  });

  document.querySelectorAll('[data-filter]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const cat = btn.dataset.filter;
      document.querySelectorAll('[data-filter]').forEach((b) => b.classList.toggle('green-gradient-btn', b.dataset.filter === cat));
      document.querySelectorAll('.article-card').forEach((card) => card.classList.toggle('hide', cat !== 'All' && card.dataset.category !== cat));
    });
  });

  document.querySelectorAll('[data-qty]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = document.querySelector('#quantity');
      if (!input) return;
      const value = parseInt(input.value || '1', 10);
      input.value = Math.max(1, value + parseInt(btn.dataset.qty, 10));
    });
  });

  const revealSections = [...document.querySelectorAll('main > section:not(.hero-section)')];
  revealSections.forEach((section) => section.classList.add('reveal-section'));

  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -70px 0px' });

    revealSections.forEach((section) => observer.observe(section));
  } else {
    revealSections.forEach((section) => section.classList.add('is-visible'));
  }
});
