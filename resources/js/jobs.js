const JobsModule = (() => {
    let allJobs = [];
    let userRole = null;
    let currentJobId = null;
    let currentBusinessId = null;
    let businessJobs = [];
    let myApplications = [];
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const init = () => {
        getUserRole();
        loadMyApplications();
        setTimeout(() => {
            if (document.getElementById('jobs-list')) loadJobs();
            if (document.getElementById('my-jobs-list')) loadMyJobs();
        }, 100);
        setupEventListeners();
    };

    const setupEventListeners = () => {
        const searchInput = document.getElementById('job-search');
        const typeFilter = document.getElementById('job-type-filter');
        const applicationForm = document.getElementById('application-form');

        if (searchInput) searchInput.addEventListener('input', filterJobs);
        if (typeFilter) typeFilter.addEventListener('change', filterJobs);
        if (applicationForm) applicationForm.addEventListener('submit', submitApplication);
    };

    const getUserRole = () => {
        const roleElement = document.querySelector('meta[name="user-role"]');
        userRole = roleElement ? roleElement.content : 'user';
    };

    const loadJobs = () => {
        fetch('/api/jobs', { credentials: 'include' })
            .then(r => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then(response => {
                // Handle paginated response: { data: { data: [...], ... } } or { data: [...] }
                let jobs = [];
                if (response.data && response.data.data && Array.isArray(response.data.data)) {
                    jobs = response.data.data;
                } else if (Array.isArray(response.data)) {
                    jobs = response.data;
                }
                allJobs = jobs;
                renderJobs(allJobs);
            })
            .catch(err => {
                console.error('Error loading jobs:', err);
                const jobsList = document.getElementById('jobs-list');
                if (jobsList) jobsList.innerHTML = '<p style="color:#e74c3c;">Error loading jobs</p>';
            });
    };

    const renderJobs = (jobs) => {
        const jobsList = document.getElementById('jobs-list');
        if (!jobsList) return;

        if (!jobs || jobs.length === 0) {
            jobsList.innerHTML = '<p style="color:#999; text-align:center; padding:20px;">No jobs available</p>';
            return;
        }

        jobsList.innerHTML = jobs.map(job => {
            const postedDate = new Date(job.created_at).toLocaleDateString();
            const isExpired = job.deadline && new Date(job.deadline) < new Date();
            const statusBadge = isExpired
                ? '<span style="background:#e74c3c; color:white; padding:4px 8px; border-radius:4px; font-size:12px;">Expired</span>'
                : job.status === 'open' 
                ? '<span style="background:#27ae60; color:white; padding:4px 8px; border-radius:4px; font-size:12px;">Open</span>'
                : '<span style="background:#95a5a6; color:white; padding:4px 8px; border-radius:4px; font-size:12px;">Closed</span>';

            return `
                <div style="border:1px solid #ddd; border-radius:8px; padding:15px; background:white; transition:box-shadow 0.2s;">
                    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                        <div>
                            <h4 style="margin:0 0 5px 0; color:#333;">${escapeHtml(job.title)}</h4>
                            <p style="margin:0; color:#666; font-size:14px;">
                                <i class="fas fa-map-marker-alt"></i> ${escapeHtml(job.location)}
                            </p>
                        </div>
                        <div>${statusBadge}</div>
                    </div>

                    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:10px; margin:10px 0; font-size:13px;">
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

                    <button onclick="JobsModule.viewJobDetails(${job.id})" style="width:100%; padding:10px 12px; background:#3498db; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">
                        <i class="fas fa-eye"></i> View Details
                    </button>
                </div>
            `;
        }).join('');
    };

    const filterJobs = () => {
        const searchTerm = document.getElementById('job-search').value.toLowerCase();
        const jobType = document.getElementById('job-type-filter').value;

        const filtered = allJobs.filter(job => {
            const matchesSearch = job.title.toLowerCase().includes(searchTerm) || 
                                job.location.toLowerCase().includes(searchTerm);
            const matchesType = !jobType || job.job_type === jobType;
            return matchesSearch && matchesType;
        });

        renderJobs(filtered);
    };

    const viewJobDetails = (jobId) => {
        currentJobId = jobId;
        
        // First try to find in allJobs array
        let job = allJobs.find(j => j.id === jobId);
        
        // If not found, fetch it directly
        if (!job) {
            fetch(`/api/jobs/${jobId}`, { credentials: 'include' })
                .then(r => {
                    if (!r.ok) throw new Error(`HTTP ${r.status}`);
                    return r.json();
                })
                .then(response => {
                    job = response.data;
                    if (job) {
                        populateJobDetails(job);
                        // Ensure applications are loaded before checking
                        if (myApplications.length === 0) {
                            loadMyApplications().then(() => {
                                checkAndUpdateApplyButton(jobId);
                                openModal('job-details-modal');
                            });
                        } else {
                            checkAndUpdateApplyButton(jobId);
                            openModal('job-details-modal');
                        }
                    }
                })
                .catch(err => console.error('Error loading job:', err));
            return;
        }

        populateJobDetails(job);
        // Ensure applications are loaded before checking
        if (myApplications.length === 0) {
            loadMyApplications().then(() => {
                checkAndUpdateApplyButton(jobId);
                openModal('job-details-modal');
            });
        } else {
            checkAndUpdateApplyButton(jobId);
            openModal('job-details-modal');
        }
    };

    const populateJobDetails = (job) => {
        document.getElementById('detail-job-title').textContent = escapeHtml(job.title);
        document.getElementById('detail-job-type').textContent = escapeHtml(job.job_type);
        document.getElementById('detail-job-salary').textContent = job.salary_range || 'Not specified';
        document.getElementById('detail-job-location').textContent = escapeHtml(job.location);
        document.getElementById('detail-job-description').textContent = escapeHtml(job.description);
        document.getElementById('detail-job-requirements').textContent = escapeHtml(job.requirements || 'No specific requirements');
    };

    const checkAndUpdateApplyButton = (jobId) => {
        const applyBtn = document.getElementById('apply-button');
        const hasApplied = myApplications.some(app => app.job_id === jobId);
        
        if (hasApplied) {
            applyBtn.disabled = true;
            applyBtn.textContent = '✓ You already applied for this position';
            applyBtn.style.background = '#95a5a6';
            applyBtn.style.cursor = 'not-allowed';
            applyBtn.style.opacity = '0.7';
        } else {
            applyBtn.disabled = false;
            applyBtn.textContent = 'Apply for this Position';
            applyBtn.style.background = '#3498db';
            applyBtn.style.cursor = 'pointer';
            applyBtn.style.opacity = '1';
        }
    };

    const openApplyModal = () => {
        if (!currentJobId) return;
        
        const hasApplied = myApplications.some(app => app.job_id === currentJobId);
        if (hasApplied) {
            alert('You have already applied for this position');
            return;
        }
        
        closeModal('job-details-modal');
        document.getElementById('resume-input').value = '';
        document.getElementById('cover-letter-input').value = '';
        openModal('application-modal');
    };

    const submitApplication = (e) => {
        e.preventDefault();

        const resumeInput = document.getElementById('resume-input');
        const coverLetterInput = document.getElementById('cover-letter-input');

        if (!resumeInput.files.length) {
            alert('Please upload a resume');
            return;
        }

        const formData = new FormData();
        formData.append('resume', resumeInput.files[0]);
        formData.append('cover_letter', coverLetterInput.value);

        const submitBtn = document.querySelector('#application-form button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';

        fetch(`/api/jobs/${currentJobId}/apply`, {
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
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Submit Application';

            if (response.success) {
                alert('Application submitted successfully!');
                closeModals();
                loadMyApplications();
            } else {
                alert('Error: ' + (response.message || 'Failed to submit application'));
            }
        })
        .catch(err => {
            console.error('Error:', err);
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Submit Application';
            alert('Error submitting application');
        });
    };

    const loadMyApplications = () => {
        return fetch('/api/jobs/applications/my-applications', { credentials: 'include' })
            .then(r => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then(response => {
                const applications = response.data || [];
                renderMyApplications(applications);
                return applications;
            })
            .catch(err => {
                console.error('Error loading applications:', err);
                const appList = document.getElementById('my-applications');
                if (appList) appList.innerHTML = '<p style="color:#e74c3c;">Error loading applications</p>';
                return [];
            });
    };

    const renderMyApplications = (applications) => {
        const appsList = document.getElementById('my-applications');
        if (!appsList) return;

        // Store applications for duplicate check
        myApplications = applications;

        if (!applications || applications.length === 0) {
            appsList.innerHTML = '<p style="color:#999; text-align:center; padding:20px;">You haven\'t applied to any jobs yet</p>';
            return;
        }

        appsList.innerHTML = applications.map(app => {
            const statusColors = {
                'pending': '#f39c12',
                'reviewed': '#3498db',
                'accepted': '#27ae60',
                'rejected': '#e74c3c'
            };
            const statusColor = statusColors[app.app_status || app.status] || '#95a5a6';
            const appliedDate = new Date(app.applied_at).toLocaleDateString();
            const status = (app.app_status || app.status || 'pending').charAt(0).toUpperCase() + (app.app_status || app.status || 'pending').slice(1);

            return `
                <div style="border:1px solid #ddd; border-radius:8px; padding:15px; background:white;">
                    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                        <div>
                            <h4 style="margin:0 0 5px 0; color:#333;">${escapeHtml(app.job_title || 'Job')}</h4>
                            <p style="margin:0; color:#666; font-size:14px;">Applied on ${appliedDate}</p>
                        </div>
                        <span style="background:${statusColor}; color:white; padding:6px 12px; border-radius:4px; font-size:12px; font-weight:600;">
                            ${status}
                        </span>
                    </div>
                    ${app.interview_date ? `
                        <p style="margin:10px 0; color:#27ae60; font-size:14px;">
                            <i class="fas fa-calendar"></i> Interview scheduled: ${new Date(app.interview_date).toLocaleDateString()}
                        </p>
                    ` : ''}
                </div>
            `;
        }).join('');
    };

    const loadMyJobs = () => {
        if (userRole !== 'employer' && userRole !== 'business') {
            document.getElementById('my-jobs-card').style.display = 'none';
            return;
        }

        fetch('/api/my-jobs', { credentials: 'include' })
            .then(r => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then(response => {
                const jobs = response.data || [];
                if (jobs.length > 0) {
                    document.getElementById('my-jobs-card').style.display = 'block';
                    renderMyJobs(jobs);
                }
            })
            .catch(err => console.error('Error loading my jobs:', err));
    };

    const renderMyJobs = (jobs) => {
        const myJobsList = document.getElementById('my-jobs');

        myJobsList.innerHTML = jobs.map(job => {
            const applicationsCount = job.applications_count || 0;
            const isExpired = job.deadline && new Date(job.deadline) < new Date();
            const statusBadge = isExpired
                ? '<span style="background:#e74c3c; color:white; padding:4px 8px; border-radius:4px; font-size:12px;">Expired</span>'
                : job.status === 'open' 
                ? '<span style="background:#27ae60; color:white; padding:4px 8px; border-radius:4px; font-size:12px;">Open</span>'
                : '<span style="background:#95a5a6; color:white; padding:4px 8px; border-radius:4px; font-size:12px;">Closed</span>';

            return `
                <div style="border:1px solid #ddd; border-radius:8px; padding:15px; background:white;">
                    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                        <div>
                            <h4 style="margin:0 0 5px 0; color:#333;">${escapeHtml(job.title)}</h4>
                            <p style="margin:0; color:#666; font-size:14px;">
                                <i class="fas fa-map-marker-alt"></i> ${escapeHtml(job.location)}
                            </p>
                        </div>
                        <div>${statusBadge}</div>
                    </div>
                    <div style="display:flex; gap:10px; margin-top:12px;">
                        <button onclick="JobsModule.toggleJobStatus(${job.id})" style="flex:1; padding:8px 12px; background:#f39c12; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">
                            <i class="fas fa-toggle-${job.status === 'open' ? 'on' : 'off'}"></i> ${job.status === 'open' ? 'Close' : 'Open'}
                        </button>
                        <button onclick="JobsModule.viewApplications(${job.id})" style="flex:1; padding:8px 12px; background:#3498db; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">
                            <i class="fas fa-file-alt"></i> View Applications (${applicationsCount})
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    };

    const toggleJobStatus = (jobId) => {
        fetch(`/api/jobs/${jobId}/toggle-status`, {
            method: 'POST',
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
                loadMyJobs();
            } else {
                alert('Error: ' + (response.message || 'Failed to toggle status'));
            }
        })
        .catch(err => alert('Error toggling job status'));
    };

    const viewApplications = (jobId) => {
        fetch(`/api/jobs/${jobId}/applications`, { credentials: 'include' })
            .then(r => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then(response => {
                const applications = response.data || [];
                if (applications.length === 0) {
                    alert('No applications yet');
                    return;
                }
                const appList = applications.map(app => 
                    `${app.user?.first_name} ${app.user?.last_name} - ${app.status}`
                ).join('\n');
                alert(`Applications:\n\n${appList}`);
            })
            .catch(err => alert('Error loading applications'));
    };

    const openModal = (modalId) => {
        document.getElementById('job-modal-overlay').style.display = 'block';
        document.getElementById(modalId).style.display = 'block';
    };

    const closeModal = (modalId) => {
        document.getElementById(modalId).style.display = 'none';
    };

    const closeModals = () => {
        document.getElementById('job-modal-overlay').style.display = 'none';
        document.getElementById('job-openings-modal').style.display = 'none';
        document.getElementById('job-details-modal').style.display = 'none';
        document.getElementById('application-modal').style.display = 'none';
        currentJobId = null;
        currentBusinessId = null;
    };

    const escapeHtml = (text) => {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    return {
        init,
        viewJobDetails,
        openApplyModal,
        closeModals,
        toggleJobStatus,
        viewApplications
    };
})();

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', JobsModule.init);
} else {
    JobsModule.init();
}
