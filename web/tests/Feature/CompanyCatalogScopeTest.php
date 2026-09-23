<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\Support;
use App\Models\Backend\Department;
use App\Models\Backend\Designation;
use App\Models\User;
use App\Repositories\MerchantPanel\Support\SupportInterface as SupportMarchandInterface;
use App\Repositories\Superadmin\Company\CompanyInterface;
use App\Repositories\Support\SupportInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S47 — le catalogue emprunte : un service qui n'est pas le sien.
 *
 * S46 a ferme les catalogues d'un COLIS. Ceux d'un COMPTE — service et fonction
 * — sont restes ouverts, et sur la surface la plus exposee du produit :
 * l'inscription d'une nouvelle societe.
 *
 * `CompanyRepository::signUpStore()` ecrivait
 * `$user->department_id = Department::first()->id` et la meme chose pour la
 * fonction : la PREMIERE ligne de la table, toutes societes confondues. Le
 * compte proprietaire d'une societe neuve pointait donc vers le catalogue d'une
 * societe existante.
 *
 * ⚠️ **La route n'est pas derriere une authentification.**
 * `POST company/sign-up/store` vit dans un groupe `['XSS', 'IsInstalled']`,
 * entre les pages publiques (blog, contact, FAQ). C'est l'inscription
 * en libre-service : n'importe qui sur Internet la declenche.
 *
 * ⚠️ **Et la consequence n'est pas cosmetique.** `users.department_id` et
 * `users.designation_id` portent `onDelete('cascade')`. Une societe qui
 * supprime SON service depuis son propre ecran de reglages detruit donc les
 * comptes qui le referencent — y compris le proprietaire d'une autre societe.
 * C'est ce que le dernier test de ce fichier verifie de bout en bout.
 *
 * La regle est connue ailleurs dans le socle : `UserRepository` sert ses deux
 * selecteurs en `where('company_id', settings()->id)->active()`. Trois lectures
 * l'ignoraient.
 */
class CompanyCatalogScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const AUTRE = 2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        Mail::fake();
    }

    /**
     * Une societe neuve n'a pas encore de catalogue. Ne rien lui donner vaut
     * mieux que lui preter celui d'une autre : la colonne est nullable, et le
     * socle sait afficher un compte sans service.
     */
    public function test_a_public_signup_never_borrows_another_companys_catalog(): void
    {
        $serviceExistant = Department::firstOrFail();
        $fonctionExistante = Designation::firstOrFail();
        $this->assertNotNull($serviceExistant->company_id, 'la fixture doit partir d\'un service appartenant a une societe');

        $this->inscrireUneSociete('nouvelle', 'proprio@nouvelle.test');

        $proprietaire = User::where('email', 'proprio@nouvelle.test')->firstOrFail();

        $this->assertNotSame(
            $serviceExistant->company_id,
            $proprietaire->company_id,
            'la fixture doit creer une societe DIFFERENTE de celle du catalogue',
        );
        $this->assertNull($proprietaire->department_id, 'le compte pointe vers le service d\'une autre societe');
        $this->assertNull($proprietaire->designation_id, 'le compte pointe vers la fonction d\'une autre societe');
    }

    /**
     * ⚠️ La consequence, de bout en bout. Une societe supprime SON service ;
     * le proprietaire de la societe voisine doit survivre.
     *
     * Avant ce lot, la cascade de cle etrangere l'emportait : le compte etait
     * detruit sans qu'aucun ecran, aucun journal, aucune confirmation ne le
     * mentionne. Une societe pouvait effacer le compte d'une autre depuis ses
     * propres reglages, sans jamais savoir qu'elle l'avait fait.
     */
    public function test_deleting_our_own_department_never_destroys_another_companys_owner(): void
    {
        $this->inscrireUneSociete('voisine', 'proprio@voisine.test');
        $proprietaire = User::where('email', 'proprio@voisine.test')->firstOrFail();

        $service = Department::firstOrFail();
        $this->assertNotSame($service->company_id, $proprietaire->company_id);

        $service->delete();

        $this->assertNotNull(
            User::find($proprietaire->id),
            'la suppression d\'un service a detruit le compte proprietaire d\'une AUTRE societe',
        );
    }

    /* ────────── le selecteur de services, ouvert DES DEUX COTES ─────────── */

    /**
     * Le selecteur de service du formulaire de ticket listait `Department::
     * active()` — toutes societes confondues — dans le back-office ET dans le
     * panneau marchand. Les intitules internes d'une maison s'affichaient donc
     * dans la liste deroulante d'une autre.
     *
     * ⚠️ Ce n'etait donc pas « ferme cote administration, ouvert cote
     * marchand » : c'etait ouvert des deux cotes, alors que `UserRepository`
     * sert le MEME catalogue correctement scope, deux fichiers plus loin. La
     * regle etait connue ; deux lectures l'ignoraient.
     */
    public function test_the_ticket_department_selector_lists_only_our_own(): void
    {
        $sien = Department::forceCreate([
            'company_id' => self::AUTRE, 'title' => 'Service du voisin', 'status' => Status::ACTIVE,
        ]);
        $mien = Department::forceCreate([
            'company_id' => settings()->id, 'title' => 'Service maison', 'status' => Status::ACTIVE,
        ]);

        foreach ([
            'back-office' => app(SupportInterface::class),
            'panneau marchand' => app(SupportMarchandInterface::class),
        ] as $ou => $depot) {
            $offerts = $depot->departments()->pluck('id');

            $this->assertTrue($offerts->contains($mien->id), "{$ou} : notre propre service a disparu du selecteur");
            $this->assertFalse($offerts->contains($sien->id), "{$ou} : le service d'une autre societe est propose");
        }
    }

    /**
     * ⚠️ Fermer le selecteur ne ferme pas l'ecriture. La liste deroulante ne
     * propose plus que nos services ; rien n'oblige le navigateur a s'y tenir —
     * c'est la phrase de S33, et elle vaut ici mot pour mot.
     *
     * Meme lecon que S43, prise par l'autre bout : la-bas un ecran nu rendait
     * une garde de selecteur impossible ; ici un selecteur garde donnait
     * l'illusion que l'ecriture l'etait aussi.
     */
    public function test_a_ticket_cannot_carry_another_companys_department(): void
    {
        $sien = Department::forceCreate([
            'company_id' => self::AUTRE, 'title' => 'Service du voisin', 'status' => Status::ACTIVE,
        ]);
        $mien = Department::forceCreate([
            'company_id' => settings()->id, 'title' => 'Service maison', 'status' => Status::ACTIVE,
        ]);

        $this->actingAs($this->agentDe(settings()->id));

        foreach ([
            'back-office' => app(SupportInterface::class),
            'panneau marchand' => app(SupportMarchandInterface::class),
        ] as $ou => $depot) {
            $this->assertFalse(
                (bool) $depot->store(new Request([
                    'department_id' => $sien->id,
                    'service' => 'Colis', 'priority' => 1,
                    'subject' => 'Sujet', 'description' => 'Details', 'date' => date('Y-m-d'),
                ])),
                "{$ou} : un ticket a ete ouvert sur le service d'une autre societe",
            );

            // Controle negatif : avec NOTRE service, le ticket s'ouvre.
            $this->assertTrue(
                (bool) $depot->store(new Request([
                    'department_id' => $mien->id,
                    'service' => 'Colis', 'priority' => 1,
                    'subject' => 'Sujet', 'description' => 'Details', 'date' => date('Y-m-d'),
                ])),
                "{$ou} : notre propre service a ete refuse",
            );
        }

        $this->assertSame(0, Support::where('department_id', $sien->id)->count());
        $this->assertSame(2, Support::where('department_id', $mien->id)->count());

        // ⚠️ Et la MODIFICATION ferme la meme porte : le sabotage l'a reclame,
        // `update()` restait vert des deux cotes. Une garde posee dans deux
        // methodes n'est pas une garde prouvee dans les deux.
        foreach ([
            'back-office' => app(SupportInterface::class),
            'panneau marchand' => app(SupportMarchandInterface::class),
        ] as $ou => $depot) {
            $ticket = Support::where('department_id', $mien->id)->orderBy('id')->get()->last();

            $this->assertFalse(
                (bool) $depot->update($ticket->id, new Request([
                    'department_id' => $sien->id,
                    'service' => 'Colis', 'priority' => 1,
                    'subject' => 'Modifie', 'description' => 'Details', 'date' => date('Y-m-d'),
                ])),
                "{$ou} : un ticket a ete bascule vers le service d'une autre societe",
            );
            $this->assertSame($mien->id, (int) $ticket->fresh()->department_id);
        }
    }

    /* ───────────────────────────── fixtures ─────────────────────────────── */

    private function agentDe(int $societe): User
    {
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent catalogue';
        $agent->email = 'agent.s47.' . $societe . '@example.test';
        $agent->mobile = '00229975100' . $societe;
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }

    private function inscrireUneSociete(string $domaine, string $courriel): void
    {
        $resultat = app(CompanyInterface::class)->signUpStore(new Request([
            'company_name' => 'Societe ' . $domaine,
            'name' => 'Proprietaire ' . $domaine,
            'email' => $courriel,
            'mobile' => '0022997' . random_int(100000, 999999),
            'address' => 'Cotonou',
            'domain' => $domaine,
            'password' => 'motdepasse',
        ]));

        $this->assertNotFalse($resultat, 'l\'inscription de la societe a echoue');
    }
}
