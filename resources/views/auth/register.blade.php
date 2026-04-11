<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - YATIS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .auth-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 500px;
        }

        .auth-left {
            display: none;
        }

        .auth-left .logo-section {
            margin-bottom: 40px;
        }

        .auth-left .logo-section img {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.2));
        }

        .auth-left h2 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .auth-left p {
            font-size: 16px;
            opacity: 0.9;
            line-height: 1.6;
        }

        .auth-right {
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
        }

        .register-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .register-header .logo-section {
            margin-bottom: 20px;
        }

        .register-header .logo-section img {
            width: 105px;
            height: 90px;
            margin-bottom: 10px;
        }

        .register-header h1 {
            color: #1a1a1a;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .register-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #1a1a1a;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e5e5e5;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
            background: #f9f9f9;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #1ba098;
            background: white;
            box-shadow: 0 0 0 4px rgba(27, 160, 152, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .btn-register {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #1ba098 0%, #0d7a72 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(27, 160, 152, 0.4);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .login-link {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-top: 20px;
        }

        .login-link a {
            color: #1ba098;
            text-decoration: none;
            font-weight: 700;
            transition: color 0.3s;
        }

        .login-link a:hover {
            color: #2c5aa0;
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            border-left: 4px solid #c33;
        }

        .terms {
            font-size: 12px;
            color: #999;
            margin-top: 15px;
            line-height: 1.6;
        }

        .terms a {
            color: #1ba098;
            text-decoration: none;
            font-weight: 600;
        }

        .terms a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .auth-wrapper {
                grid-template-columns: 1fr;
            }

            .auth-left {
                padding: 40px 30px;
                display: none;
            }

            .auth-right {
                padding: 40px 30px;
            }

            .register-header h1 {
                font-size: 24px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-left">
            <div class="logo-section">
                <img src="{{ asset('images/login-form-logo.png') }}" alt="YATIS Logo">
                <h2>YATIS</h2>
            </div>
            <p>Join our community and connect with people, businesses, and opportunities around you.</p>
        </div>

        <div class="auth-right">
            <div class="register-header">
                <div class="logo-section">
                    <img src="{{ asset('images/login-form-logo.png') }}" alt="YATIS Logo">
                </div>
                <h1>Create Account</h1>
                <p>Get started with YATIS in seconds</p>
            </div>

            @if ($errors->any())
                <div class="error-message">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="{{ old('username') }}" placeholder="Choose your username" required autofocus>
                    @error('username')
                        <span style="color: #c33; font-size: 12px;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" placeholder="John" required>
                        @error('first_name')
                            <span style="color: #c33; font-size: 12px;">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" placeholder="Doe" required>
                        @error('last_name')
                            <span style="color: #c33; font-size: 12px;">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required>
                    @error('email')
                        <span style="color: #c33; font-size: 12px;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="At least 8 characters" required>
                    @error('password')
                        <span style="color: #c33; font-size: 12px;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Confirm your password" required>
                </div>

                <button type="submit" class="btn-register">Create Account</button>

                <div class="terms">
                    By signing up, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                </div>
            </form>

            <div class="login-link">
                Already have an account? <a href="{{ route('login') }}">Sign in</a>
            </div>
        </div>
    </div>
</body>
</html>
