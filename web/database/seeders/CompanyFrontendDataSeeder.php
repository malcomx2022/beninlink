<?php

namespace Database\Seeders;

use App\Models\Backend\FrontWeb\Blog;
use App\Models\Backend\FrontWeb\Faq;
use App\Models\Backend\FrontWeb\Partner;
use App\Models\Backend\FrontWeb\Service;
use App\Models\Backend\FrontWeb\SocialLink;
use App\Models\Backend\FrontWeb\WhyCourier;
use App\Models\Backend\Upload;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * **S93** — la vitrine d'une société neuve parle français.
 *
 * Chaque société créée par le super-admin reçoit ici sa vitrine publique
 * (`CompanyRepository::companySiteData`). Le socle la remplissait en anglais
 * et en *lorem ipsum* (`Faker`), avec des pages légales vides de sens et des
 * compteurs de marketing inventés ; les semences de la société 1
 * (`Backend/FrontWeb`) répétaient le même contenu en SQL MySQL brut, injouable
 * sur SQLite. Depuis S93 **ce fichier est la seule source** : contenu français,
 * honnête (les compteurs partent de zéro, le transporteur les met à jour dans
 * Vitrine → Sections), inséré par Eloquent et `DB::table()` — la suite le joue.
 *
 * Les images restent celles du socle (`public/frontend/images/...`).
 */
class CompanyFrontendDataSeeder extends Seeder
{
    /** Les semences de la société 1 passent par les méthodes publiques ; `db:seed` n'appelle pas `run()` directement. */
    public function run(): void
    {
    }

    public function companySiteData($company_id): void
    {
        $company_id = (int) $company_id;

        $this->reseauxSociaux($company_id);
        $this->services($company_id);
        $this->atouts($company_id);
        $this->faq($company_id);
        $this->partenaires($company_id);
        $this->articles($company_id);
        $this->pages($company_id);
        $this->sections($company_id);
    }

    public function reseauxSociaux(int $company_id): void
    {
        $socials = [
            ['name' => 'Facebook',  'icon' => 'fab fa-facebook-square', 'link' => 'https://www.facebook.com',  'status' => 1],
            ['name' => 'Instagram', 'icon' => 'fab fa-instagram',       'link' => 'https://www.instagram.com', 'status' => 1],
            ['name' => 'WhatsApp',  'icon' => 'fab fa-whatsapp',        'link' => 'https://wa.me/2290197000000', 'status' => 1],
            ['name' => 'LinkedIn',  'icon' => 'fab fa-linkedin',        'link' => 'https://www.linkedin.com',  'status' => 1],
            ['name' => 'YouTube',   'icon' => 'fab fa-youtube',         'link' => 'https://www.youtube.com',   'status' => 0],
            ['name' => 'X',         'icon' => 'fab fa-twitter',         'link' => 'https://www.x.com',         'status' => 0],
        ];

        foreach ($socials as $position => $social) {
            $socialLink             = new SocialLink();
            $socialLink->company_id = $company_id;
            $socialLink->name       = $social['name'];
            $socialLink->icon       = $social['icon'];
            $socialLink->link       = $social['link'];
            $socialLink->status     = $social['status'];
            $socialLink->position   = $position + 1;
            $socialLink->save();
        }
    }

    public function services(int $company_id): void
    {
        $services = [
            ['Livraison e-commerce', 'truck.png',
                'Vos commandes en ligne livrées à vos clients à Cotonou, dans les départements et dans la CEDEAO, avec un suivi à chaque étape et un encaissement à la livraison reversé sur votre portefeuille.'],
            ['Ramassage et dépôt', 'pick-drop.png',
                'Un livreur passe prendre vos colis à votre boutique ou à votre domicile, aux horaires convenus. Vous déposez aussi vos envois dans l\'agence la plus proche.'],
            ['Emballage', 'packageing.png',
                'Cartons, enveloppes et protections pour les produits fragiles ou liquides, facturés au tarif affiché dans votre espace marchand.'],
            ['Entreposage', 'warehouse.png',
                'Vos stocks gardés dans nos agences, préparés et expédiés à la commande : vous vendez, nous livrons.'],
        ];

        foreach ($services as $position => [$titre, $image, $description]) {
            $upload           = new Upload();
            $upload->original = 'frontend/images/services/' . $image;
            $upload->save();

            $service              = new Service();
            $service->company_id  = $company_id;
            $service->title       = $titre;
            $service->image_id    = $upload->id;
            $service->description = $description;
            $service->position    = $position + 1;
            $service->save();
        }
    }

