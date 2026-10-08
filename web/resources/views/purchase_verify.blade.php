{{-- S117 : la page d'activation du socle portait le logo et le WhatsApp de l'éditeur, et parlait de
     CodeCanyon. Elle n'est plus servie (PurchaseVerify::purchaseVerify() rend toujours vrai) ; si elle
     revenait, elle dirait seulement que le domaine est inactif, au nom de la plateforme. --}}
@extends('errors.layout', ['administrator_contact' => true])
@section('title', __('This domain is inactive.'))
@section('message-headline', __('This domain is inactive.'))
@section('message')
    <p>{{ __('Please contact the platform administrator to activate it.') }}</p>
@endsection
