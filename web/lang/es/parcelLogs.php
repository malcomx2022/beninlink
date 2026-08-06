<?php

use App\Enums\ParcelStatus;

return [
    ParcelStatus::PICKUP_ASSIGN         => 'Recogida asignada',
    ParcelStatus::PICKUP_RE_SCHEDULE    => 'Recogida de paquetes reprogramada',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN => 'Paquete recibido Recolector',
    ParcelStatus::RECEIVED_WAREHOUSE => 'Paquete recibido en Almacén',
    ParcelStatus::TRANSFER_TO_HUB        => 'Transferencia de paquetes al centro',
    ParcelStatus::RECEIVED_BY_HUB        => 'Recibido por concentrador',
    ParcelStatus::DELIVERY_MAN_ASSIGN => 'Repartidor asignado',
    ParcelStatus::DELIVERY_RE_SCHEDULE => 'Envío reprogramado',

    ParcelStatus::DELIVER => 'Entregar',
    ParcelStatus::RETURN_TO_COURIER => 'Regresar al mensajero',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT => 'Devolver asignar a la comerciante',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE => 'Volver a asignar al comerciante Reprogramar',

    ParcelStatus::DELIVERED => 'Entregada',
    ParcelStatus::PARTIAL_DELIVERED => 'Entregado parcial',
    ParcelStatus::RETURN_WAREHOUSE => 'Almacén de devolución',
    ParcelStatus::ASSIGN_MERCHANT => 'Asignar comerciante',
    ParcelStatus::RETURNED_MERCHANT => 'Comerciante devuelta',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT => 'Devolución recibida por la comerciante',

    'hub_name'                      => 'Nombre del concentrador',
    'hub_phone'                      => 'Teléfono concentrador',
    'delivery_man'                  => 'Repartidor',
    'delivery_man_phone'            => 'Teléfono del repartidor'


];
