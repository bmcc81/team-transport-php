self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open("tt-tracker-v1").then((cache) => cache.addAll([
      "/tracker/",
      "/tracker/index.html",
      "/tracker/tracker.js",
      "/tracker/manifest.webmanifest",
    ]))
  );
  self.skipWaiting();
});

self.addEventListener("activate", (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener("fetch", (event) => {
  event.respondWith(
    caches.match(event.request).then((cached) => cached || fetch(event.request))
  );
});
