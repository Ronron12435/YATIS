<div id="groups" class="content-section">
    <style>
        #groups { background: #f5f5f5; padding: 20px; }
        .groups-wrapper { max-width: 1200px; margin: 0 auto; }
        .groups-header { display: flex; align-items: center; gap: 12px; margin-bottom: 30px; }
        .groups-header h1 { font-size: 32px; color: #1a3a52; margin: 0; font-weight: 700; }
        .groups-header-line { flex: 1; height: 3px; background: linear-gradient(90deg, #00bcd4 0%, transparent 100%); }
        .groups-container { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .groups-left { display: flex; flex-direction: column; gap: 20px; }
        .groups-right { display: flex; flex-direction: column; gap: 20px; }
        .modern-card { background: white; border-radius: 12px; padding: 0; box-shadow: 0 2px 12px rgba(0,0,0,.08); overflow: hidden; }
        .modern-card-header { padding: 20px; border-bottom: 1px solid #f0f0f0; background: #fafafa; }
        .card-title-group { display: flex; align-items: center; gap: 12px; }
        .card-icon-modern { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: #e3f2fd; border-radius: 8px; color: #1976d2; }
        .modern-card-header h3 { margin: 0; font-size: 16px; font-weight: 700; color: #1a3a52; }
        .modern-card-body { padding: 20px; }
        .modern-form { display: flex; flex-direction: column; gap: 0; }
        .modern-form-group { margin-bottom: 20px; }
        .modern-form-group:last-child { margin-bottom: 0; }
        .modern-label { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #1a3a52; margin-bottom: 8px; }
        .modern-label svg { width: 16px; height: 16px; color: #00bcd4; }
        .modern-input, .modern-textarea, .modern-select { width: 100%; padding: 12px 14px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 13px; font-family: inherit; background: white; transition: all .2s; }
        .modern-input:focus, .modern-textarea:focus, .modern-select:focus { outline: none; border-color: #00bcd4; box-shadow: 0 0 0 3px rgba(0, 188, 212, .1); }
        .modern-textarea { resize: vertical; min-height: 100px; }
        .input-hint { font-size: 11px; color: #999; margin-top: 6px; display: block; }
        .modern-btn { padding: 12px 24px; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all .2s; display: inline-flex; align-items: center; gap: 8px; }
        .modern-btn-primary { background: linear-gradient(135deg, #1a3a52 0%, #2c5f8d 100%); color: white; box-shadow: 0 4px 12px rgba(26, 58, 82, 0.2); }
        .modern-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(26, 58, 82, 0.3); }
        .message { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        .message.success { background: #c8e6c9; color: #2e7d32; }
        .message.error { background: #ffcdd2; color: #c62828; }
        .member-limit-info { font-size: 12px; color: #666; margin-top: 4px; }
        .member-limit-info .premium-link { color: #00bcd4; text-decoration: none; font-weight: 600; cursor: pointer; }
        .groups-list { display: flex; flex-direction: column; gap: 15px; }
        .group-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,.08); border-left: 4px solid #00bcd4; transition: all .2s; }
        .group-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.12); transform: translateY(-2px); }
        .group-card-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px; }
        .group-name { font-size: 16px; font-weight: 700; color: #1a3a52; margin: 0; }
        .group-badges { display: flex; gap: 8px; flex-wrap: wrap; }
        .group-badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-privacy-public { background: #e3f2fd; color: #1976d2; }
        .badge-privacy-private { background: #f3e5f5; color: #7b1fa2; }
        .badge-joined { background: #c8e6c9; color: #2e7d32; }
        .group-description { font-size: 13px; color: #666; margin: 10px 0; line-height: 1.5; }
        .group-meta { display: flex; gap: 20px; font-size: 12px; color: #999; margin-top: 12px; }
        .group-meta-item { display: flex; align-items: center; gap: 6px; }
        .group-meta-item svg { width: 14px; height: 14px; }
        .group-actions { display: flex; gap: 10px; margin-top: 12px; }
        .group-btn { padding: 8px 16px; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all .2s; }
        .group-btn-view { background: #e3f2fd; color: #1976d2; }
        .group-btn-view:hover { background: #1976d2; color: white; }
        .group-btn-leave { background: #ffebee; color: #c62828; }
        .group-btn-leave:hover { background: #c62828; color: white; }
        .empty-state { text-align: center; padding: 40px 20px; }
        .empty-state-icon { font-size: 48px; margin-bottom: 16px; opacity: 0.3; }
        .empty-state-text { font-size: 14px; color: #999; }
        @media (max-width: 768px) {
            .groups-container { grid-template-columns: 1fr; }
        }
    </style>

    <div class="groups-wrapper">
        {{-- Header --}}
        <div class="groups-header">
            <h1><i class="fas fa-users"></i> Groups</h1>
            <div class="groups-header-line"></div>
        </div>

        <div class="groups-container">
            {{-- Left Column: Create Group --}}
            <div class="groups-left">
                {{-- Create New Group Card --}}
                <div class="modern-card">
                    <div class="modern-card-header">
                        <div class="card-title-group">
                            <div class="card-icon-modern">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                            </div>
                            <h3>Create New Group/Clan/Guild</h3>
                        </div>
                    </div>
                    <div class="modern-card-body">
                        <div id="createGroupMessage"></div>
                        <form id="createGroupForm" class="modern-form" onsubmit="createGroup(event)">
                            <div class="modern-form-group">
                                <label class="modern-label">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
                                    Group Name
                                </label>
                                <input type="text" class="modern-input" id="groupName" placeholder="Enter group name" maxlength="255" required>
                            </div>
                            <div class="modern-form-group">
                                <label class="modern-label">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>
                                    Description
                                </label>
                                <textarea class="modern-textarea" id="groupDescription" placeholder="Describe your group" maxlength="1000"></textarea>
                                <span class="input-hint">Optional - Help people understand what your group is about</span>
                            </div>
                            <div class="modern-form-group">
                                <label class="modern-label">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                                    Privacy
                                </label>
                                <select class="modern-select" id="groupPrivacy">
                                    <option value="public">🌍 Public - Anyone can join</option>
                                    <option value="private">🔒 Private - Invite only</option>
                                </select>
                            </div>
                            <div class="modern-form-group">
                                <label class="modern-label">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
                                    Member Limit
                                </label>
                                <input type="number" class="modern-input" id="memberLimit" value="50" min="10" max="500" required>
                                <span class="member-limit-info">Free: up to 50 members. <span class="premium-link" onclick="alert('Upgrade to Premium for up to 500 members')">Upgrade to Premium</span> for up to 500 members</span>
                            </div>
                            <button type="submit" class="modern-btn modern-btn-primary">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                                Create Group
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Right Column: Your Groups --}}
            <div class="groups-right">
                {{-- Your Groups & Communities Card --}}
                <div class="modern-card">
                    <div class="modern-card-header">
                        <div class="card-title-group">
                            <div class="card-icon-modern">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                            </div>
                            <h3>Your Groups & Communities</h3>
                        </div>
                    </div>
                    <div class="modern-card-body">
                        <p style="font-size: 12px; color: #999; margin-bottom: 20px;">Free accounts limited to 50 members per group.</p>
                        <div id="groupsList" class="groups-list">
                            <div class="empty-state">
                                <div class="empty-state-icon">👥</div>
                                <p class="empty-state-text">No groups yet. Create one to get started!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Group Detail Modal --}}
<div id="groupDetailModal" class="group-detail-modal">
    <div class="group-detail-container">
        <div id="groupDetailHeader" class="group-detail-header">
            <button class="group-detail-back-btn" onclick="closeGroupDetail()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
                Back to Groups
            </button>
            <div class="group-detail-header-content">
                <h2 class="group-detail-name">Loading...</h2>
                <p class="group-detail-desc"></p>
            </div>
        </div>

        <div id="groupMessages" class="group-messages">
            <div style="text-align: center; padding: 20px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="animation: spin 1s linear infinite;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/></svg>
            </div>
        </div>

        <form class="group-message-form" onsubmit="sendGroupMessage(event)">
            <input type="hidden" id="groupDetailGroupId" value="">
            <div class="group-message-input-wrapper">
                <input 
                    type="text" 
                    id="groupMessageInput" 
                    class="group-message-input" 
                    placeholder="Type a message..." 
                    required
                >
                <button type="submit" class="group-message-send-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M16.6915026,12.4744748 L3.50612381,13.2599618 C3.19218622,13.2599618 3.03521743,13.4170592 3.03521743,13.5741566 L1.15159189,20.0151496 C0.8376543,20.8006365 0.99,21.89 1.77946707,22.52 C2.41,22.99 3.50612381,23.1 4.13399899,22.8429026 L21.714504,14.0454487 C22.6563168,13.5741566 23.1272231,12.6315722 22.9702544,11.6889879 L4.13399899,1.16346272 C3.34915502,0.9 2.40734225,1.00636533 1.77946707,1.4776575 C0.994623095,2.10604706 0.837654326,3.0486314 1.15159189,3.99021575 L3.03521743,10.4312088 C3.03521743,10.5883061 3.34915502,10.7454035 3.50612381,10.7454035 L16.6915026,11.5308905 C16.6915026,11.5308905 17.1624089,11.5308905 17.1624089,12.0021827 C17.1624089,12.4744748 16.6915026,12.4744748 16.6915026,12.4744748 Z"/></svg>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .group-detail-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: white;
        z-index: 9999;
        align-items: stretch;
        justify-content: stretch;
        padding: 0;
        flex-direction: column;
    }

    .group-detail-container {
        background: white;
        border-radius: 0;
        width: 100%;
        height: 100vh;
        display: flex;
        flex-direction: column;
        box-shadow: none;
        overflow: hidden;
    }

    .group-detail-header {
        background: linear-gradient(135deg, #1a3a52 0%, #2c5f8d 100%);
        color: white;
        padding: 0;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        flex-direction: column;
    }

    .group-detail-back-btn {
        background: transparent;
        border: none;
        color: white;
        cursor: pointer;
        padding: 12px 20px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.2s;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .group-detail-back-btn:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .group-detail-back-btn svg {
        width: 20px;
        height: 20px;
    }

    .group-detail-header-content {
        padding: 16px 20px;
    }

    .group-detail-name {
        font-size: 24px;
        font-weight: 700;
        margin: 0 0 8px 0;
        color: white;
    }

    .group-detail-desc {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.8);
        margin: 0;
    }

    .group-detail-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        border-radius: 6px;
        color: white;
        cursor: pointer;
        padding: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .group-detail-close:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    .group-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        background: #f9f9f9;
    }

    .group-messages-empty {
        text-align: center;
        color: #999;
        padding: 40px 20px;
        font-size: 14px;
    }

    .group-message {
        display: flex;
        margin-bottom: 8px;
    }

    .group-message-other {
        justify-content: flex-start;
    }

    .group-message-own {
        justify-content: flex-end;
    }

    .group-message-content {
        max-width: 70%;
        padding: 12px 16px;
        border-radius: 12px;
        word-wrap: break-word;
    }

    .group-message-other .group-message-content {
        background: white;
        border: 1px solid #e0e0e0;
        color: #333;
    }

    .group-message-own .group-message-content {
        background: #00bcd4;
        color: white;
    }

    .group-message-text {
        margin: 0;
        font-size: 13px;
        line-height: 1.4;
    }

    .group-message-time {
        font-size: 11px;
        opacity: 0.7;
        margin-top: 4px;
        display: block;
    }

    .group-message-form {
        padding: 16px;
        background: white;
        border-top: 1px solid #e0e0e0;
    }

    .group-message-input-wrapper {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .group-message-input {
        flex: 1;
        padding: 12px 14px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 13px;
        font-family: inherit;
        transition: all 0.2s;
    }

    .group-message-input:focus {
        outline: none;
        border-color: #00bcd4;
        box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.1);
    }

    .group-message-send-btn {
        background: #00bcd4;
        border: none;
        border-radius: 8px;
        color: white;
        cursor: pointer;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .group-message-send-btn:hover {
        background: #0097a7;
        transform: translateY(-2px);
    }

    .group-message-send-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    @media (max-width: 768px) {
        .group-detail-container {
            max-width: 100%;
            height: 100vh;
            border-radius: 0;
        }

        .group-message-content {
            max-width: 85%;
        }
    }
</style>
