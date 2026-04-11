// Online status tracking
document.addEventListener('DOMContentLoaded', function() {
    // Track user online status
    let isOnline = true;

    window.addEventListener('online', function() {
        isOnline = true;
        console.log('User is online');
    });

    window.addEventListener('offline', function() {
        isOnline = false;
        console.log('User is offline');
    });

    // Optional: Send heartbeat to server
    setInterval(function() {
        if (isOnline && document.hidden === false) {
            // Could send a heartbeat request here if needed
        }
    }, 30000); // Every 30 seconds
});
