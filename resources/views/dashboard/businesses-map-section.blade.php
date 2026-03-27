<div id="businesses-map" class="content-section">
    <h1 class="page-title"><i class="fas fa-map"></i> Business Locations - Sagay City</h1>
    
    <p style="color: #666; margin-bottom: 20px;">Explore local businesses in Sagay City, Negros Occidental. Each pin represents a registered business.</p>
    
    <!-- Business Map -->
    <div class="card">
        <div id="business-map-container" style="width: 100%; height: 500px; border-radius: 8px; overflow: hidden; border: 2px solid #00bcd4; margin-bottom: 15px;">
        </div>
        
        <!-- Legend -->
        <div style="display: flex; gap: 15px; margin-top: 15px; flex-wrap: wrap; justify-content: center;">
            <div style="display: flex; align-items: center; gap: 5px;">
                <div style="width: 20px; height: 20px; background: #ffd700; border-radius: 50%; border: 2px solid white;"></div>
                <span style="font-size: 13px; color: #666;">Food Business</span>
            </div>
            <div style="display: flex; align-items: center; gap: 5px;">
                <div style="width: 20px; height: 20px; background: #3498db; border-radius: 50%; border: 2px solid white;"></div>
                <span style="font-size: 13px; color: #666;">Goods Business</span>
            </div>
            <div style="display: flex; align-items: center; gap: 5px;">
                <div style="width: 20px; height: 20px; background: #9b59b6; border-radius: 50%; border: 2px solid white;"></div>
                <span style="font-size: 13px; color: #666;">Services Business</span>
            </div>
        </div>
    </div>
    
    <div class="stats">
        <div class="stat-card">
            <h3>{{ $stats['food_businesses'] ?? 0 }}</h3>
            <p>Food Businesses</p>
        </div>
        <div class="stat-card">
            <h3>{{ $stats['goods_businesses'] ?? 0 }}</h3>
            <p>Goods Stores</p>
        </div>
        <div class="stat-card">
            <h3>{{ $stats['service_businesses'] ?? 0 }}</h3>
            <p>Service Providers</p>
        </div>
    </div>
</div>
