/* PATCH LOGIN DESIGN + HIDE DUMMY ONLY */
(function () {
  function hideDummyLoginInfo() {
    const phrases = ['akun admin dummy', 'admin@gmail.com', 'admin123'];

    document.querySelectorAll('div, section, article, aside').forEach(function (el) {
      const text = (el.textContent || '').toLowerCase();
      const hasPhrase = phrases.some(function (phrase) {
        return text.includes(phrase);
      });

      if (!hasPhrase) return;

      const rect = el.getBoundingClientRect();
      if (rect.width > 0 && rect.width < 720 && rect.height > 0 && rect.height < 220) {
        el.style.display = 'none';
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', hideDummyLoginInfo);
  } else {
    hideDummyLoginInfo();
  }
})();
