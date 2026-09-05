<?php

namespace App\Services\Push;

use App\Models\User;

/**
 * Contrat d'un transport de notifications poussées.
 *
 * Une implémentation ne décide jamais s'il *faut* notifier : elle livre, et
 * elle ne remonte pas d'exception — un push raté ne doit pas faire échouer
 * l'écriture métier qui l'a déclenché (même règle que `MerchantFeed`).
 */
interface PushGateway
{
    /**
     * Livre le message aux appareils de l'utilisateur.
     *
     * @return int nombre d'appareils effectivement adressés
     */
    public function toUser(User $user, PushMessage $message): int;
}
