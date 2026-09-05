<?php

namespace App\Console\Commands;

use App\Enums\StatementType;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Parcel;
use App\Models\Backend\VatStatement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * `php artisan beninlink:ecarts-marchands` — quand le solde d'un marchand ne dit
 * plus la même chose que son relevé.
 *
 * ## L'invariant
 *
 * `merchants.current_balance` n'est qu'un **cache** : la vérité est le relevé,
 * `merchant_statements`, où chaque mouvement laisse sa ligne. Les deux doivent
 * donc toujours se répondre :
 *
 *     current_balance = opening_balance + Σ(recettes) − Σ(dépenses)
 *
 * ## Ce qui l'a cassé
 *
 * L'annulation d'une livraison partielle recalculait les montants du colis sur
 * la somme d'origine, puis créditait le solde de la **TVA recalculée** alors
 * que sa propre ligne de relevé portait la TVA réellement prélevée. Sur le jeu
 * de recette : 216 F crédités contre 201,60 F écrits — 14,40 F d'écart, à
 * chaque colis concerné, définitivement.
 *
 * Le correctif du 2026-09-05 arrête l'hémorragie ; il ne rattrape pas le passé.
 * D'où cette commande.
 *
 * ## Pourquoi elle ne corrige pas toute seule
 *
 * Un écart n'est pas forcément CET écart. Deux autres chemins le produisent, et
 * la commande les nomme plutôt que de faire comme s'ils n'existaient pas :
 *
 *   - les **passerelles de retrait en ligne** (`PayoutController` et ses
 *     variantes bKash, Skrill, Razorpay…) débitent `current_balance` sans
 *     écrire la moindre ligne de relevé ;
 *   - **modifier une fiche marchand** en renseignant le solde d'ouverture
 *     écrase `current_balance` (`MerchantRepository::update()`), effaçant tout
 *     ce qui s'était accumulé depuis.
 *
 * La commande ne corrige donc que les écarts qu'elle sait **entièrement**
 * expliquer par les annulations de livraisons partielles. Les autres, elle les
 * montre et s'arrête là : c'est à un humain de dire ce qui s'est passé.
 */
