/* Inventory: Filtering, Shortlist, and AJAX Loading */
const FALLBACK_VEHICLE_IMAGE = 'assets/images/products/toyota-land-cruiser-300.jpg';

function getBasePath() {
    return window.BASE_PATH || document.querySelector('[data-profile-dashboard]')?.dataset.basePath || '/';
}

function getFallbackVehicleImage() {
    return `${getBasePath()}${FALLBACK_VEHICLE_IMAGE}`;
}

function vehicleImageSrc(vehicle) {
    if (vehicle.image_url_resolved) return escapeHtml(vehicle.image_url_resolved);
    if (vehicle.image_url) return escapeHtml(`${getBasePath()}${vehicle.image_url}`);
    return escapeHtml(getFallbackVehicleImage());
}

function imageFallbackAttr() {
    return `onerror="this.src='${escapeHtml(getFallbackVehicleImage())}'; this.onerror=null;"`;
}

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

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = String(str ?? '');
    return div.innerHTML;
}

