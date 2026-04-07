<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YATIS - Your All-in-One Tourism Information System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f7fa;
        }

        /* Navigation */
        nav {
            background: white;
            padding: 1rem 2rem;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        nav .container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav .logo {
            color: #1a3a52;
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: 1px;
        }

        nav .logo i {
            color: #00bcd4;
            font-size: 28px;
        }

        nav .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        nav .nav-links a {
            color: #1a3a52;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        nav .nav-links a:hover {
            color: #00bcd4;
        }

        nav .auth-buttons {
            display: flex;
            gap: 1rem;
        }

        nav .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }

        nav .btn-login {
            background: transparent;
            color: #1a3a52;
            border: 2px solid #1a3a52;
        }

        nav .btn-login:hover {
            background: #1a3a52;
            color: white;
            transform: translateY(-2px);
        }

        nav .btn-signup {
            background: linear-gradient(135deg, #1a3a52 0%, #00bcd4 100%);
            color: white;
            border: none;
        }

        nav .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 188, 212, 0.3);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #1a3a52 0%, #2c5f8d 50%, #00bcd4 100%);
            color: white;
            padding: 150px 2rem 100px;
            text-align: center;
            margin-top: 60px;
        }

        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            animation: slideDown 0.8s ease;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .hero p {
            font-size: 18px;
            margin-bottom: 30px;
            opacity: 0.95;
            animation: slideUp 0.8s ease;
            font-weight: 500;
        }

        .hero .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .hero .btn-primary {
            background: white;
            color: #1a3a52;
            padding: 15px 40px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .hero .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }

        .hero .btn-secondary {
            background: transparent;
            color: white;
            padding: 15px 40px;
            border: 2px solid white;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .hero .btn-secondary:hover {
            background: white;
            color: #1a3a52;
            transform: translateY(-3px);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Features Section */
        .features {
            padding: 80px 2rem;
            background: white;
        }

        .features .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .features h2 {
            text-align: center;
            font-size: 36px;
            margin-bottom: 50px;
            color: #1a3a52;
            font-weight: 700;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background: #f5f7fa;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            transition: all 0.3s;
            text-align: center;
            border-top: 3px solid #00bcd4;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }

        .feature-card i {
            font-size: 48px;
            color: #00bcd4;
            margin-bottom: 20px;
            display: block;
        }

        .feature-card h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #1a3a52;
            font-weight: 600;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
            font-size: 14px;
        }

        /* About Section */
        .about {
            padding: 80px 2rem;
            background: white;
        }

        .about .container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
        }

        .about h2 {
            font-size: 36px;
            margin-bottom: 20px;
            color: #1a3a52;
            font-weight: 700;
        }

        .about p {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.8;
            font-size: 15px;
        }

        .about ul {
            list-style: none;
            margin-top: 20px;
        }

        .about ul li {
            padding: 10px 0;
            color: #666;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
        }

        .about ul li:before {
            content: "✓";
            color: #00bcd4;
            font-weight: bold;
            font-size: 20px;
        }

        .about-image {
            background: linear-gradient(135deg, #1a3a52 0%, #2c5f8d 100%);
            border-radius: 10px;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 80px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            background-size: cover;
            background-position: center;
        }

        /* CTA Section */
        .cta-section {
            padding: 80px 2rem;
            background: linear-gradient(135deg, #1a3a52 0%, #2c5f8d 50%, #00bcd4 100%);
            color: white;
            text-align: center;
        }

        .cta-section .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .cta-section h2 {
            font-size: 36px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .cta-section p {
            font-size: 18px;
            margin-bottom: 30px;
            opacity: 0.95;
        }

        .cta-section .btn-primary {
            background: white;
            color: #1a3a52;
            padding: 15px 40px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .cta-section .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }

        /* Footer */
        footer {
            background: #2d2d2d;
            color: white;
            padding: 40px 2rem;
            text-align: center;
        }

        footer .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        footer p {
            margin-bottom: 10px;
            font-size: 14px;
        }

        footer .social-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        footer .social-links a {
            color: white;
            font-size: 20px;
            transition: color 0.3s;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(0, 188, 212, 0.2);
        }

        footer .social-links a:hover {
            color: #00bcd4;
            background: rgba(0, 188, 212, 0.4);
        }

        /* Responsive */
        @media (max-width: 768px) {
            nav .nav-links {
                display: none;
            }

            .hero {
                padding: 100px 2rem 60px;
                margin-top: 60px;
            }

            .hero h1 {
                font-size: 32px;
            }

            .hero p {
                font-size: 16px;
            }

            .hero .cta-buttons {
                flex-direction: column;
            }

            .hero .btn-primary,
            .hero .btn-secondary {
                width: 100%;
            }

            .features h2 {
                font-size: 28px;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .about .container {
                grid-template-columns: 1fr;
            }

            .about-image {
                height: 300px;
                font-size: 60px;
            }

            .stats .container {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .cta-section h2 {
                font-size: 28px;
            }

            nav .btn {
                padding: 8px 16px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="container">
            <div class="logo">
                <i class="fas fa-globe"></i>
                YATIS
            </div>
            <ul class="nav-links">
                <li><a href="#features">Features</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <div class="auth-buttons">
                <a href="{{ route('login') }}" class="btn btn-login">Login</a>
                <a href="{{ route('register') }}" class="btn btn-signup">Sign Up</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Welcome to YATIS</h1>
            <p>Your All-in-One Tourism Information System</p>
            <p style="font-size: 16px; opacity: 0.85; margin-bottom: 40px;">Discover local businesses, connect with friends, and explore amazing destinations</p>
            <div class="cta-buttons">
                <a href="{{ route('register') }}" class="btn-primary">Get Started</a>
                <a href="#features" class="btn-secondary">Learn More</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <h2>Why Choose YATIS?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-map-location-dot"></i>
                    <h3>Discover Businesses</h3>
                    <p>Find food restaurants, goods stores, and service providers near you with our interactive map.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-users"></i>
                    <h3>Connect & Share</h3>
                    <p>Build your network, make friends, and share your experiences with the community.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-briefcase"></i>
                    <h3>Business Management</h3>
                    <p>Manage your business, add products/services, and reach more customers easily.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-suitcase-rolling"></i>
                    <h3>Tourism Guide</h3>
                    <p>Explore tourist destinations and get recommendations from local experts.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-briefcase"></i>
                    <h3>Job Opportunities</h3>
                    <p>Find employment opportunities and connect with employers in your area.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-star"></i>
                    <h3>Reviews & Ratings</h3>
                    <p>Share your experiences and help others make informed decisions.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about" id="about">
        <div class="container">
            <div>
                <h2>About YATIS</h2>
                <p>YATIS is a comprehensive tourism information system designed to connect travelers, businesses, and local communities in Sagay City and beyond.</p>
                <p>Our platform brings together everything you need to explore, discover, and connect with your local area.</p>
                <ul>
                    <li>Interactive business mapping</li>
                    <li>Social networking features</li>
                    <li>Business management tools</li>
                    <li>Tourism destination guides</li>
                    <li>Job marketplace</li>
                    <li>Community reviews and ratings</li>
                </ul>
            </div>
            <div class="about-image" style="background-image: url('{{ asset('images/about-image.jpg') }}');">
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Get Started?</h2>
            <p>Join thousands of users exploring and connecting in Sagay City</p>
            <a href="{{ route('register') }}" class="btn-primary">Create Your Account Now</a>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact">
        <div class="container">
            <p>&copy; 2026 YATIS - Your All-in-One Tourism Information System</p>
            <p>Connecting communities, one experience at a time</p>
        </div>
    </footer>
</body>
</html>
