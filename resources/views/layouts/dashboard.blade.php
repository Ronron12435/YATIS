<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() }}">
    <meta name="user-role" content="{{ auth()->user()->role }}">
    <meta name="user-latitude" content="{{ auth()->user()->latitude ?? '' }}">
    <meta name="user-longitude" content="{{ auth()->user()->longitude ?? '' }}">
    <title>@yield('title', 'Dashboard - YATIS')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    @stack('styles')
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; display: flex; height: 100vh; overflow: hidden; }
        .menu-toggle { display: none; position: fixed; top: 15px; left: 15px; z-index: 998; background: #2d2d2d; color: white; border: none; padding: 12px 16px; border-radius: 8px; font-size: 24px; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
        .navbar { background: white; color: #333; padding: 15px 20px; display: flex; justify-content: flex-end; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); z-index: 98; position: relative; flex-shrink: 0; }
        .navbar .user-info { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; color: #333; }
        .navbar .user-info span { color: #666; }
        .navbar .user-info strong { color: #333; }
        .badge { background: #00bcd4; color: white; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; box-shadow: 0 2px 6px rgba(0,188,212,0.3); white-space: nowrap; }
        .right-section { display: flex; flex-direction: column; margin-left: 260px; width: calc(100% - 260px); height: 100vh; overflow: hidden; }
        .sidebar { width: 260px; background: #2d2d2d; box-shadow: 2px 0 20px rgba(0,0,0,0.3); display: flex; flex-direction: column; transition: transform 0.3s ease; z-index: 99; border-right: 1px solid #3a3a3a; height: 100vh; position: fixed; left: 0; top: 0; }
        .sidebar-header { padding: 20px 16px; border-bottom: 1px solid #3a3a3a; background: #242424; display: flex; justify-content: center; align-items: center; }
        .sidebar-logo-text { font-size: 28px; font-weight: 700; color: #ffffff; letter-spacing: 2px; text-transform: uppercase; }
        .sidebar-content { flex: 1; overflow-y: auto; overflow-x: hidden; padding: 8px 0; }
        .sidebar-footer { padding: 16px; border-top: 1px solid #3a3a3a; background: #242424; }
        .logout-btn { width: 100%; background: #3a3a3a; color: #ff5252; border: 1px solid #4a4a4a; padding: 12px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.2s; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .logout-btn:hover { background: #ff5252; color: white; border-color: #ff5252; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(255,82,82,0.3); }

        .premium-btn { background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); color: #1a3a52 !important; font-weight: 600; margin: 10px 8px; box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3); }
        .premium-btn:hover { background: linear-gradient(135deg, #ffed4e 0%, #ffd700 100%); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4); }
        .sidebar-item { padding: 14px 18px; margin: 2px 8px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 14px; color: #b0b0b0; font-weight: 500; font-size: 15px; border-radius: 8px; position: relative; }
        .sidebar-item:hover { background: #3a3a3a; color: #ffffff; }
        .sidebar-item.active { background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%); font-weight: 600; color: #ffffff; box-shadow: 0 2px 8px rgba(25,118,210,0.4); }
        .sidebar-item.active::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 3px; height: 60%; background: #42a5f5; border-radius: 0 3px 3px 0; }
        .sidebar-item .sidebar-icon { font-size: 20px; width: 24px; text-align: center; display: flex; align-items: center; justify-content: center; }
        .notification-badge { background: #ff5252; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; margin-left: auto; box-shadow: 0 2px 6px rgba(255,82,82,0.4); }
        .sidebar-item-parent { position: relative; }
        .sidebar-dropdown { display: none; background: #242424; border-radius: 8px; margin: 4px 8px 8px 8px; padding: 4px 0; border: 1px solid #3a3a3a; }
        .sidebar-item-parent:hover .sidebar-dropdown { display: block; }
        .sidebar-dropdown .sidebar-item { padding: 10px 16px 10px 44px; font-size: 13px; margin: 2px 4px; color: #909090; }
        .sidebar-dropdown .sidebar-item:hover { background: #2a2a2a; color: #ffffff; }
        .sidebar-dropdown .sidebar-item.active { background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%); color: #ffffff; }
        .sidebar-item-parent > .sidebar-item::after { content: '›'; font-size: 16px; margin-left: auto; transition: transform 0.2s; color: #606060; font-weight: bold; }
        .sidebar-item-parent:hover > .sidebar-item::after { transform: rotate(90deg); color: #b0b0b0; }
        .content { flex: 1; overflow-y: auto; padding: 30px; background: #f5f7fa; width: 100%; min-height: 100vh; }
        .content-section { display: none; }
        .content-section.active { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .page-title { font-size: 32px; color: #1a3a52; margin-bottom: 25px; font-weight: 700; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 25px; border-top: 3px solid #00bcd4; }
        .card h3 { color: #1a3a52; margin-bottom: 15px; font-weight: 600; }
        .card p { color: #666; line-height: 1.8; }
        .profile-header { background: linear-gradient(135deg, #1a3a52 0%, #2c5f8d 50%, #00bcd4 100%); padding: 40px 30px; border-radius: 16px; margin-bottom: 30px; box-shadow: 0 8px 24px rgba(26, 58, 82, 0.2); display: flex; align-items: center; gap: 25px; position: relative; overflow: hidden; }
        .profile-header::before { content: ''; position: absolute; top: -50%; right: -10%; width: 300px; height: 300px; background: rgba(0, 188, 212, 0.1); border-radius: 50%; }
        .profile-avatar { position: relative; z-index: 1; }
        .avatar-circle { width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #00bcd4 0%, #00acc1 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 42px; font-weight: 700; box-shadow: 0 4px 16px rgba(0, 188, 212, 0.4); border: 4px solid rgba(255, 255, 255, 0.3); }
        .profile-info { flex: 1; position: relative; z-index: 1; }
        .profile-name { color: white; font-size: 32px; font-weight: 700; margin: 0 0 12px 0; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); }
        .profile-badges { display: flex; gap: 10px; flex-wrap: wrap; }
        .badge-role { background: rgba(255, 255, 255, 0.25); color: white; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .badge-premium { background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); color: #1a3a52; box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3); padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .badge-free { background: rgba(255, 255, 255, 0.2); color: white; border: 1px solid rgba(255, 255, 255, 0.3); padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .job-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 15px; }
        .job-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; }
        .job-header h4 { color: #667eea; margin: 0; }
        .job-meta { display: flex; gap: 15px; flex-wrap: wrap; margin: 10px 0; font-size: 14px; color: #666; }
        .job-meta span { display: flex; align-items: center; gap: 5px; }
        .badge-status { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .badge-pending { background: #f39c12; color: white; }
        .badge-reviewed { background: #3498db; color: white; }
        .badge-accepted { background: #2ecc71; color: white; }
        .badge-rejected { background: #e74c3c; color: white; }
        .badge-job-open { background: #2ecc71; color: white; }
        .badge-job-closed { background: #95a5a6; color: white; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: linear-gradient(135deg, #1a3a52 0%, #2c5f8d 100%); padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(26,58,82,0.2); text-align: center; color: white; transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 6px 20px rgba(26,58,82,0.3); }
        .stat-card h3 { color: #00bcd4; font-size: 42px; margin-bottom: 10px; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .stat-card p { color: #e0f7fa; font-weight: 500; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; transition: all 0.3s; text-decoration: none; display: inline-block; font-weight: 600; }
        .btn-primary { background: linear-gradient(135deg, #2c5f8d 0%, #00bcd4 100%); color: white; box-shadow: 0 3px 10px rgba(0,188,212,0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,188,212,0.4); }
        @media (max-width: 768px) { .menu-toggle { display: block !important; } .right-section { margin-left: 0; width: 100%; } .sidebar { position: fixed; left: 0; top: 0; height: 100vh; transform: translateX(-100%); z-index: 1000; } .sidebar.active { transform: translateX(0); } .content { padding: 60px 15px 15px 15px !important; width: 100%; } .page-title { font-size: 24px !important; } .stats { grid-template-columns: 1fr !important; } }
        
        /* Dark Mode Styles - Eye-Friendly */
        body.dark-mode { background: #0f1419; }
        body.dark-mode .navbar { background: #1a1f2e; color: #e8eaed; }
        body.dark-mode .navbar .user-info { color: #e8eaed; }
        body.dark-mode .navbar .user-info span { color: #9aa0a6; }
        body.dark-mode .navbar .user-info strong { color: #e8eaed; }
        body.dark-mode .content { background: #0f1419; }
        body.dark-mode .card { background: #1a1f2e; color: #e8eaed; box-shadow: 0 2px 12px rgba(0,0,0,0.5); border-top-color: #4db8d4; }
        body.dark-mode .card h3 { color: #4db8d4; }
        body.dark-mode .card p { color: #c5cad1; }
        body.dark-mode .card h2 { color: #e8eaed; }
        body.dark-mode .card h4 { color: #e8eaed; }
        body.dark-mode .card label { color: #c5cad1; }
        body.dark-mode .page-title { color: #4db8d4; }
        body.dark-mode .profile-header { background: linear-gradient(135deg, #1a3a52 0%, #0d1f2d 50%, #4db8d4 100%); }
        body.dark-mode .profile-name { color: #e8eaed; }
        body.dark-mode .stat-card { background: linear-gradient(135deg, #1a2a3a 0%, #1a3a52 100%); }
        body.dark-mode .stat-card h3 { color: #4db8d4; }
        body.dark-mode .stat-card p { color: #c5cad1; }
        body.dark-mode .job-card { background: #1a1f2e; color: #e8eaed; }
        body.dark-mode .job-header h4 { color: #4db8d4; }
        body.dark-mode .job-meta { color: #c5cad1; }
        body.dark-mode input, body.dark-mode textarea, body.dark-mode select { background: #0f1419; color: #e8eaed; border-color: #3a4150; }
        body.dark-mode input::placeholder, body.dark-mode textarea::placeholder { color: #7a8089; }
        body.dark-mode input:focus, body.dark-mode textarea:focus, body.dark-mode select:focus { background: #1a1f2e; border-color: #4db8d4; outline: none; }
        body.dark-mode button { color: #e8eaed; }
        body.dark-mode .btn { background: #1a3a52; color: #e8eaed; border-color: #3a4150; }
        body.dark-mode .btn:hover { background: #2c5f8d; }
        body.dark-mode table { background: #1a1f2e; color: #e8eaed; }
        body.dark-mode table th { background: #0f1419; color: #4db8d4; border-color: #3a4150; }
        body.dark-mode table td { border-color: #3a4150; }
        body.dark-mode table tr:hover { background: #262d3a; }
        body.dark-mode .modal { background: rgba(0, 0, 0, 0.8); }
        body.dark-mode .modal-content { background: #1a1f2e; color: #e8eaed; }
        body.dark-mode .modal-header { background: #0f1419; border-color: #3a4150; }
        body.dark-mode .modal-footer { background: #0f1419; border-color: #3a4150; }
        body.dark-mode .alert { background: #262d3a; color: #e8eaed; border-color: #3a4150; }
        body.dark-mode .alert-info { background: #1a3a52; border-color: #4db8d4; }
        body.dark-mode .alert-success { background: #1a3a2a; border-color: #2ecc71; }
        body.dark-mode .alert-warning { background: #3a3a1a; border-color: #f39c12; }
        body.dark-mode .alert-danger { background: #3a1a1a; border-color: #e74c3c; }
    </style>
</head>
<body>
    <button class="menu-toggle" onclick="toggleMobileMenu()"><i class="fas fa-bars"></i></button>
    @include('components.sidebar')
    
    <div class="right-section">
        <div class="content">
            @yield('content')
        </div>
    </div>



    <script>
        window.showSection = function(sectionId) {
            document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('active'));
            const target = document.getElementById(sectionId);
            if(target) target.classList.add('active');
            event.target.closest('.sidebar-item')?.classList.add('active');
            
            // Save active section to sessionStorage (cleared on browser close)
            sessionStorage.setItem('activeSection', sectionId);
            
            // If closing chat, clear chat state
            if (sectionId !== 'people') {
                sessionStorage.removeItem('activeChatUserId');
                sessionStorage.removeItem('activeChatName');
                sessionStorage.removeItem('activeChatInitials');
            }
            
            if(sectionId === 'dashboard') setTimeout(() => initDashboardMap(), 100);
            if(sectionId === 'businesses') setTimeout(() => { if(typeof initBusinessesMap === 'function') initBusinessesMap(); }, 100);
            if(sectionId === 'people') setTimeout(() => initPeopleMap(), 150);
            if(sectionId === 'my-friends') setTimeout(() => { if(typeof loadFriendsList === 'function') loadFriendsList(); }, 100);
            if(sectionId === 'friend-requests') setTimeout(() => { if(typeof loadFriendRequests === 'function') loadFriendRequests(); }, 100);
            if(sectionId === 'messages') setTimeout(() => { if(typeof loadConversations === 'function') loadConversations(); }, 100);
            if(sectionId === 'employers') setTimeout(() => { if(typeof EmployersModule !== 'undefined') EmployersModule.init(); if(typeof loadStats === 'function') loadStats(); }, 100);
            if(sectionId === 'jobs') setTimeout(() => { if(typeof JobsModule !== 'undefined') JobsModule.init(); }, 100);
            if(sectionId === 'job-listings') setTimeout(() => { if(typeof loadJobListings === 'function') loadJobListings(); }, 100);
            if(sectionId === 'my-applications') setTimeout(() => { if(typeof loadMyApplications === 'function') loadMyApplications(); }, 100);
            if(sectionId === 'destinations') setTimeout(() => { if(typeof initDestinationsSection === 'function') initDestinationsSection(); }, 100);
            if(sectionId === 'profile') setTimeout(() => { if(typeof initProfileSection === 'function') initProfileSection(); }, 100);
            if(sectionId === 'my-business') setTimeout(() => { if(typeof initMyBusinessSection === 'function') initMyBusinessSection(); }, 100);
            if(sectionId === 'admin-panel') setTimeout(() => { if(typeof AdminModule !== 'undefined') AdminModule.init(); }, 100);
            if(sectionId === 'events') setTimeout(() => { if(typeof EventsModule !== 'undefined') EventsModule.init(); }, 100);
            if(sectionId === 'groups') setTimeout(() => { if(typeof initGroupsSection === 'function') initGroupsSection(); }, 100);
        };

        // Restore active section on page load - use sessionStorage for refresh, dashboard for fresh login
        document.addEventListener('DOMContentLoaded', function() {
            // Only restore section if this is a same-tab page refresh (not a fresh navigation)
            const isRefresh = sessionStorage.getItem('pageLoaded') === 'true';
            const activeSection = isRefresh ? (sessionStorage.getItem('activeSection') || 'dashboard') : 'dashboard';
            sessionStorage.setItem('pageLoaded', 'true');
            const sectionElement = document.getElementById(activeSection);
            const sidebarItem = document.querySelector(`[onclick="showSection('${activeSection}')"]`);
            
            if(sectionElement) {
                document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
                document.querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('active'));
                sectionElement.classList.add('active');
                if(sidebarItem) sidebarItem.classList.add('active');
                
                // Initialize the section
                if(activeSection === 'dashboard') setTimeout(() => initDashboardMap(), 100);
                if(activeSection === 'businesses') setTimeout(() => { if(typeof initBusinessesMap === 'function') initBusinessesMap(); }, 100);
                if(activeSection === 'people') setTimeout(() => initPeopleMap(), 150);
                if(activeSection === 'my-friends') setTimeout(() => { if(typeof loadFriendsList === 'function') loadFriendsList(); }, 100);
                if(activeSection === 'friend-requests') setTimeout(() => { if(typeof loadFriendRequests === 'function') loadFriendRequests(); }, 100);
                if(activeSection === 'messages') setTimeout(() => { if(typeof loadConversations === 'function') loadConversations(); }, 100);
                if(activeSection === 'employers') setTimeout(() => { if(typeof EmployersModule !== 'undefined') EmployersModule.init(); if(typeof loadStats === 'function') loadStats(); }, 100);
                if(activeSection === 'jobs') setTimeout(() => { if(typeof JobsModule !== 'undefined') JobsModule.init(); }, 100);
                if(activeSection === 'job-listings') setTimeout(() => { if(typeof loadJobListings === 'function') loadJobListings(); }, 100);
                if(activeSection === 'my-applications') setTimeout(() => { if(typeof loadMyApplications === 'function') loadMyApplications(); }, 100);
                if(activeSection === 'destinations') setTimeout(() => { if(typeof initDestinationsSection === 'function') initDestinationsSection(); }, 100);
                if(activeSection === 'admin-panel') setTimeout(() => { if(typeof AdminModule !== 'undefined') AdminModule.init(); }, 100);
                if(activeSection === 'events') setTimeout(() => { if(typeof EventsModule !== 'undefined') EventsModule.init(); }, 100);
                if(activeSection === 'profile') setTimeout(() => { if(typeof initProfileSection === 'function') initProfileSection(); }, 100);
                if(activeSection === 'my-business') setTimeout(() => { if(typeof initMyBusinessSection === 'function') initMyBusinessSection(); }, 100);
                if(activeSection === 'groups') setTimeout(() => { if(typeof initGroupsSection === 'function') initGroupsSection(); }, 100);
            }
        });

        window.toggleMobileMenu = function() {
            document.querySelector('.sidebar')?.classList.toggle('active');
        };

        window.closeMobileMenu = function() {
            document.querySelector('.sidebar')?.classList.remove('active');
        };

        // Close menu when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (sidebar && menuToggle && window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // Close menu when a sidebar item is clicked on mobile
        document.querySelectorAll('.sidebar-item').forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    document.querySelector('.sidebar')?.classList.remove('active');
                }
            });
        });

        // Badge polling — unread messages + pending friend requests
        let badgeUpdateInProgress = false;
        
        function updateBadges() {
            if (badgeUpdateInProgress) return; // Prevent overlapping requests
            badgeUpdateInProgress = true;
            
            let unreadCount = 0;
            let friendRequestCount = 0;
            let completed = 0;
            
            // Unread messages badge
            fetch('/api/messages/unread/count', { credentials: 'include' })
                .then(r => r.json())
                .then(data => {
                    // API response structure: { success, message, data: { unread_count } }
                    unreadCount = data?.data?.unread_count || 0;
                    const badge = document.getElementById('unread-msg-badge');
                    if (badge) { 
                        badge.textContent = unreadCount; 
                        badge.style.display = unreadCount > 0 ? 'inline-flex' : 'none'; 
                    }
                    completed++;
                    if (completed === 2) updatePeopleBadge(unreadCount, friendRequestCount);
                }).catch(() => {
                    completed++;
                    if (completed === 2) updatePeopleBadge(0, friendRequestCount);
                });

            // Friend requests badge
            fetch('/api/friends/requests', { credentials: 'include' })
                .then(r => r.json())
                .then(data => {
                    const requests = data?.data || [];
                    friendRequestCount = Array.isArray(requests) ? requests.length : 0;
                    const badge = document.getElementById('friend-req-badge');
                    if (badge) {
                        badge.textContent = friendRequestCount;
                        badge.style.display = friendRequestCount > 0 ? 'inline-flex' : 'none';
                    }
                    completed++;
                    if (completed === 2) updatePeopleBadge(unreadCount, friendRequestCount);
                }).catch(() => {
                    completed++;
                    if (completed === 2) updatePeopleBadge(unreadCount, 0);
                });
        }

        // Helper function to update People badge with combined count
        function updatePeopleBadge(unreadCount, friendRequestCount) {
            const peopleBadge = document.getElementById('people-badge');
            if (!peopleBadge) return;
            
            const totalCount = (unreadCount || 0) + (friendRequestCount || 0);
            
            if (totalCount > 0) {
                peopleBadge.textContent = totalCount;
                peopleBadge.style.display = 'inline-flex';
            } else {
                peopleBadge.style.display = 'none';
            }
            
            badgeUpdateInProgress = false;
        }

        // Start badge polling on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateBadges();
            // Poll every 2 seconds for badge updates
            setInterval(updateBadges, 2000);
        });

        let dashboardMap = null;
        function initDashboardMap() {
            const container = document.getElementById('dashboard-map-container');
            if(!container || dashboardMap) return;
            
            // Create Leaflet map centered on Sagay City
            dashboardMap = L.map('dashboard-map-container', { zoomControl: false }).setView([10.8967, 123.4253], 13);
            
            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(dashboardMap);
            
            // Get user's real-time GPS location
            if(navigator.geolocation) {
                function placeUserMarker(lat, lng) {
                    if(window.userLocationMarker) dashboardMap.removeLayer(window.userLocationMarker);
                    if(window.userLocationAccuracy) dashboardMap.removeLayer(window.userLocationAccuracy);

                    // Accuracy circle
                    window.userLocationAccuracy = L.circle([lat, lng], {
                        radius: 50,
                        color: '#00bcd4',
                        fillColor: '#00bcd4',
                        fillOpacity: 0.15,
                        weight: 1
                    }).addTo(dashboardMap);

                    // Cyan dot marker
                    window.userLocationMarker = L.circleMarker([lat, lng], {
                        radius: 10,
                        fillColor: '#00bcd4',
                        color: 'white',
                        weight: 3,
                        opacity: 1,
                        fillOpacity: 1
                    }).addTo(dashboardMap).bindPopup('<b>You are here</b>');

                    dashboardMap.setView([lat, lng], 15);
                }

                // First call to trigger browser permission prompt
                navigator.geolocation.getCurrentPosition(
                    function(pos) {
                        placeUserMarker(pos.coords.latitude, pos.coords.longitude);
                        navigator.geolocation.watchPosition(
                            function(pos) { placeUserMarker(pos.coords.latitude, pos.coords.longitude); },
                            function(err) { console.warn('Watch error:', err.message); },
                            { enableHighAccuracy: true, maximumAge: 0 }
                        );
                    },
                    function(err) {
                        // GPS blocked (http:// or denied) — use saved DB location
                        @if(auth()->user()->latitude && auth()->user()->longitude)
                            placeUserMarker({{ auth()->user()->latitude }}, {{ auth()->user()->longitude }});
                        @else
                            // No DB location — try IP fallback
                            fetch('https://ipapi.co/json/')
                                .then(r => r.json())
                                .then(d => { if(d.latitude) placeUserMarker(d.latitude, d.longitude); })
                                .catch(() => {});
                        @endif
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            } else {
                @if(auth()->user()->latitude && auth()->user()->longitude)
                    placeUserMarker({{ auth()->user()->latitude }}, {{ auth()->user()->longitude }});
                @else
                    fetch('https://ipapi.co/json/')
                        .then(r => r.json())
                        .then(d => { if(d.latitude) placeUserMarker(d.latitude, d.longitude); })
                        .catch(() => {});
                @endif
            }
        }
        
        // Initialize dashboard map on page load
        document.addEventListener('DOMContentLoaded', initDashboardMap);

        window.locateMe = function() {
            const btn = document.getElementById('locate-btn');
            if(btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Locating...'; btn.disabled = true; }

            function placeAndSave(lat, lng) {
                if(dashboardMap) {
                    if(window.userLocationMarker) dashboardMap.removeLayer(window.userLocationMarker);
                    if(window.userLocationAccuracy) dashboardMap.removeLayer(window.userLocationAccuracy);
                    window.userLocationAccuracy = L.circle([lat, lng], {
                        radius: 100, color: '#00bcd4', fillColor: '#00bcd4', fillOpacity: 0.15, weight: 1
                    }).addTo(dashboardMap);
                    window.userLocationMarker = L.circleMarker([lat, lng], {
                        radius: 12, fillColor: '#00bcd4', color: 'white', weight: 3, opacity: 1, fillOpacity: 1
                    }).addTo(dashboardMap).bindPopup('<b>📍 You are here</b>').openPopup();
                    dashboardMap.setView([lat, lng], 16);
                }
                fetch('/api/profile/update-location', {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'application/json' },
                    body: JSON.stringify({ latitude: lat, longitude: lng })
                });
                if(btn) { btn.innerHTML = '<i class="fas fa-check"></i> Located'; btn.style.background = '#27ae60'; btn.disabled = false; }
            }

            // Try GPS first
            if(navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(pos) { placeAndSave(pos.coords.latitude, pos.coords.longitude); },
                    function() {
                        // GPS blocked — fall back to IP geolocation (no permission needed)
                        fetch('https://ipapi.co/json/')
                            .then(r => r.json())
                            .then(data => {
                                if(data.latitude && data.longitude) {
                                    placeAndSave(data.latitude, data.longitude);
                                    if(btn) { btn.innerHTML = '<i class="fas fa-map-marker-alt"></i> Located (IP)'; btn.style.background = '#f39c12'; btn.disabled = false; }
                                }
                            })
                            .catch(() => {
                                if(btn) { btn.innerHTML = '<i class="fas fa-crosshairs"></i> Locate Me'; btn.disabled = false; }
                            });
                    },
                    { enableHighAccuracy: true, timeout: 8000 }
                );
            } else {
                // No GPS support — use IP geolocation
                fetch('https://ipapi.co/json/')
                    .then(r => r.json())
                    .then(data => { if(data.latitude) placeAndSave(data.latitude, data.longitude); });
            }
        };
    </script>

    <!-- Load all module scripts globally -->
    <script src="{{ asset('js/jobs.js') }}"></script>
    <script src="{{ asset('js/messages.js') }}"></script>
    <script src="{{ asset('js/events.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/groups.js') }}"></script>
    <script src="{{ asset('js/people.js') }}?v={{ microtime(true) }}"></script>
    <script src="{{ asset('js/profile.js') }}?v={{ microtime(true) }}"></script>
    <script src="{{ asset('js/my-business.js') }}?v={{ microtime(true) }}"></script>
    <script src="{{ asset('js/businesses.js') }}?v={{ microtime(true) }}"></script>
    <script src="{{ asset('js/business-management.js') }}?v={{ microtime(true) }}"></script>
    @if(auth()->user()->role === 'admin')
    <script src="{{ asset('js/admin.js') }}?v={{ time() }}"></script>
    @endif
    <script src="{{ asset('js/destinations.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/employers.js') }}?v={{ time() }}"></script>

    @stack('scripts')
    
    <!-- Force reload businesses.js with inline version -->
    <script>
        // This ensures the latest version is always loaded
        fetch('{{ asset("js/businesses.js") }}?v={{ microtime(true) }}')
            .then(r => r.text())
            .then(code => {
                eval(code);
                console.log('✅ Businesses.js reloaded from server');
            })
            .catch(err => console.error('Failed to reload businesses.js:', err));
    </script>