    /** « Pourquoi nous » : les six atouts affichés sur la page d'accueil. */
    public function atouts(int $company_id): void
    {
        $atouts = [
            'Livraison dans les délais'   => 'timly-delivery.png',
            'Ramassage sans limite'       => 'limitless-pickup.png',
            'Paiement à la livraison'     => 'cash-on-delivery.png',
            'Versements à tout moment'    => 'payment.png',
            'Manipulation sécurisée'      => 'handling.png',
            'Suivi en temps réel'         => 'live-tracking.png',
        ];

        $position = 0;
        foreach ($atouts as $titre => $image) {
            $upload           = new Upload();
            $upload->original = 'frontend/images/whycourier/' . $image;
            $upload->save();

            $whyCourier             = new WhyCourier();
            $whyCourier->company_id = $company_id;
            $whyCourier->title      = $titre;
            $whyCourier->image_id   = $upload->id;
            $whyCourier->position   = ++$position;
            $whyCourier->save();
        }
    }

    public function faq(int $company_id): void
    {
        $questions = [
            ['Où livrez-vous ?',
                'À Cotonou et son agglomération, dans les chefs-lieux des départements du Bénin et, pour les envois internationaux, dans les pays de la CEDEAO. Le tarif dépend de la zone de destination, affichée au moment de la création du colis.'],
            ['Quels sont les délais de livraison ?',
                'En général sous 24 heures à Cotonou, 48 à 72 heures dans les autres villes du Bénin. Les délais vers la CEDEAO dépendent des formalités de douane.'],
            ['Comment suivre mon colis ?',
                'Chaque colis porte un numéro de suivi. Saisissez-le dans la rubrique Suivi du site ou ouvrez votre espace marchand : chaque changement de statut y apparaît, et vous êtes notifié.'],
            ['Comment fonctionne le paiement à la livraison ?',
                'Le livreur encaisse le montant que vous avez indiqué à la création du colis. La somme est portée sur votre portefeuille marchand, déduction faite des frais de livraison, puis versée selon la périodicité convenue.'],
            ['Comment suis-je payé ?',
                'Par Mobile Money (MTN MoMo ou Moov Money) ou par virement bancaire, à partir de votre portefeuille marchand. Chaque versement donne lieu à un relevé.'],
            ['Que faire si le destinataire est absent ?',
                'Le livreur tente de le joindre puis reprogramme un passage. Après deux tentatives infructueuses, le colis revient en agence et vous êtes prévenu ; les frais de retour sont ceux du barème.'],
            ['Quels documents pour ouvrir un compte marchand ?',
                'Votre IFU et, pour une société, votre RCCM. L\'inscription se fait en ligne ou en agence ; votre compte est actif dès validation.'],
            ['Livrez-vous des produits fragiles ou liquides ?',
                'Oui, avec un emballage adapté et un supplément indiqué au moment de la création du colis. Les produits interdits par la réglementation douanière ne sont pas acceptés.'],
        ];

        foreach ($questions as $position => [$question, $reponse]) {
            $faq             = new Faq();
            $faq->company_id = $company_id;
            $faq->question   = $question;
            $faq->answer     = $reponse;
            $faq->position   = $position + 1;
            $faq->save();
        }
    }

    /** Les logos de démonstration du socle ; le transporteur les remplace par ses vrais partenaires. */
    public function partenaires(int $company_id): void
    {
        $images = ['1.png', 'atom.png', 'digg.png', '2.png', 'huawei.png', 'ups.png'];

        foreach ($images as $position => $image) {
            $upload           = new Upload();
            $upload->original = 'frontend/images/partner/' . $image;
            $upload->save();

            $partner             = new Partner();
            $partner->company_id = $company_id;
            $partner->name       = 'Partenaire ' . ($position + 1);
            $partner->image_id   = $upload->id;
            $partner->link       = '#';
            $partner->position   = $position + 1;
            $partner->save();
        }
    }

    /** Trois articles de démonstration, courts et vrais : la rubrique n'est pas vide le premier jour. */
    public function articles(int $company_id): void
    {
        $articles = [
            ['Bien préparer un colis',
                'Un carton à la taille du produit, du calage pour les objets fragiles, le nom et le téléphone du destinataire lisibles : un colis bien préparé arrive en bon état et du premier coup.'],
            ['Le paiement à la livraison, mode d\'emploi',
                'Indiquez le montant à encaisser à la création du colis. Le livreur le collecte, votre portefeuille marchand est crédité, et vous retirez par Mobile Money ou virement.'],
            ['Livrer dans la CEDEAO',
                'Les envois vers les pays voisins passent la douane : déclarez le contenu et sa valeur avec précision, nous vous alertons à chaque étape du dédouanement.'],
        ];

        foreach ($articles as $position => [$titre, $texte]) {
            $blog              = new Blog();
            $blog->company_id  = $company_id;
            $blog->title       = $titre;
            $blog->description = $texte;
            $blog->position    = $position + 1;
            $blog->created_by  = 1;
            $blog->save();
        }
    }

