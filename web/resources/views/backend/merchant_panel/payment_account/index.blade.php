@extends('backend.partials.master')
@section('title')
    {{ __('merchant.payment_accounts') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('menus.accounts') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link ">{{ __('merchant.payment_accounts') }}</a></li>
                            <li class="breadcrumb-item active"><a href="" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
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
                    <div class="col-10">
                        <p class="h3">{{ __('merchant.payment_accounts') }}</p>
                    </div>
                    <div class="col-2">
                        <a href="{{ route('payment.account.create') }}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top"  title="{{ __('levels.add') }}"  >  <i class="fa fa-plus"></i>  </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table    " style="width:100%">
                            <thead>
                            <tr>
                                <th>{{ __('levels.id') }}</th>
                                <th>{{ __('merchant.payment_method') }}</th>
                                <th>{{ __('merchant.account_info') }}</th>
                                <th>{{ __('levels.actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php $i=1; @endphp
                            @foreach($accounts as $account)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>
                                        <div class="w150">
                                            {{__('merchant.'.$account->payment_method)}}
                                        </div>
                                    </td>
                                    <td class="merchantpayment">
                                        <div class="w200">
                                            <p>{{$account->bank_name}}</p>
                                            <p>{{$account->holder_name}}</p>
                                            <p>{{$account->account_no}}</p>
                                            <p>{{$account->branch_name}}</p>
                                            <p>{{$account->routing_no}}</p>
                                            <p>{{$account->mobile_company}}  </p>
                                            <p>{{$account->mobile_no}}</p>
                                            <p>{{$account->account_type}}</p>
                                            @if ($account->payment_method == \App\Enums\Merchant_panel\PaymentMethod::cash)
                                                <p>{{__('merchant.'.$account->payment_method)}}</p>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="row">
                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                            <div class="dropdown-menu">
                                                <a href="{{route('payment.account.edit',$account->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>

                                                <form id="delete" value="Test" action="{{route('payment.account.delete',$account->id)}}" method="POST" data-title="{{ __('delete.payment_account') }}">
                                                    @method('DELETE')
                                                    @csrf
                                                    <input type="hidden" name="" value="Account" id="deleteTitle">
                                                    <button type="submit" class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i> {{ __('levels.delete') }}</button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="px-3 d-flex flex-row-reverse align-items-center">
                    <span>{{ $accounts->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ $accounts->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $accounts->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ $accounts->total() }}</span>
                        {!! __('results') !!}
                    </p>
                </div>
            </div>
        </div>
        <!-- end data table  -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()

