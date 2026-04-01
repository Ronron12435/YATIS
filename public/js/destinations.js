/**
 * Tourist Destinations - Map, listings, and reviews
 */

let destMap = null;
let destMarkers = [];
let allDestinations = [];
let userLat = null;
let userLng = null;
let routingControl = null;

// ── Init ──────────────────────────────────────────────────────────────────────

window.initDestinationsSection = function () {
    loadDestinationsDashboard();
    setTimeout(() => initDestMap(), 100);
};

function loadDestinationsDashboard() {
    fetch('/api/destinations-dashboard', {
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => {
            if (!r.ok) {
                console.error(`API Error: ${r.status} ${r.statusText}`);
                throw new Error(`HTTP ${r.status}`);
            }
            return r.json();
        })
        .then(res => {
            if (!res.success) {
                console.error('API returned success: false', res);
                return;
            }
            const d = res.data;

            setEl('dest-total-count', d.total || 0);
            setEl('dest-my-reviews', d.my_reviews || 0);
            setEl('dest-my-avg', (d.my_avg_rating || 0).toFixed(1));

            allDestinations = d.destinations || [];
            renderDestinationsList(allDestinations);
            
            // Load destinations on map if map is ready
            if (destMap) {
                loadDestinationsOnMap();
            }
        })
        .catch(err => {
            console.error('Error loading destinations:', err);
            setEl('dest-total-count', '0');
            setEl('dest-my-reviews', '0');
            setEl('dest-my-avg', '0.0');
        });
}

// ── Map ───────────────────────────────────────────────────────────────────────

function initDestMap() {
    const container = document.getElementById('dest-map-container');
    if (!container) {
        return;
    }

    if (destMap) {
        setTimeout(() => destMap.invalidateSize(), 100);
        return;
    }

    destMap = L.map('dest-map-container', { zoomControl: true }).setView([10.8967, 123.4253], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(destMap);

    
    // Create marker immediately with default location
    userLat = 10.8967;
    userLng = 123.4253;
    createUserLocationMarker(userLat, userLng);
    
    // Then load actual location from database and update marker
    loadUserLocationFromDatabase();

    // Map search
    const searchInput = document.getElementById('dest-map-search');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            renderDestMapMarkers(q);
        });
    }
    
    // Load destinations on map
    loadDestinationsOnMap();
}

function createUserLocationMarker(lat, lng) {
    if (!destMap) {
        return;
    }
    
    
    // Validate coordinates
    if (isNaN(lat) || isNaN(lng)) {
        console.error('✗ Invalid coordinates - NaN detected');
        return;
    }
    
    if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
        console.error('✗ Coordinates out of valid range');
        return;
    }
    
    
    // Remove old markers
    if (window._destUserMarker) {
        destMap.removeLayer(window._destUserMarker);
    }
    if (window._destUserAccuracy) {
        destMap.removeLayer(window._destUserAccuracy);
    }
    
    // Accuracy circle (light cyan)
    window._destUserAccuracy = L.circle([lat, lng], {
        radius: 50,
        color: '#00bcd4',
        fillColor: '#00bcd4',
        fillOpacity: 0.15,
        weight: 1
    }).addTo(destMap);
    
    // Cyan dot marker - LARGER and MORE VISIBLE
    window._destUserMarker = L.circleMarker([lat, lng], {
        radius: 15,
        fillColor: '#00bcd4',
        color: 'white',
        weight: 4,
        opacity: 1,
        fillOpacity: 1,
        zIndex: 1000
    }).addTo(destMap).bindPopup('<b>📍 Your Location</b><br>Lat: ' + lat.toFixed(4) + '<br>Lng: ' + lng.toFixed(4));
    
}

function updateUserMarker(location) {
    createUserLocationMarker(location.lat, location.lng);
}

function loadDestinationsOnMap() {
    if (!destMap || allDestinations.length === 0) return;
    
    allDestinations.forEach(dest => {
        if (dest.latitude && dest.longitude) {
            addDestinationMarker(dest);
        }
    });
}

