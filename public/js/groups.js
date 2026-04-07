/**
 * My Groups - Group management, creation, and messaging
 */

window.initGroupsSection = function () {
    loadUserGroups();
    
    // Restore open group if one was saved
    const savedGroupId = localStorage.getItem('openGroupId');
    if (savedGroupId) {
        setTimeout(() => openGroupDetail(parseInt(savedGroupId)), 500);
    }
};

// ── Create Group ──────────────────────────────────────────────────────────────

window.createGroup = function (e) {
    e.preventDefault();
    const msgEl = document.getElementById('createGroupMessage');
    msgEl.innerHTML = '';

    const name = document.getElementById('groupName').value.trim();
    const description = document.getElementById('groupDescription').value.trim();
    const privacy = document.getElementById('groupPrivacy').value;
    const memberLimit = parseInt(document.getElementById('memberLimit').value);

    if (!name) {
        msgEl.innerHTML = '<div class="message error">Group name is required.</div>';
        return;
    }

    if (memberLimit < 10 || memberLimit > 500) {
        msgEl.innerHTML = '<div class="message error">Member limit must be between 10 and 500.</div>';
        return;
    }

    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg> Creating...';

    fetch('/api/groups', {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            name: name,
            description: description || null,
            privacy: privacy,
            member_limit: memberLimit,
        }),
    })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            if (res.success) {
                msgEl.innerHTML = '<div class="message success">Group created successfully!</div>';
                document.getElementById('createGroupForm').reset();
                document.getElementById('memberLimit').value = '50';
                loadUserGroups();
                setTimeout(() => msgEl.innerHTML = '', 3000);
            } else {
                msgEl.innerHTML = `<div class="message error">${res.message || 'Failed to create group'}</div>`;
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            msgEl.innerHTML = '<div class="message error">Network error. Please try again.</div>';
        });
};

// ── Load User Groups ──────────────────────────────────────────────────────────

function loadUserGroups() {
    const container = document.getElementById('groupsList');
    if (!container) {
        return;
    }

    container.innerHTML = '<div style="text-align: center; padding: 20px;"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="animation: spin 1s linear infinite;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/></svg></div>';

    Promise.all([
        fetch('/api/groups/user/my-groups', {
            credentials: 'include',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        }).then(r => r.json()),
        fetch('/api/groups/public', {
            credentials: 'include',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        }).then(r => r.json())
    ])
        .then(([userRes, publicRes]) => {
            const userGroups = Array.isArray(userRes.data) ? userRes.data : [];
            const publicGroups = Array.isArray(publicRes.data) ? publicRes.data : [];
            
            const userGroupIds = new Set(userGroups.map(g => g.id));
            const allGroups = [
                ...userGroups.map(g => ({ ...g, isOwned: g.is_creator, isJoined: true })),
                ...publicGroups.filter(pg => !userGroupIds.has(pg.id)).map(g => ({ ...g, isOwned: false, isJoined: false }))
            ];
            
            if (!allGroups.length) {
                container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">👥</div><p class="empty-state-text">No groups available yet. Create one or join a public group!</p></div>';
                return;
            }

            container.innerHTML = allGroups.map(group => `
                <div class="group-card">
                    <div class="group-card-header">
                        <h3 class="group-name">${escapeHtml(group.name)}</h3>
                        <div class="group-badges">
                            <span class="group-badge" style="background: ${group.is_private ? '#f3e5f5' : '#e3f2fd'}; color: ${group.is_private ? '#7b1fa2' : '#1976d2'};">
                                ${group.is_private ? '🔒 Private' : '🌍 Public'}
                            </span>
                            ${group.isOwned ? '<span class="group-badge" style="background: #fff3e0; color: #e65100;">👑 Creator</span>' : ''}
                            ${group.isJoined ? '<span class="group-badge" style="background: #c8e6c9; color: #2e7d32;">✓ Joined</span>' : ''}
                            <span class="group-badge" style="background: #e8f5e9; color: #2e7d32;">👥 ${group.member_count || 0} members</span>
                        </div>
                    </div>
                    ${group.description ? `<p class="group-description">${escapeHtml(group.description)}</p>` : ''}
                    <div class="group-meta">
                        <div class="group-meta-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                            Created ${formatDate(group.created_at)}
                        </div>
                    </div>
                    <div class="group-actions">
                        ${group.isJoined ? `
                            <button class="group-btn group-btn-view" onclick="openGroupDetail(${group.id})">
                                <i class="fas fa-comments"></i> Open Chat
                            </button>
                            ${group.isOwned ? `<button class="group-btn group-btn-delete" onclick="deleteGroup(${group.id})"><i class="fas fa-trash"></i> Delete</button>` : `<button class="group-btn group-btn-leave" onclick="leaveGroup(${group.id})"><i class="fas fa-sign-out-alt"></i> Leave</button>`}
                        ` : `
                            <button class="group-btn group-btn-join" onclick="joinGroup(${group.id}, '${escapeHtml(group.name)}')">
                                <i class="fas fa-plus"></i> Join Group
                            </button>
                        `}
                    </div>
                </div>
            `).join('');
        })
        .catch(err => {
            container.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">Error loading groups</p>';
        });
}

