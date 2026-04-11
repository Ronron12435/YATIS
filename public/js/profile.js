/**
 * My Profile - Profile management, posts, visitors, achievements
 */

let currentUserId = null;

// ── Init ──────────────────────────────────────────────────────────────────────

window.initProfileSection = function () {
    loadVisitors();
    loadPosts();
    setupCharCounters();
    storeOriginalFormValues();
    loadCurrentLocation();
};

function storeOriginalFormValues() {
    const firstName = document.getElementById('firstName');
    const lastName = document.getElementById('lastName');
    const bio = document.getElementById('bio');
    const isPrivate = document.getElementById('isPrivate');

    if (firstName) firstName.setAttribute('data-original', firstName.value);
    if (lastName) lastName.setAttribute('data-original', lastName.value);
    if (bio) bio.setAttribute('data-original', bio.value);
    if (isPrivate) isPrivate.setAttribute('data-original', isPrivate.value);
}

function setupCharCounters() {
    const bioEl = document.getElementById('bio');
    const postEl = document.getElementById('postContent');
    const bioCountEl = document.getElementById('bioCount');
    const postCountEl = document.getElementById('postCount');

    if (bioEl && bioCountEl) {
        bioEl.addEventListener('input', function () {
            bioCountEl.textContent = this.value.length;
        });
        bioCountEl.textContent = bioEl.value.length;
    }

    if (postEl && postCountEl) {
        postEl.addEventListener('input', function () {
            postCountEl.textContent = this.value.length;
        });
        postCountEl.textContent = postEl.value.length;
    }
}

// ── Visitors ──────────────────────────────────────────────────────────────────

function loadVisitors() {
    const container = document.getElementById('visitorsList');
    if (!container) return;

    fetch('/api/profile/current/visitors', {
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        })
        .then(res => {
            const visitors = res.data || [];
            if (!visitors.length) {
                container.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">No visitors yet.</p>';
                return;
            }
            container.innerHTML = visitors.slice(0, 5).map(v => {
                const firstName = v.first_name || 'U';
                const lastName = v.last_name || '';
                const initials = (firstName.charAt(0) + lastName.charAt(0)).toUpperCase();
                const fullName = firstName + ' ' + lastName;
                return `
                    <div style="display: flex; align-items: center; gap: 12px; padding: 12px; border-bottom: 1px solid #f0f0f0;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #00bcd4, #00acc1); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; flex-shrink: 0;">
                            ${initials}
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-size: 13px; font-weight: 600; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(fullName)}</div>
                            <div style="font-size: 12px; color: #999;">${formatDate(v.visited_at || v.created_at)}</div>
                        </div>
                    </div>
                `;
            }).join('');
        })
        .catch(() => {
            container.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">Error loading visitors</p>';
        });
}

// ── Posts ─────────────────────────────────────────────────────────────────────

function loadPosts() {
    const container = document.getElementById('postsList');
    if (!container) return;

    container.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">Loading posts...</p>';

    fetch('/api/profile/current/posts', {
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        })
        .then(res => {
            const posts = res.data || [];
            if (!posts.length) {
                container.innerHTML = `
                    <div class="modern-empty-state">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="currentColor" opacity="0.3"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                        <p>No posts yet</p>
                        <span>Share your first status update above!</span>
                    </div>
                `;
                return;
            }
            container.innerHTML = posts.map(post => `
                <div class="modern-post" id="post-${post.id}">
                    <div class="post-header">
                        <div class="post-meta">
                            ${formatDate(post.created_at)}
                            <span class="post-privacy-badge">${post.privacy || 'public'}</span>
                        </div>
                        <button class="post-delete-btn" onclick="deletePost(${post.id})">Delete</button>
                    </div>
                    ${post.image ? `<img src="${post.image}" class="post-image" alt="Post image">` : ''}
                    <div class="post-content">${escapeHtml(post.content)}</div>
                </div>
            `).join('');
        })
        .catch(() => {
            container.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">Error loading posts</p>';
        });
}

