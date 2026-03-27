const AdminModule = (() => {
    let allUsers = [];
    let allEvents = [];
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const init = () => {
        loadStatistics();
        loadUsers();
        loadEvents();
        setupEventListeners();
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
                document.getElementById('stat-total-businesses').textContent = stats.total_businesses || 0;

                const usersByRole = stats.users_by_role || [];
                const employers = usersByRole.find(r => r.role === 'employer');
                document.getElementById('stat-total-employers').textContent = employers?.count || 0;
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
                allUsers = response.data || [];
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
                alert('User deleted successfully');
                loadUsers();
                loadStatistics();
            } else {
                alert('Error: ' + (response.message || 'Failed to delete user'));
            }
        })
        .catch(err => {
            console.error('Error deleting user:', err);
            alert('Error deleting user');
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
            alert('All fields are required');
            return;
        }

        if (password !== confirmPassword) {
            alert('Passwords do not match');
            return;
        }

        if (password.length < 6) {
            alert('Password must be at least 6 characters');
            return;
        }

        // Create business account via admin endpoint
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
                alert('Business account created successfully!');
                document.getElementById('create-business-form').reset();
                loadUsers();
                loadStatistics();
            } else {
                alert('Error: ' + (response.message || 'Failed to create business account'));
            }
        })
        .catch(err => {
            console.error('Error creating business account:', err);
            alert('Error creating business account');
        });
    };

    const handleCreateEvent = (e) => {
        e.preventDefault();

        const title = document.getElementById('event-title').value.trim();
        const startDate = document.getElementById('event-start-date').value;
        const endDate = document.getElementById('event-end-date').value;
        const description = document.getElementById('event-description').value.trim();

        if (!title || !startDate || !endDate || !description) {
            alert('All fields are required');
            return;
        }

        if (new Date(startDate) >= new Date(endDate)) {
            alert('End date must be after start date');
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
                alert('Event created successfully!');
                document.getElementById('create-event-form').reset();
                loadEvents();
                loadStatistics();
            } else {
                alert('Error: ' + (response.message || 'Failed to create event'));
            }
        })
        .catch(err => {
            console.error('Error creating event:', err);
            alert('Error creating event');
        });
    };

    const loadEvents = () => {
        fetch('/api/admin/events', { credentials: 'include' })
            .then(r => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then(response => {
                allEvents = response.data || [];
                renderEvents(allEvents);
            })
            .catch(err => {
                console.error('Error loading events:', err);
                document.getElementById('events-list').innerHTML = '<p style="color:#e74c3c;">Error loading events</p>';
            });
    };

    const renderEvents = (events) => {
        const eventsList = document.getElementById('events-list');

        if (!events || events.length === 0) {
            eventsList.innerHTML = '<p style="color:#999; text-align:center; padding:20px;">No events created yet</p>';
            return;
        }

        eventsList.innerHTML = events.map(event => {
            const startDate = new Date(event.start_date).toLocaleDateString();
            const endDate = new Date(event.end_date).toLocaleDateString();

            return `
                <div style="border:1px solid #ddd; border-radius:8px; padding:15px; background:white;">
                    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                        <div>
                            <h4 style="margin:0 0 5px 0; color:#333;">${escapeHtml(event.title)}</h4>
                            <p style="margin:0; color:#666; font-size:13px;">
                                <i class="fas fa-calendar"></i> ${startDate} to ${endDate}
                            </p>
                        </div>
                        <button onclick="AdminModule.deleteEvent(${event.id})" style="padding:6px 12px; background:#e74c3c; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:12px;">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                    <p style="margin:10px 0 0 0; color:#555; font-size:13px; line-height:1.5;">
                        ${escapeHtml(event.description)}
                    </p>
                </div>
            `;
        }).join('');
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
                alert('Event deleted successfully');
                loadEvents();
                loadStatistics();
            } else {
                alert('Error: ' + (response.message || 'Failed to delete event'));
            }
        })
        .catch(err => {
            console.error('Error deleting event:', err);
            alert('Error deleting event');
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
        deleteEvent
    };
})();

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', AdminModule.init);
} else {
    AdminModule.init();
}
