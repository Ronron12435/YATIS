let peopleMap = null;
let fcUserId = null;
let fcPollInterval = null;

window.initPeopleMap = function() {
    const container = document.getElementById('people-map-container');
    if (!container) return;
    
    // If map already exists, just reload the people data
    if (peopleMap) { 
        setTimeout(() => {
            peopleMap.invalidateSize();
            loadPeopleMarkers();
        }, 100); 
        return; 
    }

    peopleMap = L.map('people-map-container', {
        zoomControl: false,
        minZoom: 13,
        maxZoom: 19
    }).setView([10.8967, 123.4253], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors', maxZoom: 19
    }).addTo(peopleMap);

    // Update user location in background (send to server only, no marker display)
    function updateMyLocation(lat, lng) {
        fetch('/api/profile/update-location', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ latitude: lat, longitude: lng })
        });
    }

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            pos => {
                updateMyLocation(pos.coords.latitude, pos.coords.longitude);
                // Watch position continuously - updates server in background
                navigator.geolocation.watchPosition(
                    pos => updateMyLocation(pos.coords.latitude, pos.coords.longitude),
                    err => {
                        // Silently ignore GPS errors
                    },
                    { enableHighAccuracy: false, maximumAge: 30000, timeout: 30000 }
                );
            },
            () => {
                const userLat = document.querySelector('meta[name="user-latitude"]')?.content;
                const userLng = document.querySelector('meta[name="user-longitude"]')?.content;
                if (userLat && userLng) {
                    updateMyLocation(parseFloat(userLat), parseFloat(userLng));
                } else {
                    fetch('https://ipapi.co/json/').then(r => r.json()).then(d => { if(d.latitude) updateMyLocation(d.latitude, d.longitude); }).catch(()=>{});
                }
            },
            { enableHighAccuracy: false, timeout: 15000, maximumAge: 30000 }
        );
    } else {
        const userLat = document.querySelector('meta[name="user-latitude"]')?.content;
        const userLng = document.querySelector('meta[name="user-longitude"]')?.content;
        if (userLat && userLng) {
            updateMyLocation(parseFloat(userLat), parseFloat(userLng));
        }
    }

    // Delay loading markers to ensure map is ready
    setTimeout(() => {
        loadPeopleMarkers();
    }, 500);
    
    loadFriendsList();
    loadFriendRequests();
};