window.deletePost = function (postId) {
    showModal('Confirm Delete', 'Are you sure you want to delete this post?', [
        {
            text: 'Delete',
            onclick: () => {
                fetch(`/api/profile/posts/${postId}`, {
                    method: 'DELETE',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then(r => {
                        if (!r.ok) throw new Error(`HTTP ${r.status}`);
                        return r.json();
                    })
                    .then(res => {
                        if (res.success) {
                            const card = document.getElementById(`post-${postId}`);
                            if (card) card.remove();
                            closeModal();
                        } else {
                            showModal('Error', res.message || 'Failed to delete post');
                        }
                    })
                    .catch(() => showModal('Error', 'Error deleting post'));
            }
        },
        {
            text: 'Cancel',
            onclick: closeModal
        }
    ]);
};

// ── Profile Update ────────────────────────────────────────────────────────────

window.updateProfile = function (e) {
    e.preventDefault();
    const msgEl = document.getElementById('settingsMessage');
    msgEl.innerHTML = '';

    const firstName = document.getElementById('firstName').value.trim();
    const lastName = document.getElementById('lastName').value.trim();
    const bio = document.getElementById('bio').value.trim();
    const isPrivate = document.getElementById('isPrivate').value === '1';

    if (!firstName || !lastName) {
        msgEl.innerHTML = '<div class="message error">First and last names are required.</div>';
        return;
    }

    const originalFirstName = document.getElementById('firstName').getAttribute('data-original') || document.getElementById('firstName').value;
    const originalLastName = document.getElementById('lastName').getAttribute('data-original') || document.getElementById('lastName').value;
    const originalBio = document.getElementById('bio').getAttribute('data-original') || document.getElementById('bio').value;
    const originalIsPrivate = document.getElementById('isPrivate').getAttribute('data-original') === '1';

    if (firstName === originalFirstName && lastName === originalLastName && bio === originalBio && isPrivate === originalIsPrivate) {
        msgEl.innerHTML = '<div class="message error">⚠️ No changes detected. Please modify something before saving.</div>';
        return;
    }

    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg> Saving...';

    fetch('/api/profile', {
        method: 'PUT',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            first_name: firstName,
            last_name: lastName,
            bio: bio || null,
            is_private: isPrivate,
        }),
    })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            if (res.success) {
                msgEl.innerHTML = '<div class="message success">Profile updated successfully!</div>';
                setTimeout(() => msgEl.innerHTML = '', 3000);
            } else {
                msgEl.innerHTML = `<div class="message error">${res.message || 'Failed to update profile'}</div>`;
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            msgEl.innerHTML = '<div class="message error">Network error. Please try again.</div>';
        });
};

// ── Change Password ───────────────────────────────────────────────────────────

window.changePassword = function (e) {
    e.preventDefault();
    const msgEl = document.getElementById('passwordMessage');
    msgEl.innerHTML = '';

    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (!currentPassword || !newPassword || !confirmPassword) {
        msgEl.innerHTML = '<div class="message error">All fields are required.</div>';
        return;
    }

    if (newPassword.length < 8) {
        msgEl.innerHTML = '<div class="message error">New password must be at least 8 characters.</div>';
        return;
    }

    if (newPassword !== confirmPassword) {
        msgEl.innerHTML = '<div class="message error">Passwords do not match.</div>';
        return;
    }

    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg> Changing...';

    fetch('/api/profile/password', {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            current_password: currentPassword,
            new_password: newPassword,
            confirm_password: confirmPassword,
        }),
    })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            if (res.success) {
                msgEl.innerHTML = '<div class="message success">Password changed successfully!</div>';
                document.getElementById('changePasswordForm').reset();
                setTimeout(() => msgEl.innerHTML = '', 3000);
            } else {
                msgEl.innerHTML = `<div class="message error">${res.message || 'Failed to change password'}</div>`;
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            msgEl.innerHTML = '<div class="message error">Network error. Please try again.</div>';
        });
};

// ── Create Post ───────────────────────────────────────────────────────────────

