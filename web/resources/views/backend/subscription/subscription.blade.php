@extends('backend.partials.master')
@section('title')
    {{ __('levels.subscription') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
    <div class="container-fluid  dashboard-content">
        <h2 class="text-center mt-5">{{ __('Choose the right plan for you!') }}</h2>
        <div class="row">

            @foreach ($plans as $key => $plan)
                <div class="col-xl-4 mt-3">
                    <div class="card  text-center p-5 h-100">
                        <div class="h-100">
                            <h3 class="text-center my-2">{{ @$plan->name }}</h3>
                            <p class="my-3">{{ @$plan->description }}</p>
                            <div class="d-flex justify-content-center my-5 ">
                                @php $settings = App\Models\Backend\GeneralSettings::find(1); @endphp
                                <h3 class="mt-2px">{{ formatAmount(@$plan->price) }} </h3>
                                <div class="mx-2 text-left">
                                    <p class="mb-2 font-weight-bold"> / {{ @$plan->intval_name }}</p>
                                </div>
                            </div>
                            <ul class="list-style-none text-left plan-accordion">
                                <li><i class="fa fa-check text-success mr-10px"></i>{{ __('levels.parcel_count') }}
                                    {{ @$plan->parcel_count }}</li>

                                @foreach ($allmodules as $module)
                                    @if (in_array($module, $plan->modules))
                                        <li><i
                                                class="fa fa-check text-success mr-10px"></i>{{ __('permissions.' . @$module) }}
                                        </li>
                                    @else
                                        <li><i class="fa fa-times text-danger mr-10px"></i>{{ __('permissions.' . @$module) }}
                                        </li>
                                    @endif
                                @endforeach

                            </ul>
                        </div>
                        <div class="card-footer bg-none" style="border: none"> 
                            <div class="align-bottom">
                                @if (Auth::user()->subscription && Auth::user()->subscription->plan_id == $plan->id && subscriptionCheck(Auth::user()))
                                    <span class="text-success">{{ __('levels.active') }}</span><br />
                                    {{ __('levels.remaining') }} {{ subscriptionCheck(Auth::user()) }}
                                    {{ __('levels.days') }}<br />
                                @elseif(Auth::user()->subscription && Auth::user()->subscription->plan_id == $plan->id)
                                    <label class="badge badge-danger mb-2">{{ __('levels.expired') }}</label><br />
                                @endif
    
                                {{-- R3 (S75) : pas de prorata au renouvellement — l'acheteur le sait AVANT de payer. --}}
                                <p class="small text-muted mb-2 plan-switch-notice">{{ __('levels.plan_switch_notice') }}</p>
                                {{-- S105 : le bouton Stripe n'apparaît que si la plateforme a l'interrupteur ET la clé (calculé par le contrôleur, jamais par une requête dans la vue). --}}
                                @if (!empty($stripeEnabled))
                                    <a class="btn btn-primary "
                                        href="{{ route('subscription.payment', ['plan_id' => $plan->id]) }}">{{ __('Subscribe') }}</a>
                                @elseif (empty($fedapayEnabled))
                                    <button class="btn btn-primary subscribe-btn" data-bs-toggle="modal"
                                        data-bs-target="#exampleModalToggle">{{ __('Subscribe') }}</button>
                                @endif
                                {{-- Chantier 3 : abonnement par Mobile Money (MTN MoMo / Moov Money).
                                     L'activation vient du webhook signé, jamais du retour de page. --}}
                                @if (!empty($fedapayEnabled) && (int) round((float) $plan->price) > 0)
                                    <form method="POST" action="{{ route('subscription.fedapay') }}" class="d-inline mt-2">
                                        @csrf
                                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                        <button type="submit" class="btn btn-warning">{{ __('fedapay.subscribe_button') }}</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

        </div>
    </div>


    <div class="modal" id="exampleModalToggle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false"
        aria-labelledby="exampleModalToggleLabel" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalToggleLabel">{{ __('levels.contact') }}</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <h4>{{ __('Contact the administrator to subscribe.') }}</h4>
                    <p class="mb-2">{{ __('levels.name') }} : {{ @$settings->name }}</p>
                    <p class="mb-2">{{ __('levels.email') }} : {{ @$settings->email }}</p>
                    <p class="mb-2">{{ __('levels.phone') }} : {{ @$settings->phone }}</p>
                </div>

            </div>
        </div>
    </div>
@endsection
