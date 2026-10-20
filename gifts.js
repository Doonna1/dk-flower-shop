let gifts = [];

const fallbackGifts = [
    { id: 1, name: 'Birthday Bloom Delight', image: 'images/happyBirthday1.jpg', category: 'happy-birthday', price: 18.00 },
    { id: 7, name: 'Endless Love Roses', image: 'images/iLoveYou_bouqet.jpg', category: 'i-love-you', price: 32.00 },
    { id: 12, name: 'Elegant Congratulations Bouquet', image: 'images/congratulation.jpg', category: 'congratulations', price: 20.00 },
    { id: 16, name: 'With Love and Apologies', image: 'images/imSorry.jpg', category: 'im-sorry', price: 25.00 }
];


async function loadGiftsFromDatabase() {
    try {

        const timestamp = new Date().getTime();
        const response = await fetch('api/get_products.php?type=gifts&t=' + timestamp);
        const data = await response.json();

        if (data.success && data.products.length > 0) {
            gifts = data.products;
            console.log('✅ Loaded ' + gifts.length + ' gifts from database');
            return true;
        } else {
            console.log('⚠️ No gifts in database, using fallback');
            gifts = fallbackGifts;
            return false;
        }
    } catch (error) {
        console.log('❌ Could not connect to database, using fallback gifts:', error);
        gifts = fallbackGifts;
        return false;
    }
}


function displayGifts(giftsToShow) {
    const giftsGrid = document.querySelector('#gifts-grid');
    if (!giftsGrid) return;

    giftsGrid.innerHTML = '';

    if (giftsToShow.length === 0) {
        giftsGrid.innerHTML = '<p class="no-products">No gifts found in this category.</p>';
        return;
    }

    giftsToShow.forEach(gift => {
        const giftCard = document.createElement('div');
        giftCard.className = 'product-card';
        giftCard.innerHTML = `
            <div class="product-image-container">
                <img src="${gift.image}" alt="${gift.name}" class="product-image" loading="lazy">
                <div class="product-overlay">
                    <button class="btn-buy-now" data-product='${JSON.stringify(gift)}'>BUY NOW</button>
                </div>
            </div>
            <div class="product-info">
                <h3 class="product-name">${gift.name}</h3>
                <p class="product-price">$${gift.price.toFixed(2)}</p>
            </div>
        `;
        giftsGrid.appendChild(giftCard);


        const buyBtn = giftCard.querySelector('.btn-buy-now');
        if (buyBtn) {
            buyBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                const productData = JSON.parse(this.getAttribute('data-product'));

                if (typeof addToCart === 'function') {
                    addToCart(productData);
                } else if (window.cart && typeof window.cart.addItem === 'function') {

                    window.cart.addItem(productData);
                    if (typeof showCartNotification === 'function') {
                        showCartNotification(productData.name);
                    }
                    if (window.cart.updateCartBadge) {
                        window.cart.updateCartBadge();
                    }
                } else {
                    console.error('Cart functionality not available');
                    alert('Unable to add item to cart. Please refresh the page.');
                }
            });
        }
    });
}


function filterGifts(category) {
    let filteredGifts;

    if (category === 'all') {
        filteredGifts = gifts;
    } else {
        filteredGifts = gifts.filter(gift => gift.category === category);
    }

    console.log(`Filtering by: ${category}, Found ${filteredGifts.length} gifts`);
    displayGifts(filteredGifts);
}

document.addEventListener('DOMContentLoaded', async function () {
    const categoryButtons = document.querySelectorAll('.category-btn');
    const giftsGrid = document.querySelector('#gifts-grid');

    await loadGiftsFromDatabase();

    if (giftsGrid) {
        displayGifts(gifts);
    }


    categoryButtons.forEach(btn => {
        btn.addEventListener('click', function () {

            categoryButtons.forEach(b => b.classList.remove('active'));


            this.classList.add('active');

            const category = this.getAttribute('data-category');
            filterGifts(category);
        });
    });


    const searchBtn = document.querySelector('.search-btn');
    if (searchBtn) {
        searchBtn.addEventListener('click', function () {
            alert('Search functionality coming soon!');
        });
    }


    const cartBtn = document.querySelector('.cart-btn');
    if (cartBtn) {
        cartBtn.addEventListener('click', function () {
            window.location.href = 'cart.html';
        });
    }
});



