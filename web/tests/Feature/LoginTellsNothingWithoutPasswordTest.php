<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S142** — la page de connexion ne dit rien d'un compte à qui n'a pas son mot de passe.
 *
 * Sur le domaine central, le socle redirigeait vers le site du transporteur du compte **avant** de vérifier
 * le mot de passe : taper l'adresse d'une PME, avec n'importe quel mot de passe, disait chez quel
 * transporteur elle est inscrite. Le refus de S104 (« connectez-vous dans l'app livreur ») disait de même
 * qu'une adresse est celle d'un livreur. Ces deux réponses ne viennent plus qu'après un mot de passe juste ;
 * sinon, l'échec ordinaire, compté par le limiteur.
 */
class LoginTellsNothingWithoutPasswordTest extends TestCase
{
    use MountsTenantRoutes;
    use RefreshDatabase;
    use SeedsTenant;

    private function marchandDeLaDemo(): User
    {
        $this->seedTenant();
        config(['app.app_installed' => 'yes']);
        $pme = User::where('user_type', UserType::MERCHANT)->firstOrFail();
        $pme->forceFill(['password' => Hash::make('secret-s142')])->save();

        return $pme;
    }

    public function test_the_central_page_names_no_carrier_for_a_wrong_password(): void
    {
        $pme = $this->marchandDeLaDemo();
        $site = scheme_name($pme->tenantDetails->domains[0]->domain);

        $refus = $this->post('/login', ['email' => $pme->email, 'password' => 'mauvais']);
        $this->assertNotSame($site, $refus->headers->get('Location'), 'un mauvais mot de passe ne mène pas au site du transporteur');
        $refus->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => $pme->email, 'password' => 'secret-s142'])->assertRedirect($site);
    }

    public function test_the_courier_refusal_is_said_only_to_who_has_the_password(): void
    {
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();
        $livreur = new User();
        $livreur->forceFill([
            'company_id' => settings()->id, 'name' => 'Livreur S142', 'email' => 'livreur.s142@example.test',
            'password' => Hash::make('secret-s142'), 'user_type' => UserType::DELIVERYMAN, 'permissions' => [],
            'status' => Status::ACTIVE, 'verification_status' => Status::ACTIVE,
        ])->save();

        $this->post(self::HOTE . '/login', ['email' => 'livreur.s142@example.test', 'password' => 'mauvais'])
            ->assertSessionHasErrors(['email' => __('auth.failed')]);
        $this->post(self::HOTE . '/login', ['email' => 'livreur.s142@example.test', 'password' => 'secret-s142'])
            ->assertSessionHasErrors(['email' => __('auth.courier_app_only')]);
        $this->assertGuest();
    }
}
