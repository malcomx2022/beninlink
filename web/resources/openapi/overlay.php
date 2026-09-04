<?php

/*
|--------------------------------------------------------------------------
| Overlay OpenAPI — ce que le routeur ne dit pas (chantier 7)
|--------------------------------------------------------------------------
| Clé d'opération : « METHOD chemin » relatif au préfixe /api/v10, exactement
| tel qu'enregistré dans routes/api.php. Une clé sans route est un ORPHELIN
| (refusé par le test) ; une route sans clé reçoit un résumé par défaut et
| est listée par `php artisan openapi:generate`.
|
| Formes de réponse relevées sur les contrôleurs et les Resources réels
| (app/Http/Resources/v10) et sur mobile/src/api/types.ts, qui en est le
| miroir typé. Les montants sont des `Amount` : entier XOF, parfois encore
| chaîne décimale selon l'endpoint.
*/

$ref = fn (string $name) => ['$ref' => "#/components/schemas/{$name}"];
$arr = fn (string $name) => ['type' => 'array', 'items' => ['$ref' => "#/components/schemas/{$name}"]];
$str = fn (string $desc = '', bool $nullable = false) => array_filter(['type' => 'string', 'description' => $desc ?: null, 'nullable' => $nullable ?: null]);
$int = fn (string $desc = '', bool $nullable = false) => array_filter(['type' => 'integer', 'description' => $desc ?: null, 'nullable' => $nullable ?: null]);
$bool = fn (string $desc = '') => array_filter(['type' => 'boolean', 'description' => $desc ?: null]);
$amount = fn () => ['$ref' => '#/components/schemas/Amount'];
$obj = fn (array $props, array $required = []) => array_filter(['type' => 'object', 'properties' => $props, 'required' => $required ?: null]);