// ── Leave Group ───────────────────────────────────────────────────────────────

window.leaveGroup = function (groupId) {
    // Store groupId for use in confirmLeaveGroup
    window.pendingLeaveGroupId = groupId;
    showLeaveGroupConfirmModal();
};

window.showLeaveGroupConfirmModal = function () {
    const modal = document.getElementById('leaveGroupConfirmModal');
    if (modal) {
        modal.style.display = 'flex';
    }
};

window.closeLeaveGroupConfirmModal = function () {
    const modal = document.getElementById('leaveGroupConfirmModal');
    if (modal) {
        modal.style.display = 'none';
    }
    window.pendingLeaveGroupId = null;
};

window.confirmLeaveGroup = function () {
    const groupId = window.pendingLeaveGroupId;
    if (!groupId) return;

    const btn = document.getElementById('confirmLeaveBtn');
    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="animation: spin 1s linear infinite;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/></svg> Leaving...';

    fetch(`/api/groups/${groupId}/members/${document.querySelector('meta[name="user-id"]').content}`, {
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
            btn.disabled = false;
            btn.innerHTML = originalText;
            if (res.success) {
                closeLeaveGroupConfirmModal();
                loadUserGroups();
            } else {
                alert(res.message || 'Failed to leave group');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            alert('Error leaving group');
        });
};

// ── Delete Group ──────────────────────────────────────────────────────────────

window.deleteGroup = function (groupId, groupName) {
    if (!confirm(`Are you sure you want to delete "${groupName}"? This action cannot be undone.`)) return;

    fetch(`/api/groups/${groupId}`, {
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
                loadUserGroups();
            } else {
                alert(res.message || 'Failed to delete group');
            }
        })
        .catch(err => {
            alert('Error deleting group');
        });
};

// ── Browse Public Groups ──────────────────────────────────────────────────────

window.loadPublicGroups = function () {
    const container = document.getElementById('publicGroupsList');
    if (!container) {
        return;
    }

    container.innerHTML = '<div style="text-align: center; padding: 20px;"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="animation: spin 1s linear infinite;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/></svg></div>';

    fetch('/api/groups/public', {
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        })
        .then(res => {
            const groups = res.data?.data || res.data || [];
            if (!groups.length) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">🔍</div>
                        <p class="empty-state-text">No public groups available yet.</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = groups.map(group => `
                <div class="group-card">
                    <div class="group-card-header">
                        <h4 class="group-name">${escapeHtml(group.name)}</h4>
                        <div class="group-badges">
                            <span class="group-badge ${group.is_private ? 'badge-privacy-private' : 'badge-privacy-public'}">
                                ${group.is_private ? '🔒 Private' : '🌍 Public'}
                            </span>
                        </div>
                    </div>
                    ${group.description ? `<p class="group-description">${escapeHtml(group.description)}</p>` : ''}
                    <div class="group-meta">
                        <div class="group-meta-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                            <span>${group.member_count || 0} members</span>
                        </div>
                        <div class="group-meta-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                            <span>${formatDate(group.created_at)}</span>
                        </div>
                    </div>
                    <div class="group-actions">
                        <button class="group-btn group-btn-join" onclick="joinGroup(${group.id}, '${escapeHtml(group.name)}')">Join Group</button>
                    </div>
                </div>
            `).join('');
            })
        .catch(err => {
            container.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">Error loading public groups</p>';
        });
};

// ── Join Group ────────────────────────────────────────────────────────────────

window.joinGroup = function (groupId, groupName) {
    fetch(`/api/groups/${groupId}/members`, {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            user_id: document.querySelector('meta[name="user-id"]').content,
        }),
    })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showJoinGroupModal(`✅ You've joined "${groupName}"!`, true);
                loadPublicGroups();
                loadUserGroups();
            } else {
                showJoinGroupModal(res.message || 'Failed to join group', false);
            }
        })
        .catch(err => {
            showJoinGroupModal('Error joining group', false);
        });
};

// ── Join Group Modal ──────────────────────────────────────────────────────────

window.showJoinGroupModal = function (message, isSuccess) {
    const modal = document.getElementById('joinGroupModal');
    const icon = document.getElementById('joinGroupIcon');
    const title = document.getElementById('joinGroupTitle');
    const msgEl = document.getElementById('joinGroupMessage');
    
    icon.textContent = isSuccess ? '✓' : '✕';
    icon.style.color = isSuccess ? '#4caf50' : '#f44336';
    title.textContent = isSuccess ? 'Success!' : 'Error';
    title.style.color = isSuccess ? '#4caf50' : '#f44336';
    msgEl.textContent = message;
    
    modal.style.display = 'flex';
    
    if (isSuccess) {
        setTimeout(() => closeJoinGroupModal(), 3000);
    }
};

window.closeJoinGroupModal = function () {
    const modal = document.getElementById('joinGroupModal');
    modal.style.display = 'none';
};

// ── Helpers ───────────────────────────────────────────────────────────────────

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

