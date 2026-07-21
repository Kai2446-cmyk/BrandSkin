/* Dynamic product gallery: add/remove image slots without changing other product fields. */
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    const wrap = document.querySelector('[data-admin-product-gallery]');
    const list = document.querySelector('[data-product-gallery-list]');
    const addButton = document.querySelector('[data-add-product-image]');
    if (!wrap || !list || !addButton) return;

    let existing = [];
    try {
      existing = JSON.parse(wrap.dataset.existingGallery || '[]') || [];
    } catch (e) {
      existing = [];
    }

    const initialCount = Math.max(4, existing.length || 0);

    function escapeHtml(value) {
      return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function imageUrl(path) {
      if (!path) return '';
      if (/^https?:\/\//i.test(path) || path.startsWith('/')) return path;
      return window.location.origin + '/' + path.replace(/^\/+/, '');
    }

    function createCard(index, current) {
      const card = document.createElement('div');
      card.className = 'admin-gallery-card';
      card.dataset.galleryCard = '';
      card.dataset.galleryIndex = index;
      card.innerHTML = `
        <div class="admin-gallery-card-head">
          <strong>Gambar ${index + 1}</strong>
          <button type="button" class="admin-gallery-remove" data-remove-product-image aria-label="Hapus gambar ${index + 1}">×</button>
        </div>
        <div class="admin-gallery-preview" data-gallery-preview>
          ${current ? `<img src="${escapeHtml(imageUrl(current))}" alt="Gambar produk ${index + 1}">` : `<span>Gambar ${index + 1}</span>`}
        </div>
        <input type="hidden" name="existing_product_images[${index}]" value="${escapeHtml(current)}">
        <label>
          <small>Upload gambar ${index + 1}</small>
          <input type="file" name="product_images[${index}]" accept="image/*" data-gallery-file>
        </label>
        <label>
          <small>Atau link gambar ${index + 1}</small>
          <input type="text" name="product_image_urls[${index}]" value="" placeholder="https://..." data-gallery-url>
        </label>
      `;
      return card;
    }

    function reindex() {
      const cards = Array.from(list.querySelectorAll('[data-gallery-card]'));
      cards.forEach(function (card, index) {
        card.dataset.galleryIndex = index;
        const title = card.querySelector('.admin-gallery-card-head strong');
        const empty = card.querySelector('.admin-gallery-preview span');
        const remove = card.querySelector('[data-remove-product-image]');
        const existingInput = card.querySelector('input[name^="existing_product_images"]');
        const fileInput = card.querySelector('input[name^="product_images"]');
        const urlInput = card.querySelector('input[name^="product_image_urls"]');
        const labels = card.querySelectorAll('small');

        if (title) title.textContent = `Gambar ${index + 1}`;
        if (empty) empty.textContent = `Gambar ${index + 1}`;
        if (remove) remove.setAttribute('aria-label', `Hapus gambar ${index + 1}`);
        if (existingInput) existingInput.name = `existing_product_images[${index}]`;
        if (fileInput) fileInput.name = `product_images[${index}]`;
        if (urlInput) urlInput.name = `product_image_urls[${index}]`;
        if (labels[0]) labels[0].textContent = `Upload gambar ${index + 1}`;
        if (labels[1]) labels[1].textContent = `Atau link gambar ${index + 1}`;
      });

      document.dispatchEvent(new CustomEvent('product-gallery:changed'));
    }

    function addCard(current) {
      list.appendChild(createCard(list.querySelectorAll('[data-gallery-card]').length, current || ''));
      reindex();
    }

    for (let i = 0; i < initialCount; i += 1) addCard(existing[i] || '');

    addButton.addEventListener('click', function () {
      addCard('');
      const cards = list.querySelectorAll('[data-gallery-card]');
      cards[cards.length - 1].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    list.addEventListener('click', function (event) {
      const button = event.target.closest('[data-remove-product-image]');
      if (!button) return;
      const cards = list.querySelectorAll('[data-gallery-card]');
      if (cards.length <= 1) return;
      button.closest('[data-gallery-card]').remove();
      reindex();
    });

    list.addEventListener('change', function (event) {
      if (!event.target.matches('[data-gallery-file]')) return;
      const file = event.target.files && event.target.files[0];
      if (!file) return;
      const preview = event.target.closest('[data-gallery-card]').querySelector('[data-gallery-preview]');
      const reader = new FileReader();
      reader.onload = function (e) {
        preview.innerHTML = `<img src="${escapeHtml(e.target.result)}" alt="Preview gambar produk">`;
      };
      reader.readAsDataURL(file);
      document.dispatchEvent(new CustomEvent('product-gallery:changed'));
    });

    list.addEventListener('input', function (event) {
      if (!event.target.matches('[data-gallery-url]')) return;
      const card = event.target.closest('[data-gallery-card]');
      const preview = card.querySelector('[data-gallery-preview]');
      const value = event.target.value.trim();
      if (value) preview.innerHTML = `<img src="${escapeHtml(value)}" alt="Preview gambar produk">`;
      document.dispatchEvent(new CustomEvent('product-gallery:changed'));
    });
  });
})();
