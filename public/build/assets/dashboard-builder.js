cat > public/build/assets/dashboard-builder.js << 'EOF'
// Basic dashboard builder fallback
console.log('Dashboard Builder Loading...');

// Simple fallback if Vue build fails
if (typeof Vue === 'undefined') {
    window.Vue = {
        createApp: function() {
            return {
                mount: function() {
                    console.log('Vue fallback mounted');
                    document.getElementById('dashboard-builder-app').innerHTML = 
                    '<div class="p-8 text-center">' +
                    '<h1 class="text-2xl font-bold mb-4">Dashboard Builder</h1>' +
                    '<p class="text-gray-600">Building assets... Please refresh the page.</p>' +
                    '</div>';
                }
            }
        }
    }
}
EOF