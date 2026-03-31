/**
 * My Business - Business registration and management for business accounts
 */

let currentBusinessId = null;
let businessLocationMap = null;
let businessLocationMarker = null;
let geocodingTimeout = null;

// Menu management
let currentMenuBusinessId = null;
let currentMenuItems = [];

// Product management
let currentProductBusinessId = null;
let currentProducts = [];

// Service management
let currentServiceBusinessId = null;
let currentServices = [];

// ── Init ──────────────────────────────────────────────────────────────────────

window.initMyBusinessSection = function () {
    loadBusinessData();
    setTimeout(() => {
        initBusinessLocationMap();
        setupAddressListener();
    }, 100);
};

function loadBusinessData() {
    const form = document.getElementById('businessForm');
    const infoDisplay = document.getElementById('businessInfoDisplay');
    const businessSelector = document.getElementById('businessSelector');
    
    if (!form || !infoDisplay) return;

    fetch('/api/my-businesses', {
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        })
        .then(res => {
            const businesses = res.data || [];
            
            if (businesses.length > 0) {
                // Populate business selector dropdown
                if (businessSelector) {
                    businessSelector.innerHTML = '<option value="">Select a business to manage...</option>';
                    businesses.forEach(b => {
                        const option = document.createElement('option');
                        option.value = b.id;
                        option.textContent = `${b.business_name} (${capitalizeType(b.business_type)})`;
                        businessSelector.appendChild(option);
                    });
                    
                    // Set first business as selected
                    businessSelector.value = businesses[0].id;
                    businessSelector.addEventListener('change', function() {
                        if (this.value) {
                            loadSelectedBusiness(parseInt(this.value));
                        }
                    });
                }
                
                // Load first business
                loadSelectedBusiness(businesses[0].id);
            } else {
                // No businesses yet, show form
                form.style.display = 'block';
                infoDisplay.style.display = 'none';
                if (businessSelector) {
                    businessSelector.style.display = 'none';
                }
            }
        })
        .catch(() => {
            // Show form on error
            form.style.display = 'block';
            infoDisplay.style.display = 'none';
        });
}

