// 1. Incrementa este número cada vez que hagas un 'npm run build'
const CACHE_NAME = 'petcare-v2';

// Archivos básicos para que la APK abra aunque no haya internet (Offline fallback)
const ASSETS_TO_CACHE = [
    '/',
    '/offline', // Opcional: crea una vista simple offline.blade.php
];

// Instalación: Forzar que el nuevo SW tome el control de inmediato
self.addEventListener('install', (event) => {
    console.log('SW: Instalando versión', CACHE_NAME);
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS_TO_CACHE))
            .then(() => self.skipWaiting()) // Salta la espera y activa el nuevo SW
    );
});

// Activación: LIMPIEZA DE BASURA (Esto evita el error 404 de archivos viejos)
self.addEventListener('activate', (event) => {
    console.log('SW: Activado y limpiando cachés antiguos');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME) {
                        console.log('SW: Borrando caché antiguo:', cache);
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => self.clients.claim()) // Toma el control de las pestañas abiertas
    );
});

// Estrategia: Red primero con fallback a caché
self.addEventListener('fetch', (event) => {
    // No cachear peticiones POST (como el registro de mascotas) ni Firebase
    if (event.request.method !== 'GET' || event.request.url.includes('googleapis')) {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Si la red responde bien, guardamos una copia en caché (opcional)
                return response;
            })
            .catch(() => {
                // Si la red falla (offline), buscamos en el caché
                return caches.match(event.request);
            })
    );
});
