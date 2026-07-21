/* PATCH SINGLE PAGE LOADER ONLY */
(function () {
  const loader = document.querySelector('[data-page-loader]');
  let fallbackTimer = null;

  function showLoader() {
    if (!loader) return;

    clearTimeout(fallbackTimer);
    loader.classList.add('is-active');

    /*
     * Failsafe: loader tidak akan nyangkut.
     * Ini hanya untuk halaman yang ternyata tidak benar-benar pindah.
     */
    fallbackTimer = setTimeout(hideLoader, 1100);
  }

  function hideLoader() {
    if (!loader) return;

    clearTimeout(fallbackTimer);
    loader.classList.remove('is-active');
  }

  /*
   * FIX UTAMA:
   * Tidak ada loading kedua setelah halaman baru selesai load.
   * Loader hanya muncul ketika user KLIK link / submit form.
   * Di halaman tujuan loader langsung dipastikan mati.
   */
  window.addEventListener('DOMContentLoaded', hideLoader);
  window.addEventListener('load', hideLoader);
  window.addEventListener('pageshow', hideLoader);
  window.addEventListener('popstate', hideLoader);
  window.addEventListener('focus', function () {
    setTimeout(hideLoader, 120);
  });

  document.addEventListener('click', function (event) {
    if (
      event.target.closest('[data-wishlist-toggle]') ||
      event.target.closest('[data-cart-add]') ||
      event.target.closest('[data-search-open]') ||
      event.target.closest('[data-search-close]') ||
      event.target.closest('[data-cart-min-alert]')
    ) {
      return;
    }

    const link = event.target.closest('a[href]');
    if (!link) return;

    const href = link.getAttribute('href') || '';

    if (
      link.target === '_blank' ||
      link.hasAttribute('download') ||
      href.startsWith('#') ||
      href.startsWith('mailto:') ||
      href.startsWith('tel:') ||
      href.startsWith('javascript:') ||
      link.closest('[data-no-page-loader]') ||
      event.ctrlKey ||
      event.metaKey ||
      event.shiftKey ||
      event.altKey
    ) {
      return;
    }

    let url;
    try {
      url = new URL(href, window.location.href);
      if (url.origin !== window.location.origin) return;
      if (url.href === window.location.href) return;
    } catch (e) {
      return;
    }

    showLoader();
  }, true);

  document.addEventListener('submit', function (event) {
    const form = event.target;

    if (!form) return;

    if (
      form.matches('[data-catalogue-filter-form]') ||
      form.closest('[data-cart-remove-form]') ||
      form.closest('[data-no-page-loader]')
    ) {
      return;
    }

    showLoader();
  }, true);

  /*
   * Catalogue AJAX filter: select tidak reload full website.
   */
  function initCatalogueAjaxFilter() {
    const form = document.querySelector('[data-catalogue-filter-form]');
    const wrap = document.querySelector('[data-catalogue-products-wrap]');
    if (!form || !wrap) return;

    let controller = null;

    async function loadFilteredProducts() {
      const url = new URL(window.location.href);
      const formData = new FormData(form);

      for (const [key, value] of formData.entries()) {
        if (value && value !== 'default' && value !== 'all') {
          url.searchParams.set(key, value);
        } else {
          url.searchParams.delete(key);
        }
      }

      if (controller) controller.abort();
      controller = new AbortController();

      wrap.classList.add('is-filtering');

      try {
        const response = await fetch(url.toString(), {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          signal: controller.signal
        });

        const html = await response.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const newWrap = doc.querySelector('[data-catalogue-products-wrap]');
        const newForm = doc.querySelector('[data-catalogue-filter-form]');

        if (newWrap) {
          wrap.innerHTML = newWrap.innerHTML;
          const grid = wrap.querySelector('.catalogue-grid');
          if (grid) grid.classList.add('ajax-enter');
        }

        if (newForm) {
          form.innerHTML = newForm.innerHTML;
          bindFilterEvents();
        }

        history.replaceState({}, '', url.toString());
      } catch (error) {
        if (error.name !== 'AbortError') console.error('Filter error:', error);
      } finally {
        wrap.classList.remove('is-filtering');
        hideLoader();
      }
    }

    function bindFilterEvents() {
      form.querySelectorAll('select').forEach((select) => {
        select.addEventListener('change', loadFilteredProducts);
      });
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      loadFilteredProducts();
    });

    bindFilterEvents();
  }

  function notifyCart(message, type = 'info') {
    const old = document.querySelector('.glow-cart-notice');
    if (old) old.remove();

    const el = document.createElement('div');
    el.className = 'glow-cart-notice ' + type;
    el.textContent = message;
    document.body.appendChild(el);

    setTimeout(() => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(12px)';
      setTimeout(() => el.remove(), 240);
    }, 2200);
  }

  function confirmBox({ title, message, okText = 'Ya', cancelText = 'Batal' }) {
    return new Promise((resolve) => {
      const overlay = document.createElement('div');
      overlay.className = 'glow-confirm-backdrop';
      overlay.innerHTML = `
        <div class="glow-confirm-card" role="dialog" aria-modal="true">
          <div class="glow-confirm-icon">!</div>
          <h3>${title}</h3>
          <p>${message}</p>
          <div class="glow-confirm-actions">
            <button type="button" class="glow-confirm-cancel">${cancelText}</button>
            <button type="button" class="glow-confirm-ok">${okText}</button>
          </div>
        </div>
      `;

      document.body.appendChild(overlay);
      requestAnimationFrame(() => overlay.classList.add('is-active'));

      const close = (value) => {
        overlay.classList.remove('is-active');
        setTimeout(() => overlay.remove(), 190);
        resolve(value);
      };

      overlay.querySelector('.glow-confirm-cancel').addEventListener('click', () => close(false));
      overlay.querySelector('.glow-confirm-ok').addEventListener('click', () => close(true));
      overlay.addEventListener('click', (event) => {
        if (event.target === overlay) close(false);
      });
    });
  }

  document.addEventListener('click', function (event) {
    const minBtn = event.target.closest('[data-cart-min-alert]');
    if (!minBtn) return;

    event.preventDefault();
    notifyCart('Jumlah produk minimal 1. Pakai tombol REMOVE kalau ingin menghapus produk dari cart.');
  });

  document.addEventListener('submit', async function (event) {
    const form = event.target.closest('[data-cart-remove-form]');
    if (!form) return;

    event.preventDefault();

    const productName = form.dataset.productName || 'produk ini';
    const ok = await confirmBox({
      title: 'Hapus produk?',
      message: `Produk "${productName}" akan dihapus dari shopping bag kamu.`,
      okText: 'Hapus',
      cancelText: 'Batal'
    });

    if (ok) {
      notifyCart('Produk sedang dihapus dari shopping bag...', 'success');
      showLoader();
      form.submit();
    }
  });

  document.addEventListener('DOMContentLoaded', initCatalogueAjaxFilter);
})();
