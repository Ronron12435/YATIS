<div id="my-business" class="content-section">
    <style>
        #my-business { background: #f5f5f5; padding: 20px; }
        .my-business-wrapper { max-width: 1200px; margin: 0 auto; }
        .modern-card { background: white; border-radius: 12px; padding: 0; box-shadow: 0 2px 12px rgba(0,0,0,.08); overflow: hidden; margin-bottom: 20px; }
        .modern-card-header { padding: 20px; border-bottom: 1px solid #f0f0f0; background: #fafafa; }
        .card-title-group { display: flex; align-items: center; gap: 12px; }
        .card-icon-modern { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: #e3f2fd; border-radius: 8px; color: #1976d2; }
        .modern-card-header h3 { margin: 0; font-size: 16px; font-weight: 700; color: #1a3a52; }
        .modern-card-body { padding: 20px; }
        .modern-form { display: flex; flex-direction: column; gap: 0; }
        .modern-form-group { margin-bottom: 20px; }
        .modern-form-group:last-child { margin-bottom: 0; }
        .modern-label { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #1a3a52; margin-bottom: 8px; }
        .modern-label svg { width: 16px; height: 16px; color: #00bcd4; }
        .modern-input, .modern-textarea, .modern-select { width: 100%; padding: 12px 14px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 13px; font-family: inherit; background: white; transition: all .2s; }
        .modern-input:focus, .modern-textarea:focus, .modern-select:focus { outline: none; border-color: #00bcd4; box-shadow: 0 0 0 3px rgba(0, 188, 212, .1); }
        .modern-textarea { resize: vertical; min-height: 100px; }
        .input-hint { font-size: 11px; color: #999; margin-top: 6px; display: block; }
        .modern-btn { padding: 12px 24px; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all .2s; display: inline-flex; align-items: center; gap: 8px; }
        .modern-btn-primary { background: linear-gradient(135deg, #1a3a52 0%, #2c5f8d 100%); color: white; box-shadow: 0 4px 12px rgba(26, 58, 82, 0.2); }
        .modern-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(26, 58, 82, 0.3); }
        .message { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        .message.success { background: #c8e6c9; color: #2e7d32; }
        .message.error { background: #ffcdd2; color: #c62828; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-row-full { grid-column: 1 / -1; }
        .business-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .business-info-card { background: #f9f9f9; padding: 16px; border-radius: 8px; border-left: 4px solid #00bcd4; }
        .business-info-label { font-size: 11px; font-weight: 700; color: #999; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        .business-info-value { font-size: 14px; color: #333; font-weight: 600; }
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .business-info-grid { grid-template-columns: 1fr; }
        }
    </style>

    <div class="my-business-wrapper">
        <div class="modern-card">
            <div class="modern-card-header">
                <div class="card-title-group">
                    <div class="card-icon-modern">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                    </div>
                    <h3>Register Your Business</h3>
                </div>
            </div>
            <div class="modern-card-body">
                <div id="businessMessage"></div>
                
                <!-- Business Selector (shown when multiple businesses exist) -->
                <div id="businessSelectorContainer" style="margin-bottom: 20px; display: none;">
                    <label class="modern-label" style="margin-bottom: 12px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
                        Select Business to Manage
                    </label>
                    <select class="modern-select" id="businessSelector"></select>
                </div>
                
                <!-- Business Info Display -->
                <div id="businessInfoDisplay" style="display: none;">
                    <div class="business-info-grid">
                        <div class="business-info-card">
                            <div class="business-info-label">Business Name</div>
                            <div class="business-info-value" id="displayBusinessName">-</div>
                        </div>
                        <div class="business-info-card">
                            <div class="business-info-label">Business Type</div>
                            <div class="business-info-value" id="displayBusinessType">-</div>
                        </div>
                        <div class="business-info-card">
                            <div class="business-info-label">Phone</div>
                            <div class="business-info-value" id="displayPhone">-</div>
                        </div>
                        <div class="business-info-card">
                            <div class="business-info-label">Email</div>
                            <div class="business-info-value" id="displayEmail">-</div>
                        </div>
                        <div class="business-info-card form-row-full">
                            <div class="business-info-label">Location</div>
                            <div class="business-info-value" id="displayLocation">-</div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <button type="button" class="modern-btn modern-btn-primary" onclick="toggleBusinessForm()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z"/></svg>
                            Edit Business
                        </button>
                        <button type="button" class="modern-btn" onclick="showCreateNewBusinessForm()" style="background: #4caf50; color: white;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                            Create New Business
                        </button>
                    </div>
                </div>

                <!-- Business Form -->
                <form id="businessForm" class="modern-form" onsubmit="registerBusiness(event)">
                    <div class="form-row">
                        <div class="modern-form-group">
                            <label class="modern-label">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
                                Business Name
                            </label>
                            <input type="text" class="modern-input" id="businessName" required>
                        </div>
                        <div class="modern-form-group">
                            <label class="modern-label">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
                                Business Type
                            </label>
                            <select class="modern-select" id="businessType" required>
                                <option value="">Select Business Type</option>
                                <option value="food">🍔 Food</option>
                                <option value="goods">🛍️ Goods</option>
                                <option value="services">🔧 Services</option>
                            </select>
                        </div>
                    </div>

                    <div class="modern-form-group">
                        <label class="modern-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12h-4v4h-2v-4H7v-2h4V9h2v4h4v2z"/></svg>
                            Description
                        </label>
                        <textarea class="modern-textarea" id="description" placeholder="Tell customers about your business..."></textarea>
                        <span class="input-hint">Share what makes your business special</span>
                    </div>

                    <div class="form-row">
                        <div class="modern-form-group">
                            <label class="modern-label">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
                                Phone
                            </label>
                            <input type="tel" class="modern-input" id="phone" required maxlength="11" inputmode="numeric" placeholder="09XXXXXXXXX">
                            <span class="input-hint">11 digits only (e.g., 09123456789)</span>
                        </div>
                        <div class="modern-form-group">
                            <label class="modern-label">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                Email
                            </label>
                            <input type="email" class="modern-input" id="email" required>
                        </div>
                    </div>

                    <div class="modern-form-group">
                        <label class="modern-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
                            Location / Address
                        </label>
                        <input type="text" class="modern-input" id="address" required>
                        <span class="input-hint">Street address, city, province</span>
                    </div>

                    <!-- Business Location Map -->
                    <div class="modern-form-group">
                        <label class="modern-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
                            Business Location
                        </label>
                        <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 12px 14px; margin-bottom: 12px; display: flex; gap: 10px; align-items: flex-start;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="#ff9800" style="flex-shrink: 0; margin-top: 2px;"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
                            <div style="font-size: 12px; color: #856404; line-height: 1.4;">
                                <strong>IMPORTANT:</strong> Click on the map below to set your exact business location.<br>
                                Your business will only appear on the map for customers if you set this location!
                            </div>
                        </div>
                        <div id="businessLocationMap" style="width: 100%; height: 400px; border-radius: 8px; border: 2px solid #e0e0e0; background: #f5f5f5; position: relative; overflow: hidden;"></div>
                        <input type="hidden" id="businessLatitude" value="">
                        <input type="hidden" id="businessLongitude" value="">
                        <span class="input-hint" id="locationCoords" style="margin-top: 8px; display: block;">Click on the map to set location</span>
                    </div>

                    <div class="form-row">
                        <div class="modern-form-group">
                            <label class="modern-label">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M11 11H5v2h6v-2zm0-4H5v2h6V7zm0 8H5v2h6v-2zm8-1v-3.5c0-.83-.67-1.5-1.5-1.5S14 9.67 14 10.5v3.5c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5zm-4-6.5H5v2h10V7zm6.5-.5c1.93 0 3.5 1.57 3.5 3.5V19c0 1.1-.9 2-2 2h-1V4h-1c0-1.1-.9-2-2-2h-1v2h1v16h1V4h1v16h1V7c0-1.93-1.57-3.5-3.5-3.5z"/></svg>
                                Opening Time
                            </label>
                            <input type="time" class="modern-input" id="openingTime">
                        </div>
                        <div class="modern-form-group">
                            <label class="modern-label">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M11 11H5v2h6v-2zm0-4H5v2h6V7zm0 8H5v2h6v-2zm8-1v-3.5c0-.83-.67-1.5-1.5-1.5S14 9.67 14 10.5v3.5c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5zm-4-6.5H5v2h10V7zm6.5-.5c1.93 0 3.5 1.57 3.5 3.5V19c0 1.1-.9 2-2 2h-1V4h-1c0-1.1-.9-2-2-2h-1v2h1v16h1V4h1v16h1V7c0-1.93-1.57-3.5-3.5-3.5z"/></svg>
                                Closing Time
                            </label>
                            <input type="time" class="modern-input" id="closingTime">
                        </div>
                    </div>

                    <div class="form-row">
                        <button type="submit" class="modern-btn modern-btn-primary">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                            Register Business
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
