// Service worker: makes the app installable and shows an offline page.
// Pages are always fetched from the network (data is live and per-user).
const CACHE = 'paivakoti-v1';
const OFFLINE = 'offline.html';
const PRECACHE = [OFFLINE, 'icons/icon-192.png'];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', e => {
  if (e.request.mode === 'navigate') {
    e.respondWith(fetch(e.request).catch(() => caches.match(OFFLINE)));
  }
});
