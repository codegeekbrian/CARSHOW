(function initFilterSheet() {
    const filterSheet = document.getElementById('inventoryFilters');
    const openBtn = document.querySelector('[data-filter-open]');
    const closeTargets = document.querySelectorAll('[data-filter-close]');
    const backdrop = document.querySelector('.filter-backdrop');
    const mobileQuery = window.matchMedia('(max-width: 900px)');
    if (!filterSheet || !openBtn) return;

    function setOpen(open) {
        const isMobile = mobileQuery.matches;
        if (isMobile && !open && filterSheet.contains(document.activeElement)) {
            openBtn.focus({ preventScroll: true });
        }
        filterSheet.classList.toggle('is-open', open);
        document.body.classList.toggle('filters-open', isMobile && open);
        openBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (isMobile) {
            filterSheet.setAttribute('aria-hidden', open ? 'false' : 'true');
            filterSheet.toggleAttribute('inert', !open);
        } else {
            filterSheet.removeAttribute('aria-hidden');
            filterSheet.removeAttribute('inert');
        }
        if (backdrop) backdrop.hidden = !(isMobile && open);
    }

    openBtn.addEventListener('click', () => {
        if (!mobileQuery.matches) {
            filterSheet.scrollIntoView({ behavior: 'smooth', block: 'start' });
            filterSheet.querySelector('input, select, button, a')?.focus({ preventScroll: true });
            return;
        }
        setOpen(true);
    });
    closeTargets.forEach(target => target.addEventListener('click', () => setOpen(false)));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') setOpen(false);
    });
    mobileQuery.addEventListener('change', () => {
        setOpen(filterSheet.classList.contains('is-open'));
    });
    setOpen(false);
})();

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
        const base = getBasePath();
        const url = qs ? `${base}inventory?${qs}` : `${base}inventory`;
        history.pushState(null, '', url);
    }

    function showSkeleton() {
        container.innerHTML = '';
        for (let i = 0; i < 6; i++) {
            const div = document.createElement('div');
            div.className = 'car-card skeleton-card';
            div.innerHTML = `<div class="skeleton skeleton-media"></div>
                <div class="card-body">
                    <div class="skeleton skeleton-title"></div>
                    <div class="skeleton skeleton-line"></div>
                    <div class="skeleton skeleton-line skeleton-line-short"></div>
                    <div class="skeleton skeleton-line skeleton-line-tiny"></div>
                </div>`;
            container.appendChild(div);
        }
    }

    function renderVehicles(data) {
        if (!data.vehicles || data.vehicles.length === 0) {
            const base = getBasePath();
            const recommendations = Array.isArray(data.recommended_vehicles) ? data.recommended_vehicles : [];
            const recommendationCards = recommendations.map((v) => {
                const imgSrc = vehicleImageSrc(v);
                const vehicleUrl = `${base}vehicle/${v.id}`;
                return `<article class="car-card recommendation-card">
                    <img class="card-img" src="${imgSrc}" alt="${escapeHtml(v.model)}" loading="lazy"
                         ${imageFallbackAttr()}>
                    <div class="card-body">
                        <p class="vehicle-card-brand">${escapeHtml(v.brand_name)}</p>
                        <h3>${escapeHtml(v.model)}</h3>
                        <div class="vehicle-card-meta">
                            <span>${escapeHtml(v.year)}</span>
                            <span>${escapeHtml(v.branch_name || 'Nairobi Showroom')}</span>
                        </div>
                        <div class="vehicle-card-actions">
                            <a href="${vehicleUrl}" class="btn card-link-wrap">View details</a>
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
            const base = getBasePath();
            const imgSrc = vehicleImageSrc(v);
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
                     ${imageFallbackAttr()}>
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
                        <a href="${vehicleUrl}" class="btn card-link-wrap">View details</a>
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
            const res = await fetch(`${getBasePath()}api/vehicles?${qs}`);
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

