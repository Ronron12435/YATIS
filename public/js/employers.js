const EmployersModule = (() => {
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let allJobs = [];

    const init = () => {
        loadStats();
        loadJobListings();
    };

    const loadStats = () => {
        fetch('/api/jobs', { credentials: 'include' })
            .then(r => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then(response => {
                let jobs = response.data || [];
                if (!Array.isArray(jobs)) {
                    jobs = [];
                }
                allJobs = jobs;
                const openCount = jobs.filter(j => j.status === 'open').length;
                const uniqueEmployers = new Set(jobs.map(j => j.employer_id)).size;
                
                const jobsEl = document.getElementById('emp-jobs-count');
                const employersEl = document.getElementById('emp-employers-count');
                
                if (jobsEl) jobsEl.textContent = openCount;
                if (employersEl) employersEl.textContent = uniqueEmployers;
            })
            .catch(err => console.error('Error loading jobs stats:', err));

        fetch('/api/jobs/applications/my-applications', { credentials: 'include' })
            .then(r => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then(response => {
                let applications = response.data || [];
                if (!Array.isArray(applications)) {
                    applications = [];
                }
                const appsEl = document.getElementById('emp-apps-count');
                if (appsEl) appsEl.textContent = applications.length;
            })
            .catch(err => console.error('Error loading applications stats:', err));
    };

    const loadJobListings = () => {
        fetch('/api/jobs', { credentials: 'include' })
            .then(r => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then(response => {
                let jobs = response.data || [];
                if (!Array.isArray(jobs)) {
                    jobs = [];
                }
                allJobs = jobs;
                displayJobListings(jobs);
                setupSearchFilter();
            })
            .catch(err => {
                console.error('Error loading job listings:', err);
                const listEl = document.getElementById('jl-jobs-list');
                if (listEl) listEl.innerHTML = '<p style="color:#e74c3c; padding:20px; text-align:center;">Error loading jobs</p>';
            });
    };

    const displayJobListings = (jobs) => {
        const listEl = document.getElementById('jl-jobs-list');
        if (!listEl) return;

        if (!jobs || jobs.length === 0) {
            listEl.innerHTML = '<p style="color:#999; padding:20px; text-align:center;">No jobs available</p>';
            return;
        }

        listEl.innerHTML = jobs.map(job => {
            const statusBadge = job.status === 'open' 
                ? '<span class="badge-status badge-job-open">OPEN</span>'
                : '<span class="badge-status" style="background:#95a5a6; color:white;">CLOSED</span>';
            
            return `
                <div class="job-card">
                    <div class="job-header">
                        <div>
                            <h4 style="margin:0 0 5px 0;">${escapeHtml(job.title)}</h4>
                            <p style="margin:0; color:#666; font-size:13px;">
                                <i class="fas fa-building"></i> ${escapeHtml(job.employer?.first_name || 'Unknown')} ${escapeHtml(job.employer?.last_name || '')}
                            </p>
                        </div>
                        ${statusBadge}
                    </div>
                    <div class="job-meta">
                        <span><i class="fas fa-map-marker-alt"></i> ${escapeHtml(job.location)}</span>
                        <span><i class="fas fa-briefcase"></i> ${escapeHtml(job.job_type)}</span>
                        ${job.salary_range ? `<span><i class="fas fa-money-bill"></i> ${escapeHtml(job.salary_range)}</span>` : ''}
                    </div>
                    <p style="color:#666; margin:10px 0; line-height:1.5;">
                        ${escapeHtml(job.description).substring(0, 150)}${job.description.length > 150 ? '...' : ''}
                    </p>
                    ${job.status === 'open' ? `
                        <button onclick="EmployersModule.applyForJob(${job.id})" style="padding:10px 20px; background:#3498db; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; width:100%; margin-top:10px;">
                            <i class="fas fa-paper-plane"></i> Apply Now
                        </button>
                    ` : ''}
                </div>
            `;
        }).join('');
    };

    const setupSearchFilter = () => {
        const searchEl = document.getElementById('jl-search');
        if (!searchEl) return;

        searchEl.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const filtered = allJobs.filter(job => 
                job.title.toLowerCase().includes(query) ||
                job.location.toLowerCase().includes(query) ||
                (job.employer?.first_name || '').toLowerCase().includes(query) ||
                (job.description || '').toLowerCase().includes(query)
            );
            displayJobListings(filtered);
        });
    };

    const showBrowseJobs = () => {
        const content = document.getElementById('jl-detail-content');
        
        let jobs = allJobs;
        if (!Array.isArray(jobs)) {
            jobs = [];
        }
        
        if (!jobs || jobs.length === 0) {
            content.innerHTML = '<p style="color:#999; text-align:center; padding:40px;">No jobs available</p>';
            document.getElementById('jl-detail-panel').style.display = 'block';
            return;
        }

        content.innerHTML = '<h2 style="margin-bottom:20px;">Available Jobs</h2>' + 
            jobs.map(job => {
                const statusBadge = job.status === 'open' 
                    ? '<span class="badge-status badge-job-open">OPEN</span>'
                    : '<span class="badge-status" style="background:#95a5a6; color:white;">CLOSED</span>';
                
                return `
                    <div class="job-card">
                        <div class="job-header">
                            <h4>${escapeHtml(job.title)}</h4>
                            ${statusBadge}
                        </div>
                        <div class="job-meta">
                            <span><i class="fas fa-user"></i> ${escapeHtml(job.employer?.first_name || 'Unknown')}</span>
                            <span><i class="fas fa-map-marker-alt"></i> ${escapeHtml(job.location)}</span>
                            <span><i class="fas fa-briefcase"></i> ${escapeHtml(job.job_type)}</span>
                            ${job.salary_range ? `<span><i class="fas fa-money-bill"></i> ${escapeHtml(job.salary_range)}</span>` : ''}
                        </div>
                        <p style="color:#666; margin:10px 0; line-height:1.5;">
                            ${escapeHtml(job.description).substring(0, 200)}${job.description.length > 200 ? '...' : ''}
                        </p>
                        ${job.status === 'open' ? `
                            <button onclick="EmployersModule.applyForJob(${job.id})" style="padding:10px 20px; background:#3498db; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; width:100%;">
                                <i class="fas fa-paper-plane"></i> Apply Now
                            </button>
                        ` : ''}
                    </div>
                `;
            }).join('');

        document.getElementById('jl-detail-panel').style.display = 'block';
    };

    const showMyApplications = () => {
        const content = document.getElementById('jl-detail-content');
        
        fetch('/api/jobs/applications/my-applications', { credentials: 'include' })
            .then(r => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then(response => {
                const applications = response.data || [];
                
                if (applications.length === 0) {
                    content.innerHTML = `
                        <h2 style="margin-bottom:20px;">My Applications</h2>
                        <div style="text-align:center; padding:40px; color:#999;">
                            <i class="fas fa-file-alt" style="font-size:48px; opacity:0.3; display:block; margin-bottom:15px;"></i>
                            <p>No applications yet. Browse jobs and apply!</p>
                        </div>
                    `;
                } else {
                    content.innerHTML = '<h2 style="margin-bottom:20px;">My Applications</h2>' + 
                        applications.map(app => {
                            const statusColors = {
                                'pending': { bg: '#fff3e0', color: '#e65100', label: 'PENDING' },
                                'reviewed': { bg: '#e3f2fd', color: '#1565c0', label: 'REVIEWING' },
                                'accepted': { bg: '#e8f5e9', color: '#2e7d32', label: 'HIRED' },
                                'rejected': { bg: '#ffebee', color: '#c62828', label: 'REJECTED' }
                            };
                            const status = statusColors[app.status] || statusColors['pending'];
                            const appliedDate = new Date(app.applied_at).toLocaleDateString();

                            return `
                                <div class="ma-card">
                                    <div class="ma-card-header">
                                        <h4>${escapeHtml(app.job?.title || 'Job')}</h4>
                                        <span class="badge-status" style="background:${status.bg}; color:${status.color};">
                                            ${status.label}
                                        </span>
                                    </div>
                                    ${app.job?.employer ? `
                                        <div class="ma-business-row">
                                            <i class="fas fa-user"></i>
                                            <span>${escapeHtml(app.job.employer.first_name)} ${escapeHtml(app.job.employer.last_name)}</span>
                                        </div>
                                    ` : ''}
                                    <div class="ma-meta-row">
                                        <span><i class="fas fa-map-marker-alt"></i> ${escapeHtml(app.job?.location || 'Unknown')}</span>
                                        <span><i class="fas fa-calendar"></i> Applied: ${appliedDate}</span>
                                    </div>
                                    ${app.interview_date ? `
                                        <div class="ma-interview-row">
                                            <i class="fas fa-calendar-check"></i> 
                                            Interview: ${new Date(app.interview_date).toLocaleDateString()}
                                        </div>
                                    ` : ''}
                                    ${app.status === 'accepted' ? `
                                        <div class="ma-hired-row">
                                            <i class="fas fa-trophy"></i> 
                                            Congratulations! You've been hired!
                                        </div>
                                    ` : ''}
                                </div>
                            `;
                        }).join('');
                }

                document.getElementById('jl-detail-panel').style.display = 'block';
            })
            .catch(err => {
                console.error('Error loading applications:', err);
                content.innerHTML = '<p style="color:#e74c3c;">Error loading applications</p>';
                document.getElementById('jl-detail-panel').style.display = 'block';
            });
    };

    const showActiveEmployers = () => {
        const content = document.getElementById('jl-employers-content');
        
        let jobs = allJobs;
        if (!Array.isArray(jobs)) {
            jobs = [];
        }
        
        if (!jobs || jobs.length === 0) {
            content.innerHTML = '<p style="color:#999; text-align:center; padding:40px;">No employers found</p>';
            document.getElementById('jl-employers-panel').style.display = 'block';
            return;
        }

        const employersMap = new Map();
        jobs.forEach(job => {
            if (job.employer_id && !employersMap.has(job.employer_id)) {
                employersMap.set(job.employer_id, {
                    id: job.employer_id,
                    name: `${job.employer?.first_name || ''} ${job.employer?.last_name || ''}`.trim(),
                    jobCount: 0
                });
            }
            if (job.employer_id) {
                const emp = employersMap.get(job.employer_id);
                emp.jobCount++;
            }
        });

        const employers = Array.from(employersMap.values());

        content.innerHTML = '<h2 style="margin-bottom:20px;">Active Employers</h2>' + 
            employers.map(emp => `
                <div style="background:#fff; padding:15px; border-radius:8px; margin-bottom:12px; border-left:4px solid #3498db; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <h4 style="margin:0 0 5px 0; color:#333;">${escapeHtml(emp.name)}</h4>
                            <p style="margin:0; color:#666; font-size:13px;">
                                <i class="fas fa-briefcase"></i> ${emp.jobCount} open position${emp.jobCount !== 1 ? 's' : ''}
                            </p>
                        </div>
                        <div style="background:#3498db; color:white; padding:8px 12px; border-radius:6px; font-weight:600; font-size:14px;">
                            ${emp.jobCount}
                        </div>
                    </div>
                </div>
            `).join('');

        document.getElementById('jl-employers-panel').style.display = 'block';
    };

    const applyForJob = (jobId) => {
        // First, load user's applications to check if they've already applied
        fetch('/api/jobs/applications/my-applications', { credentials: 'include' })
            .then(r => r.json())
            .then(response => {
                const applications = response.data || [];
                
                // Check if user has already applied to this job
                const hasApplied = applications.some(app => 
                    app.job_id === jobId || app.job_posting_id === jobId
                );
                
                if (hasApplied) {
                    alert('You have already applied for this position');
                    return;
                }
                
                // Show application form with resume upload
                showApplicationForm(jobId);
            })
            .catch(err => {
                console.error('Error checking applications:', err);
                alert('Error checking your applications');
            });
    };

    const showApplicationForm = (jobId) => {
        const content = document.getElementById('jl-detail-content');
        
        content.innerHTML = `
            <h2 style="margin-bottom:20px;">Submit Application</h2>
            <form id="app-form" style="display:flex; flex-direction:column; gap:15px;">
                <div>
                    <label style="display:block; margin-bottom:8px; font-weight:600; color:#333;">
                        Upload Resume (PDF, DOC, DOCX) *
                    </label>
                    <input type="file" id="app-resume" accept=".pdf,.doc,.docx" required 
                        style="padding:10px; border:1px solid #ddd; border-radius:6px; width:100%;">
                    <p style="font-size:12px; color:#999; margin-top:5px;">Maximum file size: 5MB</p>
                </div>
                
                <div>
                    <label style="display:block; margin-bottom:8px; font-weight:600; color:#333;">
                        Cover Letter (Optional)
                    </label>
                    <textarea id="app-cover-letter" placeholder="Tell us why you're a great fit for this position..."
                        style="padding:10px; border:1px solid #ddd; border-radius:6px; width:100%; min-height:120px; font-family:inherit;">
                    </textarea>
                </div>
                
                <div style="display:flex; gap:10px;">
                    <button type="submit" style="flex:1; padding:12px; background:#3498db; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">
                        <i class="fas fa-paper-plane"></i> Submit Application
                    </button>
                    <button type="button" onclick="document.getElementById('jl-detail-panel').style.display='none'" 
                        style="flex:1; padding:12px; background:#95a5a6; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">
                        Cancel
                    </button>
                </div>
            </form>
        `;
        
        document.getElementById('jl-detail-panel').style.display = 'block';
        
        document.getElementById('app-form').addEventListener('submit', (e) => {
            e.preventDefault();
            submitApplication(jobId);
        });
    };

    const submitApplication = (jobId) => {
        const resumeInput = document.getElementById('app-resume');
        const coverLetterInput = document.getElementById('app-cover-letter');
        
        console.log('=== EMPLOYERS SUBMIT APPLICATION DEBUG ===');
        console.log('Job ID:', jobId);
        console.log('Resume files:', resumeInput.files);
        console.log('Resume file count:', resumeInput.files.length);
        
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
        
        console.log('Sending POST to:', `/api/jobs/${jobId}/apply`);
        
        fetch(`/api/jobs/${jobId}/apply`, {
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
            if (response.success) {
                alert('Application submitted successfully!');
                document.getElementById('jl-detail-panel').style.display = 'none';
                loadStats();
                loadJobListings();
            } else {
                alert('Error: ' + (response.message || 'Failed to apply'));
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            alert('Error submitting application: ' + err.message);
        });
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
        applyForJob
    };
})();

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', EmployersModule.init);
} else {
    EmployersModule.init();
}
