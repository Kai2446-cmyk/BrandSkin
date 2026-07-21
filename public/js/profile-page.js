/* PATCH PROFILE PAGE + REVIEW EDIT ONLY */
(function () {
  function initProfilePasswordToggle() {
    const btn = document.querySelector('[data-profile-password-open]');
    const form = document.querySelector('[data-profile-password-form]');
    if (!btn || !form) return;

    btn.addEventListener('click', function () {
      form.classList.toggle('is-open');

      if (form.classList.contains('is-open')) {
        btn.textContent = 'Close Password';
        form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      } else {
        btn.textContent = 'Edit Password';
      }
    });
  }

  function syncStarPicker(picker) {
    const checked = picker.querySelector('input[type="radio"]:checked');
    const value = checked ? Number(checked.value) : 0;
    picker.querySelectorAll('label').forEach(function (label) {
      const input = label.querySelector('input[type="radio"]');
      label.classList.toggle('is-active', Number(input?.value || 0) <= value);
    });
  }

  function initStarPickers(scope) {
    (scope || document).querySelectorAll('[data-star-picker]').forEach(function (picker) {
      syncStarPicker(picker);
      picker.querySelectorAll('input[type="radio"]').forEach(function (input) {
        input.addEventListener('change', function () {
          syncStarPicker(picker);
        });
      });
      picker.querySelectorAll('label').forEach(function (label) {
        label.addEventListener('mouseenter', function () {
          const hoverValue = Number(label.querySelector('input')?.value || 0);
          picker.querySelectorAll('label').forEach(function (item) {
            const itemValue = Number(item.querySelector('input')?.value || 0);
            item.classList.toggle('is-hover', itemValue <= hoverValue);
          });
        });
      });
      picker.addEventListener('mouseleave', function () {
        picker.querySelectorAll('label').forEach(item => item.classList.remove('is-hover'));
      });
    });
  }

  function initReviewEditButtons() {
    document.querySelectorAll('[data-review-edit-target]').forEach(function (button) {
      button.addEventListener('click', function () {
        const target = document.querySelector(button.dataset.reviewEditTarget);
        if (!target) return;
        target.classList.remove('is-hidden');
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        button.textContent = 'Form Edit Dibuka';
        button.disabled = true;
        initStarPickers(target);
      });
    });
  }

  function initProfilePage() {
    initProfilePasswordToggle();
    initStarPickers(document);
    initReviewEditButtons();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initProfilePage);
  } else {
    initProfilePage();
  }
})();
