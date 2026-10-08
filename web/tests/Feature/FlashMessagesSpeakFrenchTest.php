<?php

namespace Tests\Feature;

use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * **S118** — les messages du back-office et de l'API parlent français.
 *
 * S117 tenait les vues. Restait ce que le **code** écrit à l'écran : 128 notifications
 * `Toastr` et douze réponses d'API en anglais littéral (« Something went wrong. » trente-six
 * fois, « Oparation Failds! », « Catagory Insert Successfully! », « Successfully sended. »),
 * six `Toastr::error('parcel.error_msg')` qui affichaient **la clé brute**, et des titres
 * « Error » / « Success » écrits en dur. L'API négocie pourtant sa langue (S90) : ses messages
 * restaient anglais quelle que soit la langue demandée.
 *
 * Règles tenues ici, sur tout `app/` :
 * - le message et le titre d'un `Toastr`, le message de `responseWithSuccess()` /
 *   `responseWithError()` et celui d'un `->with('success' | 'error' | …, …)` passent par `__()` ;
 * - une phrase traduite existe dans `lang/fr.json`, une clé de fichier dans `lang/fr/`.
 */
class FlashMessagesSpeakFrenchTest extends TestCase
{
    /** @return array<string, string> chemin relatif => source */
    private function sources(): array
    {
        $sources = [];
        foreach (Finder::create()->files()->in(app_path())->name('*.php') as $fichier) {
            $sources[$fichier->getRelativePathname()] = $fichier->getContents();
        }

        return $sources;
    }

    public function test_no_message_shown_to_a_user_is_a_literal(): void
    {
        $formes = [
            'message Toastr' => "/Toastr::\\w+\\(\\s*['\"]/",
            'titre Toastr' => "/Toastr::\\w+\\([^;]*,\\s*['\"][^'\"]*['\"]\\s*\\)/",
            'réponse d\'API' => "/responseWith(?:Success|Error)\\(\\s*['\"]/",
            'message de session' => "/->with\\(\\s*'(?:success|danger|error|warning|message)'\\s*,\\s*['\"]/",
        ];
        $litteraux = [];
        $lus = 0;
        foreach ($this->sources() as $chemin => $source) {
            $lus++;
            foreach ($formes as $forme => $motif) {
                if (preg_match_all($motif, $source, $trouves, PREG_OFFSET_CAPTURE)) {
                    foreach ($trouves[0] as [$texte, $position]) {
                        $ligne = substr_count(substr($source, 0, $position), "\n") + 1;
                        $litteraux[] = "{$chemin}:{$ligne} ({$forme}) {$texte}";
                    }
                }
            }
        }

        $this->assertGreaterThan(300, $lus, 'le parcours a bien lu app/');
        $this->assertSame([], $litteraux, "message écrit en dur :\n" . implode("\n", $litteraux));
    }

    public function test_every_phrase_or_key_translated_in_app_exists_in_french(): void
    {
        $francais = json_decode(file_get_contents(lang_path('fr.json')), true, 512, JSON_THROW_ON_ERROR);
        $catalogues = [];
        $manquantes = [];
        foreach ($this->sources() as $chemin => $source) {
            preg_match_all("/(?:__|trans)\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/", $source, $appels);
            foreach ($appels[1] as $cle) {
                $cle = stripslashes($cle);
                if (preg_match('/^([A-Za-z0-9_\-]+)\.([A-Za-z0-9_.\-]*)$/', $cle, $parties) && file_exists(lang_path("fr/{$parties[1]}.php"))) {
                    $catalogues[$parties[1]] ??= array_map('strval', array_keys(\Illuminate\Support\Arr::dot(require lang_path("fr/{$parties[1]}.php"))));
                    // une clé à suffixe calculé (`'status.' . $x`) compte par son fichier
                    $trouvee = $parties[2] === '' || str_ends_with($parties[2], '_') || str_ends_with($parties[2], '.')
                        || in_array($parties[2], $catalogues[$parties[1]], true)
                        || preg_grep('/^' . preg_quote($parties[2], '/') . '\./', $catalogues[$parties[1]]);
                    if (! $trouvee) {
                        $manquantes[] = "{$cle} ({$chemin})";
                    }
                    continue;
                }
                if (file_exists(lang_path("fr/{$cle}.php"))) {
                    continue; // le catalogue entier
                }
                if (! isset($francais[$cle])) {
                    $manquantes[] = "« {$cle} » ({$chemin})";
                }
            }
        }

        $this->assertSame([], array_values(array_unique($manquantes)), "absent de lang/fr :\n" . implode("\n", $manquantes));
    }

    /** Ce que lit un utilisateur, en français, pour trois messages relevés. */
    public function test_the_messages_read_in_french(): void
    {
        app()->setLocale('fr');

        $this->assertSame('Une erreur est survenue.', __('Something went wrong.'));
        $this->assertSame('L\'opération a échoué.', __('Operation failed.'));
        $this->assertNotSame('parcel.error_msg', __('parcel.error_msg'), 'la clé se traduit au lieu de s\'afficher brute');
    }

    /**
     * Cinq notes de relevé (retour d'un colis au marchand) s'écrivaient comme leur clé brute : le
     * relevé du marchand affichait `statementNote.return_received_by_merchant_statment`. Les lignes
     * déjà écrites se lisent traduites, une note libre reste telle quelle, et `retours-annules`
     * retrouve une ligne sous l'une ou l'autre forme.
     */
    public function test_a_statement_note_stored_as_a_key_reads_in_french(): void
    {
        app()->setLocale('fr');
        $cle = \App\Console\Commands\CancelledReturnsCommand::NOTE_MARCHAND;

        foreach ([\App\Models\Backend\MerchantStatement::class, \App\Models\Backend\DeliverymanStatement::class, \App\Models\Backend\CourierStatement::class] as $modele) {
            $ligne = (new $modele)->forceFill(['note' => $cle]);
            $this->assertSame('Dépenses : frais de retour du colis', $ligne->note, $modele);
            $this->assertSame('Recharge de juin', (new $modele)->forceFill(['note' => 'Recharge de juin'])->note, $modele);
        }

        $formes = \App\Console\Commands\CancelledReturnsCommand::formes($cle);
        $this->assertContains($cle, $formes, 'une ligne écrite avant S118');
        $this->assertContains('Dépenses : frais de retour du colis', $formes, 'une ligne écrite en français');
        $this->assertContains('Expense: parcel return charge', $formes, 'une ligne écrite en anglais');
    }
}
