@extends('backend.partials.master')
@section('title')
    {{ __('levels.subscription') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
    <div class="container-fluid  dashboard-content">
        <h2 class="text-center mt-5">Choose your right plan !</h2>
        <div class="row">

            @foreach ($plans as $key => $plan)
                <div class="col-xl-4 mt-3">
                    <div class="card  text-center p-5 h-100">
                        <div class="h-100">
                            <h3 class="text-center my-2">{{ @$plan->name }}</h3>
                            <p class="my-3">{{ @$plan->description }}</p>
                            <div class="d-flex justify-content-center my-5 ">
                                @php
                                    $settings = App\Models\Backend\GeneralSettings::find(1);
                                    $stripe_status = App\Models\Backend\Setting::where('company_id', 1)
                                        ->where('key', 'stripe_status')
                                        ->first();
                                @endphp
                                <h3 class="mt-2px">{{ @$settings->currency }} {{ @$plan->price }} </h3>
                                <div class="mx-2 text-left">
                                    <p class="mb-2 font-weight-bold"> / {{ @$plan->intval_name }}</p>
                                    <p>when billed annually</p>
                                </div>
                            </div>
                            <ul class="list-style-none text-left plan-accordion">
                                <li><i class="fa fa-check text-success mr-10px"></i>Total parcel count
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
    
                                @if ($stripe_status->value)
                                    <a class="btn btn-primary "
                                        href="{{ route('subscription.payment', ['plan_id' => $plan->id]) }}">Subscribe</a>
                                @else
                                    <button class="btn btn-primary subscribe-btn" data-bs-toggle="modal"
                                        data-bs-target="#exampleModalToggle">Subscribe</button>
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
                    <h1 class="modal-title fs-5" id="exampleModalToggleLabel">Contact</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <h4>Contact admin to subscribe.</h4>
                    <p class="mb-2">Name : {{ @$settings->name }}</p>
                    <p class="mb-2">Email : {{ @$settings->email }}</p>
                    <p class="mb-2">Phone : {{ @$settings->phone }}</p>
                </div>

            </div>
        </div>
    </div>
@endsection