function loadPeopleMarkers() {
    if (!peopleMap) {
        return;
    }
    
    fetch('/api/people-map', { credentials: 'include' })
        .then(r => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        })
        .then(response => {
            const users = response.data || [];

            
            if (!users.length) {
                console.warn('⚠️ No users returned from API');
                return;
            }
            
            // Clear existing markers (except your own)
            if (window._existingMarkers) {
                window._existingMarkers.forEach(marker => {
                    try {
                        peopleMap.removeLayer(marker);
                    } catch (e) {
                        // Silently ignore marker removal errors
                    }
                });
            }
            window._existingMarkers = [];
            
            let bounds = L.latLngBounds();
            
            users.forEach(user => {
                try {
                    const lat = parseFloat(user.latitude);
                    const lng = parseFloat(user.longitude);
                    
                    // Validate coordinates are within Philippines bounds (5-20 lat, 120-130 lng)
                    if (isNaN(lat) || isNaN(lng) || lat < 5 || lat > 20 || lng < 120 || lng > 130) {
                        return;
                    }
                    
                    const initials = ((user.first_name||'')[0]||'').toUpperCase() + ((user.last_name||'')[0]||'').toUpperCase();
                    
                    // Create colored circle with initials
                    const colors = ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#e67e22', '#34495e'];
                    const colorIndex = user.id % colors.length;
                    const bgColor = colors[colorIndex];
                    
                    // Create a custom marker with circle and initials combined
                    const markerIcon = L.divIcon({
                        html: `<div style="width:50px;height:50px;border-radius:50%;background:${bgColor};display:flex;align-items:center;justify-content:center;color:white;font-size:18px;font-weight:700;border:3px solid white;box-shadow:0 4px 12px rgba(0,0,0,0.4);text-align:center;line-height:1;transform:scale(1);">${initials}</div>`,
                        iconSize: [50, 50],
                        iconAnchor: [25, 25],
                        popupAnchor: [0, -70],
                        className: 'user-marker-fixed'
                    });
                    
                    let actionBtn = '';
                    if (user.friendship_status === 'friends') {
                        actionBtn = `<div style="width:100%;padding:12px;background:#27ae60;color:white;border-radius:8px;text-align:center;font-weight:700;font-size:14px;">✓ Already Friends</div>`;
                    } else if (user.friendship_status === 'request_sent') {
                        actionBtn = `<div style="width:100%;padding:12px;background:#f39c12;color:white;border-radius:8px;text-align:center;font-weight:700;font-size:14px;">⏳ Request Sent</div>`;
                    } else if (user.friendship_status === 'request_received') {
                        actionBtn = `<button onclick="acceptFriend(${user.id})" style="width:100%;padding:12px;background:#27ae60;color:white;border:none;border-radius:8px;font-weight:700;font-size:14px;cursor:pointer;">✓ Accept Request</button>`;
                    } else {
                        actionBtn = `<button onclick="addFriend(${user.id}, '${user.username}')" style="width:100%;padding:12px;background:#3498db;color:white;border:none;border-radius:8px;font-weight:700;font-size:14px;cursor:pointer;">+ Add Friend</button>`;
                    }
                    
                    const location = user.location_name || 'Nearby';
                    const popupHtml = `<div style="width:240px;border-radius:12px;font-family:sans-serif;">
                        <div style="background:linear-gradient(135deg,${bgColor},${bgColor}dd);padding:20px;text-align:center;">
                            <div style="width:70px;height:70px;border-radius:50%;background:white;margin:0 auto 10px;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;color:${bgColor};">${initials}</div>
                            <div style="color:white;font-weight:700;font-size:16px;">${user.username}</div>
                            <div style="color:rgba(255,255,255,0.85);font-size:13px;">${user.first_name} ${user.last_name}</div>
                        </div>
                        <div style="background:white;padding:15px;text-align:center;">
                            <div style="color:#555;font-size:13px;margin-bottom:8px;">📍 ${location}</div>
                            <span style="background:#3498db;color:white;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:600;">User</span>
                            <div style="margin-top:12px;">${actionBtn}</div>
                        </div>
                    </div>`;
                    
                    const marker = L.marker([lat, lng], { icon: markerIcon }).addTo(peopleMap);
                    marker.bindPopup(popupHtml, { maxWidth: 260, className: 'people-popup', autoPan: false });
                    window._existingMarkers.push(marker);
                    bounds.extend([lat, lng]);
                } catch (e) {
                    // Silently ignore marker errors
                }
            });
            
            // Display nearby users list
            displayNearbyUsersList(users);
        })
        .catch(err => {
            // Silently ignore fetch errors
        });
}

