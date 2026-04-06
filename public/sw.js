/* Self-destructing Service Worker */
/* This script unregisters the service worker and clears any related caches. */

self.addEventListener('install', function(e) {
    self.skipWaiting();
});

self.addEventListener('activate', function(e) {
    self.registration.unregister()
        .then(function() {
            return self.clients.matchAll();
        })
        .then(function(clients) {
            clients.forEach(client => {
                if (client.url && 'navigate' in client) {
                    client.navigate(client.url);
                }
            });
            console.log('Service Worker unregistered successfully.');
        })
        .catch(function(err) {
            console.error('Service Worker unregistration failed:', err);
        });
});

/* Fallback: basic pass-through for any remaining fetch events before unregistration completes */
self.addEventListener('fetch', function(event) {
    event.respondWith(fetch(event.request));
});
