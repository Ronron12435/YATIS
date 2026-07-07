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
        setupEventListeners();
        setTimeout(() => {
            if (document.getElementById('jobs-list')) loadJobs();
            if (document.getElementById('my-jobs-list')) loadMyJobs();
        }, 100);
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
            jobsList.innerHTML = '<div style="text-align:center; padding:60px 20px; color:#999;"><i style="font-size:48px; color:#ddd; display:block; margin-bottom:16px;" class="fas fa-briefcase"></i><p style="margin:0; font-size:14px;">No jobs available</p></div>';
            return;
        }

        const isDarkMode = document.body.classList.contains('dark-mode');
        const cardBg = isDarkMode ? '#1a1f2e' : 'white';
        const textPrimary = isDarkMode ? '#e8eaed' : '#1a3a52';
        const textSecondary = isDarkMode ? '#9aa0a6' : '#7f8c8d';
        const textTertiary = isDarkMode ? '#c5cad1' : '#666';

        jobsList.innerHTML = jobs.map(job => {
            const postedDate = new Date(job.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            const isExpired = job.deadline && new Date(job.deadline) < new Date();
            const statusLabel = isExpired ? 'Expired' : (job.status === 'open' ? 'Open' : 'Closed');
            const statusColor = isExpired ? '#e74c3c' : (job.status === 'open' ? '#27ae60' : '#95a5a6');
            const statusBg = isDarkMode 
                ? (isExpired ? 'rgba(231, 76, 60, 0.15)' : (job.status === 'open' ? 'rgba(39, 174, 96, 0.15)' : 'rgba(149, 165, 166, 0.15)'))
                : (isExpired ? '#ffebee' : (job.status === 'open' ? '#e8f5e9' : '#eceff1'));

            return `
                <div style="background:${cardBg}; border-radius:8px; padding:20px; margin-bottom:16px; border-left:5px solid ${statusColor}; box-shadow:0 2px 8px rgba(0,0,0,${isDarkMode ? '0.3' : '0.08'}); transition:all 0.3s ease;">
                    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:12px;">
                        <div style="flex:1;">
                            <h4 style="margin:0 0 6px 0; color:${textPrimary}; font-size:15px; font-weight:700;">${escapeHtml(job.title)}</h4>
                            <p style="margin:0; color:${textSecondary}; font-size:13px;">
                                <i class="fas fa-map-marker-alt" style="margin-right:4px;"></i>${escapeHtml(job.location)}
                            </p>
                        </div>
                        <span style="background:${statusBg}; color:${statusColor}; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:600; white-space:nowrap; flex-shrink:0; margin-left:12px;">${statusLabel}</span>
                    </div>
                    <div style="display:flex; gap:20px; margin:14px 0; font-size:13px; flex-wrap:wrap;">
                        <span style="color:${textTertiary};"><strong style="color:${textPrimary};">Type:</strong> ${escapeHtml(job.job_type)}</span>
                        <span style="color:${textTertiary};"><strong style="color:${textPrimary};">Salary:</strong> ${job.salary_range ? escapeHtml(job.salary_range) : 'Not specified'}</span>
                        <span style="color:${textSecondary};"><i class="fas fa-clock" style="margin-right:4px;"></i>${postedDate}</span>
                    </div>
                    <p style="margin:12px 0; color:${textTertiary}; font-size:13px; line-height:1.6;">
                        ${escapeHtml(job.description).substring(0, 150)}${job.description.length > 150 ? '...' : ''}
                    </p>
                    <button onclick="JobsModule.viewJobDetails(${job.id})" style="width:100%; padding:10px 16px; background:linear-gradient(135deg, #3498db 0%, #2980b9 100%); color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:13px; transition:all 0.2s ease; margin-top:8px;">
                        View Details
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
        
        let job = allJobs.find(j => j.id === jobId);
        
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
        const appsList = document.getElementById('jl-apps-list');
        if (!appsList) return;

        myApplications = applications;

        if (!applications || applications.length === 0) {
            appsList.innerHTML = '<div style="text-align:center; padding:60px 20px; color:#999;"><i style="font-size:48px; color:#ddd; display:block; margin-bottom:16px;" class="fas fa-file-alt"></i><p style="margin:0; font-size:14px;">You haven\'t applied to any jobs yet</p></div>';
            return;
        }

        const isDarkMode = document.body.classList.contains('dark-mode');
        const cardBg = isDarkMode ? '#1a1f2e' : 'white';
        const textPrimary = isDarkMode ? '#e8eaed' : '#1a3a52';
        const textSecondary = isDarkMode ? '#9aa0a6' : '#7f8c8d';
        const textTertiary = isDarkMode ? '#c5cad1' : '#666';

        appsList.innerHTML = applications.map(app => {
            const appStatus = app.app_status || app.status || 'pending';
            const appliedDate = new Date(app.applied_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            const interviewDate = app.interview_date ? new Date(app.interview_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : null;
            
            const statusConfig = {
                'pending': { color: '#f39c12', bg: isDarkMode ? 'rgba(243, 156, 18, 0.15)' : '#fffbf0', icon: 'hourglass-half' },
                'reviewed': { color: '#3498db', bg: isDarkMode ? 'rgba(52, 152, 219, 0.15)' : '#eff7ff', icon: 'eye' },
                'accepted': { color: '#27ae60', bg: isDarkMode ? 'rgba(39, 174, 96, 0.15)' : '#f0fdf4', icon: 'check-circle' },
                'rejected': { color: '#e74c3c', bg: isDarkMode ? 'rgba(231, 76, 60, 0.15)' : '#fef2f2', icon: 'times-circle' }
            };
            const status = statusConfig[appStatus] || { color: '#95a5a6', bg: isDarkMode ? 'rgba(149, 165, 166, 0.15)' : '#f5f5f5', icon: 'circle' };

            return `
                <div style="background:${cardBg}; border-radius:8px; overflow:hidden; margin-bottom:16px; box-shadow:0 2px 8px rgba(0,0,0,${isDarkMode ? '0.3' : '0.08'}); border-left:5px solid ${status.color}; transition:all 0.3s ease;">
                    <div style="display:flex; justify-content:space-between; align-items:start; padding:16px 20px; background:${status.bg}; border-bottom:1px solid rgba(0,0,0,${isDarkMode ? '0.2' : '0.05'});">
                        <div style="flex:1;">
                            <h4 style="margin:0 0 4px 0; color:${textPrimary}; font-size:15px; font-weight:700;">${escapeHtml(app.job_title || 'Job Position')}</h4>
                            <p style="margin:0; color:${textSecondary}; font-size:12px;">
                                <i class="fas fa-calendar-alt" style="margin-right:4px;"></i>Applied on ${appliedDate}
                            </p>
                        </div>
                        <span style="background:${status.color}; color:white; padding:6px 14px; border-radius:20px; font-size:11px; font-weight:600; white-space:nowrap; flex-shrink:0; margin-left:12px;">
                            <i class="fas fa-${status.icon}" style="margin-right:4px;"></i>${appStatus.charAt(0).toUpperCase() + appStatus.slice(1)}
                        </span>
                    </div>
                    <div style="padding:14px 20px;">
                        <p style="margin:0 0 10px 0; color:${textTertiary}; font-size:13px;">
                            <i class="fas fa-map-marker-alt" style="margin-right:6px; color:${textSecondary};"></i>${escapeHtml(app.location || 'Location')}
                        </p>
                        ${interviewDate ? `
                            <div style="background:${isDarkMode ? 'rgba(33, 150, 243, 0.2)' : '#e3f2fd'}; border-left:3px solid #2196F3; padding:10px 12px; border-radius:4px; font-size:13px; color:${isDarkMode ? '#64b5f6' : '#1565c0'}; font-weight:600;">
                                <i class="fas fa-calendar-check" style="margin-right:6px;"></i>Interview scheduled: ${interviewDate}
                            </div>
                        ` : ''}
                    </div>
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
        if (!myJobsList) return;

        const isDarkMode = document.body.classList.contains('dark-mode');
        const cardBg = isDarkMode ? '#1a1f2e' : 'white';
        const textPrimary = isDarkMode ? '#e8eaed' : '#1a3a52';
        const textSecondary = isDarkMode ? '#9aa0a6' : '#7f8c8d';

        myJobsList.innerHTML = jobs.map(job => {
            const applicationsCount = job.applications_count || 0;
            const isExpired = job.deadline && new Date(job.deadline) < new Date();
            const statusLabel = isExpired ? 'Expired' : (job.status === 'open' ? 'Open' : 'Closed');
            const statusColor = isExpired ? '#e74c3c' : (job.status === 'open' ? '#27ae60' : '#95a5a6');
            const statusBg = isDarkMode 
                ? (isExpired ? 'rgba(231, 76, 60, 0.15)' : (job.status === 'open' ? 'rgba(39, 174, 96, 0.15)' : 'rgba(149, 165, 166, 0.15)'))
                : (isExpired ? '#ffebee' : (job.status === 'open' ? '#e8f5e9' : '#eceff1'));

            return `
                <div style="background:${cardBg}; border-radius:8px; overflow:hidden; margin-bottom:16px; box-shadow:0 2px 8px rgba(0,0,0,${isDarkMode ? '0.3' : '0.08'}); border-left:5px solid ${statusColor};">
                    <div style="display:flex; justify-content:space-between; align-items:start; padding:16px 20px; background:${statusBg}; border-bottom:1px solid rgba(0,0,0,${isDarkMode ? '0.2' : '0.05'});">
                        <div style="flex:1;">
                            <h4 style="margin:0 0 4px 0; color:${textPrimary}; font-size:15px; font-weight:700;">${escapeHtml(job.title)}</h4>
                            <p style="margin:0; color:${textSecondary}; font-size:12px;">
                                <i class="fas fa-map-marker-alt" style="margin-right:4px;"></i>${escapeHtml(job.location)}
                            </p>
                        </div>
                        <span style="background:${statusColor}; color:white; padding:6px 14px; border-radius:20px; font-size:11px; font-weight:600; white-space:nowrap; flex-shrink:0; margin-left:12px;">${statusLabel}</span>
                    </div>
                    <div style="padding:14px 20px; display:flex; gap:10px;">
                        <button onclick="JobsModule.toggleJobStatus(${job.id})" style="flex:1; padding:8px 12px; background:linear-gradient(135deg, #f39c12 0%, #e67e22 100%); color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:12px; transition:all 0.2s ease;">
                            ${job.status === 'open' ? '<i class="fas fa-lock"></i> Close Job' : '<i class="fas fa-unlock"></i> Reopen'}
                        </button>
                        <button onclick="JobsModule.viewApplications(${job.id})" style="flex:1; padding:8px 12px; background:linear-gradient(135deg, #3498db 0%, #2980b9 100%); color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:12px; transition:all 0.2s ease;">
                            <i class="fas fa-file-alt"></i> ${applicationsCount === 1 ? '1 App' : applicationsCount + ' Apps'}
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
        viewApplications,
        loadMyApplications
    };
})();

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', JobsModule.init);
} else {
    JobsModule.init();
}

// Expose loadMyApplications globally for dashboard.blade.php
// Simply delegates to the module's internal loadMyApplications function
window.loadMyApplications = () => JobsModule.loadMyApplications ? JobsModule.loadMyApplications() : Promise.resolve([]);
