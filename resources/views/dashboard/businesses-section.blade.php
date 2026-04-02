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
                                    <div style="width: 100%; height: 120px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-size: 48px; overflow: hidden;">
                                        ${item.image_url ? `<img src="${item.image_url}" alt="${item.name}" style="width: 100%; height: 100%; object-fit: cover;">` : '🍽️'}
                                    </div>
                                    <div style="padding: 12px;">
                                        <div style="font-weight: 700; color: #333; margin-bottom: 4px; font-size: 14px;">${item.name}</div>
                                        <div style="font-size: 12px; color: #999; margin-bottom: 8px;">${item.category || 'Food'}</div>
                                        <div style="color: #ffd700; font-weight: 700; font-size: 14px; margin-bottom: 8px;">₱${parseFloat(item.price).toFixed(2)}</div>
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
                                    <div style="width: 100%; height: 120px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-size: 48px; overflow: hidden;">
                                        ${item.image_url ? `<img src="${item.image_url}" alt="${item.name}" style="width: 100%; height: 100%; object-fit: cover;">` : '📦'}
                                    </div>
                                    <div style="padding: 12px;">
                                        <div style="font-weight: 700; color: #333; margin-bottom: 4px; font-size: 14px;">${item.name}</div>
                                        <div style="font-size: 12px; color: #999; margin-bottom: 8px;">${item.category || 'Product'}</div>
                                        <div style="color: #ffd700; font-weight: 700; font-size: 14px; margin-bottom: 8px;">₱${parseFloat(item.price).toFixed(2)}</div>
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
                                    <div style="width: 100%; height: 120px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-size: 48px; overflow: hidden;">
                                        ${item.image_url ? `<img src="${item.image_url}" alt="${item.name}" style="width: 100%; height: 100%; object-fit: cover;">` : '🔧'}
                                    </div>
                                    <div style="padding: 12px;">
                                        <div style="font-weight: 700; color: #333; margin-bottom: 4px; font-size: 14px;">${item.name}</div>
                                        <div style="font-size: 12px; color: #999; margin-bottom: 8px;">${item.duration || 'Service'}</div>
                                        <div style="color: #ffd700; font-weight: 700; font-size: 14px; margin-bottom: 8px;">₱${parseFloat(item.price).toFixed(2)}</div>
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

</script>
