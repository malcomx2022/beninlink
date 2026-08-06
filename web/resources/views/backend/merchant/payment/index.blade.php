@extends('backend.merchant.view')
@section('title')
    {{ __('merchant.title') }} {{ __('merchant.payment_account') }} {{ __('levels.list') }}
@endsection
@section('backend.merchant.layout.list')
    <div class="card">
        <div class="row pl-4 pr-4 pt-4">
            <div class="col-10">
                <p class="h3">{{ __('merchant.payment_info') }}  </p>
            </div>
            @if(hasPermission('merchant_payment_create') == true )
            <div class="col-2">
                    <a href="{{route('merchant.payment.add',$singleMerchant->id)}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top"  title="{{ __('levels.add') }}"  >  <i class="fa fa-plus"></i>  </a>
            </div>
            @endif
        </div>
        <div class="card-body pl-4">
            <div class="table-responsive">
                <table class="table    " style="width:100%">
                    <thead>
                    <tr>
                        <th>{{ __('levels.id') }}</th>
                        <th>{{ __('merchant.payment_method') }}</th>
                        <th>{{ __('merchant.account_info') }}</th>
                        <th>{{ __('levels.status') }}</th>
                        @if(hasPermission('merchant_payment_update') == true || hasPermission('merchant_payment_delete') == true )
                        <th>{{ __('levels.actions') }}</th>
                        @endif
                    </tr>
                    </thead>
                    <tbody>
                    @php $i=1; @endphp
                    @foreach($payments as $payment)
                        <tr>
                            <td>{{$i++}}</td>
                            <td>{{__('merchant.'.$payment->payment_method)}}</td>
                            <td class="merchantpayment">
                                <p>{{$payment->bank_name}}</p>
                                <p>{{$payment->holder_name}}</p>
                                <p>{{$payment->account_no}}</p>
                                <p>{{$payment->branch_name}}</p>
                                <p>{{$payment->routing_no}}</p>
                                <p>{{ $payment->mobile_company}}  </p>
                                <p>{{$payment->mobile_no}}</p>
                                <p>{{$payment->account_type}}</p>
                                @if ($payment->payment_method == \App\Enums\Merchant_panel\PaymentMethod::cash)
                                    <p>{{__('merchant.'.$payment->payment_method)}}</p>
                                @endif
                            </td>
                            <td>
                                @if($payment->status == \App\Enums\Status::ACTIVE)
                                    <span class="badge badge-pill badge-success">{{ __('merchantshops.active') }}</span>
                                @else
                                    <span class="badge badge-pill badge-danger">{{ __('merchantshops.inactive') }}</span>
                                @endif
                            </td>
                            @if(
                                hasPermission('merchant_payment_update') == true ||
                                hasPermission('merchant_payment_delete') == true
                            )
                            <td>
                                <div class="row">
                                    <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                    <div class="dropdown-menu">
                                        @if( hasPermission('merchant_payment_update') == true  )
                                            <a href="{{route('merchant.payment.edit',[$payment->merchant_id,$payment->id])}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                        @endif
                                        @if(  hasPermission('merchant_payment_delete') == true  )
                                            <form id="delete" value="Test" action="{{route('merchant.payment.delete',$payment->id)}}" method="POST" data-title="{{ __('delete.payment_account') }}">
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
