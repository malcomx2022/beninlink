
@extends('auth.Layouts')
@section('title','Reset')
@section('content')

    <!-- forgot password  -->
    <div class="splash-container">
        <div class="card">
         
            <div class="card-body">
                <div class="row">
                    <div class="col-5">
                        <div class="card-header text-center border-bottom-none">
                            <a href="{{url('/')}}" class="navbar-brand">
                                <img class="logo-img" src="{{ settings()->logo_image }}"  class="logo" alt="logo">
                            </a>
                            <span class="splash-description">Reset Password</span>
                        </div>
                        <form method="POST" action="{{ route('password.update') }}">
                            @csrf 
                            <input type="hidden" name="token" value="{{ $token }}"> 
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>
        
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
        
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
        
                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
        
                            <div class="form-group">
                                <label for="password-confirm">Confirm Password</label>
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                            </div>
        
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Reset Password') }}
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-7 footer-img">
                        <img class="img-responsive my-5 " src="{{ static_asset('images/default/we-courier-process.png') }}" width="100%" />
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <span>Don't have an account? <a href="{{ route('register') }}">Sign Up</a> | <a href="{{ route('login') }}">Sign In</a></span>
            </div>
        </div>
    </div>
    <!-- end forgot password  --> 
@endsection