function displayNearbyUsersList(users) {
    const list = document.getElementById('nearby-users-list');
    
    if (!list) return;
    
    if (!users || !users.length) {
        list.innerHTML = '<p style="color:#999;grid-column:1/-1;text-align:center;padding:20px;">No nearby users found.</p>';
        return;
    }
    
    list.innerHTML = users.map(user => {
        const ini = ((user.first_name||'')[0]||'').toUpperCase() + ((user.last_name||'')[0]||'').toUpperCase();
        const colors = ['#2ecc71', '#f39c12', '#3498db', '#e74c3c', '#9b59b6', '#1abc9c', '#e67e22', '#34495e'];
        const colorIndex = user.id % colors.length;
        const bgColor = colors[colorIndex];
        
        let actionBtn = '';
        if (user.friendship_status === 'friends') {
            actionBtn = `<button style="width:100%;padding:10px;background:#27ae60;color:white;border:none;border-radius:6px;font-weight:700;font-size:13px;cursor:pointer;">✓ Friends</button>`;
        } else if (user.friendship_status === 'request_sent') {
            actionBtn = `<button style="width:100%;padding:10px;background:#f39c12;color:white;border:none;border-radius:6px;font-weight:700;font-size:13px;cursor:pointer;">⏳ Pending</button>`;
        } else if (user.friendship_status === 'request_received') {
            actionBtn = `<button onclick="acceptFriend(${user.id})" style="width:100%;padding:10px;background:#27ae60;color:white;border:none;border-radius:6px;font-weight:700;font-size:13px;cursor:pointer;">✓ Accept</button>`;
        } else {
            actionBtn = `<button onclick="addFriend(${user.id}, '${user.username}')" style="width:100%;padding:10px;background:#3498db;color:white;border:none;border-radius:6px;font-weight:700;font-size:13px;cursor:pointer;">+ Add Friend</button>`;
        }
        
        return `<div style="background:white;border:1px solid #e0e0e0;border-radius:10px;padding:15px;box-shadow:0 2px 8px rgba(0,0,0,0.1);text-align:center;">
            <div style="width:60px;height:60px;border-radius:50%;background:${bgColor};color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:20px;margin:0 auto 10px;">${ini}</div>
            <div style="font-weight:700;color:#1a3a52;font-size:14px;margin-bottom:2px;">${user.first_name} ${user.last_name}</div>
            <div style="color:#999;font-size:12px;margin-bottom:8px;">@${user.username}</div>
            <div style="color:#666;font-size:11px;margin-bottom:12px;">📍 ${user.location_name || 'Nearby'}</div>
            <div style="display:flex;gap:8px;flex-direction:column;">
                ${actionBtn}
                <button onclick="openFriendChat(${user.id},'${user.first_name} ${user.last_name}','${ini}')" style="width:100%;padding:10px;background:#ecf0f1;color:#2c3e50;border:none;border-radius:6px;font-weight:600;font-size:13px;cursor:pointer;">💬 Message</button>
            </div>
        </div>`;
    }).join('');
}

function loadFriendRequests() {
    fetch('/api/friends/requests')
        .then(r => r.json())
        .then(data => {
            document.getElementById('pending-count').textContent = Array.isArray(data.data) ? data.data.length : 0;
            const list = document.getElementById('friend-requests-list');
            if (!Array.isArray(data.data) || !data.data.length) { list.innerHTML = '<p style="color:#999;">No pending requests.</p>'; return; }
            list.innerHTML = data.data.map(f => {
                const ini = ((f.first_name||'')[0]||'').toUpperCase() + ((f.last_name||'')[0]||'').toUpperCase();
                return `<div style="display:flex;align-items:center;gap:14px;padding:12px 0;border-bottom:1px solid #f0f0f0;">
                    <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#3498db,#2980b9);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px;flex-shrink:0;">${ini}</div>
                    <div style="flex:1;"><strong style="display:block;">${f.first_name} ${f.last_name}</strong><small style="color:#999;">@${f.username}</small></div>
                    <div style="display:flex;gap:8px;flex-shrink:0;">
                        <button onclick="acceptFriend(${f.id})" style="padding:8px 14px;background:#27ae60;color:white;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;"><i class="fas fa-check"></i> Accept</button>
                        <button onclick="rejectFriend(${f.id})" style="padding:8px 14px;background:#e74c3c;color:white;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;"><i class="fas fa-times"></i> Reject</button>
                    </div>
                </div>`;
            }).join('');
        }).catch(() => { document.getElementById('pending-count').textContent = 0; });
    document.getElementById('sent-count').textContent = 0;
}

