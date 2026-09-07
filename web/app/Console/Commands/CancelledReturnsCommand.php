<?php

namespace App\Console\Commands;

use App\Enums\ParcelStatus;
use App\Enums\StatementType;
use App\Models\Backend\CourierStatement;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\DeliverymanStatement;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Parcel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * `php artisan beninlink:retours-annules` — l'argent qu'une annulation de
 * retour n'a jamais rendu.
 *
 * ## Ce qui s'est passé
 *
 * Jusqu'au 2026-09-07, `returnReceivedByMerchantCancel` supprimait l'événement
 * et reculait le statut. Rien d'autre. Le marchand restait débité de son frais
 * de retour, le livreur gardait sa course, le transporteur son produit. Et
 * comme la séquence *réception → annulation → réception* tient en deux clics
 * dans le back-office, le même retour pouvait être prélevé deux fois.
 *
 * Le correctif arrête l'hémorragie ; il ne rattrape pas le passé. D'où cette
 * commande, sur le modèle de `beninlink:ecarts-marchands`.
 *
 * ## Comment un cas se reconnaît, sans deviner
 *
 * Les écritures du retour portent une `note` qui est **la clé de traduction
 * elle-même** : `statementNote.return_received_by_merchant_statment` n'existe
 * dans aucun fichier de langue, donc `__()` rend la clé. La colonne contient le
 * même texte en français comme en anglais — c'est un marqueur stable, et c'est
 * ce qui permet d'isoler les lignes de retour parmi les autres mouvements d'un
 * colis (une livraison partielle peut précéder un retour).
 *
 * Pour chaque colis, on compare donc ce qui a été **prélevé** à ce qui est
 * **dû** :
 *
 *     prélevé = Σ(dépenses) − Σ(recettes)   sur les lignes de retour du colis
 *     dû      = frais du colis si le retour tient encore, 0 sinon
 *
 * La différence est ce qu'il faut rendre. La formule couvre les deux dégâts
 * d'un coup : l'annulation jamais inversée (dû = 0, tout est à rendre) comme le
 * double prélèvement (dû = un frais, le second est à rendre).
 *
 * ## Ce qu'elle ne corrige pas
 *
 * Un colis dont le **relevé a déjà été émis** est montré, jamais touché. Le
 * relevé a facturé le retour ; lui rendre l'argent au solde sans rien dire du
 * document laisserait les deux en désaccord — et c'est précisément l'invariant
 * de **D9**. Un avoir se décide avec l'expert-comptable, pas dans une commande.
 */