function loadSelectedBusiness(businessId) {
    const form = document.getElementById('businessForm');
    
    fetch(`/api/businesses/${businessId}`, {
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => r.json())
        .then(res => {
            const business = res.data;
            if (business) {
                currentBusinessId = business.id;
                
                // Always show form
                form.style.display = 'block';
            }
        })
        .catch(() => {});
}

function capitalizeType(type) {
    const types = {
        'food': '🍔 Food',
        'goods': '🛍️ Goods',
        'services': '🔧 Services'
    };
    return types[type] || type;
}

// ── Address Listener ──────────────────────────────────────────────────────────

function setupAddressListener() {
    const addressInput = document.getElementById('address');
    if (!addressInput) return;

    addressInput.addEventListener('change', function() {
        const address = this.value.trim();
        if (address) {
            geocodeAddress(address);
        }
    });
}

function geocodeAddress(address) {
    // Use OpenStreetMap Nominatim API for geocoding
    const query = encodeURIComponent(address);
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}`)
        .then(r => r.json())
        .then(results => {
            if (results && results.length > 0) {
                const result = results[0];
                const lat = parseFloat(result.lat);
                const lng = parseFloat(result.lon);
                placeBusinessMarker(lat, lng);
            }
        })
        .catch(() => {
            // Silently fail if geocoding doesn't work
        });
}

// ── Business Location Map ─────────────────────────────────────────────────────

function initBusinessLocationMap() {
    const mapContainer = document.getElementById('businessLocationMap');
    if (!mapContainer || businessLocationMap) return;

    // Initialize map centered on Sagay City - disable zoom controls
    businessLocationMap = L.map('businessLocationMap', { zoomControl: false }).setView([10.8967, 123.4253], 13);

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(businessLocationMap);

    // Load existing business location if available
    if (currentBusinessId) {
        fetch(`/api/businesses/${currentBusinessId}`, {
            credentials: 'include',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(r => r.json())
            .then(res => {
                const business = res.data;
                if (business && business.latitude && business.longitude) {
                    placeBusinessMarker(business.latitude, business.longitude);
                }
            })
            .catch(() => {});
    }

    // Click on map to set location
    businessLocationMap.on('click', function(e) {
        placeBusinessMarker(e.latlng.lat, e.latlng.lng);
    });
}

function placeBusinessMarker(lat, lng) {
    // Remove existing marker
    if (businessLocationMarker) {
        businessLocationMap.removeLayer(businessLocationMarker);
    }

    // Add new marker
    businessLocationMarker = L.marker([lat, lng], {
        icon: L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        })
    }).addTo(businessLocationMap).bindPopup('Your Business Location');

    // Update hidden inputs
    document.getElementById('businessLatitude').value = lat;
    document.getElementById('businessLongitude').value = lng;

    // Update location display
    document.getElementById('locationCoords').textContent = `📍 Location set: ${lat.toFixed(4)}, ${lng.toFixed(4)}`;

    // Reverse geocode to get address
    reverseGeocodeLocation(lat, lng);

    // Center map on marker
    businessLocationMap.setView([lat, lng], 15);
}

function reverseGeocodeLocation(lat, lng) {
    // Use OpenStreetMap Nominatim API for reverse geocoding
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
        .then(r => r.json())
        .then(result => {
            if (result && result.address) {
                // Build address from components
                const address = buildAddressFromComponents(result.address);
                if (address) {
                    document.getElementById('address').value = address;
                }
            }
        })
        .catch(() => {
            // Silently fail if reverse geocoding doesn't work
        });
}

function buildAddressFromComponents(addressObj) {
    // Try to build a readable address from the components
    const parts = [];
    
    if (addressObj.road) parts.push(addressObj.road);
    if (addressObj.suburb) parts.push(addressObj.suburb);
    if (addressObj.city) parts.push(addressObj.city);
    if (addressObj.province) parts.push(addressObj.province);
    if (addressObj.postcode) parts.push(addressObj.postcode);
    
    return parts.filter(p => p).join(', ');
}

// ── Form Toggle ───────────────────────────────────────────────────────────────

window.toggleBusinessForm = function () {
    const form = document.getElementById('businessForm');
    const infoDisplay = document.getElementById('businessInfoDisplay');
    
    if (form.style.display === 'none') {
        form.style.display = 'block';
        infoDisplay.style.display = 'none';
        populateFormWithCurrentData();
        setTimeout(() => {
            if (businessLocationMap) {
                businessLocationMap.invalidateSize();
            }
        }, 100);
    } else {
        form.style.display = 'none';
        infoDisplay.style.display = 'block';
    }
};

window.showCreateNewBusinessForm = function () {
    const form = document.getElementById('businessForm');
    const infoDisplay = document.getElementById('businessInfoDisplay');
    
    // Reset form for new business
    form.reset();
    currentBusinessId = null;
    document.getElementById('businessLatitude').value = '';
    document.getElementById('businessLongitude').value = '';
    document.getElementById('locationCoords').textContent = 'Click on the map to set location';
    
    // Clear any existing marker
    if (businessLocationMarker && businessLocationMap) {
        businessLocationMap.removeLayer(businessLocationMarker);
        businessLocationMarker = null;
    }
    
    // Show form, hide info
    form.style.display = 'block';
    infoDisplay.style.display = 'none';
    
    // Refresh map
    setTimeout(() => {
        if (businessLocationMap) {
            businessLocationMap.invalidateSize();
            businessLocationMap.setView([10.8967, 123.4253], 13);
        }
    }, 100);
};

window.cancelBusinessForm = function () {
    const form = document.getElementById('businessForm');
    const infoDisplay = document.getElementById('businessInfoDisplay');
    
    if (currentBusinessId) {
        form.style.display = 'none';
        infoDisplay.style.display = 'block';
    } else {
        form.reset();
    }
};

function populateFormWithCurrentData() {
    if (!currentBusinessId) return;

    fetch(`/api/businesses/${currentBusinessId}`, {
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        })
        .then(res => {
            const business = res.data;
            if (business) {
                document.getElementById('businessName').value = business.name || '';
                document.getElementById('businessType').value = business.category || '';
                document.getElementById('description').value = business.description || '';
                document.getElementById('phone').value = business.phone || '';
                document.getElementById('email').value = business.email || '';
                document.getElementById('address').value = business.address || '';
                
                // Strip seconds from time format (H:i:s -> H:i)
                const openingTime = business.opening_time || '';
                const closingTime = business.closing_time || '';
                document.getElementById('openingTime').value = openingTime ? openingTime.substring(0, 5) : '';
                document.getElementById('closingTime').value = closingTime ? closingTime.substring(0, 5) : '';
                
                document.getElementById('businessLatitude').value = business.latitude || '';
                document.getElementById('businessLongitude').value = business.longitude || '';
                
                // Update location display
                if (business.latitude && business.longitude) {
                    document.getElementById('locationCoords').textContent = `📍 Location set: ${business.latitude.toFixed(4)}, ${business.longitude.toFixed(4)}`;
                }
            }
        })
        .catch(() => {
            // Silently fail
        });
}

// ── Modal Helper ──────────────────────────────────────────────────────────────

function showModal(title, message, type = 'success') {
    // Create modal if it doesn't exist
    let modal = document.getElementById('businessModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'businessModal';
        modal.innerHTML = `
            <div class="modal-overlay">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modalTitle"></h3>
                        <button class="modal-close" onclick="closeModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p id="modalMessage"></p>
                    </div>
                    <div class="modal-footer">
                        <button class="modal-btn" onclick="closeModal()">OK</button>
                    </div>
                </div>
            </div>
            <style>
                #businessModal {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 10000;
                }
                .modal-overlay {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .modal-content {
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
                    max-width: 400px;
                    width: 90%;
                    animation: slideIn 0.3s ease-out;
                }
                @keyframes slideIn {
                    from {
                        transform: translateY(-50px);
                        opacity: 0;
                    }
                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }
                .modal-header {
                    padding: 20px;
                    border-bottom: 1px solid #f0f0f0;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .modal-header h3 {
                    margin: 0;
                    font-size: 18px;
                    font-weight: 700;
                    color: #1a3a52;
                }
                .modal-close {
                    background: none;
                    border: none;
                    font-size: 28px;
                    color: #999;
                    cursor: pointer;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .modal-close:hover {
                    color: #333;
                }
                .modal-body {
                    padding: 20px;
                    color: #666;
                    font-size: 14px;
                    line-height: 1.6;
                }
                .modal-footer {
                    padding: 16px 20px;
                    border-top: 1px solid #f0f0f0;
                    display: flex;
                    justify-content: flex-end;
                    gap: 10px;
                }
                .modal-btn {
                    padding: 10px 24px;
                    border: none;
                    border-radius: 6px;
                    font-size: 13px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s;
                }
                .modal-btn {
                    background: linear-gradient(135deg, #1a3a52 0%, #2c5f8d 100%);
                    color: white;
                }
                .modal-btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(26, 58, 82, 0.2);
                }
                .modal-content.success .modal-header {
                    background: #c8e6c9;
                    border-bottom-color: #a5d6a7;
                }
                .modal-content.success .modal-header h3 {
                    color: #2e7d32;
                }
                .modal-content.error .modal-header {
                    background: #ffcdd2;
                    border-bottom-color: #ef9a9a;
                }
                .modal-content.error .modal-header h3 {
                    color: #c62828;
                }
            </style>
        `;
        document.body.appendChild(modal);
    }
    
    // Update modal content
    const modalContent = modal.querySelector('.modal-content');
    modalContent.className = 'modal-content ' + type;
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalMessage').textContent = message;
    
    // Show modal
    modal.style.display = 'block';
}

function closeModal() {
    const modal = document.getElementById('businessModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// ── Register/Update Business ──────────────────────────────────────────────────

window.registerBusiness = function (e) {
    e.preventDefault();
    const msgEl = document.getElementById('businessMessage');
    msgEl.innerHTML = '';

    const businessName = document.getElementById('businessName').value.trim();
    const businessType = document.getElementById('businessType').value;
    const description = document.getElementById('description').value.trim();
    const phone = document.getElementById('phone').value.trim();
    const email = document.getElementById('email').value.trim();
    const address = document.getElementById('address').value.trim();
    const openingTime = document.getElementById('openingTime').value.trim();
    const closingTime = document.getElementById('closingTime').value.trim();
    const latitude = document.getElementById('businessLatitude').value;
    const longitude = document.getElementById('businessLongitude').value;

    if (!businessName || !businessType || !phone || !email || !address) {
        msgEl.innerHTML = '<div class="message error">Please fill in all required fields.</div>';
        return;
    }

    // Validate phone number - must be exactly 11 digits
    if (!/^\d{11}$/.test(phone)) {
        msgEl.innerHTML = '<div class="message error">Phone number must be exactly 11 digits (e.g., 09123456789).</div>';
        return;
    }

    if (!latitude || !longitude) {
        msgEl.innerHTML = '<div class="message error">Please set your business location on the map.</div>';
        return;
    }

    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg> Saving...';

    const payload = {
        business_name: businessName,
        business_type: businessType,
        description: description || null,
        phone: phone,
        email: email,
        address: address,
        opening_time: openingTime ? openingTime + ':00' : null,
        closing_time: closingTime ? closingTime + ':00' : null,
        latitude: parseFloat(latitude),
        longitude: parseFloat(longitude),
    };

    const method = currentBusinessId ? 'PUT' : 'POST';
    const url = currentBusinessId ? `/api/businesses/${currentBusinessId}` : '/api/businesses';

    fetch(url, {
        method: method,
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
    })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            
            if (res.success) {
                const successMsg = 'Business ' + (currentBusinessId ? 'updated' : 'registered') + ' successfully!';
                msgEl.innerHTML = '<div class="message success">✓ ' + successMsg + '</div>';
                showModal('Success', successMsg, 'success');
                currentBusinessId = res.data.id;
                setTimeout(() => {
                    loadBusinessData();
                    // Show business selector if multiple businesses
                    const selector = document.getElementById('businessSelectorContainer');
                    if (selector) {
                        selector.style.display = 'block';
                    }
                    msgEl.innerHTML = '';
                }, 3000);
            } else {
                const errorMsg = res.message || 'Failed to save business';
                msgEl.innerHTML = `<div class="message error">✗ ${errorMsg}</div>`;
                showModal('Error', errorMsg, 'error');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            const errorMsg = 'Network error. Please try again.';
            msgEl.innerHTML = '<div class="message error">✗ ' + errorMsg + '</div>';
            showModal('Error', errorMsg, 'error');
        });
};

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// ── Manage Business Modal ─────────────────────────────────────────────────────

window.openManageBusinessModal = function () {
    const modal = document.getElementById('manageBusinessModal');
    const selector = document.getElementById('manageBusinessSelector');
    
    if (!modal || !selector) return;
    
    // Load businesses
    fetch('/api/my-businesses', {
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => r.json())
        .then(res => {
            let businesses = [];
            
            // Handle paginated response
            if (res.data && res.data.data) {
                businesses = res.data.data;
            } else if (Array.isArray(res.data)) {
                businesses = res.data;
            }
            
            selector.innerHTML = '<option value="">Choose a business...</option>';
            
            if (businesses.length === 0) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'No businesses found';
                option.disabled = true;
                selector.appendChild(option);
            } else {
                businesses.forEach(b => {
                    const option = document.createElement('option');
                    option.value = b.id;
                    option.dataset.businessType = b.business_type || b.category || '';
                    const businessType = b.business_type || b.category || 'Unknown';
                    const typeLabel = capitalizeType(businessType);
                    option.textContent = `${b.business_name || b.name} (${typeLabel})`;
                    selector.appendChild(option);
                });
            }
            
            selector.addEventListener('change', function() {
                const actions = document.getElementById('manageBusinessActions');
                if (this.value) {
                    actions.style.display = 'flex';
                    updateActionButtons(this);
                } else {
                    actions.style.display = 'none';
                }
            });
            
            modal.style.display = 'block';
        })
        .catch(err => {
            console.error('Error loading businesses:', err);
            alert('Error loading businesses');
        });
};

function updateActionButtons(selectElement) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const businessType = selectedOption.dataset.businessType || '';
    
    const addMenuBtn = document.getElementById('addMenuBtn');
    const addProductBtn = document.getElementById('addProductBtn');
    const addServiceBtn = document.getElementById('addServiceBtn');
    
    // Show/hide buttons based on business type
    if (addMenuBtn) {
        addMenuBtn.style.display = businessType === 'food' ? 'flex' : 'none';
    }
    if (addProductBtn) {
        addProductBtn.style.display = businessType === 'goods' ? 'flex' : 'none';
    }
    if (addServiceBtn) {
        addServiceBtn.style.display = businessType === 'services' ? 'flex' : 'none';
    }
}

window.closeManageBusinessModal = function () {
    const modal = document.getElementById('manageBusinessModal');
    if (modal) {
        modal.style.display = 'none';
    }
};

window.editSelectedBusiness = function () {
    const selector = document.getElementById('manageBusinessSelector');
    const businessId = selector.value;
    
    if (!businessId) {
        alert('Please select a business');
        return;
    }
    
    // Load business data and populate form
    fetch(`/api/businesses/${businessId}`, {
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => r.json())
        .then(res => {
            const business = res.data;
            if (business) {
                currentBusinessId = business.id;
                
                // Populate form with business data
                document.getElementById('businessName').value = business.business_name || '';
                
                // Set business type dropdown and disable it
                const businessTypeSelect = document.getElementById('businessType');
                const businessType = business.business_type || business.category || '';
                businessTypeSelect.value = businessType;
                businessTypeSelect.disabled = true;
                
                document.getElementById('description').value = business.description || '';
                document.getElementById('phone').value = business.phone || '';
                document.getElementById('email').value = business.email || '';
                document.getElementById('address').value = business.address || '';
                
                // Strip seconds from time format (H:i:s -> H:i)
                const openingTime = business.opening_time || '';
                const closingTime = business.closing_time || '';
                document.getElementById('openingTime').value = openingTime ? openingTime.substring(0, 5) : '';
                document.getElementById('closingTime').value = closingTime ? closingTime.substring(0, 5) : '';
                
                if (business.latitude && business.longitude) {
                    document.getElementById('businessLatitude').value = business.latitude;
                    document.getElementById('businessLongitude').value = business.longitude;
                    placeBusinessMarker(business.latitude, business.longitude);
                }
                
                // Close modal and scroll to form
                closeManageBusinessModal();
                document.getElementById('businessForm').scrollIntoView({ behavior: 'smooth' });
            }
        })
        .catch(err => {
            console.error('Error loading business:', err);
            alert('Error loading business details');
        });
};

window.manageMenuItems = function () {
    const selector = document.getElementById('manageBusinessSelector');
    const businessId = selector.value;
    
    if (!businessId) {
        alert('Please select a business');
        return;
    }
    
    // Navigate to businesses section with menu management
    showSection('businesses');
    closeManageBusinessModal();
};

// ── Menu Management Modals ────────────────────────────────────────────────────

window.openMenuListModal = function () {
    const selector = document.getElementById('manageBusinessSelector');
    const businessId = selector.value;
    
    if (!businessId) {
        alert('Please select a business');
        return;
    }
    
    currentMenuBusinessId = businessId;
    loadMenuItems(businessId);
    document.getElementById('menuListModal').style.display = 'block';
};

window.closeMenuListModal = function () {
    document.getElementById('menuListModal').style.display = 'none';
};

window.openAddMenuItemModal = function () {
    document.getElementById('addMenuItemForm').reset();
    document.getElementById('imagePreview').style.display = 'none';
    document.getElementById('addMenuItemModal').style.display = 'block';
};

window.closeAddMenuItemModal = function () {
    document.getElementById('addMenuItemModal').style.display = 'none';
};

function loadMenuItems(businessId) {
    fetch(`/api/businesses/${businessId}/menu-items`, {
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => r.json())
        .then(res => {
            // Handle paginated response
            if (res.data && res.data.data) {
                currentMenuItems = res.data.data;
            } else if (Array.isArray(res.data)) {
                currentMenuItems = res.data;
            } else {
                currentMenuItems = [];
            }
            displayMenuItems();
        })
        .catch(err => {
            console.error('Error loading menu items:', err);
            currentMenuItems = [];
            displayMenuItems();
        });
}

function displayMenuItems() {
    const container = document.getElementById('menuItemsList');
    
    if (currentMenuItems.length === 0) {
        container.innerHTML = '<div style="text-align: center; color: #999; grid-column: 1 / -1; padding: 40px 20px;">No menu items yet</div>';
        return;
    }
    
    container.innerHTML = currentMenuItems.map(item => {
        const imageUrl = item.image_url || item.image;
        
        return `
            <div style="background: #f9f9f9; border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0; transition: all 0.2s; display: flex; flex-direction: column;">
                ${imageUrl ? `<img src="${imageUrl}" alt="${escapeHtml(item.name)}" style="width: 100%; height: 150px; object-fit: cover; background: #e0e0e0;">` : `<div style="width: 100%; height: 150px; background: #e0e0e0; display: flex; align-items: center; justify-content: center; color: #999; font-size: 12px;">No Image</div>`}
                <div style="padding: 12px; flex-grow: 1; display: flex; flex-direction: column;">
                    <div style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 4px;">${escapeHtml(item.name)}</div>
                    <div style="font-size: 11px; color: #999; margin-bottom: 6px;">${escapeHtml(item.category || 'N/A')}</div>
                    <div style="color: #00bcd4; font-weight: 700; font-size: 14px; margin-bottom: 8px; margin-top: auto;">₱${parseFloat(item.price).toFixed(2)}</div>
                    <button onclick="deleteMenuItem(${item.id})" style="width: 100%; padding: 6px; background: #ff6b6b; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer; font-weight: 600;">Delete</button>
                </div>
            </div>
        `;
    }).join('');
}

window.submitMenuItemForm = function (e) {
    e.preventDefault();
    
    const name = document.getElementById('menuItemName').value.trim();
    const category = document.getElementById('menuItemCategory').value.trim();
    const price = document.getElementById('menuItemPrice').value;
    const imageFile = document.getElementById('menuItemImage').files[0];
    
    if (!name || !category || !price) {
        alert('Please fill in all required fields');
        return;
    }
    
    const formData = new FormData();
    formData.append('name', name);
    formData.append('category', category);
    formData.append('price', price);
    formData.append('description', '');
    if (imageFile) {
        formData.append('image', imageFile);
    }
    
    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4z"/></svg> Adding...';
    
    fetch(`/api/businesses/${currentMenuBusinessId}/menu-items`, {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
    })
        .then(r => {
            if (!r.ok) {
                return r.json().then(data => {
                    throw new Error(data.message || `HTTP ${r.status}`);
                });
            }
            return r.json();
        })
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            
            if (res.success) {
                showModal('Success', 'Menu item added successfully!', 'success');
                closeAddMenuItemModal();
                loadMenuItems(currentMenuBusinessId);
            } else {
                showModal('Error', res.message || 'Failed to add menu item', 'error');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            console.error('Error:', err);
            showModal('Error', err.message || 'Network error. Please try again.', 'error');
        });
};

function deleteMenuItem(itemId) {
    if (!confirm('Are you sure you want to delete this menu item?')) return;
    
    fetch(`/api/menu-items/${itemId}`, {
        method: 'DELETE',
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showModal('Success', 'Menu item deleted successfully!', 'success');
                loadMenuItems(currentMenuBusinessId);
            } else {
                showModal('Error', res.message || 'Failed to delete menu item', 'error');
            }
        })
        .catch(err => {
            showModal('Error', 'Network error. Please try again.', 'error');
        });
}

// Image preview and validation
document.addEventListener('DOMContentLoaded', function() {
    // Menu item image preview
    const menuImageInput = document.getElementById('menuItemImage');
    if (menuImageInput) {
        menuImageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const maxSize = 2048 * 1024; // 2MB in bytes
                if (file.size > maxSize) {
                    showModal('File Too Large', `Image must be smaller than 2MB. Your file is ${(file.size / 1024 / 1024).toFixed(2)}MB.`, 'error');
                    menuImageInput.value = '';
                    document.getElementById('imagePreview').style.display = 'none';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('previewImg').src = event.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Product image preview
    const productImageInput = document.getElementById('productImage');
    if (productImageInput) {
        productImageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const maxSize = 2048 * 1024;
                if (file.size > maxSize) {
                    showModal('File Too Large', `Image must be smaller than 2MB. Your file is ${(file.size / 1024 / 1024).toFixed(2)}MB.`, 'error');
                    productImageInput.value = '';
                    document.getElementById('productImagePreview').style.display = 'none';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('productPreviewImg').src = event.target.result;
                    document.getElementById('productImagePreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Service image preview
    const serviceImageInput = document.getElementById('serviceImage');
    if (serviceImageInput) {
        serviceImageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const maxSize = 2048 * 1024;
                if (file.size > maxSize) {
                    showModal('File Too Large', `Image must be smaller than 2MB. Your file is ${(file.size / 1024 / 1024).toFixed(2)}MB.`, 'error');
                    serviceImageInput.value = '';
                    document.getElementById('serviceImagePreview').style.display = 'none';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('servicePreviewImg').src = event.target.result;
                    document.getElementById('serviceImagePreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
});

window.manageProducts = function () {
    const selector = document.getElementById('manageBusinessSelector');
    const businessId = selector.value;
    
    if (!businessId) {
        alert('Please select a business');
        return;
    }
    
    currentProductBusinessId = businessId;
    loadProducts(businessId);
    document.getElementById('productListModal').style.display = 'block';
};

window.closeProductListModal = function () {
    document.getElementById('productListModal').style.display = 'none';
};

window.openAddProductModal = function () {
    document.getElementById('addProductForm').reset();
    document.getElementById('productImagePreview').style.display = 'none';
    document.getElementById('addProductModal').style.display = 'block';
};

window.closeAddProductModal = function () {
    document.getElementById('addProductModal').style.display = 'none';
};

function loadProducts(businessId) {
    fetch(`/api/businesses/${businessId}/products`, {
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => r.json())
        .then(res => {
            if (res.data && res.data.data) {
                currentProducts = res.data.data;
            } else if (Array.isArray(res.data)) {
                currentProducts = res.data;
            } else {
                currentProducts = [];
            }
            displayProducts();
        })
        .catch(err => {
            console.error('Error loading products:', err);
            currentProducts = [];
            displayProducts();
        });
}

function displayProducts() {
    const container = document.getElementById('productsList');
    
    if (currentProducts.length === 0) {
        container.innerHTML = '<div style="text-align: center; color: #999; grid-column: 1 / -1; padding: 40px 20px;">No products yet</div>';
        return;
    }
    
    container.innerHTML = currentProducts.map(item => {
        let imageUrl = null;
        if (item.image) {
            if (item.image.startsWith('/storage/')) {
                imageUrl = item.image;
            } else if (item.image.startsWith('storage/')) {
                imageUrl = '/' + item.image;
            } else {
                imageUrl = '/storage/' + item.image;
            }
        }
        
        return `
            <div style="background: #f9f9f9; border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0; transition: all 0.2s; display: flex; flex-direction: column;">
                ${imageUrl ? `<img src="${imageUrl}" alt="${escapeHtml(item.name)}" style="width: 100%; height: 150px; object-fit: cover; background: #e0e0e0;">` : `<div style="width: 100%; height: 150px; background: #e0e0e0; display: flex; align-items: center; justify-content: center; color: #999; font-size: 12px;">No Image</div>`}
                <div style="padding: 12px; flex-grow: 1; display: flex; flex-direction: column;">
                    <div style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 4px;">${escapeHtml(item.name)}</div>
                    <div style="font-size: 11px; color: #999; margin-bottom: 6px;">Stock: ${item.stock || 0}</div>
                    <div style="color: #00bcd4; font-weight: 700; font-size: 14px; margin-bottom: 8px; margin-top: auto;">₱${parseFloat(item.price).toFixed(2)}</div>
                    <button onclick="deleteProduct(${item.id})" style="width: 100%; padding: 6px; background: #ff6b6b; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer; font-weight: 600;">Delete</button>
                </div>
            </div>
        `;
    }).join('');
}

window.submitProductForm = function (e) {
    e.preventDefault();
    
    const name = document.getElementById('productName').value.trim();
    const price = document.getElementById('productPrice').value;
    const stock = document.getElementById('productStock').value;
    const imageFile = document.getElementById('productImage').files[0];
    
    if (!name || !price || !stock) {
        alert('Please fill in all required fields');
        return;
    }
    
    const formData = new FormData();
    formData.append('name', name);
    formData.append('price', price);
    formData.append('stock', stock);
    formData.append('description', '');
    if (imageFile) {
        formData.append('image', imageFile);
    }
    
    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4z"/></svg> Adding...';
    
    fetch(`/api/businesses/${currentProductBusinessId}/products`, {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
    })
        .then(r => {
            if (!r.ok) {
                return r.json().then(data => {
                    throw new Error(data.message || `HTTP ${r.status}`);
                });
            }
            return r.json();
        })
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            
            if (res.success) {
                showModal('Success', 'Product added successfully!', 'success');
                closeAddProductModal();
                loadProducts(currentProductBusinessId);
            } else {
                showModal('Error', res.message || 'Failed to add product', 'error');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            console.error('Error:', err);
            showModal('Error', err.message || 'Network error. Please try again.', 'error');
        });
};

function deleteProduct(itemId) {
    if (!confirm('Are you sure you want to delete this product?')) return;
    
    fetch(`/api/products/${itemId}`, {
        method: 'DELETE',
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showModal('Success', 'Product deleted successfully!', 'success');
                loadProducts(currentProductBusinessId);
            } else {
                showModal('Error', res.message || 'Failed to delete product', 'error');
            }
        })
        .catch(err => {
            showModal('Error', 'Network error. Please try again.', 'error');
        });
}

// ── Services Management ──────────────────────────────────────────────────────

window.manageServices = function () {
    const selector = document.getElementById('manageBusinessSelector');
    const businessId = selector.value;
    
    if (!businessId) {
        alert('Please select a business');
        return;
    }
    
    currentServiceBusinessId = businessId;
    loadServices(businessId);
    document.getElementById('serviceListModal').style.display = 'block';
};

window.closeServiceListModal = function () {
    document.getElementById('serviceListModal').style.display = 'none';
};

window.openAddServiceModal = function () {
    document.getElementById('addServiceForm').reset();
    document.getElementById('serviceImagePreview').style.display = 'none';
    document.getElementById('addServiceModal').style.display = 'block';
};

window.closeAddServiceModal = function () {
    document.getElementById('addServiceModal').style.display = 'none';
};

function loadServices(businessId) {
    fetch(`/api/businesses/${businessId}/services`, {
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => r.json())
        .then(res => {
            if (res.data && res.data.data) {
                currentServices = res.data.data;
            } else if (Array.isArray(res.data)) {
                currentServices = res.data;
            } else {
                currentServices = [];
            }
            displayServices();
        })
        .catch(err => {
            console.error('Error loading services:', err);
            currentServices = [];
            displayServices();
        });
}

function displayServices() {
    const container = document.getElementById('servicesList');
    
    if (currentServices.length === 0) {
        container.innerHTML = '<div style="text-align: center; color: #999; grid-column: 1 / -1; padding: 40px 20px;">No services yet</div>';
        return;
    }
    
    container.innerHTML = currentServices.map(item => {
        const imageUrl = item.image_url || null;
        
        return `
            <div style="background: #f9f9f9; border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0; transition: all 0.2s; display: flex; flex-direction: column;">
                ${imageUrl ? `<img src="${imageUrl}" alt="${escapeHtml(item.name)}" style="width: 100%; height: 150px; object-fit: cover; background: #e0e0e0;">` : `<div style="width: 100%; height: 150px; background: #e0e0e0; display: flex; align-items: center; justify-content: center; color: #999; font-size: 12px;">No Image</div>`}
                <div style="padding: 12px; flex-grow: 1; display: flex; flex-direction: column;">
                    <div style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 4px;">${escapeHtml(item.name)}</div>
                    <div style="font-size: 11px; color: #999; margin-bottom: 6px;">Service</div>
                    <div style="color: #00bcd4; font-weight: 700; font-size: 14px; margin-bottom: 8px; margin-top: auto;">₱${parseFloat(item.price).toFixed(2)}</div>
                    <button onclick="deleteService(${item.id})" style="width: 100%; padding: 6px; background: #ff6b6b; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer; font-weight: 600;">Delete</button>
                </div>
            </div>
        `;
    }).join('');
}

window.submitServiceForm = function (e) {
    e.preventDefault();
    
    const name = document.getElementById('serviceName').value.trim();
    const price = document.getElementById('servicePrice').value;
    const imageFile = document.getElementById('serviceImage').files[0];
    
    if (!name || !price) {
        showModal('Error', 'Please fill in all required fields', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('name', name);
    formData.append('price', price);
    formData.append('description', '');
    if (imageFile) {
        formData.append('image', imageFile);
        console.log('Image file selected:', imageFile.name, imageFile.size);
    } else {
        console.log('No image file selected');
    }
    
    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4z"/></svg> Adding...';
    
    fetch(`/api/businesses/${currentServiceBusinessId}/services`, {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
    })
        .then(r => {
            if (!r.ok) {
                return r.json().then(data => {
                    throw new Error(data.message || `HTTP ${r.status}`);
                });
            }
            return r.json();
        })
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            
            if (res.success) {
                document.getElementById('addServiceForm').reset();
                showModal('Success', 'Service added successfully!', 'success');
                closeAddServiceModal();
                loadServices(currentServiceBusinessId);
            } else {
                showModal('Error', res.message || 'Failed to add service', 'error');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            console.error('Error:', err);
            showModal('Error', err.message || 'Network error. Please try again.', 'error');
        });
};

function deleteService(itemId) {
    if (!confirm('Are you sure you want to delete this service?')) return;
    
    fetch(`/api/services/${itemId}`, {
        method: 'DELETE',
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showModal('Success', 'Service deleted successfully!', 'success');
                loadServices(currentServiceBusinessId);
            } else {
                showModal('Error', res.message || 'Failed to delete service', 'error');
            }
        })
        .catch(err => {
            showModal('Error', 'Network error. Please try again.', 'error');
        });
}

// ── Services Management ──────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    const section = document.getElementById('my-business');
    if (!section) return;

    const observer = new MutationObserver(() => {
        if (section.classList.contains('active')) {
            initMyBusinessSection();
        }
    });
    observer.observe(section, { attributes: true, attributeFilter: ['class'] });
});
