// Theme Toggle Functionality
function toggleTheme() {
    const body = document.body;
    const themeToggleBtn = document.getElementById('theme-toggle-btn');
    
    // Check current theme
    const isDarkMode = body.classList.contains('dark-mode');
    
    if (isDarkMode) {
        // Switch to light mode
        body.classList.remove('dark-mode');
        body.classList.add('light-mode');
        localStorage.setItem('theme', 'light');
        themeToggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
        applyLightMode();
    } else {
        // Switch to dark mode
        body.classList.remove('light-mode');
        body.classList.add('dark-mode');
        localStorage.setItem('theme', 'dark');
        themeToggleBtn.innerHTML = '<i class="fas fa-moon"></i>';
        applyDarkMode();
    }
}

function applyDarkMode() {
    // Dark mode styles
    const style = document.getElementById('dynamic-theme-styles') || document.createElement('style');
    style.id = 'dynamic-theme-styles';
    style.innerHTML = `
        body.dark-mode {
            background: #0f1419;
        }
        body.dark-mode .navbar {
            background: #1a1f2e;
            color: #e8eaed;
            border-bottom-color: #2a3142;
        }
        body.dark-mode .navbar .user-info {
            color: #b0b3b8;
        }
        body.dark-mode .navbar .user-info strong {
            color: #e8eaed;
        }
        body.dark-mode .content {
            background: #0f1419;
        }
        body.dark-mode .content-section {
            background: #0f1419;
        }
        body.dark-mode .card {
            background: #1a1f2e;
            border-color: #2a3142;
            color: #e8eaed;
        }
        body.dark-mode .card h3 {
            color: #e8eaed;
        }
        body.dark-mode .card p {
            color: #b0b3b8;
        }
        body.dark-mode .page-title {
            color: #00bcd4;
        }
        body.dark-mode .stat-card {
            background: #1a1f2e;
            border-color: #2a3142;
            color: #e8eaed;
        }
        body.dark-mode .stat-card h3 {
            color: #00bcd4;
        }
        body.dark-mode .stat-card p {
            color: #8a8d93;
        }
        body.dark-mode .job-card {
            background: #1a1f2e;
            border-color: #2a3142;
        }
        body.dark-mode .job-header h4 {
            color: #00bcd4;
        }
        body.dark-mode .job-meta {
            color: #8a8d93;
        }
    `;
    if (!document.getElementById('dynamic-theme-styles')) {
        document.head.appendChild(style);
    }
}

function applyLightMode() {
    // Light mode styles
    const style = document.getElementById('dynamic-theme-styles') || document.createElement('style');
    style.id = 'dynamic-theme-styles';
    style.innerHTML = `
        body.light-mode {
            background: #f5f7fa;
        }
        body.light-mode .navbar {
            background: white;
            color: #333;
            border-bottom-color: #e0e0e0;
        }
        body.light-mode .navbar .user-info {
            color: #666;
        }
        body.light-mode .navbar .user-info strong {
            color: #333;
        }
        body.light-mode .content {
            background: #f5f7fa;
        }
        body.light-mode .content-section {
            background: #f5f7fa;
        }
        body.light-mode .card {
            background: white;
            border-color: #e0e0e0;
            color: #333;
        }
        body.light-mode .card h3 {
            color: #1a3a52;
        }
        body.light-mode .card p {
            color: #666;
        }
        body.light-mode .page-title {
            color: #1a3a52;
        }
        body.light-mode .stat-card {
            background: white;
            border-color: #e0e0e0;
            color: #333;
        }
        body.light-mode .stat-card h3 {
            color: #1a3a52;
        }
        body.light-mode .stat-card p {
            color: #666;
        }
        body.light-mode .job-card {
            background: white;
            border-color: #e0e0e0;
        }
        body.light-mode .job-header h4 {
            color: #667eea;
        }
        body.light-mode .job-meta {
            color: #666;
        }
    `;
    if (!document.getElementById('dynamic-theme-styles')) {
        document.head.appendChild(style);
    }
}

// Initialize theme on page load
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme') || 'dark';
    const body = document.body;
    const themeToggleBtn = document.getElementById('theme-toggle-btn');
    
    if (savedTheme === 'light') {
        body.classList.add('light-mode');
        body.classList.remove('dark-mode');
        if (themeToggleBtn) {
            themeToggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
        }
        applyLightMode();
    } else {
        body.classList.add('dark-mode');
        body.classList.remove('light-mode');
        if (themeToggleBtn) {
            themeToggleBtn.innerHTML = '<i class="fas fa-moon"></i>';
        }
        applyDarkMode();
    }
});
