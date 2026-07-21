/* PATCH LANDING SALE GREEN TONE ONLY */
/* Runtime helper untuk section sale di landing page yang class-nya tidak pasti.
   Tidak mengubah struktur HTML, hanya menambah class agar CSS hijau fokus ke area sale saja. */
(function () {
  function normalizeText(text) {
    return (text || '').replace(/\s+/g, ' ').trim().toLowerCase();
  }

  function markLandingSaleSection() {
    const candidates = Array.from(document.querySelectorAll('section, div'));

    candidates.forEach(function (el) {
      const text = normalizeText(el.textContent);
      const hasSaleText = text.includes('50% off') || text.includes('see all') && text.includes('add to bag');
      const hasProductCards = el.querySelectorAll('[data-cart-add], .product-card, .catalogue-product-card, .sale-product-card, .add-to-bag').length >= 2;

      if (hasSaleText && hasProductCards) {
        el.classList.add('landing-sale-section');
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', markLandingSaleSection);
  } else {
    markLandingSaleSection();
  }
})();
