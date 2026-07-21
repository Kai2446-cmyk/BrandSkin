(() => {
  const selector = '.product-add-to-bag[data-cart-add], .catalogue-actions--cart-only [data-cart-add]';

  document.addEventListener('click', (event) => {
    const button = event.target.closest(selector);
    if (!button || button.classList.contains('is-adding')) return;

    const originalLabel = button.textContent.trim() || 'ADD TO BAG';
    button.dataset.originalLabel = originalLabel;
    button.classList.remove('is-added');
    button.classList.add('is-adding');
    button.textContent = 'ADDING';

    window.setTimeout(() => {
      button.classList.remove('is-adding');
      button.classList.add('is-added');
      button.textContent = 'ADDED ✓';
    }, 500);

    window.setTimeout(() => {
      button.classList.remove('is-added');
      button.textContent = button.dataset.originalLabel || 'ADD TO BAG';
    }, 1550);
  });
})();
