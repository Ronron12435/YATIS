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
    
    // Add tile layer and wait for it to load before placing markers
    const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors', maxZoom: 19
    }).addTo(peopleMap);
    
    // Ensure map size is correct after tiles load
    tileLayer.on('load', () => {
        peopleMap.invalidateSize(true);
    });

    // Update user location in background without showing marker
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
            pos => updateMyLocation(pos.coords.latitude, pos.coords.longitude),
            () => {
                const userLat = document.querySelector('meta[name="user-latitude"]')?.content;
                const userLng = document.querySelector('meta[name="user-longitude"]')?.content;
                if (userLat && userLng) {
                    updateMyLocation(parseFloat(userLat), parseFloat(userLng));
                } else {
                    fetch('https://ipapi.co/json/').then(r => r.json()).then(d => { if(d.latitude) updateMyLocation(d.latitude, d.longitude); }).catch(()=>{});
                }
            },
            { enableHighAccuracy: true, timeout: 8000 }
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
    }, 1000);
    
    loadFriendsList();
    loadFriendRequests();
};

function loadPeopleMarkers() {
    if (!peopleMap) {
        return;
    }
    
    peopleMap.invalidateSize(true);
    
    fetch('/api/people-map', { credentials: 'include' })
        .then(r => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        })
        .then(response => {
            const users = response.data || [];
            
            if (users.length === 0) {
                return;
            }
            
            // Clear existing markers (except your own)
            if (window._existingMarkers) {
                window._existingMarkers.forEach(marker => {
                    try {
                        peopleMap.removeLayer(marker);
                    } catch (e) {
                        // Silently ignore errors
                    }
                });
            }
            window._existingMarkers = [];
            
            let bounds = L.latLngBounds();
            
            const placedMarkers = []; // Track placed marker positions
            users.forEach((user, index) => {
                try {
                    let lat = parseFloat(user.latitude);
                    let lng = parseFloat(user.longitude);
                    
                    // If coordinates are invalid, skip
                    if (isNaN(lat) || isNaN(lng)) {
                        return;
                    }
                    
                    // Check for overlapping markers and offset if needed
                    const minDistance = 0.0015; // ~150 meters at equator
                    let offsetLat = lat;
                    let offsetLng = lng;
                    let offsetIndex = 0;
                    
                    for (let placed of placedMarkers) {
                        const distance = Math.sqrt(Math.pow(lat - placed.lat, 2) + Math.pow(lng - placed.lng, 2));
                        if (distance < minDistance) {
                            // Offset in a circular pattern around the original location
                            const angle = (offsetIndex * 45) * (Math.PI / 180); // 45 degree increments
                            const offset = 0.0015; // ~150 meters - increased from 0.0008
                            offsetLat = lat + offset * Math.cos(angle);
                            offsetLng = lng + offset * Math.sin(angle);
                            offsetIndex++;
                        }
                    }
                    
                    placedMarkers.push({ lat: offsetLat, lng: offsetLng, username: user.username });
                    
                    const initials = ((user.first_name||'')[0]||'').toUpperCase() + ((user.last_name||'')[0]||'').toUpperCase();
                    
                    // Create colored circle with initials
                    const colors = ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#e67e22', '#34495e'];
                    const colorIndex = user.id % colors.length;
                    const bgColor = colors[colorIndex];
                    
                    // Create marker HTML as a simple string (more reliable than DOM manipulation)
                    const markerHtml = `<div style="width:50px;height:50px;border-radius:50%;background:${bgColor};display:flex;align-items:center;justify-content:center;color:white;font-size:18px;font-weight:700;border:3px solid white;box-shadow:0 4px 12px rgba(0,0,0,0.4);text-align:center;line-height:1;position:relative;z-index:100;">${initials}</div>`;
                    
                    // Create a custom marker with circle and initials combined
                    const markerIcon = L.divIcon({
                        html: markerHtml,
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
                        <div style="background:white;padding:20px;text-align:center;border-bottom:1px solid #e0e0e0;">
                            <div style="width:70px;height:70px;border-radius:50%;background:${bgColor};margin:0 auto 10px;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;color:white;">${initials}</div>
                            <div style="color:#333;font-weight:700;font-size:16px;">${user.username}</div>
                            <div style="color:#666;font-size:13px;">${user.first_name} ${user.last_name}</div>
                        </div>
                        <div style="background:white;padding:15px;text-align:center;">
                            <div style="color:#555;font-size:13px;margin-bottom:8px;">📍 ${location}</div>
                            <span style="background:#3498db;color:white;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:600;">User</span>
                            <div style="margin-top:12px;">${actionBtn}</div>
                        </div>
                    </div>`;
                    
                    const marker = L.marker([offsetLat, offsetLng], { icon: markerIcon, zIndexOffset: 1000 + index }).addTo(peopleMap);
                    marker.bindPopup(popupHtml, { maxWidth: 260, className: 'people-popup', autoPan: false });
                    window._existingMarkers.push(marker);
                    bounds.extend([offsetLat, offsetLng]);
                } catch (e) {
                    // Silently handle errors
                }
            });
            
            // Display nearby users list
            displayNearbyUsersList(users);
        })
        .catch(err => {
            // Silently handle errors
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
                const avatarHtml = f.profile_picture 
                    ? `<img src="/storage/${f.profile_picture}?t=${Date.now()}" alt="Avatar" style="width:70px;height:70px;border-radius:50%;object-fit:cover;border:3px solid #e0e0e0;flex-shrink:0;">`
                    : `<div style="width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,#3498db,#2980b9);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:24px;flex-shrink:0;">${ini}</div>`;
                
                return `<div style="display:flex;align-items:center;justify-content:space-between;padding:15px 0;border-bottom:1px solid #f0f0f0;gap:12px;flex-wrap:wrap;">
                    <div style="display:flex;align-items:center;gap:12px;flex:1;min-width:0;">
                        ${avatarHtml}
                        <div style="min-width:0;flex:1;">
                            <strong style="display:block;color:#1a3a52;font-size:15px;word-break:break-word;">${f.first_name} ${f.last_name}</strong>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;width:100%;">
                        <button onclick="openFriendChat(${f.id},'${f.first_name} ${f.last_name}','${ini}')" style="padding:8px 12px;background:#3498db;color:white;border:none;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;display:flex;align-items:center;gap:5px;white-space:nowrap;"><i class="fas fa-comment"></i> Message</button>
                        <button onclick="viewFriendProfile(${f.id})" style="padding:8px 12px;background:#7f8c8d;color:white;border:none;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;display:flex;align-items:center;gap:5px;white-space:nowrap;"><i class="fas fa-eye"></i> View</button>
                        <button onclick="unfriend(${f.id})" style="padding:8px 12px;background:#e74c3c;color:white;border:none;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;display:flex;align-items:center;gap:5px;white-space:nowrap;"><i class="fas fa-times"></i> Unfriend</button>
                    </div>
                </div>`;
            }).join('');

        }).catch(err => { 
            document.getElementById('friends-count').textContent = 0; 
        });
}

window.openFriendChat = function(userId, name, initials) {
    fcUserId = userId;
    document.getElementById('fc-name').textContent = name;
    document.getElementById('fc-avatar').textContent = initials;
    document.getElementById('friends-list-view').style.display = 'none';
    document.getElementById('friends-chat-view').style.display = 'block';
    loadFriendMessages();
    clearInterval(fcPollInterval);
    fcPollInterval = setInterval(loadFriendMessages, 3000);
};

window.closeFriendChat = function() {
    clearInterval(fcPollInterval);
    fcUserId = null;
    document.getElementById('friends-chat-view').style.display = 'none';
    document.getElementById('friends-list-view').style.display = 'block';
    if (typeof updateBadges === 'function') updateBadges();
};

function loadFriendMessages() {
    if (!fcUserId) return;
    fetch('/api/messages/' + fcUserId)
        .then(r => r.json())
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
        }).catch(() => {});
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

window.viewFriendProfile = function(userId) {
    fetch(`/api/users/${userId}`, {
        credentials: 'include',
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(response => {
        if (!response.success || !response.data) {
            alert('Error loading profile');
            return;
        }
        
        const user = response.data;
        const ini = ((user.first_name||'')[0]||'').toUpperCase() + ((user.last_name||'')[0]||'').toUpperCase();
        
        // Fetch achievements
        fetch(`/api/profile/${userId}/achievements`, {
            credentials: 'include',
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(achievRes => {
            const achievements = achievRes.data || [];
            
            const achievementsHTML = achievements.length > 0 
                ? achievements.map(a => {
                    return `
                    <div style="padding:15px;border-left:4px solid #f39c12;background:#fafafa;border-radius:6px;margin-bottom:10px;">
                        <div style="display:flex;align-items:flex-start;gap:12px;">
                            <div style="font-size:28px;flex-shrink:0;">🏆</div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-weight:600;color:#333;font-size:14px;margin-bottom:2px;">${a.name || 'Achievement'}</div>
                                <div style="color:#666;font-size:12px;margin-bottom:6px;line-height:1.4;">${a.description || 'Completed task'}</div>
                                <div style="color:#f39c12;font-weight:600;font-size:13px;">+${a.points || 0} points</div>
                            </div>
                        </div>
                    </div>
                `;
                }).join('')
                : '<div style="text-align:center;padding:30px;color:#999;">No achievements yet</div>';
            
            const modal = document.createElement('div');
            modal.id = 'friend-profile-modal';
            modal.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);z-index:10000;display:flex;align-items:center;justify-content:center;padding:20px;';
            
            modal.innerHTML = `
                <div style="background:white;border-radius:12px;max-width:500px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 10px 40px rgba(0,0,0,0.3);">
                    <div style="background:linear-gradient(135deg,#1a3a52,#2c5f8d);padding:30px;text-align:center;color:white;position:sticky;top:0;z-index:10;background-image:url('${user.cover_photo ? '/storage/' + user.cover_photo : ''}');background-size:cover;background-position:center;position:relative;min-height:200px;display:flex;flex-direction:column;justify-content:flex-end;">
                        <div style="position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.4);"></div>
                        <div style="position:relative;z-index:1;display:flex;align-items:flex-end;gap:15px;">
                            <div style="width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:700;border:3px solid white;overflow:hidden;flex-shrink:0;">
                                ${user.profile_picture ? `<img src="/storage/${user.profile_picture}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">` : ini}
                            </div>
                            <div style="text-align:left;margin-bottom:5px;">
                                <h2 style="margin:0 0 5px 0;font-size:22px;">${user.first_name} ${user.last_name}</h2>
                                <p style="margin:0;opacity:0.9;font-size:14px;">@${user.username}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div style="padding:20px;border-bottom:1px solid #eee;">
                        <p style="margin:0;color:#666;font-size:14px;line-height:1.6;">${user.bio || 'No bio added'}</p>
                    </div>
                    
                    <div style="padding:20px;">
                        <h3 style="margin:0 0 15px 0;color:#333;font-size:16px;font-weight:600;">🏆 Achievements & Badges</h3>
                        <div style="display:flex;flex-direction:column;gap:8px;">
                            ${achievementsHTML}
                        </div>
                    </div>
                    
                    <div style="padding:15px;border-top:1px solid #eee;text-align:center;position:sticky;bottom:0;background:white;z-index:10;">
                        <button onclick="document.getElementById('friend-profile-modal').remove();" style="padding:10px 30px;background:#e74c3c;color:white;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:14px;">Close</button>
                    </div>
                </div>
            `;
            
            modal.onclick = (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            };
            
            document.body.appendChild(modal);
        })
        .catch(err => {
            alert('Error loading achievements');
        });
    })
    .catch(err => {
        alert('Error loading profile');
    });
};

window.unfriend = function(userId) {
    const modal = document.createElement('div');
    modal.id = 'unfriend-modal';
    modal.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:10001; padding:30px; max-width:400px; text-align:center;';
    
    modal.innerHTML = `
        <h2 style="margin:0 0 15px 0; color:#333; font-size:18px;">Remove Friend</h2>
        <p style="margin:0; color:#666; font-size:14px; line-height:1.6;">Are you sure you want to remove this friend?</p>
        <div style="display:flex; gap:10px; margin-top:20px; justify-content:center;">
            <button id="unfriend-remove-btn" style="padding:10px 20px; background:#e74c3c; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Remove</button>
            <button id="unfriend-cancel-btn" style="padding:10px 20px; background:#95a5a6; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Cancel</button>
        </div>
    `;

    const overlay = document.createElement('div');
    overlay.id = 'unfriend-modal-overlay';
    overlay.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10000;';
    
    const closeUnfriendModal = () => {
        modal.remove();
        overlay.remove();
    };
    
    overlay.onclick = closeUnfriendModal;
    
    document.body.appendChild(overlay);
    document.body.appendChild(modal);
    
    document.getElementById('unfriend-remove-btn').onclick = () => {
        closeUnfriendModal();
        fetch(`/api/friends/${userId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            credentials: 'include'
        })
        .then(r => r.json())
        .then(data => {
            location.reload();
        })
        .catch(err => {
            console.error('Delete error:', err);
        });
    };
    
    document.getElementById('unfriend-cancel-btn').onclick = closeUnfriendModal;
};

window.addFriend = function(userId, userName) {
    if (!confirm(`Send friend request to ${userName}?`)) return;
    
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
            alert(`✅ Friend request sent to ${userName}!`);
            location.reload();
        } else {
            alert('❌ Error: ' + (response.message || 'Failed to send friend request'));
        }
    }).catch(err => {
        alert('❌ Error sending friend request: ' + err.message);
    });
};

window.acceptFriend = function(userId) {
    fetch(`/api/friends/${userId}/accept`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        credentials: 'include'
    }).then(r => r.json()).then(response => {
        if (response.success) {
            alert('Friend request accepted!');
            loadFriendsList();
            setTimeout(() => location.reload(), 500);
        } else {
            alert('Error: ' + (response.message || 'Failed to accept request'));
        }
    }).catch(err => {
        alert('Error accepting friend request');
    });
};

window.rejectFriend = function(userId) {
    if (!confirm('Reject this friend request?')) return;
    fetch(`/api/friends/${userId}/reject`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        credentials: 'include'
    }).then(r => r.json()).then(response => {
        if (response.success) {
            alert('Friend request rejected');
            setTimeout(() => location.reload(), 500);
        } else {
            alert('Error: ' + (response.message || 'Failed to reject request'));
        }
    }).catch(err => {
        alert('Error rejecting friend request');
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