function loadFriendsList() {
    fetch('/api/friends-list', { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            const friends = data.data || [];
            document.getElementById('friends-count').textContent = Array.isArray(friends) ? friends.length : 0;
            const list = document.getElementById('friends-list');
            
            if (!friends.length) { 
                list.innerHTML = '<p style="color:#999;padding:20px;text-align:center;">No friends yet.</p>'; 
                return; 
            }
            
            list.innerHTML = friends.map(f => {
                const ini = ((f.first_name||'')[0]||'').toUpperCase() + ((f.last_name||'')[0]||'').toUpperCase();
                
                return `<div style="display:flex;align-items:center;justify-content:space-between;padding:15px 0;border-bottom:1px solid #f0f0f0;">
                    <div style="display:flex;align-items:center;gap:12px;flex:1;">
                        <div style="width:50px;height:50px;border-radius:50%;background:linear-gradient(135deg,#3498db,#2980b9);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;">${ini}</div>
                        <div>
                            <strong style="display:block;color:#1a3a52;font-size:15px;">${f.first_name} ${f.last_name}</strong>
                            <small style="color:#999;font-size:13px;">@${f.username}</small>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button onclick="openFriendChat(${f.id},'${f.first_name} ${f.last_name}','${ini}')" style="padding:8px 16px;background:#3498db;color:white;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;display:flex;align-items:center;gap:5px;"><i class="fas fa-comment"></i> Message</button>
                        <button onclick="viewUserProfile(${f.id})" style="padding:8px 16px;background:#9b59b6;color:white;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;display:flex;align-items:center;gap:5px;"><i class="fas fa-eye"></i> View</button>
                        <button onclick="unfriend(${f.id})" style="padding:8px 16px;background:#e74c3c;color:white;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;display:flex;align-items:center;gap:5px;"><i class="fas fa-times"></i> Unfriend</button>
                    </div>
                </div>`;
            }).join('');
            
            // After friends list is loaded, restore active chat if it exists
            const activeChatUserId = sessionStorage.getItem('activeChatUserId');
            if (activeChatUserId) {
                const chatName = sessionStorage.getItem('activeChatName');
                const chatInitials = sessionStorage.getItem('activeChatInitials');
                setTimeout(() => {
                    openFriendChat(parseInt(activeChatUserId), chatName, chatInitials);
                }, 50);
            }
        }).catch(err => { 
            document.getElementById('friends-count').textContent = 0; 
        });
}

window.openFriendChat = function(userId, name, initials) {
    // Clear any existing interval before starting a new one
    if (fcPollInterval) {
        clearInterval(fcPollInterval);
        fcPollInterval = null;
    }
    
    fcUserId = userId;
    document.getElementById('fc-name').textContent = name;
    document.getElementById('fc-avatar').textContent = initials;
    document.getElementById('friends-list-view').style.display = 'none';
    document.getElementById('friends-chat-view').style.display = 'block';
    
    // Save chat state to sessionStorage
    sessionStorage.setItem('activeChatUserId', userId);
    sessionStorage.setItem('activeChatName', name);
    sessionStorage.setItem('activeChatInitials', initials);
    
    loadFriendMessages();
    
    // Start polling only after initial load
    fcPollInterval = setInterval(loadFriendMessages, 3000);
};

window.closeFriendChat = function() {
    clearInterval(fcPollInterval);
    fcUserId = null;
    document.getElementById('friends-chat-view').style.display = 'none';
    document.getElementById('friends-list-view').style.display = 'block';
    
    // Clear chat state from sessionStorage
    sessionStorage.removeItem('activeChatUserId');
    sessionStorage.removeItem('activeChatName');
    sessionStorage.removeItem('activeChatInitials');
    
    if (typeof updateBadges === 'function') updateBadges();
};

