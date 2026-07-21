/* PATCH AUTH SMOOTH SWITCH ONLY */
(function () {
  function initAuthSmoothSwitch() {
    const page = document.querySelector('[data-auth-page]');
    if (!page) return;

    document.querySelectorAll('[data-auth-switch]').forEach(function (link) {
      link.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();

        const href = link.href;
        if (!href || href === window.location.href) return;

        page.classList.add('is-auth-switching');

        setTimeout(function () {
          window.location.href = href;
        }, 260);
      }, true);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAuthSmoothSwitch);
  } else {
    initAuthSmoothSwitch();
  }
})();
