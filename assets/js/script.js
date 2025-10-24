document.addEventListener('DOMContentLoaded', () => {
    const navbar = document.querySelector('.navbar');
    const header = document.querySelector('.header');
    const authButtons = document.querySelector('.auth-buttons'); 
    const userBtn = document.querySelector('#login-btn'); 

    const modal = document.querySelector('#auth-modal');
    const closeModal = document.querySelector('#close-modal');

    // Toggle auth buttons
    if (userBtn) {
        userBtn.onclick = () => {
            authButtons.classList.toggle('active');
            navbar.classList.remove('active');
        };
    }

    // Close modal
    if (closeModal) {
        closeModal.onclick = () => {
            modal.style.display = 'none';
        };
    }

    // Close modal if clicking outside
    window.onclick = (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    };

    // Navbar toggle
    const menuBtn = document.querySelector('#menu-btn');
    if (menuBtn) {
        menuBtn.onclick = () => {
            navbar.classList.toggle('active');
            authButtons.classList.remove('active');
        };
    }
    
});


        

