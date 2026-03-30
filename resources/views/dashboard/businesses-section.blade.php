<div id="businesses" class="content-section">
    <!-- Modal for viewing business items -->
    <div id="business-items-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 900px; max-height: 80vh; overflow-y: auto; box-shadow: 0 8px 32px rgba(0,0,0,0.2);">
            <div style="background: linear-gradient(135deg, #00bcd4 0%, #0097a7 100%); padding: 20px; color: white; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0;">
                <div>
                    <div id="business-items-title" style="font-size: 20px; font-weight: 700; margin-bottom: 5px;"></div>
                    <div id="business-items-subtitle" style="font-size: 13px; opacity: 0.9;"></div>
                </div>
                <button onclick="closeBusinessItemsModal()" style="background: rgba(255,255,255,0.2); color: white; border: none; font-size: 24px; cursor: pointer; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">×</button>
            </div>
            <div id="business-items-content" style="padding: 20px; text-align: center;"></div>
        </div>
    </div>

    <h1 class="page-title"><i class="fas fa-map-marker-alt"></i> Business Locations - Sagay City</h1>
    <p style="color: #666; margin-bottom: 15px;">Explore local businesses in Sagay City, Negros Occidental. Each pin represents a registered business.</p>
    
    <div class="card">
        <div id="businesses-map-container" style="width: 100%; height: 500px; border-radius: 8px; overflow: hidden; border: 2px solid #00bcd4; margin: 15px 0; position: relative;"></div>
        
        <div style="display: flex; gap: 20px; margin-top: 15px; padding: 15px; background: #f9f9f9; border-radius: 8px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 16px; height: 16px; background: #ffd700; border-radius: 50%;"></div>
                <span style="font-size: 13px; color: #666;">Food Business</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 16px; height: 16px; background: #3498db; border-radius: 50%;"></div>
                <span style="font-size: 13px; color: #666;">Goods Business</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 16px; height: 16px; background: #9b59b6; border-radius: 50%;"></div>
                <span style="font-size: 13px; color: #666;">Services Business</span>
            </div>
        </div>
    </div>

    <div class="stats">
        <div class="stat-card"><h3 id="food-count">0</h3><p>Food Businesses</p></div>
        <div class="stat-card"><h3 id="goods-count">0</h3><p>Goods Stores</p></div>
        <div class="stat-card"><h3 id="services-count">0</h3><p>Service Providers</p></div>
    </div>

    <!-- Service Management Section (for service business owners) -->
    <div id="service-management-section" style="display: none; margin-top: 30px;">
        <div class="card" style="background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%); color: white; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                <div style="font-size: 32px;">🔧</div>
                <div>
                    <h2 style="margin: 0; font-size: 22px; font-weight: 700;">Manage Your Services</h2>
                    <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">Add, edit, or remove services from your business</p>
                </div>
            </div>
        </div>

        <!-- Service Business Selector -->
        <div style="margin-bottom: 20px;">
            <label style="display: block; font-size: 14px; font-weight: 600; color: #333; margin-bottom: 10px;">Select Service Business</label>
            <select id="service-business-selector" class="modern-select" style="width: 100%; padding: 12px 14px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; font-family: inherit; background: white; cursor: pointer;">
                <option value="">Choose a business...</option>
            </select>
        </div>

        <!-- Services List -->
        <div id="services-list-container" style="display: none;">
            <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
                <h3 style="margin: 0 0 15px 0; color: #333; font-size: 16px; font-weight: 700;">Current Services</h3>
                <div id="services-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                    <p style="grid-column: 1 / -1; text-align: center; color: #999; padding: 20px;">Loading services...</p>
                </div>
            </div>

            <!-- Add Service Form -->
            <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-top: 20px;">
                <h3 style="margin: 0 0 15px 0; color: #333; font-size: 16px; font-weight: 700;">Add New Service</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 8px;">Service Name *</label>
                        <input type="text" id="service-name-input" placeholder="e.g., Web Design" style="width: 100%; padding: 12px 14px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 13px; font-family: inherit; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 8px;">Price (₱) *</label>
                        <input type="number" id="service-price-input" placeholder="0.00" min="0" step="0.01" style="width: 100%; padding: 12px 14px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 13px; font-family: inherit; box-sizing: border-box;">
                    </div>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 8px;">Description (Optional)</label>
                    <textarea id="service-description-input" placeholder="Describe your service..." style="width: 100%; padding: 12px 14px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 13px; font-family: inherit; min-height: 80px; box-sizing: border-box; resize: vertical;"></textarea>
                </div>
                <button onclick="addServiceFromBusinesses()" style="width: 100%; padding: 12px 24px; background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                    <span style="display: inline-flex; align-items: center; gap: 8px; justify-content: center;">
                        <span>➕</span>
                        <span>Add Service</span>
                    </span>
                </button>
                <div id="service-message" style="margin-top: 12px; padding: 12px 14px; border-radius: 8px; display: none; font-size: 13px;"></div>
            </div>
        </div>

        <!-- No Service Business Message -->
        <div id="no-service-business-message" style="background: #fff3cd; border: 2px solid #ffc107; border-radius: 12px; padding: 20px; text-align: center; color: #856404;">
            <div style="font-size: 32px; margin-bottom: 10px;">🔧</div>
            <p style="margin: 0; font-size: 14px; font-weight: 600;">You don't have any service businesses yet</p>
            <p style="margin: 8px 0 0 0; font-size: 13px; opacity: 0.9;">Create a service business in "My Business" to manage services here</p>
        </div>
    </div>
