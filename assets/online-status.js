// Online Status System - Real-time user status tracking

// Update current user's last_seen
function updateMyLastSeen() {
    fetch('/backend/update_last_seen.php', {
        method: 'POST'
    }).catch(err => console.error('Failed to update last seen:', err));
}

// Get user status by ID
async function getUserStatus(userId) {
    try {
        const response = await fetch(`/backend/get_user_status.php?user_id=${userId}`);
        const data = await response.json();
        return data;
    } catch (err) {
        console.error('Failed to get user status:', err);
        return null;
    }
}

// Update status display for a user element
async function updateStatusDisplay(userId, element) {
    const status = await getUserStatus(userId);
    if (!status || !status.success) return;
    
    const statusTextEl = element.querySelector('.user-status-text');
    const statusDotEl = element.querySelector('.user-status-dot');
    const onlineIndicatorEl = element.querySelector('.online-indicator');
    
    if (statusTextEl) {
        if (status.is_online) {
            statusTextEl.textContent = 'Active now';
            statusTextEl.style.color = '#31a24c';
        } else {
            statusTextEl.textContent = status.status_text + ' • Offline';
            statusTextEl.style.color = '#8a8d91';
        }
    }
    
    if (statusDotEl) {
        if (status.is_online) {
            statusDotEl.style.background = '#31a24c';
            statusDotEl.style.display = 'block';
        } else {
            statusDotEl.style.background = '#8a8d91';
            statusDotEl.style.display = 'block';
        }
    }
    
    if (onlineIndicatorEl) {
        if (status.is_online) {
            onlineIndicatorEl.style.display = 'block';
        } else {
            onlineIndicatorEl.style.display = 'none';
        }
    }
}

// Update all user statuses on page
function updateAllUserStatuses() {
    const userElements = document.querySelectorAll('[data-user-id]');
    userElements.forEach(element => {
        const userId = element.getAttribute('data-user-id');
        if (userId) {
            updateStatusDisplay(userId, element);
        }
    });
}

// Initialize status system
function initOnlineStatus() {
    // Update current user's last_seen immediately
    updateMyLastSeen();
    
    // Update current user's last_seen every 30 seconds
    setInterval(updateMyLastSeen, 30000);
    
    // Update all user statuses every 10 seconds
    setInterval(updateAllUserStatuses, 10000);
    
    // Update on user interaction
    document.addEventListener('click', updateMyLastSeen);
    document.addEventListener('keypress', updateMyLastSeen);
    
    // Update before page unload
    window.addEventListener('beforeunload', function() {
        navigator.sendBeacon('/backend/update_last_seen.php');
    });
    
    // Update when page becomes visible
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            updateMyLastSeen();
            updateAllUserStatuses();
        }
    });
    
    // Initial status update
    updateAllUserStatuses();
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initOnlineStatus);
} else {
    initOnlineStatus();
}
