<div id="events" class="content-section" data-user-id="{{ auth()->id() }}">
    <h1 class="page-title">🎯 Events & Challenges</h1>
    
    <div class="stats">
        <div class="stat-card">
            <h3 id="user-points">0</h3>
            <p>Total Points</p>
        </div>
        <div class="stat-card">
            <h3 id="completed-tasks">0</h3>
            <p>Tasks Completed</p>
        </div>
        <div class="stat-card">
            <h3 id="user-rank">#0</h3>
            <p>Your Rank</p>
        </div>
    </div>

    <!-- Step Tracker Card -->
    <div class="card" style="background: linear-gradient(135deg, #e8f5e9 0%, #f5f7fa 100%); border-left: 4px solid #2ecc71;">
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
            <span style="font-size: 48px;">👟</span>
            <div style="flex: 1;">
                <h3 style="margin: 0 0 5px 0; color: #1a3a52;">Daily Step Tracker</h3>
                <p style="margin: 0; color: #666; font-size: 14px;">Automatic step counting for challenges</p>
            </div>
        </div>
        <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 32px; font-weight: bold; color: #2ecc71;" id="daily-steps-display">0</div>
                    <div style="color: #666; font-size: 14px;">steps today</div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 14px; color: #999;" id="step-tracking-status">Initializing...</div>
                </div>
            </div>
        </div>
        <div style="background: #fff3cd; padding: 10px; border-radius: 6px; border-left: 3px solid #ffc107;">
            <p style="margin: 0; color: #856404; font-size: 13px;">
                💡 <strong>Tip:</strong> Keep your phone with you while walking. Steps are automatically tracked and saved for step challenges!
            </p>
        </div>
    </div>

    <!-- User Achievements Card -->
    <div class="card">
        <div class="card-header">
            <div class="card-icon">🏆</div>
            <h3>Your Achievements</h3>
        </div>
        <div id="user-achievements-list">
            <p style="color: #999; text-align: center; padding: 20px;">Loading your achievements...</p>
        </div>
    </div>

    <!-- Active Events -->
    <div class="card">
        <div class="card-header">
            <div class="card-icon">🎪</div>
            <h3>Active Events</h3>
        </div>
        <div id="events-list">
            <p style="color: #999; text-align: center; padding: 20px;">Loading events...</p>
        </div>
    </div>

    <!-- Leaderboard -->
    <div class="card">
        <div class="card-header">
            <div class="card-icon">🥇</div>
            <h3>Leaderboard</h3>
        </div>
        <div id="leaderboard-list">
            <p style="color: #999; text-align: center; padding: 20px;">Loading leaderboard...</p>
        </div>
    </div>
</div>

@push('scripts')
@endpush
