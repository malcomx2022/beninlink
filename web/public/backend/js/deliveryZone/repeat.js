/**
 * Lignes répétables des écrans de zones (D4, étape 4).
 *
 * Une zone est une ligne, pas une colonne : ajouter une zone, un délai, un pays
 * ou une tranche ne doit demander ni migration ni passage sur les écrans. Ce
 * fichier fait ce seul geste — cloner un gabarit `<template>`, y numéroter les
 * champs, et retirer une ligne qui n'a pas encore été enregistrée.
 *
 * L'index continue au-delà des lignes déjà en base (`data-repeat-start`) : deux
 * lignes ne partagent jamais le même index, sinon le tableau reçu côté serveur
 * en perdrait une en silence.
 */
(function () {
    'use strict';

    function prochainIndex(bouton) {
        var courant = parseInt(bouton.getAttribute('data-repeat-index') || bouton.getAttribute('data-repeat-start') || '0', 10);
        bouton.setAttribute('data-repeat-index', courant + 1);
        return courant;
    }

    function ajouter(bouton) {
        var corps = document.querySelector(bouton.getAttribute('data-repeat-add'));
        var gabarit = document.getElementById(bouton.getAttribute('data-repeat-template'));

        if (!corps || !gabarit) {
            return;
        }

        var index = prochainIndex(bouton);
        var html = gabarit.innerHTML.split('__INDEX__').join(String(index));
        var hote = document.createElement('tbody');
        hote.innerHTML = html.trim();

        var vide = corps.querySelector('#zones-empty');
        if (vide) {
            vide.remove();
        }

        while (hote.firstElementChild) {
            corps.appendChild(hote.firstElementChild);
        }
    }

    document.addEventListener('click', function (evenement) {
        var ajout = evenement.target.closest('[data-repeat-add]');
        if (ajout) {
            evenement.preventDefault();
            ajouter(ajout);
            return;
        }

        var retrait = evenement.target.closest('[data-repeat-remove]');
        if (retrait) {
            evenement.preventDefault();
            var ligne = retrait.closest('tr');
            if (ligne) {
                ligne.remove();
            }
        }
    });
})();