/** Réponse 200 enveloppée : `{success, message, data: <schéma>}`. */
$ok = fn (array $data, string $desc = 'Succès') => ['200' => [
    'description' => $desc,
    'content' => ['application/json' => ['schema' => [
        'allOf' => [['$ref' => '#/components/schemas/Envelope'], ['type' => 'object', 'properties' => ['data' => $data]]],
    ]]],
]];
/** Réponse 200 **nue**, sans enveloppe (quelques endpoints du socle). */
$bare = fn (array $schema, string $desc = 'Succès (réponse nue, sans enveloppe)') => ['200' => [
    'description' => $desc,
    'content' => ['application/json' => ['schema' => $schema]],
]];
$body = fn (array $props, array $required = []) => ['required' => true, 'content' => ['application/json' => ['schema' => $obj($props, $required)]]];
$query = fn (string $name, string $desc, string $type = 'string') => ['name' => $name, 'in' => 'query', 'required' => false, 'description' => $desc, 'schema' => ['type' => $type]];
$path = fn (string $name, string $desc, string $type = 'integer') => ['name' => $name, 'in' => 'path', 'required' => true, 'description' => $desc, 'schema' => ['type' => $type]];
$page = $query('page', 'Numéro de page (10 ou 20 éléments par page selon l\'endpoint ; une page incomplète est la dernière)', 'integer');
$empty = $ok(['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 0, 'description' => 'Tableau vide']);

$schemas = [
    'Envelope' => [
        'type' => 'object',
        'description' => 'Enveloppe d\'ApiReturnFormatTrait. `data` change de forme selon l\'endpoint.',
        'properties' => ['success' => $bool(), 'message' => $str('Libellé déjà traduit (locale du serveur, FR)'), 'data' => ['description' => 'Charge utile, selon l\'endpoint']],
        'required' => ['success', 'message'],
    ],
    'ValidationError' => [
        'type' => 'object',
        'description' => 'Erreur 422 : `data.message` porte les erreurs par champ (forme Laravel).',
        'properties' => ['success' => ['type' => 'boolean', 'example' => false], 'message' => $str(), 'data' => $obj(['message' => ['type' => 'object', 'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']]]])],
    ],
    'Amount' => [
        'description' => 'Montant en XOF (FCFA), sans décimales. Entier JSON depuis le 2026-08-16 ; certains attributs sérialisés tels quels arrivent encore en chaîne décimale.',
        'oneOf' => [['type' => 'integer'], ['type' => 'string', 'example' => '1234.00']],
        'nullable' => true,
    ],
    'Hub' => $obj(['id' => $int(), 'name' => $str(), 'phone' => $str('', true), 'address' => $str('', true)]),
    'Merchant' => $obj([
        'id' => $int(), 'business_name' => $str(), 'merchant_unique_id' => $str('Identifiant marchand, aussi identifiant de connexion'),
        'ifu' => $str('IFU, 13 chiffres', true), 'rccm' => $str('', true), 'cnss' => $str('', true),
        'current_balance' => $amount(), 'opening_balance' => $amount(),
        'wallet_balance' => $amount(),
        'vat' => $amount(), 'address' => $str('', true), 'return_charges' => $amount(),
        'cod_charges' => ['type' => 'object', 'nullable' => true, 'properties' => ['inside_city' => $str(), 'sub_city' => $str(), 'outside_city' => $str()]],
    ]),
    'AuthUser' => $obj([
        'id' => $int(), 'name' => $str(), 'email' => $str('', true), 'phone' => $str('', true),
        'user_type' => ['description' => '1 admin · 2 marchand · 3 livreur · 4 responsable · 5 hub · 6 super-admin (App\\Enums\\UserType)', 'oneOf' => [['type' => 'integer'], ['type' => 'string']]],
        'address' => $str('', true), 'image' => $str('', true), 'hub' => ['allOf' => [$ref('Hub')], 'nullable' => true], 'merchant' => ['allOf' => [$ref('Merchant')], 'nullable' => true],
    ]),
    'SignInResult' => $obj(['token' => $str('Jeton Sanctum à envoyer en `Authorization: Bearer`'), 'user' => $ref('AuthUser')], ['token', 'user']),
    'DeliverymanUser' => $obj(['id' => $int(), 'name' => $str(), 'email' => $str('', true), 'phone' => $str('', true), 'user_type' => $int(), 'deliveryman' => ['type' => 'object', 'nullable' => true], 'hub' => ['type' => 'object', 'nullable' => true], 'address' => $str('', true), 'salary' => $amount(), 'status' => $int(), 'statusName' => $str(), 'image' => $str('', true)]),
    'DashboardData' => $obj([
        't_parcel' => $int(), 't_delivered' => $int(), 't_return' => $int(), 't_sale' => $amount(), 't_delivery_fee' => $amount(),
        't_balance_proc' => $amount(), 't_balance_paid' => $amount(), 't_request' => $int(), 't_shop' => $int(), 't_fraud' => $int(),
        't_cash_collection' => $amount(), 't_vat_amount' => $amount(), 'merchant' => ['allOf' => [$ref('Merchant')], 'nullable' => true],
    ]),
    'BalanceDetails' => $obj([
        'amount_delivered' => $amount(), 'payable_delivery_charge' => $amount(), 'sub_total' => $amount(), 'vat_amount' => $amount(), 'cod_charge' => $amount(),
        'available_balance' => ['allOf' => [$amount()], 'description' => 'Net à reverser = (sous-total − TVA) − frais COD'],
        'clearable_parcels' => $int(),
    ]),
    'Parcel' => $obj([
        'id' => $int(), 'tracking_id' => $str(), 'merchant_id' => $int(), 'merchant_name' => $str('', true),
        'customer_name' => $str(), 'customer_phone' => $str('', true), 'customer_address' => $str('', true), 'invoice_no' => $str('', true),
        'weight' => $str('Déjà formaté, ex. « 1 KG »', true),
        'total_delivery_amount' => ['allOf' => [$amount()], 'description' => 'Sous-total des frais **hors TVA**'],
        'cod_amount' => $amount(), 'vat_amount' => $amount(), 'current_payable' => $amount(), 'cash_collection' => $amount(),
        'delivery_type_id' => $int(), 'deliveryType' => $str('Libellé traduit', true),
        'status' => $int('App\\Enums\\ParcelStatus (33 constantes)'), 'statusName' => $str('Libellé traduit', true),
        'pickup_date' => $str('', true), 'delivery_date' => $str('', true), 'created_at' => $str('Déjà mise en forme (« 17 Aug 2026, 09:29 PM »)', true),
        'parcel_date' => $str('', true), 'parcel_time' => $str('', true),
    ]),
    'ParcelEvent' => $obj(['id' => $int(), 'parcel_status' => $str(), 'parcel_status_name' => $str(), 'description' => $str('', true), 'hub_name' => $str(), 'delivery_man' => $str(), 'delivery_phone' => $str(), 'date' => $str(), 'time_date' => $str()]),
    'ParcelStatusOption' => $obj(['id' => $int(), 'status' => $str('Libellé traduit')]),
    'ParcelFormData' => $obj([
        'shops' => $arr('Shop'),
        'deliveryCategories' => ['type' => 'object', 'description' => 'Objet indexé par identifiant, pas un tableau', 'additionalProperties' => $obj(['id' => $int(), 'title' => $str()])],
        'deliveryTypes' => ['type' => 'array', 'description' => 'Interrupteurs de configuration `{key, value}` ; les identifiants à poster viennent d\'App\\Enums\\DeliveryType', 'items' => $obj(['key' => $str(), 'value' => $str()])],
        'packagings' => ['type' => 'array', 'items' => $obj(['id' => $int(), 'name' => $str(), 'price' => $amount()])],
        'codCharges' => ['type' => 'array', 'items' => $obj(['name' => $str(), 'charge' => $str()])],
        'fragileLiquid' => $amount(),
    ]),
    'ParcelQuote' => $obj([
        'delivery_charge' => $amount(), 'cod_charge' => ['allOf' => [$amount()], 'description' => 'Taux en pourcentage'], 'cod_amount' => $amount(),
        'vat' => ['allOf' => [$amount()], 'description' => 'Taux en pourcentage'], 'vat_amount' => $amount(), 'packaging_amount' => $amount(), 'liquid_fragile_amount' => $amount(),
        'total_delivery_amount' => ['allOf' => [$amount()], 'description' => 'Sous-total hors TVA'], 'total_payable_charges' => ['allOf' => [$amount()], 'description' => 'Sous-total + TVA'],
        'current_payable' => ['allOf' => [$amount()], 'description' => 'Net à reverser'],
        'customs' => ['allOf' => [$ref('CustomsRule')], 'nullable' => true, 'description' => 'Règle douanière applicable, `null` pour un colis domestique'],
    ]),
    'Shop' => $obj(['id' => $int(), 'merchant_id' => $int(), 'name' => $str(), 'contact_no' => $str(), 'address' => $str('', true), 'merchant_lat' => $str(), 'merchant_long' => $str(), 'default_shop' => $str('« 0 » ou « 1 »'), 'status' => $int(), 'statusName' => $str(), 'created_at' => $str(), 'updated_at' => $str()]),
    'Invoice' => $obj(['id' => $int(), 'invoice_id' => $str('Numéro `PREFIXE-AAAA-NNNNNN` depuis le chantier 4'), 'status' => $str('Libellé traduit'), 'amount' => ['allOf' => [$amount()], 'description' => 'Net à reverser, retours déduits'], 'invoice_date' => $str('« 14 Jul 2026 »')]),
    'InvoiceDetails' => $obj([
        'id' => $int(), 'invoice_id' => $str(), 'status' => $str(), 'total_deliverd_amount' => ['allOf' => [$amount()], 'description' => 'Encaissé COD (faute de frappe d\'origine conservée)'],
        'delivery_charge' => $amount(), 'cod_amount' => $amount(), 'total_return_fee' => $amount(), 'payable_amount' => $amount(), 'invoice_date' => $str(),
        'merchant_name' => $str('', true), 'merchant_phone' => $str('', true), 'merchant_address' => $str('', true), 'total_parcels' => $int(),
        'parcels' => ['nullable' => true, 'description' => 'Toujours `null` (accesseur absent du modèle) — ne pas afficher'],
    ]),
    'PdfLink' => $obj(['url' => $str('URL signée, valable 15 minutes, à ouvrir dans un navigateur'), 'expires_at' => $str('ISO 8601')], ['url', 'expires_at']),
    'DeliveryRate' => $obj(['id' => $int(), 'category' => $str('', true), 'weight' => $str('Tranche comparée à l\'identique par le calculateur, pas un plafond', true), 'same_day' => $str(), 'next_day' => $str(), 'sub_city' => $str(), 'outside_city' => $str(), 'status' => $str(), 'statusName' => $str('', true)]),
    'CodCharge' => $obj(['name' => $str('Libellé traduit de la zone'), 'charge' => $str('Taux en pourcentage')]),
    'CustomsRule' => $obj(['level' => $int('1 info · 2 avertissement · 3 bloquant'), 'level_name' => $str(), 'blocking' => $bool('La création sera refusée tant que le document manque'), 'required_document' => $str('', true), 'message' => $str()]),
    'CustomsReference' => $obj(['countries' => ['type' => 'array', 'items' => $obj(['code' => $str('ISO 3166-1 alpha-2'), 'name' => $str()])], 'categories' => ['type' => 'array', 'items' => $obj(['slug' => $str(), 'name' => $str()])]]),
    'CustomsAlert' => $obj(['id' => $int(), 'parcel_id' => $int('', true), 'tracking_id' => $str('', true), 'country_code' => $str(), 'country_name' => $str(), 'goods_category' => $str(), 'category_name' => $str(), 'level' => $int(), 'level_name' => $str(), 'required_document' => $str('', true), 'message' => $str(), 'status' => $int('1 en cours · 2 traitée'), 'status_name' => $str(), 'created_at' => $str('', true)]),
    'PaymentAccount' => $obj(['id' => $int(), 'merchant_id' => $int(), 'payment_method' => ['type' => 'string', 'enum' => ['bank', 'mobile', 'cash']], 'paymentMethodName' => $str('', true), 'bank_name' => $str('', true), 'holder_name' => $str('', true), 'account_no' => $str('', true), 'branch_name' => $str('', true), 'routing_no' => $str('', true), 'mobile_company' => $str('« MTN MoMo » ou « Moov Money »', true), 'mobile_no' => $str('', true), 'account_type' => $str('', true), 'status' => $int(), 'statusName' => $str('', true)]),
    'PaymentRequest' => $obj(['id' => $int(), 'transaction_id' => $str(), 'description' => $str('', true), 'amount' => $amount(), 'currency' => $str(), 'payment_method' => $str('', true), 'paymentMethodName' => $str('', true), 'bank_name' => $str('', true), 'holder_name' => $str('', true), 'account_no' => $str('', true), 'mobile_company' => $str('', true), 'mobile_no' => $str('', true), 'status' => $int('App\\Enums\\ApprovalStatus : 1 rejeté · 2 approuvé · 3 en attente · 4 traité'), 'statusName' => $str('', true), 'request_date' => $str('', true)]),
    'WalletEntry' => $obj(['id' => $int(), 'transaction_id' => $str(), 'source' => $str('', true), 'amount' => $int('Entier XOF'), 'type' => $int('1 crédit · 2 débit'), 'typeName' => $str(), 'payment_method' => $int(), 'paymentMethodName' => $str(), 'status' => $int('1 en attente · 2 approuvé · 3 rejeté'), 'statusName' => $str(), 'created_at' => $str('', true)]),
    'RechargeInitiation' => $obj(['reference' => $str('Référence BeninLink (`BL-…`)'), 'payment_url' => $str('Page de paiement FedaPay à ouvrir dans un navigateur ; l\'app ne voit jamais les clés')], ['reference', 'payment_url']),
    'RechargeStatus' => $obj(['reference' => $str(), 'status' => ['type' => 'string', 'enum' => ['pending', 'approved', 'declined', 'canceled']], 'amount' => $int(), 'wallet_balance' => $int('Solde après traitement : c\'est lui qui fait foi')]),
    'Notification' => $obj(['id' => $str('UUID'), 'kind' => ['type' => 'string', 'enum' => ['parcel_status', 'wallet_credit', 'invoice', 'customs', 'message', 'payout']], 'title' => $str(), 'body' => $str(), 'read' => $bool(), 'created_at' => $str('', true), 'created_at_iso' => $str('ISO 8601', true), 'parcel_id' => $int('', true), 'tracking_id' => $str('', true), 'amount' => $int('', true), 'reference' => $str('', true), 'status' => $int('', true), 'level' => $int('', true)]),
    'Fraud' => $obj(['id' => $int(), 'name' => $str('', true), 'phone' => $str(), 'description' => $str('', true), 'date' => $str()]),
    'Support' => $obj(['id' => $int(), 'subject' => $str(), 'userName' => $str('', true), 'userEmail' => $str('', true), 'userMobile' => $str('', true), 'department' => $str('', true), 'service' => $str('', true), 'priority' => $str('', true), 'description' => $str('', true), 'date' => $str()]),
    'NewsOffer' => $obj(['id' => $int(), 'title' => $str(), 'author' => $str(), 'description' => $str(), 'image' => $str(), 'status' => $int(), 'statusName' => $str(), 'date' => $str()]),
    'Statement' => $obj(['id' => $int(), 'note' => $str('', true), 'date' => $str(), 'amount' => $amount(), 'currency' => $str(), 'type' => $int(), 'typeName' => $str(), 'created_at' => $str(), 'updated_at' => $str()]),
    'Transaction' => $obj(['id' => $int(), 'merchant_id' => $int(), 'transaction_id' => $str(), 'merchantAccount' => ['type' => 'object', 'nullable' => true], 'amount' => $amount(), 'currency' => $str(), 'status' => $int(), 'statusName' => $str(), 'created_at' => $str(), 'updated_at' => $str()]),
    'IncomeExpense' => $obj(['id' => $int(), 'parcel_id' => $int('', true), 'note' => $str('', true), 'date' => $str(), 'amount' => $amount(), 'cash_collection' => $amount(), 'currency' => $str(), 'type' => $int(), 'typeName' => $str()]),
    'ParcelPaymentLog' => $obj(['id' => $int(), 'name' => $str(), 'type' => $str(), 'cash_collection' => $amount(), 'date' => $str(), 'note' => $str('', true)]),
    'StatusWiseParcel' => $obj(['id' => $int(), 'invoice' => $str('', true), 'tracking_id' => $str(), 'customer_name' => $str(), 'customer_phone' => $str('', true), 'cash_collection' => $amount(), 'charge' => $amount(), 'current_payable' => $amount(), 'status_name' => $str(), 'date' => $str()]),
];

