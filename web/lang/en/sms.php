<?php

/**
 * The outgoing SMS messages. See lang/fr/sms.php for the full rationale — the
 * French file is the reference, this one is its counterpart.
 *
 * English is served only to an installation that switches its default locale
 * (`APP_LOCALE=en`); the product's acted language is French. Both files must
 * declare exactly the same keys — `SmsTemplateTest` fails otherwise.
 *
 * The GSM 03.38 constraint documented in the French file applies here too, and
 * plain English satisfies it naturally.
 */
return [

    /* ── Parcels ────────────────────────────────────────────────────────── */

    'parcel_created' => 'Dear :customer, your parcel :tracking from :merchant is registered (:amount). - :brand',

    'pickup_assigned_agent' => 'Dear :agent, please pick up parcel :tracking from :merchant (:phone, :address) before :date. - :brand',

    'pickup_assigned_merchant' => 'Dear :merchant, a pickup agent is assigned to your parcel :tracking: :agent, :agent_phone. Track: :url - :brand',

    'deliveryman_assigned_customer' => 'Dear :customer, your parcel :tracking from :merchant (:amount) is assigned to delivery agent :agent, :agent_phone. Track: :url - :brand',

    'delivery_rescheduled_customer' => 'Dear :customer, delivery of your parcel :tracking from :merchant (:amount) is rescheduled. Delivery agent :agent, :agent_phone. Track: :url - :brand',

    'warehouse_received_customer' => 'Dear :customer, your parcel :tracking from :merchant reached our sorting centre and will be delivered as soon as possible. Track: :url - :brand',

    'warehouse_received_merchant' => 'Dear :merchant, your parcel :tracking reached hub :hub. Track: :url - :brand',

    'returned_to_merchant' => 'Dear :merchant, your parcel :tracking is being returned to you by :agent, :agent_phone. Track: :url - :brand',

    'delivered_customer' => 'Dear :customer, your parcel :tracking is delivered. Rate your experience: :url - :brand',

    'delivered_merchant' => 'Dear :merchant, your parcel :tracking is delivered. Customer :customer, :phone. Track: :url - :brand',

    'delivery_cancelled_customer' => 'Dear :customer, your parcel :tracking from :merchant is cancelled. Track: :url - :brand',

    'delivery_cancelled_merchant' => 'Dear :merchant, delivery of your parcel :tracking is cancelled. Customer :customer, :phone. Track: :url - :brand',

    'partial_delivered_customer' => 'Dear :customer, your parcel :tracking is partially delivered. Amount due: :amount. Rate your experience: :url - :brand',

    'partial_delivered_merchant' => 'Dear :merchant, your parcel :tracking is partially delivered. Customer :customer, :phone. Amount collected: :amount. Track: :url - :brand',

    /* ── Wallet ─────────────────────────────────────────────────────────── */

    'wallet_recharged' => 'Dear :merchant, your :brand wallet has been topped up with :amount.',

    'wallet_recharged_with_reference' => 'Dear :merchant, your :brand wallet has been topped up with :amount. Reference: :reference',

    /* ── Authentication ─────────────────────────────────────────────────── */

    'otp' => ':code is your :brand verification code.',

];