window.createPost = function (e) {
    e.preventDefault();
    const msgEl = document.getElementById('postMessage');
    msgEl.innerHTML = '';

    const content = document.getElementById('postContent').value.trim();
    const privacy = document.getElementById('postPrivacy').value;
    const imageInput = document.getElementById('postImage');

    if (!content) {
        msgEl.innerHTML = '<div class="message error">Post content is required.</div>';
        return;
    }

    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg> Posting...';

    const formData = new FormData();
    formData.append('content', content);
    formData.append('privacy', privacy);
    if (imageInput.files.length > 0) {
        formData.append('image', imageInput.files[0]);
    }

    fetch('/api/profile/posts', {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
    })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            if (res.success) {
                msgEl.innerHTML = '<div class="message success">Post created successfully!</div>';
                document.getElementById('createPostForm').reset();
                document.getElementById('postCount').textContent = '0';
                loadPosts();
                setTimeout(() => msgEl.innerHTML = '', 3000);
            } else {
                msgEl.innerHTML = `<div class="message error">${res.message || 'Failed to create post'}</div>`;
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            msgEl.innerHTML = '<div class="message error">Network error. Please try again.</div>';
        });
};

// ── Photo Upload ──────────────────────────────────────────────────────────────

window.uploadAvatar = function (input) {
    console.log('uploadAvatar called with input:', input);
    if (!input.files.length) {
        console.log('No files selected');
        return;
    }

    console.log('File selected:', input.files[0]);
    const formData = new FormData();
    formData.append('avatar', input.files[0]);

    console.log('Sending avatar upload request...');
    fetch('/api/profile/avatar', {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
    })
        .then(r => {
            console.log('Avatar upload response status:', r.status);
            return r.json();
        })
        .then(res => {
            console.log('Avatar upload response:', res);
            if (res.success) {
                const avatarEl = document.getElementById('profileAvatar');
                if (res.data && res.data.profile_picture) {
                    console.log('Updating avatar with:', res.data.profile_picture);
                    avatarEl.innerHTML = `<img src="/storage/${res.data.profile_picture}?t=${Date.now()}" alt="Avatar">`;
                }
                showModal('Success', 'Profile picture updated successfully!');
                setTimeout(() => location.reload(), 1500);
            } else {
                console.log('Upload failed:', res.message);
                showModal('Error', res.message || 'Failed to upload avatar');
            }
        })
        .catch(err => {
            console.error('Avatar upload error:', err);
            showModal('Error', 'Error uploading avatar: ' + err.message);
        });

    input.value = '';
};

window.uploadCover = function (input) {
    if (!input.files.length) return;

    const formData = new FormData();
    formData.append('cover', input.files[0]);

    fetch('/api/profile/cover', {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
    })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const coverEl = document.getElementById('profileCover');
                if (res.data.cover_photo) {
                    coverEl.style.backgroundImage = `url('/storage/${res.data.cover_photo}')`;
                    showModal('Success', 'Cover photo updated successfully!');
                    setTimeout(() => location.reload(), 1500);
                }
            } else {
                showModal('Error', res.message || 'Failed to upload cover');
            }
        })
        .catch(() => showModal('Error', 'Error uploading cover'));

    input.value = '';
};

window.deleteCover = function () {
    showModal('Confirm Delete', 'Are you sure you want to delete your cover photo?', [
        {
            text: 'Delete',
            onclick: () => {
                fetch('/api/profile/cover', {
                    method: 'DELETE',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            showModal('Success', 'Cover photo deleted successfully!');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showModal('Error', res.message || 'Failed to delete cover');
                        }
                    })
                    .catch(() => showModal('Error', 'Error deleting cover'));
            }
        },
        {
            text: 'Cancel',
            onclick: closeModal
        }
    ]);
};

window.removeProfilePicture = function () {
    showModal('Confirm Delete', 'Are you sure you want to remove your profile picture?', [
        {
            text: 'Remove',
            color: '#e74c3c',
            onclick: () => {
                fetch('/api/profile/avatar', {
                    method: 'DELETE',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            showModal('Success', 'Profile picture removed successfully!');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showModal('Error', res.message || 'Failed to remove profile picture');
                        }
                    })
                    .catch(() => showModal('Error', 'Error removing profile picture'));
            }
        },
        {
            text: 'Cancel',
            color: '#95a5a6',
            onclick: closeModal
        }
    ]);
};

