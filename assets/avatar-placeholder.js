/**
 * Avatar Placeholder Generator
 * Creates default avatars with first letter of name when image fails to load
 */

// Extract first letter from name (ignore symbols and emojis)
function getFirstLetter(name) {
    if (!name || typeof name !== 'string') return '?';
    
    // Remove emojis and special characters, keep only letters and numbers
    const cleanName = name.replace(/[^\p{L}\p{N}\s]/gu, '').trim();
    
    // Get first character that is a letter
    for (let i = 0; i < cleanName.length; i++) {
        const char = cleanName[i];
        // Check if it's a letter (Arabic, English, or any language)
        if (/[\p{L}]/u.test(char)) {
            return char.toUpperCase();
        }
    }
    
    // If no letter found, try to get first alphanumeric character
    for (let i = 0; i < cleanName.length; i++) {
        const char = cleanName[i];
        if (/[\p{L}\p{N}]/u.test(char)) {
            return char.toUpperCase();
        }
    }
    
    // Fallback to first character of original name if all else fails
    return name.charAt(0).toUpperCase() || '?';
}

// Create default avatar with first letter of name
function createDefaultAvatar(name, size = 40) {
    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d');
    
    // Get first letter (clean from symbols)
    const firstLetter = getFirstLetter(name);
    
    // Generate color based on name
    const colors = [
        '#1877f2', // Facebook blue
        '#42b72a', // Green
        '#f59e0b', // Orange
        '#ef4444', // Red
        '#8b5cf6', // Purple
        '#06b6d4', // Cyan
        '#ec4899', // Pink
        '#10b981', // Emerald
        '#f97316', // Orange
        '#3b82f6'  // Blue
    ];
    
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    const colorIndex = Math.abs(hash) % colors.length;
    
    // Draw background circle
    ctx.fillStyle = colors[colorIndex];
    ctx.beginPath();
    ctx.arc(size / 2, size / 2, size / 2, 0, Math.PI * 2);
    ctx.fill();
    
    // Draw letter - centered properly
    ctx.fillStyle = '#ffffff';
    ctx.font = `bold ${size * 0.5}px -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    
    // Draw text at exact center
    ctx.fillText(firstLetter, size / 2, size / 2);
    
    return canvas.toDataURL();
}

// Initialize avatar placeholders
function initAvatarPlaceholders() {
    // Find all avatar images
    const avatarSelectors = [
        '.user-avatar',
        '.friend-avatar',
        '.friend-avatar-small',
        '.user-mini-avatar',
        '.topbar-avatar',
        '.post-avatar',
        '.comment-avatar',
        '.message-avatar',
        '.notification-avatar',
        '.group-avatar',
        '.member-avatar',
        '.search-result-avatar',
        'img[class*="avatar"]'
    ];
    
    const avatars = document.querySelectorAll(avatarSelectors.join(', '));
    
    avatars.forEach(img => {
        // Skip if already processed
        if (img.dataset.placeholderInit) return;
        img.dataset.placeholderInit = 'true';
        
        // Handle error event
        img.addEventListener('error', function() {
            // Get name from various sources
            let name = 'User';
            
            // Try alt attribute
            if (this.alt && this.alt.trim()) {
                name = this.alt.trim();
            }
            // Try data-name attribute
            else if (this.dataset.name) {
                name = this.dataset.name;
            }
            // Try adjacent text element
            else {
                const nameElement = this.parentElement.querySelector('[class*="name"]') || 
                                  this.nextElementSibling || 
                                  this.parentElement.querySelector('a, span, div');
                if (nameElement && nameElement.textContent.trim()) {
                    name = nameElement.textContent.trim();
                }
            }
            
            // Get size from image dimensions or class
            let size = this.width || this.offsetWidth || 40;
            if (size === 0 || size < 10) {
                // Fallback sizes based on class
                if (this.classList.contains('profile-avatar')) {
                    size = 168;
                } else if (this.classList.contains('user-avatar') || this.classList.contains('topbar-avatar')) {
                    size = 40;
                } else if (this.classList.contains('post-avatar')) {
                    size = 40;
                } else if (this.classList.contains('comment-avatar')) {
                    size = 32;
                } else if (this.classList.contains('search-result-avatar')) {
                    size = 56;
                } else if (this.classList.contains('friend-avatar-small')) {
                    size = 40;
                } else {
                    size = 40;
                }
            }
            
            // Create and set placeholder
            this.src = createDefaultAvatar(name, size);
            this.onerror = null; // Prevent infinite loop
            
            // Add loaded class for smooth transition
            setTimeout(() => {
                this.classList.add('avatar-loaded');
            }, 10);
        });
        
        // Trigger error check if image is already broken
        if (!img.complete || img.naturalHeight === 0) {
            const errorEvent = new Event('error');
            img.dispatchEvent(errorEvent);
        }
    });
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAvatarPlaceholders);
} else {
    initAvatarPlaceholders();
}

// Re-initialize when new content is added (for dynamic content)
const observer = new MutationObserver((mutations) => {
    let shouldReinit = false;
    mutations.forEach((mutation) => {
        if (mutation.addedNodes.length > 0) {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1 && (node.tagName === 'IMG' || node.querySelector('img'))) {
                    shouldReinit = true;
                }
            });
        }
    });
    if (shouldReinit) {
        initAvatarPlaceholders();
    }
});

// Start observing
observer.observe(document.body, {
    childList: true,
    subtree: true
});

// Export for manual use
window.createDefaultAvatar = createDefaultAvatar;
window.initAvatarPlaceholders = initAvatarPlaceholders;

// Function to generate placeholder for specific image
window.generateAvatarPlaceholder = function(img) {
    if (!img || img.dataset.placeholderInit) return;
    img.dataset.placeholderInit = 'true';
    
    img.addEventListener('error', function() {
        let name = 'User';
        if (this.alt && this.alt.trim()) {
            name = this.alt.trim();
        } else if (this.dataset.name) {
            name = this.dataset.name;
        }
        
        // Get size from image dimensions or class
        let size = this.width || this.offsetWidth || 40;
        if (size === 0) {
            // Fallback sizes based on class
            if (this.classList.contains('profile-avatar')) {
                size = 168;
            } else if (this.classList.contains('user-avatar') || this.classList.contains('topbar-avatar')) {
                size = 40;
            } else if (this.classList.contains('post-avatar')) {
                size = 40;
            } else if (this.classList.contains('comment-avatar')) {
                size = 32;
            } else if (this.classList.contains('search-result-avatar')) {
                size = 56;
            } else if (this.classList.contains('friend-avatar-small')) {
                size = 40;
            } else {
                size = 40;
            }
        }
        
        // Create and set placeholder
        this.src = createDefaultAvatar(name, size);
        this.onerror = null; // Prevent infinite loop
        
        // Add loaded class for smooth transition
        setTimeout(() => {
            this.classList.add('avatar-loaded');
        }, 10);
    });
    
    // Trigger error check if image is already broken
    if (!img.complete || img.naturalHeight === 0) {
        const errorEvent = new Event('error');
        img.dispatchEvent(errorEvent);
    }
};
