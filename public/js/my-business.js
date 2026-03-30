/**
 * My Business - Business registration and management for business accounts
 */

let currentBusinessId = null;
let businessLocationMap = null;
let businessLocationMarker = null;
let geocodingTimeout = null;

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
    const infoDisplay = document.getElementById('businessInfoDisplay');
    
    fetch(`/api/businesses/${businessId}`, {
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => r.json())
        .then(res => {
            const business = res.data;
            if (business) {
                currentBusinessId = business.id;
                
                // Display business info
                document.getElementById('displayBusinessName').textContent = business.name || '-';
                document.getElementById('displayBusinessType').textContent = capitalizeType(business.category) || '-';
                document.getElementById('displayPhone').textContent = business.phone || '-';
                document.getElementById('displayEmail').textContent = business.email || '-';
                document.getElementById('displayLocation').textContent = business.address || '-';
                
                // Hide form, show info
                form.style.display = 'none';
                infoDisplay.style.display = 'block';
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
                document.getElementById('openingTime').value = business.opening_time || '';
                document.getElementById('closingTime').value = business.closing_time || '';
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

// ── Services Management ──────────────────────────────────────────────────────

window.openManageServicesModal = function () {
    if (!currentBusinessId) {
        showModal('Error', 'Please select a business first', 'error');
        return;
    }
    
    const modal = document.getElementById('servicesModal');
    if (modal) {
        modal.style.display = 'block';
        loadServices();
    }
};

window.closeServicesModal = function () {
    const modal = document.getElementById('servicesModal');
    if (modal) {
        modal.style.display = 'none';
    }
    // Reset form
    document.getElementById('serviceName').value = '';
    document.getElementById('servicePrice').value = '';
    document.getElementById('serviceDescription').value = '';
    document.getElementById('servicesMessage').innerHTML = '';
};

function loadServices() {
    const servicesList = document.getElementById('servicesList');
    if (!servicesList || !currentBusinessId) return;

    servicesList.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">Loading services...</p>';

    fetch(`/api/businesses/${currentBusinessId}/services`, {
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        })
        .then(res => {
            const services = res.data || [];
            if (services.length === 0) {
                servicesList.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">No services yet. Add one above!</p>';
                return;
            }
            
            servicesList.innerHTML = services.map(service => `
                <div style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 12px; margin-bottom: 12px; background: white;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                        <div>
                            <h5 style="margin: 0 0 4px 0; color: #333; font-size: 14px; font-weight: 600;">${escapeHtml(service.name)}</h5>
                            <p style="margin: 0; color: #666; font-size: 13px;">${service.description ? escapeHtml(service.description) : 'No description'}</p>
                        </div>
                        <button onclick="deleteService(${service.id})" style="background: #e74c3c; color: white; border: none; border-radius: 6px; padding: 6px 12px; cursor: pointer; font-size: 12px; font-weight: 600;">Delete</button>
                    </div>
                    <div style="color: #00bcd4; font-weight: 600; font-size: 14px;">₱${parseFloat(service.price).toFixed(2)}</div>
                </div>
            `).join('');
        })
        .catch(() => {
            servicesList.innerHTML = '<p style="color: #e74c3c; text-align: center; padding: 20px;">Error loading services</p>';
        });
}

window.addService = function () {
    const msgEl = document.getElementById('servicesMessage');
    msgEl.innerHTML = '';

    const name = document.getElementById('serviceName').value.trim();
    const price = document.getElementById('servicePrice').value.trim();
    const description = document.getElementById('serviceDescription').value.trim();

    if (!name || !price) {
        msgEl.innerHTML = '<div class="message error">Service name and price are required.</div>';
        return;
    }

    if (isNaN(price) || parseFloat(price) < 0) {
        msgEl.innerHTML = '<div class="message error">Price must be a valid number.</div>';
        return;
    }

    const payload = {
        name: name,
        price: parseFloat(price),
        description: description || null,
    };

    fetch(`/api/businesses/${currentBusinessId}/services`, {
        method: 'POST',
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
            if (res.success) {
                msgEl.innerHTML = '<div class="message success">✓ Service added successfully!</div>';
                document.getElementById('serviceName').value = '';
                document.getElementById('servicePrice').value = '';
                document.getElementById('serviceDescription').value = '';
                setTimeout(() => {
                    loadServices();
                    msgEl.innerHTML = '';
                }, 1000);
            } else {
                msgEl.innerHTML = `<div class="message error">✗ ${res.message || 'Failed to add service'}</div>`;
            }
        })
        .catch(() => {
            msgEl.innerHTML = '<div class="message error">✗ Network error. Please try again.</div>';
        });
};

window.deleteService = function (serviceId) {
    if (!confirm('Are you sure you want to delete this service?')) return;

    fetch(`/api/businesses/services/${serviceId}`, {
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
                loadServices();
            } else {
                alert(res.message || 'Failed to delete service');
            }
        })
        .catch(() => {
            alert('Error deleting service');
        });
};

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
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
