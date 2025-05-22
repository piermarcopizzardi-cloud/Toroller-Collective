function initializeMobileMenu() {
    console.log('Initializing mobile menu...');
    
    // Gestione del link Community (solo per i link nel menu principale, non nel menu mobile)
    const communityLinks = document.querySelectorAll('.nav-links .community-link');
    communityLinks.forEach(link => {
        const isLoggedIn = link.dataset.loggedIn === 'true';
        if (!isLoggedIn) {
            link.style.display = 'none';
            link.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = 'login.php';
            });
        }
    });

    // Mobile Menu Functionality
    const hamburger = document.querySelector('.hamburger-menu');
    const closeMenu = document.querySelector('.close-menu');
    const mobileMenu = document.querySelector('.mobile-menu');
    const mobileLinks = document.querySelectorAll('.mobile-menu .nav-link, .mobile-menu .auth-buttons a, .mobile-menu .user-menu a');

    // Debug: Check if elements are found
    console.log('Hamburger menu:', hamburger);
    console.log('Close menu:', closeMenu);
    console.log('Mobile menu:', mobileMenu);
    console.log('Mobile links:', mobileLinks.length);

    function toggleMenu(e) {
        if (e) e.stopPropagation();
        console.log('[DEBUG] toggleMenu called. Event:', e); // Added log
        if (!mobileMenu) { // Added check
            console.error('[DEBUG] mobileMenu element is null in toggleMenu. Cannot toggle active state.');
            return;
        }
        console.log('[DEBUG] mobileMenu classes BEFORE toggle:', mobileMenu.className); // Added log
        mobileMenu.classList.toggle('active');
        console.log('[DEBUG] mobileMenu classes AFTER toggle:', mobileMenu.className); // Added log
        document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : '';
    }

    function closeMenuOnOutsideClick(e) {
        // Added null checks for mobileMenu and hamburger
        if (mobileMenu && mobileMenu.classList.contains('active') && 
            hamburger && !hamburger.contains(e.target) && 
            !mobileMenu.contains(e.target)) {
            console.log('[DEBUG] Closing menu due to outside click.'); // Added log
            toggleMenu(); 
        }
    }

    // Event Listeners
    if (hamburger) {
        hamburger.addEventListener('click', function(event) { // Wrapped in function to pass event explicitly
            console.log('[DEBUG] Hamburger clicked!'); // Added log
            toggleMenu(event); // Pass the event object
        });
    } else {
        console.error('[DEBUG] Hamburger element (.hamburger-menu) not found. Click event not attached.');
    }

    if (closeMenu) {
        closeMenu.addEventListener('click', function(event) { // Wrapped in function to pass event explicitly
            console.log('[DEBUG] Close menu button clicked!'); // Added log
            toggleMenu(event); // Pass the event object
        });
    } else {
        console.error('[DEBUG] Close menu button (.close-menu) not found. Click event not attached.');
    }
    
    document.addEventListener('click', closeMenuOnOutsideClick);

    mobileLinks.forEach(link => {
        link.addEventListener('click', (event) => { // Added event parameter
            console.log('[DEBUG] Mobile menu link clicked. Closing menu.'); // Added log
            // It's good practice for toggleMenu to handle a null/undefined event if called directly
            toggleMenu(event); // Pass event, or toggleMenu should be robust to event being undefined
        });
    });
}

// Initialize the mobile menu when the DOM is loaded
document.addEventListener('DOMContentLoaded', initializeMobileMenu);

