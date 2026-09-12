/* Service Worker ORGEST — coquille minimale pour rendre l'app installable
   et fournir une page hors-ligne simple. */
const CACHE = 'orgest-v1';
const APP_SHELL = [
    '/assets/css/app.css',
    '/assets/css/orgest.css',
    '/assets/js/app.js',
    '/assets/images/icon-192.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(APP_SHELL)).catch(() => {})
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const req = event.request;

    // On ne met en cache que les GET même origine (jamais les requêtes POST/auth).
    if (req.method !== 'GET' || new URL(req.url).origin !== self.location.origin) {
        return;
    }

    // Cache-first pour les assets statiques ; réseau pour le reste.
    if (req.url.includes('/assets/')) {
        event.respondWith(
            caches.match(req).then((cached) => cached || fetch(req).then((res) => {
                const copy = res.clone();
                caches.open(CACHE).then((cache) => cache.put(req, copy)).catch(() => {});
                return res;
            }).catch(() => cached))
        );
    }
});
