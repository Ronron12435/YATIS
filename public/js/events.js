/**
 * Events & Challenges Module
 * Handles loading and displaying events, achievements, and leaderboard
 */

const EventsModule = {
    apiBase: '/api',
    dailySteps: 0,
    stepTrackerInterval: null,

    /**
     * Initialize the events module
     */
    init() {
        this.loadUserAchievements();
        this.loadEvents();
        this.loadLeaderboard();
        this.initStepTracking();
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
                
                return `
                    <div style="display: flex; align-items: center; gap: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid #ffd700;">
                        <div style="font-size: 32px;">🏆</div>
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
        
        if (userPoints) userPoints.textContent = data.total_points || 0;
        if (completedTasks) completedTasks.textContent = data.tasks_completed || 0;
    },

    /**
     * Load active events
     */
    loadEvents() {
        fetch(`${this.apiBase}/events`, { credentials: 'include' })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                let events = [];
                if (data.success && Array.isArray(data.data)) {
                    events = data.data;
                } else if (Array.isArray(data.data)) {
                    events = data.data;
                } else if (Array.isArray(data)) {
                    events = data;
                }
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
        
        if (!eventsList) return;

        if (!Array.isArray(events) || events.length === 0) {
            eventsList.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">No active events at the moment.</p>';
            return;
        }

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
    },

    /**
     * Complete a task
     */
    completeTask(taskId, eventId) {
        fetch(`${this.apiBase}/events/tasks/complete`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                task_id: taskId,
                event_id: eventId || 1,
                proof_data: null,
            }),
        })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const pointsEarned = data.data?.points_earned || 0;
                    alert('Task completed! You earned ' + pointsEarned + ' points!');
                    this.loadUserAchievements();
                    this.loadLeaderboard();
                } else {
                    alert(data.message || 'Error completing task');
                }
            })
            .catch(error => {
                console.error('Error completing task:', error);
                alert('Error completing task');
            });
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
            const user = entry.user || {};
            const firstName = user.first_name || 'User';
            const lastName = user.last_name || '';
            const tasksCompleted = entry.tasks_completed || 0;
            const totalPoints = entry.total_points || 0;

            return `
                <div style="display: flex; align-items: center; gap: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid ${index === 0 ? '#ffd700' : index === 1 ? '#c0c0c0' : index === 2 ? '#cd7f32' : '#667eea'};">
                    <div style="font-size: 24px; min-width: 40px; text-align: center;">${rankIcon}</div>
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
            statusEl.textContent = 'Using motion detection';
            this.startMotionTracking();
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
                        lastStepTime = currentTime;
                    }
                }

                lastX = x;
                lastY = y;
                lastZ = z;
            });
        }
    },
};

/**
 * Reset daily steps
 */
function resetDailySteps() {
    if (confirm('Are you sure you want to reset today\'s step count?')) {
        EventsModule.dailySteps = 0;
        document.getElementById('daily-steps-display').textContent = '0';
        document.getElementById('daily-steps-count').textContent = '0';
    }
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
