function initializeMobileMenu() {
    console.log('Initializing mobile menu...');
    

    // Mobile Menu Functionality
    const hamburger = document.querySelector('.hamburger-menu');
    const closeMenu = document.querySelector('.close-menu');
    const mobileMenu = document.querySelector('.mobile-menu');
    const mobileLinks = document.querySelectorAll('.mobile-menu .nav-link, .mobile-menu .mobile-auth-buttons a, .mobile-menu .user-menu-mobile a'); // Aggiornato selettore per mobile

    // Debug: Check if elements are found
    console.log('Hamburger menu:', hamburger);
    console.log('Close menu:', closeMenu);
    console.log('Mobile menu:', mobileMenu);
    console.log('Mobile links:', mobileLinks.length);

    function toggleMenu(e) {
        if (e) e.stopPropagation();
        console.log('[DEBUG] toggleMenu called. Event:', e); 
        if (!mobileMenu) { 
            console.error('[DEBUG] mobileMenu element is null in toggleMenu. Cannot toggle active state.');
            return;
        }
        console.log('[DEBUG] mobileMenu classes BEFORE toggle:', mobileMenu.className); 
        mobileMenu.classList.toggle('active');
        console.log('[DEBUG] mobileMenu classes AFTER toggle:', mobileMenu.className); 
        document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : '';
    }

    function closeMenuOnOutsideClick(e) {
        if (mobileMenu && mobileMenu.classList.contains('active') && 
            hamburger && !hamburger.contains(e.target) && 
            !mobileMenu.contains(e.target)) {
            console.log('[DEBUG] Closing menu due to outside click.'); 
            toggleMenu(); 
        }
    }

    // Event Listeners
    if (hamburger) {
        hamburger.addEventListener('click', function(event) { 
            console.log('[DEBUG] Hamburger clicked!'); 
            toggleMenu(event); 
        });
    } else {
        console.error('[DEBUG] Hamburger element (.hamburger-menu) not found. Click event not attached.');
    }

    if (closeMenu) {
        closeMenu.addEventListener('click', function(event) { 
            console.log('[DEBUG] Close menu button clicked!'); 
            toggleMenu(event); 
        });
    } else {
        console.error('[DEBUG] Close menu button (.close-menu) not found. Click event not attached.');
    }
    
    document.addEventListener('click', closeMenuOnOutsideClick);

    mobileLinks.forEach(link => {
        link.addEventListener('click', (event) => { 
            console.log('[DEBUG] Mobile menu link clicked. Closing menu.'); 
            toggleMenu(event); 
        });
    });
}

// Initialize the mobile menu when the DOM is loaded
document.addEventListener('DOMContentLoaded', initializeMobileMenu);
