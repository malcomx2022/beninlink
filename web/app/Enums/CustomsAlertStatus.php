<?php
namespace App\Enums;

/** Suivi d'une alerte douaniere : onglets « En cours » et « Traitees ». */
Interface CustomsAlertStatus{
    const PENDING   = 1;
    const RESOLVED  = 2;
}
