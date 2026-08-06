@section('title')
    {{ __('merchant.title') }} {{ __('merchantshops.title') }} {{ __('levels.list') }}
@endsection
@extends('backend.merchant.view')
@section('backend.merchant.layout.list')
    <div class="card">
        <div class="row pl-4 pr-4 pt-4">
            <div class="col-6">
                <p class="h3">{{ __('merchantshops.title') }} {{ __('levels.list') }}</p>
            </div>
            @if(hasPermission('merchant_shop_create'))
                <div class="col-6">
                    <a href="{{route('merchant.shops.create',$singleMerchant->id)}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                </div>
            @endif
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table   " style="width:100%">
                    <thead>
                    <tr>
                        <th>{{ __('levels.id') }}</th>
                        <th>{{ __('merchantshops.name') }}</th>
                        <th>{{ __('merchantshops.contact') }}</th>
                        <th>{{ __('merchantshops.address') }}</th>
                        <th>{{ __('levels.status') }}</th>
                        @if(hasPermission('merchant_shop_update') || hasPermission('merchant_shop_delete') )
                        <th>{{ __('levels.actions') }}</th>
                        @endif
                    </tr>
                    </thead>
                    <tbody>
                    @php $i=1; @endphp
                    @foreach($merchant_shops as $shop)
                        <tr>
                            <td>{{$i++}}</td>
                            <td>{{$shop->name}}</td>
                            <td>{{$shop->contact_no}}</td>
                            <td>{{$shop->address}}</td>
                            <td>
                                @if($shop->status == \App\Enums\Status::ACTIVE)
                                    <span class="badge badge-pill badge-success mt-2">{{ __('merchantshops.active') }}</span>
                                @else
                                    <span class="badge badge-pill badge-danger mt-2">{{ __('merchantshops.inactive') }}</span>
                                @endif
                                @if($shop->default_shop == \App\Enums\Status::ACTIVE)
                                        <span class="badge badge-pill badge-primary mt-2">{{ __('merchantshops.default') }}</span>
                                    @else
                                        <a href="{{ route('merchant.shops.default',['merchant_id' => $shop->merchant_id,'id' => $shop->id]) }}" class=" default_shop_button mt-2">{{ __('merchantshops.add_default') }}</a>
                                    @endif

                            </td>
                            @if(hasPermission('merchant_shop_update') || hasPermission('merchant_shop_delete') )
                                <td>
                                    <div class="row">
                                        <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                        <div class="dropdown-menu">
                                            @if(hasPermission('merchant_shop_update') )
                                                <a href="{{route('merchant.shops.edit',$shop->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                            @endif
                                            @if(hasPermission('merchant_shop_delete') )
                                                <form id="delete" value="Test" action="{{route('merchant.shops.delete',$shop->id)}}" method="POST" data-title="{{ __('delete.shop') }}">
                                                    @method('DELETE')
                                                    @csrf
                                                    <input type="hidden" name="" value="Merchant Shop" id="deleteTitle">
                                                    <button type="submit" class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i> {{ __('levels.delete') }}</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>

                </table>
            </div>
        </div>
    </div>
@endsection()
