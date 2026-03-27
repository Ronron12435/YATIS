<div id="admin-panel" class="content-section">
    <h1 class="page-title"><i class="fas fa-cog"></i> Admin Panel</h1>

    <!-- Statistics Cards -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:20px; margin-bottom:30px;">
        <div style="background:linear-gradient(135deg, #1976d2 0%, #1565c0 100%); color:white; padding:25px; border-radius:12px; box-shadow:0 4px 12px rgba(25,118,210,0.3);">
            <div style="font-size:32px; font-weight:700; margin-bottom:8px;" id="stat-total-users">0</div>
            <div style="font-size:14px; opacity:0.9;">Total Users</div>
        </div>
        <div style="background:linear-gradient(135deg, #388e3c 0%, #2e7d32 100%); color:white; padding:25px; border-radius:12px; box-shadow:0 4px 12px rgba(56,142,60,0.3);">
            <div style="font-size:32px; font-weight:700; margin-bottom:8px;" id="stat-total-businesses">0</div>
            <div style="font-size:14px; opacity:0.9;">Businesses</div>
        </div>
        <div style="background:linear-gradient(135deg, #f57c00 0%, #e65100 100%); color:white; padding:25px; border-radius:12px; box-shadow:0 4px 12px rgba(245,124,0,0.3);">
            <div style="font-size:32px; font-weight:700; margin-bottom:8px;" id="stat-total-employers">0</div>
            <div style="font-size:14px; opacity:0.9;">Employers</div>
        </div>
    </div>

    <!-- All Users Section -->
    <div class="card" style="margin-bottom:30px;">
        <h3><i class="fas fa-users"></i> All Users</h3>
        <div style="margin-bottom:15px;">
            <input id="user-search" type="text" placeholder="Search users by username, email, or name..." style="width:100%; padding:10px 14px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
        </div>
        <div id="users-list" style="display:grid; gap:12px;">
            <p style="color:#999; text-align:center; padding:20px;">Loading users...</p>
        </div>
    </div>

    <!-- Create Business Account Section -->
    <div class="card" style="margin-bottom:30px; border-top:3px solid #e74c3c;">
        <h3><i class="fas fa-building"></i> Create Business Account (Subscribed)</h3>
        <p style="color:#666; margin-bottom:20px;">Create accounts for businesses that have subscribed to the platform</p>

        <form id="create-business-form" style="display:grid; gap:15px;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600; color:#333;">Username *</label>
                    <input id="business-username" type="text" placeholder="e.g., mybusiness" style="width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600; color:#333;">Email *</label>
                    <input id="business-email" type="email" placeholder="business@example.com" style="width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600; color:#333;">First Name *</label>
                    <input id="business-first-name" type="text" placeholder="First name" style="width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600; color:#333;">Last Name *</label>
                    <input id="business-last-name" type="text" placeholder="Last name" style="width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600; color:#333;">Password *</label>
                    <input id="business-password" type="password" placeholder="Enter password" style="width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600; color:#333;">Confirm Password *</label>
                    <input id="business-confirm-password" type="password" placeholder="Confirm password" style="width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                </div>
            </div>

            <button type="submit" style="padding:12px 24px; background:#e74c3c; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:14px;">
                <i class="fas fa-plus"></i> Create Business Account
            </button>
        </form>
    </div>

    <!-- Event Management Section -->
    <div class="card" style="border-top:3px solid #27ae60;">
        <h3><i class="fas fa-calendar"></i> Event Management</h3>
        <p style="color:#666; margin-bottom:20px;">Create and manage events and challenges for tourists</p>

        <form id="create-event-form" style="display:grid; gap:15px; margin-bottom:30px;">
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600; color:#333;">Event Title *</label>
                <input id="event-title" type="text" placeholder="e.g., Summer Adventure Challenge" style="width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600; color:#333;">Start Date *</label>
                    <input id="event-start-date" type="date" style="width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600; color:#333;">End Date *</label>
                    <input id="event-end-date" type="date" style="width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                </div>
            </div>

            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600; color:#333;">Description *</label>
                <textarea id="event-description" placeholder="Describe the event and what participants can expect..." style="width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px; min-height:100px; resize:vertical;"></textarea>
            </div>

            <button type="submit" style="padding:12px 24px; background:#27ae60; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:14px;">
                <i class="fas fa-plus"></i> Create Event
            </button>
        </form>

        <h4 style="margin-top:25px; margin-bottom:15px; color:#333;">Existing Events</h4>
        <div id="events-list" style="display:grid; gap:12px;">
            <p style="color:#999; text-align:center; padding:20px;">Loading events...</p>
        </div>
    </div>
</div>
