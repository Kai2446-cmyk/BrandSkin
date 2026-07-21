(function () {
  const form = document.querySelector('[data-address-form]');
  const mapEl = document.querySelector('#profileAddressMap');
  if (!form || !mapEl) return;

  const statusEl = form.querySelector('[data-profile-map-status]');
  const latInput = form.querySelector('[data-profile-lat]');
  const lngInput = form.querySelector('[data-profile-lng]');
  const linkInput = form.querySelector('[data-profile-map-link]');
  const toggle = document.querySelector('[data-address-toggle]');
  const locationButton = form.querySelector('[data-profile-use-location]');
  const defaultLat = Number(mapEl.dataset.defaultLat || -6.93552104);
  const defaultLng = Number(mapEl.dataset.defaultLng || 107.53465931);
  let map;
  let marker;
  let reverseTimer;

  const setStatus = (text) => { if (statusEl) statusEl.textContent = text; };
  const fill = (name, value) => {
    const field = form.querySelector(`[name="${name}"]`);
    if (field) field.value = value || '';
  };

  function saveCoordinates(lat, lng, reverse = true) {
    const fixedLat = Number(lat).toFixed(8);
    const fixedLng = Number(lng).toFixed(8);
    if (latInput) latInput.value = fixedLat;
    if (lngInput) lngInput.value = fixedLng;
    if (linkInput) linkInput.value = `https://www.google.com/maps?q=${fixedLat},${fixedLng}`;
    setStatus('Titik lokasi dipilih. Data alamat sedang dibaca dari maps.');

    if (reverse) {
      clearTimeout(reverseTimer);
      reverseTimer = setTimeout(() => reverseGeocode(fixedLat, fixedLng), 350);
    }
  }

  async function reverseGeocode(lat, lng) {
    setStatus('Membaca alamat dari maps...');
    try {
      const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&accept-language=id`;
      const response = await fetch(url, { headers: { Accept: 'application/json' } });
      if (!response.ok) throw new Error('Gagal membaca alamat');
      const data = await response.json();
      const address = data.address || {};
      fill('address_line', data.display_name || '');
      fill('district', address.suburb || address.city_district || address.village || address.district || '');
      fill('city', address.city || address.town || address.county || address.municipality || '');
      fill('province', address.state || address.region || '');
      fill('postal_code', address.postcode || '');
      fill('country', address.country || 'Indonesia');
      setStatus('Alamat otomatis terisi. Silakan koreksi bila ada bagian yang kurang tepat.');
    } catch (error) {
      setStatus('Titik maps sudah tersimpan, tetapi alamat belum terbaca otomatis. Silakan lengkapi manual.');
    }
  }

  function initMap() {
    if (!window.L) {
      setStatus('Maps belum dapat dimuat. Periksa koneksi internet lalu refresh halaman.');
      return;
    }
    if (!map) {
      map = L.map(mapEl, { zoomControl: true, scrollWheelZoom: true }).setView([defaultLat, defaultLng], 15);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
      }).addTo(map);
      marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
      map.on('click', (event) => {
        marker.setLatLng(event.latlng);
        saveCoordinates(event.latlng.lat, event.latlng.lng, true);
      });
      marker.on('dragend', () => {
        const pos = marker.getLatLng();
        saveCoordinates(pos.lat, pos.lng, true);
      });
      saveCoordinates(defaultLat, defaultLng, false);
    }
    setTimeout(() => map.invalidateSize(), 120);
  }

  toggle?.addEventListener('click', () => setTimeout(initMap, 80));

  locationButton?.addEventListener('click', () => {
    if (!navigator.geolocation) {
      setStatus('Browser tidak mendukung lokasi perangkat. Pilih titik secara manual di maps.');
      return;
    }
    setStatus('Mengambil lokasi perangkat...');
    navigator.geolocation.getCurrentPosition((position) => {
      const lat = position.coords.latitude;
      const lng = position.coords.longitude;
      initMap();
      map.setView([lat, lng], 17);
      marker.setLatLng([lat, lng]);
      saveCoordinates(lat, lng, true);
    }, () => {
      setStatus('Lokasi tidak dapat diambil. Izinkan akses lokasi atau pilih titik manual di maps.');
    }, { enableHighAccuracy: true, timeout: 12000 });
  });
})();
