@extends('backend.partials.master')
@section('title')
    {{ __('hub_payment.title') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
<!-- wrapper  -->
<div class="container-fluid  dashboard-content">
    <!-- pageheader -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('hub.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <!-- data table  -->
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-8">
                        <p class="h3">{{ __('hub.title') }} {{__('hub_payment.payment')}} {{__('levels.list')}}</p>
                    </div>
                    @if(hasPermission('hub_payment_create') == true)
                    <div class="col-4">
                        <a href="{{route('hub.hub-payment.create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                    </div>
                    @endif
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('hub_payment.details') }}</th>
                                    <th>{{ __('hub_payment.transaction_id') }}</th>
                                    <th>{{ __('hub_payment.reference') }}</th>
                                    <th>{{ __('hub_payment.description') }}</th>
                                    <th>{{ __('hub_payment.amount') }}</th>
                                    <th>{{ __('levels.status') }}</th>

                                @if(
                                        hasPermission('hub_payment_reject') == true ||
                                        hasPermission('hub_payment_process') == true ||
                                        hasPermission('hub_payment_update') == true ||
                                        hasPermission('hub_payment_delete') == true
                                     )
                                    <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp

                                @foreach($payments as $payment)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>
                                        @if ($payment->frompayment !==null && $payment->frompayment->gateway == 1)
                                            Cash
                                        @elseif($payment->frompayment !==null && $payment->frompayment->gateway == 2)
                                            {{ @$payment->frompayment->account_holder_name}}<br/>
                                            {{ @$payment->frompayment->account_no }}<br/>
                                            {{ @$payment->frompayment->branch_name }}
                                        @elseif(
                                            $payment->frompayment !==null &&
                                            @$payment->frompayment->gateway == 3 ||
                                            @$payment->frompayment->gateway == 4 ||
                                            @$payment->frompayment->gateway == 5
                                        )
                                            @if($payment->frompayment->gateway == 3)
                                                Bkash
                                            @elseif($payment->frompayment->gateway == 4)
                                                Rocket
                                            @elseif($payment->frompayment->gateway == 5)
                                                Nagad
                                            @endif <br/>
                                            {{ @$payment->frompayment->account_holder_name}}<br/>
                                            {{ @$payment->frompayment->mobile }}<br/>
                                            @if($payment->frompayment->account_type == 1)
                                                Merchant
                                            @else
                                                Persional
                                            @endif
                                        @endif
                                    </td>
                                    <td>{{$payment->transaction_id}}</td>
                                    <td>@if(isset($payment->referencefile))<a href="{{ static_asset($payment->referencefile->original) }}" download="">Download</a>@endif</td>

                                    <td>{{\Str::limit($payment->description,100,' ...')}}</td>
                                    <td>{{settings()->currency}}{{$payment->amount}}</td>
                                    <td>
                                        @if($payment->status == \App\Enums\ApprovalStatus::REJECT)
                                        <span class="badge badge-pill badge-danger">{{trans('approvalstatus.'.\App\Enums\ApprovalStatus::REJECT) }}</span>
                                        @elseif($payment->status == \App\Enums\ApprovalStatus::PENDING)
                                        <span class="badge badge-pill badge-warning">{{trans('approvalstatus.'.\App\Enums\ApprovalStatus::PENDING) }}</span>
                                        @elseif($payment->status == \App\Enums\ApprovalStatus::PROCESSED)
                                        <span class="badge badge-pill badge-success">{{trans('approvalstatus.'.\App\Enums\ApprovalStatus::PROCESSED) }}</span>
                                        @endif
                                    </td>

                                    @if(
                                        hasPermission('hub_payment_reject') == true ||
                                        hasPermission('hub_payment_process') == true ||
                                        hasPermission('hub_payment_update') == true ||
                                        hasPermission('hub_payment_delete') == true
                                     )
                                        <td>
                                            @if ($payment->status == \App\Enums\ApprovalStatus::PROCESSED || $payment->status == \App\Enums\ApprovalStatus::REJECT )
                                                <div class="row">
                                                    <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                                    <div class="dropdown-menu">
                                                        @if ( $payment->status == \App\Enums\ApprovalStatus::PROCESSED)
                                                            <a href="{{route('hub-payment.cancel-process',$payment->id)}}" class="dropdown-item"><i class="fas fa-check" aria-hidden="true"></i> {{ __('levels.cancel_process') }}</a>
                                                        @else
                                                            <a href="{{route('hub-payment.cancel-reject',$payment->id)}}" class="dropdown-item"><i class="fas fa-ban" aria-hidden="true"></i> {{ __('levels.cancel_reject') }}</a>
                                                        @endif
                                                    </div>
                                                </div>
                                            @else
                                                <div class="row">
                                                    <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                                    <div class="dropdown-menu">
                                                        @if ( $payment->status == \App\Enums\ApprovalStatus::PENDING)
                                                            @if(  hasPermission('hub_payment_reject') == true  )
                                                                <a href="{{route('hub-payment.reject',$payment->id)}}" class="dropdown-item"><i class="fas fa-ban" aria-hidden="true"></i> {{ __('levels.reject') }}</a>
                                                            @endif
                                                            @if( hasPermission('hub_payment_process') == true  )
                                                                <a href="{{route('hub-payment.process',$payment->id)}}" class="dropdown-item"><i class="fas fa-check" aria-hidden="true"></i> {{ __('levels.process') }}</a>
                                                            @endif
                                                                @if( hasPermission('hub_payment_update') == true  )
                                                                    <a href="{{route('hub.hub-payment.edit',$payment->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                                @endif
                                                                @if(  hasPermission('hub_payment_delete') == true   )
                                                                    <form id="delete" value="Test" action="{{route('hub.hub-payment.delete',$payment->id)}}" method="POST" data-title="{{ __('delete.hub_payment') }}">
                                                                        @method('DELETE')
                                                                        @csrf
                                                                        <input type="hidden" name="" value="Payment" id="deleteTitle">
                                                                        <button type="submit" class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i> {{ __('levels.delete') }}</button>
                                                                    </form>
                                                                @endif
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>

                        </table>

                        <div class="px-3 d-flex flex-row-reverse align-items-center">
                            <span>{{ $payments->links() }}</span>
                            <p class="p-2 small">
                                {!! __('Showing') !!}
                                <span class="font-medium">{{ $payments->firstItem() }}</span>
                                {!! __('to') !!}
                                <span class="font-medium">{{ $payments->lastItem() }}</span>
                                {!! __('of') !!}
                                <span class="font-medium">{{ $payments->total() }}</span>
                                {!! __('results') !!}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end data table  -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()