</div>

<script>
let businessesMap = null;
let allBusinessMarkers = [];
let currentFilter = null;

window.filterBusinessesByType = function(type) {
    currentFilter = type;
    
    // Show/hide markers based on filter
    allBusinessMarkers.forEach(marker => {
        if (marker.businessType === type) {
            businessesMap.addLayer(marker);
        } else {
            businessesMap.removeLayer(marker);
        }
    });
};

window.resetBusinessFilter = function() {
    currentFilter = null;
    
    // Show all markers
    allBusinessMarkers.forEach(marker => {
        businessesMap.addLayer(marker);
    });
    
    // Reset sidebar filter UI
    document.querySelectorAll('.business-filter-item').forEach(el => {
        el.style.background = '';
    });
};

window.updateFilterUI = function(element) {
    // Remove highlight from all filter items
    document.querySelectorAll('.business-filter-item').forEach(el => {
        el.style.background = '';
    });
    
    // Highlight the clicked filter item
    element.style.background = '#e3f2fd';
};

window.initBusinessesMap = function() {
    const container = document.getElementById('businesses-map-container');
    if (!container) return;
    if (businessesMap) { setTimeout(() => businessesMap.invalidateSize(), 100); return; }

    const sagayBounds = L.latLngBounds([10.75, 123.30], [11.05, 123.55]);
    businessesMap = L.map('businesses-map-container', {
        zoomControl: false, maxBounds: sagayBounds, maxBoundsViscosity: 1.0, minZoom: 11
    }).setView([10.8967, 123.4253], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors', maxZoom: 19
    }).addTo(businessesMap);

    fetch('/api/businesses-map')
        .then(r => r.json())
        .then(response => {
            const data = response.data || {};
            const businesses = data.businesses || [];
            const counts = data.counts || {};

            document.getElementById('food-count').textContent = counts.food || 0;
            document.getElementById('goods-count').textContent = counts.goods || 0;
            document.getElementById('services-count').textContent = counts.services || 0;

            businesses.forEach(business => {
                let bgColor = '#ffd700';
                let emoji = '🍔';
                let typeLabel = 'Food Business';
                
                if (business.business_type === 'goods') {
                    bgColor = '#3498db';
                    emoji = '🛍️';
                    typeLabel = 'Goods Business';
                }
                if (business.business_type === 'services') {
                    bgColor = '#9b59b6';
                    emoji = '🔧';
                    typeLabel = 'Services Business';
                }

                // Create circular pin with emoji
                const pinIcon = L.divIcon({
                    className: '',
                    html: `<div style="width: 35px; height: 35px; background: ${bgColor}; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        ${emoji}
                    </div>`,
                    iconSize: [35, 35],
                    iconAnchor: [17, 35],
                    popupAnchor: [0, -35]
                });

                const marker = L.marker([business.latitude, business.longitude], { icon: pinIcon }).addTo(businessesMap);
                marker.businessType = business.business_type;
                allBusinessMarkers.push(marker);

                // Create detailed popup with business info
                let actionButton = '';
                let viewType = '';
                if (business.business_type === 'food') {
                    actionButton = `<button onclick="showBusinessItems(${business.id}, 'menu')" style="width: 100%; padding: 12px; background: #3498db; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; margin-top: 10px;">View Menu</button>`;
                    viewType = 'menu';
                } else if (business.business_type === 'goods') {
                    actionButton = `<button onclick="showBusinessItems(${business.id}, 'products')" style="width: 100%; padding: 12px; background: #3498db; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; margin-top: 10px;">View Products</button>`;
                    viewType = 'products';
                } else {
                    actionButton = `<button onclick="showBusinessItems(${business.id}, 'services')" style="width: 100%; padding: 12px; background: #3498db; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; margin-top: 10px;">View Services</button>`;
                    viewType = 'services';
                }

                const popupContent = `<div style="width: 300px; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.15); border: 3px solid #ffd700; font-family: sans-serif;">
                    <div style="background: ${bgColor}; padding: 15px; color: white;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                            <span style="font-size: 24px;">${emoji}</span>
                            <div style="font-weight: 700; font-size: 16px;">${business.business_name}</div>
                        </div>
                        <div style="font-size: 13px; opacity: 0.9;">${typeLabel}</div>
                    </div>
                    <div style="background: white; padding: 15px;">
                        <div style="display: flex; gap: 8px; margin-bottom: 10px; color: #666; font-size: 13px;">
                            <span style="color: #e74c3c;">📍</span>
                            <span>${business.address}</span>
                        </div>
                        <div style="display: flex; gap: 8px; margin-bottom: 12px; color: #666; font-size: 13px;">
                            <span style="color: #e74c3c;">📞</span>
                            <span>${business.phone || 'N/A'}</span>
                        </div>
                        <div style="background: #e8f5e9; border-left: 4px solid #27ae60; padding: 10px; border-radius: 4px; margin-bottom: 12px; font-size: 13px;">
                            <div style="color: #27ae60; font-weight: 700; margin-bottom: 3px;">● OPEN NOW</div>
                            <div style="color: #666; display: flex; gap: 5px;">
                                <span>🕐</span>
                                <span>${business.opening_time || '8:00 AM'} - ${business.closing_time || '5:00 PM'}</span>
                            </div>
                        </div>
                        ${actionButton}
                        <button onclick="showBusinessJobs(${business.id})" style="width: 100%; padding: 12px; background: #27ae60; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; margin-top: 8px; display: flex; align-items: center; justify-content: center; gap: 5px;">
                            <span>💼</span> View All Jobs
                        </button>
                    </div>
                </div>`;
                
                marker.bindPopup(popupContent, { maxWidth: 320 });
            });
        })
        .catch(err => console.error('Error loading businesses:', err));
};

