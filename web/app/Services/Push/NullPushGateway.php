<?php

namespace App\Services\Push;

use App\Models\User;

/**
 * Transport « aucun » : le fil en base reste écrit, rien ne part au dehors.
 *
 * C'est le pilote des tests et d'une installation sans app mobile. Il rend le
 * push désactivable sans que le code appelant ait à se poser la question.
 */
class NullPushGateway implements PushGateway
{
    public function toUser(User $user, PushMessage $message): int
    {
        return 0;
    }
}
