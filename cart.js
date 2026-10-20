// Cart functionality using localStorage
class Cart {
    constructor() {
        this.items = this.loadCart();
        this.updateCartBadge();
    }

    // Load cart from localStorage
    loadCart() {
        const cartData = localStorage.getItem('cart');
        return cartData ? JSON.parse(cartData) : [];
    }

    // Save cart to localStorage
    saveCart() {
        localStorage.setItem('cart', JSON.stringify(this.items));
        this.updateCartBadge();
    }

    // Add item to cart
    addItem(product) {
        const existingItem = this.items.find(item => item.id === product.id);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            this.items.push({
                id: product.id,
                name: product.name,
                image: product.image,
                price: product.price,
                quantity: 1
            });
        }
        
        this.saveCart();
        return this.items;
    }

    // Remove item from cart
    removeItem(productId) {
        this.items = this.items.filter(item => item.id !== productId);
        this.saveCart();
        // Refresh cart display if on cart page
        if (document.getElementById('cart-items')) {
            this.displayCart();
        }
    }

    // Update item quantity
    updateQuantity(productId, quantity) {
        const item = this.items.find(item => item.id === productId);
        if (item) {
            if (quantity <= 0) {
                this.removeItem(productId);
            } else {
                item.quantity = quantity;
                this.saveCart();
            }
            // Refresh cart display if on cart page
            if (document.getElementById('cart-items')) {
                this.displayCart();
            }
        }
    }

    // Get total items count
    getTotalItems() {
        return this.items.reduce((total, item) => total + item.quantity, 0);
    }

    // Get total price
    getTotalPrice() {
        return this.items.reduce((total, item) => total + (item.price * item.quantity), 0);
    }

    // Clear cart
    clearCart() {
        this.items = [];
        this.saveCart();
    }

    // Update cart badge in header
    updateCartBadge() {
        const badges = document.querySelectorAll('.cart-badge');
        const cartTexts = document.querySelectorAll('.cart-text');
        const totalItems = this.getTotalItems();
        const totalPrice = this.getTotalPrice();

        badges.forEach(badge => {
            badge.textContent = totalItems;
        });

        cartTexts.forEach(text => {
            text.textContent = `CART ($${totalPrice.toFixed(2)})`;
        });
    }

    // Display cart items
    displayCart() {
        const cartContainer = document.getElementById('cart-items');
        if (!cartContainer) return;

        if (this.items.length === 0) {
            cartContainer.innerHTML = `
                <div class="empty-cart">
                    <p>Your cart is empty</p>
                    <a href="index.html" class="btn-continue-shopping">Continue Shopping</a>
                </div>
            `;
            this.updateCartSummary();
            return;
        }

        let cartHTML = '';
        this.items.forEach(item => {
            cartHTML += `
                <div class="cart-item" data-id="${item.id}">
                    <div class="cart-item-image">
                        <img src="${item.image}" alt="${item.name}">
                    </div>
                    <div class="cart-item-details">
                        <h3>${item.name}</h3>
                        <p class="cart-item-price">$${item.price.toFixed(2)}</p>
                    </div>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn minus" data-id="${item.id}" data-action="decrease">-</button>
                        <span class="quantity-value">${item.quantity}</span>
                        <button class="quantity-btn plus" data-id="${item.id}" data-action="increase">+</button>
                    </div>
                    <div class="cart-item-total">
                        <p>$${(item.price * item.quantity).toFixed(2)}</p>
                    </div>
                    <div class="cart-item-remove">
                        <button class="btn-remove" data-id="${item.id}" aria-label="Remove item">×</button>
                    </div>
                </div>
            `;
        });

        cartContainer.innerHTML = cartHTML;
        
        // Add event listeners for quantity buttons
        cartContainer.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = parseInt(this.getAttribute('data-id'));
                const action = this.getAttribute('data-action');
                const item = cart.items.find(i => i.id === productId);
                
                if (item) {
                    if (action === 'increase') {
                        cart.updateQuantity(productId, item.quantity + 1);
                    } else if (action === 'decrease') {
                        cart.updateQuantity(productId, item.quantity - 1);
                    }
                }
            });
        });
        
        // Add event listeners for remove buttons
        cartContainer.querySelectorAll('.btn-remove').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = parseInt(this.getAttribute('data-id'));
                cart.removeItem(productId);
                cart.displayCart();
            });
        });
        
        this.updateCartSummary();
    }

    // Update cart summary
    updateCartSummary() {
        const subtotalEl = document.getElementById('cart-subtotal');
        const shippingEl = document.getElementById('cart-shipping');
        const totalEl = document.getElementById('cart-total');
        const checkoutBtn = document.getElementById('checkout-btn');
        
        const subtotal = this.getTotalPrice();
        const shipping = subtotal > 0 ? 1.99 : 0; 
        const total = subtotal + shipping;

        if (subtotalEl) subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
        if (shippingEl) shippingEl.textContent = subtotal > 0 ? `$${shipping.toFixed(2)}` : '$0.00';
        if (totalEl) totalEl.textContent = `$${total.toFixed(2)}`;
        
        // Enable/disable checkout button based on cart status
        if (checkoutBtn) {
            if (this.items.length === 0) {
                checkoutBtn.disabled = true;
                checkoutBtn.style.opacity = '0.6';
                checkoutBtn.style.cursor = 'not-allowed';
                checkoutBtn.style.pointerEvents = 'none';
            } else {
                checkoutBtn.disabled = false;
                checkoutBtn.style.opacity = '1';
                checkoutBtn.style.cursor = 'pointer';
                checkoutBtn.style.pointerEvents = 'auto';
            }
        }
    }
}

