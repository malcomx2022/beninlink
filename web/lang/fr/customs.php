<?php

return [
    'title'                 => 'Alertes douanières',
    'rules'                 => 'Règles douanières',
    'export'                => 'Export UEMOA / CEDEAO',
    'country'               => 'Pays de destination',
    'category'              => 'Catégorie de marchandise',
    'level'                 => 'Niveau',
    'required_document'     => 'Document requis',
    'message'               => 'Message',
    'pending'               => 'En cours',
    'resolved'              => 'Traitée',
    'resolved_msg'          => 'Alerte marquée comme traitée.',
    'not_found'             => 'Alerte introuvable.',
    'empty'                 => 'Aucune alerte douanière.',

    'level_1'               => 'Info',
    'level_2'               => 'Avertissement',
    'level_3'               => 'Bloquant',

    /** Renvoyé au marchand quand la création est refusée. */
    'blocked'               => "Livraison interdite vers ce pays pour cette catégorie sans le document requis : :document",

    'category_alimentaire'    => 'Produits alimentaires',
    'category_textile'        => 'Textile et habillement',
    'category_electronique'   => 'Électronique',
    'category_cosmetique'     => 'Cosmétiques',
    'category_pharmaceutique' => 'Produits pharmaceutiques',
    'category_autre'          => 'Autres marchandises',

    'added_msg'             => 'Règle douanière ajoutée.',
    'update_msg'            => 'Règle douanière mise à jour.',
    'delete_msg'            => 'Règle douanière supprimée.',
    'error_msg'             => 'Une erreur est survenue.',

    /** Mention sous le titre des règles : le référentiel livré reste à faire valider. */
    'referentiel_a_valider' => '— référentiel de départ, à faire valider par un transitaire.',
];
