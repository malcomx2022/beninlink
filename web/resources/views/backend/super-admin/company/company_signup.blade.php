@extends('auth.Layouts')
@section('title', __('auth.sign_up'))
@section('content') 
<!-- signup form  -->
<form class="splash-container" method="POST" action="{{ route('company.sign-up.store') }}">
    @csrf
    <div class="card">
        <div class="row">
            <div class="col-lg-5">
                <div class="card auth-boxs">
                    <div class="card-header text-center">
                        <a href="{{url('/')}}" class="navbar-brand">
                            <img class="logo-img" src="{{ settings()->logo_image }}"  class="logo" alt="{{ settings()->name }}">
                        </a>
                        <h3 class="mb-1">Registrations Form</h3>
                        <p>Please enter your user information.</p>
                    </div>
                    <div class="card-body">
  
                        <div class="form-group"> 
                            <input id="company_name" type="text" name="company_name"
                                data-parsley-trigger="change"
                                placeholder="{{ __('placeholder.Enter_company_name') }}" autocomplete="off"
                                class="form-control @error('company_name') is-invalid @enderror"
                                value="{{ old('company_name') }}" require>
                            @error('company_name')
                                <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Identifiants légaux du transporteur (chantier 2). Facultatifs
                             ici : l'inscription d'un locataire précède souvent la
                             transmission de ses pièces ; ils se complètent ensuite dans
                             les réglages généraux. --}}
                        <div class="form-group">
                            <input id="ifu" type="text" name="ifu" inputmode="numeric" maxlength="13"
                                placeholder="{{ __('merchant.ifu') }} — {{ __('placeholder.enter_ifu') }}" autocomplete="off"
                                class="form-control @error('ifu') is-invalid @enderror" value="{{ old('ifu') }}">
                            @error('ifu')
                                <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <input id="rccm" type="text" name="rccm" maxlength="50"
                                placeholder="{{ __('merchant.rccm') }} — {{ __('placeholder.enter_rccm') }}" autocomplete="off"
                                class="form-control @error('rccm') is-invalid @enderror" value="{{ old('rccm') }}">
                            @error('rccm')
                                <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-2" > 
                            <div class="input-group  ">
                                <span class="input-group-text start-domain" >{{ scheme_name() }}</span>
                                <input id="domain" type="text" name="domain"
                                data-parsley-trigger="change"
                                placeholder="{{ __('placeholder.Enter_domain') }}" autocomplete="off"
                                class="form-control @error('domain') is-invalid @enderror"
                                value="{{ old('domain') }}" require>
                                <span class="input-group-text end-domain" >{{ '.'.get_host() }}</span>
                              </div>  
                            @error('domain')
                                <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror 
                        </div>
 
                        <div class="form-group"> 
                            <input id="name" type="text" name="name" data-parsley-trigger="change"
                                placeholder="{{ __('placeholder.Enter_name') }}" autocomplete="off"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" require>
                            @error('name')
                                <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group"> 
                            <input id="email" type="email" name="email"
                                data-parsley-trigger="change"
                                placeholder="{{ __('placeholder.enter_email') }}" autocomplete="off"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}" require>
                            @error('email')
                                <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group"> 
                            <input id="mobile" type="number" name="mobile" data-parsley-trigger="change"
                                placeholder="{{ __('placeholder.Enter_mobile') }}" autocomplete="off"
                                class="form-control @error('mobile') is-invalid @enderror"
                                value="{{ old('mobile') }}" require>
                            @error('mobile')
                                <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>


                        <div class="form-group">
                            <textarea name="address" id="address" class="form-control @error('address') is-invalid @enderror" placeholder="{{ __('placeholder.Enter_address') }} *" rows="5">{{ old('address')  }}</textarea>
                            @error('address')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>


                        <div class="form-group">
                            <input id="password" type="password" class="form-control form-control-lg @error('password') is-invalid @enderror" name="password"  autocomplete="new-password" placeholder="{{ __('placeholder.Enter_password') }} *">
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
 

                        <div class="form-group">
                            <label class=" form-check">
                                <input id="merchant_registration_checkbox" name="policy" class="  form-check-input" type="checkbox"><span class=" ">I agree to <a href="#" class="text-primary">{{ settings()->name }}</a> Privacy Policy & Terms.</span>
                            </label>
                        </div>
                        <div class="form-group pt-2">
                            <button id="merchant_registration_submit" class="btn btn-block btn-primary" type="submit">Register My Account</button>
                        </div>

                    </div>
                    <div class="card-footer bg-white">
                        <p>Already member? <a href="{{ route('login') }}" class="text-primary">Login Here.</a></p>
                    </div>
                </div>
            </div>
            <div class="col-lg-7 footer-img">
                <img alt="" class="img-responsive margin-t-20 py-5" src="{{ static_asset('frontend/images/banner1.png') }}" width="100%"/>
            </div>
        </div>
    </div>
</form>
<!-- end signup form  -->
 
 @endsection

