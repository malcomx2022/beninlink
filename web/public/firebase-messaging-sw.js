/*
 * S22 / D12 — service worker de retrait.
 *
 * Ce fichier a porté le push navigateur du back-office : il inscrivait le
 * navigateur de chaque agent au projet Firebase de l'éditeur et attendait des
 * messages d'une API arrêtée par Google en juin 2024.
 *
 * On ne peut pas se contenter de retirer le code de la page : un service
 * worker déjà installé **survit** au déploiement et resterait enregistré dans
 * les navigateurs des agents, avec les clés d'un projet tiers. Le fichier
 * garde donc son chemin — c'est celui que ces navigateurs interrogent — et ne
 * fait plus qu'une chose : se désinscrire, puis recharger les onglets ouverts
 * pour qu'ils repartent sans lui.
 *
 * À supprimer une fois le parc renouvelé (quelques semaines suffisent).
 */
self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil(
        self.registration
            .unregister()
            .then(() => self.clients.matchAll({ type: 'window' }))
            .then((clients) => clients.forEach((client) => client.navigate(client.url)))
    );
});
