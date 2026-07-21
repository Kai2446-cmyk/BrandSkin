/* PATCH ARTICLES SHOW ALL FIX ONLY */
(function () {
  function revealArticles() {
    document.querySelectorAll('.reveal-article').forEach(function (card, index) {
      card.classList.remove('is-hidden-filter');
      setTimeout(function () {
        card.classList.add('is-visible');
      }, index * 35);
    });

    const grid = document.querySelector('[data-article-grid]');
    if (grid) grid.dataset.currentFilter = 'all';
  }

  function removeEmptyMessage(grid) {
    const old = grid.querySelector('.article-filter-empty');
    if (old) old.remove();
  }

  function initArticleFilter() {
    const tabs = document.querySelector('[data-article-tabs]');
    const grid = document.querySelector('[data-article-grid]');
    if (!tabs || !grid) return;

    tabs.addEventListener('click', function (event) {
      const link = event.target.closest('[data-article-filter]');
      if (!link) return;

      event.preventDefault();
      event.stopPropagation();
      event.stopImmediatePropagation();

      const filter = (link.dataset.articleFilter || 'all').toLowerCase();

      tabs.querySelectorAll('a').forEach(item => item.classList.remove('active'));
      link.classList.add('active');

      grid.dataset.currentFilter = filter;
      grid.classList.add('is-switching');
      removeEmptyMessage(grid);

      setTimeout(function () {
        let visibleIndex = 0;

        grid.querySelectorAll('.article-card[data-article-category]').forEach(function (card) {
          const category = (card.dataset.articleCategory || '').toLowerCase();
          const show = filter === 'all' || category === filter;

          card.classList.remove('article-tab-enter');
          card.classList.remove('is-visible');

          if (!show) {
            card.classList.add('is-hidden-filter');
            return;
          }

          card.classList.remove('is-hidden-filter');

          setTimeout(function () {
            card.classList.add('is-visible');
            card.classList.add('article-tab-enter');
          }, visibleIndex * 45);

          visibleIndex++;
        });

        if (visibleIndex < 1) {
          const empty = document.createElement('div');
          empty.className = 'article-filter-empty';
          empty.textContent = 'Belum ada artikel untuk kategori ini.';
          grid.appendChild(empty);
        }

        grid.classList.remove('is-switching');

        const url = new URL(link.href, window.location.href);
        if (filter === 'all') {
          history.replaceState({}, '', url.pathname);
        } else {
          history.replaceState({}, '', url.pathname + url.search);
        }
      }, 120);
    }, true);
  }

  function initLoadMore() {
    const btn = document.querySelector('[data-article-show-all]');
    const grid = document.querySelector('[data-article-grid]');
    if (!btn || !grid) return;

    btn.addEventListener('click', function () {
      grid.querySelectorAll('.article-card').forEach(function (card, index) {
        card.classList.remove('is-hidden-filter');
        setTimeout(function () {
          card.classList.add('is-visible');
          card.classList.add('article-tab-enter');
        }, index * 25);
      });

      const allTab = document.querySelector('[data-article-filter="all"]');
      if (allTab) allTab.click();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      revealArticles();
      initArticleFilter();
      initLoadMore();
    });
  } else {
    revealArticles();
    initArticleFilter();
    initLoadMore();
  }
})();