function loadFriendMessages() {
    if (!fcUserId) return;
    fetch('/api/messages/' + fcUserId)
        .then(r => {
            if (!r.ok) {
                throw new Error(`HTTP ${r.status}: ${r.statusText}`);
            }
            return r.json();
        })
        .then(response => {
            const msgs = response.data || [];
            const box = document.getElementById('fc-messages');
            const myId = document.querySelector('meta[name="user-id"]')?.content;
            if (!Array.isArray(msgs) || !msgs.length) {
                box.innerHTML = '<p style="color:#999;text-align:center;margin-top:40px;">No messages yet. Say hello!</p>';
                if (typeof updateBadges === 'function') updateBadges();
                return;
            }
            box.innerHTML = msgs.map(m => {
                const mine = m.sender_id == myId;
                const text = m.content || m.message || '';
                return `<div style="display:flex;justify-content:${mine ? 'flex-end' : 'flex-start'};">
                    <div style="max-width:65%;padding:10px 14px;border-radius:${mine ? '12px 12px 0 12px' : '12px 12px 12px 0'};background:${mine ? '#3498db' : 'white'};color:${mine ? 'white' : '#333'};box-shadow:0 1px 4px rgba(0,0,0,0.1);font-size:14px;">${text}</div>
                </div>`;
            }).join('');
            box.scrollTop = box.scrollHeight;
            if (typeof updateBadges === 'function') updateBadges();
        })
        .catch(err => {
            const box = document.getElementById('fc-messages');
            if (box) {
                box.innerHTML = `<p style="color:#e74c3c;text-align:center;margin-top:40px;">Error loading messages</p>`;
            }
            // Stop polling on error
            clearInterval(fcPollInterval);
        });
}

window.sendFriendMessage = function() {
    const input = document.getElementById('fc-input');
    const msg = input.value.trim();
    if (!msg || !fcUserId) return;
    input.value = '';
    fetch('/api/messages', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'application/json' },
        body: JSON.stringify({ recipient_id: fcUserId, message: msg }),
        credentials: 'include'
    }).then(r => { if (r.ok) loadFriendMessages(); }).catch(() => {});
};

// Keep openChat as alias so map popups still work
window.openChat = window.openFriendChat;

window.unfriend = function(userId) {
    // Create modal
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    `;
    
    modal.innerHTML = `
        <div style="background: white; border-radius: 12px; padding: 30px; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); text-align: center;">
            <h2 style="margin: 0 0 15px 0; color: #1a3a52; font-size: 20px;">Remove Friend?</h2>
            <p style="margin: 0 0 25px 0; color: #666; font-size: 15px;">Are you sure you want to remove this friend? This action cannot be undone.</p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button onclick="this.closest('div').parentElement.remove()" style="padding: 10px 24px; background: #ecf0f1; color: #2c3e50; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">Cancel</button>
                <button onclick="confirmUnfriend(${userId}); this.closest('div').parentElement.remove();" style="padding: 10px 24px; background: #e74c3c; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">Remove</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
};