window.viewProfilePictureModal = function () {
    const profileImg = document.querySelector('.modern-avatar img');
    if (!profileImg) return;
    
    const modal = document.createElement('div');
    modal.id = 'profile-picture-modal';
    modal.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.9); z-index:10000; display:flex; align-items:center; justify-content:center;';
    
    modal.innerHTML = `
        <div style="position:relative; max-width:90%; max-height:90%; display:flex; flex-direction:column; align-items:center;">
            <img src="${profileImg.src}" alt="Profile Picture" style="max-width:100%; max-height:80vh; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.5);">
            <button onclick="document.getElementById('profile-picture-modal').remove();" style="position:absolute; top:10px; right:10px; background:#e74c3c; color:white; border:none; border-radius:50%; width:40px; height:40px; cursor:pointer; font-size:24px; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(0,0,0,0.3);">✕</button>
            <p style="color:white; margin-top:15px; font-size:14px;">Click outside or press X to close</p>
        </div>
    `;
    
    modal.onclick = (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    };
    
    document.body.appendChild(modal);
};

window.viewProfilePicture = function (event) {
    if (event) event.stopPropagation();
    viewProfilePictureModal();
};

window.toggleAvatarMenu = function (event) {
    if (event) event.stopPropagation();
    const menu = document.getElementById('avatarMenu');
    const btn = event && event.target ? event.target.closest('button') : document.querySelector('[onclick*="toggleAvatarMenu"]');
    
    if (menu) {
        if (menu.style.display === 'none' || menu.style.display === '') {
            if (btn) {
                const rect = btn.getBoundingClientRect();
                menu.style.top = (rect.bottom + 8) + 'px';
                menu.style.left = (rect.right - 160) + 'px';
            }
            menu.style.display = 'block';
        } else {
            menu.style.display = 'none';
        }
    }
};

window.closeAvatarMenu = function () {
    const menu = document.getElementById('avatarMenu');
    if (menu) {
        menu.style.display = 'none';
    }
};

document.addEventListener('click', function (e) {
    const menu = document.getElementById('avatarMenu');
    const btn = document.querySelector('[onclick*="toggleAvatarMenu"]');
    if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target)) {
        menu.style.display = 'none';
    }
});

// ── Modal Functions ──────────────────────────────────────────────────────────

window.showModal = function (title, message, buttons = []) {
    const modal = document.createElement('div');
    modal.id = 'custom-modal';
    modal.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:10001; padding:30px; max-width:400px; text-align:center;';
    
    let buttonsHTML = '';
    if (buttons.length > 0) {
        buttonsHTML = `<div style="display:flex; gap:10px; margin-top:20px; justify-content:center;">
            ${buttons.map((btn, idx) => `<button id="modal-btn-${idx}" style="padding:10px 20px; background:${btn.color || '#3498db'}; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">${btn.text}</button>`).join('')}
        </div>`;
    } else {
        buttonsHTML = `<button onclick="closeModal();" style="padding:10px 20px; background:#3498db; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; margin-top:20px;">OK</button>`;
    }
    
    modal.innerHTML = `
        <h2 style="margin:0 0 15px 0; color:#333; font-size:18px;">${title}</h2>
        <p style="margin:0; color:#666; font-size:14px; line-height:1.6;">${message}</p>
        ${buttonsHTML}
    `;

    const overlay = document.createElement('div');
    overlay.id = 'custom-modal-overlay';
    overlay.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10000;';
    overlay.onclick = () => {
        modal.remove();
        overlay.remove();
    };

    document.body.appendChild(overlay);
    document.body.appendChild(modal);
    
    buttons.forEach((btn, idx) => {
        const btnEl = document.getElementById(`modal-btn-${idx}`);
        if (btnEl && btn.onclick) {
            btnEl.onclick = btn.onclick;
        }
    });
};

window.closeModal = function () {
    const modal = document.getElementById('custom-modal');
    const overlay = document.getElementById('custom-modal-overlay');
    if (modal) modal.remove();
    if (overlay) overlay.remove();
};

// ── Location Update ──────────────────────────────────────────────────────────

