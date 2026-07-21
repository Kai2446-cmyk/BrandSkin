/* PATCH SALE PAGE SHOW ANIMATION ONLY */
(function () {
  function readyCataloguePage() {
    document.querySelectorAll('.catalogue-page').forEach(function (page) {
      page.classList.add('is-page-ready');
      page.setAttribute('data-page-ready', 'true');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      requestAnimationFrame(readyCataloguePage);
    });
  } else {
    requestAnimationFrame(readyCataloguePage);
  }

  window.addEventListener('pageshow', function () {
    requestAnimationFrame(readyCataloguePage);
  });

  /*
   * Kalau AJAX filter mengganti isi produk, animasi tetap jalan lagi.
   */
  document.addEventListener('change', function (event) {
    if (!event.target.closest('[data-catalogue-filter-form]')) return;

    setTimeout(function () {
      const grid = document.querySelector('.catalogue-grid');
      if (grid) {
        grid.classList.remove('ajax-enter');
        void grid.offsetWidth;
        grid.classList.add('ajax-enter');
      }
      readyCataloguePage();
    }, 120);
  });
})();
