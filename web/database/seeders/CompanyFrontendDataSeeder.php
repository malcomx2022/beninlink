<?php

namespace Database\Seeders;

use App\Models\Backend\FrontWeb\Blog;
use App\Models\Backend\FrontWeb\Faq;
use App\Models\Backend\FrontWeb\Partner;
use App\Models\Backend\FrontWeb\Service;
use App\Models\Backend\FrontWeb\SocialLink;
use App\Models\Backend\FrontWeb\WhyCourier;
use App\Models\Backend\Upload;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
class CompanyFrontendDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    }
 
    public function companySiteData($company_id)
    {
        // Start Social Link
        $socials = [
            [
                'name'  => 'facebook',
                'icon'  => 'fab fa-facebook-square',
                'link'  => 'https://www.facebook.com',
                'status' => 1
            ],
            [
                'name'  => 'Instagram',
                'icon'  => 'fab fa-instagram',
                'link'  => 'https://www.instagram.com',
                'status' => 1
            ],
            [
                'name'  => 'Twitter',
                'icon'  => 'fab fa-twitter',
                'link'  => 'https://www.twitter.com',
                'status' => 1
            ],
            [
                'name'  => 'Youtube',
                'icon'  => 'fab fa-youtube',
                'link'  => 'https://www.youtube.com',
                'status' => 0
            ],
            [
                'name'  => 'Whatsapp',
                'icon'  => 'fab fa-whatsapp',
                'link'  => 'https://www.whatsapp.com',
                'status' => 0
            ],
            [
                'name'  => 'Skype',
                'icon'  => 'fab fa-skype',
                'link'  => 'https://www.skype.com',
                'status' => 1
            ]
        ];

        foreach ($socials as  $key => $social) {
            $socialLink                 = new SocialLink();
            $socialLink->company_id     = $company_id;
            $socialLink->name           = $social['name'];
            $socialLink->icon           = $social['icon'];
            $socialLink->link           = $social['link'];
            $socialLink->status         = $social['status'];
            $socialLink->position       = ($key + 1);
            $socialLink->save();
        }
        // End Social Link

        //Start Service
        $faker = Faker::create();
        $services = [
            'E-Commerce delivery' => 'truck.png',
            'Pick & Drop' => 'pick-drop.png',
            'Packageing' => 'packageing.png',
            'Warehousing' => 'warehouse.png',
        ];

        $i = 0;
        foreach ($services as  $key => $serviceT) {
            $i++;
            $upload           = new Upload();
            $upload->original = "frontend/images/services/" . $serviceT;
            $upload->save();

            $service              = new Service();
            $service->company_id     = $company_id;
            $service->title       = $key;
            $service->image_id    = $upload->id;
            $service->description = $faker->sentence(50);
            $service->position    = $i;
            $service->save();
        }
        //End Service

        //Start Why Courier
        $lists = [
            'Timely Delivery '      => 'timly-delivery.png',
            'Limitless Pickup'      => 'limitless-pickup.png',
            'Cash on delivery (COD)' => 'cash-on-delivery.png',
            'Get Payment Any Time ' => 'payment.png',
            'Secure Handling '      => 'handling.png',
            'Live Tracking Update'  => 'live-tracking.png',
        ];

        $i = 0;
        foreach ($lists as  $key => $item) {
            $i++;
            $upload           = new Upload();
            $upload->original = "frontend/images/whycourier/" . $item;
            $upload->save();

            $whyCourier             = new WhyCourier();
            $whyCourier->company_id     = $company_id;
            $whyCourier->title      = $key;
            $whyCourier->image_id   = $upload->id;
            $whyCourier->position   = $i;
            $whyCourier->save();
        }
        //End Why Courier

        //Start FAQ
        $faker = Faker::create();
        $questions = [
            'What is wecourier Delivery?',
            'How do I contact you?',
            'How can a merchant track their parcel delivery?',
            'How do I send a product/ courier via wecourier Delivery?',
            'I want to hold a parcel for more than 3 days before home delivery. Is it possible?',
            'Can you do product exchange from customers?',
            'Can you deliver to addresses inside Cantonment or other restricted areas?',
            'I do not have a Facebook page, can I register as a merchant?',
            'What kind of products does wecourier deliver?',
        ];
        foreach ($questions as $key => $question) {
            $faq                = new Faq();
            $faq->company_id     = $company_id;
            $faq->question      = $question;
            $faq->answer   = $faker->sentence(30);
            $faq->position      = ($key + 1);
            $faq->save();
        }
        //End FAQ

        //Start Partner
        $faker  = Faker::create('en_US');
        $data = [
            '1.png',
            'atom.png',
            'digg.png',
            '2.png',
            'huawei.png',
            'ups.png',
            '1.png',
            'atom.png',
            'digg.png',
            '2.png',
            'huawei.png',
            'ups.png'
        ];
        foreach ($data as $key => $value) {
            $upload           = new Upload();
            $upload->original = "frontend/images/partner/" . $value;
            $upload->save();

            $partner           = new Partner();
            $partner->company_id     = $company_id;
            $partner->name     = $faker->unique()->company();
            $partner->image_id = $upload->id;
            $partner->link     = '#';
            $partner->position = ($key + 1);
            $partner->save();
        }
        //End Partner

        //Start Blogs
        $faker = Faker::create();
        for ($i = 0; $i < 10; $i++) {
            $blog              = new Blog();
            $blog->company_id     = $company_id;
            $blog->title       = $faker->unique()->sentence(10);
            $blog->description = $faker->unique()->sentence(100);
            $blog->position    = $i;
            $blog->created_by  = 1;
            $blog->save();
        }
        //End Blogs

        //Start Pages
        DB::statement("INSERT INTO `pages` (`company_id`, `page`, `title`, `description`, `status`, `created_at`, `updated_at`) VALUES
            ($company_id, 'privacy_policy', 'Privacy & Policy', 'Deleniti magnam dignissimos similique a dolorem magni culpa vero voluptatem quia nostrum officia itaque velit reiciendis corporis quia officia labore odit maiores dolor aspernatur eum beatae alias sed et tempore dignissimos nesciunt iusto iste facere tenetur quod adipisci natus sint cumque quia cupiditate vitae rerum velit rerum quibusdam consequatur sed sequi aut qui incidunt qui repudiandae mollitia tenetur fuga inventore assumenda qui vitae molestiae consequatur aut et libero similique quidem eligendi vero mollitia dolores quos aut dolores molestias repellat quod tenetur voluptas impedit illo tempora voluptatem enim nulla voluptate qui est quo temporibus aperiam blanditiis commodi et et ut id quisquam ut suscipit cumque atque voluptas veritatis aut eligendi atque ut sunt tempora consequatur et soluta maxime nesciunt molestias est quam atque doloremque in quia aut est quasi qui minima modi explicabo sunt sit ipsa sit ipsum ipsum et quos beatae molestiae voluptas aliquid distinctio aut earum laborum libero omnis dolor voluptatem nobis aut ut ullam voluptatem labore magnam veniam voluptatum blanditiis eum unde laudantium atque aperiam a perspiciatis excepturi aperiam in quo ea ut sequi iste cumque illo iste natus rerum velit labore eum odit non eum voluptate voluptas expedita cupiditate a dolorem nulla rerum facilis illum officiis repellendus sed quis officiis rerum inventore molestias voluptatem expedita quia enim quo neque labore architecto quos vel in omnis saepe optio et et et nobis odio id suscipit vero nisi maxime ab magni est quos voluptatem adipisci illum quaerat delectus explicabo est laboriosam pariatur qui deleniti error aut aliquid et molestias animi facilis et perspiciatis ut ab incidunt incidunt maxime vel deleniti impedit voluptas labore et delectus id ipsam deleniti maiores excepturi esse aut iure ipsam vero officia fuga repellat molestias dolorem molestiae laboriosam repellendus nemo qui pariatur libero nisi recusandae aspernatur neque dolorum voluptatem magni quo quia sequi distinctio itaque cupiditate reiciendis ea quisquam nam ut vero ut non velit eveniet fuga voluptatibus molestias velit tenetur eos ex quam voluptatibus officiis dolorem non sit dolorem temporibus accusamus magni corrupti quia a saepe qui nam rerum culpa dicta quia et veniam cupiditate ut maiores aliquid eaque odio sint quia tempore ut nemo doloremque omnis expedita qui molestias deserunt repudiandae esse beatae et est eum praesentium dolor omnis cum laboriosam ipsa sit eos id sint minima alias molestiae sint voluptate exercitationem excepturi molestiae accusamus quia minima enim sunt nulla accusantium dolor voluptas nostrum et velit veritatis sunt cupiditate possimus quibusdam itaque repellendus eos officiis ipsum iusto qui cupiditate repellendus quod unde accusamus voluptatem sunt soluta eius molestiae neque ipsa molestiae dicta dolor placeat non sunt quidem molestiae aperiam sunt suscipit placeat ut sapiente molestiae suscipit velit aliquam dolores dolorem ex beatae ut aliquid quae repudiandae consequatur laudantium minima ut voluptatem dolorum iusto ea explicabo suscipit et at voluptatem autem mollitia fugiat modi sequi eum reiciendis aut quam suscipit ut aut cupiditate non non dolores dolorem tempore ut dolorem ex fugit est fugiat nesciunt fugit corrupti consequatur ut sit aliquid expedita illum quis quos necessitatibus voluptatem hic dolorum autem aut laborum quam fugiat veritatis mollitia voluptatem culpa ut consequuntur perferendis rerum sit nihil rerum voluptatem voluptatem sit autem hic omnis totam inventore incidunt laborum doloribus cumque esse consequuntur sed tempora vel dolor quisquam qui quaerat voluptas architecto sunt saepe at assumenda quisquam facere et dolorem accusamus quia aliquid dignissimos quis adipisci veniam nemo aut aperiam eum qui molestias voluptates ipsam illum quis accusamus qui illum illum illum possimus odit eius officiis perspiciatis quis soluta ad voluptatem odio sint nulla eum consectetur ut voluptatem veritatis fuga aut iusto repudiandae tempore qui qui sed molestias in aliquam dignissimos sint recusandae ab architecto voluptatem qui mollitia quae sed velit et corporis odit numquam nesciunt quis et et laudantium laboriosam quasi iure animi sit fugit temporibus dolorem alias reiciendis quibusdam nobis quaerat et quam aspernatur ullam laboriosam nesciunt nemo aut tempora consequatur rerum architecto quam maiores itaque necessitatibus molestias quisquam voluptas ut deleniti dolor reprehenderit aut voluptate aut voluptatem porro qui enim beatae minus aperiam qui accusamus cumque aspernatur est dignissimos illum alias quaerat ut earum qui expedita aut placeat hic ut repudiandae earum deleniti ut debitis autem tempora molestiae ea nostrum recusandae aliquid dignissimos qui est ut error laboriosam accusantium enim qui exercitationem illum commodi est quia sunt sit quia dolorem magni repudiandae ratione doloremque blanditiis reiciendis rerum qui fugit repudiandae debitis corrupti omnis maiores vel perspiciatis aliquid vero praesentium et qui ipsam non aliquam dolor incidunt quae eaque nostrum sit doloremque corrupti et nemo eius atque eos enim laborum ea et qui quia quia magni aut odit alias ullam quasi libero quisquam et aut architecto magni sit necessitatibus sint iusto omnis nisi atque voluptas animi natus laborum quia veritatis qui maxime itaque consequatur sapiente quas architecto rerum minus totam est non id quam repellat qui est animi ut omnis consectetur perferendis modi et vel et accusamus similique corporis ea sed est eveniet totam et sunt hic est consequatur repudiandae natus doloribus voluptas recusandae est nisi veniam esse itaque vitae molestiae perferendis fuga dicta nemo cum velit eos at maiores architecto et ducimus consequatur ut aliquid voluptatem nostrum quas exercitationem sed sed atque dolorem vel qui at est in esse et voluptate nemo necessitatibus sunt quia ut accusantium qui odit ex est et autem ex rerum quae sit quasi sit ducimus ipsa est dolores esse pariatur doloribus voluptates quam quaerat et quo accusantium rerum laudantium doloribus voluptatem nulla architecto quibusdam optio non earum a in ipsam adipisci ab aut laboriosam natus alias recusandae quia iure enim expedita in dolorem sint ab rem quia perferendis id soluta voluptatum est modi repudiandae incidunt exercitationem autem fugit itaque voluptatem doloribus aut reprehenderit unde repudiandae quasi aliquam ut impedit dolores in inventore odit deleniti voluptate perferendis quasi cum cumque eligendi corrupti ex quibusdam nulla ut modi modi omnis perferendis numquam qui sit dolorum ipsa sed vel fugiat molestiae architecto ut et voluptatem provident explicabo non autem blanditiis officiis qui occaecati qui in dolor illo voluptas dolore voluptatibus hic sapiente quis quaerat corrupti sequi dolorem illo odio dolore et iusto in sit in aliquam dicta esse sunt a eaque magnam quasi minus quasi a quidem est et excepturi et id nisi dolore ut et laboriosam quasi perspiciatis vitae expedita id quos reiciendis quod est qui numquam hic veniam incidunt qui dolorem aut iure nisi necessitatibus vel quia laudantium et cumque quas tempore totam ex suscipit sunt nulla eos at iusto saepe amet ad unde autem facere ullam nulla sed quidem tempora similique pariatur error ad est ullam voluptatum repudiandae quis aut aut dolorem odit commodi sequi culpa inventore doloribus voluptas molestiae sed aliquam molestias atque nulla animi modi eum ipsam magnam eligendi animi ducimus maxime eius totam et ex eius quis unde corrupti neque sequi eum quia impedit consequatur magni consequatur dolor officia quis praesentium quia veniam earum porro dolores quis quae fuga amet sit enim tenetur vero aut pariatur repellat voluptas consequatur perferendis eum odit vel adipisci explicabo dicta sit enim perferendis minus velit necessitatibus magni deserunt perspiciatis aut quae amet cumque voluptatibus earum doloremque neque esse maiores sed voluptates id magnam ipsa incidunt repellat officiis dolorem nulla mollitia et placeat perspiciatis sed enim harum quis ut nisi facilis architecto nobis et natus fuga dignissimos dignissimos adipisci quisquam molestias ex placeat sint magnam non autem laborum autem nihil quia est architecto tempora earum odit quos hic repellat beatae repudiandae suscipit numquam sapiente maxime laboriosam quia numquam et ea fugit numquam voluptatum sit dicta eum omnis recusandae nobis esse maiores architecto magnam rerum alias et et excepturi cupiditate autem et aut aspernatur reiciendis excepturi iusto sit natus dolores voluptatibus id numquam dolorum provident minima eligendi eum fuga nulla non enim iusto ullam id eum ratione dolores saepe beatae atque voluptatum quis earum perferendis aut accusantium omnis est quibusdam ipsum est voluptas provident.', 1, '2023-06-13 10:57:18', '2023-06-13 10:57:18'),
            ($company_id, 'terms_conditions', 'Terms & Conditions', 'Deleniti magnam dignissimos similique a dolorem magni culpa vero voluptatem quia nostrum officia itaque velit reiciendis corporis quia officia labore odit maiores dolor aspernatur eum beatae alias sed et tempore dignissimos nesciunt iusto iste facere tenetur quod adipisci natus sint cumque quia cupiditate vitae rerum velit rerum quibusdam consequatur sed sequi aut qui incidunt qui repudiandae mollitia tenetur fuga inventore assumenda qui vitae molestiae consequatur aut et libero similique quidem eligendi vero mollitia dolores quos aut dolores molestias repellat quod tenetur voluptas impedit illo tempora voluptatem enim nulla voluptate qui est quo temporibus aperiam blanditiis commodi et et ut id quisquam ut suscipit cumque atque voluptas veritatis aut eligendi atque ut sunt tempora consequatur et soluta maxime nesciunt molestias est quam atque doloremque in quia aut est quasi qui minima modi explicabo sunt sit ipsa sit ipsum ipsum et quos beatae molestiae voluptas aliquid distinctio aut earum laborum libero omnis dolor voluptatem nobis aut ut ullam voluptatem labore magnam veniam voluptatum blanditiis eum unde laudantium atque aperiam a perspiciatis excepturi aperiam in quo ea ut sequi iste cumque illo iste natus rerum velit labore eum odit non eum voluptate voluptas expedita cupiditate a dolorem nulla rerum facilis illum officiis repellendus sed quis officiis rerum inventore molestias voluptatem expedita quia enim quo neque labore architecto quos vel in omnis saepe optio et et et nobis odio id suscipit vero nisi maxime ab magni est quos voluptatem adipisci illum quaerat delectus explicabo est laboriosam pariatur qui deleniti error aut aliquid et molestias animi facilis et perspiciatis ut ab incidunt incidunt maxime vel deleniti impedit voluptas labore et delectus id ipsam deleniti maiores excepturi esse aut iure ipsam vero officia fuga repellat molestias dolorem molestiae laboriosam repellendus nemo qui pariatur libero nisi recusandae aspernatur neque dolorum voluptatem magni quo quia sequi distinctio itaque cupiditate reiciendis ea quisquam nam ut vero ut non velit eveniet fuga voluptatibus molestias velit tenetur eos ex quam voluptatibus officiis dolorem non sit dolorem temporibus accusamus magni corrupti quia a saepe qui nam rerum culpa dicta quia et veniam cupiditate ut maiores aliquid eaque odio sint quia tempore ut nemo doloremque omnis expedita qui molestias deserunt repudiandae esse beatae et est eum praesentium dolor omnis cum laboriosam ipsa sit eos id sint minima alias molestiae sint voluptate exercitationem excepturi molestiae accusamus quia minima enim sunt nulla accusantium dolor voluptas nostrum et velit veritatis sunt cupiditate possimus quibusdam itaque repellendus eos officiis ipsum iusto qui cupiditate repellendus quod unde accusamus voluptatem sunt soluta eius molestiae neque ipsa molestiae dicta dolor placeat non sunt quidem molestiae aperiam sunt suscipit placeat ut sapiente molestiae suscipit velit aliquam dolores dolorem ex beatae ut aliquid quae repudiandae consequatur laudantium minima ut voluptatem dolorum iusto ea explicabo suscipit et at voluptatem autem mollitia fugiat modi sequi eum reiciendis aut quam suscipit ut aut cupiditate non non dolores dolorem tempore ut dolorem ex fugit est fugiat nesciunt fugit corrupti consequatur ut sit aliquid expedita illum quis quos necessitatibus voluptatem hic dolorum autem aut laborum quam fugiat veritatis mollitia voluptatem culpa ut consequuntur perferendis rerum sit nihil rerum voluptatem voluptatem sit autem hic omnis totam inventore incidunt laborum doloribus cumque esse consequuntur sed tempora vel dolor quisquam qui quaerat voluptas architecto sunt saepe at assumenda quisquam facere et dolorem accusamus quia aliquid dignissimos quis adipisci veniam nemo aut aperiam eum qui molestias voluptates ipsam illum quis accusamus qui illum illum illum possimus odit eius officiis perspiciatis quis soluta ad voluptatem odio sint nulla eum consectetur ut voluptatem veritatis fuga aut iusto repudiandae tempore qui qui sed molestias in aliquam dignissimos sint recusandae ab architecto voluptatem qui mollitia quae sed velit et corporis odit numquam nesciunt quis et et laudantium laboriosam quasi iure animi sit fugit temporibus dolorem alias reiciendis quibusdam nobis quaerat et quam aspernatur ullam laboriosam nesciunt nemo aut tempora consequatur rerum architecto quam maiores itaque necessitatibus molestias quisquam voluptas ut deleniti dolor reprehenderit aut voluptate aut voluptatem porro qui enim beatae minus aperiam qui accusamus cumque aspernatur est dignissimos illum alias quaerat ut earum qui expedita aut placeat hic ut repudiandae earum deleniti ut debitis autem tempora molestiae ea nostrum recusandae aliquid dignissimos qui est ut error laboriosam accusantium enim qui exercitationem illum commodi est quia sunt sit quia dolorem magni repudiandae ratione doloremque blanditiis reiciendis rerum qui fugit repudiandae debitis corrupti omnis maiores vel perspiciatis aliquid vero praesentium et qui ipsam non aliquam dolor incidunt quae eaque nostrum sit doloremque corrupti et nemo eius atque eos enim laborum ea et qui quia quia magni aut odit alias ullam quasi libero quisquam et aut architecto magni sit necessitatibus sint iusto omnis nisi atque voluptas animi natus laborum quia veritatis qui maxime itaque consequatur sapiente quas architecto rerum minus totam est non id quam repellat qui est animi ut omnis consectetur perferendis modi et vel et accusamus similique corporis ea sed est eveniet totam et sunt hic est consequatur repudiandae natus doloribus voluptas recusandae est nisi veniam esse itaque vitae molestiae perferendis fuga dicta nemo cum velit eos at maiores architecto et ducimus consequatur ut aliquid voluptatem nostrum quas exercitationem sed sed atque dolorem vel qui at est in esse et voluptate nemo necessitatibus sunt quia ut accusantium qui odit ex est et autem ex rerum quae sit quasi sit ducimus ipsa est dolores esse pariatur doloribus voluptates quam quaerat et quo accusantium rerum laudantium doloribus voluptatem nulla architecto quibusdam optio non earum a in ipsam adipisci ab aut laboriosam natus alias recusandae quia iure enim expedita in dolorem sint ab rem quia perferendis id soluta voluptatum est modi repudiandae incidunt exercitationem autem fugit itaque voluptatem doloribus aut reprehenderit unde repudiandae quasi aliquam ut impedit dolores in inventore odit deleniti voluptate perferendis quasi cum cumque eligendi corrupti ex quibusdam nulla ut modi modi omnis perferendis numquam qui sit dolorum ipsa sed vel fugiat molestiae architecto ut et voluptatem provident explicabo non autem blanditiis officiis qui occaecati qui in dolor illo voluptas dolore voluptatibus hic sapiente quis quaerat corrupti sequi dolorem illo odio dolore et iusto in sit in aliquam dicta esse sunt a eaque magnam quasi minus quasi a quidem est et excepturi et id nisi dolore ut et laboriosam quasi perspiciatis vitae expedita id quos reiciendis quod est qui numquam hic veniam incidunt qui dolorem aut iure nisi necessitatibus vel quia laudantium et cumque quas tempore totam ex suscipit sunt nulla eos at iusto saepe amet ad unde autem facere ullam nulla sed quidem tempora similique pariatur error ad est ullam voluptatum repudiandae quis aut aut dolorem odit commodi sequi culpa inventore doloribus voluptas molestiae sed aliquam molestias atque nulla animi modi eum ipsam magnam eligendi animi ducimus maxime eius totam et ex eius quis unde corrupti neque sequi eum quia impedit consequatur magni consequatur dolor officia quis praesentium quia veniam earum porro dolores quis quae fuga amet sit enim tenetur vero aut pariatur repellat voluptas consequatur perferendis eum odit vel adipisci explicabo dicta sit enim perferendis minus velit necessitatibus magni deserunt perspiciatis aut quae amet cumque voluptatibus earum doloremque neque esse maiores sed voluptates id magnam ipsa incidunt repellat officiis dolorem nulla mollitia et placeat perspiciatis sed enim harum quis ut nisi facilis architecto nobis et natus fuga dignissimos dignissimos adipisci quisquam molestias ex placeat sint magnam non autem laborum autem nihil quia est architecto tempora earum odit quos hic repellat beatae repudiandae suscipit numquam sapiente maxime laboriosam quia numquam et ea fugit numquam voluptatum sit dicta eum omnis recusandae nobis esse maiores architecto magnam rerum alias et et excepturi cupiditate autem et aut aspernatur reiciendis excepturi iusto sit natus dolores voluptatibus id numquam dolorum provident minima eligendi eum fuga nulla non enim iusto ullam id eum ratione dolores saepe beatae atque voluptatum quis earum perferendis aut accusantium omnis est quibusdam ipsum est voluptas provident.', 1, '2023-06-13 10:57:18', '2023-06-13 10:57:18'),
            ($company_id, 'about_us', 'About Us', 'Deleniti magnam dignissimos similique a dolorem magni culpa vero voluptatem quia nostrum officia itaque velit reiciendis corporis quia officia labore odit maiores dolor aspernatur eum beatae alias sed et tempore dignissimos nesciunt iusto iste facere tenetur quod adipisci natus sint cumque quia cupiditate vitae rerum velit rerum quibusdam consequatur sed sequi aut qui incidunt qui repudiandae mollitia tenetur fuga inventore assumenda qui vitae molestiae consequatur aut et libero similique quidem eligendi vero mollitia dolores quos aut dolores molestias repellat quod tenetur voluptas impedit illo tempora voluptatem enim nulla voluptate qui est quo temporibus aperiam blanditiis commodi et et ut id quisquam ut suscipit cumque atque voluptas veritatis aut eligendi atque ut sunt tempora consequatur et soluta maxime nesciunt molestias est quam atque doloremque in quia aut est quasi qui minima modi explicabo sunt sit ipsa sit ipsum ipsum et quos beatae molestiae voluptas aliquid distinctio aut earum laborum libero omnis dolor voluptatem nobis aut ut ullam voluptatem labore magnam veniam voluptatum blanditiis eum unde laudantium atque aperiam a perspiciatis excepturi aperiam in quo ea ut sequi iste cumque illo iste natus rerum velit labore eum odit non eum voluptate voluptas expedita cupiditate a dolorem nulla rerum facilis illum officiis repellendus sed quis officiis rerum inventore molestias voluptatem expedita quia enim quo neque labore architecto quos vel in omnis saepe optio et et et nobis odio id suscipit vero nisi maxime ab magni est quos voluptatem adipisci illum quaerat delectus explicabo est laboriosam pariatur qui deleniti error aut aliquid et molestias animi facilis et perspiciatis ut ab incidunt incidunt maxime vel deleniti impedit voluptas labore et delectus id ipsam deleniti maiores excepturi esse aut iure ipsam vero officia fuga repellat molestias dolorem molestiae laboriosam repellendus nemo qui pariatur libero nisi recusandae aspernatur neque dolorum voluptatem magni quo quia sequi distinctio itaque cupiditate reiciendis ea quisquam nam ut vero ut non velit eveniet fuga voluptatibus molestias velit tenetur eos ex quam voluptatibus officiis dolorem non sit dolorem temporibus accusamus magni corrupti quia a saepe qui nam rerum culpa dicta quia et veniam cupiditate ut maiores aliquid eaque odio sint quia tempore ut nemo doloremque omnis expedita qui molestias deserunt repudiandae esse beatae et est eum praesentium dolor omnis cum laboriosam ipsa sit eos id sint minima alias molestiae sint voluptate exercitationem excepturi molestiae accusamus quia minima enim sunt nulla accusantium dolor voluptas nostrum et velit veritatis sunt cupiditate possimus quibusdam itaque repellendus eos officiis ipsum iusto qui cupiditate repellendus quod unde accusamus voluptatem sunt soluta eius molestiae neque ipsa molestiae dicta dolor placeat non sunt quidem molestiae aperiam sunt suscipit placeat ut sapiente molestiae suscipit velit aliquam dolores dolorem ex beatae ut aliquid quae repudiandae consequatur laudantium minima ut voluptatem dolorum iusto ea explicabo suscipit et at voluptatem autem mollitia fugiat modi sequi eum reiciendis aut quam suscipit ut aut cupiditate non non dolores dolorem tempore ut dolorem ex fugit est fugiat nesciunt fugit corrupti consequatur ut sit aliquid expedita illum quis quos necessitatibus voluptatem hic dolorum autem aut laborum quam fugiat veritatis mollitia voluptatem culpa ut consequuntur perferendis rerum sit nihil rerum voluptatem voluptatem sit autem hic omnis totam inventore incidunt laborum doloribus cumque esse consequuntur sed tempora vel dolor quisquam qui quaerat voluptas architecto sunt saepe at assumenda quisquam facere et dolorem accusamus quia aliquid dignissimos quis adipisci veniam nemo aut aperiam eum qui molestias voluptates ipsam illum quis accusamus qui illum illum illum possimus odit eius officiis perspiciatis quis soluta ad voluptatem odio sint nulla eum consectetur ut voluptatem veritatis fuga aut iusto repudiandae tempore qui qui sed molestias in aliquam dignissimos sint recusandae ab architecto voluptatem qui mollitia quae sed velit et corporis odit numquam nesciunt quis et et laudantium laboriosam quasi iure animi sit fugit temporibus dolorem alias reiciendis quibusdam nobis quaerat et quam aspernatur ullam laboriosam nesciunt nemo aut tempora consequatur rerum architecto quam maiores itaque necessitatibus molestias quisquam voluptas ut deleniti dolor reprehenderit aut voluptate aut voluptatem porro qui enim beatae minus aperiam qui accusamus cumque aspernatur est dignissimos illum alias quaerat ut earum qui expedita aut placeat hic ut repudiandae earum deleniti ut debitis autem tempora molestiae ea nostrum recusandae aliquid dignissimos qui est ut error laboriosam accusantium enim qui exercitationem illum commodi est quia sunt sit quia dolorem magni repudiandae ratione doloremque blanditiis reiciendis rerum qui fugit repudiandae debitis corrupti omnis maiores vel perspiciatis aliquid vero praesentium et qui ipsam non aliquam dolor incidunt quae eaque nostrum sit doloremque corrupti et nemo eius atque eos enim laborum ea et qui quia quia magni aut odit alias ullam quasi libero quisquam et aut architecto magni sit necessitatibus sint iusto omnis nisi atque voluptas animi natus laborum quia veritatis qui maxime itaque consequatur sapiente quas architecto rerum minus totam est non id quam repellat qui est animi ut omnis consectetur perferendis modi et vel et accusamus similique corporis ea sed est eveniet totam et sunt hic est consequatur repudiandae natus doloribus voluptas recusandae est nisi veniam esse itaque vitae molestiae perferendis fuga dicta nemo cum velit eos at maiores architecto et ducimus consequatur ut aliquid voluptatem nostrum quas exercitationem sed sed atque dolorem vel qui at est in esse et voluptate nemo necessitatibus sunt quia ut accusantium qui odit ex est et autem ex rerum quae sit quasi sit ducimus ipsa est dolores esse pariatur doloribus voluptates quam quaerat et quo accusantium rerum laudantium doloribus voluptatem nulla architecto quibusdam optio non earum a in ipsam adipisci ab aut laboriosam natus alias recusandae quia iure enim expedita in dolorem sint ab rem quia perferendis id soluta voluptatum est modi repudiandae incidunt exercitationem autem fugit itaque voluptatem doloribus aut reprehenderit unde repudiandae quasi aliquam ut impedit dolores in inventore odit deleniti voluptate perferendis quasi cum cumque eligendi corrupti ex quibusdam nulla ut modi modi omnis perferendis numquam qui sit dolorum ipsa sed vel fugiat molestiae architecto ut et voluptatem provident explicabo non autem blanditiis officiis qui occaecati qui in dolor illo voluptas dolore voluptatibus hic sapiente quis quaerat corrupti sequi dolorem illo odio dolore et iusto in sit in aliquam dicta esse sunt a eaque magnam quasi minus quasi a quidem est et excepturi et id nisi dolore ut et laboriosam quasi perspiciatis vitae expedita id quos reiciendis quod est qui numquam hic veniam incidunt qui dolorem aut iure nisi necessitatibus vel quia laudantium et cumque quas tempore totam ex suscipit sunt nulla eos at iusto saepe amet ad unde autem facere ullam nulla sed quidem tempora similique pariatur error ad est ullam voluptatum repudiandae quis aut aut dolorem odit commodi sequi culpa inventore doloribus voluptas molestiae sed aliquam molestias atque nulla animi modi eum ipsam magnam eligendi animi ducimus maxime eius totam et ex eius quis unde corrupti neque sequi eum quia impedit consequatur magni consequatur dolor officia quis praesentium quia veniam earum porro dolores quis quae fuga amet sit enim tenetur vero aut pariatur repellat voluptas consequatur perferendis eum odit vel adipisci explicabo dicta sit enim perferendis minus velit necessitatibus magni deserunt perspiciatis aut quae amet cumque voluptatibus earum doloremque neque esse maiores sed voluptates id magnam ipsa incidunt repellat officiis dolorem nulla mollitia et placeat perspiciatis sed enim harum quis ut nisi facilis architecto nobis et natus fuga dignissimos dignissimos adipisci quisquam molestias ex placeat sint magnam non autem laborum autem nihil quia est architecto tempora earum odit quos hic repellat beatae repudiandae suscipit numquam sapiente maxime laboriosam quia numquam et ea fugit numquam voluptatum sit dicta eum omnis recusandae nobis esse maiores architecto magnam rerum alias et et excepturi cupiditate autem et aut aspernatur reiciendis excepturi iusto sit natus dolores voluptatibus id numquam dolorum provident minima eligendi eum fuga nulla non enim iusto ullam id eum ratione dolores saepe beatae atque voluptatum quis earum perferendis aut accusantium omnis est quibusdam ipsum est voluptas provident.', 1, '2023-06-13 10:57:18', '2023-06-13 10:57:18'),
            ($company_id, 'faq', 'Have Question', 'Take a look at the most commonly asked questions.', 1, '2023-06-13 10:57:18', '2023-06-13 10:57:18'),
            ($company_id, 'contact', 'Contact Us', 'Take a look at the most commonly asked questions.', 1, '2023-06-13 10:57:18', '2023-06-13 10:57:18')");
        //End Pages

        //Start Sections
        DB::statement("INSERT INTO `sections` ( `company_id`, `type`, `key`, `value`, `created_at`, `updated_at`) VALUES
            ($company_id, 1, 'title_1','WE PROVIDE', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 1, 'title_2','HASSLE FREE', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 1, 'title_3','FASTEST DELIVERY', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 1, 'sub_title','We Committed to delivery - Make easy Efficient and quality delivery.', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 1, 'banner',null, '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 2, 'branch_icon','fa fa-warehouse', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 2, 'branch_count','7520', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 2, 'branch_title','Branches', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 2, 'parcel_icon','fa fa-gifts', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 2, 'parcel_count','50000000', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 2, 'parcel_title','Parcel Delivered', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 2, 'merchant_icon','fa fa-users', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 2, 'merchant_count','400000', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 2, 'merchant_title','Happy Merchant', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 2, 'reviews_icon','fa fa-thumbs-up', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 2, 'reviews_count','700', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 2, 'reviews_title','Positive Reviews', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 3, 'about_us', 'Fastest platform with all courier service features. Help you start, run and grow your courier service.', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 4, 'subscribe_title', 'Subscribe Us', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 4, 'subscribe_description','Get business news , tip and solutions to your problems our experts.', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 5, 'playstore_icon','fa-brands fa-google-play', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 5, 'playstore_link','https://drive.google.com/drive/folders/1jLe_s4F-HDSjI7dHPsen7vRUw2wv9SMi', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 5, 'ios_icon','fa-brands fa-app-store-ios', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 5, 'ios_link','https://drive.google.com/drive/folders/1jLe_s4F-HDSjI7dHPsen7vRUw2wv9SMi', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            ($company_id, 6, 'map_link','https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d542.6581052086841!2d90.3516149889463!3d23.798889773393963!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755c0e8a725cb8b%3A0x5a69b65edf9c7cf4!2sWemax%20IT!5e0!3m2!1sen!2sbd!4v1687082326781!5m2!1sen!2sbd', '2023-01-27 17:30:40', '2023-01-27 17:30:40')");
        //End Sections
    }
}
