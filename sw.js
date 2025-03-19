const CACHE_NAME = 'loteria-app-v1';
const urlsToCache = [
    '/',
    '/index.php',
    '/revendedor/index.php',
    '/admin/gerenciar_resultados.php',
    '/css/style.css',
    '/assets/images/logos/megasena.png',
    '/assets/images/logos/lotofacil.png',
    '/assets/images/logos/quina.png',
    '/assets/images/logos/lotomania.png',
    '/assets/images/logos/timemania.png',
    '/assets/images/logos/duplasena.png',
    '/assets/images/logos/maismilionaria.png',
    '/assets/images/logos/diadesorte.png',
    '/assets/images/logos/default.png',
    '/assets/images/icon-192x192.png',
    '/assets/images/icon-512x512.png',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/webfonts/fa-solid-900.woff2'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(urlsToCache))
    );
});

self.addEventListener('activate', function(event) {
    const cacheWhitelist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

self.addEventListener('fetch', function(event) {
    if (event.request.method !== 'GET') return;
    
    if (event.request.url.includes('/api/')) return;
    
    event.respondWith(
        fetch(event.request)
            .then(function(response) {
                if (response.status === 200) {
                    const responseToCache = response.clone();
                    caches.open(CACHE_NAME)
                        .then(function(cache) {
                            cache.put(event.request, responseToCache);
                        });
                }
                return response;
            })
            .catch(function() {
                return caches.match(event.request)
                    .then(function(response) {
                        if (response) {
                            return response;
                        }
                        
                        if (event.request.url.match(/\.(jpg|jpeg|png|gif|svg)$/)) {
                            return caches.match('/assets/images/logos/default.png');
                        }
                        
                        return caches.match('/offline.html');
                    });
            })
    );
}); 