class MerchantBalanceDriftCommand extends Command
{
    protected $signature = 'beninlink:ecarts-marchands
        {--marchand= : Ne traiter qu\'un marchand (son identifiant)}
        {--corriger : Réaligner le solde sur le relevé, pour les écarts entièrement expliqués}
        {--force : Autoriser --corriger en production}';

    protected $description = 'Compare le solde de chaque marchand à son relevé, et explique les écarts';

    /** En dessous, c'est du bruit de virgule flottante, pas un écart. */
    private const EPSILON = 0.005;

    /**
     * Ici, et seulement ici, les montants s'affichent **au centime**.
     *
     * Le projet arrondit le FCFA à l'entier partout ailleurs, et c'est la
     * bonne règle : le franc CFA n'a pas de subdivision en circulation. Mais
     * cet outil sert à reconnaître un écart de 14,40 F — l'afficher « 14 F »
     * cacherait précisément ce qui l'identifie, et empêcherait de rapprocher
     * deux chiffres. Les colonnes sont des soldes techniques, pas des prix.
     */
    private function montant(float|string $valeur): string
    {
        return number_format((float) $valeur, 2, ',', "\xC2\xA0");
    }

    public function handle(): int
    {
        $marchands = Merchant::query()
            ->when($this->option('marchand'), fn ($q, $id) => $q->whereKey($id))
            ->get();

        $lignes = [];
        $corrigeables = [];

        foreach ($marchands as $marchand) {
            $releve = $this->releve($marchand);
            $ecart  = round((float) $marchand->current_balance - $releve, 2);

            if (abs($ecart) < self::EPSILON) {
                continue;
            }

            $explique = $this->partiellesAnnulees($marchand);
            $couvert  = abs($ecart - $explique['montant']) < self::EPSILON;

            $lignes[] = [
                $marchand->id,
                $marchand->business_name,
                $this->montant($marchand->current_balance),
                $this->montant($releve),
                $this->montant($ecart),
                $explique['colis'] === 0 ? '—' : $explique['colis'] . ' colis / ' . $this->montant($explique['montant']),
                $couvert ? 'oui' : 'NON',
            ];

            if ($couvert) {
                $corrigeables[$marchand->id] = $releve;
            }
        }

        if ($lignes === []) {
            $this->info('Aucun écart : chaque solde répond à son relevé.');

            return self::SUCCESS;
        }

        $this->warn(sprintf('%d marchand(s) dont le solde ne répond pas au relevé.', count($lignes)));
        $this->newLine();
        $this->table(
            ['Marchand', 'Nom', 'Solde', 'Relevé', 'Écart', 'Partielles annulées', 'Expliqué'],
            $lignes,
        );

        $this->newLine();
        $this->line('« Expliqué » signifie que l\'écart correspond, au centime près, à la TVA');
        $this->line('sur-créditée par les annulations de livraisons partielles.');
        $this->line('Un « NON » vient d\'ailleurs : un retrait passé par une passerelle en ligne');
        $this->line('(qui débite sans écrire au relevé), ou une fiche marchand ré-enregistrée');
        $this->line('avec un solde d\'ouverture, qui écrase le solde courant.');

        if (!$this->option('corriger')) {
            $this->newLine();
            $this->comment('Ajouter --corriger pour réaligner les soldes entièrement expliqués sur leur relevé.');

            return self::SUCCESS;
        }

        if ($corrigeables === []) {
            $this->newLine();
            $this->error('Aucun écart n\'est entièrement expliqué : rien ne sera corrigé.');

            return self::FAILURE;
        }

        if (app()->environment('production') && !$this->option('force')) {
            $this->newLine();
            $this->error('Refusé en production sans --force : la correction touche des soldes réels.');

            return self::FAILURE;
        }

        // Aucune écriture de relevé n'accompagne la correction, et c'est
        // voulu : le relevé a toujours été juste, c'est le cache qui avait
        // dérivé. Lui ajouter une ligne le rendrait faux à son tour.
        foreach ($corrigeables as $id => $releve) {
            DB::transaction(function () use ($id, $releve) {
                $marchand = Merchant::whereKey($id)->lockForUpdate()->first();
                $marchand->current_balance = $releve;
                $marchand->save();
            });
        }

        $this->newLine();
        $this->info(sprintf('%d solde(s) réaligné(s) sur leur relevé.', count($corrigeables)));

        return self::SUCCESS;
    }

    /**
     * Ce que le relevé du marchand dit de son solde.
     *
     * On accepte les deux rattachements : `merchant_id`, et — pour les lignes
     * écrites avant que les treize écritures du cycle de vie ne le
     * renseignent — le colis. Sans cela le rapprochement lirait un relevé vide
     * là où il y a eu des dizaines de livraisons, et crierait à l'écart sur
     * tout le monde. La migration de rattachement comble le passé ; cette
     * double lecture reste le filet.
     */
    private function releve(Merchant $marchand): float
    {
        $lignes = MerchantStatement::where(function ($q) use ($marchand) {
            $q->where('merchant_id', $marchand->id)
              ->orWhereIn('parcel_id', Parcel::where('merchant_id', $marchand->id)->select('id'));
        });

        return round(
            (float) $marchand->opening_balance
            + (float) (clone $lignes)->where('type', StatementType::INCOME)->sum('amount')
            - (float) (clone $lignes)->where('type', StatementType::EXPENSE)->sum('amount'),
            2,
        );
    }

    /**
     * Les colis livrés partiellement puis annulés, et la TVA sur-créditée au
     * passage.
     *
     * Le colis en garde la trace : `old_cash_collection` renseigné (la
     * livraison partielle l'écrit et personne ne l'efface) alors qu'il n'est
     * plus marqué partiel. Le montant sur-crédité est l'écart entre la TVA que
     * porte le colis — recalculée par l'annulation sur la somme d'origine — et
     * celle que le relevé de TVA a réellement enregistrée.
     *
     * On ne retient que les colis dont le relevé de TVA se solde à zéro : la
     * livraison partielle a bien été inversée, et rien n'est venu s'ajouter
     * depuis.
     */
    private function partiellesAnnulees(Merchant $marchand): array
    {
        $montant = 0.0;
        $colis = 0;

        $candidats = Parcel::where('merchant_id', $marchand->id)
            ->whereNotNull('old_cash_collection')
            ->where('partial_delivered', '!=', \App\Enums\BooleanStatus::YES)
            ->get(['id', 'vat_amount']);

        foreach ($candidats as $candidat) {
            $tva = VatStatement::where('parcel_id', $candidat->id);
            $percue = (float) (clone $tva)->where('type', StatementType::INCOME)->sum('amount');
            $rendue = (float) (clone $tva)->where('type', StatementType::EXPENSE)->sum('amount');

            if ($percue <= 0 || abs($percue - $rendue) >= self::EPSILON) {
                continue;
            }

            $ecart = round((float) $candidat->vat_amount - $percue, 2);
            if (abs($ecart) < self::EPSILON) {
                continue;
            }

            $montant += $ecart;
            $colis++;
        }

        return ['colis' => $colis, 'montant' => round($montant, 2)];
    }
}
