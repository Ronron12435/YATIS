const BusinessManagementModule = (() => {
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const init = () => {
        // Module initialization
    };

    const openManageBusinessModal = () => {
        // Fetch all businesses for this user
        fetch('/api/user/businesses', { credentials: 'include' })
            .then(r => r.json())
            .then(response => {
                const businesses = response.data || [];
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
        modal.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:10001; padding:30px; max-width:600px; width:90%; max-height:80vh; overflow-y:auto;';
        
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
        
        modal.innerHTML = `
            <button onclick="this.closest('div').remove(); document.getElementById('manage-business-overlay').remove();" style="position:absolute; top:15px; right:15px; background:none; border:none; font-size:24px; color:#999; cursor:pointer;">&times;</button>
            
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
        `;

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
                <button onclick="this.closest('div').remove(); document.getElementById('location-edit-overlay').remove();" style="padding:12px 24px; background:#95a5a6; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Cancel</button>
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
                        <button onclick="const overlay = document.getElementById('info-edit-overlay'); this.closest('div').remove(); if(overlay) overlay.remove();" style="padding:12px 24px; background:#95a5a6; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Cancel</button>
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
        modal.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; padding:30px; max-width:400px; width:90%; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:10002; text-align:center;';
        
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
        overlay.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10001;';
        overlay.onclick = () => {
            modal.remove();
            overlay.remove();
        };

        document.body.appendChild(overlay);
        document.body.appendChild(modal);
    };

    const updateSelectedBusiness = (index) => {
        const business = window.managedBusinesses[index];
        if (!business) return;
        
        document.getElementById('business-name-display').textContent = escapeHtml(business.name);
        document.getElementById('business-info-display').textContent = `${escapeHtml(business.category)} • ${escapeHtml(business.address)}`;
        
        // Update button onclick handlers
        const buttons = document.querySelectorAll('[onclick*="editLocation"], [onclick*="editInfo"]');
        buttons.forEach(btn => {
            btn.onclick = null;
            if (btn.textContent.includes('Edit Location')) {
                btn.onclick = () => BusinessManagementModule.editLocation(business.id);
            } else if (btn.textContent.includes('Edit Info')) {
                btn.onclick = () => BusinessManagementModule.editInfo(business.id);
            }
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

    return {
        init,
        openManageBusinessModal,
        editLocation,
        editInfo,
        saveLocation,
        saveInfo,
        updateSelectedBusiness
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
