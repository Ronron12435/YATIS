<div id="destinations" class="content-section">
    <style>
        .dest-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.08); margin-bottom: 16px; border-left: 4px solid #e74c3c; }
        .dest-card-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
        .dest-card-title { color: #e74c3c; font-size: 18px; margin: 0 0 6px; }
        .dest-card-desc { color: #555; font-size: 14px; margin: 0 0 8px; line-height: 1.5; }
        .dest-card-meta { font-size: 13px; color: #666; margin: 4px 0; display: flex; align-items: center; gap: 6px; }
        .dest-card-rating { text-align: right; min-width: 140px; }
        .dest-rating-text { display: block; font-size: 12px; color: #888; margin-top: 4px; }
        .dest-card-actions { display: flex; gap: 10px; margin-top: 14px; flex-wrap: wrap; }
        .btn-dest { padding: 8px 16px; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all .2s; }
        .btn-dest-reviews { background: #1a3a52; color: white; }
        .btn-dest-reviews:hover { background: #2c5f8d; }
        .btn-dest-write { background: #2ecc71; color: white; }
        .btn-dest-write:hover { background: #27ae60; }
        .btn-dest-map { background: #e74c3c; color: white; }
        .btn-dest-map:hover { background: #c0392b; }
        .dest-panel-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 9998; align-items: center; justify-content: center; }
        .dest-panel { background: white; border-radius: 12px; width: 90%; max-width: 560px; max-height: 80vh; display: flex; flex-direction: column; box-shadow: 0 8px 32px rgba(0,0,0,.2); overflow: hidden; }
        .dest-panel-header { padding: 18px 20px; background: #1a3a52; color: white; display: flex; justify-content: space-between; align-items: center; }
        .dest-panel-header h3 { margin: 0; font-size: 16px; }
        .dest-panel-close { background: none; border: none; color: white; font-size: 20px; cursor: pointer; line-height: 1; }
        .dest-panel-body { padding: 20px; overflow-y: auto; flex: 1; }
        .review-card { background: #f8f9fa; border-radius: 8px; padding: 14px; margin-bottom: 12px; }
        .review-header { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
        .review-avatar { width: 36px; height: 36px; border-radius: 50%; background: #1a3a52; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; flex-shrink: 0; }
        .review-text { color: #555; font-size: 14px; margin: 0; line-height: 1.6; }
        .dest-map-btn { padding: 8px 16px; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all .2s; }
        .dest-popup { border-radius: 8px !important; }
        .leaflet-popup-content { border-radius: 8px !important; }
        .leaflet-popup-content-wrapper { border-radius: 8px !important; box-shadow: 0 4px 16px rgba(0,0,0,0.2) !important; }
    </style>

    <h1 class="page-title"><i class="fas fa-globe-asia"></i> Tourist Destinations - Sagay City, Negros Occidental</h1>

    {{-- Stats --}}
    <div class="stats">
        <div class="stat-card">
            <h3 id="dest-total-count">0</h3>
            <p>Destinations</p>
        </div>
        <div class="stat-card">
            <h3 id="dest-my-reviews">0</h3>
            <p>My Reviews</p>
        </div>
        <div class="stat-card">
            <h3 id="dest-my-avg">0.0</h3>
            <p>Avg Rating</p>
        </div>
    </div>

    {{-- Interactive Map --}}
    <div class="card" style="padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
            <h3 style="margin: 0;"><i class="fas fa-map-marked-alt" style="color:#e74c3c;"></i> Interactive Tourist Map</h3>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button class="dest-map-btn" style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;" onclick="centerOnSagayCity()">
                    🏙️ Sagay City
                </button>
                <button class="dest-map-btn" style="background:#95a5a6;color:white;" onclick="toggleDestMap()">
                    <span id="dest-map-toggle-text">Hide Map</span>
                </button>
            </div>
        </div>
        <div id="dest-gps-status" style="font-size: 13px; color: #666; margin-bottom: 12px; display: inline-flex; align-items: center; gap: 6px;">
            <span style="width: 8px; height: 8px; border-radius: 50%; background: #95a5a6; display: inline-block;"></span>
            <span>GPS: Initializing...</span>
        </div>
        <div id="dest-map-wrapper">
            <div style="position: relative; margin-bottom: 12px;">
                <input id="dest-map-search" type="text" placeholder="🔍 Search destinations (e.g., Carbin Reef, Vito Church...)"
                    style="position:absolute;top:10px;left:50%;transform:translateX(-50%);z-index:1000;width:90%;max-width:400px;padding:12px 15px;border-radius:25px;border:2px solid #00bcd4;box-shadow:0 4px 12px rgba(0,0,0,.2);font-size:14px;outline:none;background:white;">
            </div>
            <div id="dest-map-container" style="width:100%;height:500px;border-radius:8px;overflow:hidden;border:2px solid #667eea;position:relative;"></div>
        </div>
        <div style="margin-top: 15px; padding: 12px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #00bcd4;">
            <p style="margin: 0; color: #666; font-size: 14px;">
                💡 <strong>Tip:</strong> Hover over destination pins to see quick directions. Click "Get Directions" to view the full route from your location.
            </p>
        </div>
    </div>

    {{-- Destination List --}}
    <div id="dest-list">
        <p style="color: #999; padding: 20px; text-align: center;">Loading destinations...</p>
    </div>
</div>

{{-- Reviews Panel --}}
<div id="dest-reviews-panel" class="dest-panel-overlay">
    <div class="dest-panel">
        <div class="dest-panel-header">
            <h3 id="dest-reviews-title">Reviews</h3>
            <button class="dest-panel-close" onclick="closeReviewsPanel()">&#x2715;</button>
        </div>
        <div id="dest-reviews-body" class="dest-panel-body"></div>
    </div>
</div>

{{-- Write Review Panel --}}
<div id="dest-write-panel" class="dest-panel-overlay">
    <div class="dest-panel">
        <div class="dest-panel-header">
            <h3 id="dest-write-title">Write a Review</h3>
            <button class="dest-panel-close" onclick="closeWritePanel()">&#x2715;</button>
        </div>
        <div class="dest-panel-body">
            <input type="hidden" id="dest-write-dest-id">
            <div style="margin-bottom: 16px;">
                <label style="display:block;font-weight:600;color:#1a3a52;margin-bottom:6px;">Rating</label>
                <select id="dest-write-rating" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;font-size:14px;">
                    <option value="">Select a rating</option>
                    <option value="5">⭐⭐⭐⭐⭐ — Excellent</option>
                    <option value="4">⭐⭐⭐⭐ — Good</option>
                    <option value="3">⭐⭐⭐ — Average</option>
                    <option value="2">⭐⭐ — Poor</option>
                    <option value="1">⭐ — Terrible</option>
                </select>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display:block;font-weight:600;color:#1a3a52;margin-bottom:6px;">Your Review</label>
                <textarea id="dest-write-text" rows="4" placeholder="Share your experience..."
                    style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;font-size:14px;resize:vertical;"></textarea>
            </div>
            <p id="dest-write-error" style="color:#e74c3c;font-size:13px;margin-bottom:10px;"></p>
            <button id="dest-write-submit" class="btn btn-primary" style="width:100%;" onclick="submitReview()">
                Submit Review
            </button>
        </div>
    </div>
</div>

{{-- Directions Modal --}}
<div id="dest-directions-modal" class="dest-panel-overlay">
    <div class="dest-panel" style="max-width:600px;">
        <div class="dest-panel-header">
            <h3 id="dest-directions-title">🗺️ Route to Destination</h3>
            <button class="dest-panel-close" onclick="closeDirectionsModal()">&#x2715;</button>
        </div>
        <div class="dest-panel-body">
            <div id="dest-directions-content">
                <p style="color:#999;text-align:center;padding:20px;">Loading directions...</p>
            </div>
        </div>
    </div>
</div>
