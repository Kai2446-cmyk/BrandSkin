/* PATCH REGISTER EMAIL VERIFICATION ONLY */
(function () {
  function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
  }

  function setAlert(message, type) {
    const alert = document.querySelector('[data-register-code-alert]');
    if (!alert) return;

    alert.hidden = false;
    alert.textContent = message;
    alert.classList.remove('success', 'error');
    alert.classList.add(type || 'success');
  }

  function initRegisterEmailVerification() {
    const button = document.querySelector('[data-send-register-code]');
    const emailInput = document.querySelector('[data-register-email]');
    const url = window.GLOWSKIN_REGISTER_CODE_URL || '/register/send-code';

    if (!button || !emailInput) return;

    button.addEventListener('click', async function () {
      const email = (emailInput.value || '').trim();

      if (!email) {
        setAlert('Isi email terlebih dahulu sebelum klik Verif Email.', 'error');
        emailInput.focus();
        return;
      }

      button.classList.add('is-sending');
      button.textContent = 'SENDING...';

      try {
        const response = await fetch(url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({ email: email }),
        });

        const data = await response.json().catch(function () {
          return {};
        });

        if (!response.ok || data.ok === false) {
          setAlert(data.message || 'Gagal mengirim kode verifikasi. Coba lagi.', 'error');
          return;
        }

        setAlert(data.message || 'Kode verifikasi berhasil dikirim ke email kamu.', 'success');

        let seconds = 60;
        button.disabled = true;
        button.textContent = 'KIRIM ULANG 60';

        const timer = setInterval(function () {
          seconds -= 1;
          button.textContent = 'KIRIM ULANG ' + seconds;

          if (seconds <= 0) {
            clearInterval(timer);
            button.disabled = false;
            button.textContent = 'VERIF EMAIL';
          }
        }, 1000);
      } catch (error) {
        setAlert('Gagal mengirim kode. Pastikan server dan konfigurasi email sudah benar.', 'error');
      } finally {
        button.classList.remove('is-sending');
        if (!button.disabled) {
          button.textContent = 'VERIF EMAIL';
        }
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRegisterEmailVerification);
  } else {
    initRegisterEmailVerification();
  }
})();
