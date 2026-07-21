/* PATCH AUTH FULL HEIGHT FIX ONLY */
(function () {
  function initShowPassword() {
    document.querySelectorAll('[data-auth-show]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const wrap = btn.closest('.auth-password-line');
        const input = wrap ? wrap.querySelector('[data-auth-password]') : null;
        if (!input) return;

        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        btn.textContent = isPassword ? 'HIDE' : 'SHOW';
      });
    });
  }

  function hideDummyAndLogoIssue() {
    const phrases = ['akun admin dummy', 'admin@gmail.com', 'admin123'];

    document.querySelectorAll('div, section, article, aside').forEach(function (el) {
      const text = (el.textContent || '').toLowerCase();
      const hasPhrase = phrases.some(function (phrase) {
        return text.includes(phrase);
      });

      if (!hasPhrase) return;

      const rect = el.getBoundingClientRect();
      if (rect.width > 0 && rect.width < 760 && rect.height > 0 && rect.height < 240) {
        el.style.display = 'none';
      }
    });

    document.querySelectorAll('.auth-ref-page .login-brand, .auth-ref-page .auth-brand').forEach(function (el) {
      el.style.display = 'none';
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initShowPassword();
      hideDummyAndLogoIssue();
    });
  } else {
    initShowPassword();
    hideDummyAndLogoIssue();
  }
})();
