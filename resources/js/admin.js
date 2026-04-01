const AdminModule = (() => {
    let allUsers = [];
    let allEvents = [];
    let initialized = false;
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const init = () => {
        if (initialized) {
            console.log('AdminModule already initialized, skipping...');
            return;
        }
        initialized = true;
        console.log('AdminModule initializing...');
        loadStatistics();
        loadUsers();
        loadEvents();
        setupEventListeners();
        createModalHTML();
    };

    const createModalHTML = () => {
        if (!document.getElementById('admin-modal')) {
            const modalHTML = `
                <div id="admin-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
                    <div style="background:white; border-radius:12px; padding:30px; max-width:400px; box-shadow:0 10px 40px rgba(0,0,0,0.3); text-align:center;">
                        <div id="modal-icon" style="font-size:48px; margin-bottom:15px;"></div>
                        <h2 id="modal-title" style="margin:0 0 10px 0; color:#333; font-size:20px;"></h2>
                        <p id="modal-message" style="margin:0 0 25px 0; color:#666; font-size:14px; line-height:1.5;"></p>
                        <button id="modal-btn" onclick="AdminModule.closeModal()" style="padding:10px 30px; background:#3498db; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:14px;">
                            OK
                        </button>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }
    };

    const showModal = (title, message, icon = '✓', type = 'success') => {
        const modal = document.getElementById('admin-modal');
        const titleEl = document.getElementById('modal-title');
        const messageEl = document.getElementById('modal-message');
        const iconEl = document.getElementById('modal-icon');
        const btnEl = document.getElementById('modal-btn');

        titleEl.textContent = title;
        messageEl.textContent = message;
        iconEl.textContent = icon;

        const colors = {
            'success': '#27ae60',
            'error': '#e74c3c',
            'warning': '#f39c12',
            'info': '#3498db'
        };

        btnEl.style.background = colors[type] || colors['info'];
        modal.style.display = 'flex';

        // Auto-close success modals after 1 second
        if (type === 'success') {
            setTimeout(() => {
                closeModal();
            }, 1000);
        }
    };

    const closeModal = () => {
        const modal = document.getElementById('admin-modal');
        modal.style.display = 'none';
    };

    const setupEventListeners = () => {
        const userSearch = document.getElementById('user-search');
        const createBusinessForm = document.getElementById('create-business-form');
        const createEventForm = document.getElementById('create-event-form');

        if (userSearch) userSearch.addEventListener('input', filterUsers);
        if (createBusinessForm) createBusinessForm.addEventListener('submit', handleCreateBusiness);
        if (createEventForm) createEventForm.addEventListener('submit', handleCreateEvent);
    };

    const loadStatistics = () => {
        fetch('/api/admin/statistics', { credentials: 'include' })
            .then(r => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then(response => {
                const stats = response.data || {};
                document.getElementById('stat-total-users').textContent = stats.total_users || 0;
                document.getElementById('stat-total-business-users').textContent = stats.total_business_users || 0;
                document.getElementById('stat-total-posts').textContent = stats.total_posts || 0;
            })
            .catch(err => {
                console.error('Error loading statistics:', err);
            });
    };

    const loadUsers = () => {
        fetch('/api/users', { credentials: 'include' })
            .then(r => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then(response => {
                // Handle both paginated and direct array responses
                if (response.data && Array.isArray(response.data)) {
                    allUsers = response.data;
                } else if (response.data && response.data.data && Array.isArray(response.data.data)) {
                    allUsers = response.data.data;
                } else if (Array.isArray(response.data)) {
                    allUsers = response.data;
                } else {
                    allUsers = [];
                }
                renderUsers(allUsers);
            })
            .catch(err => {
                console.error('Error loading users:', err);
                document.getElementById('users-list').innerHTML = '<p style="color:#e74c3c;">Error loading users</p>';
            });
    };

    const renderUsers = (users) => {
        const usersList = document.getElementById('users-list');

        if (!users || users.length === 0) {
            usersList.innerHTML = '<p style="color:#999; text-align:center; padding:20px;">No users found</p>';
            return;
        }

        usersList.innerHTML = users.map(user => {
            const roleColors = {
                'user': '#3498db',
                'business': '#f39c12',
                'employer': '#9b59b6',
                'admin': '#e74c3c'
            };
            const roleColor = roleColors[user.role] || '#95a5a6';
            const initials = ((user.first_name || '')[0] || '').toUpperCase() + 
                           ((user.last_name || '')[0] || '').toUpperCase();

            return `
                <div style="display:flex; align-items:center; gap:15px; padding:15px; border:1px solid #ddd; border-radius:8px; background:white;">
                    <div style="width:45px; height:45px; border-radius:50%; background:linear-gradient(135deg,#3498db,#2980b9); color:white; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:16px;">
                        ${initials}
                    </div>
                    <div style="flex:1;">
                        <div style="font-weight:600; color:#333;">${escapeHtml(user.first_name || '')} ${escapeHtml(user.last_name || '')}</div>
                        <div style="font-size:13px; color:#666;">${escapeHtml(user.email)}</div>
                        <div style="font-size:12px; color:#999; margin-top:3px;">Joined ${new Date(user.created_at).toLocaleDateString()}</div>
                    </div>
                    <span style="background:${roleColor}; color:white; padding:6px 12px; border-radius:4px; font-size:12px; font-weight:600; white-space:nowrap;">
                        ${user.role.charAt(0).toUpperCase() + user.role.slice(1)}
                    </span>
                    <button onclick="AdminModule.deleteUser(${user.id})" style="padding:8px 14px; background:#e74c3c; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:12px;">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            `;
        }).join('');
    };

    const filterUsers = () => {
        const searchTerm = document.getElementById('user-search').value.toLowerCase();

        const filtered = allUsers.filter(user => {
            const fullName = `${user.first_name || ''} ${user.last_name || ''}`.toLowerCase();
            return fullName.includes(searchTerm) || 
                   user.email.toLowerCase().includes(searchTerm) ||
                   (user.username || '').toLowerCase().includes(searchTerm);
        });

        renderUsers(filtered);
    };

    const deleteUser = (userId) => {
        if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            return;
        }

        fetch(`/api/users/${userId}`, {
            method: 'DELETE',
            credentials: 'include',
            headers: {
                'X-CSRF-Token': CSRF_TOKEN,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(response => {
            if (response.success) {
                showModal('Success', 'User deleted successfully', '✓', 'success');
                setTimeout(() => {
                    loadUsers();
                    loadStatistics();
                }, 1500);
            } else {
                showModal('Error', response.message || 'Failed to delete user', '✕', 'error');
            }
        })
        .catch(err => {
            console.error('Error deleting user:', err);
            showModal('Error', 'Error deleting user', '✕', 'error');
        });
    };

    const handleCreateBusiness = (e) => {
        e.preventDefault();

        const username = document.getElementById('business-username').value.trim();
        const email = document.getElementById('business-email').value.trim();
        const firstName = document.getElementById('business-first-name').value.trim();
        const lastName = document.getElementById('business-last-name').value.trim();
        const password = document.getElementById('business-password').value;
        const confirmPassword = document.getElementById('business-confirm-password').value;

        if (!username || !email || !firstName || !lastName || !password || !confirmPassword) {
            showModal('Validation Error', 'All fields are required', '⚠', 'warning');
            return;
        }

        if (password !== confirmPassword) {
            showModal('Validation Error', 'Passwords do not match', '⚠', 'warning');
            return;
        }

        if (password.length < 6) {
            showModal('Validation Error', 'Password must be at least 6 characters', '⚠', 'warning');
            return;
        }

        fetch('/api/admin/create-business-account', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'X-CSRF-Token': CSRF_TOKEN,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                username: username,
                email: email,
                first_name: firstName,
                last_name: lastName,
                password: password,
                password_confirmation: confirmPassword
            })
        })
        .then(r => r.json())
        .then(response => {
            if (response.success) {
                showModal('Success', 'Business account created successfully!', '✓', 'success');
                document.getElementById('create-business-form').reset();
                setTimeout(() => {
                    loadUsers();
                    loadStatistics();
                }, 1500);
            } else {
                showModal('Error', response.message || 'Failed to create business account', '✕', 'error');
            }
        })
        .catch(err => {
            console.error('Error creating business account:', err);
            showModal('Error', 'Error creating business account', '✕', 'error');
        });
    };

    const handleCreateEvent = (e) => {
        e.preventDefault();

        const title = document.getElementById('event-title').value.trim();
        const startDate = document.getElementById('event-start-date').value;
        const endDate = document.getElementById('event-end-date').value;
        const description = document.getElementById('event-description').value.trim();

        if (!title || !startDate || !endDate || !description) {
            showModal('Validation Error', 'All fields are required', '⚠', 'warning');
            return;
        }

        if (new Date(startDate) >= new Date(endDate)) {
            showModal('Validation Error', 'End date must be after start date', '⚠', 'warning');
            return;
        }

        fetch('/api/events', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'X-CSRF-Token': CSRF_TOKEN,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                title: title,
                description: description,
                start_date: startDate,
                end_date: endDate
            })
        })
        .then(r => r.json())
        .then(response => {
            if (response.success) {
                showModal('Success', 'Event created successfully!', '✓', 'success');
                document.getElementById('create-event-form').reset();
                setTimeout(() => {
                    loadEvents();
                    loadStatistics();
                }, 1200);
            } else {
                showModal('Error', response.message || 'Failed to create event', '✕', 'error');
            }
        })
        .catch(err => {
            console.error('Error creating event:', err);
            showModal('Error', 'Error creating event', '✕', 'error');
        });
    };

    const loadEvents = () => {
        fetch('/api/events', { credentials: 'include' })
            .then(r => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then(response => {
                console.log('Full events response:', response);
                
                // The response structure is: { success, message, data: { data: [...], ... } }
                const events = response.data && response.data.data ? response.data.data : [];
                console.log('Extracted events array:', events);
                console.log('Events count:', events.length);
                
                allEvents = events;
                
                // Force a small delay to ensure DOM is ready
                setTimeout(() => {
                    renderEvents(allEvents);
                }, 50);
            })
            .catch(err => {
                console.error('Error loading events:', err);
                const eventsList = document.getElementById('events-list');
                if (eventsList) {
                    eventsList.innerHTML = '<p style="color:#e74c3c;">Error loading events</p>';
                }
            });
    };

    const renderEvents = (events) => {
        const eventsList = document.getElementById('events-list');

        console.log('renderEvents function called');
        console.log('eventsList element found:', !!eventsList);
        console.log('events array:', events);

        if (!eventsList) {
            console.error('CRITICAL: events-list element not found in DOM!');
            return;
        }

        if (!events || events.length === 0) {
            console.log('No events to display');
            eventsList.innerHTML = '<p style="color:#999; text-align:center; padding:20px;">No events created yet</p>';
            return;
        }

        console.log('Building HTML for', events.length, 'events');
        
        let html = '';
        for (let i = 0; i < events.length; i++) {
            const event = events[i];
            const startDate = new Date(event.start_date).toLocaleDateString();
            const endDate = new Date(event.end_date).toLocaleDateString();
            
            html += `<div style="border:1px solid #ddd; border-radius:8px; padding:15px; background:white;">
                    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                        <div style="flex:1;">
                            <h4 style="margin:0 0 5px 0; color:#333;">${escapeHtml(event.title)}</h4>
                            <p style="margin:0; color:#666; font-size:13px;">
                                <i class="fas fa-calendar"></i> ${startDate} to ${endDate}
                            </p>
                        </div>
                        <button onclick="AdminModule.deleteEvent(${event.id})" style="padding:6px 12px; background:#e74c3c; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:12px; white-space:nowrap; margin-left:10px;">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                    <p style="margin:10px 0 0 0; color:#555; font-size:13px; line-height:1.5;">
                        ${escapeHtml(event.description)}
                    </p>
                </div>`;
        }

        console.log('HTML to insert:', html.substring(0, 100) + '...');
        eventsList.innerHTML = html;
        console.log('HTML inserted into events-list');
        console.log('events-list innerHTML length:', eventsList.innerHTML.length);
        console.log('eventsList children count:', eventsList.children.length);
        
        // Scroll events into view
        setTimeout(() => {
            eventsList.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            console.log('Scrolled events into view');
        }, 100);
    };

    const deleteEvent = (eventId) => {
        if (!confirm('Are you sure you want to delete this event?')) {
            return;
        }

        fetch(`/api/admin/events/${eventId}`, {
            method: 'DELETE',
            credentials: 'include',
            headers: {
                'X-CSRF-Token': CSRF_TOKEN,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(response => {
            if (response.success) {
                showModal('Success', 'Event deleted successfully', '✓', 'success');
                setTimeout(() => {
                    loadEvents();
                    loadStatistics();
                }, 1500);
            } else {
                showModal('Error', response.message || 'Failed to delete event', '✕', 'error');
            }
        })
        .catch(err => {
            console.error('Error deleting event:', err);
            showModal('Error', 'Error deleting event', '✕', 'error');
        });
    };

    const escapeHtml = (text) => {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    return {
        init,
        deleteUser,
        deleteEvent,
        closeModal
    };
})();

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', AdminModule.init);
} else {
    AdminModule.init();
}
