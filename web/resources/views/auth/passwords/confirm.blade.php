
@extends('auth.Layouts')
@section('title', __('levels.confirm_password'))
@section('content')
    <!-- confirm password  -->
    <div class="splash-container">
        <div class="card">
            <div class="card-header text-center">
                <a href="{{url('/')}}" class="navbar-brand">
                    <img class="logo-img" src="{{ settings()->logo_image }}"  class="logo" alt="logo">
                </a>
                <span class="splash-description">{{ __('levels.confirm_password') }}</span>
            </div>
            <div class="card-body">
                {{ __('auth.confirm_password_hint') }}
                <form method="POST" action="{{ route('password.confirm') }}">
                    @csrf

                    <div class="row mb-3">
                        <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>
                        <div class="col-md-6">
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-0">
                        <div class="col-md-8 offset-md-4">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Confirm Password') }}
                            </button>

                            @if (Route::has('password.request'))
                                <a class="btn btn-link" href="{{ route('password.request') }}">
                                    {{ __('Forgot Your Password?') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <span>{{ __('auth.no_account') }} <a href="{{ route('register') }}">{{ __('auth.sign_up') }}</a> | <a href="{{ route('login') }}">{{ __('auth.sign_in') }}</a></span>
            </div>
        </div>
    </div>
    <!-- end confirm password  -->
@endsection
