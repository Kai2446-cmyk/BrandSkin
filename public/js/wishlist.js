/* PATCH WISHLIST PER USER DATABASE ONLY */
(function () {
  const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const toggleUrl = document.querySelector('meta[name="wishlist-toggle-url"]')?.content || '/wishlist/toggle';
  const idsUrl = document.querySelector('meta[name="wishlist-ids-url"]')?.content || '/wishlist/ids';
  const loginUrl = document.querySelector('meta[name="login-url"]')?.content || '/login';

  let savedIds = [];

  function setCount(count) {
    document.querySelectorAll('[data-wishlist-count]').forEach((badge) => {
      badge.textContent = count;
      badge.hidden = Number(count) < 1;
    });
  }

  function applyUI() {
    document.querySelectorAll('[data-wishlist-toggle]').forEach((btn) => {
      const id = String(btn.dataset.productId || btn.closest('[data-product-id]')?.dataset.productId || '');
      const saved = id && savedIds.includes(id);

      btn.classList.toggle('is-wished', saved);
      btn.setAttribute('aria-pressed', saved ? 'true' : 'false');

      if (btn.classList.contains('wishlist-love')) {
        btn.textContent = saved ? '♥' : '♡';
      }
    });
  }

  async function loadWishlistIds() {
    try {
      const response = await fetch(idsUrl, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const data = await response.json();
      savedIds = (data.ids || []).map(String);
      setCount(data.count || 0);
      applyUI();
    } catch (error) {
      applyUI();
    }
  }

  async function toggleWishlist(productId) {
    try {
      const response = await fetch(toggleUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ product_id: productId })
      });

      if (response.status === 401) {
        window.location.href = loginUrl;
        return;
      }

      const data = await response.json();

      if (!data.success) {
        if (data.redirect) window.location.href = data.redirect;
        return;
      }

      savedIds = (data.ids || []).map(String);
      setCount(data.count || 0);
      applyUI();

      if (window.location.pathname.replace(/\/$/, '') === '/wishlist' && data.saved === false) {
        const card = document.querySelector(`.wishlist-card[data-product-id="${productId}"]`);
        if (card) {
          card.style.transition = 'opacity .25s ease, transform .25s ease';
          card.style.opacity = '0';
          card.style.transform = 'translateY(12px)';
          setTimeout(() => {
            card.remove();
            if (!document.querySelector('.wishlist-card')) {
              window.location.reload();
            }
          }, 260);
        }
      }
    } catch (error) {
      console.error('Wishlist error:', error);
    }
  }

  document.addEventListener('click', function (event) {
    const btn = event.target.closest('[data-wishlist-toggle]');
    if (!btn) return;

    event.preventDefault();
    event.stopPropagation();

    const id = btn.dataset.productId || btn.closest('[data-product-id]')?.dataset.productId;
    if (!id) return;

    toggleWishlist(id);
  });

  document.addEventListener('DOMContentLoaded', loadWishlistIds);
})();
