const CACHE_NAME = 'debt-ms-v1';
const ASSETS_CACHE = 'debt-ms-assets-v1';

// Assets to cache
const assetsToCache = [
    '/',
    '/auth/login.php',
    '/manifest.json',
    '/icons/icon.svg',
    '/icons/icon-maskable.svg',
    'https://cdn.tailwindcss.com'
];

// Install event - cache assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(ASSETS_CACHE)
            .then(cache => cache.addAll(assetsToCache))
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        Promise.all([
            self.clients.claim(),
            caches.keys().then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== CACHE_NAME && cacheName !== ASSETS_CACHE) {
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
        ])
    );
});

// Fetch event - network first, then cache
self.addEventListener('fetch', event => {
    // Skip cross-origin requests
    if (!event.request.url.startsWith(self.location.origin)) {
        return;
    }

    // Handle API requests differently
    if (event.request.url.includes('/api/')) {
        event.respondWith(
            fetch(event.request)
                .catch(() => {
                    return caches.match(event.request);
                })
        );
        return;
    }

    // For page navigations, try network first then cache
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME)
                        .then(cache => cache.put(event.request, responseClone));
                    return response;
                })
                .catch(() => {
                    return caches.match(event.request)
                        .then(response => response || caches.match('/'));
                })
        );
        return;
    }

    // For other requests, try cache first then network
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                if (response) {
                    return response;
                }

                return fetch(event.request).then(response => {
                    // Don't cache non-successful responses
                    if (!response || response.status !== 200) {
                        return response;
                    }

                    const responseClone = response.clone();
                    caches.open(CACHE_NAME)
                        .then(cache => cache.put(event.request, responseClone));
                    return response;
                });
            })
    );
});
