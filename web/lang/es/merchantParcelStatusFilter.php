<?php

use App\Enums\ParcelStatus;

return array (
    ParcelStatus::PENDING                                => 'Pendiente',
    ParcelStatus::PICKUP_ASSIGN                          => 'Asignación de recogida',
    ParcelStatus::RECEIVED_WAREHOUSE                     => 'Almacén recibido',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => 'Asignación de repartidor',
    ParcelStatus::PARTIAL_DELIVERED                      => 'Entregado parcial',
    ParcelStatus::DELIVERED                              => 'Entregada',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => 'Devolver asignar a la comerciante',

);
