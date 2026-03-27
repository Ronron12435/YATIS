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
        const coverLetter = prompt('Enter your cover letter (optional):');
        if (coverLetter === null) return;

        fetch(`/api/jobs/${jobId}/apply`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'X-CSRF-Token': CSRF_TOKEN,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                cover_letter: coverLetter || null
            })
        })
        .then(r => r.json())
        .then(response => {
            if (response.success) {
                alert('Application submitted successfully!');
                loadStats();
                showBrowseJobs();
            } else {
                alert('Error: ' + (response.message || 'Failed to apply'));
            }
        })
        .catch(err => {
            console.error('Error applying for job:', err);
            alert('Error submitting application');
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
