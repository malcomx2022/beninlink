<?php

namespace Tests\Feature;

use App\Models\Backend\Department;
use App\Models\Backend\Merchant;
use App\Support\SafeUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Finder\Finder;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S126** — un fichier téléversé ne devient jamais du code exécuté par le serveur.
 *
 * Avant ce lot, un marchand (inscription libre) joignait `x.php` à un ticket de support : la pièce
 * jointe n'avait aucune règle, son nom venait de `getClientOriginalExtension()`, le fichier tombait
 * dans `public/uploads/support/` et nginx exécutait tout `.php` sous `public/`. Deux défenses,
 * chacune suffisante seule : l'extension vient du contenu (`SafeUpload`), et nginx n'exécute plus que
 * `index.php`.
 */
class UploadedPhpNeverLandsTest extends TestCase
{
    use MountsTenantRoutes;
    use RefreshDatabase;
    use SeedsTenant;

    /**
     * Un VRAI fichier téléversé : `UploadedFile::fake()` annonce un type MIME tiré du NOM, alors
     * qu'en production `getMimeType()` lit le contenu (`finfo`). C'est le contenu qu'on éprouve ici.
     */
    private function fichier(string $nom, ?string $contenu = null): UploadedFile
    {
        $chemin = tempnam(sys_get_temp_dir(), 'up');
        if ($contenu === null) {
            $img = imagecreatetruecolor(4, 4);
            imagepng($img, $chemin);
        } else {
            file_put_contents($chemin, $contenu);
        }

        return new UploadedFile($chemin, $nom, null, null, true);
    }

    private function deposer(UploadedFile $piece): array
    {
        $marchand = Merchant::firstOrFail();
        $departement = Department::query()->first() ?? tap(new Department(), function ($d) use ($marchand) {
            $d->forceFill(['title' => 'Support', 'company_id' => $marchand->company_id, 'status' => 1])->save();
        });
        $avant = glob(public_path('uploads/support/*')) ?: [];

        $this->actingAs($marchand->user)->post(self::HOTE . '/merchant/support/store', [
            'department_id' => $departement->id, 'service' => 'parcel', 'priority' => 'high', 'subject' => 'Test',
            'description' => 'x', 'date' => '2026-10-08', 'attached_file' => $piece,
        ]);

        $nouveaux = array_values(array_diff(glob(public_path('uploads/support/*')) ?: [], $avant));
        foreach ($nouveaux as $f) {
            @unlink($f);
        }

        return $nouveaux;
    }

    public function test_a_merchant_cannot_drop_php_code_through_a_support_ticket(): void
    {
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();

        $ecrits = $this->deposer($this->fichier('shell.php', '<?php echo "pwn";'));
        $this->assertSame([], $ecrits, 'un fichier PHP téléversé a été écrit sous public/');

        $image = $this->deposer($this->fichier('recu.png'));
        $this->assertCount(1, $image, 'une vraie image passe toujours');
        $this->assertStringEndsWith('.png', $image[0]);
    }

    public function test_the_extension_comes_from_the_content_not_from_the_client(): void
    {
        $this->assertSame('png', SafeUpload::extension($this->fichier('photo.php')));
        $this->assertSame('txt', SafeUpload::extension($this->fichier('notes.php', 'bonjour')));

        foreach (['x.php' => '<?php echo 1;', 'x.svg' => '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
                  'x.html' => '<html><body><script>alert(1)</script></body></html>'] as $nom => $contenu) {
            try {
                SafeUpload::extension($this->fichier(str_replace(['.svg', '.html'], '.png', $nom), $contenu));
                $this->fail("{$nom} est accepté");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('file', $e->errors());
            }
        }
    }

    public function test_no_repository_names_a_file_after_the_client_extension(): void
    {
        $fautifs = [];
        foreach (Finder::create()->files()->in(app_path())->name('*.php') as $f) {
            if (preg_match('/->getClientOriginalExtension\(/', $f->getContents())) {
                $fautifs[] = $f->getRelativePathname();
            }
        }
        $this->assertSame([], $fautifs, "l'extension d'un fichier écrit vient de safeUploadExtension()");
    }

    public function test_nginx_executes_the_front_controller_only(): void
    {
        $conf = file_get_contents(base_path('../docs/guides/infra/nginx/beninlink.conf'));
        $this->assertMatchesRegularExpression('/location\s+~\s+\\\\\.php\$\s*\{\s*return\s+404;\s*\}/', $conf);
        preg_match_all('/location\s+([^{]+)\{(?:[^{}]|\{[^{}]*\})*fastcgi_pass/s', $conf, $blocs);
        $this->assertSame(['= /index.php'], array_map('trim', $blocs[1]), 'seul /index.php passe à PHP-FPM');

        $php = array_map(fn ($f) => $f->getRelativePathname(),
            iterator_to_array(Finder::create()->files()->in(public_path())->name('*.php'), false));
        $this->assertSame(['index.php'], $php, 'un autre point d\'entrée PHP sous public/ répondrait 404');
    }
}
