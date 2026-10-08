<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Lot 6 de la charte web (2026-09-18) — accessibilité et petits écrans.
 *
 * ┌─ CE QUE LE PLAN ANNONÇAIT, ET CE QU'IL Y AVAIT ────────────────────────────┐
 * │ Le §8 listait quatre items. Trois avaient déjà été livrés par le lot 1     │
 * │ (`focus-visible`, les trois viewports, le contraste du sidebar inactif) —  │
 * │ ils sont vérifiés ici pour qu'une re-fusion du socle ne les défasse pas.   │
 * │ Les deux qui restaient étaient mal chiffrés, et dans les deux sens :       │
 * │                                                                            │
 * │   « 42 tableaux sans table-responsive » → 57 au total, mais **5** écrans   │
 * │   « les alt à reprendre »               → 47, pas 127                      │
 * │                                                                            │
 * │ Le second chiffre mérite une note, parce que c'est un piège de mesure :    │
 * │ `<img[^>]*>` COUPE une balise Blade en plein milieu — `->` contient un     │
 * │ `>`. Un `<img src="{{ $u->image }}" alt="…">` passe donc pour dépourvu     │
 * │ d'alt. Le compte naïf donnait 127 ; le compte juste, 47. Les tests         │
 * │ ci-dessous emploient un lecteur qui respecte les guillemets.               │
 * └────────────────────────────────────────────────────────────────────────────┘
 */
class WebAccessibilityTest extends TestCase
{
    /**
     * Les vues où un `<table>` ne peut PAS recevoir `table-responsive`, et
     * pourquoi. Ce n'est pas une liste d'oubliés : `table-responsive` pose un
     * `overflow-x: auto` sur un conteneur d'écran.
     *
     *  - **PDF** : dompdf ne fait pas défiler une page. L'attribut est ignoré.
     *  - **impression** : une feuille de papier ne défile pas non plus.
     *  - **courriel** : un client de messagerie retire les `div` de mise en
     *    page ou en ignore l'`overflow` ; Outlook rend le tableau en Word.
     *
     * L'**installeur** figurait ici (« il tourne une fois, chez l'intégrateur »).
     * Il en est sorti : ses trois tableaux listent des extensions PHP et des
     * permissions de dossiers, et un intégrateur qui installe depuis un
     * téléphone est un cas réel. Ils portent l'enveloppe, comme les autres.
     */
    private const TABLEAUX_HORS_ECRAN = [
        '_pdf', 'pdf.blade', 'print', 'pay_slip', '/mail/', '_mail',
    ];

    /**
     * Alternatives qui ne renseignent rien. Une image dont l'alternative est
     * « user » ou « stripe.png » est **pire** qu'une image sans alternative :
     * le lecteur d'écran annonce quelque chose, et ce quelque chose est faux.
     */
    private const VIDES_DE_SENS = [
        'user', 'users', 'image', 'images', 'img', 'photo', 'picture', 'avatar',
        'user avatar', 'logo', 'icon', 'banner', 'thumbnail', 'file',
        'delivered_image', 'signature_image', 'nid', 'trade',
    ];

    /**
     * Lit les `<img …>` d'une source Blade en respectant les guillemets.
     *
     * Une expression regex sur `[^>]*` est FAUSSE ici, et silencieusement : le
     * `>` de `->` ferme la balise trop tôt. C'est ce qui fait la différence
     * entre 127 images « sans alt » et les 47 qui l'étaient vraiment.
     *
     * @return list<string>
     */
    private function balisesImg(string $source): array
    {
        // Les commentaires Blade sortent d'abord : un commentaire qui PARLE d'une
        // balise image en citant son nom n'est pas une image. C'est arrivé.
        $source = preg_replace('/\{\{--.*?--\}\}/s', '', $source) ?? $source;

        $out = [];
        $n = strlen($source);

        foreach ($this->positions($source, '<img') as $debut) {
            $i = $debut + 4;
            $quote = null;

            while ($i < $n) {
                $c = $source[$i];
                if ($quote !== null) {
                    if ($c === $quote) {
                        $quote = null;
                    }
                } elseif ($c === '"' || $c === "'") {
                    $quote = $c;
                } elseif ($c === '>') {
                    break;
                }
                $i++;
            }

            $out[] = substr($source, $debut, $i - $debut + 1);
        }

        return $out;
    }

