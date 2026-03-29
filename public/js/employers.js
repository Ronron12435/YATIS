const EmployersModule = (() => {
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let currentJobId = null;
    let currentApplications = [];

    const init = () => {
        createModalHTML();
    };

    const createModalHTML = () => {
        if (document.getElementById('modal-container')) return;
        
        const modalHTML = `
            <div id="modal-container" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; align-items:center; justify-content:center;">
                <div style="background:white; border-radius:12px; padding:30px; max-width:500px; width:90%; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
                    <h3 id="modal-title" style="margin:0 0 15px 0; color:#333; font-size:20px; font-weight:600;"></h3>
                    <p id="modal-message" style="margin:0 0 25px 0; color:#666; font-size:14px; line-height:1.6;"></p>
                    <div id="modal-buttons" style="display:flex; gap:10px; justify-content:flex-end;">
                        <button id="modal-close-btn" onclick="EmployersModule.closeModal()" style="padding:10px 20px; background:#3498db; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Close</button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    };

    const showModal = (title, message, buttons = null) => {
        const container = document.getElementById('modal-container');
        const titleEl = document.getElementById('modal-title');
        const messageEl = document.getElementById('modal-message');
        const buttonsEl = document.getElementById('modal-buttons');

        if (!container) {
            createModalHTML();
        }

        titleEl.textContent = title;
        messageEl.textContent = message;

        if (buttons) {
            buttonsEl.innerHTML = buttons;
        } else {
            buttonsEl.innerHTML = '<button onclick="EmployersModule.closeModal()" style="padding:10px 20px; background:#3498db; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Close</button>';
        }

        document.getElementById('modal-container').style.display = 'flex';
    };

    const closeModal = () => {
        const container = document.getElementById('modal-container');
        if (container) {
            container.style.display = 'none';
        }
    };

    const loadStats = () => {
        // Get user role from meta tag
        const userRole = document.querySelector('meta[name="user-role"]')?.content || 'user';
        
        // Load pending applications count (only for business owners/employers)
        if (userRole === 'business' || userRole === 'employer') {
            fetch('/api/jobs/pending-applications-count', { credentials: 'include' })
                .then(r => {
                    if (r.status === 404) {
                        return null;
                    }
                    return r.json();
                })
                .then(response => {
                    if (response && response.success) {
                        const countEl = document.getElementById('emp-apps-count');
                        if (countEl) {
                            countEl.textContent = response.data.count || 0;
                        }
                    }
                })
                .catch(err => {
                    console.debug('Stats endpoint not available for this user role');
                });
        } else {
            // For regular users, load their own pending applications
            fetch('/api/jobs/applications/my-applications', { credentials: 'include' })
                .then(r => r.json())
                .then(response => {
                    let pendingCount = 0;
                    
                    if (response.success && Array.isArray(response.data)) {
                        // Count applications with pending status
                        pendingCount = response.data.filter(app => app.app_status === 'pending').length;
                    } else if (Array.isArray(response.data)) {
                        pendingCount = response.data.filter(app => app.app_status === 'pending').length;
                    }
                    
                    const countEl = document.getElementById('emp-apps-count');
                    if (countEl) {
                        countEl.textContent = pendingCount;
                    }
                })
                .catch(err => {
                    console.debug('Could not load user applications count');
                });
        }

        // Load open positions count (for all users)
        fetch('/api/jobs', { credentials: 'include' })
            .then(r => r.json())
            .then(response => {
                let openCount = 0;
                
                if (response.success && response.data) {
                    // Handle paginated response
                    if (response.data.data && Array.isArray(response.data.data)) {
                        openCount = response.data.data.length;
                    } else if (response.data.total) {
                        // Use total count from pagination
                        openCount = response.data.total;
                    } else if (Array.isArray(response.data)) {
                        // Handle simple array response
                        openCount = response.data.length;
                    }
                }
                
                const countEl = document.getElementById('emp-jobs-count');
                if (countEl) {
                    countEl.textContent = openCount;
                }
            })
            .catch(err => {
                console.debug('Could not load job count');
            });
    };

    const showBrowseJobs = () => {
        const section = document.getElementById('job-listings');
        if (section) {
            section.scrollIntoView({ behavior: 'smooth' });
            loadJobListings();
        }
    };

    const showMyApplications = () => {
        const section = document.getElementById('my-applications');
        if (section) {
            section.scrollIntoView({ behavior: 'smooth' });
            loadMyApplications();
        }
    };

    const loadMyApplications = () => {
        fetch('/api/jobs/applications/my-applications')
            .then(r => r.json())
            .then(response => {
                if (response.success) {
                    renderMyApplications(response.data || []);
                }
            })
            .catch(err => console.error('Error loading applications:', err));
    };

    const renderMyApplications = (applications) => {
        const container = document.getElementById('jl-apps-list');
        if (!container) return;

        if (!applications || applications.length === 0) {
            showModal('No Applications', 'You haven\'t applied to any jobs yet');
            return;
        }

        container.innerHTML = applications.map(app => {
            const statusColor = {
                'pending': '#f39c12',
                'reviewed': '#3498db',
                'accepted': '#27ae60',
                'rejected': '#e74c3c'
            }[app.app_status] || '#95a5a6';

            const statusLabel = {
                'pending': 'Pending',
                'reviewed': 'Reviewed',
                'accepted': 'Accepted',
                'rejected': 'Rejected'
            }[app.app_status] || app.app_status;

            let interviewInfo = '';
            if (app.interview_date) {
                const interviewDate = new Date(app.interview_date);
                interviewInfo = `
                    <div style="margin-top:10px; padding:10px; background:#ecf0f1; border-left:4px solid #27ae60; border-radius:4px;">
                        <p style="margin:0; color:#27ae60; font-weight:600;">
                            <i class="fas fa-calendar-check"></i> Interview Scheduled
                        </p>
                        <p style="margin:5px 0 0 0; color:#333; font-size:13px;">
                            ${interviewDate.toLocaleDateString()} at ${interviewDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                        </p>
                    </div>
                `;
            }

            return `
                <div style="border:1px solid #ddd; border-radius:8px; padding:15px; background:white; margin-bottom:10px;">
                    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                        <div>
                            <h4 style="margin:0 0 5px 0; color:#333;">${escapeHtml(app.job_title)}</h4>
                            <p style="margin:5px 0; color:#666; font-size:13px;">
                                <i class="fas fa-map-marker-alt"></i> ${escapeHtml(app.location)}
                            </p>
                            ${app.business_name ? `<p style="margin:5px 0; color:#666; font-size:13px;"><strong>Company:</strong> ${escapeHtml(app.business_name)}</p>` : ''}
                        </div>
                        <span style="background:${statusColor}; color:white; padding:6px 12px; border-radius:4px; font-size:12px; font-weight:600;">${statusLabel}</span>
                    </div>
                    <p style="margin:10px 0 0 0; color:#999; font-size:12px;">Applied: ${new Date(app.applied_at).toLocaleDateString()}</p>
                    ${interviewInfo}
                </div>
            `;
        }).join('');
    };

    const showActiveEmployers = () => {
        const panel = document.getElementById('jl-employers-panel');
        if (panel) {
            panel.style.display = 'block';
            loadActiveEmployers();
        }
    };

    const viewJobDetails = (jobId) => {
        fetch(`/api/jobs/${jobId}`, { credentials: 'include' })
            .then(r => r.json())
            .then(response => {
                const job = response.data || response;
                if (!job) {
                    showModal('Error', 'Job not found');
                    return;
                }

                // Create job details modal
                const modal = document.createElement('div');
                modal.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:10001; padding:30px; max-width:600px; width:90%; max-height:80vh; overflow-y:auto;';
                
                const postedDate = new Date(job.created_at).toLocaleDateString();
                
                modal.innerHTML = `
                    <button onclick="this.closest('div').remove(); document.getElementById('job-detail-overlay').remove();" style="position:absolute; top:15px; right:15px; background:none; border:none; font-size:24px; color:#999; cursor:pointer;">&times;</button>
                    <h2 style="margin:0 0 10px 0; color:#333;">${escapeHtml(job.title)}</h2>
                    <p style="margin:0 0 20px 0; color:#666; font-size:14px;">
                        <i class="fas fa-map-marker-alt"></i> ${escapeHtml(job.location)}
                        ${job.business_name ? ` • <strong>${escapeHtml(job.business_name)}</strong>` : ''}
                    </p>
                    
                    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:15px; margin-bottom:20px; padding-bottom:20px; border-bottom:1px solid #eee;">
                        <div>
                            <div style="color:#999; font-size:12px;">Job Type</div>
                            <div style="color:#333; font-weight:600; margin-top:4px;">${escapeHtml(job.job_type)}</div>
                        </div>
                        <div>
                            <div style="color:#999; font-size:12px;">Salary Range</div>
                            <div style="color:#333; font-weight:600; margin-top:4px;">${job.salary_range || 'Not specified'}</div>
                        </div>
                        <div>
                            <div style="color:#999; font-size:12px;">Posted</div>
                            <div style="color:#333; font-weight:600; margin-top:4px;">${postedDate}</div>
                        </div>
                    </div>
                    
                    <div style="margin-bottom:20px;">
                        <h3 style="margin:0 0 10px 0; color:#333; font-size:16px;">Description</h3>
                        <p style="margin:0; color:#555; line-height:1.6;">${escapeHtml(job.description)}</p>
                    </div>
                    
                    ${job.requirements ? `
                        <div style="margin-bottom:20px;">
                            <h3 style="margin:0 0 10px 0; color:#333; font-size:16px;">Requirements</h3>
                            <p style="margin:0; color:#555; line-height:1.6;">${escapeHtml(job.requirements)}</p>
                        </div>
                    ` : ''}
                    
                    <button onclick="EmployersModule.openApplyModal(${jobId}); this.closest('div').remove(); document.getElementById('job-detail-overlay').remove();" style="width:100%; padding:12px 16px; background:#3498db; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:14px;">
                        <i class="fas fa-paper-plane"></i> Apply for this Position
                    </button>
                `;

                // Create overlay
                const overlay = document.createElement('div');
                overlay.id = 'job-detail-overlay';
                overlay.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10000;';
                overlay.onclick = () => {
                    modal.remove();
                    overlay.remove();
                };

                document.body.appendChild(overlay);
                document.body.appendChild(modal);
            })
            .catch(err => {
                console.error('Error loading job details:', err);
                showModal('Error', 'Failed to load job details');
            });
    };

    const openApplyModal = (jobId) => {
        const modal = document.createElement('div');
        modal.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:10001; padding:30px; max-width:500px; width:90%;';
        
        modal.innerHTML = `
            <button onclick="this.closest('div').remove(); document.getElementById('apply-overlay').remove();" style="position:absolute; top:15px; right:15px; background:none; border:none; font-size:24px; color:#999; cursor:pointer;">&times;</button>
            <h2 style="margin:0 0 20px 0; color:#333;">Apply for Position</h2>
            
            <form id="job-apply-form" style="display:flex; flex-direction:column; gap:15px;">
                <div>
                    <label style="display:block; margin-bottom:8px; color:#333; font-weight:600; font-size:14px;">Resume (PDF, DOC, DOCX)</label>
                    <input type="file" id="apply-resume-input" accept=".pdf,.doc,.docx" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:14px;" required>
                </div>
                
                <div>
                    <label style="display:block; margin-bottom:8px; color:#333; font-weight:600; font-size:14px;">Cover Letter (Optional)</label>
                    <textarea id="apply-cover-letter-input" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:14px; min-height:120px; resize:vertical;" placeholder="Tell us why you're interested in this position..."></textarea>
                </div>
                
                <button type="submit" style="padding:12px 16px; background:#3498db; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:14px;">
                    <i class="fas fa-paper-plane"></i> Submit Application
                </button>
            </form>
        `;

        const overlay = document.createElement('div');
        overlay.id = 'apply-overlay';
        overlay.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10000;';
        overlay.onclick = () => {
            modal.remove();
            overlay.remove();
        };

        document.body.appendChild(overlay);
        document.body.appendChild(modal);

        // Handle form submission
        document.getElementById('job-apply-form').addEventListener('submit', (e) => {
            e.preventDefault();
            
            const resumeInput = document.getElementById('apply-resume-input');
            const coverLetterInput = document.getElementById('apply-cover-letter-input');
            
            if (!resumeInput.files.length) {
                showModal('Error', 'Please upload a resume');
                return;
            }

            const formData = new FormData();
            formData.append('resume', resumeInput.files[0]);
            formData.append('cover_letter', coverLetterInput.value);

            const submitBtn = document.querySelector('#job-apply-form button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            fetch(`/api/jobs/${jobId}/apply`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'X-CSRF-Token': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(r => r.json())
            .then(response => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Application';

                if (response.success) {
                    showModal('Success', 'Application submitted successfully!');
                    setTimeout(() => {
                        closeModal();
                        modal.remove();
                        overlay.remove();
                    }, 1500);
                } else {
                    showModal('Error', response.message || 'Failed to submit application');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Application';
                showModal('Error', 'Error submitting application');
            });
        });
    };

    const loadActiveEmployers = () => {
        fetch('/api/my-jobs', { credentials: 'include' })
            .then(r => {
                if (r.status === 404 || r.status === 403) {
                    // User doesn't have permission to view their jobs (not a business owner)
                    return null;
                }
                return r.json();
            })
            .then(response => {
                if (!response) {
                    // Not a business owner, hide the panel
                    const panel = document.getElementById('jl-employers-panel');
                    if (panel) {
                        panel.style.display = 'none';
                    }
                    return;
                }
                
                let jobs = [];
                if (response.success && Array.isArray(response.data)) {
                    jobs = response.data;
                } else if (Array.isArray(response.data)) {
                    jobs = response.data;
                } else if (Array.isArray(response)) {
                    jobs = response;
                }
                renderActiveEmployers(jobs);
            })
            .catch(err => {
                console.error('Error loading employers:', err);
                const panel = document.getElementById('jl-employers-panel');
                if (panel) {
                    panel.style.display = 'none';
                }
            });
    };

    const renderActiveEmployers = (jobs) => {
        const container = document.getElementById('jl-employers-content');
        if (!container) return;

        if (!jobs || jobs.length === 0) {
            showModal('No Job Postings', 'You haven\'t posted any jobs yet');
            return;
        }

        container.innerHTML = `
            <h3 style="margin-top:0;">My Job Postings</h3>
            ${jobs.map(job => `
                <div style="border:1px solid #ddd; border-radius:8px; padding:15px; background:white; margin-bottom:10px;">
                    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                        <div>
                            <h4 style="margin:0 0 5px 0; color:#333;">${escapeHtml(job.title)}</h4>
                            <p style="margin:5px 0; color:#666; font-size:13px;">
                                <i class="fas fa-map-marker-alt"></i> ${escapeHtml(job.location)}
                            </p>
                        </div>
                        <span style="background:${job.status === 'open' ? '#27ae60' : '#95a5a6'}; color:white; padding:6px 12px; border-radius:4px; font-size:12px; font-weight:600;">${escapeHtml(job.status)}</span>
                    </div>
                    <p style="margin:10px 0 0 0; color:#666; font-size:13px;">Applications: <strong>${job.applications_count || 0}</strong></p>
                    <button onclick="EmployersModule.viewJobApplications(${job.id})" style="margin-top:10px; padding:8px 12px; background:#3498db; color:white; border:none; border-radius:4px; cursor:pointer; font-size:13px; width:100%;">View Applications</button>
                </div>
            `).join('')}
        `;
    };

    const viewJobApplications = (jobId) => {
        currentJobId = jobId;
        fetch(`/api/jobs/${jobId}/applications`)
            .then(r => {
                if (r.status === 403) {
                    showModal('Permission Denied', 'You do not have permission to view applications for this job');
                    return null;
                }
                return r.json();
            })
            .then(response => {
                if (!response) return;
                if (response.success) {
                    currentApplications = response.data || [];
                    if (currentApplications.length === 0) {
                        showModal('No Applications', 'No applications yet for this job');
                    } else {
                        renderJobApplications(currentApplications);
                    }
                } else {
                    showModal('Error', 'Error: ' + (response.message || 'Failed to load applications'));
                }
            })
            .catch(err => {
                console.error('Error loading applications:', err);
                showModal('Error', 'Error loading applications');
            });
    };

    const renderJobApplications = (applications) => {
        const container = document.getElementById('jl-detail-content');
        if (!container) return;

        if (!applications || applications.length === 0) {
            return;
        }

        container.innerHTML = `
            <h3 style="margin-top:0;">Job Applications (${applications.length})</h3>
            ${applications.map((app, idx) => `
                <div style="border:1px solid #ddd; border-radius:8px; padding:15px; background:white; margin-bottom:15px;">
                    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                        <div>
                            <h4 style="margin:0 0 5px 0; color:#333;">${escapeHtml(app.first_name)} ${escapeHtml(app.last_name)}</h4>
                            <p style="margin:5px 0; color:#666; font-size:13px;">@${escapeHtml(app.username)}</p>
                            <p style="margin:5px 0; color:#666; font-size:13px;">${escapeHtml(app.email)}</p>
                        </div>
                        <span style="background:${getStatusColor(app.app_status)}; color:white; padding:6px 12px; border-radius:4px; font-size:12px; font-weight:600;">${getStatusLabel(app.app_status)}</span>
                    </div>
                    
                    ${app.cover_letter ? `<p style="margin:10px 0; padding:10px; background:#f5f5f5; border-radius:4px; color:#555; font-size:13px;"><strong>Cover Letter:</strong><br>${escapeHtml(app.cover_letter)}</p>` : ''}
                    
                    ${app.resume ? `<p style="margin:10px 0; color:#666; font-size:13px;"><a href="/storage/${escapeHtml(app.resume)}" target="_blank" style="color:#3498db; text-decoration:none;"><i class="fas fa-file-pdf"></i> View Resume</a></p>` : ''}
                    
                    <div style="margin-top:15px; padding-top:15px; border-top:1px solid #eee;">
                        <label style="display:block; margin-bottom:10px; font-weight:600; color:#333;">Update Status:</label>
                        <select id="status-${idx}" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; margin-bottom:10px;">
                            <option value="pending" ${app.app_status === 'pending' ? 'selected' : ''}>Pending</option>
                            <option value="reviewed" ${app.app_status === 'reviewed' ? 'selected' : ''}>Reviewed</option>
                            <option value="accepted" ${app.app_status === 'accepted' ? 'selected' : ''}>Accepted</option>
                            <option value="rejected" ${app.app_status === 'rejected' ? 'selected' : ''}>Rejected</option>
                        </select>
                        
                        <label style="display:block; margin-bottom:10px; font-weight:600; color:#333;">Interview Date (if accepted):</label>
                        <input type="datetime-local" id="interview-${idx}" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; margin-bottom:10px;" ${app.interview_date ? `value="${formatDateTimeLocal(app.interview_date)}"` : ''}>
                        
                        <button onclick="EmployersModule.updateApplicationStatus(${app.id}, ${idx})" style="width:100%; padding:10px; background:#27ae60; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:600;">Save Changes</button>
                    </div>
                </div>
            `).join('')}
        `;

        document.getElementById('jl-detail-panel').style.display = 'block';
    };

    const updateApplicationStatus = (appId, idx) => {
        const status = document.getElementById(`status-${idx}`).value;
        const interviewDate = document.getElementById(`interview-${idx}`).value;

        const formData = new FormData();
        formData.append('_token', CSRF_TOKEN);
        formData.append('status', status);
        if (interviewDate && status === 'accepted') {
            formData.append('interview_date', new Date(interviewDate).toISOString());
        }

        fetch(`/api/jobs/applications/${appId}`, {
            method: 'PUT',
            body: formData
        })
        .then(r => r.json())
        .then(response => {
            if (response.success) {
                showModal('Success', 'Application updated successfully!');
                setTimeout(() => {
                    closeModal();
                    loadStats();
                    if (currentJobId) {
                        viewJobApplications(currentJobId);
                    }
                }, 1500);
            } else {
                showModal('Error', 'Error: ' + (response.message || 'Failed to update'));
            }
        })
        .catch(err => {
            console.error('Error updating application:', err);
            showModal('Error', 'Error updating application');
        });
    };

    const getStatusColor = (status) => {
        const colors = {
            'pending': '#f39c12',
            'reviewed': '#3498db',
            'accepted': '#27ae60',
            'rejected': '#e74c3c'
        };
        return colors[status] || '#95a5a6';
    };

    const getStatusLabel = (status) => {
        const labels = {
            'pending': 'Pending',
            'reviewed': 'Reviewed',
            'accepted': 'Accepted',
            'rejected': 'Rejected'
        };
        return labels[status] || status;
    };

    const formatDateTimeLocal = (dateString) => {
        const date = new Date(dateString);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    };

    const escapeHtml = (text) => {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    return {
        init,
        showBrowseJobs,
        showMyApplications,
        showActiveEmployers,
        viewJobApplications,
        updateApplicationStatus,
        showModal,
        closeModal,
        loadMyApplications,
        viewJobDetails,
        loadStats,
        openApplyModal
    };
})();

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', EmployersModule.init);
} else {
    EmployersModule.init();
}

// Global wrapper function for dashboard
window.loadMyApplications = function() {
    if (typeof EmployersModule !== 'undefined' && EmployersModule.loadMyApplications) {
        EmployersModule.loadMyApplications();
    }
};

// Global function to load stats
window.loadStats = function() {
    if (typeof EmployersModule !== 'undefined' && EmployersModule.loadStats) {
        EmployersModule.loadStats();
    }
};

// Global function to load job listings for regular users
window.loadJobListings = function() {
    fetch('/api/jobs', { credentials: 'include' })
        .then(r => r.json())
        .then(response => {
            let jobs = [];
            if (response.success && response.data && response.data.data) {
                // Handle paginated response
                jobs = response.data.data;
            } else if (response.success && Array.isArray(response.data)) {
                jobs = response.data;
            } else if (Array.isArray(response.data)) {
                jobs = response.data;
            } else if (Array.isArray(response)) {
                jobs = response;
            }
            renderJobListings(jobs);
        })
        .catch(err => {
            console.error('Error loading job listings:', err);
            const container = document.getElementById('jl-jobs-list');
            if (container) {
                container.innerHTML = '<p style="color:#e74c3c; padding:20px; text-align:center;">Error loading job postings</p>';
            }
        });
};

// Render job listings
function renderJobListings(jobs) {
    const container = document.getElementById('jl-jobs-list');
    if (!container) return;

    if (!Array.isArray(jobs) || jobs.length === 0) {
        container.innerHTML = '<p style="color:#999; padding:20px; text-align:center;">No job postings available</p>';
        return;
    }

    // Load user's applications to check if already applied
    fetch('/api/jobs/applications/my-applications', { credentials: 'include' })
        .then(r => r.json())
        .then(response => {
            let userApplications = [];
            if (response.success && Array.isArray(response.data)) {
                userApplications = response.data;
            } else if (Array.isArray(response.data)) {
                userApplications = response.data;
            }

            container.innerHTML = jobs.map(job => {
                const postedDate = new Date(job.created_at).toLocaleDateString();
                const statusBadge = job.status === 'open' 
                    ? '<span style="background:#27ae60; color:white; padding:6px 12px; border-radius:4px; font-size:12px; font-weight:600;">Open</span>'
                    : '<span style="background:#95a5a6; color:white; padding:6px 12px; border-radius:4px; font-size:12px; font-weight:600;">Closed</span>';

                // Check if user already applied
                const hasApplied = userApplications.some(app => app.job_id === job.id);
                const buttonText = hasApplied ? '✓ Already Applied' : 'View Details & Apply';
                const buttonStyle = hasApplied 
                    ? 'background:#95a5a6; cursor:not-allowed; opacity:0.7;'
                    : 'background:#3498db; cursor:pointer; opacity:1;';
                const buttonDisabled = hasApplied ? 'disabled' : '';

                return `
                    <div style="border:1px solid #ddd; border-radius:8px; padding:15px; background:white; margin-bottom:15px; transition:box-shadow 0.2s;">
                        <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                            <div>
                                <h4 style="margin:0 0 5px 0; color:#333; font-size:16px; font-weight:600;">${escapeHtml(job.title)}</h4>
                                <p style="margin:0; color:#666; font-size:14px;">
                                    <i class="fas fa-map-marker-alt"></i> ${escapeHtml(job.location)}
                                </p>
                                ${job.business_name ? `<p style="margin:5px 0 0 0; color:#666; font-size:13px;"><strong>Company:</strong> ${escapeHtml(job.business_name)}</p>` : ''}
                            </div>
                            <div>${statusBadge}</div>
                        </div>

                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:10px; margin:10px 0; font-size:13px;">
                            <div>
                                <span style="color:#999;">Type:</span>
                                <div style="color:#333; font-weight:600;">${escapeHtml(job.job_type)}</div>
                            </div>
                            <div>
                                <span style="color:#999;">Salary:</span>
                                <div style="color:#333; font-weight:600;">${job.salary_range ? escapeHtml(job.salary_range) : 'Not specified'}</div>
                            </div>
                            <div>
                                <span style="color:#999;">Posted:</span>
                                <div style="color:#333; font-weight:600;">${postedDate}</div>
                            </div>
                        </div>

                        <p style="margin:10px 0; color:#555; font-size:14px; line-height:1.5;">
                            ${escapeHtml(job.description).substring(0, 150)}${job.description.length > 150 ? '...' : ''}
                        </p>

                        <button onclick="${hasApplied ? 'return false;' : `EmployersModule.viewJobDetails(${job.id})`}" ${buttonDisabled} style="width:100%; padding:10px 12px; color:white; border:none; border-radius:6px; font-weight:600; display:flex; align-items:center; justify-content:center; gap:8px; ${buttonStyle}">
                            <i class="fas ${hasApplied ? 'fa-check' : 'fa-eye'}"></i> ${buttonText}
                        </button>
                    </div>
                `;
            }).join('');
        })
        .catch(err => {
            console.error('Error loading applications:', err);
            // Render jobs without application check
            container.innerHTML = jobs.map(job => {
                const postedDate = new Date(job.created_at).toLocaleDateString();
                const statusBadge = job.status === 'open' 
                    ? '<span style="background:#27ae60; color:white; padding:6px 12px; border-radius:4px; font-size:12px; font-weight:600;">Open</span>'
                    : '<span style="background:#95a5a6; color:white; padding:6px 12px; border-radius:4px; font-size:12px; font-weight:600;">Closed</span>';

                return `
                    <div style="border:1px solid #ddd; border-radius:8px; padding:15px; background:white; margin-bottom:15px; transition:box-shadow 0.2s;">
                        <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                            <div>
                                <h4 style="margin:0 0 5px 0; color:#333; font-size:16px; font-weight:600;">${escapeHtml(job.title)}</h4>
                                <p style="margin:0; color:#666; font-size:14px;">
                                    <i class="fas fa-map-marker-alt"></i> ${escapeHtml(job.location)}
                                </p>
                                ${job.business_name ? `<p style="margin:5px 0 0 0; color:#666; font-size:13px;"><strong>Company:</strong> ${escapeHtml(job.business_name)}</p>` : ''}
                            </div>
                            <div>${statusBadge}</div>
                        </div>

                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:10px; margin:10px 0; font-size:13px;">
                            <div>
                                <span style="color:#999;">Type:</span>
                                <div style="color:#333; font-weight:600;">${escapeHtml(job.job_type)}</div>
                            </div>
                            <div>
                                <span style="color:#999;">Salary:</span>
                                <div style="color:#333; font-weight:600;">${job.salary_range ? escapeHtml(job.salary_range) : 'Not specified'}</div>
                            </div>
                            <div>
                                <span style="color:#999;">Posted:</span>
                                <div style="color:#333; font-weight:600;">${postedDate}</div>
                            </div>
                        </div>

                        <p style="margin:10px 0; color:#555; font-size:14px; line-height:1.5;">
                            ${escapeHtml(job.description).substring(0, 150)}${job.description.length > 150 ? '...' : ''}
                        </p>

                        <button onclick="EmployersModule.viewJobDetails(${job.id})" style="width:100%; padding:10px 12px; background:#3498db; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; display:flex; align-items:center; justify-content:center; gap:8px;">
                            <i class="fas fa-eye"></i> View Details & Apply
                        </button>
                    </div>
                `;
            }).join('');
        });
}

// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
