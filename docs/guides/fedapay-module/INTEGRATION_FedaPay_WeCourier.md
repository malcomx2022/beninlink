# Intégration FedaPay (MTN / Moov Bénin) — socle We Courier → BeninLink

Ce module **ajoute** FedaPay comme passerelle, sans réécrire le cœur multi-tenant.
Il se branche sur trois briques existantes de We Courier : l'abstraction de
passerelle, le service wallet, le module d'abonnement. On les **appelle**.

## Étape 0 (obligatoire)
Repérer dans le code réel : interface de gateway (via la classe Paystack), service
wallet, module d'abonnement, résolution du locataire, table des gateways.

## Installation
1. `composer require fedapay/fedapay-php`
2. Déposer les fichiers dans web/ (mêmes chemins).
3. `.env` : FEDAPAY_ENVIRONMENT, FEDAPAY_PUBLIC_KEY, FEDAPAY_SECRET_KEY, FEDAPAY_WEBHOOK_SECRET
4. Routes :
   - POST /fedapay/initiate   (auth) name=fedapay.initiate  (+ exposer dans api.php pour l'app)
   - GET  /fedapay/callback   name=fedapay.callback
   - POST /fedapay/webhook    name=fedapay.webhook  (SANS auth, EXCLU du CSRF)
5. Exclure `fedapay/webhook` de VerifyCsrfToken::$except.
6. `php artisan migrate` ; enregistrer la gateway (slug=fedapay).
7. Dashboard FedaPay : webhook -> https://DOMAINE/fedapay/webhook ; copier le
   secret de signature ; événement `transaction.approved`.

## Côté app React Native
- Recharge : POST /api/fedapay/initiate {amount, purpose:"wallet_recharge"} ->
  ouvrir `payment_url` en WebView. Le solde n'est à jour qu'après le webhook.
- L'app n'accède JAMAIS aux clés FedaPay.

## FCFA
XOF = montants entiers. Le service force `(int) round($amount)`.
