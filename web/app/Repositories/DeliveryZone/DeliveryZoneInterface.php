<?php

namespace App\Repositories\DeliveryZone;

use App\Models\Backend\DeliveryZone;
use Illuminate\Support\Collection;

interface DeliveryZoneInterface
{
    public function zones(): Collection;

    public function delais(): Collection;

    public function zoneExport(): ?DeliveryZone;

    public function pays(DeliveryZone $zone): Collection;

    public function categories(): Collection;

    public function tranches(?int $categoryId): array;

    public function grilleMarchand(int $merchantId): Collection;

    public function enregistrerZones(array $lignes): array;

    public function enregistrerDelais(array $lignes): int;

    public function enregistrerPays(DeliveryZone $zone, array $lignes): int;

    public function enregistrerGrille(int $categoryId, array $lignes): int;
}
