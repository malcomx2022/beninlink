@extends('backend.partials.master')
@section('title')
    {{ __('paymentrequest.title') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="{{ route('merchant-panel.payment-request.index') }}" class="breadcrumb-link">{{ __('paymentrequest.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-10">
                        <p class="h3">{{ __('paymentrequest.title') }} {{ __('levels.list') }}</p>
                    </div>
                    <div class="col-2">
                        <a href="{{ route('merchant-panel.payment-request.create') }}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('paymentrequest.account_details') }}</th>
                                    <th>{{ __('merchantmanage.transaction_id') }}</th>
                                    <th>{{ __('merchantmanage.description') }}</th>
                                    <th>{{ __('paymentrequest.request_date') }}</th>
                                    <th>{{ __('levels.status') }}</th>
                                    <th>{{ __('merchantmanage.amount') }}</th>
                                    <th>{{ __('levels.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($payments as $payment)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>
                                        <div class="w200">
                                            @if ($payment->merchantAccount->payment_method == 'bank')
                                                {{ $payment->merchantAccount->holder_name }}<br/>
                                                {{ $payment->merchantAccount->bank_name }}<br/>
                                                {{ $payment->merchantAccount->account_no }}<br/>
                                                {{ $payment->merchantAccount->branch_name }}<br/>
                                                {{ $payment->merchantAccount->routing_no }}<br/>
                                            @elseif ($payment->merchantAccount->payment_method == 'mobile')
                                                {{ $payment->merchantAccount->mobile_company }}<br/>
                                                {{ $payment->merchantAccount->mobile_no }}<br/>
                                                {{ $payment->merchantAccount->account_type }}<br/>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{$payment->transaction_id}}</td>
                                    <td>
                                        <div class="w200">
                                            {{$payment->description}}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="w250">
                                            {{ date('d M Y H:i:s a',strtotime($payment->created_at)) }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($payment->status == \App\Enums\ApprovalStatus::REJECT)
                                        <span class="badge badge-pill badge-danger">{{trans('approvalstatus.'.\App\Enums\ApprovalStatus::REJECT) }}</span>
                                        @elseif($payment->status == \App\Enums\ApprovalStatus::PENDING)
                                        <span class="badge badge-pill badge-warning">{{trans('approvalstatus.'.\App\Enums\ApprovalStatus::PENDING) }}</span>
                                        @elseif($payment->status == \App\Enums\ApprovalStatus::PROCESSED)
                                        <span class="badge badge-pill badge-success">{{trans('approvalstatus.'.\App\Enums\ApprovalStatus::PROCESSED) }}</span>
                                        @endif
                                    </td>
                                    <td>{{settings()->currency}}{{$payment->amount}}</td>
                                    <td>
                                        @if($payment->created_by == \App\Enums\UserType::MERCHANT && $payment->status == \App\Enums\ApprovalStatus::PENDING)
                                            <div class="row">
                                                <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                                <div class="dropdown-menu">
                                                    <a href="{{route('merchant-panel.payment-request.edit',$payment->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>

                                                    <form id="delete" value="Test" action="{{route('merchant-panel.payment-request.delete',$payment->id)}}" method="POST" data-title="{{ __('delete.payment_request') }}">
                                                        @method('DELETE')
                                                        @csrf
                                                        <input type="hidden" name="" value="Payment" id="deleteTitle">
                                                        <button type="submit" class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i> {{ __('levels.delete') }}</button>
                                                    </form>
                                                </div>
                                            </div>
                                        @else
                                        ...
                                        @endif
                                    </td>
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
    </div>
</div>
@endsection()


