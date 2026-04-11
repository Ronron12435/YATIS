<div id="people-sidebar" class="people-sidebar">
    <style>
        .people-sidebar {
            position: fixed;
            right: 0;
            top: 0;
            width: 320px;
            height: 100vh;
            background: white;
            box-shadow: -4px 0 20px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            display: none;
            flex-direction: column;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .people-sidebar.hidden {
            transform: translateX(100%);
        }

        .people-sidebar-header {
            padding: 20px;
            background: rgba(0, 0, 0, 0.1);
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .people-sidebar-header h3 {
            color: white;
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }

        .people-sidebar-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .people-sidebar-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .people-sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }

        .people-sidebar-content::-webkit-scrollbar {
            width: 6px;
        }

        .people-sidebar-content::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        .people-sidebar-content::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }

        .people-sidebar-content::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        .nearby-user-item {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .nearby-user-item:hover {
            transform: translateX(-4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .nearby-user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
            position: relative;
            overflow: hidden;
        }

        .nearby-user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .nearby-user-status {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 12px;
            height: 12px;
            background: #4ade80;
            border: 2px solid white;
            border-radius: 50%;
        }

        .nearby-user-info {
            flex: 1;
            min-width: 0;
        }

        .nearby-user-name {
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nearby-user-status-text {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }

        .nearby-user-actions {
            display: flex;
            gap: 6px;
        }

        .nearby-user-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .nearby-user-btn.message {
            background: #3b82f6;
            color: white;
        }

        .nearby-user-btn.message:hover {
            background: #2563eb;
        }

        .nearby-user-btn.add-friend {
            background: #10b981;
            color: white;
        }

        .nearby-user-btn.add-friend:hover {
            background: #059669;
        }

        .nearby-user-btn.added {
            background: #e5e7eb;
            color: #6b7280;
            cursor: default;
        }

        .people-sidebar-empty {
            text-align: center;
            padding: 40px 20px;
            color: rgba(255, 255, 255, 0.7);
        }

        .people-sidebar-empty-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .people-sidebar-empty-text {
            font-size: 14px;
            line-height: 1.5;
        }

        .people-sidebar-toggle {
            position: fixed;
            right: 20px;
            bottom: 20px;
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            display: none;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            z-index: 999;
        }

        .people-sidebar-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.6);
        }

        .people-sidebar-toggle.hidden {
            display: none;
        }

        @media (max-width: 768px) {
            .people-sidebar {
                width: 100%;
            }
        }
    </style>

    <div class="people-sidebar-header">
        <h3>👥 Nearby People</h3>
        <button class="people-sidebar-close" onclick="closePeopleSidebar()">✕</button>
    </div>

    <div class="people-sidebar-content" id="nearby-users-list">
        <div class="people-sidebar-empty">
            <div class="people-sidebar-empty-icon">🔍</div>
            <div class="people-sidebar-empty-text">Loading nearby users...</div>
        </div>
    </div>

</div>

<button class="people-sidebar-toggle" id="people-sidebar-toggle" onclick="openPeopleSidebar()">👥</button>

<script>
    function openPeopleSidebar() {
        document.getElementById('people-sidebar').classList.remove('hidden');
        document.getElementById('people-sidebar-toggle').classList.add('hidden');
        loadNearbyUsers();
    }

    function closePeopleSidebar() {
        document.getElementById('people-sidebar').classList.add('hidden');
        document.getElementById('people-sidebar-toggle').classList.remove('hidden');
    }

    async function loadNearbyUsers() {
        try {
            // Use the same endpoint as the map to show all users with coordinates
            const response = await fetch('/api/people-map', {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Failed to load nearby users');

            const data = await response.json();
            const users = data.data || [];

            const container = document.getElementById('nearby-users-list');

            if (users.length === 0) {
                container.innerHTML = `
                    <div class="people-sidebar-empty">
                        <div class="people-sidebar-empty-icon">😴</div>
                        <div class="people-sidebar-empty-text">No one nearby right now</div>
                    </div>
                `;
                return;
            }

            container.innerHTML = users.map(user => {
                const initials = ((user.first_name||'')[0]||'').toUpperCase() + ((user.last_name||'')[0]||'').toUpperCase();
                const colors = ['#2ecc71', '#f39c12', '#3498db', '#e74c3c', '#9b59b6', '#1abc9c', '#e67e22', '#34495e'];
                const colorIndex = user.id % colors.length;
                const bgColor = colors[colorIndex];
                
                let actionBtn = '';
                if (user.friendship_status === 'friends') {
                    return `
                        <div class="nearby-user-item">
                            <div class="nearby-user-avatar" style="background: ${bgColor};">
                                ${initials}
                                <div class="nearby-user-status" style="background: ${user.online_status === 'online' ? '#4ade80' : '#9ca3af'};"></div>
                            </div>
                            <div class="nearby-user-info">
                                <div class="nearby-user-name">${user.first_name} ${user.last_name}</div>
                                <div class="nearby-user-status-text">${user.online_status === 'online' ? 'Online now' : 'Offline'}</div>
                            </div>
                            <div class="nearby-user-actions">
                                <button class="nearby-user-btn message" onclick="openFriendChat(${user.id},'${user.first_name} ${user.last_name}','${initials}')" title="Message">💬</button>
                            </div>
                        </div>
                    `;
                } else if (user.friendship_status === 'request_sent') {
                    return `
                        <div class="nearby-user-item">
                            <div class="nearby-user-avatar" style="background: ${bgColor};">
                                ${initials}
                                <div class="nearby-user-status" style="background: ${user.online_status === 'online' ? '#4ade80' : '#9ca3af'};"></div>
                            </div>
                            <div class="nearby-user-info">
                                <div class="nearby-user-name">${user.first_name} ${user.last_name}</div>
                                <div class="nearby-user-status-text">Request sent</div>
                            </div>
                            <div class="nearby-user-actions">
                                <button class="nearby-user-btn added" disabled title="Request Sent">⏳</button>
                            </div>
                        </div>
                    `;
                } else if (user.friendship_status === 'request_received') {
                    return `
                        <div class="nearby-user-item">
                            <div class="nearby-user-avatar" style="background: ${bgColor};">
                                ${initials}
                                <div class="nearby-user-status" style="background: ${user.online_status === 'online' ? '#4ade80' : '#9ca3af'};"></div>
                            </div>
                            <div class="nearby-user-info">
                                <div class="nearby-user-name">${user.first_name} ${user.last_name}</div>
                                <div class="nearby-user-status-text">Wants to be friends</div>
                            </div>
                            <div class="nearby-user-actions">
                                <button class="nearby-user-btn add-friend" onclick="acceptFriend(${user.id})" title="Accept">✓</button>
                            </div>
                        </div>
                    `;
                } else {
                    return `
                        <div class="nearby-user-item">
                            <div class="nearby-user-avatar" style="background: ${bgColor};">
                                ${initials}
                                <div class="nearby-user-status" style="background: ${user.online_status === 'online' ? '#4ade80' : '#9ca3af'};"></div>
                            </div>
                            <div class="nearby-user-info">
                                <div class="nearby-user-name">${user.first_name} ${user.last_name}</div>
                                <div class="nearby-user-status-text">${user.online_status === 'online' ? 'Online now' : 'Offline'}</div>
                            </div>
                            <div class="nearby-user-actions">
                                <button class="nearby-user-btn message" onclick="openFriendChat(${user.id},'${user.first_name} ${user.last_name}','${initials}')" title="Message">💬</button>
                                <button class="nearby-user-btn add-friend" onclick="addFriend(${user.id}, '${user.username}')" title="Add Friend">➕</button>
                            </div>
                        </div>
                    `;
                }
            }).join('');
        } catch (error) {
            document.getElementById('nearby-users-list').innerHTML = `
                <div class="people-sidebar-empty">
                    <div class="people-sidebar-empty-icon">⚠️</div>
                    <div class="people-sidebar-empty-text">Error loading users</div>
                </div>
            `;
        }
    }

    // Refresh nearby users every 30 seconds
    setInterval(loadNearbyUsers, 30000);
</script>
