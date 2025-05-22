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
        mobileMenu.classList.toggle('active');
        document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : '';
    }

    function closeMenuOnOutsideClick(e) {
        if (mobileMenu.classList.contains('active') && !mobileMenu.contains(e.target) && !hamburger.contains(e.target)) {
            toggleMenu();
        }
    }

    // Event Listeners
    hamburger.addEventListener('click', toggleMenu);
    closeMenu.addEventListener('click', toggleMenu);
    document.addEventListener('click', closeMenuOnOutsideClick);

    // Close menu when clicking on links
    mobileLinks.forEach(link => {
        link.addEventListener('click', toggleMenu);
    });
}

// Initialize the mobile menu when the DOM is loaded
document.addEventListener('DOMContentLoaded', initializeMobileMenu);
