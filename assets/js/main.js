/**
 * Own It & Drive It Frontend JavaScript
 * Theme toggle, mobile nav, AJAX filter, contact form,
 * back-to-top, tab switching, scroll reveal, and lightbox.
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
        btn.dataset.mode = mode;
        const label = btn.querySelector('.theme-label');
        if (label) label.textContent = mode === 'light' ? 'Day' : 'Night';
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
// Mobile filter sheet
(function initFilterSheet() {
    const filterSheet = document.getElementById('inventoryFilters');
    const openBtn = document.querySelector('[data-filter-open]');
    const closeTargets = document.querySelectorAll('[data-filter-close]');
    const backdrop = document.querySelector('.filter-backdrop');
    if (!filterSheet || !openBtn) return;

    function setOpen(open) {
        filterSheet.classList.toggle('is-open', open);
        document.body.classList.toggle('filters-open', open);
        openBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        filterSheet.setAttribute('aria-hidden', open ? 'false' : 'true');
        filterSheet.toggleAttribute('inert', !open);
        if (backdrop) backdrop.hidden = !open;
    }

    openBtn.addEventListener('click', () => setOpen(true));
    closeTargets.forEach(target => target.addEventListener('click', () => setOpen(false)));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') setOpen(false);
    });
    setOpen(false);
})();

function showUiNotice(message) {
    let notice = document.querySelector('.ui-notice');
    if (!notice) {
        notice = document.createElement('div');
        notice.className = 'ui-notice';
        notice.setAttribute('role', 'status');
        document.body.appendChild(notice);
    }
    notice.textContent = message;
    notice.classList.add('visible');
    window.clearTimeout(showUiNotice.timer);
    showUiNotice.timer = window.setTimeout(() => {
        notice.classList.remove('visible');
    }, 2400);
}

function getVehicleLabel(control) {
    const scope = control.closest('.car-card, .vehicle-detail, .vehicle-detail-grid, .similar-card') || document;
    return scope.querySelector('h1, h3')?.textContent?.trim()
        || document.querySelector('h1')?.textContent?.trim()
        || `Vehicle ${control.dataset.saveId || control.dataset.compareId}`;
}

function getStoredIds(key) {
    if (window.customerJourneyState?.loaded && window.customerJourneyState.loggedIn) {
        if (key === 'savedVehicles') return window.customerJourneyState.savedIds.map(String);
        if (key === 'compareVehicles') return window.customerJourneyState.compareIds.map(String);
    }
    try {
        return JSON.parse(localStorage.getItem(key) || '[]');
    } catch {
        return [];
    }
}

function setStoredIds(key, ids) {
    if (window.customerJourneyState?.loaded && window.customerJourneyState.loggedIn) {
        if (key === 'savedVehicles') window.customerJourneyState.savedIds = ids.map(String);
        if (key === 'compareVehicles') window.customerJourneyState.compareIds = ids.map(String);
        return;
    }
    localStorage.setItem(key, JSON.stringify(ids));
}

window.customerJourneyState = {
    loaded: false,
    loggedIn: false,
    savedIds: [],
    compareIds: [],
};

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

async function loadCustomerJourneyState() {
    if (window.customerJourneyState.loaded) return window.customerJourneyState;
    const base = window.BASE_PATH || document.querySelector('[data-profile-dashboard]')?.dataset.basePath || '/';
    try {
        const res = await fetch(`${base}api/customer-journey`, { headers: { Accept: 'application/json' } });
        const data = await res.json();
        window.customerJourneyState.loaded = true;
        window.customerJourneyState.loggedIn = Boolean(data.logged_in);
        window.customerJourneyState.savedIds = (data.saved_ids || []).map(String);
        window.customerJourneyState.compareIds = (data.compare_ids || []).map(String);
    } catch {
        window.customerJourneyState.loaded = true;
        window.customerJourneyState.loggedIn = false;
    }
    return window.customerJourneyState;
}

async function postCustomerJourney(action, payload = {}) {
    await loadCustomerJourneyState();
    if (!window.customerJourneyState.loggedIn) {
        showUiNotice('Log in or create an account to save and compare vehicles.');
        return { success: false, requires_login: true };
    }

    const base = window.BASE_PATH || document.querySelector('[data-profile-dashboard]')?.dataset.basePath || '/';
    const body = new FormData();
    body.append('action', action);
    body.append('csrf_token', getCsrfToken());

    Object.entries(payload).forEach(([key, value]) => {
        if (Array.isArray(value)) {
            value.forEach(item => body.append(`${key}[]`, item));
        } else {
            body.append(key, value);
        }
    });

    const res = await fetch(`${base}api/customer-journey`, {
        method: 'POST',
        body,
        headers: { Accept: 'application/json' },
    });
    const data = await res.json();
    if (data.saved_ids) window.customerJourneyState.savedIds = data.saved_ids.map(String);
    if (data.compare_ids) window.customerJourneyState.compareIds = data.compare_ids.map(String);
    return data;
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = String(str ?? '');
    return div.innerHTML;
}

function syncSavedButtons() {
    const saved = new Set(getStoredIds('savedVehicles'));
    document.querySelectorAll('[data-save-id]').forEach(btn => {
        const isSaved = saved.has(btn.dataset.saveId);
        btn.classList.toggle('is-saved', isSaved);
        btn.setAttribute('aria-pressed', isSaved ? 'true' : 'false');
        btn.textContent = isSaved ? 'Saved' : 'Save';
    });
}

function renderCompareTray() {
    const ids = getStoredIds('compareVehicles');
    let tray = document.querySelector('.compare-tray');
    if (!ids.length) {
        tray?.remove();
        return;
    }

    if (!tray) {
        tray = document.createElement('aside');
        tray.className = 'compare-tray';
        tray.setAttribute('aria-live', 'polite');
        document.body.appendChild(tray);
    }

    tray.innerHTML = `<strong>Compare</strong>
        <span>${ids.length}/3 selected</span>
        <a href="${window.BASE_PATH || '/'}compare">View</a>
        <button type="button" data-clear-compare>Clear</button>`;
}

async function getInventoryVehicles() {
    if (getInventoryVehicles.cache) return getInventoryVehicles.cache;
    const base = window.BASE_PATH || document.querySelector('[data-profile-dashboard]')?.dataset.basePath || '/';
    getInventoryVehicles.cache = fetch(`${base}api/vehicles`)
        .then(res => res.json())
        .then(data => Array.isArray(data.vehicles) ? data.vehicles : [])
        .catch(() => []);
    return getInventoryVehicles.cache;
}

function formatVehiclePrice(price) {
    const value = Number(price || 0);
    return value > 0 ? `KES ${value.toLocaleString()}` : 'Price on inquiry';
}

function profileVehicleCard(vehicle, type) {
    const base = window.BASE_PATH || document.querySelector('[data-profile-dashboard]')?.dataset.basePath || '/';
    const title = `${vehicle.brand_name || ''} ${vehicle.model || ''}`.trim();
    const inquirySubject = encodeURIComponent(`${type === 'appointment' ? 'Appointment: view' : 'Inquiry:'} ${title}`);
    const image = escapeHtml(vehicle.image_url_resolved || `${base}${vehicle.image_url || ''}`);
    const titleHtml = escapeHtml(title || `Vehicle ${vehicle.id}`);
    const branch = 'Nairobi Showroom';

    return `<article class="profile-vehicle-card">
        <img src="${image}" alt="${titleHtml}" loading="lazy">
        <div>
            <span>${escapeHtml(vehicle.year || '')} / ${escapeHtml(branch)}</span>
            <h3>${titleHtml}</h3>
            <p>${escapeHtml(formatVehiclePrice(vehicle.price))}</p>
            <div class="profile-card-actions">
                <a href="${base}vehicle/${escapeHtml(vehicle.id)}">View</a>
                <a href="${base}contact?subject=${inquirySubject}">Book viewing</a>
            </div>
        </div>
    </article>`;
}

function renderStoredProfileList(container, vehicles, ids, emptyText, compact = false) {
    if (!container) return;
    const selected = ids
        .map(id => vehicles.find(vehicle => String(vehicle.id) === String(id)))
        .filter(Boolean);

    if (!selected.length) {
        container.innerHTML = `<p class="profile-empty">${emptyText}</p>`;
        return;
    }

    if (compact) {
        container.innerHTML = selected.map(vehicle => {
            const base = window.BASE_PATH || document.querySelector('[data-profile-dashboard]')?.dataset.basePath || '/';
            const title = `${vehicle.brand_name || ''} ${vehicle.model || ''}`.trim();
            return `<a class="profile-compare-item" href="${base}vehicle/${escapeHtml(vehicle.id)}">
                <span>${escapeHtml(title)}</span>
                <strong>${escapeHtml(formatVehiclePrice(vehicle.price))}</strong>
            </a>`;
        }).join('');
        return;
    }

    container.innerHTML = selected.map(vehicle => profileVehicleCard(vehicle, 'appointment')).join('');
}

async function renderProfileDashboard() {
    const dashboard = document.querySelector('[data-profile-dashboard]');
    if (!dashboard) return;

    const savedIds = getStoredIds('savedVehicles');
    const compareIds = getStoredIds('compareVehicles');
    const vehicles = await getInventoryVehicles();
    const availableIds = new Set(vehicles.map(vehicle => String(vehicle.id)));
    const visibleSavedIds = savedIds.filter(id => availableIds.has(String(id)));
    const visibleCompareIds = compareIds.filter(id => availableIds.has(String(id)));

    dashboard.querySelector('[data-profile-saved-count]').textContent = visibleSavedIds.length;
    dashboard.querySelector('[data-profile-compare-count]').textContent = visibleCompareIds.length;
    renderStoredProfileList(
        dashboard.querySelector('[data-profile-saved-list]'),
        vehicles,
        visibleSavedIds,
        'Saved vehicles will appear here after you tap Save in inventory.'
    );
    renderStoredProfileList(
        dashboard.querySelector('[data-profile-compare-list]'),
        vehicles,
        visibleCompareIds,
        'Add up to three cars to compare.',
        true
    );
}

document.addEventListener('click', async (e) => {
    const saveBtn = e.target.closest('[data-save-id]');
    if (saveBtn) {
        const id = saveBtn.dataset.saveId;
        const saved = new Set(getStoredIds('savedVehicles'));
        if (saved.has(id)) {
            const data = await postCustomerJourney('unsave', { vehicle_id: id });
            if (!data.success) return;
            saved.delete(id);
            showUiNotice(data.message || `${getVehicleLabel(saveBtn)} removed from saved vehicles.`);
        } else {
            const data = await postCustomerJourney('save', { vehicle_id: id });
            if (!data.success) return;
            saved.add(id);
            showUiNotice(data.message || `${getVehicleLabel(saveBtn)} saved.`);
        }
        setStoredIds('savedVehicles', Array.from(saved));
        syncSavedButtons();
        renderProfileDashboard();
        return;
    }

    const compareBtn = e.target.closest('[data-compare-id]');
    if (compareBtn) {
        const id = compareBtn.dataset.compareId;
        const selected = getStoredIds('compareVehicles').filter(Boolean);
        let nextSelected = selected;
        if (selected.includes(id)) {
            nextSelected = selected.filter(item => item !== id);
        } else if (selected.length >= 3) {
            showUiNotice('Compare is limited to three vehicles.');
            return;
        } else {
            nextSelected = [...selected, id];
        }
        const data = await postCustomerJourney('compare', { vehicle_ids: nextSelected });
        if (!data.success) return;
        setStoredIds('compareVehicles', nextSelected);
        showUiNotice(data.message || `${getVehicleLabel(compareBtn)} compare list updated.`);
        renderCompareTray();
        renderProfileDashboard();
        return;
    }

    if (e.target.closest('[data-clear-compare]')) {
        if (window.customerJourneyState?.loggedIn) {
            const data = await postCustomerJourney('clear_compare');
            if (!data.success) return;
        } else {
            localStorage.removeItem('compareVehicles');
        }
        renderCompareTray();
        renderProfileDashboard();
        showUiNotice('Compare cleared.');
    }

    if (e.target.closest('[data-clear-saved]')) {
        if (window.customerJourneyState?.loggedIn) {
            const data = await postCustomerJourney('clear_saved');
            if (!data.success) return;
        } else {
            localStorage.removeItem('savedVehicles');
        }
        syncSavedButtons();
        renderProfileDashboard();
        showUiNotice('Saved vehicles cleared.');
    }
});

document.addEventListener('DOMContentLoaded', () => {
    loadCustomerJourneyState().then(() => {
        syncSavedButtons();
        renderCompareTray();
        renderProfileDashboard();
    });
});

// ── Back to Top Button ──────────────────────────────────
const backTop = document.getElementById('back-top');
if (backTop) {
    window.addEventListener('scroll', () => {
        backTop.classList.toggle('visible', window.scrollY > 400);
    }, { passive: true });
}

// ── AJAX Forms ────────────────────────────────────────────
(function initAjaxForms() {
    document.querySelectorAll('[data-ajax-form]').forEach(form => {
        form.removeAttribute('onsubmit');
        form.noValidate = true;
        const resp = form.querySelector('.form-response');
        const btn = form.querySelector('.btn-submit');

        function requiredFieldMessage(input) {
            if (!input) return 'Please fill in all required fields.';
            const name = input.getAttribute('name') || '';
            if (name === 'name' || name === 'customer_name') return 'Please enter your full name.';
            if (name === 'email' || name === 'customer_email') return 'Please enter a valid email address.';
            if (name === 'message') return 'Please enter your message.';
            const label = form.querySelector(`label[for="${input.id}"]`)?.textContent?.trim();
            return label ? `Please enter ${label.toLowerCase()}.` : 'Please fill in all required fields.';
        }

        function showFieldError(input) {
            if (!resp) return;
            resp.textContent = requiredFieldMessage(input);
            resp.className = 'form-response visible error-response';
        }

        form.addEventListener('invalid', (event) => {
            event.preventDefault();
            const firstMissing = form.querySelector(':invalid') || event.target;
            if (firstMissing instanceof HTMLElement) {
                showFieldError(firstMissing);
                firstMissing.focus();
            }
        }, true);

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitLabel = form.dataset.submitLabel || btn?.textContent || 'Submit';
            const requiredFields = (form.dataset.requiredFields || '')
                .split(',')
                .map(field => field.trim())
                .filter(Boolean);

            if (!resp || !btn) return;

            const firstMissing = requiredFields
                .map(field => form.querySelector(`[name="${field}"]`))
                .find(input => !input || !input.value.trim());

            const missing = Boolean(firstMissing);

            if (missing) {
                if (firstMissing instanceof HTMLElement) {
                    showFieldError(firstMissing);
                    firstMissing.focus();
                } else {
                    resp.textContent = 'Please fill in all required fields.';
                    resp.className = 'form-response visible error-response';
                }
                return;
            }

            const invalidField = requiredFields
                .map(field => form.querySelector(`[name="${field}"]`))
                .find(input => input && !input.checkValidity());

            if (invalidField instanceof HTMLElement) {
                showFieldError(invalidField);
                invalidField.focus();
                return;
            }

            const hasMissingRequiredField = requiredFields.some(field => {
                const input = form.querySelector(`[name="${field}"]`);
                return !input || !input.value.trim();
            });

            if (hasMissingRequiredField) {
                resp.textContent = 'Please fill in all required fields.';
                resp.className = 'form-response visible error-response';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Sending...';

            try {
                const fd = new FormData(form);
                const endpoint = form.getAttribute('action');
                const r = await fetch(endpoint, { method: 'POST', body: fd });
                const data = await r.json();
                resp.textContent = data.message || (data.success ? 'Submitted.' : 'Something went wrong.');
                resp.className = 'form-response visible' + (data.success ? '' : ' error-response');
                if (data.success) form.reset();
            } catch {
                resp.textContent = 'Network error. Please try again.';
                resp.className = 'form-response visible error-response';
            }

            btn.disabled = false;
            btn.textContent = submitLabel;
        });
    });
})();

// ── Scroll Reveal (fade-in on scroll) ───────────────────
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

// ── LIVE FILTERING (Inventory) ──────────────────────────
(function initFilter() {
    const container = document.getElementById('inventory-container');
    const filtersBar = document.querySelector('.filters-bar form');
    const resultsCount = document.getElementById('results-count');
    if (!container || !filtersBar) return;

    const searchInput = filtersBar.querySelector('#search');
    const brandSelect = filtersBar.querySelector('#brand');
    const typeSelect = filtersBar.querySelector('#type');
    const fuelSelect = filtersBar.querySelector('#fuel');
    const transmissionSelect = filtersBar.querySelector('#transmission');
    const conditionSelect = filtersBar.querySelector('#condition');
    const branchSelect = filtersBar.querySelector('#branch');
    const minYearInput = filtersBar.querySelector('#min_year');
    const maxYearInput = filtersBar.querySelector('#max_year');
    const minPriceInput = filtersBar.querySelector('#min_price');
    const maxPriceInput = filtersBar.querySelector('#max_price');
    const skeleton = document.getElementById('filter-skeleton');

    function debounce(fn, ms) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), ms);
        };
    }

    function buildQuery() {
        const params = new URLSearchParams();
        if (searchInput?.value) params.set('search', searchInput.value);
        if (brandSelect?.value) params.set('brand', brandSelect.value);
        if (typeSelect?.value) params.set('type', typeSelect.value);
        if (fuelSelect?.value) params.set('fuel', fuelSelect.value);
        if (transmissionSelect?.value) params.set('transmission', transmissionSelect.value);
        if (conditionSelect?.value) params.set('condition', conditionSelect.value);
        if (branchSelect?.value) params.set('branch', branchSelect.value);
        if (minYearInput?.value) params.set('min_year', minYearInput.value);
        if (maxYearInput?.value) params.set('max_year', maxYearInput.value);
        if (minPriceInput?.value) params.set('min_price', minPriceInput.value);
        if (maxPriceInput?.value) params.set('max_price', maxPriceInput.value);
        return params.toString();
    }

    function updateURL(qs) {
        const base = window.BASE_PATH || '/';
        const url = qs ? `${base}inventory?${qs}` : `${base}inventory`;
        history.pushState(null, '', url);
    }

    function showSkeleton() {
        container.innerHTML = '';
        for (let i = 0; i < 6; i++) {
            const div = document.createElement('div');
            div.className = 'car-card';
            div.innerHTML = `<div class="skeleton" style="height:210px;width:100%"></div>
                <div class="card-body">
                    <div class="skeleton" style="height:28px;width:60%;margin-bottom:12px"></div>
                    <div class="skeleton" style="height:14px;width:80%;margin-bottom:6px"></div>
                    <div class="skeleton" style="height:14px;width:50%;margin-bottom:6px"></div>
                    <div class="skeleton" style="height:14px;width:40%"></div>
                </div>`;
            container.appendChild(div);
        }
    }

    function renderVehicles(data) {
        if (!data.vehicles || data.vehicles.length === 0) {
            const base = window.BASE_PATH || '/';
            const recommendations = Array.isArray(data.recommended_vehicles) ? data.recommended_vehicles : [];
            const recommendationCards = recommendations.map((v) => {
                const imgSrc = escapeHtml(v.image_url_resolved || `${base}${v.image_url || ''}`);
                const fallbackImg = `${base}assets/images/products/toyota-land-cruiser-300.jpg`;
                const vehicleUrl = `${base}vehicle/${v.id}`;
                return `<article class="car-card recommendation-card">
                    <img class="card-img" src="${imgSrc}" alt="${escapeHtml(v.model)}" loading="lazy"
                         onerror="this.src='${fallbackImg}'; this.onerror=null;">
                    <div class="card-body">
                        <p class="vehicle-card-brand">${escapeHtml(v.brand_name)}</p>
                        <h3>${escapeHtml(v.model)}</h3>
                        <div class="vehicle-card-meta">
                            <span>${escapeHtml(v.year)}</span>
                            <span>${escapeHtml(v.branch_name || 'Nairobi Showroom')}</span>
                        </div>
                        <div class="vehicle-card-actions">
                            <a href="${vehicleUrl}" class="btn card-link-wrap">View</a>
                        </div>
                    </div>
                </article>`;
            }).join('');
            container.innerHTML = `<div class="empty-state">
                <h3>No Vehicles Found</h3>
                <p>Try adjusting your filters or search terms.</p>
                ${recommendationCards ? `<div class="no-results-recommendations" aria-label="Similar vehicles">
                    <h4>Similar vehicles worth checking</h4>
                    <div class="cards-grid">${recommendationCards}</div>
                </div>` : ''}
            </div>`;
            if (resultsCount) {
                resultsCount.textContent = '0 vehicles found';
                resultsCount.style.display = 'block';
            }
            return;
        }

        let html = '';
        let currentBrand = '';
        data.vehicles.forEach((v, i) => {
            if (currentBrand !== v.brand_name) {
                if (currentBrand !== '') html += '</div></section>';
                currentBrand = v.brand_name;
                const slug = currentBrand.toLowerCase().replace(/\s+/g, '-');
                html += `<section class="brand-section" id="${slug}">
                    <h2>${escapeHtml(currentBrand)} Motors</h2>
                    <div class="cards-grid">`;
            }
            const base = window.BASE_PATH || '/';
            const imgSrc = escapeHtml(v.image_url_resolved || `${base}${v.image_url || ''}`);
            const fallbackImg = `${base}assets/images/products/toyota-land-cruiser-300.jpg`;
            const vehicleUrl = `${base}vehicle/${v.id}`;
            const availabilityUrl = `${base}contact?subject=${encodeURIComponent(`Availability check: ${v.brand_name} ${v.model}`)}`;
            const priceUrl = `${base}contact?subject=${encodeURIComponent(`Best price request: ${v.brand_name} ${v.model}`)}`;
            const finance = v.finance_estimate || {};
            const financeLabel = finance.available ? finance.label : 'Check availability';
            const pricePanel = `<div class="vehicle-card-price-row">
                <span>${escapeHtml(financeLabel)}</span>
                <a href="${priceUrl}">Unlock Best Price</a>
            </div>`;
            const statusLabel = escapeHtml((v.status || 'available').replace(/^\w/, c => c.toUpperCase()));
            const featured = Number(v.is_featured) ? '<span class="vehicle-badge-icon featured" data-icon="VIP" aria-label="Featured" title="Featured"><span class="sr-only">Featured</span></span>' : '';
            html += `<article class="car-card" data-brand="${escapeHtml(v.brand_name)}">
                <div class="vehicle-badges" aria-label="Vehicle status">
                    <span class="vehicle-badge-icon status" data-icon="OK" aria-label="${statusLabel}" title="${statusLabel}"><span class="sr-only">${statusLabel}</span></span>
                    <span class="vehicle-badge-icon inspection" data-icon="IN" aria-label="Inspection-ready" title="Inspection-ready"><span class="sr-only">Inspection-ready</span></span>
                    <span class="vehicle-badge-icon finance" data-icon="KES" aria-label="Finance available" title="Finance available"><span class="sr-only">Finance available</span></span>
                    ${featured}
                </div>
                <img class="card-img" src="${imgSrc}" alt="${escapeHtml(v.model)}" loading="lazy"
                     onerror="this.src='${fallbackImg}'; this.onerror=null;">
                <div class="card-body">
                    <p class="vehicle-card-brand">${escapeHtml(v.brand_name)}</p>
                    <h3>${escapeHtml(v.model)}</h3>
                    <div class="vehicle-card-meta">
                        <span>${escapeHtml(v.year)}</span>
                        <span>${escapeHtml(v.branch_name || 'Nairobi Showroom')}</span>
                        ${v.vehicle_type ? `<span>${escapeHtml(v.vehicle_type)}</span>` : ''}
                    </div>
                    ${pricePanel}
                    <div class="vehicle-card-actions">
                        <a href="${vehicleUrl}" class="btn card-link-wrap">View</a>
                        <a href="${availabilityUrl}" class="btn-outline">Check Availability</a>
                    </div>
                    <div class="vehicle-card-tools" aria-label="Vehicle shortlist actions">
                        <button type="button" class="btn-outline compact-action" data-save-id="${escapeHtml(v.id)}">Save</button>
                        <button type="button" class="btn-outline compact-action" data-compare-id="${escapeHtml(v.id)}">Compare</button>
                    </div>
                </div>
            </article>`;
        });
        html += '</div></section>';
        container.innerHTML = html;
        syncSavedButtons();

        if (resultsCount) {
            const hasFilters = data.filters.search || data.filters.brand || data.filters.type || data.filters.fuel || data.filters.transmission || data.filters.condition || data.filters.branch || data.filters.min_year || data.filters.max_year || data.filters.min_price || data.filters.max_price;
            if (hasFilters) {
                resultsCount.textContent = `${data.count} vehicle${data.count !== 1 ? 's' : ''} found`;
                resultsCount.style.display = 'block';
            } else {
                resultsCount.style.display = 'none';
            }
        }
    }

    async function fetchAndRender() {
        const qs = buildQuery();
        updateURL(qs);
        showSkeleton();
        try {
            const base = window.BASE_PATH || '/';
            const res = await fetch(`${base}api/vehicles?${qs}`);
            const data = await res.json();
            renderVehicles(data);
        } catch {
            container.innerHTML = '<div class="empty-state"><h3>Error loading vehicles</h3><p>Please try again.</p></div>';
        }
    }

    const debouncedFetch = debounce(fetchAndRender, 300);

    searchInput?.addEventListener('input', debouncedFetch);
    brandSelect?.addEventListener('change', debouncedFetch);
    typeSelect?.addEventListener('change', debouncedFetch);
    fuelSelect?.addEventListener('change', debouncedFetch);
    transmissionSelect?.addEventListener('change', debouncedFetch);
    conditionSelect?.addEventListener('change', debouncedFetch);
    branchSelect?.addEventListener('change', debouncedFetch);
    minYearInput?.addEventListener('input', debouncedFetch);
    maxYearInput?.addEventListener('input', debouncedFetch);
    minPriceInput?.addEventListener('input', debouncedFetch);
    maxPriceInput?.addEventListener('input', debouncedFetch);

    // Preserve URL params on load
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.toString()) {
        if (searchInput && urlParams.has('search')) searchInput.value = urlParams.get('search');
        if (brandSelect && urlParams.has('brand')) brandSelect.value = urlParams.get('brand');
        if (typeSelect && urlParams.has('type')) typeSelect.value = urlParams.get('type');
        if (fuelSelect && urlParams.has('fuel')) fuelSelect.value = urlParams.get('fuel');
        if (transmissionSelect && urlParams.has('transmission')) transmissionSelect.value = urlParams.get('transmission');
        if (conditionSelect && urlParams.has('condition')) conditionSelect.value = urlParams.get('condition');
        if (branchSelect && urlParams.has('branch')) branchSelect.value = urlParams.get('branch');
        if (minYearInput && urlParams.has('min_year')) minYearInput.value = urlParams.get('min_year');
        if (maxYearInput && urlParams.has('max_year')) maxYearInput.value = urlParams.get('max_year');
        if (minPriceInput && urlParams.has('min_price')) minPriceInput.value = urlParams.get('min_price');
        if (maxPriceInput && urlParams.has('max_price')) maxPriceInput.value = urlParams.get('max_price');
        fetchAndRender();
    }
})();

// Carousel controls for featured vehicles
(function initCarousels() {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.querySelectorAll('[data-carousel]').forEach(carousel => {
        const track = carousel.querySelector('[data-carousel-track]');
        if (!track) return;

        const items = Array.from(track.children);
        if (items.length <= 1) return;

        let index = 0;
        let timer = null;
        const interval = Number(carousel.dataset.carouselInterval || 5000);
        const progress = carousel.querySelector('[data-carousel-progress]');
        const dotsWrap = carousel.querySelector('[data-carousel-dots]');
        const dots = [];
        let startX = 0;
        let pointerDown = false;

        if (dotsWrap) {
            items.forEach((item, i) => {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.setAttribute('aria-label', `Show featured vehicle ${i + 1}`);
                dot.addEventListener('click', () => {
                    stop();
                    go(i);
                    start();
                });
                dotsWrap.appendChild(dot);
                dots.push(dot);
            });
        }

        function render() {
            track.style.setProperty('--carousel-index', index);
            items.forEach((item, i) => {
                item.classList.toggle('is-active', i === index);
                item.setAttribute('aria-hidden', i === index ? 'false' : 'true');
            });
            dots.forEach((dot, i) => {
                dot.classList.toggle('is-active', i === index);
                dot.setAttribute('aria-current', i === index ? 'true' : 'false');
            });
            if (progress) {
                progress.style.transform = `scaleX(${(index + 1) / items.length})`;
            }
        }

        function go(nextIndex) {
            index = (nextIndex + items.length) % items.length;
            render();
        }

        function start() {
            if (reduceMotion || timer) return;
            timer = window.setInterval(() => go(index + 1), interval);
        }

        function stop() {
            if (!timer) return;
            window.clearInterval(timer);
            timer = null;
        }

        carousel.querySelector('[data-carousel-prev]')?.addEventListener('click', () => {
            stop();
            go(index - 1);
            start();
        });
        carousel.querySelector('[data-carousel-next]')?.addEventListener('click', () => {
            stop();
            go(index + 1);
            start();
        });
        carousel.addEventListener('mouseenter', stop);
        carousel.addEventListener('mouseleave', start);
        carousel.addEventListener('focusin', stop);
        carousel.addEventListener('focusout', start);

        carousel.addEventListener('pointerdown', (e) => {
            pointerDown = true;
            startX = e.clientX;
        });
        carousel.addEventListener('pointerup', (e) => {
            if (!pointerDown) return;
            pointerDown = false;
            const delta = e.clientX - startX;
            if (Math.abs(delta) < 48) return;
            stop();
            go(delta > 0 ? index - 1 : index + 1);
            start();
        });
        carousel.addEventListener('pointercancel', () => {
            pointerDown = false;
        });

        render();
        start();
    });
})();

// Password visibility controls
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-password-toggle]').forEach((btn) => {
        const target = document.getElementById(btn.dataset.passwordToggle);
        if (!target) return;

        btn.addEventListener('click', () => {
            const isVisible = target.type === 'text';
            target.type = isVisible ? 'password' : 'text';
            btn.textContent = isVisible ? 'Show' : 'Hide';
            btn.setAttribute('aria-pressed', isVisible ? 'false' : 'true');
        });
    });
});

(function initLightbox() {
    const gallery = document.querySelector('.vehicle-gallery');
    const mainImg = document.querySelector('.vehicle-detail-media img');
    if (!gallery && !mainImg) return;

    let images = [];
    const galleryImgs = gallery ? gallery.querySelectorAll('img') : [];
    if (galleryImgs.length) {
        images = Array.from(galleryImgs).map(img => img.src);
    } else if (mainImg) {
        images = [mainImg.src];
    }
    if (!images.length) return;

    const lb = document.createElement('div');
    lb.className = 'lightbox';
    lb.innerHTML = `<span class="lightbox-close">&times;</span>
        <div class="lightbox-content">
            <button class="lightbox-prev">&lsaquo;</button>
            <button class="lightbox-next">&rsaquo;</button>
        </div>`;
    document.body.appendChild(lb);

    const lightboxContent = lb.querySelector('.lightbox-content');
    let lbImg = null;
    const closeBtn = lb.querySelector('.lightbox-close');
    const prevBtn = lb.querySelector('.lightbox-prev');
    const nextBtn = lb.querySelector('.lightbox-next');
    let currentIdx = 0;

    function getLightboxImage() {
        if (!lbImg) {
            lbImg = document.createElement('img');
            lbImg.className = 'lightbox-image';
            lbImg.alt = 'Gallery image';
            lightboxContent.insertBefore(lbImg, nextBtn);
        }
        return lbImg;
    }

    function open(idx) {
        currentIdx = idx;
        getLightboxImage().src = images[currentIdx];
        lb.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        updateNav();
    }

    function close() {
        lb.style.display = 'none';
        document.body.style.overflow = '';
    }

    function prev() {
        if (images.length > 1) {
            currentIdx = (currentIdx - 1 + images.length) % images.length;
            getLightboxImage().src = images[currentIdx];
            updateNav();
        }
    }

    function next() {
        if (images.length > 1) {
            currentIdx = (currentIdx + 1) % images.length;
            getLightboxImage().src = images[currentIdx];
            updateNav();
        }
    }

    function updateNav() {
        prevBtn.style.display = images.length > 1 ? 'block' : 'none';
        nextBtn.style.display = images.length > 1 ? 'block' : 'none';
    }

    // Click handlers
    if (galleryImgs.length) {
        galleryImgs.forEach((img, i) => {
            img.style.cursor = 'zoom-in';
            img.addEventListener('click', () => open(i));
        });
    } else if (mainImg) {
        mainImg.style.cursor = 'zoom-in';
        mainImg.addEventListener('click', () => open(0));
    }

    closeBtn.addEventListener('click', close);
    lb.addEventListener('click', (e) => {
        if (e.target === lb) close();
    });
    prevBtn.addEventListener('click', (e) => { e.stopPropagation(); prev(); });
    nextBtn.addEventListener('click', (e) => { e.stopPropagation(); next(); });

    // Keyboard support
    document.addEventListener('keydown', (e) => {
        if (lb.style.display !== 'flex') return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowLeft') prev();
        if (e.key === 'ArrowRight') next();
    });

    // Touch swipe
    let touchStartX = 0;
    lb.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });
    lb.addEventListener('touchend', (e) => {
        const diff = e.changedTouches[0].screenX - touchStartX;
        if (Math.abs(diff) > 50) {
            diff > 0 ? prev() : next();
        }
    }, { passive: true });
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
