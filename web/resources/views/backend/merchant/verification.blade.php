@include('backend.partials.header')
    <!-- email verification password  -->
    <div class="splash-container">
        <div class="card">
            <div class="row">
                <div class="col-lg-5">
                    <div class="card-header text-center">
                        <a href="{{url('/')}}">
                            <img class="logo-img" src="{{ settings()->logo_image }}" alt="logo">
                        </a>
                        <span class="splash-description">Confirm OTP</span>
                    </div>
                    <div class="card-body">
                        @if (\Session::has('success'))
                            <div class="alert alert-success">
                                <p class="text-center">{!! \Session::get('success') !!}</p>
                            </div>
                        @elseif (\Session::has('warning'))
                            <div class="alert alert-warning">
                                <p class="text-center">{!! \Session::get('warning') !!}</p>
                            </div>
                        @endif
                        <form method="POST" action="{{route('merchant.otp-verification')}}">
                            @csrf
                                <p>Your Merchant ID:{{ session('merchant_id') }}</p>
                                <p class="text-center">Check Your Phone. We have sent you a 5 digit OTP. Please confirm that OTP to verify you phone number for registration. <br><strong>{{ substr(session('mobile'), 0, 2).'********'.substr(session('mobile'), -2)}}<br>
                             </strong> <br>
                            </p>
                            <div class="form-group">
                                <input type="hidden" name="mobile" value="{{session('mobile')}}">
                                <input id="otp" type="number" class="form-control form-control-lg @error('otp') is-invalid @enderror" name="otp" value="{{ old('otp') }}" required autocomplete="otp" autofocus placeholder="OTP *">
                                @error('otp')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-block btn-primary btn-xl">Submit</button>
                        </form>
                        <form id="resend" method="POST" action="{{route('merchant.resend-otp')}}">
                            @csrf
                            <input type="hidden" name="mobile" value="{{session('mobile')}}">
                            <p class="text-center pt-4">Didn't get? <a href="javascript:$('#resend').submit();" class="text-primary">Resend Code!</a></p>
                        </form>
                    </div>
                    <div class="card-footer bg-white ">
                        <p class="text-center">Already member? <a href="{{ route('login') }}" class="text-primary">Login Here.</a></p>
                    </div>
                </div>
                <div class="col-lg-7 footer-img">
                    <img class="img-responsive mt-5 " src="{{ static_asset('images/default/we-courier-process.png') }}" width="100%" />
                </div>
            </div>


        </div>
    </div>
@include('backend.partials.footer')
