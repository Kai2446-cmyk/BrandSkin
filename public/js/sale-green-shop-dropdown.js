/* PATCH SALE GREEN + SHOP DROPDOWN ONLY */
(function () {
  function cleanText(text) {
    return (text || '').replace(/\s+/g, ' ').trim();
  }

  function lowerText(el) {
    return cleanText(el.textContent).toLowerCase();
  }

  function markSaleSection() {
    const nodes = Array.from(document.querySelectorAll('section, .section, .home-section, div'));

    let best = null;
    let bestScore = 0;

    nodes.forEach(function (el) {
      const text = lowerText(el);
      const productCount = el.querySelectorAll('[data-cart-add], .add-to-bag, .add-to-cart, .product-card, [class*="product"]').length;

      let score = 0;
      if (text.includes('50% off')) score += 6;
      if (text.includes('see all')) score += 2;
      if (text.includes('add to bag')) score += 4;
      if (text.includes('-50%')) score += 3;
      if (productCount >= 4) score += 4;
      if (productCount >= 8) score += 2;

      if (score > bestScore && productCount >= 3) {
        bestScore = score;
        best = el;
      }
    });

    if (best) {
      best.classList.add('glow-force-sale-section');

      /* Tambah marker khusus untuk heading 50% OFF */
      Array.from(best.querySelectorAll('*')).forEach(function (child) {
        const text = lowerText(child);
        if (text.includes('50% off') && text.length <= 30) {
          child.classList.add('glow-sale-title');
          child.setAttribute('data-glow-sale-title', '1');
        }

        if (/^-\d+%$/.test(cleanText(child.textContent))) {
          child.classList.add('glow-discount-badge');
        }
      });
    }
  }

  function findNavLinks() {
    const links = Array.from(document.querySelectorAll('nav a, header a, .navbar a, .site-nav a'));
    const findByText = function (label) {
      return links.find(function (a) {
        return cleanText(a.textContent).toLowerCase() === label.toLowerCase();
      });
    };

    return {
      makeup: findByText('makeup'),
      skincare: findByText('skincare'),
      sale: findByText('sale')
    };
  }

  function createShopDropdown() {
    if (document.querySelector('[data-glow-shop-dropdown]')) return;

    const old = findNavLinks();
    if (!old.makeup || !old.skincare || !old.sale) return;

    const parent = old.makeup.parentElement || old.makeup.closest('li')?.parentElement || old.makeup.closest('nav');
    const insertBefore = old.makeup;

    const dropdown = document.createElement('div');
    dropdown.className = 'glow-shop-dropdown';
    dropdown.setAttribute('data-glow-shop-dropdown', '1');

    const currentPath = window.location.pathname.replace(/\/+$/, '') || '/';

    const makeLink = function (label, href) {
      const a = document.createElement('a');
      a.href = href;
      a.textContent = label;
      if (currentPath === href) a.classList.add('active');
      return a;
    };

    dropdown.innerHTML = '<button type="button" class="glow-shop-toggle">SHOP</button><div class="glow-shop-menu"></div>';
    const menu = dropdown.querySelector('.glow-shop-menu');

    menu.appendChild(makeLink('Makeup', old.makeup.getAttribute('href') || '/makeup'));
    menu.appendChild(makeLink('Skincare', old.skincare.getAttribute('href') || '/skincare'));
    menu.appendChild(makeLink('Sale', old.sale.getAttribute('href') || '/sale'));

    parent.insertBefore(dropdown, insertBefore);

    old.makeup.classList.add('glow-nav-hidden-by-shop');
    old.skincare.classList.add('glow-nav-hidden-by-shop');
    old.sale.classList.add('glow-nav-hidden-by-shop');

    /* Kalau nav pakai li wrapper, sembunyikan wrapper-nya juga */
    [old.makeup, old.skincare, old.sale].forEach(function (a) {
      const li = a.closest('li');
      if (li && li.parentElement === parent) {
        li.classList.add('glow-nav-hidden-by-shop');
      }
    });

    const toggle = dropdown.querySelector('.glow-shop-toggle');
    toggle.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      dropdown.classList.toggle('is-open');
    });

    document.addEventListener('click', function () {
      dropdown.classList.remove('is-open');
    });
  }

  function initPatch() {
    markSaleSection();
    createShopDropdown();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPatch);
  } else {
    initPatch();
  }

  window.addEventListener('load', initPatch);
})();
