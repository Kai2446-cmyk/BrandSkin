(() => {
    const input = document.querySelector('[data-catalogue-search]');
    const grid = document.getElementById('catalogueGrid');
    if (!input || !grid) return;

    const wrap = input.closest('[data-catalogue-search-wrap]');
    const clearButton = wrap?.querySelector('[data-catalogue-search-clear]');
    const count = document.querySelector('[data-catalogue-count]');
    const empty = document.querySelector('[data-catalogue-search-empty]');
    const cards = Array.from(grid.querySelectorAll('.catalogue-card'));

    const normalize = (value) => String(value || '')
        .toLocaleLowerCase('id-ID')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();

    const rankCards = (items) => items.sort((a, b) => {
        const soldDiff = Number(b.dataset.soldCount || 0) - Number(a.dataset.soldCount || 0);
        if (soldDiff !== 0) return soldDiff;
        const ratingDiff = Number(b.dataset.rating || 0) - Number(a.dataset.rating || 0);
        if (ratingDiff !== 0) return ratingDiff;
        return cards.indexOf(a) - cards.indexOf(b);
    });

    const applySearch = () => {
        const keyword = normalize(input.value);
        wrap?.classList.toggle('has-value', keyword.length > 0);

        const matches = cards.filter((card) => {
            const isMatch = !keyword || normalize(card.dataset.searchText).includes(keyword);
            card.hidden = !isMatch;
            return isMatch;
        });

        // Hasil pencarian selalu memprioritaskan produk terlaris, lalu rating terbaik.
        rankCards(matches).forEach((card) => grid.appendChild(card));

        if (count) count.textContent = String(matches.length);
        if (empty) empty.hidden = matches.length !== 0;
        grid.hidden = matches.length === 0;
    };

    input.addEventListener('input', applySearch);
    clearButton?.addEventListener('click', () => {
        input.value = '';
        input.focus();
        applySearch();
    });
})();