function addDestinationMarker(destination) {
    if (!destMap) return;
    
    // Get emoji icon based on destination name
    const icon = getDestinationIcon(destination.name);
    
    const marker = L.marker([destination.latitude, destination.longitude], {
        icon: L.divIcon({
            className: 'destination-marker',
            html: `<div style="font-size: 32px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">${icon}</div>`,
            iconSize: [40, 40],
            iconAnchor: [20, 40]
        })
    }).addTo(destMap);
    
    // Create hover tooltip
    const tooltipContent = createHoverTooltip(destination);
    marker.bindTooltip(tooltipContent, {
        permanent: false,
        direction: 'top',
        offset: [0, -40],
        className: 'destination-tooltip'
    });
    
    // Create popup with full details
    const popupContent = createDestinationPopup(destination);
    marker.bindPopup(popupContent, {
        maxWidth: 300,
        className: 'destination-popup'
    });
    
    // Store destination data with marker
    marker.destinationData = destination;
    
    destMarkers.push(marker);
}

function getDestinationIcon(name) {
    const nameLower = name.toLowerCase();
    if (nameLower.includes('reef') || nameLower.includes('marine')) return '🏖️';
    if (nameLower.includes('island') || nameLower.includes('mangrove')) return '🌴';
    if (nameLower.includes('beach')) return '🏝️';
    if (nameLower.includes('church') || nameLower.includes('vito')) return '⛪';
    if (nameLower.includes('museum') || nameLower.includes('museo')) return '🏛️';
    if (nameLower.includes('plaza') || nameLower.includes('garden')) return '🌳';
    if (nameLower.includes('river') || nameLower.includes('cruise')) return '🚤';
    if (nameLower.includes('festival') || nameLower.includes('sinigayan')) return '🎉';
    return '📍';
}

function createHoverTooltip(destination, distance = null) {
    let distanceText = '';
    if (distance !== null) {
        distanceText = `<div style="font-size: 12px; color: #666; margin-top: 4px;">📏 ${distance.toFixed(2)} km away</div>`;
    } else if (userLat && userLng) {
        const dist = calculateDistance(userLat, userLng, destination.latitude, destination.longitude);
        distanceText = `<div style="font-size: 12px; color: #666; margin-top: 4px;">📏 ${dist.toFixed(2)} km away</div>`;
    }
    
    return `
        <div style="text-align: center;">
            <strong>${destination.name}</strong>
            ${distanceText}
        </div>
    `;
}

function createDestinationPopup(destination) {
    const rating = destination.rating ? parseFloat(destination.rating).toFixed(1) : 'N/A';
    const reviews = destination.reviews_count || 0;
    
    return `
        <div style="min-width: 250px;">
            <h4 style="margin: 0 0 10px 0; color: #1a3a52;">${destination.name}</h4>
            <div style="margin-bottom: 10px;">
                <span style="color: #ffd700; font-size: 14px;">${generateStars(parseFloat(destination.rating || 0))}</span>
                <span style="color: #666; font-size: 13px; margin-left: 5px;">${rating} (${reviews} reviews)</span>
            </div>
            ${destination.description ? `<p style="margin: 10px 0; color: #666; font-size: 13px; line-height: 1.4;">${destination.description}</p>` : ''}
            <div style="margin-top: 12px; display: flex; flex-direction: column; gap: 8px;">
                <button onclick="getDirectionsTo(${destination.latitude}, ${destination.longitude}, '${destination.name.replace(/'/g, "\\'")}'); event.stopPropagation();" 
                        class="btn btn-primary" style="width: 100%; padding: 8px; font-size: 13px; background: #1976d2; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    🗺️ Get Directions
                </button>
                <button onclick="viewReviews(${destination.id}, '${escapeHtml(destination.name)}'); event.stopPropagation();" 
                        class="btn btn-success" style="width: 100%; padding: 8px; font-size: 13px; background: #2ecc71; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    📖 View Reviews
                </button>
            </div>
        </div>
    `;
}

