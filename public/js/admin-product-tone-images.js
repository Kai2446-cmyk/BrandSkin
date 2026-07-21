/* Product tone images: each tone can reference any image from the dynamic product gallery. */
(function () {
  function normalizeHex(value) {
    let hex = (value || '').trim().toUpperCase();
    if (!hex) return '';
    if (!hex.startsWith('#')) hex = '#' + hex;
    if (/^#[0-9A-F]{3}$/.test(hex)) {
      hex = '#' + hex.slice(1).split('').map((c) => c + c).join('');
    }
    return /^#[0-9A-F]{6}$/.test(hex) ? hex : '';
  }

  function splitColors(value) {
    return (value || '')
      .split(',')
      .map(normalizeHex)
      .filter(Boolean)
      .filter((color, index, arr) => arr.indexOf(color) === index);
  }

  document.addEventListener('DOMContentLoaded', function () {
    const colorInput = document.querySelector('[data-color-values]');
    const wrap = document.querySelector('[data-admin-tone-images]');
    const list = document.querySelector('[data-tone-image-list]');
    if (!colorInput || !wrap || !list) return;

    let existing = {};
    try { existing = JSON.parse(wrap.dataset.existingToneImages || '{}') || {}; } catch (e) { existing = {}; }

    function getExisting(color) {
      return existing[color] || existing[color.toLowerCase()] || existing[color.toUpperCase()] || '';
    }

    function galleryOptions(selectedPath) {
      const cards = Array.from(document.querySelectorAll('[data-gallery-card]'));
      let options = '<option value="">Pilih gambar produk</option>';
      cards.forEach(function (card, index) {
        const existingInput = card.querySelector('input[name^="existing_product_images"]');
        const urlInput = card.querySelector('input[name^="product_image_urls"]');
        const fileInput = card.querySelector('input[name^="product_images"]');
        const path = (urlInput && urlInput.value.trim()) || (existingInput && existingInput.value.trim()) || '';
        const label = fileInput && fileInput.files && fileInput.files[0]
          ? `Gambar ${index + 1} — ${fileInput.files[0].name}`
          : `Gambar ${index + 1}${path ? ' — tersedia' : ' — belum diisi'}`;
        const selected = selectedPath && path === selectedPath ? ' selected' : '';
        options += `<option value="${index}"${selected}>${label}</option>`;
      });
      return options;
    }

    function render() {
      const colors = splitColors(colorInput.value);
      const previousSelections = {};
      list.querySelectorAll('[data-tone-color]').forEach(function (row) {
        const select = row.querySelector('[data-tone-gallery-select]');
        if (select) previousSelections[row.dataset.toneColor] = select.value;
      });

      list.innerHTML = '';
      if (!colors.length) {
        list.innerHTML = '<div class="admin-tone-empty">Pilih warna produk dulu untuk menambahkan gambar per tone.</div>';
        return;
      }

      colors.forEach(function (color, index) {
        const current = getExisting(color);
        const row = document.createElement('div');
        row.className = 'admin-tone-card';
        row.dataset.toneColor = color;
        row.innerHTML = `
          <div class="admin-tone-head">
            <span class="admin-tone-dot" style="--tone:${color}"></span>
            <strong>${color}</strong>
          </div>
          <input type="hidden" name="color_image_colors[${index}]" value="${color}">
          <input type="hidden" name="existing_color_images[${index}]" value="${String(current).replace(/"/g, '&quot;')}">
          <label>
            <small>Pilih dari galeri gambar produk</small>
            <select name="color_image_gallery_indexes[${index}]" data-tone-gallery-select>
              ${galleryOptions(current)}
            </select>
          </label>
          <div class="admin-tone-divider"><span>atau</span></div>
          <label>
            <small>Upload gambar khusus tone ini</small>
            <input type="file" name="color_image_uploads[${index}]" accept="image/*">
          </label>
          <label>
            <small>Atau link gambar tone</small>
            <input type="text" name="color_image_urls[${index}]" value="" placeholder="${current || 'https://...'}">
          </label>
          ${current ? `<div class="admin-tone-current">Saat ini: <span>${current}</span></div>` : ''}
        `;
        list.appendChild(row);
        const select = row.querySelector('[data-tone-gallery-select]');
        if (previousSelections[color] !== undefined) select.value = previousSelections[color];
      });
    }

    render();

    let lastValue = colorInput.value;
    setInterval(function () {
      if (colorInput.value !== lastValue) {
        lastValue = colorInput.value;
        render();
      }
    }, 250);

    document.addEventListener('click', function () {
      setTimeout(function () {
        if (colorInput.value !== lastValue) {
          lastValue = colorInput.value;
          render();
        }
      }, 0);
    });

    function refreshGallerySelects() {
      list.querySelectorAll('[data-tone-gallery-select]').forEach(function (select) {
        const selected = select.value;
        select.innerHTML = galleryOptions('');
        select.value = selected;
      });
    }

    document.addEventListener('product-gallery:changed', refreshGallerySelects);
  });
})();
