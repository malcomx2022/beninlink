
@extends('auth.Layouts')
@section('title','Login')
@section('content')
    <div class="splash-container">
        <div class="card">
            <div class="row">
                <div class="col-lg-5">
                    <div class="card auth-boxs">
                        <div class="card-header text-center">
                            <a href="{{ url('/') }}" class="navbar-brand">
                                <img class="logo-img" src="{{ settings()->logo_image }}" class="logo" alt="logo">
                            </a>
                            <span class="splash-description">Please enter your user information.</span>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('login') }}">
                                @csrf
                                <div class="form-group">
                                    <input id="email" type="text"
                                        class="form-control form-control-lg @error('email') is-invalid @enderror"
                                        name="email" required autocomplete="email" autofocus
                                        placeholder="Enter Email or Mobile"
                                        @if (Cookie::has('useremail')) ? value="{{ Cookie::get('useremail') }}" : value="{{ old('email') }}" @endif>
                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <input id="password" type="password"
                                        class="form-control form-control-lg @error('password') is-invalid @enderror"
                                        name="password" required autocomplete="current-password" placeholder="Password"
                                        @if (Cookie::has('userpassword')) value="{{ Cookie::get('userpassword') }}" @endif>
                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label class="custom-control custom-checkbox">
                                        <input class="form-check-input" type="checkbox" name="remember" id="remember"
                                            {{ old('remember') ? 'checked' : '' }}
                                            @if (Cookie::has('useremail')) checked @endif>

                                        <label class="form-check-label" for="remember">
                                            {{ __('Remember Me') }}
                                        </label>
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg btn-block">Sign in</button>
                                @if (tenant())
                                    <div class="text-center p-2">
                                        <span><b>OR</b></span>
                                    </div>
                                    @if (globalSettings('facebook_status') || globalSettings('google_status'))
                                        <div class="row ">
                                            @if (globalSettings('facebook_status') == App\Enums\Status::ACTIVE)
                                                <div class="col-sm-6 m-auto  ">
                                                    <a href="{{ route('social.login', 'facebook') }}"
                                                        class="btn w-100 btn-social btn-primary mt-2" type="button"> <i
                                                            class="fab fa-facebook"></i> Facebook</a>
                                                </div>
                                            @endif
                                            @if (globalSettings('google_status') == App\Enums\Status::ACTIVE)
                                                <div class="col-sm-6 m-auto  ">
                                                    <a href="{{ route('social.login', 'google') }}"
                                                        class="btn  w-100 btn-social btn-primary mt-2" type="button"><i
                                                            class="fab fa-google"></i> Google</a>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                @endif
                                @if (env('DEMO') && env('DEMO') !== '')
                                    <div class="text-center p-2">
                                        <span><b>Demo Login</b></span>
                                    </div>
                                    <div class="row  ">
                                        @if (tenant())
                                            <div class="col-sm-6">
                                                <button type="button"
                                                    class="btn btn-primary btn-lg btn-block mt-2 demo-login-btn"
                                                    id="demo-admin" data-email="company@wemaxdevs.com"
                                                    data-password="12345678">Admin</button>
                                            </div>
                                            <div class="col-sm-6">
                                                <button type="button"
                                                    class="btn btn-primary btn-lg btn-block mt-2 demo-login-btn"
                                                    id="demo-branch" data-email="branch@wemaxdevs.com"
                                                    data-password="12345678">Branch</button>
                                            </div>
                                            <div class="col-sm-6">
                                                <button type="button"
                                                    class="btn btn-primary btn-lg btn-block mt-2 demo-login-btn"
                                                    id="demo-merchant" data-merchant="merchant"
                                                    data-email="merchant@wemaxdevs.com"
                                                    data-password="12345678">Merchant</button>
                                            </div>
                                        @else
                                            <div class="col-sm-6">
                                                <button type="button"
                                                    class="btn btn-primary btn-lg btn-block mt-2 demo-login-btn"
                                                    data-email="admin@wemaxdevs.com" data-password="12345678">Admin</button>
                                            </div>

                                            <div class="col-sm-6">
                                                <a class="btn btn-primary btn-lg btn-block mt-2"
                                                    href="{{ scheme_name() . Stancl\Tenancy\Database\Models\Domain::first()->domain }}"
                                                    target="_blank">Company Panel</a>
                                            </div>
                                        @endif

                                    </div>
                                @endif

                            </form>
                        </div>
                        <div class="card-footer bg-white p-0  ">
                            <div class="card-footer-item card-footer-item-bordered">
                                @if (tenant())
                                    <a href="{{ route('merchant.sign-up') }}" class="footer-link">Sign up here</a>
                            </div>
                        @else
                            <a href="{{ route('company.sign-up') }}" class="footer-link">Sign up here</a>
                        </div>
                        @endif
                        <div class="card-footer-item card-footer-item-bordered">
                            <a href="{{ route('password.request') }}" class="footer-link">Forgot Password</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7 footer-img">
                <img class="img-responsive margin-t-20 " src="{{ static_asset('images/default/we-courier-process.png') }}"
                    width="100%" />
            </div>
        </div>
    </div>
    </div>
@endsection
@push('styles')    
<style>
    .login-dashboard-main-wrapper {
        padding-top: 10% !important;
    }
</style>
@endpush
