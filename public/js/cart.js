/* PATCH GUEST CART LOGIN REDIRECT ONLY */
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const addUrl = document.querySelector('meta[name="cart-add-url"]')?.content || '/cart/add';
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
      box.style.background = '#111';
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
    }, 1800);
  }

  function updateCartCount(count) {
    document.querySelectorAll('[data-cart-count]').forEach((badge) => {
      badge.textContent = count || 0;
      if ((count || 0) > 0) {
        badge.hidden = false;
      } else {
        badge.hidden = true;
      }
    });
  }

  function redirectLogin(url) {
    toast('Silakan login terlebih dahulu untuk memasukkan produk ke shopping bag.');
    setTimeout(() => {
      window.location.href = url || loginUrl;
    }, 650);
  }

  async function addToCart(productId, quantity) {
    const response = await fetch(addUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        product_id: productId,
        quantity: quantity || 1,
      }),
    });

    if (response.status === 401) {
      let data = {};
      try { data = await response.json(); } catch (e) {}
      redirectLogin(data.login_url);
      return;
    }

    const data = await response.json();

    if (!response.ok || data.ok === false) {
      toast(data.message || 'Produk gagal dimasukkan ke shopping bag.');
      return;
    }

    updateCartCount(data.count || 0);
    toast(data.message || 'Produk berhasil ditambahkan ke shopping bag.');
  }

  function findProductId(target) {
    return target.dataset.productId
      || target.closest('[data-product-id]')?.dataset.productId
      || target.closest('form')?.querySelector('[name="product_id"]')?.value
      || target.closest('.product-card')?.dataset.productId
      || target.closest('.catalogue-product-card')?.dataset.productId;
  }

  function findQuantity(target) {
    return target.closest('form')?.querySelector('[name="quantity"]')?.value
      || target.closest('[data-product-id]')?.querySelector('[name="quantity"]')?.value
      || 1;
  }

  document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-cart-add], .js-add-to-cart, .add-to-cart, .add-to-bag, [data-add-to-bag]');
    if (!button) return;

    const productId = findProductId(button);
    if (!productId) return;

    event.preventDefault();
    event.stopPropagation();

    addToCart(productId, findQuantity(button));
  }, true);

  document.addEventListener('submit', function (event) {
    const form = event.target.closest('[data-cart-form], .cart-add-form, .add-to-cart-form');
    if (!form) return;

    const productId = form.querySelector('[name="product_id"]')?.value || form.dataset.productId;
    if (!productId) return;

    event.preventDefault();
    event.stopPropagation();

    addToCart(productId, form.querySelector('[name="quantity"]')?.value || 1);
  }, true);
})();
