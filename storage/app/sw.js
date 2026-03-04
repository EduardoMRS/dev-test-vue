// Service Worker para AppLaravel PWA
// Web Push Notifications + Cache Strategy

const SW_VERSION = '{$appVersion}';
const STATIC_CACHE = `v${SW_VERSION}`;
const DYNAMIC_CACHE = `v${SW_VERSION}`;

console.log(`[SW] Service Worker carregado - {$appName} PWA v${SW_VERSION}`);

// Recursos para cache offline
const STATIC_ASSETS = [
    '/',
    '/favicon.ico'
];
// API paths that are safe to cache per-user (GET only). Add endpoints here
// that you want available offline / faster (e.g. trainings list/details).
const API_CACHE_WHITELIST = [

];

// Install event - Cache recursos est�ticos
self.addEventListener('install', (event) => {
    // Install: cache static assets
    event.waitUntil(
        Promise.all([
            caches.open(STATIC_CACHE).then(cache => {
                return cache.addAll(STATIC_ASSETS.map(url => {
                    return new Request(url, { cache: 'reload' });
                })).catch(err => {
                    // Sw: asset cache failed for some resources; fail silently
                    // console warnings are omitted intentionally for production service worker
                });
            }),
            self.skipWaiting()
        ])
    );
});

// Activate event
self.addEventListener('activate', (event) => {
    // Activate: cleanup old caches
    event.waitUntil(
        Promise.all([
            caches.keys().then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (![STATIC_CACHE, DYNAMIC_CACHE].includes(cacheName)) {
                            // Old cache removed silently in production
                            return caches.delete(cacheName);
                        }
                    })
                );
            }),
            self.clients.claim()
        ])
    );
});

// Push event
self.addEventListener('push', (event) => {
    // Push: show notification if data present
    if (!event.data) {
        return;
    }

    let notificationData;

    try {
        notificationData = event.data.json();
    } catch (e) {
        notificationData = {
            title: 'AppLaravel PWA',
            body: event.data.text() || 'Nova notificão recebida',
            icon: '/favicon.ico'
        };
    }

    const notificationOptions = {
        body: notificationData.body,
        icon: notificationData.icon || '/favicon.ico',
        badge: notificationData.badge || '/favicon.ico',
        tag: notificationData.tag || 'default',
        requireInteraction: notificationData.requireInteraction || false,
        vibrate: [200, 100, 200],
        data: {
            url: notificationData.url || '/',
            timestamp: Date.now()
        },
        actions: [{
            action: 'open',
            title: 'Abrir'
        }]
    };

    event.waitUntil(self.registration.showNotification(notificationData.title, notificationOptions));
});

// Notification click
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const data = event.notification.data || {};
    const urlToOpen = data.url || '/';

    event.waitUntil(
        self.clients.matchAll({ type: 'window' }).then(clients => {
            for (const client of clients) {
                if (client.url.includes(location.origin)) {
                    return client.focus();
                }
            }
            return self.clients.openWindow(urlToOpen);
        })
    );
});

// Fetch event - Cache strategy
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (!request.url.startsWith('http')) {
        return;
    }

    // API calls - Network First
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(
            fetch(request).then(response => {
                // Only cache responses for whitelisted API paths
                const shouldCacheApi = API_CACHE_WHITELIST.some(prefix => url.pathname.startsWith(prefix));
                if (response.status === 200 && request.method === 'GET') {
                    // Only cache successful GET responses for whitelisted API endpoints.
                    if (shouldCacheApi) {
                        const responseClone = response.clone();
                        caches.open(DYNAMIC_CACHE).then(cache => {
                            try {
                                cache.put(request, responseClone);
                            } catch (e) {
                                // If cache.put fails (edge cases), fail silently
                            }
                        });
                    }
                }
                return response;
            }).catch(() => {
                return caches.match(request);
            })
        );
        return;
    }

    // Páginas - Network First
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).then(response => {
                // navigation requests are typically GET; guard just in case
                if (request.method === 'GET') {
                    const responseClone = response.clone();
                    caches.open(DYNAMIC_CACHE).then(cache => {
                        try {
                            cache.put(request, responseClone);
                        } catch (e) {
                            // ignore cache errors
                        }
                    });
                }
                return response;
            }).catch(() => {
                return caches.match(request) || caches.match('/');
            })
        );
        return;
    }

    // Outros recursos - Cache First
    event.respondWith(
        caches.match(request).then(response => {
            return response || fetch(request);
        })
    );
});
