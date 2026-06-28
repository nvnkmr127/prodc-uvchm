/**
 * Main Application Entry Point
 *
 * This file is the entry point for the Vite build process.
 * It imports and initializes the main application resources.
 */

// Import CSS
import '../css/app.css';
// Load Alpine.js dynamically only when needed
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('[x-data]')) {
        import('alpinejs').then((Alpine) => {
            window.Alpine = Alpine.default;
            window.Alpine.start();
        });
    }
});

// Initialize application when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('College Management System - Application loaded successfully');
});

// Export for use in other modules
export default {
    init() {
        console.log('App initialized');
    }
};