function loadCurrentLocation() {
    console.log('📍 Loading current location...');
    
    fetch('/api/user/location', {
        credentials: 'include',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        })
        .then(res => {
            console.log('✅ Location response:', res);
            
            const latInput = document.getElementById('locationLatitude');
            const lngInput = document.getElementById('locationLongitude');
            const displayEl = document.getElementById('currentLocationDisplay');
            
            if (res.data && res.data.latitude && res.data.longitude) {
                const lat = parseFloat(res.data.latitude);
                const lng = parseFloat(res.data.longitude);
                
                console.log('📌 Current location:', lat, lng);
                
                if (latInput) latInput.value = lat;
                if (lngInput) lngInput.value = lng;
                
                if (displayEl) {
                    displayEl.innerHTML = `
                        <div style="font-weight: 600; color: #1a3a52; margin-bottom: 4px;">📍 Latitude: ${lat.toFixed(4)}</div>
                        <div style="font-weight: 600; color: #1a3a52;">📍 Longitude: ${lng.toFixed(4)}</div>
                        <div style="font-size: 11px; color: #999; margin-top: 6px;">Last updated: ${res.data.location_updated_at ? new Date(res.data.location_updated_at).toLocaleString() : 'Never'}</div>
                    `;
                }
            } else {
                console.log('⚠️ No location data found');
                if (displayEl) {
                    displayEl.innerHTML = '<div style="color: #999;">No location set yet. Enable GPS or enter coordinates manually.</div>';
                }
            }
        })
        .catch(err => {
            console.error('❌ Error loading location:', err);
            const displayEl = document.getElementById('currentLocationDisplay');
            if (displayEl) {
                displayEl.innerHTML = '<div style="color: #e74c3c;">Error loading location</div>';
            }
        });
}

window.enableLocationGPS = function () {
    console.log('📍 Enabling GPS location...');
    
    const latInput = document.getElementById('locationLatitude');
    const lngInput = document.getElementById('locationLongitude');
    const msgEl = document.getElementById('locationMessage');
    
    msgEl.innerHTML = '<div class="message" style="background: #e3f2fd; color: #1976d2; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px;">⏳ Requesting GPS location...</div>';
    
    if (!navigator.geolocation) {
        console.warn('⚠️ Geolocation not supported');
        msgEl.innerHTML = '<div class="message error">Geolocation is not supported by your browser. Please enter coordinates manually.</div>';
        return;
    }
    
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            console.log('✅ GPS location obtained:', lat, lng);
            
            if (latInput) latInput.value = lat.toFixed(4);
            if (lngInput) lngInput.value = lng.toFixed(4);
            
            msgEl.innerHTML = '<div class="message success">✅ GPS location captured! Click "Save Location" to update.</div>';
            setTimeout(() => msgEl.innerHTML = '', 3000);
        },
        (error) => {
            console.warn('⚠️ Geolocation error:', error.code, error.message);
            msgEl.innerHTML = '<div class="message error">⚠️ GPS access denied or timed out. Using IP-based location fallback...</div>';
            
            fetch('https://ipapi.co/json/')
                .then(r => r.json())
                .then(data => {
                    if (data.latitude && data.longitude) {
                        const lat = parseFloat(data.latitude);
                        const lng = parseFloat(data.longitude);
                        
                        console.log('✅ IP-based location:', lat, lng);
                        
                        if (latInput) latInput.value = lat.toFixed(4);
                        if (lngInput) lngInput.value = lng.toFixed(4);
                        
                        msgEl.innerHTML = '<div class="message success">✅ IP-based location captured! Click "Save Location" to update.</div>';
                        setTimeout(() => msgEl.innerHTML = '', 3000);
                    }
                })
                .catch(() => {
                    msgEl.innerHTML = '<div class="message error">❌ Could not determine location. Please enter coordinates manually.</div>';
                });
        }
    );
};

