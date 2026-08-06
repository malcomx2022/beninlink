<div id="add-to-wallet-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
    aria-hidden="false">
    <div class="modal-dialog modal-dialog-centered modal-lg" id="modalSize">
        <div class="modal-content rounded-xl p-0 b-0 ">
            <div class="panel panel-color panel-primary">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-title">{{ __('parcel.wallet_recharge') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <form action="{{ route('wallet.request.recharge') }}" method="post" id="recharge-form">
                                @csrf
                                <div class="form-group ">
                                    <label for="wallet_merchant_id">{{ __('parcel.merchant') }} <span
                                            class="text-danger">*</span></label>
                                    <select style="width: 100%" id="wallet_merchant_id" name="merchant_id"
                                        class="form-control select2 mt-1" data-url="{{ route('parcel.merchant.get') }}">
                                        <option value="" selected> {{ __('parcel.select') }}
                                            {{ __('merchant.title') }}</option>
                                    </select>
                                    @error('merchant_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="mb-2 mt-2">
                                    <label for="transaction_id">{{ __('parcel.transaction_id') }} </label>
                                    <input id="transaction_id" type="text" name="transaction_id"
                                        data-parsley-trigger="change" placeholder="{{ __('parcel.transaction_id') }}"
                                        autocomplete="off" class="form-control" value="{{ old('transaction_id') }}">
                                </div>
                                <div class="mb-2 mt-2">
                                    <label for="recharge_amount">{{ __('parcel.amount') }} <span
                                            class="text-danger">*</span></label>
                                    <input id="recharge_amount" type="number" name="amount"
                                        data-parsley-trigger="change" placeholder="{{ __('parcel.amount') }}"
                                        autocomplete="off" class="form-control" value="{{ old('amount') }}">
                                    @error('amount')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="mt-2 text-end">
                                    <button class="input-group-text btn btn-primary ml-0"
                                        type="submit">{{ __('parcel.add_to_wallet') }}</button>
                                </div>
                            </form>
                            <h5 class="text-uppercase mt-3 mb-1">{{ __('parcel.quick_add') }}</h5>
                            <p>{{ __('parcel.quickly_add_money_from_given_options_and_recharge_your_wallet') }}</p>
                            <div class="row">
                                <div class="col-6 col-xl-3 col-lg-4">
                                    <div class="card  mb-2">
                                        <label class="card-body h6 mb-0 text-center p-2 font-weight-bold quick-amount"
                                            data-amount="500" style="cursor:pointer">500</label>
                                    </div>
                                </div>
                                <div class="col-6 col-xl-3 col-lg-4">
                                    <div class="card  mb-2">
                                        <label class="card-body h6 mb-0 text-center p-2 font-weight-bold quick-amount"
                                            data-amount="1000" style="cursor:pointer">1000</label>
                                    </div>
                                </div>
                                <div class="col-6 col-xl-3 col-lg-4">
                                    <div class="card  mb-2">
                                        <label class="card-body h6 mb-0 text-center p-2 font-weight-bold quick-amount"
                                            data-amount="2000" style="cursor:pointer">2000</label>
                                    </div>
                                </div>
                                <div class="col-6 col-xl-3 col-lg-4">
                                    <div class="card  mb-2">
                                        <label class="card-body h6 mb-0 text-center p-2 font-weight-bold quick-amount"
                                            data-amount="3000" style="cursor:pointer">3000</label>
                                    </div>
                                </div>
                                <div class="col-6 col-xl-3 col-lg-4">
                                    <div class="card  mb-2">
                                        <label class="card-body h6 mb-0 text-center p-2 font-weight-bold quick-amount"
                                            data-amount="5000" style="cursor:pointer">5000</label>
                                    </div>
                                </div>
                                <div class="col-6 col-xl-3 col-lg-4">
                                    <div class="card  mb-2">
                                        <label class="card-body h6 mb-0 text-center p-2 font-weight-bold quick-amount"
                                            data-amount="10000" style="cursor:pointer">10000</label>
                                    </div>
                                </div>
                                <div class="col-6 col-xl-3 col-lg-4">
                                    <div class="card  mb-2">
                                        <label class="card-body h6 mb-0 text-center p-2 font-weight-bold quick-amount"
                                            data-amount="15000" style="cursor:pointer">15000</label>
                                    </div>
                                </div>
                                <div class="col-6 col-xl-3 col-lg-4">
                                    <div class="card  mb-2">
                                        <label class="card-body h6 mb-0 text-center p-2 font-weight-bold quick-amount"
                                            data-amount="20000" style="cursor:pointer">20000</label>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col-lg-6">
                            <img src="{{ static_asset('backend/images/default/payout/wallet.png') }}"
                                class="w-100" />
                        </div>
                    </div>


                </div>
            </div>
        </div>
    </div>
</div>
