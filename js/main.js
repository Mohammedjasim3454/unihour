// Main JavaScript for UniHour Platform

document.addEventListener('DOMContentLoaded', () => {
    // Add simple scroll effect to navbar
    const navbar = document.querySelector('.navbar');
    
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.style.background = 'rgba(0, 0, 51, 0.95)';
            navbar.style.boxShadow = '0 4px 20px rgba(0,0,0,0.3)';
        } else {
            navbar.style.background = 'rgba(0, 0, 51, 0.8)';
            navbar.style.boxShadow = 'none';
        }
    });

    // Check for user login status (placeholder for logic)
    const checkLoginStatus = () => {
        // We will implement JWT or Session checking via PHP API
        console.log("UniHour initialized.");
    };

    checkLoginStatus();

    // Scroll Animation Observer
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.15 // Trigger when 15% of the element is visible
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target); // Stop observing once it's visible
            }
        });
    }, observerOptions);

    const fadeElements = document.querySelectorAll('.fade-in-section');
    fadeElements.forEach(el => observer.observe(el));
});
