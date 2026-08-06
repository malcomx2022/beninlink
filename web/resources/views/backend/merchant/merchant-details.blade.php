@extends('backend.merchant.view')

@section('backend.merchant.layout.list')
<ul class="list-group">
    <li class="list-group-item profile-list-group-item">
        <span class="float-left font-weight-bold">{{ __('levels.business_name') }}</span>
        <span class="float-right">{{ $singleMerchant->business_name }}</span>
    </li>
    <li class="list-group-item profile-list-group-item">
        <span class="float-left font-weight-bold">{{ __('levels.hub') }}</span>
        <span class="float-right">{{$singleMerchant->user->hub->name}}</span>
    </li>
    <li class="list-group-item profile-list-group-item">
        <span class="float-left font-weight-bold">{{ __('levels.unique_id') }}</span>
        <span class="float-right">{{ $singleMerchant->merchant_unique_id }}</span>
    </li>

    <li class="list-group-item profile-list-group-item">
        <span class="float-left font-weight-bold">{{ __('merchant.opening_balance') }}</span>
        <span class="float-right">{{ $singleMerchant->opening_balance }}</span>
    </li>
    <li class="list-group-item profile-list-group-item">
        <span class="float-left font-weight-bold">{{ __('merchant.vat') }}</span>
        <span class="float-right">{{ $singleMerchant->vat }}</span>
    </li>
    <li class="list-group-item profile-list-group-item">
        <span class="float-left font-weight-bold">{{ __('merchant.cod_charges') }}</span>
        <span class="float-right">{{ $singleMerchant->my_cod_charges}}</span>
    </li>


    <li class="list-group-item profile-list-group-item justify-content-center">
        <span class="float-left font-weight-bold">{{ __('levels.nid') }}</span>
        <span class="float-right profile-list-group-item-addresss"><img src="{{ $singleMerchant->nid }}" width="100px" /></span>
    </li>

    <li class="list-group-item profile-list-group-item justify-content-center">
        <span class="float-left font-weight-bold">{{ __('levels.trade_license') }}</span>
        <span class="float-right profile-list-group-item-addresss"><img src="{{ $singleMerchant->trade }}" width="100px" /></span>
    </li>

    <li class="list-group-item profile-list-group-item">
        <span class="float-left font-weight-bold">{{ __('levels.address') }}</span>
        <span class="float-right profile-list-group-item-addresss">{{ $singleMerchant->user->address }}</span>
    </li>
    <li class="list-group-item profile-list-group-item">
        <span class="float-left font-weight-bold">{{ __('levels.payment_period') }}</span>
        <span class="float-right profile-list-group-item-addresss">{{ $singleMerchant->payment_period }}</span>
    </li>

    <li class="list-group-item profile-list-group-item">
        <span class="float-left font-weight-bold">{{ __('levels.status') }}</span>
        <span class="float-right profile-list-group-item-addresss">{!! $singleMerchant->user->my_status  !!} </span>
    </li>
</ul>
@endsection()
 