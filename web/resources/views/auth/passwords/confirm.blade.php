
@extends('auth.Layouts')
@section('title','Confirm Password')
@section('content')
    <!-- confirm password  -->
    <div class="splash-container">
        <div class="card">
            <div class="card-header text-center">
                <a href="{{url('/')}}" class="navbar-brand">
                    <img class="logo-img" src="{{ settings()->logo_image }}"  class="logo" alt="logo">
                </a>
                <span class="splash-description">Confirm Password</span>
            </div>
            <div class="card-body">
                {{ __('Please confirm your password before continuing.') }}
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
                <span>Don't have an account? <a href="{{ route('register') }}">Sign Up</a> | <a href="{{ route('login') }}">Sign In</a></span>
            </div>
        </div>
    </div>
    <!-- end confirm password  -->
@endsection
