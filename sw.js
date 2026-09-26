// sw.js - Dogodek FETCH
self.addEventListener('fetch', (event) => {
    // Zahteve, ki niso GET (npr. POST v transactions.php, login.php), naj gredo neposredno mimo SW
    if (event.request.method !== 'GET') {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Če je omrežni odgovor veljaven, ga vrnemo
                return response;
            })
            .catch(() => {
                // Če omrežje spodleti (offline način), poišči v predpomnilniku
                return caches.match(event.request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // Če ni v predpomnilniku, vrni prazen/rezervni odgovor namesto rušenja
                    return new Response('Povezava ni na voljo.', {
                        status: 503,
                        statusText: 'Service Unavailable',
                        headers: new Headers({ 'Content-Type': 'text/plain; charset=utf-8' })
                    });
                });
            })
    );
});