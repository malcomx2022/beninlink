@extends('backend.partials.master')
@section('title')
    {{ __('merchantmanage.title') }} {{ __('merchantmanage.payment') }}  {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('merchantmanage.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('merchantmanage.payment') }}</a></li>
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
                <div class="card-body">
                    <form action="{{route('merchantmanage.payment.filter')}}"  method="GET">
                        @csrf
                        <div class="row">
                            <div class="form-group col-12 col-sm-6 col-md-6 col-lg-6 col-xl-2">
                                <label for="date">{{ __('parcel.date') }}</label>
                                <input type="text" autocomplete="off" id="date" name="date" class="form-control date_range_picker" value="{{ old('date',$request->date) }}" placeholder="Enter date">
                            </div>
                            <div class="form-group col-12 col-sm-6 col-md-6 col-lg-6 col-xl-2">
                                <label for="merchant">{{ __('merchant.title') }}</label>
                                <input id="mercant_url"  data-url="{{ route('merchant-manage.merchant-search') }}" type="hidden"/>
                                <select style="width: 100%" id="merchant" data-url="{{ route('merchant-manage.merchant.account') }}"   name="merchant_id" class="form-control"  >
                                    <option value="" > {{ __('menus.select') }} {{ __('merchant.title') }}</option>
                                    @if ($request->merchant_id)
                                        <option value="{{ $merchant->id }}"  selected > {{$merchant->business_name}}  </option>
                                    @endif
                                </select>
                            </div>
                            <div class="form-group col-12 col-sm-6 col-md-6 col-lg-6  col-xl-3">
                                <label for="merchant_account">{{ __('merchantmanage.merchant_account') }}</label>
                                <select style="width: 100%" id="merchant_account"  name="merchant_account" class="form-control"  >
                                    <option value="" > {{ __('menus.select') }} {{ __('merchantmanage.merchant_account') }}</option>
                                    @if(isset($merchantaccounts))
                                        @foreach ($merchantaccounts as $maccounts)
                                            @if($maccounts->payment_method == 'bank')
                                                <option value='{{ $maccounts->id }}' @if($maccounts->id == $request->merchant_account) selected @endif> {{ $maccounts->holder_name  }} | {{ $maccounts->bank_name }} | {{ $maccounts->account_no }} | {{ $maccounts->branch_name}}</option>
                                            @elseif($maccounts->payment_method == 'mobile'){
                                                <option value='{{ $maccounts->id }}' @if($maccounts->id == $request->merchant_account) selected @endif>{{ $maccounts->mobile_company }} | {{ $maccounts->mobile_no }} | {{ $maccounts->account_type }}</option>
                                            @elseif($maccounts->payment_method == 'cash')
                                                <option value='{{ $maccounts->id }}' @if($maccounts->id == $request->merchant_account) selected @endif> Cash </option>
                                            @endif
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="form-group col-12 col-sm-6 col-md-6 col-lg-6  col-xl-3">
                                <label for="hub_id">{{ __('levels.from_account') }}</label>
                                <select style="width: 100%" id="hub_id"  name="from_account" class="form-control"  >
                                    <option value="" > {{ __('menus.select') }} {{ __('levels.from_account') }}</option>
                                    @foreach ($accounts as $account)
                                        @if ($account->gateway == 1)
                                            <option value="{{ $account->id }}" @if($request->from_account == $account->id) selected @endif>{{ $account->user->name }} | {{ __('merchant.cash') }} : {{ $account->balance }} </option>
                                        @elseif($account->gateway == 3 || $account->gateway == 4 || $account->gateway == 5)
                                            <option value="{{ $account->id }}" @if($request->from_account == $account->id) selected @endif>{{$account->account_holder_name}} |No : {{ $account->mobile }}|  @if($account->account_type == 1) {{ __('merchant.title') }} @else {{ __('placeholder.persional') }} @endif | {{ __('merchantmanage.current_balance') }}: {{ $account->balance }} </option>
                                        @else
                                            <option value="{{ $account->id }}" @if($request->from_account == $account->id) selected @endif>{{$account->account_holder_name}} | A.No : {{ $account->account_no }} | {{ __('merchantmanage.current_balance') }}: {{ $account->balance }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-12 col-sm-6 col-md-6 col-lg-6 col-xl-2 pt-1">
                                <div class="col-12 pt-4 d-flex justify-content pl-0">
                                    <button type="submit" class="btn btn-sm btn-space btn-primary"><i class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                    <a href="{{ url('admin/payment/index') }}" class="btn btn-sm btn-space btn-secondary"><i class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-10">
                        <p class="h3">{{ __('merchantmanage.merchant_payment_manage') }}</p>
                    </div>
                    @if(hasPermission('payment_create') == true)
                    <div class="col-2">
                        <a href="{{route('merchant-manage.payment.create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                    </div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('merchantmanage.merchant_details') }}</th>
                                    <th>{{ __('merchantmanage.transaction_id') }}</th>
                                    <th>{{ __('merchantmanage.from_account') }}</th>
                                    <th>{{ __('merchantmanage.reference') }}</th>
                                    <th>{{ __('merchantmanage.description') }}</th>
                                    <th>{{ __('levels.status') }}</th>
                                    <th>{{ __('merchantmanage.amount') }}</th>
                                    @if(
                                        hasPermission('payment_reject') == true ||
                                        hasPermission('payment_process') == true ||
                                        hasPermission('payment_update') == true ||
                                        hasPermission('payment_delete') == true
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
                                        <div class="row">
                                            <div class=" col-4">
                                                <img src=" {{$payment->merchant->user->image}}" alt="user" class="rounded" width="40" height="40">
                                            </div>
                                            <div class="col-8">
                                                <strong> : {{$payment->merchant->user->name}}</strong>
                                                <p> : {{$payment->merchant->user->email}}</p>
                                            </div>
                                        </div>
                                        <br/>
                                        <div class="row">
                                            <div class="col-4"> Business name </div>
                                            <div class="col-8"> : {{ @$payment->merchant->business_name }} </div>
                                        </div>
                                        @if ($payment->merchantAccount !==null && $payment->merchantAccount->payment_method == 'bank')
                                            <div class="row">
                                                <div class="col-4"> Holder </div>
                                                <div class="col-8"> : {{ $payment->merchantAccount->holder_name }} </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-4"> Bank </div>
                                                <div class="col-8"> : {{ $payment->merchantAccount->bank_name }} </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-4"> Acc. No. </div>
                                                <div class="col-8"> : {{ $payment->merchantAccount->account_no }} </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-4"> Branch </div>
                                                <div class="col-8"> : {{ $payment->merchantAccount->branch_name }} </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-4"> Routing No. </div>
                                                <div class="col-8"> : {{ $payment->merchantAccount->routing_no }} </div>
                                            </div>
                                        @elseif ($payment->merchantAccount !==null &&  $payment->merchantAccount->payment_method == 'mobile')
                                            {{-- mobile --}}
                                            <div class="row">
                                                <div class="col-4"> Compnay  </div>
                                                <div class="col-8"> : {{ $payment->merchantAccount->mobile_company }} </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-4"> Mobile  </div>
                                                <div class="col-8"> : {{ $payment->merchantAccount->mobile_no }} </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-4"> Type  </div>
                                                <div class="col-8"> : {{ $payment->merchantAccount->account_type }} </div>
                                            </div>
                                        @elseif ($payment->merchantAccount !==null &&  $payment->merchantAccount->payment_method == 'cash')
                                            <div class="row">
                                                <div class="col-4"> Payment method  </div>
                                                <div class="col-8"> :  Cash </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{$payment->transaction_id}}</td>
                                    <td>
                                        @if ($payment->frompayment !==null && $payment->frompayment->gateway == 1)
                                           {{ @$payment->frompayment->user->name }} ( Cash)
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
                                    <td><a href="@if($payment->referencefile !==null){{ static_asset($payment->referencefile->original) }}@endif" download="">Download</a></td>

                                    <td>{{$payment->description}}</td>
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
                                    @if(
                                        hasPermission('payment_reject') == true ||
                                        hasPermission('payment_process') == true ||
                                        hasPermission('payment_update') == true ||
                                        hasPermission('payment_delete') == true
                                     )
                                        <td>
                                            @if ($payment->status == \App\Enums\ApprovalStatus::PROCESSED || $payment->status == \App\Enums\ApprovalStatus::REJECT )
                                                <div class="row">
                                                    <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                                    <div class="dropdown-menu">
                                                        @if ( $payment->status == \App\Enums\ApprovalStatus::PROCESSED)
                                                            <a href="{{route('merchantmanage.payment.cancel-process',$payment->id)}}" class="dropdown-item"><i class="fas fa-check" aria-hidden="true"></i> {{ __('levels.cancel_process') }}</a>
                                                        @else
                                                            <a href="{{route('merchantmanage.payment.cancel-reject',$payment->id)}}" class="dropdown-item"><i class="fas fa-ban" aria-hidden="true"></i> {{ __('levels.cancel_reject') }}</a>
                                                        @endif
                                                    </div>
                                                </div>
                                            @else
                                                <div class="row">
                                                    <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                                    <div class="dropdown-menu">
                                                        @if ( $payment->status == \App\Enums\ApprovalStatus::PENDING)
                                                            @if(  hasPermission('payment_reject') == true  )
                                                                <a href="{{route('merchantmanage.payment.reject',$payment->id)}}" class="dropdown-item"><i class="fas fa-ban" aria-hidden="true"></i> {{ __('levels.reject') }}</a>
                                                            @endif
                                                            @if( hasPermission('payment_process') == true  )
                                                                <a href="{{route('merchantmanage.payment.process',$payment->id)}}" class="dropdown-item"><i class="fas fa-check" aria-hidden="true"></i> {{ __('levels.process') }}</a>
                                                            @endif
                                                        @endif
                                                        @if( hasPermission('payment_update') == true  )
                                                            <a href="{{route('merchatmanage.payment.edit',$payment->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                        @endif
                                                        @if(  hasPermission('payment_delete') == true   )
                                                            <form id="delete" value="Test" action="{{route('merchantmanage.payment.delete',$payment->id)}}" method="POST" data-title="{{ __('delete.payment') }}">
                                                                @method('DELETE')
                                                                @csrf
                                                                <input type="hidden" name="" value="Payment" id="deleteTitle">
                                                                <button type="submit" class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i> {{ __('levels.delete') }}</button>
                                                            </form>
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
                            <span>{{ $payments->appends($request->all())->links() }}</span>
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
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ static_asset('backend/js/merchantmanaage/create.js') }}"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript" src="{{ static_asset('backend/js/date-range-picker/date-range-picker-custom.js') }}"></script>
@endpush


