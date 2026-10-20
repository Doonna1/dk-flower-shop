let products = [];

const fallbackProducts = [
    { id: 1, name: 'Blush Pink Rose Bouquet', image: 'images/pinkRoses.jpg', category: 'popular', price: 45.99 },
    { id: 2, name: 'Classic Red Rose Bouquet', image: 'images/red-roses.jpg', category: 'popular', price: 49.99 },
    { id: 3, name: 'Pure White Rose Elegance', image: 'images/white-roses.jpg', category: 'popular', price: 47.99 },
    { id: 4, name: 'Luxury Peony Arrangement', image: 'images/peonies.jpg', category: 'popular', price: 55.99 },
    { id: 5, name: 'Spring Tulip Bouquet', image: 'images/tulips.jpg', category: 'popular', price: 39.99 },
    { id: 6, name: 'Elegant Lily Arrangement', image: 'images/lilies.jpg', category: 'popular', price: 52.99 },
    { id: 7, name: 'Violet Dream Bouquet', image: 'images/violets.jpg', category: 'popular', price: 35.99 },
    { id: 8, name: 'Golden Narcissus Bouquet', image: 'images/narcissuses.jpg', category: 'popular', price: 38.99 },
    { id: 9, name: 'Forever Love Rose Bouquet', image: 'images/iLoveYou_bouqet.jpg', category: 'popular', price: 59.99 },
    { id: 10, name: 'Marry Me Signature Bouquet', image: 'images/marryMe.jpg', category: 'popular', price: 49.99 },
    { id: 11, name: 'Summer Breeze Bouquet', image: 'images/summerBouqet.jpg', category: 'popular', price: 44.99 },
    { id: 12, name: 'Pretty Paradise Floral Mix', image: 'images/prettyParadise.jpg', category: 'popular', price: 48.99 },
    { id: 13, name: 'Seasonal Mixed Flowers', image: 'images/mixflowers1.jpg', category: 'popular', price: 42.99 },
    { id: 14, name: 'Sunny Summer Bloom', image: 'images/summerFlowers.jpg', category: 'popular', price: 39.99 },
    { id: 15, name: 'Tulip Basket Delight', image: 'images/tulipbasket.jpg', category: 'popular', price: 34.99 },
    { id: 16, name: 'Fresh Garden Harmony', image: 'images/freshFromGarden.jpg', category: 'popular', price: 29.99 },
    { id: 30, name: 'Tulip Basket Delight', image: 'images/mixflowers2.jpg', category: 'popular', price: 34.99 },
    { id: 31, name: 'Fresh Garden Harmony', image: 'images/babyPinkRoses.jpg', category: 'popular', price: 29.99 },

    { id: 17, name: 'Exotic Bloom Elegance', image: 'images/exotic1.jpg', category: 'exotic', price: 34.99 },
    { id: 18, name: 'Tropical Exotic Bouquet', image: 'images/exotic2.jpg', category: 'exotic', price: 37.99 },
    { id: 19, name: 'Rare Exotic Flowers', image: 'images/exotic3.jpg', category: 'exotic', price: 35.99 },
    { id: 20, name: 'Luxury Exotic Collection', image: 'images/exoticBouqet.jpg', category: 'exotic', price: 79.99 },

    { id: 21, name: 'Green Serenity Arrangement', image: 'images/green1.jpg', category: 'greens', price: 34.99 },
    { id: 22, name: 'Botanical Green Touch', image: 'images/green2.jpg', category: 'greens', price: 36.99 },
    { id: 23, name: 'Natural Green Harmony', image: 'images/green3.jpg', category: 'greens', price: 35.99 },

    { id: 24, name: 'Cactus Garden Collection', image: 'images/cactus.jpg', category: 'cactuses', price: 29.99 },
    { id: 25, name: 'Modern Cactus Arrangement', image: 'images/cactus1.jpg', category: 'cactuses', price: 19.99 },
    { id: 26, name: 'Mini Cactus Design', image: 'images/cactus2.jpg', category: 'cactuses', price: 18.99 },

    { id: 27, name: 'Winter Wonderland Bouquet', image: 'images/winter1bouqet.jpg', category: 'winter', price: 38.99 },
    { id: 28, name: 'Frosted Winter Blooms', image: 'images/winter2bouqet.jpg', category: 'winter', price: 41.99 },
    { id: 29, name: 'Cozy Winter Floral Mix', image: 'images/winter4bouqet.jpg', category: 'winter', price: 29.99 }
];

function getHomeFlowerProductsApiUrls() {
    const qs = 'type=home&t=' + Date.now();
    const projectFolder = 'dk_projekt';
    const urls = [];

    if (window.location.protocol === 'file:') {
        urls.push('http://localhost/' + projectFolder + '/api/get_products.php?' + qs);
        urls.push('http://127.0.0.1/' + projectFolder + '/api/get_products.php?' + qs);
        return urls;
    }

    urls.push(new URL('api/get_products.php?' + qs, window.location.href).href);

    const port = window.location.port;

    if (port === '5500' || port === '5501' || port === '3000') {
        urls.push('http://localhost/' + projectFolder + '/api/get_products.php?' + qs);
        urls.push('http://127.0.0.1/' + projectFolder + '/api/get_products.php?' + qs);
    }

    return [...new Set(urls)];
}

