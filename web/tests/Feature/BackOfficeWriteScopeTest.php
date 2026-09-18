<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Http\Controllers\Backend\MerchantShopsController;
use App\Models\Backend\FrontWeb\Blog;
use App\Models\Backend\FrontWeb\Faq;
use App\Models\Backend\FrontWeb\Partner;
use App\Models\Backend\FrontWeb\Service;
use App\Models\Backend\FrontWeb\SocialLink;
use App\Models\Backend\FrontWeb\WhyCourier;
use App\Models\Backend\Merchant;
use App\Models\Backend\Support;
use App\Models\MerchantShops;
use App\Models\User;
use App\Repositories\FrontWeb\Blogs\BlogsInterface;
use App\Repositories\FrontWeb\Faq\FaqInterface;
use App\Repositories\FrontWeb\Partner\PartnerInterface;
use App\Repositories\FrontWeb\Service\ServiceInterface;
use App\Repositories\FrontWeb\SocialLink\SocialLinkInterface;
use App\Repositories\FrontWeb\WhyCourier\WhyCourierInterface;
use App\Repositories\MerchantShops\ShopsInterface;
use App\Repositories\Support\SupportInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S29 — dans le back-office, **les écritures étaient moins scopées que les
 * lectures**. C'est le motif dominant de l'arriéré de `WebIsolationCoverageTest`,
 * et il s'est glissé jusque dans mes propres correctifs.
 *
 * Trois formes, relevées en dépouillant les 171 routes héritées :
 *
 * 1. **La vitrine.** Les six dépôts de `FrontWeb` lisent avec
 *    `companyWise()->findOrFail()` — un `edit` hors périmètre répondait déjà 404.
 *    Mais leur `delete()` était `Modele::destroy($id)`, **nu** : la question,
 *    l'article, le service, le partenaire, le lien social ou le bloc « pourquoi
 *    nous » d'un AUTRE transporteur se supprimait en changeant l'identifiant
 *    dans l'URL. Le site public de la victime perdait son contenu.
 *
 * 2. **⚠️ Le trou de S23.** Ce lot avait scopé les *lectures* du support
 *    (`all`, `get`, `chats`) et laissé `update()` et `delete()` nus. On pouvait
 *    donc réécrire le ticket d'un autre transporteur — et s'en attribuer la
 *    paternité par le `user_id` — ou le supprimer. J'avais annoncé « les trois
 *    lectures » ; c'était exact, et incomplet.
 *
 * 3. **⚠️ Le trou de S26.** Même chose pour les boutiques : les quatre lectures
 *    passaient par `boutiquesDeLaSociete()`, `update()` et `delete()` non. Et
 *    `update()` lit `merchant_id` dans la requête, donc permettait en plus de
 *    **rattacher** la boutique à un autre marchand.
 *
 * La leçon, à retenir pour la suite de l'arriéré : corriger la fuite que le
 * rapport montre ne suffit pas — il faut relire les méthodes SŒURS du même dépôt.
 */
class BackOfficeWriteScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    /** La société du voisin. Repère : le seed appartient à la société 2. */
    private const AUTRE = 2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
    }

    /* ─────────────────── 1. la vitrine : six suppressions ───────────────── */

    /** Modèle, interface du dépôt, et champs minimaux pour écrire une ligne. */
    private function vitrine(): array
    {
        $auteur = User::where('company_id', self::AUTRE)->firstOrFail()->id;

        return [
            'faq' => [Faq::class, FaqInterface::class, ['question' => 'Combien coûte une livraison ?']],
            'blog' => [Blog::class, BlogsInterface::class, ['title' => 'Nous ouvrons à Parakou', 'created_by' => $auteur]],
            'service' => [Service::class, ServiceInterface::class, ['title' => 'Livraison express']],
            'partner' => [Partner::class, PartnerInterface::class, ['name' => 'Chambre de commerce']],
            'social_link' => [SocialLink::class, SocialLinkInterface::class, ['name' => 'WhatsApp']],
            'why_courier' => [WhyCourier::class, WhyCourierInterface::class, ['title' => 'Suivi en temps réel']],
        ];
    }

    public function test_a_front_web_row_of_another_company_cannot_be_deleted(): void
    {
        $fuites = [];

        foreach ($this->vitrine() as $nom => [$modele, $interface, $champs]) {
            $sienne = $this->ligne($modele, $champs, self::AUTRE);

            app($interface)->delete($sienne->id);

            if (blank($modele::find($sienne->id))) {
                $fuites[] = $nom;
            }
        }

        $this->assertSame([], $fuites, "Le contenu du site public d'une autre société a été supprimé :\n - "
            . implode("\n - ", $fuites));
    }

    /** Et la suppression légitime fonctionne toujours. */
    public function test_a_front_web_row_of_my_own_company_is_still_deleted(): void
    {
        $restantes = [];

        foreach ($this->vitrine() as $nom => [$modele, $interface, $champs]) {
            $mienne = $this->ligne($modele, $champs, settings()->id);

            app($interface)->delete($mienne->id);

            if (filled($modele::find($mienne->id))) {
                $restantes[] = $nom;
            }
        }

        $this->assertSame([], $restantes, "Le correctif a cassé la suppression légitime :\n - "
            . implode("\n - ", $restantes));
    }

    /**
     * La lecture, elle, était déjà scopée — et elle répond 404, pas 500.
     *
     * `Page` s'ajoute ici : elle n'a pas d'écran de suppression, mais son
     * `update()` **délègue** à `getFind()`. Prouver la lecture prouve donc les
     * deux routes de la page.
     */
    public function test_the_front_web_read_of_another_company_was_already_a_not_found(): void
    {
        $familles = $this->vitrine() + [
            'page' => [\App\Models\Backend\FrontWeb\Page::class, \App\Repositories\FrontWeb\Pages\PagesInterface::class,
                ['page' => 'about-us', 'title' => 'À propos du voisin']],
        ];

        foreach ($familles as $nom => [$modele, $interface, $champs]) {
            $sienne = $this->ligne($modele, $champs, self::AUTRE);

            try {
                app($interface)->getFind($sienne->id);
                $this->fail("{$nom} : la lecture hors périmètre a répondu");
            } catch (ModelNotFoundException $e) {
                $this->assertTrue(true);
            }
        }
    }

    /* ─────────────────── 2. le trou de S23 : le support ─────────────────── */

    public function test_the_ticket_of_another_company_cannot_be_rewritten(): void
    {
        $sien = $this->ticketDe(User::where('company_id', self::AUTRE)->firstOrFail());
        $sujetDorigine = $sien->subject;

        $requete = new Request([
            'department_id' => $sien->department_id,
            'service' => 'Détournement',
            'priority' => 'high',
            'subject' => 'Réécrit par le voisin',
            'description' => 'Réécrit',
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($this->agentDe(settings()->id));

        $this->assertFalse(app(SupportInterface::class)->update($sien->id, $requete));
        $this->assertSame($sujetDorigine, $sien->fresh()->subject);
    }

    public function test_the_ticket_of_another_company_cannot_be_deleted(): void
    {
        $sien = $this->ticketDe(User::where('company_id', self::AUTRE)->firstOrFail());

        $this->actingAs($this->agentDe(settings()->id));

        $this->assertFalse((bool) app(SupportInterface::class)->delete($sien->id));
        $this->assertNotNull(Support::find($sien->id));
    }

    /**
     * Le changement de statut, lui, passait déjà par `get()` — donc par le
     * périmètre. On l'inscrit pour que la route soit couverte, elle aussi.
     */
    public function test_the_status_of_another_companys_ticket_cannot_be_changed(): void
    {
        $sien = $this->ticketDe(User::where('company_id', self::AUTRE)->firstOrFail());
        // `forceCreate` ne pose pas `status` : la valeur vient du defaut SQL,
        // donc l'instance en memoire l'a encore a null. On lit la ligne ecrite.
        $statutDorigine = $sien->fresh()->status;

        $this->actingAs($this->agentDe(settings()->id));

        $this->assertFalse(app(SupportInterface::class)->statusUpdate($sien->id, new Request(['status' => 3])));
        $this->assertSame($statutDorigine, $sien->fresh()->status);
    }

    public function test_my_own_ticket_is_still_writable_and_deletable(): void
    {
        $agent = $this->agentDe(settings()->id);
        $this->actingAs($agent);

        $mien = $this->ticketDe($agent);

        $requete = new Request([
            'department_id' => $mien->department_id,
            'service' => 'Réclamation',
            'priority' => 'high',
            'subject' => 'Sujet corrigé',
            'description' => 'Corrigé',
            'date' => now()->toDateString(),
        ]);

        $this->assertTrue(app(SupportInterface::class)->update($mien->id, $requete));
        $this->assertSame('Sujet corrigé', $mien->fresh()->subject);

        $this->assertTrue((bool) app(SupportInterface::class)->delete($mien->id));
        $this->assertNull(Support::find($mien->id));
    }

    /* ────────────────── 3. le trou de S26 : les boutiques ───────────────── */

    public function test_the_shop_of_another_companys_merchant_cannot_be_rewritten(): void
    {
        $sienne = $this->boutiqueDe(Merchant::where('company_id', self::AUTRE)->firstOrFail());
        $nomDorigine = $sienne->name;

        $requete = new Request([
            'id' => $sienne->id,
            'merchant_id' => $sienne->merchant_id,
            'name' => 'Détournée',
            'contact_no' => '0022997000999',
            'address' => 'Ailleurs',
            'status' => 1,
        ]);

        $this->assertFalse(app(ShopsInterface::class)->update($requete));
        $this->assertSame($nomDorigine, $sienne->fresh()->name);
    }

    public function test_the_shop_of_another_companys_merchant_cannot_be_deleted(): void
    {
        $sienne = $this->boutiqueDe(Merchant::where('company_id', self::AUTRE)->firstOrFail());

        $this->assertFalse((bool) app(ShopsInterface::class)->delete($sienne->id));
        $this->assertNotNull(MerchantShops::find($sienne->id));
    }

    /**
     * Et l'écran d'édition répond 404 au lieu de l'erreur fatale : le garde
     * était posé APRÈS la déréférence de `null`.
     */
    public function test_the_shop_edit_screen_answers_not_found_out_of_scope(): void
    {
        $sienne = $this->boutiqueDe(Merchant::where('company_id', self::AUTRE)->firstOrFail());

        $this->expectException(NotFoundHttpException::class);

        app(MerchantShopsController::class)->edit($sienne->id);
    }

    /** Et la suppression hors périmètre ne s'annonce plus comme un succès. */
    public function test_the_shop_delete_screen_answers_not_found_out_of_scope(): void
    {
        $sienne = $this->boutiqueDe(Merchant::where('company_id', self::AUTRE)->firstOrFail());

        $this->expectException(NotFoundHttpException::class);

        app(MerchantShopsController::class)->delete($sienne->id);
    }

    /* ─────────────────────────── fixtures ───────────────────────────────── */

    /**
     * ⚠️ Ces modèles du socle ne déclarent ni `$fillable` ni `$guarded` : un
     * `Modele::create([...])` lève `MassAssignmentException`. On écrit donc les
     * attributs un par un.
     */
    private function ligne(string $modele, array $champs, int $societe)
    {
        $ligne = new $modele();

        foreach ($champs + ['company_id' => $societe] as $colonne => $valeur) {
            $ligne->{$colonne} = $valeur;
        }

        $ligne->save();

        return $ligne;
    }

    private function agentDe(int $societe): User
    {
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent ' . $societe;
        $agent->email = 'agent.s29.' . $societe . '@example.test';
        $agent->mobile = '00229970009' . $societe;
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }

    /**
     * Repère : `supports` ne porte pas de `company_id` — le périmètre passe par
     * l'AUTEUR (`ticketsVisibles()` fait `whereHas('user', companywise())`), et
     * `priority` est une chaîne, pas un entier. Même forme que
     * `BackOfficeScopingTest`, qui a servi de modèle.
     */
    private function ticketDe(User $auteur): Support
    {
        return Support::forceCreate([
            'user_id' => $auteur->id,
            'department_id' => \App\Models\Backend\Department::first()?->id,
            'service' => 'Livraison',
            'priority' => 'high',
            'subject' => 'Sujet de la société ' . $auteur->company_id,
            'description' => 'Contenu confidentiel',
            'date' => now()->toDateString(),
        ]);
    }

    private function boutiqueDe(Merchant $marchand): MerchantShops
    {
        $boutique = new MerchantShops();
        $boutique->merchant_id = $marchand->id;
        $boutique->name = 'Boutique de la société ' . $marchand->company_id;
        $boutique->contact_no = '0022997001111';
        $boutique->address = 'Cotonou';
        $boutique->status = 1;
        $boutique->save();

        return $boutique;
    }
}
