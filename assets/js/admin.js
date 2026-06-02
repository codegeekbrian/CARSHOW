(function initAdminShell() {
    const nav = document.getElementById('adminNav');
    const toggle = document.querySelector('[data-admin-menu-toggle]');
    const backdrop = document.querySelector('[data-admin-menu-close]');
    const accountMenu = document.querySelector('.admin-account-menu');
    const themeToggle = document.querySelector('[data-admin-theme-toggle]');
    const themeLabel = document.querySelector('[data-admin-theme-label]');
    const mobileQuery = window.matchMedia('(max-width: 820px)');
    let menuScrollY = 0;
    let collapseTimer = null;

    if (!nav || !toggle) return;

    function applyTheme(theme) {
        const nextTheme = theme === 'day' ? 'day' : 'night';
        document.documentElement.dataset.adminTheme = nextTheme;
        document.body.dataset.adminTheme = nextTheme;
        themeToggle?.setAttribute('aria-pressed', nextTheme === 'day' ? 'true' : 'false');
        themeToggle?.setAttribute('aria-label', nextTheme === 'day' ? 'Switch to night mode' : 'Switch to day mode');
        if (themeLabel) themeLabel.textContent = nextTheme === 'day' ? 'Day' : 'Night';
    }

    applyTheme(localStorage.getItem('admin-theme') || document.documentElement.dataset.adminTheme || 'night');
    themeToggle?.addEventListener('click', () => {
        const nextTheme = document.documentElement.dataset.adminTheme === 'day' ? 'night' : 'day';
        localStorage.setItem('admin-theme', nextTheme);
        applyTheme(nextTheme);
    });

    function isDesktop() {
        return window.innerWidth > 820;
    }

    function closeNavGroups() {
        nav.querySelectorAll('details.admin-nav-group[open]').forEach((group) => {
            group.removeAttribute('open');
        });
    }

    function closeAccountMenu() {
        accountMenu?.removeAttribute('open');
    }

    function setNavInteractivity(open) {
        const hiddenFromMobile = mobileQuery.matches && !open;
        nav.setAttribute('aria-hidden', hiddenFromMobile ? 'true' : 'false');
        if ('inert' in nav) {
            nav.inert = hiddenFromMobile;
        }
    }

    function focusFirstNavItem() {
        const firstItem = nav.querySelector('a, summary, button, [tabindex]:not([tabindex="-1"])');
        if (firstItem instanceof HTMLElement) {
            firstItem.focus({ preventScroll: true });
        }
    }

    function setOpen(open, options = {}) {
        nav.classList.toggle('is-open', open);
        toggle.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        setNavInteractivity(open);
        document.body.classList.toggle('admin-menu-open', open);
        if (open) {
            menuScrollY = window.scrollY || document.documentElement.scrollTop || 0;
            document.body.style.top = `-${menuScrollY}px`;
            window.setTimeout(focusFirstNavItem, 80);
        } else {
            document.body.style.top = '';
            if (menuScrollY) {
                window.scrollTo(0, menuScrollY);
            }
            if (options.restoreFocus) {
                toggle.focus({ preventScroll: true });
            }
        }
        if (backdrop) {
            backdrop.hidden = !open;
            backdrop.classList.toggle('is-open', open);
        }
    }

    toggle.addEventListener('click', () => setOpen(!nav.classList.contains('is-open')));
    backdrop?.addEventListener('click', () => setOpen(false));
    nav.querySelectorAll('details.admin-nav-group').forEach((group) => {
        const summary = group.querySelector('summary');
        if (!summary) return;

        summary.addEventListener('click', () => {
            window.clearTimeout(collapseTimer);
            closeAccountMenu();
            nav.querySelectorAll('details.admin-nav-group[open]').forEach((openGroup) => {
                if (openGroup !== group) openGroup.removeAttribute('open');
            });
        });
    });

    nav.addEventListener('mouseleave', () => {
        if (!isDesktop()) return;
        collapseTimer = window.setTimeout(closeNavGroups, 220);
    });

    nav.addEventListener('mouseenter', () => {
        window.clearTimeout(collapseTimer);
    });

    accountMenu?.addEventListener('toggle', () => {
        if (accountMenu.open) closeNavGroups();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false, { restoreFocus: nav.classList.contains('is-open') });
            closeNavGroups();
            closeAccountMenu();
        }
    });
    document.addEventListener('click', (event) => {
        if (!isDesktop()) return;
        const target = event.target;
        if (!(target instanceof Element)) return;
        if (!nav.contains(target)) closeNavGroups();
        if (accountMenu && !accountMenu.contains(target)) closeAccountMenu();
    });
    nav.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            setOpen(false);
            closeNavGroups();
        });
    });
    window.addEventListener('resize', () => {
        if (window.innerWidth > 820) setOpen(false);
        setNavInteractivity(nav.classList.contains('is-open'));
        closeNavGroups();
        closeAccountMenu();
    });
    setNavInteractivity(false);
})();

document.querySelectorAll('[data-bulk-form]').forEach((form) => {
    const selectAll = document.querySelector('[data-bulk-select-all]');
    const items = Array.from(document.querySelectorAll(`[form="${form.id}"][data-bulk-item]`));
    if (!selectAll || !items.length) return;

    function syncSelectAll() {
        const checkedCount = items.filter((item) => item.checked).length;
        selectAll.checked = checkedCount === items.length;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < items.length;
    }

    selectAll.addEventListener('change', () => {
        items.forEach((item) => {
            item.checked = selectAll.checked;
        });
        syncSelectAll();
    });

    items.forEach((item) => item.addEventListener('change', syncSelectAll));
    syncSelectAll();
});

const adminModalTriggers = new WeakMap();

function getFocusableElements(container) {
    return Array.from(container.querySelectorAll([
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled]):not([type="hidden"])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])'
    ].join(','))).filter((element) => element instanceof HTMLElement && element.offsetParent !== null);
}

function openAdminModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    const trigger = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    if (trigger) {
        adminModalTriggers.set(modal, trigger);
    }
    modal.setAttribute('role', modal.getAttribute('role') || 'dialog');
    modal.setAttribute('aria-modal', 'true');
    if (!modal.getAttribute('aria-labelledby')) {
        const heading = modal.querySelector('h2, h3');
        if (heading) {
            if (!heading.id) heading.id = `${id}-title`;
            modal.setAttribute('aria-labelledby', heading.id);
        }
    }
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('admin-modal-open');
    const focusTarget = getFocusableElements(modal)[0] || modal;
    window.setTimeout(() => focusTarget.focus({ preventScroll: true }), 40);
}

function closeAdminModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    if (!document.querySelector('.modal.is-open')) {
        document.body.classList.remove('admin-modal-open');
    }
    const trigger = adminModalTriggers.get(modal);
    if (trigger?.isConnected) {
        trigger.focus({ preventScroll: true });
    }
}

document.addEventListener('click', (event) => {
    const modal = event.target.closest('.modal');
    if (modal && event.target === modal) {
        closeAdminModal(modal.id);
    }
});

document.addEventListener('keydown', (event) => {
    const openModal = document.querySelector('.modal.is-open');
    if (!openModal) return;

    if (event.key === 'Escape') {
        closeAdminModal(openModal.id);
        return;
    }

    if (event.key !== 'Tab') return;
    const focusable = getFocusableElements(openModal);
    if (!focusable.length) {
        event.preventDefault();
        openModal.focus({ preventScroll: true });
        return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus({ preventScroll: true });
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus({ preventScroll: true });
    }
});
