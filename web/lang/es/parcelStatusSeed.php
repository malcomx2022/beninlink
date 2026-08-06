<?php
  use App\Enums\ParcelStatus;
return [

    ParcelStatus::PICKUP_ASSIGN                          => 'Asignación de recogida',
    ParcelStatus::PICKUP_RE_SCHEDULE                     => 'Reprogramación de recogida',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN                 => 'Recibido por el recolector',
    ParcelStatus::RECEIVED_WAREHOUSE                     => 'Almacén recibido',
    ParcelStatus::TRANSFER_TO_HUB                        => 'Transferir al centro',
    ParcelStatus::RECEIVED_BY_HUB                        => 'Recibido por centro',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => 'Asignación de repartidor',
    ParcelStatus::DELIVERY_RE_SCHEDULE                   => 'Envío reprogramado',
    ParcelStatus::RETURN_TO_COURIER                      => 'Regresar al mensajero',
    ParcelStatus::PARTIAL_DELIVERED                      => 'Entregado parcial',
    ParcelStatus::DELIVERED                              => 'Entregada',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => 'Devolver asignar a la comerciante',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE            => ' Devolver la asignación al comerciante Reprogramar',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT            => 'Devolución recibida por la comerciante',
    ParcelStatus::RETURN_WAREHOUSE                       => 'Almacén de devolución',
    ParcelStatus::ASSIGN_MERCHANT                        => 'Asignar comerciante',
    ParcelStatus::RETURNED_MERCHANT                      => 'Comerciante devuelta',

];