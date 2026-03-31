const CACHE_NAME = 'uvchm_v1';
const STATIC_ASSETS = [
    '/',
    '/admin_theme/css/sb-admin-2.min.css',
    '/admin_theme/vendor/fontawesome-free/css/all.min.css',
    '/css/modern-theme.css',
    '/admin_theme/vendor/jquery/jquery.min.js',
    '/admin_theme/vendor/bootstrap/js/bootstrap.bundle.min.js',
    '/storage/settings/1753508439_UV Foundation (1).png'
];

// URLs to cache using Network First strategy (Dynamic Data)
const DYNAMIC_DATA_URLS = [
    '/admin/enquiries',
    '/admin/dashboard'
];

self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS))
    );
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.map((key) => { if (key !== CACHE_NAME) return caches.delete(key); })
        ))
    );
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Network First Strategy for Enquiries & Dashboard (Dynamic)
    if (DYNAMIC_DATA_URLS.some(path => url.pathname.startsWith(path))) {
        event.respondWith(
            fetch(event.request)
                .then((response) => {
                    // Update cache with fresh data
                    const resClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, resClone);
                    });
                    return response;
                })
                .catch(() => caches.match(event.request)) // Fallback to cache if offline
        );
    } else {
        // Stale-While-Revalidate for Static Assets
        event.respondWith(
            caches.match(event.request).then((res) => {
                const fetchPromise = fetch(event.request).then((networkRes) => {
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, networkRes.clone()));
                    return networkRes;
                });
                return res || fetchPromise;
            })
        );
    }
});
