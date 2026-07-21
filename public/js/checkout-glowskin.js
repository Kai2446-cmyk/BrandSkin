(function () {
  const modal = document.querySelector('[data-address-modal]');
  if (!modal) return;

  const openButtons = document.querySelectorAll('[data-address-modal-open]');
  const closeButtons = modal.querySelectorAll('[data-address-modal-close]');
  const form = modal.querySelector('.checkout-address-form');
  const editBlock = modal.querySelector('[data-address-edit-block]');
  const mapText = modal.querySelector('[data-map-text]');
  const mapInput = form?.querySelector('[name="map_link"]');
  const mapStatus = modal.querySelector('[data-map-status]');
  const mapEl = modal.querySelector('#checkoutAddressMap');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const defaultLat = parseFloat(mapEl?.dataset.defaultLat || '-6.93552104');
  const defaultLng = parseFloat(mapEl?.dataset.defaultLng || '107.53465931');
  let map = null;
  let marker = null;
  let reverseTimer = null;

  const fill = (selector, value) => {
    const input = form?.querySelector(selector);
    if (input) input.value = value || '';
  };

  function focusEditBlock() {
    if (!editBlock) return;
    window.setTimeout(() => {
      editBlock.scrollIntoView({ behavior: 'smooth', block: 'start' });
      if (map) map.invalidateSize();
    }, 120);
  }

  function openModal() {
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('checkout-modal-open');
    window.setTimeout(initMap, 80);
  }

  function closeModal() {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('checkout-modal-open');
  }

  openButtons.forEach((button) => button.addEventListener('click', openModal));
  closeButtons.forEach((button) => button.addEventListener('click', closeModal));

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeModal();
  });

  function setMapStatus(text) {
    if (mapStatus) mapStatus.textContent = text;
  }

  function writeLocation(lat, lng, shouldReverse = true) {
    const fixedLat = Number(lat).toFixed(8);
    const fixedLng = Number(lng).toFixed(8);
    const link = `https://www.google.com/maps?q=${fixedLat},${fixedLng}`;

    fill('[data-address-lat]', fixedLat);
    fill('[data-address-lng]', fixedLng);
    if (mapInput) mapInput.value = link;
    if (mapText) mapText.textContent = link;
    setMapStatus('Titik lokasi dipilih. Alamat akan otomatis dibaca dari maps.');

    if (shouldReverse) {
      window.clearTimeout(reverseTimer);
      reverseTimer = window.setTimeout(() => reverseGeocode(fixedLat, fixedLng), 350);
    }
  }

  async function reverseGeocode(lat, lng) {
    setMapStatus('Membaca alamat dari maps...');
    try {
      const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&accept-language=id`;
      const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
      if (!response.ok) throw new Error('Reverse geocode failed');
      const data = await response.json();
      const address = data.address || {};
      const roadParts = [
        address.road,
        address.neighbourhood || address.suburb || address.village,
        address.city_district || address.district
      ].filter(Boolean);
      const city = address.city || address.town || address.county || address.municipality || '';
      const province = address.state || address.region || '';
      const district = address.suburb || address.city_district || address.village || address.district || '';
      const postcode = address.postcode || '';
      const display = data.display_name || roadParts.join(', ');

      if (display) fill('[name="address_line"]', display);
      fill('[name="district"]', district);
      fill('[name="city"]', city);
      fill('[name="province"]', province);
      fill('[name="postal_code"]', postcode);
      setMapStatus('Alamat otomatis terisi dari titik maps. Silakan koreksi manual kalau ada yang kurang tepat.');
    } catch (error) {
      setMapStatus('Titik maps sudah dipilih. Alamat belum bisa dibaca otomatis, silakan lengkapi manual.');
    }
  }

  function initMap() {
    if (!mapEl || !window.L) {
      setMapStatus('Maps belum terbaca. Cek koneksi internet, lalu refresh halaman.');
      return;
    }

    const currentLat = parseFloat(form?.querySelector('[data-address-lat]')?.value || defaultLat || -6.93552104);
    const currentLng = parseFloat(form?.querySelector('[data-address-lng]')?.value || defaultLng || 107.53465931);
    const start = [currentLat, currentLng];

    if (!map) {
      map = L.map(mapEl, {
        zoomControl: true,
        scrollWheelZoom: true
      }).setView(start, 15);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
      }).addTo(map);

      marker = L.marker(start, { draggable: true }).addTo(map);

      map.on('click', function (event) {
        marker.setLatLng(event.latlng);
        writeLocation(event.latlng.lat, event.latlng.lng, true);
      });

      marker.on('dragend', function () {
        const pos = marker.getLatLng();
        writeLocation(pos.lat, pos.lng, true);
      });
    } else {
      map.setView(start, 15);
      marker.setLatLng(start);
    }

    window.setTimeout(() => map.invalidateSize(), 120);
    writeLocation(start[0], start[1], !mapInput?.value);
  }

  modal.querySelectorAll('[data-fill-address]').forEach((button) => {
    button.addEventListener('click', function () {
      let data = {};
      try { data = JSON.parse(button.dataset.address || '{}'); } catch (e) {}

      fill('[data-address-id]', data.id);
      fill('[name="label"]', data.label || 'Home');
      fill('[name="recipient_name"]', data.recipient_name);
      fill('[name="phone"]', data.phone);
      fill('[name="address_line"]', data.address_line);
      fill('[name="district"]', data.district);
      fill('[name="city"]', data.city);
      fill('[name="province"]', data.province);
      fill('[name="postal_code"]', data.postal_code);
      fill('[name="map_link"]', data.map_link);
      fill('[name="courier_note"]', data.courier_note);
      fill('[data-address-lat]', data.latitude);
      fill('[data-address-lng]', data.longitude);

      if (mapText) mapText.textContent = data.map_link || 'Pilih titik maps atau klik Use My Location.';
      if (data.latitude && data.longitude && map && marker) {
        const pos = [parseFloat(data.latitude), parseFloat(data.longitude)];
        map.setView(pos, 15);
        marker.setLatLng(pos);
        window.setTimeout(() => map.invalidateSize(), 80);
      }

      modal.querySelectorAll('.checkout-saved-address-card').forEach((item) => item.classList.remove('active'));
      button.closest('.checkout-saved-address-card')?.classList.add('active');
      focusEditBlock();
    });
  });


  modal.querySelector('[data-new-address]')?.addEventListener('click', function () {
    fill('[data-address-id]', '');
    fill('[name="label"]', 'Home');
    fill('[name="recipient_name"]', '');
    fill('[name="phone"]', '');
    fill('[name="address_line"]', '');
    fill('[name="district"]', '');
    fill('[name="city"]', '');
    fill('[name="province"]', '');
    fill('[name="postal_code"]', '');
    fill('[name="map_link"]', '');
    fill('[name="courier_note"]', '');
    modal.querySelectorAll('.checkout-saved-address-card').forEach((item) => item.classList.remove('active'));
    setMapStatus('Alamat baru. Pilih titik di maps atau gunakan lokasi perangkat.');
    focusEditBlock();
  });


  function showAddressNotice(message, type = 'success') {
    let notice = document.querySelector('[data-checkout-notice]');
    if (!notice) {
      notice = document.createElement('div');
      notice.className = 'checkout-notice';
      notice.setAttribute('data-checkout-notice', '');
      document.body.appendChild(notice);
    }

    notice.textContent = message;
    notice.dataset.type = type;
    notice.classList.add('is-show');
    window.clearTimeout(notice._timer);
    notice._timer = window.setTimeout(() => notice.classList.remove('is-show'), 2600);
  }

  function confirmAddressDelete(label) {
    return new Promise((resolve) => {
      const confirmBox = document.createElement('div');
      confirmBox.className = 'checkout-confirm is-open';
      confirmBox.innerHTML = `
        <div class="checkout-confirm-backdrop"></div>
        <div class="checkout-confirm-panel">
          <strong>Hapus alamat?</strong>
          <p>Alamat <b>${label || 'ini'}</b> akan dihapus dari daftar alamat tersimpan.</p>
          <div>
            <button type="button" data-confirm-cancel>Cancel</button>
            <button type="button" data-confirm-ok>Hapus</button>
          </div>
        </div>
      `;
      document.body.appendChild(confirmBox);

      const done = (value) => {
        confirmBox.remove();
        resolve(value);
      };

      confirmBox.querySelector('[data-confirm-cancel]').addEventListener('click', () => done(false));
      confirmBox.querySelector('.checkout-confirm-backdrop').addEventListener('click', () => done(false));
      confirmBox.querySelector('[data-confirm-ok]').addEventListener('click', () => done(true));
    });
  }

  modal.querySelectorAll('[data-delete-address]').forEach((button) => {
    button.addEventListener('click', async function (event) {
      event.preventDefault();
      event.stopPropagation();

      const url = button.dataset.deleteUrl;
      const label = button.dataset.deleteLabel || 'alamat ini';
      if (!url) return;

      const ok = await confirmAddressDelete(label);
      if (!ok) return;

      button.disabled = true;
      try {
        const response = await fetch(url, {
          method: 'DELETE',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf
          }
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok || data.ok === false) {
          throw new Error(data.message || 'Alamat belum bisa dihapus.');
        }

        showAddressNotice('Alamat berhasil dihapus.', 'success');
        window.setTimeout(() => window.location.reload(), 650);
      } catch (error) {
        button.disabled = false;
        showAddressNotice('Alamat belum bisa dihapus. Coba lagi.', 'error');
      }
    });
  });

  if (mapInput && mapText) {
    mapInput.addEventListener('input', function () {
      mapText.textContent = mapInput.value || 'Pilih titik maps atau klik Use My Location.';
    });
  }

  const locationButton = modal.querySelector('[data-use-current-location]');
  locationButton?.addEventListener('click', function () {
    if (!navigator.geolocation) {
      setMapStatus('Browser belum mendukung geolocation. Pilih titik di maps atau isi alamat manual.');
      return;
    }

    locationButton.disabled = true;
    locationButton.textContent = 'Detecting...';
    setMapStatus('Mengambil lokasi perangkat...');

    navigator.geolocation.getCurrentPosition(function (position) {
      const lat = position.coords.latitude;
      const lng = position.coords.longitude;
      if (map && marker) {
        map.setView([lat, lng], 16);
        marker.setLatLng([lat, lng]);
        window.setTimeout(() => map.invalidateSize(), 80);
      }
      writeLocation(lat, lng, true);
      locationButton.disabled = false;
      locationButton.textContent = 'Use My Location';
    }, function () {
      setMapStatus('Lokasi tidak bisa diambil. Izinkan akses lokasi, lalu coba lagi atau pilih titik manual di maps.');
      locationButton.disabled = false;
      locationButton.textContent = 'Use My Location';
    }, { enableHighAccuracy: true, timeout: 12000 });
  });
})();

(function () {
  const box = document.querySelector('[data-shipping-box]');
  if (!box) return;

  const optionsEl = box.querySelector('[data-shipping-options]');
  const loader = box.querySelector('[data-shipping-loader]');
  const empty = box.querySelector('[data-shipping-empty]');
  const summary = document.querySelector('[data-shipping-summary]');
  const totalEl = document.querySelector('[data-checkout-total]');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const baseTotal = parseInt(totalEl?.dataset.baseTotal || '0', 10) || 0;
  const hasFreeShippingVoucher = String(box.dataset.freeShipping || '0') === '1';
  const selectedShippingCost = document.querySelector('[data-selected-shipping-cost]');
  const selectedShippingCourier = document.querySelector('[data-selected-shipping-courier]');
  const selectedShippingService = document.querySelector('[data-selected-shipping-service]');
  const selectedShippingEtd = document.querySelector('[data-selected-shipping-etd]');
  const selectedShippingDescription = document.querySelector('[data-selected-shipping-description]');

  const rupiah = (number) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(Number(number || 0)).replace('IDR', 'Rp').trim();

  function updateTotal(shippingCost, item = null) {
    const finalShippingCost = hasFreeShippingVoucher ? 0 : Number(shippingCost || 0);
    if (summary) summary.textContent = hasFreeShippingVoucher ? 'Rp0' : rupiah(finalShippingCost);
    if (totalEl) totalEl.textContent = rupiah(baseTotal + finalShippingCost);

    if (selectedShippingCost) selectedShippingCost.value = finalShippingCost;
    if (item) {
      if (selectedShippingCourier) selectedShippingCourier.value = cleanText(item.courier_name || item.courier_code || '');
      if (selectedShippingService) selectedShippingService.value = cleanText(item.service || '');
      if (selectedShippingEtd) selectedShippingEtd.value = estimateText(item.etd);
      if (selectedShippingDescription) selectedShippingDescription.value = cleanText(item.description || '');
    }
  }

  function cleanText(value) {
    return String(value || '').replace(/\s+/g, ' ').trim();
  }

  function estimateText(etd) {
    const raw = cleanText(etd).replace(/day|days|hari|kerja/gi, '').trim();
    if (!raw || raw === '-') return 'Estimasi belum tersedia';
    if (raw === '0') return 'Hari ini';
    return `${raw} hari`;
  }

  function minWeightText(description) {
    const text = cleanText(description);
    const match = text.match(/minimal\s+berat\s+(\d+)\s*kg/i);
    if (match) return `Min. ${match[1]} kg`;
    return 'Min. 1 kg';
  }

  function serviceDetail(item) {
    const details = [];
    const desc = cleanText(item.description || 'Layanan pengiriman')
      .replace(/layanan pengiriman dengan minimal berat\s+\d+\s*kg/ig, '')
      .replace(/minimal berat\s+\d+\s*kg/ig, '')
      .trim();

    if (desc && desc !== '-') details.push(desc);
    details.push(estimateText(item.etd));
    details.push(minWeightText(item.description));
    return details.filter(Boolean).join(' • ');
  }

  function groupRates(options) {
    const groups = new Map();

    options
      .slice()
      .sort((a, b) => Number(a.cost || 0) - Number(b.cost || 0))
      .forEach((item) => {
        const courierName = cleanText(item.courier_name || item.courier_code || 'Kurir');
        const key = courierName.toUpperCase();
        if (!groups.has(key)) {
          groups.set(key, {
            name: courierName,
            services: []
          });
        }
        groups.get(key).services.push(item);
      });

    return Array.from(groups.values()).map((group) => {
      group.services.sort((a, b) => Number(a.cost || 0) - Number(b.cost || 0));
      group.cheapest = group.services[0];
      return group;
    }).sort((a, b) => Number(a.cheapest?.cost || 0) - Number(b.cheapest?.cost || 0));
  }

  function renderOptions(options) {
    optionsEl.innerHTML = '';

    const groups = groupRates(options);
    const wrapper = document.createElement('div');
    wrapper.className = 'checkout-shipping-groups';

    groups.forEach((group, groupIndex) => {
      const groupEl = document.createElement('section');
      groupEl.className = 'checkout-shipping-group';

      const cheapest = group.cheapest || {};
      const head = document.createElement('button');
      head.type = 'button';
      head.className = 'checkout-shipping-group-head';
      head.innerHTML = `
        <span>
          <strong>${group.name}</strong>
          <small>${group.services.length} layanan tersedia</small>
        </span>
        <em>Mulai ${rupiah(cheapest.cost)} · ${estimateText(cheapest.etd)}</em>
        <i aria-hidden="true">⌄</i>
      `;

      const servicesEl = document.createElement('div');
      servicesEl.className = 'checkout-shipping-services';

      group.services.forEach((item, serviceIndex) => {
        const optionId = `shipping_${groupIndex}_${serviceIndex}`;
        const label = document.createElement('label');
        label.className = 'checkout-shipping-service';
        label.setAttribute('for', optionId);
        label.innerHTML = `
          <input id="${optionId}" type="radio" name="shipping_option" value="${item.cost}" ${groupIndex === 0 && serviceIndex === 0 ? 'checked' : ''}>
          <span class="checkout-shipping-radio"></span>
          <div class="checkout-shipping-info">
            <strong>${item.service || '-'} <b>${rupiah(item.cost)}</b></strong>
            <p>${serviceDetail(item)}</p>
          </div>
        `;

        const input = label.querySelector('input');
        input.dataset.courierName = item.courier_name || item.courier_code || '';
        input.dataset.service = item.service || '';
        input.dataset.etd = estimateText(item.etd);
        input.dataset.description = item.description || '';
        input.addEventListener('change', () => {
          updateTotal(item.cost, item);
          document.querySelectorAll('.checkout-shipping-group').forEach((groupNode) => groupNode.classList.remove('is-selected'));
          groupEl.classList.add('is-selected', 'is-open');
        });

        servicesEl.appendChild(label);
      });

      head.addEventListener('click', () => {
        const willOpen = !groupEl.classList.contains('is-open');
        wrapper.querySelectorAll('.checkout-shipping-group').forEach((node) => node.classList.remove('is-open'));
        if (willOpen) groupEl.classList.add('is-open');
      });

      groupEl.appendChild(head);
      groupEl.appendChild(servicesEl);
      wrapper.appendChild(groupEl);
    });

    optionsEl.appendChild(wrapper);

    const checked = optionsEl.querySelector('input[name="shipping_option"]:checked');
    const firstItem = groups[0]?.cheapest || null;
    const firstCost = checked ? checked.value : (firstItem?.cost || 0);
    const firstGroup = optionsEl.querySelector('.checkout-shipping-group');
    if (firstGroup) firstGroup.classList.add('is-selected');
    updateTotal(firstCost, firstItem);
  }

  function uniqueValues(values) {
    return Array.from(new Set(values.map(cleanText).filter((value) => value.length >= 3)));
  }

  async function loadRates() {
    const query = (box.dataset.destinationQuery || '').trim();
    const ratesUrl = box.dataset.ratesUrl;
    const weight = parseInt(box.dataset.weight || '1000', 10) || 1000;
    const district = cleanText(box.dataset.district || '');
    const city = cleanText(box.dataset.city || '');
    const province = cleanText(box.dataset.province || '');
    const postalCode = cleanText(box.dataset.postalCode || '');
    const addressLine = cleanText(box.dataset.addressLine || '');
    const addressParts = addressLine.split(',').map(cleanText).filter(Boolean);
    const destinationCandidates = uniqueValues([
      [district, city, province, postalCode].join(' '),
      [district, city, province].join(' '),
      [postalCode, city].join(' '),
      [district, city].join(' '),
      [city, province].join(' '),
      query
    ]).slice(0, 4);

    if ((!destinationCandidates.length && !query) || !ratesUrl) {
      loader.hidden = true;
      empty.hidden = false;
      updateTotal(0);
      return;
    }

    loader.hidden = false;
    empty.hidden = true;
    optionsEl.innerHTML = '';
    updateTotal(0);

    try {
      const response = await fetch(ratesUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf
        },
        body: JSON.stringify({
          destination_query: query || destinationCandidates[0] || '',
          destination_candidates: destinationCandidates,
          district,
          city,
          province,
          postal_code: postalCode,
          address_line: addressLine,
          weight
        })
      });
      const data = await response.json();
      loader.hidden = true;

      if (!response.ok || !data.ok || !Array.isArray(data.options) || !data.options.length) {
        empty.hidden = false;
        empty.querySelector('p').textContent = data.message || 'Ongkir belum bisa dimuat. Lengkapi alamat pengiriman atau coba beberapa saat lagi.';
        updateTotal(0);
        return;
      }

      renderOptions(data.options);
    } catch (error) {
      loader.hidden = true;
      empty.hidden = false;
      empty.querySelector('p').textContent = 'Ongkir belum bisa dimuat. Silakan coba beberapa saat lagi.';
      updateTotal(0);
    }
  }

  loadRates();
})();


(function () {
  function initVoucherAccordion(root = document) {
    root.querySelectorAll('[data-glowskin-voucher-toggle]').forEach((button) => {
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
  }

  function showVoucherNotice(message, type = 'success') {
    let notice = document.querySelector('[data-checkout-notice]');
    if (!notice) {
      notice = document.createElement('div');
      notice.className = 'checkout-notice';
      notice.setAttribute('data-checkout-notice', '');
      document.body.appendChild(notice);
    }
    notice.textContent = message;
    notice.dataset.type = type;
    notice.classList.add('is-show');
    window.clearTimeout(notice._timer);
    notice._timer = window.setTimeout(() => notice.classList.remove('is-show'), 2200);
  }

  document.querySelectorAll('[data-checkout-remove-voucher]').forEach((form) => {
    if (form.dataset.voucherRemoveReady === '1') return;
    form.dataset.voucherRemoveReady = '1';
    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      const button = form.querySelector('button[type="submit"]');
      const originalText = button?.textContent || 'Remove';
      const url = form.getAttribute('action');
      const formData = new FormData(form);
      if (button) {
        button.disabled = true;
        button.textContent = 'Removing...';
      }
      try {
        const response = await fetch(url, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: formData,
          credentials: 'same-origin'
        });
        if (!response.ok && response.status !== 302) throw new Error('Remove voucher failed');
        showVoucherNotice('Voucher berhasil dilepas.', 'success');
        window.setTimeout(() => window.location.replace(form.dataset.checkoutUrl || window.location.pathname), 300);
      } catch (error) {
        showVoucherNotice('Voucher belum bisa dilepas. Coba lagi sebentar.', 'error');
        if (button) {
          button.disabled = false;
          button.textContent = originalText;
        }
      }
    });
  });

  initVoucherAccordion();
})();
