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
        
        // Only load applications for non-business users
        if (userRole !== 'business') {
            loadMyApplications().then(() => {
                console.log('Applications loaded:', myApplications);
                setTimeout(() => {
                    loadJobs();
                    loadMyJobs();
                }, 100);
            });
        } else {
            // For business users, just load their jobs
            setTimeout(() => {
                loadMyJobs();
            }, 100);
        }
        
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
        // Only load jobs for non-business users
        if (userRole === 'business') {
            return;
        }

        fetch('/api/jobs', { credentials: 'include' })
            .then(r => r.json())
            .then(response => {
                if (response.success && Array.isArray(response.data)) {
                    allJobs = response.data;
                } else if (Array.isArray(response.data)) {
                    allJobs = response.data;
                } else if (Array.isArray(response)) {
                    allJobs = response;
                } else {
                    allJobs = [];
                }
                renderJobs(allJobs);
            })
            .catch(err => {
                console.error('Error loading jobs:', err);
                allJobs = [];
                const jobsList = document.getElementById('jobs-list');
                if (jobsList) {
                    jobsList.innerHTML = '<p style="color:#e74c3c;">Error loading jobs</p>';
                }
            });
    };

    const renderJobs = (jobs) => {
        const jobsList = document.getElementById('jobs-list');
        
        // If element doesn't exist, skip rendering (e.g., business account view)
        if (!jobsList) {
            return;
        }

        if (!Array.isArray(jobs) || jobs.length === 0) {
            jobsList.innerHTML = '<p style="color:#999; text-align:center; padding:20px;">No jobs available</p>';
            return;
        }

        jobsList.innerHTML = jobs.map(job => {
            const postedDate = new Date(job.created_at).toLocaleDateString();
            const statusBadge = job.status === 'open' 
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
        
        let job = allJobs.find(j => j.id === jobId);
        
        if (!job) {
            fetch(`/api/jobs/${jobId}`, { credentials: 'include' })
                .then(r => r.json())
                .then(response => {
                    job = response.data || response;
                    if (job) {
                        populateJobDetails(job);
                        // Always reload applications to ensure fresh data
                        loadMyApplications().then(() => {
                            checkAndUpdateApplyButton(jobId);
                            openModal('job-details-modal');
                        });
                    }
                })
                .catch(err => console.error('Error loading job:', err));
            return;
        }

        populateJobDetails(job);
        // Always reload applications to ensure fresh data
        loadMyApplications().then(() => {
            checkAndUpdateApplyButton(jobId);
            openModal('job-details-modal');
        });
    };

    const checkAndUpdateApplyButton = (jobId) => {
        const applyBtn = document.getElementById('apply-button');
        if (!applyBtn) {
            console.error('Apply button not found');
            return;
        }
        
        console.log('Checking button for job:', jobId);
        console.log('My applications:', myApplications);
        
        const hasApplied = myApplications.some(app => {
            console.log('Comparing app.job_id:', app.job_id, 'with jobId:', jobId);
            return app.job_id === jobId || app.job?.id === jobId;
        });
        
        console.log('Has applied:', hasApplied);
        
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

    const populateJobDetails = (job) => {
        document.getElementById('detail-job-title').textContent = escapeHtml(job.title);
        document.getElementById('detail-job-type').textContent = escapeHtml(job.job_type);
        document.getElementById('detail-job-salary').textContent = job.salary_range || 'Not specified';
        document.getElementById('detail-job-location').textContent = escapeHtml(job.location);
        document.getElementById('detail-job-description').textContent = escapeHtml(job.description);
        document.getElementById('detail-job-requirements').textContent = escapeHtml(job.requirements || 'No specific requirements');
    };

    const openApplyModal = () => {
        if (!currentJobId) return;
        
        console.log('openApplyModal - currentJobId:', currentJobId);
        console.log('openApplyModal - myApplications:', myApplications);
        
        const hasApplied = myApplications.some(app => {
            console.log('Checking app:', app, 'against jobId:', currentJobId);
            return app.job_id === currentJobId || app.job_posting_id === currentJobId;
        });
        
        console.log('openApplyModal - hasApplied:', hasApplied);
        
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

        console.log('=== SUBMIT APPLICATION DEBUG ===');
        console.log('Resume files:', resumeInput.files);
        console.log('Resume file count:', resumeInput.files.length);
        console.log('Current Job ID:', currentJobId);

        if (!resumeInput.files.length) {
            alert('Please upload a resume');
            return;
        }

        const formData = new FormData();
        formData.append('resume', resumeInput.files[0]);
        formData.append('cover_letter', coverLetterInput.value);

        console.log('FormData entries:');
        for (let [key, value] of formData.entries()) {
            console.log(`  ${key}:`, value);
        }

        const submitBtn = document.querySelector('#application-form button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';

        console.log('Sending POST to:', `/api/jobs/${currentJobId}/apply`);

        fetch(`/api/jobs/${currentJobId}/apply`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'X-CSRF-Token': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(r => {
            console.log('Response status:', r.status);
            console.log('Response headers:', r.headers);
            return r.json();
        })
        .then(response => {
            console.log('API Response:', response);
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Submit Application';

            if (response.success) {
                alert('Application submitted successfully!');
                closeModals();
                // Reload applications to update the list
                loadMyApplications().then(() => {
                    console.log('Applications reloaded after submission');
                });
            } else {
                alert('Error: ' + (response.message || 'Failed to submit application'));
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Submit Application';
            alert('Error submitting application: ' + err.message);
        });
    };

    const loadMyApplications = () => {
        return fetch('/api/jobs/applications/my-applications', { credentials: 'include' })
            .then(r => r.json())
            .then(response => {
                console.log('API Response:', response);
                let applications = [];
                if (response.success && Array.isArray(response.data)) {
                    applications = response.data;
                } else if (Array.isArray(response.data)) {
                    applications = response.data;
                } else if (Array.isArray(response)) {
                    applications = response;
                }
                // Store applications FIRST before rendering
                myApplications = applications;
                console.log('Loaded and stored myApplications:', myApplications);
                renderMyApplications(applications);
                return applications;
            })
            .catch(err => {
                console.error('Error loading applications:', err);
                const appsList = document.getElementById('my-applications');
                if (appsList) {
                    appsList.innerHTML = '<p style="color:#e74c3c;">Error loading applications</p>';
                }
                return [];
            });
    };

    const renderMyApplications = (applications) => {
        const appsList = document.getElementById('my-applications');

        if (!appsList) {
            console.warn('my-applications element not found');
            return;
        }

        console.log('Rendering applications:', applications);

        if (!Array.isArray(applications) || applications.length === 0) {
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
            return;
        }

        fetch('/api/my-jobs', { credentials: 'include' })
            .then(r => r.json())
            .then(response => {
                let jobs = [];
                if (response.success && Array.isArray(response.data)) {
                    jobs = response.data;
                } else if (Array.isArray(response.data)) {
                    jobs = response.data;
                } else if (Array.isArray(response)) {
                    jobs = response;
                }
                renderMyJobs(jobs);
            })
            .catch(err => {
                console.error('Error loading my jobs:', err);
                const myJobsList = document.getElementById('my-jobs');
                if (myJobsList) {
                    myJobsList.innerHTML = '<p style="color:#e74c3c;">Error loading jobs</p>';
                }
            });
    };

    const renderMyJobs = (jobs) => {
        const myJobsList = document.getElementById('my-jobs');
        
        if (!myJobsList) return;

        if (!Array.isArray(jobs) || jobs.length === 0) {
            myJobsList.innerHTML = '<p style="color:#999; text-align:center; padding:20px;">No job postings yet</p>';
            return;
        }

        myJobsList.innerHTML = jobs.map(job => {
            const statusBadge = job.status === 'open' 
                ? '<span style="background:#27ae60; color:white; padding:4px 8px; border-radius:4px; font-size:12px;">Open</span>'
                : '<span style="background:#95a5a6; color:white; padding:4px 8px; border-radius:4px; font-size:12px;">Closed</span>';

            // Show notification badge if there are pending applications
            const pendingBadge = job.applications_count && job.applications_count > 0
                ? `<span style="background:#ff5252; color:white; padding:6px 10px; border-radius:50%; font-size:12px; font-weight:bold; min-width:28px; text-align:center; display:inline-flex; align-items:center; justify-content:center; margin-left:8px;">${job.applications_count}</span>`
                : '';

            return `
                <div style="border:1px solid #ddd; border-radius:8px; padding:15px; background:white;">
                    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                        <div>
                            <div style="display:flex; align-items:center;">
                                <h4 style="margin:0 0 5px 0; color:#333;">${escapeHtml(job.title)}</h4>
                                ${pendingBadge}
                            </div>
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
                            <i class="fas fa-file-alt"></i> View Applications
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
            .then(r => r.json())
            .then(response => {
                let applications = [];
                if (response.success && Array.isArray(response.data)) {
                    applications = response.data;
                } else if (Array.isArray(response.data)) {
                    applications = response.data;
                } else if (Array.isArray(response)) {
                    applications = response;
                }
                
                if (applications.length === 0) {
                    alert('No applications yet');
                    return;
                }

                // Create applications list HTML with action buttons
                const appListHTML = applications.map(app => {
                    const statusColors = {
                        'pending': '#f39c12',
                        'reviewed': '#3498db',
                        'accepted': '#27ae60',
                        'rejected': '#e74c3c'
                    };
                    const statusColor = statusColors[app.status] || '#95a5a6';
                    
                    // Format interview date and time if available
                    let interviewDisplay = '';
                    const hasInterview = app.interview_date;
                    if (hasInterview) {
                        const interviewDateTime = new Date(app.interview_date);
                        const interviewDate = interviewDateTime.toLocaleDateString();
                        const interviewTime = interviewDateTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        interviewDisplay = `<div style="font-size:12px; color:#27ae60; margin-bottom:10px; font-weight:600;">
                            <i class="fas fa-calendar"></i> Interview: ${interviewDate} at ${interviewTime}
                        </div>`;
                    }
                    
                    // Disable buttons if interview is already scheduled
                    const acceptDisabled = hasInterview ? 'disabled' : '';
                    const rejectDisabled = hasInterview ? 'disabled' : '';
                    const acceptOpacity = hasInterview ? '0.5' : '1';
                    const rejectOpacity = hasInterview ? '0.5' : '1';
                    const acceptCursor = hasInterview ? 'not-allowed' : 'pointer';
                    const rejectCursor = hasInterview ? 'not-allowed' : 'pointer';
                    
                    return `
                        <div style="background:#f9f9f9; padding:15px; border-radius:6px; margin-bottom:15px; border-left:4px solid ${statusColor};">
                            <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                                <div>
                                    <div style="font-weight:600; color:#333;">${escapeHtml(app.first_name)} ${escapeHtml(app.last_name)}</div>
                                    <div style="font-size:13px; color:#666; margin-top:4px;">
                                        <i class="fas fa-envelope"></i> ${escapeHtml(app.email)}
                                    </div>
                                </div>
                                <span style="background:${statusColor}; color:white; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600;">${escapeHtml(app.status)}</span>
                            </div>
                            
                            <div style="font-size:12px; color:#999; margin-bottom:10px;">
                                Applied: ${new Date(app.applied_at).toLocaleDateString()}
                            </div>
                            
                            ${interviewDisplay}
                            
                            ${!hasInterview ? `
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:10px;">
                                    <input type="date" id="interview-date-${app.id}" style="padding:6px; border:1px solid #ddd; border-radius:4px; font-size:12px;" placeholder="Interview date">
                                    <input type="time" id="interview-time-${app.id}" style="padding:6px; border:1px solid #ddd; border-radius:4px; font-size:12px;" placeholder="Interview time">
                                </div>
                            ` : ''}
                            
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                                <button onclick="JobsModule.updateApplicationStatus(${app.id}, 'accepted')" ${acceptDisabled} style="padding:8px 12px; background:#27ae60; color:white; border:none; border-radius:4px; cursor:${acceptCursor}; font-size:12px; font-weight:600; opacity:${acceptOpacity};">
                                    <i class="fas fa-check"></i> Accept
                                </button>
                                <button onclick="JobsModule.updateApplicationStatus(${app.id}, 'rejected')" ${rejectDisabled} style="padding:8px 12px; background:#e74c3c; color:white; border:none; border-radius:4px; cursor:${rejectCursor}; font-size:12px; font-weight:600; opacity:${rejectOpacity};">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </div>
                        </div>
                    `;
                }).join('');

                // Show modal with applications
                const modal = document.createElement('div');
                modal.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:10000; width:90%; max-width:700px; max-height:80vh; overflow-y:auto;';
                modal.innerHTML = `
                    <div style="padding:20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; background:white;">
                        <h2 style="margin:0; font-size:20px; color:#333;"><i class="fas fa-file-alt"></i> Job Applications (${applications.length})</h2>
                        <button onclick="this.closest('div').parentElement.remove(); document.getElementById('app-modal-overlay').remove();" style="background:none; border:none; font-size:24px; color:#999; cursor:pointer;">&times;</button>
                    </div>
                    <div style="padding:20px;">
                        ${appListHTML}
                    </div>
                `;

                // Create overlay
                const overlay = document.createElement('div');
                overlay.id = 'app-modal-overlay';
                overlay.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:9999;';
                overlay.onclick = () => {
                    modal.remove();
                    overlay.remove();
                };

                document.body.appendChild(overlay);
                document.body.appendChild(modal);
            })
            .catch(err => {
                console.error('Error loading applications:', err);
                alert('Error loading applications');
            });
    };

    const showModal = (title, message, buttons = []) => {
        const modal = document.createElement('div');
        modal.id = 'custom-modal';
        modal.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:10001; padding:30px; max-width:400px; text-align:center;';
        
        let buttonsHTML = '';
        if (buttons.length > 0) {
            buttonsHTML = `<div style="display:flex; gap:10px; margin-top:20px; justify-content:center;">
                ${buttons.map(btn => `<button onclick="${btn.onclick}" style="padding:10px 20px; background:${btn.color}; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">${btn.text}</button>`).join('')}
            </div>`;
        } else {
            buttonsHTML = `<button onclick="document.getElementById('custom-modal').remove(); document.getElementById('custom-modal-overlay').remove();" style="padding:10px 20px; background:#3498db; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; margin-top:20px;">OK</button>`;
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
    };

    const scheduleInterview = (appId, interviewDate, interviewTime) => {
        if (!interviewDate) {
            showModal('Missing Date', 'Please select an interview date');
            return;
        }

        if (!interviewTime) {
            showModal('Missing Time', 'Please select an interview time');
            return;
        }

        // Combine date and time into datetime format
        const interviewDateTime = `${interviewDate} ${interviewTime}`;

        fetch(`/api/jobs/applications/${appId}/interview`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'X-CSRF-Token': CSRF_TOKEN,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ interview_date: interviewDateTime })
        })
        .then(r => r.json())
        .then(response => {
            if (response.success) {
                showModal('Success', 'Interview scheduled successfully!');
                setTimeout(() => {
                    document.getElementById('app-modal-overlay').click();
                }, 1500);
            } else {
                showModal('Error', response.message || 'Failed to schedule interview');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showModal('Error', 'Error scheduling interview');
        });
    };

    const updateApplicationStatus = (appId, status) => {
        // For "accepted" status, require interview date and time to be set first
        if (status === 'accepted') {
            const interviewDateInput = document.getElementById(`interview-date-${appId}`);
            const interviewTimeInput = document.getElementById(`interview-time-${appId}`);
            const interviewDate = interviewDateInput ? interviewDateInput.value : null;
            const interviewTime = interviewTimeInput ? interviewTimeInput.value : null;
            
            if (!interviewDate || !interviewTime) {
                showModal('Missing Information', 'You must set both interview date and time before accepting an applicant');
                return;
            }
            
            // Show confirmation modal
            showModal('Confirm Action', 'Accept this applicant?', [
                {
                    text: 'Yes, Accept',
                    color: '#27ae60',
                    onclick: `JobsModule.confirmAcceptApplicant(${appId}, '${interviewDate}', '${interviewTime}')`
                },
                {
                    text: 'Cancel',
                    color: '#95a5a6',
                    onclick: `document.getElementById('custom-modal-overlay').click()`
                }
            ]);
            return;
        }

        // For reject, show confirmation modal
        if (status === 'rejected') {
            showModal('Confirm Action', 'Reject this applicant?', [
                {
                    text: 'Yes, Reject',
                    color: '#e74c3c',
                    onclick: `JobsModule.confirmRejectApplicant(${appId})`
                },
                {
                    text: 'Cancel',
                    color: '#95a5a6',
                    onclick: `document.getElementById('custom-modal-overlay').click()`
                }
            ]);
        }
    };

    const confirmAcceptApplicant = (appId, interviewDate, interviewTime) => {
        const interviewDateTime = `${interviewDate} ${interviewTime}`;
        
        // Schedule interview and accept in one action
        fetch(`/api/jobs/applications/${appId}/interview`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'X-CSRF-Token': CSRF_TOKEN,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ interview_date: interviewDateTime })
        })
        .then(r => r.json())
        .then(response => {
            if (response.success) {
                // Now update status to accepted
                return fetch(`/api/jobs/applications/${appId}/status`, {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'X-CSRF-Token': CSRF_TOKEN,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: 'accepted' })
                });
            } else {
                throw new Error(response.message || 'Failed to schedule interview');
            }
        })
        .then(r => r.json())
        .then(response => {
            if (response.success) {
                showModal('Success', 'Applicant accepted! Interview scheduled.');
                setTimeout(() => {
                    document.getElementById('app-modal-overlay').click();
                    // Reload jobs to update notification badge
                    loadMyJobs();
                }, 1500);
            } else {
                showModal('Error', response.message || 'Failed to accept applicant');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showModal('Error', err.message || 'Error accepting applicant');
        });
    };

    const confirmRejectApplicant = (appId) => {
        fetch(`/api/jobs/applications/${appId}/status`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'X-CSRF-Token': CSRF_TOKEN,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: 'rejected' })
        })
        .then(r => r.json())
        .then(response => {
            if (response.success) {
                showModal('Success', 'Applicant rejected!');
                setTimeout(() => {
                    document.getElementById('app-modal-overlay').click();
                    // Reload jobs to update notification badge
                    loadMyJobs();
                }, 1500);
            } else {
                showModal('Error', response.message || 'Failed to reject applicant');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showModal('Error', 'Error rejecting applicant');
        });
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
        scheduleInterview,
        updateApplicationStatus,
        confirmAcceptApplicant,
        confirmRejectApplicant
    };
})();

// Global functions for job posting (business accounts)
function loadBusinessesForJobPosting() {
    fetch('/api/user/businesses', { credentials: 'include' })
        .then(r => r.json())
        .then(response => {
            const businesses = response.data || [];
            const selector = document.getElementById('jobBusinessSelector');
            if (!selector) return;
            
            selector.innerHTML = '<option value="">Select a business</option>';
            businesses.forEach(business => {
                const option = document.createElement('option');
                option.value = business.id;
                option.textContent = `${business.name} (${business.category})`;
                option.dataset.address = business.address || '';
                selector.appendChild(option);
            });
            
            // Add change event listener to auto-fill location
            selector.addEventListener('change', function() {
                if (this.value) {
                    const selectedOption = this.options[this.selectedIndex];
                    const address = selectedOption.dataset.address;
                    if (address) {
                        document.getElementById('jobLocation').value = address;
                    }
                }
            });
        })
        .catch(err => console.error('Error loading businesses:', err));
}

function postJob(event) {
    event.preventDefault();
    
    const businessId = document.getElementById('jobBusinessSelector').value;
    const title = document.getElementById('jobTitle').value;
    const jobType = document.getElementById('jobType').value;
    const salaryRange = document.getElementById('salaryRange').value;
    const location = document.getElementById('jobLocation').value;
    const description = document.getElementById('jobDescription').value;
    const requirements = document.getElementById('jobRequirements').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (!businessId) {
        showJobMessage('Please select a business', 'error');
        return;
    }
    
    if (!startDate || !endDate) {
        showJobMessage('Please select start and end dates', 'error');
        return;
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    
    fetch('/api/jobs', {
        method: 'POST',
        credentials: 'include',
        headers: {
            'X-CSRF-Token': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            business_id: businessId,
            title: title,
            job_type: jobType,
            salary_range: salaryRange,
            location: location,
            description: description,
            requirements: requirements,
            status: 'open',
            start_date: startDate,
            end_date: endDate
        })
    })
    .then(r => r.json())
    .then(response => {
        if (response.success) {
            showJobMessage('Job posting created successfully!', 'success');
            clearJobForm();
            setTimeout(() => {
                JobsModule.init();
            }, 1500);
        } else {
            showJobMessage(response.message || 'Failed to post job', 'error');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        showJobMessage('Error posting job', 'error');
    });
}

function clearJobForm() {
    document.getElementById('jobPostingForm').reset();
    document.getElementById('jobBusinessSelector').value = '';
}

function showJobMessage(message, type) {
    const messageDiv = document.getElementById('jobPostMessage');
    if (!messageDiv) return;
    
    messageDiv.innerHTML = `
        <div style="padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; background: ${type === 'success' ? '#c8e6c9' : '#ffcdd2'}; color: ${type === 'success' ? '#2e7d32' : '#c62828'};">
            ${type === 'success' ? '✓' : '✕'} ${message}
        </div>
    `;
    
    if (type === 'success') {
        setTimeout(() => {
            messageDiv.innerHTML = '';
        }, 3000);
    }
}

// Initialize job posting form when jobs section loads
document.addEventListener('DOMContentLoaded', function() {
    const observer = new MutationObserver(function() {
        const section = document.getElementById('jobs');
        if (section && section.classList.contains('active')) {
            loadBusinessesForJobPosting();
        }
    });
    const target = document.getElementById('jobs');
    if (target) observer.observe(target, { attributes: true, attributeFilter: ['class'] });
    
    // Also load on initial page load if jobs section exists
    if (document.getElementById('jobBusinessSelector')) {
        loadBusinessesForJobPosting();
    }
});
