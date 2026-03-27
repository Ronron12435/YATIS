<div id="people" class="content-section">
<style>
.people-popup .leaflet-popup-content-wrapper { 
    padding: 0; 
    border-radius: 12px; 
    overflow: hidden;
    background: white !important;
    border: 3px solid #f1c40f !important;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15) !important;
}
.people-popup .leaflet-popup-content { 
    margin: 0;
    padding: 0;
}
.people-popup .leaflet-popup-tip-container { 
    display: none;
}
.people-popup .leaflet-popup-tip { 
    display: none;
}

/* Fixed size markers that don't scale with zoom */
.leaflet-marker-icon.user-marker-fixed {
    background: transparent !important;
    border: none !important;
    padding: 0 !important;
    margin: 0 !important;
    width: 50px !important;
    height: 50px !important;
    transform: scale(1) !important;
}

.leaflet-marker-icon.user-marker-fixed > div {
    transform: scale(1) !important;
}
</style>
    <h1 class="page-title"><i class="fas fa-users"></i> People</h1>
    <div class="card">
        <h3>🗺️ People Near You</h3>
        <p style="color: #666; margin-bottom: 10px;">Discover people in Sagay City. Click on pins to view profiles and add friends!</p>
        <div id="people-map-container" style="width: 100%; height: 500px; border-radius: 8px; overflow: hidden; border: 2px solid #00bcd4; margin: 15px 0; position: relative;"></div>
    </div>
    <div class="stats">
        <div class="stat-card"><h3 id="friends-count">0</h3><p>Friends</p></div>
        <div class="stat-card"><h3 id="pending-count">0</h3><p>Pending Requests</p></div>
        <div class="stat-card"><h3 id="sent-count">0</h3><p>Sent Requests</p></div>
    </div>
</div>

<div id="my-friends" class="content-section">
    <h1 class="page-title"><i class="fas fa-user-friends"></i> My Friends</h1>

    {{-- Friends list view --}}
    <div id="friends-list-view">
        <div class="card">
            <h3><i class="fas fa-users"></i> Your Friends List</h3>
            <div id="friends-list"><p style="color:#999;">Loading friends...</p></div>
        </div>
    </div>

    {{-- Chat window (hidden until Message button clicked) --}}
    <div id="friends-chat-view" style="display:none;">
        <div style="background:linear-gradient(135deg,#1a3a52,#2c5f8d);padding:14px 20px;border-radius:10px;display:flex;align-items:center;gap:14px;margin-bottom:16px;">
            <button onclick="closeFriendChat()" style="background:rgba(255,255,255,0.15);color:white;border:none;padding:6px 14px;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;">&larr; Back</button>
            <div id="fc-avatar" style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#3498db,#2980b9);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;"></div>
            <span id="fc-name" style="color:white;font-weight:600;font-size:16px;"></span>
        </div>
        <div class="card" style="padding:0;overflow:hidden;">
            <div id="fc-messages" style="height:420px;overflow-y:auto;padding:20px;background:#f5f7fa;display:flex;flex-direction:column;gap:10px;">
                <p style="color:#999;text-align:center;">Loading messages...</p>
            </div>
            <div style="display:flex;gap:10px;padding:14px;border-top:1px solid #eee;background:white;">
                <input id="fc-input" type="text" placeholder="Type a message..."
                    onkeydown="if(event.key==='Enter') sendFriendMessage()"
                    style="flex:1;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px;outline:none;">
                <button onclick="sendFriendMessage()" style="padding:10px 22px;background:#3498db;color:white;border:none;border-radius:8px;cursor:pointer;font-weight:700;font-size:14px;">Send</button>
            </div>
        </div>
    </div>
</div>

<div id="friend-requests" class="content-section">
    <h1 class="page-title"><i class="fas fa-user-plus"></i> Friend Requests</h1>
    <div class="card">
        <h3>📬 Pending Friend Requests</h3>
        <div id="friend-requests-list"><p style="color:#999;">Loading requests...</p></div>
    </div>
</div>