// Cart Popup Functionality
document.addEventListener('DOMContentLoaded', () => {
    const cartIcon = document.getElementById('cartIcon');
    const cartPopup = document.getElementById('cartPopup');
    const closeCartPopup = document.getElementById('closeCartPopup');
    const cartBadge = document.getElementById('cartBadge');
    const cartPopupItems = document.getElementById('cartPopupItems');
    const cartPopupTotal = document.getElementById('cartPopupTotal');
    const cartPopupCheckoutBtn = document.getElementById('cartPopupCheckoutBtn');
    const basePath = (document.querySelector('meta[name="base-path"]'))?.getAttribute('content') || '';

    if (!cartIcon || !cartPopup || !closeCartPopup || !cartBadge || !cartPopupItems || !cartPopupTotal || !cartPopupCheckoutBtn) {
        console.error('Cart DOM elements not found. Cart functionality may be affected.');
        return;
    }

    // Function to toggle cart popup visibility
    function toggleCartPopup() {
        cartPopup.classList.toggle('active');
    }

    // Function to render cart items in the popup
    function renderCartItems(data) {
        cartPopupItems.innerHTML = ''; // Clear existing items
        if (data.cartItems && data.cartItems.length > 0) {
            data.cartItems.forEach(item => {
                const itemElement = document.createElement('div');
                itemElement.classList.add('cart-item');
                itemElement.innerHTML = `
                    <img src="${basePath}/${item.image || 'assets/product-placeholder.jpg'}" alt="${item.name}">
                    <div class="item-details">
                        <div class="item-name">${item.name}</div>
                        <div class="item-quantity-price">${item.quantita} x €${parseFloat(item.price).toFixed(2)}</div>
                    </div>
                    <div class="item-price">€${(item.quantita * item.price).toFixed(2)}</div>
                    <button class="remove-item-popup" data-cart-item-id="${item.id}">&times;</button>
                `;
                cartPopupItems.appendChild(itemElement);
            });
            addRemoveButtonListeners();
        } else {
            cartPopupItems.innerHTML = '<p class="empty-cart">Il tuo carrello è vuoto.</p>';
        }
        cartPopupTotal.innerHTML = `<span>Totale:</span> <span>€${parseFloat(data.total || 0).toFixed(2)}</span>`;
        updateCartBadge(data.cartTotalQuantity || 0);
    }

    // Function to update cart badge
    function updateCartBadge(quantity) {
        cartBadge.textContent = quantity;
        cartBadge.style.display = quantity > 0 ? 'block' : 'none';
    }

    // Function to load cart items via AJAX
    async function loadCartItems() {
        try {
            const response = await fetch(`${basePath}/shop.php?action=get_cart_items`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            if (data.success) {
                renderCartItems(data);
            } else {
                console.error('Failed to load cart items:', data.message);
                cartPopupItems.innerHTML = '<p class="empty-cart">Errore nel caricare il carrello.</p>';
            }
        } catch (error) {
            console.error('Error loading cart items:', error);
            cartPopupItems.innerHTML = '<p class="empty-cart">Errore di connessione.</p>';
        }
    }

    // Function to remove item from cart via AJAX
    async function removeFromCart(cartItemId) {
        try {
            const formData = new FormData();
            formData.append('action', 'remove_from_cart');
            formData.append('cart_item_id', cartItemId);

            const response = await fetch(`${basePath}/shop.php`, {
                method: 'POST',
                body: formData
            });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            if (data.success) {
                loadCartItems(); // Reload cart items to reflect removal
            } else {
                console.error('Failed to remove item from cart:', data.message);
                alert('Errore nella rimozione dell\'articolo.');
            }
        } catch (error) {
            console.error('Error removing item from cart:', error);
            alert('Errore di connessione durante la rimozione.');
        }
    }

    // Add event listeners to dynamically created remove buttons
    function addRemoveButtonListeners() {
        document.querySelectorAll('.remove-item-popup').forEach(button => {
            button.addEventListener('click', function() {
                const cartItemId = this.dataset.cartItemId;
                removeFromCart(cartItemId);
            });
        });
    }

    // Event Listeners
    cartIcon.addEventListener('click', (e) => {
        e.stopPropagation(); 
        // Check if user is logged in (assuming $isLoggedIn is available globally or via a meta tag)
        // For now, we assume if cartIcon is visible, user might be logged in or guest cart is allowed.
        // Proper login check should be done before sensitive actions or on page load.
        loadCartItems(); // Load/refresh items when icon is clicked
        toggleCartPopup();
    });

    closeCartPopup.addEventListener('click', () => {
        toggleCartPopup();
    });

    cartPopupCheckoutBtn.addEventListener('click', () => {
        window.location.href = `${basePath}/checkout.php`;
    });

    // Close popup if clicked outside
    document.addEventListener('click', (event) => {
        if (cartPopup.classList.contains('active') && !cartPopup.contains(event.target) && !cartIcon.contains(event.target)) {
            toggleCartPopup();
        }
    });

    // Prevent cart popup from closing when clicking inside it (if event bubbles up)
    cartPopup.addEventListener('click', (event) => {
        event.stopPropagation();
    });

    // Global function to refresh cart state (e.g., after adding an item from shop.php)
    window.refreshCartState = async (showPopup = false) => {
        await loadCartItems();
        if (showPopup) {
            if (!cartPopup.classList.contains('active')) {
                toggleCartPopup();
            }
        } else {
             // Optionally, just update badge if popup isn't meant to be shown
            try {
                const response = await fetch(`${basePath}/shop.php?action=get_cart_quantity`);
                const data = await response.json();
                if (data.success) {
                    updateCartBadge(data.cartTotalQuantity || 0);
                }
            } catch (error) {
                console.error('Error updating cart badge only:', error);
            }
        }
    };
    
    // Initial badge update on page load (if user is logged in and has items)
    // This relies on the $cartCount PHP variable echoed into the badge initially.
    // For a more dynamic approach, uncomment and adapt:
    /*
    if (cartBadge.textContent.trim() === '0' || cartBadge.textContent.trim() === '') { // Only if not set by PHP
        refreshCartState(false); 
    }
    */
   // Ensure badge visibility is correct based on initial count from PHP
   const initialBadgeCount = parseInt(cartBadge.textContent, 10);
   // cartBadge.style.display = initialBadgeCount > 0 ? 'block' : 'none'; // Commented out to rely on CSS
});