// Initialize cart
const cart = new Cart();

// Make cart available globally
window.cart = cart;

// Add to cart function (called from product pages)
function addToCart(product) {
    cart.addItem(product);
    
    // Show notification
    showCartNotification(product.name);
    
    // Update cart badge
    cart.updateCartBadge();
}

// Show cart notification
function showCartNotification(productName) {
    // Remove existing notification if any
    const existingNotification = document.querySelector('.cart-notification');
    if (existingNotification) {
        existingNotification.remove();
    }

    // Create notification
    const notification = document.createElement('div');
    notification.className = 'cart-notification';
    notification.innerHTML = `
        <div class="cart-notification-content">
            <span>✓ Added to cart: ${productName}</span>
            <a href="cart.html">View Cart</a>
        </div>
    `;
    document.body.appendChild(notification);

    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Show order success message
function showOrderSuccessMessage(orderData) {
    console.log('Showing success message with data:', orderData);
    
    // Remove any existing notifications
    const existingNotification = document.querySelector('.order-success-message');
    if (existingNotification) {
        existingNotification.remove();
    }

    // Ensure we have the data we need
    const customerName = orderData.customerName || 'Customer';
    const customerAddress = orderData.customerAddress || '';
    const total = orderData.total || 0;

    // Create success message
    const successMessage = document.createElement('div');
    successMessage.className = 'order-success-message';
    successMessage.innerHTML = `
        <div class="order-success-content">
            <div class="success-icon">✓</div>
            <h2>Order Placed Successfully!</h2>
            <div class="order-details">
                <p><strong>Thank you, ${customerName}!</strong></p>
                <p>Your order has been confirmed and will be delivered to:</p>
                <p class="delivery-address">${customerAddress}</p>
                <div class="order-summary">
                    <p><strong>Order Total: $${total.toFixed(2)}</strong></p>
                    <p class="order-note">You will receive a confirmation email shortly.</p>
                </div>
            </div>
            <button class="btn-close-success" onclick="this.closest('.order-success-message').remove()">Close</button>
        </div>
    `;
    
    // Add to body
    document.body.appendChild(successMessage);
    
    // Force display
    successMessage.style.display = 'flex';
    successMessage.style.opacity = '1';

    // Auto-remove after 10 seconds
    setTimeout(() => {
        if (successMessage.parentNode) {
            successMessage.style.opacity = '0';
            setTimeout(() => {
                if (successMessage.parentNode) {
                    successMessage.remove();
                }
            }, 300);
        }
    }, 10000);
}

// Initialize cart display on cart page
if (document.getElementById('cart-items')) {
    cart.displayCart();
    
    // Handle checkout - Simple and direct approach
    const checkoutBtn = document.getElementById('checkout-btn');
    if (checkoutBtn) {
        // Use onclick for maximum compatibility
        checkoutBtn.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            handleCheckout();
            return false;
        };
    }

    // Handle clear cart
    const clearCartBtn = document.getElementById('clear-cart-btn');
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to clear your cart?')) {
                cart.clearCart();
                cart.displayCart();
            }
        });
    }
}