// ── Boot ──────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    const section = document.getElementById('groups');
    if (!section) return;

    // Initialize modal close handler
    const modal = document.getElementById('groupDetailModal');
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeGroupDetail();
            }
        });
    }

    const observer = new MutationObserver(() => {
        if (section.classList.contains('active')) {
            initGroupsSection();
        }
    });
    observer.observe(section, { attributes: true, attributeFilter: ['class'] });
});

// ── Group Detail Modal ────────────────────────────────────────────────────────

window.openGroupDetail = function (groupId) {
    const modal = document.getElementById('groupDetailModal');
    if (!modal) {
        return;
    }

    // Save the open group to localStorage
    localStorage.setItem('openGroupId', groupId);
    
    modal.style.display = 'flex';
    setTimeout(() => loadGroupDetail(groupId), 100);
};

window.closeGroupDetail = function () {
    const modal = document.getElementById('groupDetailModal');
    if (modal) {
        modal.style.display = 'none';
        // Clear the saved group when closing
        localStorage.removeItem('openGroupId');
    }
};

function loadGroupDetail(groupId) {
    const header = document.getElementById('groupDetailHeader');
    const messagesContainer = document.getElementById('groupMessages');

    if (!header || !messagesContainer) {
        return;
    }

    // Load group info
    fetch(`/api/groups/${groupId}`, {
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        })
        .then(res => {
            const group = res.data;
            const headerContent = header.querySelector('.group-detail-header-content');
            if (headerContent) {
                headerContent.innerHTML = `
                    <h2 class="group-detail-name">${escapeHtml(group.name)}</h2>
                `;
            }
            document.getElementById('groupDetailGroupId').value = groupId;
        })
        .catch(err => {
            const headerContent = header.querySelector('.group-detail-header-content');
            if (headerContent) {
                headerContent.innerHTML = '<p style="color: #ffcccc;">Error loading group details</p>';
            }
        });

    // Load messages
    fetch(`/api/groups/${groupId}/messages`, {
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        })
        .then(res => {
            const messages = res.data || [];
            if (!messages.length) {
                messagesContainer.innerHTML = '<div class="group-messages-empty">No messages yet. Start the conversation!</div>';
                return;
            }
            const currentUserId = parseInt(document.querySelector('meta[name="user-id"]').content);
            messagesContainer.innerHTML = messages.map((msg, idx) => {
                // Get sender information - handle both cases where sender is loaded or not
                const sender = msg.sender || {};
                const senderId = sender.id || msg.user_id;
                const isOwnMessage = senderId === currentUserId;
                
                // Build sender name from available fields
                const senderFirstName = sender.first_name || '';
                const senderLastName = sender.last_name || '';
                const senderName = (senderFirstName + ' ' + senderLastName).trim() || sender.name || 'Unknown User';
                const initials = (senderFirstName.charAt(0) + senderLastName.charAt(0)).toUpperCase() || '?';
                
                // Generate avatar - use profile picture if available, otherwise generate initials avatar
                let senderAvatar;
                if (sender.profile_picture) {
                    senderAvatar = `/storage/${sender.profile_picture}`;
                } else {
                    // Generate a color based on user ID for consistency
                    const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E2'];
                    const colorIndex = (senderId % colors.length);
                    const bgColor = colors[colorIndex];
                    senderAvatar = `data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ccircle cx='50' cy='50' r='50' fill='${encodeURIComponent(bgColor)}'/%3E%3Ctext x='50' y='50' text-anchor='middle' dy='.3em' fill='white' font-size='40' font-weight='bold' font-family='Arial'%3E${initials}%3C/text%3E%3C/svg%3E`;
                }
                
                return `
                    <div class="group-message ${isOwnMessage ? 'group-message-own' : 'group-message-other'}">
                        ${!isOwnMessage ? `
                            <div class="group-message-sender-info">
                                <img src="${senderAvatar}" alt="${escapeHtml(senderName)}" class="group-message-avatar">
                                <span class="group-message-sender-name">${escapeHtml(senderName)}</span>
                            </div>
                        ` : ''}
                        <div class="group-message-content">
                            <p class="group-message-text">${escapeHtml(msg.message)}</p>
                            <span class="group-message-time">${formatTime(msg.created_at)}</span>
                        </div>
                    </div>
                `;
            }).join('');
            // Scroll to bottom
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        })
        .catch(err => {
            messagesContainer.innerHTML = '<p style="color: #c62828; text-align: center;">Error loading messages</p>';
        });
}

window.sendGroupMessage = function (e) {
    e.preventDefault();
    const groupId = document.getElementById('groupDetailGroupId').value;
    const input = document.getElementById('groupMessageInput');
    const message = input.value.trim();

    if (!message) return;

    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;

    fetch(`/api/groups/${groupId}/messages`, {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ content: message }),
    })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            if (res.success) {
                input.value = '';
                loadGroupDetail(groupId);
            } else {
                alert(res.message || 'Failed to send message');
            }
        })
        .catch(err => {
            btn.disabled = false;
            alert('Error sending message');
        });
};

function formatTime(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}


