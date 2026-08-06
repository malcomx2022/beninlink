<div class="row">
    <div class="col-lg-6"> 
        <form action="{{ route('merchant-panel.my.wallet.recharge.add') }}" method="post" id="recharge-form">
            @csrf
            <div class="cbm-input-group mb-2">
                <label for="recharge_amount">{{ __('levels.amount') }} <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input id="recharge_amount" type="number" name="amount" data-parsley-trigger="change"
                        placeholder="{{ __('levels.amount') }}" autocomplete="off" class="form-control"
                        value="{{ old('amount') }}">
                    <div class="input-group-append">
                        <button class="input-group-text btn btn-primary ml-0"
                            type="submit">{{ __('parcel.paynow') }}</button>
                    </div>
                </div>
                @error('width')
                    <small class="text-danger mt-2">{{ $message }}</small>
                @enderror
            </div>
        </form>
        <h5 class="text-uppercase mt-3 mb-1">{{ __('parcel.quick_add') }}</h5>
        <p>{{ __('parcel.quickly_add_money_from_given_options_and_recharge_your_wallet') }}</p>
        <div class="row">
            <div class="col-6 col-xl-3 col-lg-4">
                <div class="card  mb-2">
                    <label class="card-body h6 mb-0 text-center p-2 font-weight-bold quick-amount" data-amount="500"
                        style="cursor:pointer">500</label>
                </div>
            </div>
            <div class="col-6 col-xl-3  col-lg-4">
                <div class="card  mb-2">
                    <label class="card-body h6 mb-0 text-center p-2 font-weight-bold quick-amount" data-amount="1000"
                        style="cursor:pointer">1000</label>
                </div>
            </div>
            <div class="col-6 col-xl-3 col-lg-4">
                <div class="card  mb-2">
                    <label class="card-body h6 mb-0 text-center p-2 font-weight-bold quick-amount" data-amount="2000"
                        style="cursor:pointer">2000</label>
                </div>
            </div>
            <div class="col-6 col-xl-3 col-lg-4">
                <div class="card  mb-2">
                    <label class="card-body h6 mb-0 text-center p-2 font-weight-bold quick-amount" data-amount="3000"
                        style="cursor:pointer">3000</label>
                </div>
            </div>
            <div class="col-6 col-xl-3 col-lg-4">
                <div class="card  mb-2">
                    <label class="card-body h6 mb-0 text-center p-2 font-weight-bold quick-amount" data-amount="5000"
                        style="cursor:pointer">5000</label>
                </div>
            </div>
            <div class="col-6 col-xl-3 col-lg-4">
                <div class="card  mb-2">
                    <label class="card-body h6 mb-0 text-center p-2 font-weight-bold quick-amount" data-amount="10000"
                        style="cursor:pointer">10000</label>
                </div>
            </div>
            <div class="col-6 col-xl-3 col-lg-4">
                <div class="card  mb-2">
                    <label class="card-body h6 mb-0 text-center p-2 font-weight-bold quick-amount" data-amount="15000"
                        style="cursor:pointer">15000</label>
                </div>
            </div>
            <div class="col-6 col-xl-3 col-lg-4">
                <div class="card  mb-2">
                    <label class="card-body h6 mb-0 text-center p-2 font-weight-bold quick-amount" data-amount="20000"
                        style="cursor:pointer">20000</label>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <img src="{{ static_asset('backend/images/default/payout/wallet.png') }}" class="w-100" />
    </div>
</div>