// Global function for checkout - can be called from HTML onclick
function handleCheckout() {
    // Check cart
    if (!window.cart || window.cart.items.length === 0) {
        alert('Your cart is empty! Please add items to your cart before checkout.');
        return false;
    }
    
    // Get form values - allow any input, no strict validation
    const nameField = document.getElementById('customer-name');
    const addressField = document.getElementById('customer-address');
    
    const customerName = nameField ? nameField.value : '';
    const customerAddress = addressField ? addressField.value : '';
    
    // Very basic validation - just check if fields have any content
    if (!customerName || customerName.trim().length === 0) {
        alert('Please enter your name.');
        if (nameField) nameField.focus();
        return false;
    }
    
    if (!customerAddress || customerAddress.trim().length === 0) {
        alert('Please enter your delivery address.');
        if (addressField) addressField.focus();
        return false;
    }
    
    // Get order total (subtotal + shipping)
    const subtotal = window.cart.getTotalPrice();
    const shipping = subtotal > 0 ? 1.99 : 0;
    const orderTotal = subtotal + shipping;
    
    // Show confirmation
    const confirmMsg = 'Proceed to checkout?\n\n' +
        'Name: ' + customerName + '\n' +
        'Address: ' + customerAddress + '\n' +
        'Total: $' + orderTotal.toFixed(2);
    
    if (confirm(confirmMsg)) {
        // Prepare order data for database
        const orderData = {
            customerName: customerName.trim(),
            customerAddress: customerAddress.trim(),
            total: orderTotal,
            subtotal: subtotal,
            shipping: shipping,
            items: window.cart.items
        };
        
        // Save order to database
        fetch('api/save_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(orderData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Order saved to database:', data.order_id);
            } else {
                console.error('Error saving order:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
        
        // Show success message
        try {
            if (typeof showOrderSuccessMessage === 'function') {
                showOrderSuccessMessage(orderData);
            } else {
                // Fallback to alert if function doesn't exist
                alert('Order placed successfully!\n\nThank you, ' + orderData.customerName + '!\nYour order will be delivered to:\n' + orderData.customerAddress + '\n\nTotal: $' + orderData.total.toFixed(2));
            }
        } catch (error) {
            console.error('Error showing success message:', error);
            // Fallback to simple alert
            alert('Order placed successfully!\n\nThank you, ' + orderData.customerName + '!\nYour order will be delivered to:\n' + orderData.customerAddress + '\n\nTotal: $' + orderData.total.toFixed(2));
        }
        
        // Clear everything
        window.cart.clearCart();
        window.cart.displayCart();
        if (nameField) nameField.value = '';
        if (addressField) addressField.value = '';
    }
    
    return false;
}

// Update cart badge on page load
document.addEventListener('DOMContentLoaded', function() {
    // Only update badge if elements exist
    const badges = document.querySelectorAll('.cart-badge');
    const cartTexts = document.querySelectorAll('.cart-text');
    if (badges.length > 0 || cartTexts.length > 0) {
        cart.updateCartBadge();
    }
    
   
    const cartBtn = document.querySelector('.cart-btn');
    if (cartBtn) {
        cartBtn.addEventListener('click', function() {
            window.location.href = 'cart.html';
        });
    }
});