    /** @return list<int> */
    private function positions(string $source, string $aiguille): array
    {
        $out = [];
        $d = 0;

        while (($d = strpos($source, $aiguille, $d)) !== false) {
            $out[] = $d;
            $d += strlen($aiguille);
        }

        return $out;
    }

    /** @return array<string,string> chemin relatif → chemin absolu */
    private function vues(): array
    {
        $racine = resource_path('views');
        $out = [];

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($racine));
        foreach ($it as $f) {
            if ($f->isFile() && str_ends_with($f->getFilename(), '.blade.php')) {
                $out[str_replace($racine . '/', '', $f->getPathname())] = $f->getPathname();
            }
        }

        ksort($out);

        return $out;
    }

    // — les images ----------------------------------------------------------

    /**
     * LE test de ce lot. Une image sans `alt` est annoncée par son nom de
     * fichier, ou passée sous silence ; une image avec un `alt` vide est
     * **délibérément sautée**. Les deux sont des choix ; l'absence d'attribut
     * n'en est pas un.
     */
    public function test_every_image_in_every_view_declares_an_alt(): void
    {
        $sans = [];

        foreach ($this->vues() as $relatif => $chemin) {
            foreach ($this->balisesImg(file_get_contents($chemin)) as $balise) {
                if (preg_match('/\balt\s*=/', $balise) !== 1) {
                    $sans[] = $relatif . ' : ' . substr(preg_replace('/\s+/', ' ', $balise), 0, 90);
                }
            }
        }

        $this->assertSame([], $sans, "images sans attribut alt :\n" . implode("\n", $sans));
    }

    /**
     * Un `alt` ne se remplit pas au kilomètre. Ces quatre-là portent une
     * information que rien d'autre sur la page ne donne — les vérifier, c'est
     * empêcher qu'un futur passage « uniformise » en `alt="image"`.
     */
    public function test_the_images_that_carry_meaning_say_what_they_show(): void
    {
        $attendu = [
            // Sans elle, une recherche infructueuse ne dit RIEN : le bloc ne
            // contient que cette image, pas une ligne de texte.
            'frontend/pages/tracking.blade.php' => "__('No parcel matches this tracking number')",
            // Logo de partenaire seul dans un lien : sinon le lien est muet.
            'frontend/section/partner.blade.php' => '$partner->name',
            // La présence d'une pièce justificative EST l'information.
            'backend/merchant/merchant-details.blade.php' => "__('Uploaded identity document')",
            // Le logo identifie le site, il ne s'appelle pas « logo ».
            'backend/partials/navber.blade.php' => 'settings()->name',
        ];

        foreach ($attendu as $vue => $fragment) {
            $source = file_get_contents(resource_path('views/' . $vue));
            $altsPleins = '';

            foreach ($this->balisesImg($source) as $balise) {
                if (preg_match('/\balt\s*=\s*"([^"]*)"/', $balise, $m) === 1) {
                    $altsPleins .= $m[1] . "\n";
                }
            }

            $this->assertStringContainsString($fragment, $altsPleins, $vue);
        }
    }

    /**
     * Et l'inverse : quand le texte est DÉJÀ à côté, l'image doit être sautée.
     * Un avatar suivi du nom de la personne annoncé deux fois, c'est du bruit
     * — le `alt=""` est ici la bonne réponse, pas un oubli.
     */
    public function test_a_redundant_image_is_skipped_rather_than_announced_twice(): void
    {
        $source = file_get_contents(resource_path('views/backend/partials/navber.blade.php'));

        foreach ($this->balisesImg($source) as $balise) {
            if (str_contains($balise, "singleUser(\$notify['user_id'])")) {
                $this->assertMatchesRegularExpression(
                    '/\balt\s*=\s*""/',
                    $balise,
                    'le nom de la personne est déjà rendu juste à côté de son avatar'
                );
            }
        }

        // Le nom est bien là : si le socle le retirait, ce `alt=""` deviendrait
        // faux, et ce test le dirait.
        $this->assertStringContainsString(
            "singleUser(\$notify['user_id'])->name",
            $source,
            'l\'avatar n\'est décoratif que parce que le nom l\'accompagne'
        );
    }

    // — les tableaux ---------------------------------------------------------

    /**
     * Un tableau d'écran déborde sur un téléphone : sans conteneur, la PAGE
     * défile latéralement et les colonnes de droite deviennent inatteignables.
     * Cela compte pour des agents en hub, qui travaillent au téléphone.
     *
     * Le test ne réclame l'enveloppe que pour les écrans, et la liste des
     * familles exclues est **dans le code** : ajouter une exception demande de
     * la justifier ici, pas de la laisser passer en silence.
     */
    public function test_every_on_screen_table_can_scroll_on_a_phone(): void
    {
        $nus = [];

        foreach ($this->vues() as $relatif => $chemin) {
            $minuscule = strtolower($relatif);
            foreach (self::TABLEAUX_HORS_ECRAN as $hors) {
                if (str_contains($minuscule, $hors)) {
                    continue 2;
                }
            }

            $source = file_get_contents($chemin);
            foreach ($this->positions($source, '<table') as $d) {
                $amont = substr($source, max(0, $d - 600), min($d, 600));
                if (! str_contains($amont, 'table-responsive')) {
                    $nus[] = $relatif . ' (caractère ' . $d . ')';
                }
            }
        }

        $this->assertSame([], $nus, "tableaux d'écran sans table-responsive :\n" . implode("\n", $nus));
    }

    // — le contenu mixte -----------------------------------------------------

    /**
     * Une feuille ou un script en `http://` sur une page servie en `https://`
     * est du **contenu mixte** : le navigateur le bloque, sans erreur visible
     * ailleurs que dans la console. Le socle chargeait Toastr depuis
     * `cdn.bootcss.com` en clair — dans l'installeur, donc précisément là où
     * l'on a besoin de voir les messages.
     */
    public function test_no_view_loads_a_resource_over_plain_http(): void
    {
        $fautives = [];

        foreach ($this->vues() as $relatif => $chemin) {
            $source = file_get_contents($chemin);
            if (preg_match_all('/(?:src|href)\s*=\s*["\']http:\/\/[^"\']*/i', $source, $m)) {
                foreach ($m[0] as $t) {
                    $fautives[] = $relatif . ' : ' . $t;
                }
            }
        }

        $this->assertSame([], $fautives, "contenu mixte :\n" . implode("\n", $fautives));

        // Et la copie locale existe vraiment — sans quoi on aurait remplacé une
        // ressource bloquée par une ressource absente.
        foreach (['toastr.min.css', 'toastr.min.js'] as $f) {
            $this->assertFileExists(public_path('backend/vendor/toastr/' . $f));
        }
    }

    /**
     * Le `http://` le plus coûteux n'était pas dans une vue.
     *
     * En fastcgi, nginx termine le TLS et **ne le dit pas à PHP** : sauf
     * `fastcgi_param HTTPS on`, `$_SERVER['HTTPS']` reste vide, et c'est la
     * seule chose que Symfony consulte ici (`TrustProxies` ne s'applique pas —
     * il n'y a pas de proxy HTTP devant, et `$proxies` vaut `null`).
     *
     * Conséquence, mesurée en construisant la requête telle que PHP-FPM la
     * reçoit : `url()` et `route()` rendent du **`http://`**. Donc le lien de
     * réinitialisation envoyé par courriel, et le `callback_url` remis à
     * FedaPay — l'URL sur laquelle le client revient après avoir payé.
     */
    public function test_the_nginx_config_tells_php_that_tls_is_terminated(): void
    {
        $conf = base_path('../docs/guides/infra/nginx/beninlink.conf');
        $this->assertFileExists($conf);

        $source = file_get_contents($conf);

        $this->assertMatchesRegularExpression(
            '/^\s*fastcgi_param\s+HTTPS\s+on\s*;/m',
            $source,
            'sans cette ligne, Laravel génère des URL absolues en http://'
        );

        // Dans le bloc PHP, pas ailleurs : ailleurs, elle ne servirait à rien.
        $this->assertLessThan(
            strpos($source, 'fastcgi_param HTTPS on'),
            strpos($source, 'location = /index.php'),
            'la ligne doit vivre dans le bloc qui passe la main à PHP-FPM'
        );
    }

    // — ce que le lot 1 avait déjà posé, et qu'une re-fusion défairait --------

    /**
     * Ces trois-là étaient au programme du lot 6 ; le lot 1 les a livrés en
     * chemin. On ne les refait pas — on les **tient**, parce que ce sont des
     * lignes du socle, donc exactement ce qu'une montée de We Courier réécrit.
     */
    public function test_what_lot_one_already_delivered_is_still_there(): void
    {
        foreach (['theme-frontend.css', 'theme-backoffice.css'] as $feuille) {
            $this->assertStringContainsString(
                ':focus-visible',
                file_get_contents(public_path('beninlink/css/' . $feuille)),
                $feuille
            );
        }

        // Le sidebar inactif du socle était à 4,30:1 — sous AA pour du 14 px.
        // On n'exige pas la disparition de `#71789e` : il subsiste en COMMENTAIRE,
        // pour dire ce qu'on a remplacé et pourquoi. Ce qui compte, c'est que la
        // règle serve le jeton et non l'hexadécimal.
        $backoffice = file_get_contents(public_path('beninlink/css/theme-backoffice.css'));

        $this->assertSame(
            1,
            preg_match(
                '/#71789e[^\n]*\n\.nav-left-sidebar[^{]*\{[^}]*var\(--bl-text-muted\)/',
                $backoffice
            ),
            'la couleur du sidebar inactif doit venir du jeton, pas du gris du socle'
        );
    }

    // — la QUALITÉ de l'alternative, pas sa seule présence ------------------

    /**
     * Le test qui manquait, et il vaut le premier.
     *
     * Le lot précédent a posé un `alt` sur les 47 images qui en manquaient, et
     * son test le vérifie. Mais il ne regardait pas les **106 autres** : 91 y
     * portaient une valeur inutilisable, que le socle avait écrite et que
     * personne n'avait relue.
     *
     *   - `alt="user"` sur 44 images — dont le logo, le logo clair et le
     *     favicon du formulaire de réglages : un agent au lecteur d'écran
     *     entendait « user » trois fois sans pouvoir les distinguer ;
     *   - `alt="stripe.png"` sur 8 images — dont celles de **PayPal**, de
     *     **SSLCommerz** et d'**aamarPay** ;
     *   - `alt="skrill.png"` sur celle de **bKash** ;
     *   - `alt="Logo"` sur 26 images, en anglais, sans dire de qui ;
     *   - `alt="Image"`, `alt="delivered_image"`, `alt="signature_image"`.
     *
     * Autrement dit : poser `alt="user"` sur les 47 restantes aurait suffi à
     * faire passer le test « toute image a un alt ». Celui-ci l'interdit.
     */
    public function test_no_alternative_is_a_filename_or_an_empty_word(): void
    {
        $fautives = [];

        foreach ($this->vues() as $relatif => $absolu) {
            $source = file_get_contents($absolu);

            foreach ($this->balisesImg($source) as $balise) {
                if (!preg_match('/\balt\s*=\s*"([^"]*)"/', $balise, $t)) {
                    continue;
                }
                $valeur = trim($t[1]);

                // Une alternative vide est un choix : image décorative.
                if ($valeur === '') {
                    continue;
                }
                // Une expression Blade est évaluée au rendu : on la croit.
                if (str_contains($valeur, '{{') || str_contains($valeur, '{!!')) {
                    continue;
                }
                if (preg_match('/\.(png|jpe?g|gif|svg|webp|ico)$/i', $valeur)
                    || in_array(mb_strtolower($valeur), self::VIDES_DE_SENS, true)) {
                    $fautives[] = "{$relatif} → alt=\"{$valeur}\"";
                }
            }
        }

        $this->assertSame([], $fautives, "alternatives sans contenu :\n" . implode("\n", $fautives));
    }

    /**
     * Chaque logo de passerelle nomme **sa** passerelle. Le socle mettait
     * `alt="stripe.png"` sur quatre images différentes : sur l'écran de choix du
     * moyen de paiement, un agent entendait « stripe point p n g » quatre fois.
     */
    public function test_a_gateway_logo_names_its_own_gateway(): void
    {
        $passerelles = [
            'paypal' => 'PayPal', 'stripe' => 'Stripe', 'skrill' => 'Skrill',
            'bkash' => 'bKash', 'razorpay' => 'Razorpay',
            'sslecommerce' => 'SSLCommerz', 'aamarpay' => 'aamarPay',
        ];

        $vus = 0;

        foreach ($this->vues() as $relatif => $absolu) {
            foreach ($this->balisesImg(file_get_contents($absolu)) as $balise) {
                foreach ($passerelles as $fichier => $nom) {
                    if (!str_contains($balise, "payout/{$fichier}.png")) {
                        continue;
                    }
                    $vus++;
                    $this->assertMatchesRegularExpression(
                        '/\balt\s*=\s*"' . preg_quote($nom, '/') . '"/',
                        $balise,
                        "{$relatif} : l'image de {$nom} n'annonce pas {$nom}",
                    );
                }
            }
        }

        $this->assertSame(13, $vus, 'les treize logos de passerelle doivent être vus');
    }

    /**
     * Le logo du transporteur dit **de qui** il est.
     *
     * On ne vise que les images qui lisent le logo par l'aide `settings()` —
     * le logo affiché *en tant que* logo. Les trois aperçus du formulaire de
     * réglages lisent `$settings->logo_image`, le modèle du formulaire : là,
     * l'alternative doit nommer le CHAMP prévisualisé. C'est le test suivant.
     */
    public function test_the_carrier_logo_announces_the_carrier(): void
    {
        $vus = 0;

        foreach ($this->vues() as $relatif => $absolu) {
            foreach ($this->balisesImg(file_get_contents($absolu)) as $balise) {
                if (!preg_match('/src\s*=\s*"[^"]*@?settings\(\)\s*->\s*(logo_image|light_logo_image|LogoImage|rxlogo)/', $balise)) {
                    continue;
                }
                $vus++;
                $this->assertMatchesRegularExpression(
                    '/\balt\s*=\s*"\{\{\s*@?(settings\(\)->name|\$companyName)/',
                    $balise,
                    "{$relatif} : le logo n'annonce pas la raison sociale",
                );
            }
        }

        $this->assertGreaterThanOrEqual(25, $vus, 'trop peu de logos vus');
    }

    /** Les trois aperçus des réglages nomment le champ qu'ils montrent. */
    public function test_the_settings_previews_name_the_field_they_preview(): void
    {
        $vue = file_get_contents(resource_path('views/backend/general_settings/index.blade.php'));

        foreach (['logo', 'light_logo', 'favicon'] as $champ) {
            $this->assertMatchesRegularExpression(
                '/<img[^>]*src\s*=\s*"\{\{\$settings->' . $champ . '_image\}\}"\s+alt\s*=\s*"\{\{ __\(.levels\.' . $champ . '.\) \}\}"/',
                $vue,
                "l'aperçu de {$champ} ne nomme pas son champ",
            );
        }
    }

    /**
     * F4 — aucun de ces trois courriels n'interroge la société **au rendu**.
     *
     * L'alternative textuelle du logo posée par le lot précédent était
     * `alt="{{ settings()->name }}"`. Or ces trois gabarits sont bâtis par le
     * worker (`ShouldQueue`), où `settings()` retombe sur la société 1 : le
     * marchand recevait le nom — et le logo — d'un autre transporteur. Les
     * mailables figent désormais la société ; le comportement est vérifié par
     * `QueuedDeliveryTest`.
     */
    public function test_no_queued_mail_view_reads_the_carrier_at_render_time(): void
    {
        foreach ([
            'backend/contact/contact_mail.blade.php',
            'backend/merchant/mail/signup.blade.php',
            'backend/super-admin/company/mail/signup.blade.php',
        ] as $vue) {
            $this->assertStringNotContainsString(
                'settings()',
                file_get_contents(resource_path('views/' . $vue)),
                "{$vue} lit encore settings() au rendu — F4",
            );
        }
    }

    /** La preuve de livraison porte deux libellés, et ils sont traduits. */
    public function test_the_delivery_proof_labels_are_translated(): void
    {
        foreach (['fr', 'en'] as $langue) {
            $table = require base_path("lang/{$langue}/parcel.php");
            $this->assertArrayHasKey('delivered_photo', $table, $langue);
            $this->assertArrayHasKey('signature', $table, $langue);
        }

        $this->assertSame('Photo de livraison', trans('parcel.delivered_photo', [], 'fr'));

        $vue = file_get_contents(resource_path('views/backend/parcel/parcel-delivered-info.blade.php'));
        $this->assertStringNotContainsString('>Delivered Photo<', $vue);
        $this->assertStringContainsString("__('parcel.delivered_photo')", $vue);
    }
}
