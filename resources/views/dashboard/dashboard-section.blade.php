<div id="dashboard" class="content-section active">
    <h1 class="page-title"><i class="fas fa-home"></i> Dashboard</h1>
    

    
    <!-- Discover Businesses Map Section -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
            <h3 style="margin: 0;"><i class="fas fa-map-pin"></i> Discover Businesses in Sagay City</h3>
            <button onclick="locateMe()" id="locate-btn" style="background: #00bcd4; color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-crosshairs"></i> Locate Me
            </button>
        </div>
        <p style="color: #666; margin-bottom: 10px;">Explore food, goods, and services businesses on the map. Click pins to see details and menu offers!</p>
        
        <div id="dashboard-map-container" style="width: 100%; height: 500px; border-radius: 8px; overflow: hidden; border: 2px solid #00bcd4; margin: 15px 0;">
        </div>
        
        <!-- Legend -->
        <div style="display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 5px;">
                <div style="width: 20px; height: 20px; background: #00bcd4; border-radius: 50%; border: 2px solid white;"></div>
                <span style="font-size: 13px; color: #666;">Your Location</span>
            </div>
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
    
    <!-- Welcome Card -->
    <div class="card">
        <h3><i class="fas fa-info-circle"></i> Welcome to YATIS!</h3>
        <p>Your all-in-one community and business networking platform. Connect with people, discover businesses, find jobs, and explore tourist destinations.</p>
    </div>
    

</div>