function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
             Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
             Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

function generateStars(rating) {
    const full = Math.floor(rating);
    const half = rating - full >= 0.5;
    let html = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= full) html += '★';
        else if (i === full + 1 && half) html += '⭐';
        else html += '☆';
    }
    return html;
}

function renderDestMapMarkers(filter) {
    destMarkers.forEach(m => destMap.removeLayer(m));
    destMarkers = [];

    const list = filter
        ? allDestinations.filter(d => d.name.toLowerCase().includes(filter))
        : allDestinations;

    list.forEach(dest => {
        if (dest.latitude && dest.longitude) {
            addDestinationMarker(dest);
        }
    });
}

window.getDirectionsTo = function (lat, lng, name) {
    if (!userLat || !userLng) {
        loadUserLocationFromDatabase();
        setTimeout(() => {
            if (userLat && userLng) {
                openDirectionsModal(userLat, userLng, lat, lng, name);
            } else {
                alert('Your location is not available. Please try again in a moment.');
            }
        }, 1000);
        return;
    }
    
    // Open directions modal
    openDirectionsModal(userLat, userLng, lat, lng, name);
};

function openDirectionsModal(fromLat, fromLng, toLat, toLng, destName) {
    const modal = document.getElementById('dest-directions-modal');
    const title = document.getElementById('dest-directions-title');
    const content = document.getElementById('dest-directions-content');
    
    if (!modal) return;
    
    title.textContent = `🗺️ Directions to ${destName}`;
    modal.style.display = 'flex';
    
    // Create a unique container for this directions map
    content.innerHTML = `
        <div id="directions-map-container" style="width: 100%; height: 500px; border-radius: 8px; overflow: hidden; margin-bottom: 15px;"></div>
        <div id="directions-info" style="background: #f8f9fa; padding: 15px; border-radius: 8px; max-height: 150px; overflow-y: auto;"></div>
    `;
    
    // Initialize the directions map
    setTimeout(() => {
        initDirectionsMap(fromLat, fromLng, toLat, toLng, destName);
    }, 100);
}

window.closeDirectionsModal = function () {
    const modal = document.getElementById('dest-directions-modal');
    if (modal) {
        modal.style.display = 'none';
        // Clean up the directions map
        if (window._directionsMap) {
            window._directionsMap.remove();
            window._directionsMap = null;
        }
    }
};

function initDirectionsMap(fromLat, fromLng, toLat, toLng, destName) {
    const container = document.getElementById('directions-map-container');
    if (!container) return;
    
    // Remove old map if exists
    if (window._directionsMap) {
        window._directionsMap.remove();
    }
    
    // Create new map
    window._directionsMap = L.map('directions-map-container', { zoomControl: false }).setView(
        [(fromLat + toLat) / 2, (fromLng + toLng) / 2],
        13
    );
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(window._directionsMap);
    
    // Add start marker (user location - blue)
    L.marker([fromLat, fromLng], {
        icon: L.divIcon({
            className: 'route-start-marker',
            html: '<div style="width: 32px; height: 32px; background: #3498db; border: 4px solid white; border-radius: 50%; box-shadow: 0 0 15px rgba(52, 152, 219, 0.9); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 16px;">📍</div>',
            iconSize: [32, 32],
            iconAnchor: [16, 16]
        })
    }).addTo(window._directionsMap)
        .bindPopup('<strong>📍 Your Location</strong>', { autoClose: false });
    
    // Add end marker (destination - red)
    L.marker([toLat, toLng], {
        icon: L.divIcon({
            className: 'route-end-marker',
            html: '<div style="width: 32px; height: 32px; background: #e74c3c; border: 4px solid white; border-radius: 50%; box-shadow: 0 0 15px rgba(231, 76, 60, 0.9); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 16px;">📍</div>',
            iconSize: [32, 32],
            iconAnchor: [16, 16]
        })
    }).addTo(window._directionsMap)
        .bindPopup(`<strong>🎯 ${destName}</strong>`, { autoClose: false });
    
    // Add routing control
    if (window._directionsRoutingControl) {
        window._directionsMap.removeControl(window._directionsRoutingControl);
    }
    
    window._directionsRoutingControl = L.Routing.control({
        waypoints: [
            L.latLng(fromLat, fromLng),
            L.latLng(toLat, toLng)
        ],
        routeWhileDragging: false,
        show: true,
        addWaypoints: false,
        lineOptions: {
            styles: [{ color: '#3498db', weight: 5, opacity: 0.8 }]
        },
        createMarker: function() {
            return null; // Don't create default markers, we have custom ones
        }
    }).addTo(window._directionsMap);
    
    // Fit bounds to show entire route
    setTimeout(() => {
        try {
            const bounds = L.latLngBounds([fromLat, fromLng], [toLat, toLng]);
            window._directionsMap.fitBounds(bounds, { padding: [60, 60] });
        } catch (e) {
        }
    }, 500);
    
    // Display route info
    displayDirectionsInfo(fromLat, fromLng, toLat, toLng, destName);
}

