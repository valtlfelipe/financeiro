const CACHE_NAME = 'financeiro-shell-v1';
const SHELL = ['/favicon.svg', '/manifest.webmanifest'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(SHELL)),
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter((key) => key !== CACHE_NAME)
                        .map((key) => caches.delete(key)),
                ),
            ),
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);
    const cacheableDestinations = ['style', 'script', 'font', 'image'];

    if (
        request.method !== 'GET' ||
        url.origin !== self.location.origin ||
        !cacheableDestinations.includes(request.destination)
    ) {
        return;
    }

    event.respondWith(
        caches.match(request).then((cached) => {
            const network = fetch(request).then((response) => {
                if (response.ok) {
                    const copy = response.clone();
                    void caches
                        .open(CACHE_NAME)
                        .then((cache) => cache.put(request, copy));
                }

                return response;
            });

            return cached ?? network;
        }),
    );
});