window.confirmUnfriend = function(userId) {
    fetch(`/api/friends/${userId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        credentials: 'include'
    }).then(() => location.reload());
};

window.addFriend = function(userId, userName) {
    // Create modal
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    `;
    
    modal.innerHTML = `
        <div style="background: white; border-radius: 12px; padding: 30px; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); text-align: center;">
            <h2 style="margin: 0 0 15px 0; color: #1a3a52; font-size: 20px;">Send Friend Request?</h2>
            <p style="margin: 0 0 25px 0; color: #666; font-size: 15px;">Send a friend request to <strong>${userName}</strong>?</p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button onclick="this.closest('div').parentElement.remove()" style="padding: 10px 24px; background: #ecf0f1; color: #2c3e50; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">Cancel</button>
                <button onclick="confirmAddFriend(${userId}, '${userName}'); this.closest('div').parentElement.remove();" style="padding: 10px 24px; background: #3498db; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">Send Request</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
};

window.confirmAddFriend = function(userId, userName) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    fetch(`/api/friends/${userId}/add`, {
        method: 'POST',
        headers: { 
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'include'
    }).then(r => {
        if (!r.ok) {
            return r.text().then(text => {
                throw new Error(`HTTP ${r.status}: ${text.substring(0, 100)}`);
            });
        }
        return r.json();
    }).then(response => {
        if (response.success) {
            // Show success modal
            const successModal = document.createElement('div');
            successModal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
            `;
            
            successModal.innerHTML = `
                <div style="background: white; border-radius: 12px; padding: 30px; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); text-align: center;">
                    <h2 style="margin: 0 0 15px 0; color: #27ae60; font-size: 20px;">✓ Request Sent!</h2>
                    <p style="margin: 0 0 25px 0; color: #666; font-size: 15px;">Friend request sent to <strong>${userName}</strong>!</p>
                    <button onclick="this.closest('div').parentElement.remove(); location.reload();" style="padding: 10px 24px; background: #27ae60; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">OK</button>
                </div>
            `;
            
            document.body.appendChild(successModal);
        } else {
            // Show error modal
            const errorModal = document.createElement('div');
            errorModal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
            `;
            
            errorModal.innerHTML = `
                <div style="background: white; border-radius: 12px; padding: 30px; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); text-align: center;">
                    <h2 style="margin: 0 0 15px 0; color: #e74c3c; font-size: 20px;">✗ Error</h2>
                    <p style="margin: 0 0 25px 0; color: #666; font-size: 15px;">${response.message || 'Failed to send friend request'}</p>
                    <button onclick="this.closest('div').parentElement.remove();" style="padding: 10px 24px; background: #e74c3c; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">OK</button>
                </div>
            `;
            
            document.body.appendChild(errorModal);
        }
    }).catch(err => {
        // Show error modal
        const errorModal = document.createElement('div');
        errorModal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        `;
        
        errorModal.innerHTML = `
            <div style="background: white; border-radius: 12px; padding: 30px; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); text-align: center;">
                <h2 style="margin: 0 0 15px 0; color: #e74c3c; font-size: 20px;">✗ Error</h2>
                <p style="margin: 0 0 25px 0; color: #666; font-size: 15px;">Error sending friend request: ${err.message}</p>
                <button onclick="this.closest('div').parentElement.remove();" style="padding: 10px 24px; background: #e74c3c; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">OK</button>
            </div>
        `;
        
        document.body.appendChild(errorModal);
    });
};

