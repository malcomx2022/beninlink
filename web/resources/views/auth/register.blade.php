@extends('auth.Layouts')
@section('title','Register')
@section('content')
<!-- signup form  -->
<form class="splash-container" method="POST" action="{{ route('register') }}">
    @csrf
    <div class="card">
        <div class="row">
            <div class="col-lg-5">
                <div class="card auth-boxs">
                    <div class="card-header text-center">
                        <a href="{{ url('/') }}" class="navbar-brand">
                            <img src="{{ settings()->logo_image }}" class="logo" />
                        </a>
                        <h3 class="mb-1">Registrations Form</h3>
                        <p>Please enter your user information.</p>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <input id="name" type="text"
                                class="form-control form-control-lg @error('name') is-invalid @enderror" name="name"
                                value="{{ old('name') }}" required autocomplete="name" autofocus
                                placeholder="Username">
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <input id="email" type="email"
                                class="form-control form-control-lg @error('email') is-invalid @enderror" name="email"
                                value="{{ old('email') }}" required autocomplete="email" placeholder="Email Address">
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <input id="password" type="password"
                                class="form-control form-control-lg @error('password') is-invalid @enderror"
                                name="password" required autocomplete="new-password" placeholder="Password">
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <input id="password-confirm" type="password" class="form-control form-control-lg"
                                name="password_confirmation" required autocomplete="new-password"
                                placeholder="Confirm Password">
                        </div>
                        <div class="form-group pt-2">
                            <button class="btn btn-block btn-primary" type="submit">Register My Account</button>
                        </div>
                        <div class="form-group">
                            <label class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox"><span
                                    class="custom-control-label">By creating an account, you agree the <a
                                        href="#">terms and conditions</a></span>
                            </label>
                        </div>
                        <div class="form-group row pt-0">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 mb-2">
                                <button class="btn btn-block btn-social btn-facebook " type="button">Facebook</button>
                            </div>
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                <button class="btn  btn-block btn-social btn-twitter" type="button">Twitter</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <p>Already member? <a href="{{ route('login') }}" class="text-secondary">Login Here.</a></p>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
            </div>
        </div>
    </div>
</form>
<!-- end signup form  -->
 @endsection