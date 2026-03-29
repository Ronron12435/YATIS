<div id="jobs" class="content-section">
    <style>
        #jobs { background: #f5f5f5; padding: 20px; }
        .jobs-wrapper { max-width: 1200px; margin: 0 auto; }
        .modern-card { background: white; border-radius: 12px; padding: 0; box-shadow: 0 2px 12px rgba(0,0,0,.08); overflow: hidden; margin-bottom: 20px; }
        .modern-card-header { padding: 20px; border-bottom: 1px solid #f0f0f0; background: #fafafa; }
        .card-title-group { display: flex; align-items: center; gap: 12px; }
        .card-icon-modern { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: #e3f2fd; border-radius: 8px; color: #1976d2; }
        .modern-card-header h3 { margin: 0; font-size: 16px; font-weight: 700; color: #1a3a52; }
        .modern-card-body { padding: 20px; }
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
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-row-full { grid-column: 1 / -1; }
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
        }
    </style>

    <div class="jobs-wrapper">
        @php
            $userRole = auth()->user()->role;
            $isBusiness = $userRole === 'business';
        @endphp

        <!-- POST A JOB SECTION (Business Accounts Only) -->
        @if($isBusiness)
        <div class="modern-card">
            <div class="modern-card-header">
                <div class="card-title-group">
                    <div class="card-icon-modern">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    </div>
                    <h3>Post a Job Opening</h3>
                </div>
            </div>
            <div class="modern-card-body">
                <div id="jobPostMessage"></div>

                <!-- Business Selector -->
                <div class="modern-form-group">
                    <label class="modern-label">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
                        Job Store Name
                    </label>
                    <select class="modern-select" id="jobBusinessSelector" required>
                        <option value="">Select a business</option>
                    </select>
                </div>

                <!-- Job Posting Form -->
                <form id="jobPostingForm" class="modern-form" onsubmit="postJob(event)">
                    <div class="form-row">
                        <div class="modern-form-group">
                            <label class="modern-label">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/></svg>
                                Job Title
                            </label>
                            <input type="text" class="modern-input" id="jobTitle" placeholder="e.g., Senior Developer" required>
                        </div>
                        <div class="modern-form-group">
                            <label class="modern-label">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
                                Job Type
                            </label>
                            <select class="modern-select" id="jobType" required>
                                <option value="">Select Job Type</option>
                                <option value="full-time">Full-time</option>
                                <option value="part-time">Part-time</option>
                                <option value="contract">Contract</option>
                                <option value="freelance">Freelance</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="modern-form-group">
                            <label class="modern-label">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
                                Salary Range
                            </label>
                            <input type="text" class="modern-input" id="salaryRange" placeholder="e.g., ₱25,000 - ₱50,000">
                        </div>
                        <div class="modern-form-group">
                            <label class="modern-label">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
                                Location
                            </label>
                            <input type="text" class="modern-input" id="jobLocation" placeholder="e.g., Sagay City, Negros Occidental" required>
                        </div>
                    </div>

                    <div class="modern-form-group">
                        <label class="modern-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/></svg>
                            Job Description
                        </label>
                        <textarea class="modern-textarea" id="jobDescription" placeholder="Describe the job responsibilities, requirements, and benefits..." required></textarea>
                        <span class="input-hint">Provide detailed information about the position</span>
                    </div>

                    <div class="modern-form-group">
                        <label class="modern-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/></svg>
                            Requirements
                        </label>
                        <textarea class="modern-textarea" id="jobRequirements" placeholder="List the qualifications and skills required..."></textarea>
                        <span class="input-hint">Skills, experience, and qualifications needed</span>
                    </div>

                    <div class="form-row">
                        <div class="modern-form-group">
                            <label class="modern-label">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
                                Start Date
                            </label>
                            <input type="date" class="modern-input" id="startDate" required>
                        </div>
                        <div class="modern-form-group">
                            <label class="modern-label">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
                                End Date
                            </label>
                            <input type="date" class="modern-input" id="endDate" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <button type="submit" class="modern-btn modern-btn-primary">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                            Post Job Opening
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif





        <!-- MY JOBS (Business Accounts) -->
        @if($isBusiness)
        <div class="modern-card" style="margin-top:20px;">
            <div class="modern-card-header">
                <div class="card-title-group">
                    <div class="card-icon-modern">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
                    </div>
                    <h3>My Job Postings</h3>
                </div>
            </div>
            <div class="modern-card-body">
                <div id="my-jobs" style="display:grid; gap:15px;">
                    <p style="color:#999; text-align:center; padding:20px;">Loading your jobs...</p>
                </div>
            </div>
        </div>
        @endif

        <!-- MY APPLICATIONS (Regular Users) -->
        @if(!$isBusiness)
        <div class="modern-card" style="margin-top:20px;">
            <div class="modern-card-header">
                <div class="card-title-group">
                    <div class="card-icon-modern">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/></svg>
                    </div>
                    <h3>My Applications</h3>
                </div>
            </div>
            <div class="modern-card-body">
                <div id="my-applications" style="display:grid; gap:15px;">
                    <p style="color:#999; text-align:center; padding:20px;">Loading your applications...</p>
                </div>
            </div>
        </div>
        @endif

        <!-- AVAILABLE JOBS (Regular Users) -->
        @if(!$isBusiness)
        <div class="modern-card" style="margin-top:20px;">
            <div class="modern-card-header">
                <div class="card-title-group">
                    <div class="card-icon-modern">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    </div>
                    <h3>Available Jobs</h3>
                </div>
            </div>
            <div class="modern-card-body">
                <div style="margin-bottom:20px;">
                    <input type="text" id="job-search" placeholder="Search jobs by title or location..." style="width:100%; padding:12px 14px; border:2px solid #e0e0e0; border-radius:8px; font-size:13px; margin-bottom:12px;">
                    <select id="job-type-filter" style="width:100%; padding:12px 14px; border:2px solid #e0e0e0; border-radius:8px; font-size:13px;">
                        <option value="">All Job Types</option>
                        <option value="full-time">Full-time</option>
                        <option value="part-time">Part-time</option>
                        <option value="contract">Contract</option>
                        <option value="freelance">Freelance</option>
                    </select>
                </div>
                <div id="jobs-list" style="display:grid; gap:15px;">
                    <p style="color:#999; text-align:center; padding:20px;">Loading jobs...</p>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="job-modal-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:1000; animation:fadeIn 0.2s ease;"></div>

<!-- Job Openings Modal -->
<div id="job-openings-modal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:1001; width:90%; max-width:600px; max-height:80vh; overflow-y:auto; animation:slideUp 0.3s ease;">
    <div style="padding:20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; background:white;">
        <h2 id="modal-business-name" style="margin:0; font-size:20px; color:#333;"><i class="fas fa-briefcase"></i> Job Openings</h2>
        <button onclick="JobsModule.closeModals()" style="background:none; border:none; font-size:24px; color:#999; cursor:pointer;">&times;</button>
    </div>
    <div id="job-openings-list" style="padding:20px;">
        <p style="color:#999; text-align:center;">Loading jobs...</p>
    </div>
</div>

<!-- Job Details Modal -->
<div id="job-details-modal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:1001; width:90%; max-width:600px; max-height:80vh; overflow-y:auto; animation:slideUp 0.3s ease;">
    <div style="padding:20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; background:white;">
        <h2 id="detail-job-title" style="margin:0; font-size:20px; color:#333;"></h2>
        <button onclick="JobsModule.closeModals()" style="background:none; border:none; font-size:24px; color:#999; cursor:pointer;">&times;</button>
    </div>
    <div style="padding:20px;">
        <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:15px; margin-bottom:20px;">
            <div>
                <div style="color:#999; font-size:12px; margin-bottom:5px;"><i class="fas fa-briefcase"></i> Type</div>
                <div id="detail-job-type" style="font-weight:600; color:#333;"></div>
            </div>
            <div>
                <div style="color:#999; font-size:12px; margin-bottom:5px;"><i class="fas fa-coins"></i> Salary</div>
                <div id="detail-job-salary" style="font-weight:600; color:#333;"></div>
            </div>
            <div>
                <div style="color:#999; font-size:12px; margin-bottom:5px;"><i class="fas fa-map-marker-alt"></i> Location</div>
                <div id="detail-job-location" style="font-weight:600; color:#333;"></div>
            </div>
        </div>

        <div style="margin-bottom:20px;">
            <h3 style="color:#333; margin-bottom:10px;">Job Description</h3>
            <p id="detail-job-description" style="color:#555; line-height:1.6;"></p>
        </div>

        <div style="margin-bottom:20px;">
            <h3 style="color:#333; margin-bottom:10px;">Requirements</h3>
            <p id="detail-job-requirements" style="color:#555; line-height:1.6; padding:12px; background:#f5f5f5; border-left:4px solid #27ae60; border-radius:4px;"></p>
        </div>

        <div style="background:#e8f5e9; padding:12px; border-radius:6px; margin-bottom:20px; border-left:4px solid #27ae60;">
            <p style="margin:0; color:#27ae60;"><i class="fas fa-envelope"></i> Contact: Contact through application</p>
        </div>

        <button id="apply-button" onclick="JobsModule.openApplyModal()" style="width:100%; padding:14px; background:#3498db; color:white; border:none; border-radius:6px; font-weight:600; font-size:16px; cursor:pointer;">
            Apply for this Position
        </button>
    </div>
</div>

<!-- Application Form Modal -->
<div id="application-modal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.3); z-index:1001; width:90%; max-width:600px; max-height:80vh; overflow-y:auto; animation:slideUp 0.3s ease;">
    <div style="padding:20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; background:white;">
        <h2 style="margin:0; font-size:20px; color:#333;"><i class="fas fa-file-alt"></i> Apply for Position</h2>
        <button onclick="JobsModule.closeModals()" style="background:none; border:none; font-size:24px; color:#999; cursor:pointer;">&times;</button>
    </div>
    <form id="application-form" style="padding:20px;">
        <div style="margin-bottom:20px;">
            <label style="display:block; font-weight:600; color:#333; margin-bottom:8px;">Upload Resume (PDF, DOC, DOCX) *</label>
            <input id="resume-input" type="file" accept=".pdf,.doc,.docx" style="display:block; width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
            <p style="font-size:12px; color:#999; margin-top:5px;">Maximum file size: 5MB</p>
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:block; font-weight:600; color:#333; margin-bottom:8px;">Cover Letter (Optional)</label>
            <textarea id="cover-letter-input" placeholder="Tell us why you're a great fit for this position..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:14px; font-family:inherit; resize:vertical; min-height:120px;"></textarea>
        </div>

        <div style="display:flex; gap:10px;">
            <button type="submit" style="flex:1; padding:12px; background:#3498db; color:white; border:none; border-radius:6px; font-weight:600; cursor:pointer;">
                <i class="fas fa-check"></i> Submit Application
            </button>
            <button type="button" onclick="JobsModule.closeModals()" style="flex:1; padding:12px; background:#95a5a6; color:white; border:none; border-radius:6px; font-weight:600; cursor:pointer;">
                Cancel
            </button>
        </div>
    </form>
</div>

<style>
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes slideUp {
    from { transform: translate(-50%, -40%); opacity: 0; }
    to { transform: translate(-50%, -50%); opacity: 1; }
}
</style>