function normalizeStoreProduct(p) {
    if (!p || typeof p !== 'object') return null;

    const image = p.image || p.image_url || '';

    return {
        ...p,
        id: p.id != null ? Number(p.id) : p.id,
        price: p.price != null ? Number(p.price) : 0,
        image
    };
}

function escapeHtml(text) {
    const s = String(text);

    return s
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/"/g, '&quot;');
}

function escapeAttr(text) {
    return String(text).replace(/"/g, '&quot;');
}

async function loadProductsFromDatabase() {
    products = [];

    const urls = getHomeFlowerProductsApiUrls();
    let lastErr = null;

    for (const url of urls) {
        try {
            const res = await fetch(url, { cache: 'no-store' });
            const text = await res.text();

            let data;

            try {
                data = JSON.parse(text);
            } catch (e) {
                lastErr = e;
                continue;
            }

            if (!res.ok || !data.success) {
                continue;
            }

            const raw = Array.isArray(data.products)
                ? data.products
                : Array.isArray(data.data)
                    ? data.data
                    : [];

            const list = raw
                .map(normalizeStoreProduct)
                .filter(Boolean);

            list.sort((a, b) => (Number(b.id) || 0) - (Number(a.id) || 0));

            products = list;

            console.log('Home flower products from:', url.split('&')[0], 'count:', products.length);

            return;

        } catch (err) {
            lastErr = err;
        }
    }

    console.log(
        'Could not load home products from API.',
        urls.map(u => u.split('?')[0]),
        lastErr
    );
}

const HOME_CATEGORY_ORDER = [
    'popular',
    'winter',
    'exotic',
    'greens',
    'cactuses'
];

function groupHomeProductsByCategory(all) {
    const map = {};

    HOME_CATEGORY_ORDER.forEach((slug) => {
        map[slug] = [];
    });

    all.forEach((p) => {
        const c = String(p.category || '').toLowerCase();

        if (map[c]) {
            map[c].push(p);
        }
    });

    HOME_CATEGORY_ORDER.forEach((k) => {
        map[k].sort((a, b) => (Number(b.id) || 0) - (Number(a.id) || 0));
    });

    return map;
}

function orderHomeProductsForCarousel(all) {
    const map = groupHomeProductsByCategory(all);
    const out = [];

    HOME_CATEGORY_ORDER.forEach((slug) => {
        (map[slug] || []).forEach((p) => out.push(p));
    });

    return out;
}

function getCarouselScrollStepForGrid(grid) {
    if (!grid) return 300;

    const card = grid.querySelector('.product-card');
    const styles = window.getComputedStyle(grid);
    const gap = parseFloat(styles.columnGap || styles.gap) || 32;

    return card ? card.offsetWidth + gap : 300;
}

function createHomeProductCard(product) {
    const card = document.createElement('div');

    card.className = 'product-card';

    card.innerHTML = `
        <div class="product-image-container">
            <img 
                src="${escapeAttr(product.image)}" 
                alt="${escapeHtml(product.name)}" 
                class="product-image" 
                loading="lazy"
            >

            <button 
                type="button" 
                class="product-heart-icon" 
                aria-label="Add to wishlist"
            >
                <svg 
                    viewBox="0 0 24 24" 
                    xmlns="http://www.w3.org/2000/svg" 
                    aria-hidden="true" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="2" 
                    stroke-linecap="round" 
                    stroke-linejoin="round"
                >
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
            </button>
        </div>

        <div class="product-info">
            <h3 class="product-name">${escapeHtml(product.name)}</h3>
            <p class="product-price">$${Number(product.price).toFixed(2)}</p>
        </div>
    `;

    const heartBtn = card.querySelector('.product-heart-icon');

    if (heartBtn) {
        heartBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            heartBtn.classList.toggle('active');
        });
    }

    card.addEventListener('click', () => {
        addToCart(product);
    });

    return card;
}

function renderProductCards(grid, list) {
    if (!grid) return;

    grid.innerHTML = '';

    if (list.length === 0) {
        grid.innerHTML = '<p>No products found.</p>';
        return;
    }

    list.forEach((p) => {
        grid.appendChild(createHomeProductCard(p));
    });
}

document.addEventListener('DOMContentLoaded', async function () {

    const productsGrid = document.getElementById('products-grid');
    const shownCountSpan = document.getElementById('shown-count');
    const totalCountSpan = document.getElementById('total-count');

    let displayedCount = 0;
    let allProductsShown = false;
    let currentProducts = [];

    await loadProductsFromDatabase();

    if (products.length === 0) {
        products = fallbackProducts
            .map(normalizeStoreProduct)
            .filter(Boolean);
    }

    currentProducts = orderHomeProductsForCarousel(products);

    function updateProductsMessage() {
        if (shownCountSpan && totalCountSpan) {

            const total = currentProducts.length;

            totalCountSpan.textContent = total;

            shownCountSpan.textContent = allProductsShown
                ? total
                : Math.min(5, total);
        }
    }

    function displayProducts(productsToShow) {
        if (!productsGrid) return;

        renderProductCards(productsGrid, productsToShow);
    }

    displayedCount = Math.min(5, currentProducts.length);

    displayProducts(currentProducts.slice(0, displayedCount));

    updateProductsMessage();
});