document.addEventListener('DOMContentLoaded', function() {
    const observer = new MutationObserver(function() {
        const section = document.getElementById('businesses');
        if (section && section.classList.contains('active')) initBusinessesMap();
    });
    const target = document.getElementById('businesses');
    if (target) observer.observe(target, { attributes: true, attributeFilter: ['class'] });
});

// Modal for viewing business items
window.showBusinessItems = function(businessId, itemType) {
    const modal = document.getElementById('business-items-modal');
    const modalContent = document.getElementById('business-items-content');
    
    fetch(`/api/businesses/${businessId}`)
        .then(r => r.json())
        .then(response => {
            const business = response.data;
            let title = '';
            let subtitle = '';
            let itemsHtml = '';
            
            if (itemType === 'menu') {
                title = `${business.business_name}`;
                subtitle = 'Browse menu items and offerings';
                
                fetch(`/api/businesses/${businessId}/menu-items`)
                    .then(r => r.json())
                    .then(data => {
                        const items = data.data?.data || [];
                        if (items.length === 0) {
                            itemsHtml = '<div style="text-align: center; padding: 40px; color: #999;"><div style="font-size: 48px; margin-bottom: 10px;">🍽️</div><div>No menu items available yet.</div></div>';
                        } else {
                            itemsHtml = items.map(item => `
                                <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: inline-block; width: 180px; margin: 10px;">
                                    <div style="width: 100%; height: 120px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-size: 48px;">🍽️</div>
                                    <div style="padding: 12px;">
                                        <div style="font-weight: 700; color: #333; margin-bottom: 4px; font-size: 14px;">${item.name}</div>
                                        <div style="font-size: 12px; color: #999; margin-bottom: 8px;">Food</div>
                                        <div style="color: #ffd700; font-weight: 700; font-size: 14px; margin-bottom: 8px;">₱${item.price}</div>
                                        <button style="width: 100%; padding: 8px; background: #27ae60; color: white; border: none; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer;">✓ Available</button>
                                    </div>
                                </div>
                            `).join('');
                        }
                        displayModal(title, subtitle, itemsHtml);
                    });
            } else if (itemType === 'products') {
                title = `${business.business_name}`;
                subtitle = 'Browse products and offerings';
                
                fetch(`/api/businesses/${businessId}/products`)
                    .then(r => r.json())
                    .then(data => {
                        const items = data.data?.data || [];
                        if (items.length === 0) {
                            itemsHtml = '<div style="text-align: center; padding: 40px; color: #999;"><div style="font-size: 48px; margin-bottom: 10px;">📦</div><div>No products available yet.</div></div>';
                        } else {
                            itemsHtml = items.map(item => `
                                <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: inline-block; width: 180px; margin: 10px;">
                                    <div style="width: 100%; height: 120px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-size: 48px;">📦</div>
                                    <div style="padding: 12px;">
                                        <div style="font-weight: 700; color: #333; margin-bottom: 4px; font-size: 14px;">${item.name}</div>
                                        <div style="font-size: 12px; color: #999; margin-bottom: 8px;">Product</div>
                                        <div style="color: #ffd700; font-weight: 700; font-size: 14px; margin-bottom: 8px;">₱${item.price}</div>
                                        <button style="width: 100%; padding: 8px; background: #27ae60; color: white; border: none; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer;">✓ Available</button>
                                    </div>
                                </div>
                            `).join('');
                        }
                        displayModal(title, subtitle, itemsHtml);
                    });
            } else if (itemType === 'services') {
                title = `${business.business_name}`;
                subtitle = 'Browse services and offerings';
                
                fetch(`/api/businesses/${businessId}/services`)
                    .then(r => r.json())
                    .then(data => {
                        const items = data.data?.data || [];
                        if (items.length === 0) {
                            itemsHtml = '<div style="text-align: center; padding: 40px; color: #999;"><div style="font-size: 48px; margin-bottom: 10px;">🔧</div><div>No services available yet.</div></div>';
                        } else {
                            itemsHtml = items.map(item => `
                                <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: inline-block; width: 180px; margin: 10px;">
                                    <div style="width: 100%; height: 120px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-size: 48px;">🔧</div>
                                    <div style="padding: 12px;">
                                        <div style="font-weight: 700; color: #333; margin-bottom: 4px; font-size: 14px;">${item.name}</div>
                                        <div style="font-size: 12px; color: #999; margin-bottom: 8px;">Service</div>
                                        <div style="color: #ffd700; font-weight: 700; font-size: 14px; margin-bottom: 8px;">₱${item.price}</div>
                                        <button style="width: 100%; padding: 8px; background: #27ae60; color: white; border: none; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer;">✓ Available</button>
                                    </div>
                                </div>
                            `).join('');
                        }
                        displayModal(title, subtitle, itemsHtml);
                    });
            }
        });
};

