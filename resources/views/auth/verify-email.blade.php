<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - YATIS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #2c5aa0 0%, #1ba098 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 420px;
            width: 100%;
            padding: 40px;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo img {
            width: 80px;
            height: 80px;
            margin-bottom: 15px;
        }

        .logo h1 {
            color: #1a1a1a;
            font-size: 24px;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .logo p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: #1a1a1a;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e5e5e5;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background: #f9f9f9;
        }

        input:focus {
            outline: none;
            border-color: #1ba098;
            background: white;
            box-shadow: 0 0 0 4px rgba(27, 160, 152, 0.1);
        }

        input[type="text"]::placeholder {
            color: #999;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-verify {
            background: linear-gradient(135deg, #1ba098 0%, #0d7a72 100%);
            color: white;
        }

        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(27, 160, 152, 0.4);
        }

        .btn-resend {
            background: #f0f0f0;
            color: #1ba098;
            font-weight: 600;
        }

        .btn-resend:hover {
            background: #e8e8e8;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 12px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: 700;
            color: #1a1a1a;
        }

        .modal-icon {
            margin-right: 12px;
            font-size: 24px;
        }

        .modal-body {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .modal-footer {
            display: flex;
            gap: 12px;
        }

        .modal-btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .modal-btn-primary {
            background: linear-gradient(135deg, #1ba098 0%, #0d7a72 100%);
            color: white;
        }

        .modal-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(27, 160, 152, 0.4);
        }

        .alert {
            display: none;
        }

        .info-text {
            color: #666;
            font-size: 13px;
            margin-top: 15px;
            text-align: center;
            line-height: 1.5;
        }

        .email-display {
            background: #f5f5f5;
            padding: 12px;
            border-radius: 6px;
            text-align: center;
            margin-bottom: 20px;
            font-size: 14px;
            color: #1a1a1a;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="{{ asset('images/login-form-logo.png') }}" alt="YATIS Logo">
            <h1>Verify Email</h1>
            <p>Enter the OTP sent to your email</p>
        </div>

        <div class="email-display">
            {{ session('email') ?? 'your email' }}
        </div>

        <form method="POST" action="/api/verify-email" id="verifyForm">
            @csrf
            <input type="hidden" name="email" value="{{ session('email') }}">

            <div class="form-group">
                <label for="otp">OTP Code</label>
                <input 
                    type="text" 
                    id="otp" 
                    name="otp" 
                    placeholder="Enter 6-digit OTP" 
                    maxlength="6" 
                    pattern="[0-9]{6}"
                    required
                    autofocus
                >
            </div>

            <div class="button-group">
                <button type="submit" class="btn-verify">Verify</button>
                <button type="button" class="btn-resend" onclick="resendOtp()">Resend OTP</button>
            </div>
        </form>

        <div class="info-text">
            ✓ OTP expires in 60 seconds<br>
            Check your spam folder if you don't see the email
        </div>
    </div>

    <!-- Modal -->
    <div id="alertModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-icon" id="modalIcon">ℹ️</span>
                <span id="modalTitle">Message</span>
            </div>
            <div class="modal-body" id="modalMessage">
                Your message here
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-primary" onclick="closeModal()">OK</button>
            </div>
        </div>
    </div>

    <script>
        function showModal(title, message, icon = '✓') {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalMessage').textContent = message;
            document.getElementById('modalIcon').textContent = icon;
            document.getElementById('alertModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('alertModal').classList.remove('show');
        }

        // Close modal when clicking outside
        document.getElementById('alertModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        document.getElementById('verifyForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.querySelector('input[name="email"]').value;
            const otp = document.querySelector('input[name="otp"]').value;
            const csrfToken = document.querySelector('input[name="_token"]').value;
            
            try {
                const response = await fetch('/api/verify-email', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ email, otp })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showModal('Success', data.message, '✓');
                    setTimeout(() => {
                        window.location.href = data.redirect || '/dashboard';
                    }, 1500);
                } else {
                    showModal('Error', data.message, '✗');
                }
            } catch (err) {
                showModal('Error', err.message, '✗');
            }
        });

        function resendOtp() {
            const email = document.querySelector('input[name="email"]').value;
            const csrfToken = document.querySelector('input[name="_token"]').value;
            
            fetch('/api/resend-otp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ email })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showModal('Success', data.message, '✓');
                } else {
                    showModal('Error', data.message, '✗');
                }
            })
            .catch(err => showModal('Error', err.message, '✗'));
        }

        // Auto-format OTP input to numbers only
        document.getElementById('otp').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>
