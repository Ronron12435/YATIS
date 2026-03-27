<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YATIS - Your Adventure Travel Information System</title>
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
        }
        
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            text-align: center;
        }
        
        header h1 {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }
        
        header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }
        
        .feature {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .feature:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .feature h3 {
            color: #667eea;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .feature p {
            color: #666;
            line-height: 1.8;
        }
        
        .cta-section {
            background: #f8f9fa;
            padding: 3rem 2rem;
            border-radius: 8px;
            text-align: center;
            margin: 3rem 0;
        }
        
        .cta-section h2 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 2rem;
        }
        
        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }
        
        footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 3rem;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
            text-align: center;
        }
        
        .stat {
            padding: 1rem;
        }
        
        .stat-number {
            font-size: 2.5rem;
            color: #667eea;
            font-weight: bold;
        }
        
        .stat-label {
            color: #666;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <header>
        <h1>🌍 YATIS</h1>
        <p>Your Adventure Travel Information System</p>
    </header>
    
    <div class="container">
        <section class="cta-section">
            <h2>Welcome to YATIS</h2>
            <p style="margin-bottom: 2rem; color: #666; font-size: 1.1rem;">
                Connect with travelers, discover destinations, find jobs, and build your adventure community
            </p>
            <div class="btn-group">
                <a href="/register" class="btn btn-primary">Get Started</a>
                <a href="/login" class="btn btn-secondary">Sign In</a>
            </div>
        </section>
        
        <section class="features">
            <div class="feature">
                <h3>👥 Social Network</h3>
                <p>Connect with fellow travelers, make friends, and share your travel experiences with the community.</p>
            </div>
            
            <div class="feature">
                <h3>🗺️ Destinations</h3>
                <p>Discover amazing travel destinations, read reviews, and plan your next adventure.</p>
            </div>
            
            <div class="feature">
                <h3>💼 Jobs & Opportunities</h3>
                <p>Find travel-related job opportunities and connect with businesses in the tourism industry.</p>
            </div>
            
            <div class="feature">
                <h3>🏢 Businesses</h3>
                <p>Explore restaurants, hotels, and travel services. Check hours and make reservations.</p>
            </div>
            
            <div class="feature">
                <h3>📱 Messages</h3>
                <p>Stay connected with private messages and group chats with your travel buddies.</p>
            </div>
            
            <div class="feature">
                <h3>🎉 Events</h3>
                <p>Discover and create travel events to meet other adventurers and explore together.</p>
            </div>
        </section>
        
        <section class="stats">
            <div class="stat">
                <div class="stat-number">100+</div>
                <div class="stat-label">Destinations</div>
            </div>
            <div class="stat">
                <div class="stat-number">50+</div>
                <div class="stat-label">Businesses</div>
            </div>
            <div class="stat">
                <div class="stat-number">1000+</div>
                <div class="stat-label">Users</div>
            </div>
            <div class="stat">
                <div class="stat-number">24/7</div>
                <div class="stat-label">Support</div>
            </div>
        </section>
    </div>
    
    <footer>
        <p>&copy; 2026 YATIS - Your Adventure Travel Information System. All rights reserved.</p>
    </footer>
</body>
</html>