class CancelledReturnsCommand extends Command
{
    protected $signature = 'beninlink:retours-annules
        {--societe= : Ne traiter qu\'une société}
        {--marchand= : Ne traiter qu\'un marchand (son identifiant)}
        {--corriger : Écrire les contreparties manquantes et remettre les soldes}
        {--force : Autoriser --corriger en production}';

    protected $description = 'Retrouve les frais de retour prélevés puis jamais rendus, et les rend';

    /** La note portée par les écritures de retour du marchand. */
    public const NOTE_MARCHAND = 'statementNote.return_received_by_merchant_statment';

    /** Celle du livreur, pour sa course de retour. */
    public const NOTE_LIVREUR = 'statementNote.return_to_merchant_deliveryman_statement';

    /** Celle du transporteur, en regard du frais marchand. */
    public const NOTE_COURSIER = 'statementNote.return_received_by_statement';

    /** En dessous, c'est du bruit de virgule flottante, pas un écart. */
    private const EPSILON = 0.005;

    private function montant(float $valeur): string
    {
        return number_format($valeur, 0, ',', "\xC2\xA0");
    }

    public function handle(): int
    {
        $colis = Parcel::query()
            ->when($this->option('societe'), fn ($q, $id) => $q->where('company_id', $id))
            ->when($this->option('marchand'), fn ($q, $id) => $q->where('merchant_id', $id))
            ->whereIn('id', MerchantStatement::where('note', self::NOTE_MARCHAND)->select('parcel_id'))
            ->orderBy('id')
            ->get();

        $lignes = [];
        $corrigeables = [];
        $figes = [];

        foreach ($colis as $parcel) {
            $ecart = $this->ecartMarchand($parcel);
            $livreurs = $this->ecartsLivreurs($parcel);
            $residu = $this->residu($parcel);

            if (abs($ecart) < self::EPSILON && $livreurs === [] && !$residu) {
                continue;
            }

            $releveEmis = $parcel->invoice_id !== null;

            $lignes[] = [
                $parcel->tracking_id,
                $parcel->merchant_id,
                trans('parcelStatus.' . $parcel->status),
                $this->montant($ecart),
                $livreurs === [] ? '—' : $this->montant(array_sum($livreurs)),
                $residu ? $this->montant((float) $parcel->return_charges) : '—',
                $releveEmis ? 'OUI — figé' : 'non',
            ];

            if ($releveEmis) {
                $figes[] = $parcel;
                continue;
            }

            $corrigeables[] = [$parcel, $ecart, $livreurs, $residu];
        }

        if ($lignes === []) {
            $this->info('Aucun retour annulé sans réversion : chaque frais prélevé est dû ou a été rendu.');

            return self::SUCCESS;
        }

        $this->warn(sprintf('%d colis dont le frais de retour n\'a pas été rendu.', count($lignes)));
        $this->newLine();
        $this->table(
            ['Colis', 'Marchand', 'Statut actuel', 'À rendre', 'Repris au livreur', 'Frais résiduel', 'Relevé émis'],
            $lignes,
        );

        $this->newLine();
        $this->line('« À rendre » est ce qui a été prélevé au marchand au-delà de ce qui lui est dû.');
        $this->line('« Frais résiduel » est le montant laissé sur le colis par une annulation :');
        $this->line('le relevé rassemble les retours par statut, et le refacturerait tel quel.');

        if ($figes !== []) {
            $this->newLine();
            $this->error(sprintf(
                '%d colis sont figés : leur relevé est déjà émis, et il a facturé le retour.',
                count($figes),
            ));
            $this->line('Ils ne seront pas touchés — un avoir se décide avec l\'expert-comptable,');
            $this->line('pas dans une commande. Le relevé a raison (D9).');
        }

        if (!$this->option('corriger')) {
            $this->newLine();
            $this->comment('Ajouter --corriger pour écrire les contreparties et remettre les soldes.');

            return self::SUCCESS;
        }

        if ($corrigeables === []) {
            $this->newLine();
            $this->error('Rien n\'est corrigeable ici : tout ce qui reste est figé par un relevé.');

            return self::FAILURE;
        }

        if (app()->environment('production') && !$this->option('force')) {
            $this->newLine();
            $this->error('Refusé en production sans --force : la correction touche des soldes réels.');

            return self::FAILURE;
        }

        $rendus = 0;
        foreach ($corrigeables as [$parcel, $ecart, $livreurs, $residu]) {
            DB::transaction(function () use ($parcel, $ecart, $livreurs, $residu) {
                if (abs($ecart) >= self::EPSILON) {
                    $this->rendreAuMarchand($parcel, $ecart);
                }
                foreach ($livreurs as $livreurId => $montant) {
                    $this->reprendreAuLivreur($parcel, (int) $livreurId, $montant);
                }
                if ($residu) {
                    // Sans quoi le prochain relevé refacturerait le retour annulé.
                    $frais = Parcel::whereKey($parcel->id)->lockForUpdate()->first();
                    $frais->return_charges = 0;
                    $frais->save();
                }
            });
            $rendus++;
        }

        $this->newLine();
        $this->info(sprintf('%d colis régularisé(s).', $rendus));

        return self::SUCCESS;
    }

    /**
     * Ce qui a été prélevé au marchand au-delà de ce qui lui est dû.
     *
     * Le montant dû vient de `parcels.return_charges`, écrit au moment du
     * retour : jamais d'un nouveau calcul. `merchants.return_charges` est un
     * pourcentage du tarif de livraison, qui a pu changer depuis — recalculer
     * laisserait un résidu, exactement le défaut des 14,40 F de D9.
     */
    private function ecartMarchand(Parcel $parcel): float
    {
        $lignes = MerchantStatement::where('parcel_id', $parcel->id)->where('note', self::NOTE_MARCHAND);

        $preleve = (float) (clone $lignes)->where('type', StatementType::EXPENSE)->sum('amount')
                 - (float) (clone $lignes)->where('type', StatementType::INCOME)->sum('amount');

        $du = (int) $parcel->status === ParcelStatus::RETURN_RECEIVED_BY_MERCHANT
            ? (float) $parcel->return_charges
            : 0.0;

        return round($preleve - $du, 2);
    }

    /**
     * Ce que chaque livreur a gardé de trop, par identifiant.
     *
     * Si le retour tient encore, une course lui reste due — celle de la
     * réception en cours, c'est-à-dire le dernier crédit qu'il a reçu sur ce
     * colis. Sinon il ne lui en est due aucune.
     *
     * @return array<int,float>
     */
    private function ecartsLivreurs(Parcel $parcel): array
    {
        $lignes = DeliverymanStatement::where('parcel_id', $parcel->id)->where('note', self::NOTE_LIVREUR)->get();
        $tient = (int) $parcel->status === ParcelStatus::RETURN_RECEIVED_BY_MERCHANT;

        $ecarts = [];
        foreach ($lignes->groupBy('delivery_man_id') as $livreurId => $siennes) {
            $percu = (float) $siennes->where('type', StatementType::INCOME)->sum('amount')
                   - (float) $siennes->where('type', StatementType::EXPENSE)->sum('amount');

            $du = $tient
                ? (float) ($siennes->where('type', StatementType::INCOME)->sortByDesc('id')->first()?->amount ?? 0)
                : 0.0;

            $ecart = round($percu - $du, 2);
            if (abs($ecart) >= self::EPSILON) {
                $ecarts[(int) $livreurId] = $ecart;
            }
        }

        return $ecarts;
    }

    /** Un frais laissé sur un colis dont le retour ne tient plus. */
    private function residu(Parcel $parcel): bool
    {
        return (float) $parcel->return_charges > 0
            && (int) $parcel->status !== ParcelStatus::RETURN_RECEIVED_BY_MERCHANT;
    }

    /**
     * On inverse, on n'efface pas : la contrepartie laisse sa ligne, comme le
     * fait désormais l'annulation elle-même.
     */
    private function rendreAuMarchand(Parcel $parcel, float $montant): void
    {
        $statement = new MerchantStatement();
        $statement->company_id = $parcel->company_id;
        $statement->merchant_id = $parcel->merchant_id;
        $statement->parcel_id = $parcel->id;
        $statement->amount = $montant;
        $statement->type = StatementType::INCOME;
        $statement->date = date('Y-m-d H:i:s');
        $statement->note = self::NOTE_MARCHAND;
        $statement->save();

        $marchand = Merchant::whereKey($parcel->merchant_id)->lockForUpdate()->first();
        $marchand->current_balance = (float) $marchand->current_balance + $montant;
        $marchand->save();

        $courier = new CourierStatement();
        $courier->company_id = $parcel->company_id;
        $courier->parcel_id = $parcel->id;
        $courier->amount = $montant;
        $courier->type = StatementType::EXPENSE;
        $courier->date = date('Y-m-d H:i:s');
        $courier->note = self::NOTE_COURSIER;
        $courier->save();
    }

    private function reprendreAuLivreur(Parcel $parcel, int $livreurId, float $montant): void
    {
        $statement = new DeliverymanStatement();
        $statement->company_id = $parcel->company_id;
        $statement->parcel_id = $parcel->id;
        $statement->delivery_man_id = $livreurId;
        $statement->amount = $montant;
        $statement->type = StatementType::EXPENSE;
        $statement->date = date('Y-m-d H:i:s');
        $statement->note = self::NOTE_LIVREUR;
        $statement->save();

        $livreur = DeliveryMan::whereKey($livreurId)->lockForUpdate()->first();
        $livreur->current_balance = (float) $livreur->current_balance - $montant;
        $livreur->save();

        $courier = new CourierStatement();
        $courier->company_id = $parcel->company_id;
        $courier->parcel_id = $parcel->id;
        $courier->delivery_man_id = $livreurId;
        $courier->amount = $montant;
        $courier->type = StatementType::INCOME;
        $courier->date = date('Y-m-d H:i:s');
        $courier->note = self::NOTE_LIVREUR;
        $courier->save();
    }
}
