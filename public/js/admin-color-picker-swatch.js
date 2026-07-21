/* PATCH ADMIN COLOR PICKER SWATCH ONLY */
(function () {
  function normalizeHex(value) {
    let hex = (value || '').trim().toUpperCase();

    if (!hex) return '';

    if (!hex.startsWith('#')) {
      hex = '#' + hex;
    }

    if (/^#[0-9A-F]{3}$/.test(hex)) {
      hex = '#' + hex.slice(1).split('').map((c) => c + c).join('');
    }

    if (!/^#[0-9A-F]{6}$/.test(hex)) {
      return '';
    }

    return hex;
  }

  function splitColors(value) {
    return (value || '')
      .split(',')
      .map(normalizeHex)
      .filter(Boolean)
      .filter((color, index, arr) => arr.indexOf(color) === index);
  }

  document.addEventListener('DOMContentLoaded', function () {
    const hiddenColors = document.querySelector('[data-color-values]');
    const selectedColor = document.querySelector('[data-selected-color]');
    const swatchGrid = document.querySelector('[data-swatch-grid]');
    const selectedWrap = document.querySelector('[data-selected-colors]');
    const clearBtn = document.querySelector('[data-clear-colors]');
    const hexInput = document.querySelector('[data-hex-input]');
    const addHexBtn = document.querySelector('[data-add-hex]');

    if (!hiddenColors || !selectedColor || !swatchGrid || !selectedWrap) return;

    let colors = splitColors(hiddenColors.value);

    function syncInputs() {
      hiddenColors.value = colors.join(', ');

      if (!colors.includes(normalizeHex(selectedColor.value))) {
        selectedColor.value = colors[0] || '';
      }
    }

    function renderSelected() {
      selectedWrap.innerHTML = '';

      if (!colors.length) {
        selectedWrap.innerHTML = '<p class="admin-color-help">Belum ada warna dipilih. Produk akan tampil tanpa pilihan tone warna.</p>';
        return;
      }

      colors.forEach((color) => {
        const pill = document.createElement('span');
        pill.className = 'admin-selected-pill';
        pill.innerHTML = `<i style="--pill:${color}"></i><span>${color}</span><button type="button" aria-label="Hapus warna ${color}">×</button>`;

        pill.querySelector('button').addEventListener('click', function () {
          colors = colors.filter((item) => item !== color);
          syncInputs();
          renderAll();
        });

        selectedWrap.appendChild(pill);
      });
    }

    function renderSwatches() {
      swatchGrid.querySelectorAll('[data-color]').forEach((btn) => {
        const color = normalizeHex(btn.dataset.color);
        btn.classList.toggle('is-selected', colors.includes(color));
      });
    }

    function addCustomSwatch(color) {
      const existing = swatchGrid.querySelector(`[data-color="${color}"]`);
      if (existing) return;

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'admin-color-swatch';
      btn.dataset.color = color;
      btn.style.setProperty('--swatch', color);
      btn.setAttribute('aria-label', 'Pilih warna ' + color);
      swatchGrid.appendChild(btn);
    }

    function renderAll() {
      syncInputs();
      renderSelected();
      renderSwatches();
    }

    swatchGrid.addEventListener('click', function (event) {
      const btn = event.target.closest('[data-color]');
      if (!btn) return;

      const color = normalizeHex(btn.dataset.color);
      if (!color) return;

      if (colors.includes(color)) {
        colors = colors.filter((item) => item !== color);
      } else {
        colors.push(color);
        selectedColor.value = color;
      }

      renderAll();
    });

    if (clearBtn) {
      clearBtn.addEventListener('click', function () {
        colors = [];
        selectedColor.value = '';
        renderAll();
      });
    }

    function addManualColor() {
      const color = normalizeHex(hexInput.value);

      if (!color) {
        hexInput.focus();
        return;
      }

      addCustomSwatch(color);

      if (!colors.includes(color)) {
        colors.push(color);
      }

      selectedColor.value = color;
      hexInput.value = '';
      renderAll();
    }

    if (addHexBtn) {
      addHexBtn.addEventListener('click', addManualColor);
    }

    if (hexInput) {
      hexInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
          event.preventDefault();
          addManualColor();
        }
      });
    }

    colors.forEach(addCustomSwatch);
    renderAll();
  });
})();
