<?php
require_once 'config/config.php';

// Redirect to dashboard if already logged in
if(isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Community & Business Networking Platform</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #1a3a52 0%, #2c5f8d 50%, #00bcd4 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 15px; }
        .container { background: white; padding: 45px; border-radius: 15px; box-shadow: 0 15px 50px rgba(0,0,0,0.3); max-width: 450px; width: 100%; }
        h1 { 
            background: linear-gradient(135deg, #1a3a52 0%, #00bcd4 100%); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px; 
            text-align: center;
            font-size: 36px;
            font-weight: 700;
            letter-spacing: 2px;
        }
        p { color: #666; margin-bottom: 30px; text-align: center; font-size: 15px; }
        .tabs { display: flex; margin-bottom: 25px; border-radius: 8px; overflow: hidden; }
        .tab { flex: 1; padding: 12px; text-align: center; cursor: pointer; background: #f5f7fa; border: none; font-weight: 600; transition: all 0.3s; font-size: 14px; }
        .tab.active { background: linear-gradient(135deg, #2c5f8d 0%, #00bcd4 100%); color: white; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 6px; color: #1a3a52; font-weight: 600; font-size: 14px; }
        input, select { width: 100%; padding: 12px; border: 2px solid #e0f7fa; border-radius: 6px; transition: all 0.3s; font-size: 16px; }
        input:focus, select:focus { outline: none; border-color: #00bcd4; box-shadow: 0 0 0 3px rgba(0,188,212,0.1); }
        button[type="submit"] { width: 100%; padding: 14px; background: linear-gradient(135deg, #2c5f8d 0%, #00bcd4 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: 700; transition: all 0.3s; }
        button[type="submit"]:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,188,212,0.4); }
        .hidden { display: none; }
        .logo { text-align: center; margin-bottom: 20px; }
        .logo-circle { 
            width: 80px; 
            height: 80px; 
            margin: 0 auto 15px; 
            background: linear-gradient(135deg, #1a3a52 0%, #00bcd4 100%); 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            box-shadow: 0 5px 20px rgba(0,188,212,0.3);
        }
        .logo-circle span { font-size: 48px; color: white; font-weight: 700; }
        
        /* Mobile Responsive */
        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 28px;
                letter-spacing: 1px;
            }
            
            p {
                font-size: 14px;
                margin-bottom: 20px;
            }
            
            .logo-circle {
                width: 70px;
                height: 70px;
            }
            
            .logo-circle span {
                font-size: 40px;
            }
            
            .tab {
                padding: 10px;
                font-size: 13px;
            }
            
            input, select {
                padding: 10px;
            }
            
            button[type="submit"] {
                padding: 12px;
                font-size: 15px;
            }
        }
    </style>
    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }
        
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            border-radius: 12px 12px 0 0;
        }
        
        .modal-header.success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
        }
        
        .modal-header.error {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .modal-body {
            padding: 25px;
            font-size: 15px;
            color: #333;
            line-height: 1.6;
        }
        
        .modal-footer {
            padding: 15px 20px;
            text-align: right;
            border-top: 1px solid #e0e0e0;
        }
        
        .modal-btn {
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .modal-btn-primary {
            background: linear-gradient(135deg, #2c5f8d 0%, #00bcd4 100%);
            color: white;
        }
        
        .modal-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 188, 212, 0.3);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <div class="logo-circle">
                <span>Y</span>
            </div>
        </div>
        <h1><?php echo APP_NAME; ?></h1>
        <p>Your All-in-One Tourist In Sagay</p>
        
        <?php if(isset($_GET['message']) && $_GET['message'] === 'account_deleted'): ?>
        <div style="background: #ffebee; border: 2px solid #ef5350; color: #c62828; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 600;">
            ⚠️ Your account has been deleted by an administrator.
        </div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab active" onclick="showTab('login')">Login</button>
            <button class="tab" onclick="showTab('register')">Register</button>
        </div>
        
        <form id="loginForm">
            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        
        <form id="registerForm" class="hidden">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" required>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <!-- Only allow user registration, admin creates business/employer accounts -->
            <input type="hidden" name="role" value="user">
            <button type="submit">Register</button>
            <p style="margin-top: 15px; font-size: 13px; color: #999; text-align: center;">
                Business owners: Contact admin for account creation
            </p>
        </form>
    </div>
    
    <script>
        // Modal Functions
        function showModal(title, message, type = 'success') {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header ${type}">
                        <h2>${type === 'success' ? '✓' : '✗'} ${title}</h2>
                    </div>
                    <div class="modal-body">
                        ${message}
                    </div>
                    <div class="modal-footer">
                        <button class="modal-btn modal-btn-primary" onclick="this.closest('.modal').remove()">OK</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Close on background click
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }
        
        // Remove message parameter from URL after displaying (do it immediately on page load)
        window.addEventListener('DOMContentLoaded', function() {
            if(window.location.search.includes('message=')) {
                // Show message for 3 seconds, but clean URL immediately
                const url = new URL(window.location);
                url.searchParams.delete('message');
                window.history.replaceState({}, document.title, url.pathname);
            }
        });
        
        function showTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            
            if(tab === 'login') {
                document.getElementById('loginForm').classList.remove('hidden');
                document.getElementById('registerForm').classList.add('hidden');
            } else {
                document.getElementById('loginForm').classList.add('hidden');
                document.getElementById('registerForm').classList.remove('hidden');
            }
        }

        // Login Form Handler
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                action: 'login',
                username: formData.get('username'),
                password: formData.get('password')
            };
            
            try {
                const response = await fetch('api/users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if(result.success) {
                    showModal('Login Successful', 'Redirecting to dashboard...', 'success');
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 1000);
                } else {
                    showModal('Login Failed', result.message || 'Invalid credentials. Please try again.', 'error');
                }
            } catch(error) {
                showModal('Error', 'An error occurred. Please try again.', 'error');
                console.error(error);
            }
        });

        // Register Form Handler
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                action: 'register',
                username: formData.get('username'),
                email: formData.get('email'),
                first_name: formData.get('first_name'),
                last_name: formData.get('last_name'),
                password: formData.get('password'),
                role: formData.get('role')
            };
            
            try {
                const response = await fetch('api/users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if(result.success) {
                    showModal('Registration Successful', 'Your account has been created! Please login with your new credentials.', 'success');
                    // Switch to login tab and reset registration form
                    setTimeout(() => {
                        document.querySelector('.tab').click(); // Click first tab (Login)
                        document.getElementById('registerForm').reset();
                    }, 1500);
                } else {
                    showModal('Registration Failed', result.message || 'Could not create account. Please try again.', 'error');
                }
            } catch(error) {
                showModal('Error', 'An error occurred. Please try again.', 'error');
                console.error(error);
            }
        });
    </script>
</body>
</html>
