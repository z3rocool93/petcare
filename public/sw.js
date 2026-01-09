// Nombre del caché (puedes cambiarlo al actualizar la app)
const CACHE_NAME = 'petcare-v1';

// Instalación del Service Worker
self.addEventListener('install', (event) => {
    console.log('SW: Instalado');
});

// Activación y limpieza de cachés antiguos
self.addEventListener('activate', (event) => {
    console.log('SW: Activado');
});

// Estrategia de respuesta: Red primero, si falla, buscar en caché
self.addEventListener('fetch', (event) => {
    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request))
    );
});
