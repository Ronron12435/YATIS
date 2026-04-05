window.showBusinessTables = function(businessId) {
    const modal = document.getElementById('business-items-modal');
    const titleEl = document.getElementById('business-items-title');
    const subtitleEl = document.getElementById('business-items-subtitle');
    const contentEl = document.getElementById('business-items-content');
    
    let pollInterval = null;
    
    const loadTables = () => {
        fetch(`/api/businesses/${businessId}/tables`, { credentials: 'include' })
            .then(r => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then(response => {
                // Try multiple ways to extract tables
                let tables = [];
                
                // Method 1: response.data.data (paginated)
                if (response.data && response.data.data && Array.isArray(response.data.data)) {
                    tables = response.data.data;
                }
                // Method 2: response.data (direct array)
                else if (Array.isArray(response.data)) {
                    tables = response.data;
                }
                // Method 3: response itself is array
                else if (Array.isArray(response)) {
                    tables = response;
                }
                // Method 4: response.data is object with data property
                else if (response.data && typeof response.data === 'object') {
                    tables = [];
                }
                else {
                    tables = [];
                }
                
                titleEl.textContent = 'Available Tables';
                subtitleEl.textContent = tables.length > 0 ? `${tables.length} table${tables.length !== 1 ? 's' : ''}` : 'No tables available';
                
                if (tables.length === 0) {
                    contentEl.innerHTML = '<div style="text-align: center; padding: 40px; color: #999;"><div style="font-size: 48px; margin-bottom: 10px;">🪑</div><div>No tables available at this time.</div></div>';
                } else {
                    const availableCount = tables.filter(t => t.status === 'available').length;
                    const occupiedCount = tables.filter(t => t.status === 'occupied').length;
                    const totalSeats = tables.reduce((sum, t) => sum + (t.capacity || 0), 0);
                    
                    let html = `
                        <div style="display: flex; gap: 15px; margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 8px;">
                            <div style="flex: 1; text-align: center;">
                                <div style="font-size: 24px; font-weight: 700; color: #27ae60;">${availableCount}</div>
                                <div style="font-size: 12px; color: #666;">Available</div>
                            </div>
                            <div style="flex: 1; text-align: center;">
                                <div style="font-size: 24px; font-weight: 700; color: #e74c3c;">${occupiedCount}</div>
                                <div style="font-size: 12px; color: #666;">Occupied</div>
                            </div>
                            <div style="flex: 1; text-align: center;">
                                <div style="font-size: 24px; font-weight: 700; color: #3498db;">${totalSeats}</div>
                                <div style="font-size: 12px; color: #666;">Total Seats</div>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px;">
                    `;
                    
                    tables.forEach((table, idx) => {
                        const isOccupied = table.status === 'occupied';
                        const isReserved = table.status === 'reserved';
                        const borderColor = isOccupied ? '#e74c3c' : isReserved ? '#f39c12' : '#27ae60';
                        const bgColor = isOccupied ? '#ffebee' : isReserved ? '#fff3cd' : '#e8f5e9';
                        const textColor = isOccupied ? '#e74c3c' : isReserved ? '#f39c12' : '#27ae60';
                        const statusText = isOccupied ? '🔴 Occupied' : isReserved ? '🟡 Reserved' : '🟢 Available';
                        
                        html += `
                            <div style="background: white; border-radius: 8px; padding: 15px; text-align: center; border: 2px solid ${borderColor}; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                <div style="font-size: 32px; margin-bottom: 8px;">🪑</div>
                                <div style="font-weight: 700; color: #333; margin-bottom: 4px;">Table ${table.table_number}</div>
                                <div style="font-size: 12px; color: #666; margin-bottom: 8px;">${table.capacity} seats</div>
                                <div style="background: ${bgColor}; padding: 6px; border-radius: 4px; font-size: 12px; font-weight: 600; color: ${textColor};">
                                    ${statusText}
                                </div>
                            </div>
                        `;
                    });
                    
                    html += '</div>';
                    contentEl.innerHTML = html;
                }
                
                modal.style.display = 'flex';
            })
            .catch(err => {
                contentEl.innerHTML = '<div style="text-align: center; padding: 40px; color: #999;"><div style="font-size: 48px; margin-bottom: 10px;">⚠️</div><div>Error: ' + err.message + '</div></div>';
            });
    };
    
    // Load tables immediately
    loadTables();
    
    // Poll for updates every 3 seconds
    pollInterval = setInterval(loadTables, 3000);
    
    // Store interval ID on modal for cleanup
    modal.dataset.pollInterval = pollInterval;
    
    // Clear interval when modal is closed
    const originalDisplay = modal.style.display;
    const checkModalClosed = setInterval(() => {
        if (modal.style.display === 'none' || !document.body.contains(modal)) {
            clearInterval(pollInterval);
            clearInterval(checkModalClosed);
        }
    }, 500);
};
