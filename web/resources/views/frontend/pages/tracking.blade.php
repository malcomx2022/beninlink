@extends('frontend.layouts.master')
@section('title')
    {{ __('levels.parcel_tracking') }} | {{ @settings()->name }}
@endsection 
@section('content') 
<section class="container-fluid pb-5  ">
    <div class="container pt-5 pb-5 ">
        <div class="row align-items-center mt-3">
            <div class="col-lg-8 m-auto"> 
                <form action="{{ route('tracking.index') }}" method="GET">
                   
                    <div class="input-group mb-3 tracking-page tracking-form">
                        <input type="text" class="form-control" placeholder="{{ __('levels.enter_tracking_id') }}" name="tracking_id" value="{{ $request->tracking_id }}"  >
                        <div class="input-group-append">
                            <button type="submit" class="input-group-text bg-primary"  >{{ __('levels.track_now') }}</button>
                        </div>
                    </div>
                </form>
                <h3 class="font-size-1-5rem display-6 font-weight-bold text-center my-4">{{ __('levels.parcel_tracking_no') }}: <span class="text-primary"># {{ $request->tracking_id }}</span></h3>
            </div>  
        </div> 
        <div class="parcel-oprations"> 
            @if(!empty($request->tracking_id) && $parcel)
            <section class="cd-timeline js-cd-timeline">
                <div class="cd-timeline__container">  
                    @foreach ($parcelevents as $key=>$log)
                        @php
                            // Le socle collait ici le mot anglais « cancel », en dur, au
                            // libellé du statut — sur la page que lit le client final.
                            $annule = ! empty($log->cancel_parcel_id);
                            $etape  = \App\Services\Parcel\ParcelStage::of($log->parcel_status);
                        @endphp
        
                        {{-- Le socle écrivait ONZE branches — dix statuts et un repli — dont
                             le corps ne différait que par une ligne d'acteur. Et sa table de
                             couleurs était une QUATRIÈME copie de celle que le lot 2 avait
                             réduite à une seule : `ParcelStage` la donne désormais ici aussi.
                             C'est la page que voit le CLIENT FINAL ; elle peignait jusqu'ici
                             chaque étape en vert vif, y compris une livraison PARTIELLE, que
                             la charte réserve à l'avertissement. --}}
                        <div class="cd-timeline__block js-cd-block">
                            <div class="cd-timeline__img js-cd-img bl-node bl-node--{{ $annule ? 'cancel' : $etape }}">
                                <i class="timeline_icon fas {{ $annule ? 'fa-times' : 'fa-check' }}" aria-hidden="true"></i>
                            </div>
                            <!-- cd-timeline__img -->
                            <div class="cd-timeline__content js-cd-content">
                                <strong>{{ __('parcelLogs.' . $log->parcel_status) }}@if($annule) — {{ __('Cancelled') }}@endif</strong><br>

                                {{-- Les acteurs se rendent à la PRÉSENCE, non plus par statut :
                                     le socle n'affichait le ramasseur que sur deux statuts et le
                                     masquait partout ailleurs, même quand le journal le portait. --}}
                                @isset($log->pickupman)
                                    <span>{{ __('parcel.pickup_man') }}: {{ $log->pickupman->user->name }}</span><br>
                                    <span>{{ __('levels.mobile') }}: {{ $log->pickupman->user->mobile }}</span><br>
                                @endisset
                                @isset($log->hub)
                                    <span>{{ __('parcelLogs.hub_name') }}: {{ $log->hub->name }}</span><br>
                                    <span>{{ __('parcelLogs.hub_phone') }}: {{ $log->hub->phone }}</span><br>
                                @endisset
                                @isset($log->transferDeliveryman)
                                    <span>{{ __('parcelLogs.delivery_man') }}: {{ $log->transferDeliveryman->user->name }}</span><br>
                                    <span>{{ __('parcelLogs.delivery_man_phone') }}: {{ $log->transferDeliveryman->user->mobile }}</span><br>
                                @endisset
                                @isset($log->deliveryMan)
                                    <span>{{ __('parcelLogs.delivery_man') }}: {{ $log->deliveryMan->user->name }}</span><br>
                                    <span>{{ __('parcelLogs.delivery_man_phone') }}: {{ $log->deliveryMan->user->mobile }}</span><br>
                                @endisset

                                <span>{{ __('levels.note') }}: {{ $log->note }}</span><br/>

                                <strong>{{ __('levels.created_by') }}</strong><br/>
                                <span>{{ __('levels.name') }}: {{ $log->user->name }}</span><br/>
                                <span>{{ __('levels.mobile') }}: {{ $log->user->mobile }}</span><br/>

                                <div class="cd-timeline__date">
                                    <strong>{!! dateFormat($log->created_at) !!}</strong><br>
                                    <small>{!! date('h:i a', strtotime($log->created_at)) !!}</small>
                                </div>
                            </div>
                            <!-- cd-timeline__content -->
                        </div>
                    @endforeach 
                    <div class="cd-timeline__block js-cd-block">
                        <div class="cd-timeline__img js-cd-img bl-node bl-node--wait">
                            <i class="timeline_icon fas fa-check" aria-hidden="true"></i>
                        </div>
                        <!-- cd-timeline__img -->
                        <div class="cd-timeline__content js-cd-content">
                            <strong>{{__('parcel.parcel_create')}}</strong><br>
                            <span>{{__('levels.name')}}: {{$parcel->merchant->user->name}}</span><br>
                            <span>{{__('levels.email')}}: {{$parcel->merchant->user->email}}</span><br>
                            <span>{{__('levels.mobile')}}: {{$parcel->merchant->user->mobile}}</span><br/>
        
                            <div class="cd-timeline__date">
                                <strong>{!! dateFormat($parcel->created_at) !!}</strong><br>
                                <small>{!! date('h:i a', strtotime($parcel->created_at)) !!}</small>
                            </div>
                        </div>
                        <!-- cd-timeline__content -->
                    </div> 
                </div>
            </section>
            <!-- cd-timeline -->
            @elseif(!empty($request->tracking_id) && !$parcel) 
                <div class="row my-5">
                    <div class="col-lg-6 m-auto">
                        <img alt="{{ __('No parcel matches this tracking number') }}" src="{{ static_asset('frontend/images/parcel-was-not-found.png') }}" width="100%"/>
                    </div>
                </div> 
            @endif
        </div>
    </div>
</section> 
@endsection
@push('styles')
    <link rel="stylesheet" href="{{ static_asset('frontend/css/timeline.css') }}"/>
@endpush