const BusinessManagementModule = (() => {
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const init = () => {
        // Module initialization
    };

    const cleanupAllModals = () => {
        // Remove all modals and overlays to prevent stacking
        document.querySelectorAll('[id$="-modal"], [id$="-overlay"]').forEach(el => el.remove());
    };

    const openManageBusinessModal = () => {
        // Fetch all businesses for this user
        fetch('/api/my-businesses', { credentials: 'include' })
            .then(r => r.json())
            .then(response => {
                let businesses = [];
                
                // Handle paginated response (response.data.data)
                if (response.data && response.data.data && Array.isArray(response.data.data)) {
                    businesses = response.data.data;
                }
                // Handle direct array response (response.data)
                else if (Array.isArray(response.data)) {
                    businesses = response.data;
                }
                
                if (businesses.length === 0) {
                    showModal('No Business', 'You don\'t have any registered business yet. Please register one first.');
                    return;
                }

                showManagementModal(businesses);
            })
            .catch(err => {
                console.error('Error loading businesses:', err);
                showModal('Error', 'Failed to load business information');
            });
    };

    const showManagementModal = (businesses) => {
        let currentBusiness = businesses[0]; // Default to first business

        const modal = document.createElement('div');
        modal.id = 'manage-business-modal';
        modal.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:10001; padding:0; max-width:600px; width:90%; max-height:80vh; display:flex; flex-direction:column; overflow:hidden;';
        
        let businessSelectorHTML = '';
        if (businesses.length > 1) {
            businessSelectorHTML = `
                <div style="margin-bottom:20px;">
                    <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Select Business</label>
                    <select id="business-selector" onchange="BusinessManagementModule.updateSelectedBusiness(this.value)" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box;">
                        ${businesses.map((b, i) => `<option value="${i}">${b.name} (${b.category})</option>`).join('')}
                    </select>
                </div>
            `;
        }
        
        // Build category-specific buttons for initial business
        let categoryButtonHTML = '';
        if (currentBusiness.category === 'food') {
            categoryButtonHTML = `<div style="display:grid; grid-template-columns:1fr; gap:12px; margin-bottom:20px;">
                <button onclick="BusinessManagementModule.viewTableStatus(${currentBusiness.id})" style="padding:12px 16px; background:#e67e22; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <i class="fas fa-eye"></i> View Table Status
                </button>
                <button onclick="BusinessManagementModule.openAddMenuModal(${currentBusiness.id})" style="padding:12px 16px; background:#16a34a; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <i class="fas fa-utensils"></i> Add Menu
                </button>
            </div>`;
        } else if (currentBusiness.category === 'goods') {
            categoryButtonHTML = `<div style="display:grid; grid-template-columns:1fr; gap:12px; margin-bottom:20px;">
                <button onclick="BusinessManagementModule.openAddProductsModal(${currentBusiness.id})" style="padding:12px 16px; background:#0ea5e9; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <i class="fas fa-box"></i> Add Products
                </button>
            </div>`;
        } else if (currentBusiness.category === 'services') {
            categoryButtonHTML = `<div style="display:grid; grid-template-columns:1fr; gap:12px; margin-bottom:20px;">
                <button onclick="BusinessManagementModule.openAddServicesModal(${currentBusiness.id})" style="padding:12px 16px; background:#f59e0b; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <i class="fas fa-briefcase"></i> Add Services
                </button>
            </div>`;
        }
        
        // Build tables button HTML
        let tablesButtonHTML = '';
        if (currentBusiness.category === 'food') {
            tablesButtonHTML = `<button onclick="BusinessManagementModule.manageTables(${currentBusiness.id})" style="padding:12px 16px; background:#9b59b6; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; display:flex; align-items:center; justify-content:center; gap:8px; width:100%;"><i class="fas fa-chair"></i> 🪑 Manage Tables</button>`;
        }
        
        const scrollContent = `
            <div style="padding:30px; overflow-y:auto; flex:1;">
                <button onclick="document.getElementById('manage-business-overlay').click();" style="position:absolute; top:15px; right:15px; background:none; border:none; font-size:24px; color:#999; cursor:pointer; z-index:10002;">&times;</button>
                
                <h2 style="margin:0 0 20px 0; color:#333; font-size:20px;">Manage Business</h2>
                
                ${businessSelectorHTML}
                
                <div style="background:#f0f4ff; border-left:4px solid #667eea; padding:12px 16px; border-radius:6px; margin-bottom:20px;">
                    <p style="margin:0; color:#333; font-weight:600;" id="business-name-display">${escapeHtml(currentBusiness.name)}</p>
                    <p style="margin:5px 0 0 0; color:#666; font-size:13px;" id="business-info-display">${escapeHtml(currentBusiness.category)} • ${escapeHtml(currentBusiness.address)}</p>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:20px;">
                    <button onclick="BusinessManagementModule.editLocation(${currentBusiness.id})" style="padding:12px 16px; background:#3498db; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; display:flex; align-items:center; justify-content:center; gap:8px;">
                        <i class="fas fa-map-marker-alt"></i> Edit Location
                    </button>
                    <button onclick="BusinessManagementModule.editInfo(${currentBusiness.id})" style="padding:12px 16px; background:#2ecc71; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; display:flex; align-items:center; justify-content:center; gap:8px;">
                        <i class="fas fa-info-circle"></i> Edit Info
                    </button>
                </div>
                ${categoryButtonHTML}
            </div>
            
            <div style="padding:20px 30px; border-top:1px solid #eee; background:#fafafa; display:grid; grid-template-columns:1fr; gap:12px;" id="tables-button-container">
                ${tablesButtonHTML}
            </div>
        `;
        
        modal.innerHTML = scrollContent;

        const overlay = document.createElement('div');
        overlay.id = 'manage-business-overlay';
        overlay.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10000;';
        overlay.onclick = () => {
            modal.remove();
            overlay.remove();
        };

        document.body.appendChild(overlay);
        document.body.appendChild(modal);
        
        // Store businesses for selector
        window.managedBusinesses = businesses;
    };

    const editLocation = (businessId) => {
        const modal = document.createElement('div');
        modal.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:10002; padding:30px; max-width:600px; width:90%; max-height:85vh; overflow-y:auto;';
        
        modal.innerHTML = `
            <button onclick="this.closest('div').remove(); document.getElementById('location-edit-overlay').remove();" style="position:absolute; top:15px; right:15px; background:none; border:none; font-size:24px; color:#999; cursor:pointer;">&times;</button>
            
            <h2 style="margin:0 0 20px 0; color:#333; font-size:20px;">Edit Business Location</h2>
            
            <div style="background:#fff3cd; border:1px solid #ffc107; border-radius:8px; padding:12px 14px; margin-bottom:20px; display:flex; gap:10px; align-items:flex-start;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#ff9800" style="flex-shrink:0; margin-top:2px;"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
                <div style="font-size:12px; color:#856404; line-height:1.4;">
                    <strong>IMPORTANT:</strong> Click on the map below to set your exact business location.<br>
                    Your business will only appear on the map for customers if you set this location!
                </div>
            </div>
            
            <div id="locationMapContainer" style="width:100%; height:400px; border-radius:8px; border:2px solid #e0e0e0; background:#f5f5f5; position:relative; overflow:hidden; margin-bottom:20px;"></div>
            
            <input type="hidden" id="edit-location-lat" value="">
            <input type="hidden" id="edit-location-lng" value="">
            <span id="locationCoords" style="font-size:12px; color:#666; display:block; margin-bottom:20px;">Click on the map to set location</span>
            
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button onclick="BusinessManagementModule.saveLocation(${businessId})" style="padding:12px 24px; background:#3498db; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Save Location</button>
            </div>
        `;

        const overlay = document.createElement('div');
        overlay.id = 'location-edit-overlay';
        overlay.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10001;';
        overlay.onclick = () => {
            modal.remove();
            overlay.remove();
        };

        document.body.appendChild(overlay);
        document.body.appendChild(modal);
        
        // Initialize map after modal is added to DOM
        setTimeout(() => initLocationMap(businessId), 100);
    };

    const initLocationMap = (businessId) => {
        const container = document.getElementById('locationMapContainer');
        if (!container) return;
        
        // Create map centered on Sagay City with zoom controls disabled
        const map = L.map(container, { zoomControl: false }).setView([10.8967, 123.4253], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);
        
        let marker = null;
        
        // Handle map clicks
        map.on('click', (e) => {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            
            document.getElementById('edit-location-lat').value = lat;
            document.getElementById('edit-location-lng').value = lng;
            document.getElementById('locationCoords').textContent = `📍 Location: ${lat.toFixed(4)}, ${lng.toFixed(4)}`;
            
            if (marker) map.removeLayer(marker);
            marker = L.circleMarker([lat, lng], {
                radius: 10,
                fillColor: '#3498db',
                color: 'white',
                weight: 3,
                opacity: 1,
                fillOpacity: 1
            }).addTo(map).bindPopup('Business Location').openPopup();
            
            map.setView([lat, lng], 15);
        });
    };

    const editInfo = (businessId) => {
        // Fetch current business data
        fetch(`/api/businesses/${businessId}`, { credentials: 'include' })
            .then(r => r.json())
            .then(response => {
                const business = response.data || {};
                
                const modal = document.createElement('div');
                modal.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:10002; padding:30px; max-width:600px; width:90%; max-height:85vh; overflow-y:auto;';
                
                modal.innerHTML = `
                    <button onclick="const overlay = document.getElementById('info-edit-overlay'); this.closest('div').remove(); if(overlay) overlay.remove();" style="position:absolute; top:15px; right:15px; background:none; border:none; font-size:24px; color:#999; cursor:pointer;">&times;</button>
                    
                    <h2 style="margin:0 0 20px 0; color:#333; font-size:20px;">Edit Business Info</h2>
                    
                    <div style="margin-bottom:20px;">
                        <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Business Name</label>
                        <input type="text" id="info-business-name" placeholder="Enter business name" value="${business.name || ''}" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box;">
                    </div>
                    
                    <div style="margin-bottom:20px;">
                        <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Description</label>
                        <textarea id="info-description" placeholder="Tell customers about your business..." style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box; min-height:100px; resize:vertical;">${business.description || ''}</textarea>
                    </div>
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px;">
                        <div>
                            <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Phone</label>
                            <input type="tel" id="info-phone" placeholder="09XXXXXXXXX" maxlength="11" value="${business.phone || ''}" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Email</label>
                            <input type="email" id="info-email" placeholder="email@example.com" value="${business.email || ''}" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box;">
                        </div>
                    </div>
                    
                    <div style="margin-bottom:20px;">
                        <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Address</label>
                        <input type="text" id="info-address" placeholder="Street address, city, province" value="${business.address || ''}" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box;">
                    </div>
                    
                    <div style="display:flex; gap:10px; justify-content:flex-end;">
                        <button onclick="BusinessManagementModule.saveInfo(${businessId})" style="padding:12px 24px; background:#2ecc71; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Save Info</button>
                    </div>
                `;

                const overlay = document.createElement('div');
                overlay.id = 'info-edit-overlay';
                overlay.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10001;';
                overlay.onclick = () => {
                    modal.remove();
                    overlay.remove();
                };

                document.body.appendChild(overlay);
                document.body.appendChild(modal);
            })
            .catch(err => {
                console.error('Error loading business:', err);
                showModal('Error', 'Failed to load business information');
            });
    };

    const showModal = (title, message, buttons = null) => {
        const modal = document.createElement('div');
        modal.id = 'info-modal';
        modal.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; padding:30px; max-width:400px; width:90%; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:10005; text-align:center;';
        
        let buttonsHTML = '';
        if (buttons) {
            buttonsHTML = `<div style="display:flex; gap:10px; margin-top:20px; justify-content:center;">
                ${buttons.map(btn => `<button onclick="${btn.onclick}" style="padding:10px 20px; background:${btn.color}; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">${btn.text}</button>`).join('')}
            </div>`;
        } else {
            buttonsHTML = `<button onclick="document.getElementById('info-modal-overlay').click();" style="padding:10px 20px; background:#3498db; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; margin-top:20px;">OK</button>`;
        }
        
        modal.innerHTML = `
            <h2 style="margin:0 0 15px 0; color:#333; font-size:18px;">${title}</h2>
            <p style="margin:0; color:#666; font-size:14px; line-height:1.6;">${message}</p>
            ${buttonsHTML}
        `;

        const overlay = document.createElement('div');
        overlay.id = 'info-modal-overlay';
        overlay.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10004;';
        overlay.onclick = () => {
            modal.remove();
            overlay.remove();
        };

        document.body.appendChild(overlay);
        document.body.appendChild(modal);
    };

    const updateSelectedBusiness = (index) => {
        const business = window.managedBusinesses[index];
        if (!business) {
            return;
        }
        
        // Find the management modal by ID
        const modal = document.getElementById('manage-business-modal');
        if (!modal) {
            return;
        }
        
        const scrollContent = modal.querySelector('[style*="overflow-y:auto"]');
        if (!scrollContent) {
            return;
        }
        
        // Build category-specific buttons
        let categoryButtonHTML = '';
        if (business.category === 'food') {
            categoryButtonHTML = `<div style="display:grid; grid-template-columns:1fr; gap:12px; margin-bottom:20px;">
                <button onclick="BusinessManagementModule.viewTableStatus(${business.id})" style="padding:12px 16px; background:#e67e22; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <i class="fas fa-eye"></i> View Table Status
                </button>
                <button onclick="BusinessManagementModule.openAddMenuModal(${business.id})" style="padding:12px 16px; background:#16a34a; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <i class="fas fa-utensils"></i> Add Menu
                </button>
            </div>`;
        } else if (business.category === 'goods') {
            categoryButtonHTML = `<div style="display:grid; grid-template-columns:1fr; gap:12px; margin-bottom:20px;">
                <button onclick="BusinessManagementModule.openAddProductsModal(${business.id})" style="padding:12px 16px; background:#0ea5e9; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <i class="fas fa-box"></i> Add Products
                </button>
            </div>`;
        } else if (business.category === 'services') {
            categoryButtonHTML = `<div style="display:grid; grid-template-columns:1fr; gap:12px; margin-bottom:20px;">
                <button onclick="BusinessManagementModule.openAddServicesModal(${business.id})" style="padding:12px 16px; background:#f59e0b; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <i class="fas fa-briefcase"></i> Add Services
                </button>
            </div>`;
        }
        
        // Build business selector HTML
        let businessSelectorHTML = '';
        if (window.managedBusinesses.length > 1) {
            businessSelectorHTML = `
                <div style="margin-bottom:20px;">
                    <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Select Business</label>
                    <select id="business-selector" onchange="BusinessManagementModule.updateSelectedBusiness(this.value)" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box;">
                        ${window.managedBusinesses.map((b, i) => `<option value="${i}" ${i == index ? 'selected' : ''}>${b.name} (${b.category})</option>`).join('')}
                    </select>
                </div>
            `;
        }
        
        // Update the scroll content HTML - preserve the selector
        scrollContent.innerHTML = `
            <button onclick="document.getElementById('manage-business-overlay').click();" style="position:absolute; top:15px; right:15px; background:none; border:none; font-size:24px; color:#999; cursor:pointer; z-index:10002;">&times;</button>
            
            <h2 style="margin:0 0 20px 0; color:#333; font-size:20px;">Manage Business</h2>
            
            ${businessSelectorHTML}
            
            <div style="background:#f0f4ff; border-left:4px solid #667eea; padding:12px 16px; border-radius:6px; margin-bottom:20px;">
                <p style="margin:0; color:#333; font-weight:600;" id="business-name-display">${escapeHtml(business.name)}</p>
                <p style="margin:5px 0 0 0; color:#666; font-size:13px;" id="business-info-display">${escapeHtml(business.category)} • ${escapeHtml(business.address)}</p>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:20px;">
                <button onclick="BusinessManagementModule.editLocation(${business.id})" style="padding:12px 16px; background:#3498db; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <i class="fas fa-map-marker-alt"></i> Edit Location
                </button>
                <button onclick="BusinessManagementModule.editInfo(${business.id})" style="padding:12px 16px; background:#2ecc71; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <i class="fas fa-info-circle"></i> Edit Info
                </button>
            </div>
            ${categoryButtonHTML}
        `;
        
        // Update tables button container (only for food)
        const tablesContainer = modal.querySelector('#tables-button-container');
        if (tablesContainer) {
            if (business.category === 'food') {
                tablesContainer.innerHTML = `<button onclick="BusinessManagementModule.manageTables(${business.id})" style="padding:12px 16px; background:#9b59b6; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; display:flex; align-items:center; justify-content:center; gap:8px; width:100%;"><i class="fas fa-chair"></i> 🪑 Manage Tables</button>`;
            } else {
                tablesContainer.innerHTML = '';
            }
        }
    };

    const openAddMenuModal = (businessId) => {
        // Clean up ALL modals first (not just menu ones)
        cleanupAllModals();
        
        const modal = document.createElement('div');
        modal.id = 'add-menu-modal';
        modal.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:10003; padding:0; max-width:1400px; width:95%; max-height:90vh; display:flex; flex-direction:column; overflow:hidden;';
        
        modal.innerHTML = `
            <div style="background:#17a2b8; color:white; padding:20px 30px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h2 style="margin:0; font-size:18px; font-weight:600;">🍽️ Menu Items</h2>
                    <p style="margin:5px 0 0 0; font-size:13px; opacity:0.9;">Browse menu items and offerings</p>
                </div>
                <button onclick="document.getElementById('add-menu-modal').remove(); document.getElementById('add-menu-overlay').remove();" style="background:none; border:none; font-size:24px; color:white; cursor:pointer; opacity:0.8;">&times;</button>
            </div>
            
            <div style="padding:30px; overflow-y:auto; flex:1;">
                <div id="menu-items-gallery" style="display:grid; grid-template-columns:repeat(5, 1fr); gap:20px;">
                    <div style="display:flex; align-items:center; justify-content:center; color:#999; font-size:14px; grid-column:1/-1; min-height:200px;">
                        Loading menu items...
                    </div>
                </div>
            </div>
            
            <div style="padding:20px 30px; border-top:1px solid #eee; background:#fafafa; display:flex; gap:10px; justify-content:flex-end;">
                <button onclick="BusinessManagementModule.openAddMenuItemForm(${businessId})" style="padding:12px 24px; background:#17a2b8; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">+ Add Menu Here</button>
            </div>
        `;

        const overlay = document.createElement('div');
        overlay.id = 'add-menu-overlay';
        overlay.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10002;';
        overlay.onclick = () => {
            modal.remove();
            overlay.remove();
        };

        document.body.appendChild(overlay);
        document.body.appendChild(modal);
        
        // Fetch existing menu items
        fetch(`/api/businesses/${businessId}`, { credentials: 'include' })
            .then(r => r.json())
            .then(data => {
                const gallery = document.getElementById('menu-items-gallery');
                const business = data.data;
                
                if (business && business.menu_items && business.menu_items.length > 0) {
                    gallery.innerHTML = business.menu_items.map(item => `
                        <div style="background:white; border:1px solid #e0e0e0; border-radius:8px; overflow:hidden; text-align:center; transition:transform 0.2s, box-shadow 0.2s; position:relative; opacity:${item.is_available ? '1' : '0.6'};" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 15px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            <div style="position:relative;">
                                ${item.image_url ? `<img src="${item.image_url}" style="width:100%; height:140px; object-fit:cover; filter:${item.is_available ? 'none' : 'grayscale(100%)'};">` : `<div style="width:100%; height:140px; background:#f0f0f0; display:flex; align-items:center; justify-content:center;"><i class="fas fa-image" style="color:#ccc; font-size:40px;"></i></div>`}
                                <div style="position:absolute; top:8px; right:8px; display:flex; gap:6px;">
                                    <button onclick="BusinessManagementModule.toggleMenuAvailability(${item.id}, ${businessId}, ${!item.is_available})" style="background:${item.is_available ? '#28a745' : '#ffc107'}; color:white; border:none; border-radius:50%; width:32px; height:32px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:16px; opacity:0.9;" title="${item.is_available ? 'Mark Unavailable' : 'Mark Available'}"><i class="fas fa-${item.is_available ? 'eye' : 'eye-slash'}"></i></button>
                                    <button onclick="BusinessManagementModule.deleteMenuItem(${item.id}, ${businessId})" style="background:#dc3545; color:white; border:none; border-radius:50%; width:32px; height:32px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:16px; opacity:0.9;" title="Delete"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                            <div style="padding:15px;">
                                <p style="margin:0; font-weight:600; font-size:14px; color:#333;">${item.name}</p>
                                <p style="margin:5px 0 0 0; color:#999; font-size:12px;">${item.category}</p>
                                <p style="margin:10px 0 0 0; color:#17a2b8; font-weight:700; font-size:16px;">₱${parseFloat(item.price).toFixed(2)}</p>
                                <button style="margin-top:10px; padding:8px 16px; background:${item.is_available ? '#17a2b8' : '#6c757d'}; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:600; font-size:12px; width:100%;">${item.is_available ? '✓ Available' : '✗ Unavailable'}</button>
                            </div>
                        </div>
                    `).join('');
                } else {
                    // Show empty state with add button
                    gallery.innerHTML = '<div style="display:flex; align-items:center; justify-content:center; color:#999; font-size:14px; grid-column:1/-1; min-height:200px;">No menu items yet. Click "+ Add Menu Here" to get started!</div>';
                }
            })
            .catch(err => {
                console.log('Error fetching menu items');
                const gallery = document.getElementById('menu-items-gallery');
                gallery.innerHTML = '<div style="display:flex; align-items:center; justify-content:center; color:#999; font-size:14px; grid-column:1/-1; min-height:200px;">No menu items yet</div>';
            });
    };

    const openAddMenuItemForm = (businessId) => {
        const modal = document.createElement('div');
        modal.id = 'add-menu-item-modal';
        modal.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:10004; padding:30px; max-width:600px; width:90%; max-height:85vh; overflow-y:auto;';
        
        modal.innerHTML = `
            <button onclick="document.getElementById('add-menu-item-modal').remove(); document.getElementById('add-menu-item-form-overlay').remove();" style="position:absolute; top:15px; right:15px; background:none; border:none; font-size:24px; color:#999; cursor:pointer;">&times;</button>
            
            <h2 style="margin:0 0 20px 0; color:#333; font-size:20px;">🍽️ Add Menu Item</h2>
            
            <div style="margin-bottom:20px;">
                <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Item Name</label>
                <input type="text" id="menu-item-name" placeholder="e.g., Adobo, Sinigang" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box;">
            </div>
            
            <div style="margin-bottom:20px;">
                <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Description</label>
                <textarea id="menu-item-description" placeholder="Describe your menu item..." style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box; min-height:80px; resize:vertical;"></textarea>
            </div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px;">
                <div>
                    <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Price (₱)</label>
                    <input type="number" id="menu-item-price" placeholder="0.00" step="0.01" min="0" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Category</label>
                    <select id="menu-item-category" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box;">
                        <option value="">Select Category</option>
                        <option value="appetizer">Appetizer</option>
                        <option value="main">Main Course</option>
                        <option value="dessert">Dessert</option>
                        <option value="beverage">Beverage</option>
                    </select>
                </div>
            </div>
            
            <div style="margin-bottom:20px;">
                <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Menu Image</label>
                <input type="file" id="menu-item-image" accept="image/*" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box;">
                <small style="color:#666; display:block; margin-top:5px;">Recommended: 500x500px or larger</small>
            </div>
            
            <div id="menu-image-preview" style="margin-bottom:20px; text-align:center; display:none;">
                <img id="menu-preview-img" style="max-width:100%; max-height:200px; border-radius:6px; border:1px solid #ddd;">
            </div>
            
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button onclick="BusinessManagementModule.saveMenuItem(${businessId})" style="padding:12px 24px; background:#16a34a; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Add Menu Item</button>
            </div>
        `;

        const overlay = document.createElement('div');
        overlay.id = 'add-menu-item-form-overlay';
        overlay.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10003;';
        overlay.onclick = () => {
            modal.remove();
            overlay.remove();
        };

        document.body.appendChild(overlay);
        document.body.appendChild(modal);
        
        document.getElementById('menu-item-image').addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    document.getElementById('menu-preview-img').src = event.target.result;
                    document.getElementById('menu-image-preview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    };

    const openAddProductsModal = (businessId) => {
        // Clean up ALL modals first (not just product ones)
        cleanupAllModals();
        
        const modal = document.createElement('div');
        modal.id = 'add-products-gallery-modal';
        modal.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:10003; padding:0; max-width:1400px; width:95%; max-height:90vh; display:flex; flex-direction:column; overflow:hidden;';
        
        modal.innerHTML = `
            <div style="background:#0ea5e9; color:white; padding:20px 30px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h2 style="margin:0; font-size:18px; font-weight:600;">📦 Products</h2>
                    <p style="margin:5px 0 0 0; font-size:13px; opacity:0.9;">Browse your products</p>
                </div>
                <button onclick="document.getElementById('add-products-gallery-modal').remove(); document.getElementById('add-products-gallery-overlay').remove();" style="background:none; border:none; font-size:24px; color:white; cursor:pointer; opacity:0.8;">&times;</button>
            </div>
            
            <div style="padding:30px; overflow-y:auto; flex:1;">
                <div id="products-gallery" style="display:grid; grid-template-columns:repeat(5, 1fr); gap:20px;">
                    <div style="display:flex; align-items:center; justify-content:center; color:#999; font-size:14px; grid-column:1/-1; min-height:200px;">
                        Loading products...
                    </div>
                </div>
            </div>
            
            <div style="padding:20px 30px; border-top:1px solid #eee; background:#fafafa; display:flex; gap:10px; justify-content:flex-end;">
                <button onclick="BusinessManagementModule.openAddProductForm(${businessId})" style="padding:12px 24px; background:#0ea5e9; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">+ Add Product Here</button>
            </div>
        `;

        const overlay = document.createElement('div');
        overlay.id = 'add-products-gallery-overlay';
        overlay.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10002;';
        overlay.onclick = () => {
            modal.remove();
            overlay.remove();
        };

        document.body.appendChild(overlay);
        document.body.appendChild(modal);
        
        // Fetch existing products
        fetch(`/api/businesses/${businessId}`, { credentials: 'include' })
            .then(r => r.json())
            .then(data => {
                const gallery = document.getElementById('products-gallery');
                const business = data.data;
                
                if (business && business.products && business.products.length > 0) {
                    gallery.innerHTML = business.products.map(item => `
                        <div style="background:white; border:1px solid #e0e0e0; border-radius:8px; overflow:hidden; text-align:center; transition:transform 0.2s, box-shadow 0.2s; position:relative; opacity:${item.is_available ? '1' : '0.6'};" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 15px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            <div style="position:relative;">
                                ${item.image_url ? `<img src="${item.image_url}" style="width:100%; height:140px; object-fit:cover; filter:${item.is_available ? 'none' : 'grayscale(100%)'};">` : `<div style="width:100%; height:140px; background:#f0f0f0; display:flex; align-items:center; justify-content:center;"><i class="fas fa-image" style="color:#ccc; font-size:40px;"></i></div>`}
                                <div style="position:absolute; top:8px; right:8px; display:flex; gap:6px;">
                                    <button onclick="BusinessManagementModule.toggleProductAvailability(${item.id}, ${businessId}, ${!item.is_available})" style="background:${item.is_available ? '#28a745' : '#ffc107'}; color:white; border:none; border-radius:50%; width:32px; height:32px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:16px; opacity:0.9;" title="${item.is_available ? 'Mark Unavailable' : 'Mark Available'}"><i class="fas fa-${item.is_available ? 'eye' : 'eye-slash'}"></i></button>
                                    <button onclick="BusinessManagementModule.deleteProduct(${item.id}, ${businessId})" style="background:#dc3545; color:white; border:none; border-radius:50%; width:32px; height:32px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:16px; opacity:0.9;" title="Delete"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                            <div style="padding:15px;">
                                <p style="margin:0; font-weight:600; font-size:14px; color:#333;">${item.name}</p>
                                <p style="margin:5px 0 0 0; color:#999; font-size:12px;">Stock: ${item.stock}</p>
                                <p style="margin:10px 0 0 0; color:#0ea5e9; font-weight:700; font-size:16px;">₱${parseFloat(item.price).toFixed(2)}</p>
                                <button style="margin-top:10px; padding:8px 16px; background:${item.is_available ? '#0ea5e9' : '#6c757d'}; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:600; font-size:12px; width:100%;">${item.is_available ? '✓ Available' : '✗ Unavailable'}</button>
                            </div>
                        </div>
                    `).join('');
                } else {
                    // Show empty state with add button
                    gallery.innerHTML = '<div style="display:flex; align-items:center; justify-content:center; color:#999; font-size:14px; grid-column:1/-1; min-height:200px;">No products yet. Click "+ Add Product Here" to get started!</div>';
                }
            })
            .catch(err => {
                console.error('Error:', err);
                const gallery = document.getElementById('products-gallery');
                gallery.innerHTML = '<div style="display:flex; align-items:center; justify-content:center; color:#999; font-size:14px; grid-column:1/-1; min-height:200px;">No products yet</div>';
            });
    };

    const openAddProductForm = (businessId) => {
        const modal = document.createElement('div');
        modal.id = 'add-product-form-modal';
        modal.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:10004; padding:30px; max-width:600px; width:90%; max-height:85vh; overflow-y:auto;';
        
        modal.innerHTML = `
            <button onclick="document.getElementById('add-product-form-modal').remove(); document.getElementById('add-product-form-overlay').remove();" style="position:absolute; top:15px; right:15px; background:none; border:none; font-size:24px; color:#999; cursor:pointer;">&times;</button>
            
            <h2 style="margin:0 0 20px 0; color:#333; font-size:20px;">📦 Add Product</h2>
            
            <div style="margin-bottom:20px;">
                <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Product Name</label>
                <input type="text" id="product-name" placeholder="e.g., T-Shirt, Shoes" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box;">
            </div>
            
            <div style="margin-bottom:20px;">
                <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Description</label>
                <textarea id="product-description" placeholder="Describe your product..." style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box; min-height:80px; resize:vertical;"></textarea>
            </div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px;">
                <div>
                    <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Price (₱)</label>
                    <input type="number" id="product-price" placeholder="0.00" step="0.01" min="0" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Stock</label>
                    <input type="number" id="product-stock" placeholder="0" min="0" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box;">
                </div>
            </div>
            
            <div style="margin-bottom:20px;">
                <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Product Image</label>
                <input type="file" id="product-image" accept="image/*" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box;">
                <small style="color:#666; display:block; margin-top:5px;">Recommended: 500x500px or larger</small>
            </div>
            
            <div id="product-image-preview" style="margin-bottom:20px; text-align:center; display:none;">
                <img id="product-preview-img" style="max-width:100%; max-height:200px; border-radius:6px; border:1px solid #ddd;">
            </div>
            
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button onclick="BusinessManagementModule.saveProduct(${businessId})" style="padding:12px 24px; background:#0ea5e9; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Add Product</button>
            </div>
        `;

        const overlay = document.createElement('div');
        overlay.id = 'add-product-form-overlay';
        overlay.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10003;';
        overlay.onclick = () => {
            modal.remove();
            overlay.remove();
        };

        document.body.appendChild(overlay);
        document.body.appendChild(modal);
        
        document.getElementById('product-image').addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    document.getElementById('product-preview-img').src = event.target.result;
                    document.getElementById('product-image-preview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    };

    const openAddServicesModal = (businessId) => {
        // Clean up ALL modals first (not just service ones)
        cleanupAllModals();
        
        const modal = document.createElement('div');
        modal.id = 'add-services-gallery-modal';
        modal.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:10003; padding:0; max-width:1400px; width:95%; max-height:90vh; display:flex; flex-direction:column; overflow:hidden;';
        
        modal.innerHTML = `
            <div style="background:#f59e0b; color:white; padding:20px 30px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h2 style="margin:0; font-size:18px; font-weight:600;">🔧 Services</h2>
                    <p style="margin:5px 0 0 0; font-size:13px; opacity:0.9;">Browse your services</p>
                </div>
                <button onclick="document.getElementById('add-services-gallery-modal').remove(); document.getElementById('add-services-gallery-overlay').remove();" style="background:none; border:none; font-size:24px; color:white; cursor:pointer; opacity:0.8;">&times;</button>
            </div>
            
            <div style="padding:30px; overflow-y:auto; flex:1;">
                <div id="services-gallery" style="display:grid; grid-template-columns:repeat(5, 1fr); gap:20px;">
                    <div style="display:flex; align-items:center; justify-content:center; color:#999; font-size:14px; grid-column:1/-1; min-height:200px;">
                        Loading services...
                    </div>
                </div>
            </div>
            
            <div style="padding:20px 30px; border-top:1px solid #eee; background:#fafafa; display:flex; gap:10px; justify-content:flex-end;">
                <button onclick="BusinessManagementModule.openAddServiceForm(${businessId})" style="padding:12px 24px; background:#f59e0b; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">+ Add Service Here</button>
            </div>
        `;

        const overlay = document.createElement('div');
        overlay.id = 'add-services-gallery-overlay';
        overlay.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10002;';
        overlay.onclick = () => {
            modal.remove();
            overlay.remove();
        };

        document.body.appendChild(overlay);
        document.body.appendChild(modal);
        
        // Fetch existing services
        fetch(`/api/businesses/${businessId}`, { credentials: 'include' })
            .then(r => r.json())
            .then(data => {
                const gallery = document.getElementById('services-gallery');
                const business = data.data;
                
                if (business && business.services && business.services.length > 0) {
                    gallery.innerHTML = business.services.map(item => `
                        <div style="background:white; border:1px solid #e0e0e0; border-radius:8px; overflow:hidden; text-align:center; transition:transform 0.2s, box-shadow 0.2s; position:relative; opacity:${item.is_available ? '1' : '0.6'};" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 15px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            <div style="position:relative;">
                                ${item.image_url ? `<img src="${item.image_url}" style="width:100%; height:140px; object-fit:cover; filter:${item.is_available ? 'none' : 'grayscale(100%)'};">` : `<div style="width:100%; height:140px; background:#f0f0f0; display:flex; align-items:center; justify-content:center;"><i class="fas fa-image" style="color:#ccc; font-size:40px;"></i></div>`}
                                <div style="position:absolute; top:8px; right:8px; display:flex; gap:6px;">
                                    <button onclick="BusinessManagementModule.toggleServiceAvailability(${item.id}, ${businessId}, ${!item.is_available})" style="background:${item.is_available ? '#28a745' : '#ffc107'}; color:white; border:none; border-radius:50%; width:32px; height:32px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:16px; opacity:0.9;" title="${item.is_available ? 'Mark Unavailable' : 'Mark Available'}"><i class="fas fa-${item.is_available ? 'eye' : 'eye-slash'}"></i></button>
                                    <button onclick="BusinessManagementModule.deleteService(${item.id}, ${businessId})" style="background:#dc3545; color:white; border:none; border-radius:50%; width:32px; height:32px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:16px; opacity:0.9;" title="Delete"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                            <div style="padding:15px;">
                                <p style="margin:0; font-weight:600; font-size:14px; color:#333;">${item.name}</p>
                                <p style="margin:5px 0 0 0; color:#999; font-size:12px;">${item.duration} mins</p>
                                <p style="margin:10px 0 0 0; color:#f59e0b; font-weight:700; font-size:16px;">₱${parseFloat(item.price).toFixed(2)}</p>
                                <button style="margin-top:10px; padding:8px 16px; background:${item.is_available ? '#f59e0b' : '#6c757d'}; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:600; font-size:12px; width:100%;">${item.is_available ? '✓ Available' : '✗ Unavailable'}</button>
                            </div>
                        </div>
                    `).join('');
                } else {
                    // Show empty state with add button
                    gallery.innerHTML = '<div style="display:flex; align-items:center; justify-content:center; color:#999; font-size:14px; grid-column:1/-1; min-height:200px;">No services yet. Click "+ Add Service Here" to get started!</div>';
                }
            })
            .catch(err => {
                console.error('Error:', err);
                const gallery = document.getElementById('services-gallery');
                gallery.innerHTML = '<div style="display:flex; align-items:center; justify-content:center; color:#999; font-size:14px; grid-column:1/-1; min-height:200px;">No services yet</div>';
            });
    };

    const openAddServiceForm = (businessId) => {
        const modal = document.createElement('div');
        modal.id = 'add-service-form-modal';
        modal.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:10004; padding:30px; max-width:600px; width:90%; max-height:85vh; overflow-y:auto;';
        
        modal.innerHTML = `
            <button onclick="document.getElementById('add-service-form-modal').remove(); document.getElementById('add-service-form-overlay').remove();" style="position:absolute; top:15px; right:15px; background:none; border:none; font-size:24px; color:#999; cursor:pointer;">&times;</button>
            
            <h2 style="margin:0 0 20px 0; color:#333; font-size:20px;">🔧 Add Service</h2>
            
            <div style="margin-bottom:20px;">
                <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Service Name</label>
                <input type="text" id="service-name" placeholder="e.g., Hair Cut, Massage" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box;">
            </div>
            
            <div style="margin-bottom:20px;">
                <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Description</label>
                <textarea id="service-description" placeholder="Describe your service..." style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box; min-height:80px; resize:vertical;"></textarea>
            </div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px;">
                <div>
                    <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Price (₱)</label>
                    <input type="number" id="service-price" placeholder="0.00" step="0.01" min="0" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Duration (minutes)</label>
                    <input type="number" id="service-duration" placeholder="30" min="0" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box;">
                </div>
            </div>
            
            <div style="margin-bottom:20px;">
                <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Service Image</label>
                <input type="file" id="service-image" accept="image/*" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box;">
                <small style="color:#666; display:block; margin-top:5px;">Recommended: 500x500px or larger</small>
            </div>
            
            <div id="service-image-preview" style="margin-bottom:20px; text-align:center; display:none;">
                <img id="service-preview-img" style="max-width:100%; max-height:200px; border-radius:6px; border:1px solid #ddd;">
            </div>
            
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button onclick="BusinessManagementModule.saveService(${businessId})" style="padding:12px 24px; background:#f59e0b; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Add Service</button>
            </div>
        `;

        const overlay = document.createElement('div');
        overlay.id = 'add-service-form-overlay';
        overlay.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10003;';
        overlay.onclick = () => {
            modal.remove();
            overlay.remove();
        };

        document.body.appendChild(overlay);
        document.body.appendChild(modal);
        
        document.getElementById('service-image').addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    document.getElementById('service-preview-img').src = event.target.result;
                    document.getElementById('service-image-preview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    };

    const saveMenuItem = (businessId) => {
        const name = document.getElementById('menu-item-name')?.value;
        const description = document.getElementById('menu-item-description')?.value;
        const price = document.getElementById('menu-item-price')?.value;
        const category = document.getElementById('menu-item-category')?.value;
        const imageFile = document.getElementById('menu-item-image')?.files[0];

        if (!name || !price || !category) {
            showModal('Missing Fields', 'Please fill in all required fields.');
            return;
        }

        const formData = new FormData();
        formData.append('business_id', businessId);
        formData.append('name', name);
        formData.append('description', description);
        formData.append('price', price);
        formData.append('category', category);
        if (imageFile) {
            formData.append('image', imageFile);
        }

        fetch(`/api/businesses/${businessId}/menu-items`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Clean up ALL modals first
                cleanupAllModals();
                
                // Reopen gallery with fresh data
                BusinessManagementModule.openAddMenuModal(businessId);
            } else {
                showModal('Error', data.message || 'Failed to add menu item');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showModal('Error', 'Failed to add menu item. Please try again.');
        });
    };

    const saveProduct = (businessId) => {
        const name = document.getElementById('product-name')?.value;
        const description = document.getElementById('product-description')?.value;
        const price = document.getElementById('product-price')?.value;
        const stock = document.getElementById('product-stock')?.value;
        const imageFile = document.getElementById('product-image')?.files[0];

        if (!name || !price || !stock) {
            showModal('Missing Fields', 'Please fill in all required fields.');
            return;
        }

        const formData = new FormData();
        formData.append('business_id', businessId);
        formData.append('name', name);
        formData.append('description', description);
        formData.append('price', price);
        formData.append('stock', stock);
        if (imageFile) {
            formData.append('image', imageFile);
        }

        fetch(`/api/businesses/${businessId}/products`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Clean up ALL modals first
                cleanupAllModals();
                
                // Reopen gallery with fresh data
                BusinessManagementModule.openAddProductsModal(businessId);
            } else {
                showModal('Error', data.message || 'Failed to add product');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showModal('Error', 'Failed to add product. Please try again.');
        });
    };

    const saveService = (businessId) => {
        const name = document.getElementById('service-name')?.value;
        const description = document.getElementById('service-description')?.value;
        const price = document.getElementById('service-price')?.value;
        const duration = document.getElementById('service-duration')?.value;
        const imageFile = document.getElementById('service-image')?.files[0];

        if (!name || !price || !duration) {
            showModal('Missing Fields', 'Please fill in all required fields.');
            return;
        }

        const formData = new FormData();
        formData.append('business_id', businessId);
        formData.append('name', name);
        formData.append('description', description);
        formData.append('price', price);
        formData.append('duration', duration);
        if (imageFile) {
            formData.append('image', imageFile);
        }

        fetch(`/api/businesses/${businessId}/services`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Clean up ALL modals first
                cleanupAllModals();
                
                // Reopen gallery with fresh data
                BusinessManagementModule.openAddServicesModal(businessId);
            } else {
                showModal('Error', data.message || 'Failed to add service');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showModal('Error', 'Failed to add service. Please try again.');
        });
    };

    const toggleMenuAvailability = (itemId, businessId, isAvailable) => {
        fetch(`/api/menu-items/${itemId}/toggle-availability`, {
            method: 'PATCH',
            credentials: 'include',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ is_available: isAvailable })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Refresh gallery
                BusinessManagementModule.openAddMenuModal(businessId);
            } else {
                showModal('Error', data.message || 'Failed to update availability');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showModal('Error', 'Failed to update availability. Please try again.');
        });
    };

    const toggleProductAvailability = (productId, businessId, isAvailable) => {
        fetch(`/api/products/${productId}/toggle-availability`, {
            method: 'PATCH',
            credentials: 'include',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ is_available: isAvailable })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Refresh gallery
                BusinessManagementModule.openAddProductsModal(businessId);
            } else {
                showModal('Error', data.message || 'Failed to update availability');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showModal('Error', 'Failed to update availability. Please try again.');
        });
    };

    const toggleServiceAvailability = (serviceId, businessId, isAvailable) => {
        fetch(`/api/services/${serviceId}/toggle-availability`, {
            method: 'PATCH',
            credentials: 'include',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ is_available: isAvailable })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Refresh gallery
                BusinessManagementModule.openAddServicesModal(businessId);
            } else {
                showModal('Error', data.message || 'Failed to update availability');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showModal('Error', 'Failed to update availability. Please try again.');
        });
    };

    const escapeHtml = (text) => {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    const saveLocation = (businessId) => {
        const lat = document.getElementById('edit-location-lat')?.value;
        const lng = document.getElementById('edit-location-lng')?.value;

        if (!lat || !lng) {
            showModal('Missing Location', 'Please click on the map to set your business location.');
            return;
        }

        fetch(`/api/businesses/${businessId}`, {
            method: 'PUT',
            credentials: 'include',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                latitude: parseFloat(lat),
                longitude: parseFloat(lng)
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showModal('Success', 'Business location updated successfully!');
                document.getElementById('location-edit-overlay')?.click();
                setTimeout(() => openManageBusinessModal(), 500);
            } else {
                showModal('Error', data.message || 'Failed to update location');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showModal('Error', 'Failed to update location. Please try again.');
        });
    };

    const saveInfo = (businessId) => {
        const name = document.getElementById('info-business-name')?.value;
        const description = document.getElementById('info-description')?.value;
        const phone = document.getElementById('info-phone')?.value;
        const email = document.getElementById('info-email')?.value;
        const address = document.getElementById('info-address')?.value;

        if (!name || !phone || !email || !address) {
            showModal('Missing Fields', 'Please fill in all required fields.');
            return;
        }

        fetch(`/api/businesses/${businessId}`, {
            method: 'PUT',
            credentials: 'include',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                business_name: name,
                description: description,
                phone: phone,
                email: email,
                address: address
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showModal('Success', 'Business info updated successfully!');
                document.getElementById('info-edit-overlay')?.click();
                setTimeout(() => openManageBusinessModal(), 500);
            } else {
                showModal('Error', data.message || 'Failed to update info');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showModal('Error', 'Failed to update info. Please try again.');
        });
    };

    const manageTables = (businessId) => {
        const modal = document.createElement('div');
        modal.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:10002; padding:30px; max-width:500px; width:90%; max-height:85vh; overflow-y:auto;';
        
        modal.innerHTML = `
            <button onclick="this.closest('div').remove(); document.getElementById('tables-edit-overlay').remove();" style="position:absolute; top:15px; right:15px; background:none; border:none; font-size:24px; color:#999; cursor:pointer;">&times;</button>
            
            <h2 style="margin:0 0 20px 0; color:#333; font-size:20px;">🪑 Manage Restaurant Tables</h2>
            
            <div style="background:#e8f5e9; border:1px solid #4caf50; border-radius:8px; padding:12px 14px; margin-bottom:20px; display:flex; gap:10px; align-items:flex-start;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#4caf50" style="flex-shrink:0; margin-top:2px;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                <div style="font-size:12px; color:#2e7d32; line-height:1.4;">
                    <strong>Generate Tables:</strong> Create tables for your restaurant. Customers will see available tables when viewing your business.
                </div>
            </div>
            
            <div style="margin-bottom:20px;">
                <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Number of Tables</label>
                <input type="number" id="num-tables" placeholder="e.g., 10" min="1" max="50" value="10" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box;">
            </div>
            
            <div style="margin-bottom:20px;">
                <label style="display:block; color:#333; font-weight:600; margin-bottom:8px;">Seats per Table</label>
                <input type="number" id="seats-per-table" placeholder="e.g., 4" min="1" max="20" value="4" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box;">
            </div>
            
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button onclick="BusinessManagementModule.generateTables(${businessId})" style="padding:12px 24px; background:#4caf50; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Generate Tables</button>
            </div>
        `;

        const overlay = document.createElement('div');
        overlay.id = 'tables-edit-overlay';
        overlay.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10001;';
        overlay.onclick = () => {
            modal.remove();
            overlay.remove();
        };

        document.body.appendChild(overlay);
        document.body.appendChild(modal);
    };

    const generateTables = (businessId) => {
        const numTables = parseInt(document.getElementById('num-tables')?.value) || 10;
        const seatsPerTable = parseInt(document.getElementById('seats-per-table')?.value) || 4;

        if (numTables < 1 || numTables > 50) {
            showModal('Invalid Input', 'Number of tables must be between 1 and 50.');
            return;
        }

        if (seatsPerTable < 1 || seatsPerTable > 20) {
            showModal('Invalid Input', 'Seats per table must be between 1 and 20.');
            return;
        }

        fetch(`/api/businesses/${businessId}/generate-tables`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                number_of_tables: numTables,
                seats_per_table: seatsPerTable
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showModal('Success', `✅ ${numTables} tables with ${seatsPerTable} seats each have been created! Customers can now see your tables.`);
                document.getElementById('tables-edit-overlay')?.click();
                setTimeout(() => openManageBusinessModal(), 500);
            } else {
                showModal('Error', data.message || 'Failed to generate tables');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showModal('Error', 'Failed to generate tables. Please try again.');
        });
    };

    const viewTableStatus = (businessId) => {
        fetch(`/api/tables?business_id=${businessId}`, { credentials: 'include' })
            .then(r => r.json())
            .then(response => {
                let tables = [];
                if (response.data && response.data.data && Array.isArray(response.data.data)) {
                    tables = response.data.data;
                } else if (Array.isArray(response.data)) {
                    tables = response.data;
                }

                const modal = document.createElement('div');
                modal.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:10002; padding:30px; max-width:700px; width:90%; max-height:85vh; overflow-y:auto;';
                
                let html = `
                    <button onclick="document.getElementById('table-status-overlay').click();" style="position:absolute; top:15px; right:15px; background:none; border:none; font-size:24px; color:#999; cursor:pointer;">&times;</button>
                    <h2 style="margin:0 0 20px 0; color:#333; font-size:20px;">Table Status Management</h2>
                    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap:12px;">
                `;

                tables.forEach(table => {
                    const statusColor = table.status === 'available' ? '#27ae60' : table.status === 'reserved' ? '#f39c12' : '#e74c3c';
                    const statusEmoji = table.status === 'available' ? '🟢' : table.status === 'reserved' ? '🟡' : '🔴';
                    
                    html += `
                        <div style="background:white; border:2px solid ${statusColor}; border-radius:8px; padding:12px; text-align:center;">
                            <div style="font-size:28px; margin-bottom:8px;">🪑</div>
                            <div style="font-weight:700; color:#333; margin-bottom:4px;">Table ${table.table_number}</div>
                            <div style="font-size:11px; color:#666; margin-bottom:8px;">${table.capacity} seats</div>
                            <select onchange="BusinessManagementModule.updateTableStatus(${table.id}, this.value)" style="width:100%; padding:6px; border:1px solid ${statusColor}; border-radius:4px; font-size:12px; cursor:pointer;">
                                <option value="available" ${table.status === 'available' ? 'selected' : ''}>🟢 Available</option>
                                <option value="reserved" ${table.status === 'reserved' ? 'selected' : ''}>🟡 Reserved</option>
                                <option value="occupied" ${table.status === 'occupied' ? 'selected' : ''}>🔴 Occupied</option>
                            </select>
                        </div>
                    `;
                });

                html += `</div>`;
                modal.innerHTML = html;

                const overlay = document.createElement('div');
                overlay.id = 'table-status-overlay';
                overlay.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10001;';
                overlay.onclick = () => {
                    modal.remove();
                    overlay.remove();
                };

                document.body.appendChild(overlay);
                document.body.appendChild(modal);
            })
            .catch(err => {
                console.error('Error:', err);
                showModal('Error', 'Failed to load table status');
            });
    };

    const updateTableStatus = (tableId, newStatus) => {
        fetch(`/api/tables/${tableId}`, {
            method: 'PUT',
            credentials: 'include',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ status: newStatus })
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                showModal('Error', data.message || 'Failed to update table status');
            }
        })
        .catch(err => {
            showModal('Error', 'Failed to update table status');
        });
    };

    const deleteMenuItem = (itemId, businessId) => {
        fetch(`/api/menu-items/${itemId}`, {
            method: 'DELETE',
            credentials: 'include',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Clean up ALL modals and overlays
                cleanupAllModals();
                
                // Reopen gallery with refreshed data
                BusinessManagementModule.openAddMenuModal(businessId);
            } else {
                showModal('Error', data.message || 'Failed to delete menu item');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showModal('Error', 'Failed to delete menu item. Please try again.');
        });
    };

    const confirmDeleteMenuItem = (itemId, businessId) => {
        // This function is no longer used but kept for backward compatibility
    };

    const deleteProduct = (productId, businessId) => {
        fetch(`/api/products/${productId}`, {
            method: 'DELETE',
            credentials: 'include',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Clean up ALL modals and overlays
                cleanupAllModals();
                
                // Reopen gallery with refreshed data
                BusinessManagementModule.openAddProductsModal(businessId);
            } else {
                showModal('Error', data.message || 'Failed to delete product');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showModal('Error', 'Failed to delete product. Please try again.');
        });
    };

    const confirmDeleteProduct = (productId, businessId) => {
        // This function is no longer used but kept for backward compatibility
    };

    const deleteService = (serviceId, businessId) => {
        fetch(`/api/services/${serviceId}`, {
            method: 'DELETE',
            credentials: 'include',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Clean up ALL modals and overlays
                cleanupAllModals();
                // Reopen gallery with refreshed data
                BusinessManagementModule.openAddServicesModal(businessId);
            } else {
                showModal('Error', data.message || 'Failed to delete service');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showModal('Error', 'Failed to delete service. Please try again.');
        });
    };

    const confirmDeleteService = (serviceId, businessId) => {
        // This function is no longer used but kept for backward compatibility
    };

    return {
        init,
        openManageBusinessModal,
        editLocation,
        editInfo,
        saveLocation,
        saveInfo,
        updateSelectedBusiness,
        manageTables,
        generateTables,
        viewTableStatus,
        updateTableStatus,
        openAddMenuModal,
        openAddMenuItemForm,
        openAddProductsModal,
        openAddProductForm,
        openAddServicesModal,
        openAddServiceForm,
        saveMenuItem,
        saveProduct,
        saveService,
        deleteMenuItem,
        deleteProduct,
        deleteService,
        toggleMenuAvailability,
        toggleProductAvailability,
        toggleServiceAvailability,
        confirmDeleteMenuItem,
        confirmDeleteProduct,
        confirmDeleteService
    };
})();

// Global wrapper function
window.openManageBusinessModal = function() {
    BusinessManagementModule.openManageBusinessModal();
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', BusinessManagementModule.init);
} else {
    BusinessManagementModule.init();
}
