/**
 * Events & Challenges Module
 * Handles loading and displaying events, achievements, and leaderboard
 */

const EventsModule = {
    apiBase: '/api',
    dailySteps: 0,
    stepTrackerInterval: null,
    autoClaimInterval: null,
    attemptedTasks: new Set(), // Track attempted tasks to avoid spam

    /**
     * Initialize the events module
     */
    init() {
        this.loadUserAchievements();
        this.loadEvents();
        this.loadLeaderboard();
        this.initStepTracking();
        // Auto-claim disabled - using manual complete button with validation instead
    },

    /**
     * Start auto-claiming steps tasks every 30 seconds
     */
    startAutoClaimStepTasks() {
        // Wait 5 seconds before first check to ensure page is fully loaded
        setTimeout(() => {
            this.autoClaimStepTasks();
        }, 5000);
        
        // Then check every 30 seconds
        this.autoClaimInterval = setInterval(() => {
            this.autoClaimStepTasks();
        }, 30000);
    },

    /**
     * Auto-claim steps tasks when user reaches target
     */
    autoClaimStepTasks() {
        fetch(`${this.apiBase}/events`, { credentials: 'include' })
            .then(response => response.json())
            .then(data => {
                let events = [];
                if (data.success && data.data && data.data.data && Array.isArray(data.data.data)) {
                    events = data.data.data;
                } else if (data.data && Array.isArray(data.data)) {
                    events = data.data;
                }

                // For each event, check its tasks
                events.forEach(event => {
                    fetch(`${this.apiBase}/events/${event.id}/tasks`, { credentials: 'include' })
                        .then(r => r.json())
                        .then(taskData => {
                            let tasks = Array.isArray(taskData) ? taskData : (Array.isArray(taskData.data) ? taskData.data : []);
                            
                            // Filter for steps tasks that haven't been attempted yet
                            tasks.filter(t => t.task_type === 'steps' && !this.attemptedTasks.has(t.id)).forEach(task => {
                                this.tryAutoClaimTask(task.id, event.id);
                            });
                        })
                        .catch(err => console.error('Error fetching tasks for auto-claim:', err));
                });
            })
            .catch(err => console.error('Error fetching events for auto-claim:', err));
    },

    /**
     * Try to auto-claim a steps task
     */
    tryAutoClaimTask(taskId, eventId) {
        // Mark as attempted to avoid spam
        this.attemptedTasks.add(taskId);

        fetch(`${this.apiBase}/events/tasks/complete`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                task_id: taskId,
                event_id: eventId,
                proof_data: null,
            }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const pointsEarned = data.data?.points_earned || 0;
                    console.log(`✓ Steps task auto-completed! Earned ${pointsEarned} points`);
                    this.loadUserAchievements();
                    this.loadLeaderboard();
                } else if (data.message && data.message.includes('already completed')) {
                    // Task already completed, silently ignore
                    console.log(`Task ${taskId} already completed`);
                } else if (data.message && data.message.includes('need to walk')) {
                    // User hasn't reached target yet, remove from attempted so it tries again later
                    this.attemptedTasks.delete(taskId);
                    console.log(`Task ${taskId} pending: ${data.message}`);
                } else {
                    // Other errors - remove from attempted to retry
                    this.attemptedTasks.delete(taskId);
                    console.warn(`Task ${taskId} error: ${data.message}`);
                }
            })
            .catch(err => {
                // On error, remove from attempted to retry
                this.attemptedTasks.delete(taskId);
                console.error(`Error auto-claiming task ${taskId}:`, err.message);
            });
    },

    /**
     * Load user achievements
     */
    loadUserAchievements() {
        fetch(`${this.apiBase}/achievements`, { credentials: 'include' })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                let achievementData = {};
                if (data.success && data.data) {
                    achievementData = data.data;
                } else if (data.data) {
                    achievementData = data.data;
                } else {
                    achievementData = data;
                }
                this.renderAchievements(achievementData);
                this.updateStats(achievementData);
            })
            .catch(error => {
                console.error('Error loading achievements:', error);
                const el = document.getElementById('user-achievements-list');
                if (el) el.innerHTML = '<p style="color: #e74c3c; text-align: center; padding: 20px;">Error loading achievements.</p>';
            });
    },

    /**
     * Render achievements
     */
    renderAchievements(data) {
        const achievementsList = document.getElementById('user-achievements-list');
        
        if (!achievementsList) return;

        const achievements = Array.isArray(data.achievements) ? data.achievements : [];

        if (achievements.length > 0) {
            achievementsList.innerHTML = achievements.map(achievement => {
                const title = achievement.title || 'Achievement';
                const description = achievement.description || '';
                const points = achievement.points_earned || achievement.points || 0;
                const badge = achievement.badge || '🏆';
                
                return `
                    <div style="display: flex; align-items: center; gap: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid #ffd700;">
                        <div style="font-size: 32px;">${badge}</div>
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 5px 0; color: #1a3a52;">${title}</h4>
                            <p style="margin: 0; color: #666; font-size: 13px;">${description}</p>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 18px; font-weight: bold; color: #ffd700;">+${points}</div>
                            <div style="font-size: 12px; color: #999;">points</div>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            achievementsList.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">No achievements yet. Complete some tasks to earn rewards!</p>';
        }
    },

    /**
     * Update stats display
     */
    updateStats(data) {
        const userPoints = document.getElementById('user-points');
        const completedTasks = document.getElementById('completed-tasks');
        const userRank = document.getElementById('user-rank');
        const eventsSection = document.getElementById('events');
        const currentUserId = eventsSection ? eventsSection.getAttribute('data-user-id') : null;
        
        if (userPoints) userPoints.textContent = data.total_points || 0;
        if (completedTasks) completedTasks.textContent = data.tasks_completed || 0;
        
        // Calculate user rank from leaderboard
        if (userRank && currentUserId) {
            fetch(`${this.apiBase}/leaderboard`, { credentials: 'include' })
                .then(r => r.json())
                .then(leaderboardData => {
                    let leaderboard = [];
                    if (leaderboardData.success && Array.isArray(leaderboardData.data)) {
                        leaderboard = leaderboardData.data;
                    } else if (Array.isArray(leaderboardData.data)) {
                        leaderboard = leaderboardData.data;
                    } else if (Array.isArray(leaderboardData)) {
                        leaderboard = leaderboardData;
                    }
                    
                    // Find current user's rank by ID
                    let userRankPosition = 0;
                    for (let i = 0; i < leaderboard.length; i++) {
                        if (leaderboard[i].id == currentUserId) {
                            userRankPosition = i + 1;
                            break;
                        }
                    }
                    
                    userRank.textContent = userRankPosition > 0 ? `#${userRankPosition}` : '#0';
                })
                .catch(err => {
                    console.error('Error fetching leaderboard for rank:', err);
                    userRank.textContent = '#0';
                });
        }
    },

    /**
     * Load active events
     */
    loadEvents() {
        console.log('loadEvents called');
        fetch(`${this.apiBase}/events`, { credentials: 'include' })
            .then(response => {
                console.log('loadEvents - fetch response status:', response.status);
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                console.log('loadEvents - API response:', data);
                let events = [];
                // Handle paginated response: { success, message, data: { data: [...], ...pagination... } }
                if (data.success && data.data && data.data.data && Array.isArray(data.data.data)) {
                    events = data.data.data;
                } else if (data.data && data.data.data && Array.isArray(data.data.data)) {
                    events = data.data.data;
                } else if (data.data && Array.isArray(data.data)) {
                    events = data.data;
                } else if (Array.isArray(data)) {
                    events = data;
                }
                console.log('loadEvents - extracted events:', events);
                this.renderEvents(events);
            })
            .catch(error => {
                console.error('Error loading events:', error);
                const el = document.getElementById('events-list');
                if (el) el.innerHTML = '<p style="color: #e74c3c; text-align: center; padding: 20px;">Error loading events.</p>';
            });
    },

    /**
     * Render events list
     */
    renderEvents(events) {
        const eventsList = document.getElementById('events-list');
        
        if (!eventsList) {
            console.error('events-list element not found');
            return;
        }

        console.log('renderEvents - events array:', events);
        console.log('renderEvents - events count:', events.length);

        if (!Array.isArray(events) || events.length === 0) {
            console.log('renderEvents - no events to display');
            eventsList.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">No active events at the moment.</p>';
            return;
        }

        try {
            eventsList.innerHTML = events.map(event => {
                const title = event.title || 'Event';
                const description = event.description || '';
                const isActive = event.is_active ? 'Active' : 'Inactive';
                const startDate = new Date(event.start_date).toLocaleDateString();
                const tasksCount = event.tasks_count || 0;
                
                return `
                    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 15px; border-left: 4px solid #667eea;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                            <div>
                                <h4 style="margin: 0 0 5px 0; color: #667eea; font-size: 18px;">${title}</h4>
                                <p style="margin: 0; color: #666; font-size: 14px;">${description}</p>
                            </div>
                            <span style="background: #667eea; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                                ${isActive}
                            </span>
                        </div>
                        <div style="display: flex; gap: 15px; flex-wrap: wrap; margin: 10px 0; font-size: 14px; color: #666;">
                            <span><i class="fas fa-calendar"></i> ${startDate}</span>
                            <span><i class="fas fa-tasks"></i> ${tasksCount} tasks</span>
                        </div>
                        <button class="btn btn-primary" onclick="EventsModule.viewEventTasks(${event.id})" style="margin-top: 10px;">View Tasks</button>
                    </div>
                `;
            }).join('');
            console.log('renderEvents - HTML rendered successfully');
        } catch (error) {
            console.error('renderEvents - Error rendering HTML:', error);
            eventsList.innerHTML = '<p style="color: #e74c3c; text-align: center; padding: 20px;">Error rendering events.</p>';
        }
    },

    /**
     * View event tasks
     */
    viewEventTasks(eventId) {
        fetch(`${this.apiBase}/events/${eventId}/tasks`, { credentials: 'include' })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                let tasks = [];
                if (data.success && Array.isArray(data.data)) {
                    tasks = data.data;
                } else if (Array.isArray(data.data)) {
                    tasks = data.data;
                } else if (Array.isArray(data)) {
                    tasks = data;
                }
                this.showTasksModal(tasks, eventId);
            })
            .catch(error => console.error('Error loading tasks:', error));
    },

    /**
     * Show tasks modal
     */
    showTasksModal(tasks, eventId) {
        const modal = document.createElement('div');
        modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 2000;';
        
        const content = document.createElement('div');
        content.style.cssText = 'background: white; padding: 30px; border-radius: 12px; max-width: 600px; max-height: 80vh; overflow-y: auto; box-shadow: 0 8px 24px rgba(0,0,0,0.2);';
        
        // Fetch user achievements to check which tasks are completed
        fetch(`${this.apiBase}/achievements`, { credentials: 'include' })
            .then(r => r.json())
            .then(achievementData => {
                const completedTaskIds = new Set();
                if (achievementData.data && Array.isArray(achievementData.data.achievements)) {
                    achievementData.data.achievements.forEach(achievement => {
                        completedTaskIds.add(achievement.task_id);
                    });
                }
                
                const tasksHtml = Array.isArray(tasks) && tasks.length > 0 ? tasks.map(task => {
                    const taskId = task.id || 0;
                    const title = task.title || 'Task';
                    const description = task.description || '';
                    const rewardPoints = task.reward_points || 0;
                    const taskType = task.task_type || 'custom';
                    const targetValue = task.target_value || 0;
                    const isCompleted = completedTaskIds.has(taskId);
                    
                    // For steps tasks, show button state based on current steps
                    let actionHtml = '';
                    if (taskType === 'steps') {
                        // Get current steps from the daily steps display
                        const currentSteps = parseInt(document.getElementById('daily-steps-count')?.textContent || '0');
                        const isEligible = currentSteps >= targetValue && !isCompleted;
                        const buttonStyle = isCompleted
                            ? 'padding: 6px 16px; font-size: 13px; background: #95a5a6; color: white; border: none; border-radius: 6px; cursor: not-allowed; font-weight: 600;'
                            : isEligible 
                                ? 'padding: 6px 16px; font-size: 13px; background: #27ae60; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;'
                                : 'padding: 6px 16px; font-size: 13px; background: #bdc3c7; color: #7f8c8d; border: none; border-radius: 6px; cursor: not-allowed; font-weight: 600;';
                        const buttonText = isCompleted ? '✓ Completed' : (isEligible ? 'Complete' : `Need ${targetValue - currentSteps} more steps`);
                        
                        actionHtml = `
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="background: #667eea; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                                    +${rewardPoints} points
                                </span>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 12px; color: #666;">
                                        ${currentSteps} / ${targetValue} steps
                                    </span>
                                    <button class="btn btn-primary" onclick="${isEligible ? `EventsModule.completeTask(${taskId}, ${eventId})` : 'return false'}" style="${buttonStyle}" ${isEligible ? '' : 'disabled'}>
                                        ${buttonText}
                                    </button>
                                </div>
                            </div>
                        `;
                    } else {
                        const buttonStyle = isCompleted
                            ? 'padding: 6px 16px; font-size: 13px; background: #95a5a6; color: white; border: none; border-radius: 6px; cursor: not-allowed; font-weight: 600;'
                            : 'padding: 6px 16px; font-size: 13px; background: #3498db; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;';
                        const buttonText = isCompleted ? '✓ Completed' : 'Complete';
                        
                        actionHtml = `
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="background: #667eea; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                                    +${rewardPoints} points
                                </span>
                                <button class="btn btn-primary" onclick="${!isCompleted ? `EventsModule.completeTask(${taskId}, ${eventId})` : 'return false'}" style="${buttonStyle}" ${isCompleted ? 'disabled' : ''}>
                                    ${buttonText}
                                </button>
                            </div>
                        `;
                    }
                    
                    return `
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid #667eea;">
                            <h4 style="margin: 0 0 5px 0; color: #1a3a52;">${title}</h4>
                            <p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">${description}</p>
                            ${actionHtml}
                        </div>
                    `;
                }).join('') : '<p style="color: #999; text-align: center; padding: 20px;">No tasks available.</p>';
                
                content.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 style="margin: 0; color: #1a3a52;">Event Tasks</h2>
                        <button onclick="this.closest('div').parentElement.parentElement.remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #999;">&times;</button>
                    </div>
                    ${tasksHtml}
                `;
                
                modal.appendChild(content);
                document.body.appendChild(modal);
            })
            .catch(error => {
                console.error('Error fetching achievements:', error);
                // Fallback: show tasks without completion check
                const tasksHtml = Array.isArray(tasks) && tasks.length > 0 ? tasks.map(task => {
                    const taskId = task.id || 0;
                    const title = task.title || 'Task';
                    const description = task.description || '';
                    const rewardPoints = task.reward_points || 0;
                    
                    return `
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid #667eea;">
                            <h4 style="margin: 0 0 5px 0; color: #1a3a52;">${title}</h4>
                            <p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">${description}</p>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="background: #667eea; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                                    +${rewardPoints} points
                                </span>
                                <button class="btn btn-primary" onclick="EventsModule.completeTask(${taskId}, ${eventId})" style="padding: 6px 16px; font-size: 13px;">Complete</button>
                            </div>
                        </div>
                    `;
                }).join('') : '<p style="color: #999; text-align: center; padding: 20px;">No tasks available.</p>';
                
                content.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 style="margin: 0; color: #1a3a52;">Event Tasks</h2>
                        <button onclick="this.closest('div').parentElement.parentElement.remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #999;">&times;</button>
                    </div>
                    ${tasksHtml}
                `;
                
                modal.appendChild(content);
                document.body.appendChild(modal);
            });
    },

    /**
     * Complete a task
     */
    completeTask(taskId, eventId) {
        // Get the task to check its type
        fetch(`${this.apiBase}/events/${eventId}/tasks`, { credentials: 'include' })
            .then(response => response.json())
            .then(data => {
                let tasks = Array.isArray(data) ? data : (Array.isArray(data.data) ? data.data : []);
                const task = tasks.find(t => t.id === taskId);
                
                if (!task) {
                    alert('Task not found');
                    return;
                }

                // Prepare proof data based on task type
                let proofData = null;
                
                if (task.task_type === 'steps') {
                    // Steps task - no additional proof needed, server will check daily steps
                    proofData = null;
                } else if (task.task_type === 'location') {
                    // Location task - get user's current location
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            position => {
                                proofData = {
                                    latitude: position.coords.latitude,
                                    longitude: position.coords.longitude,
                                    accuracy: position.coords.accuracy
                                };
                                this.submitTaskCompletion(taskId, eventId, proofData);
                            },
                            error => {
                                this.showErrorModal('Location Error', 'Unable to get your location. Please enable location services.');
                            }
                        );
                        return;
                    } else {
                        this.showErrorModal('Geolocation Not Supported', 'Geolocation is not supported by your browser');
                        return;
                    }
                } else if (task.task_type === 'qr_scan') {
                    // QR scan task - show QR scanner modal
                    this.showQRScannerModal(taskId, eventId);
                    return;
                } else if (task.task_type === 'custom') {
                    // Custom task - require proof image (photo/screenshot)
                    this.showProofUploadModal(taskId, eventId);
                    return;
                }

                this.submitTaskCompletion(taskId, eventId, proofData);
            })
            .catch(error => {
                console.error('Error fetching task:', error);
                alert('Error loading task details');
            });
    },

    /**
     * Submit task completion to server
     */
    submitTaskCompletion(taskId, eventId, proofData) {
        const payload = {
            task_id: taskId,
            event_id: eventId || 1,
            proof_data: proofData,
        };
        
        console.log('Submitting task completion:', payload);
        
        fetch(`${this.apiBase}/events/tasks/complete`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify(payload),
        })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        console.error('Server error response:', data);
                        throw new Error(data.message || `HTTP ${response.status}`);
                    }).catch(err => {
                        console.error('Failed to parse error response:', err);
                        throw new Error(`HTTP ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const pointsEarned = data.data?.points_earned || 0;
                    this.showSuccessModal('Task Completed!', 'You earned ' + pointsEarned + ' points!');
                    this.loadUserAchievements();
                    this.loadLeaderboard();
                } else {
                    this.showErrorModal('Invalid QR Code', data.message || 'Error completing task');
                }
            })
            .catch(error => {
                console.error('Error completing task:', error);
                this.showErrorModal('Error', error.message || 'Error completing task');
            });
    },

    /**
     * Show proof upload modal for custom tasks
     */
    showProofUploadModal(taskId, eventId) {
        const modalHTML = `
            <div id="proof-modal" style="display:flex; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; align-items:center; justify-content:center;">
                <div style="background:white; border-radius:12px; padding:30px; max-width:400px; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
                    <h2 style="margin:0 0 15px 0; color:#333; font-size:18px;">Proof of Completion Required</h2>
                    <p style="margin:0 0 20px 0; color:#666; font-size:14px;">Please upload a photo or screenshot as proof that you completed this task.</p>
                    
                    <div style="margin-bottom:20px;">
                        <input type="file" id="proof-image-input" accept="image/*" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                    </div>
                    
                    <div style="display:flex; gap:10px;">
                        <button onclick="EventsModule.closeProofModal()" style="flex:1; padding:10px; background:#95a5a6; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Cancel</button>
                        <button onclick="EventsModule.submitProofAndCompleteTask(${taskId}, ${eventId})" style="flex:1; padding:10px; background:#27ae60; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Submit Proof</button>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existing = document.getElementById('proof-modal');
        if (existing) existing.remove();
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    },

    /**
     * Close proof upload modal
     */
    closeProofModal() {
        const modal = document.getElementById('proof-modal');
        if (modal) modal.remove();
    },

    /**
     * Show QR code scanner modal
     */
    showQRScannerModal(taskId, eventId) {
        const modalHTML = `
            <div id="qr-scanner-modal" style="display:flex; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:10000; align-items:center; justify-content:center;" data-task-id="${taskId}" data-event-id="${eventId}">
                <div style="background:white; border-radius:12px; padding:30px; max-width:500px; width:90%; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
                    <h2 style="margin:0 0 10px 0; color:#333; font-size:20px;"><i class="fas fa-qrcode"></i> Scan QR Code</h2>
                    <p style="margin:0 0 20px 0; color:#666; font-size:14px;">Scan the QR code or manually enter the code value below.</p>
                    
                    <div style="margin-bottom:20px;">
                        <label style="display:block; margin-bottom:8px; font-weight:600; color:#333; font-size:14px;">QR Code Value:</label>
                        <input type="text" id="qr-code-input" placeholder="e.g., EVENT-2026-SUMMER-001" style="width:100%; padding:12px; border:2px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box;">
                        <p style="margin:8px 0 0 0; color:#999; font-size:12px;">Enter the code you see on the QR code or scan it with your device camera.</p>
                    </div>

                    <div style="background:#f0f0f0; padding:15px; border-radius:6px; margin-bottom:20px; text-align:center;">
                        <p style="margin:0 0 10px 0; color:#666; font-size:13px;"><i class="fas fa-info-circle"></i> <strong>How to use:</strong></p>
                        <p style="margin:0; color:#666; font-size:12px; line-height:1.6;">
                            1. Use your phone camera to scan the QR code<br>
                            2. Or manually type the code value<br>
                            3. Click "Submit" to complete the task
                        </p>
                    </div>
                    
                    <div style="display:flex; gap:10px;">
                        <button onclick="EventsModule.closeQRScannerModal()" style="flex:1; padding:12px; background:#95a5a6; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:14px;">Cancel</button>
                        <button onclick="EventsModule.submitQRCodeFromModal()" style="flex:1; padding:12px; background:#27ae60; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:14px;"><i class="fas fa-check"></i> Submit</button>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existing = document.getElementById('qr-scanner-modal');
        if (existing) existing.remove();
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Focus on input field
        setTimeout(() => {
            const input = document.getElementById('qr-code-input');
            if (input) input.focus();
        }, 100);
    },

    /**
     * Close QR scanner modal
     */
    closeQRScannerModal() {
        const modal = document.getElementById('qr-scanner-modal');
        if (modal) modal.remove();
    },

    /**
     * Submit QR code from modal (retrieves IDs from data attributes)
     */
    submitQRCodeFromModal() {
        const modal = document.getElementById('qr-scanner-modal');
        const taskId = parseInt(modal.getAttribute('data-task-id'));
        const eventId = parseInt(modal.getAttribute('data-event-id'));
        this.submitQRCode(taskId, eventId);
    },

    /**
     * Submit QR code and complete task
     */
    submitQRCode(taskId, eventId) {
        const qrCodeInput = document.getElementById('qr-code-input');
        const qrCode = qrCodeInput ? qrCodeInput.value.trim() : '';
        
        if (!qrCode) {
            this.showErrorModal('Empty QR Code', 'Please enter or scan the QR code');
            return;
        }

        const proofData = { qr_code: qrCode };
        this.closeQRScannerModal();
        this.submitTaskCompletion(taskId, eventId, proofData);
    },

    /**
     * Submit proof and complete task
     */
    submitProofAndCompleteTask(taskId, eventId) {
        const fileInput = document.getElementById('proof-image-input');
        
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            this.showErrorModal('No File Selected', 'Please select an image file');
            return;
        }

        const file = fileInput.files[0];
        const reader = new FileReader();

        reader.onload = (e) => {
            const proofData = {
                proof_image: e.target.result // Base64 encoded image
            };
            
            this.closeProofModal();
            this.submitTaskCompletion(taskId, eventId, proofData);
        };

        reader.onerror = () => {
            this.showErrorModal('File Error', 'Error reading file');
        };

        reader.readAsDataURL(file);
    },

    /**
     * Load leaderboard
     */
    loadLeaderboard() {
        fetch(`${this.apiBase}/leaderboard`, { credentials: 'include' })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                let leaderboard = [];
                if (data.success && Array.isArray(data.data)) {
                    leaderboard = data.data;
                } else if (Array.isArray(data.data)) {
                    leaderboard = data.data;
                } else if (Array.isArray(data)) {
                    leaderboard = data;
                }
                this.renderLeaderboard(leaderboard);
            })
            .catch(error => {
                console.error('Error loading leaderboard:', error);
                const el = document.getElementById('leaderboard-list');
                if (el) el.innerHTML = '<p style="color: #e74c3c; text-align: center; padding: 20px;">Error loading leaderboard.</p>';
            });
    },

    /**
     * Render leaderboard
     */
    renderLeaderboard(leaderboard) {
        const leaderboardList = document.getElementById('leaderboard-list');
        
        if (!leaderboardList) return;

        if (!Array.isArray(leaderboard) || leaderboard.length === 0) {
            leaderboardList.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">No rankings yet. Be the first to complete tasks!</p>';
            return;
        }

        leaderboardList.innerHTML = leaderboard.map((entry, index) => {
            const rankIcon = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : `#${index + 1}`;
            
            // Handle both nested user object and flat structure
            const firstName = entry.first_name || (entry.user && entry.user.first_name) || 'User';
            const lastName = entry.last_name || (entry.user && entry.user.last_name) || '';
            const profilePicture = entry.profile_picture || (entry.user && entry.user.profile_picture) || null;
            const tasksCompleted = entry.tasks_completed || 0;
            const totalPoints = entry.total_points || 0;
            
            // Create initials for avatar
            const initials = ((firstName || '')[0] || '').toUpperCase() + ((lastName || '')[0] || '').toUpperCase();
            
            // Determine avatar display
            const avatarUrl = profilePicture ? `/storage/avatars/${profilePicture}` : null;
            const avatarHtml = avatarUrl 
                ? `<img src="${avatarUrl}" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #667eea;">`
                : `<div style="width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; border: 2px solid #667eea;">${initials}</div>`;

            return `
                <div style="display: flex; align-items: center; gap: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid ${index === 0 ? '#ffd700' : index === 1 ? '#c0c0c0' : index === 2 ? '#cd7f32' : '#667eea'};">
                    <div style="font-size: 24px; min-width: 40px; text-align: center;">${rankIcon}</div>
                    ${avatarHtml}
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 5px 0; color: #1a3a52;">${firstName} ${lastName}</h4>
                        <p style="margin: 0; color: #666; font-size: 13px;">${tasksCompleted} tasks completed</p>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 18px; font-weight: bold; color: #667eea;">${totalPoints}</div>
                        <div style="font-size: 12px; color: #999;">points</div>
                    </div>
                </div>
            `;
        }).join('');
    },

    /**
     * Initialize step tracking
     */
    initStepTracking() {
        const statusEl = document.getElementById('step-tracking-status');

        if ('Pedometer' in window) {
            statusEl.textContent = 'Tracking enabled';
            this.startStepTracking();
        } else if ('DeviceMotionEvent' in window) {
            // iOS 13+ requires explicit permission
            if (typeof DeviceMotionEvent.requestPermission === 'function') {
                statusEl.textContent = 'Tap to enable step tracking';
                statusEl.style.cursor = 'pointer';
                statusEl.style.color = '#3498db';
                statusEl.style.textDecoration = 'underline';
                statusEl.onclick = () => {
                    DeviceMotionEvent.requestPermission()
                        .then(permission => {
                            if (permission === 'granted') {
                                statusEl.textContent = 'Using motion detection';
                                statusEl.style.cursor = '';
                                statusEl.style.color = '';
                                statusEl.style.textDecoration = '';
                                statusEl.onclick = null;
                                this.startMotionTracking();
                            } else {
                                statusEl.textContent = 'Permission denied';
                            }
                        })
                        .catch(() => {
                            statusEl.textContent = 'Step tracking unavailable';
                        });
                };
            } else {
                // Android / desktop with motion support
                statusEl.textContent = 'Using motion detection';
                this.startMotionTracking();
            }
        } else {
            statusEl.textContent = 'Step tracking unavailable';
        }
    },

    /**
     * Start step tracking using Pedometer API
     */
    startStepTracking() {
        if (!('Pedometer' in window)) return;

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        Pedometer.query(
            {
                startDate: today,
                endDate: new Date(),
            },
            (result) => {
                this.dailySteps = result.numberOfSteps;
                document.getElementById('daily-steps-display').textContent = this.dailySteps;
                document.getElementById('daily-steps-count').textContent = this.dailySteps;
            },
            (error) => {
                console.error('Pedometer error:', error);
            }
        );
    },

    /**
     * Start step tracking using motion detection
     */
    startMotionTracking() {
        let lastX = 0, lastY = 0, lastZ = 0;
        let stepCount = 0;
        let lastStepTime = 0;

        if (window.DeviceMotionEvent) {
            window.addEventListener('devicemotion', (event) => {
                const x = event.accelerationIncludingGravity.x || 0;
                const y = event.accelerationIncludingGravity.y || 0;
                const z = event.accelerationIncludingGravity.z || 0;

                const acceleration = Math.sqrt(
                    Math.pow(x - lastX, 2) +
                    Math.pow(y - lastY, 2) +
                    Math.pow(z - lastZ, 2)
                );

                if (acceleration > 25) {
                    const currentTime = Date.now();
                    if (currentTime - lastStepTime > 500) {
                        stepCount++;
                        this.dailySteps = stepCount;
                        document.getElementById('daily-steps-display').textContent = stepCount;
                        document.getElementById('daily-steps-count').textContent = stepCount;
                        
                        // Send to backend
                        fetch('/api/steps/increment', {
                            method: 'POST',
                            credentials: 'include',
                            headers: {
                                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ increment: 1 })
                        }).catch(err => console.error('Failed to sync steps:', err));
                        
                        lastStepTime = currentTime;
                    }
                }

                lastX = x;
                lastY = y;
                lastZ = z;
            });
        }
    },

    /**
     * Show error modal
     */
    showErrorModal(title, message) {
        const modalHTML = `
            <div id="event-error-modal" style="display:flex; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:10001; align-items:center; justify-content:center;">
                <div style="background:white; border-radius:12px; padding:40px; max-width:450px; width:90%; box-shadow:0 10px 40px rgba(0,0,0,0.3); text-align:center;">
                    <div style="font-size:60px; margin-bottom:20px;">✕</div>
                    <h2 style="margin:0 0 15px 0; color:#e74c3c; font-size:22px; font-weight:700;">${title}</h2>
                    <p style="margin:0 0 25px 0; color:#666; font-size:14px; line-height:1.6;">${message}</p>
                    <button onclick="EventsModule.closeErrorModal()" style="padding:12px 30px; background:#e74c3c; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:14px; transition:all 0.2s;">
                        OK
                    </button>
                </div>
            </div>
        `;
        
        const existing = document.getElementById('event-error-modal');
        if (existing) existing.remove();
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    },

    /**
     * Close error modal
     */
    closeErrorModal() {
        const modal = document.getElementById('event-error-modal');
        if (modal) modal.remove();
    },

    /**
     * Show success modal
     */
    showSuccessModal(title, message) {
        const modalHTML = `
            <div id="event-success-modal" style="display:flex; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:10001; align-items:center; justify-content:center;">
                <div style="background:white; border-radius:12px; padding:40px; max-width:450px; width:90%; box-shadow:0 10px 40px rgba(0,0,0,0.3); text-align:center;">
                    <div style="font-size:60px; margin-bottom:20px;">✓</div>
                    <h2 style="margin:0 0 15px 0; color:#27ae60; font-size:22px; font-weight:700;">${title}</h2>
                    <p style="margin:0 0 25px 0; color:#666; font-size:14px; line-height:1.6;">${message}</p>
                    <button onclick="EventsModule.closeSuccessModal()" style="padding:12px 30px; background:#27ae60; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:14px; transition:all 0.2s;">
                        OK
                    </button>
                </div>
            </div>
        `;
        
        const existing = document.getElementById('event-success-modal');
        if (existing) existing.remove();
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Auto-close after 2 seconds
        setTimeout(() => {
            this.closeSuccessModal();
        }, 2000);
    },

    /**
     * Close success modal
     */
    closeSuccessModal() {
        const modal = document.getElementById('event-success-modal');
        if (modal) modal.remove();
    }
};

/**
 * Reset daily steps
 */
function resetDailySteps() {
    showModal('Steps reset automatically at midnight PH time', 'info');
}

/**
 * Initialize when section is loaded
 */
document.addEventListener('DOMContentLoaded', () => {
    // Check if we're on the events section
    const eventsSection = document.getElementById('events');
    if (eventsSection) {
        EventsModule.init();
    }
});
