<?php

namespace App\Traits;

/**
 * S51 — la meme regle que S48, sur les portes de CREATION.
 *
 * S45 l'a trouvee sur l'agent d'un mouvement de colis, S46 sur les catalogues
 * d'un colis, S47 sur le catalogue d'un compte, S48 sur les contreparties d'une
 * ecriture comptable, S49 sur le marchand d'une boutique. Six fois de suite la
 * meme forme : **la ressource est gardee, le SECOND identifiant ne l'est pas.**
 *
 * S48 avait extrait la regle pour le module comptable, mais sa carte est
 * fermee : elle nomme `merchant_id`, `account_id`, `hub_id`… Or les portes de
 * creation du socle appellent les memes ressources par d'AUTRES noms de champ
 * — `merchant`, `from_account`, `merchant_account`, `assetcategory_id` — et
 * une carte fermee ne pouvait pas les couvrir.
 *
 * La regle est donc ici, avec une carte FOURNIE PAR L'APPELANT ; S48 devient un
 * appelant comme un autre, avec sa carte a lui. Aucun de ses huit points
 * d'appel ne change de comportement.
 *
 * ⚠️ Le sabotage doit viser chaque POINT D'APPEL et chaque CHAMP : une aide
 * partagee donne l'illusion d'une couverture que ses appelants n'ont pas
 * (lecon de S46, confirmee par S47 puis S48).
 */
trait GuardsForeignIdentifiers
{
    /**
     * Vrai si un identifiant NOMME dans la requete ne designe pas une ressource
     * de la maison.
     *
     * Chaque champ reste FACULTATIF : un champ absent laisse l'ecriture sans
     * cette reference, comme avant. Seul un champ fourni ET etranger fait
     * refuser — avant la moindre ecriture, pour qu'un refus ne laisse derriere
     * lui ni ligne orpheline ni releve a moitie ecrit.
     *
     * @param array<string, class-string> $carte champ de requete => modele portant le perimetre
     */
    protected function identifiantsHorsPerimetre($request, array $carte): bool
    {
        foreach ($carte as $champ => $modele) {
            $valeur = $request->{$champ} ?? null;

            if (filled($valeur) && blank($modele::companywise()->find($valeur))) {
                return true;
            }
        }

        return false;
    }
}
