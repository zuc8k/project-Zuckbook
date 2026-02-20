/**
 * Dark Mode System
 * Global dark mode that works across all pages
 */

// Check and apply dark mode on page load
function initDarkMode() {
    // Check if dark mode is enabled
    fetch('/backend/get_dark_mode.php')
        .then(res => res.json())
        .then(data => {
            if (data.dark_mode) {
                enableDarkMode();
            } else {
                disableDarkMode();
            }
        })
        .catch(err => {
            console.error('Failed to load dark mode preference:', err);
        });
}

// Enable dark mode
function enableDarkMode() {
    document.documentElement.classList.add('dark-mode');
    document.body.classList.add('dark-mode');
    localStorage.setItem('dark_mode', 'true');
}

// Disable dark mode
function disableDarkMode() {
    document.documentElement.classList.remove('dark-mode');
    document.body.classList.remove('dark-mode');
    localStorage.setItem('dark_mode', 'false');
}

// Toggle dark mode
function toggleDarkMode() {
    const isDark = document.body.classList.contains('dark-mode');
    
    if (isDark) {
        disableDarkMode();
    } else {
        enableDarkMode();
    }
    
    // Save to database
    fetch('/backend/update_dark_mode.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'dark_mode=' + (!isDark ? '1' : '0')
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            console.log('Dark mode preference saved');
        }
    })
    .catch(err => {
        console.error('Failed to save dark mode preference:', err);
    });
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDarkMode);
} else {
    initDarkMode();
}

// Export for use in other scripts
window.toggleDarkMode = toggleDarkMode;
window.enableDarkMode = enableDarkMode;
window.disableDarkMode = disableDarkMode;
