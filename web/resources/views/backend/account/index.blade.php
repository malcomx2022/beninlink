@extends('backend.partials.master')
@section('title')
    {{ __('account.title') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="{{route('accounts.index')}}" class="breadcrumb-link">{{ __('account.title') }}</a></li>
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
                    <form action="{{route('accounts.filter')}}"  method="GET">
                        @csrf
                        <div class="row">
                            <div class="form-group col-12 col-xl-3 col-lg-4 col-md-6" >
                                <label for="holder_name">{{ __('levels.holder_name') }}</label>
                                <input type="text" id="holder_name" name="holder_name" placeholder="{{ __('placeholder.holder_name') }}"  class="form-control" value="{{old('holder_name', $request->holder_name)}}">
                                @error('holder_name')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group col-12 col-xl-3 col-lg-4 col-md-6">
                                <label for="account_no">{{ __('levels.account_no')}}</label> <span class="text-danger"></span>
                                <input type="text" id="account_no" name="account_no" placeholder="{{ __('placeholder.account_no') }}"  class="form-control" value="{{old('account_no', $request->account_no)}}">
                                @error('account_no')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group col-12 col-xl-3 col-lg-4 col-md-6">
                                <label for="bank">{{ __('levels.bank') }}</label> <span class="text-danger">*</span>
                                <select name="bank" class="form-control">
                                    <option selected disabled>{{ __('menus.select') }} {{ __('placeholder.Bank_name') }}</option>
                                    <option value = "1">BB</option>
                                    <option value = "2">DBBL</option>
                                    <option value = "3">IB</option>
                                </select>
                                @error('bank')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-xl-3 col-lg-4 col-md-6 pt-1">
                                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 pt-4 d-flex justify-content pl-0">
                                    <button type="submit" class="btn btn-space btn-primary"><i class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                    <a href="{{ route('accounts.index') }}" class="btn btn-space btn-secondary"><i class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-6">
                        <p class="h3">{{ __('account.title') }}</p>
                    </div>
                    @if(hasPermission('account_create') == true )
                    <div class="col-6">
                        <a href="{{route('accounts.create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                    </div>
                    @endif
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('levels.gateway') }}</th>
                                    <th colspan="2">{{ __('levels.account_info') }}</th>
                                    <th>{{ __('levels.status') }}</th>
                                    @if(hasPermission('account_update') == true || hasPermission('account_delete') == true )
                                    <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($accounts as $account)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>
                                        @if($account->gateway == 1)
                                            Cash
                                        @elseif($account->gateway == 2)
                                            Bank
                                        @elseif($account->gateway == 3)
                                            bKash
                                        @elseif($account->gateway == 4)
                                            Rocket
                                        @elseif($account->gateway == 5)
                                            Nagad
                                        @endif
                                    </td>
                                    <td colspan="2" >
                                        @if($account->user)
                                            <div class="d-flex width300px">
                                                <div class="width100px">
                                                    <img src="{{@$account->user->image}}" alt="user" class="rounded" width="40" height="40">
                                                </div>
                                                <div >
                                                    <strong>: {{@$account->user->name}}</strong>
                                                    <p>: {{@$account->user->email}}</p>
                                                </div>
                                            </div>
                                        <br/>
                                        @endif
                                        @if($account->account_holder_name != null)
                                            <div class="d-flex width300px">
                                                <div class="width100px "> Holder Name  </div>
                                                <div  class=" "> : {{$account->account_holder_name}} </div>
                                            </div>
                                        @endif
                                        @if($account->account_no != null)
                                            <div class="d-flex width300px">
                                                <div class="width100px">Account No. </div>
                                                <div  class="active"> : {{$account->account_no}} </div>
                                            </div>
                                        @endif
                                        @if($account->bank != null)
                                            <div class="d-flex">
                                                <div class="width100px"> Bank Name  </div>
                                                <div  class="">:
                                                    @if($account->bank == 1)
                                                        BB <br>
                                                    @elseif($account->bank == 2)
                                                        DBBL <br>
                                                    @elseif($account->bank == 3)
                                                        IB <br>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                        @if($account->branch_name != null)
                                            <div class="d-flex width300px">
                                                <div class="width100px"> Branch Name  </div>
                                                <div  class=""> : {{$account->branch_name}} </div>
                                            </div>
                                        @endif

                                        @if($account->mobile != null)
                                            <div class="d-flex width300px">
                                                <div class="width100px"> Mobile  </div>
                                                <div  class=""> : {{$account->mobile}} </div>
                                            </div>
                                        @endif
                                        @if($account->account_type != null)
                                            <div class="d-flex width300px">
                                                <div class="width100px"> Account Type  </div>
                                                <div  class=""> : {{$account->account_type == 1 ? 'Merchant' : 'Personal'}} </div>
                                            </div>
                                        @endif

                                        @if($account->opening_balance != null)
                                            <div class="d-flex width300px">
                                                <div class="width100px"> Opening  Balance  </div>
                                                <div  class="" > : {{settings()->currency}}{{$account->opening_balance}} </div>
                                            </div>
                                        @endif
                                        @if($account->balance != null)
                                            <div class="d-flex width300px">
                                                <div class="width100px"> Current Balance   </div>
                                                <div  class=""> : {{settings()->currency}}{{$account->balance}} </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{!! $account->my_status !!}</td>
                                    @if(hasPermission('account_update') == true || hasPermission('account_delete') == true )
                                    <td>
                                        <div class="row">
                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                            <div class="dropdown-menu">
                                                @if(hasPermission('account_update') == true )
                                                    <a href="{{route('accounts.edit',$account->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                @endif
                                                @if( hasPermission('account_delete') == true )
                                                    <form id="delete" value="Test" action="{{route('accounts.delete',$account->id)}}" method="POST" data-title="{{ __('delete.account') }}">
                                                        @method('DELETE')
                                                        @csrf
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


                <div class="col-12">
                    <div class="table-responsive">
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
        </div>
        <!-- end data table  -->
    </div>
</div>
<!-- end wrapper-->
@endsection()