function displayModal(title, subtitle, content) {
    const modal = document.getElementById('business-items-modal');
    const titleEl = document.getElementById('business-items-title');
    const subtitleEl = document.getElementById('business-items-subtitle');
    const contentEl = document.getElementById('business-items-content');
    
    titleEl.textContent = title;
    subtitleEl.textContent = subtitle;
    contentEl.innerHTML = content;
    modal.style.display = 'flex';
}

window.closeBusinessItemsModal = function() {
    document.getElementById('business-items-modal').style.display = 'none';
};

window.showBusinessJobs = function(businessId) {
    const modal = document.getElementById('business-items-modal');
    const modalContent = document.getElementById('business-items-content');
    
    fetch(`/api/jobs/business/${businessId}`)
        .then(r => r.json())
        .then(response => {
            const jobs = response.data || [];
            const titleEl = document.getElementById('business-items-title');
            const subtitleEl = document.getElementById('business-items-subtitle');
            const contentEl = document.getElementById('business-items-content');
            
            titleEl.textContent = 'Now Hiring';
            subtitleEl.textContent = jobs.length > 0 ? `${jobs.length} open position${jobs.length !== 1 ? 's' : ''}` : 'No open positions';
            
            if (jobs.length === 0) {
                contentEl.innerHTML = '<div style="text-align: center; padding: 40px; color: #999;"><div style="font-size: 48px; margin-bottom: 10px;">💼</div><div>No open positions at this time.</div></div>';
            } else {
                contentEl.innerHTML = jobs.map(job => `
                    <div onclick="JobsModule.viewJobDetails(${job.id}); closeBusinessItemsModal();" style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin: 10px; padding: 15px; text-align: left; border-left: 4px solid #27ae60; cursor: pointer; transition: all 0.2s; border: 2px solid transparent;"
                         onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.2)'; this.style.borderColor='#27ae60';"
                         onmouseout="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'; this.style.borderColor='transparent';">
                        <div style="font-weight: 700; color: #333; margin-bottom: 8px; font-size: 15px;">${job.title}</div>
                        <div style="display: flex; gap: 10px; margin-bottom: 8px; flex-wrap: wrap;">
                            <span style="background: #f0f0f0; padding: 4px 10px; border-radius: 4px; font-size: 12px; color: #666;">📋 ${job.job_type}</span>
                            <span style="background: #f0f0f0; padding: 4px 10px; border-radius: 4px; font-size: 12px; color: #666;">📍 ${job.location}</span>
                        </div>
                        ${job.salary_range ? `<div style="color: #27ae60; font-weight: 700; font-size: 13px; margin-bottom: 8px;">💰 ${job.salary_range}</div>` : ''}
                        <div style="background: #e8f5e9; padding: 8px; border-radius: 4px; font-size: 12px; color: #27ae60; font-weight: 600; text-align: center;">✓ Open</div>
                    </div>
                `).join('');
            }
            
            modal.style.display = 'flex';
        })
        .catch(err => {
            console.error('Error loading jobs:', err);
            document.getElementById('business-items-content').innerHTML = '<div style="text-align: center; padding: 40px; color: #999;">Error loading jobs</div>';
        });
};