$parcelInput = [
    'shop_id' => $int('Boutique d\'expédition (`shops/index`)'),
    'category_id' => $int('Catégorie de livraison (`parcel/create`)'),
    'delivery_type_id' => $int('1 même jour · 2 lendemain · 3 sous-ville · 4 hors-ville (App\\Enums\\DeliveryType)'),
    'customer_name' => $str(), 'customer_address' => $str(), 'customer_phone' => $str(),
    'cash_collection' => $int('Montant à encaisser (COD), entier XOF'),
    'selling_price' => $int('', true), 'invoice_no' => $str('', true), 'weight' => ['type' => 'number', 'nullable' => true],
    'packaging_id' => $int('', true), 'fragile_liquid' => $bool('Colis fragile ou liquide'), 'note' => $str('', true),
    'destination_country' => $str('ISO alpha-2 ; absent ou `BJ` = colis domestique. Sinon export : `customs_category` obligatoire', true),
    'customs_category' => $str('Slug de `customs/reference`', true),
];
$parcelRequired = ['shop_id', 'category_id', 'delivery_type_id', 'customer_name', 'customer_address', 'customer_phone'];

$operations = [
    // — Authentification -------------------------------------------------
    'POST register' => ['tag' => 'Authentification', 'summary' => 'Inscription d\'une PME marchande',
        'description' => 'Ne connecte pas : envoie un code par SMS et renvoie le numéro. Enchaîner sur `POST otp-verification`. IFU et RCCM sont exigés (chantier 2) ; la CNSS ne concerne que les employeurs.',
        'requestBody' => $body(['business_name' => $str(), 'full_name' => $str(), 'address' => $str(), 'mobile' => $str('11 à 14 chiffres avec indicatif, ex. 22997000000'), 'password' => $str('6 caractères minimum'), 'ifu' => $str('13 chiffres'), 'rccm' => $str('ex. RB/COT/24 B 1234'), 'cnss' => $str('', true), 'hub_id' => $int('', true)], ['business_name', 'full_name', 'address', 'mobile', 'password', 'ifu', 'rccm']),
        'responses' => $ok($obj(['mobile' => $str()]), 'Code SMS envoyé')],
    'POST signin' => ['tag' => 'Authentification', 'summary' => 'Connexion marchand',
        'description' => '`merchant_id` est l\'**identifiant marchand** (`users.unique_id`), pas le téléphone. 401 pour un compte qui n\'est pas marchand.',
        'requestBody' => $body(['merchant_id' => $str(), 'password' => $str()], ['merchant_id', 'password']),
        'responses' => $ok($ref('SignInResult')) + ['401' => ['description' => 'Identifiants invalides']]],
    'POST deliveryman/login' => ['tag' => 'Livreur', 'summary' => 'Connexion livreur',
        'requestBody' => $body(['driver_id' => $str('Identifiant livreur'), 'password' => $str()], ['driver_id', 'password']),
        'responses' => $ok($obj(['token' => $str(), 'user' => $ref('DeliverymanUser')])) + ['401' => ['description' => 'Identifiants invalides']]],
    'POST otp-verification' => ['tag' => 'Authentification', 'summary' => 'Vérifier le code SMS et ouvrir la session',
        'requestBody' => $body(['otp' => $str('5 chiffres')], ['otp']),
        'responses' => $ok($ref('SignInResult')) + ['401' => ['description' => 'Code invalide (l\'enveloppe porte `success: true`, se fier au statut HTTP)']]],
    'POST resend-otp' => ['tag' => 'Authentification', 'summary' => 'Renvoyer le code SMS', 'requestBody' => $body(['mobile' => $str()], ['mobile']), 'responses' => $ok($obj(['mobile' => $str()]))],
    'POST password/email' => ['tag' => 'Authentification', 'summary' => 'Demander un lien de réinitialisation', 'description' => 'Le lien envoyé par e-mail mène à la page web du backend ; le jeton qu\'il porte est accepté par `POST password/reset`.', 'requestBody' => $body(['email' => $str()], ['email']), 'responses' => $ok($obj(['message' => $str()]))],
    'POST password/reset' => ['tag' => 'Authentification', 'summary' => 'Définir un nouveau mot de passe à partir du jeton', 'requestBody' => $body(['token' => $str(), 'email' => $str(), 'password' => $str('8 caractères minimum'), 'password_confirmation' => $str()], ['token', 'email', 'password', 'password_confirmation']), 'responses' => $ok($obj(['message' => $str()]))],
    'GET refresh' => ['tag' => 'Authentification', 'summary' => 'Renouveler le jeton', 'description' => 'Révoque tous les jetons du compte et en émet un nouveau.', 'responses' => $ok($obj(['token' => $str()]))],
    'POST sign-out' => ['tag' => 'Authentification', 'summary' => 'Déconnexion (révocation du jeton)', 'responses' => $empty],
    'PUT update-password' => ['tag' => 'Profil', 'summary' => 'Changer le mot de passe', 'requestBody' => $body(['old_password' => $str(), 'new_password' => $str('6 caractères minimum'), 'confirm_password' => $str()], ['old_password', 'new_password', 'confirm_password']), 'responses' => $empty + ['422' => ['description' => 'Validation, ou ancien mot de passe incorrect']]],

    // — Référentiels -------------------------------------------------------
    'GET hub' => ['tag' => 'Référentiels', 'summary' => 'Agences (hubs)', 'responses' => $ok($obj(['hubs' => $arr('Hub')]))],
    'GET general-settings' => ['tag' => 'Référentiels', 'summary' => 'Paramètres généraux de l\'installation', 'responses' => $ok(['type' => 'object'])],
    'GET all-currencies' => ['tag' => 'Référentiels', 'summary' => 'Devises', 'responses' => $ok(['type' => 'object'])],
    'GET settings/cod-charges' => ['tag' => 'Référentiels', 'summary' => 'Taux d\'encaissement (COD) par zone, en pourcentage', 'responses' => $ok($obj(['codCharges' => $arr('CodCharge')]))],
    'GET settings/delivery-charges' => ['tag' => 'Référentiels', 'summary' => 'Barème de livraison du marchand (poids × zone)', 'responses' => $ok($obj(['deliveryCharges' => $arr('DeliveryRate')]))],

    // — Tableau de bord et profil ------------------------------------------
    'GET dashboard' => ['tag' => 'Tableau de bord', 'summary' => 'Compteurs du tableau de bord marchand', 'responses' => $ok($ref('DashboardData'))],
    'GET dashboard/filter' => ['tag' => 'Tableau de bord', 'summary' => 'Compteurs filtrés par période', 'parameters' => [$query('filter_date', 'Plage « AAAA-MM-JJ To AAAA-MM-JJ »')], 'responses' => $ok($ref('DashboardData'))],
    'GET dashboard/balance-details' => ['tag' => 'Tableau de bord', 'summary' => 'Relevé de règlement en cours : encaissé COD − frais − TVA = net à reverser', 'description' => '⚠️ Renvoie l\'objet **nu**, sans enveloppe.', 'responses' => $bare($ref('BalanceDetails'))],
    'GET dashboard/available-parcels' => ['tag' => 'Tableau de bord', 'summary' => 'Colis disponibles', 'responses' => $ok(['type' => 'object'])],
    'GET analytics' => ['tag' => 'Tableau de bord', 'summary' => 'Statistiques', 'responses' => $ok(['type' => 'object'])],
    'GET profile' => ['tag' => 'Profil', 'summary' => 'Profil du compte connecté', 'responses' => $ok($obj(['user' => $ref('AuthUser')]))],
    'POST profile/update' => ['tag' => 'Profil', 'summary' => 'Modifier le profil',
        'description' => '⚠️ Le backend ne valide que `name` et `address` mais **réécrit** aussi `mobile`, `email` et `business_name` avec ce qu\'il reçoit : envoyer les cinq champs, sous peine d\'effacer les absents.',
        'requestBody' => $body(['name' => $str(), 'address' => $str(), 'mobile' => $str(), 'email' => $str(), 'business_name' => $str()], ['name', 'address']), 'responses' => $empty],
    'POST fcm-subscribe' => ['tag' => 'Push', 'summary' => 'Abonner un appareil aux notifications push', 'description' => '⚠️ Hors service : l\'API FCM legacy visée est arrêtée depuis 2024 (bloc K). Le fil de notifications (`notifications/*`) le remplace.', 'requestBody' => $body(['device_token' => $str()], ['device_token']), 'responses' => $empty],
    'POST fcm-unsubscribe' => ['tag' => 'Push', 'summary' => 'Désabonner un appareil', 'requestBody' => $body(['device_token' => $str()], ['device_token']), 'responses' => $empty],

    // — Boutiques ----------------------------------------------------------
    'GET shops/index' => ['tag' => 'Boutiques', 'summary' => 'Boutiques du marchand', 'responses' => $ok($obj(['shops' => $arr('Shop')]))],
    'POST shops/store' => ['tag' => 'Boutiques', 'summary' => 'Créer une boutique', 'requestBody' => $body(['name' => $str(), 'contact_no' => $str('11 à 14 chiffres'), 'address' => $str(), 'status' => $int('1 active')], ['name', 'contact_no', 'address', 'status']), 'responses' => $empty],
    'GET shops/edit/{id}' => ['tag' => 'Boutiques', 'summary' => 'Une boutique du marchand', 'responses' => $ok($obj(['shop' => $ref('Shop')]))],
    'PUT shops/update/{id}' => ['tag' => 'Boutiques', 'summary' => 'Modifier une boutique', 'requestBody' => $body(['name' => $str(), 'contact_no' => $str(), 'address' => $str(), 'status' => $int()], ['name', 'contact_no', 'address', 'status']), 'responses' => $empty],
    'DELETE shops/delete/{id}' => ['tag' => 'Boutiques', 'summary' => 'Supprimer une boutique', 'responses' => $empty],

    // — Colis --------------------------------------------------------------
    'GET parcel/index' => ['tag' => 'Colis', 'summary' => 'Colis du marchand', 'responses' => $ok($obj(['parcels' => $arr('Parcel')]))],
    'GET parcel/filter' => ['tag' => 'Colis', 'summary' => 'Colis filtrés', 'parameters' => [$query('status', 'Statut (App\\Enums\\ParcelStatus)', 'integer'), $query('date', 'Plage de dates'), $query('search', 'Recherche libre')], 'responses' => $ok($obj(['parcels' => $arr('Parcel')]))],
    'GET parcel/create' => ['tag' => 'Colis', 'summary' => 'Référentiels du formulaire de création', 'responses' => $ok($ref('ParcelFormData'))],
    'POST parcel/quote' => ['tag' => 'Colis', 'summary' => 'Devis : les montants d\'un colis AVANT sa création', 'description' => 'Même `ChargeCalculator` que la création : ce que le devis annonce est ce que `parcel/store` enregistrera. N\'écrit rien. Le bloc `customs` dit si un export passe.', 'requestBody' => $body($parcelInput, ['category_id', 'delivery_type_id']), 'responses' => $ok($ref('ParcelQuote'))],
    'POST parcel/store' => ['tag' => 'Colis', 'summary' => 'Créer un colis', 'description' => 'Tous les montants (frais, TVA, net) sont calculés **côté serveur** (S2) ; un éventuel `chargeDetails` posté est ignoré. Un export bloquant (règle douanière niveau 3) est refusé en 422.', 'requestBody' => $body($parcelInput, $parcelRequired), 'responses' => $empty],
    'GET parcel/details/{id}' => ['tag' => 'Colis', 'summary' => 'Détail d\'un colis', 'responses' => $ok($obj(['parcel' => $ref('Parcel')]))],
    'GET parcel/edit/{id}' => ['tag' => 'Colis', 'summary' => 'Colis et référentiels pour modification', 'responses' => $ok(['type' => 'object'])],
    'PUT parcel/update/{id}' => ['tag' => 'Colis', 'summary' => 'Modifier un colis', 'requestBody' => $body($parcelInput, $parcelRequired), 'responses' => $empty],
    'GET parcel/logs/{id}' => ['tag' => 'Colis', 'summary' => 'Suivi (timeline) d\'un colis', 'responses' => $ok($obj(['parcelLogs' => $arr('ParcelEvent')]))],
    'GET parcel/{id}/status/{statusId}' => ['tag' => 'Colis', 'summary' => 'Changer le statut d\'un colis (annulation par le marchand)', 'responses' => $empty],
    'DELETE parcel/delete/{id}' => ['tag' => 'Colis', 'summary' => 'Supprimer un colis', 'responses' => $empty],
    'GET parcel/all/status' => ['tag' => 'Colis', 'summary' => 'Les 10 statuts marchand, traduits', 'description' => '⚠️ Liste **nue**, sans enveloppe. Ne jamais réinventer ces libellés côté app.', 'responses' => $bare($arr('ParcelStatusOption'))],
    'GET status-wise/parcel/list/{status}' => ['tag' => 'Colis', 'summary' => 'Colis d\'un statut donné', 'parameters' => [$path('status', 'Statut (App\\Enums\\ParcelStatus)')], 'responses' => $ok($obj(['parcels' => $arr('StatusWiseParcel')]))],

    // — Douane -------------------------------------------------------------
    'GET customs/reference' => ['tag' => 'Douane', 'summary' => 'Pays et catégories couverts par les règles douanières', 'responses' => $ok($ref('CustomsReference'))],
    'GET customs/alerts' => ['tag' => 'Douane', 'summary' => 'Alertes douanières du marchand', 'parameters' => [$query('status', '1 en cours · 2 traitées ; absent = toutes', 'integer'), $page], 'responses' => $ok($obj(['alerts' => $arr('CustomsAlert')]))],
    'PUT customs/alerts/{id}/resolve' => ['tag' => 'Douane', 'summary' => 'Marquer une alerte traitée', 'responses' => $ok($obj(['alert' => $ref('CustomsAlert')]))],

    // — Argent -------------------------------------------------------------
    'GET invoice-list/index' => ['tag' => 'Factures', 'summary' => 'Relevés de règlement émis', 'description' => '10 par page ; `data` est le tableau des relevés (compteurs du paginateur hors de portée).', 'parameters' => [$page], 'responses' => $bare($obj(['data' => $arr('Invoice')]))],
    'GET invoice-details/{id}' => ['tag' => 'Factures', 'summary' => 'Ventilation d\'un relevé', 'responses' => $bare($obj(['data' => $ref('InvoiceDetails')]))],
    'GET invoice-pdf-link/{id}' => ['tag' => 'Factures', 'summary' => 'Lien signé (15 min) vers le relevé en PDF', 'description' => 'L\'app ne peut pas joindre son jeton à un navigateur : elle ouvre cette URL signée. Mentions IFU/RCCM des deux parties (chantier 4).', 'responses' => $ok($ref('PdfLink'))],
    'GET payment-accounts/index' => ['tag' => 'Retraits', 'summary' => 'Comptes de règlement du marchand', 'responses' => $ok($obj(['accounts' => $arr('PaymentAccount')]))],
    'POST payment-account/store' => ['tag' => 'Retraits', 'summary' => 'Ajouter un compte de règlement', 'description' => 'Selon `payment_method` : `bank` (bank_name, holder_name, account_no, branch_name, routing_no) ou `mobile` (mobile_holder_name, mobile_company, mobile_no, account_type).', 'requestBody' => $body(['payment_method' => ['type' => 'string', 'enum' => ['bank', 'mobile', 'cash']], 'bank_name' => $str(), 'holder_name' => $str(), 'account_no' => $str(), 'branch_name' => $str(), 'routing_no' => $str(), 'mobile_holder_name' => $str(), 'mobile_company' => $str('« MTN MoMo » ou « Moov Money »'), 'mobile_no' => $str('11 à 14 chiffres'), 'account_type' => $str('« Personnel » ou « Marchand »')], ['payment_method']), 'responses' => $empty],
    'GET payment-account/edit/{id}' => ['tag' => 'Retraits', 'summary' => 'Un compte de règlement', 'responses' => $ok($obj(['account' => $ref('PaymentAccount')]))],
    'PUT payment-account/update' => ['tag' => 'Retraits', 'summary' => 'Modifier un compte de règlement', 'requestBody' => $body(['id' => $int(), 'payment_method' => $str()], ['id', 'payment_method']), 'responses' => $empty],
    'DELETE payment-account/delete/{id}' => ['tag' => 'Retraits', 'summary' => 'Supprimer un compte de règlement', 'responses' => $empty],
    'GET payment-request/index' => ['tag' => 'Retraits', 'summary' => 'Demandes de retrait du marchand', 'responses' => $ok($obj(['payments' => $arr('PaymentRequest')]))],
    'GET payment-request/create' => ['tag' => 'Retraits', 'summary' => 'Marchand et comptes disponibles pour une demande', 'responses' => $ok($obj(['merchant' => $ref('Merchant'), 'merchantAccounts' => $arr('PaymentAccount')]))],
    'POST payment-request/store' => ['tag' => 'Retraits', 'summary' => 'Demander un retrait du net à reverser', 'description' => 'Le montant est comparé à `merchant.current_balance` et le compte doit appartenir au marchand (S19) : 422 sinon.', 'requestBody' => $body(['amount' => $int(), 'merchant_account' => $int('Identifiant d\'un compte du marchand'), 'description' => $str('', true)], ['amount', 'merchant_account']), 'responses' => $empty],
    'GET payment-request/edit/{id}' => ['tag' => 'Retraits', 'summary' => 'Une demande de retrait', 'responses' => $ok($obj(['payment' => $ref('PaymentRequest'), 'merchantAccounts' => $arr('PaymentAccount')]))],
    'PUT payment-request/update/{id}' => ['tag' => 'Retraits', 'summary' => 'Modifier une demande encore en attente', 'requestBody' => $body(['amount' => $int(), 'merchant_account' => $int(), 'description' => $str('', true)], ['amount', 'merchant_account']), 'responses' => $empty],
    'DELETE payment-request/delete/{id}' => ['tag' => 'Retraits', 'summary' => 'Annuler une demande encore en attente', 'responses' => $empty],
    'GET account-transaction/index' => ['tag' => 'Argent', 'summary' => 'Transactions de compte', 'responses' => $ok($obj(['transactions' => $arr('Transaction')]))],
    'POST account-transaction/filter' => ['tag' => 'Argent', 'summary' => 'Transactions filtrées', 'requestBody' => $body(['date' => $str('Plage « AAAA-MM-JJ To AAAA-MM-JJ »'), 'merchant_account' => $int()]), 'responses' => $ok($obj(['transactions' => $arr('Transaction')]))],
    'GET statements/index' => ['tag' => 'Argent', 'summary' => 'Relevés de compte', 'responses' => $ok($obj(['statements' => $arr('Statement')]))],
    'POST statements/filter' => ['tag' => 'Argent', 'summary' => 'Relevés de compte filtrés', 'requestBody' => $body(['date' => $str('Plage « AAAA-MM-JJ To AAAA-MM-JJ »')]), 'responses' => $ok($obj(['statements' => $arr('Statement')]))],
    'POST statement-reports' => ['tag' => 'Argent', 'summary' => 'Rapport de synthèse', 'requestBody' => $body(['date' => $str('Plage « AAAA-MM-JJ To AAAA-MM-JJ »')]), 'responses' => $ok(['type' => 'object'])],

    // — FedaPay et wallet --------------------------------------------------
    'POST fedapay/initiate' => ['tag' => 'Wallet', 'summary' => 'Initier une recharge du wallet par Mobile Money (FedaPay)', 'description' => 'Renvoie l\'URL de paiement à ouvrir. **Le solde n\'est crédité que par le webhook signé** : interroger `fedapay/status` au retour, ne rien déduire de la fermeture du navigateur. 503 si la passerelle n\'est pas configurée.', 'requestBody' => $body(['amount' => $int('Entier XOF ≥ 1')], ['amount']), 'responses' => $ok($ref('RechargeInitiation')) + ['403' => ['description' => 'Compte non marchand'], '502' => ['description' => 'FedaPay injoignable'], '503' => ['description' => 'Passerelle non configurée']]],
    'GET fedapay/status/{reference}' => ['tag' => 'Wallet', 'summary' => 'État d\'une recharge', 'parameters' => [$path('reference', 'Référence renvoyée par `fedapay/initiate`', 'string')], 'responses' => $ok($ref('RechargeStatus'))],
    'GET wallet/history' => ['tag' => 'Wallet', 'summary' => 'Mouvements du porte-monnaie prépayé', 'parameters' => [$page], 'responses' => $ok($obj(['entries' => $arr('WalletEntry')]))],

    // — Notifications ------------------------------------------------------
    'GET notifications/index' => ['tag' => 'Notifications', 'summary' => 'Fil de notifications du marchand', 'parameters' => [$page], 'responses' => $ok($obj(['notifications' => $arr('Notification'), 'unread_count' => $int()]))],
    'GET notifications/unread-count' => ['tag' => 'Notifications', 'summary' => 'Nombre de notifications non lues', 'responses' => $ok($obj(['unread_count' => $int()]))],
    'PUT notifications/read-all' => ['tag' => 'Notifications', 'summary' => 'Tout marquer comme lu', 'responses' => $ok($obj(['unread_count' => ['type' => 'integer', 'example' => 0]]))],
    'PUT notifications/{id}/read' => ['tag' => 'Notifications', 'summary' => 'Marquer une notification lue', 'parameters' => [$path('id', 'UUID de la notification', 'string')], 'responses' => $ok($obj(['notification' => $ref('Notification')]))],

    // — Relation -----------------------------------------------------------
    'GET fraud/index' => ['tag' => 'Relation', 'summary' => 'Clients signalés (fraude)', 'responses' => $ok($obj(['frauds' => $arr('Fraud')]))],
    'POST fraud/store' => ['tag' => 'Relation', 'summary' => 'Signaler un client', 'requestBody' => $body(['name' => $str(), 'phone' => $str(), 'description' => $str('', true)], ['phone']), 'responses' => $empty],
    'GET fraud/edit/{id}' => ['tag' => 'Relation', 'summary' => 'Un signalement', 'responses' => $ok($obj(['fraud' => $ref('Fraud')]))],
    'PUT fraud/update/{id}' => ['tag' => 'Relation', 'summary' => 'Modifier un signalement', 'requestBody' => $body(['name' => $str(), 'phone' => $str(), 'description' => $str('', true)], ['phone']), 'responses' => $empty],
    'DELETE fraud/delete/{id}' => ['tag' => 'Relation', 'summary' => 'Supprimer un signalement', 'responses' => $empty],
    'POST fraud/check' => ['tag' => 'Relation', 'summary' => 'Vérifier un numéro avant de créer un colis', 'requestBody' => $body(['phone' => $str()], ['phone']), 'responses' => $ok(['type' => 'object'])],
    'GET news-offer/index' => ['tag' => 'Relation', 'summary' => 'Actualités et offres', 'description' => 'Ce sont des **offres**, pas des notifications : voir `notifications/*`.', 'responses' => $ok($obj(['newsOffers' => $arr('NewsOffer')]))],
    'GET support/index' => ['tag' => 'Relation', 'summary' => 'Tickets de support', 'responses' => $ok($obj(['supports' => $arr('Support')]))],
    'GET support/create' => ['tag' => 'Relation', 'summary' => 'Référentiels du formulaire de ticket (départements, services)', 'responses' => $ok(['type' => 'object'])],
    'POST support/store' => ['tag' => 'Relation', 'summary' => 'Ouvrir un ticket', 'requestBody' => $body(['subject' => $str(), 'department_id' => $int(), 'service' => $str(), 'priority' => $str(), 'description' => $str()], ['subject', 'description']), 'responses' => $empty],
    'GET support/edit/{id}' => ['tag' => 'Relation', 'summary' => 'Un ticket', 'responses' => $ok($obj(['support' => $ref('Support')]))],
    'PUT support/update/{id}' => ['tag' => 'Relation', 'summary' => 'Modifier un ticket', 'requestBody' => $body(['subject' => $str(), 'description' => $str()], ['subject', 'description']), 'responses' => $empty],
    'DELETE support/delete/{id}' => ['tag' => 'Relation', 'summary' => 'Supprimer un ticket', 'responses' => $empty],
    'GET support/view/{id}' => ['tag' => 'Relation', 'summary' => 'Ticket et fil de discussion', 'responses' => $ok(['type' => 'object'])],
    'POST support/reply' => ['tag' => 'Relation', 'summary' => 'Répondre dans un ticket', 'requestBody' => $body(['support_id' => $int(), 'message' => $str()], ['support_id', 'message']), 'responses' => $empty],

    // — Livreur ------------------------------------------------------------
    'GET deliveryman/parcel/index' => ['tag' => 'Livreur', 'summary' => 'Courses du livreur', 'responses' => $ok($obj(['parcels' => $arr('Parcel')]))],
    'GET deliveryman/parcel/details/{id}' => ['tag' => 'Livreur', 'summary' => 'Détail d\'une course et son suivi', 'responses' => $ok($obj(['parcel' => $ref('Parcel'), 'parcelEvents' => $arr('ParcelEvent')]))],
    'POST deliveryman/parcel/delivered/{id}' => ['tag' => 'Livreur', 'summary' => 'Déclarer un colis livré', 'requestBody' => $body(['cash_collection' => $int('Montant encaissé, entier XOF'), 'note' => $str('', true)]), 'responses' => $empty],
    'POST deliveryman/parcel/partial-delivered/{id}' => ['tag' => 'Livreur', 'summary' => 'Déclarer une livraison partielle', 'requestBody' => $body(['cash_collection' => $int('Montant encaissé'), 'note' => $str('', true)], ['cash_collection']), 'responses' => $empty],
    'GET deliveryman/income-expense' => ['tag' => 'Livreur', 'summary' => 'Gains et dépenses du livreur', 'responses' => $ok($obj(['income' => $arr('IncomeExpense'), 'expense' => $arr('IncomeExpense'), 'total' => ['type' => 'object']]))],
    'GET deliveryman/dashboard' => ['tag' => 'Livreur', 'summary' => 'Compteurs du livreur (en cours, livrés, retours, COD)', 'responses' => $ok(['type' => 'object'])],
    'GET deliveryman/profile' => ['tag' => 'Livreur', 'summary' => 'Profil du livreur', 'responses' => $ok($obj(['user' => $ref('DeliverymanUser')]))],
    'GET deliveryman/payment-logs' => ['tag' => 'Livreur', 'summary' => 'Encaissements remis', 'responses' => $ok(['type' => 'object'])],
    'GET deliveryman/parcel-payment-logs' => ['tag' => 'Livreur', 'summary' => 'Encaissements par colis', 'responses' => $ok($obj(['logs' => $arr('ParcelPaymentLog')]))],
    'GET deliveryman/parcel-status' => ['tag' => 'Livreur', 'summary' => 'Statuts que le livreur peut poser', 'responses' => $ok(['type' => 'object'])],
    'POST deliveryman/parcel-status-update' => ['tag' => 'Livreur', 'summary' => 'Changer le statut d\'une course (livré, partiel, retour)', 'requestBody' => $body(['parcel_id' => $int(), 'status' => $int('App\\Enums\\ParcelStatus'), 'cash_collection' => $int('', true), 'note' => $str('', true)], ['parcel_id', 'status']), 'responses' => $empty],
    'POST deliveryman/parcel-location-update' => ['tag' => 'Livreur', 'summary' => 'Remonter la position d\'une course', 'description' => 'Sous `auth:sanctum` depuis S4.', 'requestBody' => $body(['parcel_id' => $int(), 'lat' => ['type' => 'number'], 'long' => ['type' => 'number']], ['parcel_id', 'lat', 'long']), 'responses' => $empty],

    // — Public -------------------------------------------------------------
    'GET openapi.json' => ['tag' => 'Public', 'summary' => 'Cette spécification, générée depuis le routeur', 'responses' => $bare(['type' => 'object'], 'Document OpenAPI 3.0')],
    'GET customer/installation' => ['tag' => 'Public', 'summary' => 'Vérification d\'installation (socle)', 'responses' => $ok(['type' => 'object'])],
    'GET parcel/tracking/{tracking_id}' => ['tag' => 'Public', 'summary' => 'Suivi public d\'un colis par numéro', 'parameters' => [$path('tracking_id', 'Numéro de suivi', 'string')], 'responses' => $ok(['type' => 'object'])],
    'POST contact-us' => ['tag' => 'Public', 'summary' => 'Message de contact du site', 'requestBody' => $body(['name' => $str(), 'email' => $str(), 'subject' => $str(), 'message' => $str()], ['name', 'email', 'message']), 'responses' => $empty],
    'POST subscribe' => ['tag' => 'Public', 'summary' => 'Inscription à la lettre d\'information', 'requestBody' => $body(['email' => $str()], ['email']), 'responses' => $empty],
    'GET delivery-charges' => ['tag' => 'Référentiels', 'summary' => 'Barème de livraison (variante hors clé API, jeton seul — S10)', 'responses' => $ok(['type' => 'object'])],
];

return ['schemas' => $schemas, 'operations' => $operations];
