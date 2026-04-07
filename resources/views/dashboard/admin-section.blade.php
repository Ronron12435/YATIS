<div id="admin-panel" class="content-section">
    <h1 class="page-title"><i class="fas fa-cog"></i> Admin Panel</h1>

    <!-- Statistics Cards -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:20px; margin-bottom:30px;">
        <div style="background:linear-gradient(135deg, #1976d2 0%, #1565c0 100%); color:white; padding:25px; border-radius:12px; box-shadow:0 4px 12px rgba(25,118,210,0.3);">
            <div style="font-size:32px; font-weight:700; margin-bottom:8px;" id="stat-total-users">0</div>
            <div style="font-size:14px; opacity:0.9;">Total Users</div>
        </div>
        <div style="background:linear-gradient(135deg, #388e3c 0%, #2e7d32 100%); color:white; padding:25px; border-radius:12px; box-shadow:0 4px 12px rgba(56,142,60,0.3);">
            <div style="font-size:32px; font-weight:700; margin-bottom:8px;" id="stat-total-business-users">0</div>
            <div style="font-size:14px; opacity:0.9;">Business Users</div>
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
        <div id="admin-events-list" style="display:block !important; width:100% !important; min-height:100px !important; visibility:visible !important; opacity:1 !important; overflow:visible !important; background:white !important; padding:15px !important; border:1px solid #ddd !important; border-radius:6px !important; position:relative !important; z-index:1 !important; clear:both !important; float:none !important;"><p style="color:#999; text-align:center; padding:20px;">Loading events...</p></div>
    </div>
</div>

<!-- Event Details Modal -->
<div id="event-details-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9998; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:12px; padding:30px; max-width:600px; width:90%; max-height:90vh; overflow-y:auto; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 id="event-details-title" style="margin:0; color:#333; font-size:20px;"><i class="fas fa-calendar"></i> Event Details</h2>
            <button onclick="AdminModule.closeEventModal()" style="background:none; border:none; font-size:24px; cursor:pointer; color:#999;">&times;</button>
        </div>

        <div id="event-details-content" style="margin-bottom:20px;">
            <!-- Event info will be inserted here -->
        </div>

        <div style="border-top:1px solid #ddd; padding-top:20px;">
            <h3 style="margin:0 0 15px 0; color:#333; font-size:16px;"><i class="fas fa-tasks"></i> Tasks</h3>
            <div id="event-tasks-list" style="margin-bottom:20px;">
                <p style="color:#999; text-align:center; padding:20px;">Loading tasks...</p>
            </div>
            <button onclick="AdminModule.openTaskFormForEvent()" style="width:100%; padding:12px 24px; background:#667eea; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:14px;">
                <i class="fas fa-plus"></i> Add Task to This Event
            </button>
        </div>
    </div>
</div>

<!-- Task Management Modal -->
<div id="task-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:12px; padding:30px; max-width:600px; width:90%; max-height:90vh; overflow-y:auto; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="margin:0; color:#333; font-size:20px;"><i class="fas fa-tasks"></i> Add Task to Event</h2>
            <button onclick="AdminModule.closeTaskModal()" style="background:none; border:none; font-size:24px; cursor:pointer; color:#999;">&times;</button>
        </div>

        <form id="create-task-form" style="display:grid; gap:15px;">
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600; color:#333;">Select Event *</label>
                <select id="task-event-id" style="width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                    <option value="">-- Choose an event --</option>
                </select>
            </div>

            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600; color:#333;">Task Title *</label>
                <input id="task-title" type="text" placeholder="e.g., Walk 5000 steps" style="width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
            </div>

            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600; color:#333;">Task Description</label>
                <textarea id="task-description" placeholder="Describe what users need to do..." style="width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px; min-height:80px; resize:vertical;"></textarea>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600; color:#333;">Task Type *</label>
                    <select id="task-type" style="width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                        <option value="">-- Select type --</option>
                        <option value="steps">Steps</option>
                        <option value="location">Location</option>
                        <option value="qr_scan">QR Scan</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600; color:#333;">Reward Points *</label>
                    <input id="task-reward-points" type="number" placeholder="e.g., 100" min="1" style="width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                </div>
            </div>

            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600; color:#333;">Target Value (optional)</label>
                <input id="task-target-value" type="number" placeholder="e.g., 5000 for steps" min="1" style="width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
            </div>

            <div id="qr-code-field" style="display:none;">
                <label style="display:block; margin-bottom:5px; font-weight:600; color:#333;">QR Code Value *</label>
                <input id="task-qr-code" type="text" placeholder="e.g., EVENT-2026-SUMMER-001" style="width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                <p style="margin:5px 0 0 0; color:#666; font-size:12px;">Users will need to scan or enter this exact code to complete the task.</p>
                
                <!-- QR Code Preview -->
                <div style="margin-top:15px; padding:15px; background:#f0f0f0; border-radius:8px; text-align:center;">
                    <p style="margin:0 0 10px 0; color:#333; font-weight:600;">QR Code Preview:</p>
                    <div id="qr-preview" style="display:inline-block; padding:10px; background:white; border-radius:6px; min-width:200px; min-height:200px; display:flex; align-items:center; justify-content:center;">
                        <div style="color:#999; font-size:12px;">Enter QR code value</div>
                    </div>
                    <div style="margin-top:10px;">
                        <button type="button" onclick="AdminModule.downloadQRCode()" style="padding:8px 16px; background:#27ae60; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:12px;">
                            📥 Download QR Code
                        </button>
                    </div>
                </div>
            </div>

            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600; color:#333;">Badge/Achievement Icon (emoji or text)</label>
                <input id="task-badge" type="text" placeholder="e.g., 🏆 or 🥇 or 'Gold Medal'" style="width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
            </div>

            <button type="submit" style="padding:12px 24px; background:#667eea; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:14px;">
                <i class="fas fa-plus"></i> Create Task
            </button>
        </form>
    </div>
</div>
    </div>
</div>
