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
        const createTaskForm = document.getElementById('create-task-form');
        const taskTypeSelect = document.getElementById('task-type');
        const qrCodeInput = document.getElementById('task-qr-code');

        if (userSearch) userSearch.addEventListener('input', filterUsers);
        if (createBusinessForm) createBusinessForm.addEventListener('submit', handleCreateBusiness);
        if (createEventForm) createEventForm.addEventListener('submit', handleCreateEvent);
        if (createTaskForm) createTaskForm.addEventListener('submit', handleCreateTask);
        if (taskTypeSelect) taskTypeSelect.addEventListener('change', handleTaskTypeChange);
        if (qrCodeInput) qrCodeInput.addEventListener('input', (e) => {
            generateQRCode(e.target.value);
        });
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

    const handleTaskTypeChange = () => {
        const taskType = document.getElementById('task-type').value;
        const qrCodeField = document.getElementById('qr-code-field');
        
        if (taskType === 'qr_scan') {
            qrCodeField.style.display = 'block';
            // Generate QR code when field becomes visible
            setTimeout(() => generateQRCode(''), 100);
        } else {
            qrCodeField.style.display = 'none';
        }
    };

    const generateQRCode = (value) => {
        const preview = document.getElementById('qr-preview');
        if (!preview) return;

        // Clear previous content
        preview.innerHTML = '';

        // Only generate if value is not empty
        if (!value || value.trim() === '') {
            preview.innerHTML = '<div style="width:200px; height:200px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; border-radius:6px; color:#999; font-size:12px; text-align:center; padding:10px;">Enter QR code value</div>';
            return;
        }

        // Generate QR code using QRCode library
        try {
            // Create a temporary div for QR code
            const tempDiv = document.createElement('div');
            tempDiv.style.display = 'none';
            document.body.appendChild(tempDiv);
            
            // Generate QR code in temp div
            new QRCode(tempDiv, {
                text: value,
                width: 200,
                height: 200,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });

            // Wait for QR code to be generated
            setTimeout(() => {
                const img = tempDiv.querySelector('img');
                if (img) {
                    // Move the generated image to preview
                    preview.innerHTML = '';
                    preview.appendChild(img);
                    img.style.borderRadius = '6px';
                }
                document.body.removeChild(tempDiv);
            }, 100);
        } catch (err) {
            console.error('Error generating QR code:', err);
            preview.innerHTML = '<div style="width:200px; height:200px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; border-radius:6px; color:#e74c3c; font-size:12px; text-align:center;">Error generating QR</div>';
        }
    };

    const downloadQRCode = () => {
        const qrValue = document.getElementById('task-qr-code').value.trim();

        if (!qrValue) {
            showModal('Error', 'Please enter a QR code value first', '✕', 'error');
            return;
        }

        try {
            // Get the QR code image from preview
            const preview = document.getElementById('qr-preview');
            const img = preview.querySelector('img');
            
            if (!img) {
                showModal('Error', 'QR code not generated yet', '✕', 'error');
                return;
            }

            // Create canvas and draw the image
            const canvas = document.createElement('canvas');
            canvas.width = 200;
            canvas.height = 200;
            const ctx = canvas.getContext('2d');
            
            // Draw image on canvas
            ctx.drawImage(img, 0, 0);
            
            // Convert to blob and download
            canvas.toBlob(blob => {
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `qr-code-${qrValue.replace(/[^a-z0-9]/gi, '-').toLowerCase()}.png`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
                
                showModal('Success', 'QR code downloaded successfully!', '📥', 'success');
            });
        } catch (err) {
            console.error('Error downloading QR code:', err);
            showModal('Error', 'Error downloading QR code', '✕', 'error');
        }
    };

    const handleCreateTask = (e) => {
        e.preventDefault();

        const eventId = document.getElementById('task-event-id').value.trim();
        const title = document.getElementById('task-title').value.trim();
        const description = document.getElementById('task-description').value.trim();
        const taskType = document.getElementById('task-type').value.trim();
        const rewardPoints = document.getElementById('task-reward-points').value.trim();
        const targetValue = document.getElementById('task-target-value').value.trim();
        const badge = document.getElementById('task-badge').value.trim();
        const qrCode = document.getElementById('task-qr-code').value.trim();

        if (!eventId || !title || !taskType || !rewardPoints) {
            showModal('Validation Error', 'Event, Title, Task Type, and Reward Points are required', '⚠', 'warning');
            return;
        }

        if (parseInt(rewardPoints) < 1) {
            showModal('Validation Error', 'Reward Points must be at least 1', '⚠', 'warning');
            return;
        }

        // Validate QR code for QR Scan tasks
        if (taskType === 'qr_scan' && !qrCode) {
            showModal('Validation Error', 'QR Code Value is required for QR Scan tasks', '⚠', 'warning');
            return;
        }

        fetch(`/api/events/${eventId}/tasks`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'X-CSRF-Token': CSRF_TOKEN,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                title: title,
                description: description || null,
                task_type: taskType,
                reward_points: parseInt(rewardPoints),
                target_value: targetValue ? parseInt(targetValue) : null,
                qr_code: qrCode || null,
                badge: badge || null
            })
        })
        .then(r => r.json())
        .then(response => {
            if (response.success) {
                showModal('Success', 'Task created successfully!', '✓', 'success');
                document.getElementById('create-task-form').reset();
                setTimeout(() => {
                    closeTaskForm();
                    loadEvents();
                }, 1200);
            } else {
                showModal('Error', response.message || 'Failed to create task', '✕', 'error');
            }
        })
        .catch(err => {
            console.error('Error creating task:', err);
            showModal('Error', 'Error creating task', '✕', 'error');
        });
    };

    const populateEventDropdown = () => {
        const dropdown = document.getElementById('task-event-id');
        if (!dropdown || !allEvents || allEvents.length === 0) return;

        dropdown.innerHTML = '<option value="">-- Choose an event --</option>';
        allEvents.forEach(event => {
            const option = document.createElement('option');
            option.value = event.id;
            option.textContent = event.title;
            dropdown.appendChild(option);
        });
    };

    const openTaskForm = (eventId) => {
        const taskModal = document.getElementById('task-modal');
        const dropdown = document.getElementById('task-event-id');
        
        if (taskModal) {
            taskModal.style.display = 'flex';
            if (dropdown) {
                dropdown.value = eventId;
            }
        }
    };

    const closeTaskForm = () => {
        const taskModal = document.getElementById('task-modal');
        if (taskModal) {
            taskModal.style.display = 'none';
            document.getElementById('create-task-form').reset();
        }
    };

    const closeTaskModal = () => {
        closeTaskForm();
    };

    const viewEventDetails = (eventId) => {
        const event = allEvents.find(e => e.id === eventId);
        if (!event) return;

        const modal = document.getElementById('event-details-modal');
        const titleEl = document.getElementById('event-details-title');
        const contentEl = document.getElementById('event-details-content');
        const tasksListEl = document.getElementById('event-tasks-list');

        // Set event title
        titleEl.textContent = event.title;

        // Set event details
        const startDate = new Date(event.start_date).toLocaleDateString();
        const endDate = new Date(event.end_date).toLocaleDateString();
        contentEl.innerHTML = `
            <div style="background:#f9f9f9; padding:15px; border-radius:8px; border-left:4px solid #667eea;">
                <p style="margin:0 0 10px 0; color:#333;"><strong>Dates:</strong> ${startDate} to ${endDate}</p>
                <p style="margin:0; color:#555; font-size:14px; line-height:1.5;">${escapeHtml(event.description)}</p>
            </div>
        `;

        // Load and display tasks
        tasksListEl.innerHTML = '<p style="color:#999; text-align:center; padding:20px;">Loading tasks...</p>';
        
        fetch(`/api/events/${eventId}/tasks`, { credentials: 'include' })
            .then(r => r.json())
            .then(response => {
                let tasks = [];
                if (response.success && response.data && response.data.data) {
                    tasks = response.data.data;
                } else if (response.data && Array.isArray(response.data)) {
                    tasks = response.data;
                }

                if (tasks.length === 0) {
                    tasksListEl.innerHTML = '<p style="color:#999; text-align:center; padding:20px;">No tasks added yet</p>';
                } else {
                    tasksListEl.innerHTML = tasks.map(task => `
                        <div style="background:#f9f9f9; padding:12px; border-radius:6px; margin-bottom:10px; border-left:3px solid #667eea;">
                            <h5 style="margin:0 0 5px 0; color:#333;">${escapeHtml(task.title)}</h5>
                            <p style="margin:0 0 8px 0; color:#666; font-size:13px;">${escapeHtml(task.description || '')}</p>
                            <div style="display:flex; gap:10px; font-size:12px; color:#999;">
                                <span><strong>Type:</strong> ${task.task_type}</span>
                                <span><strong>Points:</strong> ${task.reward_points}</span>
                            </div>
                        </div>
                    `).join('');
                }
            })
            .catch(err => {
                console.error('Error loading tasks:', err);
                tasksListEl.innerHTML = '<p style="color:#e74c3c; text-align:center; padding:20px;">Error loading tasks</p>';
            });

        // Store current event ID for adding tasks
        window.currentEventId = eventId;
        
        if (modal) {
            modal.style.display = 'flex';
        }
    };

    const closeEventModal = () => {
        const modal = document.getElementById('event-details-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    };

    const openTaskFormForEvent = () => {
        closeEventModal();
        const dropdown = document.getElementById('task-event-id');
        if (dropdown && window.currentEventId) {
            dropdown.value = window.currentEventId;
        }
        const taskModal = document.getElementById('task-modal');
        if (taskModal) {
            taskModal.style.display = 'flex';
        }
    };

    const loadEvents = () => {
        console.log('loadEvents called - fetching from /api/events');
        fetch('/api/events', { credentials: 'include' })
            .then(r => {
                console.log('loadEvents - fetch response status:', r.status);
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then(response => {
                console.log('loadEvents - Full API response:', response);
                
                // The response structure is: { success, message, data: { data: [...], ... } }
                let events = [];
                if (response.data && response.data.data && Array.isArray(response.data.data)) {
                    events = response.data.data;
                    console.log('loadEvents - Extracted from response.data.data');
                } else if (response.data && Array.isArray(response.data)) {
                    events = response.data;
                    console.log('loadEvents - Extracted from response.data');
                } else if (Array.isArray(response)) {
                    events = response;
                    console.log('loadEvents - Extracted from response directly');
                }
                
                console.log('loadEvents - Extracted events array:', events);
                console.log('loadEvents - Events count:', events.length);
                
                allEvents = events;
                
                // Force a small delay to ensure DOM is ready and then render
                setTimeout(() => {
                    console.log('loadEvents - Calling renderEvents with', events.length, 'events');
                    renderEvents(allEvents);
                }, 100);
            })
            .catch(err => {
                console.error('loadEvents - Error loading events:', err);
                const eventsList = document.getElementById('admin-events-list');
                if (eventsList) {
                    eventsList.innerHTML = '<p style="color:#e74c3c;">Error loading events</p>';
                }
            });
    };

    const renderEvents = (events) => {
        const eventsList = document.getElementById('admin-events-list');

        console.log('renderEvents function called');

        if (!eventsList) {
            console.error('CRITICAL: admin-events-list element not found in DOM!');
            return;
        }

        // Debug: Check current styles
        const computedStyle = window.getComputedStyle(eventsList);
        console.log('Current eventsList styles:', {
            display: computedStyle.display,
            visibility: computedStyle.visibility,
            opacity: computedStyle.opacity,
            height: computedStyle.height,
            width: computedStyle.width,
            overflow: computedStyle.overflow
        });

        if (!events || events.length === 0) {
            console.log('No events to display');
            eventsList.innerHTML = '<div style="color:#999; text-align:center; padding:20px;">No events created yet</div>';
            return;
        }

        console.log('Building HTML for', events.length, 'events');
        
        // Build HTML string with test div at the beginning
        let html = '<div style="background:#e8f5e9; padding:10px; margin-bottom:15px; border-radius:6px; border-left:4px solid #27ae60; color:#2e7d32; font-weight:bold;">✓ Events loaded successfully (' + events.length + ' events)</div>';
        
        for (let i = 0; i < events.length; i++) {
            const event = events[i];
            const startDate = new Date(event.start_date).toLocaleDateString();
            const endDate = new Date(event.end_date).toLocaleDateString();
            
            html += `<div onclick="AdminModule.viewEventDetails(${event.id})" style="border:2px solid #27ae60; border-radius:8px; padding:15px; background:#f0f8f0; margin-bottom:12px; display:block !important; visibility:visible !important; opacity:1 !important; cursor:pointer; transition:all 0.2s;">
                <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                    <div style="flex:1;">
                        <h4 style="margin:0 0 5px 0; color:#333;">${escapeHtml(event.title)}</h4>
                        <p style="margin:0; color:#666; font-size:13px;">
                            <i class="fas fa-calendar"></i> ${startDate} to ${endDate}
                        </p>
                    </div>
                    <div style="display:flex; gap:8px; margin-left:10px;">
                        <button onclick="event.stopPropagation(); AdminModule.openTaskForm(${event.id})" style="padding:6px 12px; background:#667eea; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:12px; white-space:nowrap;">
                            <i class="fas fa-plus"></i> Add Task
                        </button>
                        <button onclick="event.stopPropagation(); AdminModule.deleteEvent(${event.id})" style="padding:6px 12px; background:#e74c3c; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:12px; white-space:nowrap;">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
                <p style="margin:10px 0 0 0; color:#555; font-size:13px; line-height:1.5;">
                    ${escapeHtml(event.description)}
                </p>
            </div>`;
            console.log('Added event:', event.title);
        }

        console.log('HTML length:', html.length);
        console.log('Setting innerHTML...');
        eventsList.innerHTML = html;
        console.log('innerHTML set');
        console.log('eventsList children count:', eventsList.children.length);
        console.log('eventsList innerHTML length:', eventsList.innerHTML.length);
        
        // Force browser repaint and check final styles
        void eventsList.offsetHeight;
        const finalStyle = window.getComputedStyle(eventsList);
        console.log('Final eventsList styles after render:', {
            display: finalStyle.display,
            visibility: finalStyle.visibility,
            opacity: finalStyle.opacity,
            height: finalStyle.height,
            width: finalStyle.width,
            overflow: finalStyle.overflow
        });
        
        // Scroll into view
        setTimeout(() => {
            eventsList.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            console.log('Scrolled events into view');
            // Populate the task event dropdown after events are rendered
            populateEventDropdown();
        }, 100);
        
        console.log('Events rendered and repainted');
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
        closeModal,
        openTaskForm,
        closeTaskForm,
        closeTaskModal,
        viewEventDetails,
        closeEventModal,
        openTaskFormForEvent,
        generateQRCode,
        downloadQRCode
    };
})();

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', AdminModule.init);
} else {
    AdminModule.init();
}
