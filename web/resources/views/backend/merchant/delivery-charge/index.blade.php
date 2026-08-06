@section('title')
    {{ __('merchant.title') }} {{ __('merchant.delivery_charge') }} {{ __('levels.list') }}
@endsection
@extends('backend.merchant.view')
@section('backend.merchant.layout.list')
    <div class="card">
        <div class="row pl-4 pr-4 pt-4">
            <div class="col-6">
                <p class="h3">{{ __('merchant.delivery_charge') }} {{ __('levels.list') }}</p>
            </div>
            @if(hasPermission('merchant_delivery_charge_create') == true )
            <div class="col-6">
                <a href="{{route('merchant.deliveryCharge.create',$singleMerchant->id)}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
            </div>
            @endif
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table   " style="width:100%">
                    <thead>
                    <tr>
                        <th>{{ __('levels.id') }}</th>
                        <th>{{ __('merchant.category') }}</th>
                        <th>{{ __('merchant.weight') }}</th>
                        <th>{{ __('merchant.same_day') }}</th>
                        <th>{{ __('merchant.next_day') }}</th>
                        <th>{{ __('merchant.sub_city') }}</th>
                        <th>{{ __('merchant.outside_city') }}</th>
                        <th>{{ __('levels.status') }}</th>
                        @if(
                         hasPermission('merchant_delivery_charge_update') == true ||
                         hasPermission('merchant_delivery_charge_delete') == true
                         )
                        <th>{{ __('levels.actions') }}</th>
                        @endif
                    </tr>
                    </thead>
                    <tbody>
                    @php $i=1; @endphp
                    @foreach($merchantDeliveryCharges as $merchantDeliveryCharge)
                        <tr>
                            <td>{{$i++}}</td>
                            <td>{{$merchantDeliveryCharge->deliveryCharge->category->title}}</td>
                            <td>{{$merchantDeliveryCharge->deliveryCharge->weight ?? 0}}</td>
                            <td>{{$merchantDeliveryCharge->same_day}}</td>
                            <td>{{$merchantDeliveryCharge->next_day}}</td>
                            <td>{{$merchantDeliveryCharge->sub_city}}</td>
                            <td>{{$merchantDeliveryCharge->outside_city}}</td>
                            <td>
                                {!! $merchantDeliveryCharge->my_status !!}
                            </td>
                            @if(
                            hasPermission('merchant_delivery_charge_update') == true ||
                            hasPermission('merchant_delivery_charge_delete') == true
                            )
                                <td>
                                    <div class="row">
                                        <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                        <div class="dropdown-menu">
                                            @if( hasPermission('merchant_delivery_charge_update') == true   )
                                            <a href="{{route('merchant.deliveryCharge.edit',[$singleMerchant->id,$merchantDeliveryCharge])}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                            @endif
                                            @if( hasPermission('merchant_delivery_charge_delete') == true  )
                                            <form action="{{route('merchant.deliveryCharge.delete',[$singleMerchant->id,$merchantDeliveryCharge])}}" method="POST" id="delete" data-title="{{ __('delete.delivery_charge') }}">
                                                @method('DELETE')
                                                @csrf
                                                <button type="submit"  class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i> {{ __('levels.delete') }}</button>
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