window.acceptFriend = function(userId) {
    fetch(`/api/friends/${userId}/accept`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        credentials: 'include'
    }).then(r => r.json()).then(response => {
        if (response.success) {
            // Show success modal
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
            `;
            
            modal.innerHTML = `
                <div style="background: white; border-radius: 12px; padding: 30px; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); text-align: center;">
                    <h2 style="margin: 0 0 15px 0; color: #27ae60; font-size: 20px;">✓ Friend Request Accepted!</h2>
                    <p style="margin: 0 0 25px 0; color: #666; font-size: 15px;">You are now friends!</p>
                    <button onclick="this.closest('div').parentElement.remove(); location.reload();" style="padding: 10px 24px; background: #27ae60; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">OK</button>
                </div>
            `;
            
            document.body.appendChild(modal);
            loadFriendsList();
        } else {
            // Show error modal
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
            `;
            
            modal.innerHTML = `
                <div style="background: white; border-radius: 12px; padding: 30px; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); text-align: center;">
                    <h2 style="margin: 0 0 15px 0; color: #e74c3c; font-size: 20px;">✗ Error</h2>
                    <p style="margin: 0 0 25px 0; color: #666; font-size: 15px;">${response.message || 'Failed to accept request'}</p>
                    <button onclick="this.closest('div').parentElement.remove();" style="padding: 10px 24px; background: #e74c3c; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">OK</button>
                </div>
            `;
            
            document.body.appendChild(modal);
        }
    }).catch(err => {
        // Show error modal
        const modal = document.createElement('div');
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        `;
        
        modal.innerHTML = `
            <div style="background: white; border-radius: 12px; padding: 30px; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); text-align: center;">
                <h2 style="margin: 0 0 15px 0; color: #e74c3c; font-size: 20px;">✗ Error</h2>
                <p style="margin: 0 0 25px 0; color: #666; font-size: 15px;">Error accepting friend request</p>
                <button onclick="this.closest('div').parentElement.remove();" style="padding: 10px 24px; background: #e74c3c; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">OK</button>
            </div>
        `;
        
        document.body.appendChild(modal);
    });
};

window.rejectFriend = function(userId) {
    // Create confirmation modal
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    `;
    
    modal.innerHTML = `
        <div style="background: white; border-radius: 12px; padding: 30px; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); text-align: center;">
            <h2 style="margin: 0 0 15px 0; color: #1a3a52; font-size: 20px;">Reject Request?</h2>
            <p style="margin: 0 0 25px 0; color: #666; font-size: 15px;">Are you sure you want to reject this friend request?</p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button onclick="this.closest('div').parentElement.remove()" style="padding: 10px 24px; background: #ecf0f1; color: #2c3e50; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">Cancel</button>
                <button onclick="confirmRejectFriend(${userId}); this.closest('div').parentElement.remove();" style="padding: 10px 24px; background: #e74c3c; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">Reject</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
};

window.confirmRejectFriend = function(userId) {
    fetch(`/api/friends/${userId}/reject`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        credentials: 'include'
    }).then(r => r.json()).then(response => {
        if (response.success) {
            // Show success modal
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
            `;
            
            modal.innerHTML = `
                <div style="background: white; border-radius: 12px; padding: 30px; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); text-align: center;">
                    <h2 style="margin: 0 0 15px 0; color: #27ae60; font-size: 20px;">✓ Request Rejected</h2>
                    <p style="margin: 0 0 25px 0; color: #666; font-size: 15px;">Friend request has been rejected.</p>
                    <button onclick="this.closest('div').parentElement.remove(); location.reload();" style="padding: 10px 24px; background: #27ae60; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">OK</button>
                </div>
            `;
            
            document.body.appendChild(modal);
        } else {
            // Show error modal
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
            `;
            
            modal.innerHTML = `
                <div style="background: white; border-radius: 12px; padding: 30px; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); text-align: center;">
                    <h2 style="margin: 0 0 15px 0; color: #e74c3c; font-size: 20px;">✗ Error</h2>
                    <p style="margin: 0 0 25px 0; color: #666; font-size: 15px;">${response.message || 'Failed to reject request'}</p>
                    <button onclick="this.closest('div').parentElement.remove();" style="padding: 10px 24px; background: #e74c3c; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">OK</button>
                </div>
            `;
            
            document.body.appendChild(modal);
        }
    }).catch(err => {
        // Show error modal
        const modal = document.createElement('div');
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        `;
        
        modal.innerHTML = `
            <div style="background: white; border-radius: 12px; padding: 30px; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); text-align: center;">
                <h2 style="margin: 0 0 15px 0; color: #e74c3c; font-size: 20px;">✗ Error</h2>
                <p style="margin: 0 0 25px 0; color: #666; font-size: 15px;">Error rejecting friend request</p>
                <button onclick="this.closest('div').parentElement.remove();" style="padding: 10px 24px; background: #e74c3c; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">OK</button>
            </div>
        `;
        
        document.body.appendChild(modal);
    });
};

// Load friends list when My Friends section is shown
document.addEventListener('DOMContentLoaded', function() {
    const observer = new MutationObserver(function() {
        const section = document.getElementById('my-friends');
        if (section && section.classList.contains('active')) loadFriendsList();
    });
    const target = document.getElementById('my-friends');
    if (target) observer.observe(target, { attributes: true, attributeFilter: ['class'] });
});

