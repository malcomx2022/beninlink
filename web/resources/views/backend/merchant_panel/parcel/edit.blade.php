@extends('backend.partials.master')
@section('title')
    {{ __('parcel.title') }} {{ __('levels.edit') }}
@endsection
@section('maincontent')
    <div class="container-fluid  dashboard-content">
        <!-- pageheader -->
        <div class="row">
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                <div class="page-header">
                    <div class="page-breadcrumb">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}"
                                        class="breadcrumb-link">{{ __('parcel.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('merchant-panel.parcel.index') }}"
                                        class="breadcrumb-link">{{ __('parcel.title') }}</a></li>
                                <li class="breadcrumb-item"><a href=""
                                        class="breadcrumb-link active">{{ __('levels.edit') }}</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="pageheader-title">{{ __('parcel.edit_parcel') }}</h2>
                        <form action="{{ route('merchant-panel.parcel.update', $parcel) }}" method="POST"
                            enctype="multipart/form-data" id="basicform">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="form-group col-12 col-md-6">
                                    <label for="shopID">{{ __('parcel.shop') }}</label>
                                    <select style="width: 100%" id="shopID" class="form-control" name="shop_id"
                                        data-url="{{ route('merchant-panel.parcel.merchant.shops') }}">
                                        <option value=""> {{ __('Select Shop') }}</option>
                                        @foreach ($shops as $shop)
                                            <option value="{{ $shop->id }}"
                                                {{ old('shop_id', $parcel->merchant_shop_id) == $shop->id ? 'selected' : '' }}>
                                                {{ $shop->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('shop_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror

                                    <input type="hidden" id="inside_city"
                                        value="{{ $merchant->cod_charges['inside_city'] }}" />
                                    <input type="hidden" id="sub_city"
                                        value="{{ $merchant->cod_charges['sub_city'] }}" />
                                    <input type="hidden" id="outside_city"
                                        value="{{ $merchant->cod_charges['outside_city'] }}" />


                                </div>
                                <div class="form-group col-12 col-md-6">
                                    <label for="pickup_phone">{{ __('parcel.pickup_phone') }}</label>
                                    <input id="pickup_phone" type="text" name="pickup_phone"
                                        data-parsley-trigger="change" placeholder="{{ __('parcel.pickup_phone') }}"
                                        autocomplete="off" class="form-control"
                                        value="{{ old('pickup_phone', $parcel->pickup_phone) }}" required="">
                                    @error('pickup_phone')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-12 col-md-6">
                                    <label for="pickup_address">{{ __('parcel.pickup_address') }}</label>
                                    <input id="pickup_address" type="text" name="pickup_address"
                                        data-parsley-trigger="change" placeholder="{{ __('parcel.pickup_address') }}"
                                        autocomplete="off" class="form-control"
                                        value="{{ old('pickup_address', $parcel->pickup_address) }}" required="">

                                    <input type="hidden" id="pickup_lat" name="pickup_lat" required=""
                                        value="{{ $parcel->pickup_lat }}">
                                    <input type="hidden" id="pickup_long" name="pickup_long" required=""
                                        value="{{ $parcel->pickup_long }}">


                                    @error('pickup_address')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group col-12 col-md-6">
                                    <label for="cash_collection">{{ __('parcel.cash_collection') }} </label> <span
                                        class="text-danger">*</span>
                                    <div class="form-control-wrap">
                                        <input type="text" class="form-control cash-collection" id="cash_collection"
                                            value="{{ old('cash_collection', $parcel->cash_collection) }}"
                                            name="cash_collection"
                                            placeholder="{{ __('parcel.Cash_amount_including_delivery_charge') }}"
                                            required="">
                                        @error('cash_collection')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                                <div class="form-group col-12 col-md-6">
                                    <label for="selling_price">{{ __('parcel.selling_price') }} </label>
                                    <div class="form-control-wrap">
                                        <input type="text" class="form-control cash-collection" id="selling_price"
                                            value="{{ old('selling_price', $parcel->selling_price) }}" name="selling_price"
                                            placeholder="{{ __('parcel.Selling_price_of_parcel') }}">
                                        @error('selling_price')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group col-12 col-md-6">
                                    <label for="opening_balance">{{ __('parcel.invoice') }}</label>
                                    <input id="invoice_no" type="text" name="invoice_no"
                                        data-parsley-trigger="change"
                                        placeholder="{{ __('parcel.enter_invoice_number') }}" autocomplete="off"
                                        class="form-control" value="{{ old('invoice_no', $parcel->invoice_no) }}">
                                    @error('invoice_no')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group col-12 col-md-6">
                                    <label for="merchant">{{ __('parcel.category') }}</label> <span
                                        class="text-danger">*</span>
                                    <select style="width: 100%" id="category_id" class="form-control select2"
                                        name="category_id" class="form-control @error('category_id') is-invalid @enderror"
                                        data-url="{{ route('parcel.deliveryCategory.deliveryWeight') }}" required="">
                                        <option value=""> {{ __('menus.select') }} {{ __('parcel.category') }}
                                        </option>
                                        @foreach ($deliveryCategoryCharges as $deliveryCategoryCharge)
                                            <option value="{{ $deliveryCategories[$deliveryCategoryCharge]->id }}"
                                                {{ old('category_id', $parcel->category_id) == $deliveryCategories[$deliveryCategoryCharge]->id ? 'selected' : '' }}>
                                                {{ $deliveryCategories[$deliveryCategoryCharge]->title }}</option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group col-12 col-md-6" id="categoryWeight">
                                    <label for="weightID">{{ __('parcel.weight') }}</label> <span
                                        class="text-danger">*</span>
                                    <select style="width: 100%" id="weightID" class="form-control select2"
                                        name="weight">
                                        <option value=""> {{ __('menus.select') }} {{ __('parcel.weight') }}
                                        </option>
                                        @if (!blank($deliveryCharges))
                                            @foreach ($deliveryCharges as $deliveryCharge)
                                                <option value="{{ $deliveryCharge->weight ?? 0 }}"
                                                    {{ old('weight', $parcel->weight) == $deliveryCharge->weight ? 'selected' : '' }}>
                                                    {{ $deliveryCharge->weight }} {{ $deliveryCharge->category->title }}
                                                </option>
                                            @endforeach
                                        @endif

                                    </select>
                                    @error('weight')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group col-12 col-md-6">
                                    <label for="delivery_type_id">{{ __('parcel.delivery_type') }}</label> <span
                                        class="text-danger">*</span>
                                    <select style="width: 100%" class="form-control select2" id="delivery_type_id"
                                        name="delivery_type_id" required="">
                                        <option value=""> {{ __('menus.select') }} {{ __('parcel.delivery_type') }}
                                        </option>
                                        @foreach ($deliveryTypes as $key => $status)
                                            <option
                                                @if ($status->key == 'same_day') value="1" @if ($parcel->delivery_type_id == 1) selected @endif
                                            @elseif($status->key == 'next_day') value="2"
                                                @if ($parcel->delivery_type_id == 2) selected @endif
                                            @elseif($status->key == 'sub_city') value="3"
                                                @if ($parcel->delivery_type_id == 3) selected @endif
                                            @elseif($status->key == 'outside_City') value="4"
                                                @if ($parcel->delivery_type_id == 4) selected @endif @endif
                                                >
                                                {{ __('deliveryType.' . $status->key) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('delivery_type_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group col-12 col-md-6">
                                    <label for="customer_name">{{ __('parcel.customer_name') }}</label> <span
                                        class="text-danger">*</span>
                                    <input id="customer_name" type="text" name="customer_name"
                                        data-parsley-trigger="change" placeholder="{{ __('parcel.customer_name') }}"
                                        autocomplete="off" class="form-control"
                                        value="{{ old('customer_name', $parcel->customer_name) }}" required="">
                                    @error('customer_name')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-12 col-md-6">
                                    <label for="phone">{{ __('parcel.customer_phone') }}</label> <span
                                        class="text-danger">*</span>
                                    <input id="phone" type="text" name="customer_phone"
                                        data-parsley-trigger="change" placeholder="{{ __('parcel.customer_phone') }}"
                                        autocomplete="off" class="form-control"
                                        value="{{ old('customer_phone', $parcel->customer_phone) }}" required="">
                                    @error('customer_phone')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-group col-12 col-md-6">
                                    <label for="customer_address">{{ __('parcel.customer_address') }}</label> <span
                                        class="text-danger">*</span>
                                    <input type="hidden" id="lat" name="lat" required=""
                                        value="{{ $parcel->customer_lat }}">
                                    <input type="hidden" id="long" name="long" required=""
                                        value="{{ $parcel->customer_long }}">
                                    <div class="main-search-input-item location location-search">
                                        <div id="autocomplete-container" class="form-group random-search">
                                            <input id="autocomplete-input" type="text" name="customer_address"
                                                class="recipe-search2 form-control" placeholder="Location Here!"
                                                required="">
                                            <a href="javascript:void(0)" class="submit-btn btn current-location"
                                                id="locationIcon" onclick="getLocation()">
                                                <i class="fa fa-crosshairs"></i>
                                            </a>
                                            @error('customer_address')
                                                <small class="text-danger mt-2">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="">
                                        <div id="googleMap" class="custom-map"></div>
                                    </div>
                                </div>
                                <div class="form-group col-12 col-md-6">
                                    <label for="note">{{ __('parcel.note') }}</label>
                                    <textarea id="note" name="note" class="form-control" rows="10">{{ old('note', $parcel->note) }}</textarea>
                                    @error('note')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                @if (SettingHelper('fragile_liquid_status') == \App\Enums\Status::ACTIVE)
                                    <div class="col-md-6 mt-2">
                                        <label class="form-label"
                                            for="fv-full-name">{{ __('parcel.liquid_check_label') }}</label>
                                        <div class="row pt-1">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <div class="preview-block">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input"
                                                                id="fragileLiquid"
                                                                data-amount="{{ SettingHelper('fragile_liquid_charge') }}"
                                                                @if ($parcel->liquid_fragile_amount) checked @endif
                                                                name="fragileLiquid" onclick="processCheck(this);">
                                                            <label class="custom-control-label"
                                                                for="fragileLiquid">{{ __('parcel.liquid_fragile') }}</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="form-group col-md-6" id="PackagingID">
                                    <label for="packaging_id">{{ __('parcel.packaging') }}</label>
                                    <select style="width: 100%" id="packaging_id" class="form-control"
                                        name="packaging_id">
                                        <option value=""> {{ __('menus.select') }} {{ __('parcel.packaging') }}
                                        </option>
                                        @foreach ($packagings as $packaging)
                                            <option data-packagingamount="{{ $packaging->price }}"
                                                value="{{ $packaging->id }}"
                                                {{ old('packaging_id', $parcel->packaging_id) == $packaging->id ? 'selected' : '' }}>
                                                {{ $packaging->name }} ( {{ number_format($packaging->price, 2) }} Tk)
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('packaging_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <input type="hidden" id="merchant_id" name="merchant_id"
                                    value="{{ $merchant->id }}" />
                                <input type="hidden" id="merchantVat" name="vat_tex"
                                    value="{{ $parcel->vat ?? 0 }}" />
                                <input type="hidden" id="merchantCodCharge" name="cod_charge"
                                    value="{{ $parcel->cod_charge ?? 0 }}" />
                                <input type="hidden" id="chargeDetails" name="chargeDetails" value="" />
                            </div>
                            <div class="row">
                                <div class="form-group col-12">
                                    <label class="custom-control custom-checkbox">
                                        <input type="checkbox" @if ($parcel->parcel_bank) checked @endif
                                            name="parcel_bank" class="custom-control-input"><span
                                            class="custom-control-label">{{ __('levels.parcel_bank') }}</span>
                                    </label>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12  d-flex justify-content-end">
                                    <button type="submit"
                                        class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                    <a href="{{ route('parcel.index') }}"
                                        class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="pageheader-title">{{ __('parcel.charge_details') }}</h2>

                        <ul class="list-group">
                            <li class="list-group-item profile-list-group-item">
                                <span class="float-left font-weight-bold">{{ __('levels.title') }}</span>
                                <span class="float-right">{{ __('levels.amount') }}</span>
                            </li>
                            <li class="list-group-item profile-list-group-item">
                                <span class="float-left font-weight-bold">{{ __('parcel.Cash_Collection') }}</span>
                                <span class="float-right"
                                    id="totalCashCollection">{{ $parcel->cash_collection ?? '0.00' }}</span>
                            </li>
                            <li class="list-group-item profile-list-group-item">
                                <span class="float-left font-weight-bold">{{ __('parcel.Delivery_Charge') }}</span>
                                <span class="float-right"
                                    id="deliveryChargeAmount">{{ $parcel->delivery_charge ?? '0.00' }}</span>
                            </li>
                            <li class="list-group-item profile-list-group-item">
                                <span class="float-left font-weight-bold">{{ __('reports.COD_Charge') }}</span>
                                <span class="float-right"
                                    id="codChargeAmount">{{ $parcel->cod_amount ?? '0.00' }}</span>
                            </li>


                            <li class="list-group-item profile-list-group-item hideShowLiquidFragile">
                                <span class="float-left font-weight-bold">{{ __('parcel.Liquid/Fragile_Charge') }}</span>
                                <span class="float-right"
                                    id="liquidFragileAmount">{{ $parcel->liquid_fragile_amount ?? '0.00' }}</span>
                            </li>
                            <li class="list-group-item profile-list-group-item" id="packagingShow">
                                <span class="float-left font-weight-bold">{{ __('reports.P.Charge') }}</span>
                                <span class="float-right"
                                    id="packagingAmount">{{ $parcel->packaging_amount ?? '0.00' }}</span>
                            </li>
                            <li class="list-group-item profile-list-group-item">
                                <span class="float-left font-weight-bold">{{ __('parcel.Total_Charge') }}</span>
                                <span class="float-right"
                                    id="totalDeliveryChargeAmount">{{ $parcel->total_delivery_amount ?? '0.00' }}</span>
                            </li>
                            <li class="list-group-item profile-list-group-item">
                                <span class="float-left font-weight-bold">{{ __('parcel.Vat') }}</span>
                                <span class="float-right" id="VatAmount">{{ $parcel->vat_amount ?? '0.00' }}</span>
                            </li>

                            <li class="list-group-item profile-list-group-item">
                                <span class="float-left font-weight-bold">{{ __('parcel.Net_Payable') }}</span>
                                <span class="float-right"
                                    id="netPayable">{{ $parcel->total_delivery_amount + $parcel->vat_amount }}</span>
                            </li>


                            <li class="list-group-item profile-list-group-item">
                                <span class="float-left font-weight-bold">{{ __('parcel.Current_payable') }}</span>
                                <span class="float-right"
                                    id="currentPayable">{{ $parcel->current_payable ?? '0.00' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection()
@push('styles')
    <style>
        .main-search-input-item {
            flex: 1;
            margin-top: 3px;
            position: relative;
        }

        #autocomplete-container,
        #autocomplete-input {
            position: relative;
            z-index: 101;
        }

        .main-search-input input,
        .main-search-input input:focus {
            font-size: 16px;
            border: none;
            background: #fff;
            margin: 0;
            padding: 0;
            height: 44px;
            line-height: 44px;
            box-shadow: none;
        }

        .input-with-icon i,
        .main-search-input-item.location a {
            padding: 5px 10px;
            z-index: 101;
        }

        .main-search-input-item.location a {
            position: absolute;
            right: -50px;
            top: 40%;
            transform: translateY(-50%);
            color: #999;
            padding: 10px;
        }

        .current-location {
            margin-right: 50px;
            margin-top: 5px;
            color: #FFCC00 !important;
        }

        .custom-map {
            width: 100%;
            height: 17rem;
        }

        .pac-container {
            width: 295px;
            position: absolute;
            left: 0px !important;
            top: 28px !important;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        var deliverChargeUrl = '{{ route('merchant-panel.parcel.deliveryCharge.get') }}';
        var merchantData = @json($merchant);
        var category_id = '{{ $parcel->category_id }}';
        var packaging_id = '{{ $parcel->packaging_id }}';
        var liquid_fragile_amount = '{{ $parcel->liquid_fragile_amount }}';

        var mapLat = '{{ $parcel->customer_lat }}';
        var mapLong = '{{ $parcel->customer_long }}';
    </script>
    <script type="text/javascript" src="{{ static_asset('backend/js/parcel/edit-map-current.js') }}"></script>
    <script async
        src="https://maps.googleapis.com/maps/api/js?key={{ googleMapSettingKey() }}&libraries=places&callback=initMap">
    </script>
    <script src="{{ static_asset('backend/js/merchant_panel/parcel/edit.js') }}"></script>
@endpush
