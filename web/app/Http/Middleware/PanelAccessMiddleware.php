<?php

namespace App\Http\Middleware;

use App\Enums\UserType;
use Closure;
use Illuminate\Http\Request;

/**
 * S41 — cloisonne les TROIS PANNEAUX du web par type de compte.
 *
 * `routes/web.php` place `admin/*` et `merchant/*` dans le MEME groupe
 * `auth` + `subscriptionCheck`, sans aucune garde sur `user_type` : le seul
 * separateur entre le back-office et le panneau marchand etait le
 * `hasPermission` pose route par route. La ou il manque — 60 routes, dont 38
 * ECRITURES — la porte etait donc ouverte a tout compte authentifie.
 *
 * Mesure par appel HTTP avant correctif, sur `POST admin/parcel/priority/update`
 * (une ecriture) : 200 pour un agent sans aucun droit, 200 pour un MARCHAND,
 * 200 pour un LIVREUR. `GET admin/payout` — la page de paiement aux marchands —
 * repondait 200 a un marchand. Et dans l'autre sens, un agent ou un livreur
 * atteignait le panneau marchand, ou il recoltait un 500 : un refus annonce
 * comme une panne, la famille de S37.
 *
 * ⚠️ LE MEME TROU A DEJA ETE FERME SUR L'API, EN S5. `UserTypeMiddleware`
 * existe et son docbloc decrit ce symptome exact : « le socle placait
 * `deliveryman/*` et les routes marchand dans le meme groupe `auth:sanctum`,
 * sans garde sur `user_type` […] le controleur marchand repondait alors 500,
 * pas un refus ». Le web est reste ouvert pendant tout le chantier.
 *
 * POURQUOI UNE CLASSE A PART et non `userType` reutilise : ce middleware-la
 * verifie en second l'ABILITY DU JETON (`tokenCan`) et repond dans l'enveloppe
 * JSON de l'API. Une session web n'a pas de jeton, et un humain n'attend pas
 * une enveloppe. Surtout, `UserTypeMiddleware::scopeOf()` / `abilitiesFor()`
 * servent a EMETTRE les abilities a la connexion : y ajouter un type
 * « back-office » changerait les jetons emis aux administrateurs. Le concept est
 * partage, l'implementation ne peut pas l'etre.
 *
 * LE PREFIXE D'URI DIT LE PANNEAU, PAS LE NOM DE ROUTE. Une cinquantaine de
 * routes NOMMEES `merchant.*` vivent sous `admin/` : ce sont les ecrans du
 * back-office *a propos* des marchands (`merchant.shops.index` =
 * `admin/merchant/{id}/shops/index`). C'est le prefixe qui decide, et c'est
 * pour cela que la garde se pose sur le GROUPE.
 *
 * Usage : `panel:back-office`, `panel:merchant`, `panel:super-admin`.
 */
class PanelAccessMiddleware
{
    /** Panneau → types de compte qui y ont leur place. */
    public const PANNEAUX = [
        'back-office' => [UserType::ADMIN, UserType::SUPER_ADMIN],
        'merchant'    => [UserType::MERCHANT],
        'super-admin' => [UserType::SUPER_ADMIN],
    ];

    public function handle(Request $request, Closure $next, string ...$panneaux)
    {
        $autorises = [];
        foreach ($panneaux as $panneau) {
            foreach (self::PANNEAUX[$panneau] ?? [] as $type) {
                $autorises[] = (int) $type;
            }
        }

        $utilisateur = $request->user();
        abort_if(blank($utilisateur), 403);

        // FERME PAR DEFAUT, et c'est la liste vide qui le fait : un nom de panneau
        // inconnu — une faute de frappe dans une declaration de route — laisse
        // `$autorises` vide, et `in_array` sur un tableau vide est toujours faux.
        // Un `abort_if(blank($autorises))` explicite a d'abord ete ecrit ici ; le
        // sabotage a montre qu'il etait REDONDANT (le retirer ne changeait aucun
        // verdict), et une ligne qu'aucun test ne peut distinguer n'a pas sa place.
        // Le test `test_an_unknown_panel_name_refuses_instead_of_opening` prouve le
        // comportement, qui est ce qui compte.
        //
        // ⚠️ Le `true` de `in_array` n'est PAS prouve : `user_type` est une colonne
        // entiere, et la comparaison relachee rend le meme verdict sur tous les cas
        // exerces — un sabotage l'a montre en restant vert. Il est garde par hygiene,
        // contre un changement de cast futur, et non parce qu'un test le tient.
        abort_unless(in_array((int) $utilisateur->user_type, $autorises, true), 403);

        return $next($request);
    }
}
