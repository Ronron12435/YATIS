<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YATIS - Email Verification OTP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #2c5aa0 0%, #1ba098 100%);
            padding: 20px;
            min-height: 100vh;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .email-header {
            background: linear-gradient(135deg, #2c5aa0 0%, #1ba098 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            display: block;
        }

        .email-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }

        .email-header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .email-body {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 16px;
            color: #1a1a1a;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .greeting strong {
            color: #1ba098;
        }

        .otp-section {
            background: linear-gradient(135deg, #f0fffe 0%, #f5f9f8 100%);
            border: 2px solid #1ba098;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }

        .otp-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .otp-code {
            font-size: 48px;
            font-weight: 700;
            color: #1ba098;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
            margin: 15px 0;
            word-break: break-all;
        }

        .otp-expiry {
            font-size: 13px;
            color: #e74c3c;
            margin-top: 15px;
            font-weight: 600;
        }

        .instructions {
            background: #f9f9f9;
            border-left: 4px solid #1ba098;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }

        .instructions h3 {
            color: #1a1a1a;
            font-size: 14px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .instructions ol {
            margin-left: 20px;
            color: #666;
            font-size: 13px;
            line-height: 1.8;
        }

        .instructions li {
            margin-bottom: 8px;
        }

        .security-note {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin: 25px 0;
            font-size: 12px;
            color: #856404;
            line-height: 1.6;
        }

        .security-note strong {
            color: #1a1a1a;
        }

        .email-footer {
            background: #f5f5f5;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e5e5e5;
        }

        .footer-text {
            font-size: 12px;
            color: #999;
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .footer-links {
            font-size: 11px;
            color: #999;
        }

        .footer-links a {
            color: #1ba098;
            text-decoration: none;
            margin: 0 10px;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }

        .divider {
            height: 1px;
            background: #e5e5e5;
            margin: 15px 0;
        }

        .button-container {
            text-align: center;
            margin: 30px 0;
        }

        .button {
            display: inline-block;
            background: linear-gradient(135deg, #1ba098 0%, #0d7a72 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }

        .button:hover {
            opacity: 0.9;
        }

        @media (max-width: 600px) {
            .email-container {
                border-radius: 0;
            }

            .email-header {
                padding: 30px 20px;
            }

            .email-body {
                padding: 25px 20px;
            }

            .otp-code {
                font-size: 36px;
                letter-spacing: 4px;
            }

            .email-footer {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <div style="font-size: 48px; margin-bottom: 15px;">🔐</div>
            <h1>YATIS</h1>
            <p>Email Verification</p>
        </div>

        <!-- Body -->
        <div class="email-body">
            <div class="greeting">
                Hello <strong>{{ $user->first_name ?? 'User' }}</strong>,
            </div>

            <p style="color: #666; font-size: 14px; line-height: 1.8; margin-bottom: 20px;">
                Thank you for signing up with YATIS! To complete your email verification and secure your account, please use the OTP code below.
            </p>

            <!-- OTP Section -->
            <div class="otp-section">
                <div class="otp-label">Your Verification Code</div>
                <div class="otp-code">{{ $otp }}</div>
                <div class="otp-expiry">⏱️ This code expires in 60 seconds</div>
            </div>

            <!-- Instructions -->
            <div class="instructions">
                <h3>How to use your OTP:</h3>
                <ol>
                    <li>Copy the 6-digit code above</li>
                    <li>Return to the YATIS verification page</li>
                    <li>Paste the code in the verification field</li>
                    <li>Click "Verify" to complete your registration</li>
                </ol>
            </div>

            <!-- Security Note -->
            <div class="security-note">
                <strong>🔒 Security Notice:</strong> Never share this code with anyone. YATIS staff will never ask for your OTP code. If you didn't request this code, please ignore this email.
            </div>

            <p style="color: #666; font-size: 13px; line-height: 1.8; margin-top: 20px;">
                If you're having trouble verifying your email, you can request a new code from the verification page.
            </p>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <div class="footer-text">
                © {{ date('Y') }} YATIS. All rights reserved.
            </div>
            <div class="divider"></div>
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Contact Support</a>
            </div>
            <div class="footer-text" style="margin-top: 15px; font-size: 11px;">
                This is an automated email. Please do not reply to this message.
            </div>
        </div>
    </div>
</body>
</html>
