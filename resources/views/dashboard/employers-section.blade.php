{{-- Employers Overview --}}
<div id="employers" class="content-section">
    <h1 class="page-title"><i class="fas fa-briefcase"></i> Employers & Job Opportunities</h1>
    
    <!-- Stats Cards -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:20px; margin-bottom:30px;">
        <div style="background:linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color:white; padding:25px; border-radius:12px; box-shadow:0 4px 15px rgba(30,60,114,0.3);">
            <div style="font-size:36px; font-weight:700; color:#00bcd4; margin-bottom:8px;" id="emp-jobs-count">0</div>
            <div style="font-size:16px; font-weight:600;">Open Positions</div>
        </div>
        <div style="background:linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color:white; padding:25px; border-radius:12px; box-shadow:0 4px 15px rgba(30,60,114,0.3);">
            <div style="font-size:36px; font-weight:700; color:#00bcd4; margin-bottom:8px;" id="emp-apps-count">0</div>
            <div style="font-size:16px; font-weight:600;">Pending Applications</div>
        </div>
    </div>

    <!-- Find Your Next Career Card -->
    <div class="card" style="border-top:4px solid #3498db; margin-bottom:30px;">
        <h3 style="margin-bottom:10px;"><i class="fas fa-briefcase"></i> Find Your Next Career</h3>
        <p style="color:#666; margin-bottom:20px;">Browse job postings from employers and apply with your resume.</p>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <button onclick="showSection('job-listings');" style="padding:12px 24px; background:#3498db; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-list"></i> Browse Jobs
            </button>
            <button onclick="showSection('my-applications');" style="padding:12px 24px; background:#95a5a6; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-file-alt"></i> My Applications
            </button>
        </div>
    </div>
</div>
<div id="job-listings" class="content-section">
    <h1 class="page-title">📋 Job Listings</h1>
    <div id="jl-jobs-list"><p style="color:#999;padding:20px;text-align:center;">Loading job postings...</p></div>
</div>
<div id="my-applications" class="content-section">
    <h1 class="page-title"><i class="fas fa-file-alt"></i> My Applications</h1>
    <div class="card" style="margin-bottom:20px;border-top:3px solid #00bcd4;">
        <h3>Application Status</h3>
        <p style="color:#666;">Track your job applications and their current status.</p>
    </div>
    <div id="jl-apps-list"><p style="color:#999;padding:20px;">Loading your applications...</p></div>
</div>
<div id="jl-detail-panel" style="display:none; position:fixed; top:0; right:0; width:480px; height:100vh; background:#fff; box-shadow:-4px 0 20px rgba(0,0,0,.15); z-index:9999; overflow-y:auto; padding:24px;">
    <button onclick="document.getElementById('jl-detail-panel').style.display='none'" style="position:absolute; top:16px; right:16px; background:none; border:none; font-size:22px; cursor:pointer; color:#666;">✕</button>
    <div id="jl-detail-content"></div>
</div>

<div id="jl-employers-panel" style="display:none; position:fixed; top:0; right:0; width:440px; height:100vh; background:#fff; box-shadow:-4px 0 20px rgba(0,0,0,.15); z-index:9999; overflow-y:auto; padding:24px;">
    <button onclick="document.getElementById('jl-employers-panel').style.display='none'" style="position:absolute; top:16px; right:16px; background:none; border:none; font-size:22px; cursor:pointer; color:#666;">✕</button>
    <div id="jl-employers-content"></div>
</div>

<style>
.job-card { background:#fff; padding:20px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,.1); margin-bottom:15px; }
.job-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px; }
.job-header h4 { color:#667eea; margin:0; font-size:17px; }
.job-meta { display:flex; flex-wrap:wrap; gap:10px; margin:10px 0; font-size:13px; color:#666; }
.job-meta span { background:#f0f4ff; padding:4px 10px; border-radius:20px; }
.badge-status { padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; white-space:nowrap; }
.badge-job-open { background:#e8f5e9; color:#2e7d32; }
.badge-pending { background:#fff3e0; color:#e65100; }
.badge-accepted { background:#e8f5e9; color:#2e7d32; }
.badge-rejected { background:#ffebee; color:#c62828; }
.badge-reviewed { background:#e3f2fd; color:#1565c0; }
.ma-card { background:#fff; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,.08); margin-bottom:20px; overflow:hidden; }
.ma-card-header { display:flex; justify-content:space-between; align-items:center; padding:16px 20px 0; }
.ma-card-header h4 { color:#1a3a52; font-size:17px; margin:0; }
.ma-business-row { display:flex; align-items:center; gap:8px; padding:10px 20px; background:linear-gradient(135deg,#e3f2fd,#bbdefb); border-left:4px solid #2196F3; margin:10px 0 0; }
.ma-business-row span { font-weight:600; color:#1976D2; font-size:14px; }
.ma-meta-row { display:flex; align-items:center; gap:18px; padding:8px 20px; font-size:13px; color:#666; }
.ma-interview-row { display:flex; align-items:center; gap:8px; padding:8px 20px; background:#e3f2fd; border-left:4px solid #2196F3; font-size:13px; color:#1565c0; font-weight:600; }
.ma-hired-row { display:flex; align-items:center; gap:8px; padding:8px 20px; background:#e8f5e9; border-left:4px solid #4caf50; font-size:13px; color:#2e7d32; font-weight:600; }
.ma-card-footer { padding:14px 20px; }
.badge-hired { background:#4caf50; color:#fff; padding:4px 14px; border-radius:20px; font-size:11px; font-weight:700; }
.badge-reviewing { background:#00bcd4; color:#fff; padding:4px 14px; border-radius:20px; font-size:11px; font-weight:700; }
</style>
