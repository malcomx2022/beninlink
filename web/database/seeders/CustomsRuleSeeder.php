<?php

namespace Database\Seeders;

use App\Enums\CustomsLevel;
use App\Enums\Status;
use App\Models\Backend\CustomsRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Referentiel douanier de depart — chantier 5.
 *
 * ⚠️ CE JEU DE REGLES EST UNE BASE DE TRAVAIL, PAS UN AVIS DOUANIER.
 * Il reproduit la logique du DAT (§2.5-2.6) et les trois exemples de la maquette
 * validee, et donne une couverture coherente des 8 destinations d'export du
 * Benin. Il DOIT etre relu et corrige par un transitaire ou la douane beninoise
 * avant toute mise en production : les exigences documentaires changent, et une
 * regle BLOQUANTE erronee empeche un marchand de creer son colis.
 *
 * Les regles se modifient ensuite depuis le back-office ; ce seeder ne fait que
 * poser le point de depart, et il est rejouable (updateOrCreate).
 *
 * Structure : une ligne de base par bloc regional, plus des exceptions par pays.
 *   - UEMOA (union douaniere, meme tarif exterieur commun) : circulation plus
 *     simple, l'essentiel tient au certificat d'origine.
 *   - CEDEAO hors UEMOA (Nigeria, Ghana) : frontiere douaniere reelle, controles
 *     plus lourds.
 */
class CustomsRuleSeeder extends Seeder
{
    /** Destinations d'export du Benin : 8 pays, comme annonce dans la maquette. */
    private const COUNTRIES = [
        ['TG', 'Togo', 'uemoa'],
        ['CI', "Cote d'Ivoire", 'uemoa'],
        ['BF', 'Burkina Faso', 'uemoa'],
        ['NE', 'Niger', 'uemoa'],
        ['ML', 'Mali', 'uemoa'],
        ['SN', 'Senegal', 'uemoa'],
        ['NG', 'Nigeria', 'cedeao'],
        ['GH', 'Ghana', 'cedeao'],
    ];

    /** [niveau, document, message] par bloc regional et par categorie. */
    private const BASELINE = [
        'uemoa' => [
            'alimentaire' => [CustomsLevel::WARNING, 'Certificat sanitaire et phytosanitaire', "Certificat sanitaire et phytosanitaire obligatoire pour les denrees alimentaires."],
            'textile' => [CustomsLevel::WARNING, "Declaration d'exportation UEMOA", "Declaration d'exportation UEMOA requise avant expedition."],
            'electronique' => [CustomsLevel::INFO, 'Facture commerciale', "Facture commerciale recommandee pour accelerer le passage en douane."],
            'cosmetique' => [CustomsLevel::WARNING, 'Certificat de libre vente', "Certificat de libre vente exige pour les produits cosmetiques."],
            'pharmaceutique' => [CustomsLevel::BLOCKING, "Autorisation d'importation pharmaceutique", "Produits pharmaceutiques : autorisation prealable obligatoire. Livraison interdite sans ce document."],
            'autre' => [CustomsLevel::INFO, "Certificat d'origine UEMOA", "Certificat d'origine UEMOA recommande pour beneficier de l'exoneration de droits."],
        ],
        'cedeao' => [
            'alimentaire' => [CustomsLevel::WARNING, 'Certificat sanitaire', "Certificat sanitaire obligatoire a l'entree pour les denrees alimentaires."],
            'textile' => [CustomsLevel::WARNING, "Declaration d'exportation CEDEAO", "Declaration d'exportation CEDEAO requise avant expedition."],
            'electronique' => [CustomsLevel::INFO, 'Facture commerciale', "Facture commerciale recommandee pour accelerer le passage en douane."],
            'cosmetique' => [CustomsLevel::WARNING, 'Certificat de libre vente', "Certificat de libre vente exige pour les produits cosmetiques."],
            'pharmaceutique' => [CustomsLevel::BLOCKING, "Autorisation d'importation pharmaceutique", "Produits pharmaceutiques : autorisation prealable obligatoire. Livraison interdite sans ce document."],
            'autre' => [CustomsLevel::INFO, 'Facture commerciale', "Facture commerciale recommandee pour le passage en douane."],
        ],
    ];

    /** Exceptions par pays, qui priment sur la ligne de base. */
    private const OVERRIDES = [
        // Exemple porte par la maquette validee.
        'NG.alimentaire' => [CustomsLevel::BLOCKING, 'Certificat sanitaire NAFDAC', "Certificat sanitaire NAFDAC obligatoire. Livraison interdite sans ce document."],
    ];

    public function run(): void
    {
        // Chaque societe recoit le meme referentiel de depart : les regles sont
        // scopees, une societe pourra ensuite adapter les siennes.
        $companies = DB::table('general_settings')->pluck('id');

        foreach ($companies as $companyId) {
            foreach (self::COUNTRIES as [$code, $name, $bloc]) {
                foreach (self::BASELINE[$bloc] as $category => $rule) {
                    [$level, $document, $message] = self::OVERRIDES[$code . '.' . $category] ?? $rule;

                    CustomsRule::updateOrCreate(
                        [
                            'company_id' => $companyId,
                            'country_code' => $code,
                            'goods_category' => $category,
                        ],
                        [
                            'country_name' => $name,
                            'level' => $level,
                            'required_document' => $document,
                            'message' => $message,
                            'status' => Status::ACTIVE,
                        ]
                    );
                }
            }
        }
    }
}
