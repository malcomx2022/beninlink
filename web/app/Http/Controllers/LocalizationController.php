<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\App;

class LocalizationController extends Controller
{
    /**
     * Bascule la langue de la session.
     *
     * ⚠️ Le socle posait en session **n'importe quelle** chaîne reçue dans l'URL.
     * `/localization/xx` mettait donc l'interface dans une locale inexistante :
     * toutes les traductions retombaient sur leurs clés brutes (`levels.name`),
     * durablement, la session survivant aux requêtes — et rien n'indiquait à
     * l'utilisateur qu'il devait appeler `/localization/fr` pour s'en sortir.
     *
     * Seules les langues déclarées par `config/locales.php` sont acceptées ; une
     * valeur inconnue est **ignorée** (on ne touche pas à la session) et la page
     * d'origine est rendue telle quelle. Ignorer plutôt que retomber sur le
     * français est volontaire : un lien mal formé ne doit pas changer la langue
     * de quelqu'un qui avait choisi l'anglais.
     */
    public function setLocalization(string $language)
    {
        if (array_key_exists($language, config('locales.supported', []))) {
            App::setLocale($language);
            session()->put('locale', $language);
        }

        return redirect()->back();
    }
}
