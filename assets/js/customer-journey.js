async function loadCustomerJourneyState() {
    if (window.customerJourneyState.loaded) return window.customerJourneyState;
    try {
        const res = await fetch(`${getBasePath()}api/customer-journey`, { headers: { Accept: 'application/json' } });
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

    const res = await fetch(`${getBasePath()}api/customer-journey`, {
        method: 'POST',
        body,
        headers: { Accept: 'application/json' },
    });
    const data = await res.json();
    if (data.saved_ids) window.customerJourneyState.savedIds = data.saved_ids.map(String);
    if (data.compare_ids) window.customerJourneyState.compareIds = data.compare_ids.map(String);
    return data;
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
        <a href="${getBasePath()}compare">View</a>
        <button type="button" data-clear-compare>Clear</button>`;
}

async function getInventoryVehicles() {
    if (getInventoryVehicles.cache) return getInventoryVehicles.cache;
    getInventoryVehicles.cache = fetch(`${getBasePath()}api/vehicles`)
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
    const base = getBasePath();
    const title = `${vehicle.brand_name || ''} ${vehicle.model || ''}`.trim();
    const inquirySubject = encodeURIComponent(`${type === 'appointment' ? 'Appointment: view' : 'Inquiry:'} ${title}`);
    const image = vehicleImageSrc(vehicle);
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
            const base = getBasePath();
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