function displayDirectionsInfo(fromLat, fromLng, toLat, toLng, destName) {
    const infoEl = document.getElementById('directions-info');
    if (!infoEl) return;
    
    const distance = calculateDistance(fromLat, fromLng, toLat, toLng);
    
    infoEl.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <div>
                <p style="margin: 0; font-size: 13px; color: #666;"><strong>📍 From:</strong> Your Location</p>
                <p style="margin: 0; font-size: 13px; color: #666;"><strong>🎯 To:</strong> ${destName}</p>
            </div>
            <div style="text-align: right;">
                <p style="margin: 0; font-size: 16px; font-weight: bold; color: #3498db;">📏 ${distance.toFixed(2)} km</p>
                <p style="margin: 0; font-size: 12px; color: #999;">Approximate distance</p>
            </div>
        </div>
        <p style="margin: 8px 0 0 0; font-size: 12px; color: #666; line-height: 1.5;">
            💡 <strong>Tip:</strong> The blue line shows your route. Blue pin is your location, red pin is the destination.
        </p>
    `;
}

window.centerOnSagayCity = function () {
    if (destMap) destMap.setView([10.8967, 123.4253], 12);
};

function requestGPS() {
    // GPS requests disabled - using database location only
    return;
}

window.setTestLocation = function (lat, lng) {
    // Test location disabled
    return;
};

window.toggleDestMap = function () {
    const container = document.getElementById('dest-map-wrapper');
    if (!container) return;
    const hidden = container.style.display === 'none';
    container.style.display = hidden ? 'block' : 'none';
    const toggleText = document.getElementById('dest-map-toggle-text');
    if (toggleText) toggleText.textContent = hidden ? 'Hide Map' : 'Show Map';
    if (hidden && destMap) setTimeout(() => destMap.invalidateSize(), 100);
};

// ── Destination List ──────────────────────────────────────────────────────────

function renderDestinationsList(destinations) {
    const container = document.getElementById('dest-list');
    if (!container) return;

    if (!destinations.length) {
        container.innerHTML = '<p style="color:#999;padding:20px;text-align:center;">No destinations found.</p>';
        return;
    }

    container.innerHTML = destinations.map(dest => {
        const stars = renderStars(dest.rating || 0);
        const reviewCount = dest.reviews_count || 0;
        return `
        <div class="dest-card" id="dest-card-${dest.id}">
            <div class="dest-card-header">
                <div>
                    <h3 class="dest-card-title">${dest.name}</h3>
                    <p class="dest-card-desc">${dest.description || ''}</p>
                    <p class="dest-card-meta"><span>📍</span> <strong>Location:</strong> ${dest.location || ''}</p>
                    ${dest.address ? `<p class="dest-card-meta"><span>🏠</span> <strong>Address:</strong> ${dest.address}</p>` : ''}
                </div>
                <div class="dest-card-rating">
                    ${stars}
                    <span class="dest-rating-text">${(dest.rating || 0).toFixed(1)} (${reviewCount} review${reviewCount !== 1 ? 's' : ''})</span>
                </div>
            </div>
            <div class="dest-card-actions">
                <button class="btn-dest btn-dest-reviews" onclick="viewReviews(${dest.id}, '${escapeHtml(dest.name)}')">View Reviews</button>
                <button class="btn-dest btn-dest-write" onclick="openWriteReview(${dest.id}, '${escapeHtml(dest.name)}')">Write Review</button>
                ${dest.latitude && dest.longitude
                    ? `<button class="btn-dest btn-dest-map" onclick="showOnMap(${dest.latitude},${dest.longitude})">📍 Show on Map</button>`
                    : ''}
            </div>
        </div>`;
    }).join('');
}

window.showOnMap = function (lat, lng) {
    const mapWrapper = document.getElementById('dest-map-wrapper');
    if (mapWrapper) mapWrapper.style.display = 'block';
    if (destMap) {
        setTimeout(() => {
            destMap.invalidateSize();
            destMap.setView([lat, lng], 16);
        }, 100);
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

// ── Reviews ───────────────────────────────────────────────────────────────────

window.viewReviews = function (destId, destName) {
    const panel = document.getElementById('dest-reviews-panel');
    const title = document.getElementById('dest-reviews-title');
    const body = document.getElementById('dest-reviews-body');
    if (!panel) return;

    title.textContent = destName + ' — Reviews';
    body.innerHTML = '<p style="color:#999;padding:20px;">Loading...</p>';
    panel.style.display = 'flex';

    fetch(`/api/destinations/${destId}/reviews`, {
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        })
        .then(res => {
            const reviews = res.data?.data || res.data || [];
            if (!reviews.length) {
                body.innerHTML = '<p style="color:#999;padding:20px;text-align:center;">No reviews yet. Be the first!</p>';
                return;
            }
            body.innerHTML = reviews.map(rv => `
                <div class="review-card">
                    <div class="review-header">
                        <div class="review-avatar">${(rv.first_name || rv.username || 'U')[0].toUpperCase()}</div>
                        <div>
                            <strong>${rv.first_name ? rv.first_name + ' ' + rv.last_name : rv.username}</strong>
                            <div style="font-size:12px;color:#999;">${formatDate(rv.created_at)}</div>
                        </div>
                        <div style="margin-left:auto;">${renderStars(rv.rating)}</div>
                    </div>
                    <p class="review-text">${rv.review}</p>
                </div>
            `).join('');
        })
        .catch(() => { body.innerHTML = '<p style="color:#e74c3c;padding:20px;">Error loading reviews.</p>'; });
};

window.closeReviewsPanel = function () {
    const panel = document.getElementById('dest-reviews-panel');
    if (panel) panel.style.display = 'none';
};

window.openWriteReview = function (destId, destName) {
    const panel = document.getElementById('dest-write-panel');
    const title = document.getElementById('dest-write-title');
    if (!panel) return;
    title.textContent = 'Write a Review — ' + destName;
    document.getElementById('dest-write-dest-id').value = destId;
    document.getElementById('dest-write-rating').value = '';
    document.getElementById('dest-write-text').value = '';
    document.getElementById('dest-write-error').textContent = '';
    panel.style.display = 'flex';
};

window.closeWritePanel = function () {
    const panel = document.getElementById('dest-write-panel');
    if (panel) panel.style.display = 'none';
};

window.submitReview = function () {
    
    const destId = document.getElementById('dest-write-dest-id').value;
    const rating = document.getElementById('dest-write-rating').value;
    const review = document.getElementById('dest-write-text').value.trim();
    const errEl = document.getElementById('dest-write-error');


    if (!rating || !review) {
        errEl.textContent = 'Please fill in all fields.';
        return;
    }

    const btn = document.getElementById('dest-write-submit');
    btn.disabled = true;
    btn.textContent = 'Submitting...';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    const payload = { rating: parseInt(rating), review };

    fetch(`/api/destinations/${destId}/reviews`, {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
    })
        .then(r => {
            if (!r.ok) {
                console.error('✗ HTTP Error:', r.status);
                throw new Error(`HTTP ${r.status}`);
            }
            return r.json();
        })
        .then(res => {
            btn.disabled = false;
            btn.textContent = 'Submit Review';
            if (res.success) {
                closeWritePanel();
                loadDestinationsDashboard();
            } else {
                errEl.textContent = res.message || 'Failed to submit review.';
            }
        })
        .catch(err => {
            console.error('✗ Error submitting review:', err);
            console.error('Error message:', err.message);
            console.error('Error stack:', err.stack);
            btn.disabled = false;
            btn.textContent = 'Submit Review';
            errEl.textContent = 'Network error. Please try again.';
        });
};

// ── Helpers ───────────────────────────────────────────────────────────────────

function renderStars(rating) {
    const full = Math.floor(rating);
    const half = rating - full >= 0.5;
    let html = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= full) html += '<i class="fas fa-star" style="color:#f39c12;font-size:13px;"></i>';
        else if (i === full + 1 && half) html += '<i class="fas fa-star-half-alt" style="color:#f39c12;font-size:13px;"></i>';
        else html += '<i class="far fa-star" style="color:#f39c12;font-size:13px;"></i>';
    }
    return html;
}

function updateGpsStatus(status, message) {
    const el = document.getElementById('dest-gps-status');
    if (!el) return;
    
    const colors = {
        'searching': '#f39c12',
        'active': '#27ae60',
        'error': '#e74c3c'
    };
    
    const icons = {
        'searching': '🔍',
        'active': '✓',
        'error': '✗'
    };
    
    el.innerHTML = `
        <span style="width: 8px; height: 8px; border-radius: 50%; background: ${colors[status]}; display: inline-block;"></span>
        ${icons[status]} GPS: ${message}
    `;
}

function setEl(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
}

function escapeHtml(str) {
    return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function loadUserLocationFromDatabase() {
    
    fetch('/api/user/location', {
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => {
            if (!r.ok) {
                throw new Error(`HTTP ${r.status}`);
            }
            return r.json();
        })
        .then(response => {
            
            if (response.success && response.data) {
                
                if (response.data.latitude !== null && response.data.latitude !== undefined && 
                    response.data.longitude !== null && response.data.longitude !== undefined) {
                    
                    userLat = parseFloat(response.data.latitude);
                    userLng = parseFloat(response.data.longitude);
                    
                    
                    // Update marker immediately with actual location
                    if (destMap) {
                        createUserLocationMarker(userLat, userLng);
                        updateGpsStatus('active', 'Location loaded from database');
                    } else {
                    }
                } else {
                    updateGpsStatus('active', 'Using default location');
                }
            } else {
                updateGpsStatus('active', 'Using default location');
            }
        })
        .catch(err => {
            console.error('✗ Error loading location:', err);
            console.error('Error stack:', err.stack);
            updateGpsStatus('active', 'Using default location');
        });
}

function saveLocationToDatabase(lat, lng) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    fetch('/api/user/location', {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            latitude: lat,
            longitude: lng,
        }),
    })
        .then(r => r.json())
        .then(response => {
            if (response.success) {
            }
        })
        .catch(err => console.log('Error saving location:', err));
}

// ── Boot ──────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    const section = document.getElementById('destinations');
    if (!section) return;

    const observer = new MutationObserver(() => {
        if (section.classList.contains('active')) {
            initDestinationsSection();
        }
    });
    observer.observe(section, { attributes: true, attributeFilter: ['class'] });
});