// User Profile Modal
window.viewUserProfile = function(userId) {
    const url = `/api/profile/${userId}/view`;
    
    // Record this visit
    fetch(`/api/profile/${userId}/visit`, {
        method: 'POST',
        headers: { 
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        },
        credentials: 'include'
    }).catch(err => {
        // Silently fail if visit recording fails
    });
    
    fetch(url, { credentials: 'include' })
        .then(r => {
            if (!r.ok) {
                return r.text().then(text => {
                    throw new Error(`HTTP ${r.status}: ${text}`);
                });
            }
            return r.json();
        })
        .then(response => {
            if (!response.success) {
                alert('Failed to load user profile: ' + (response.message || 'Unknown error'));
                return;
            }

            const user = response.data;
            const profilePicture = user.profile_picture ? `/storage/${user.profile_picture}` : null;
            const coverPhoto = user.cover_photo ? `/storage/${user.cover_photo}` : null;

            let achievementsHtml = '';
            if (user.achievements && user.achievements.length > 0) {
                achievementsHtml = user.achievements.map(achievement => `
                    <div style="text-align:center;padding:10px;">
                        <div style="font-size:32px;margin-bottom:5px;">${achievement.badge_icon || '🏆'}</div>
                        <div style="font-weight:600;font-size:12px;color:#333;">${achievement.badge_name}</div>
                        <div style="font-size:11px;color:#999;">${achievement.description || ''}</div>
                    </div>
                `).join('');
            } else {
                achievementsHtml = '<p style="color:#999;text-align:center;padding:20px;">No achievements yet.</p>';
            }

            const modalHtml = `
                <div id="user-profile-modal" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:10000;">
                    <div style="background:white;border-radius:12px;width:90%;max-width:500px;max-height:90vh;overflow-y:auto;box-shadow:0 10px 40px rgba(0,0,0,0.3);">
                        <!-- Cover Photo -->
                        <div style="height:150px;background:linear-gradient(135deg,#3498db,#2980b9);position:relative;overflow:hidden;">
                            ${coverPhoto ? `<img src="${coverPhoto}" style="width:100%;height:100%;object-fit:cover;">` : ''}
                        </div>

                        <!-- Profile Picture -->
                        <div style="padding:0 20px;margin-top:-50px;position:relative;z-index:1;">
                            <div style="width:100px;height:100px;border-radius:50%;background:white;border:4px solid white;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.2);">
                                ${profilePicture ? `<img src="${profilePicture}" style="width:100%;height:100%;object-fit:cover;">` : `<div style="width:100%;height:100%;background:#e0e0e0;display:flex;align-items:center;justify-content:center;font-size:40px;">👤</div>`}
                            </div>
                        </div>

                        <!-- User Info -->
                        <div style="padding:20px;">
                            <h2 style="margin:0 0 5px 0;color:#1a3a52;font-size:20px;">${user.first_name} ${user.last_name}</h2>
                            <p style="margin:0 0 15px 0;color:#999;font-size:14px;">@${user.username}</p>

                            <!-- Bio -->
                            ${user.bio ? `<div style="background:#f5f5f5;padding:12px;border-radius:8px;margin-bottom:20px;color:#555;font-size:14px;line-height:1.5;">${user.bio}</div>` : ''}

                            <!-- Achievements Section -->
                            <div style="margin-top:20px;">
                                <h3 style="margin:0 0 15px 0;color:#1a3a52;font-size:16px;font-weight:600;">Achievements & Badges</h3>
                                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;background:#f9f9f9;padding:15px;border-radius:8px;">
                                    ${achievementsHtml}
                                </div>
                            </div>

                            <!-- Close Button -->
                            <button onclick="document.getElementById('user-profile-modal').remove()" style="width:100%;margin-top:20px;padding:12px;background:#e74c3c;color:white;border:none;border-radius:6px;font-weight:600;cursor:pointer;font-size:14px;">Close</button>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);
        })
        .catch(err => {
            alert('Error loading user profile: ' + err.message);
        });
    
    // Refresh visitors list after a short delay
    setTimeout(() => {
        if (typeof loadVisitors === 'function') {
            loadVisitors();
        }
    }, 1000);
};
