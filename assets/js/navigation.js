/* Navigation: Mobile and Dropdowns */
function closeNavDropdowns() {
    document.querySelectorAll('.nav-dropdown.open').forEach(item => {
        item.classList.remove('open');
        item.querySelector('.dropdown-trigger')?.setAttribute('aria-expanded', 'false');
    });
}

function syncMobileNavState(open) {
    const links = document.getElementById('navLinks');
    const toggle = document.getElementById('navToggle');
    const isMobile = window.matchMedia('(max-width: 900px)').matches;

    toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle?.setAttribute('aria-label', open ? 'Close main menu' : 'Open main menu');

    if (!links) return;
    if (isMobile) {
        links.setAttribute('aria-hidden', open ? 'false' : 'true');
        links.toggleAttribute('inert', !open);
    } else {
        links.removeAttribute('aria-hidden');
        links.removeAttribute('inert');
    }
}

function setMobileNav(open) {
    const links = document.getElementById('navLinks');
    const toggle = document.getElementById('navToggle');
    const backdrop = document.getElementById('navBackdrop');

    if (!open && links?.contains(document.activeElement)) {
        toggle?.focus({ preventScroll: true });
    }

    links?.classList.toggle('open', open);
    toggle?.classList.toggle('open', open);
    toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
    backdrop?.classList.toggle('is-open', open);
    if (backdrop) backdrop.hidden = !open;
    document.body.classList.toggle('nav-open', open);
    syncMobileNavState(open);

    if (!open) closeNavDropdowns();
}

function toggleNav() {
    const links = document.getElementById('navLinks');
    setMobileNav(!links?.classList.contains('open'));
}

document.getElementById('navToggle')?.addEventListener('click', toggleNav);
syncMobileNavState(false);
window.addEventListener('resize', () => {
    const links = document.getElementById('navLinks');
    syncMobileNavState(Boolean(links?.classList.contains('open')));
});

document.querySelectorAll('.nav-dropdown .dropdown-trigger').forEach(trigger => {
    trigger.addEventListener('click', (e) => {
        e.preventDefault();
        const dropdown = trigger.closest('.nav-dropdown');
        const willOpen = !dropdown?.classList.contains('open');
        closeNavDropdowns();
        dropdown?.classList.toggle('open', willOpen);
        trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });
});

document.addEventListener('click', (e) => {
    if (e.target.closest('.nav-dropdown')) return;
    closeNavDropdowns();
});

document.getElementById('navBackdrop')?.addEventListener('click', () => {
    setMobileNav(false);
});

document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    setMobileNav(false);
});

document.querySelectorAll('.navbar-links a').forEach(a => {
    a.addEventListener('click', () => {
        if (a.classList.contains('dropdown-trigger')) return;
        setMobileNav(false);
    });
});
