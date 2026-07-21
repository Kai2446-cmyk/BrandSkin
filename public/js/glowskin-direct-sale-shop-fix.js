/* DIRECT FIX — SALE GREEN + SHOP NAV */
(function () {
    if (window.__GLOWSKIN_DIRECT_SALE_SHOP_FIX__) return;
    window.__GLOWSKIN_DIRECT_SALE_SHOP_FIX__ = true;

    function clean(t) {
        return (t || '').replace(/\s+/g, ' ').trim();
    }

    function lower(el) {
        return clean(el.textContent).toLowerCase();
    }

    function important(el, prop, value) {
        if (!el) return;
        try { el.style.setProperty(prop, value, 'important'); } catch(e) {}
    }

    function findExactLink(label) {
        const links = Array.from(document.querySelectorAll('a'));
        return links.find(a => clean(a.textContent).toLowerCase() === label.toLowerCase());
    }

    function makeShopDropdown() {
        if (document.querySelector('.glowskin-shop-nav-wrap')) return;

        const makeup = findExactLink('makeup');
        const skincare = findExactLink('skincare');
        const sale = findExactLink('sale');

        if (!makeup || !skincare || !sale) return;

        const makeupHolder = makeup.closest('li') || makeup;
        const navParent = makeupHolder.parentElement;
        if (!navParent) return;

        const shop = document.createElement(makeup.closest('li') ? 'li' : 'div');
        shop.className = 'glowskin-shop-nav-wrap';

        const current = window.location.pathname.replace(/\/+$/, '') || '/';
        const makeupHref = makeup.getAttribute('href') || '/makeup';
        const skincareHref = skincare.getAttribute('href') || '/skincare';
        const saleHref = sale.getAttribute('href') || '/sale';

        shop.innerHTML = `
            <button type="button" class="glowskin-shop-nav-btn">SHOP</button>
            <div class="glowskin-shop-nav-menu">
                <a href="${makeupHref}" class="${current === '/makeup' ? 'active' : ''}">Makeup</a>
                <a href="${skincareHref}" class="${current === '/skincare' ? 'active' : ''}">Skincare</a>
                <a href="${saleHref}" class="${current === '/sale' ? 'active' : ''}">Sale</a>
            </div>
        `;

        navParent.insertBefore(shop, makeupHolder);

        [makeup, skincare, sale].forEach(a => {
            a.classList.add('glowskin-hide-old-shop-link');
            const li = a.closest('li');
            if (li) li.classList.add('glowskin-hide-old-shop-link');
            else if (a.parentElement && a.parentElement !== navParent) a.parentElement.classList.add('glowskin-hide-old-shop-link');
        });

        const btn = shop.querySelector('.glowskin-shop-nav-btn');
        btn.addEventListener('click', function(e){
            e.preventDefault();
            e.stopPropagation();
            shop.classList.toggle('is-open');
        });

        document.addEventListener('click', function(){
            shop.classList.remove('is-open');
        });
    }

    function findSaleSection() {
        const all = Array.from(document.querySelectorAll('section, main > div, body > div, .section, .home-section, div'));

        let best = null;
        let bestScore = 0;

        all.forEach(el => {
            const txt = lower(el);
            const productCount = el.querySelectorAll('[data-cart-add], .add-to-bag, .add-to-cart, .product-card, [class*="product"]').length;

            let score = 0;
            if (txt.includes('50% off')) score += 30;
            if (txt.includes('see all')) score += 8;
            if (txt.includes('add to bag')) score += 12;
            if (txt.includes('-50%')) score += 10;
            if (txt.includes('-0%')) score += 5;
            if (productCount >= 3) score += 10;
            if (productCount >= 6) score += 10;

            const rect = el.getBoundingClientRect();
            const tooHuge = rect.height > window.innerHeight * 2.5;
            if (!tooHuge && score > bestScore && productCount >= 3) {
                bestScore = score;
                best = el;
            }
        });

        if (!best) {
            const title = Array.from(document.querySelectorAll('body *')).find(el => {
                return lower(el).includes('50% off') && clean(el.textContent).length < 100;
            });

            if (title) {
                let p = title;
                for (let i=0; i<10 && p.parentElement; i++) {
                    p = p.parentElement;
                    const productCount = p.querySelectorAll('[data-cart-add], .add-to-bag, .add-to-cart, .product-card, [class*="product"]').length;
                    if (productCount >= 3) {
                        best = p;
                        break;
                    }
                }
            }
        }

        return best;
    }

    function forceSaleGreen() {
        const section = findSaleSection();
        if (!section) return;

        section.classList.add('glowskin-sale-green-section');
        important(section, 'background', 'radial-gradient(circle at 14% 10%, rgba(101,162,64,.35) 0%, transparent 30%), radial-gradient(circle at 90% 16%, rgba(61,99,40,.45) 0%, transparent 34%), linear-gradient(135deg, #13230d 0%, #243d17 36%, #3D6328 72%, #65A240 145%)');

        Array.from(section.querySelectorAll('*')).forEach(el => {
            const txt = lower(el);
            const short = clean(el.textContent);

            if (txt.includes('50% off') && short.length < 100) {
                el.classList.add('glowskin-sale-heading');
                el.setAttribute('data-gs-sale-heading', '1');
                important(el, 'background', 'linear-gradient(135deg, rgba(61,99,40,.96), rgba(101,162,64,.72))');
                important(el, 'color', '#f4ffe8');
                important(el, 'border-color', 'rgba(101,162,64,.65)');
            }

            if (/^-\d+%$/.test(short)) {
                el.classList.add('glowskin-sale-percent');
                important(el, 'background', 'linear-gradient(135deg,#65A240,#a4df75)');
                important(el, 'color', '#10220c');
            }

            if (el.matches('article,.product-card,.sale-product-card,.catalogue-product-card,[class*="product-card"],[class*="card"]') && lower(el).includes('add to bag')) {
                important(el, 'background', 'linear-gradient(180deg, rgba(33,55,22,.98), rgba(18,33,12,.99))');
                important(el, 'border-color', 'rgba(101,162,64,.42)');
            }

            if (el.matches('button,.add-to-bag,.add-to-cart,[data-cart-add]')) {
                important(el, 'background', 'rgba(61,99,40,.88)');
                important(el, 'color', '#dff4d5');
                important(el, 'border-color', 'rgba(101,162,64,.62)');
            }
        });
    }

    function init() {
        makeShopDropdown();
        forceSaleGreen();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();

    window.addEventListener('load', init);
    setTimeout(init, 200);
    setTimeout(init, 800);
    setTimeout(init, 1600);
})();
