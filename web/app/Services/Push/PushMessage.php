<?php

namespace App\Services\Push;

/**
 * Ce qu'un push transporte, indépendamment du service qui le livre.
 *
 * `data` voyage à côté du texte : c'est ce que l'app lit au toucher de la
 * notification pour ouvrir le bon écran (un colis, un relevé, une alerte).
 * Les mêmes clés que le fil en base (`kind`, `parcel_id`…) — l'app n'a pas
 * deux vocabulaires à connaître.
 */
class PushMessage
{
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
    ) {
    }
}
