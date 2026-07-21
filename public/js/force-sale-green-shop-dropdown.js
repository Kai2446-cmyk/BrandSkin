/* FORCE INSTALL PATCH: SALE GREEN + SHOP DROPDOWN */
(function () {
  if (window.__GLOWSKIN_FORCE_SALE_SHOP_PATCH__) return;
  window.__GLOWSKIN_FORCE_SALE_SHOP_PATCH__ = true;

  function cleanText(text) {
    return (text || '').replace(/\s+/g, ' ').trim();
  }

  function lowerText(el) {
    return cleanText(el.textContent).toLowerCase();
  }

  function forceStyle(el, prop, value) {
    if (!el) return;
    try { el.style.setProperty(prop, value, 'important'); } catch(e) {}
  }

  function findSaleSection() {
    const all = Array.from(document.querySelectorAll('section, main > div, body > div, .section, .home-section, div'));
    let best = null;
    let bestScore = 0;

    all.forEach(function (el) {
      const text = lowerText(el);
      const productCount = el.querySelectorAll('[data-cart-add], .add-to-bag, .add-to-cart, .product-card, [class*="product"]').length;
      let score = 0;

      if (text.includes('50% off')) score += 20;
      if (text.includes('see all')) score += 5;
      if (text.includes('add to bag')) score += 8;
      if (text.includes('-50%')) score += 8;
      if (text.includes('-0%')) score += 4;
      if (productCount >= 3) score += 8;
      if (productCount >= 6) score += 8;

      /* Hindari memilih body/html terlalu besar */
      const rect = el.getBoundingClientRect();
      if (rect.height > 0 && rect.height < window.innerHeight * 2.2 && score > bestScore) {
        bestScore = score;
        best = el;
      }
    });

    /* fallback: cari teks 50% OFF lalu naik parent sampai ketemu banyak produk */
    if (!best) {
      const title = Array.from(document.querySelectorAll('body *')).find(function (el) {
        return lowerText(el).includes('50% off') && cleanText(el.textContent).length < 80;
      });

      if (title) {
        let parent = title;
        for (let i = 0; i < 9 && parent.parentElement; i++) {
          parent = parent.parentElement;
          const productCount = parent.querySelectorAll('[data-cart-add], .add-to-bag, .add-to-cart, .product-card, [class*="product"]').length;
          if (productCount >= 3) {
            best = parent;
            break;
          }
        }
      }
    }

    return best;
  }

  function forceGreenSale() {
    const section = findSaleSection();
    if (!section) return;

    section.classList.add('glow-sale-green-forced');
    forceStyle(section, 'background',
      'radial-gradient(circle at 14% 8%, rgba(101,162,64,.34) 0%, transparent 30%), radial-gradient(circle at 90% 18%, rgba(61,99,40,.45) 0%, transparent 34%), linear-gradient(135deg, #14230D 0%, #243D17 38%, #3D6328 72%, #65A240 145%)'
    );

    /* paksa background coklat/gold di children penting */
    Array.from(section.querySelectorAll('*')).forEach(function (el) {
      const text = lowerText(el);
      const shortText = cleanText(el.textContent);

      if (text.includes('50% off') && shortText.length <= 80) {
        el.classList.add('glow-sale-heading-target');
        el.setAttribute('data-glow-sale-heading', '1');
        forceStyle(el, 'background', 'linear-gradient(135deg, rgba(61,99,40,.92), rgba(101,162,64,.72))');
        forceStyle(el, 'color', '#F4FFE8');
        forceStyle(el, 'border-color', 'rgba(101,162,64,.62)');
      }

      if (/^-\d+%$/.test(shortText)) {
        el.classList.add('glow-sale-percent-target');
        forceStyle(el, 'background', 'linear-gradient(135deg, #65A240, #A4E077)');
        forceStyle(el, 'color', '#10220C');
      }

      if (
        el.matches('article,.product-card,.sale-product-card,.catalogue-product-card,[class*="product-card"],[class*="card"]') &&
        lowerText(el).includes('add to bag')
      ) {
        forceStyle(el, 'background', 'linear-gradient(180deg, rgba(33,55,22,.98), rgba(18,33,12,.99))');
        forceStyle(el, 'border-color', 'rgba(101,162,64,.42)');
      }

      if (el.matches('button,.add-to-bag,.add-to-cart,[data-cart-add]')) {
        forceStyle(el, 'background', 'rgba(61,99,40,.86)');
        forceStyle(el, 'color', '#DFF4D5');
        forceStyle(el, 'border-color', 'rgba(101,162,64,.62)');
      }
    });
  }

  function findNavLinks() {
    const links = Array.from(document.querySelectorAll('nav a, header a, .navbar a, .site-nav a, .main-nav a, .nav-menu a'));
    function byLabel(label) {
      return links.find(function (a) {
        return cleanText(a.textContent).toLowerCase() === label.toLowerCase();
      });
    }
    return {
      makeup: byLabel('makeup'),
      skincare: byLabel('skincare'),
      sale: byLabel('sale')
    };
  }

  function createShopDropdown() {
    if (document.querySelector('[data-glow-shop-dropdown]')) return;

    const old = findNavLinks();
    if (!old.makeup || !old.skincare || !old.sale) return;

    const parent =
      old.makeup.parentElement?.tagName === 'LI'
        ? old.makeup.parentElement.parentElement
        : old.makeup.parentElement;

    const anchorForInsert = old.makeup.parentElement?.tagName === 'LI' ? old.makeup.parentElement : old.makeup;

    if (!parent) return;

    const dropdown = document.createElement(old.makeup.parentElement?.tagName === 'LI' ? 'li' : 'div');
    dropdown.className = 'glow-shop-dropdown';
    dropdown.setAttribute('data-glow-shop-dropdown', '1');

    const currentPath = window.location.pathname.replace(/\/+$/, '') || '/';

    function hrefOf(a, fallback) {
      return a.getAttribute('href') || fallback;
    }

    function activeClass(href) {
      return currentPath === href ? ' active' : '';
    }

    dropdown.innerHTML =
      '<button type="button" class="glow-shop-toggle">SHOP</button>' +
      '<div class="glow-shop-menu">' +
      '<a class="' + activeClass('/makeup') + '" href="' + hrefOf(old.makeup, '/makeup') + '">Makeup</a>' +
      '<a class="' + activeClass('/skincare') + '" href="' + hrefOf(old.skincare, '/skincare') + '">Skincare</a>' +
      '<a class="' + activeClass('/sale') + '" href="' + hrefOf(old.sale, '/sale') + '">Sale</a>' +
      '</div>';

    parent.insertBefore(dropdown, anchorForInsert);

    [old.makeup, old.skincare, old.sale].forEach(function (a) {
      a.classList.add('glow-nav-hidden-by-shop');

      const li = a.closest('li');
      if (li) li.classList.add('glow-nav-hidden-by-shop-force');

      /* fallback kalau bukan li */
      if (!li && a.parentElement && a.parentElement !== parent) {
        a.parentElement.classList.add('glow-nav-hidden-by-shop-force');
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

  function init() {
    forceGreenSale();
    createShopDropdown();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.addEventListener('load', init);

  /* kalau slider/landing render terlambat */
  setTimeout(init, 300);
  setTimeout(init, 900);
  setTimeout(init, 1800);
})();