window.searchAddress = function (query) {
    console.log('🔍 Searching address in Sagay City:', query);
    
    const suggestionsEl = document.getElementById('addressSuggestions');
    
    if (!query || query.length < 2) {
        suggestionsEl.style.display = 'none';
        return;
    }
    
    const searchQuery = `${query}, Sagay City, Negros Occidental, Philippines`;
    
    fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(searchQuery)}&format=json&limit=10&viewbox=123.38,10.94,123.47,10.85&bounded=1`)
        .then(r => r.json())
        .then(results => {
            console.log('✅ Search results from Sagay City:', results);
            
            const sagayMinLat = 10.85;
            const sagayMaxLat = 10.94;
            const sagayMinLng = 123.38;
            const sagayMaxLng = 123.47;
            
            const filteredResults = results.filter(result => {
                const lat = parseFloat(result.lat);
                const lng = parseFloat(result.lon);
                const inBounds = lat >= sagayMinLat && lat <= sagayMaxLat && lng >= sagayMinLng && lng <= sagayMaxLng;
                return inBounds;
            });
            
            if (!filteredResults.length) {
                suggestionsEl.innerHTML = '<div style="padding: 12px; color: #999; text-align: center;">No results found in Sagay City.</div>';
                suggestionsEl.style.display = 'block';
                return;
            }
            
            suggestionsEl.innerHTML = filteredResults.map(result => `
                <div onclick="selectAddress('${result.display_name}', ${result.lat}, ${result.lon})" style="padding: 12px 14px; border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: background 0.2s;">
                    <div style="font-weight: 600; color: #1a3a52; font-size: 13px;">${result.display_name}</div>
                    <div style="font-size: 11px; color: #999; margin-top: 2px;">Lat: ${parseFloat(result.lat).toFixed(4)}, Lng: ${parseFloat(result.lon).toFixed(4)}</div>
                </div>
            `).join('');
            
            suggestionsEl.style.display = 'block';
            
            suggestionsEl.querySelectorAll('div[onclick]').forEach(el => {
                el.addEventListener('mouseover', () => el.style.background = '#f5f5f5');
                el.addEventListener('mouseout', () => el.style.background = 'transparent');
            });
        })
        .catch(err => {
            console.error('❌ Search error:', err);
            suggestionsEl.innerHTML = '<div style="padding: 12px; color: #e74c3c; text-align: center;">Search error. Please try again.</div>';
            suggestionsEl.style.display = 'block';
        });
};

window.selectAddress = function (address, lat, lng) {
    console.log('✅ Selected address:', address, lat, lng);
    
    const addressInput = document.getElementById('locationAddress');
    const latInput = document.getElementById('locationLatitude');
    const lngInput = document.getElementById('locationLongitude');
    const suggestionsEl = document.getElementById('addressSuggestions');
    
    if (addressInput) addressInput.value = address;
    if (latInput) latInput.value = parseFloat(lat).toFixed(4);
    if (lngInput) lngInput.value = parseFloat(lng).toFixed(4);
    
    suggestionsEl.style.display = 'none';
    
    const msgEl = document.getElementById('locationMessage');
    msgEl.innerHTML = '<div class="message success">✅ Address selected! Click "Save Location" to update.</div>';
    setTimeout(() => msgEl.innerHTML = '', 3000);
};

window.updateLocationSubmit = function (e) {
    e.preventDefault();
    
    console.log('💾 Submitting location update...');
    
    const msgEl = document.getElementById('locationMessage');
    msgEl.innerHTML = '';
    
    const latitude = parseFloat(document.getElementById('locationLatitude').value);
    const longitude = parseFloat(document.getElementById('locationLongitude').value);
    
    if (isNaN(latitude) || isNaN(longitude)) {
        console.error('❌ Invalid coordinates');
        msgEl.innerHTML = '<div class="message error">Please search for an address or enable GPS to get coordinates.</div>';
        return;
    }
    
    if (latitude < -90 || latitude > 90) {
        console.error('❌ Latitude out of range');
        msgEl.innerHTML = '<div class="message error">Latitude must be between -90 and 90.</div>';
        return;
    }
    
    if (longitude < -180 || longitude > 180) {
        console.error('❌ Longitude out of range');
        msgEl.innerHTML = '<div class="message error">Longitude must be between -180 and 180.</div>';
        return;
    }
    
    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg> Saving...';

    fetch('/api/user/location', {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            latitude: latitude,
            longitude: longitude,
        }),
    })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            if (res.success) {
                msgEl.innerHTML = '<div class="message success">✅ Location updated successfully!</div>';
                loadCurrentLocation();
                setTimeout(() => msgEl.innerHTML = '', 3000);
            } else {
                msgEl.innerHTML = `<div class="message error">${res.message || 'Failed to update location'}</div>`;
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            msgEl.innerHTML = '<div class="message error">❌ Network error. Please try again.</div>';
        });
};

// ── Helpers ───────────────────────────────────────────────────────────────────

function setEl(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

// ── Boot ──────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    const section = document.getElementById('profile');
    if (!section) return;

    const observer = new MutationObserver(() => {
        if (section.classList.contains('active')) {
            initProfileSection();
        }
    });
    observer.observe(section, { attributes: true, attributeFilter: ['class'] });
});
