/* PATCH PROFILE DROPDOWN LINK + PROFILE TRIGGER TEXT VISIBILITY ONLY */
(function () {
  function injectProfileTriggerVisibilityStyle() {
    if (document.getElementById('profile-trigger-visibility-fix')) return;

    const style = document.createElement('style');
    style.id = 'profile-trigger-visibility-fix';
    style.textContent = `
      body .site-header .profile-trigger strong,
      body header .profile-trigger strong,
      body .profile-trigger strong {
        color: #2f2139 !important;
        -webkit-text-fill-color: #2f2139 !important;
        opacity: 1 !important;
        visibility: visible !important;
      }

      body .site-header .profile-trigger i,
      body header .profile-trigger i,
      body .profile-trigger i {
        color: #8753b2 !important;
        -webkit-text-fill-color: #8753b2 !important;
        opacity: 1 !important;
        visibility: visible !important;
      }

      body .site-header .profile-trigger:hover strong,
      body header .profile-trigger:hover strong,
      body .profile-trigger:hover strong {
        color: #74429f !important;
        -webkit-text-fill-color: #74429f !important;
      }
    `;

    document.head.appendChild(style);
  }

  function fixProfileDropdownLinks() {
    injectProfileTriggerVisibilityStyle();

    const profileUrl = '/profile';

    document.querySelectorAll('a, button').forEach(function (el) {
      const text = (el.textContent || '').trim().toLowerCase();

      if (
        text === 'profile' ||
        text === 'my profile' ||
        text.includes('my profile')
      ) {
        if (el.tagName.toLowerCase() === 'a') {
          el.setAttribute('href', profileUrl);
        }
      }
    });

    document.querySelectorAll('[data-profile-trigger]').forEach(function (btn) {
      const menu = btn.closest('[data-profile-menu]');
      if (!menu || btn.dataset.profileFixBound === 'true') return;

      btn.dataset.profileFixBound = 'true';
      btn.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        menu.classList.toggle('is-open');
      });
    });

    document.addEventListener('click', function (event) {
      document.querySelectorAll('[data-profile-menu].is-open').forEach(function (menu) {
        if (!menu.contains(event.target)) {
          menu.classList.remove('is-open');
        }
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fixProfileDropdownLinks);
  } else {
    fixProfileDropdownLinks();
  }
})();
