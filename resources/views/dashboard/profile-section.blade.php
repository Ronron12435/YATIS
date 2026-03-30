<div id="profile" class="content-section">
    <style>
        #profile { background: #f5f5f5; padding: 20px; }
        .profile-wrapper { max-width: 1200px; margin: 0 auto; }
        .modern-profile-header { background: white; border-radius: 12px; overflow: hidden; margin-bottom: 30px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .profile-cover { height: 200px; background: linear-gradient(135deg, #1a3a52 0%, #2c5f8d 50%, #00bcd4 100%); background-size: cover; background-position: center; position: relative; }
        .profile-cover::before { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 50px; background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 50"><path fill="white" d="M0,25 Q150,5 300,25 T600,25 T900,25 T1200,25 L1200,50 L0,50 Z"/></svg>') repeat-x; background-size: 300px 50px; }
        .btn-upload-cover { position: absolute; top: 12px; right: 12px; background: white; color: #333; border: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,.15); transition: all .2s; z-index: 10; }
        .btn-upload-cover:hover { box-shadow: 0 4px 12px rgba(0,0,0,.2); }
        .btn-remove-cover { position: absolute; top: 12px; right: 120px; background: white; color: #e74c3c; border: none; padding: 8px 12px; border-radius: 6px; font-size: 16px; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,.15); transition: all .2s; z-index: 10; }
        .btn-remove-cover:hover { box-shadow: 0 4px 12px rgba(0,0,0,.2); }
        .profile-main { padding: 0 24px 24px; position: relative; display: flex; gap: 24px; align-items: flex-start; }
        .profile-avatar-wrapper { position: relative; }
        .modern-avatar { width: 100px; height: 100px; border-radius: 50%; border: 4px solid white; background: linear-gradient(135deg, #00bcd4 0%, #00acc1 100%); display: flex; align-items: center; justify-content: center; font-size: 40px; font-weight: 700; color: white; margin-top: -50px; box-shadow: 0 4px 16px rgba(0, 188, 212, 0.4); cursor: pointer; overflow: hidden; position: relative; }
        .modern-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-text { font-size: 40px; font-weight: 700; }
        .avatar-menu-overlay { position: absolute; inset: 0; background: rgba(0,0,0,.5); display: flex; align-items: center; justify-content: center; border-radius: 50%; opacity: 0; transition: opacity .2s; }
        .modern-avatar:hover .avatar-menu-overlay { opacity: 1; }
        .avatar-menu-icon { font-size: 28px; }
        .profile-details { flex: 1; }
        .modern-profile-name { font-size: 32px; font-weight: 700; color: #1a3a52; margin: 0 0 8px; }
        .profile-role-text { font-size: 14px; color: #666; margin: 0 0 12px; }
        .modern-badges { display: flex; gap: 10px; flex-wrap: wrap; }
        .modern-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .premium-badge { background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); color: #1a3a52; }
        .role-badge { background: #e3f2fd; color: #1976d2; }
        .profile-grid { display: grid; grid-template-columns: 280px 1fr; gap: 20px; }
        .profile-column-left { display: flex; flex-direction: column; gap: 20px; }
        .profile-column-right { display: flex; flex-direction: column; gap: 20px; }
        .modern-card { background: white; border-radius: 12px; padding: 0; box-shadow: 0 2px 12px rgba(0,0,0,.08); overflow: hidden; }
        .modern-card-header { padding: 20px; border-bottom: 1px solid #f0f0f0; background: #fafafa; }
        .card-title-group { display: flex; align-items: center; gap: 12px; }
        .card-icon-modern { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: #e3f2fd; border-radius: 8px; color: #1976d2; }
        .modern-card-header h3 { margin: 0; font-size: 16px; font-weight: 700; color: #1a3a52; }
        .modern-card-body { padding: 20px; }
        .about-item { margin-bottom: 16px; }
        .about-item:last-child { margin-bottom: 0; }
        .about-label { font-size: 11px; font-weight: 700; color: #999; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        .about-value { font-size: 13px; color: #333; line-height: 1.6; }
        .modern-form { display: flex; flex-direction: column; gap: 0; }
        .modern-form-group { margin-bottom: 20px; }
        .modern-form-group:last-child { margin-bottom: 0; }
        .modern-label { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #1a3a52; margin-bottom: 8px; }
        .modern-label svg { width: 16px; height: 16px; color: #00bcd4; }
        .modern-input, .modern-textarea, .modern-select { width: 100%; padding: 12px 14px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 13px; font-family: inherit; background: white; transition: all .2s; }
        .modern-input:focus, .modern-textarea:focus, .modern-select:focus { outline: none; border-color: #00bcd4; box-shadow: 0 0 0 3px rgba(0, 188, 212, .1); }
        .modern-textarea { resize: vertical; min-height: 100px; }
        .input-hint { font-size: 11px; color: #999; margin-top: 6px; display: block; }
        .modern-btn { padding: 12px 24px; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all .2s; display: inline-flex; align-items: center; gap: 8px; }
        .modern-btn-primary { background: linear-gradient(135deg, #1a3a52 0%, #2c5f8d 100%); color: white; box-shadow: 0 4px 12px rgba(26, 58, 82, 0.2); }
        .modern-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(26, 58, 82, 0.3); }
        .message { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        .message.success { background: #c8e6c9; color: #2e7d32; }
        .message.error { background: #ffcdd2; color: #c62828; }
        @media (max-width: 768px) {
            .profile-grid { grid-template-columns: 1fr; }
            .profile-main { flex-direction: column; align-items: center; text-align: center; }
        }
    </style>

    <div class="profile-wrapper">
        {{-- Modern Profile Header --}}
        <div class="modern-profile-header">
            <div class="profile-cover" id="profileCover" style="background-image: url('{{ auth()->user()->cover_photo ?? '' }}');">
                <button class="btn-upload-cover" onclick="document.getElementById('coverPhotoInput').click();">📷 Change Cover</button>
                <input type="file" id="coverPhotoInput" style="display:none;" accept="image/*" onchange="uploadCover(this)">
                @if(auth()->user()->cover_photo)
                    <button class="btn-remove-cover" onclick="deleteCover();">🗑️</button>
                @endif
            </div>
            <div class="profile-main">
                <div class="profile-avatar-wrapper" style="position: relative; display: inline-block;">
                    <div class="modern-avatar" id="profileAvatar" style="cursor: pointer; position: relative;">
                        @if(auth()->user()->profile_picture)
                            <img src="{{ asset('storage/avatars/' . basename(auth()->user()->profile_picture)) }}" alt="Avatar">
                        @else
                            <span class="avatar-text">{{ strtoupper(substr(auth()->user()->first_name ?? 'U', 0, 1)) }}</span>
                        @endif
                    </div>
                    <input type="file" id="avatarInput" style="display:none;" accept="image/*" onchange="uploadAvatar(this)">
                    @if(auth()->user()->profile_picture)
                        <button onclick="toggleAvatarMenu(event);" style="position: absolute; bottom: 0; right: 0; background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.2); z-index: 100;" title="Profile picture options">⋮</button>
                        <div id="avatarMenu" style="position: fixed; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 101; display: none; min-width: 160px; overflow: hidden;">
                            <button onclick="viewProfilePictureModal(); closeAvatarMenu();" style="width: 100%; padding: 12px 16px; border: none; background: none; text-align: left; cursor: pointer; font-size: 13px; color: #333; transition: all 0.2s; border-bottom: 1px solid #f0f0f0; white-space: nowrap;">👁️ View</button>
                            <button onclick="document.getElementById('avatarInput').click(); closeAvatarMenu();" style="width: 100%; padding: 12px 16px; border: none; background: none; text-align: left; cursor: pointer; font-size: 13px; color: #333; transition: all 0.2s; border-bottom: 1px solid #f0f0f0; white-space: nowrap;">📷 Change</button>
                            <button onclick="removeProfilePicture();" style="width: 100%; padding: 12px 16px; border: none; background: none; text-align: left; cursor: pointer; font-size: 13px; color: #e74c3c; transition: all 0.2s; white-space: nowrap;">🗑️ Delete</button>
                        </div>
                    @endif
                </div>
                <div class="profile-details">
                    <h1 class="modern-profile-name">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</h1>
                    <p class="profile-role-text">{{ ucfirst(auth()->user()->role) }} Account</p>
                    <div class="modern-badges">
                        @if(auth()->user()->is_premium)
                            <span class="modern-badge premium-badge">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                Premium
                            </span>
                        @endif
                        <span class="modern-badge role-badge">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            {{ auth()->user()->role }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Profile Grid --}}
        <div class="profile-grid">
            {{-- Left Column --}}
            <div class="profile-column-left">
                {{-- About Card --}}
                <div class="modern-card">
                    <div class="modern-card-header">
                        <div class="card-title-group">
                            <div class="card-icon-modern">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                            </div>
                            <h3>About</h3>
                        </div>
                    </div>
                    <div class="modern-card-body">
                        <div class="about-item">
                            <span class="about-label">Bio</span>
                            <p class="about-value">{{ auth()->user()->bio ?? 'No bio added yet' }}</p>
                        </div>
                        <div class="about-item">
                            <span class="about-label">Account Type</span>
                            <p class="about-value">{{ auth()->user()->is_premium ? '⭐ Premium Member' : 'Free Member' }}</p>
                        </div>
                        <div class="about-item">
                            <span class="about-label">Privacy</span>
                            <p class="about-value">{{ auth()->user()->is_private ? '🔒 Private Profile' : '🌍 Public Profile' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Recent Visitors Card --}}
                <div class="modern-card">
                    <div class="modern-card-header">
                        <div class="card-title-group">
                            <div class="card-icon-modern">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zm4 18v-6h2.5l-2.54-7.63A2.996 2.996 0 0 0 17.06 6H16c-.8 0-1.54.37-2.01.99L12 9l-1.99-2.01A2.99 2.99 0 0 0 8 6H6.94c-1.4 0-2.59.93-2.9 2.37L1.5 16H4v6h2v-6h2.5l1.5-4.5L12 14l2-2.5L15.5 16H18v6h2z"/></svg>
                            </div>
                            <h3>Recent Visitors</h3>
                        </div>
                    </div>
                    <div class="modern-card-body">
                        <div id="visitorsList">
                            <p style="color: #999; text-align: center; padding: 20px;">Loading visitors...</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column --}}
            <div class="profile-column-right">
                {{-- Change Password Card --}}
                <div class="modern-card">
                    <div class="modern-card-header">
                        <div class="card-title-group">
                            <div class="card-icon-modern">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                            </div>
                            <h3>Change Password</h3>
                        </div>
                    </div>
                    <div class="modern-card-body">
                        <div id="passwordMessage"></div>
                        <form id="changePasswordForm" class="modern-form" onsubmit="changePassword(event)">
                            <div class="modern-form-group">
                                <label class="modern-label">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                                    Current Password
                                </label>
                                <input type="password" class="modern-input" id="currentPassword" required>
                            </div>
                            <div class="modern-form-group">
                                <label class="modern-label">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                                    New Password
                                </label>
                                <input type="password" class="modern-input" id="newPassword" required>
                                <span class="input-hint">Minimum 8 characters</span>
                            </div>
                            <div class="modern-form-group">
                                <label class="modern-label">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                                    Confirm New Password
                                </label>
                                <input type="password" class="modern-input" id="confirmPassword" required>
                            </div>
                            <button type="submit" class="modern-btn modern-btn-primary">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                                Change Password
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Create Post Card --}}
                <div class="modern-card">
                    <div class="modern-card-header">
                        <div class="card-title-group">
                            <div class="card-icon-modern">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 12h-4v4h-2v-4H7v-2h4V9h2v4h4v2z"/></svg>
                            </div>
                            <h3>Create Post</h3>
                        </div>
                    </div>
                    <div class="modern-card-body">
                        <div id="postMessage"></div>
                        <form id="createPostForm" class="modern-form" onsubmit="createPost(event)">
                            <div class="modern-form-group">
                                <label class="modern-label">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12h-4v4h-2v-4H7v-2h4V9h2v4h4v2z"/></svg>
                                    What's on your mind?
                                </label>
                                <textarea class="modern-textarea" id="postContent" maxlength="5000" required></textarea>
                                <span class="input-hint"><span id="postCount">0</span>/5000 characters</span>
                            </div>
                            <div class="modern-form-group">
                                <label class="modern-label">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                                    Add Image (Optional)
                                </label>
                                <input type="file" class="modern-input" id="postImage" accept="image/*">
                                <span class="input-hint">JPG, PNG, GIF up to 5MB</span>
                            </div>
                            <div class="modern-form-group">
                                <label class="modern-label">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
                                    Privacy
                                </label>
                                <select class="modern-select" id="postPrivacy">
                                    <option value="public">🌍 Public</option>
                                    <option value="friends">👥 Friends Only</option>
                                    <option value="private">🔒 Private</option>
                                </select>
                            </div>
                            <button type="submit" class="modern-btn modern-btn-primary">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                                Post
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Profile Settings Card --}}
                <div class="modern-card">
                    <div class="modern-card-header">
                        <div class="card-title-group">
                            <div class="card-icon-modern">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
                            </div>
                            <h3>Profile Settings</h3>
                        </div>
                    </div>
                    <div class="modern-card-body">
                        <div id="settingsMessage"></div>
                        <form id="profileSettingsForm" class="modern-form" onsubmit="updateProfile(event)">
                            <div class="modern-form-group">
                                <label class="modern-label">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                    First Name
                                </label>
                                <input type="text" class="modern-input" id="firstName" value="{{ auth()->user()->first_name }}" required>
                            </div>
                            <div class="modern-form-group">
                                <label class="modern-label">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                    Last Name
                                </label>
                                <input type="text" class="modern-input" id="lastName" value="{{ auth()->user()->last_name }}" required>
                            </div>
                            <div class="modern-form-group">
                                <label class="modern-label">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/></svg>
                                    Bio
                                </label>
                                <textarea class="modern-textarea" id="bio" maxlength="500">{{ auth()->user()->bio }}</textarea>
                                <span class="input-hint">Share a bit about yourself with the community</span>
                            </div>
                            <div class="modern-form-group">
                                <label class="modern-label">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                                    Profile Privacy
                                </label>
                                <select class="modern-select" id="isPrivate">
                                    <option value="0" {{ !auth()->user()->is_private ? 'selected' : '' }}>🌍 Public - Anyone can view</option>
                                    <option value="1" {{ auth()->user()->is_private ? 'selected' : '' }}>🔒 Private - Friends only</option>
                                </select>
                            </div>
                            <button type="submit" class="modern-btn modern-btn-primary">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                                Save Changes
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>