    public function pages(int $company_id): void
    {
        $maintenant = now();
        $pages = [
            ['privacy_policy', 'Politique de confidentialité',
                'Les données que vous nous confiez (identité, coordonnées, adresses de livraison, montants encaissés) servent uniquement à exécuter vos envois, à vous payer et à répondre à nos obligations légales. Elles ne sont ni vendues ni cédées. Vous pouvez demander leur consultation, leur correction ou leur suppression auprès de notre service client.'],
            ['terms_conditions', 'Conditions générales',
                'Chaque colis est pris en charge au tarif de sa zone, affiché avant confirmation. Le marchand déclare le contenu et la valeur de l\'envoi ; les produits interdits par la réglementation sont refusés. Les montants encaissés à la livraison sont crédités sur le portefeuille marchand et versés selon la périodicité convenue. Les réclamations se font dans les 48 heures suivant la livraison ou le retour.'],
            ['about_us', 'À propos',
                'Nous sommes un transporteur béninois : ramassage, livraison et encaissement à la livraison pour les commerçants et les boutiques en ligne, à Cotonou, dans les départements et vers la CEDEAO. Chaque colis est suivi de la prise en charge à la remise.'],
            ['faq', 'Des questions ?',
                'Les réponses aux questions que l\'on nous pose le plus souvent.'],
            ['contact', 'Contactez-nous',
                'Une question, une réclamation, un partenariat : écrivez-nous, nous répondons sous un jour ouvré.'],
        ];

        DB::table('pages')->insert(array_map(fn (array $page) => [
            'company_id'  => $company_id,
            'page'        => $page[0],
            'title'       => $page[1],
            'description' => $page[2],
            'status'      => 1,
            'created_at'  => $maintenant,
            'updated_at'  => $maintenant,
        ], $pages));
    }

    /**
     * Les sections de la page d'accueil et du pied de page (`section($type, $key)`).
     * Les compteurs partent d'une agence et de zéro colis : ils se mettent à jour
     * dans Vitrine → Sections, ils ne s'inventent pas.
     */
    public function sections(int $company_id): void
    {
        $maintenant = now();
        $sections = [
            [1, 'title_1',    'VOS COLIS'],
            [1, 'title_2',    'LIVRÉS AU BÉNIN'],
            [1, 'title_3',    'ET DANS LA CEDEAO'],
            [1, 'sub_title',  'Ramassage, livraison et paiement à la livraison pour les commerçants : simple, suivi, payé.'],
            [1, 'banner',     null],
            [2, 'branch_icon',    'fa fa-warehouse'],
            [2, 'branch_count',   '1'],
            [2, 'branch_title',   'Agence'],
            [2, 'parcel_icon',    'fa fa-gifts'],
            [2, 'parcel_count',   '0'],
            [2, 'parcel_title',   'Colis livrés'],
            [2, 'merchant_icon',  'fa fa-users'],
            [2, 'merchant_count', '0'],
            [2, 'merchant_title', 'Marchands servis'],
            [2, 'reviews_icon',   'fa fa-thumbs-up'],
            [2, 'reviews_count',  '0'],
            [2, 'reviews_title',  'Avis positifs'],
            [3, 'about_us', 'Transporteur béninois : ramassage, livraison et encaissement à la livraison pour les commerçants, à Cotonou, dans les départements et vers la CEDEAO.'],
            [4, 'subscribe_title',       'Restez informés'],
            [4, 'subscribe_description', 'Nos nouveautés, nos zones et nos tarifs, directement dans votre boîte.'],
            [5, 'playstore_icon', 'fa-brands fa-google-play'],
            [5, 'playstore_link', '#'],
            [5, 'ios_icon',       'fa-brands fa-app-store-ios'],
            [5, 'ios_link',       '#'],
            [6, 'map_link', 'https://www.google.com/maps?q=6.3703,2.3912&z=13&output=embed'],
        ];

        DB::table('sections')->insert(array_map(fn (array $section) => [
            'company_id' => $company_id,
            'type'       => $section[0],
            'key'        => $section[1],
            'value'      => $section[2],
            'created_at' => $maintenant,
            'updated_at' => $maintenant,
        ], $sections));
    }
}