// ── Service Management Functions ──────────────────────────────────────────

window.initServiceManagement = function() {
    loadServiceBusinesses();
};

function loadServiceBusinesses() {
    fetch('/api/my-businesses', {
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => r.json())
        .then(res => {
            // Handle paginated response - data.data contains the actual businesses
            let businesses = [];
            if (res.data && res.data.data) {
                businesses = res.data.data;
            } else if (Array.isArray(res.data)) {
                businesses = res.data;
            }
            
            const selector = document.getElementById('service-business-selector');
            const section = document.getElementById('service-management-section');
            const noMessage = document.getElementById('no-service-business-message');
            const listContainer = document.getElementById('services-list-container');
            
            if (!selector || !section) return;
            
            if (businesses.length === 0) {
                section.style.display = 'block';
                noMessage.style.display = 'block';
                listContainer.style.display = 'none';
                return;
            }
            
            section.style.display = 'block';
            noMessage.style.display = 'none';
            listContainer.style.display = 'block';
            
            selector.innerHTML = '<option value="">Choose a business...</option>';
            businesses.forEach(b => {
                const option = document.createElement('option');
                option.value = b.id;
                option.textContent = b.name + ' (' + (b.category === 'food' ? '🍔 Food' : b.category === 'goods' ? '🛍️ Goods' : '🔧 Services') + ')';
                selector.appendChild(option);
            });
            
            selector.addEventListener('change', function() {
                if (this.value) {
                    loadServicesForBusiness(parseInt(this.value));
                }
            });
        })
        .catch(err => console.error('Error loading service businesses:', err));
}

function loadServicesForBusiness(businessId) {
    const servicesList = document.getElementById('services-list');
    if (!servicesList) return;
    
    servicesList.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #999; padding: 20px;">Loading services...</p>';
    
    fetch(`/api/businesses/${businessId}/services`, {
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => r.json())
        .then(res => {
            // Handle paginated response - data.data contains the actual services
            let services = [];
            if (res.data && res.data.data) {
                services = res.data.data;
            } else if (Array.isArray(res.data)) {
                services = res.data;
            }
            
            if (services.length === 0) {
                servicesList.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #999; padding: 20px;">No services yet. Add one below!</p>';
                return;
            }
            
            servicesList.innerHTML = services.map(service => `
                <div style="background: white; border-radius: 8px; border: 2px solid #e0e0e0; padding: 15px; transition: all 0.2s;"
                     onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'; this.style.borderColor='#9b59b6';"
                     onmouseout="this.style.boxShadow='none'; this.style.borderColor='#e0e0e0';">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                        <div>
                            <h4 style="margin: 0 0 4px 0; color: #333; font-size: 15px; font-weight: 700;">🔧 ${escapeHtml(service.name)}</h4>
                            <p style="margin: 0; color: #666; font-size: 12px; line-height: 1.4;">${service.description ? escapeHtml(service.description) : 'No description'}</p>
                        </div>
                        <button onclick="deleteServiceFromBusinesses(${service.id})" style="background: #e74c3c; color: white; border: none; border-radius: 6px; padding: 6px 12px; cursor: pointer; font-size: 12px; font-weight: 600; white-space: nowrap; margin-left: 10px;">Delete</button>
                    </div>
                    <div style="color: #9b59b6; font-weight: 700; font-size: 16px;">₱${parseFloat(service.price).toFixed(2)}</div>
                </div>
            `).join('');
        })
        .catch(err => {
            console.error('Error loading services:', err);
            servicesList.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #e74c3c; padding: 20px;">Error loading services</p>';
        });
}

window.addServiceFromBusinesses = function() {
    const businessId = document.getElementById('service-business-selector').value;
    const name = document.getElementById('service-name-input').value.trim();
    const price = document.getElementById('service-price-input').value.trim();
    const description = document.getElementById('service-description-input').value.trim();
    const msgEl = document.getElementById('service-message');
    
    msgEl.style.display = 'none';
    msgEl.innerHTML = '';
    
    if (!businessId) {
        showServiceMessage('Please select a business first', 'error');
        return;
    }
    
    if (!name || !price) {
        showServiceMessage('Service name and price are required', 'error');
        return;
    }
    
    if (isNaN(price) || parseFloat(price) < 0) {
        showServiceMessage('Price must be a valid number', 'error');
        return;
    }
    
    const payload = {
        name: name,
        price: parseFloat(price),
        description: description || null,
    };
    
    fetch(`/api/businesses/${businessId}/services`, {
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
                showServiceMessage('✓ Service added successfully!', 'success');
                document.getElementById('service-name-input').value = '';
                document.getElementById('service-price-input').value = '';
                document.getElementById('service-description-input').value = '';
                setTimeout(() => {
                    loadServicesForBusiness(parseInt(businessId));
                    msgEl.style.display = 'none';
                }, 1500);
            } else {
                showServiceMessage('✗ ' + (res.message || 'Failed to add service'), 'error');
            }
        })
        .catch(err => {
            console.error('Error adding service:', err);
            showServiceMessage('✗ Network error. Please try again.', 'error');
        });
};

window.deleteServiceFromBusinesses = function(serviceId) {
    if (!confirm('Are you sure you want to delete this service?')) return;
    
    const businessId = document.getElementById('service-business-selector').value;
    
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
                loadServicesForBusiness(parseInt(businessId));
            } else {
                alert(res.message || 'Failed to delete service');
            }
        })
        .catch(err => {
            console.error('Error deleting service:', err);
            alert('Error deleting service');
        });
};

function showServiceMessage(message, type) {
    const msgEl = document.getElementById('service-message');
    msgEl.style.display = 'block';
    msgEl.style.background = type === 'success' ? '#c8e6c9' : '#ffcdd2';
    msgEl.style.color = type === 'success' ? '#2e7d32' : '#c62828';
    msgEl.textContent = message;
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Initialize when section becomes active
document.addEventListener('DOMContentLoaded', function() {
    const observer = new MutationObserver(function() {
        const section = document.getElementById('businesses');
        if (section && section.classList.contains('active')) {
            initServiceManagement();
        }
    });
    const target = document.getElementById('businesses');
    if (target) observer.observe(target, { attributes: true, attributeFilter: ['class'] });
});
</script>
