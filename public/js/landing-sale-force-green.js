/* PATCH LANDING SALE FORCE GREEN FIX ONLY */
/* Menandai section sale landing supaya CSS hijau fokus dan bisa override warna gold. */
(function () {
  function textOf(el) {
    return (el.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
  }

  function isSaleSection(el) {
    const text = textOf(el);
    const hasDiscountTitle = text.includes('50% off') || text.includes('off ⚡') || text.includes('see all');
    const hasProducts = el.querySelectorAll('[data-cart-add], .add-to-bag, .add-to-cart, .product-card, [class*="product"]').length >= 3;
    const hasSaleBadges = text.includes('-50%') || text.includes('-0%') || text.includes('add to bag');
    return hasDiscountTitle && hasProducts && hasSaleBadges;
  }

  function forceGreenTone() {
    const blocks = Array.from(document.querySelectorAll('section, .section, .home-section, .sale-section, .discount-section, div'));

    let marked = false;

    blocks.forEach(function (el) {
      if (marked) return;
      if (isSaleSection(el)) {
        el.classList.add('glowskin-force-sale-green');
        marked = true;
      }
    });

    /* Fallback: cari elemen 50% OFF lalu naik ke parent besar */
    if (!marked) {
      const all = Array.from(document.querySelectorAll('body *'));
      const title = all.find(function (el) {
        return textOf(el).includes('50% off');
      });

      if (title) {
        let parent = title;
        for (let i = 0; i < 6 && parent.parentElement; i++) {
          parent = parent.parentElement;
          if (parent.querySelectorAll('[data-cart-add], .add-to-bag, .add-to-cart, .product-card, [class*="product"]').length >= 3) {
            parent.classList.add('glowskin-force-sale-green');
            marked = true;
            break;
          }
        }
      }
    }

    /* Ubah inline style warna gold di dalam section yang sudah ditandai */
    document.querySelectorAll('.glowskin-force-sale-green [style]').forEach(function (el) {
      const style = el.getAttribute('style') || '';
      if (/c8951a|d4a017|b8860b|gold|rgba?\(.*146.*91|rgba?\(.*120.*72/i.test(style)) {
        el.style.backgroundColor = '';
        el.style.borderColor = '';
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', forceGreenTone);
  } else {
    forceGreenTone();
  }

  window.addEventListener('load', forceGreenTone);
})();
