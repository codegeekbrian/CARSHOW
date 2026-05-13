/**
 * CARSHOW Frontend JavaScript
 * Theme toggle, mobile nav, contact form, back-to-top, tab switching, scroll reveal.
 */

// ── Theme: Dark / Light ─────────────────────────────────
(function initTheme() {
    const saved = localStorage.getItem('site-theme');
    const prefers = matchMedia('(prefers-color-scheme:light)');
    const mode = saved || (prefers.matches ? 'light' : 'dark');
    document.documentElement.setAttribute('data-theme', mode);
})();

document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('theme-toggle');
    if (!btn) return;
    const setBtn = (mode) => {
        btn.setAttribute('aria-label', mode === 'light' ? 'Switch to dark mode' : 'Switch to light mode');
        const label = btn.querySelector('.theme-label');
        if (label) label.textContent = mode === 'light' ? 'Dark' : 'Light';
    };
    setBtn(document.documentElement.getAttribute('data-theme'));
    btn.addEventListener('click', () => {
        const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('site-theme', next);
        setBtn(next);
    });
});

// ── Mobile Navigation ───────────────────────────────────
function toggleNav() {
    const links  = document.getElementById('navLinks');
    const toggle = document.getElementById('navToggle');
    links?.classList.toggle('open');
    toggle?.classList.toggle('open');
}

// Mobile dropdown click trigger (for touch devices)
document.querySelectorAll('.nav-dropdown .dropdown-trigger').forEach(trigger => {
    trigger.addEventListener('click', (e) => {
        e.preventDefault();
        const menu = trigger.closest('.nav-dropdown').querySelector('.dropdown-menu');
        if (menu) menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    });
});

// Close mobile nav when a link is clicked
document.querySelectorAll('.navbar-links a').forEach(a => {
    a.addEventListener('click', () => {
        document.getElementById('navLinks')?.classList.remove('open');
    });
});

// ── Back to Top Button ──────────────────────────────────
const backTop = document.getElementById('back-top');
if (backTop) {
    window.addEventListener('scroll', () => {
        backTop.classList.toggle('visible', window.scrollY > 400);
    }, { passive: true });
}

// ── Contact Form (AJAX Submit) ───────────────────────────
(function initContact() {
    const form = document.querySelector('.contact-wrap form');
    if (!form) return;
    form.removeAttribute('onsubmit');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const resp = document.getElementById('response');
        const btn  = form.querySelector('.btn-submit');
        if (!resp) return;
        const name    = form.querySelector('#name')?.value.trim();
        const email   = form.querySelector('#email')?.value.trim();
        const message = form.querySelector('#message')?.value.trim();
        if (!name || !email || !message) {
            resp.textContent = 'Please fill in all required fields.';
            resp.className = 'visible error-response';
            return;
        }
        btn.disabled = true;
        btn.textContent = 'Sending...';
        try {
            const fd  = new FormData(form);
            const r   = await fetch(form.getAttribute('action') || '../include/contact_handler.php', {
                method: 'POST', body: fd
            });
            const data = await r.json();
            resp.textContent = data.message || (data.success ? 'Message sent!' : 'Something went wrong.');
            resp.className = 'visible' + (data.success ? '' : ' error-response');
            if (data.success) form.reset();
        } catch {
            resp.textContent = 'Network error. Please try again.';
            resp.className = 'visible error-response';
        }
        btn.disabled = false;
        btn.textContent = 'Send Message';
    });
})();

// ── Heritage Tab Switching ──────────────────────────────
function showTab(tabId, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => {
        p.style.display  = 'none';
        p.style.opacity  = '0';
    });
    document.querySelectorAll('.page-tabs a').forEach(a => a.classList.remove('active'));
    const panel = document.getElementById(tabId);
    if (panel) {
        panel.style.display = 'block';
        requestAnimationFrame(() => {
            panel.style.transition = 'opacity 0.3s ease';
            panel.style.opacity = '1';
        });
    }
    if (btn) btn.classList.add('active');
}

// ── Scroll Reveal (fade-in on scroll) ───────────────────
document.addEventListener('DOMContentLoaded', () => {
    const revealEls = document.querySelectorAll('.gen-block, .overview-card, .car-card, .legacy-tile, .stat-item');
    if (!revealEls.length) return;

    // Trigger first tab if present
    const firstTab = document.querySelector('.page-tabs a');
    if (firstTab) firstTab.click();

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