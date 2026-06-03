(function initScrollReveal() {
    document.addEventListener('DOMContentLoaded', () => {
        const revealEls = document.querySelectorAll('.car-card, .stat-item');
        if (!revealEls.length) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity   = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08 });

        revealEls.forEach((el, i) => {
            el.style.opacity    = '0';
            el.style.transform  = 'translateY(20px)';
            el.style.transition = `opacity 0.5s ease ${i * 0.04}s, transform 0.5s ease ${i * 0.04}s`;
            observer.observe(el);
        });
    });
})();

(function initBackToTop() {
    const backTop = document.getElementById('back-top');
    if (backTop) {
        window.addEventListener('scroll', () => {
            backTop.classList.toggle('visible', window.scrollY > 400);
        }, { passive: true });

        backTop.addEventListener('click', () => {
            const behavior = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';
            window.scrollTo({ top: 0, behavior });
            backTop.blur();
        });
    }
})();

(function initInventoryReveal() {
    const button = document.querySelector('[data-show-inventory]');
    if (!button) return;

    button.addEventListener('click', () => {
        document.querySelectorAll('.inventory-extra, .inventory-extra-section').forEach((item) => {
            item.classList.remove('is-hidden');
        });
        button.closest('.inventory-more-row')?.remove();
    });
})();
