/* CART PAGE FIX ONLY
   - Remove item di cart berfungsi lagi memakai AJAX DELETE.
   - Klik minus saat quantity tinggal 1 akan memunculkan popup konfirmasi hapus.
   - Popup memakai desain custom GlowSkin, bukan confirm browser.
   - Tidak mengubah halaman lain.
*/
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const cartAddUrl = document.querySelector('meta[name="cart-add-url"]')?.content || '/cart/add';
  const wishlistToggleUrl = document.querySelector('meta[name="wishlist-toggle-url"]')?.content || '/wishlist/toggle';
  const loginUrl = document.querySelector('meta[name="login-url"]')?.content || '/login';

  function toast(message) {
    let box = document.querySelector('[data-cart-toast]');
    if (!box) {
      box = document.createElement('div');
      box.setAttribute('data-cart-toast', '');
      box.style.position = 'fixed';
      box.style.right = '28px';
      box.style.bottom = '28px';
      box.style.zIndex = '99999';
      box.style.maxWidth = '420px';
      box.style.padding = '17px 22px';
      box.style.borderRadius = '18px';
      box.style.background = '#101810';
      box.style.color = '#fff';
      box.style.fontWeight = '900';
      box.style.boxShadow = '0 22px 60px rgba(0,0,0,.26)';
      box.style.borderLeft = '5px solid #4A7A3A';
      box.style.opacity = '0';
      box.style.transform = 'translateY(14px)';
      box.style.transition = '.22s ease';
      document.body.appendChild(box);
    }

    box.textContent = message;
    requestAnimationFrame(() => {
      box.style.opacity = '1';
      box.style.transform = 'translateY(0)';
    });

    clearTimeout(window.__glowCartToastTimer);
    window.__glowCartToastTimer = setTimeout(() => {
      box.style.opacity = '0';
      box.style.transform = 'translateY(14px)';
    }, 1700);
  }

  function updateCartBadge(count) {
    document.querySelectorAll('[data-cart-count]').forEach((badge) => {
      badge.textContent = count || 0;
      badge.hidden = !(Number(count) > 0);
    });
  }

  function updateWishlistBadge(count) {
    if (typeof count === 'undefined' || count === null) return;

    document.querySelectorAll('[data-wishlist-count]').forEach((badge) => {
      badge.textContent = count || 0;
      badge.hidden = !(Number(count) > 0);
    });
  }

  async function requestJson(url, options) {
    const response = await fetch(url, options);
    let data = {};
    try {
      data = await response.json();
    } catch (e) {}

    if (response.status === 401) {
      window.location.href = data.login_url || loginUrl;
      return { redirected: true, response, data };
    }

    return { response, data };
  }

  function confirmRemoveCart(productName, customText) {
    return new Promise(function (resolve) {
      const oldOverlay = document.querySelector('[data-glow-cart-confirm]');
      if (oldOverlay) oldOverlay.remove();

      const overlay = document.createElement('div');
      overlay.className = 'glow-cart-confirm-backdrop';
      overlay.setAttribute('data-glow-cart-confirm', '');
      overlay.innerHTML = `
        <div class="glow-cart-confirm-card" role="dialog" aria-modal="true" aria-labelledby="glow-cart-confirm-title">
          <button type="button" class="glow-cart-confirm-close" aria-label="Tutup">×</button>
          <div class="glow-cart-confirm-icon">!</div>
          <h3 id="glow-cart-confirm-title">Hapus produk?</h3>
          <p>${customText || `Produk <strong>"${productName || 'ini'}"</strong> akan dihapus dari shopping bag kamu.`}</p>
          <div class="glow-cart-confirm-actions">
            <button type="button" class="glow-cart-confirm-cancel">Batal</button>
            <button type="button" class="glow-cart-confirm-ok">Hapus</button>
          </div>
        </div>
      `;

      function close(result) {
        overlay.classList.remove('is-active');
        setTimeout(function () {
          overlay.remove();
          resolve(result);
        }, 180);
      }

      overlay.addEventListener('click', function (event) {
        if (event.target === overlay) close(false);
      });
      overlay.querySelector('.glow-cart-confirm-close').addEventListener('click', function () { close(false); });
      overlay.querySelector('.glow-cart-confirm-cancel').addEventListener('click', function () { close(false); });
      overlay.querySelector('.glow-cart-confirm-ok').addEventListener('click', function () { close(true); });

      document.body.appendChild(overlay);
      requestAnimationFrame(function () { overlay.classList.add('is-active'); });
    });
  }

  function productNameFromItem(item) {
    return item?.querySelector('.cart-old-name')?.textContent?.trim() || 'produk ini';
  }

  async function removeItem(itemId, productName, fromMinus) {
    const text = fromMinus
      ? `Quantity produk <strong>"${productName || 'ini'}"</strong> tinggal 1. Kalau dikurangi lagi, produk akan dihapus dari shopping bag. Lanjut hapus?`
      : `Produk <strong>"${productName || 'ini'}"</strong> akan dihapus dari shopping bag kamu.`;

    const confirmed = await confirmRemoveCart(productName || 'produk ini', text);
    if (!confirmed) return;

    const { response, data, redirected } = await requestJson(`/cart/${itemId}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    if (redirected) return;

    if (!response.ok || data.ok === false) {
      toast(data.message || 'Gagal menghapus produk dari shopping bag.');
      return;
    }

    updateCartBadge(data.count || 0);
    toast(data.message || 'Produk berhasil dihapus dari shopping bag.');
    setTimeout(function () { window.location.reload(); }, 450);
  }

  async function changeQty(itemId, qty) {
    const { response, data, redirected } = await requestJson(`/cart/${itemId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ quantity: qty }),
    });

    if (redirected) return;

    if (!response.ok || data.ok === false) {
      toast(data.message || 'Gagal update quantity.');
      return;
    }

    updateCartBadge(data.count || 0);
    window.location.reload();
  }

  async function addToCart(productId, quantity) {
    if (!productId) return;

    const { response, data, redirected } = await requestJson(cartAddUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ product_id: productId, quantity: quantity || 1 }),
    });

    if (redirected) return;

    if (!response.ok || data.ok === false) {
      toast(data.message || 'Produk gagal dimasukkan ke shopping bag.');
      return;
    }

    updateCartBadge(data.count || 0);
    toast(data.message || 'Produk berhasil ditambahkan ke shopping bag.');
    setTimeout(function () { window.location.reload(); }, 450);
  }

  async function toggleWishlist(productId, button) {
    if (!productId) return;

    const { response, data, redirected } = await requestJson(wishlistToggleUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ product_id: productId }),
    });

    if (redirected) return;

    if (!response.ok || data.ok === false) {
      toast(data.message || 'Wishlist gagal diperbarui.');
      return;
    }

    updateWishlistBadge(data.count);

    const active = Boolean(data.active ?? data.is_wishlisted ?? data.wishlisted);

    if (button) {
      button.classList.toggle('active', active);
      button.textContent = active ? '♥' : '♡';
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
    }

    toast(data.message || (active ? 'Produk masuk wishlist.' : 'Produk dihapus dari wishlist.'));

    if (!active && button) {
      const card = button.closest('.cart-old-wishlist-card, .cart-wishlist-card, [data-product-id]');
      if (card && card.closest('.cart-old-wishlist, .cart-wishlist-section')) {
        card.style.transition = '.22s ease';
        card.style.opacity = '0';
        card.style.transform = 'translateY(10px)';
        setTimeout(function () {
          card.remove();

          const grid = document.querySelector('.cart-old-wishlist-grid, .cart-wishlist-grid');
          const section = document.querySelector('.cart-old-wishlist, .cart-wishlist-section');
          if (grid && section && grid.children.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'cart-old-wishlist-empty';
            empty.textContent = 'Wishlist kamu masih kosong.';
            grid.replaceWith(empty);
          }
        }, 240);
      }
    }
  }

  function getProductId(target) {
    return target.dataset.productId
      || target.closest('[data-product-id]')?.dataset.productId
      || target.closest('form')?.querySelector('[name="product_id"]')?.value;
  }

  function getQuantity(target) {
    return target.closest('form')?.querySelector('[name="quantity"]')?.value
      || target.closest('[data-product-id]')?.querySelector('[name="quantity"]')?.value
      || 1;
  }

  document.addEventListener('click', function (event) {
    const plus = event.target.closest('[data-cart-plus]');
    const minus = event.target.closest('[data-cart-minus]');
    const remove = event.target.closest('[data-cart-remove]');
    const add = event.target.closest('[data-cart-add], .cart-old-add, .cart-wishlist-add');
    const love = event.target.closest('[data-wishlist-toggle], .cart-old-heart, .cart-wishlist-heart');
    const addWishlistFromCart = event.target.closest('[data-wishlist-from-cart]');

    if (plus || minus) {
      event.preventDefault();
      event.stopPropagation();

      const btn = plus || minus;
      const item = btn.closest('[data-cart-item]');
      const input = item?.querySelector('[data-cart-qty]');
      const itemId = btn.dataset.itemId || item?.dataset.cartItem;
      const current = parseInt(input?.value || '1', 10);
      const next = plus ? current + 1 : current - 1;
      const productName = productNameFromItem(item);

      if (!itemId) return;

      if (minus && current <= 1) {
        removeItem(itemId, productName, true);
        return;
      }

      changeQty(itemId, next);
      return;
    }

    if (remove) {
      event.preventDefault();
      event.stopPropagation();

      const item = remove.closest('[data-cart-item]');
      const itemId = remove.dataset.itemId || item?.dataset.cartItem;
      const productName = productNameFromItem(item);
      if (itemId) removeItem(itemId, productName, false);
      return;
    }

    if (add) {
      event.preventDefault();
      event.stopPropagation();

      const productId = getProductId(add);
      if (productId) addToCart(productId, getQuantity(add));
      return;
    }

    if (love || addWishlistFromCart) {
      event.preventDefault();
      event.stopPropagation();

      const btn = love || addWishlistFromCart;
      const productId = getProductId(btn);
      if (productId) toggleWishlist(productId, btn);
      return;
    }
  }, true);
})();

(function () {
  document.querySelectorAll('[data-glowskin-voucher-toggle]').forEach((button) => {
    if (button.dataset.voucherAccordionReady === '1') return;
    button.dataset.voucherAccordionReady = '1';
    button.addEventListener('click', function () {
      const group = button.closest('[data-glowskin-voucher-group]');
      const wrapper = button.closest('[data-glowskin-voucher-groups]');
      if (!group) return;
      const willOpen = !group.classList.contains('is-open');
      wrapper?.querySelectorAll('[data-glowskin-voucher-group]').forEach((item) => {
        if (item !== group) {
          item.classList.remove('is-open');
          item.querySelector('[data-glowskin-voucher-toggle]')?.setAttribute('aria-expanded', 'false');
        }
      });
      group.classList.toggle('is-open', willOpen);
      button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });
  });
})();
