<?php

use App\Enums\ParcelStatus;

return array (
    ParcelStatus::PENDING                                => 'Pendiente',
    ParcelStatus::PICKUP_ASSIGN                          => 'Asignación de recogida',
    ParcelStatus::PICKUP_ASSIGN_CANCEL                   => 'Recogida Asignada Cancelar',
    ParcelStatus::PICKUP_RE_SCHEDULE_CANCEL              => 'Recoger Reprogramar Cancelar',
    ParcelStatus::PICKUP_RE_SCHEDULE                     => 'Reprogramación de recogida',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN                 => 'Recibido por el recolector',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN_CANCEL          => 'Recibido por el recolector Cancelar',
    ParcelStatus::RECEIVED_WAREHOUSE                     => 'Almacén recibido',
    ParcelStatus::RECEIVED_WAREHOUSE_CANCEL              => 'Almacén recibido Cancelar',
    ParcelStatus::RECEIVED_BY_HUB_CANCEL                 => 'Recibido por hub cancelar',
    ParcelStatus::TRANSFER_TO_HUB                        => 'Transferir al centro',
    ParcelStatus::TRANSFER_TO_HUB_CANCEL                 => 'Transferir al centro cancelar',
    ParcelStatus::RECEIVED_BY_HUB                        => 'Recibido por centro',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => 'Asignación de repartidor',
    ParcelStatus::DELIVERY_MAN_ASSIGN_CANCEL             => 'Repartidor Asignar Cancelar',
    ParcelStatus::DELIVERY_RE_SCHEDULE_CANCEL            => 'Entrega Reprogramar Cancelar',
    ParcelStatus::DELIVERY_RE_SCHEDULE                   => 'Envío reprogramado',
    ParcelStatus::PARTIAL_DELIVERED_CANCEL               => 'Cancelar entrega parcial',
    ParcelStatus::RETURN_TO_COURIER                      => 'Regresar al mensajero',
    ParcelStatus::RETURN_TO_COURIER_CANCEL               => 'Devolver al servicio de mensajería cancelar',
    ParcelStatus::PARTIAL_DELIVERED                      => 'Entregado parcial',
    ParcelStatus::DELIVERED                              => 'Entregada',
    ParcelStatus::DELIVERED_CANCEL                       => 'Entregado Cancelar',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT_CANCEL       => 'Devolver asignar a la comerciante cancelar',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => 'Devolver asignar a la comerciante',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE_CANCEL     => 'Devolver asignar a la comerciante reprogramar cancelar',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE            => 'Devolver la asignación al comerciante Reprogramar ',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT            => 'Devolución recibida por la comerciante',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT_CANCEL     => 'Devolución recibida por la comerciante cancelar',
    ParcelStatus::DELIVER                                => 'Entregar',
    ParcelStatus::RETURN_WAREHOUSE                       => 'Almacén de retorno',
    ParcelStatus::ASSIGN_MERCHANT                        => 'Asignar comerciante',
    ParcelStatus::RETURNED_MERCHANT                      => 'Comerciante devuelta',

 
);
