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
    if (!confirm('Delete this post?')) return;

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
                loadProfileStats();
            } else {
                alert(res.message || 'Failed to delete post');
            }
        })
        .catch(() => alert('Error deleting post'));
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

    // Check if anything actually changed
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
                document.querySelector('.modern-profile-name').textContent = firstName + ' ' + lastName;
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
    if (!input.files.length) return;

    const formData = new FormData();
    formData.append('avatar', input.files[0]);

    fetch('/api/profile/avatar', {
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
                const avatarEl = document.getElementById('profileAvatar');
                if (res.data.profile_picture) {
                    avatarEl.innerHTML = `<img src="${res.data.profile_picture}" alt="Avatar">`;
                }
            } else {
                alert(res.message || 'Failed to upload avatar');
            }
        })
        .catch(() => alert('Error uploading avatar'));

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
                    coverEl.style.backgroundImage = `url('${res.data.cover_photo}')`;
                    location.reload();
                }
            } else {
                alert(res.message || 'Failed to upload cover');
            }
        })
        .catch(() => alert('Error uploading cover'));

    input.value = '';
};

window.deleteCover = function () {
    if (!confirm('Delete cover photo?')) return;

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
                location.reload();
            } else {
                alert(res.message || 'Failed to delete cover');
            }
        })
        .catch(() => alert('Error deleting cover'));
};

// ── Helpers ───────────────────────────────────────────────────────────────────

function setEl(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
}

function escapeHtml(str) {
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
