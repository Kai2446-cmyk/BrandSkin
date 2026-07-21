(function () {
  'use strict';

  var key = 'glowskin-scroll:' + window.location.pathname + window.location.search;
  var navEntry = performance.getEntriesByType && performance.getEntriesByType('navigation')[0];
  var isReload = navEntry ? navEntry.type === 'reload' : (performance.navigation && performance.navigation.type === 1);
  var saved = Number(sessionStorage.getItem(key) || 0);

  // Hanya cegah flash ke hero ketika halaman di-refresh dari posisi scroll bawah.
  if (isReload && saved > 8) {
    document.documentElement.classList.add('gs-restoring-scroll');
    var style = document.createElement('style');
    style.id = 'gs-refresh-scroll-style';
    style.textContent = 'html.gs-restoring-scroll body{visibility:hidden!important}';
    document.head.appendChild(style);
  }

  function savePosition() {
    try {
      sessionStorage.setItem(key, String(window.scrollY || document.documentElement.scrollTop || 0));
    } catch (e) {}
  }

  function finishRestore() {
    if (!(isReload && saved > 8)) return;

    window.scrollTo(0, saved);
    requestAnimationFrame(function () {
      window.scrollTo(0, saved);
      document.documentElement.classList.remove('gs-restoring-scroll');
      var style = document.getElementById('gs-refresh-scroll-style');
      if (style) style.remove();
    });
  }

  window.addEventListener('pagehide', savePosition);
  window.addEventListener('beforeunload', savePosition);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', finishRestore, { once: true });
  } else {
    finishRestore();
  }

  window.addEventListener('load', function () {
    if (isReload && saved > 8) {
      window.scrollTo(0, saved);
      document.documentElement.classList.remove('gs-restoring-scroll');
      var style = document.getElementById('gs-refresh-scroll-style');
      if (style) style.remove();
    }
  }, { once: true });
})();
