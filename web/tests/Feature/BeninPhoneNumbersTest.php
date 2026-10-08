<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Enums\UserType;
use App\Http\Middleware\NormalizePhoneNumbers;
use App\Http\Services\SmsService;
use App\Jobs\SendSms;
use App\Models\User;
use App\Support\BeninPhone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S133** — un numéro béninois se saisit comme on le dit.
 *
 * Le socle exigeait 11 à 14 chiffres, sans espace ni « + » (format du Bangladesh) : une PME qui
 * tapait son numéro au format national, « 01 97 01 00 07 », était refusée à l'inscription, et
 * « +229 01 97 01 00 07 », lu comme une adresse électronique, ne la connectait pas. Le numéro
 * entre désormais au format rangé `2290197010007` (`BeninPhone`), sur le web comme dans l'API,
 * avant la validation ; la connexion le cherche sous cette forme, puis sous ce qu'on a tapé pour un
 * compte rangé avant S133 ; un SMS part avec l'indicatif même vers un numéro rangé sans.
 */
class BeninPhoneNumbersTest extends TestCase
{
    use MountsTenantRoutes;
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'blk_cle_de_test';

    public function test_the_ways_a_beninese_number_is_written_become_one_form(): void
    {
        foreach ([
            '01 97 01 00 07'      => '2290197010007',
            '0197010007'          => '2290197010007',
            '+229 01 97 01 00 07' => '2290197010007',
            '00229 0197010007'    => '2290197010007',
            '2290197010007'       => '2290197010007',
            '97 01 00 07'         => '2290197010007', // ancien national, migration ARCEP 2024
            '22997010007'         => '2290197010007', // ancien international
            '01.97.01.00.07'      => '2290197010007',
            '+33 6 12 34 56 78'   => '33612345678',   // un autre pays garde ses chiffres
        ] as $saisie => $range) {
            $this->assertSame($range, BeninPhone::normalize((string) $saisie), "« {$saisie} »"); // une clé tout en chiffres devient un entier
        }

        foreach (['client@example.test', 'abc', '', null, 97010007] as $autre) {
            $this->assertSame($autre, BeninPhone::normalize($autre), 'ce qui n\'est pas un numéro ressort tel quel');
        }
    }

    public function test_only_writes_are_normalized_a_search_keeps_what_was_typed(): void
    {
        $vu = null;
        $suivant = function (Request $r) use (&$vu) { $vu = $r->all(); return response('ok'); };
        $middleware = new NormalizePhoneNumbers();

        $middleware->handle(Request::create('/x', 'POST', [
            'mobile' => '01 97 01 00 07', 'customer_phone' => '+229 0197010008', 'nom' => '01 97 01 00 07',
            'shops' => [['contact_no' => '0197010009']],
        ]), $suivant);
        $this->assertSame('2290197010007', $vu['mobile']);
        $this->assertSame('2290197010008', $vu['customer_phone']);
        $this->assertSame('2290197010009', $vu['shops'][0]['contact_no'], 'un champ imbriqué suit la même règle');
        $this->assertSame('01 97 01 00 07', $vu['nom'], 'un autre champ ne bouge pas');

        $middleware->handle(Request::create('/x', 'GET', ['phone' => '97010007']), $suivant);
        $this->assertSame('97010007', $vu['phone'], 'un filtre de recherche cherche ce qu\'on a tapé, dans des numéros rangés avant S133');

        $this->assertTrue(app(\App\Http\Kernel::class)->hasMiddleware(NormalizePhoneNumbers::class),
            'le middleware est global : web et API');
    }

    public function test_a_pme_registers_and_verifies_its_code_with_the_national_format(): void
    {
        Queue::fake();
        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);

        $this->postJson('/api/v10/register', [
            'business_name' => 'Boutique Houénou', 'full_name' => 'Rachidatou Houénou', 'address' => 'Akpakpa, Cotonou',
            'mobile' => '+229 01 97 01 00 07', 'password' => 'pilote2026', 'policy' => 1, 'hub_id' => 1,
            'ifu' => '3202600010007', 'rccm' => 'RB/COT/26 B 10007',
        ], ['apiKey' => self::API_KEY])->assertOk();

        $compte = User::where('mobile', '2290197010007')->firstOrFail();
        Queue::assertPushed(SendSms::class, fn (SendSms $job) => $job->phone === '2290197010007');

        $this->postJson('/api/v10/otp-verification', ['mobile' => '01 97 01 00 07', 'otp' => $compte->otp], ['apiKey' => self::API_KEY])
            ->assertOk()->assertJsonStructure(['data' => ['token']]);
    }

    public function test_the_web_login_finds_the_account_by_the_number_as_spoken(): void
    {
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();
        $this->agent('agent.s133@example.test', '2290197920002');

        $this->post(self::HOTE . '/login', ['email' => '+229 01 97 92 00 02', 'password' => 'secret'])
            ->assertRedirect(self::HOTE . '/dashboard');
        $this->assertAuthenticated();
    }

    public function test_an_account_stored_before_s133_still_logs_in_with_what_it_types(): void
    {
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();
        $this->agent('agent.ancien@example.test', '0022997920003');

        $this->post(self::HOTE . '/login', ['email' => '0022997920003', 'password' => 'secret'])
            ->assertRedirect(self::HOTE . '/dashboard');
        $this->assertAuthenticated();
    }

    public function test_an_sms_to_a_number_stored_without_its_prefix_leaves_with_it(): void
    {
        Queue::fake();
        $this->seedTenant();

        app(SmsService::class)->sendSms('0197010007', 'Votre colis est en route');
        app(SmsService::class)->sendOtp('97010008', '12345');

        Queue::assertPushed(SendSms::class, fn (SendSms $job) => $job->phone === '2290197010007' && ! $job->otp);
        Queue::assertPushed(SendSms::class, fn (SendSms $job) => $job->phone === '2290197010008' && $job->otp);
    }

    private function agent(string $email, string $mobile): User
    {
        $u = new User();
        $u->company_id = settings()->id;
        $u->name = 'Agent S133';
        $u->email = $email;
        $u->mobile = $mobile;
        $u->password = bcrypt('secret');
        $u->user_type = UserType::ADMIN;
        $u->role_id = 1;
        $u->permissions = ['dashboard_read'];
        $u->status = Status::ACTIVE;
        $u->verification_status = Status::ACTIVE;
        $u->save();

        return $u;
    }
}
