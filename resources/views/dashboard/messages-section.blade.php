<div id="messages" class="content-section">
    <h1 class="page-title"><i class="fas fa-envelope"></i> Messages</h1>

    <!-- Conversation list view -->
    <div id="chat-list-view">
        <div class="card">
            <h3><i class="fas fa-comments"></i> Your Conversations</h3>
            <div id="conversations-list">
                <p style="color:#999; text-align:center; padding:20px;">Loading conversations...</p>
            </div>
        </div>
    </div>

    <!-- Chat window view -->
    <div id="chat-window-view" style="display:none;">
        <div style="background:#f8f9fa; padding:14px; border-bottom:1px solid #ddd; display:flex; align-items:center; gap:12px;">
            <button id="back-to-list-btn" style="background:rgba(52,152,219,0.1); color:#3498db; border:none; padding:6px 14px; border-radius:6px; cursor:pointer; font-size:14px;">
                <i class="fas fa-arrow-left"></i> Back
            </button>
            <div id="chat-friend-avatar" style="width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,#3498db,#2980b9); color:white; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:bold;"></div>
            <span id="chat-friend-name" style="color:#333; font-weight:600; font-size:16px;"></span>
        </div>
        <div id="chat-messages" style="flex:1; overflow-y:auto; padding:14px; background:white; min-height:300px; max-height:400px;">
            <p style="color:#999; text-align:center;">Loading messages...</p>
        </div>
        <div style="display:flex; gap:10px; padding:14px; border-top:1px solid #eee; background:white;">
            <input id="chat-input" type="text" placeholder="Type a message..." style="flex:1; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
            <button id="send-message-btn" style="padding:10px 22px; background:#3498db; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">
                <i class="fas fa-paper-plane"></i> Send
            </button>
        </div>
    </div>
</div>
