<?php

namespace Tests\Feature;

use App\Http\Services\PushNotificationService;
use App\Mail\ContactMail;
use App\Models\Backend\Merchant;
use App\Models\Backend\NotificationSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S11, S12, S13 — durcissement des notifications sortantes.
 *
 * Fermés dans le code le 2026-08-18 sans test ni mise à jour de la
 * cartographie. Ces tests fixent le comportement pour qu'il ne régresse pas :
 *   - S11 : le topic push d'un utilisateur ne se déduit plus de son e-mail ;
 *   - S12 : plus aucun appel sortant SMS / push sans vérification TLS ;
 *   - S13 : l'e-mail de contact part au nom de la plateforme, le visiteur
 *     n'est que l'adresse de réponse.
 */
class NotificationHardeningTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();

        $settings = new NotificationSettings();
        $settings->company_id = settings()->id;
        $settings->fcm_secret_key = '';
        $settings->fcm_topic = 'beninlink';
        $settings->save();
    }

    private function visiteur(): array
    {
        return [
            'name' => 'Aïcha Kora',
            'email' => 'aicha@example.test',
            'subject' => 'Question sur un colis',
            'message' => 'Bonjour, où en est ma livraison ?',
        ];
    }

    public function test_contact_mail_is_sent_by_the_platform_and_replies_go_to_the_visitor(): void
    {
        $mail = new ContactMail($this->visiteur());
        $mail->build();

        $this->assertTrue($mail->hasFrom(settings()->email, settings()->name));
        $this->assertTrue($mail->hasReplyTo('aicha@example.test', 'Aïcha Kora'));
        $this->assertFalse($mail->hasFrom('aicha@example.test'));
        $this->assertTrue($mail->hasTo(settings()->email));
    }

    public function test_public_contact_api_validates_and_sends(): void
    {
        Mail::fake();

        $this->postJson('/api/v10/contact-us', ['email' => 'pas-une-adresse'])->assertStatus(422);
        Mail::assertNothingSent();
        Mail::assertNothingQueued();

        $this->postJson('/api/v10/contact-us', $this->visiteur())->assertOk();
        // D13 — le courriel part désormais **en file** : `assertSent` ne le voit
        // plus, `assertQueued` oui. La propriété vérifiée, elle, ne bouge pas :
        // le visiteur reste l'adresse de réponse, pas l'expéditeur (S13).
        // Le faux mailer stocke le mailable sans le construire : on le construit
        // pour lire ses destinataires.
        Mail::assertQueued(ContactMail::class, fn (ContactMail $mail) => $mail->build()->hasReplyTo('aicha@example.test')
            && $mail->hasFrom(settings()->email, settings()->name));
    }

    public function test_push_topic_is_not_derived_from_the_email(): void
    {
        $service = app(PushNotificationService::class);
        $user = Merchant::firstOrFail()->user;

        $topic = $service->topicFor($user);

        $this->assertStringStartsWith('beninlink_', $topic);
        $this->assertStringNotContainsString($user->email, $topic);
        $this->assertStringNotContainsString('@', $topic);
        // Stable pour un même compte, quel que soit l'identifiant fourni.
        $this->assertSame($topic, $service->topicFor($user->id));
        $this->assertSame($topic, $service->topicFor($user->email));
        // Un inconnu retombe sur le topic global, pas sur un topic devinable.
        $this->assertSame('beninlink', $service->topicFor('inconnu@example.test'));
    }

    public function test_outbound_sms_and_push_verify_tls(): void
    {
        foreach (['app/Http/Services/SmsService.php', 'app/Http/Services/PushNotificationService.php'] as $file) {
            $source = file_get_contents(base_path($file));
            $this->assertDoesNotMatchRegularExpression('/CURLOPT_SSL_VERIFYPEER\s*,\s*(false|0)\b/i', $source, $file);
            $this->assertDoesNotMatchRegularExpression('/CURLOPT_SSL_VERIFYHOST\s*,\s*(false|0)\b/i', $source, $file);
        }
    }
}
